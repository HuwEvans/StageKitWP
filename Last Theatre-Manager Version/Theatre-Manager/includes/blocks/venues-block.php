<?php
/**
 * Venues Block v4.0
 * Gutenberg block for displaying venues with expandable accordion layout
 */

defined('ABSPATH') || exit;

/**
 * Register the Venues block
 */
function tm_register_venues_block() {
    register_block_type('theatre-manager/venues', array(
        'render_callback' => 'tm_render_venues_block',
        'attributes' => array(
            'showMaps' => array(
                'type' => 'boolean',
                'default' => false,
            ),
            'columns' => array(
                'type' => 'number',
                'default' => 1,
            ),
            'showSearch' => array(
                'type' => 'boolean',
                'default' => true,
            ),
            'showId' => array(
                'type' => 'string',
                'default' => '',
            ),
        ),
    ));
}
add_action('init', 'tm_register_venues_block');

/**
 * Render the Venues block
 */
function tm_render_venues_block($attributes) {
    // Sanitize attributes
    $show_maps = isset($attributes['showMaps']) ? (bool) $attributes['showMaps'] : false;
    $columns = isset($attributes['columns']) ? max(1, absint($attributes['columns'])) : 1;
    $show_search = isset($attributes['showSearch']) ? (bool) $attributes['showSearch'] : true;
    $show_id = isset($attributes['showId']) ? sanitize_text_field($attributes['showId']) : '';

    // Enqueue Leaflet if maps are enabled
    if ($show_maps) {
        wp_enqueue_style('leaflet-css', 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css', array(), '1.9.4');
        wp_enqueue_script('leaflet-js', 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js', array(), '1.9.4', true);
    }

    // Enqueue block-specific scripts and styles
    wp_enqueue_style('tm-venues-block-css', plugins_url('venues-block.css', __FILE__), array(), '4.0.0');
    wp_enqueue_script('tm-venues-block-js', plugins_url('venues-block.js', __FILE__), array(), '4.0.0', true);

    // Query venues
    $venue_args = array(
        'post_type' => 'venue',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
    );

    // If show_id is provided, get the venue associated with that show
    if (!empty($show_id)) {
        $venue_id = get_post_meta(intval($show_id), '_tm_show_venue', true);
        if ($venue_id) {
            $venue_args['post__in'] = array($venue_id);
        } else {
            return '<div class="tm-venues-block tm-venues-empty"><p>No venue assigned to this show.</p></div>';
        }
    }

    $venues = get_posts($venue_args);

    if (empty($venues)) {
        return '<div class="tm-venues-block tm-venues-empty"><p>No venues found.</p></div>';
    }

    // Start output
    $output = '<div class="tm-venues-block" data-show-maps="' . esc_attr($show_maps ? '1' : '0') . '" data-columns="' . esc_attr($columns) . '">';

    // Search box (optional)
    if ($show_search && count($venues) > 1) {
        $output .= '<div class="tm-venues-search">';
        $output .= '<input type="text" class="tm-venues-search-input" placeholder="Search venues..." />';
        $output .= '</div>';
    }

    // Venues accordion
    $output .= '<div class="tm-venues-accordion">';

    $map_counter = 0;
    foreach ($venues as $venue) {
        $venue_id = $venue->ID;
        $venue_name = get_post_meta($venue_id, '_tm_venue_name', true);
        $venue_address = get_post_meta($venue_id, '_tm_venue_address', true);
        $venue_phone = get_post_meta($venue_id, '_tm_venue_phone', true);
        $venue_website = get_post_meta($venue_id, '_tm_venue_website', true);
        $venue_latitude = get_post_meta($venue_id, '_tm_venue_latitude', true);
        $venue_longitude = get_post_meta($venue_id, '_tm_venue_longitude', true);
        $venue_image = get_post_meta($venue_id, '_tm_venue_image', true);

        $map_id = 'tm-venue-map-' . $map_counter;
        $map_counter++;

        // Build Google Maps link for "Get Directions"
        $google_maps_url = '';
        if (!empty($venue_latitude) && !empty($venue_longitude)) {
            $google_maps_url = 'https://maps.google.com/?q=' . urlencode($venue_latitude . ',' . $venue_longitude);
        } elseif (!empty($venue_address)) {
            $google_maps_url = 'https://maps.google.com/?q=' . urlencode($venue_address);
        }

        // Accordion item header
        $output .= '<div class="tm-venue-accordion-item" data-venue-id="' . esc_attr($venue_id) . '" data-venue-name="' . esc_attr($venue_name) . '">';
        $output .= '<button class="tm-venue-accordion-header" aria-expanded="false" aria-controls="tm-venue-content-' . esc_attr($venue_id) . '">';
        $output .= '<span class="tm-venue-name">' . esc_html($venue_name) . '</span>';
        $output .= '<span class="tm-venue-toggle-icon">+</span>';
        $output .= '</button>';

        // Accordion item content
        $output .= '<div id="tm-venue-content-' . esc_attr($venue_id) . '" class="tm-venue-accordion-content" hidden>';

        // Venue image (if available)
        if (!empty($venue_image)) {
            if (is_numeric($venue_image)) {
                $image_url = wp_get_attachment_url($venue_image);
            } else {
                $image_url = $venue_image;
            }
            if ($image_url) {
                $output .= '<div class="tm-venue-image">';
                $output .= '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($venue_name) . '" />';
                $output .= '</div>';
            }
        }

        // Venue details
        $output .= '<div class="tm-venue-details">';

        if (!empty($venue_address)) {
            $output .= '<div class="tm-venue-detail-item tm-venue-address">';
            $output .= '<strong>Address:</strong><br />';
            $output .= nl2br(esc_html($venue_address));
            $output .= '</div>';
        }

        if (!empty($venue_phone)) {
            $output .= '<div class="tm-venue-detail-item tm-venue-phone">';
            $output .= '<strong>Phone:</strong> ';
            $output .= '<a href="tel:' . esc_attr(str_replace(' ', '', $venue_phone)) . '">' . esc_html($venue_phone) . '</a>';
            $output .= '</div>';
        }

        if (!empty($venue_website)) {
            $output .= '<div class="tm-venue-detail-item tm-venue-website">';
            $output .= '<strong>Website:</strong> ';
            $output .= '<a href="' . esc_url($venue_website) . '" target="_blank" rel="noopener noreferrer">Visit Website</a>';
            $output .= '</div>';
        }

        // Google Maps link (always show if address available)
        if (!empty($google_maps_url)) {
            $output .= '<div class="tm-venue-detail-item tm-venue-directions">';
            $output .= '<a href="' . esc_url($google_maps_url) . '" target="_blank" rel="noopener noreferrer" class="tm-btn tm-btn-directions">Get Directions</a>';
            $output .= '</div>';
        }

        $output .= '</div>';

        // Interactive map (optional)
        if ($show_maps && !empty($venue_latitude) && !empty($venue_longitude)) {
            $output .= '<div class="tm-venue-map-container">';
            $output .= '<div id="' . esc_attr($map_id) . '" class="tm-venue-map"></div>';
            $output .= '</div>';

            // Map initialization script
            $output .= '<script>
            (function() {
                function initVenueMap() {
                    if (typeof L !== "undefined") {
                        var map = L.map("' . esc_js($map_id) . '").setView([' . floatval($venue_latitude) . ', ' . floatval($venue_longitude) . '], 15);
                        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
                            attribution: "&copy; OpenStreetMap contributors",
                            maxZoom: 19
                        }).addTo(map);
                        L.marker([' . floatval($venue_latitude) . ', ' . floatval($venue_longitude) . ']).addTo(map)
                            .bindPopup("<strong>' . esc_js($venue_name) . '</strong><br>' . esc_js($venue_address) . '");
                    } else {
                        setTimeout(initVenueMap, 100);
                    }
                }
                if (document.readyState === "loading") {
                    document.addEventListener("DOMContentLoaded", initVenueMap);
                } else {
                    initVenueMap();
                }
            })();
            </script>';
        }

        $output .= '</div>'; // End accordion content
        $output .= '</div>'; // End accordion item
    }

    $output .= '</div>'; // End accordion
    $output .= '</div>'; // End container

    return $output;
}
