<?php
/**
 * Board Members Block
 * Gutenberg block for displaying board member profiles with per-block display overrides.
 */

defined('ABSPATH') || exit;

/**
 * Register the Board Members block.
 */
function stagekitwp_register_board_members_block() {
    wp_register_script(
        'stagekitwp-board-members-block-editor',
        plugins_url('board-members-block-editor.js', __FILE__),
        array('wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-server-side-render'),
        STAGEKITWP_CORE_VERSION,
        true
    );

    register_block_type('stagekitwp/board-members', array(
        'editor_script' => 'stagekitwp-board-members-block-editor',
        'render_callback' => 'stagekitwp_render_board_members_block',
        'attributes' => array(
            'layout' => array(
                'type' => 'string',
                'enum' => array('grid', 'list', 'accordion'),
                'default' => 'grid',
            ),
            'showPhotos' => array(
                'type' => 'boolean',
                'default' => true,
            ),
            'showCompany' => array(
                'type' => 'boolean',
                'default' => true,
            ),
            'photoSize' => array(
                'type' => 'string',
                'enum' => array('small', 'medium', 'large'),
                'default' => 'medium',
            ),
            'columns' => array(
                'type' => 'number',
                'default' => 0,
            ),
            'columnsDesktop' => array(
                'type' => 'number',
                'default' => 0,
            ),
            'columnsTablet' => array(
                'type' => 'number',
                'default' => 0,
            ),
            'columnsMobile' => array(
                'type' => 'number',
                'default' => 1,
            ),
            'bgColor' => array(
                'type' => 'string',
                'default' => '',
            ),
            'textColor' => array(
                'type' => 'string',
                'default' => '',
            ),
            'borderColor' => array(
                'type' => 'string',
                'default' => '',
            ),
            'borderWidth' => array(
                'type' => 'number',
                'default' => 0,
            ),
            'rounded' => array(
                'type' => 'boolean',
                'default' => false,
            ),
            'borderRadius' => array(
                'type' => 'number',
                'default' => 0,
            ),
            'shadow' => array(
                'type' => 'boolean',
                'default' => false,
            ),
            'baseFont' => array(
                'type' => 'string',
                'default' => '',
            ),
        ),
    ));
}
add_action('init', 'stagekitwp_register_board_members_block');

/**
 * Render the Board Members block.
 */
function stagekitwp_render_board_members_block($attributes) {
    $columns = isset($attributes['columns']) ? absint($attributes['columns']) : 0;
    $columns_desktop = isset($attributes['columnsDesktop']) ? absint($attributes['columnsDesktop']) : 0;

    if ($columns_desktop < 1 && $columns > 0) {
        $columns_desktop = $columns;
    }

    $shortcode_atts = array(
        'layout' => isset($attributes['layout']) ? sanitize_text_field($attributes['layout']) : 'grid',
        'show_photos' => isset($attributes['showPhotos']) ? ($attributes['showPhotos'] ? 'true' : 'false') : '',
        'show_company' => isset($attributes['showCompany']) ? ($attributes['showCompany'] ? 'true' : 'false') : '',
        'photo_size' => isset($attributes['photoSize']) ? sanitize_text_field($attributes['photoSize']) : '',
        'columns' => $columns > 0 ? $columns : '',
        'columns_desktop' => $columns_desktop > 0 ? $columns_desktop : '',
        'columns_tablet' => isset($attributes['columnsTablet']) && absint($attributes['columnsTablet']) > 0 ? absint($attributes['columnsTablet']) : '',
        'columns_mobile' => isset($attributes['columnsMobile']) && absint($attributes['columnsMobile']) > 0 ? absint($attributes['columnsMobile']) : '',
        'bg_color' => isset($attributes['bgColor']) ? sanitize_hex_color($attributes['bgColor']) : '',
        'text_color' => isset($attributes['textColor']) ? sanitize_hex_color($attributes['textColor']) : '',
        'border_color' => isset($attributes['borderColor']) ? sanitize_hex_color($attributes['borderColor']) : '',
        'border_width' => isset($attributes['borderWidth']) ? absint($attributes['borderWidth']) : '',
        'rounded' => isset($attributes['rounded']) ? ($attributes['rounded'] ? 'true' : 'false') : '',
        'border_radius' => isset($attributes['borderRadius']) ? absint($attributes['borderRadius']) : '',
        'shadow' => isset($attributes['shadow']) ? ($attributes['shadow'] ? 'true' : 'false') : '',
        'base_font' => isset($attributes['baseFont']) ? sanitize_text_field($attributes['baseFont']) : '',
    );

    $shortcode_atts = array_filter($shortcode_atts, function ($value) {
        return $value !== '' && $value !== null;
    });

    wp_enqueue_style('stagekitwp-board-members-block-css', plugins_url('board-members-block.css', __FILE__), array(), STAGEKITWP_CORE_VERSION);

    if (isset($attributes['layout']) && $attributes['layout'] === 'accordion') {
        wp_enqueue_script('stagekitwp-board-members-block-js', plugins_url('board-members-block.js', __FILE__), array(), STAGEKITWP_CORE_VERSION, true);
    }

    return stagekitwp_board_member_shortcode($shortcode_atts);
}
