<?php
// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Get image URL from attachment ID or return the value if it's already a URL
 * Handles both: direct URLs and WordPress attachment IDs
 * 
 * @param int|string $value Either an attachment ID or a URL
 * @return string The image URL, or empty string if not found
 */
function stagekitwp_get_venue_image_url($value) {
    if (empty($value)) {
        return '';
    }
    
    // If it's already a URL, return it
    if (is_string($value) && (strpos($value, 'http') === 0 || strpos($value, '/') === 0)) {
        return $value;
    }
    
    // If it's an attachment ID, get the URL
    if (is_numeric($value)) {
        $attachment_id = intval($value);
        if ($attachment_id > 0) {
            $image_url = wp_get_attachment_url($attachment_id);
            if ($image_url) {
                return $image_url;
            }
        }
    }
    
    return '';
}

/**
 * Register Venue Custom Post Type
 */
function stagekitwp_register_venue_cpt() {
    $labels = array(
        'name' => 'Venues',
        'singular_name' => 'Venue',
        'menu_name' => 'Venues',
        'name_admin_bar' => 'Venue',
        'add_new' => 'Add New',
        'add_new_item' => 'Add New Venue',
        'new_item' => 'New Venue',
        'edit_item' => 'Edit Venue',
        'view_item' => 'View Venue',
        'all_items' => 'Venues',
        'search_items' => 'Search Venues',
        'not_found' => 'No venues found.',
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'menu_position' => 22,
        'menu_icon' => 'dashicons-location-alt',
        'supports' => array(''),
        'has_archive' => true,
        'show_in_menu'    => false,
        'capability_type' => 'post',
        'map_meta_cap'    => true,
        'show_in_rest'    => true,  // Required for Gutenberg block dropdowns
    );

    register_post_type('venue', $args);
}
add_action('init', 'stagekitwp_register_venue_cpt');

/**
 * Add Meta Boxes for Venue
 */
function stagekitwp_add_venue_meta_boxes() {
    add_meta_box('stagekitwp_venue_details', 'Venue Details', 'stagekitwp_render_venue_meta_box', 'venue', 'normal', 'default');
}
add_action('add_meta_boxes', 'stagekitwp_add_venue_meta_boxes');

/**
 * Render Venue Meta Box
 */
function stagekitwp_render_venue_meta_box($post) {
    wp_nonce_field('stagekitwp_save_venue_meta', 'stagekitwp_venue_meta_nonce');

    $fields = [
        'name' => '',
        'address' => '',
        'phone' => '',
        'website' => '',
        'image' => '',
        'latitude' => '',
        'longitude' => '',
    ];

    foreach ($fields as $key => $default) {
        $fields[$key] = get_post_meta($post->ID, '_stagekitwp_venue_' . $key, true);
    }

    echo '<p><label>Name:<br><input type="text" name="stagekitwp_venue_name" value="' . esc_attr($fields['name']) . '" class="widefat" /></label></p>';
    
    echo '<p><label>Address:<br><textarea name="stagekitwp_venue_address" class="widefat" rows="3">' . esc_textarea($fields['address']) . '</textarea></label></p>';
    
    echo '<p><label>Phone:<br><input type="tel" name="stagekitwp_venue_phone" value="' . esc_attr($fields['phone']) . '" class="widefat" /></label></p>';
    
    echo '<p><label>Website:<br><input type="url" name="stagekitwp_venue_website" value="' . esc_attr($fields['website']) . '" class="widefat" /></label></p>';

    echo '<p><label>Latitude:<br><input type="text" name="stagekitwp_venue_latitude" value="' . esc_attr($fields['latitude']) . '" class="widefat" placeholder="e.g., 40.7128" /></label></p>';
    
    echo '<p><label>Longitude:<br><input type="text" name="stagekitwp_venue_longitude" value="' . esc_attr($fields['longitude']) . '" class="widefat" placeholder="e.g., -74.0060" /></label></p>';

    echo '<p><small>Latitude and Longitude are optional. If provided, they will be used to generate Google Maps links.</small></p>';

    echo '<p><label>Venue Image:<br>';
    echo '<input type="text" name="stagekitwp_venue_image" id="stagekitwp_venue_image" value="' . esc_attr($fields['image']) . '" class="widefat" />';
    echo '<button type="button" class="button stagekitwp-media-button" data-target="stagekitwp_venue_image" data-preview="stagekitwp_venue_image_preview">Select Image</button>';
    echo '</label></p>';
    // Convert attachment ID to URL for preview display
    $image_url = stagekitwp_get_venue_image_url($fields['image']);
    echo '<div><img id="stagekitwp_venue_image_preview" src="' . esc_url($image_url) . '" style="max-width:150px;' . ($image_url ? '' : ' display:none;') . '" /></div>';
}

/**
 * Save Venue Meta
 */
function stagekitwp_save_venue_meta($post_id) {
    $nonce = isset($_POST['stagekitwp_venue_meta_nonce']) ? sanitize_text_field(wp_unslash($_POST['stagekitwp_venue_meta_nonce'])) : '';
    if (!$nonce || !wp_verify_nonce($nonce, 'stagekitwp_save_venue_meta')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    $post_type = isset($_POST['post_type']) ? sanitize_key(wp_unslash($_POST['post_type'])) : '';
    if ('venue' !== $post_type || !current_user_can('edit_post', $post_id)) return;

    $fields = [
        'name', 'address', 'phone', 'website', 'image', 'latitude', 'longitude'
    ];

    foreach ($fields as $field) {
        if (isset($_POST['stagekitwp_venue_' . $field])) {
            if ($field === 'address') {
                update_post_meta($post_id, '_stagekitwp_venue_' . $field, sanitize_textarea_field(wp_unslash($_POST['stagekitwp_venue_' . $field])));
            } elseif ($field === 'website') {
                update_post_meta($post_id, '_stagekitwp_venue_' . $field, esc_url_raw(wp_unslash($_POST['stagekitwp_venue_' . $field])));
            } else {
                update_post_meta($post_id, '_stagekitwp_venue_' . $field, sanitize_text_field(wp_unslash($_POST['stagekitwp_venue_' . $field])));
            }
        }
    }

    // Auto-fill post title from Name field
    remove_action('save_post', 'stagekitwp_save_venue_meta');
    $venue_name = isset($_POST['stagekitwp_venue_name']) ? sanitize_text_field(wp_unslash($_POST['stagekitwp_venue_name'])) : '';
    wp_update_post([
        'ID' => $post_id,
        'post_title' => $venue_name
    ]);
    add_action('save_post', 'stagekitwp_save_venue_meta');
}
add_action('save_post', 'stagekitwp_save_venue_meta');
