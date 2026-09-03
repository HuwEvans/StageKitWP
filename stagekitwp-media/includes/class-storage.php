<?php
namespace SKWPM;

defined( 'ABSPATH' ) || exit;

/**
 * Handles physical file storage, security, MIME validation, and thumbnail generation
 * for StageKit WP Media outside the standard WordPress media library.
 */
class Storage {

	public const STORAGE_FOLDER = 'stagekit-media';

	/**
	 * Whitelist of allowed MIME types and file extensions.
	 */
	public const ALLOWED_MIME_TYPES = [
		// Images
		'jpg|jpeg|jpe' => 'image/jpeg',
		'png'          => 'image/png',
		'gif'          => 'image/gif',
		'webp'         => 'image/webp',
		'svg'          => 'image/svg+xml',
		'avif'         => 'image/avif',
		// Videos
		'mp4|m4v'      => 'video/mp4',
		'webm'         => 'video/webm',
		'ogv'          => 'video/ogg',
		'mov|qt'       => 'video/quicktime',
		// Audio
		'mp3|m4a'      => 'audio/mpeg',
		'wav'          => 'audio/wav',
		'ogg|oga'      => 'audio/ogg',
		// Documents
		'pdf'          => 'application/pdf',
	];

	/**
	 * Thumbnail size definitions (width, height, crop).
	 */
	public const THUMBNAIL_SIZES = [
		'thumb'  => [ 'width' => 150, 'height' => 150, 'crop' => true ],
		'medium' => [ 'width' => 600, 'height' => 600, 'crop' => false ],
		'large'  => [ 'width' => 1200, 'height' => 1200, 'crop' => false ],
	];

	/**
	 * Get the absolute path to the StageKit media storage directory.
	 */
	public static function get_base_dir(): string {
		$upload_dir = wp_upload_dir();
		return trailingslashit( $upload_dir['basedir'] ) . self::STORAGE_FOLDER;
	}

	/**
	 * Get the public base URL for StageKit media storage.
	 */
	public static function get_base_url(): string {
		$upload_dir = wp_upload_dir();
		return trailingslashit( $upload_dir['baseurl'] ) . self::STORAGE_FOLDER;
	}

	/**
	 * Ensure the storage directory and protection files (.htaccess, index.php) exist.
	 */
	public static function init_storage(): bool {
		$dir        = self::get_base_dir();
		$thumbs_dir = $dir . '/thumbs';

		if ( ! wp_mkdir_p( $dir ) || ! wp_mkdir_p( $thumbs_dir ) ) {
			return false;
		}

		// Security: Prevent direct script execution in the media directory.
		$htaccess_file = trailingslashit( $dir ) . '.htaccess';
		if ( ! file_exists( $htaccess_file ) ) {
			$htaccess_content  = "# Deny execution of scripts in StageKit Media directory\n";
			$htaccess_content .= "<FilesMatch \"\\.(php|phtml|php3|php4|php5|php7|phps)$\">\n";
			$htaccess_content .= "    Deny from all\n";
			$htaccess_content .= "</FilesMatch>\n";
			@file_put_contents( $htaccess_file, $htaccess_content );
		}

		// Prevent directory listing
		$index_file = trailingslashit( $dir ) . 'index.php';
		if ( ! file_exists( $index_file ) ) {
			@file_put_contents( $index_file, "<?php // Silence is golden\n" );
		}

		$thumbs_index = trailingslashit( $thumbs_dir ) . 'index.php';
		if ( ! file_exists( $thumbs_index ) ) {
			@file_put_contents( $thumbs_index, "<?php // Silence is golden\n" );
		}

		return true;
	}

	/**
	 * Validate a file before saving.
	 *
	 * @param string $filename  The original or proposed filename.
	 * @param string $temp_path Temporary file path or string content for inspection.
	 * @return array{valid: bool, error?: string, mime?: string, ext?: string}
	 */
	public static function validate_file( string $filename, string $temp_path = '' ): array {
		$wp_filetype = wp_check_filetype( $filename, self::ALLOWED_MIME_TYPES );
		$ext         = strtolower( $wp_filetype['ext'] ?? '' );
		$mime        = $wp_filetype['type'] ?? '';

		if ( empty( $ext ) || empty( $mime ) ) {
			return [
				'valid' => false,
				'error' => __( 'File type is not permitted for security reasons.', 'stagekitwp-media' ),
			];
		}

		// If a temporary physical file is available, verify real MIME type
		if ( ! empty( $temp_path ) && file_exists( $temp_path ) ) {
			$real_mime = function_exists( 'mime_content_type' ) ? @mime_content_type( $temp_path ) : '';
			if ( $real_mime && 'text/x-php' === $real_mime ) {
				return [
					'valid' => false,
					'error' => __( 'Executable scripts cannot be uploaded.', 'stagekitwp-media' ),
				];
			}
		}

		return [
			'valid' => true,
			'mime'  => $mime,
			'ext'   => $ext,
		];
	}

	/**
	 * Save a file to StageKit media storage.
	 *
	 * @param string $source       File contents (raw string) or path to temporary uploaded file.
	 * @param string $filename     Target or original filename.
	 * @param bool   $is_raw_bytes True if $source is binary string, false if $source is a temp file path.
	 * @return array|null Structured file data array, or null on failure.
	 */
	public static function save_file( string $source, string $filename, bool $is_raw_bytes = false ): ?array {
		if ( ! self::init_storage() ) {
			return null;
		}

		$temp_path = $is_raw_bytes ? '' : $source;
		$validation = self::validate_file( $filename, $temp_path );
		if ( ! $validation['valid'] ) {
			return null;
		}

		$base_dir = self::get_base_dir();
		$base_url = self::get_base_url();

		$safe_name = self::generate_unique_filename( $filename, $base_dir );
		$dest_path = trailingslashit( $base_dir ) . $safe_name;

		if ( $is_raw_bytes ) {
			$saved = (bool) @file_put_contents( $dest_path, $source );
		} else {
			if ( is_uploaded_file( $source ) ) {
				$saved = @move_uploaded_file( $source, $dest_path );
			} else {
				$saved = @copy( $source, $dest_path );
			}
		}

		if ( ! $saved || ! file_exists( $dest_path ) ) {
			return null;
		}

		@chmod( $dest_path, 0644 );

		$file_size = (int) filesize( $dest_path );
		$file_url  = trailingslashit( $base_url ) . $safe_name;
		$mime_type = $validation['mime'];

		$width      = null;
		$height     = null;
		$thumbnails = [];

		// If the file is an image, inspect dimensions and generate thumbnails
		if ( 0 === strpos( $mime_type, 'image/' ) && 'image/svg+xml' !== $mime_type ) {
			$image_size = @getimagesize( $dest_path );
			if ( is_array( $image_size ) ) {
				$width  = (int) $image_size[0];
				$height = (int) $image_size[1];
			}

			$thumbnails = self::generate_thumbnails( $dest_path, $safe_name );
		}

		return [
			'filename'   => $safe_name,
			'file_path'  => $safe_name,
			'file_url'   => $file_url,
			'mime_type'  => $mime_type,
			'file_size'  => $file_size,
			'width'      => $width,
			'height'     => $height,
			'thumbnails' => $thumbnails,
		];
	}

	/**
	 * Generate responsive thumbnails for an image.
	 */
	public static function generate_thumbnails( string $image_path, string $safe_name ): array {
		if ( ! file_exists( $image_path ) ) {
			return [];
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';

		$thumbs     = [];
		$base_url   = self::get_base_url();
		$base_dir   = self::get_base_dir();
		$thumbs_dir = trailingslashit( $base_dir ) . 'thumbs';
		$thumbs_url = trailingslashit( $base_url ) . 'thumbs';

		$info = pathinfo( $safe_name );
		$name = $info['filename'];
		$ext  = $info['extension'] ?? 'jpg';

		foreach ( self::THUMBNAIL_SIZES as $size_key => $size_cfg ) {
			$editor = wp_get_image_editor( $image_path );
			if ( is_wp_error( $editor ) ) {
				continue;
			}

			$resized = $editor->resize( $size_cfg['width'], $size_cfg['height'], $size_cfg['crop'] );
			if ( is_wp_error( $resized ) ) {
				continue;
			}

			$thumb_filename = sprintf( '%s-%dx%d.%s', $name, $size_cfg['width'], $size_cfg['height'], $ext );
			$thumb_dest     = trailingslashit( $thumbs_dir ) . $thumb_filename;

			$saved = $editor->save( $thumb_dest );
			if ( ! is_wp_error( $saved ) && file_exists( $thumb_dest ) ) {
				@chmod( $thumb_dest, 0644 );
				$size_info = $editor->get_size();

				$thumbs[ $size_key ] = [
					'file'   => 'thumbs/' . $thumb_filename,
					'url'    => trailingslashit( $thumbs_url ) . $thumb_filename,
					'width'  => $size_info['width'] ?? $size_cfg['width'],
					'height' => $size_info['height'] ?? $size_cfg['height'],
				];
			}
		}

		return $thumbs;
	}

	/**
	 * Delete a stored file and all its associated thumbnails.
	 */
	public static function delete_file( string $relative_path, ?array $thumbnails = null ): bool {
		$base_dir = self::get_base_dir();
		$path     = trailingslashit( $base_dir ) . ltrim( $relative_path, '/\\' );

		if ( file_exists( $path ) && is_file( $path ) ) {
			@unlink( $path );
		}

		if ( is_array( $thumbnails ) ) {
			foreach ( $thumbnails as $thumb ) {
				if ( ! empty( $thumb['file'] ) ) {
					$thumb_path = trailingslashit( $base_dir ) . ltrim( $thumb['file'], '/\\' );
					if ( file_exists( $thumb_path ) && is_file( $thumb_path ) ) {
						@unlink( $thumb_path );
					}
				}
			}
		}

		return ! file_exists( $path );
	}

	/**
	 * Generate a unique, sanitized filename within the storage directory.
	 */
	private static function generate_unique_filename( string $filename, string $dir ): string {
		$filename = sanitize_file_name( $filename );
		$info     = pathinfo( $filename );
		$ext      = ! empty( $info['extension'] ) ? '.' . $info['extension'] : '';
		$name     = $info['filename'];

		$candidate = $filename;
		$counter   = 1;

		while ( file_exists( trailingslashit( $dir ) . $candidate ) ) {
			$candidate = sprintf( '%s-%d%s', $name, $counter, $ext );
			$counter++;
		}

		return $candidate;
	}
}
