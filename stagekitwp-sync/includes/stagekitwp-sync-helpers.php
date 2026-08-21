<?php
/**
 * Normalize and sanitize a SharePoint URL.
 * - Enforces https://
 * - Strips query/hash
 * - Trims trailing slash
 */
function stagekitwp_sync_normalize_sharepoint_url( $url ) {
    $url = trim( (string) $url );

    if ( $url === '' ) {
        return '';
    }

    // If scheme is missing, assume https.
    if ( preg_match( '#^https?://#i', $url ) !== 1 ) {
        $url = 'https://' . ltrim( $url, '/' );
    }

    // Basic sanitization
    $url = esc_url_raw( $url );

    // Remove trailing slash and query/hash parts
    $parts = wp_parse_url( $url );
    if ( empty( $parts['host'] ) ) {
        return '';
    }

    $normalized  = $parts['scheme'] . '://' . $parts['host'];
    if ( ! empty( $parts['path'] ) ) {
        $normalized .= rtrim( $parts['path'], '/' );
    }

    return $normalized;
}

if ( ! function_exists( 'stagekitwp_sync_normalize_sharepoint_url' ) ) {
    /**
     * StageKitWP alias wrapper for SharePoint URL normalization.
     *
     * @param string $url Raw URL value.
     * @return string
     */
    function stagekitwp_sync_normalize_sharepoint_url( $url ) {
        return stagekitwp_sync_normalize_sharepoint_url( $url );
    }
}

function stagekitwp_sync_run_generic($cpt) {
    // Placeholder: Replace with actual sync logic
    return "Sync for {$cpt} completed successfully.";
}

if ( ! function_exists( 'stagekitwp_sync_run_generic' ) ) {
	/**
	 * StageKitWP alias wrapper for generic sync execution.
	 *
	 * @param string $post_type_slug Post type slug.
	 * @return string
	 */
	function stagekitwp_sync_run_generic( $post_type_slug ) {
		return stagekitwp_sync_run_generic( $post_type_slug );
	}
}

$sync_post_types = [ 'contributor', 'advertiser', 'board_member', 'sponsor', 'testimonial', 'season', 'show', 'cast' ];

foreach ( $sync_post_types as $post_type_slug ) {
    add_action('wp_ajax_stagekitwp_sync_run_' . $post_type_slug, function() use ( $post_type_slug ) {
        check_ajax_referer('stagekitwp_sync_nonce');
        $message = stagekitwp_sync_run_generic( $post_type_slug );
        wp_send_json_success(['message' => $message]);
    });
}

add_action('wp_ajax_stagekitwp_sync_run_all', function() use ( $sync_post_types ) {
    check_ajax_referer('stagekitwp_sync_nonce');
    $results = [];
    foreach ( $sync_post_types as $post_type_slug ) {
        $results[] = stagekitwp_sync_run_generic( $post_type_slug );
    }
    wp_send_json_success(['message' => implode("\n", $results)]);
});

function stagekitwp_sync_normalize_url($url) {
    return esc_url_raw(trim($url));
}

if ( ! function_exists( 'stagekitwp_sync_normalize_url' ) ) {
	/**
	 * StageKitWP alias wrapper for URL normalization.
	 *
	 * @param string $url URL value.
	 * @return string
	 */
	function stagekitwp_sync_normalize_url( $url ) {
		return stagekitwp_sync_normalize_url( $url );
	}
}

function stagekitwp_sync_download_image($url, $name) {
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $tmp = download_url($url);
    if (is_wp_error($tmp)) return false;

    $file_array = [
        'name' => sanitize_file_name($name . '.jpg'),
        'tmp_name' => $tmp
    ];

    $id = media_handle_sideload($file_array, 0);
    if (is_wp_error($id)) return false;

    return $id;
}

