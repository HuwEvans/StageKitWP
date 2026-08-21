<?php
/**
 * STAGEKITWP_IMPORT_EXPORT_Module – abstract base class for all TM IO modules.
 *
 * Every module (stagekitwp, stagekitwp-members-area, etc.) extends this class
 * and overrides the abstract methods to declare:
 *   1. Which post types it owns.
 *   2. Which option keys (wp_options) it owns.
 *   3. Any special export/import logic beyond the defaults.
 *
 * Default implementations cover the 90% case:
 *   – export_posts()   → WP_Query over owned CPTs, dumps post + all meta.
 *   – export_options() → get_option() for each declared key.
 *   – import_posts()   → wp_insert_post / wp_update_post with conflict handling.
 *   – import_options() → update_option() for each declared key.
 *
 * @package STAGEKITWP_IO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class STAGEKITWP_IMPORT_EXPORT_Module {

	// ── Identity (override in subclass) ───────────────────────────────────────

	/**
	 * Unique machine-readable ID (matches the plugin slug / folder name).
	 * e.g. 'stagekitwp', 'stagekitwp-members-area'
	 */
	abstract public function id(): string;

	/**
	 * Human-readable label shown in the admin UI.
	 */
	abstract public function label(): string;

	/**
	 * Post type names this module owns.
	 * Return an empty array if the module has no CPTs.
	 *
	 * @return string[]
	 */
	abstract public function post_types(): array;

	/**
	 * wp_options keys this module owns.
	 * Supports wildcard suffix: 'stagekitwp_members_*' (matched via get_option prefix scan).
	 *
	 * @return string[]
	 */
	abstract public function option_keys(): array;

	/**
	 * Meta keys whose value is a related post's ID (a cross-post relationship
	 * such as show→season). On import these old IDs are remapped to the new
	 * post IDs so relationships — and the shortcodes that query them — survive.
	 *
	 * Override in subclasses that own relationship meta. Each value may be a
	 * single ID or an array of IDs; the remapper handles both.
	 *
	 * @return string[]
	 */
	public function relationship_meta_keys(): array {
		return [];
	}

	// ── Optional overrides ────────────────────────────────────────────────────

	/**
	 * Short description rendered in the UI beneath the module label.
	 */
	public function description(): string {
		return '';
	}

	/**
	 * Whether this module is available (i.e. its parent plugin is active).
	 * The exporter/importer uses this to show a warning when a module is absent
	 * on the receiving site.
	 */
	public function is_available(): bool {
		return true;
	}

	// ── Export ────────────────────────────────────────────────────────────────

	/**
	 * Export all data for this module.
	 *
	 * Returns a structured array ready for JSON-encoding:
	 * [
	 *   'module'      => string,   // module ID
	 *   'label'       => string,
	 *   'exported_at' => string,   // ISO 8601
	 *   'posts'       => [],       // see export_posts()
	 *   'options'     => [],       // see export_options()
	 *   'extra'       => [],       // module-specific payload (override export_extra)
	 * ]
	 *
	 * @return array<string, mixed>
	 */
	public function export(): array {
		return [
			'module'      => $this->id(),
			'label'       => $this->label(),
			'exported_at' => gmdate( 'c' ),
			'posts'       => $this->export_posts(),
			'options'     => $this->export_options(),
			'extra'       => $this->export_extra(),
		];
	}

	/**
	 * Fetch all posts (+ meta) for this module's post types.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function export_posts(): array {
		if ( empty( $this->post_types() ) ) {
			return [];
		}

		$posts     = [];
		$paged     = 1;
		// Page the export query by the configured batch size so large sites are
		// read in bounded chunks (consistent with import/delete batching).
		$per_page  = function_exists( 'stagekitwp_import_export_batch_size' ) ? stagekitwp_import_export_batch_size() : 100;

		do {
			$query = new WP_Query( [
				'post_type'      => $this->post_types(),
				'post_status'    => 'any',
				'posts_per_page' => $per_page,
				'paged'          => $paged,
				'no_found_rows'  => false,
				'fields'         => 'ids',
			] );

			foreach ( $query->posts as $post_id ) {
				$post = get_post( $post_id );
				if ( ! $post ) {
					continue;
				}

				$meta = get_post_meta( $post_id );
				// Remove internal WP meta that shouldn't be transferred.
				$meta = array_filter( $meta, fn( $k ) => ! in_array( $k, [
					'_edit_lock', '_edit_last', '_encloseme',
				], true ), ARRAY_FILTER_USE_KEY );

				// Flatten single-value meta so it round-trips cleanly.
				$flat_meta = [];
				foreach ( $meta as $key => $values ) {
					$flat_meta[ $key ] = ( count( $values ) === 1 )
						? maybe_unserialize( $values[0] )
						: array_map( 'maybe_unserialize', $values );
				}

				$posts[] = [
					'old_id'          => (int) $post_id,
					'guid'            => $post->guid,
					'post_type'       => $post->post_type,
					'post_status'     => $post->post_status,
					'post_title'      => $post->post_title,
					'post_name'       => $post->post_name,
					'post_content'    => $post->post_content,
					'post_excerpt'    => $post->post_excerpt,
					'post_date'       => $post->post_date,
					'post_date_gmt'   => $post->post_date_gmt,
					'post_modified'   => $post->post_modified,
					'menu_order'      => $post->menu_order,
					'post_parent_guid'=> $post->post_parent
						? ( get_post( $post->post_parent )->guid ?? null )
						: null,
					'thumbnail_id'    => get_post_thumbnail_id( $post_id ) ?: null,
					'terms'           => $this->export_post_terms( $post_id ),
					'meta'            => $flat_meta,
				];
			}

			$paged++;
		} while ( $paged <= $query->max_num_pages );

		return $posts;
	}

	/**
	 * Export taxonomy terms assigned to a post.
	 *
	 * @param int $post_id
	 * @return array<string, array<array<string,string>>>
	 */
	protected function export_post_terms( int $post_id ): array {
		$taxonomies = get_object_taxonomies( get_post_type( $post_id ) );
		$result     = [];

		foreach ( $taxonomies as $tax ) {
			$terms = get_the_terms( $post_id, $tax );
			if ( is_array( $terms ) ) {
				$result[ $tax ] = array_map( fn( $t ) => [
					'slug' => $t->slug,
					'name' => $t->name,
				], $terms );
			}
		}

		return $result;
	}

	/**
	 * Export wp_options rows for this module.
	 *
	 * @return array<string, mixed>
	 */
	public function export_options(): array {
		global $wpdb;
		$out = [];

		foreach ( $this->option_keys() as $key ) {
			if ( str_ends_with( $key, '*' ) ) {
				// Wildcard scan.
				$prefix = rtrim( $key, '*' );
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL
				$rows = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s",
						$wpdb->esc_like( $prefix ) . '%'
					)
				);
				foreach ( $rows as $row ) {
					$out[ $row->option_name ] = maybe_unserialize( $row->option_value );
				}
			} else {
				$value = get_option( $key );
				if ( false !== $value ) {
					$out[ $key ] = $value;
				}
			}
		}

		return $out;
	}

	/**
	 * Module-specific extra data. Override in subclass when needed.
	 *
	 * @return array<string, mixed>
	 */
	public function export_extra(): array {
		return [];
	}

	// ── Import ────────────────────────────────────────────────────────────────

	/**
	 * Import a full module payload (as produced by export()).
	 *
	 * @param array<string,mixed> $data      Decoded JSON payload for this module.
	 * @param array<string,mixed> $options   [ 'conflict' => 'skip'|'overwrite'|'duplicate' ]
	 * @return array<string,mixed>           Summary: ['imported'=>int,'skipped'=>int,'errors'=>string[]]
	 */
	public function import( array $data, array $options = [] ): array {
		$conflict = $options['conflict'] ?? 'skip';
		$summary  = [ 'imported' => 0, 'skipped' => 0, 'errors' => [], 'id_map' => [] ];

		// Posts.
		if ( ! empty( $data['posts'] ) ) {
			// Speed: defer term counting and suspend cache invalidation for the
			// whole batch (re-enabled below). wp_insert_post otherwise recounts
			// terms and purges caches on every single call, which dominates the
			// import time on large sites.
			$prev_defer   = wp_defer_term_counting( true );
			$prev_suspend = wp_suspend_cache_invalidation( true );

			// Speed: the post data is already complete in the payload, so the
			// per-post save_post handlers (meta-box savers that read $_POST, cache
			// purges, sync triggers — ~60+ callbacks on this site) add nothing but
			// cost. Suspend them for the batch and restore afterwards.
			$suspended = $this->suspend_save_hooks();

			try {
				foreach ( $data['posts'] as $post_data ) {
					// Resolve the source post's original ID: prefer the explicit
					// old_id field (v1.3.2+ exports), else parse it from the GUID
					// (?p=123 / ?post_type=x&p=123) so relationship remapping also
					// works on older export files that predate the old_id field.
					$old_id = (int) ( $post_data['old_id'] ?? 0 );
					if ( ! $old_id && ! empty( $post_data['guid'] ) ) {
						$old_id = $this->old_id_from_guid( $post_data['guid'] );
					}

					$result = $this->import_post( $post_data, $conflict );
					if ( is_wp_error( $result ) ) {
						$summary['errors'][] = $result->get_error_message();
					} elseif ( 'skipped' === $result ) {
						$summary['skipped']++;
						// Even when skipped, map old→existing so relationships resolve.
						if ( $old_id ) {
							$existing = $this->find_existing_post( $post_data );
							if ( $existing ) {
								$summary['id_map'][ $old_id ] = $existing;
							}
						}
					} else {
						$summary['imported']++;
						if ( $old_id ) {
							$summary['id_map'][ $old_id ] = (int) $result;
						}
					}
				}
			} finally {
				$this->restore_save_hooks( $suspended );
				wp_suspend_cache_invalidation( $prev_suspend );
				wp_defer_term_counting( $prev_defer );
			}
		}

		// Options.
		if ( ! empty( $data['options'] ) ) {
			$this->import_options( $data['options'] );
		}

		// Extra.
		if ( ! empty( $data['extra'] ) ) {
			$this->import_extra( $data['extra'] );
		}

		return $summary;
	}

	/**
	 * Import (or update) a single post.
	 *
	 * @param array<string,mixed>  $data
	 * @param string               $conflict  skip|overwrite|duplicate
	 * @return int|string|WP_Error  post ID on insert/update, 'skipped', or WP_Error.
	 */
	protected function import_post( array $data, string $conflict ): int|string|\WP_Error {
		// Find existing post by GUID first, then by post_name+post_type.
		$existing_id = $this->find_existing_post( $data );

		if ( $existing_id ) {
			if ( 'skip' === $conflict ) {
				return 'skipped';
			}
			if ( 'overwrite' === $conflict ) {
				$post_arr = $this->build_post_array( $data );
				$post_arr['ID'] = $existing_id;
				$result = wp_update_post( $post_arr, true );
				if ( ! is_wp_error( $result ) ) {
					$this->import_post_meta( $result, $data['meta'] ?? [] );
					$this->import_post_terms( $result, $data['terms'] ?? [] );
				}
				return $result;
			}
			// 'duplicate' – fall through to insert.
		}

		$post_arr = $this->build_post_array( $data );
		$result   = wp_insert_post( $post_arr, true );

		if ( ! is_wp_error( $result ) ) {
			// Preserve the source GUID so re-imports and any overlapping runner
			// reliably detect this post as "already imported" (dedup by guid).
			// wp_insert_post always generates a fresh guid, so we set it back.
			if ( ! empty( $data['guid'] ) ) {
				global $wpdb;
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->update( $wpdb->posts, [ 'guid' => $data['guid'] ], [ 'ID' => $result ] );
				clean_post_cache( $result );
			}
			$this->import_post_meta( $result, $data['meta'] ?? [], true );
			$this->import_post_terms( $result, $data['terms'] ?? [] );
		}

		return $result;
	}

	/**
	 * Public wrapper for dry-run use by STAGEKITWP_IMPORT_EXPORT_Importer.
	 */
	public function find_existing_post_public( array $data ): int {
		return $this->find_existing_post( $data );
	}

	/**
	 * Extract a post's original ID from its GUID. WordPress default GUIDs look
	 * like "http://site/?p=123" or "http://site/?post_type=show&p=123" (the
	 * ampersand may be HTML-encoded as &#038; or &amp; in exported data).
	 *
	 * @param string $guid
	 * @return int  old ID, or 0 if it can't be parsed.
	 */
	protected function old_id_from_guid( string $guid ): int {
		$guid = html_entity_decode( $guid, ENT_QUOTES );
		if ( preg_match( '/[?&]p=(\d+)/', $guid, $m ) ) {
			return (int) $m[1];
		}
		if ( preg_match( '/[?&]page_id=(\d+)/', $guid, $m ) ) {
			return (int) $m[1];
		}
		return 0;
	}

	/**
	 * Temporarily detach the per-post save hooks that other plugins register
	 * (meta-box savers, cache purges, sync). Returns the removed callbacks so
	 * restore_save_hooks() can re-attach them exactly.
	 *
	 * @return array<string, \WP_Hook>  hook name => the WP_Hook object we detached
	 */
	protected function suspend_save_hooks(): array {
		global $wp_filter;

		$names = [ 'save_post', 'transition_post_status', 'pre_post_update', 'edit_post', 'post_updated' ];
		foreach ( $this->post_types() as $pt ) {
			$names[] = 'save_post_' . $pt;
		}

		$saved = [];
		foreach ( $names as $name ) {
			if ( isset( $wp_filter[ $name ] ) ) {
				$saved[ $name ] = $wp_filter[ $name ];
				unset( $wp_filter[ $name ] );
			}
		}

		return $saved;
	}

	/**
	 * Re-attach hooks removed by suspend_save_hooks().
	 *
	 * @param array<string, \WP_Hook> $saved
	 */
	protected function restore_save_hooks( array $saved ): void {
		global $wp_filter;
		foreach ( $saved as $name => $hook ) {
			$wp_filter[ $name ] = $hook;
		}
	}

	/**
	 * Locate an existing post by GUID or (slug + post_type) pair.
	 */
	protected function find_existing_post( array $data ): int {
		global $wpdb;

		if ( ! empty( $data['guid'] ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$id = (int) $wpdb->get_var(
				$wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE guid = %s LIMIT 1", $data['guid'] )
			);
			if ( $id ) {
				return $id;
			}
		}

		if ( ! empty( $data['post_name'] ) && ! empty( $data['post_type'] ) ) {
			$existing = get_page_by_path( $data['post_name'], OBJECT, $data['post_type'] );
			if ( $existing ) {
				return (int) $existing->ID;
			}
		}

		return 0;
	}

	protected function build_post_array( array $data ): array {
		$arr = [
			'post_type'    => sanitize_key( $data['post_type']    ?? 'post' ),
			'post_status'  => sanitize_key( $data['post_status']  ?? 'draft' ),
			'post_title'   => sanitize_text_field( $data['post_title']   ?? '' ),
			'post_name'    => sanitize_title( $data['post_name']   ?? '' ),
			'post_content' => wp_kses_post( $data['post_content'] ?? '' ),
			'post_excerpt' => sanitize_textarea_field( $data['post_excerpt'] ?? '' ),
			'post_date'    => $data['post_date']     ?? '',
			'post_date_gmt'=> $data['post_date_gmt'] ?? '',
			'menu_order'   => (int) ( $data['menu_order'] ?? 0 ),
		];

		// Resolve a hierarchical parent by its exported GUID, if it already exists
		// locally. (Relationship remap fixes any that import out of order.)
		if ( ! empty( $data['post_parent_guid'] ) ) {
			global $wpdb;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$parent = (int) $wpdb->get_var(
				$wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE guid = %s LIMIT 1", $data['post_parent_guid'] )
			);
			if ( $parent ) {
				$arr['post_parent'] = $parent;
			}
		}

		return $arr;
	}

	/**
	 * Write a post's meta.
	 *
	 * @param int                  $post_id
	 * @param array<string,mixed>  $meta
	 * @param bool                 $is_new  When true (a fresh insert) meta cannot
	 *                                      pre-exist, so we use add_post_meta and
	 *                                      skip update_post_meta's per-key SELECT +
	 *                                      delete round-trip — the single biggest
	 *                                      cost of importing on SQLite.
	 */
	protected function import_post_meta( int $post_id, array $meta, bool $is_new = false ): void {
		if ( ! $is_new ) {
			foreach ( $meta as $key => $value ) {
				update_post_meta( $post_id, $key, $value );
			}
			return;
		}

		// Fresh insert: write every meta row in ONE multi-row SQL INSERT instead
		// of a query per key. On SQLite each individual write is a separate commit,
		// so batching cuts the dominant import cost dramatically.
		//
		// Each meta value is stored as a SINGLE serialized row — this mirrors
		// add_post_meta($id, $key, $arrayValue), which serializes arrays into one
		// row. (The exporter already flattened multi-row meta back into arrays.)
		global $wpdb;
		$rows  = [];
		$place = [];
		foreach ( $meta as $key => $value ) {
			$value = sanitize_meta( (string) $key, $value, 'post' );
			$place[] = '(%d,%s,%s)';
			$rows[]  = $post_id;
			$rows[]  = (string) $key;
			$rows[]  = maybe_serialize( $value );
		}

		if ( empty( $place ) ) {
			return;
		}

		$sql = "INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value) VALUES " . implode( ',', $place );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL
		$wpdb->query( $wpdb->prepare( $sql, $rows ) );

		// Keep the object cache correct after a direct write.
		wp_cache_delete( $post_id, 'post_meta' );
	}

	protected function import_post_terms( int $post_id, array $terms_by_tax ): void {
		foreach ( $terms_by_tax as $taxonomy => $terms ) {
			$slugs = wp_list_pluck( $terms, 'slug' );
			$ids   = [];
			foreach ( $slugs as $slug ) {
				$term = get_term_by( 'slug', $slug, $taxonomy );
				if ( $term ) {
					$ids[] = $term->term_id;
				}
			}
			if ( $ids ) {
				wp_set_post_terms( $post_id, $ids, $taxonomy );
			}
		}
	}

	/**
	 * Write options back to wp_options.
	 *
	 * @param array<string, mixed> $options
	 */
	public function import_options( array $options ): void {
		foreach ( $options as $key => $value ) {
			update_option( sanitize_key( $key ), $value );
		}
	}

	/**
	 * Module-specific extra import logic. Override in subclass when needed.
	 *
	 * @param array<string, mixed> $extra
	 */
	public function import_extra( array $extra ): void {}

	/**
	 * Rewrite this module's relationship meta (e.g. _stagekitwp_show_season) from old
	 * source post IDs to the new local IDs, using the accumulated old→new map
	 * built during import. Runs once after all posts land so relationships that
	 * imported out of order (a show before its season) still resolve.
	 *
	 * Idempotent: only values found in $id_map are changed, and once a value is
	 * a valid new ID it won't match an old key again.
	 *
	 * @param array<int,int> $id_map  old post ID => new post ID
	 * @return int  number of meta values rewritten
	 */
	public function remap_relationships( array $id_map ): int {
		$keys = $this->relationship_meta_keys();
		if ( empty( $keys ) || empty( $id_map ) || empty( $this->post_types() ) ) {
			return 0;
		}

		$changed = 0;
		$paged   = 1;
		$per     = function_exists( 'stagekitwp_import_export_batch_size' ) ? stagekitwp_import_export_batch_size() : 100;

		do {
			$query = new WP_Query( [
				'post_type'      => $this->post_types(),
				'post_status'    => 'any',
				'posts_per_page' => $per,
				'paged'          => $paged,
				'fields'         => 'ids',
				'no_found_rows'  => false,
			] );

			foreach ( $query->posts as $pid ) {
				foreach ( $keys as $key ) {
					$value = get_post_meta( $pid, $key, true );
					if ( '' === $value || null === $value ) {
						continue;
					}
					$new = $this->remap_id_value( $value, $id_map, $did );
					if ( $did ) {
						update_post_meta( $pid, $key, $new );
						$changed++;
					}
				}
			}

			$paged++;
		} while ( $paged <= $query->max_num_pages );

		return $changed;
	}

	/**
	 * Remap a single meta value (scalar ID or array of IDs) through the id_map.
	 *
	 * @param mixed          $value
	 * @param array<int,int> $id_map
	 * @param bool           $did   (out) whether anything was remapped.
	 * @return mixed  remapped value (same shape as input)
	 */
	protected function remap_id_value( $value, array $id_map, ?bool &$did = null ) {
		$did = false;

		if ( is_array( $value ) ) {
			foreach ( $value as $k => $v ) {
				$value[ $k ] = $this->remap_id_value( $v, $id_map, $sub );
				if ( $sub ) { $did = true; }
			}
			return $value;
		}

		if ( is_numeric( $value ) && isset( $id_map[ (int) $value ] ) ) {
			$did = true;
			// Preserve original scalar type (string vs int) as WP stored it.
			return is_string( $value ) ? (string) $id_map[ (int) $value ] : $id_map[ (int) $value ];
		}

		return $value;
	}

	// ── Record count (used by importer to pick sync vs async) ─────────────────

	/**
	 * Count how many records are in an export payload.
	 * Used by STAGEKITWP_IMPORT_EXPORT_Importer to decide sync vs async processing.
	 */
	public function count_records( array $data ): int {
		return count( $data['posts'] ?? [] );
	}
}
