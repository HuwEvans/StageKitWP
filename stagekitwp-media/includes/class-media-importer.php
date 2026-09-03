<?php
namespace SKWPM;

defined( 'ABSPATH' ) || exit;

/**
 * Shared helper for importing remote provider media files (Google Drive, Google Photos,
 * Dropbox, etc.) into StageKit Media storage and custom database tables.
 */
class MediaImporter {

	/**
	 * Import raw binary bytes into StageKit Media storage and database.
	 *
	 * @param string $bytes    Binary file content.
	 * @param string $filename Desired filename.
	 * @param array  $meta     Optional metadata (folder_id, provider, external_id, title, author, license).
	 * @return array|null The formatted StageKit Media item array, or null on failure.
	 */
	public static function import_bytes( string $bytes, string $filename, array $meta = [] ): ?array {
		$saved = Storage::save_file( $bytes, $filename, true );
		if ( ! $saved ) {
			return null;
		}

		$folder_id = isset( $meta['folder_id'] ) ? max( 0, (int) $meta['folder_id'] ) : 0;
		$provider  = sanitize_key( $meta['provider'] ?? 'local' );

		// If no folder specified but provider is known, optionally auto-place in provider folder
		if ( 0 === $folder_id && ! empty( $provider ) && 'local' !== $provider ) {
			$folder_id = self::get_or_create_provider_folder( $provider );
		}

		$title = sanitize_text_field( $meta['title'] ?? pathinfo( $filename, PATHINFO_FILENAME ) );

		$record_data = array_merge( $saved, [
			'folder_id'   => $folder_id,
			'provider'    => $provider ?: 'local',
			'external_id' => ! empty( $meta['external_id'] ) ? sanitize_text_field( $meta['external_id'] ) : null,
			'title'       => $title,
			'caption'     => $meta['caption'] ?? null,
			'alt_text'    => sanitize_text_field( $meta['alt_text'] ?? '' ),
			'author'      => sanitize_text_field( $meta['author'] ?? '' ),
			'license'     => sanitize_text_field( $meta['license'] ?? '' ),
			'sync_hash'   => ! empty( $meta['sync_hash'] ) ? sanitize_text_field( $meta['sync_hash'] ) : null,
		] );

		$media_id = MediaRepository::create( $record_data );
		if ( ! $media_id ) {
			Storage::delete_file( $saved['file_path'], $saved['thumbnails'] );
			return null;
		}

		return MediaRepository::get( $media_id );
	}

	/**
	 * Import a local file path into StageKit Media storage and database.
	 *
	 * @param string $source_path Local temporary file path.
	 * @param string $filename    Desired filename.
	 * @param array  $meta        Optional metadata.
	 * @return array|null The formatted StageKit Media item array, or null on failure.
	 */
	public static function import_file( string $source_path, string $filename, array $meta = [] ): ?array {
		$saved = Storage::save_file( $source_path, $filename, false );
		if ( ! $saved ) {
			return null;
		}

		$folder_id = isset( $meta['folder_id'] ) ? max( 0, (int) $meta['folder_id'] ) : 0;
		$provider  = sanitize_key( $meta['provider'] ?? 'local' );

		if ( 0 === $folder_id && ! empty( $provider ) && 'local' !== $provider ) {
			$folder_id = self::get_or_create_provider_folder( $provider );
		}

		$title = sanitize_text_field( $meta['title'] ?? pathinfo( $filename, PATHINFO_FILENAME ) );

		$record_data = array_merge( $saved, [
			'folder_id'   => $folder_id,
			'provider'    => $provider ?: 'local',
			'external_id' => ! empty( $meta['external_id'] ) ? sanitize_text_field( $meta['external_id'] ) : null,
			'title'       => $title,
			'caption'     => $meta['caption'] ?? null,
			'alt_text'    => sanitize_text_field( $meta['alt_text'] ?? '' ),
			'author'      => sanitize_text_field( $meta['author'] ?? '' ),
			'license'     => sanitize_text_field( $meta['license'] ?? '' ),
			'sync_hash'   => ! empty( $meta['sync_hash'] ) ? sanitize_text_field( $meta['sync_hash'] ) : null,
		] );

		$media_id = MediaRepository::create( $record_data );
		if ( ! $media_id ) {
			Storage::delete_file( $saved['file_path'], $saved['thumbnails'] );
			return null;
		}

		return MediaRepository::get( $media_id );
	}

	/**
	 * Find or create a default folder for a synced provider (e.g. "Google Drive", "Google Photos").
	 */
	public static function get_or_create_provider_folder( string $provider_slug, ?string $custom_name = null ): int {
		// First, check if admin configured a specific default folder in Settings
		$option_map = [
			'google-drive'  => 'skwpm_default_gdrive_folder',
			'google-photos' => 'skwpm_default_gphotos_folder',
			'dropbox'       => 'skwpm_default_dropbox_folder',
		];

		if ( isset( $option_map[ $provider_slug ] ) ) {
			$configured_id = (int) get_option( $option_map[ $provider_slug ], 0 );
			if ( $configured_id > 0 && FolderRepository::get( $configured_id ) ) {
				return $configured_id;
			}
		}

		$provider_obj = Registry::get( $provider_slug );
		$name         = $custom_name ?: ( $provider_obj ? $provider_obj->label() : ucwords( str_replace( '-', ' ', $provider_slug ) ) );
		$slug         = sanitize_title( $provider_slug );

		$existing = FolderRepository::get_by_slug( $slug, 0 );
		if ( $existing ) {
			return (int) $existing['id'];
		}

		$created_id = FolderRepository::create( [
			'name'      => $name,
			'slug'      => $slug,
			'parent_id' => 0,
			'provider'  => $provider_slug,
		] );

		return $created_id ?: 0;
	}

	/**
	 * Legacy helper to maintain backwards compatibility.
	 *
	 * @return int StageKit Media ID, or 0 on failure.
	 */
	public static function side_load( string $bytes, string $filename ): int {
		$item = self::import_bytes( $bytes, $filename );
		return $item ? (int) $item['id'] : 0;
	}
}
