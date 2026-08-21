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

    // Get display options
    $bg_color     = get_option('tm_show_bg_color', '#ffffff');
    $text_color   = get_option('tm_show_text_color', '#000000');
    $border_color = get_option('tm_show_border_color', '#cccccc');
    $border_width = get_option('tm_show_border_width', '0');
    $rounded      = get_option('tm_show_rounded') ? 'true' : 'false';
    $radius       = get_option('tm_show_radius', '8');
    $shadow       = get_option('tm_show_shadow') ? 'true' : 'false';
    $base_font    = get_option('tm_show_base_font', 'Arial, sans-serif');

    $border_style = $border_width > 0 ? "border: {$border_width}px solid " . esc_attr($border_color) . ";" : "";
    $radius_style = $rounded === 'true' ? "border-radius: {$radius}px;" : "";
    $shadow_style = $shadow === 'true' ? "box-shadow: 0 2px 6px rgba(0,0,0,0.2);" : "";

    $wrapper_style = "background-color: " . esc_attr($bg_color) . "; color: " . esc_attr($text_color) . "; font-family: " . esc_attr($base_font) . "; padding: 15px; " . $border_style . $radius_style . $shadow_style;

    $output = '<div class="tm-show-cast" style="' . $wrapper_style . '">';
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