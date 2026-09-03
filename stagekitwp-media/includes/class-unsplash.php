<?php
namespace SKWPM;

class Unsplash implements Provider {

	public function slug(): string { return 'unsplash'; }
	public function label(): string { return 'Unsplash'; }

	public function is_configured(): bool {
		return (bool) get_option( 'skwpm_unsplash_key' );
	}

	public function search( string $query, array $args = [] ): array {
		$key = get_option( 'skwpm_unsplash_key' );
		$cache = 'skwpm_unsplash_' . md5( $query . wp_json_encode( $args ) );
		$cached = get_transient( $cache );
		if ( false !== $cached ) {
			return $cached;
		}

		$params = array_merge( [ 'query' => $query, 'per_page' => 30 ], $args );
		$request = wp_remote_get(
			Http::url( 'https://api.unsplash.com/search/photos', $params ),
			[ 'headers' => [ 'Authorization' => 'Client-ID ' . $key ], 'timeout' => 15 ]
		);

		if ( is_wp_error( $request ) || 200 !== wp_remote_retrieve_response_code( $request ) ) {
			return [];
		}

		$body  = json_decode( wp_remote_retrieve_body( $request ), true );
		$items = array_map( [ $this, 'normalize' ], $body['results'] ?? [] );

		set_transient( $cache, $items, 6 * HOUR_IN_SECONDS );
		return $items;
	}

	public function fetch( string $external_id ): ?array {
		$key = get_option( 'skwpm_unsplash_key' );
		$request = wp_remote_get(
			'https://api.unsplash.com/photos/' . rawurlencode( $external_id ),
			[ 'headers' => [ 'Authorization' => 'Client-ID ' . $key ], 'timeout' => 15 ]
		);
		if ( is_wp_error( $request ) || 200 !== wp_remote_retrieve_response_code( $request ) ) {
			return null;
		}
		$photo = json_decode( wp_remote_retrieve_body( $request ), true );
		return $photo ? $this->normalize( $photo ) : null;
	}

	private function normalize( array $photo ): array {
		$urls = $photo['urls'] ?? [];
		return [
			'provider'    => $this->slug(),
			'external_id' => (string) ( $photo['id'] ?? '' ),
			'type'        => 'image',
			'url'         => $urls['regular'] ?? ( $urls['full'] ?? '' ),
			'thumb'       => $urls['small'] ?? ( $urls['thumb'] ?? '' ),
			'title'       => $photo['alt_description'] ?? ( $photo['description'] ?? '' ),
			'author'      => $photo['user']['name'] ?? '',
			'license'     => 'Unsplash License',
		];
	}
}
