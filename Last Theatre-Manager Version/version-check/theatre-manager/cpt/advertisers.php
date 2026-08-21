<?php
// Register Advertisers CPT
function tm_register_advertisers_cpt() {
    $labels = array(
        'name' => 'Advertisers',
        'singular_name' => 'Advertiser',
        'menu_name' => 'Advertisers',
        'name_admin_bar' => 'Advertiser',
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => false,
        'menu_position' => 6,
        'menu_icon' => 'dashicons-megaphone',
        'supports' => array(''),
        'show_in_menu' => 'theatre-manager',
        'show_in_rest' => false,
    );

    register_post_type('advertiser', $args);
}
add_action('init', 'tm_register_advertisers_cpt');

// Add Meta Boxes
function tm_add_advertiser_meta_boxes() {
    add_meta_box('tm_advertiser_details', 'Advertiser Details', 'tm_advertiser_meta_box_callback', 'advertiser', 'normal', 'default');
}
add_action('add_meta_boxes', 'tm_add_advertiser_meta_boxes');

// Meta Box Callback
function tm_advertiser_meta_box_callback($post) {
    wp_nonce_field('tm_save_advertiser_meta', 'tm_advertiser_nonce');

    $name = get_post_meta($post->ID, '_tm_name', true);
    $logo = get_post_meta($post->ID, '_tm_logo', true);
    $website = get_post_meta($post->ID, '_tm_website', true);
    $banner = get_post_meta($post->ID, '_tm_banner', true);
    $restaurant = get_post_meta($post->ID, '_tm_restaurant', true);

    echo '<label>Name:</label><br>';
    echo '<input type="text" name="tm_name" value="' . esc_attr($name) . '" style="width:100%;" /><br><br>';

    echo '<label>Logo:</label><br>';
    echo '<input type="hidden" name="tm_logo" id="tm_logo" value="' . esc_attr($logo) . '" />';
    
    // Display logo preview (handle both attachment IDs and URLs)
    if (!empty($logo)) {
        if (is_numeric($logo)) {
            // It's an attachment ID, get the URL
            $logo_url = wp_get_attachment_url($logo);
            if ($logo_url) {
                echo '<img id="tm_logo_preview" src="' . esc_url($logo_url) . '" style="max-width:150px; display:block; margin-bottom:10px;" alt="Logo preview" />';
            }
        } else {
            // It's a URL
            echo '<img id="tm_logo_preview" src="' . esc_url($logo) . '" style="max-width:150px; display:block; margin-bottom:10px;" alt="Logo preview" />';
        }
    }
    
    echo '<button type="button" class="button" id="tm_logo_button">Select Logo</button><br><br>';

    echo '<label>Website URL:</label><br>';
    echo '<input type="url" name="tm_website" value="' . esc_attr($website) . '" style="width:100%;" /><br><br>';

    echo '<label>Banner:</label><br>';
    echo '<input type="hidden" name="tm_banner" id="tm_banner" value="' . esc_attr($banner) . '" />';
    
    // Display banner preview (handle both attachment IDs and URLs)
    if (!empty($banner)) {
        if (is_numeric($banner)) {
            // It's an attachment ID, get the URL
            $banner_url = wp_get_attachment_url($banner);
            if ($banner_url) {
                echo '<img id="tm_banner_preview" src="' . esc_url($banner_url) . '" style="max-width:150px; display:block; margin-bottom:10px;" alt="Banner preview" />';
            }
        } else {
            // It's a URL
            echo '<img id="tm_banner_preview" src="' . esc_url($banner) . '" style="max-width:150px; display:block; margin-bottom:10px;" alt="Banner preview" />';
        }
    }
    
    echo '<button type="button" class="button" id="tm_banner_button">Select Banner</button><br><br>';

    echo '<label>Restaurant:</label><br>';
    echo '<select name="tm_restaurant" style="width:100%;">';
    echo '<option value="yes"' . selected($restaurant, 'yes', false) . '>Yes</option>';
    echo '<option value="no"' . selected($restaurant, 'no', false) . '>No</option>';
    echo '</select><br><br>';
}

// Save Meta Data
function tm_save_advertiser_meta($post_id) {
    if (!isset($_POST['tm_advertiser_nonce']) || !wp_verify_nonce($_POST['tm_advertiser_nonce'], 'tm_save_advertiser_meta')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $name = sanitize_text_field($_POST['tm_name'] ?? '');
    $logo = esc_url_raw($_POST['tm_logo'] ?? '');
    $website = esc_url_raw($_POST['tm_website'] ?? '');
    $banner = esc_url_raw($_POST['tm_banner'] ?? '');
    $restaurant = sanitize_text_field($_POST['tm_restaurant'] ?? '');

    update_post_meta($post_id, '_tm_name', $name);
    update_post_meta($post_id, '_tm_logo', $logo);
    update_post_meta($post_id, '_tm_website', $website);
    update_post_meta($post_id, '_tm_banner', $banner);
    update_post_meta($post_id, '_tm_restaurant', $restaurant);

    // Auto-set post title from Name
    remove_action('save_post', 'tm_save_advertiser_meta');
    wp_update_post(array('ID' => $post_id, 'post_title' => $name));
    add_action('save_post', 'tm_save_advertiser_meta');
}
add_action('save_post', 'tm_save_advertiser_meta');

// Admin Columns
function tm_advertiser_columns($columns) {
    $columns['tm_logo'] = 'Logo';
    $columns['tm_name'] = 'Name';
    $columns['tm_website'] = 'Website';
    $columns['tm_banner'] = 'Banner';
    $columns['tm_restaurant'] = 'Restaurant';
    return $columns;
}
add_filter('manage_advertiser_posts_columns', 'tm_advertiser_columns');

function tm_advertiser_column_content($column, $post_id) {
    switch ($column) {
        case 'tm_logo':
            $logo = get_post_meta($post_id, '_tm_logo', true);
            if (!empty($logo)) {
                if (is_numeric($logo)) {
                    // It's an attachment ID, get the URL
                    $logo_url = wp_get_attachment_url($logo);
                    if ($logo_url) {
                        echo '<img src="' . esc_url($logo_url) . '" style="max-width:50px; height:auto;" alt="Logo" />';
                    }
                } else {
                    // It's a URL
                    echo '<img src="' . esc_url($logo) . '" style="max-width:50px; height:auto;" alt="Logo" />';
                }
            }
            break;
        case 'tm_name':
            echo esc_html(get_post_meta($post_id, '_tm_name', true));
            break;
        case 'tm_website':
            $url = get_post_meta($post_id, '_tm_website', true);
            if ($url) echo '<a href="' . esc_url($url) . '" target="_blank">' . esc_html($url) . '</a>';
            break;
        case 'tm_banner':
            $banner = get_post_meta($post_id, '_tm_banner', true);
            if (!empty($banner)) {
                if (is_numeric($banner)) {
                    // It's an attachment ID, get the URL
                    $banner_url = wp_get_attachment_url($banner);
                    if ($banner_url) {
                        echo '<img src="' . esc_url($banner_url) . '" style="max-width:50px; height:auto;" alt="Banner" />';
                    }
                } else {
                    // It's a URL
                    echo '<img src="' . esc_url($banner) . '" style="max-width:50px; height:auto;" alt="Banner" />';
                }
            }
            break;
        case 'tm_restaurant':
            $restaurant = get_post_meta($post_id, '_tm_restaurant', true);
            if ($restaurant === 'yes' || $restaurant === '1' || $restaurant === true) {
                echo 'Yes';
            }
            break;
    }
}
add_action('manage_advertiser_posts_custom_column', 'tm_advertiser_column_content', 10, 2);
?>
