<?php
/**
 * STAGEKITWP_IMPORT_EXPORT_Purger – bulk delete posts from StageKitWP ecosystem CPTs.
 *
 * Three modes
 * ───────────
 * 1. Full ecosystem  – every CPT across every TM plugin
 * 2. By plugin       – all CPTs owned by one plugin group
 * 3. By CPT          – one specific post type
 *
 * All deletes are permanent (force-delete, bypass trash).
 * A dry-run mode returns counts without touching the DB.
 *
 * @package STAGEKITWP_IO
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class STAGEKITWP_IMPORT_EXPORT_Purger {

	// ── CPT registry ──────────────────────────────────────────────────────────

	/**
	 * Every stagekitwp-ecosystem CPT, grouped by the plugin that registers it.
	 * Key   = plugin group slug (used in AJAX calls).
	 * Value = [ label, cpts[] ]
	 *
	 * @var array<string, array{label:string, cpts:string[]}>
	 */
	public const PLUGIN_GROUPS = [
		'stagekitwp' => [
			'label' => 'StageKitWP Core',
			'cpts'  => [
				'show',
				'season',
				'cast',
				'venue',
				'award',
				'board_member',
				'contributor',
				'sponsor',
				'testimonial',
				'advertiser',
			],
		],
		'stagekitwp-members-area' => [
			'label' => 'Members Area',
			'cpts'  => [
				'stagekitwp_ann',
				'stagekitwp_event',
				'stagekitwp_conv',
				'stagekitwp_email',
			],
		],
		'stagekitwp-rc-library' => [
			'label' => 'RC Library',
			'cpts'  => [
				'stagekitwp_rubric',
				'stagekitwp_template',
				'stagekitwp_book',
			],
		],
	];

	/** Human-readable labels for individual CPTs. */
	public const CPT_LABELS = [
		'show'               => 'Shows',
		'season'             => 'Seasons',
		'cast'               => 'Cast',
		'venue'              => 'Venues',
		'award'              => 'Awards',
		'board_member'       => 'Board Members',
		'contributor'        => 'Contributors',
		'sponsor'            => 'Sponsors',
		'testimonial'        => 'Testimonials',
		'advertiser'         => 'Advertisers',
		'stagekitwp_ann'    => 'Announcements',
		'stagekitwp_event'           => 'Events',
		'stagekitwp_conv'    => 'Conversations',
		'stagekitwp_email'  => 'Email Templates',
		'stagekitwp_rubric'     => 'Rubric Items',
		'stagekitwp_template' => 'Rubric Templates',
		'stagekitwp_book'            => 'Books & Scripts',
	];

	// ── Public API ────────────────────────────────────────────────────────────

	/**
	 * Count posts for a list of CPTs without deleting anything.
	 *
	 * @param  string[] $post_types
	 * @return array<string, int>  post_type => count
	 */
	public static function count( array $post_types ): array {
		global $wpdb;
		$counts = [];
		foreach ( $post_types as $pt ) {
			// Direct query avoids WP_Query overhead & SQLite parser issues.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$n = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->posts}
					 WHERE post_type = %s
					   AND post_status NOT IN ('auto-draft')",
					$pt
				)
			);
			$counts[ $pt ] = $n;
		}
		return $counts;
	}

	/**
	 * Delete all posts for the given CPTs.
	 *
	 * Returns a per-CPT result array and an errors list.
	 *
	 * @param  string[] $post_types
	 * @param  bool     $dry_run    When true, count only – no deletes.
	 * @return array{results:array<string,int>, errors:string[], total_deleted:int}
	 */
	public static function purge( array $post_types, bool $dry_run = false ): array {
		$results       = [];
		$errors        = [];
		$total_deleted = 0;

		foreach ( $post_types as $pt ) {
			$deleted = 0;
			$errs    = 0;

			if ( $dry_run ) {
				$counts        = self::count( [ $pt ] );
				$results[ $pt ] = $counts[ $pt ];
				continue;
			}

			// Fetch IDs in batches of 200 to avoid memory exhaustion.
			do {
				$ids = self::get_batch_ids( $pt, 200 );
				if ( empty( $ids ) ) { break; }
				foreach ( $ids as $id ) {
					$result = wp_delete_post( $id, true ); // force-delete
					if ( $result ) {
						$deleted++;
						$total_deleted++;
					} else {
						$errs++;
					}
				}
			} while ( count( $ids ) === 200 ); // loop until fewer than a full batch

			$results[ $pt ] = $deleted;
			if ( $errs > 0 ) {
				$errors[] = sprintf(
					/* translators: 1: post type, 2: count */
					__( '%1$s: %2$d posts could not be deleted.', 'stagekitwp-io' ),
					self::CPT_LABELS[ $pt ] ?? $pt,
					$errs
				);
			}
		}

		return compact( 'results', 'errors', 'total_deleted' );
	}

	/**
	 * Delete a single, time-boxed batch of posts across the given CPTs.
	 *
	 * Designed to be called repeatedly over AJAX so no single HTTP request runs
	 * long enough to hit PHP's max_execution_time. Each call deletes at most
	 * $batch_size posts (across all supplied CPTs, drained in order) and returns
	 * the running totals plus a `done` flag and the remaining count so the client
	 * can render a progress bar and decide whether to request the next batch.
	 *
	 * @param  string[] $post_types
	 * @param  int      $batch_size  Max posts to delete in this call (default 50).
	 * @return array{
	 *   deleted_this_batch:int,
	 *   deleted_by_type:array<string,int>,
	 *   remaining:int,
	 *   remaining_by_type:array<string,int>,
	 *   errors:string[],
	 *   done:bool
	 * }
	 */
	public static function purge_batch( array $post_types, int $batch_size = 50 ): array {
		$batch_size        = max( 1, $batch_size );
		$deleted_this_batch = 0;
		$deleted_by_type   = [];
		$errors            = [];
		$err_counts        = [];

		foreach ( $post_types as $pt ) {
			if ( $deleted_this_batch >= $batch_size ) {
				break; // Batch budget spent — stop; the next call resumes here.
			}

			$remaining_budget = $batch_size - $deleted_this_batch;
			$ids              = self::get_batch_ids( $pt, $remaining_budget );
			if ( empty( $ids ) ) {
				continue;
			}

			foreach ( $ids as $id ) {
				$result = wp_delete_post( $id, true ); // force-delete, bypass trash
				if ( $result ) {
					$deleted_this_batch++;
					$deleted_by_type[ $pt ] = ( $deleted_by_type[ $pt ] ?? 0 ) + 1;
				} else {
					$err_counts[ $pt ] = ( $err_counts[ $pt ] ?? 0 ) + 1;
				}
			}
		}

		// What's left after this batch — drives the progress bar and the loop.
		$remaining_by_type = self::count( $post_types );
		$remaining         = array_sum( $remaining_by_type );

		foreach ( $err_counts as $pt => $n ) {
			$errors[] = sprintf(
				/* translators: 1: post type label, 2: count */
				__( '%1$s: %2$d posts could not be deleted.', 'stagekitwp-io' ),
				self::CPT_LABELS[ $pt ] ?? $pt,
				$n
			);
		}

		// If everything remaining is undeletable (only errors this pass, nothing
		// deleted, but posts remain), treat as done to avoid an infinite loop.
		$stalled = ( 0 === $deleted_this_batch && $remaining > 0 );

		return [
			'deleted_this_batch' => $deleted_this_batch,
			'deleted_by_type'    => $deleted_by_type,
			'remaining'          => $remaining,
			'remaining_by_type'  => $remaining_by_type,
			'errors'             => $errors,
			'done'               => ( 0 === $remaining ) || $stalled,
		];
	}

	/**
	 * Resolve a scope string into a list of CPT slugs.
	 *
	 * @param  string $scope  'all' | plugin-group slug | individual CPT slug
	 * @return string[]|\WP_Error
	 */
	public static function resolve_scope( string $scope ): array|\WP_Error {
		if ( 'all' === $scope ) {
			$cpts = [];
			foreach ( self::PLUGIN_GROUPS as $group ) {
				$cpts = array_merge( $cpts, $group['cpts'] );
			}
			return $cpts;
		}
		if ( isset( self::PLUGIN_GROUPS[ $scope ] ) ) {
			return self::PLUGIN_GROUPS[ $scope ]['cpts'];
		}
		if ( isset( self::CPT_LABELS[ $scope ] ) ) {
			return [ $scope ];
		}
		return new \WP_Error(
			'stagekitwp_import_export_purge_bad_scope',
			sprintf( __( 'Unknown scope "%s".', 'stagekitwp-io' ), esc_html( $scope ) )
		);
	}

	// ── Helpers ───────────────────────────────────────────────────────────────

	/**
	 * Fetch up to $limit post IDs for a given post type using a direct query.
	 *
	 * @param  string $post_type
	 * @param  int    $limit
	 * @return int[]
	 */
	private static function get_batch_ids( string $post_type, int $limit ): array {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts}
				 WHERE post_type = %s
				   AND post_status NOT IN ('auto-draft')
				 LIMIT %d",
				$post_type,
				$limit
			)
		);
		return array_map( 'intval', (array) $ids );
	}
}
