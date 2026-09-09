<?php
/**
 * Shortcode to display a gallery of program PDFs by season.
 * Usage: [stagekitwp_programs season="123" columns="3"]
 * Usage: [stagekitwp_programs layout="current_link"]
 * If season omitted, shows programs for all seasons grouped by season.
 * current_link layout returns a single program link for the current show,
 * and falls back to the previous show's program if the current show has none.
 */

/**
 * Resolve a usable program URL for a show.
 */
function stagekitwp_get_program_url_for_show($show_id) {
    $program_id = get_post_meta($show_id, '_stagekitwp_show_program', true);
    $program_url = get_post_meta($show_id, '_stagekitwp_show_program_url', true);

    if (!$program_url && $program_id) {
        $program_url = wp_get_attachment_url($program_id);
    }

    return $program_url ? esc_url_raw($program_url) : '';
}

/**
 * Base query defaults for read-only shortcode loops.
 */
function stagekitwp_programs_query_defaults($args = array()) {
    $defaults = array(
        'no_found_rows'          => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    );

    return array_merge($defaults, $args);
}

/**
 * Return the date window for a season slot. StageKitWP seasons normally run
 * September through June: Fall, Winter, then Spring.
 */
function stagekitwp_programs_slot_date_range($slot, $season_start, $season_end) {
    $season_year = (int) wp_date('Y', $season_start);
    $slot_dates = array(
        'Fall'   => array(strtotime($season_year . '-09-01'), strtotime($season_year . '-11-30') + 86399),
        'Winter' => array(strtotime($season_year . '-12-01'), strtotime(($season_year + 1) . '-03-31') + 86399),
        'Spring' => array(strtotime(($season_year + 1) . '-04-01'), strtotime(($season_year + 1) . '-06-30') + 86399),
    );

    if (!isset($slot_dates[$slot])) {
        return array('start' => 0, 'end' => 0);
    }

    return array(
        'start' => max($season_start, $slot_dates[$slot][0]),
        'end' => min($season_end, $slot_dates[$slot][1]),
    );
}

/**
 * Resolve the program show by dates: playing now, next upcoming, then latest past.
 */
function stagekitwp_programs_resolve_current_show_id() {
    $now = current_time('timestamp');
    $slot_order = stagekitwp_show_slot_order();
    $seasons = get_posts(stagekitwp_programs_query_defaults(array(
        'post_type' => 'season',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'orderby' => 'meta_value',
        'meta_key' => '_stagekitwp_season_start_date',
        'order' => 'ASC',
    )));
    $candidates = array('current' => array(), 'upcoming' => array(), 'past' => array());

    foreach ($seasons as $season) {
        $season_start = strtotime(get_post_meta($season->ID, '_stagekitwp_season_start_date', true));
        $season_end_raw = strtotime(get_post_meta($season->ID, '_stagekitwp_season_end_date', true));
        $season_end = $season_end_raw ? $season_end_raw + 86399 : 0;
        if (!$season_start || !$season_end) {
            continue;
        }

        $shows = get_posts(stagekitwp_programs_query_defaults(array(
            'post_type' => 'show',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => array(array(
                'key' => '_stagekitwp_show_season',
                'value' => $season->ID,
                'compare' => '=',
            )),
        )));

        foreach ($shows as $show) {
            if (!stagekitwp_get_program_url_for_show($show->ID)) {
                continue;
            }
            $slot = get_post_meta($show->ID, '_stagekitwp_show_time_slot', true);
            if (!isset($slot_order[$slot])) {
                continue;
            }
            $range = stagekitwp_programs_slot_date_range($slot, $season_start, $season_end);
            if (!$range['start'] || !$range['end'] || $range['start'] > $range['end']) {
                continue;
            }
            $candidate = array(
                'id' => $show->ID,
                'season_start' => $season_start,
                'season_end' => $season_end,
                'slot_order' => $slot_order[$slot],
            );
            if ($range['start'] <= $now && $range['end'] >= $now) {
                $candidates['current'][] = $candidate;
            } elseif ($range['start'] > $now) {
                $candidates['upcoming'][] = $candidate;
            } elseif ($range['end'] < $now) {
                $candidates['past'][] = $candidate;
            }
        }
    }

    if (!empty($candidates['current'])) {
        usort($candidates['current'], static function($left, $right) {
            return $left['season_start'] <=> $right['season_start'] ?: $left['slot_order'] <=> $right['slot_order'];
        });
        return intval($candidates['current'][0]['id']);
    }
    if (!empty($candidates['upcoming'])) {
        usort($candidates['upcoming'], static function($left, $right) {
            return $left['season_start'] <=> $right['season_start'] ?: $left['slot_order'] <=> $right['slot_order'];
        });
        return intval($candidates['upcoming'][0]['id']);
    }
    if (!empty($candidates['past'])) {
        usort($candidates['past'], static function($left, $right) {
            return $right['season_end'] <=> $left['season_end'] ?: $right['slot_order'] <=> $left['slot_order'];
        });
        return intval($candidates['past'][0]['id']);
    }

    return 0;
}

/**
 * Find the previous show that has a program URL.
 */
function stagekitwp_get_previous_program_show($current_show_id = 0) {
    $exclude_ids = $current_show_id ? array(intval($current_show_id)) : array();

    // Prefer another show from the same season first.
    if ($current_show_id) {
        $current_season_id = intval(get_post_meta($current_show_id, '_stagekitwp_show_season', true));
        if ($current_season_id > 0) {
            $same_season = new WP_Query(stagekitwp_programs_query_defaults(array(
                'post_type' => 'show',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'orderby' => 'date',
                'order' => 'DESC',
                'post__not_in' => $exclude_ids,
                'meta_query' => array(
                    array(
                        'key' => '_stagekitwp_show_season',
                        'value' => $current_season_id,
                        'compare' => '=',
                    ),
                ),
            )));

            if ($same_season->have_posts()) {
                while ($same_season->have_posts()) {
                    $same_season->the_post();
                    $candidate_id = get_the_ID();
                    if (stagekitwp_get_program_url_for_show($candidate_id)) {
                        wp_reset_postdata();
                        return intval($candidate_id);
                    }
                }
            }
            wp_reset_postdata();
        }
    }

    // Then search all published shows by most recent publish date.
    $fallback = new WP_Query(stagekitwp_programs_query_defaults(array(
        'post_type' => 'show',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
        'post__not_in' => $exclude_ids,
    )));

    if ($fallback->have_posts()) {
        while ($fallback->have_posts()) {
            $fallback->the_post();
            $candidate_id = get_the_ID();
            if (stagekitwp_get_program_url_for_show($candidate_id)) {
                wp_reset_postdata();
                return intval($candidate_id);
            }
        }
    }
    wp_reset_postdata();

    return 0;
}

/**
 * Render current_link layout output for stagekitwp_programs shortcode.
 */
function stagekitwp_render_programs_current_link_layout($atts) {
    $selected_show_id = stagekitwp_programs_resolve_current_show_id();
    $program_url = $selected_show_id ? stagekitwp_get_program_url_for_show($selected_show_id) : '';

    if (!$program_url || !$selected_show_id) {
        return '<p class="stagekitwp-programs-current-link-empty">No program available.</p>';
    }

    $link_text = !empty($atts['link_text']) ? $atts['link_text'] : 'View Program';
    $target = ($atts['open_new'] === 'false' || $atts['open_new'] === '0') ? '' : ' target="_blank" rel="noopener noreferrer"';
    $show_title = get_the_title($selected_show_id);

    $output = '<div class="stagekitwp-programs-current-link-wrap">';
    $output .= '<a class="stagekitwp-programs-current-link" href="' . esc_url($program_url) . '"' . $target . '>' . esc_html($link_text) . '</a>';
    if ($show_title) {
        $output .= '<div class="stagekitwp-programs-current-link-show">' . esc_html($show_title) . '</div>';
    }
    $output .= '</div>';

    return $output;
}

function stagekitwp_programs_shortcode($atts) {
    $atts = shortcode_atts(array(
        'layout' => 'gallery',
        'season' => '',
        'columns' => 3,
        'size' => 'medium',
        'link_text' => 'View Program',
        'open_new' => 'true',
    ), $atts);

    $layout = strtolower(trim((string) $atts['layout']));

    if ($layout === 'current_link') {
        return stagekitwp_render_programs_current_link_layout($atts);
    }

    // Enqueue PDF.js preview assets only for gallery/canvas rendering layouts.
    stagekitwp_enqueue_pdf_preview_assets();

    $season = $atts['season'];
    $columns = max(1, intval($atts['columns']));

    ob_start();

    if ($season) {
        // If numeric, use as ID; otherwise try to resolve by slug/title
        if (is_numeric($season)) {
            $season_id = intval($season);
        } else {
            $s = get_page_by_path($season, OBJECT, 'season');
            $season_id = $s ? $s->ID : 0;
        }

        $meta_query = array(
            array(
                'key' => '_stagekitwp_show_season',
                'value' => $season_id,
                'compare' => '='
            )
        );

        $query = new WP_Query(stagekitwp_programs_query_defaults(array('post_type' => 'show', 'posts_per_page' => -1, 'meta_query' => $meta_query)));
        echo '<div class="stagekitwp-programs-gallery stagekitwp-programs-season-' . esc_attr($season_id) . '">';
        $i = 0;
        while ($query->have_posts()) {
            $query->the_post();
            $id = get_the_ID();
            $program_id = get_post_meta($id, '_stagekitwp_show_program', true);
            $program_url = get_post_meta($id, '_stagekitwp_show_program_url', true);
            if (!$program_url && $program_id) $program_url = wp_get_attachment_url($program_id);

            echo '<div class="stagekitwp-program-item" style="width:' . esc_attr(100 / $columns) . '%;float:left;padding:8px;box-sizing:border-box;">';
            echo '<h4>' . esc_html(get_the_title()) . '</h4>';
                if ($program_id) {
                    // Try to get preview image (WP creates image preview for PDFs)
                    $preview = wp_get_attachment_image_src($program_id, $atts['size']);
                    if ($preview) {
                        echo '<a href="' . esc_url($program_url) . '" target="_blank"><img src="' . esc_url($preview[0]) . '" style="max-width:100%;height:auto;" /></a>';
                    } else {
                        // Check for generated preview saved in attachment meta
                        $generated = get_post_meta($program_id, '_stagekitwp_pdf_preview', true);
                        if ($generated) {
                            echo '<a href="' . esc_url($program_url) . '" target="_blank"><img src="' . esc_url($generated) . '" style="max-width:100%;height:auto;" /></a>';
                        } else {
                            // No server preview: output a canvas placeholder that will be rendered client-side by PDF.js
                            echo '<div class="stagekitwp-program-preview">';
                            echo '<a href="' . esc_url($program_url) . '" target="_blank">';
                            echo '<canvas class="stagekitwp-pdf-canvas" data-pdf="' . esc_attr($program_url) . '" data-width="300" aria-label="Program preview"></canvas>';
                            echo '</a>';
                            echo '</div>';
                        }
                    }
                } elseif ($program_url) {
                    // No attachment ID (direct URL) — attempt client-side rendering
                    echo '<div class="stagekitwp-program-preview">';
                    echo '<a href="' . esc_url($program_url) . '" target="_blank">';
                    echo '<canvas class="stagekitwp-pdf-canvas" data-pdf="' . esc_attr($program_url) . '" data-width="300" aria-label="Program preview"></canvas>';
                    echo '</a>';
                    echo '</div>';
                } else {
                    echo '<p>No program available</p>';
                }
            echo '</div>';

            $i++;
        }
        echo '<div style="clear:both;"></div>';
        echo '</div>';
        wp_reset_postdata();
    } else {
        // No season provided: group shows by season
        $seasons = get_posts(stagekitwp_programs_query_defaults(array('post_type' => 'season', 'numberposts' => -1)));
        echo '<div class="stagekitwp-programs-by-season">';
        foreach ($seasons as $s) {
            echo '<h3>' . esc_html($s->post_title) . '</h3>';
            $query = new WP_Query(stagekitwp_programs_query_defaults(array('post_type' => 'show', 'posts_per_page' => -1, 'meta_key' => '_stagekitwp_show_season', 'meta_value' => $s->ID)));
            if ($query->have_posts()) {
                echo '<div class="stagekitwp-programs-season-' . esc_attr($s->ID) . '">';
                while ($query->have_posts()) {
                    $query->the_post();
                    $id = get_the_ID();
                    $program_id = get_post_meta($id, '_stagekitwp_show_program', true);
                    $program_url = get_post_meta($id, '_stagekitwp_show_program_url', true);
                    if (!$program_url && $program_id) $program_url = wp_get_attachment_url($program_id);

                    echo '<div class="stagekitwp-program-item" style="width:' . esc_attr(100 / $columns) . '%;float:left;padding:8px;box-sizing:border-box;">';
                    echo '<h4>' . esc_html(get_the_title()) . '</h4>';
                    if ($program_id) {
                        $preview = wp_get_attachment_image_src($program_id, $atts['size']);
                        if ($preview) {
                            echo '<a href="' . esc_url($program_url) . '" target="_blank"><img src="' . esc_url($preview[0]) . '" style="max-width:100%;height:auto;" /></a>';
                        } else {
                            $generated = get_post_meta($program_id, '_stagekitwp_pdf_preview', true);
                            if ($generated) {
                                echo '<a href="' . esc_url($program_url) . '" target="_blank"><img src="' . esc_url($generated) . '" style="max-width:100%;height:auto;" /></a>';
                            } else {
                                // No server preview: render client-side canvas
                                echo '<a href="' . esc_url($program_url) . '" target="_blank">';
                                echo '<canvas class="stagekitwp-pdf-canvas" data-pdf="' . esc_attr($program_url) . '" data-width="300" aria-label="Program preview"></canvas>';
                                echo '</a>';
                            }
                        }
                    } elseif ($program_url) {
                        // No attachment ID but URL present: attempt client-side rendering
                        echo '<a href="' . esc_url($program_url) . '" target="_blank">';
                        echo '<canvas class="stagekitwp-pdf-canvas" data-pdf="' . esc_attr($program_url) . '" data-width="300" aria-label="Program preview"></canvas>';
                        echo '</a>';
                    } else {
                        echo '<p>No program available</p>';
                    }
                    echo '</div>';
                }
                echo '<div style="clear:both;"></div>';
                echo '</div>';
            } else {
                echo '<p>No programs for this season.</p>';
            }
            wp_reset_postdata();
        }
        echo '</div>';
    }

    return ob_get_clean();
}
add_shortcode('stagekitwp_programs', 'stagekitwp_programs_shortcode');

?>
