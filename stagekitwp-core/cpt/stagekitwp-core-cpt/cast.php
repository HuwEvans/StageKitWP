<?php
// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Register Cast Custom Post Type
 */
function stagekitwp_register_cast_cpt() {
    $labels = array(
        'name' => 'Cast',
        'singular_name' => 'Cast Member',
        'menu_name' => 'Cast',
        'name_admin_bar' => 'Cast Member',
        'add_new' => 'Add New',
        'add_new_item' => 'Add New Cast Member',
        'new_item' => 'New Cast Member',
        'edit_item' => 'Edit Cast Member',
        'view_item' => 'View Cast Member',
        'all_items' => 'Cast Members',
        'search_items' => 'Search Cast',
        'not_found' => 'No cast members found.',
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'menu_position' => 22,
        'menu_icon' => 'dashicons-groups',
        'supports' => array(''),
        'has_archive' => true,
        'show_in_menu' => false,
        'capability_type' => 'post',
        'map_meta_cap'  => true,
        'show_in_rest'  => true,  // Required for Gutenberg block dropdowns
    );

    register_post_type('cast', $args);
}
add_action('init', 'stagekitwp_register_cast_cpt');

/**
 * Add Meta Boxes for Cast
 */
function stagekitwp_add_cast_meta_boxes() {
    add_meta_box('stagekitwp_cast_details', 'Cast Details', 'stagekitwp_render_cast_meta_box', 'cast', 'normal', 'default');
}
add_action('add_meta_boxes', 'stagekitwp_add_cast_meta_boxes');

/**
 * Render Cast Meta Box
 */
function stagekitwp_render_cast_meta_box($post) {
    wp_nonce_field('stagekitwp_save_cast_meta', 'stagekitwp_cast_meta_nonce');

    $fields = [
        'character_name' => '',
        'actor_name' => '',
        'picture' => '',
        'show' => '',
        'bio' => ''
    ];

    foreach ($fields as $key => $default) {
        $fields[$key] = get_post_meta($post->ID, '_stagekitwp_cast_' . $key, true);
    }

    $shows = get_posts(['post_type' => 'show', 'numberposts' => -1]);

    echo '<p><label>Character Name:<br><input type="text" name="stagekitwp_cast_character_name" value="' . esc_attr($fields['character_name']) . '" class="widefat" /></label></p>';
    echo '<p><label>Actor Name:<br><input type="text" name="stagekitwp_cast_actor_name" value="' . esc_attr($fields['actor_name']) . '" class="widefat" /></label></p>';

	echo '<p><label>Picture:<br>';
	echo '<input type="text" name="stagekitwp_cast_picture" id="stagekitwp_cast_picture" value="' . esc_attr($fields['picture']) . '" class="widefat" />';
	echo '<button type="button" class="button stagekitwp-media-button" data-target="stagekitwp_cast_picture" data-preview="stagekitwp_cast_picture_preview">Select Image</button>';
	echo '</label></p>';
	
	// Convert attachment ID to URL for display
	$picture_url = function_exists('stagekitwp_get_image_url') ? stagekitwp_get_image_url($fields['picture']) : $fields['picture'];
	echo '<div><img id="stagekitwp_cast_picture_preview" src="' . esc_url($picture_url) . '" style="max-width:150px;' . ($picture_url ? '' : ' display:none;') . '" /></div>';

    echo '<p><label>Bio:<br><textarea name="stagekitwp_cast_bio" class="widefat" rows="4">' . esc_textarea($fields['bio']) . '</textarea></label></p>';

    echo '<p><label>Show:<br><select name="stagekitwp_cast_show">';
    foreach ($shows as $show) {
        echo '<option value="' . esc_attr($show->ID) . '" ' . selected($fields['show'], $show->ID, false) . '>' . esc_html($show->post_title) . '</option>';
    }
    echo '</select></label></p>';
}

/**
 * Save Cast Meta
 */
function stagekitwp_save_cast_meta($post_id) {
    $nonce = isset($_POST['stagekitwp_cast_meta_nonce']) ? sanitize_text_field(wp_unslash($_POST['stagekitwp_cast_meta_nonce'])) : '';
    if (!$nonce || !wp_verify_nonce($nonce, 'stagekitwp_save_cast_meta')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    $post_type = isset($_POST['post_type']) ? sanitize_key(wp_unslash($_POST['post_type'])) : '';
    if ('cast' !== $post_type || !current_user_can('edit_post', $post_id)) return;

    $fields = ['character_name', 'actor_name', 'picture', 'show', 'bio'];

    foreach ($fields as $field) {
        if (isset($_POST['stagekitwp_cast_' . $field])) {
            $raw = wp_unslash($_POST['stagekitwp_cast_' . $field]);
            if ('show' === $field) {
                update_post_meta($post_id, '_stagekitwp_cast_' . $field, absint($raw));
            } elseif ('bio' === $field) {
                update_post_meta($post_id, '_stagekitwp_cast_' . $field, sanitize_textarea_field($raw));
            } else {
                update_post_meta($post_id, '_stagekitwp_cast_' . $field, sanitize_text_field($raw));
            }
        }
    }

    remove_action('save_post', 'stagekitwp_save_cast_meta');
    $character_name = isset($_POST['stagekitwp_cast_character_name']) ? sanitize_text_field(wp_unslash($_POST['stagekitwp_cast_character_name'])) : '';
    wp_update_post(['ID' => $post_id, 'post_title' => $character_name]);
    add_action('save_post', 'stagekitwp_save_cast_meta');
}
add_action('save_post', 'stagekitwp_save_cast_meta');

/**
 * Customize Cast List Columns
 */
function stagekitwp_cast_columns($columns) {
    return array(
        'cb' => '<input type="checkbox" />',
        'title' => 'Character Name',
        'actor_name' => 'Actor Name',
        'picture' => 'Picture',
        'show' => 'Show'
    );
}
add_filter('manage_cast_posts_columns', 'stagekitwp_cast_columns');

/**
 * Render Custom Columns
 */
function stagekitwp_cast_custom_column($column, $post_id) {
    switch ($column) {
        case 'actor_name':
            echo esc_html(get_post_meta($post_id, '_stagekitwp_cast_actor_name', true));
            break;
        case 'picture':
            $img = get_post_meta($post_id, '_stagekitwp_cast_picture', true);
            if ($img) {
                // Convert attachment ID to URL for display
                $picture_url = function_exists('stagekitwp_get_image_url') ? stagekitwp_get_image_url($img) : $img;
                echo '<img src="' . esc_url($picture_url) . '" style="max-width:50px;" />';
            }
            break;
        case 'show':
            $show_id = get_post_meta($post_id, '_stagekitwp_cast_show', true);
            echo $show_id ? get_the_title($show_id) : '';
            break;
    }
}
add_action('manage_cast_posts_custom_column', 'stagekitwp_cast_custom_column', 10, 2);
