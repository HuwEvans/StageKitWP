<?php
namespace SKWPM;

defined( 'ABSPATH' ) || exit;

/**
 * Data Access Layer / Repository for StageKit WP Media folders.
 */
class FolderRepository {

	/**
	 * Fetch a single folder by ID.
	 */
	public static function get( int $id ): ?array {
		global $wpdb;
		$table = Schema::folders_table();
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A );
		return $row ?: null;
	}

	/**
	 * Fetch a single folder by slug and optional parent ID.
	 */
	public static function get_by_slug( string $slug, int $parent_id = 0 ): ?array {
		global $wpdb;
		$table = Schema::folders_table();
		$row   = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE slug = %s AND parent_id = %d", $slug, $parent_id ),
			ARRAY_A
		);
		return $row ?: null;
	}

	/**
	 * Create a new folder.
	 *
	 * @param array{name: string, parent_id?: int, provider?: string, color?: string, slug?: string} $data
	 * @return int|false New folder ID on success, false on failure.
	 */
	public static function create( array $data ) {
		global $wpdb;

		$name      = sanitize_text_field( $data['name'] ?? '' );
		$parent_id = max( 0, (int) ( $data['parent_id'] ?? 0 ) );
		$provider  = sanitize_key( $data['provider'] ?? 'local' );
		$color     = sanitize_hex_color( $data['color'] ?? '' );
		$slug      = sanitize_title( $data['slug'] ?? $name );

		if ( empty( $name ) ) {
			return false;
		}

		// Ensure unique slug under the same parent
		$slug = self::unique_slug( $slug, $parent_id );

		$insert = [
			'name'       => $name,
			'slug'       => $slug,
			'parent_id'  => $parent_id,
			'provider'   => $provider ?: 'local',
			'color'      => $color ?: null,
			'created_at' => current_time( 'mysql' ),
			'updated_at' => current_time( 'mysql' ),
		];

		$result = $wpdb->insert( Schema::folders_table(), $insert, [ '%s', '%s', '%d', '%s', '%s', '%s', '%s' ] );
		return $result ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Update an existing folder.
	 */
	public static function update( int $id, array $data ): bool {
		global $wpdb;

		$existing = self::get( $id );
		if ( ! $existing ) {
			return false;
		}

		$update = [];
		$format = [];

		if ( isset( $data['name'] ) ) {
			$name = sanitize_text_field( $data['name'] );
			if ( ! empty( $name ) ) {
				$update['name'] = $name;
				$format[]       = '%s';
			}
		}

		if ( isset( $data['slug'] ) ) {
			$parent_id = isset( $data['parent_id'] ) ? (int) $data['parent_id'] : (int) $existing['parent_id'];
			$slug      = self::unique_slug( sanitize_title( $data['slug'] ), $parent_id, $id );
			$update['slug'] = $slug;
			$format[]       = '%s';
		}

		if ( isset( $data['parent_id'] ) ) {
			$parent_id = max( 0, (int) $data['parent_id'] );
			// Prevent parenting a folder to itself or its descendants
			if ( $parent_id !== $id && ! self::is_descendant_of( $parent_id, $id ) ) {
				$update['parent_id'] = $parent_id;
				$format[]            = '%d';
			}
		}

		if ( isset( $data['provider'] ) ) {
			$update['provider'] = sanitize_key( $data['provider'] );
			$format[]           = '%s';
		}

		if ( array_key_exists( 'color', $data ) ) {
			$color           = sanitize_hex_color( $data['color'] ?? '' );
			$update['color'] = $color ?: null;
			$format[]        = '%s';
		}

		if ( empty( $update ) ) {
			return true;
		}

		$update['updated_at'] = current_time( 'mysql' );
		$format[]             = '%s';

		$result = $wpdb->update(
			Schema::folders_table(),
			$update,
			[ 'id' => $id ],
			$format,
			[ '%d' ]
		);

		return false !== $result;
	}

	/**
	 * Delete a folder.
	 *
	 * @param int  $id                  Folder ID.
	 * @param bool $reassign_to_parent If true, reassigns subfolders & media to parent folder. If false, deletes all descendants.
	 */
	public static function delete( int $id, bool $reassign_to_parent = true ): bool {
		global $wpdb;

		$folder = self::get( $id );
		if ( ! $folder ) {
			return false;
		}

		$parent_id = (int) $folder['parent_id'];
		$folders_table = Schema::folders_table();
		$media_table   = Schema::media_table();

		if ( $reassign_to_parent ) {
			// Move child folders up
			$wpdb->update( $folders_table, [ 'parent_id' => $parent_id ], [ 'parent_id' => $id ], [ '%d' ], [ '%d' ] );
			// Move media items in this folder up
			$wpdb->update( $media_table, [ 'folder_id' => $parent_id ], [ 'folder_id' => $id ], [ '%d' ], [ '%d' ] );
		} else {
			// Delete child media items & files
			$child_media = $wpdb->get_results( $wpdb->prepare( "SELECT id FROM {$media_table} WHERE folder_id = %d", $id ), ARRAY_A );
			foreach ( $child_media as $m ) {
				MediaRepository::delete( (int) $m['id'] );
			}

			// Recursively delete child folders
			$child_folders = $wpdb->get_results( $wpdb->prepare( "SELECT id FROM {$folders_table} WHERE parent_id = %d", $id ), ARRAY_A );
			foreach ( $child_folders as $cf ) {
				self::delete( (int) $cf['id'], false );
			}
		}

		$deleted = $wpdb->delete( $folders_table, [ 'id' => $id ], [ '%d' ] );
		return (bool) $deleted;
	}

	/**
	 * Get all folders matching optional criteria.
	 */
	public static function get_all( array $args = [] ): array {
		global $wpdb;
		$table = Schema::folders_table();

		$where = [ '1=1' ];
		$params = [];

		if ( isset( $args['provider'] ) && '' !== $args['provider'] ) {
			$where[]  = 'provider = %s';
			$params[] = sanitize_key( $args['provider'] );
		}

		if ( isset( $args['parent_id'] ) ) {
			$where[]  = 'parent_id = %d';
			$params[] = (int) $args['parent_id'];
		}

		$orderby = in_array( $args['orderby'] ?? '', [ 'name', 'created_at', 'id' ], true ) ? $args['orderby'] : 'name';
		$order   = 'DESC' === strtoupper( $args['order'] ?? '' ) ? 'DESC' : 'ASC';

		$sql = "SELECT * FROM {$table} WHERE " . implode( ' AND ', $where ) . " ORDER BY {$orderby} {$order}";

		if ( ! empty( $params ) ) {
			$sql = $wpdb->prepare( $sql, ...$params );
		}

		return $wpdb->get_results( $sql, ARRAY_A ) ?: [];
	}

	/**
	 * Get full hierarchical tree of folders.
	 */
	public static function get_tree( int $parent_id = 0 ): array {
		$all_folders = self::get_all( [ 'orderby' => 'name', 'order' => 'ASC' ] );
		return self::build_tree( $all_folders, $parent_id );
	}

	/**
	 * Helper to build nested tree and append item counts.
	 */
	private static function build_tree( array &$folders, int $parent_id = 0 ): array {
		$branch = [];

		foreach ( $folders as $folder ) {
			if ( (int) $folder['parent_id'] === $parent_id ) {
				$folder['id']         = (int) $folder['id'];
				$folder['parent_id']  = (int) $folder['parent_id'];
				$folder['item_count'] = self::get_item_count( $folder['id'], false );
				$children             = self::build_tree( $folders, $folder['id'] );

				if ( ! empty( $children ) ) {
					$folder['children'] = $children;
				} else {
					$folder['children'] = [];
				}

				$branch[] = $folder;
			}
		}

		return $branch;
	}

	/**
	 * Get breadcrumb trail from root to the specified folder.
	 */
	public static function get_breadcrumbs( int $id ): array {
		$trail = [];
		$current_id = $id;

		while ( $current_id > 0 ) {
			$folder = self::get( $current_id );
			if ( ! $folder ) {
				break;
			}
			$trail[]    = $folder;
			$current_id = (int) $folder['parent_id'];
		}

		return array_reverse( $trail );
	}

	/**
	 * Count media items inside a folder.
	 */
	public static function get_item_count( int $folder_id, bool $recursive = false ): int {
		global $wpdb;
		$media_table = Schema::media_table();

		if ( ! $recursive ) {
			return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$media_table} WHERE folder_id = %d", $folder_id ) );
		}

		$all_ids = self::get_descendant_ids( $folder_id );
		$all_ids[] = $folder_id;
		$placeholders = implode( ',', array_fill( 0, count( $all_ids ), '%d' ) );

		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$media_table} WHERE folder_id IN ({$placeholders})", ...$all_ids ) );
	}

	/**
	 * Get all descendant IDs of a folder.
	 */
	public static function get_descendant_ids( int $folder_id ): array {
		global $wpdb;
		$table = Schema::folders_table();
		$ids   = [];

		$children = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$table} WHERE parent_id = %d", $folder_id ) );
		foreach ( $children as $child_id ) {
			$cid   = (int) $child_id;
			$ids[] = $cid;
			$ids   = array_merge( $ids, self::get_descendant_ids( $cid ) );
		}

		return $ids;
	}

	/**
	 * Check if a folder is a descendant of another folder.
	 */
	private static function is_descendant_of( int $folder_id, int $ancestor_id ): bool {
		$descendants = self::get_descendant_ids( $ancestor_id );
		return in_array( $folder_id, $descendants, true );
	}

	/**
	 * Generate a unique slug under a specific parent folder.
	 */
	private static function unique_slug( string $slug, int $parent_id, int $exclude_id = 0 ): string {
		global $wpdb;
		$table     = Schema::folders_table();
		$base_slug = $slug;
		$counter   = 1;

		while ( true ) {
			$query = "SELECT id FROM {$table} WHERE slug = %s AND parent_id = %d";
			$params = [ $slug, $parent_id ];

			if ( $exclude_id > 0 ) {
				$query   .= ' AND id != %d';
				$params[] = $exclude_id;
			}

			$existing = $wpdb->get_var( $wpdb->prepare( $query, ...$params ) );
			if ( ! $existing ) {
				break;
			}

			$slug = sprintf( '%s-%d', $base_slug, $counter );
			$counter++;
		}

		return $slug;
	}
}
