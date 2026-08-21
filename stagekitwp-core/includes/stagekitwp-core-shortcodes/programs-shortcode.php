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
 * Resolve the current show ID for stagekitwp_programs current_link layout.
 */
function stagekitwp_programs_resolve_current_show_id() {
    // Reuse the existing helper when available.
    if (function_exists('stagekitwp_get_current_show')) {
        $helper_id = intval(stagekitwp_get_current_show());
        if ($helper_id > 0) {
            return $helper_id;
        }
    }

    // Primary fallback: explicit current season flag used by stagekitwp_tickets.
    $current_seasons = get_posts(stagekitwp_programs_query_defaults(array(
        'post_type' => 'season',
        'posts_per_page' => 1,
        'post_status' => 'publish',
        'fields' => 'ids',
        'meta_query' => array(
            array(
                'key' => '_stagekitwp_season_is_current',
                'value' => 1,
                'compare' => '=',
            ),
        ),
    )));

    if (!empty($current_seasons)) {
        $season_id = intval($current_seasons[0]);
        $season_shows = get_posts(stagekitwp_programs_query_defaults(array(
            'post_type' => 'show',
            'posts_per_page' => 1,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC',
            'fields' => 'ids',
            'meta_query' => array(
                array(
                    'key' => '_stagekitwp_show_season',
                    'value' => $season_id,
                    'compare' => '=',
                ),
            ),
        )));
        if (!empty($season_shows)) {
            return intval($season_shows[0]);
        }
    }

    // Secondary fallback: derive current season from date range, inclusively.
    $now = current_time('timestamp');
    $seasons = get_posts(stagekitwp_programs_query_defaults(array(
        'post_type' => 'season',
        'posts_per_page' => -1,
        'post_status' => 'publish',
    )));
    if (!empty($seasons)) {
        foreach ($seasons as $season) {
            $start_raw = get_post_meta($season->ID, '_stagekitwp_season_start_date', true);
            $end_raw = get_post_meta($season->ID, '_stagekitwp_season_end_date', true);
            $start_ts = $start_raw ? strtotime($start_raw) : 0;
            $end_ts = $end_raw ? (strtotime($end_raw) + 86399) : 0;

            if ($start_ts && $end_ts && $now >= $start_ts && $now <= $end_ts) {
                $season_shows = get_posts(stagekitwp_programs_query_defaults(array(
                    'post_type' => 'show',
                    'posts_per_page' => 1,
                    'post_status' => 'publish',
                    'orderby' => 'date',
                    'order' => 'DESC',
                    'fields' => 'ids',
                    'meta_query' => array(
                        array(
                            'key' => '_stagekitwp_show_season',
                            'value' => intval($season->ID),
                            'compare' => '=',
                        ),
                    ),
                )));
                if (!empty($season_shows)) {
                    return intval($season_shows[0]);
                }
            }
        }
    }

    // Last fallback: most recent published show.
    $latest = get_posts(stagekitwp_programs_query_defaults(array(
        'post_type' => 'show',
        'posts_per_page' => 1,
        'post_status' => 'publish',
        'orderby' => 'date',
        'order' => 'DESC',
        'fields' => 'ids',
    )));

    return !empty($latest) ? intval($latest[0]) : 0;
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
    $current_show_id = stagekitwp_programs_resolve_current_show_id();
    $selected_show_id = $current_show_id;
    $program_url = $selected_show_id ? stagekitwp_get_program_url_for_show($selected_show_id) : '';

    if (!$program_url) {
        $selected_show_id = stagekitwp_get_previous_program_show($current_show_id);
        $program_url = $selected_show_id ? stagekitwp_get_program_url_for_show($selected_show_id) : '';
    }

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
