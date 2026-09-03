<?php
namespace SKWPM;

/**
 * Google Photos Picker API integration.
 *
 * Unlike the keyword-search providers, Google Photos no longer allows an app
 * to search a user's library directly — the Picker API instead opens a
 * session that the user completes in a Google-hosted picker UI. Picker media
 * URLs also require a short-lived OAuth Bearer token, so picked items are
 * side-loaded into the WordPress Media Library on import instead of being
 * hot-linked from Google.
 */
class GooglePhotos implements Provider {

	private const API = 'https://photospicker.googleapis.com/v1';

	public function slug(): string { return 'google-photos'; }
	public function label(): string { return 'Google Photos'; }

	public function is_configured(): bool {
		return OAuth::is_connected( 'google-photos' );
	}

	/** Not used — Google Photos has no keyword search; see start_session()/list_session_items(). */
	public function search( string $query, array $args = [] ): array {
		return [];
	}

	/** Not used — see import_item() for fetching/importing a single picked item. */
	public function fetch( string $external_id ): ?array {
		return null;
	}

	/** Start a new Picker session; the admin UI opens the returned pickerUri for the user to select photos in. */
	public function start_session(): ?array {
		$token = OAuth::get_access_token( 'google-photos' );
		if ( ! $token ) {
			Debug::set( __( 'Google Photos is not connected (no access token). Connect it under Settings.', 'stagekitwp-media' ) );
			return null;
		}

		$request = wp_remote_post( self::API . '/sessions', [
			'timeout' => 15,
			'headers' => [ 'Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json' ],
			'body'    => '{}',
		] );
		if ( is_wp_error( $request ) || 200 !== wp_remote_retrieve_response_code( $request ) ) {
			Debug::set( Debug::describe_response( $request ) );
			return null;
		}

		$session = json_decode( wp_remote_retrieve_body( $request ), true );
		return is_array( $session ) ? $session : null;
	}

	/** Poll whether the user has finished picking in the session's Google-hosted UI. */
	public function session_status( string $session_id ): ?array {
		$token = OAuth::get_access_token( 'google-photos' );
		if ( ! $token ) {
			Debug::set( __( 'Google Photos is not connected (no access token). Connect it under Settings.', 'stagekitwp-media' ) );
			return null;
		}

		$request = wp_remote_get( self::API . '/sessions/' . rawurlencode( $session_id ), [
			'timeout' => 15,
			'headers' => [ 'Authorization' => 'Bearer ' . $token ],
		] );
		if ( is_wp_error( $request ) || 200 !== wp_remote_retrieve_response_code( $request ) ) {
			Debug::set( Debug::describe_response( $request ) );
			return null;
		}

		return json_decode( wp_remote_retrieve_body( $request ), true ) ?: null;
	}

	/** List the (not-yet-imported) media items the user picked in a completed session. */
	public function list_session_items( string $session_id ): array {
		$token = OAuth::get_access_token( 'google-photos' );
		if ( ! $token ) {
			Debug::set( __( 'Google Photos is not connected (no access token). Connect it under Settings.', 'stagekitwp-media' ) );
			return [];
		}

		$request = wp_remote_get(
			Http::url( self::API . '/mediaItems', [ 'sessionId' => $session_id, 'pageSize' => 100 ] ),
			[ 'timeout' => 20, 'headers' => [ 'Authorization' => 'Bearer ' . $token ] ]
		);
		if ( is_wp_error( $request ) || 200 !== wp_remote_retrieve_response_code( $request ) ) {
			Debug::set( Debug::describe_response( $request ) );
			return [];
		}

		$body  = json_decode( wp_remote_retrieve_body( $request ), true );
		$items = [];
		foreach ( $body['mediaItems'] ?? [] as $media ) {
			$file    = $media['mediaFile'] ?? [];
			$items[] = [
				'provider'    => $this->slug(),
				'external_id' => $media['id'] ?? '',
				'type'        => 0 === strpos( $file['mimeType'] ?? '', 'video/' ) ? 'video' : 'image',
				'title'       => $file['filename'] ?? '',
				'author'      => '',
				'license'     => '',
				// Left empty until import_item() side-loads the file into the Media Library.
				'thumb'       => '',
				'url'         => '',
			];
		}

		return $items;
	}

	/**
	 * Download one picked media item's bytes (via the session's short-lived
	 * authenticated baseUrl) and store it in StageKit Media storage.
	 */
	public function import_item( string $session_id, string $media_item_id, int $folder_id = 0 ): ?array {
		$token = OAuth::get_access_token( 'google-photos' );
		if ( ! $token ) {
			Debug::set( __( 'Google Photos is not connected (no access token). Connect it under Settings.', 'stagekitwp-media' ) );
			return null;
		}

		$media = $this->find_session_media_item( $session_id, $media_item_id, $token );
		if ( ! $media ) {
			return null;
		}

		$file = $media['mediaFile'] ?? [];
		$base = $file['baseUrl'] ?? '';
		if ( '' === $base ) {
			Debug::set( __( 'Google Photos returned no baseUrl for this item.', 'stagekitwp-media' ) );
			return null;
		}

		$is_video = 0 === strpos( $file['mimeType'] ?? '', 'video/' );
		// Per the Picker API docs: images need the download `=d` param; videos need `=dv`.
		// A bare width (e.g. "=w2048") without a paired height is invalid and the request fails.
		$download_url = $base . ( $is_video ? '=dv' : '=d' );

		$bytes = wp_remote_get( $download_url, [ 'timeout' => 30, 'headers' => [ 'Authorization' => 'Bearer ' . $token ] ] );
		if ( is_wp_error( $bytes ) || 200 !== wp_remote_retrieve_response_code( $bytes ) ) {
			Debug::set( Debug::describe_response( $bytes ) );
			return null;
		}

		$filename = sanitize_file_name( $file['filename'] ?? ( $media_item_id . ( $is_video ? '.mp4' : '.jpg' ) ) );
		$imported = MediaImporter::import_bytes(
			wp_remote_retrieve_body( $bytes ),
			$filename,
			[
				'provider'    => $this->slug(),
				'external_id' => $media_item_id,
				'folder_id'   => $folder_id,
				'title'       => $file['filename'] ?? '',
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

	/**
	 * The Picker API has no "get single item by id" endpoint — only
	 * mediaItems.list. Page through the session's picked items to find the
	 * one the admin clicked "Add" on.
	 */
	private function find_session_media_item( string $session_id, string $media_item_id, string $token ): ?array {
		$page_token = '';
		do {
			$params = [ 'sessionId' => $session_id, 'pageSize' => 100 ];
			if ( $page_token ) {
				$params['pageToken'] = $page_token;
			}

			$request = wp_remote_get(
				Http::url( self::API . '/mediaItems', $params ),
				[ 'timeout' => 20, 'headers' => [ 'Authorization' => 'Bearer ' . $token ] ]
			);
			if ( is_wp_error( $request ) || 200 !== wp_remote_retrieve_response_code( $request ) ) {
				Debug::set( Debug::describe_response( $request ) );
				return null;
			}

			$body = json_decode( wp_remote_retrieve_body( $request ), true );
			foreach ( $body['mediaItems'] ?? [] as $item ) {
				if ( ( $item['id'] ?? '' ) === $media_item_id ) {
					return $item;
				}
			}
			$page_token = $body['nextPageToken'] ?? '';
		} while ( $page_token );

		Debug::set( __( 'Could not find that item in the picker session (it may have expired).', 'stagekitwp-media' ) );
		return null;
	}

}
