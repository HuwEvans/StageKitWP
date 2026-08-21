<?php
function tm_season_cast_shortcode($atts) {

  //  $bg_color = get_option('tm_show_bg_color', '#ffffff');
    $text_color = function_exists('tm_sanitize_css_color') ? tm_sanitize_css_color(get_option('tm_show_text_color', '#000000'), '#000000') : '#000000';
    $border_color = function_exists('tm_sanitize_css_color') ? tm_sanitize_css_color(get_option('tm_show_border_color', '#000000'), '#000000') : '#000000';
    $border_width = 0;
    $border_radius = absint(get_option('tm_show_radius', '20'));
    $shadow_enabled = (bool) get_option('tm_show_shadow');
    $base_font = get_option('tm_show_base_font', 'Arial, sans-serif');

    $style = 'color:' . esc_attr($text_color) . '; font-family:' . esc_attr($base_font) . '; border-width:' . esc_attr($border_width) . 'px; border-color:' . esc_attr($border_color) . '; border-radius:' . esc_attr($border_radius) . 'px; border-style:solid;';
    if ($shadow_enabled) {
        $style .= ' box-shadow: 5px 10px;';
    }

    $atts = shortcode_atts([
        'season_id' => 0,
        'show_cast_images' => 'true',
        'cast_layout' => 'grid',
        // which: all | current | next | current_and_next
        'which' => 'all'
    ], $atts);

    // Determine seasons to render. If season_id provided, use that; otherwise use 'which' selection
    if (!empty($atts['season_id'])) {
        $seasons = get_posts([
            'post_type' => 'season',
            'numberposts' => -1,
            'p' => intval($atts['season_id'])
        ]);
    } else {
        // Load all seasons and sort by start date
        $seasons = get_posts(['post_type' => 'season', 'numberposts' => -1]);
        foreach ($seasons as $s) {
            $start_raw = get_post_meta($s->ID, '_tm_season_start_date', true);
            $s->tm_start_ts = $start_raw ? strtotime($start_raw) : 0;
            $end_raw = get_post_meta($s->ID, '_tm_season_end_date', true);
            $s->tm_end_ts = $end_raw ? strtotime($end_raw) : 0;
        }
        usort($seasons, function($a, $b) {
            return $a->tm_start_ts <=> $b->tm_start_ts;
        });

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

    $slot_order = ['Fall', 'Winter', 'Spring'];
    $output = '<div style="'.$style. '" class="tm-season-cast">';

    // For each selected season, fetch shows assigned to that season and render them grouped by time slot
    if (empty($seasons)) {
        $output .= '<p><em>No seasons found.</em></p>';
    } else {
        foreach ($seasons as $season) {
            $output .= '<h2 class="tm-season-title">' . esc_html($season->post_title) . '</h2>';

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

            foreach ($slot_order as $slot) {
                if (!isset($grouped[$slot])) continue;

                $output .= '<h3>' . esc_html($slot) . ' Show</h3>';
                foreach ($grouped[$slot] as $show) {
            $img = get_post_meta($show->ID, '_tm_show_sm_image', true);
            $author = get_post_meta($show->ID, '_tm_show_author', true);
            $sub_authors = get_post_meta($show->ID, '_tm_show_sub_authors', true);
            $director = get_post_meta($show->ID, '_tm_show_director', true);
            $associate_director = get_post_meta($show->ID, '_tm_show_associate_director', true);
            $synopsis = get_post_meta($show->ID, '_tm_show_synopsis', true);
            $show_dates = get_post_meta($show->ID, '_tm_show_show_dates', true);

            $output .= '<div class="tm-show-card">';
            if ($img) {
                $output .= '<img src="' . esc_url($img) . '" alt="' . esc_attr($show->post_title) . '" class="tm-show-image">';
            }
            $output .= '<h3>' . esc_html($show->post_title) . '</h3>';

            if ($author) $output .= '<p><strong>Author:</strong> ' . esc_html($author) . '</p>';
            if ($sub_authors) $output .= '<p><strong>Sub-author(s):</strong> ' . esc_html($sub_authors) . '</p>';
            if ($director) $output .= '<p><strong>Director:</strong> ' . esc_html($director) . '</p>';
            if ($associate_director) $output .= '<p><strong>Associate Director:</strong> ' . esc_html($associate_director) . '</p>';
            if ($synopsis) $output .= '<p class=tm_show_synopsis><strong>Synopsis:</strong> ' . esc_html($synopsis) . '</p>';
            if ($show_dates) $output .= '<p><strong>Show Dates:</strong> ' . esc_html($show_dates) . '</p>';

            $cast_members = get_posts([
                'post_type' => 'cast',
                'numberposts' => -1,
                'meta_key' => '_tm_cast_show',
                'meta_value' => $show->ID
            ]);

            if ($cast_members) {
                $layout_class = $atts['cast_layout'] === 'list' ? 'tm-cast-list' : 'tm-cast-grid';
                $output .= '<div class="' . esc_attr($layout_class) . '">';

                if ($atts['cast_layout'] === 'list') {
                    $output .= '<table class="tm-cast-table">';
                    $output .= '<thead><tr>';
                    if ($atts['show_cast_images'] === 'true') {
                        $output .= '<th></th>';
                    }
                    $output .= '<th>Character</th><th>Actor</th>';
                    $output .= '</tr></thead><tbody>';
                }

                foreach ($cast_members as $cast) {
                    $character = get_post_meta($cast->ID, '_tm_cast_character_name', true);
                    $actor = get_post_meta($cast->ID, '_tm_cast_actor_name', true);
                    $picture = get_post_meta($cast->ID, '_tm_cast_picture', true);

                    if ($atts['cast_layout'] === 'list') {
                        $output .= '<tr>';
                        if ($atts['show_cast_images'] === 'true') {
                            $output .= '<td>';
                            if ($picture) {
                                $picture_url = tm_get_image_url($picture);
                                if ($picture_url) {
                                    $output .= '<img src="' . esc_url($picture_url) . '" alt="' . esc_attr($character) . '" style="max-width:50px;">';
                                }
                            }
                            $output .= '</td>';
                        }
                        $output .= '<td>' . esc_html($character) . '</td>';
                        $output .= '<td>' . esc_html($actor) . '</td>';
                        $output .= '</tr>';
                    } else {
                        $output .= '<div class="tm-cast-card">';
                        if ($atts['show_cast_images'] === 'true' && $picture) {
                            $picture_url = tm_get_image_url($picture);
                            if ($picture_url) {
							    $output .= '<img src="' . esc_url($picture_url) . '" alt="' . esc_attr($character) . '" class="tm-cast-image">';
                            }
                        }
                        $output .= '<p><strong>Character:</strong> ' . esc_html($character) . '</p>';
                        $output .= '<p><strong>Actor:</strong> ' . esc_html($actor) . '</p>';
                        $output .= '</div>';
                    }
                }

                if ($atts['cast_layout'] === 'list') {
                    $output .= '</tbody></table>';
                }

                $output .= '</div>';
            } else {
                $output .= '<p><em>Cast: TBD</em></p>';
            }

            // Program preview for this show (placed after the cast list)
            $program_id = get_post_meta($show->ID, '_tm_show_program', true);
            $program_url = get_post_meta($show->ID, '_tm_show_program_url', true);
            if (!$program_url && $program_id) $program_url = wp_get_attachment_url($program_id);

            if (!empty($program_id) || !empty($program_url)) {
                $output .= '<div class="tm-program-section">';
                $output .= '<h4 class="tm-program-header" style="color: ' . $text_color . ';">Program Preview</h4>';
                if ($program_id) {
                    $preview = wp_get_attachment_image_src($program_id, 'medium');
                    if ($preview) {
                        $output .= '<div class="tm-program-preview"><a href="' . esc_url($program_url) . '" target="_blank" class="tm-program-link"><img class="tm-program-preview-img" src="' . esc_url($preview[0]) . '" alt="Program preview" /></a></div>';
                    } else {
                        $generated = get_post_meta($program_id, '_tm_pdf_preview', true);
                        if ($generated) {
                            $output .= '<div class="tm-program-preview"><a href="' . esc_url($program_url) . '" target="_blank" class="tm-program-link"><img class="tm-program-preview-img" src="' . esc_url($generated) . '" alt="Program preview" /></a></div>';
                        } else {
                            $output .= '<div class="tm-program-preview"><a href="' . esc_url($program_url) . '" target="_blank" class="tm-program-link">';
                            $output .= '<canvas class="tm-pdf-canvas" data-pdf="' . esc_attr($program_url) . '" data-width="200" aria-label="Program preview"></canvas>';
                            $output .= '</a></div>';
                        }
                    }
                } elseif ($program_url) {
                    $output .= '<div class="tm-program-preview"><a href="' . esc_url($program_url) . '" target="_blank" class="tm-program-link">';
                    $output .= '<canvas class="tm-pdf-canvas" data-pdf="' . esc_attr($program_url) . '" data-width="200" aria-label="Program preview"></canvas>';
                    $output .= '</a></div>';
                }
                $output .= '</div>'; // close tm-program-section
            }

            $output .= '</div>';
                }
            }
        }
    }

    $output .= '</div>';
    return $output;
}
add_shortcode('tm_season_cast', 'tm_season_cast_shortcode');
?>