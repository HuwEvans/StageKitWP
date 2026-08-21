<?php
function tm_sponsor_slider_shortcode($atts) {
    $args = array(
        'post_type' => 'sponsor',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    );

    $query = new WP_Query($args);

    // Get Display Options
    $bg_color = get_option('tm_sponsor_bg_color', '#ffffff');
    $border_color = get_option('tm_sponsor_border_color', '#000000');
    $border_width = get_option('tm_sponsor_border_width', '0');
    $rounded = get_option('tm_sponsor_rounded') ? 'true' : 'false';
    $border_radius = get_option('tm_sponsor_radius', '20');
    $shadow = get_option('tm_sponsor_shadow') ? 'true' : 'false';

    $style = "background-color: {$bg_color}; border: {$border_width}px solid {$border_color};";
    if ($rounded === 'true') {
        $style .= " border-radius: {$border_radius}px;";
    }
    if ($shadow === 'true') {
        $style .= " box-shadow: 0 2px 6px rgba(0,0,0,0.2);";
    }

    ob_start();

    if ($query->have_posts()) {
//        echo '<div class="tm-sponsor-slider-wrapper" style="' . esc_attr($style) . '">';
        echo '<div class="tm-sponsor-slider-wrapper">';

        echo '<div class="tm-sponsor-slider">';
        while ($query->have_posts()) {
            $query->the_post();
            $banner = get_post_meta(get_the_ID(), '_tm_banner', true);
            $website = get_post_meta(get_the_ID(), '_tm_website', true);

            if ($banner && $website) {
                $banner_url = tm_get_image_url($banner);
                if ($banner_url) {
                    echo '<div class="tm-sponsor-slide">';
                    echo '<a href="' . esc_url($website) . '" target="_blank">';
                    echo '<img src="' . esc_url($banner_url) . '" alt="' . esc_attr(get_the_title()) . '" />';
                    echo '</a>';
                    echo '</div>';
                }
            }
        }
        echo '</div>'; // .tm-sponsor-slider
        echo '</div>'; // .tm-sponsor-slider-wrapper

        // Inline CSS
        echo '<style>
            .tm-sponsor-slider-wrapper {
                width: 100%;
                margin: 0 auto;
                overflow: hidden;
            }
            .tm-sponsor-slider {
                display: none;
            }
            .tm-sponsor-slide {
                text-align: center;
            }
            .tm-sponsor-slide img {
                max-width: 100%;
                height: auto;
                display: inline-block;
            }
        </style>';

        // Slick slider initialization
        echo '<script>
            jQuery(document).ready(function($) {
                $(".tm-sponsor-slider").slick({
                    slidesToShow: 1,
                    slidesToScroll: 1,
                    autoplay: true,
                    autoplaySpeed: 3000,
                    arrows: false,
                    dots: false,
                    adaptiveHeight: true
                }).show();
            });
        </script>';

        wp_reset_postdata();
    } else {
        echo '<p>No sponsor banners found.</p>';
    }

    return ob_get_clean();
}
add_shortcode('tm_sponsor_slider', 'tm_sponsor_slider_shortcode');
?>
