<?php
/**
 * Custom Post Type: Contributor
 * Description: Defines the Contributor CPT with custom fields and admin UI.
 */

defined('ABSPATH') || exit;

function tm_register_contributor_cpt() {
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
        'all_items' => 'All Contributors',
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
		'show_in_menu' => 'theatre-manager',
        'show_in_rest' => false,
    );

    register_post_type('contributor', $args);
}
add_action('init', 'tm_register_contributor_cpt');

function tm_add_contributor_meta_boxes() {
    add_meta_box('tm_contributor_details', 'Contributor Details', 'tm_render_contributor_meta_box', 'contributor', 'normal', 'default');
}
add_action('add_meta_boxes', 'tm_add_contributor_meta_boxes');

function tm_render_contributor_meta_box($post) {
    wp_nonce_field('tm_save_contributor_meta', 'tm_contributor_nonce');

    $name = get_post_meta($post->ID, '_tm_name', true);
    $company = get_post_meta($post->ID, '_tm_company', true);
    $level = get_post_meta($post->ID, '_tm_level', true);
    ?>
    <p><label>Name:<br><input type="text" name="tm_name" value="<?php echo esc_attr($name); ?>" style="width:100%;" /></label></p>
    <p><label>Company:<br><input type="text" name="tm_company" value="<?php echo esc_attr($company); ?>" style="width:100%;" /></label></p>
    <p><label>Contribution Level:<br>
        <select name="tm_level">
            <option value="Platinum" <?php selected($level, 'Platinum'); ?>>Platinum</option>
            <option value="Gold" <?php selected($level, 'Gold'); ?>>Gold</option>
            <option value="Silver" <?php selected($level, 'Silver'); ?>>Silver</option>
            <option value="Bronze" <?php selected($level, 'Bronze'); ?>>Bronze</option>
        </select>
    </label></p>
    <?php
}

function tm_save_contributor_meta($post_id) {
    if (!isset($_POST['tm_contributor_nonce']) || !wp_verify_nonce($_POST['tm_contributor_nonce'], 'tm_save_contributor_meta')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if ('contributor' !== $_POST['post_type']) return;
    if (!current_user_can('edit_post', $post_id)) return;

    update_post_meta($post_id, '_tm_name', sanitize_text_field($_POST['tm_name']));
    update_post_meta($post_id, '_tm_company', sanitize_text_field($_POST['tm_company']));
    update_post_meta($post_id, '_tm_level', sanitize_text_field($_POST['tm_level']));

    remove_action('save_post', 'tm_save_contributor_meta');
    wp_update_post(array('ID' => $post_id, 'post_title' => sanitize_text_field($_POST['tm_name'])));
    add_action('save_post', 'tm_save_contributor_meta');
}
add_action('save_post', 'tm_save_contributor_meta');

function tm_contributor_columns($columns) {
    return array(
        'cb' => '<input type="checkbox" />',
        'title' => 'Name',
        'company' => 'Company',
        'level' => 'Contribution Level'
    );
}
add_filter('manage_contributor_posts_columns', 'tm_contributor_columns');

function tm_contributor_custom_column($column, $post_id) {
    switch ($column) {
        case 'company':
            echo esc_html(get_post_meta($post_id, '_tm_company', true));
            break;
        case 'level':
            echo esc_html(get_post_meta($post_id, '_tm_level', true));
            break;
    }
}
add_action('manage_contributor_posts_custom_column', 'tm_contributor_custom_column', 10, 2);
