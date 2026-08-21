<?php
// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Shortcode to display testimonials using Slick Slider
 */
function tm_testimonials_shortcode($atts) {
    ob_start();

    // Get display options
    $bg_color = get_option('tm_testimonials_bg_color', '#ffffff');
    $text_color = get_option('tm_testimonials_text_color', '#000000');
    $border_color = get_option('tm_testimonials_border_color', '#000000');
    $border_width = get_option('tm_testimonials_border_width', '1');
    $rounded = get_option('tm_testimonials_rounded') ? '10px' : '0';
    $radius = get_option('tm_testimonials_radius', '10');
    $shadow = get_option('tm_testimonials_shadow') ? '0 4px 8px rgba(0,0,0,0.1)' : 'none';
    $rating_symbol = get_option('tm_testimonials_rating_symbol', 'Stars');

    // Symbol sets. For some sets we will use the same glyph for filled and empty
    // and rely on CSS filters/opacity to show the difference.
    $symbols = array(
        'Stars' => array('filled' => '★', 'empty' => '☆'),
        'Thumbs Up' => array('filled' => '👍', 'empty' => '👍'),
        'Rockets' => array('filled' => '🚀', 'empty' => '🚀'),
        'Hearts' => array('filled' => '❤️', 'empty' => '🤍'),
        'Theatre Masks' => array('filled' => '🎭', 'empty' => '🎭')
    );

    $symbol_set = isset($symbols[$rating_symbol]) ? $symbols[$rating_symbol] : $symbols['Stars'];
    $use_filter_for_empty = in_array($rating_symbol, array('Theatre Masks', 'Thumbs Up', 'Rockets'));

    // Base CSS for rating symbols
    $css = '';
    $css .= '.tm-symbol-filled { color: ' . esc_attr($text_color) . '; opacity: 1; }';
    $css .= '.tm-symbol-empty { color: ' . esc_attr($text_color) . '; text-shadow: -1px -1px 0 #000, 1px -1px 0 #000, -1px 1px 0 #000, 1px 1px 0 #000; opacity: 1; }';
    $css .= '.tm-testimonials-slider { width: 100%; margin: 0 auto; height: auto; }';

    if ($use_filter_for_empty) {
        $css .= '.tm-symbol-filled { transform: scale(1.05); }';
        $css .= '.tm-symbol-empty { opacity: 0.35; filter: grayscale(100%) contrast(80%) brightness(90%); }';
        $css .= '.tm-symbol-filled, .tm-symbol-empty { font-size: 1.2em; display: inline-block; margin: 0 2px; }';
    }

    echo '<style>' . $css . '</style>';

    // Enqueue Slick Slider assets
    wp_enqueue_style('slick-css', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css');
    wp_enqueue_style('slick-css', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css');
    wp_enqueue_script('slick-js', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js', array('jquery'), null, true);
    wp_add_inline_script('slick-js', '
        jQuery(document).ready(function($) {
            $(".tm-testimonials-slider").slick({
                slidesToShow: 1,
                slidesToScroll: 1,
                arrows: true,
                dots: true,
                adaptiveHeight: true,
                autoplay: true,
                autoplaySpeed: 5000,
                arrows: true
            });
        });
    ');

    $args = array(
        'post_type' => 'testimonial',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    );
    $query = new WP_Query($args);

    if ($query->have_posts()) {
        echo '<div class="tm-testimonials-slider">';
        while ($query->have_posts()) {
            $query->the_post();

            $rating = intval(get_post_meta(get_the_ID(), '_tm_rating', true));
            $comment = get_post_meta(get_the_ID(), '_tm_comment', true);
            $author = get_post_meta(get_the_ID(), '_tm_name', true);
            $date = get_the_date();

            echo '<div class="tm-testimonial" style="';
            echo 'background-color:' . esc_attr($bg_color) . ';';
            echo 'color:' . esc_attr($text_color) . ';';
            echo 'border:' . intval($border_width) . 'px solid ' . esc_attr($border_color) . ';';
            echo 'border-radius:' . intval($radius) . 'px;';
            echo 'box-shadow:' . esc_attr($shadow) . ';';
            echo 'padding: 20px; margin-bottom: 20px;';
            echo '">';

            echo '<div class="tm-rating">';
            for ($i = 1; $i <= 5; $i++) {
                if ($i <= $rating) {
                    echo '<span class="tm-symbol-filled">' . $symbol_set['filled'] . '</span>';
                } else {
                    echo '<span class="tm-symbol-empty">' . $symbol_set['empty'] . '</span>';
                }
            }
            echo '</div>';

            echo '<div class="tm-comment" style="margin-bottom: 10px;">' . esc_html($comment) . '</div>';
            echo '<div class="tm-author" style="font-style: italic;">' . esc_html($author) . ' - ' . esc_html($date) . '</div>';
            echo '</div>';
        }
        echo '</div>';
        wp_reset_postdata();
    } else {
        echo '<p>No testimonials found.</p>';
    }

    return ob_get_clean();
}
add_shortcode('tm_testimonials', 'tm_testimonials_shortcode');
?>
