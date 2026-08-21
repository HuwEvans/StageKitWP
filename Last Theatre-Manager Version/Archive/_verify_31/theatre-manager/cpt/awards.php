<?php
// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Register Awards Custom Post Type
 */
function tm_register_award_cpt() {
    $labels = array(
        'name' => 'Awards',
        'singular_name' => 'Award',
        'menu_name' => 'Awards',
        'name_admin_bar' => 'Award',
        'add_new' => 'Add New',
        'add_new_item' => 'Add New Award',
        'new_item' => 'New Award',
        'edit_item' => 'Edit Award',
        'view_item' => 'View Award',
        'all_items' => 'All Awards',
        'search_items' => 'Search Awards',
        'not_found' => 'No awards found.',
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'menu_position' => 22,
        'menu_icon' => 'dashicons-awards',
        'supports' => array(''),
        'has_archive' => false,
        'show_in_menu' => 'theatre-manager',
        'show_in_rest' => false,
    );

    register_post_type('award', $args);
}
add_action('init', 'tm_register_award_cpt');

/**
 * Add meta box for Awards
 */
function tm_add_award_meta_boxes() {
    add_meta_box('tm_award_details', 'Award Details', 'tm_render_award_meta_box', 'award', 'normal', 'default');
}
add_action('add_meta_boxes', 'tm_add_award_meta_boxes');

/**
 * Render Award meta box
 */
function tm_render_award_meta_box($post) {
    wp_nonce_field('tm_save_award_meta', 'tm_award_nonce');

    $award_id = get_post_meta($post->ID, '_tm_award_id', true);
    $show_id = get_post_meta($post->ID, '_tm_award_show_id', true);
    $category = get_post_meta($post->ID, '_tm_award_category', true);
    $award_name = get_post_meta($post->ID, '_tm_award_name', true);
    $recipient = get_post_meta($post->ID, '_tm_award_recipient', true);
    $status = get_post_meta($post->ID, '_tm_award_status', true);

    $categories = array('Musical', 'Drama', 'Comedy');
    $statuses = array('Nominated', 'THEA Winner');

    $shows = get_posts(array(
        'post_type' => 'show',
        'numberposts' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
    ));

    echo '<p><label>Award ID:<br><input type="text" name="tm_award_id" value="' . esc_attr($award_id) . '" class="widefat" /></label></p>';

    echo '<p><label>Show ID:<br><select name="tm_award_show_id" class="widefat">';
    echo '<option value="">Select a Show</option>';
    foreach ($shows as $show) {
        $season_id = get_post_meta($show->ID, '_tm_show_season', true);
        $show_label = $show->post_title;
        if (!empty($season_id)) {
            $show_label .= ' (' . get_the_title($season_id) . ')';
        }
        echo '<option value="' . esc_attr($show->ID) . '" ' . selected($show_id, $show->ID, false) . '>' . esc_html($show_label) . '</option>';
    }
    echo '</select></label></p>';

    echo '<p><label>Award Category:<br><select name="tm_award_category" class="widefat">';
    foreach ($categories as $cat) {
        echo '<option value="' . esc_attr($cat) . '" ' . selected($category, $cat, false) . '>' . esc_html($cat) . '</option>';
    }
    echo '</select></label></p>';

    echo '<p><label>Award Name:<br><input type="text" name="tm_award_name" value="' . esc_attr($award_name) . '" class="widefat" /></label></p>';
    echo '<p><label>Award Recipient:<br><input type="text" name="tm_award_recipient" value="' . esc_attr($recipient) . '" class="widefat" /></label></p>';

    echo '<p><label>Status:<br><select name="tm_award_status" class="widefat">';
    foreach ($statuses as $s) {
        echo '<option value="' . esc_attr($s) . '" ' . selected($status, $s, false) . '>' . esc_html($s) . '</option>';
    }
    echo '</select></label></p>';
}

/**
 * Save Award meta data
 */
function tm_save_award_meta($post_id) {
    if (!isset($_POST['tm_award_nonce']) || !wp_verify_nonce($_POST['tm_award_nonce'], 'tm_save_award_meta')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!isset($_POST['post_type']) || 'award' !== $_POST['post_type']) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $valid_categories = array('Musical', 'Drama', 'Comedy');
    $valid_statuses = array('Nominated', 'THEA Winner');

    $award_id = sanitize_text_field($_POST['tm_award_id'] ?? '');
    $show_id = isset($_POST['tm_award_show_id']) ? intval($_POST['tm_award_show_id']) : 0;
    $category = sanitize_text_field($_POST['tm_award_category'] ?? '');
    $award_name = sanitize_text_field($_POST['tm_award_name'] ?? '');
    $recipient = sanitize_text_field($_POST['tm_award_recipient'] ?? '');
    $status = sanitize_text_field($_POST['tm_award_status'] ?? '');

    if ($show_id > 0 && get_post_type($show_id) !== 'show') {
        $show_id = 0;
    }

    if (!in_array($category, $valid_categories, true)) {
        $category = 'Drama';
    }

    if (!in_array($status, $valid_statuses, true)) {
        $status = 'Nominated';
    }

    update_post_meta($post_id, '_tm_award_id', $award_id);
    update_post_meta($post_id, '_tm_award_show_id', $show_id);
    update_post_meta($post_id, '_tm_award_category', $category);
    update_post_meta($post_id, '_tm_award_name', $award_name);
    update_post_meta($post_id, '_tm_award_recipient', $recipient);
    update_post_meta($post_id, '_tm_award_status', $status);

    remove_action('save_post', 'tm_save_award_meta');
    wp_update_post(array(
        'ID' => $post_id,
        'post_title' => $award_name,
    ));
    add_action('save_post', 'tm_save_award_meta');
}
add_action('save_post', 'tm_save_award_meta');

/**
 * Customize Award list columns
 */
function tm_award_columns($columns) {
    return array(
        'cb' => '<input type="checkbox" />',
        'title' => 'Award Name',
        'award_id' => 'Award ID',
        'category' => 'Category',
        'recipient' => 'Recipient',
        'status' => 'Status',
        'show' => 'Show',
        'season' => 'Season',
    );
}
add_filter('manage_award_posts_columns', 'tm_award_columns');

/**
 * Render custom Award columns
 */
function tm_award_custom_column($column, $post_id) {
    switch ($column) {
        case 'award_id':
            echo esc_html(get_post_meta($post_id, '_tm_award_id', true));
            break;
        case 'category':
            echo esc_html(get_post_meta($post_id, '_tm_award_category', true));
            break;
        case 'recipient':
            echo esc_html(get_post_meta($post_id, '_tm_award_recipient', true));
            break;
        case 'status':
            echo esc_html(get_post_meta($post_id, '_tm_award_status', true));
            break;
        case 'show':
            $show_id = intval(get_post_meta($post_id, '_tm_award_show_id', true));
            if ($show_id > 0) {
                echo esc_html(get_the_title($show_id));
            }
            break;
        case 'season':
            $show_id = intval(get_post_meta($post_id, '_tm_award_show_id', true));
            if ($show_id > 0) {
                $season_id = intval(get_post_meta($show_id, '_tm_show_season', true));
                if ($season_id > 0) {
                    echo esc_html(get_the_title($season_id));
                }
            }
            break;
    }
}
add_action('manage_award_posts_custom_column', 'tm_award_custom_column', 10, 2);
