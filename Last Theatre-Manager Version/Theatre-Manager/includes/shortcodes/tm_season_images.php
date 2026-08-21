<?php
function tm_season_images_shortcode($atts) {
    $atts = shortcode_atts(['season_id' => 0], $atts);
    if (!$atts['season_id']) return '';

    $output = '<div class="tm-season-images">';
    $fields = ['_tm_season_social_banner', '_tm_season_image_front', '_tm_season_image_back'];

    foreach ($fields as $field) {
        $img = get_post_meta($atts['season_id'], $field, true);
        if ($img) {
            $img_url = tm_get_image_url($img);
            if ($img_url) {
                if ($field == '_tm_season_social_banner') {
                    $output .= '<img src="' . esc_url($img_url) . '" alt="Season Image">';
                } else {
                    $output .= '<img src="' . esc_url($img_url) . '" alt="Season Image" style="display: inline-block;width:45%">';
                }
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
add_shortcode('tm_season_images', 'tm_season_images_shortcode');
?>