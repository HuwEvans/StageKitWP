<?php
/**
 * Plugin Name:       Stagekitwp Blocks
 * Description:       Additional Block Tools to be used in the StageKitWP Ecosystem
 * Version:           1.5.0
 * Requires at least: 6.8
 * Requires PHP:      7.4
 * Author:            Huw Evans
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       stagekitwp-blocks
 *
 * @package CreateBlock
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Check if StageKitWP or Theatre-Manager theme is active.
 *
 * @return bool
 */
function stagekitwp_is_theme_active() {
	$current_theme = wp_get_theme();
	$theme_name    = strtolower( $current_theme->get( 'Name' ) );
	$theme_slug    = strtolower( $current_theme->get_stylesheet() );
	$parent_slug   = strtolower( $current_theme->get_template() );

	return (
		strpos( $theme_name, 'stagekit' ) !== false ||
		strpos( $theme_name, 'theatre' ) !== false ||
		strpos( $theme_slug, 'stagekit' ) !== false ||
		strpos( $theme_slug, 'theatre' ) !== false ||
		strpos( $parent_slug, 'stagekit' ) !== false ||
		strpos( $parent_slug, 'theatre' ) !== false
	);
}

/**
 * Register blocks and third-party assets on init.
 */
function stagekitwp_register_blocks() {
	// 1. Register Swiper CSS
	wp_register_style(
		'swiper-css',
		'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css',
		array(),
		'11.0.0'
	);

	// 2. Register Slick Assets
	wp_register_style(
		'slick-css',
		'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css',
		array(),
		'1.8.1'
	);

	wp_register_style(
		'slick-theme-css',
		'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css',
		array( 'slick-css' ),
		'1.8.1'
	);

	wp_register_script(
		'slick-js',
		'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js',
		array( 'jquery' ),
		'1.8.1',
		true
	);

	// 3. Register Custom Blocks
	$blocks = array(
		'stagekitwp-tabs',
		'stagekitwp-tab-item',
		'stagekitwp-post-carousel',
		'bookshelf-container',
		'bookshelf-item',
		'stagekitwp-dual-image',
		'stagekitwp-lottie',
		'stagekitwp-countup',
		'stagekitwp-countdown',
		'stagekitwp-accordion',
		'stagekitwp-accordion-item',
		'stagekitwp-thermometer',
		'stagekitwp-divider',
	);

	foreach ( $blocks as $block ) {
		$block_path = __DIR__ . '/build/blocks/' . $block;
		if ( file_exists( $block_path . '/block.json' ) ) {
			register_block_type( $block_path );
		}
	}
}
add_action( 'init', 'stagekitwp_register_blocks' );

/**
 * Enqueue frontend assets safely.
 */
function stagekitwp_enqueue_frontend_assets() {
	if ( is_admin() ) {
		return;
	}

	if ( has_block( 'stagekitwp/post-carousel' ) ) {
		wp_enqueue_style( 'slick-css' );
		wp_enqueue_style( 'slick-theme-css' );
		wp_enqueue_script( 'slick-js' );
	}
}
add_action( 'wp_enqueue_scripts', 'stagekitwp_enqueue_frontend_assets' );

add_filter( 'block_categories_all', 'stagekitwp_new_block_category' );

function stagekitwp_new_block_category( $cats ) {
	$new = array(
		'literallyanything' => array(
			'slug'  => 'stagekitwp-blocks',
			'title' => 'StageKitWP Blocks',
		),
	);

	$position = 1;

	$cats = array_slice( $cats, 0, $position, true ) + $new + array_slice( $cats, $position, null, true );

	return array_values( $cats );
}

add_filter(
	'upload_mimes',
	function ( $mimes ) {
		$mimes['json'] = 'application/json';
		return $mimes;
	}
);

/**
 * Allow JSON uploads for Lottie animations.
 */
add_filter(
	'upload_mimes',
	function ( $mimes ) {

		$mimes['json'] = 'application/json';

		return $mimes;
	}
);

/**
 * Ensure JSON files are recognized properly.
 */
add_filter(
	'wp_check_filetype_and_ext',
	function ( $data, $file, $filename ) {

		if ( 'json' === pathinfo( $filename, PATHINFO_EXTENSION ) ) {

			$data['ext']  = 'json';
			$data['type'] = 'application/json';

		}

		return $data;

	},
	10,
	3
);

/**
 * Register a REST proxy for the Canadian Play Outlet title search.
 *
 * The storefront's predictive-search endpoint doesn't send CORS headers, so the
 * block editor can't call it directly from the browser. This route fetches it
 * server-side instead. The target host is fixed and the only user input is the
 * search title, so this can't be used to reach arbitrary URLs.
 */
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'stagekitwp-blocks/v1',
			'/canadian-play-outlet-search',
			array(
				'methods'             => 'GET',
				'callback'            => 'stagekitwp_canadian_play_outlet_search',
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
				'args'                => array(
					'title' => array(
						'required' => true,
						'type'     => 'string',
					),
				),
			)
		);
	}
);

/**
 * Look up plays by title on the Canadian Play Outlet (Playwrights Guild of Canada) storefront.
 *
 * @param WP_REST_Request $request The REST request.
 * @return WP_REST_Response|WP_Error
 */
function stagekitwp_canadian_play_outlet_search( $request ) {
	$title = sanitize_text_field( $request->get_param( 'title' ) );

	if ( '' === $title ) {
		return new WP_Error( 'stagekitwp_missing_title', __( 'A title is required.', 'stagekitwp-blocks' ), array( 'status' => 400 ) );
	}

	$url = add_query_arg(
		array(
			'q'                 => rawurlencode( $title ),
			'resources[type]'   => 'product',
			'resources[limit]'  => 5,
		),
		'https://www.canadianplayoutlet.com/search/suggest.json'
	);

	$response = wp_remote_get(
		$url,
		array(
			'timeout' => 8,
			'headers' => array( 'Accept' => 'application/json' ),
		)
	);

	if ( is_wp_error( $response ) ) {
		return new WP_Error( 'stagekitwp_lookup_failed', __( 'The Canadian Play Outlet lookup failed.', 'stagekitwp-blocks' ), array( 'status' => 502 ) );
	}

	$body     = json_decode( wp_remote_retrieve_body( $response ), true );
	$products = $body['resources']['results']['products'] ?? array();

	$results = array_map(
		function ( $product ) {
			$path = isset( $product['url'] ) ? ltrim( wp_parse_url( $product['url'], PHP_URL_PATH ), '/' ) : '';
			return array(
				'title'       => isset( $product['title'] ) ? sanitize_text_field( $product['title'] ) : '',
				'path'        => $path,
				'coverImage'  => isset( $product['featured_image']['url'] ) ? esc_url_raw( $product['featured_image']['url'] ) : '',
				'description' => isset( $product['body'] ) ? sanitize_text_field( wp_strip_all_tags( $product['body'] ) ) : '',
			);
		},
		$products
	);

	return rest_ensure_response( array( 'results' => $results ) );
}