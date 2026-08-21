<?php
/**
 * Theatre Manager Gutenberg Blocks Registration
 * 
 * Registers and enqueues all custom Gutenberg blocks
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register custom block category for Theatre Manager
 */
function tm_register_block_category($categories, $post) {
    return array_merge(
        $categories,
        array(
            array(
                'slug' => 'theatre-manager',
                'title' => __('Theatre Manager Blocks', 'theatre-manager'),
                'icon' => 'theater'
            )
        )
    );
}
add_filter('block_categories_all', 'tm_register_block_category', 10, 2);

/**
 * Enqueue block editor assets
 * 
 * Registers and enqueues all block scripts and styles
 */
function tm_enqueue_block_assets() {
    // Only enqueue on the editor
    if (!is_admin()) {
        return;
    }

    // Enqueue unified blocks script
    wp_enqueue_script(
        'tm-blocks',
        TM_PLUGIN_URL . 'blocks/tm-blocks/index.js',
        ['wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor'],
        THEATRE_MANAGER_VERSION,
        true
    );

    // Also enqueue standalone landing page block for backwards compatibility
    wp_enqueue_script(
        'tm-landingpage-block-editor',
        TM_PLUGIN_URL . 'blocks/tm-landingpage-block/index.js',
        ['wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor'],
        THEATRE_MANAGER_VERSION,
        true
    );

    // Enqueue editor styles
    wp_enqueue_style(
        'tm-blocks-editor',
        TM_PLUGIN_URL . 'blocks/tm-blocks/editor.css',
        [],
        THEATRE_MANAGER_VERSION
    );

    // Landing page block styles
    wp_enqueue_style(
        'tm-landingpage-block-editor',
        TM_PLUGIN_URL . 'blocks/tm-landingpage-block/editor.css',
        [],
        THEATRE_MANAGER_VERSION
    );
}
add_action('enqueue_block_editor_assets', 'tm_enqueue_block_assets');
