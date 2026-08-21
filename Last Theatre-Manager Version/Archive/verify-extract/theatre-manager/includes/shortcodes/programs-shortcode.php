<?php
/**
 * Shortcode to display a gallery of program PDFs by season.
 * Usage: [tm_programs season="123" columns="3"]
 * If season omitted, shows programs for all seasons grouped by season.
 */

function tm_programs_shortcode($atts) {
    $atts = shortcode_atts(array(
        'season' => '',
        'columns' => 3,
        'size' => 'medium'
    ), $atts);

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
                'key' => '_tm_show_season',
                'value' => $season_id,
                'compare' => '='
            )
        );

        $query = new WP_Query(array('post_type' => 'show', 'posts_per_page' => -1, 'meta_query' => $meta_query));
        echo '<div class="tm-programs-gallery tm-programs-season-' . esc_attr($season_id) . '">';
        $i = 0;
        while ($query->have_posts()) {
            $query->the_post();
            $id = get_the_ID();
            $program_id = get_post_meta($id, '_tm_show_program', true);
            $program_url = get_post_meta($id, '_tm_show_program_url', true);
            if (!$program_url && $program_id) $program_url = wp_get_attachment_url($program_id);

            echo '<div class="tm-program-item" style="width:' . esc_attr(100 / $columns) . '%;float:left;padding:8px;box-sizing:border-box;">';
            echo '<h4>' . get_the_title() . '</h4>';
                if ($program_id) {
                    // Try to get preview image (WP creates image preview for PDFs)
                    $preview = wp_get_attachment_image_src($program_id, $atts['size']);
                    if ($preview) {
                        echo '<a href="' . esc_url($program_url) . '" target="_blank"><img src="' . esc_url($preview[0]) . '" style="max-width:100%;height:auto;" /></a>';
                    } else {
                        // Check for generated preview saved in attachment meta
                        $generated = get_post_meta($program_id, '_tm_pdf_preview', true);
                        if ($generated) {
                            echo '<a href="' . esc_url($program_url) . '" target="_blank"><img src="' . esc_url($generated) . '" style="max-width:100%;height:auto;" /></a>';
                        } else {
                            // No server preview: output a canvas placeholder that will be rendered client-side by PDF.js
                            echo '<div class="tm-program-preview">';
                            echo '<a href="' . esc_url($program_url) . '" target="_blank">';
                            echo '<canvas class="tm-pdf-canvas" data-pdf="' . esc_attr($program_url) . '" data-width="300" aria-label="Program preview"></canvas>';
                            echo '</a>';
                            echo '</div>';
                        }
                    }
                } elseif ($program_url) {
                    // No attachment ID (direct URL) — attempt client-side rendering
                    echo '<div class="tm-program-preview">';
                    echo '<a href="' . esc_url($program_url) . '" target="_blank">';
                    echo '<canvas class="tm-pdf-canvas" data-pdf="' . esc_attr($program_url) . '" data-width="300" aria-label="Program preview"></canvas>';
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
        $seasons = get_posts(array('post_type' => 'season', 'numberposts' => -1));
        echo '<div class="tm-programs-by-season">';
        foreach ($seasons as $s) {
            echo '<h3>' . esc_html($s->post_title) . '</h3>';
            $query = new WP_Query(array('post_type' => 'show', 'posts_per_page' => -1, 'meta_key' => '_tm_show_season', 'meta_value' => $s->ID));
            if ($query->have_posts()) {
                echo '<div class="tm-programs-season-' . esc_attr($s->ID) . '">';
                while ($query->have_posts()) {
                    $query->the_post();
                    $id = get_the_ID();
                    $program_id = get_post_meta($id, '_tm_show_program', true);
                    $program_url = get_post_meta($id, '_tm_show_program_url', true);
                    if (!$program_url && $program_id) $program_url = wp_get_attachment_url($program_id);

                    echo '<div class="tm-program-item" style="width:' . esc_attr(100 / $columns) . '%;float:left;padding:8px;box-sizing:border-box;">';
                    echo '<h4>' . get_the_title() . '</h4>';
                    if ($program_id) {
                        $preview = wp_get_attachment_image_src($program_id, $atts['size']);
                        if ($preview) {
                            echo '<a href="' . esc_url($program_url) . '" target="_blank"><img src="' . esc_url($preview[0]) . '" style="max-width:100%;height:auto;" /></a>';
                        } else {
                            $generated = get_post_meta($program_id, '_tm_pdf_preview', true);
                            if ($generated) {
                                echo '<a href="' . esc_url($program_url) . '" target="_blank"><img src="' . esc_url($generated) . '" style="max-width:100%;height:auto;" /></a>';
                            } else {
                                // No server preview: render client-side canvas
                                echo '<a href="' . esc_url($program_url) . '" target="_blank">';
                                echo '<canvas class="tm-pdf-canvas" data-pdf="' . esc_attr($program_url) . '" data-width="300" aria-label="Program preview"></canvas>';
                                echo '</a>';
                            }
                        }
                    } elseif ($program_url) {
                        // No attachment ID but URL present: attempt client-side rendering
                        echo '<a href="' . esc_url($program_url) . '" target="_blank">';
                        echo '<canvas class="tm-pdf-canvas" data-pdf="' . esc_attr($program_url) . '" data-width="300" aria-label="Program preview"></canvas>';
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
add_shortcode('tm_programs', 'tm_programs_shortcode');

?>
