<?php
namespace SKWPM;

class Dropbox implements Provider {

	public function slug(): string { return 'dropbox'; }
	public function label(): string { return 'Dropbox'; }

	public function is_configured(): bool {
		return OAuth::is_connected( 'dropbox' );
	}

	public function search( string $query, array $args = [] ): array {
		$token = OAuth::get_access_token( 'dropbox' );
		if ( ! $token ) {
			return [];
		}

		$cache  = 'skwpm_dropbox_' . md5( $query . wp_json_encode( $args ) );
		$cached = get_transient( $cache );
		if ( false !== $cached ) {
			return $cached;
		}

		$request = wp_remote_post( 'https://api.dropboxapi.com/2/files/search_v2', [
			'timeout' => 20,
			'headers' => [
				'Authorization' => 'Bearer ' . $token,
				'Content-Type'  => 'application/json',
			],
			'body' => wp_json_encode( array_merge( [
				'query'   => $query,
				'options' => [
					'max_results'     => 20,
					'file_categories' => [ 'image', 'video' ],
				],
			], $args ) ),
		] );

		if ( is_wp_error( $request ) || 200 !== wp_remote_retrieve_response_code( $request ) ) {
			return [];
		}

		$decoded = json_decode( wp_remote_retrieve_body( $request ), true );
		$items   = [];
		foreach ( $decoded['matches'] ?? [] as $match ) {
			$file = $match['metadata']['metadata'] ?? [];
			if ( 'file' !== ( $file['.tag'] ?? '' ) ) {
				continue;
			}
			$normalized = $this->normalize( $file, $token );
			if ( $normalized ) {
				$items[] = $normalized;
			}
		}

		set_transient( $cache, $items, 6 * HOUR_IN_SECONDS );
		return $items;
	}

	public function fetch( string $external_id ): ?array {
		$token = OAuth::get_access_token( 'dropbox' );
		if ( ! $token ) {
			return null;
		}

		$request = wp_remote_post( 'https://api.dropboxapi.com/2/files/get_metadata', [
			'timeout' => 15,
			'headers' => [
				'Authorization' => 'Bearer ' . $token,
				'Content-Type'  => 'application/json',
			],
			'body' => wp_json_encode( [ 'path' => $external_id ] ),
		] );
		if ( is_wp_error( $request ) || 200 !== wp_remote_retrieve_response_code( $request ) ) {
			return null;
		}

		$file = json_decode( wp_remote_retrieve_body( $request ), true );
		return is_array( $file ) ? $this->normalize( $file, $token ) : null;
	}

	private function normalize( array $file, string $token ): ?array {
		$id   = $file['id'] ?? '';
		$path = $file['path_lower'] ?? ( $file['path_display'] ?? '' );
		if ( '' === $id || '' === $path ) {
			return null;
		}

		$url = $this->shared_link( $path, $token );
		if ( '' === $url ) {
			return null;
		}

		$name     = $file['name'] ?? '';
		$is_video = (bool) preg_match( '/\.(mp4|mov|m4v|webm)$/i', $name );

		return [
			'provider'    => $this->slug(),
			'external_id' => $id,
			'type'        => $is_video ? 'video' : 'image',
			'url'         => $url,
			'thumb'       => $url,
			'title'       => $name,
			'author'      => '',
			'license'     => '',
		];
	}

	/**
	 * Get (or create) a shared link for a Dropbox path and convert it into a
	 * direct-view URL suitable as an <img>/<video> src, instead of Dropbox's
	 * own web-viewer page.
	 */
	private function shared_link( string $path, string $token ): string {
		$headers = [
			'Authorization' => 'Bearer ' . $token,
			'Content-Type'  => 'application/json',
		];

		$create = wp_remote_post( 'https://api.dropboxapi.com/2/sharing/create_shared_link_with_settings', [
			'timeout' => 15,
			'headers' => $headers,
			'body'    => wp_json_encode( [ 'path' => $path ] ),
		] );

		if ( is_wp_error( $create ) ) {
			return '';
		}

		$body = json_decode( wp_remote_retrieve_body( $create ), true );

		if ( 200 === wp_remote_retrieve_response_code( $create ) ) {
			return $this->to_raw_url( $body['url'] ?? '' );
		}

		// A link may already exist — Dropbox returns a 409 with the existing link's URL embedded in the error payload.
		$existing = $body['error']['shared_link_already_exists']['metadata']['url'] ?? '';
		return $existing ? $this->to_raw_url( $existing ) : '';
	}

	private function to_raw_url( string $shared_url ): string {
		if ( '' === $shared_url ) {
			return '';
		}
		return add_query_arg( 'raw', '1', remove_query_arg( 'dl', $shared_url ) );
	}
}
