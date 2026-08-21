<?php

/**
 * Get image URL from attachment ID or return the value if it's already a URL
 * Handles both: direct URLs and WordPress attachment IDs
 * 
 * @param int|string $value Either an attachment ID or a URL
 * @return string The image URL, or empty string if not found
 */
function tm_get_season_image_url($value) {
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

function tm_register_season_cpt() {
    $labels = array(
        'name' => 'Seasons',
        'singular_name' => 'Season',
        'menu_name' => 'Seasons',
        'name_admin_bar' => 'Season',
        'add_new' => 'Add New',
        'add_new_item' => 'Add New Season',
        'new_item' => 'New Season',
        'edit_item' => 'Edit Season',
        'view_item' => 'View Season',
        'all_items' => 'All Seasons',
        'search_items' => 'Search Seasons',
        'not_found' => 'No seasons found.',
    );
    $args = array(
        'labels' => $labels,
        'public' => true,
        'show_in_menu' => 'theatre-manager',
        'supports' => array(''),
        'menu_position' => 5,
        'menu_icon' => 'dashicons-calendar-alt',
        'has_archive' => false,
        'rewrite' => array('slug' => 'season'),
        'show_in_rest' => true,
    );
    register_post_type('season', $args);
}
add_action('init', 'tm_register_season_cpt');

function tm_add_season_meta_boxes() {
    add_meta_box('tm_season_fields', 'Season Details', 'tm_render_season_fields', 'season', 'normal', 'default');
}
add_action('add_meta_boxes', 'tm_add_season_meta_boxes');

function tm_render_season_fields($post) {
    wp_nonce_field('tm_save_season_fields', 'tm_season_nonce');

    $name = get_post_meta($post->ID, '_tm_season_name', true);
    $start_date = get_post_meta($post->ID, '_tm_season_start_date', true);
    $end_date = get_post_meta($post->ID, '_tm_season_end_date', true);
    $image_front = get_post_meta($post->ID, '_tm_season_image_front', true);
    $image_back = get_post_meta($post->ID, '_tm_season_image_back', true);
    $social_banner = get_post_meta($post->ID, '_tm_season_social_banner', true);
    $sm_square = get_post_meta($post->ID, '_tm_season_sm_square', true);
    $sm_portrait = get_post_meta($post->ID, '_tm_season_sm_portrait', true);
    $is_current = get_post_meta($post->ID, '_tm_season_is_current', true);
    $is_upcoming = get_post_meta($post->ID, '_tm_season_is_upcoming', true);

    // Convert attachment IDs to URLs for display
    $image_front_url = tm_get_season_image_url($image_front);
    $image_back_url = tm_get_season_image_url($image_back);
    $social_banner_url = tm_get_season_image_url($social_banner);
    $sm_square_url = tm_get_season_image_url($sm_square);
    $sm_portrait_url = tm_get_season_image_url($sm_portrait);

    echo '<p><label>Name:<br><input type="text" name="tm_season_name" value="' . esc_attr($name) . '" class="widefat" /></label></p>';
    echo '<p><label>Start Date:<br><input type="date" name="tm_season_start_date" value="' . esc_attr($start_date) . '" class="widefat tm-datepicker" /></label></p>';
    echo '<p><label>End Date:<br><input type="date" name="tm_season_end_date" value="' . esc_attr($end_date) . '" class="widefat tm-datepicker" /></label></p>';

    echo '<p>';
    echo '<label><input type="checkbox" name="tm_season_is_current" value="1" ' . checked($is_current, 1, false) . ' /> Is Current Season</label>';
    echo '</p>';
    
    echo '<p>';
    echo '<label><input type="checkbox" name="tm_season_is_upcoming" value="1" ' . checked($is_upcoming, 1, false) . ' /> Is Upcoming Season</label>';
    echo '</p>';

	echo '<label for="tm_season_image_front">3-up Front Image:</label>';
	echo '<input type="text" name="tm_season_image_front" id="tm_season_image_front" value="' . esc_attr($image_front) . '" class="widefat" />';
	echo '<button type="button" class="button tm-media-button" data-target="tm_season_image_front" data-preview="tm_season_image_front_preview">Select Image</button>';
	echo '<div><img id="tm_season_image_front_preview" src="' . esc_url($image_front_url) . '" style="max-width:150px;' . ($image_front_url ? '' : ' display:none;') . '" /></div>';
	
	echo '<label for="tm_season_image_back">3-up Back Image:</label>';
	echo '<input type="text" name="tm_season_image_back" id="tm_season_image_back" value="' . esc_attr($image_back) . '" class="widefat" />';
	echo '<button type="button" class="button tm-media-button" data-target="tm_season_image_back" data-preview="tm_season_image_back_preview">Select Image</button>';
	echo '<div><img id="tm_season_image_back_preview" src="' . esc_url($image_back_url) . '" style="max-width:150px;' . ($image_back_url ? '' : ' display:none;') . '" /></div>';
	
	echo '<label for="tm_season_social_banner">Website Banner:</label>';
	echo '<input type="text" name="tm_season_social_banner" id="tm_season_social_banner" value="' . esc_attr($social_banner) . '" class="widefat" />';
	echo '<button type="button" class="button tm-media-button" data-target="tm_season_social_banner" data-preview="tm_season_social_banner_preview">Select Image</button>';
	echo '<div><img id="tm_season_social_banner_preview" src="' . esc_url($social_banner_url) . '" style="max-width:150px;' . ($social_banner_url ? '' : ' display:none;') . '" /></div>';

	echo '<label for="tm_season_sm_square">Social Media Square:</label>';
	echo '<input type="text" name="tm_season_sm_square" id="tm_season_sm_square" value="' . esc_attr($sm_square) . '" class="widefat" />';
	echo '<button type="button" class="button tm-media-button" data-target="tm_season_sm_square" data-preview="tm_season_sm_square_preview">Select Image</button>';
	echo '<div><img id="tm_season_sm_square_preview" src="' . esc_url($sm_square_url) . '" style="max-width:150px;' . ($sm_square_url ? '' : ' display:none;') . '" /></div>';

	echo '<label for="tm_season_sm_portrait">Social Media Portrait:</label>';
	echo '<input type="text" name="tm_season_sm_portrait" id="tm_season_sm_portrait" value="' . esc_attr($sm_portrait) . '" class="widefat" />';
	echo '<button type="button" class="button tm-media-button" data-target="tm_season_sm_portrait" data-preview="tm_season_sm_portrait_preview">Select Image</button>';
	echo '<div><img id="tm_season_sm_portrait_preview" src="' . esc_url($sm_portrait_url) . '" style="max-width:150px;' . ($sm_portrait_url ? '' : ' display:none;') . '" /></div>';

}

function tm_save_season_fields($post_id) {
    if (!isset($_POST['tm_season_nonce']) || !wp_verify_nonce($_POST['tm_season_nonce'], 'tm_save_season_fields')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if ('season' !== $_POST['post_type'] || !current_user_can('edit_post', $post_id)) return;

    $name = sanitize_text_field($_POST['tm_season_name']);
    update_post_meta($post_id, '_tm_season_name', $name);
    update_post_meta($post_id, '_tm_season_start_date', sanitize_text_field($_POST['tm_season_start_date']));
    update_post_meta($post_id, '_tm_season_end_date', sanitize_text_field($_POST['tm_season_end_date']));
    update_post_meta($post_id, '_tm_season_image_front', esc_url_raw($_POST['tm_season_image_front']));
    update_post_meta($post_id, '_tm_season_image_back', esc_url_raw($_POST['tm_season_image_back']));
    update_post_meta($post_id, '_tm_season_social_banner', esc_url_raw($_POST['tm_season_social_banner']));
    update_post_meta($post_id, '_tm_season_sm_square', esc_url_raw($_POST['tm_season_sm_square'] ?? ''));
    update_post_meta($post_id, '_tm_season_sm_portrait', esc_url_raw($_POST['tm_season_sm_portrait'] ?? ''));
    update_post_meta($post_id, '_tm_season_is_current', isset($_POST['tm_season_is_current']) ? 1 : 0);
    update_post_meta($post_id, '_tm_season_is_upcoming', isset($_POST['tm_season_is_upcoming']) ? 1 : 0);

    remove_action('save_post', 'tm_save_season_fields');
    wp_update_post(array('ID' => $post_id, 'post_title' => $name));
    add_action('save_post', 'tm_save_season_fields');
}
add_action('save_post', 'tm_save_season_fields');

function tm_season_columns($columns) {
    return array(
        'cb' => '<input type="checkbox" />',
        'title' => 'Season Name',
        'start_date' => 'Start Date',
        'end_date' => 'End Date',
        'is_current' => 'Current',
        'is_upcoming' => 'Upcoming',
        'image_front' => 'Front Image',
        'image_back' => 'Back Image',
        'social_banner' => 'Website Banner',
        'sm_square' => 'Social Square',
        'sm_portrait' => 'Social Portrait',
		'post_id' => 'ID'
    );
}
add_filter('manage_season_posts_columns', 'tm_season_columns');

function tm_season_custom_column($column, $post_id) {
    switch ($column) {
        case 'start_date':
            echo esc_html(get_post_meta($post_id, '_tm_season_start_date', true));
            break;
        case 'end_date':
            echo esc_html(get_post_meta($post_id, '_tm_season_end_date', true));
            break;
        case 'image_front':
            $img_value = get_post_meta($post_id, '_tm_season_image_front', true);
            $img_url = tm_get_season_image_url($img_value);
            if ($img_url) echo '<img src="' . esc_url($img_url) . '" style="max-width:60px;">';
            break;
        case 'image_back':
            $img_value = get_post_meta($post_id, '_tm_season_image_back', true);
            $img_url = tm_get_season_image_url($img_value);
            if ($img_url) echo '<img src="' . esc_url($img_url) . '" style="max-width:60px;">';
            break;
        case 'social_banner':
            $img_value = get_post_meta($post_id, '_tm_season_social_banner', true);
            $img_url = tm_get_season_image_url($img_value);
            if ($img_url) echo '<img src="' . esc_url($img_url) . '" style="max-width:60px;">';
            break;
		case 'post_id':
			echo $post_id;		
			break;
    }
}
add_action('manage_season_posts_custom_column', 'tm_season_custom_column', 10, 2);
