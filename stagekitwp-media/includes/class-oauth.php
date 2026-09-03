<?php
namespace SKWPM;

/**
 * Minimal OAuth2 (authorization-code + refresh-token) helper shared by any
 * provider that needs a full account sign-in (Dropbox, Google Drive), as
 * opposed to a simple API key (Pexels, Unsplash, Flickr, YouTube, Vimeo).
 */
class OAuth {

	public static function init(): void {
		add_action( 'admin_post_skwpm_oauth_connect', [ __CLASS__, 'connect' ] );
		add_action( 'admin_post_skwpm_oauth_callback', [ __CLASS__, 'callback' ] );
		add_action( 'admin_post_skwpm_oauth_disconnect', [ __CLASS__, 'disconnect_request' ] );
	}

	public static function connect_url( string $provider ): string {
		return wp_nonce_url(
			add_query_arg( [ 'action' => 'skwpm_oauth_connect', 'provider' => $provider ], admin_url( 'admin-post.php' ) ),
			'skwpm_oauth_connect_' . $provider
		);
	}

	public static function disconnect_url( string $provider ): string {
		return wp_nonce_url(
			add_query_arg( [ 'action' => 'skwpm_oauth_disconnect', 'provider' => $provider ], admin_url( 'admin-post.php' ) ),
			'skwpm_oauth_disconnect_' . $provider
		);
	}

	/** Register this exact URL as the app's "Redirect URI" in Dropbox/Google's developer console. */
	public static function redirect_uri(): string {
		return admin_url( 'admin-post.php?action=skwpm_oauth_callback' );
	}

	public static function connect(): void {
		$provider = isset( $_GET['provider'] ) ? sanitize_key( wp_unslash( $_GET['provider'] ) ) : '';
		check_admin_referer( 'skwpm_oauth_connect_' . $provider );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'stagekitwp-media' ) );
		}

		$config = self::config( $provider );
		if ( ! $config || empty( $config['client_id'] ) ) {
			self::redirect_to_settings( $provider, 'missing_credentials' );
			return;
		}

		$params = array_merge( [
			'client_id'     => $config['client_id'],
			'redirect_uri'  => self::redirect_uri(),
			'response_type' => 'code',
			'state'         => $provider . ':' . wp_create_nonce( 'skwpm_oauth_state_' . $provider ),
			'scope'         => $config['scope'],
		], $config['authorize_extra'] ?? [] );

		wp_redirect( Http::url( $config['authorize_url'], $params ) );
		exit;
	}

	public static function callback(): void {
		$state = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : '';
		$code  = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '';
		[ $provider, $nonce ] = array_pad( explode( ':', $state, 2 ), 2, '' );
		$provider = sanitize_key( $provider );

		if ( ! $provider || ! wp_verify_nonce( $nonce, 'skwpm_oauth_state_' . $provider ) ) {
			wp_die( esc_html__( 'Invalid or expired OAuth state. Please try connecting again.', 'stagekitwp-media' ) );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'stagekitwp-media' ) );
		}

		if ( isset( $_GET['error'] ) ) {
			self::redirect_to_settings( $provider, 'error' );
			return;
		}

		$config = self::config( $provider );
		if ( ! $config ) {
			self::redirect_to_settings( $provider, 'error' );
			return;
		}

		$request = wp_remote_post( $config['token_url'], [
			'timeout' => 15,
			'body'    => [
				'code'          => $code,
				'grant_type'    => 'authorization_code',
				'client_id'     => $config['client_id'],
				'client_secret' => $config['client_secret'],
				'redirect_uri'  => self::redirect_uri(),
			],
		] );

		if ( is_wp_error( $request ) || 200 !== wp_remote_retrieve_response_code( $request ) ) {
			self::redirect_to_settings( $provider, 'error' );
			return;
		}

		$body = json_decode( wp_remote_retrieve_body( $request ), true );
		if ( empty( $body['access_token'] ) ) {
			self::redirect_to_settings( $provider, 'error' );
			return;
		}

		self::store_tokens( $provider, $body );
		self::redirect_to_settings( $provider, 'connected' );
	}

	public static function disconnect_request(): void {
		$provider = isset( $_GET['provider'] ) ? sanitize_key( wp_unslash( $_GET['provider'] ) ) : '';
		check_admin_referer( 'skwpm_oauth_disconnect_' . $provider );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'stagekitwp-media' ) );
		}
		delete_option( self::option_name( $provider ) );
		self::redirect_to_settings( $provider, 'disconnected' );
	}

	private static function redirect_to_settings( string $provider, string $status ): void {
		wp_safe_redirect( add_query_arg( [
			'page'           => 'skwpm-settings',
			'skwpm_oauth'    => $status,
			'skwpm_provider' => $provider,
		], admin_url( 'edit.php?post_type=skwpm_gallery' ) ) );
		exit;
	}

	private static function option_name( string $provider ): string {
		return 'skwpm_oauth_' . $provider;
	}

	private static function store_tokens( string $provider, array $token_response ): void {
		$existing = get_option( self::option_name( $provider ), [] );
		update_option( self::option_name( $provider ), [
			'access_token'  => $token_response['access_token'],
			'refresh_token' => $token_response['refresh_token'] ?? ( $existing['refresh_token'] ?? '' ),
			'expires_at'    => time() + (int) ( $token_response['expires_in'] ?? 3600 ) - 60,
		], false );
	}

	public static function is_connected( string $provider ): bool {
		$data = get_option( self::option_name( $provider ), [] );
		return ! empty( $data['refresh_token'] ) || ! empty( $data['access_token'] );
	}

	/** Returns a currently-valid access token, transparently refreshing it first if it has expired. */
	public static function get_access_token( string $provider ): ?string {
		$data = get_option( self::option_name( $provider ), [] );
		if ( empty( $data['access_token'] ) ) {
			return null;
		}
		if ( ! empty( $data['expires_at'] ) && time() < $data['expires_at'] ) {
			return $data['access_token'];
		}
		if ( empty( $data['refresh_token'] ) ) {
			return $data['access_token']; // Best effort — no refresh token available.
		}

		$config = self::config( $provider );
		if ( ! $config ) {
			return null;
		}

		$request = wp_remote_post( $config['token_url'], [
			'timeout' => 15,
			'body'    => [
				'grant_type'    => 'refresh_token',
				'refresh_token' => $data['refresh_token'],
				'client_id'     => $config['client_id'],
				'client_secret' => $config['client_secret'],
			],
		] );
		if ( is_wp_error( $request ) || 200 !== wp_remote_retrieve_response_code( $request ) ) {
			return $data['access_token']; // Fall back; the API call will 401 and surface cleanly.
		}

		$body = json_decode( wp_remote_retrieve_body( $request ), true );
		if ( empty( $body['access_token'] ) ) {
			return $data['access_token'];
		}

		self::store_tokens( $provider, $body );
		return $body['access_token'];
	}

	/** Central registry of OAuth provider configs. */
	public static function config( string $provider ): ?array {
		if ( 'dropbox' === $provider ) {
			return [
				'client_id'       => get_option( 'skwpm_dropbox_client_id' ),
				'client_secret'   => get_option( 'skwpm_dropbox_client_secret' ),
				'authorize_url'   => 'https://www.dropbox.com/oauth2/authorize',
				'authorize_extra' => [ 'token_access_type' => 'offline' ],
				'token_url'       => 'https://api.dropboxapi.com/oauth2/token',
				'scope'           => 'files.metadata.read files.content.read sharing.read sharing.write',
			];
		}
		if ( 'google-drive' === $provider ) {
			return [
				'client_id'       => get_option( 'skwpm_gdrive_client_id' ),
				'client_secret'   => get_option( 'skwpm_gdrive_client_secret' ),
				'authorize_url'   => 'https://accounts.google.com/o/oauth2/v2/auth',
				'authorize_extra' => [ 'access_type' => 'offline', 'prompt' => 'consent' ],
				'token_url'       => 'https://oauth2.googleapis.com/token',
				'scope'           => 'https://www.googleapis.com/auth/drive.readonly',
			];
		}
		if ( 'google-photos' === $provider ) {
			return [
				'client_id'       => get_option( 'skwpm_gphotos_client_id' ),
				'client_secret'   => get_option( 'skwpm_gphotos_client_secret' ),
				'authorize_url'   => 'https://accounts.google.com/o/oauth2/v2/auth',
				'authorize_extra' => [ 'access_type' => 'offline', 'prompt' => 'consent' ],
				'token_url'       => 'https://oauth2.googleapis.com/token',
				'scope'           => 'https://www.googleapis.com/auth/photospicker.mediaitems.readonly',
			];
		}
		return null;
	}
}
