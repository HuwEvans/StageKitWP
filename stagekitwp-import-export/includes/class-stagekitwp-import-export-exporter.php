<?php
/**
 * STAGEKITWP_IMPORT_EXPORT_Exporter – orchestrates the export of one or more modules into a ZIP bundle.
 *
 * Bundle layout:
 *
 *   stagekitwp-export-{site}-{date}.zip
 *   ├── manifest.json
 *   ├── stagekitwp/
 *   │   ├── posts.json
 *   │   └── options.json
 *   ├── stagekitwp-members-area/
 *   │   └── options.json
 *   ├── stagekitwp-rc-library/
 *   │   ├── posts.json
 *   │   └── options.json
 *   ├── stagekitwp-sync/
 *   │   └── options.json
 *   ├── stagekitwp-theme/
 *   │   └── options.json
 *   └── media/
 *       └── {basename}   ← copies of attachment files referenced by exported posts
 *
 * @package STAGEKITWP_IO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class STAGEKITWP_IMPORT_EXPORT_Exporter {

	/** @var STAGEKITWP_IMPORT_EXPORT_Module[] */
	private array $modules;

	/**
	 * @param STAGEKITWP_IMPORT_EXPORT_Module[] $modules  All registered module instances.
	 */
	public function __construct( array $modules ) {
		$this->modules = $modules;
	}

	// ── Public API ────────────────────────────────────────────────────────────

	/**
	 * Export the requested modules and return the path to the finished ZIP.
	 *
	 * @param string[]            $module_ids  Module IDs to include, or ['all'].
	 * @param array<string,mixed> $options     [ 'include_media' => bool ] (default true).
	 * @param array<string,mixed> $stats       (out) populated with export stats:
	 *                                         media_count, media_files, post_count,
	 *                                         filesize.
	 * @return string|WP_Error  Absolute path to the ZIP file on success.
	 */
	public function export( array $module_ids, array $options = [], array &$stats = [] ): string|\WP_Error {
		$include_media = ! array_key_exists( 'include_media', $options ) || (bool) $options['include_media'];
		if ( ! class_exists( 'ZipArchive' ) ) {
			return new \WP_Error(
				'stagekitwp_import_export_no_zip',
				__( 'PHP ZipArchive extension is required for export. Please enable it on your server.', 'stagekitwp-io' )
			);
		}

		// Resolve module list.
		if ( in_array( 'all', $module_ids, true ) ) {
			$module_ids = array_keys( $this->modules );
		}

		$payloads    = [];
		$media_ids   = [];

		foreach ( $module_ids as $id ) {
			$module = $this->modules[ $id ] ?? null;
			if ( ! $module ) {
				continue;
			}

			$payload       = $module->export();
			$payloads[$id] = $payload;

			if ( $include_media ) {
				foreach ( $payload['posts'] ?? [] as $post ) {
					$media_ids = array_merge( $media_ids, $this->collect_post_media_ids( $post ) );
				}
			}
		}

		$manifest  = STAGEKITWP_IMPORT_EXPORT_Manifest::build( $payloads );
		$media_ids = $include_media ? array_values( array_unique( array_filter( $media_ids ) ) ) : [];

		// Count posts across all included modules for the stats readout.
		$post_count = 0;
		foreach ( $payloads as $payload ) {
			$post_count += count( $payload['posts'] ?? [] );
		}

		$stats = [
			'media_count' => count( $media_ids ), // unique attachments
			'media_files' => 0,                    // files packed (incl. size variants)
			'post_count'  => $post_count,
			'filesize'    => 0,
			'include_media' => $include_media,
		];

		return $this->build_zip( $manifest, $payloads, $media_ids, $stats );
	}

	/**
	 * Collect every attachment ID referenced by a single exported post.
	 *
	 * StageKitWP stores images in many meta keys (e.g. _stagekitwp_logo,
	 * _stagekitwp_show_sm_image, _stagekitwp_season_image_front, _stagekitwp_photo, _stagekitwp_venue_image,
	 * picture) and each value may be an attachment ID OR a raw upload URL. This
	 * scans broadly rather than matching a fixed key-name pattern:
	 *   1. the featured image (thumbnail_id)
	 *   2. every meta value that is an attachment ID or an upload URL
	 *   3. any upload URLs embedded in post_content (galleries, inline images)
	 *
	 * @param array<string,mixed> $post  One entry from a module payload's posts[].
	 * @return int[]  Attachment IDs referenced by this post.
	 */
	private function collect_post_media_ids( array $post ): array {
		$ids = [];

		// 1. Featured image.
		if ( ! empty( $post['thumbnail_id'] ) ) {
			$ids[] = (int) $post['thumbnail_id'];
		}

		// 2. Every meta value (recursively) — ID or URL.
		foreach ( (array) ( $post['meta'] ?? [] ) as $value ) {
			$this->scan_value_for_media( $value, $ids );
		}

		// 3. Upload URLs inside post_content.
		if ( ! empty( $post['post_content'] ) && is_string( $post['post_content'] ) ) {
			foreach ( $this->extract_upload_urls( $post['post_content'] ) as $url ) {
				$id = $this->attachment_id_from_url( $url );
				if ( $id ) {
					$ids[] = $id;
				}
			}
		}

		return $ids;
	}

	/**
	 * Recursively inspect a meta value and append any attachment IDs it implies.
	 *
	 * @param mixed $value
	 * @param int[] $ids  Collected IDs (by reference).
	 */
	private function scan_value_for_media( $value, array &$ids ): void {
		if ( is_array( $value ) ) {
			foreach ( $value as $item ) {
				$this->scan_value_for_media( $item, $ids );
			}
			return;
		}

		if ( is_numeric( $value ) ) {
			$id = (int) $value;
			if ( $id > 0 && 'attachment' === get_post_type( $id ) ) {
				$ids[] = $id;
			}
			return;
		}

		if ( is_string( $value ) && false !== strpos( $value, '/wp-content/uploads/' ) ) {
			foreach ( $this->extract_upload_urls( $value ) as $url ) {
				$id = $this->attachment_id_from_url( $url );
				if ( $id ) {
					$ids[] = $id;
				}
			}
		}
	}

	/**
	 * Pull all /wp-content/uploads/ URLs out of an arbitrary string.
	 *
	 * @param string $text
	 * @return string[]
	 */
	private function extract_upload_urls( string $text ): array {
		if ( ! preg_match_all( '#https?://[^\s"\'<>()]+/wp-content/uploads/[^\s"\'<>()]+#i', $text, $m ) ) {
			return [];
		}
		return array_unique( $m[0] );
	}

	/**
	 * Resolve an upload URL to an attachment ID, tolerating resized variants
	 * (e.g. image-300x200.jpg → the original image.jpg).
	 *
	 * @param string $url
	 * @return int  Attachment ID, or 0 when it can't be resolved.
	 */
	private function attachment_id_from_url( string $url ): int {
		$url = strtok( $url, '?#' );

		$id = attachment_url_to_postid( $url );
		if ( $id ) {
			return (int) $id;
		}

		// Strip a WordPress size suffix (-WxH) and retry against the original.
		$stripped = preg_replace( '#-\d+x\d+(\.[a-zA-Z0-9]+)$#', '$1', $url );
		if ( $stripped && $stripped !== $url ) {
			$id = attachment_url_to_postid( $stripped );
		}

		return (int) $id;
	}

	// ── ZIP assembly ─────────────────────────────────────────────────────────

	/**
	 * Assembles all payloads into a ZipArchive and saves it to the uploads/stagekitwp-io/ dir.
	 *
	 * @param array<string,mixed>                $manifest
	 * @param array<string, array<string,mixed>> $payloads
	 * @param int[]                              $media_ids
	 * @param array<string,mixed>                $stats  (out) media_files + filesize.
	 * @return string|WP_Error
	 */
	private function build_zip( array $manifest, array $payloads, array $media_ids, array &$stats = [] ): string|\WP_Error {
		$upload_dir = wp_upload_dir();
		$out_dir    = trailingslashit( $upload_dir['basedir'] ) . 'stagekitwp-io';
		wp_mkdir_p( $out_dir );

		$site_slug  = sanitize_title( get_bloginfo( 'name' ) );
		$timestamp  = gmdate( 'Y-m-d-His' );
		$zip_name   = "stagekitwp-export-{$site_slug}-{$timestamp}.zip";
		$zip_path   = trailingslashit( $out_dir ) . $zip_name;

		$zip = new \ZipArchive();
		$opened = $zip->open( $zip_path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE );

		if ( true !== $opened ) {
			return new \WP_Error(
				'stagekitwp_import_export_zip_open',
				sprintf( __( 'Could not create ZIP archive (ZipArchive error code %d).', 'stagekitwp-io' ), $opened )
			);
		}

		// manifest.json
		$zip->addFromString(
			STAGEKITWP_IMPORT_EXPORT_Manifest::FILENAME,
			wp_json_encode( $manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES )
		);

		// Module payloads.
		foreach ( $payloads as $id => $payload ) {
			$dir = $id . '/';

			if ( ! empty( $payload['posts'] ) ) {
				$zip->addFromString(
					$dir . 'posts.json',
					wp_json_encode( $payload['posts'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES )
				);
			}

			if ( ! empty( $payload['options'] ) ) {
				$zip->addFromString(
					$dir . 'options.json',
					wp_json_encode( $payload['options'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES )
				);
			}

			if ( ! empty( $payload['extra'] ) ) {
				$zip->addFromString(
					$dir . 'extra.json',
					wp_json_encode( $payload['extra'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES )
				);
			}
		}

		// Media files.
		$files_packed = 0;
		$media_index  = $this->add_media_to_zip( $zip, $media_ids, $files_packed );
		if ( ! empty( $media_index ) ) {
			$zip->addFromString(
				'media/index.json',
				wp_json_encode( $media_index, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES )
			);
		}

		$zip->close();

		if ( ! file_exists( $zip_path ) ) {
			return new \WP_Error( 'stagekitwp_import_export_zip_missing', __( 'ZIP file was not created. Check server write permissions.', 'stagekitwp-io' ) );
		}

		$stats['media_count'] = count( $media_index );
		$stats['media_files'] = $files_packed;
		$stats['filesize']    = (int) filesize( $zip_path );

		return $zip_path;
	}

	// ── Media packing ────────────────────────────────────────────────────────

	/**
	 * Copy attachment files into the media/ folder inside the ZIP.
	 *
	 * Returns an index array: attachment_id → { filename, mime_type, original_url }
	 *
	 * @param ZipArchive $zip
	 * @param int[]      $media_ids
	 * @param int        $files_packed  (out) total files added, incl. size variants.
	 * @return array<int, array<string,string>>
	 */
	private function add_media_to_zip( \ZipArchive $zip, array $media_ids, int &$files_packed = 0 ): array {
		$index = [];

		foreach ( $media_ids as $attachment_id ) {
			$file = get_attached_file( $attachment_id );
			if ( ! $file || ! file_exists( $file ) ) {
				continue;
			}

			$basename = basename( $file );
			$zip->addFile( $file, 'media/' . $basename );
			$files_packed++;

			$index[ $attachment_id ] = [
				'filename'     => $basename,
				'mime_type'    => get_post_mime_type( $attachment_id ) ?: '',
				'original_url' => wp_get_attachment_url( $attachment_id ) ?: '',
				'post_title'   => get_the_title( $attachment_id ),
				'alt_text'     => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ?: '',
			];

			// Also include any generated image sizes.
			$meta = wp_get_attachment_metadata( $attachment_id );
			if ( ! empty( $meta['sizes'] ) ) {
				$upload_dir = trailingslashit( dirname( $file ) );
				foreach ( $meta['sizes'] as $size_data ) {
					$size_file = $upload_dir . $size_data['file'];
					if ( file_exists( $size_file ) ) {
						$zip->addFile( $size_file, 'media/' . $size_data['file'] );
						$files_packed++;
					}
				}
			}
		}

		return $index;
	}
}
