<?php
// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Get image URL from attachment ID or return the value if it's already a URL
 * Handles both: direct URLs and WordPress attachment IDs
 * 
 * @param int|string $value Either an attachment ID or a URL
 * @return string The image URL, or empty string if not found
 */
function tm_get_show_image_url($value) {
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

/**
 * Register Show Custom Post Type
 */
function tm_register_show_cpt() {
    $labels = array(
        'name' => 'Shows',
        'singular_name' => 'Show',
        'menu_name' => 'Shows',
        'name_admin_bar' => 'Show',
        'add_new' => 'Add New',
        'add_new_item' => 'Add New Show',
        'new_item' => 'New Show',
        'edit_item' => 'Edit Show',
        'view_item' => 'View Show',
        'all_items' => 'All Shows',
        'search_items' => 'Search Shows',
        'not_found' => 'No shows found.',
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'menu_position' => 21,
        'menu_icon' => 'dashicons-tickets-alt',
        'supports' => array(''),
        'has_archive' => true,
        'show_in_menu' => 'theatre-manager',
    );

    register_post_type('show', $args);
}
add_action('init', 'tm_register_show_cpt');

/**
 * Add Meta Boxes for Show
 */
function tm_add_show_meta_boxes() {
    add_meta_box('tm_show_details', 'Show Details', 'tm_render_show_meta_box', 'show', 'normal', 'default');
}
add_action('add_meta_boxes', 'tm_add_show_meta_boxes');

/**
 * Render Show Meta Box
 */
function tm_render_show_meta_box($post) {
    wp_nonce_field('tm_save_show_meta', 'tm_show_meta_nonce');

    $fields = [
        'name' => '',
        'author' => '',
        'sub_authors' => '',
        'synopsis' => '',
        'genre' => '',
        'director' => '',
        'associate_director' => '',
        'time_slot' => '',
        'show_dates' => '',
        'season' => '',
        'sm_image' => ''
    ];

    foreach ($fields as $key => $default) {
            $fields[$key] = get_post_meta($post->ID, '_tm_show_' . $key, true);
    }

        // Program meta (separate fields: attachment ID and URL)
        $program_id = get_post_meta($post->ID, '_tm_show_program', true);
        $program_url = get_post_meta($post->ID, '_tm_show_program_url', true);

    $genres = ['Comedy', 'Farce', 'Mystery', 'Drama', 'Musical'];
    $slots = ['Fall', 'Winter', 'Spring'];
    $seasons = get_posts(['post_type' => 'season', 'numberposts' => -1]);

    echo '<p><label>Name:<br><input type="text" name="tm_show_name" value="' . esc_attr($fields['name']) . '" class="widefat" /></label></p>';
    echo '<p><label>Author:<br><input type="text" name="tm_show_author" value="' . esc_attr($fields['author']) . '" class="widefat" /></label></p>';
    echo '<p><label>Sub-authors:<br><input type="text" name="tm_show_sub_authors" value="' . esc_attr($fields['sub_authors']) . '" class="widefat" /></label></p>';
    echo '<p><label>Synopsis:<br><textarea name="tm_show_synopsis" class="widefat">' . esc_textarea($fields['synopsis']) . '</textarea></label></p>';

    echo '<p><label>Genre:<br><select name="tm_show_genre">';
    foreach ($genres as $genre) {
        echo '<option value="' . esc_attr($genre) . '" ' . selected($fields['genre'], $genre, false) . '>' . esc_html($genre) . '</option>';
    }
    echo '</select></label></p>';

    echo '<p><label>Director:<br><input type="text" name="tm_show_director" value="' . esc_attr($fields['director']) . '" class="widefat" /></label></p>';
    echo '<p><label>Associate Director:<br><input type="text" name="tm_show_associate_director" value="' . esc_attr($fields['associate_director']) . '" class="widefat" /></label></p>';

    echo '<p><label>Time Slot:<br><select name="tm_show_time_slot">';
    foreach ($slots as $slot) {
        echo '<option value="' . esc_attr($slot) . '" ' . selected($fields['time_slot'], $slot, false) . '>' . esc_html($slot) . '</option>';
    }
    echo '</select></label></p>';

    echo '<p><label>Show Dates:<br><textarea name="tm_show_show_dates" class="widefat">' . esc_textarea($fields['show_dates']) . '</textarea></label></p>';

    echo '<p><label>Season:<br><select name="tm_show_season">';
    foreach ($seasons as $season) {
        echo '<option value="' . esc_attr($season->ID) . '" ' . selected($fields['season'], $season->ID, false) . '>' . esc_html($season->post_title) . '</option>';
    }
    echo '</select></label></p>';

	echo '<p><label>SM Image:<br>';
	echo '<input type="text" name="tm_show_sm_image" id="tm_show_sm_image" value="' . esc_attr($fields['sm_image']) . '" class="widefat" />';
    echo '<button type="button" class="button tm-media-button" data-target="tm_show_sm_image" data-preview="tm_show_sm_image_preview">Select Image</button>';
	echo '</label></p>';
	// Convert attachment ID to URL for preview display
	$sm_image_url = tm_get_show_image_url($fields['sm_image']);
	echo '<div><img id="tm_show_sm_image_preview" src="' . esc_url($sm_image_url) . '" style="max-width:150px;' . ($sm_image_url ? '' : ' display:none;') . '" /></div>';

    // Program PDF upload field (visible URL + hidden attachment ID)
    echo '<p><label>Program PDF:<br>';
    echo '<input type="text" name="tm_show_program" id="tm_show_program" value="' . esc_attr($program_url) . '" class="widefat" />';
    echo '<input type="hidden" name="tm_show_program_id" id="tm_show_program_id" value="' . esc_attr($program_id) . '" />';
    echo '<button type="button" class="button tm-media-button" data-target="tm_show_program" data-preview="tm_show_program_preview">Select PDF</button>';
    echo '</label></p>';
    // Determine a sensible preview src for admin UI: prefer generated preview or attachment image size, then icon, then don't show
    $program_preview_src = '';
    if ($program_id) {
        // try WP image sizes for attachment
        $att_preview = wp_get_attachment_image_src($program_id, 'medium');
        if ($att_preview) {
            $program_preview_src = $att_preview[0];
        } else {
            // try our generated preview meta
            $gen = get_post_meta($program_id, '_tm_pdf_preview', true);
            if ($gen) $program_preview_src = $gen;
            else {
                // try WP thumbnail/icon
                $thumb = wp_get_attachment_thumb_url($program_id);
                if ($thumb) $program_preview_src = $thumb;
            }
        }
    } else {
        // no attachment id; if URL points to an attachment, try to resolve id
        if (!empty($program_url)) {
            $maybe_id = attachment_url_to_postid($program_url);
            if ($maybe_id) {
                $att_preview = wp_get_attachment_image_src($maybe_id, 'medium');
                if ($att_preview) $program_preview_src = $att_preview[0];
                else {
                    $gen = get_post_meta($maybe_id, '_tm_pdf_preview', true);
                    if ($gen) $program_preview_src = $gen;
                    else {
                        $thumb = wp_get_attachment_thumb_url($maybe_id);
                        if ($thumb) $program_preview_src = $thumb;
                    }
                }
            }
        }
    }

    $program_preview_style = $program_preview_src ? '' : ' display:none;';
    echo '<div><img id="tm_show_program_preview" src="' . esc_url($program_preview_src) . '" style="max-width:150px;' . $program_preview_style . '" /></div>';
}
/**
 * Save Show Meta
 */
function tm_save_show_meta($post_id) {
    if (!isset($_POST['tm_show_meta_nonce']) || !wp_verify_nonce($_POST['tm_show_meta_nonce'], 'tm_save_show_meta')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if ('show' !== $_POST['post_type'] || !current_user_can('edit_post', $post_id)) return;

    $fields = [
        'name', 'author', 'sub_authors', 'synopsis',
        'genre', 'director', 'associate_director',
        'time_slot', 'show_dates', 'season', 'sm_image'
    ];

    foreach ($fields as $field) {
        if (isset($_POST['tm_show_' . $field])) {
            update_post_meta($post_id, '_tm_show_' . $field, sanitize_text_field($_POST['tm_show_' . $field]));
        }
    }

    // Program saving: prefer explicit attachment ID if provided, otherwise resolve URL
    if (isset($_POST['tm_show_program_id']) && is_numeric($_POST['tm_show_program_id'])) {
        $att_id = intval($_POST['tm_show_program_id']);
        update_post_meta($post_id, '_tm_show_program', $att_id);
        update_post_meta($post_id, '_tm_show_program_url', esc_url_raw(wp_get_attachment_url($att_id)));
    } elseif (isset($_POST['tm_show_program'])) {
        $val = sanitize_text_field($_POST['tm_show_program']);
        $att_id = 0;
        if (!empty($val)) {
            $att_id = attachment_url_to_postid($val);
        }
        if ($att_id) {
            update_post_meta($post_id, '_tm_show_program', $att_id);
            update_post_meta($post_id, '_tm_show_program_url', esc_url_raw(wp_get_attachment_url($att_id)));
        } else {
            update_post_meta($post_id, '_tm_show_program', '');
            update_post_meta($post_id, '_tm_show_program_url', esc_url_raw($val));
        }
    }

    // Auto-fill post title from Name field
    remove_action('save_post', 'tm_save_show_meta');
    wp_update_post([
        'ID' => $post_id,
        'post_title' => sanitize_text_field($_POST['tm_show_name'])
    ]);
    add_action('save_post', 'tm_save_show_meta');
}
add_action('save_post', 'tm_save_show_meta');
/**
 * Customize Show List Columns
 */
function tm_show_columns($columns) {
    return array(
        'cb' => '<input type="checkbox" />',
        'title' => 'Show Name',
        'genre' => 'Genre',
        'time_slot' => 'Time Slot',
        'season' => 'Season',
        'sm_image' => 'Image',
        'program_pdf' => 'Program PDF',
		'post_id' => 'ID'
    );
}
add_filter('manage_show_posts_columns', 'tm_show_columns');

/**
 * Render Custom Columns
 */
function tm_show_custom_column($column, $post_id) {
    switch ($column) {
        case 'genre':
            echo esc_html(get_post_meta($post_id, '_tm_show_genre', true));
            break;
        case 'time_slot':
            echo esc_html(get_post_meta($post_id, '_tm_show_time_slot', true));
            break;
        case 'season':
            $season_id = get_post_meta($post_id, '_tm_show_season', true);
            echo $season_id ? get_the_title($season_id) : '';
            break;
        case 'sm_image':
            $img = get_post_meta($post_id, '_tm_show_sm_image', true);
            if ($img) echo '<img src="' . esc_url($img) . '" style="max-width:50px;" />';
            break;
        case 'program_pdf':
            // Get program PDF attachment ID (stored in _tm_show_program)
            $program_id = get_post_meta($post_id, '_tm_show_program', true);
            if ($program_id) {
                // Try to get generated thumbnail preview
                $preview_url = get_post_meta($program_id, '_tm_pdf_preview', true);
                if (!$preview_url) {
                    // Fallback to attachment image
                    $att_preview = wp_get_attachment_image_src($program_id, 'thumbnail');
                    if ($att_preview) {
                        $preview_url = $att_preview[0];
                    }
                }
                if ($preview_url) {
                    echo '<a href="' . esc_url(get_post_meta($post_id, '_tm_show_program_url', true)) . '" target="_blank">';
                    echo '<img src="' . esc_url($preview_url) . '" style="max-width:50px; max-height:75px;" />';
                    echo '</a>';
                } else {
                    echo '📄 PDF';
                }
            }
            break;
		case 'post_id':
			echo $post_id;
			break;
    }
}
add_action('manage_show_posts_custom_column', 'tm_show_custom_column', 10, 2);
