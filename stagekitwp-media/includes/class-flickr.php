<?php
namespace SKWPM;

class Flickr implements Provider {

	/** Common Flickr numeric license codes we bother to name; anything else falls back to "Flickr". */
	private const LICENSES = [
		0  => 'All Rights Reserved',
		4  => 'CC BY 2.0',
		5  => 'CC BY-SA 2.0',
		6  => 'CC BY-ND 2.0',
		7  => 'No known copyright restrictions',
		9  => 'CC0 (Public Domain)',
		10 => 'Public Domain Mark',
	];

	public function slug(): string { return 'flickr'; }
	public function label(): string { return 'Flickr'; }

	public function is_configured(): bool {
		return (bool) get_option( 'skwpm_flickr_key' );
	}

	public function search( string $query, array $args = [] ): array {
		$key   = get_option( 'skwpm_flickr_key' );
		$cache = 'skwpm_flickr_' . md5( $query . wp_json_encode( $args ) );
		$cached = get_transient( $cache );
		if ( false !== $cached ) {
			return $cached;
		}

		$params = array_merge( [
			'method'        => 'flickr.photos.search',
			'api_key'       => $key,
			'text'          => $query,
			'per_page'      => 30,
			'format'        => 'json',
			'nojsoncallback'=> 1,
			'extras'        => 'owner_name,url_m,url_s,license',
			'content_type'  => 1, // photos only
			'media'         => 'photos',
		], $args );

		$request = wp_remote_get(
			Http::url( 'https://api.flickr.com/services/rest/', $params ),
			[ 'timeout' => 15 ]
		);

		if ( is_wp_error( $request ) || 200 !== wp_remote_retrieve_response_code( $request ) ) {
			return [];
		}

		$body  = json_decode( wp_remote_retrieve_body( $request ), true );
		$items = array_map( [ $this, 'normalize' ], $body['photos']['photo'] ?? [] );

		set_transient( $cache, $items, 6 * HOUR_IN_SECONDS );
		return $items;
	}

	public function fetch( string $external_id ): ?array {
		$key = get_option( 'skwpm_flickr_key' );

		$sizes_request = wp_remote_get(
			Http::url( 'https://api.flickr.com/services/rest/', [
				'method'        => 'flickr.photos.getSizes',
				'api_key'       => $key,
				'photo_id'      => $external_id,
				'format'        => 'json',
				'nojsoncallback'=> 1,
			] ),
			[ 'timeout' => 15 ]
		);
		if ( is_wp_error( $sizes_request ) || 200 !== wp_remote_retrieve_response_code( $sizes_request ) ) {
			return null;
		}
		$sizes_body = json_decode( wp_remote_retrieve_body( $sizes_request ), true );
		$sizes      = $sizes_body['sizes']['size'] ?? [];
		$by_label   = [];
		foreach ( $sizes as $size ) {
			$by_label[ $size['label'] ?? '' ] = $size['source'] ?? '';
		}

		$info_request = wp_remote_get(
			Http::url( 'https://api.flickr.com/services/rest/', [
				'method'        => 'flickr.photos.getInfo',
				'api_key'       => $key,
				'photo_id'      => $external_id,
				'format'        => 'json',
				'nojsoncallback'=> 1,
			] ),
			[ 'timeout' => 15 ]
		);
		$title    = '';
		$owner    = '';
		$license  = 0;
		if ( ! is_wp_error( $info_request ) && 200 === wp_remote_retrieve_response_code( $info_request ) ) {
			$info_body = json_decode( wp_remote_retrieve_body( $info_request ), true );
			$photo     = $info_body['photo'] ?? [];
			$title     = $photo['title']['_content'] ?? '';
			$owner     = $photo['owner']['realname'] ?? ( $photo['owner']['username'] ?? '' );
			$license   = (int) ( $photo['license'] ?? 0 );
		}

		return [
			'provider'    => $this->slug(),
			'external_id' => $external_id,
			'type'        => 'image',
			'url'         => $by_label['Medium'] ?? ( $by_label['Large'] ?? '' ),
			'thumb'       => $by_label['Small'] ?? ( $by_label['Medium'] ?? '' ),
			'title'       => $title,
			'author'      => $owner,
			'license'     => self::LICENSES[ $license ] ?? 'Flickr',
		];
	}

	private function normalize( array $photo ): array {
		$license = (int) ( $photo['license'] ?? 0 );
		return [
			'provider'    => $this->slug(),
			'external_id' => (string) ( $photo['id'] ?? '' ),
			'type'        => 'image',
			'url'         => $photo['url_m'] ?? ( $photo['url_s'] ?? '' ),
			'thumb'       => $photo['url_s'] ?? ( $photo['url_m'] ?? '' ),
			'title'       => $photo['title'] ?? '',
			'author'      => $photo['ownername'] ?? '',
			'license'     => self::LICENSES[ $license ] ?? 'Flickr',
		];
	}
}
