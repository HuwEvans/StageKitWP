<?php
namespace SKWPM;

defined( 'ABSPATH' ) || exit;

/**
 * Data Access Layer / Repository for StageKit WP Media items.
 */
class MediaRepository {

	/**
	 * Fetch a single media item by ID.
	 */
	public static function get( int $id ): ?array {
		global $wpdb;
		$table = Schema::media_table();
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A );
		return $row ? self::format_item( $row ) : null;
	}

	/**
	 * Fetch a media item by remote provider and external ID.
	 */
	public static function get_by_external_id( string $external_id, string $provider ): ?array {
		global $wpdb;
		$table = Schema::media_table();
		$row   = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE external_id = %s AND provider = %s", $external_id, $provider ),
			ARRAY_A
		);
		return $row ? self::format_item( $row ) : null;
	}

	/**
	 * Create a new media record.
	 *
	 * @param array $data Media item attributes.
	 * @return int|false New media ID on success, false on failure.
	 */
	public static function create( array $data ) {
		global $wpdb;

		$filename  = sanitize_text_field( $data['filename'] ?? '' );
		$file_path = sanitize_text_field( $data['file_path'] ?? $filename );
		$file_url  = esc_url_raw( $data['file_url'] ?? '' );
		$mime_type = sanitize_text_field( $data['mime_type'] ?? '' );

		if ( empty( $filename ) || empty( $file_path ) || empty( $file_url ) || empty( $mime_type ) ) {
			return false;
		}

		$thumbnails = $data['thumbnails'] ?? [];
		if ( is_array( $thumbnails ) ) {
			$thumbnails_json = ! empty( $thumbnails ) ? wp_json_encode( $thumbnails ) : null;
		} else {
			$thumbnails_json = is_string( $thumbnails ) ? $thumbnails : null;
		}

		$title = sanitize_text_field( $data['title'] ?? pathinfo( $filename, PATHINFO_FILENAME ) );

		$insert = [
			'folder_id'   => max( 0, (int) ( $data['folder_id'] ?? 0 ) ),
			'filename'    => $filename,
			'file_path'   => $file_path,
			'file_url'    => $file_url,
			'mime_type'   => $mime_type,
			'file_size'   => max( 0, (int) ( $data['file_size'] ?? 0 ) ),
			'width'       => isset( $data['width'] ) && $data['width'] ? (int) $data['width'] : null,
			'height'      => isset( $data['height'] ) && $data['height'] ? (int) $data['height'] : null,
			'thumbnails'  => $thumbnails_json,
			'provider'    => sanitize_key( $data['provider'] ?? 'local' ),
			'external_id' => ! empty( $data['external_id'] ) ? sanitize_text_field( $data['external_id'] ) : null,
			'title'       => $title,
			'caption'     => isset( $data['caption'] ) ? sanitize_textarea_field( $data['caption'] ) : null,
			'alt_text'    => sanitize_text_field( $data['alt_text'] ?? '' ),
			'author'      => sanitize_text_field( $data['author'] ?? '' ),
			'license'     => sanitize_text_field( $data['license'] ?? '' ),
			'sync_hash'   => ! empty( $data['sync_hash'] ) ? sanitize_text_field( $data['sync_hash'] ) : null,
			'created_at'  => current_time( 'mysql' ),
			'updated_at'  => current_time( 'mysql' ),
		];

		$result = $wpdb->insert(
			Schema::media_table(),
			$insert,
			[
				'%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s',
				'%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'
			]
		);

		return $result ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Update an existing media item.
	 */
	public static function update( int $id, array $data ): bool {
		global $wpdb;

		$existing = self::get( $id );
		if ( ! $existing ) {
			return false;
		}

		$update = [];
		$format = [];

		$string_fields = [ 'filename', 'file_path', 'file_url', 'mime_type', 'provider', 'external_id', 'title', 'alt_text', 'author', 'license', 'sync_hash' ];
		foreach ( $string_fields as $field ) {
			if ( array_key_exists( $field, $data ) ) {
				$update[ $field ] = sanitize_text_field( $data[ $field ] );
				$format[]         = '%s';
			}
		}

		if ( array_key_exists( 'caption', $data ) ) {
			$update['caption'] = sanitize_textarea_field( $data['caption'] );
			$format[]          = '%s';
		}

		if ( array_key_exists( 'folder_id', $data ) ) {
			$update['folder_id'] = max( 0, (int) $data['folder_id'] );
			$format[]            = '%d';
		}

		if ( array_key_exists( 'file_size', $data ) ) {
			$update['file_size'] = max( 0, (int) $data['file_size'] );
			$format[]            = '%d';
		}

		if ( array_key_exists( 'width', $data ) ) {
			$update['width'] = $data['width'] ? (int) $data['width'] : null;
			$format[]        = '%d';
		}

		if ( array_key_exists( 'height', $data ) ) {
			$update['height'] = $data['height'] ? (int) $data['height'] : null;
			$format[]         = '%d';
		}

		if ( array_key_exists( 'thumbnails', $data ) ) {
			$thumbs = $data['thumbnails'];
			$update['thumbnails'] = is_array( $thumbs ) ? wp_json_encode( $thumbs ) : (string) $thumbs;
			$format[]             = '%s';
		}

		if ( empty( $update ) ) {
			return true;
		}

		$update['updated_at'] = current_time( 'mysql' );
		$format[]             = '%s';

		$result = $wpdb->update(
			Schema::media_table(),
			$update,
			[ 'id' => $id ],
			$format,
			[ '%d' ]
		);

		return false !== $result;
	}

	/**
	 * Delete a media item and optionally clean its physical storage.
	 */
	public static function delete( int $id, bool $delete_physical_file = true ): bool {
		global $wpdb;

		$item = self::get( $id );
		if ( ! $item ) {
			return false;
		}

		if ( $delete_physical_file && ! empty( $item['file_path'] ) ) {
			Storage::delete_file( $item['file_path'], $item['thumbnails'] ?? [] );
		}

		$deleted = $wpdb->delete( Schema::media_table(), [ 'id' => $id ], [ '%d' ] );
		return (bool) $deleted;
	}

	/**
	 * Bulk delete multiple media items.
	 */
	public static function bulk_delete( array $ids, bool $delete_physical_files = true ): int {
		$count = 0;
		foreach ( $ids as $id ) {
			if ( self::delete( (int) $id, $delete_physical_files ) ) {
				$count++;
			}
		}
		return $count;
	}

	/**
	 * Move multiple media items to a target folder.
	 */
	public static function move_to_folder( array $ids, int $target_folder_id ): int {
		global $wpdb;
		if ( empty( $ids ) ) {
			return 0;
		}

		$clean_ids    = array_map( 'intval', $ids );
		$folder_id    = max( 0, $target_folder_id );
		$placeholders = implode( ',', array_fill( 0, count( $clean_ids ), '%d' ) );
		$table        = Schema::media_table();

		$params = array_merge( [ $folder_id, current_time( 'mysql' ) ], $clean_ids );
		$query  = "UPDATE {$table} SET folder_id = %d, updated_at = %s WHERE id IN ({$placeholders})";

		$result = $wpdb->query( $wpdb->prepare( $query, ...$params ) );
		return false !== $result ? (int) $result : 0;
	}

	/**
	 * Query media items with filtering, search, pagination, and sorting.
	 *
	 * @param array $args
	 * @return array{items: array[], total: int, pages: int, page: int, per_page: int}
	 */
	public static function query( array $args = [] ): array {
		global $wpdb;
		$table = Schema::media_table();

		$where  = [ '1=1' ];
		$params = [];

		// Folder filtering
		if ( isset( $args['folder_id'] ) && 'all' !== $args['folder_id'] ) {
			$folder_id = (int) $args['folder_id'];
			if ( ! empty( $args['recursive_folder'] ) && $folder_id > 0 ) {
				$descendants = FolderRepository::get_descendant_ids( $folder_id );
				$descendants[] = $folder_id;
				$in_clause = implode( ',', array_fill( 0, count( $descendants ), '%d' ) );
				$where[]   = "folder_id IN ({$in_clause})";
				$params    = array_merge( $params, $descendants );
			} else {
				$where[]  = 'folder_id = %d';
				$params[] = $folder_id;
			}
		}

		// Provider filtering
		if ( ! empty( $args['provider'] ) ) {
			$where[]  = 'provider = %s';
			$params[] = sanitize_key( $args['provider'] );
		}

		// Type filtering (e.g. image, video, audio, application)
		if ( ! empty( $args['type'] ) ) {
			$type = sanitize_key( $args['type'] );
			if ( 'image' === $type ) {
				$where[] = "mime_type LIKE 'image/%'";
			} elseif ( 'video' === $type ) {
				$where[] = "mime_type LIKE 'video/%'";
			} elseif ( 'audio' === $type ) {
				$where[] = "mime_type LIKE 'audio/%'";
			} elseif ( 'document' === $type || 'pdf' === $type ) {
				$where[] = "mime_type = 'application/pdf'";
			}
		}

		// Direct MIME type filter
		if ( ! empty( $args['mime_type'] ) ) {
			$where[]  = 'mime_type = %s';
			$params[] = sanitize_text_field( $args['mime_type'] );
		}

		// Search term (across title, filename, caption, alt_text)
		if ( ! empty( $args['search'] ) ) {
			$search_term = '%' . $wpdb->esc_like( sanitize_text_field( $args['search'] ) ) . '%';
			$where[]     = '(title LIKE %s OR filename LIKE %s OR caption LIKE %s OR alt_text LIKE %s)';
			$params[]    = $search_term;
			$params[]    = $search_term;
			$params[]    = $search_term;
			$params[]    = $search_term;
		}

		// Total count query
		$count_sql = "SELECT COUNT(*) FROM {$table} WHERE " . implode( ' AND ', $where );
		if ( ! empty( $params ) ) {
			$count_sql = $wpdb->prepare( $count_sql, ...$params );
		}
		$total = (int) $wpdb->get_var( $count_sql );

		// Pagination
		$page     = max( 1, (int) ( $args['page'] ?? 1 ) );
		$per_page = max( 1, min( 100, (int) ( $args['per_page'] ?? 24 ) ) );
		$offset   = ( $page - 1 ) * $per_page;
		$pages    = (int) ceil( $total / $per_page );

		// Sorting
		$allowed_orderby = [ 'id', 'title', 'filename', 'file_size', 'created_at', 'updated_at' ];
		$orderby = in_array( $args['orderby'] ?? '', $allowed_orderby, true ) ? $args['orderby'] : 'created_at';
		$order   = 'ASC' === strtoupper( $args['order'] ?? '' ) ? 'ASC' : 'DESC';

		$query_sql = "SELECT * FROM {$table} WHERE " . implode( ' AND ', $where ) . " ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
		$query_params = array_merge( $params, [ $per_page, $offset ] );

		$rows = $wpdb->get_results( $wpdb->prepare( $query_sql, ...$query_params ), ARRAY_A ) ?: [];
		$items = array_map( [ __CLASS__, 'format_item' ], $rows );

		return [
			'items'    => $items,
			'total'    => $total,
			'pages'    => $pages,
			'page'     => $page,
			'per_page' => $per_page,
		];
	}

	/**
	 * Format raw DB row into clean item representation with decoded thumbnails and preview helpers.
	 */
	public static function format_item( array $row ): array {
		$row['id']        = (int) $row['id'];
		$row['folder_id'] = (int) $row['folder_id'];
		$row['file_size'] = (int) $row['file_size'];
		$row['width']     = $row['width'] !== null ? (int) $row['width'] : null;
		$row['height']    = $row['height'] !== null ? (int) $row['height'] : null;

		$thumbnails = [];
		if ( ! empty( $row['thumbnails'] ) ) {
			$decoded = json_decode( $row['thumbnails'], true );
			if ( is_array( $decoded ) ) {
				$thumbnails = $decoded;
			}
		}
		$row['thumbnails'] = $thumbnails;

		// Provide a convenient 'thumb_url' for UI display
		$mime_type = $row['mime_type'] ?? '';
		$thumb_url = $row['file_url'];

		if ( ! empty( $thumbnails['medium']['url'] ) ) {
			$thumb_url = $thumbnails['medium']['url'];
		} elseif ( ! empty( $thumbnails['thumb']['url'] ) ) {
			$thumb_url = $thumbnails['thumb']['url'];
		}

		$row['thumb_url'] = $thumb_url;

		// Classification type
		if ( 0 === strpos( $mime_type, 'image/' ) ) {
			$row['type'] = 'image';
		} elseif ( 0 === strpos( $mime_type, 'video/' ) ) {
			$row['type'] = 'video';
		} elseif ( 0 === strpos( $mime_type, 'audio/' ) ) {
			$row['type'] = 'audio';
		} elseif ( 'application/pdf' === $mime_type ) {
			$row['type'] = 'document';
		} else {
			$row['type'] = 'other';
		}

		return $row;
	}
}
