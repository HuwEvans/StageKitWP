<?php
/**
 * Shortcode: [tm_tickets]
 * Displays ticket purchase buttons for current season and its shows
 * Shows: 1 season button + up to 4 show buttons
 */

defined('ABSPATH') || exit;

function tm_tickets_shortcode($atts) {
    // Parse attributes
    $atts = shortcode_atts(array(
        'show_limit' => '4',
    ), $atts, 'tm_tickets');

    $show_limit = max(1, intval($atts['show_limit']));

    // Find the current season
    $current_season_args = array(
        'post_type' => 'season',
        'posts_per_page' => 1,
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key' => '_tm_season_is_current',
                'value' => 1,
                'compare' => '=',
            ),
        ),
    );

    $current_seasons = get_posts($current_season_args);

    if (empty($current_seasons)) {
        return '<div class="tm-tickets-block tm-tickets-empty"><p>No current season found.</p></div>';
    }

    $current_season = $current_seasons[0];
    $season_name = get_post_meta($current_season->ID, '_tm_season_name', true);
    if (empty($season_name)) {
        $season_name = get_the_title($current_season->ID);
    }
    $season_tickets_url = get_post_meta($current_season->ID, '_tm_season_tickets_url', true);

    // Get shows for the current season
    $shows_args = array(
        'post_type' => 'show',
        'posts_per_page' => $show_limit,
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key' => '_tm_show_season',
                'value' => $current_season->ID,
                'compare' => '=',
            ),
        ),
        'orderby' => 'meta_value',
        'order' => 'ASC',
    );

    $shows = get_posts($shows_args);

    // Start output
    $output = '<div class="tm-tickets-block">';

    // Display display options styling
    $bg_color = get_option('tm_tickets_bg_color', '#f8f9fa');
    $text_color = get_option('tm_tickets_text_color', '#333333');
    $button_color = get_option('tm_tickets_button_color', '#0073aa');
    $button_hover_color = get_option('tm_tickets_button_hover_color', '#005a87');
    $border_color = get_option('tm_tickets_border_color', '#e0e0e0');
    $rounded = get_option('tm_tickets_rounded', '1');
    $radius = get_option('tm_tickets_radius', '6');
    $shadow = get_option('tm_tickets_shadow', '0');
    $base_font = get_option('tm_tickets_base_font', 'Arial, sans-serif');

    // Build styles
    $border_style = 'border: 1px solid ' . esc_attr($border_color) . ';';
    $border_radius_style = $rounded ? 'border-radius: ' . intval($radius) . 'px;' : '';
    $box_shadow_style = $shadow ? 'box-shadow: 0 2px 4px rgba(0,0,0,0.1);' : '';

    $container_style = 'background-color: ' . esc_attr($bg_color) . '; 
        color: ' . esc_attr($text_color) . '; 
        font-family: ' . esc_attr($base_font) . '; 
        padding: 30px; 
        ' . $border_style . ' ' . $border_radius_style . ' ' . $box_shadow_style;

    $output .= '<div style="' . esc_attr($container_style) . '">';

    // Season ticket button (always show if URL exists)
    if (!empty($season_tickets_url)) {
        $output .= '<div class="tm-ticket-button-wrapper">';
        $output .= '<a href="' . esc_url($season_tickets_url) . '" class="tm-ticket-button tm-ticket-season" style="background-color: ' . esc_attr($button_color) . '; color: #ffffff;">';
        $output .= '<span class="tm-ticket-button-label">Season Tickets</span>';
        $output .= '<span class="tm-ticket-button-title">' . esc_html($season_name) . '</span>';
        $output .= '</a>';
        $output .= '</div>';
    }

    // Show ticket buttons
    if (!empty($shows)) {
        foreach ($shows as $show) {
            $show_title = get_the_title($show->ID);
            $show_tickets_url = get_post_meta($show->ID, '_tm_show_tickets_url', true);

            // Only show if URL exists
            if (!empty($show_tickets_url)) {
                $output .= '<div class="tm-ticket-button-wrapper">';
                $output .= '<a href="' . esc_url($show_tickets_url) . '" class="tm-ticket-button tm-ticket-show" style="background-color: ' . esc_attr($button_color) . '; color: #ffffff;">';
                $output .= '<span class="tm-ticket-button-label">Show Tickets</span>';
                $output .= '<span class="tm-ticket-button-title">' . esc_html($show_title) . '</span>';
                $output .= '</a>';
                $output .= '</div>';
            }
        }
    }

    $output .= '</div>';
    $output .= '</div>';

    // Inline styles for buttons
    $output .= '<style>
        .tm-tickets-block {
            margin: 20px 0;
        }

        .tm-tickets-empty {
            padding: 20px;
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
            color: #666;
        }

        .tm-ticket-button-wrapper {
            margin-bottom: 16px;
        }

        .tm-ticket-button-wrapper:last-child {
            margin-bottom: 0;
        }

        .tm-ticket-button {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px 24px;
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.2s ease;
            gap: 6px;
        }

        .tm-ticket-button:hover {
            background-color: ' . esc_attr($button_hover_color) . ' !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .tm-ticket-button-label {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            opacity: 0.9;
        }

        .tm-ticket-button-title {
            font-size: 18px;
            font-weight: 700;
        }

        @media (min-width: 600px) {
            .tm-ticket-button {
                padding: 24px 32px;
            }

            .tm-ticket-button-label {
                font-size: 13px;
            }

            .tm-ticket-button-title {
                font-size: 20px;
            }
        }
    </style>';

    return $output;
}
add_shortcode('tm_tickets', 'tm_tickets_shortcode');
