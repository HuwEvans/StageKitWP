<?php
function tm_season_banner_shortcode($atts) {
    $atts = shortcode_atts(['season_id' => 0], $atts);
    if (!$atts['season_id']) return '';

    $output = '<div class="tm-season-banner">';
    $fields = ['_tm_season_social_banner', '_tm_season_image_front', '_tm_season_image_back'];

    foreach ($fields as $field) {
        $img = get_post_meta($atts['season_id'], $field, true);
        if ($img && $field == '_tm_season_social_banner') {
            $img_url = tm_get_image_url($img);
            if ($img_url) {
                $output .= '<img src="' . esc_url($img_url) . '" alt="Season Image">';
            }
        }
    }

    $output .= '</div>';

    $disable_border = get_option('tm_season_disable_border', false);
    if ($disable_border) {
        $output = str_replace(['border-width:', 'border-color:', 'border-style:'], '', $output);
    }

    return $output;
}
add_shortcode('tm_season_banner', 'tm_season_banner_shortcode');
?>