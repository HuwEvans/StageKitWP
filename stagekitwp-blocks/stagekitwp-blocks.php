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