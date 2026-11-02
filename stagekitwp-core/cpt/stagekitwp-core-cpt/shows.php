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
function stagekitwp_get_show_image_url($value) {
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
function stagekitwp_register_show_cpt() {
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
        'all_items' => 'Shows',
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
        'show_in_menu' => false,
        'capability_type' => 'post',
        'map_meta_cap' => true,
        'show_in_rest' => true,
    );

    register_post_type('show', $args);
}
add_action('init', 'stagekitwp_register_show_cpt');

/**
 * Add Meta Boxes for Show
 */
function stagekitwp_add_show_meta_boxes() {
    add_meta_box('stagekitwp_show_details', 'Show Details', 'stagekitwp_render_show_meta_box', 'show', 'normal', 'default');
}
add_action('add_meta_boxes', 'stagekitwp_add_show_meta_boxes');

/**
 * Render Show Meta Box
 */
function stagekitwp_render_show_meta_box($post) {
    wp_nonce_field('stagekitwp_save_show_meta', 'stagekitwp_show_meta_nonce');

    $fields = [
        'name' => '',
        'author' => '',
        'sub_authors' => '',
        'synopsis' => '',
        'audition_date' => '',
        'genre' => '',
        'director' => '',
        'associate_director' => '',
        'producer' => '',
        'stage_manager' => '',
        'time_slot' => '',
        'show_dates' => '',
        'season' => '',
        'venue' => '',
        'tickets_url' => '',
        'sm_image' => '',
        'testimonial_media' => ''
    ];

    foreach ($fields as $key => $default) {
            $fields[$key] = get_post_meta($post->ID, '_stagekitwp_show_' . $key, true);
    }

    $actual_dates = get_post_meta($post->ID, '_stagekitwp_show_actual_dates', true);
    if (!is_array($actual_dates)) {
        $actual_dates = [];
    }
    if (empty($actual_dates)) {
        $actual_dates = [''];
    }
    $duration_minutes = absint(get_post_meta($post->ID, '_stagekitwp_show_duration_minutes', true));
    if ($duration_minutes < 1 || $duration_minutes > 1440) {
        $duration_minutes = 165;
    }

        // Program meta (separate fields: attachment ID and URL)
        $program_id = get_post_meta($post->ID, '_stagekitwp_show_program', true);
        $program_url = get_post_meta($post->ID, '_stagekitwp_show_program_url', true);

    $genres = ['Comedy', 'Farce', 'Mystery', 'Drama', 'Musical'];
    $slots = ['Fall', 'Winter', 'Spring'];
    $seasons = get_posts(['post_type' => 'season', 'numberposts' => -1]);
    $venues = get_posts(['post_type' => 'venue', 'numberposts' => -1]);

    echo '<p><label>Name:<br><input type="text" name="stagekitwp_show_name" value="' . esc_attr($fields['name']) . '" class="widefat" /></label></p>';
    echo '<p><label>Author:<br><input type="text" name="stagekitwp_show_author" value="' . esc_attr($fields['author']) . '" class="widefat" /></label></p>';
    echo '<p><label>Sub-authors:<br><input type="text" name="stagekitwp_show_sub_authors" value="' . esc_attr($fields['sub_authors']) . '" class="widefat" /></label></p>';
    echo '<p><label>Synopsis:<br><textarea name="stagekitwp_show_synopsis" class="widefat">' . esc_textarea($fields['synopsis']) . '</textarea></label></p>';
    echo '<p><label>Audition Date:<br><input type="date" name="stagekitwp_show_audition_date" value="' . esc_attr($fields['audition_date']) . '" class="widefat stagekitwp-datepicker" /></label></p>';

    $slot_names = array('start' => 'stagekitwp_audition_slot_start[]', 'duration' => 'stagekitwp_audition_slot_duration[]', 'venue' => 'stagekitwp_audition_slot_venue[]');
    echo '<fieldset class="stagekitwp-audition-slots"><legend>Audition Dates, Times and Venues</legend>';
    echo '<p>Each audition session: start, duration and venue (blank uses the show venue). When slots exist, the Audition Date above is set automatically from them.</p>';
    echo '<div class="stagekitwp-audition-slots-list">';
    foreach (stagekitwp_get_audition_slots($post->ID) as $audition_slot) {
        echo stagekitwp_audition_slot_row_html($slot_names, $audition_slot); // phpcs:ignore WordPress.Security.EscapeOutput
    }
    echo '</div><button type="button" class="button stagekitwp-add-audition-slot">Add Audition Slot</button></fieldset>';
    echo '<script>
        (function () {
            var rowHtml = ' . wp_json_encode(stagekitwp_audition_slot_row_html($slot_names)) . ';
            var fs = document.querySelector(".stagekitwp-audition-slots");
            if (!fs) return;
            var list = fs.querySelector(".stagekitwp-audition-slots-list");
            fs.addEventListener("click", function (e) {
                if (e.target.classList.contains("stagekitwp-add-audition-slot")) {
                    var tmp = document.createElement("div");
                    tmp.innerHTML = rowHtml;
                    list.appendChild(tmp.firstChild);
                }
                if (e.target.classList.contains("stagekitwp-remove-audition-slot")) {
                    e.target.closest(".stagekitwp-audition-slot-row").remove();
                }
            });
        })();
    </script>';

    echo '<p><label>Audition Details:</label></p>';
    wp_editor(
        get_post_meta($post->ID, '_stagekitwp_show_audition_details', true),
        'stagekitwp_show_audition_details',
        array(
            'textarea_name' => 'stagekitwp_show_audition_details',
            'textarea_rows' => 6,
            'media_buttons' => false,
            'teeny' => true,
        )
    );

    echo '<p><label>Genre:<br><select name="stagekitwp_show_genre">';
    foreach ($genres as $genre) {
        echo '<option value="' . esc_attr($genre) . '" ' . selected($fields['genre'], $genre, false) . '>' . esc_html($genre) . '</option>';
    }
    echo '</select></label></p>';

    echo '<p><label>Director:<br><input type="text" name="stagekitwp_show_director" value="' . esc_attr($fields['director']) . '" class="widefat" /></label></p>';
    echo '<p><label>Associate Director:<br><input type="text" name="stagekitwp_show_associate_director" value="' . esc_attr($fields['associate_director']) . '" class="widefat" /></label></p>';
    echo '<p><label>Producer:<br><input type="text" name="stagekitwp_show_producer" value="' . esc_attr($fields['producer']) . '" class="widefat" /></label></p>';
    echo '<p><label>Stage Manager:<br><input type="text" name="stagekitwp_show_stage_manager" value="' . esc_attr($fields['stage_manager']) . '" class="widefat" /></label></p>';

    echo '<p><label>Time Slot:<br><select name="stagekitwp_show_time_slot">';
    foreach ($slots as $slot) {
        echo '<option value="' . esc_attr($slot) . '" ' . selected($fields['time_slot'], $slot, false) . '>' . esc_html($slot) . '</option>';
    }
    echo '</select></label></p>';

    echo '<p><label>Show Dates:<br><textarea name="stagekitwp_show_show_dates" class="widefat">' . esc_textarea($fields['show_dates']) . '</textarea></label></p>';

    echo '<fieldset class="stagekitwp-show-actual-dates"><legend>Actual Show Dates and Times</legend>';
    echo '<p>Enter each scheduled performance date and start time. Optionally choose a sponsor for that performance; blank uses the show\'s featured sponsor, then the season\'s.</p>';
    echo '<div class="stagekitwp-show-actual-dates-list">';
    $performance_sponsors = stagekitwp_get_performance_sponsors($post->ID);
    foreach ($actual_dates as $actual_date) {
        echo '<p class="stagekitwp-show-actual-date-row"><input type="datetime-local" name="stagekitwp_show_actual_dates[]" value="' . esc_attr($actual_date) . '" /> ';
        stagekitwp_render_sponsor_select('stagekitwp_show_performance_sponsors[]', isset($performance_sponsors[$actual_date]) ? $performance_sponsors[$actual_date] : '', '— Default sponsor —');
        echo ' <button type="button" class="button stagekitwp-remove-actual-date">Remove</button></p>';
    }
    echo '</div>';
    echo '<button type="button" class="button stagekitwp-add-actual-date">Add Performance Date</button>';
    echo '</fieldset>';
    ob_start();
    stagekitwp_render_sponsor_select('stagekitwp_show_performance_sponsors[]', '', '— Default sponsor —');
    $sponsor_select_html = ob_get_clean();
    echo '<script>
        (function () {
            var sponsorSelectHtml = ' . wp_json_encode($sponsor_select_html) . ';
            var fieldset = document.querySelector(".stagekitwp-show-actual-dates");
            if (!fieldset) return;
            var list = fieldset.querySelector(".stagekitwp-show-actual-dates-list");
            fieldset.addEventListener("click", function (event) {
                if (event.target.classList.contains("stagekitwp-add-actual-date")) {
                    var row = document.createElement("p");
                    row.className = "stagekitwp-show-actual-date-row";
                    row.innerHTML = \'<input type="datetime-local" name="stagekitwp_show_actual_dates[]" /> \' + sponsorSelectHtml + \' <button type="button" class="button stagekitwp-remove-actual-date">Remove</button>\';
                    list.appendChild(row);
                }
                if (event.target.classList.contains("stagekitwp-remove-actual-date")) {
                    event.target.closest(".stagekitwp-show-actual-date-row").remove();
                }
            });
        })();
    </script>';

    echo '<fieldset class="stagekitwp-show-duration"><legend>Show Duration</legend>';
    echo '<label>Hours: <input type="number" name="stagekitwp_show_duration_hours" min="0" max="24" step="1" value="' . esc_attr((string) floor($duration_minutes / 60)) . '" /></label> ';
    echo '<label>Minutes: <input type="number" name="stagekitwp_show_duration_remainder" min="0" max="59" step="1" value="' . esc_attr((string) ($duration_minutes % 60)) . '" /></label>';
    echo '</fieldset>';

    echo '<p><label>Season:<br><select name="stagekitwp_show_season">';
    foreach ($seasons as $season) {
        echo '<option value="' . esc_attr($season->ID) . '" ' . selected($fields['season'], $season->ID, false) . '>' . esc_html($season->post_title) . '</option>';
    }
    echo '</select></label></p>';

    echo '<p><label>Featured Sponsor:<br>';
    stagekitwp_render_sponsor_select('stagekitwp_show_featured_sponsor', get_post_meta($post->ID, '_stagekitwp_show_featured_sponsor', true));
    echo '</label></p>';

    echo '<p><label>Venue:<br><select name="stagekitwp_show_venue">';
    echo '<option value="">-- Select Venue --</option>';
    foreach ($venues as $venue) {
        echo '<option value="' . esc_attr($venue->ID) . '" ' . selected($fields['venue'], $venue->ID, false) . '>' . esc_html($venue->post_title) . '</option>';
    }
    echo '</select></label></p>';

    echo '<p><label>Tickets URL:<br><input type="url" name="stagekitwp_show_tickets_url" value="' . esc_attr($fields['tickets_url']) . '" class="widefat" placeholder="https://example.com/tickets" /></label></p>';

	echo '<p><label>SM Image:<br>';
	echo '<input type="text" name="stagekitwp_show_sm_image" id="stagekitwp_show_sm_image" value="' . esc_attr($fields['sm_image']) . '" class="widefat" />';
    echo '<button type="button" class="button stagekitwp-media-button" data-target="stagekitwp_show_sm_image" data-preview="stagekitwp_show_sm_image_preview">Select Image</button>';
	echo '</label></p>';
	// Convert attachment ID to URL for preview display
	$sm_image_url = stagekitwp_get_show_image_url($fields['sm_image']);
	echo '<div><img id="stagekitwp_show_sm_image_preview" src="' . esc_url($sm_image_url) . '" style="max-width:150px;' . ($sm_image_url ? '' : ' display:none;') . '" /></div>';

    echo '<p><label>Testimonial Media (optional):<br>';
    echo '<input type="text" name="stagekitwp_show_testimonial_media" id="stagekitwp_show_testimonial_media" value="' . esc_attr($fields['testimonial_media']) . '" class="widefat" />';
    echo '<button type="button" class="button stagekitwp-media-button" data-target="stagekitwp_show_testimonial_media" data-preview="stagekitwp_show_testimonial_media_preview">Select Image</button>';
    echo '</label></p>';
    $testimonial_media_url = stagekitwp_get_show_image_url($fields['testimonial_media']);
    echo '<div><img id="stagekitwp_show_testimonial_media_preview" src="' . esc_url($testimonial_media_url) . '" style="max-width:150px;' . ($testimonial_media_url ? '' : ' display:none;') . '" /></div>';

    // Program PDF upload field (visible URL + hidden attachment ID)
    echo '<p><label>Program PDF:<br>';
    echo '<input type="text" name="stagekitwp_show_program" id="stagekitwp_show_program" value="' . esc_attr($program_url) . '" class="widefat" />';
    echo '<input type="hidden" name="stagekitwp_show_program_id" id="stagekitwp_show_program_id" value="' . esc_attr($program_id) . '" />';
    echo '<button type="button" class="button stagekitwp-media-button" data-target="stagekitwp_show_program" data-preview="stagekitwp_show_program_preview">Select PDF</button>';
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
            $gen = get_post_meta($program_id, '_stagekitwp_pdf_preview', true);
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
                    $gen = get_post_meta($maybe_id, '_stagekitwp_pdf_preview', true);
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
    echo '<div><img id="stagekitwp_show_program_preview" src="' . esc_url($program_preview_src) . '" style="max-width:150px;' . $program_preview_style . '" /></div>';
}
/**
 * Save Show Meta
 */
function stagekitwp_save_show_meta($post_id) {
    $nonce = isset($_POST['stagekitwp_show_meta_nonce']) ? sanitize_text_field(wp_unslash($_POST['stagekitwp_show_meta_nonce'])) : '';
    if (!$nonce || !wp_verify_nonce($nonce, 'stagekitwp_save_show_meta')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    $post_type = isset($_POST['post_type']) ? sanitize_key(wp_unslash($_POST['post_type'])) : '';
    if ('show' !== $post_type || !current_user_can('edit_post', $post_id)) return;

    $fields = [
        'name', 'author', 'sub_authors', 'synopsis', 'audition_date',
        'genre', 'director', 'associate_director', 'producer', 'stage_manager',
        'time_slot', 'show_dates', 'season', 'venue', 'tickets_url', 'sm_image', 'testimonial_media'
    ];

    foreach ($fields as $field) {
        if (isset($_POST['stagekitwp_show_' . $field])) {
            update_post_meta($post_id, '_stagekitwp_show_' . $field, sanitize_text_field(wp_unslash($_POST['stagekitwp_show_' . $field])));
        }
    }

    update_post_meta($post_id, '_stagekitwp_show_featured_sponsor', stagekitwp_sanitize_post_id_of_type(isset($_POST['stagekitwp_show_featured_sponsor']) ? wp_unslash($_POST['stagekitwp_show_featured_sponsor']) : '', 'sponsor'));

    $actual_dates = isset($_POST['stagekitwp_show_actual_dates']) && is_array($_POST['stagekitwp_show_actual_dates'])
        ? wp_unslash($_POST['stagekitwp_show_actual_dates'])
        : [];
    $posted_sponsors = isset($_POST['stagekitwp_show_performance_sponsors']) && is_array($_POST['stagekitwp_show_performance_sponsors'])
        ? array_values(wp_unslash($_POST['stagekitwp_show_performance_sponsors']))
        : [];
    $valid_actual_dates = [];
    $performance_sponsor_map = [];
    foreach (array_values($actual_dates) as $row_index => $actual_date) {
        if (!is_string($actual_date)) {
            continue;
        }
        $actual_date = sanitize_text_field($actual_date);
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})$/', $actual_date, $matches)
            && checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1])
            && (int) $matches[4] <= 23
            && (int) $matches[5] <= 59) {
            $valid_actual_dates[] = $actual_date;
            $row_sponsor = isset($posted_sponsors[$row_index]) && is_scalar($posted_sponsors[$row_index])
                ? stagekitwp_sanitize_post_id_of_type($posted_sponsors[$row_index], 'sponsor')
                : '';
            if ($row_sponsor && !isset($performance_sponsor_map[$actual_date])) {
                $performance_sponsor_map[$actual_date] = $row_sponsor;
            }
        }
    }
    if ($performance_sponsor_map) {
        update_post_meta($post_id, '_stagekitwp_show_performance_sponsors', $performance_sponsor_map);
    } else {
        delete_post_meta($post_id, '_stagekitwp_show_performance_sponsors');
    }
    if (!empty($valid_actual_dates)) {
        update_post_meta($post_id, '_stagekitwp_show_actual_dates', array_values(array_unique($valid_actual_dates)));
    } else {
        delete_post_meta($post_id, '_stagekitwp_show_actual_dates');
    }

    stagekitwp_save_audition_slots($post_id, stagekitwp_sanitize_audition_slots(
        isset($_POST['stagekitwp_audition_slot_start']) ? wp_unslash($_POST['stagekitwp_audition_slot_start']) : [],
        isset($_POST['stagekitwp_audition_slot_duration']) ? wp_unslash($_POST['stagekitwp_audition_slot_duration']) : [],
        isset($_POST['stagekitwp_audition_slot_venue']) ? wp_unslash($_POST['stagekitwp_audition_slot_venue']) : []
    ));

        $duration_hours = isset($_POST['stagekitwp_show_duration_hours']) && is_scalar($_POST['stagekitwp_show_duration_hours'])
            ? wp_unslash($_POST['stagekitwp_show_duration_hours'])
            : '2';
        $duration_remainder = isset($_POST['stagekitwp_show_duration_remainder']) && is_scalar($_POST['stagekitwp_show_duration_remainder'])
            ? wp_unslash($_POST['stagekitwp_show_duration_remainder'])
            : '45';
        $duration_hours = is_string($duration_hours) && preg_match('/^\d+$/', $duration_hours) ? (int) $duration_hours : 2;
        $duration_remainder = is_string($duration_remainder) && preg_match('/^\d+$/', $duration_remainder) ? (int) $duration_remainder : 45;
        $duration_total_minutes = ($duration_hours * 60) + $duration_remainder;
        if ($duration_hours > 24 || $duration_remainder > 59 || $duration_total_minutes < 1 || $duration_total_minutes > 1440) {
            $duration_total_minutes = 165;
        }
        update_post_meta($post_id, '_stagekitwp_show_duration_minutes', $duration_total_minutes);

    $audition_details = isset($_POST['stagekitwp_show_audition_details']) ? wp_kses_post(wp_unslash($_POST['stagekitwp_show_audition_details'])) : '';
    update_post_meta($post_id, '_stagekitwp_show_audition_details', $audition_details);

    // Program saving: prefer explicit attachment ID if provided, otherwise resolve URL
    if (isset($_POST['stagekitwp_show_program_id']) && is_numeric($_POST['stagekitwp_show_program_id'])) {
        $att_id = intval($_POST['stagekitwp_show_program_id']);
        update_post_meta($post_id, '_stagekitwp_show_program', $att_id);
        update_post_meta($post_id, '_stagekitwp_show_program_url', esc_url_raw(wp_get_attachment_url($att_id)));
    } elseif (isset($_POST['stagekitwp_show_program'])) {
        $val = sanitize_text_field(wp_unslash($_POST['stagekitwp_show_program']));
        $att_id = 0;
        if (!empty($val)) {
            $att_id = attachment_url_to_postid($val);
        }
        if ($att_id) {
            update_post_meta($post_id, '_stagekitwp_show_program', $att_id);
            update_post_meta($post_id, '_stagekitwp_show_program_url', esc_url_raw(wp_get_attachment_url($att_id)));
        } else {
            update_post_meta($post_id, '_stagekitwp_show_program', '');
            update_post_meta($post_id, '_stagekitwp_show_program_url', esc_url_raw($val));
        }
    }

    // Auto-fill post title from Name field
    remove_action('save_post', 'stagekitwp_save_show_meta');
    $show_name = isset($_POST['stagekitwp_show_name']) ? sanitize_text_field(wp_unslash($_POST['stagekitwp_show_name'])) : '';
    wp_update_post([
        'ID' => $post_id,
        'post_title' => $show_name
    ]);
    add_action('save_post', 'stagekitwp_save_show_meta');
}
add_action('save_post', 'stagekitwp_save_show_meta');
/**
 * Customize Show List Columns
 */
function stagekitwp_show_columns($columns) {
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
add_filter('manage_show_posts_columns', 'stagekitwp_show_columns');

// Sortable Season column: orders shows by their season's start date (shows with no season sort last).
function stagekitwp_show_sortable_columns($columns) {
    $columns['season'] = 'stagekitwp_season';
    return $columns;
}
add_filter('manage_edit-show_sortable_columns', 'stagekitwp_show_sortable_columns');

function stagekitwp_show_sort_clauses($clauses, $query) {
    global $wpdb;
    if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== 'show' || $query->get('orderby') !== 'stagekitwp_season') {
        return $clauses;
    }
    $dir = strtoupper($query->get('order')) === 'DESC' ? 'DESC' : 'ASC';
    $clauses['join']   .= " LEFT JOIN {$wpdb->postmeta} AS skw_show_season ON ({$wpdb->posts}.ID = skw_show_season.post_id AND skw_show_season.meta_key = '_stagekitwp_show_season')"
        . " LEFT JOIN {$wpdb->postmeta} AS skw_season_start ON (skw_season_start.post_id = CAST(skw_show_season.meta_value AS UNSIGNED) AND skw_season_start.meta_key = '_stagekitwp_season_start_date')";
    $clauses['orderby'] = "(skw_season_start.meta_value IS NULL OR skw_season_start.meta_value = '') ASC, skw_season_start.meta_value {$dir}, {$wpdb->posts}.post_title ASC";
    return $clauses;
}
add_filter('posts_clauses', 'stagekitwp_show_sort_clauses', 10, 2);

/**
 * Render Custom Columns
 */
function stagekitwp_show_custom_column($column, $post_id) {
    switch ($column) {
        case 'genre':
            echo esc_html(get_post_meta($post_id, '_stagekitwp_show_genre', true));
            break;
        case 'time_slot':
            echo esc_html(get_post_meta($post_id, '_stagekitwp_show_time_slot', true));
            break;
        case 'season':
            $season_id = get_post_meta($post_id, '_stagekitwp_show_season', true);
            echo $season_id ? get_the_title($season_id) : '';
            break;
        case 'sm_image':
            $img = get_post_meta($post_id, '_stagekitwp_show_sm_image', true);
            if ($img) echo '<img src="' . esc_url($img) . '" style="max-width:50px;" />';
            break;
        case 'program_pdf':
            // Get program PDF attachment ID (stored in _stagekitwp_show_program)
            $program_id = get_post_meta($post_id, '_stagekitwp_show_program', true);
            if ($program_id) {
                // Try to get generated thumbnail preview
                $preview_url = get_post_meta($program_id, '_stagekitwp_pdf_preview', true);
                if (!$preview_url) {
                    // Fallback to attachment image
                    $att_preview = wp_get_attachment_image_src($program_id, 'thumbnail');
                    if ($att_preview) {
                        $preview_url = $att_preview[0];
                    }
                }
                if ($preview_url) {
                    echo '<a href="' . esc_url(get_post_meta($post_id, '_stagekitwp_show_program_url', true)) . '" target="_blank">';
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
add_action('manage_show_posts_custom_column', 'stagekitwp_show_custom_column', 10, 2);
