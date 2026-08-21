<?php

/**
 * Get image URL from attachment ID or return the value if it's already a URL
 * Handles both: direct URLs and WordPress attachment IDs
 * 
 * @param int|string $value Either an attachment ID or a URL
 * @return string The image URL, or empty string if not found
 */
function stagekitwp_get_season_image_url($value) {
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

function stagekitwp_register_season_cpt() {
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
        'all_items' => 'Seasons',
        'search_items' => 'Search Seasons',
        'not_found' => 'No seasons found.',
    );
    $args = array(
        'labels' => $labels,
        'public' => true,
        'show_in_menu' => false,
        'supports' => array(''),
        'menu_position' => 5,
        'menu_icon' => 'dashicons-calendar-alt',
        'has_archive' => false,
        'rewrite' => array('slug' => 'season'),
        'show_in_rest' => true,
        'capability_type' => 'post',
        'map_meta_cap' => true,
    );
    register_post_type('season', $args);
}
add_action('init', 'stagekitwp_register_season_cpt');

function stagekitwp_add_season_meta_boxes() {
    add_meta_box('stagekitwp_season_fields', 'Season Details', 'stagekitwp_render_season_fields', 'season', 'normal', 'default');
}
add_action('add_meta_boxes', 'stagekitwp_add_season_meta_boxes');

function stagekitwp_render_season_fields($post) {
    wp_nonce_field('stagekitwp_save_season_fields', 'stagekitwp_season_nonce');

    $name = get_post_meta($post->ID, '_stagekitwp_season_name', true);
    $start_date = get_post_meta($post->ID, '_stagekitwp_season_start_date', true);
    $end_date = get_post_meta($post->ID, '_stagekitwp_season_end_date', true);
    $tickets_url = get_post_meta($post->ID, '_stagekitwp_season_tickets_url', true);
    $image_front = get_post_meta($post->ID, '_stagekitwp_season_image_front', true);
    $image_back = get_post_meta($post->ID, '_stagekitwp_season_image_back', true);
    $social_banner = get_post_meta($post->ID, '_stagekitwp_season_social_banner', true);
    $sm_square = get_post_meta($post->ID, '_stagekitwp_season_sm_square', true);
    $sm_portrait = get_post_meta($post->ID, '_stagekitwp_season_sm_portrait', true);
    $is_current = get_post_meta($post->ID, '_stagekitwp_season_is_current', true);
    $is_upcoming = get_post_meta($post->ID, '_stagekitwp_season_is_upcoming', true);

    // Convert attachment IDs to URLs for display
    $image_front_url = stagekitwp_get_season_image_url($image_front);
    $image_back_url = stagekitwp_get_season_image_url($image_back);
    $social_banner_url = stagekitwp_get_season_image_url($social_banner);
    $sm_square_url = stagekitwp_get_season_image_url($sm_square);
    $sm_portrait_url = stagekitwp_get_season_image_url($sm_portrait);

    echo '<p><label>Name:<br><input type="text" name="stagekitwp_season_name" value="' . esc_attr($name) . '" class="widefat" /></label></p>';
    echo '<p><label>Start Date:<br><input type="date" name="stagekitwp_season_start_date" value="' . esc_attr($start_date) . '" class="widefat stagekitwp-datepicker" /></label></p>';
    echo '<p><label>End Date:<br><input type="date" name="stagekitwp_season_end_date" value="' . esc_attr($end_date) . '" class="widefat stagekitwp-datepicker" /></label></p>';
    echo '<p><label>Tickets URL:<br><input type="url" name="stagekitwp_season_tickets_url" value="' . esc_attr($tickets_url) . '" class="widefat" placeholder="https://example.com/tickets" /></label></p>';

    echo '<p>';
    echo '<label><input type="checkbox" id="stagekitwp_season_is_current" name="stagekitwp_season_is_current" value="1" ' . checked($is_current, 1, false) . ' /> Is Current Season</label>';
    echo '</p>';
    
    echo '<p>';
    echo '<label><input type="checkbox" id="stagekitwp_season_is_upcoming" name="stagekitwp_season_is_upcoming" value="1" ' . checked($is_upcoming, 1, false) . ' /> Is Upcoming Season</label>';
    echo '</p>';

    ?>
    <script>
    (function() {
        var currentCheckbox = document.getElementById('stagekitwp_season_is_current');
        var upcomingCheckbox = document.getElementById('stagekitwp_season_is_upcoming');

        if (currentCheckbox) {
            currentCheckbox.addEventListener('change', function() {
                if (this.checked && upcomingCheckbox) {
                    upcomingCheckbox.checked = false;
                }
            });
        }

        if (upcomingCheckbox) {
            upcomingCheckbox.addEventListener('change', function() {
                if (this.checked && currentCheckbox) {
                    currentCheckbox.checked = false;
                }
            });
        }
    })();
    </script>
    <?php

	echo '<label for="stagekitwp_season_image_front">3-up Front Image:</label>';
	echo '<input type="text" name="stagekitwp_season_image_front" id="stagekitwp_season_image_front" value="' . esc_attr($image_front) . '" class="widefat" />';
	echo '<button type="button" class="button stagekitwp-media-button" data-target="stagekitwp_season_image_front" data-preview="stagekitwp_season_image_front_preview">Select Image</button>';
	echo '<div><img id="stagekitwp_season_image_front_preview" src="' . esc_url($image_front_url) . '" style="max-width:150px;' . ($image_front_url ? '' : ' display:none;') . '" /></div>';
	
	echo '<label for="stagekitwp_season_image_back">3-up Back Image:</label>';
	echo '<input type="text" name="stagekitwp_season_image_back" id="stagekitwp_season_image_back" value="' . esc_attr($image_back) . '" class="widefat" />';
	echo '<button type="button" class="button stagekitwp-media-button" data-target="stagekitwp_season_image_back" data-preview="stagekitwp_season_image_back_preview">Select Image</button>';
	echo '<div><img id="stagekitwp_season_image_back_preview" src="' . esc_url($image_back_url) . '" style="max-width:150px;' . ($image_back_url ? '' : ' display:none;') . '" /></div>';
	
	echo '<label for="stagekitwp_season_social_banner">Website Banner:</label>';
	echo '<input type="text" name="stagekitwp_season_social_banner" id="stagekitwp_season_social_banner" value="' . esc_attr($social_banner) . '" class="widefat" />';
	echo '<button type="button" class="button stagekitwp-media-button" data-target="stagekitwp_season_social_banner" data-preview="stagekitwp_season_social_banner_preview">Select Image</button>';
	echo '<div><img id="stagekitwp_season_social_banner_preview" src="' . esc_url($social_banner_url) . '" style="max-width:150px;' . ($social_banner_url ? '' : ' display:none;') . '" /></div>';

	echo '<label for="stagekitwp_season_sm_square">Social Media Square:</label>';
	echo '<input type="text" name="stagekitwp_season_sm_square" id="stagekitwp_season_sm_square" value="' . esc_attr($sm_square) . '" class="widefat" />';
	echo '<button type="button" class="button stagekitwp-media-button" data-target="stagekitwp_season_sm_square" data-preview="stagekitwp_season_sm_square_preview">Select Image</button>';
	echo '<div><img id="stagekitwp_season_sm_square_preview" src="' . esc_url($sm_square_url) . '" style="max-width:150px;' . ($sm_square_url ? '' : ' display:none;') . '" /></div>';

	echo '<label for="stagekitwp_season_sm_portrait">Social Media Portrait:</label>';
	echo '<input type="text" name="stagekitwp_season_sm_portrait" id="stagekitwp_season_sm_portrait" value="' . esc_attr($sm_portrait) . '" class="widefat" />';
	echo '<button type="button" class="button stagekitwp-media-button" data-target="stagekitwp_season_sm_portrait" data-preview="stagekitwp_season_sm_portrait_preview">Select Image</button>';
	echo '<div><img id="stagekitwp_season_sm_portrait_preview" src="' . esc_url($sm_portrait_url) . '" style="max-width:150px;' . ($sm_portrait_url ? '' : ' display:none;') . '" /></div>';

}

function stagekitwp_save_season_fields($post_id) {
    $nonce = isset($_POST['stagekitwp_season_nonce']) ? sanitize_text_field(wp_unslash($_POST['stagekitwp_season_nonce'])) : '';
    if (!$nonce || !wp_verify_nonce($nonce, 'stagekitwp_save_season_fields')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    $post_type = isset($_POST['post_type']) ? sanitize_key(wp_unslash($_POST['post_type'])) : '';
    if ('season' !== $post_type || !current_user_can('edit_post', $post_id)) return;

    // Get the checkbox values
    $is_current = isset($_POST['stagekitwp_season_is_current']) ? 1 : 0;
    $is_upcoming = isset($_POST['stagekitwp_season_is_upcoming']) ? 1 : 0;

    // Enforce mutual exclusivity: a season cannot be both current and upcoming
    if ($is_current && $is_upcoming) {
        $is_upcoming = 0;
    }

    // If this season is being marked as current, remove is_current from all other seasons
    if ($is_current) {
        $args = array(
            'post_type' => 'season',
            'posts_per_page' => -1,
            'exclude' => $post_id,
            'meta_query' => array(
                array(
                    'key' => '_stagekitwp_season_is_current',
                    'value' => '1',
                ),
            ),
        );
        $other_current_seasons = get_posts($args);
        foreach ($other_current_seasons as $season) {
            delete_post_meta($season->ID, '_stagekitwp_season_is_current');
        }
    }

    // If this season is being marked as upcoming, remove is_upcoming from all other seasons
    if ($is_upcoming) {
        $args = array(
            'post_type' => 'season',
            'posts_per_page' => -1,
            'exclude' => $post_id,
            'meta_query' => array(
                array(
                    'key' => '_stagekitwp_season_is_upcoming',
                    'value' => '1',
                ),
            ),
        );
        $other_upcoming_seasons = get_posts($args);
        foreach ($other_upcoming_seasons as $season) {
            delete_post_meta($season->ID, '_stagekitwp_season_is_upcoming');
        }
    }

    $name = isset($_POST['stagekitwp_season_name']) ? sanitize_text_field(wp_unslash($_POST['stagekitwp_season_name'])) : '';
    update_post_meta($post_id, '_stagekitwp_season_name', $name);
    update_post_meta($post_id, '_stagekitwp_season_start_date', isset($_POST['stagekitwp_season_start_date']) ? sanitize_text_field(wp_unslash($_POST['stagekitwp_season_start_date'])) : '');
    update_post_meta($post_id, '_stagekitwp_season_end_date', isset($_POST['stagekitwp_season_end_date']) ? sanitize_text_field(wp_unslash($_POST['stagekitwp_season_end_date'])) : '');
    update_post_meta($post_id, '_stagekitwp_season_tickets_url', isset($_POST['stagekitwp_season_tickets_url']) ? esc_url_raw(wp_unslash($_POST['stagekitwp_season_tickets_url'])) : '');
    update_post_meta($post_id, '_stagekitwp_season_image_front', isset($_POST['stagekitwp_season_image_front']) ? esc_url_raw(wp_unslash($_POST['stagekitwp_season_image_front'])) : '');
    update_post_meta($post_id, '_stagekitwp_season_image_back', isset($_POST['stagekitwp_season_image_back']) ? esc_url_raw(wp_unslash($_POST['stagekitwp_season_image_back'])) : '');
    update_post_meta($post_id, '_stagekitwp_season_social_banner', isset($_POST['stagekitwp_season_social_banner']) ? esc_url_raw(wp_unslash($_POST['stagekitwp_season_social_banner'])) : '');
    update_post_meta($post_id, '_stagekitwp_season_sm_square', isset($_POST['stagekitwp_season_sm_square']) ? esc_url_raw(wp_unslash($_POST['stagekitwp_season_sm_square'])) : '');
    update_post_meta($post_id, '_stagekitwp_season_sm_portrait', isset($_POST['stagekitwp_season_sm_portrait']) ? esc_url_raw(wp_unslash($_POST['stagekitwp_season_sm_portrait'])) : '');
    update_post_meta($post_id, '_stagekitwp_season_is_current', $is_current);
    update_post_meta($post_id, '_stagekitwp_season_is_upcoming', $is_upcoming);

    remove_action('save_post', 'stagekitwp_save_season_fields');
    wp_update_post(array('ID' => $post_id, 'post_title' => $name));
    add_action('save_post', 'stagekitwp_save_season_fields');
}
add_action('save_post', 'stagekitwp_save_season_fields');

function stagekitwp_season_columns($columns) {
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
add_filter('manage_season_posts_columns', 'stagekitwp_season_columns');

function stagekitwp_season_custom_column($column, $post_id) {
    switch ($column) {
        case 'start_date':
            echo esc_html(get_post_meta($post_id, '_stagekitwp_season_start_date', true));
            break;
        case 'end_date':
            echo esc_html(get_post_meta($post_id, '_stagekitwp_season_end_date', true));
            break;
        case 'image_front':
            $img_value = get_post_meta($post_id, '_stagekitwp_season_image_front', true);
            $img_url = stagekitwp_get_season_image_url($img_value);
            if ($img_url) echo '<img src="' . esc_url($img_url) . '" style="max-width:60px;">';
            break;
        case 'image_back':
            $img_value = get_post_meta($post_id, '_stagekitwp_season_image_back', true);
            $img_url = stagekitwp_get_season_image_url($img_value);
            if ($img_url) echo '<img src="' . esc_url($img_url) . '" style="max-width:60px;">';
            break;
        case 'social_banner':
            $img_value = get_post_meta($post_id, '_stagekitwp_season_social_banner', true);
            $img_url = stagekitwp_get_season_image_url($img_value);
            if ($img_url) echo '<img src="' . esc_url($img_url) . '" style="max-width:60px;">';
            break;
		case 'post_id':
			echo $post_id;		
			break;
    }
}
add_action('manage_season_posts_custom_column', 'stagekitwp_season_custom_column', 10, 2);
