<?php
namespace SKWPM;

/**
 * AJAX endpoint backing the Gallery Items search box in wp-admin.
 */
class Ajax {
	public static function init(): void {
		add_action( 'wp_ajax_skwpm_search_media', [ __CLASS__, 'search' ] );
		add_action( 'wp_ajax_skwpm_gphotos_start', [ __CLASS__, 'gphotos_start' ] );
		add_action( 'wp_ajax_skwpm_gphotos_status', [ __CLASS__, 'gphotos_status' ] );
		add_action( 'wp_ajax_skwpm_gphotos_import', [ __CLASS__, 'gphotos_import' ] );
		add_action( 'wp_ajax_skwpm_drive_import', [ __CLASS__, 'drive_import' ] );
	}

	public static function search(): void {
		check_ajax_referer( 'skwpm_gallery_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'stagekitwp-media' ) ], 403 );
		}

		$slug  = isset( $_POST['provider'] ) ? sanitize_key( wp_unslash( $_POST['provider'] ) ) : '';
		$query = isset( $_POST['query'] ) ? sanitize_text_field( wp_unslash( $_POST['query'] ) ) : '';

		// Google Drive only matches file *names*, not photo content — allow a blank query there to browse recent files instead of forcing a (likely fruitless) keyword guess.
		$query_required = 'google-drive' !== $slug;
		if ( '' === $slug || ( $query_required && '' === $query ) ) {
			wp_send_json_error( [ 'message' => __( 'Choose a provider and enter a search term.', 'stagekitwp-media' ) ] );
		}

		$provider = Registry::get( $slug );
		if ( ! $provider ) {
			wp_send_json_error( [ 'message' => __( 'Unknown provider.', 'stagekitwp-media' ) ] );
		}
		if ( ! $provider->is_configured() ) {
			wp_send_json_error( [ 'message' => __( 'This provider has no API key configured yet. Add one under Settings.', 'stagekitwp-media' ) ] );
		}

		$items = $provider->search( $query );
		$debug = $items ? '' : Debug::get_and_clear();
		wp_send_json_success( [ 'items' => $items, 'message' => $debug ] );
	}

	private static function google_photos(): ?GooglePhotos {
		check_ajax_referer( 'skwpm_gallery_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'stagekitwp-media' ) ], 403 );
		}

		$provider = Registry::get( 'google-photos' );
		if ( ! $provider instanceof GooglePhotos || ! $provider->is_configured() ) {
			wp_send_json_error( [ 'message' => __( 'Google Photos is not connected yet. Connect it under Settings.', 'stagekitwp-media' ) ] );
			return null;
		}
		return $provider;
	}

	/** Start a Picker session; the browser opens the returned pickerUri for the user to select photos in. */
	public static function gphotos_start(): void {
		$provider = self::google_photos();
		$session  = $provider->start_session();
		if ( ! $session || empty( $session['pickerUri'] ) || empty( $session['id'] ) ) {
			wp_send_json_error( [ 'message' => self::with_debug( __( 'Could not start a Google Photos picker session.', 'stagekitwp-media' ) ) ] );
		}

		wp_send_json_success( [
			'sessionId'    => $session['id'],
			'pickerUri'    => $session['pickerUri'],
			'pollInterval' => (int) ( $session['pollingConfig']['pollInterval']['seconds'] ?? 2 ),
		] );
	}

	/** Poll a session; once the user has finished picking, returns the (not-yet-imported) picked items. */
	public static function gphotos_status(): void {
		$provider   = self::google_photos();
		$session_id = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : '';
		if ( '' === $session_id ) {
			wp_send_json_error( [ 'message' => __( 'Missing session.', 'stagekitwp-media' ) ] );
		}

		$status = $provider->session_status( $session_id );
		if ( ! $status ) {
			wp_send_json_error( [ 'message' => self::with_debug( __( 'Could not check the picker session status.', 'stagekitwp-media' ) ) ] );
		}

		$ready = ! empty( $status['mediaItemsSet'] );
		wp_send_json_success( [
			'ready' => $ready,
			'items' => $ready ? $provider->list_session_items( $session_id ) : [],
		] );
	}

	/** Store one picked item into StageKit Media storage and return its item data. */
	public static function gphotos_import(): void {
		$provider      = self::google_photos();
		$session_id    = isset( $_POST['session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['session_id'] ) ) : '';
		$media_item_id = isset( $_POST['media_item_id'] ) ? sanitize_text_field( wp_unslash( $_POST['media_item_id'] ) ) : '';
		$folder_id     = isset( $_POST['folder_id'] ) ? max( 0, (int) $_POST['folder_id'] ) : 0;
		if ( '' === $session_id || '' === $media_item_id ) {
			wp_send_json_error( [ 'message' => __( 'Missing item.', 'stagekitwp-media' ) ] );
		}

		$item = $provider->import_item( $session_id, $media_item_id, $folder_id );
		if ( ! $item ) {
			wp_send_json_error( [ 'message' => self::with_debug( __( 'Could not import that item from Google Photos.', 'stagekitwp-media' ) ) ] );
		}

		wp_send_json_success( [ 'item' => $item ] );
	}

	/** Append the last captured provider API error detail (if any) to a user-facing message. */
	private static function with_debug( string $message ): string {
		$debug = Debug::get_and_clear();
		return $debug ? $message . ' (' . $debug . ')' : $message;
	}

	/** Store one picked Google Drive file into StageKit Media storage and return its item data. */
	public static function drive_import(): void {
		check_ajax_referer( 'skwpm_gallery_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'stagekitwp-media' ) ], 403 );
		}

		$provider = Registry::get( 'google-drive' );
		if ( ! $provider instanceof GoogleDrive || ! $provider->is_configured() ) {
			wp_send_json_error( [ 'message' => __( 'Google Drive is not connected yet. Connect it under Settings.', 'stagekitwp-media' ) ] );
		}

		$file_id   = isset( $_POST['file_id'] ) ? sanitize_text_field( wp_unslash( $_POST['file_id'] ) ) : '';
		$folder_id = isset( $_POST['folder_id'] ) ? max( 0, (int) $_POST['folder_id'] ) : 0;
		if ( '' === $file_id ) {
			wp_send_json_error( [ 'message' => __( 'Missing item.', 'stagekitwp-media' ) ] );
		}

		$item = $provider->import_item( $file_id, $folder_id );
		if ( ! $item ) {
			wp_send_json_error( [ 'message' => self::with_debug( __( 'Could not import that item from Google Drive.', 'stagekitwp-media' ) ) ] );
		}

		wp_send_json_success( [ 'item' => $item ] );
	}
}

