<?php
namespace SKWPM;

defined( 'ABSPATH' ) || exit;

/**
 * Storage health, diagnostics, cleanup tools, and WordPress Core Media Library bridge.
 */
class Diagnostics {

	/**
	 * Get comprehensive storage statistics, file counts, and breakdown by type/provider.
	 */
	public static function get_storage_stats(): array {
		global $wpdb;

		$base_dir      = Storage::get_base_dir();
		$exists        = file_exists( $base_dir );
		$is_writable   = $exists && is_writable( $base_dir );
		$physical_size = 0;
		$file_count    = 0;

		if ( $exists ) {
			$iterator = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator( $base_dir, \RecursiveDirectoryIterator::SKIP_DOTS ),
				\RecursiveIteratorIterator::SELF_FIRST
			);
			foreach ( $iterator as $file ) {
				if ( $file->isFile() ) {
					$file_count++;
					$physical_size += $file->getSize();
				}
			}
		}

		$media_table   = Schema::media_table();
		$folders_table = Schema::folders_table();

		$total_records = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$media_table}" );
		$total_folders = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$folders_table}" );

		// Breakdown by type
		$types = [
			'images'    => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$media_table} WHERE mime_type LIKE 'image/%'" ),
			'videos'    => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$media_table} WHERE mime_type LIKE 'video/%'" ),
			'audio'     => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$media_table} WHERE mime_type LIKE 'audio/%'" ),
			'documents' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$media_table} WHERE mime_type = 'application/pdf'" ),
		];

		// Breakdown by provider
		$providers_raw = $wpdb->get_results( "SELECT provider, COUNT(*) as count FROM {$media_table} GROUP BY provider", ARRAY_A ) ?: [];
		$providers = [];
		foreach ( $providers_raw as $p ) {
			$providers[ $p['provider'] ] = (int) $p['count'];
		}

		return [
			'storage_dir'        => $base_dir,
			'storage_exists'     => $exists,
			'storage_writable'   => $is_writable,
			'physical_files'     => $file_count,
			'physical_size'      => $physical_size,
			'physical_size_fmt'  => size_format( $physical_size, 2 ),
			'total_records'      => $total_records,
			'total_folders'      => $total_folders,
			'types_breakdown'    => $types,
			'provider_breakdown' => $providers,
		];
	}

	/**
	 * Find files on disk that have no corresponding record in wp_skwpm_media.
	 */
	public static function scan_orphaned_files(): array {
		global $wpdb;
		$base_dir = Storage::get_base_dir();
		if ( ! file_exists( $base_dir ) ) {
			return [];
		}

		$media_table = Schema::media_table();
		$known_paths = $wpdb->get_col( "SELECT file_path FROM {$media_table}" ) ?: [];
		$known_map   = array_flip( $known_paths );

		$orphans  = [];
		$iterator = new \DirectoryIterator( $base_dir );

		foreach ( $iterator as $fileinfo ) {
			if ( $fileinfo->isFile() ) {
				$filename = $fileinfo->getFilename();
				if ( in_array( $filename, [ '.htaccess', 'index.php' ], true ) ) {
					continue;
				}
				if ( ! isset( $known_map[ $filename ] ) ) {
					$orphans[] = [
						'filename'  => $filename,
						'path'      => $fileinfo->getPathname(),
						'size'      => $fileinfo->getSize(),
						'size_fmt'  => size_format( $fileinfo->getSize(), 1 ),
						'modified'  => date( 'Y-m-d H:i:s', $fileinfo->getMTime() ),
					];
				}
			}
		}

		return $orphans;
	}

	/**
	 * Delete physical files on disk that are not registered in the database.
	 */
	public static function cleanup_orphaned_files(): int {
		$orphans = self::scan_orphaned_files();
		$deleted = 0;

		foreach ( $orphans as $orphan ) {
			if ( file_exists( $orphan['path'] ) && is_file( $orphan['path'] ) ) {
				if ( @unlink( $orphan['path'] ) ) {
					$deleted++;
				}
			}
		}

		return $deleted;
	}

	/**
	 * Find records in wp_skwpm_media whose physical files are missing from disk.
	 */
	public static function scan_missing_files(): array {
		global $wpdb;
		$base_dir    = Storage::get_base_dir();
		$media_table = Schema::media_table();
		$records     = $wpdb->get_results( "SELECT id, title, filename, file_path, provider FROM {$media_table}", ARRAY_A ) ?: [];

		$missing = [];
		foreach ( $records as $rec ) {
			$full_path = trailingslashit( $base_dir ) . ltrim( $rec['file_path'], '/\\' );
			if ( ! file_exists( $full_path ) ) {
				$missing[] = $rec;
			}
		}

		return $missing;
	}

	/**
	 * Delete database records whose physical files are missing from disk.
	 */
	public static function cleanup_missing_records(): int {
		$missing = self::scan_missing_files();
		$count   = 0;

		foreach ( $missing as $item ) {
			if ( MediaRepository::delete( (int) $item['id'], false ) ) {
				$count++;
			}
		}

		return $count;
	}

	/**
	 * Regenerate responsive thumbnails for all image items in StageKit Media.
	 */
	public static function regenerate_all_thumbnails(): array {
		global $wpdb;
		$base_dir    = Storage::get_base_dir();
		$media_table = Schema::media_table();
		$images      = $wpdb->get_results( "SELECT id, filename, file_path FROM {$media_table} WHERE mime_type LIKE 'image/%' AND mime_type != 'image/svg+xml'", ARRAY_A ) ?: [];

		$success = 0;
		$failed  = 0;

		foreach ( $images as $img ) {
			$full_path = trailingslashit( $base_dir ) . ltrim( $img['file_path'], '/\\' );
			if ( file_exists( $full_path ) ) {
				$thumbs = Storage::generate_thumbnails( $full_path, $img['filename'] );
				if ( ! empty( $thumbs ) ) {
					MediaRepository::update( (int) $img['id'], [ 'thumbnails' => $thumbs ] );
					$success++;
				} else {
					$failed++;
				}
			} else {
				$failed++;
			}
		}

		return [
			'total'   => count( $images ),
			'success' => $success,
			'failed'  => $failed,
		];
	}

	/**
	 * Bridge Action: Export a StageKit Media item to the WordPress Core Media Library (`wp_posts` attachment).
	 *
	 * @param int $media_id StageKit Media Item ID.
	 * @return int|false WordPress Attachment ID on success, false on failure.
	 */
	public static function export_to_wp_attachment( int $media_id ) {
		$item = MediaRepository::get( $media_id );
		if ( ! $item ) {
			return false;
		}

		$base_dir  = Storage::get_base_dir();
		$full_path = trailingslashit( $base_dir ) . ltrim( $item['file_path'], '/\\' );
		if ( ! file_exists( $full_path ) ) {
			return false;
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';

		$attachment_data = [
			'post_mime_type' => $item['mime_type'],
			'post_title'     => $item['title'] ?: pathinfo( $item['filename'], PATHINFO_FILENAME ),
			'post_content'   => $item['caption'] ?? '',
			'post_excerpt'   => $item['caption'] ?? '',
			'post_status'    => 'inherit',
		];

		$attachment_id = wp_insert_attachment( $attachment_data, $full_path );
		if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
			return false;
		}

		// Generate WordPress attachment metadata
		$metadata = wp_generate_attachment_metadata( $attachment_id, $full_path );
		wp_update_attachment_metadata( $attachment_id, $metadata );

		if ( ! empty( $item['alt_text'] ) ) {
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', $item['alt_text'] );
		}

		// Link back to StageKit Media
		update_post_meta( $attachment_id, '_skwpm_source_media_id', $media_id );

		return (int) $attachment_id;
	}
}
