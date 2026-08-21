<?php
/**
* Shortcode: Advertisers Display
* Description: Outputs Advertiser entries styled with Display Options.
*/

defined('ABSPATH') || exit;

function tm_advertiser_shortcode($atts) {


    $args = [
        'post_type' => 'advertiser',
        'posts_per_page' => -1,
        'post_status' => 'publish',
    ];

    // Filter by restaurant field
    if (!empty($atts['category']) && $atts['category'] === 'restaurant') {
        $args['meta_query'] = [
            [
                'key' => '_tm_restaurant',
                'value' => 'yes',
                'compare' => '='
            ]
        ];
    }

    $query = new WP_Query($args);

    $bg_color = get_option('tm_advertiser_bg_color', '#ffffff');
    $text_color = get_option('tm_advertiser_text_color', '#000000');
	$border_color = get_option('tm_advertiser_border_color', '#000000');
    $border_width = get_option('tm_advertiser_border_width', '0');
    $rounded = get_option('tm_advertiser_rounded') ? 'true' : 'false';
    $border_radius = get_option('tm_advertiser_radius', '20');
	$grid_columns = get_option('tm_advertiser_grid_columns', '3');
    $shadow = get_option('tm_advertiser_shadow') ? 'true' : 'false';

    $atts = shortcode_atts([
        'view' => 'grid', // 'grid' or 'slider'
        'category' => '', // e.g., 'restaurant'
        'image_type' => 'banner', // 'banner' or 'logo' for slider view
        'columns' => $grid_columns, // number of columns in grid view
    ], $atts);
	
    $style = "background-color: {$bg_color}; color: {$text_color}; border: {$border_width}px solid {$border_color};";
    if ($rounded === 'true') { $style .= " border-radius: {$border_radius}px;"; }
    if ($shadow === 'true') { $style .= " box-shadow: 0 2px 6px rgba(0,0,0,0.2);"; }

    ob_start();
    if ($query->have_posts()) {
        if ($atts['view'] === 'slider') {
            echo '<div class="tm-advertiser-slider slick-slider">';
            while ($query->have_posts()) {
                $query->the_post();
                $image = ($atts['image_type'] === 'logo')
                    ? get_post_meta(get_the_ID(), '_tm_logo', true)
                    : get_post_meta(get_the_ID(), '_tm_banner', true);
                if ($image) {
                    $image_url = tm_get_image_url($image);
                    if ($image_url) {
                        echo '<div class="tm-advertiser-slide"><img src="' . esc_url($image_url) . '" alt="' . esc_attr(get_the_title()) . '" /></div>';
                    }
                }
            }
            echo '</div>';
        } else {
            $columns = max(1, min(6, intval($atts['columns'])));
            $grid_class = 'tm-grid-cols-' . $columns;

            echo '<div class="tm-advertiser-wrapper ' . esc_attr($grid_class) . '">';
            while ($query->have_posts()) {
                $query->the_post();
                $logo = get_post_meta(get_the_ID(), '_tm_logo', true);
                $website = get_post_meta(get_the_ID(), '_tm_website', true);
                if ($logo) {
                    $logo_url = tm_get_image_url($logo);
                    if ($logo_url) {
                        echo '<div class="tm-advertiser-entry" style="' . esc_attr($style) . '">';
                        if ($website) {
                            echo '<a href="' . esc_url($website) . '" target="_blank"><img src="' . esc_url($logo_url) . '" alt="' . esc_attr(get_the_title()) . '" /></a>';
                        } else {
                            echo '<img src="' . esc_url($logo_url) . '" alt="' . esc_attr(get_the_title()) . '" />';
                        }
                        echo '</div>';
                    }
                }
            }
            echo '</div>';
        }
        wp_reset_postdata();
    } else {
        echo '<p>No advertisers found.</p>';
    }

    return ob_get_clean();
}
add_shortcode('tm_advertisers', 'tm_advertiser_shortcode');
?>
