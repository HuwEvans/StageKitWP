<?php
defined('ABSPATH') || exit;

/**
 * Get image URL from attachment ID or return the value if it's already a URL
 * Handles both: direct URLs and WordPress attachment IDs
 * 
 * @param int|string $value Either an attachment ID or a URL
 * @return string The image URL, or empty string if not found
 */
function tm_get_sponsor_image_url($value) {
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

function tm_register_sponsor_cpt() {
    $labels = array(
        'name' => 'Sponsors',
        'singular_name' => 'Sponsor',
        'menu_name' => 'Sponsors',
        'name_admin_bar' => 'Sponsor',
        'add_new' => 'Add New',
        'add_new_item' => 'Add New Sponsor',
        'new_item' => 'New Sponsor',
        'edit_item' => 'Edit Sponsor',
        'view_item' => 'View Sponsor',
        'all_items' => 'All Sponsors',
        'search_items' => 'Search Sponsors',
        'not_found' => 'No sponsors found.',
        'not_found_in_trash' => 'No sponsors found in Trash.'
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => false,
        'menu_position' => 5,
        'menu_icon' => 'dashicons-megaphone',
        'supports' => array(''),
        'show_in_menu' => 'theatre-manager',
        'show_in_rest' => false,
    );

    register_post_type('sponsor', $args);
}
add_action('init', 'tm_register_sponsor_cpt');

function tm_add_sponsor_meta_boxes() {
    add_meta_box('tm_sponsor_details', 'Sponsor Details', 'tm_render_sponsor_meta_box', 'sponsor', 'normal', 'default');
}
add_action('add_meta_boxes', 'tm_add_sponsor_meta_boxes');

function tm_render_sponsor_meta_box($post) {
    wp_nonce_field('tm_save_sponsor_meta', 'tm_sponsor_nonce');

    $name = get_post_meta($post->ID, '_tm_name', true);
    $company = get_post_meta($post->ID, '_tm_company', true);
    $level = get_post_meta($post->ID, '_tm_level', true);
    $logo = get_post_meta($post->ID, '_tm_logo', true);
    $banner = get_post_meta($post->ID, '_tm_banner', true);
    $website = get_post_meta($post->ID, '_tm_website', true);
    ?>
    <p><label>Name:<br><input type="text" name="tm_name" value="<?php echo esc_attr($name); ?>" style="width:100%;" /></label></p>
    <p><label>Company:<br><input type="text" name="tm_company" value="<?php echo esc_attr($company); ?>" style="width:100%;" /></label></p>
    <p><label>Sponsorship Level:<br>
        <select name="tm_level">
            <option value="Platinum" <?php selected($level, 'Platinum'); ?>>Platinum</option>
            <option value="Gold" <?php selected($level, 'Gold'); ?>>Gold</option>
            <option value="Silver" <?php selected($level, 'Silver'); ?>>Silver</option>
            <option value="Bronze" <?php selected($level, 'Bronze'); ?>>Bronze</option>
        </select>
    </label></p>

    <p>
        <label for="tm_logo">Logo:</label><br>
        <input type="text" id="tm_logo" name="tm_logo" value="<?php echo esc_attr($logo); ?>" style="width:80%;" />
        <button type="button" class="button tm-media-upload" data-target="tm_logo">Select Logo</button><br>
        <?php 
        $logo_url = tm_get_sponsor_image_url($logo);
        if ($logo_url): 
        ?>
            <img src="<?php echo esc_url($logo_url); ?>" alt="Logo Preview" style="max-width:150px; margin-top:10px;" />
        <?php endif; ?>
    </p>

    <p>
        <label for="tm_banner">Banner:</label><br>
        <input type="text" id="tm_banner" name="tm_banner" value="<?php echo esc_attr($banner); ?>" style="width:80%;" />
        <button type="button" class="button tm-media-upload" data-target="tm_banner">Select Banner</button><br>
        <?php 
        $banner_url = tm_get_sponsor_image_url($banner);
        if ($banner_url): 
        ?>
            <img src="<?php echo esc_url($banner_url); ?>" alt="Banner Preview" style="max-width:150px; margin-top:10px;" />
        <?php endif; ?>
    </p>

    <p><label>Website URL:<br><input type="url" name="tm_website" value="<?php echo esc_attr($website); ?>" style="width:100%;" /></label></p>
    <?php
}

function tm_save_sponsor_meta($post_id) {
    if (!isset($_POST['tm_sponsor_nonce']) || !wp_verify_nonce($_POST['tm_sponsor_nonce'], 'tm_save_sponsor_meta')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if ('sponsor' !== $_POST['post_type']) return;
    if (!current_user_can('edit_post', $post_id)) return;

    update_post_meta($post_id, '_tm_name', sanitize_text_field($_POST['tm_name']));
    update_post_meta($post_id, '_tm_company', sanitize_text_field($_POST['tm_company']));
    update_post_meta($post_id, '_tm_level', sanitize_text_field($_POST['tm_level']));
    
    // Save logo - can be attachment ID or URL
    $logo = sanitize_text_field($_POST['tm_logo']);
    if (is_numeric($logo)) {
        update_post_meta($post_id, '_tm_logo', intval($logo));
    } else {
        update_post_meta($post_id, '_tm_logo', esc_url_raw($logo));
    }
    
    // Save banner - can be attachment ID or URL
    $banner = sanitize_text_field($_POST['tm_banner']);
    if (is_numeric($banner)) {
        update_post_meta($post_id, '_tm_banner', intval($banner));
    } else {
        update_post_meta($post_id, '_tm_banner', esc_url_raw($banner));
    }
    
    update_post_meta($post_id, '_tm_website', esc_url_raw($_POST['tm_website']));

    remove_action('save_post', 'tm_save_sponsor_meta');
    wp_update_post(array('ID' => $post_id, 'post_title' => sanitize_text_field($_POST['tm_name'])));
    add_action('save_post', 'tm_save_sponsor_meta');
}
add_action('save_post', 'tm_save_sponsor_meta');

function tm_sponsor_columns($columns) {
    return array(
        'cb' => '<input type="checkbox" />',
        'title' => 'Name',
        'company' => 'Company',
        'level' => 'Level',
        'logo' => 'Logo',
        'banner' => 'Banner',
        'website' => 'Website'
    );
}
add_filter('manage_sponsor_posts_columns', 'tm_sponsor_columns');

function tm_sponsor_custom_column($column, $post_id) {
    switch ($column) {
        case 'company':
            echo esc_html(get_post_meta($post_id, '_tm_company', true));
            break;
        case 'level':
            echo esc_html(get_post_meta($post_id, '_tm_level', true));
            break;
        case 'logo':
            $logo = get_post_meta($post_id, '_tm_logo', true);
            $logo_url = tm_get_sponsor_image_url($logo);
            if ($logo_url) echo '<img src="' . esc_url($logo_url) . '" style="max-width:50px;" />';
            break;
        case 'banner':
            $banner = get_post_meta($post_id, '_tm_banner', true);
            $banner_url = tm_get_sponsor_image_url($banner);
            if ($banner_url) echo '<img src="' . esc_url($banner_url) . '" style="max-width:50px;" />';
            break;
        case 'website':
            $url = get_post_meta($post_id, '_tm_website', true);
            if ($url) echo '<a href="' . esc_url($url) . '" target="_blank">Visit</a>';
            break;
    }
}
add_action('manage_sponsor_posts_custom_column', 'tm_sponsor_custom_column', 10, 2);
?>
