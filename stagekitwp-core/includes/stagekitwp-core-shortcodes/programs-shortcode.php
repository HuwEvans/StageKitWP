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
 * Extract a pixel count from a CSS width value (e.g. "220px" -> 220) for the
 * PDF.js canvas data-width attribute. Falls back when no digits are found.
 */
function stagekitwp_program_preview_canvas_width($css_value, $fallback = 300) {
    if (preg_match('/([0-9]+)/', (string) $css_value, $m)) {
        return max(80, intval($m[1]));
    }
    return $fallback;
}

/**
 * Render a program preview: attachment image size, generated PDF thumbnail,
 * or (as a last resort) a client-side PDF.js canvas. Works for image attachments
 * and PDFs alike, and for direct URLs with no attachment ID.
 *
 * @param bool $wrap_link When true (default) the media is wrapped in its own <a>.
 *                        Pass false when the caller already wraps the whole card in a link.
 */
function stagekitwp_render_program_preview_html($program_id, $program_url, $size = 'medium', $canvas_width = 300, $wrap_link = true) {
    if (!$program_url) {
        return '';
    }

    $program_id = absint($program_id);
    if (!$program_id) {
        $program_id = attachment_url_to_postid($program_url);
    }

    $media = '';
    if ($program_id) {
        $preview = wp_get_attachment_image_src($program_id, $size);
        if ($preview) {
            $media = '<img class="stagekitwp-program-preview-img" src="' . esc_url($preview[0]) . '" alt="Program preview" />';
        } else {
            $generated = get_post_meta($program_id, '_stagekitwp_pdf_preview', true);
            if ($generated) {
                $media = '<img class="stagekitwp-program-preview-img" src="' . esc_url($generated) . '" alt="Program preview" />';
            }
        }
    }
    if ($media === '') {
        // No server-generated preview available: render the PDF's first page client-side.
        $media = '<canvas class="stagekitwp-pdf-canvas" data-pdf="' . esc_attr($program_url) . '" data-width="' . esc_attr($canvas_width) . '" aria-label="Program preview"></canvas>';
    }

    if (!$wrap_link) {
        return $media;
    }
    return '<a href="' . esc_url($program_url) . '" target="_blank" rel="noopener noreferrer" class="stagekitwp-program-link">' . $media . '</a>';
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

    stagekitwp_enqueue_pdf_preview_assets();

    $program_id = absint(get_post_meta($selected_show_id, '_stagekitwp_show_program', true));
    $link_text = !empty($atts['link_text']) ? $atts['link_text'] : 'View Program';
    $target = ($atts['open_new'] === 'false' || $atts['open_new'] === '0') ? '' : ' target="_blank" rel="noopener noreferrer"';
    $show_title = get_the_title($selected_show_id);

    // Not constrained by a grid column, so give it a sensible default width when none is set.
    $preview_width = trim((string) ($atts['preview_width'] ?? ''));
    if ($preview_width === '') { $preview_width = '260px'; }
    $canvas_width = stagekitwp_program_preview_canvas_width($preview_width);

    $align = strtolower(trim((string) ($atts['align'] ?? '')));
    if (!in_array($align, array('left', 'center', 'right'), true)) { $align = 'left'; }

    $media_html = stagekitwp_render_program_preview_html($program_id, $program_url, $atts['size'] ?? 'medium', $canvas_width, false);

    // Whole card (image + title + CTA) is a single link so it behaves as one clickable unit.
    $card = '<a class="stagekitwp-programs-current-link-card" href="' . esc_url($program_url) . '"' . $target . ' style="max-width:' . esc_attr($preview_width) . ';">';
    $card .= '<span class="stagekitwp-program-preview">' . $media_html . '</span>';
    if ($show_title) {
        $card .= '<span class="stagekitwp-programs-current-link-show">' . esc_html($show_title) . '</span>';
    }
    $card .= '<span class="stagekitwp-programs-current-link">' . esc_html($link_text) . '</span>';
    $card .= '</a>';

    return '<div class="stagekitwp-programs-current-link-wrap stagekitwp-programs-current-link-align-' . esc_attr($align) . '">' . $card . '</div>';
}

function stagekitwp_programs_shortcode($atts) {
    $atts = shortcode_atts(array(
        'layout' => 'gallery',
        'season' => '',
        'columns' => 3,
        'size' => 'medium',
        'preview_width' => '',
        'align' => 'left',
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

        $shows = get_posts(stagekitwp_programs_query_defaults(array('post_type' => 'show', 'posts_per_page' => -1, 'meta_query' => $meta_query)));
        $shows = stagekitwp_sort_shows_by_slot($shows);
        echo '<div class="stagekitwp-programs-gallery stagekitwp-programs-season-' . esc_attr($season_id) . '">';
        foreach ($shows as $show) {
            $id = $show->ID;
            $program_id = get_post_meta($id, '_stagekitwp_show_program', true);
            $program_url = get_post_meta($id, '_stagekitwp_show_program_url', true);
            if (!$program_url && $program_id) $program_url = wp_get_attachment_url($program_id);

            echo '<div class="stagekitwp-program-item" style="width:' . esc_attr(100 / $columns) . '%;float:left;padding:8px;box-sizing:border-box;">';
            echo '<h4>' . esc_html(get_the_title($id)) . '</h4>';
                if ($program_url) {
                    $preview_style = $atts['preview_width'] !== '' ? ' style="max-width:' . esc_attr($atts['preview_width']) . ';margin-left:auto;margin-right:auto;"' : '';
                    $canvas_width = stagekitwp_program_preview_canvas_width($atts['preview_width']);
                    echo '<div class="stagekitwp-program-preview"' . $preview_style . '>' . stagekitwp_render_program_preview_html($program_id, $program_url, $atts['size'], $canvas_width) . '</div>';
                } else {
                    echo '<p>No program available</p>';
                }
            echo '</div>';
        }
        echo '<div style="clear:both;"></div>';
        echo '</div>';
    } else {
        // No season provided: group shows by season, most current season first
        $season_ids = get_posts(stagekitwp_programs_query_defaults(array('post_type' => 'season', 'numberposts' => -1, 'fields' => 'ids')));
        $seasons = array();
        foreach ($season_ids as $sid) {
            $start_raw = get_post_meta($sid, '_stagekitwp_season_start_date', true);
            $seasons[] = array('id' => $sid, 'title' => get_the_title($sid), 'start_ts' => $start_raw ? strtotime($start_raw) : 0);
        }
        usort($seasons, function($a, $b) { return $b['start_ts'] <=> $a['start_ts']; });

        echo '<div class="stagekitwp-programs-by-season">';
        foreach ($seasons as $season) {
            $sid = $season['id'];
            echo '<h3>' . esc_html($season['title']) . '</h3>';
            $shows = get_posts(stagekitwp_programs_query_defaults(array(
                'post_type' => 'show',
                'posts_per_page' => -1,
                'meta_query' => array(array('key' => '_stagekitwp_show_season', 'value' => $sid, 'compare' => '=')),
            )));
            if ($shows) {
                $shows = stagekitwp_sort_shows_by_slot($shows);
                echo '<div class="stagekitwp-programs-season-' . esc_attr($sid) . '">';
                foreach ($shows as $show) {
                    $id = $show->ID;
                    $program_id = get_post_meta($id, '_stagekitwp_show_program', true);
                    $program_url = get_post_meta($id, '_stagekitwp_show_program_url', true);
                    if (!$program_url && $program_id) $program_url = wp_get_attachment_url($program_id);

                    echo '<div class="stagekitwp-program-item" style="width:' . esc_attr(100 / $columns) . '%;float:left;padding:8px;box-sizing:border-box;">';
                    echo '<h4>' . esc_html(get_the_title($id)) . '</h4>';
                    if ($program_url) {
                        $preview_style = $atts['preview_width'] !== '' ? ' style="max-width:' . esc_attr($atts['preview_width']) . ';margin-left:auto;margin-right:auto;"' : '';
                        $canvas_width = stagekitwp_program_preview_canvas_width($atts['preview_width']);
                        echo '<div class="stagekitwp-program-preview"' . $preview_style . '>' . stagekitwp_render_program_preview_html($program_id, $program_url, $atts['size'], $canvas_width) . '</div>';
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
        }
        echo '</div>';
    }

    return ob_get_clean();
}
add_shortcode('stagekitwp_programs', 'stagekitwp_programs_shortcode');

?>
