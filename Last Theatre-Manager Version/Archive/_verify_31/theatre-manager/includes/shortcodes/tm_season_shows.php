<?php
/**
 * Shortcode: tm_season_shows
 * Usage: [tm_season_shows season_id="123" which="all|current|next|current_and_next"]
 *
 * - season_id: optional, show only that season
 * - which: controls selection when season_id omitted:
 *     all (default) - show all seasons
 *     current        - show only the current season (today > start && today < end)
 *     next           - show the next season (after current, or first with start > today)
 *     current_and_next - show current season and the one after it (or next two upcoming if no current)
 */

function tm_season_shows_shortcode($atts) {

    $atts = shortcode_atts(['season_id' => 0, 'which' => 'all'], $atts);

    // Load seasons
    if (!empty($atts['season_id'])) {
        $seasons = get_posts([
            'post_type' => 'season',
            'numberposts' => -1,
            'p' => intval($atts['season_id']),
        ]);
    } else {
        $seasons = get_posts(['post_type' => 'season', 'numberposts' => -1]);

        // Parse start/end dates into timestamps and sort by start date
        foreach ($seasons as $s) {
            $start_raw = get_post_meta($s->ID, '_tm_season_start_date', true);
            $s->tm_start_ts = $start_raw ? strtotime($start_raw) : 0;
            $end_raw = get_post_meta($s->ID, '_tm_season_end_date', true);
            $s->tm_end_ts = $end_raw ? strtotime($end_raw) : 0;
        }
        usort($seasons, function($a, $b) {
            return $a->tm_start_ts <=> $b->tm_start_ts;
        });

        // Apply 'which' filter if requested
        $which = strtolower(trim($atts['which']));
        if ($which !== 'all') {
            $today_ts = strtotime(date('Y-m-d'));
            $current_index = null;
            foreach ($seasons as $i => $s) {
                if (!empty($s->tm_start_ts) && !empty($s->tm_end_ts)) {
                    if ($today_ts > $s->tm_start_ts && $today_ts < $s->tm_end_ts) {
                        $current_index = $i;
                        break;
                    }
                }
            }

            $selected = [];
            if ($which === 'current') {
                if ($current_index !== null) $selected[] = $seasons[$current_index];
            } elseif ($which === 'next') {
                if ($current_index !== null) {
                    $next_index = $current_index + 1;
                    if (isset($seasons[$next_index])) $selected[] = $seasons[$next_index];
                } else {
                    foreach ($seasons as $s) {
                        if (!empty($s->tm_start_ts) && $s->tm_start_ts > $today_ts) {
                            $selected[] = $s;
                            break;
                        }
                    }
                }
            } elseif ($which === 'current_and_next' || $which === 'both') {
                if ($current_index !== null) {
                    $selected[] = $seasons[$current_index];
                    $next_index = $current_index + 1;
                    if (isset($seasons[$next_index])) $selected[] = $seasons[$next_index];
                } else {
                    foreach ($seasons as $i => $s) {
                        if (!empty($s->tm_start_ts) && $s->tm_start_ts > $today_ts) {
                            $selected[] = $s;
                            if (isset($seasons[$i+1])) $selected[] = $seasons[$i+1];
                            break;
                        }
                    }
                }
            }

            if (!empty($selected)) {
                $seasons = $selected;
            }
        }
    }

    // Styling and output
    $text_color = function_exists('tm_sanitize_css_color') ? tm_sanitize_css_color(get_option('tm_show_text_color', '#000000'), '#000000') : '#000000';
    $border_color = function_exists('tm_sanitize_css_color') ? tm_sanitize_css_color(get_option('tm_show_border_color', '#000000'), '#000000') : '#000000';
    $border_width = absint(get_option('tm_show_border_width', '0'));
    $border_radius = absint(get_option('tm_show_radius', '20'));
    $shadow_enabled = (bool) get_option('tm_show_shadow');

    $style = 'color:' . esc_attr($text_color) . '; border-width:' . esc_attr($border_width) . 'px; border-color:' . esc_attr($border_color) . '; border-radius:' . esc_attr($border_radius) . 'px; border-style:solid;';
    if ($shadow_enabled) {
        $style .= ' box-shadow: 5px 10px;';
    }

    $output = '<div style="' . esc_attr($style) . '" class="tm-season-shows">';

    $slot_order = ['Fall', 'Winter', 'Spring'];

    foreach ($seasons as $season) {
        $output .= '<h2>' . esc_html($season->post_title) . '</h2>';

        $shows = get_posts([
            'post_type' => 'show',
            'numberposts' => -1,
            'meta_query' => [
                [
                    'key' => '_tm_show_season',
                    'value' => $season->ID,
                    'compare' => '='
                ]
            ]
        ]);

        $grouped = [];
        foreach ($shows as $show) {
            $slot = get_post_meta($show->ID, '_tm_show_time_slot', true);
            if (!isset($grouped[$slot])) {
                $grouped[$slot] = [];
            }
            $grouped[$slot][] = $show;
        }
        $output .= '<div class="tm-show-grid">'; // Start grid container
        foreach ($slot_order as $slot) {
            if (!isset($grouped[$slot])) continue;

            foreach ($grouped[$slot] as $show) {
                $img = get_post_meta($show->ID, '_tm_show_sm_image', true);
                $author = get_post_meta($show->ID, '_tm_show_author', true);
                $sub_authors = get_post_meta($show->ID, '_tm_show_sub_authors', true);
                $director = get_post_meta($show->ID, '_tm_show_director', true);
                $associate_director = get_post_meta($show->ID, '_tm_show_associate_director', true);

                $output .= '<div class="tm-show-card">';
                $output .= '<h3 style="color:' . esc_attr($text_color) . ';">' . esc_html($slot) . ' Show</h3>';
                if ($img) {
                    $img_url = tm_get_image_url($img);
                    if ($img_url) {
                        $output .= '<img src="' . esc_url($img_url) . '" alt="' . esc_attr($show->post_title) . '" class="tm-show-image">';
                    }
                }
                $output .= '<h4 style="color:' . esc_attr($text_color) . ';">' . esc_html($show->post_title) . '</h4>';

                if ($author) {
                    $output .= '<p><strong>Author:</strong> ' . esc_html($author) . '</p>';
                }
                if ($sub_authors) {
                    $output .= '<p><strong>Sub-author(s):</strong> ' . esc_html($sub_authors) . '</p>';
                }
                if ($director) {
                    $output .= '<p><strong>Director:</strong> ' . esc_html($director) . '</p>';
                }
                if ($associate_director) {
                    $output .= '<p><strong>Associate Director:</strong> ' . esc_html($associate_director) . '</p>';
                }

                $output .= '</div>'; // End show card
            }

        }
        $output .= '</div>'; // End grid container

    }

    $output .= '</div>';

    // Fetch header colors dynamically
    $header_colors = [];
    foreach (['h1', 'h2', 'h3', 'h4', 'h5', 'h6'] as $header) {
        $header_colors[$header] = get_option("tm_season_{$header}_color", ''); // No default value here
    }

    // Generate styles for headers
    $output .= '<style>';
    foreach ($header_colors as $header => $color) {
        $safe_color = function_exists('tm_sanitize_css_color') ? tm_sanitize_css_color($color, '') : '';
        if (!empty($safe_color)) {
            $output .= ".tm-season-shows {$header} { color: " . esc_attr($safe_color) . "; }";
        }
    }
    $output .= '</style>';

    $h2_color = function_exists('tm_sanitize_css_color') ? tm_sanitize_css_color(get_option('tm_season_h2_color', '#851212'), '#851212') : '#851212';

    $output .= '<style>';
    $output .= '.tm-season-shows h2 { color: ' . esc_attr($h2_color) . '; }';
    $output .= '</style>';

    return $output;
}
add_shortcode('tm_season_shows', 'tm_season_shows_shortcode');

?>