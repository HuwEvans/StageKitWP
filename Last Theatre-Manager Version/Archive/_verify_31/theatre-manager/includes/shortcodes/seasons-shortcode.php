<?php
/**
 * Shortcode to display season entries.
 * Usage: [tm_seasons exclude="start_date,end_date"]
 * By default, all fields are shown. Use 'exclude' to hide specific fields.
 */

function tm_shortcode_seasons($atts) {
    $atts = shortcode_atts(array(
        'exclude' => ''
    ), $atts);

    $exclude = array_map('trim', explode(',', $atts['exclude']));

    // Load all seasons and sort by start date (robust to different meta formats)
    $seasons = get_posts(array('post_type' => 'season', 'posts_per_page' => -1));
    // Attach parsed start timestamp to each season for sorting
    foreach ($seasons as $s) {
        $start_raw = get_post_meta($s->ID, '_tm_season_start_date', true);
        $s->tm_start_ts = $start_raw ? strtotime($start_raw) : 0;
        $end_raw = get_post_meta($s->ID, '_tm_season_end_date', true);
        $s->tm_end_ts = $end_raw ? strtotime($end_raw) : 0;
    }
    usort($seasons, function($a, $b) {
        return $a->tm_start_ts <=> $b->tm_start_ts;
    });

    ob_start();
	$bg_color = esc_attr(get_option("tm_season_bg_color"));
	$text_color = esc_attr(get_option("tm_season_text_color"));
	$border_width = esc_attr(get_option("tm_season_border_width"));
	$rounded = get_option("tm_season_rounded") ? esc_attr(get_option("tm_season_radius")) : '0';
	$shadow = get_option("tm_season_shadow") ? '0 0 10px rgba(0,0,0,0.3)' : 'none';
	$style = "background-color: $bg_color; color: $text_color; border-width: {$border_width}px; border-style: solid; border-radius: {$rounded}px; box-shadow: $shadow;";

	echo '<div class="tm-shortcode-wrapper tm-season-wrapper" style="' . $style . '">';
    $today_ts = strtotime(date('Y-m-d'));
    foreach ($seasons as $season) {
        $id = $season->ID;
        $is_current = false;
        // If both start and end are valid timestamps, check > start and < end
        if (!empty($season->tm_start_ts) && !empty($season->tm_end_ts)) {
            if ($today_ts > $season->tm_start_ts && $today_ts < $season->tm_end_ts) {
                $is_current = true;
            }
        }

        // Render season block
        $classes = 'tm-entry tm-seasons-entry';
        if ($is_current) $classes .= ' tm-season-current';
        echo '<div class="' . esc_attr($classes) . '">';
        echo '<h3>' . esc_html($season->post_title);
        if ($is_current) {
            echo ' <span class="tm-season-current-label">(' . esc_html__('Current Season', 'theatre-manager') . ')</span>';
        }
        echo '</h3>';

        if (!in_array('start_date', $exclude)) {
            $value = get_post_meta($id, '_tm_season_start_date', true);
            if ($value) echo '<p><strong>' . esc_html__('Start Date:', 'theatre-manager') . '</strong> ' . esc_html($value) . '</p>';
        }
        if (!in_array('end_date', $exclude)) {
            $value = get_post_meta($id, '_tm_season_end_date', true);
            if ($value) echo '<p><strong>' . esc_html__('End Date:', 'theatre-manager') . '</strong> ' . esc_html($value) . '</p>';
        }
        foreach (['image_front', 'image_back', 'social_banner'] as $field) {
            if (!in_array($field, $exclude)) {
                $value = get_post_meta($id, '_tm_season_' . $field, true);
                if ($value) echo '<div><img src="' . esc_url($value) . '" style="max-width:150px;" /></div>';
            }
        }

        echo '</div>';
    }
    echo '</div>';
    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode('tm_seasons', 'tm_shortcode_seasons');
?>
