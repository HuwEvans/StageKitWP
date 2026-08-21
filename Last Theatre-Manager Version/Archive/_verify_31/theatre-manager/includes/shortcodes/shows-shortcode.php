<?php
/**
 * Shortcode to display show entries.
 * Usage: [tm_shows exclude="genre,director"]
 * By default, all fields are shown. Use 'exclude' to hide specific fields.
 */

function tm_shortcode_shows($atts) {
    $atts = shortcode_atts(array(
        'exclude' => '',
        'season_id' => 0,
        // which: all | current | next | current_and_next
        'which' => 'all'
    ), $atts);

    $exclude = array_map('trim', explode(',', $atts['exclude']));
    
    // Get seasons and sort them by date
    $seasons = array();
    $season_shows = array();
    $unassigned_shows = array();
    
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

        // Filter seasons based on 'which' parameter
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

            $selected = array();
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
            } elseif ($which === 'current_and_next') {
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

    // Get all shows and organize them by season and slot
    $slot_order = array('Fall' => 0, 'Winter' => 1, 'Spring' => 2);
    $shows = get_posts(array('post_type' => 'show', 'posts_per_page' => -1));
    
    foreach ($shows as $show) {
        $season_id = get_post_meta($show->ID, '_tm_show_season', true);
        if ($season_id) {
            if (!isset($season_shows[$season_id])) {
                $season_shows[$season_id] = array();
            }
            $slot = get_post_meta($show->ID, '_tm_show_time_slot', true);
            $slot_index = isset($slot_order[$slot]) ? $slot_order[$slot] : 999;
            if (!isset($season_shows[$season_id][$slot_index])) {
                $season_shows[$season_id][$slot_index] = array();
            }
            $season_shows[$season_id][$slot_index][] = $show;
        } else {
            $unassigned_shows[] = $show;
        }
    }

    ob_start();
	$bg_color = esc_attr(get_option("tm_show_bg_color"));
	$text_color = esc_attr(get_option("tm_show_text_color"));
	$border_width = esc_attr(get_option("tm_show_border_width"));
	$rounded = get_option("tm_show_rounded") ? esc_attr(get_option("tm_show_radius")) : '0';
	$shadow = get_option("tm_show_shadow") ? '0 0 10px rgba(0,0,0,0.3)' : 'none';
	$style = "background-color: $bg_color; color: $text_color; border-width: {$border_width}px; border-style: solid; border-radius: {$rounded}px; box-shadow: $shadow;";

    echo '<div class="tm-shortcode-wrapper tm-show-wrapper" style="' . $style . '">';

    // Display shows by season and slot
    foreach ($seasons as $season) {
        echo '<div class="tm-season-section">';
        echo '<h2 class="tm-season-title" style="color: ' . $text_color . ';">' . esc_html($season->post_title) . '</h2>';
        
        if (isset($season_shows[$season->ID])) {
            ksort($season_shows[$season->ID]); // Sort by slot index
            foreach ($season_shows[$season->ID] as $slot_index => $shows_in_slot) {
                $slot_name = array_search($slot_index, $slot_order);
                if ($slot_name) {
                    echo '<h3 class="tm-slot-title" style="color: ' . $text_color . ';">' . esc_html($slot_name) . ' Show</h3>';
                }
                
                foreach ($shows_in_slot as $show) {
                    setup_postdata($show);
                    $id = $show->ID;
                    echo '<div class="tm-entry tm-shows-entry">';
        
        // Show image first if available
        if (!in_array('sm_image', $exclude)) {
            $value = get_post_meta($id, '_tm_show_sm_image', true);
            if ($value) {
                $img_url = tm_get_image_url($value);
                if ($img_url) {
                    echo '<div class="tm-show-image"><img src="' . esc_url($img_url) . '" alt="Show image" /></div>';
                }
            }
        }

        // Show title with proper color
        echo '<h3 class="tm-show-title" style="color: ' . $text_color . ';">' . esc_html(get_the_title()) . '</h3>';

        $fields = ['author', 'sub_authors', 'synopsis', 'genre', 'director', 'associate_director', 'time_slot', 'show_dates'];
        foreach ($fields as $field) {
            if (!in_array($field, $exclude)) {
                $value = get_post_meta($id, '_tm_show_' . $field, true);
                if ($value) echo '<p><strong>' . ucfirst(str_replace('_', ' ', $field)) . ':</strong> ' . esc_html($value) . '</p>';
            }
        }

        if (!in_array('season', $exclude)) {
            $season_id = get_post_meta($id, '_tm_show_season', true);
            if ($season_id) echo '<p><strong>Season:</strong> ' . esc_html(get_the_title($season_id)) . '</p>';
        }

        // Program preview section
        if (!in_array('program', $exclude)) {
            echo '<div class="tm-program-section">';
            echo '<h4 class="tm-program-header" style="color: ' . $text_color . ';">Program Preview</h4>';
            
            $program_id = get_post_meta($id, '_tm_show_program', true);
            $program_url = get_post_meta($id, '_tm_show_program_url', true);
            if (!$program_url && $program_id) $program_url = wp_get_attachment_url($program_id);

            if ($program_id) {
                $preview = wp_get_attachment_image_src($program_id, 'medium');
                if ($preview) {
                    echo '<div class="tm-program-preview"><a href="' . esc_url($program_url) . '" target="_blank" class="tm-program-link"><img class="tm-program-preview-img" src="' . esc_url($preview[0]) . '" alt="Program preview" /></a></div>';
                } else {
                    $generated = get_post_meta($program_id, '_tm_pdf_preview', true);
                    if ($generated) {
                        echo '<div class="tm-program-preview"><a href="' . esc_url($program_url) . '" target="_blank" class="tm-program-link"><img class="tm-program-preview-img" src="' . esc_url($generated) . '" alt="Program preview" /></a></div>';
                    } else {
                        // No server preview: render client-side canvas via PDF.js
                        echo '<div class="tm-program-preview"><a href="' . esc_url($program_url) . '" target="_blank" class="tm-program-link">';
                        echo '<canvas class="tm-pdf-canvas" data-pdf="' . esc_attr($program_url) . '" data-width="200" aria-label="Program preview"></canvas>';
                        echo '</a></div>';
                    }
                }
                } elseif ($program_url) {
                    // No attachment ID (direct URL) — attempt client-side rendering
                    echo '<div class="tm-program-preview"><a href="' . esc_url($program_url) . '" target="_blank" class="tm-program-link">';
                    echo '<canvas class="tm-pdf-canvas" data-pdf="' . esc_attr($program_url) . '" data-width="200" aria-label="Program preview"></canvas>';
                    echo '</a></div>';
                }
            echo '</div>'; // Close tm-program-section
        }
                    echo '</div>'; // Close tm-shows-entry
                }
            }
        }
        echo '</div>'; // Close tm-season-section
    }
    
    // Display unassigned shows at the end
    if (!empty($unassigned_shows)) {
        echo '<div class="tm-season-section">';
        echo '<h2 class="tm-season-title" style="color: ' . $text_color . ';">Other Shows</h2>';
        
        foreach ($unassigned_shows as $show) {
            setup_postdata($show);
            $id = $show->ID;
            echo '<div class="tm-entry tm-shows-entry">';
            
            // Show content rendering (same as above)
            if (!in_array('sm_image', $exclude)) {
                $value = get_post_meta($id, '_tm_show_sm_image', true);
                if ($value) {
                    $img_url = tm_get_image_url($value);
                    if ($img_url) {
                        echo '<div class="tm-show-image"><img src="' . esc_url($img_url) . '" alt="Show image" /></div>';
                    }
                }
            }
            
            echo '<h3 class="tm-show-title" style="color: ' . $text_color . ';">' . esc_html($show->post_title) . '</h3>';
            
            $fields = ['author', 'sub_authors', 'synopsis', 'genre', 'director', 'associate_director', 'time_slot', 'show_dates'];
            foreach ($fields as $field) {
                if (!in_array($field, $exclude)) {
                    $value = get_post_meta($id, '_tm_show_' . $field, true);
                    if ($value) echo '<p><strong>' . ucfirst(str_replace('_', ' ', $field)) . ':</strong> ' . esc_html($value) . '</p>';
                }
            }
            
            // Program preview section for unassigned shows
            if (!in_array('program', $exclude)) {
                echo '<div class="tm-program-section">';
                echo '<h4 class="tm-program-header" style="color: ' . $text_color . ';">Program Preview</h4>';
                
                $program_id = get_post_meta($id, '_tm_show_program', true);
                $program_url = get_post_meta($id, '_tm_show_program_url', true);
                if (!$program_url && $program_id) $program_url = wp_get_attachment_url($program_id);
                
                if ($program_id) {
                    $preview = wp_get_attachment_image_src($program_id, 'medium');
                    if ($preview) {
                        echo '<div class="tm-program-preview"><a href="' . esc_url($program_url) . '" target="_blank" class="tm-program-link"><img class="tm-program-preview-img" src="' . esc_url($preview[0]) . '" alt="Program preview" /></a></div>';
                    } else {
                        $generated = get_post_meta($program_id, '_tm_pdf_preview', true);
                        if ($generated) {
                            echo '<div class="tm-program-preview"><a href="' . esc_url($program_url) . '" target="_blank" class="tm-program-link"><img class="tm-program-preview-img" src="' . esc_url($generated) . '" alt="Program preview" /></a></div>';
                        } else {
                            echo '<div class="tm-program-preview"><a href="' . esc_url($program_url) . '" target="_blank" class="tm-program-link">';
                            echo '<canvas class="tm-pdf-canvas" data-pdf="' . esc_attr($program_url) . '" data-width="200" aria-label="Program preview"></canvas>';
                            echo '</a></div>';
                        }
                    }
                } elseif ($program_url) {
                    echo '<div class="tm-program-preview"><a href="' . esc_url($program_url) . '" target="_blank" class="tm-program-link">';
                    echo '<canvas class="tm-pdf-canvas" data-pdf="' . esc_attr($program_url) . '" data-width="200" aria-label="Program preview"></canvas>';
                    echo '</a></div>';
                }
                echo '</div>'; // Close tm-program-section
            }
            
            echo '</div>'; // Close tm-shows-entry
        }
        echo '</div>'; // Close tm-season-section for unassigned shows
    }
    
    echo '</div>'; // Close tm-show-wrapper
    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode('tm_shows', 'tm_shortcode_shows');
?>
