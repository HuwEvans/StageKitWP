<?php
namespace SKWPM;

/**
 * add_query_arg() does NOT urlencode its values (see its own doc comment),
 * so any query param containing reserved URL characters — a search query
 * with "&", "#", "+", or a redirect_uri that itself has a "?" in it —
 * silently produces a broken URL. http_build_query() encodes correctly.
 */
class Http {
	public static function url( string $base, array $params ): string {
		$separator = ( false === strpos( $base, '?' ) ) ? '?' : '&';
		return $base . $separator . http_build_query( $params, '', '&', PHP_QUERY_RFC3986 );
	}
}
