<?php
namespace SKWPM;

class Pexels implements Provider {

	public function slug(): string { return 'pexels'; }
	public function label(): string { return 'Pexels'; }

	public function is_configured(): bool {
		return (bool) get_option( 'skwpm_pexels_key' );
	}

	public function search( string $query, array $args = [] ): array {
		$key = get_option( 'skwpm_pexels_key' );
		$cache = 'skwpm_pexels_' . md5( $query . wp_json_encode( $args ) );
		$cached = get_transient( $cache );
		if ( false !== $cached ) {
			return $cached;
		}

		$params = array_merge( [ 'query' => $query, 'per_page' => 30 ], $args );
		$request = wp_remote_get(
			Http::url( 'https://api.pexels.com/v1/search', $params ),
			[ 'headers' => [ 'Authorization' => $key ], 'timeout' => 15 ]
		);

		if ( is_wp_error( $request ) || 200 !== wp_remote_retrieve_response_code( $request ) ) {
			return [];
		}

		$body = json_decode( wp_remote_retrieve_body( $request ), true );
		$items = array_map( [ $this, 'normalize' ], $body['photos'] ?? [] );

		set_transient( $cache, $items, 6 * HOUR_IN_SECONDS );
		return $items;
	}

	public function fetch( string $external_id ): ?array {
		$key = get_option( 'skwpm_pexels_key' );
		$request = wp_remote_get(
			'https://api.pexels.com/v1/photos/' . rawurlencode( $external_id ),
			[ 'headers' => [ 'Authorization' => $key ], 'timeout' => 15 ]
		);
		if ( is_wp_error( $request ) || 200 !== wp_remote_retrieve_response_code( $request ) ) {
			return null;
		}
		return $this->normalize( json_decode( wp_remote_retrieve_body( $request ), true ) );
	}

	private function normalize( array $photo ): array {
		$src = $photo['src'] ?? [];
		return [
			'provider' => $this->slug(),
			'external_id' => (string) ( $photo['id'] ?? '' ),
			'type' => 'image',
			'url' => $src['original'] ?? '',
			'thumb' => $src['medium'] ?? '',
			'title' => $photo['alt'] ?? '',
			'author' => $photo['photographer'] ?? '',
			'license' => 'Pexels License',
		];
	}
}
