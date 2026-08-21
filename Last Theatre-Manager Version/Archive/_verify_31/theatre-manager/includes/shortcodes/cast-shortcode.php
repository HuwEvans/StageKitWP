<?php
/**
 * Cast shortcode - clean implementation
 * Usage: [tm_cast exclude="picture,actor_name" show_id="123" group_by="show" orderby="title" order="ASC"]
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render a single cast member by post ID
 */
function tm_render_cast_member_by_id($post_id, $exclude = array(), $show_output = true) {
    $output = '<div class="tm-entry tm-cast-entry">';
    $output .= '<h3>' . esc_html(get_the_title($post_id)) . '</h3>';

    if (!in_array('actor_name', $exclude)) {
        $value = get_post_meta($post_id, '_tm_cast_actor_name', true);
        if ($value) {
            $output .= '<p><strong>Actor Name:</strong> ' . esc_html($value) . '</p>';
        }
    }

    if (!in_array('picture', $exclude)) {
        $image_value = get_post_meta($post_id, '_tm_cast_picture', true);
        if ($image_value) {
            $image_url = tm_get_image_url($image_value);
            if ($image_url) {
                $output .= '<div class="tm-cast-image">';
                $output .= '<img src="' . esc_url($image_url) . '" alt="' . esc_attr(get_the_title($post_id)) . '" />';
                $output .= '</div>';
            }
        }
    }

    if ($show_output && !in_array('show', $exclude)) {
        $show_id = get_post_meta($post_id, '_tm_cast_show', true);
        if ($show_id) {
            $output .= '<p><strong>Show:</strong> ' . esc_html(get_the_title($show_id)) . '</p>';
        }
    }

    $output .= '</div>';
    return $output;
}

/**
 * Shortcode handler
 */
function tm_shortcode_cast($atts) {
    $atts = shortcode_atts(array(
        'exclude' => '',
        'show_id' => '',
        'orderby' => 'title',
        'order' => 'ASC',
        'group_by' => 'none'
    ), $atts, 'tm_cast');

    $exclude = array_filter(array_map('trim', explode(',', $atts['exclude'])));

    // Query cast members
    $cast_args = array(
        'post_type' => 'cast',
        'posts_per_page' => -1,
        'orderby' => $atts['orderby'],
        'order' => $atts['order'],
        'fields' => 'ids',
    );

    if (!empty($atts['show_id'])) {
        $cast_args['meta_query'] = array(
            array(
                'key' => '_tm_cast_show',
                'value' => intval($atts['show_id']),
                'compare' => '='
            )
        );
    }

    $cast_ids = get_posts($cast_args);
    if (empty($cast_ids)) {
        return ''; // nothing to show
    }

    $output = '';

    // Wrapper style (use theme defaults if options missing)
    $bg_color = get_option('tm_cast_bg_color', '#ffffff');
    $text_color = get_option('tm_cast_text_color', '#000000');
    $border_width = get_option('tm_cast_border_width', '1');
    $rounded = get_option('tm_cast_rounded') ? get_option('tm_cast_radius', '8') : '0';
    $shadow = get_option('tm_cast_shadow') ? '0 0 10px rgba(0,0,0,0.3)' : 'none';
    $style = sprintf('background-color:%s;color:%s;border-width:%spx;border-style:solid;border-radius:%spx;box-shadow:%s;', esc_attr($bg_color), esc_attr($text_color), esc_attr($border_width), esc_attr($rounded), esc_attr($shadow));

    $output .= '<div class="tm-shortcode-wrapper tm-cast-wrapper" style="' . esc_attr($style) . '">';

    if ($atts['group_by'] === 'show') {
        // Group cast IDs by show meta
        $grouped = array();
        foreach ($cast_ids as $cid) {
            $show_id = get_post_meta($cid, '_tm_cast_show', true) ?: 0;
            if (!isset($grouped[$show_id])) {
                $grouped[$show_id] = array();
            }
            $grouped[$show_id][] = $cid;
        }

        foreach ($grouped as $show_id => $members) {
            if ($show_id) {
                $output .= '<div class="tm-cast-show-section">';
                $output .= '<h2 class="tm-cast-show-title">' . esc_html(get_the_title($show_id)) . '</h2>';
                $output .= '<div class="tm-cast-grid">';
                foreach ($members as $mid) {
                    // When grouped by show, don't repeat the show title under each member
                    $output .= tm_render_cast_member_by_id($mid, $exclude, false);
                }
                $output .= '</div></div>';
            } else {
                // members without a show: show them in a 'Unassigned' block
                $output .= '<div class="tm-cast-show-section">';
                $output .= '<h2 class="tm-cast-show-title">' . esc_html__('Unassigned', 'theatre-manager') . '</h2>';
                $output .= '<div class="tm-cast-grid">';
                foreach ($members as $mid) {
                    // Unassigned members also shouldn't show a redundant show name
                    $output .= tm_render_cast_member_by_id($mid, $exclude, false);
                }
                $output .= '</div></div>';
            }
        }

    } else {
        // Simple grid of all cast members
        $output .= '<div class="tm-cast-grid">';
        foreach ($cast_ids as $cid) {
            $output .= tm_render_cast_member_by_id($cid, $exclude);
        }
        $output .= '</div>';
    }

    $output .= '</div>';

    // Inline minimal CSS for layout
    $output .= '<style>
    .tm-cast-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:20px;padding:20px}
    .tm-cast-show-title{padding:20px;margin:0;background:rgba(0,0,0,0.05)}
    .tm-cast-entry{text-align:center;padding:15px;background:rgba(255,255,255,0.7);border-radius:8px;transition:transform .2s}
    .tm-cast-entry:hover{transform:translateY(-5px)}
    .tm-cast-image{margin:10px auto}
    .tm-cast-image img{max-width:150px;height:auto;border-radius:5px;box-shadow:0 2px 5px rgba(0,0,0,0.1)}
    .tm-cast-show-section{margin-bottom:30px}
    @media(max-width:768px){.tm-cast-grid{grid-template-columns:repeat(auto-fill,minmax(200px,1fr))}}
    </style>';

    return $output;
}
add_shortcode('tm_cast', 'tm_shortcode_cast');
