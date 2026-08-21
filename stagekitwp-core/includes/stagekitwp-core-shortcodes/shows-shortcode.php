<?php
/**
 * Shortcode to display show entries.
 * Usage: [stagekitwp_shows exclude="genre,director"]
 * By default, all fields are shown. Use 'exclude' to hide specific fields.
 */

function stagekitwp_shortcode_shows($atts) {
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
            $start_raw = get_post_meta($s->ID, '_stagekitwp_season_start_date', true);
            $s->stagekitwp_start_ts = $start_raw ? strtotime($start_raw) : 0;
            $end_raw = get_post_meta($s->ID, '_stagekitwp_season_end_date', true);
            $s->stagekitwp_end_ts = $end_raw ? strtotime($end_raw) : 0;
        }
        usort($seasons, function($a, $b) {
            return $a->stagekitwp_start_ts <=> $b->stagekitwp_start_ts;
        });

        // Filter seasons based on 'which' parameter
        $which = strtolower(trim($atts['which']));
        if ($which !== 'all') {
            $today_ts = strtotime(date('Y-m-d'));
            $current_index = null;
            foreach ($seasons as $i => $s) {
                if (!empty($s->stagekitwp_start_ts) && !empty($s->stagekitwp_end_ts)) {
                    if ($today_ts > $s->stagekitwp_start_ts && $today_ts < $s->stagekitwp_end_ts) {
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
                        if (!empty($s->stagekitwp_start_ts) && $s->stagekitwp_start_ts > $today_ts) {
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
                        if (!empty($s->stagekitwp_start_ts) && $s->stagekitwp_start_ts > $today_ts) {
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

    // Get all shows and organize them by season and slot (Fall=0, Winter=1, Spring=2 for July-June season year)
    $slot_order = stagekitwp_show_slot_order();
    $shows = get_posts(array('post_type' => 'show', 'posts_per_page' => -1));
    
    foreach ($shows as $show) {
        $season_id = get_post_meta($show->ID, '_stagekitwp_show_season', true);
        if ($season_id) {
            if (!isset($season_shows[$season_id])) {
                $season_shows[$season_id] = array();
            }
            $slot = get_post_meta($show->ID, '_stagekitwp_show_time_slot', true);
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
	$bg_color     = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_show_bg_color', '' ), '#ffffff' );
	$text_color   = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_show_text_color', '' ), '#000000' );
	$border_color = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_show_border_color', '' ), '#000000' );
	$border_width = absint( get_option( 'stagekitwp_show_border_width', '0' ) );
	$rounded      = get_option( 'stagekitwp_show_rounded' ) ? absint( get_option( 'stagekitwp_show_radius', '0' ) ) : 0;
	$shadow       = get_option( 'stagekitwp_show_shadow' ) ? '0 0 10px rgba(0,0,0,0.3)' : 'none';
	$base_font    = sanitize_text_field( get_option( 'stagekitwp_show_base_font', 'Arial, sans-serif' ) );
	$style = "background-color: {$bg_color}; color: {$text_color}; font-family: {$base_font}; border: {$border_width}px solid {$border_color}; border-radius: {$rounded}px; box-shadow: {$shadow};";

    echo '<div class="stagekitwp-shortcode-wrapper stagekitwp-show-wrapper" style="' . $style . '">';

    // Display shows by season and slot
    foreach ($seasons as $season) {
        echo '<div class="stagekitwp-season-section">';
        echo '<h2 class="stagekitwp-season-title" style="color: ' . $text_color . ';">' . esc_html($season->post_title) . '</h2>';
        
        if (isset($season_shows[$season->ID])) {
            ksort($season_shows[$season->ID]); // Sort by slot index (Fall=0, Winter=1, Spring=2, unknown=999)
            foreach ($season_shows[$season->ID] as $slot_index => $shows_in_slot) {
                // array_search returns false for index 999 (no slot assigned); use strict comparison
                $slot_name = array_search($slot_index, $slot_order, true);
                if ( $slot_name !== false ) {
                    echo '<h3 class="stagekitwp-slot-title" style="color: ' . $text_color . ';">' . esc_html($slot_name) . ' Show</h3>';
                }
                
                foreach ($shows_in_slot as $show) {
                    setup_postdata($show);
                    $id = $show->ID;
                    echo '<div class="stagekitwp-entry stagekitwp-shows-entry">';
        
        // Show image first if available
        $show_url = stagekitwp_show_page_url( $id );
        if (!in_array('sm_image', $exclude)) {
            $value = get_post_meta($id, '_stagekitwp_show_sm_image', true);
            if ($value) {
                $img_url = stagekitwp_get_image_url($value);
                if ($img_url) {
                    $img_tag = '<img src="' . esc_url($img_url) . '" alt="' . esc_attr(get_the_title()) . '" />';
                    echo '<div class="stagekitwp-show-image">';
                    echo $show_url ? '<a href="' . esc_url($show_url) . '">' . $img_tag . '</a>' : $img_tag;
                    echo '</div>';
                }
            }
        }

        // Show title with proper color
        $title_text = esc_html(get_the_title());
        echo '<h3 class="stagekitwp-show-title" style="color: ' . $text_color . ';">';
        echo $show_url ? '<a href="' . esc_url($show_url) . '" style="color:inherit;text-decoration:none;">' . $title_text . '</a>' : $title_text;
        echo '</h3>';

        $fields = ['author', 'sub_authors', 'synopsis', 'genre', 'director', 'associate_director', 'time_slot', 'show_dates'];
        foreach ($fields as $field) {
            if (!in_array($field, $exclude)) {
                $value = get_post_meta($id, '_stagekitwp_show_' . $field, true);
                if ($value) echo '<p><strong>' . ucfirst(str_replace('_', ' ', $field)) . ':</strong> ' . esc_html($value) . '</p>';
            }
        }

        if (!in_array('season', $exclude)) {
            $season_id = get_post_meta($id, '_stagekitwp_show_season', true);
            if ($season_id) echo '<p><strong>Season:</strong> ' . esc_html(get_the_title($season_id)) . '</p>';
        }

        // Program preview section
        if (!in_array('program', $exclude)) {
            echo '<div class="stagekitwp-program-section">';
            echo '<h4 class="stagekitwp-program-header" style="color: ' . $text_color . ';">Program Preview</h4>';
            
            $program_id = get_post_meta($id, '_stagekitwp_show_program', true);
            $program_url = get_post_meta($id, '_stagekitwp_show_program_url', true);
            if (!$program_url && $program_id) $program_url = wp_get_attachment_url($program_id);

            if ($program_id) {
                $preview = wp_get_attachment_image_src($program_id, 'medium');
                if ($preview) {
                    echo '<div class="stagekitwp-program-preview"><a href="' . esc_url($program_url) . '" target="_blank" class="stagekitwp-program-link"><img class="stagekitwp-program-preview-img" src="' . esc_url($preview[0]) . '" alt="Program preview" /></a></div>';
                } else {
                    $generated = get_post_meta($program_id, '_stagekitwp_pdf_preview', true);
                    if ($generated) {
                        echo '<div class="stagekitwp-program-preview"><a href="' . esc_url($program_url) . '" target="_blank" class="stagekitwp-program-link"><img class="stagekitwp-program-preview-img" src="' . esc_url($generated) . '" alt="Program preview" /></a></div>';
                    } else {
                        // No server preview: render client-side canvas via PDF.js
                        echo '<div class="stagekitwp-program-preview"><a href="' . esc_url($program_url) . '" target="_blank" class="stagekitwp-program-link">';
                        echo '<canvas class="stagekitwp-pdf-canvas" data-pdf="' . esc_attr($program_url) . '" data-width="200" aria-label="Program preview"></canvas>';
                        echo '</a></div>';
                    }
                }
                } elseif ($program_url) {
                    // No attachment ID (direct URL) — attempt client-side rendering
                    echo '<div class="stagekitwp-program-preview"><a href="' . esc_url($program_url) . '" target="_blank" class="stagekitwp-program-link">';
                    echo '<canvas class="stagekitwp-pdf-canvas" data-pdf="' . esc_attr($program_url) . '" data-width="200" aria-label="Program preview"></canvas>';
                    echo '</a></div>';
                }
            echo '</div>'; // Close stagekitwp-program-section
        }
                    echo '</div>'; // Close stagekitwp-shows-entry
                }
            }
        }
        echo '</div>'; // Close stagekitwp-season-section
    }
    
    // Display unassigned shows at the end
    if (!empty($unassigned_shows)) {
        echo '<div class="stagekitwp-season-section">';
        echo '<h2 class="stagekitwp-season-title" style="color: ' . $text_color . ';">Other Shows</h2>';
        
        foreach ($unassigned_shows as $show) {
            setup_postdata($show);
            $id = $show->ID;
            echo '<div class="stagekitwp-entry stagekitwp-shows-entry">';
            
            // Show content rendering (same as above)
            $show_url = stagekitwp_show_page_url( $id );
            if (!in_array('sm_image', $exclude)) {
                $value = get_post_meta($id, '_stagekitwp_show_sm_image', true);
                if ($value) {
                    $img_url = stagekitwp_get_image_url($value);
                    if ($img_url) {
                        $img_tag = '<img src="' . esc_url($img_url) . '" alt="' . esc_attr($show->post_title) . '" />';
                        echo '<div class="stagekitwp-show-image">';
                        echo $show_url ? '<a href="' . esc_url($show_url) . '">' . $img_tag . '</a>' : $img_tag;
                        echo '</div>';
                    }
                }
            }

            $title_text = esc_html($show->post_title);
            echo '<h3 class="stagekitwp-show-title" style="color: ' . $text_color . ';">';
            echo $show_url ? '<a href="' . esc_url($show_url) . '" style="color:inherit;text-decoration:none;">' . $title_text . '</a>' : $title_text;
            echo '</h3>';
            
            $fields = ['author', 'sub_authors', 'synopsis', 'genre', 'director', 'associate_director', 'time_slot', 'show_dates'];
            foreach ($fields as $field) {
                if (!in_array($field, $exclude)) {
                    $value = get_post_meta($id, '_stagekitwp_show_' . $field, true);
                    if ($value) echo '<p><strong>' . ucfirst(str_replace('_', ' ', $field)) . ':</strong> ' . esc_html($value) . '</p>';
                }
            }
            
            // Program preview section for unassigned shows
            if (!in_array('program', $exclude)) {
                echo '<div class="stagekitwp-program-section">';
                echo '<h4 class="stagekitwp-program-header" style="color: ' . $text_color . ';">Program Preview</h4>';
                
                $program_id = get_post_meta($id, '_stagekitwp_show_program', true);
                $program_url = get_post_meta($id, '_stagekitwp_show_program_url', true);
                if (!$program_url && $program_id) $program_url = wp_get_attachment_url($program_id);
                
                if ($program_id) {
                    $preview = wp_get_attachment_image_src($program_id, 'medium');
                    if ($preview) {
                        echo '<div class="stagekitwp-program-preview"><a href="' . esc_url($program_url) . '" target="_blank" class="stagekitwp-program-link"><img class="stagekitwp-program-preview-img" src="' . esc_url($preview[0]) . '" alt="Program preview" /></a></div>';
                    } else {
                        $generated = get_post_meta($program_id, '_stagekitwp_pdf_preview', true);
                        if ($generated) {
                            echo '<div class="stagekitwp-program-preview"><a href="' . esc_url($program_url) . '" target="_blank" class="stagekitwp-program-link"><img class="stagekitwp-program-preview-img" src="' . esc_url($generated) . '" alt="Program preview" /></a></div>';
                        } else {
                            echo '<div class="stagekitwp-program-preview"><a href="' . esc_url($program_url) . '" target="_blank" class="stagekitwp-program-link">';
                            echo '<canvas class="stagekitwp-pdf-canvas" data-pdf="' . esc_attr($program_url) . '" data-width="200" aria-label="Program preview"></canvas>';
                            echo '</a></div>';
                        }
                    }
                } elseif ($program_url) {
                    echo '<div class="stagekitwp-program-preview"><a href="' . esc_url($program_url) . '" target="_blank" class="stagekitwp-program-link">';
                    echo '<canvas class="stagekitwp-pdf-canvas" data-pdf="' . esc_attr($program_url) . '" data-width="200" aria-label="Program preview"></canvas>';
                    echo '</a></div>';
                }
                echo '</div>'; // Close stagekitwp-program-section
            }
            
            echo '</div>'; // Close stagekitwp-shows-entry
        }
        echo '</div>'; // Close stagekitwp-season-section for unassigned shows
    }
    
    echo '</div>'; // Close stagekitwp-show-wrapper
    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode('stagekitwp_shows', 'stagekitwp_shortcode_shows');
?>
