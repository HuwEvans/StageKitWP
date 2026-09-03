<?php
namespace SKWPM;

class GoogleDrive implements Provider {

	public function slug(): string { return 'google-drive'; }
	public function label(): string { return 'Google Drive'; }

	public function is_configured(): bool {
		return OAuth::is_connected( 'google-drive' );
	}

	public function search( string $query, array $args = [] ): array {
		$token = OAuth::get_access_token( 'google-drive' );
		if ( ! $token ) {
			Debug::set( __( 'Google Drive is not connected (no access token). Connect it under Settings.', 'stagekitwp-media' ) );
			return [];
		}

		$cache  = 'skwpm_gdrive_' . md5( $query . wp_json_encode( $args ) );
		$cached = get_transient( $cache );
		if ( false !== $cached ) {
			return $cached;
		}

		// Drive only matches the file *name*, not photo content/date/location — an empty
		// query instead browses the most recently modified images/videos in the Drive.
		$conditions = [ 'trashed = false', "(mimeType contains 'image/' or mimeType contains 'video/')" ];
		if ( '' !== $query ) {
			$conditions[] = sprintf( "name contains '%s'", str_replace( "'", "\\'", $query ) );
		}

		$params = array_merge( [
			'q'        => implode( ' and ', $conditions ),
			'fields'   => 'files(id,name,mimeType)',
			'orderBy'  => 'modifiedTime desc',
			'pageSize' => 30,
		], $args );

		$request = wp_remote_get(
			Http::url( 'https://www.googleapis.com/drive/v3/files', $params ),
			[ 'headers' => [ 'Authorization' => 'Bearer ' . $token ], 'timeout' => 15 ]
		);

		if ( is_wp_error( $request ) || 200 !== wp_remote_retrieve_response_code( $request ) ) {
			Debug::set( Debug::describe_response( $request ) );
			return [];
		}

		$body  = json_decode( wp_remote_retrieve_body( $request ), true );
		$items = array_map( [ $this, 'normalize' ], $body['files'] ?? [] );

		set_transient( $cache, $items, 6 * HOUR_IN_SECONDS );
		return $items;
	}

	public function fetch( string $external_id ): ?array {
		$token = OAuth::get_access_token( 'google-drive' );
		if ( ! $token ) {
			return null;
		}

		$request = wp_remote_get(
			Http::url(
				'https://www.googleapis.com/drive/v3/files/' . rawurlencode( $external_id ),
				[ 'fields' => 'id,name,mimeType' ]
			),
			[ 'headers' => [ 'Authorization' => 'Bearer ' . $token ], 'timeout' => 15 ]
		);
		if ( is_wp_error( $request ) || 200 !== wp_remote_retrieve_response_code( $request ) ) {
			return null;
		}

		$file = json_decode( wp_remote_retrieve_body( $request ), true );
		return is_array( $file ) ? $this->normalize( $file ) : null;
	}

	/**
	 * Download one picked file's bytes (using the stored OAuth token) and
	 * store it in StageKit Media storage.
	 */
	public function import_item( string $file_id, int $folder_id = 0 ): ?array {
		$token = OAuth::get_access_token( 'google-drive' );
		if ( ! $token ) {
			Debug::set( __( 'Google Drive is not connected (no access token). Connect it under Settings.', 'stagekitwp-media' ) );
			return null;
		}

		$meta_request = wp_remote_get(
			Http::url( 'https://www.googleapis.com/drive/v3/files/' . rawurlencode( $file_id ), [ 'fields' => 'id,name,mimeType' ] ),
			[ 'timeout' => 15, 'headers' => [ 'Authorization' => 'Bearer ' . $token ] ]
		);
		if ( is_wp_error( $meta_request ) || 200 !== wp_remote_retrieve_response_code( $meta_request ) ) {
			Debug::set( Debug::describe_response( $meta_request ) );
			return null;
		}

		$meta     = json_decode( wp_remote_retrieve_body( $meta_request ), true );
		$is_video = 0 === strpos( $meta['mimeType'] ?? '', 'video/' );
		$filename = sanitize_file_name( $meta['name'] ?? ( $file_id . ( $is_video ? '.mp4' : '.jpg' ) ) );

		$bytes_request = wp_remote_get(
			Http::url( 'https://www.googleapis.com/drive/v3/files/' . rawurlencode( $file_id ), [ 'alt' => 'media' ] ),
			[ 'timeout' => 30, 'headers' => [ 'Authorization' => 'Bearer ' . $token ] ]
		);
		if ( is_wp_error( $bytes_request ) || 200 !== wp_remote_retrieve_response_code( $bytes_request ) ) {
			Debug::set( Debug::describe_response( $bytes_request ) );
			return null;
		}

		$imported = MediaImporter::import_bytes(
			wp_remote_retrieve_body( $bytes_request ),
			$filename,
			[
				'provider'    => $this->slug(),
				'external_id' => $file_id,
				'folder_id'   => $folder_id,
				'title'       => $meta['name'] ?? '',
			]
		);

		if ( ! $imported ) {
			Debug::set( __( 'Could not save the downloaded file.', 'stagekitwp-media' ) );
			return null;
		}

		return [
			'id'          => $imported['id'],
			'provider'    => $this->slug(),
			'external_id' => (string) $imported['id'],
			'type'        => $is_video ? 'video' : 'image',
			'url'         => $imported['file_url'],
			'thumb'       => $imported['thumb_url'],
			'title'       => $imported['title'],
			'author'      => '',
			'license'     => '',
		];
	}

	private function normalize( array $file ): array {
		$id       = $file['id'] ?? '';
		$is_video = 0 === strpos( $file['mimeType'] ?? '', 'video/' );

		return [
			'provider'    => $this->slug(),
			'external_id' => $id,
			'type'        => $is_video ? 'video' : 'image',
			// Left empty until import_item() side-loads the file into the Media Library —
			// Drive's own thumbnail/view URLs aren't reliably viewable outside the owner's browser.
			'url'         => '',
			'thumb'       => '',
			'title'       => $file['name'] ?? '',
			'author'      => '',
			'license'     => '',
		];
	}
}
