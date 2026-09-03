<?php
namespace SKWPM;

class Vimeo implements Provider {

	public function slug(): string { return 'vimeo'; }
	public function label(): string { return 'Vimeo'; }

	public function is_configured(): bool {
		return (bool) get_option( 'skwpm_vimeo_token' );
	}

	private function headers(): array {
		return [
			'Authorization' => 'bearer ' . get_option( 'skwpm_vimeo_token' ),
			'Accept'        => 'application/vnd.vimeo.*+json;version=3.4',
		];
	}

	public function search( string $query, array $args = [] ): array {
		$cache  = 'skwpm_vimeo_' . md5( $query . wp_json_encode( $args ) );
		$cached = get_transient( $cache );
		if ( false !== $cached ) {
			return $cached;
		}

		$params  = array_merge( [ 'query' => $query, 'per_page' => 30 ], $args );
		$request = wp_remote_get(
			Http::url( 'https://api.vimeo.com/videos', $params ),
			[ 'headers' => $this->headers(), 'timeout' => 15 ]
		);

		if ( is_wp_error( $request ) || 200 !== wp_remote_retrieve_response_code( $request ) ) {
			return [];
		}

		$body  = json_decode( wp_remote_retrieve_body( $request ), true );
		$items = array_map( [ $this, 'normalize' ], $body['data'] ?? [] );

		set_transient( $cache, $items, 6 * HOUR_IN_SECONDS );
		return $items;
	}

	public function fetch( string $external_id ): ?array {
		$request = wp_remote_get(
			'https://api.vimeo.com/videos/' . rawurlencode( $external_id ),
			[ 'headers' => $this->headers(), 'timeout' => 15 ]
		);
		if ( is_wp_error( $request ) || 200 !== wp_remote_retrieve_response_code( $request ) ) {
			return null;
		}
		$video = json_decode( wp_remote_retrieve_body( $request ), true );
		return $video ? $this->normalize( $video ) : null;
	}

	private function normalize( array $video ): array {
		$id    = basename( $video['uri'] ?? '' );
		$sizes = $video['pictures']['sizes'] ?? [];
		// Prefer a mid-size thumbnail over the smallest or largest available.
		$mid = $sizes ? $sizes[ (int) floor( ( count( $sizes ) - 1 ) / 2 ) ] : [];
		return [
			'provider'    => $this->slug(),
			'external_id' => $id,
			'type'        => 'video',
			'url'         => 'https://vimeo.com/' . $id,
			'thumb'       => $mid['link'] ?? '',
			'title'       => $video['name'] ?? '',
			'author'      => $video['user']['name'] ?? '',
			'license'     => 'Vimeo',
		];
	}
}
