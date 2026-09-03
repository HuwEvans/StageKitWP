<?php
namespace SKWPM;

class YouTube implements Provider {

	public function slug(): string { return 'youtube'; }
	public function label(): string { return 'YouTube'; }

	public function is_configured(): bool {
		return (bool) get_option( 'skwpm_youtube_key' );
	}

	/**
	 * Keyword search, OR — if $query looks like a channel ID, channel URL,
	 * or @handle — all of that channel's uploaded videos (newest first).
	 * This lets the single search box in wp-admin double as a channel browser
	 * without any UI changes: paste a channel link instead of typing words.
	 */
	public function search( string $query, array $args = [] ): array {
		$channel_id = $this->resolve_channel_id( trim( $query ) );
		if ( $channel_id ) {
			return $this->channel_uploads( $channel_id, $args );
		}

		$key   = get_option( 'skwpm_youtube_key' );
		$cache = 'skwpm_youtube_' . md5( $query . wp_json_encode( $args ) );
		$cached = get_transient( $cache );
		if ( false !== $cached ) {
			return $cached;
		}

		$params = array_merge( [
			'part'       => 'snippet',
			'type'       => 'video',
			'q'          => $query,
			'maxResults' => 30,
			'key'        => $key,
		], $args );
		$request = wp_remote_get(
			Http::url( 'https://www.googleapis.com/youtube/v3/search', $params ),
			[ 'timeout' => 15 ]
		);

		if ( is_wp_error( $request ) || 200 !== wp_remote_retrieve_response_code( $request ) ) {
			return [];
		}

		$body  = json_decode( wp_remote_retrieve_body( $request ), true );
		$items = array_map( [ $this, 'normalize_search' ], $body['items'] ?? [] );

		set_transient( $cache, $items, 6 * HOUR_IN_SECONDS );
		return $items;
	}

	public function fetch( string $external_id ): ?array {
		$key    = get_option( 'skwpm_youtube_key' );
		$params = [ 'part' => 'snippet', 'id' => $external_id, 'key' => $key ];
		$request = wp_remote_get(
			Http::url( 'https://www.googleapis.com/youtube/v3/videos', $params ),
			[ 'timeout' => 15 ]
		);
		if ( is_wp_error( $request ) || 200 !== wp_remote_retrieve_response_code( $request ) ) {
			return null;
		}
		$body = json_decode( wp_remote_retrieve_body( $request ), true );
		$item = $body['items'][0] ?? null;
		return $item ? $this->normalize_video( $item ) : null;
	}

	/**
	 * Recognize a raw channel ID ("UC..."), a full channel/handle URL, or a
	 * bare "@handle". Returns null when $query looks like a normal search term.
	 */
	private function resolve_channel_id( string $query ): ?string {
		if ( '' === $query ) {
			return null;
		}

		if ( preg_match( '/^UC[0-9A-Za-z_-]{22}$/', $query ) ) {
			return $query;
		}
		if ( preg_match( '#youtube\.com/channel/(UC[0-9A-Za-z_-]{22})#i', $query, $m ) ) {
			return $m[1];
		}
		if ( preg_match( '#(?:^|youtube\.com/)@([A-Za-z0-9_.-]+)#i', $query, $m ) ) {
			return $this->lookup_channel( [ 'forHandle' => '@' . $m[1] ] );
		}
		if ( preg_match( '#youtube\.com/(?:c|user)/([A-Za-z0-9_.-]+)#i', $query, $m ) ) {
			return $this->lookup_channel( [ 'forUsername' => $m[1] ] );
		}

		return null;
	}

	/** Resolve a channel ID from a handle or legacy username. */
	private function lookup_channel( array $lookup_params ): ?string {
		$key     = get_option( 'skwpm_youtube_key' );
		$params  = array_merge( [ 'part' => 'id', 'key' => $key ], $lookup_params );
		$request = wp_remote_get(
			Http::url( 'https://www.googleapis.com/youtube/v3/channels', $params ),
			[ 'timeout' => 15 ]
		);
		if ( is_wp_error( $request ) || 200 !== wp_remote_retrieve_response_code( $request ) ) {
			return null;
		}
		$body = json_decode( wp_remote_retrieve_body( $request ), true );
		return $body['items'][0]['id'] ?? null;
	}

	/**
	 * Every channel has a hidden "uploads" playlist containing every video it
	 * has published — this is the standard way to list "all of a channel's
	 * videos" since the Data API has no direct "videos by channel" endpoint.
	 */
	private function channel_uploads( string $channel_id, array $args = [] ): array {
		$key    = get_option( 'skwpm_youtube_key' );
		$cache  = 'skwpm_youtube_uploads_' . md5( $channel_id . wp_json_encode( $args ) );
		$cached = get_transient( $cache );
		if ( false !== $cached ) {
			return $cached;
		}

		$channel_request = wp_remote_get(
			Http::url( 'https://www.googleapis.com/youtube/v3/channels', [ 'part' => 'contentDetails', 'id' => $channel_id, 'key' => $key ] ),
			[ 'timeout' => 15 ]
		);
		if ( is_wp_error( $channel_request ) || 200 !== wp_remote_retrieve_response_code( $channel_request ) ) {
			return [];
		}
		$channel_body     = json_decode( wp_remote_retrieve_body( $channel_request ), true );
		$uploads_playlist = $channel_body['items'][0]['contentDetails']['relatedPlaylists']['uploads'] ?? '';
		if ( '' === $uploads_playlist ) {
			return [];
		}

		$params = array_merge( [
			'part'       => 'snippet',
			'playlistId' => $uploads_playlist,
			'maxResults' => 30,
			'key'        => $key,
		], $args );
		$items_request = wp_remote_get(
			Http::url( 'https://www.googleapis.com/youtube/v3/playlistItems', $params ),
			[ 'timeout' => 15 ]
		);
		if ( is_wp_error( $items_request ) || 200 !== wp_remote_retrieve_response_code( $items_request ) ) {
			return [];
		}

		$items_body = json_decode( wp_remote_retrieve_body( $items_request ), true );
		$items      = array_map( [ $this, 'normalize_playlist_item' ], $items_body['items'] ?? [] );

		set_transient( $cache, $items, 6 * HOUR_IN_SECONDS );
		return $items;
	}

	private function normalize_search( array $item ): array {
		return $this->build( $item['id']['videoId'] ?? '', $item['snippet'] ?? [] );
	}

	private function normalize_video( array $item ): array {
		return $this->build( $item['id'] ?? '', $item['snippet'] ?? [] );
	}

	private function normalize_playlist_item( array $item ): array {
		$snippet = $item['snippet'] ?? [];
		return $this->build( $snippet['resourceId']['videoId'] ?? '', $snippet );
	}

	private function build( string $id, array $snippet ): array {
		$thumbs = $snippet['thumbnails'] ?? [];
		$thumb  = $thumbs['medium']['url'] ?? ( $thumbs['default']['url'] ?? '' );
		return [
			'provider'    => $this->slug(),
			'external_id' => $id,
			'type'        => 'video',
			'url'         => 'https://www.youtube.com/watch?v=' . $id,
			'thumb'       => $thumb,
			'title'       => $snippet['title'] ?? '',
			'author'      => $snippet['channelTitle'] ?? '',
			'license'     => 'YouTube',
		];
	}
}
