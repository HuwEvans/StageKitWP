<?php
namespace SKWPM;

/**
 * Per-request capture of the last provider API error, so the admin search UI
 * can surface *why* a call returned no results (expired token, bad scope,
 * network failure, etc.) instead of just showing "No results."
 */
class Debug {
	private static string $last_error = '';

	public static function set( string $message ): void {
		self::$last_error = $message;
	}

	public static function get_and_clear(): string {
		$message = self::$last_error;
		self::$last_error = '';
		return $message;
	}

	/** Build a short diagnostic string from a wp_remote_* result. */
	public static function describe_response( $request ): string {
		if ( is_wp_error( $request ) ) {
			return $request->get_error_message();
		}
		$code = wp_remote_retrieve_response_code( $request );
		$body = wp_remote_retrieve_body( $request );
		$decoded = json_decode( $body, true );
		$api_message = $decoded['error']['message'] ?? '';
		return trim( sprintf( 'HTTP %d%s', $code, $api_message ? ' — ' . $api_message : '' ) );
	}
}
