<?php
/**
 * STAGEKITWP_IMPORT_EXPORT_Queue – hybrid async job runner.
 *
 * Priority order:
 *   1. Action Scheduler (used by WooCommerce, very reliable) – if available.
 *   2. Custom WP-Cron batch job as fallback.
 *
 * Each import job is stored as a transient keyed by job ID. The queue processes
 * one module at a time, in groups of stagekitwp_import_export_batch_size() posts (configurable on
 * TM I/O → Settings; falls back to BATCH_SIZE).
 * Progress is written back to a separate transient so the admin UI can poll it.
 *
 * @package STAGEKITWP_IO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class STAGEKITWP_IMPORT_EXPORT_Queue {

	private const CRON_HOOK    = 'stagekitwp_import_export_process_batch';
	private const BATCH_SIZE   = 50;
	private const AS_GROUP     = 'stagekitwp-io';

	// ── Install / teardown ────────────────────────────────────────────────────

	/**
	 * Called on plugin activation.
	 * Creates the WP-Cron schedule if Action Scheduler isn't present.
	 */
	public function install_tables(): void {
		// Nothing to create for WP-Cron; hook registration is enough.
		add_action( self::CRON_HOOK, [ $this, 'process_cron_batch' ] );
	}

	/**
	 * Called on deactivation.
	 */
	public function clear_scheduled_hooks(): void {
		wp_clear_scheduled_hook( self::CRON_HOOK );
	}

	// ── Enqueue ───────────────────────────────────────────────────────────────

	/**
	 * Enqueue a background import job by job ID.
	 * The job payload must already be stored as a transient (stagekitwp_import_export_job_{job_id}).
	 *
	 * @param string $job_id
	 */
	public function enqueue( string $job_id ): void {
		$this->init_progress( $job_id );
		$this->store_total_from_job( $job_id );

		if ( $this->has_action_scheduler() ) {
			as_enqueue_async_action(
				self::CRON_HOOK,
				[ 'job_id' => $job_id ],
				self::AS_GROUP
			);
		} else {
			// Schedule a one-off WP-Cron event 10 seconds from now.
			wp_schedule_single_event( time() + 10, self::CRON_HOOK, [ $job_id ] );
		}
	}

	// ── Process ───────────────────────────────────────────────────────────────

	/**
	 * Process one batch of a queued import job.
	 * Hooked to `stagekitwp_import_export_process_batch` by both Action Scheduler and WP-Cron.
	 *
	 * @param string $job_id
	 */
	/**
	 * Process one batch on demand and return the resulting progress.
	 *
	 * This is called by the progress-poll AJAX handler so the import advances
	 * every time the browser polls — a reliable fallback for environments where
	 * WP-Cron / Action Scheduler do not fire promptly (e.g. local dev servers).
	 * It is idempotent and safe to call alongside the cron/AS runner: whichever
	 * runs first advances the offset, the other simply sees updated state.
	 *
	 * @param string $job_id
	 * @return array<string,mixed> Current progress after processing one batch.
	 */
	public function process_next_batch( string $job_id ): array {
		$progress = $this->get_progress( $job_id );
		if ( in_array( $progress['status'] ?? '', [ 'complete', 'failed' ], true ) ) {
			return $progress;
		}

		// Process one real post-batch per poll, but roll through any empty /
		// options-only modules in the same call so the tail doesn't stall the bar.
		$guard = 0;
		do {
			$before_processed = (int) ( $progress['imported'] ?? 0 ) + (int) ( $progress['skipped'] ?? 0 );
			$this->process_cron_batch( $job_id );
			$progress = $this->get_progress( $job_id );
			$after_processed = (int) ( $progress['imported'] ?? 0 ) + (int) ( $progress['skipped'] ?? 0 );
			// Stop once this pass actually imported/skipped posts, or the job ended.
			if ( $after_processed > $before_processed ) {
				break;
			}
			if ( in_array( $progress['status'] ?? '', [ 'complete', 'failed' ], true ) ) {
				break;
			}
		} while ( ++$guard < 25 );

		return $progress;
	}

	public function process_cron_batch( string $job_id ): void {
		// Acquire a short-lived processing lock so overlapping runners (Action
		// Scheduler, the reschedule chain, AND the browser progress-poll) can never
		// process the same batch at the same offset — the cause of duplicate imports.
		if ( ! $this->acquire_lock( $job_id ) ) {
			return; // another runner holds the lock; it will advance the offset.
		}

		try {
			$this->process_cron_batch_locked( $job_id );
		} finally {
			$this->release_lock( $job_id );
		}
	}

	/**
	 * The actual batch worker, guaranteed to run under the per-job lock.
	 *
	 * @param string $job_id
	 */
	private function process_cron_batch_locked( string $job_id ): void {
		$job = get_transient( 'stagekitwp_import_export_job_' . $job_id );
		if ( ! $job ) {
			$this->fail_progress( $job_id, __( 'Job data expired or not found.', 'stagekitwp-io' ) );
			return;
		}

		$payloads = $job['payloads'];
		$conflict = $job['conflict'];
		$progress = $this->get_progress( $job_id );

		// Import media FIRST (before any module) so post batches can be remapped
		// to the newly-imported attachments. Ordered so '_media' comes first.
		$modules = array_keys( $payloads );
		if ( in_array( '_media', $modules, true ) ) {
			$modules = array_merge( [ '_media' ], array_values( array_diff( $modules, [ '_media' ] ) ) );
		}

		// Find the current module to process.
		$done_modules  = $progress['done_modules'] ?? [];
		$pending       = array_diff( $modules, $done_modules );

		if ( empty( $pending ) ) {
			// Finalize: remap cross-post relationship meta now that all posts exist.
			$post_id_map = $job['post_id_map'] ?? [];
			if ( $post_id_map ) {
				foreach ( array_keys( $payloads ) as $mid ) {
					if ( is_string( $mid ) && str_starts_with( $mid, '_' ) ) {
						continue;
					}
					$m = stagekitwp_io()->module( $mid );
					if ( $m ) {
						$m->remap_relationships( $post_id_map );
					}
				}
			}
			$this->complete_progress( $job_id );
			delete_transient( 'stagekitwp_import_export_job_' . $job_id );
			return;
		}

		$current_id = reset( $pending );

		// The media pseudo-module: import all attachments now and stash the
		// old→new maps on the job so later batches can remap references.
		if ( '_media' === $current_id ) {
			stagekitwp_io()->importer()->import_media_public( $payloads['_media'], $job_id );
			$this->advance_module( $job_id, $current_id, [ 'skipped' => 0, 'imported' => 0, 'errors' => [] ] );
			$this->reschedule( $job_id );
			return;
		}

		// Skip any other pseudo-modules.
		if ( is_string( $current_id ) && str_starts_with( $current_id, '_' ) ) {
			$this->advance_module( $job_id, $current_id, [ 'skipped' => 0, 'imported' => 0, 'errors' => [] ] );
			$this->reschedule( $job_id );
			return;
		}

		$module = stagekitwp_io()->module( $current_id );

		if ( ! $module ) {
			// Module not available on this site – mark done and continue.
			$this->advance_module( $job_id, $current_id, [ 'skipped' => 0, 'imported' => 0, 'errors' => [] ] );
			$this->reschedule( $job_id );
			return;
		}

		$payload      = $payloads[ $current_id ];
		$posts        = $payload['posts']   ?? [];
		$options      = $payload['options'] ?? [];
		$extra        = $payload['extra']   ?? [];
		$offset       = (int) ( $progress['module_offset'] ?? 0 );

		// Options-only / empty module: apply its options + extra and advance in
		// this same pass so it doesn't consume a whole poll for zero posts.
		if ( empty( $posts ) ) {
			if ( $options ) { $module->import_options( $options ); }
			if ( $extra )   { $module->import_extra( $extra ); }
			$this->advance_module( $job_id, $current_id, [ 'skipped' => 0, 'imported' => 0, 'errors' => [] ] );
			$this->reschedule( $job_id );
			return;
		}
		$batch_size   = function_exists( 'stagekitwp_import_export_batch_size' ) ? stagekitwp_import_export_batch_size() : self::BATCH_SIZE;
		$batch        = array_slice( $posts, $offset, $batch_size );

		// Seed the importer's media maps from the job, then remap this batch so
		// featured images, image meta, and inline URLs point at imported media.
		$importer = stagekitwp_io()->importer();
		$importer->set_media_maps( $job['media_id_map'] ?? [], $job['media_url_map'] ?? [] );
		$remapped = $importer->remap_payload_media( [ 'posts' => $batch ] );
		$batch    = $remapped['posts'];

		// Process this batch of posts.
		$batch_summary = $module->import(
			[ 'posts' => $batch, 'options' => [], 'extra' => [] ],
			[ 'conflict' => $conflict ]
		);

		// Accumulate old→new post IDs on the job so relationships (show→season,
		// etc.) can be remapped once every post has been imported.
		if ( ! empty( $batch_summary['id_map'] ) ) {
			$job = get_transient( 'stagekitwp_import_export_job_' . $job_id );
			if ( is_array( $job ) ) {
				$job['post_id_map'] = ( $job['post_id_map'] ?? [] ) + $batch_summary['id_map'];
				set_transient( 'stagekitwp_import_export_job_' . $job_id, $job, 10 * MINUTE_IN_SECONDS );
			}
		}

		// On first batch, also apply options + extra.
		if ( 0 === $offset ) {
			if ( $options ) {
				$module->import_options( $options );
			}
			if ( $extra ) {
				$module->import_extra( $extra );
			}
		}

		$next_offset = $offset + count( $batch );
		$total_posts = count( $posts );

		if ( $next_offset >= $total_posts ) {
			// Module finished.
			$this->advance_module( $job_id, $current_id, $batch_summary );
		} else {
			// More posts remain for this module.
			$this->update_module_offset( $job_id, $next_offset, $batch_summary );
		}

		// Reschedule to process the next batch / module.
		$this->reschedule( $job_id );
	}

	// ── Progress ──────────────────────────────────────────────────────────────

	/**
	 * Read job progress from transient.
	 *
	 * @param string $job_id
	 * @return array<string,mixed>
	 */
	public function get_progress( string $job_id ): array {
		return get_transient( 'stagekitwp_import_export_progress_' . $job_id ) ?: [
			'status'        => 'pending',
			'done_modules'  => [],
			'module_offset' => 0,
			'imported'      => 0,
			'skipped'       => 0,
			'errors'        => [],
			'percent'       => 0,
			'total'         => 0,
			'processed'     => 0,
		];
	}

	private function init_progress( string $job_id ): void {
		set_transient( 'stagekitwp_import_export_progress_' . $job_id, [
			'status'        => 'queued',
			'done_modules'  => [],
			'module_offset' => 0,
			'imported'      => 0,
			'skipped'       => 0,
			'errors'        => [],
			'percent'       => 0,
			'total'         => 0,
			'processed'     => 0,
		], HOUR_IN_SECONDS );
	}

	/**
	 * Compute and store the total post count for a job so we can report a
	 * meaningful percentage while it runs.
	 *
	 * @param string $job_id
	 */
	private function store_total_from_job( string $job_id ): void {
		$job = get_transient( 'stagekitwp_import_export_job_' . $job_id );
		if ( ! $job || empty( $job['payloads'] ) ) {
			return;
		}
		$total = 0;
		foreach ( $job['payloads'] as $mid => $payload ) {
			if ( is_string( $mid ) && str_starts_with( $mid, '_' ) ) {
				continue; // skip _media and other pseudo-modules
			}
			$total += count( $payload['posts'] ?? [] );
		}
		$progress          = $this->get_progress( $job_id );
		$progress['total'] = $total;
		$this->save_progress( $job_id, $progress );
	}

	/**
	 * Recalculate the completion percentage from processed vs total posts.
	 *
	 * @param array<string,mixed> $progress
	 * @return array<string,mixed>
	 */
	private function recompute_percent( array $progress ): array {
		$total     = (int) ( $progress['total'] ?? 0 );
		$processed = (int) ( $progress['imported'] ?? 0 ) + (int) ( $progress['skipped'] ?? 0 );
		$progress['processed'] = $processed;
		if ( $total > 0 ) {
			// Cap at 99% until the job is explicitly marked complete.
			$progress['percent'] = min( 99, (int) floor( ( $processed / $total ) * 100 ) );
		}
		return $progress;
	}

	private function advance_module( string $job_id, string $module_id, array $batch_summary ): void {
		$progress = $this->get_progress( $job_id );
		$progress['done_modules'][]  = $module_id;
		$progress['module_offset']   = 0;
		$progress['imported']       += $batch_summary['imported'] ?? 0;
		$progress['skipped']        += $batch_summary['skipped']  ?? 0;
		$progress['errors']          = array_merge( $progress['errors'], $batch_summary['errors'] ?? [] );
		$progress['status']          = 'processing';
		$progress                    = $this->recompute_percent( $progress );
		$this->save_progress( $job_id, $progress );
	}

	private function update_module_offset( string $job_id, int $offset, array $batch_summary ): void {
		$progress = $this->get_progress( $job_id );
		$progress['module_offset']  = $offset;
		$progress['imported']      += $batch_summary['imported'] ?? 0;
		$progress['skipped']       += $batch_summary['skipped']  ?? 0;
		$progress['errors']         = array_merge( $progress['errors'], $batch_summary['errors'] ?? [] );
		$progress['status']         = 'processing';
		$progress                   = $this->recompute_percent( $progress );
		$this->save_progress( $job_id, $progress );
	}

	private function complete_progress( string $job_id ): void {
		$progress              = $this->get_progress( $job_id );
		$progress['status']    = 'complete';
		$progress['percent']   = 100;
		$progress['processed'] = (int) ( $progress['total'] ?? $progress['processed'] ?? 0 );
		$this->save_progress( $job_id, $progress );
	}

	private function fail_progress( string $job_id, string $message ): void {
		$progress             = $this->get_progress( $job_id );
		$progress['status']   = 'failed';
		$progress['errors'][] = $message;
		$this->save_progress( $job_id, $progress );
	}

	private function save_progress( string $job_id, array $progress ): void {
		set_transient( 'stagekitwp_import_export_progress_' . $job_id, $progress, HOUR_IN_SECONDS );
	}

	// ── Reschedule ────────────────────────────────────────────────────────────

	private function reschedule( string $job_id ): void {
		if ( $this->has_action_scheduler() ) {
			as_enqueue_async_action(
				self::CRON_HOOK,
				[ 'job_id' => $job_id ],
				self::AS_GROUP
			);
		} else {
			wp_schedule_single_event( time() + 5, self::CRON_HOOK, [ $job_id ] );
		}
	}

	// ── Helpers ───────────────────────────────────────────────────────────────

	private function has_action_scheduler(): bool {
		return function_exists( 'as_enqueue_async_action' );
	}

	// ── Processing lock ─────────────────────────────────────────────────

	private const LOCK_TTL = 120; // seconds a stale lock is honored before takeover.

	/**
	 * Try to acquire an exclusive per-job lock.
	 *
	 * Uses a direct, conditional SQL insert into wp_options as the atomic
	 * primitive (add_option is not race-safe under all object caches). Returns
	 * true only for the caller that actually created the row; a lock older than
	 * LOCK_TTL is treated as stale and forcibly taken over.
	 *
	 * @param string $job_id
	 * @return bool
	 */
	private function acquire_lock( string $job_id ): bool {
		global $wpdb;
		$name = $this->lock_name( $job_id );
		$now  = time();

		// Atomic insert: succeeds for exactly one racer.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL
		$inserted = $wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$wpdb->options} (option_name, option_value, autoload)
				 SELECT %s, %s, 'no'
				 WHERE NOT EXISTS ( SELECT 1 FROM {$wpdb->options} WHERE option_name = %s )",
				$name, (string) $now, $name
			)
		);

		if ( $inserted ) {
			return true;
		}

		// Row exists — take over only if it's stale.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$held = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1", $name )
		);
		if ( $held && ( $now - $held ) < self::LOCK_TTL ) {
			return false; // fresh lock held by another runner.
		}

		// Stale: atomically claim it by CAS-updating the timestamp.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL
		$claimed = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s AND option_value = %s",
				(string) $now, $name, (string) $held
			)
		);

		return (bool) $claimed;
	}

	private function release_lock( string $job_id ): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->delete( $wpdb->options, [ 'option_name' => $this->lock_name( $job_id ) ] );
	}

	private function lock_name( string $job_id ): string {
		return 'stagekitwp_import_export_lock_' . $job_id;
	}
}
