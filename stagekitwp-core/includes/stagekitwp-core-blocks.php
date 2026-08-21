<?php
/**
 * StageKitWP Gutenberg Blocks Registration
 *
 * Registers and enqueues all custom Gutenberg blocks and
 * the StageKitWP block category.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register the 'StageKitWP Core' block category so all
 * stagekitwp/* blocks are grouped together in the
 * block inserter instead of appearing under 'uncategorized'.
 *
 * Uses block_categories_all (WP 5.8+). Falls back gracefully
 * on older WP because the filter simply won't fire.
 */
function stagekitwp_register_block_category( $categories ) {
    // Prepend so it appears at the top of the inserter.
    array_unshift( $categories, array(
        'slug'  => 'stagekitwp-core',
        'title' => __( 'StageKitWP Core', 'stagekitwp-core' ),
        'icon'  => 'admin-multisite',
    ) );
    return $categories;
}
add_filter( 'block_categories_all', 'stagekitwp_register_block_category', 5 );

/**
 * Enqueue all block editor assets.
 *
 * - stagekitwp-blocks/index.js   : registers all 16 shortcode-backed blocks under the
 *                          'stagekitwp-core' category (single bundled file).
 * - stagekitwp-landingpage-block : standalone landing-page block registered separately
 *                          for a more focused UX.
 *
 * NOTE: enqueue_block_editor_assets fires only inside the block editor (admin
 * + site editor), so the redundant is_admin() guard has been removed.
 */
function stagekitwp_enqueue_block_assets() {

    $deps = array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-api-fetch' );

    // ── Main shortcode blocks bundle ────────────────────────────────────────
    wp_enqueue_script(
        'stagekitwp-blocks',
        STAGEKITWP_CORE_URL . 'blocks/stagekitwp-blocks/index.js',
        $deps,
        STAGEKITWP_CORE_VERSION,
        true
    );

    wp_enqueue_style(
        'stagekitwp-blocks-editor',
        STAGEKITWP_CORE_URL . 'blocks/stagekitwp-blocks/editor.css',
        array(),
        STAGEKITWP_CORE_VERSION
    );

    // Pass the REST API root and nonce so JS uses the correct URL on
    // sub-directory installs instead of hardcoded /wp-json/ paths.
    wp_localize_script( 'stagekitwp-blocks', 'stagekitwpBlocksData', array(
        'restRoot'  => esc_url_raw( rest_url() ),
        'restNonce' => wp_create_nonce( 'wp_rest' ),
        'version'   => STAGEKITWP_CORE_VERSION,
    ) );

    // ── Landing-page block ──────────────────────────────────────────────────
    wp_enqueue_script(
        'stagekitwp-landingpage-block-editor',
        STAGEKITWP_CORE_URL . 'blocks/stagekitwp-landingpage-block/index.js',
        $deps,
        STAGEKITWP_CORE_VERSION,
        true
    );

    wp_enqueue_style(
        'stagekitwp-landingpage-block-editor',
        STAGEKITWP_CORE_URL . 'blocks/stagekitwp-landingpage-block/editor.css',
        array(),
        STAGEKITWP_CORE_VERSION
    );
}
add_action( 'enqueue_block_editor_assets', 'stagekitwp_enqueue_block_assets' );
