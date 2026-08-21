<?php
// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Register Cast Custom Post Type
 */
function tm_register_cast_cpt() {
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
        'all_items' => 'All Cast Members',
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
        'show_in_menu' => 'theatre-manager',
    );

    register_post_type('cast', $args);
}
add_action('init', 'tm_register_cast_cpt');

/**
 * Add Meta Boxes for Cast
 */
function tm_add_cast_meta_boxes() {
    add_meta_box('tm_cast_details', 'Cast Details', 'tm_render_cast_meta_box', 'cast', 'normal', 'default');
}
add_action('add_meta_boxes', 'tm_add_cast_meta_boxes');

/**
 * Render Cast Meta Box
 */
function tm_render_cast_meta_box($post) {
    wp_nonce_field('tm_save_cast_meta', 'tm_cast_meta_nonce');

    $fields = [
        'character_name' => '',
        'actor_name' => '',
        'picture' => '',
        'show' => ''
    ];

    foreach ($fields as $key => $default) {
        $fields[$key] = get_post_meta($post->ID, '_tm_cast_' . $key, true);
    }

    $shows = get_posts(['post_type' => 'show', 'numberposts' => -1]);

    echo '<p><label>Character Name:<br><input type="text" name="tm_cast_character_name" value="' . esc_attr($fields['character_name']) . '" class="widefat" /></label></p>';
    echo '<p><label>Actor Name:<br><input type="text" name="tm_cast_actor_name" value="' . esc_attr($fields['actor_name']) . '" class="widefat" /></label></p>';

	echo '<p><label>Picture:<br>';
	echo '<input type="text" name="tm_cast_picture" id="tm_cast_picture" value="' . esc_attr($fields['picture']) . '" class="widefat" />';
	echo '<button type="button" class="button tm-media-button" data-target="tm_cast_picture" data-preview="tm_cast_picture_preview">Select Image</button>';
	echo '</label></p>';
	
	// Convert attachment ID to URL for display
	$picture_url = function_exists('tm_get_image_url') ? tm_get_image_url($fields['picture']) : $fields['picture'];
	echo '<div><img id="tm_cast_picture_preview" src="' . esc_url($picture_url) . '" style="max-width:150px;' . ($picture_url ? '' : ' display:none;') . '" /></div>';

    echo '<p><label>Show:<br><select name="tm_cast_show">';
    foreach ($shows as $show) {
        echo '<option value="' . esc_attr($show->ID) . '" ' . selected($fields['show'], $show->ID, false) . '>' . esc_html($show->post_title) . '</option>';
    }
    echo '</select></label></p>';
}

/**
 * Save Cast Meta
 */
function tm_save_cast_meta($post_id) {
    if (!isset($_POST['tm_cast_meta_nonce']) || !wp_verify_nonce($_POST['tm_cast_meta_nonce'], 'tm_save_cast_meta')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if ('cast' !== $_POST['post_type'] || !current_user_can('edit_post', $post_id)) return;

    $fields = ['character_name', 'actor_name', 'picture', 'show'];

    foreach ($fields as $field) {
        if (isset($_POST['tm_cast_' . $field])) {
            update_post_meta($post_id, '_tm_cast_' . $field, sanitize_text_field($_POST['tm_cast_' . $field]));
        }
    }

    remove_action('save_post', 'tm_save_cast_meta');
    wp_update_post(['ID' => $post_id, 'post_title' => sanitize_text_field($_POST['tm_cast_character_name'])]);
    add_action('save_post', 'tm_save_cast_meta');
}
add_action('save_post', 'tm_save_cast_meta');

/**
 * Customize Cast List Columns
 */
function tm_cast_columns($columns) {
    return array(
        'cb' => '<input type="checkbox" />',
        'title' => 'Character Name',
        'actor_name' => 'Actor Name',
        'picture' => 'Picture',
        'show' => 'Show'
    );
}
add_filter('manage_cast_posts_columns', 'tm_cast_columns');

/**
 * Render Custom Columns
 */
function tm_cast_custom_column($column, $post_id) {
    switch ($column) {
        case 'actor_name':
            echo esc_html(get_post_meta($post_id, '_tm_cast_actor_name', true));
            break;
        case 'picture':
            $img = get_post_meta($post_id, '_tm_cast_picture', true);
            if ($img) {
                // Convert attachment ID to URL for display
                $picture_url = function_exists('tm_get_image_url') ? tm_get_image_url($img) : $img;
                echo '<img src="' . esc_url($picture_url) . '" style="max-width:50px;" />';
            }
            break;
        case 'show':
            $show_id = get_post_meta($post_id, '_tm_cast_show', true);
            echo $show_id ? get_the_title($show_id) : '';
            break;
    }
}
add_action('manage_cast_posts_custom_column', 'tm_cast_custom_column', 10, 2);
