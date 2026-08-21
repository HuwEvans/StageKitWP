<?php
// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Shortcode: TM_Auditions
 * Usage: [TM_Auditions]
 *
 * Shows auditions for shows with an audition date no older than 7 days ago,
 * ordered by audition date ascending.
 */
function tm_auditions_shortcode($atts) {
    $atts = shortcode_atts(array(
        'days_past' => 7,
    ), $atts, 'tm_auditions');

    $days_past = absint($atts['days_past']);
    if ($days_past < 1) {
        $days_past = 7;
    }

    $cutoff = wp_date('Y-m-d', strtotime('-' . $days_past . ' days', current_time('timestamp')));

    $shows = get_posts(array(
        'post_type' => 'show',
        'numberposts' => -1,
        'meta_key' => '_tm_show_audition_date',
        'orderby' => 'meta_value',
        'order' => 'ASC',
        'meta_query' => array(
            array(
                'key' => '_tm_show_audition_date',
                'value' => $cutoff,
                'compare' => '>=',
                'type' => 'DATE',
            ),
        ),
    ));

    usort($shows, function($left, $right) {
        $left_date = get_post_meta($left->ID, '_tm_show_audition_date', true);
        $right_date = get_post_meta($right->ID, '_tm_show_audition_date', true);

        $left_timestamp = strtotime($left_date) ?: 0;
        $right_timestamp = strtotime($right_date) ?: 0;

        if ($left_timestamp === $right_timestamp) {
            return strcmp(get_the_title($left->ID), get_the_title($right->ID));
        }

        return $left_timestamp <=> $right_timestamp;
    });

    if (empty($shows)) {
        return '<div class="tm-auditions"><p><em>No upcoming auditions at this time.</em></p></div>';
    }

    $bg_color    = get_option('tm_auditions_bg_color',   '#ffffff');
    $text_color  = get_option('tm_auditions_text_color', '#000000');
    $base_font   = get_option('tm_auditions_base_font',  'Arial, sans-serif');

    $output = '<div class="tm-auditions">';

    foreach ($shows as $show) {
        $audition_date_raw = get_post_meta($show->ID, '_tm_show_audition_date', true);
        if (empty($audition_date_raw)) {
            continue;
        }

        // Keep backend filtering strict: include only dates that are not older than cutoff.
        if ($audition_date_raw < $cutoff) {
            continue;
        }

        $audition_details = get_post_meta($show->ID, '_tm_show_audition_details', true);
        $formatted_date = date_i18n(get_option('date_format'), strtotime($audition_date_raw));

        $output .= '<article class="tm-audition-item" style="background-color:' . esc_attr($bg_color) . ';color:' . esc_attr($text_color) . ';font-family:' . esc_attr($base_font) . ';margin-bottom:20px;padding:15px;border-bottom:1px solid rgba(0,0,0,0.1);">';
        $output .= '<h3 class="tm-audition-show-title">' . esc_html(get_the_title($show->ID)) . '</h3>';
        $output .= '<p class="tm-audition-date"><strong>Audition Date:</strong> ' . esc_html($formatted_date) . '</p>';

        if (!empty($audition_details)) {
              $safe_details = wp_kses_post(force_balance_tags(wpautop($audition_details)));
              $output .= '<div class="tm-audition-details">' . $safe_details . '</div>';
        }

        $output .= '</article>';
    }

    $output .= '</div>';

    return $output;
}

add_shortcode('tm_auditions', 'tm_auditions_shortcode');
add_shortcode('TM_Auditions', 'tm_auditions_shortcode');
