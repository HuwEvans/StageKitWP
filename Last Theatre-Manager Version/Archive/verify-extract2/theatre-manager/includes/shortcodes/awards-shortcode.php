<?php
/**
 * Awards shortcode - displays awards by season and category in a table format
 * Usage: [tm_awards]
 * Displays awards organized by season and category, sorted by status and name
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode handler for displaying awards
 */
function tm_shortcode_awards($atts) {
    $atts = shortcode_atts(array(
        'season_id' => '',
        'category' => '',
    ), $atts, 'tm_awards');

    // Query all awards
    $award_args = array(
        'post_type' => 'award',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
        'fields' => 'ids',
    );

    $award_ids = get_posts($award_args);
    if (empty($award_ids)) {
        return ''; // nothing to show
    }

    // Collect awards with related data
    $awards_data = array();
    foreach ($award_ids as $award_id) {
        $show_id = get_post_meta($award_id, '_tm_award_show_id', true);
        $category = get_post_meta($award_id, '_tm_award_category', true);
        $award_name = get_post_meta($award_id, '_tm_award_name', true);
        $recipient = get_post_meta($award_id, '_tm_award_recipient', true);
        $status = get_post_meta($award_id, '_tm_award_status', true);

        // Get show info
        $show_title = '';
        $season_id = '';
        if ($show_id) {
            $show_title = get_the_title($show_id);
            $season_id = get_post_meta($show_id, '_tm_show_season', true);
        }

        // Store award data
        $awards_data[] = array(
            'award_id' => $award_id,
            'show_id' => $show_id,
            'show_title' => $show_title,
            'season_id' => $season_id,
            'season_title' => $season_id ? get_the_title($season_id) : 'Unassigned',
            'category' => $category,
            'award_name' => $award_name,
            'recipient' => $recipient,
            'status' => $status,
        );
    }

    if (empty($awards_data)) {
        return ''; // nothing to show
    }

    // Sort awards: Status first (THEA Winner before Nomination), then by award name
    usort($awards_data, function ($a, $b) {
        // First sort by status (THEA Winner = 0, Nomination = 1)
        $status_order_a = ($a['status'] === 'THEA Winner') ? 0 : 1;
        $status_order_b = ($b['status'] === 'THEA Winner') ? 0 : 1;
        
        if ($status_order_a !== $status_order_b) {
            return $status_order_a - $status_order_b;
        }
        
        // Then sort by award name
        return strcasecmp($a['award_name'], $b['award_name']);
    });

    // Get display options
    $bg_color = get_option('tm_awards_bg_color', '#ffffff');
    $text_color = get_option('tm_awards_text_color', '#000000');
    $border_color = get_option('tm_awards_border_color', '#cccccc');
    $border_width = get_option('tm_awards_border_width', '1');
    $rounded = get_option('tm_awards_rounded') ? get_option('tm_awards_radius', '8') : '0';
    $shadow = get_option('tm_awards_shadow') ? '0 0 10px rgba(0,0,0,0.3)' : 'none';
    $base_font = get_option('tm_awards_base_font', 'Arial, sans-serif');
    $h2_color = get_option('tm_awards_h2_color', '#333333');
    $h3_color = get_option('tm_awards_h3_color', '#555555');

    $style = sprintf(
        'background-color:%s;color:%s;font-family:%s;border:%spx solid %s;border-radius:%spx;box-shadow:%s;padding:20px;',
        esc_attr($bg_color),
        esc_attr($text_color),
        esc_attr($base_font),
        esc_attr($border_width),
        esc_attr($border_color),
        esc_attr($rounded),
        esc_attr($shadow)
    );

    $output = '<div class="tm-shortcode-wrapper tm-awards-wrapper" style="' . esc_attr($style) . '">';

    // Group by season
    $seasons = array();
    foreach ($awards_data as $award) {
        $season_id = $award['season_id'];
        if (!isset($seasons[$season_id])) {
            // Get season start date for sorting
            $season_start_date = '';
            if ($season_id) {
                $season_start_date = get_post_meta($season_id, '_tm_season_start_date', true);
            }
            
            $seasons[$season_id] = array(
                'title' => $award['season_title'],
                'start_date' => $season_start_date,
                'categories' => array(),
            );
        }
        
        // Group by category within season
        $category = $award['category'];
        if (!isset($seasons[$season_id]['categories'][$category])) {
            $seasons[$season_id]['categories'][$category] = array();
        }
        
        $seasons[$season_id]['categories'][$category][] = $award;
    }

    // Sort seasons by start date in descending order (newest first)
    usort($seasons, function ($a, $b) {
        $date_a = strtotime($a['start_date']);
        $date_b = strtotime($b['start_date']);
        
        // If dates can't be parsed, keep original order
        if (!$date_a || !$date_b) {
            return 0;
        }
        
        // Sort descending (newest first)
        return $date_b - $date_a;
    });

    // Display awards grouped by season and category
    foreach ($seasons as $season_data) {
        $output .= '<div class="tm-awards-season" style="margin-bottom: 30px;">';
        $output .= '<h2 style="color:' . esc_attr($h2_color) . '; border-bottom: 2px solid ' . esc_attr($border_color) . '; padding-bottom: 10px;">' . esc_html($season_data['title']) . '</h2>';

        foreach ($season_data['categories'] as $category => $awards) {
            $output .= '<div class="tm-awards-category" style="margin-bottom: 20px;">';
            $output .= '<h3 style="color:' . esc_attr($h3_color) . '; margin-top: 15px;">' . esc_html($category) . '</h3>';
            
            // Create table for each category
            $output .= '<table style="width:100%; border-collapse: collapse; background-color:' . esc_attr($bg_color) . ';">';
            $output .= '<thead>';
            $output .= '<tr style="background-color:' . esc_attr($border_color) . '; color:' . esc_attr($text_color) . ';">';
            $output .= '<th style="padding: 12px; text-align: left; border: 1px solid ' . esc_attr($border_color) . ';">Show Name</th>';
            $output .= '<th style="padding: 12px; text-align: left; border: 1px solid ' . esc_attr($border_color) . ';">Award Status</th>';
            $output .= '<th style="padding: 12px; text-align: left; border: 1px solid ' . esc_attr($border_color) . ';">Award Name</th>';
            $output .= '<th style="padding: 12px; text-align: left; border: 1px solid ' . esc_attr($border_color) . ';">Recipient</th>';
            $output .= '</tr>';
            $output .= '</thead>';
            $output .= '<tbody>';

            foreach ($awards as $award) {
                // Highlight THEA Winner rows
                $row_bg = ($award['status'] === 'THEA Winner') ? 'background-color: rgba(255, 215, 0, 0.1);' : '';
                $output .= '<tr style="' . esc_attr($row_bg) . '">';
                $output .= '<td style="padding: 10px; border: 1px solid ' . esc_attr($border_color) . ';">' . esc_html($award['show_title']) . '</td>';
                $output .= '<td style="padding: 10px; border: 1px solid ' . esc_attr($border_color) . '; font-weight: ' . ($award['status'] === 'THEA Winner' ? 'bold;' : 'normal;') . '">';
                
                // Add visual indicator for THEA Winner
                if ($award['status'] === 'THEA Winner') {
                    $output .= '⭐ ';
                }
                $output .= esc_html($award['status']) . '</td>';
                $output .= '<td style="padding: 10px; border: 1px solid ' . esc_attr($border_color) . ';">' . esc_html($award['award_name']) . '</td>';
                $output .= '<td style="padding: 10px; border: 1px solid ' . esc_attr($border_color) . ';">' . esc_html($award['recipient']) . '</td>';
                $output .= '</tr>';
            }

            $output .= '</tbody>';
            $output .= '</table>';
            $output .= '</div>';
        }

        $output .= '</div>';
    }

    $output .= '</div>';

    return $output;
}
add_shortcode('tm_awards', 'tm_shortcode_awards');
