<?php
/**
* Shortcode: Advertisers Display
* Description: Outputs Advertiser entries styled with Display Options.
*/

defined('ABSPATH') || exit;

function stagekitwp_advertiser_shortcode($atts) {

    // Normalise attributes FIRST so $atts['category'] is safely validated
    // before it is used in the meta_query below.
    $atts = shortcode_atts( [
        'view'       => 'grid',
        'category'   => '',
        'image_type' => 'banner',
        'columns'    => get_option( 'stagekitwp_advertiser_grid_columns', '3' ),
        'lock_columns' => 'false',
        'mini_logos'   => 'false',
    ], $atts, 'stagekitwp_advertisers' );
    $atts['category'] = sanitize_key( $atts['category'] );

    $is_true = static function( $value ) {
        return in_array( strtolower( (string) $value ), [ '1', 'true', 'yes', 'on' ], true );
    };

    $args = [
        'post_type'      => 'advertiser',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
    ];

    // Filter by restaurant field
    if ( ! empty( $atts['category'] ) && $atts['category'] === 'restaurant' ) {
        $args['meta_query'] = [
            [
                'key'     => '_stagekitwp_restaurant',
                'value'   => 'yes',
                'compare' => '='
            ]
        ];
    }

    $query = new WP_Query( $args );

    $bg_color     = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_advertiser_bg_color', '' ), '#ffffff' );
    $text_color   = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_advertiser_text_color', '' ), '#000000' );
    $border_color = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_advertiser_border_color', '' ), '#000000' );
    $border_width = absint( get_option( 'stagekitwp_advertiser_border_width', '0' ) );
    $rounded      = get_option( 'stagekitwp_advertiser_rounded' ) ? 'true' : 'false';
    $border_radius = absint( get_option( 'stagekitwp_advertiser_radius', '20' ) );
    $grid_columns = absint( get_option( 'stagekitwp_advertiser_grid_columns', '3' ) );
    $shadow       = get_option( 'stagekitwp_advertiser_shadow' ) ? 'true' : 'false';
    $base_font    = sanitize_text_field( get_option( 'stagekitwp_advertiser_base_font', 'Arial, sans-serif' ) );

    // shortcode_atts() already called at top of function; $atts is normalised.
	
    $style = "background-color: {$bg_color}; color: {$text_color}; font-family: {$base_font}; border: {$border_width}px solid {$border_color};";
    if ($rounded === 'true') { $style .= " border-radius: {$border_radius}px;"; }
    if ($shadow === 'true') { $style .= " box-shadow: 0 2px 6px rgba(0,0,0,0.2);"; }

    ob_start();
    if ($query->have_posts()) {
        if ($atts['view'] === 'slider') {
            echo '<div class="stagekitwp-advertiser-slider slick-slider">';
            while ($query->have_posts()) {
                $query->the_post();
                $image = ($atts['image_type'] === 'logo')
                    ? get_post_meta(get_the_ID(), '_stagekitwp_logo', true)
                    : get_post_meta(get_the_ID(), '_stagekitwp_banner', true);
                if ($image) {
                    $image_url = stagekitwp_get_image_url($image);
                    if ($image_url) {
                        echo '<div class="stagekitwp-advertiser-slide"><img src="' . esc_url($image_url) . '" alt="' . esc_attr(get_the_title()) . '" /></div>';
                    }
                }
            }
            echo '</div>';
        } else {
            $columns = max(1, min(6, intval($atts['columns'])));
            $grid_class = 'stagekitwp-grid-cols-' . $columns;
            $lock_columns = $is_true( $atts['lock_columns'] );
            $mini_logos   = $is_true( $atts['mini_logos'] );

            $wrapper_classes = [ 'stagekitwp-advertiser-wrapper', $grid_class ];
            if ( $lock_columns ) {
                $wrapper_classes[] = 'stagekitwp-adv-lock-cols';
            }
            if ( $mini_logos ) {
                $wrapper_classes[] = 'stagekitwp-adv-mini-logos';
            }

            $wrapper_style = '';
            if ( $lock_columns ) {
                $wrapper_style = ' style="--stagekitwp-adv-force-cols:' . esc_attr( (string) $columns ) . ';"';
            }

            echo '<div class="' . esc_attr( implode( ' ', $wrapper_classes ) ) . '"' . $wrapper_style . '>';
            while ($query->have_posts()) {
                $query->the_post();
                $logo = get_post_meta(get_the_ID(), '_stagekitwp_logo', true);
                $website = get_post_meta(get_the_ID(), '_stagekitwp_website', true);
                if ($logo) {
                    $logo_url = stagekitwp_get_image_url($logo);
                    if ($logo_url) {
                        echo '<div class="stagekitwp-advertiser-entry" style="' . esc_attr($style) . '">';
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
add_shortcode('stagekitwp_advertisers', 'stagekitwp_advertiser_shortcode');
?>
