<?php
/**
 * Custom Post Type: Contributor
 * Description: Defines the Contributor CPT with custom fields and admin UI.
 */

defined('ABSPATH') || exit;

function stagekitwp_register_contributor_cpt() {
    $labels = array(
        'name' => 'Contributors',
        'singular_name' => 'Contributor',
        'menu_name' => 'Contributors',
        'name_admin_bar' => 'Contributor',
        'add_new' => 'Add New',
        'add_new_item' => 'Add New Contributor',
        'new_item' => 'New Contributor',
        'edit_item' => 'Edit Contributor',
        'view_item' => 'View Contributor',
        'all_items' => 'Contributors',
        'search_items' => 'Search Contributors',
        'not_found' => 'No contributors found.',
        'not_found_in_trash' => 'No contributors found in Trash.'
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => false,
        'menu_position' => 5,
        'menu_icon' => 'dashicons-groups',
        'supports' => array(''),
        'show_in_menu' => false,
        'show_in_rest' => true,  // Required for Gutenberg block dropdowns
        'capability_type' => 'post',
        'map_meta_cap' => true,
    );

    register_post_type('contributor', $args);
}
add_action('init', 'stagekitwp_register_contributor_cpt');

function stagekitwp_add_contributor_meta_boxes() {
    add_meta_box('stagekitwp_contributor_details', 'Contributor Details', 'stagekitwp_render_contributor_meta_box', 'contributor', 'normal', 'default');
}
add_action('add_meta_boxes', 'stagekitwp_add_contributor_meta_boxes');

function stagekitwp_render_contributor_meta_box($post) {
    wp_nonce_field('stagekitwp_save_contributor_meta', 'stagekitwp_contributor_nonce');

    $name = get_post_meta($post->ID, '_stagekitwp_name', true);
    $company = get_post_meta($post->ID, '_stagekitwp_company', true);
    $level = get_post_meta($post->ID, '_stagekitwp_level', true);
    ?>
    <p><label>Name:<br><input type="text" name="stagekitwp_name" value="<?php echo esc_attr($name); ?>" style="width:100%;" /></label></p>
    <p><label>Company:<br><input type="text" name="stagekitwp_company" value="<?php echo esc_attr($company); ?>" style="width:100%;" /></label></p>
    <p><label>Contribution Level:<br>
        <select name="stagekitwp_level">
            <option value="Platinum" <?php selected($level, 'Platinum'); ?>>Platinum</option>
            <option value="Gold" <?php selected($level, 'Gold'); ?>>Gold</option>
            <option value="Silver" <?php selected($level, 'Silver'); ?>>Silver</option>
            <option value="Bronze" <?php selected($level, 'Bronze'); ?>>Bronze</option>
        </select>
    </label></p>
    <?php
}

function stagekitwp_save_contributor_meta($post_id) {
    $nonce = isset($_POST['stagekitwp_contributor_nonce']) ? sanitize_text_field(wp_unslash($_POST['stagekitwp_contributor_nonce'])) : '';
    if (!$nonce || !wp_verify_nonce($nonce, 'stagekitwp_save_contributor_meta')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    $post_type = isset($_POST['post_type']) ? sanitize_key(wp_unslash($_POST['post_type'])) : '';
    if ('contributor' !== $post_type) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $name = isset($_POST['stagekitwp_name']) ? sanitize_text_field(wp_unslash($_POST['stagekitwp_name'])) : '';
    $company = isset($_POST['stagekitwp_company']) ? sanitize_text_field(wp_unslash($_POST['stagekitwp_company'])) : '';
    $level = isset($_POST['stagekitwp_level']) ? sanitize_text_field(wp_unslash($_POST['stagekitwp_level'])) : '';

    update_post_meta($post_id, '_stagekitwp_name', $name);
    update_post_meta($post_id, '_stagekitwp_company', $company);
    update_post_meta($post_id, '_stagekitwp_level', $level);

    remove_action('save_post', 'stagekitwp_save_contributor_meta');
    wp_update_post(array('ID' => $post_id, 'post_title' => $name));
    add_action('save_post', 'stagekitwp_save_contributor_meta');
}
add_action('save_post', 'stagekitwp_save_contributor_meta');

function stagekitwp_contributor_columns($columns) {
    return array(
        'cb' => '<input type="checkbox" />',
        'title' => 'Name',
        'company' => 'Company',
        'level' => 'Contribution Level'
    );
}
add_filter('manage_contributor_posts_columns', 'stagekitwp_contributor_columns');

function stagekitwp_contributor_custom_column($column, $post_id) {
    switch ($column) {
        case 'company':
            echo esc_html(get_post_meta($post_id, '_stagekitwp_company', true));
            break;
        case 'level':
            echo esc_html(get_post_meta($post_id, '_stagekitwp_level', true));
            break;
    }
}
add_action('manage_contributor_posts_custom_column', 'stagekitwp_contributor_custom_column', 10, 2);
