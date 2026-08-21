<?php
/**
 * Board Members Block v4.0
 * Gutenberg block for displaying board member profiles
 */

defined('ABSPATH') || exit;

/**
 * Register the Board Members block
 */
function tm_register_board_members_block() {
    register_block_type('theatre-manager/board-members', array(
        'render_callback' => 'tm_render_board_members_block',
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
        ),
    ));
}
add_action('init', 'tm_register_board_members_block');

/**
 * Render the Board Members block
 */
function tm_render_board_members_block($attributes) {
    // Sanitize attributes
    $layout = isset($attributes['layout']) ? sanitize_text_field($attributes['layout']) : 'grid';
    $show_photos = isset($attributes['showPhotos']) ? (bool) $attributes['showPhotos'] : true;
    $show_company = isset($attributes['showCompany']) ? (bool) $attributes['showCompany'] : true;
    $photo_size = isset($attributes['photoSize']) ? sanitize_text_field($attributes['photoSize']) : 'medium';
    $columns = isset($attributes['columns']) ? max(0, absint($attributes['columns'])) : 0;

    // Validate layout
    if (!in_array($layout, array('grid', 'list', 'accordion'))) {
        $layout = 'grid';
    }

    // Enqueue block styles
    wp_enqueue_style('tm-board-members-block-css', plugins_url('board-members-block.css', __FILE__), array(), '4.0.0');

    // Enqueue block scripts for interactive layouts
    if ($layout === 'accordion') {
        wp_enqueue_script('tm-board-members-block-js', plugins_url('board-members-block.js', __FILE__), array(), '4.0.0', true);
    }

    // Query board members
    $query_args = array(
        'post_type' => 'board_member',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'orderby' => 'title',
        'order' => 'ASC',
    );

    $posts = get_posts($query_args);

    if (empty($posts)) {
        return '<div class="tm-board-members-block tm-board-members-empty"><p>No board members found.</p></div>';
    }

    // Sort posts: priority roles first, then alphabetical
    $priority_roles = array('President', 'Vice-President', 'Treasurer', 'Secretary');
    usort($posts, function ($a, $b) use ($priority_roles) {
        $position_a = get_post_meta($a->ID, '_tm_position', true);
        $position_b = get_post_meta($b->ID, '_tm_position', true);

        $index_a = array_search($position_a, $priority_roles);
        $index_b = array_search($position_b, $priority_roles);

        // Prioritize predefined roles
        if ($index_a !== false && $index_b !== false) {
            return $index_a - $index_b;
        } elseif ($index_a !== false) {
            return -1;
        } elseif ($index_b !== false) {
            return 1;
        }

        // Alphabetical by position
        return strcmp($position_a, $position_b);
    });

    // Start output
    $output = '<div class="tm-board-members-block" data-layout="' . esc_attr($layout) . '" data-columns="' . esc_attr($columns) . '" data-photo-size="' . esc_attr($photo_size) . '">';

    if ($layout === 'grid') {
        $output .= tm_render_board_members_grid($posts, $show_photos, $show_company, $photo_size, $columns);
    } elseif ($layout === 'list') {
        $output .= tm_render_board_members_list($posts, $show_photos, $show_company);
    } elseif ($layout === 'accordion') {
        $output .= tm_render_board_members_accordion($posts, $show_photos, $show_company);
    }

    $output .= '</div>';

    return $output;
}

/**
 * Render grid layout
 */
function tm_render_board_members_grid($posts, $show_photos, $show_company, $photo_size, $columns) {
    $grid_class = 'tm-board-members-grid';
    if ($columns > 0) {
        $grid_class .= ' tm-columns-' . $columns;
    }

    $output = '<div class="' . esc_attr($grid_class) . '">';

    foreach ($posts as $post) {
        $name = get_the_title($post->ID);
        $position = get_post_meta($post->ID, '_tm_position', true);
        $company = get_post_meta($post->ID, '_tm_company', true);
        $photo = get_post_meta($post->ID, '_tm_photo', true);

        $output .= '<div class="tm-board-member-card tm-photo-' . esc_attr($photo_size) . '">';

        // Photo
        if ($show_photos && !empty($photo)) {
            $photo_url = is_numeric($photo) ? wp_get_attachment_url($photo) : $photo;
            if ($photo_url) {
                $output .= '<div class="tm-board-member-photo">';
                $output .= '<img src="' . esc_url($photo_url) . '" alt="' . esc_attr($name) . '" />';
                $output .= '</div>';
            }
        }

        // Name
        $output .= '<h3 class="tm-board-member-name">' . esc_html($name) . '</h3>';

        // Position
        if (!empty($position)) {
            $output .= '<p class="tm-board-member-position">' . esc_html($position) . '</p>';
        }

        // Company
        if ($show_company && !empty($company)) {
            $output .= '<p class="tm-board-member-company">' . esc_html($company) . '</p>';
        }

        $output .= '</div>';
    }

    $output .= '</div>';

    return $output;
}

/**
 * Render list layout
 */
function tm_render_board_members_list($posts, $show_photos, $show_company) {
    $output = '<ul class="tm-board-members-list">';

    foreach ($posts as $post) {
        $name = get_the_title($post->ID);
        $position = get_post_meta($post->ID, '_tm_position', true);
        $company = get_post_meta($post->ID, '_tm_company', true);
        $photo = get_post_meta($post->ID, '_tm_photo', true);

        $output .= '<li class="tm-board-member-item">';

        if ($show_photos && !empty($photo)) {
            $photo_url = is_numeric($photo) ? wp_get_attachment_url($photo) : $photo;
            if ($photo_url) {
                $output .= '<img src="' . esc_url($photo_url) . '" alt="' . esc_attr($name) . '" class="tm-board-member-photo-small" />';
            }
        }

        $output .= '<div class="tm-board-member-info">';
        $output .= '<h4 class="tm-board-member-name">' . esc_html($name) . '</h4>';

        if (!empty($position)) {
            $output .= '<p class="tm-board-member-position">' . esc_html($position) . '</p>';
        }

        if ($show_company && !empty($company)) {
            $output .= '<p class="tm-board-member-company">' . esc_html($company) . '</p>';
        }

        $output .= '</div>';
        $output .= '</li>';
    }

    $output .= '</ul>';

    return $output;
}

/**
 * Render accordion layout
 */
function tm_render_board_members_accordion($posts, $show_photos, $show_company) {
    $output = '<div class="tm-board-members-accordion">';

    foreach ($posts as $post) {
        $name = get_the_title($post->ID);
        $position = get_post_meta($post->ID, '_tm_position', true);
        $company = get_post_meta($post->ID, '_tm_company', true);
        $photo = get_post_meta($post->ID, '_tm_photo', true);

        $output .= '<div class="tm-board-member-accordion-item" data-member-id="' . esc_attr($post->ID) . '">';
        $output .= '<button class="tm-board-member-accordion-header" aria-expanded="false" aria-controls="tm-board-member-' . esc_attr($post->ID) . '">';
        $output .= '<span class="tm-member-name">' . esc_html($name) . '</span>';
        if (!empty($position)) {
            $output .= '<span class="tm-member-position-header">' . esc_html($position) . '</span>';
        }
        $output .= '<span class="tm-accordion-toggle">+</span>';
        $output .= '</button>';

        $output .= '<div id="tm-board-member-' . esc_attr($post->ID) . '" class="tm-board-member-accordion-content" hidden>';

        if ($show_photos && !empty($photo)) {
            $photo_url = is_numeric($photo) ? wp_get_attachment_url($photo) : $photo;
            if ($photo_url) {
                $output .= '<div class="tm-board-member-photo">';
                $output .= '<img src="' . esc_url($photo_url) . '" alt="' . esc_attr($name) . '" />';
                $output .= '</div>';
            }
        }

        if ($show_company && !empty($company)) {
            $output .= '<p class="tm-board-member-company"><strong>Company:</strong> ' . esc_html($company) . '</p>';
        }

        $output .= '</div>';
        $output .= '</div>';
    }

    $output .= '</div>';

    return $output;
}
