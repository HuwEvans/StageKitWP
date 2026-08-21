<?php
// Register Advertisers CPT
function stagekitwp_register_advertisers_cpt() {
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
        'show_in_menu' => false,
        'show_in_rest' => true,  // Required for Gutenberg block dropdowns
        'capability_type' => 'post',
        'map_meta_cap' => true,
    );

    register_post_type('advertiser', $args);
}
add_action('init', 'stagekitwp_register_advertisers_cpt');

// Add Meta Boxes
function stagekitwp_add_advertiser_meta_boxes() {
    add_meta_box('stagekitwp_advertiser_details', 'Advertiser Details', 'stagekitwp_advertiser_meta_box_callback', 'advertiser', 'normal', 'default');
}
add_action('add_meta_boxes', 'stagekitwp_add_advertiser_meta_boxes');

// Meta Box Callback
function stagekitwp_advertiser_meta_box_callback($post) {
    wp_nonce_field('stagekitwp_save_advertiser_meta', 'stagekitwp_advertiser_nonce');

    $name = get_post_meta($post->ID, '_stagekitwp_name', true);
    $logo = get_post_meta($post->ID, '_stagekitwp_logo', true);
    $website = get_post_meta($post->ID, '_stagekitwp_website', true);
    $banner = get_post_meta($post->ID, '_stagekitwp_banner', true);
    $restaurant = get_post_meta($post->ID, '_stagekitwp_restaurant', true);

    echo '<label>Name:</label><br>';
    echo '<input type="text" name="stagekitwp_name" value="' . esc_attr($name) . '" style="width:100%;" /><br><br>';

    echo '<label>Logo:</label><br>';
    echo '<input type="hidden" name="stagekitwp_logo" id="stagekitwp_logo" value="' . esc_attr($logo) . '" />';
    
    // Display logo preview (handle both attachment IDs and URLs)
    if (!empty($logo)) {
        if (is_numeric($logo)) {
            // It's an attachment ID, get the URL
            $logo_url = wp_get_attachment_url($logo);
            if ($logo_url) {
                echo '<img id="stagekitwp_logo_preview" src="' . esc_url($logo_url) . '" style="max-width:150px; display:block; margin-bottom:10px;" alt="Logo preview" />';
            }
        } else {
            // It's a URL
            echo '<img id="stagekitwp_logo_preview" src="' . esc_url($logo) . '" style="max-width:150px; display:block; margin-bottom:10px;" alt="Logo preview" />';
        }
    }
    
    echo '<button type="button" class="button" id="stagekitwp_logo_button">Select Logo</button><br><br>';

    echo '<label>Website URL:</label><br>';
    echo '<input type="url" name="stagekitwp_website" value="' . esc_attr($website) . '" style="width:100%;" /><br><br>';

    echo '<label>Banner:</label><br>';
    echo '<input type="hidden" name="stagekitwp_banner" id="stagekitwp_banner" value="' . esc_attr($banner) . '" />';
    
    // Display banner preview (handle both attachment IDs and URLs)
    if (!empty($banner)) {
        if (is_numeric($banner)) {
            // It's an attachment ID, get the URL
            $banner_url = wp_get_attachment_url($banner);
            if ($banner_url) {
                echo '<img id="stagekitwp_banner_preview" src="' . esc_url($banner_url) . '" style="max-width:150px; display:block; margin-bottom:10px;" alt="Banner preview" />';
            }
        } else {
            // It's a URL
            echo '<img id="stagekitwp_banner_preview" src="' . esc_url($banner) . '" style="max-width:150px; display:block; margin-bottom:10px;" alt="Banner preview" />';
        }
    }
    
    echo '<button type="button" class="button" id="stagekitwp_banner_button">Select Banner</button><br><br>';

    echo '<label>Restaurant:</label><br>';
    echo '<select name="stagekitwp_restaurant" style="width:100%;">';
    echo '<option value="yes"' . selected($restaurant, 'yes', false) . '>Yes</option>';
    echo '<option value="no"' . selected($restaurant, 'no', false) . '>No</option>';
    echo '</select><br><br>';
}

// Save Meta Data
function stagekitwp_save_advertiser_meta($post_id) {
    $nonce = isset($_POST['stagekitwp_advertiser_nonce']) ? sanitize_text_field(wp_unslash($_POST['stagekitwp_advertiser_nonce'])) : '';
    if (!$nonce || !wp_verify_nonce($nonce, 'stagekitwp_save_advertiser_meta')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    $post_type = isset($_POST['post_type']) ? sanitize_key(wp_unslash($_POST['post_type'])) : '';
    if ('advertiser' !== $post_type) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $name = isset($_POST['stagekitwp_name']) ? sanitize_text_field(wp_unslash($_POST['stagekitwp_name'])) : '';
    $logo = isset($_POST['stagekitwp_logo']) ? esc_url_raw(wp_unslash($_POST['stagekitwp_logo'])) : '';
    $website = isset($_POST['stagekitwp_website']) ? esc_url_raw(wp_unslash($_POST['stagekitwp_website'])) : '';
    $banner = isset($_POST['stagekitwp_banner']) ? esc_url_raw(wp_unslash($_POST['stagekitwp_banner'])) : '';
    $restaurant = isset($_POST['stagekitwp_restaurant']) ? sanitize_text_field(wp_unslash($_POST['stagekitwp_restaurant'])) : '';

    update_post_meta($post_id, '_stagekitwp_name', $name);
    update_post_meta($post_id, '_stagekitwp_logo', $logo);
    update_post_meta($post_id, '_stagekitwp_website', $website);
    update_post_meta($post_id, '_stagekitwp_banner', $banner);
    update_post_meta($post_id, '_stagekitwp_restaurant', $restaurant);

    // Auto-set post title from Name
    remove_action('save_post', 'stagekitwp_save_advertiser_meta');
    wp_update_post(array('ID' => $post_id, 'post_title' => $name));
    add_action('save_post', 'stagekitwp_save_advertiser_meta');
}
add_action('save_post', 'stagekitwp_save_advertiser_meta');

// Admin Columns
function stagekitwp_advertiser_columns($columns) {
    $columns['stagekitwp_logo'] = 'Logo';
    $columns['stagekitwp_name'] = 'Name';
    $columns['stagekitwp_website'] = 'Website';
    $columns['stagekitwp_banner'] = 'Banner';
    $columns['stagekitwp_restaurant'] = 'Restaurant';
    return $columns;
}
add_filter('manage_advertiser_posts_columns', 'stagekitwp_advertiser_columns');

function stagekitwp_advertiser_column_content($column, $post_id) {
    switch ($column) {
        case 'stagekitwp_logo':
            $logo = get_post_meta($post_id, '_stagekitwp_logo', true);
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
        case 'stagekitwp_name':
            echo esc_html(get_post_meta($post_id, '_stagekitwp_name', true));
            break;
        case 'stagekitwp_website':
            $url = get_post_meta($post_id, '_stagekitwp_website', true);
            if ($url) echo '<a href="' . esc_url($url) . '" target="_blank">' . esc_html($url) . '</a>';
            break;
        case 'stagekitwp_banner':
            $banner = get_post_meta($post_id, '_stagekitwp_banner', true);
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
        case 'stagekitwp_restaurant':
            $restaurant = get_post_meta($post_id, '_stagekitwp_restaurant', true);
            if ($restaurant === 'yes' || $restaurant === '1' || $restaurant === true) {
                echo 'Yes';
            }
            break;
    }
}
add_action('manage_advertiser_posts_custom_column', 'stagekitwp_advertiser_column_content', 10, 2);
?>
