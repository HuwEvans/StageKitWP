<?php
function tm_show_cast_shortcode($atts) {
    $atts = shortcode_atts(['show_id' => 0], $atts);
    if (!$atts['show_id']) return '';

    $cast_members = get_posts([
        'post_type' => 'cast',
        'numberposts' => -1,
        'meta_key' => '_tm_cast_show',
        'meta_value' => $atts['show_id']
    ]);

    $output = '<div class="tm-show-cast">';
    foreach ($cast_members as $cast) {
        $actor = get_post_meta($cast->ID, '_tm_cast_actor_name', true);
        $character = get_post_meta($cast->ID, '_tm_cast_character_name', true);
        $picture = get_post_meta($cast->ID, '_tm_cast_picture', true);
        $picture_url = tm_get_image_url($picture);

        $output .= '<div class="cast-member">';
        if ($picture_url) $output .= '<img src="' . esc_url($picture_url) . '" alt="' . esc_attr($character) . '">';
        $output .= '<h3>' . esc_html($character) . '</h3>';
        $output .= '<p>' . esc_html($actor) . '</p>';
        $output .= '</div>';
    }
    $output .= '</div>';

    $header_colors = [];
    foreach (['h1', 'h2', 'h3', 'h4', 'h5', 'h6'] as $header) {
        $header_colors[$header] = function_exists('tm_sanitize_css_color') ? tm_sanitize_css_color(get_option("tm_show_{$header}_color", ''), '') : '';
    }

    $base_font = get_option('tm_show_base_font', 'Arial, sans-serif');

    $output .= '<style>';
    $output .= '.tm-show-cast { font-family: ' . esc_attr($base_font) . '; }';
    foreach ($header_colors as $header => $color) {
        if (!empty($color)) {
            $output .= '.tm-show-cast ' . $header . ' { color: ' . esc_attr($color) . '; }';
        }
    }
    $output .= '</style>';

    return $output;
}
add_shortcode('tm_show_cast', 'tm_show_cast_shortcode');
?>