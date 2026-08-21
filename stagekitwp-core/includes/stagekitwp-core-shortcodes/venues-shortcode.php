<?php
defined('ABSPATH') || exit;

function stagekitwp_shortcode_venues($atts) {
    wp_enqueue_style('leaflet-css', 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css', array(), '1.9.4');
    wp_enqueue_script('leaflet-js', 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js', array(), '1.9.4', true);

    $atts = shortcode_atts(array(
        'show_id' => '',
    ), $atts, 'stagekitwp_venues');

    $bg_color = get_option('stagekitwp_venues_bg_color', '#ffffff');
    $text_color = get_option('stagekitwp_venues_text_color', '#333333');
    $base_font = get_option('stagekitwp_venues_base_font', 'Georgia');
    $border_color = get_option('stagekitwp_venues_border_color', '#cccccc');
    $border_width = get_option('stagekitwp_venues_border_width', '1');
    $rounded = get_option('stagekitwp_venues_rounded', '0');
    $radius = get_option('stagekitwp_venues_radius', '5');
    $shadow = get_option('stagekitwp_venues_shadow', '0');
    $h2_color = get_option('stagekitwp_venues_h2_color', '#333333');
    $h3_color = get_option('stagekitwp_venues_h3_color', '#555555');

    $border_style = $border_width > 0 ? 'border: ' . intval($border_width) . 'px solid ' . esc_attr($border_color) . ';' : '';
    $border_radius_style = $rounded ? 'border-radius: ' . intval($radius) . 'px;' : '';
    $box_shadow_style = $shadow ? 'box-shadow: 0 2px 5px rgba(0,0,0,0.1);' : '';

    $wrapper_vars = 'background-color: var(--stagekitwp-sc-bg, ' . esc_attr($bg_color) . ');'
        . 'color: var(--stagekitwp-sc-text, ' . esc_attr($text_color) . ');'
        . 'font-family: var(--stagekitwp-sc-font, ' . esc_attr($base_font) . ');'
        . 'padding: 20px;'
        . $border_style
        . $border_radius_style
        . $box_shadow_style;

    $h2_style = 'color: var(--stagekitwp-sc-h2, ' . esc_attr($h2_color) . '); font-family: var(--stagekitwp-sc-font, ' . esc_attr($base_font) . '); margin-top: 0;';
    $h3_style = 'color: var(--stagekitwp-sc-h3, ' . esc_attr($h3_color) . '); font-family: var(--stagekitwp-sc-font, ' . esc_attr($base_font) . ');';

    $venue_args = array(
        'post_type' => 'venue',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
    );

    if (!empty($atts['show_id'])) {
        $venue_id = get_post_meta($atts['show_id'], '_stagekitwp_show_venue', true);
        if ($venue_id) {
            $venue_args['post__in'] = array($venue_id);
        } else {
            return '<div class="stagekitwp-venue-card" style="' . esc_attr($wrapper_vars) . '">No venue assigned to this show.</div>';
        }
    }

    $venues = get_posts($venue_args);

    if (empty($venues)) {
        return '<div class="stagekitwp-venue-card" style="' . esc_attr($wrapper_vars) . '">No venues found.</div>';
    }

    $output = '<div class="stagekitwp-venue-card" style="' . esc_attr($wrapper_vars) . '">';
    $output .= '<h2 style="' . esc_attr($h2_style) . '">Venues</h2>';

    $map_counter = 0;
    foreach ($venues as $venue) {
        $venue_name = get_post_meta($venue->ID, '_stagekitwp_venue_name', true);
        $venue_address = get_post_meta($venue->ID, '_stagekitwp_venue_address', true);
        $venue_phone = get_post_meta($venue->ID, '_stagekitwp_venue_phone', true);
        $venue_website = get_post_meta($venue->ID, '_stagekitwp_venue_website', true);
        $venue_latitude = get_post_meta($venue->ID, '_stagekitwp_venue_latitude', true);
        $venue_longitude = get_post_meta($venue->ID, '_stagekitwp_venue_longitude', true);
        $venue_image = get_post_meta($venue->ID, '_stagekitwp_venue_image', true);

        $map_id = 'stagekitwp-venue-map-' . $map_counter;
        $map_counter++;

        $google_maps_url = '';
        if (!empty($venue_latitude) && !empty($venue_longitude)) {
            $google_maps_url = 'https://maps.google.com/?q=' . urlencode($venue_latitude . ',' . $venue_longitude);
        } elseif (!empty($venue_address)) {
            $google_maps_url = 'https://maps.google.com/?q=' . urlencode($venue_address);
        }

        $output .= '<div class="stagekitwp-venue-entry" style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid var(--stagekitwp-sc-border, ' . esc_attr($border_color) . ');">';
        $output .= '<h3 style="' . esc_attr($h3_style) . '">' . esc_html($venue_name) . '</h3>';

        if (!empty($venue_image)) {
            if (is_numeric($venue_image)) {
                $image_url = wp_get_attachment_url($venue_image);
            } else {
                $image_url = $venue_image;
            }
            if ($image_url) {
                $output .= '<div style="margin-bottom: 15px;"><img src="' . esc_url($image_url) . '" style="max-width: 100%; height: auto;" /></div>';
            }
        }

        if (!empty($venue_address)) {
            $output .= '<p><strong>Address:</strong> ' . nl2br(esc_html($venue_address)) . '</p>';
        }

        if (!empty($venue_phone)) {
            $output .= '<p><strong>Phone:</strong> <a href="tel:' . esc_attr(str_replace(' ', '', $venue_phone)) . '">' . esc_html($venue_phone) . '</a></p>';
        }

        if (!empty($venue_website)) {
            $output .= '<p><strong>Website:</strong> <a href="' . esc_url($venue_website) . '" target="_blank">Visit Website</a></p>';
        }

        if (!empty($venue_latitude) && !empty($venue_longitude)) {
            $map_wrap_style = $rounded ? 'border-radius: ' . intval($radius) . 'px;' : '';
            $map_style = 'height: 300px; border: 1px solid var(--stagekitwp-sc-border, ' . esc_attr($border_color) . ');' . ($rounded ? 'border-radius: ' . intval($radius) . 'px;' : '');
            $output .= '<div class="stagekitwp-venue-map-wrap" style="' . esc_attr($map_wrap_style) . ' overflow: hidden;">';
            $output .= '<div id="' . esc_attr($map_id) . '" class="stagekitwp-venue-map" style="' . esc_attr($map_style) . '"></div>';
            $output .= '</div>';

            $output .= '<script>
            (function() {
                function initMap() {
                    if (typeof L !== "undefined") {
                        var map = L.map("' . esc_js($map_id) . '").setView([' . floatval($venue_latitude) . ', ' . floatval($venue_longitude) . '], 15);
                        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
                            attribution: "&copy; OpenStreetMap contributors",
                            maxZoom: 19
                        }).addTo(map);
                        L.marker([' . floatval($venue_latitude) . ', ' . floatval($venue_longitude) . ']).addTo(map)
                            .bindPopup("<strong>' . esc_js($venue_name) . '</strong><br>' . esc_js($venue_address) . '");
                    } else {
                        setTimeout(initMap, 100);
                    }
                }
                if (document.readyState === "loading") {
                    document.addEventListener("DOMContentLoaded", initMap);
                } else {
                    initMap();
                }
            })();
            </script>';
        }

        if (!empty($google_maps_url)) {
            $output .= '<p><a href="' . esc_url($google_maps_url) . '" target="_blank" style="color: var(--stagekitwp-sc-h3, ' . esc_attr($h3_color) . '); text-decoration: none; border-bottom: 2px solid var(--stagekitwp-sc-h3, ' . esc_attr($h3_color) . '); padding-bottom: 2px;">📍 Get Directions</a></p>';
        }

        $output .= '</div>';
    }

    $output .= '</div>';
    return $output;
}

add_shortcode('stagekitwp_venues', 'stagekitwp_shortcode_venues');
