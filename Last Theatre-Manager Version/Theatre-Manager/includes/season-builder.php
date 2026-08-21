<?php
// Exit if accessed directly
defined('ABSPATH') || exit;

function tm_season_builder_resolve_media_value($url_value, $attachment_id) {
    $attachment_id = absint($attachment_id);
    if ($attachment_id > 0 && get_post_type($attachment_id) === 'attachment') {
        return $attachment_id;
    }

    $url_value = esc_url_raw($url_value);
    return $url_value !== '' ? $url_value : '';
}

function tm_season_builder_get_program_preview_url($program_id, $program_url) {
    $program_id = absint($program_id);
    if ($program_id > 0) {
        $preview = wp_get_attachment_image_src($program_id, 'medium');
        if ($preview) {
            return $preview[0];
        }

        $generated = get_post_meta($program_id, '_tm_pdf_preview', true);
        if ($generated) {
            return $generated;
        }

        $thumb = wp_get_attachment_thumb_url($program_id);
        if ($thumb) {
            return $thumb;
        }
    }

    if (!empty($program_url)) {
        $maybe_id = attachment_url_to_postid($program_url);
        if ($maybe_id) {
            return tm_season_builder_get_program_preview_url($maybe_id, '');
        }
    }

    return '';
}

function tm_season_builder_render_media_field($args) {
    $defaults = array(
        'label' => '',
        'input_name' => '',
        'input_id' => '',
        'value' => '',
        'attachment_id_name' => '',
        'attachment_id' => '',
        'preview_id' => '',
        'preview_url' => '',
        'button_text' => 'Select Media',
        'description' => '',
    );
    $args = wp_parse_args($args, $defaults);
    ?>
    <div class="tm-sb-media-field" style="margin-bottom: 16px;">
        <label for="<?php echo esc_attr($args['input_id']); ?>"><strong><?php echo esc_html($args['label']); ?></strong></label>
        <div style="margin-top: 6px;">
            <input
                type="text"
                class="regular-text"
                style="min-width: 320px;"
                name="<?php echo esc_attr($args['input_name']); ?>"
                id="<?php echo esc_attr($args['input_id']); ?>"
                value="<?php echo esc_attr($args['value']); ?>"
            />
            <?php if ($args['attachment_id_name'] !== '') : ?>
                <input
                    type="hidden"
                    name="<?php echo esc_attr($args['attachment_id_name']); ?>"
                    id="<?php echo esc_attr($args['input_id']); ?>_id"
                    value="<?php echo esc_attr($args['attachment_id']); ?>"
                />
            <?php endif; ?>
            <button
                type="button"
                class="button tm-media-button"
                data-target="<?php echo esc_attr($args['input_id']); ?>"
                data-preview="<?php echo esc_attr($args['preview_id']); ?>"
            ><?php echo esc_html($args['button_text']); ?></button>
            <?php if ($args['attachment_id_name'] !== '') : ?>
                <button
                    type="button"
                    class="button tm-media-clear-button"
                    style="margin-left: 4px;"
                    data-target="<?php echo esc_attr($args['input_id']); ?>"
                    data-preview="<?php echo esc_attr($args['preview_id']); ?>"
                    data-id-target="<?php echo esc_attr($args['input_id']); ?>_id"
                >Clear</button>
            <?php endif; ?>
        </div>
        <div style="margin-top: 8px;">
            <img
                id="<?php echo esc_attr($args['preview_id']); ?>"
                src="<?php echo esc_url($args['preview_url']); ?>"
                style="max-width: 180px;<?php echo $args['preview_url'] ? '' : ' display:none;'; ?>"
            />
        </div>
        <?php if ($args['description'] !== '') : ?>
            <p class="description"><?php echo esc_html($args['description']); ?></p>
        <?php endif; ?>
    </div>
    <?php
}

function tm_season_builder_render_show_editor($row_key, $field_name, $content, $label) {
    $editor_id = 'tm_sb_' . sanitize_html_class($field_name . '_' . $row_key);
    ?>
    <div class="tm-sb-richtext-field">
        <p><strong><?php echo esc_html($label); ?></strong></p>
        <?php
        wp_editor(
            $content,
            $editor_id,
            array(
                'textarea_name' => 'shows[' . $row_key . '][' . $field_name . ']',
                'textarea_rows' => 6,
                'media_buttons' => false,
                'teeny' => true,
                'quicktags' => true,
            )
        );
        ?>
    </div>
    <?php
}

/**
 * Save data from the Season Builder form.
 */
function tm_season_builder_handle_save() {
    if (empty($_POST['tm_season_builder_submit'])) {
        return null;
    }

    if (!current_user_can('edit_posts')) {
        return array('type' => 'error', 'message' => 'Insufficient permissions.', 'season_id' => 0);
    }

    if (!isset($_POST['tm_season_builder_nonce']) || !wp_verify_nonce($_POST['tm_season_builder_nonce'], 'tm_season_builder_save')) {
        return array('type' => 'error', 'message' => 'Security check failed.', 'season_id' => 0);
    }

    $season = isset($_POST['season']) && is_array($_POST['season']) ? $_POST['season'] : array();
    $season_id = isset($season['id']) ? absint($season['id']) : 0;
    $season_name = sanitize_text_field($season['name'] ?? '');
    $season_start = sanitize_text_field($season['start_date'] ?? '');
    $season_end = sanitize_text_field($season['end_date'] ?? '');
    $season_tickets_url = esc_url_raw($season['tickets_url'] ?? '');
    $season_status = sanitize_text_field($season['status'] ?? 'past');
    
    // Validate status value
    $valid_statuses = array('past', 'current', 'upcoming');
    if (!in_array($season_status, $valid_statuses, true)) {
        $season_status = 'past';
    }
    
    // Convert status to is_current and is_upcoming flags
    $season_is_current = ($season_status === 'current') ? 1 : 0;
    $season_is_upcoming = ($season_status === 'upcoming') ? 1 : 0;

    if ($season_name === '') {
        return array('type' => 'error', 'message' => 'Season Name is required.', 'season_id' => $season_id);
    }

    if ($season_id > 0 && get_post_type($season_id) !== 'season') {
        $season_id = 0;
    }

    if ($season_id > 0) {
        wp_update_post(array(
            'ID' => $season_id,
            'post_title' => $season_name,
        ));
    } else {
        $season_id = wp_insert_post(array(
            'post_type' => 'season',
            'post_status' => 'publish',
            'post_title' => $season_name,
        ));
        if (is_wp_error($season_id)) {
            return array('type' => 'error', 'message' => 'Failed to create season: ' . $season_id->get_error_message(), 'season_id' => 0);
        }
    }

    // If this season is being marked as current, remove is_current from all other seasons
    if ($season_is_current) {
        $args = array(
            'post_type' => 'season',
            'posts_per_page' => -1,
            'exclude' => $season_id,
            'meta_query' => array(
                array(
                    'key' => '_tm_season_is_current',
                    'value' => '1',
                ),
            ),
        );
        $other_current_seasons = get_posts($args);
        foreach ($other_current_seasons as $other_season) {
            delete_post_meta($other_season->ID, '_tm_season_is_current');
        }
    }

    // If this season is being marked as upcoming, remove is_upcoming from all other seasons
    if ($season_is_upcoming) {
        $args = array(
            'post_type' => 'season',
            'posts_per_page' => -1,
            'exclude' => $season_id,
            'meta_query' => array(
                array(
                    'key' => '_tm_season_is_upcoming',
                    'value' => '1',
                ),
            ),
        );
        $other_upcoming_seasons = get_posts($args);
        foreach ($other_upcoming_seasons as $other_season) {
            delete_post_meta($other_season->ID, '_tm_season_is_upcoming');
        }
    }
    
    update_post_meta($season_id, '_tm_season_name', $season_name);
    update_post_meta($season_id, '_tm_season_start_date', $season_start);
    update_post_meta($season_id, '_tm_season_end_date', $season_end);
    update_post_meta($season_id, '_tm_season_tickets_url', $season_tickets_url);
    update_post_meta($season_id, '_tm_season_is_current', $season_is_current);
    update_post_meta($season_id, '_tm_season_is_upcoming', $season_is_upcoming);
    update_post_meta($season_id, '_tm_season_image_front', tm_season_builder_resolve_media_value($season['image_front'] ?? '', $season['image_front_id'] ?? 0));
    update_post_meta($season_id, '_tm_season_image_back', tm_season_builder_resolve_media_value($season['image_back'] ?? '', $season['image_back_id'] ?? 0));
    update_post_meta($season_id, '_tm_season_social_banner', tm_season_builder_resolve_media_value($season['social_banner'] ?? '', $season['social_banner_id'] ?? 0));
    update_post_meta($season_id, '_tm_season_sm_square', tm_season_builder_resolve_media_value($season['sm_square'] ?? '', $season['sm_square_id'] ?? 0));
    update_post_meta($season_id, '_tm_season_sm_portrait', tm_season_builder_resolve_media_value($season['sm_portrait'] ?? '', $season['sm_portrait_id'] ?? 0));

    $existing_show_ids = get_posts(array(
        'post_type' => 'show',
        'numberposts' => -1,
        'fields' => 'ids',
        'meta_key' => '_tm_show_season',
        'meta_value' => $season_id,
    ));
    $existing_show_ids = array_map('intval', $existing_show_ids);

    $existing_cast_ids = array();
    $existing_award_ids = array();
    if (!empty($existing_show_ids)) {
        $existing_cast_ids = get_posts(array(
            'post_type' => 'cast',
            'numberposts' => -1,
            'fields' => 'ids',
            'meta_query' => array(
                array(
                    'key' => '_tm_cast_show',
                    'value' => $existing_show_ids,
                    'compare' => 'IN',
                ),
            ),
        ));
        $existing_cast_ids = array_map('intval', $existing_cast_ids);

        $existing_award_ids = get_posts(array(
            'post_type' => 'award',
            'numberposts' => -1,
            'fields' => 'ids',
            'meta_query' => array(
                array(
                    'key' => '_tm_award_show_id',
                    'value' => $existing_show_ids,
                    'compare' => 'IN',
                ),
            ),
        ));
        $existing_award_ids = array_map('intval', $existing_award_ids);
    }

    $shows_in = isset($_POST['shows']) && is_array($_POST['shows']) ? $_POST['shows'] : array();
    $show_row_key_to_id = array();
    $saved_shows = 0;
    $submitted_show_ids = array();

    $valid_genres = array('Comedy', 'Farce', 'Mystery', 'Drama', 'Musical');

    foreach ($shows_in as $row_key => $row) {
        if (!is_array($row)) {
            continue;
        }

        $show_id = absint($row['id'] ?? 0);
        $show_name = sanitize_text_field($row['name'] ?? '');
        $show_author = sanitize_text_field($row['author'] ?? '');
        $show_sub_authors = sanitize_text_field($row['sub_authors'] ?? '');
        $show_synopsis = sanitize_textarea_field($row['synopsis'] ?? '');
        $show_audition_date = sanitize_text_field($row['audition_date'] ?? '');
        $show_audition_details = wp_kses_post($row['audition_details'] ?? '');
        $show_genre = sanitize_text_field($row['genre'] ?? '');
        $show_director = sanitize_text_field($row['director'] ?? '');
        $show_associate_director = sanitize_text_field($row['associate_director'] ?? '');
        $show_producer = sanitize_text_field($row['producer'] ?? '');
        $show_stage_manager = sanitize_text_field($row['stage_manager'] ?? '');
        $show_time_slot = sanitize_text_field($row['time_slot'] ?? '');
        $show_dates = wp_kses_post($row['show_dates'] ?? '');
        $show_venue = absint($row['venue'] ?? 0);
        $show_tickets_url = esc_url_raw($row['tickets_url'] ?? '');

        if ($show_name === '') {
            continue;
        }

        if (!in_array($show_genre, $valid_genres, true)) {
            $show_genre = '';
        }

        if ($show_id > 0 && get_post_type($show_id) !== 'show') {
            $show_id = 0;
        }

        if ($show_id > 0) {
            wp_update_post(array(
                'ID' => $show_id,
                'post_title' => $show_name,
            ));
        } else {
            $show_id = wp_insert_post(array(
                'post_type' => 'show',
                'post_status' => 'publish',
                'post_title' => $show_name,
            ));
            if (is_wp_error($show_id)) {
                continue;
            }
        }

        update_post_meta($show_id, '_tm_show_name', $show_name);
        update_post_meta($show_id, '_tm_show_author', $show_author);
        update_post_meta($show_id, '_tm_show_sub_authors', $show_sub_authors);
        update_post_meta($show_id, '_tm_show_synopsis', $show_synopsis);
        update_post_meta($show_id, '_tm_show_audition_date', $show_audition_date);
        update_post_meta($show_id, '_tm_show_audition_details', $show_audition_details);
        update_post_meta($show_id, '_tm_show_genre', $show_genre);
        update_post_meta($show_id, '_tm_show_director', $show_director);
        update_post_meta($show_id, '_tm_show_associate_director', $show_associate_director);
        update_post_meta($show_id, '_tm_show_producer', $show_producer);
        update_post_meta($show_id, '_tm_show_stage_manager', $show_stage_manager);
        update_post_meta($show_id, '_tm_show_time_slot', $show_time_slot);
        update_post_meta($show_id, '_tm_show_show_dates', $show_dates);
        if ($show_venue > 0) {
            update_post_meta($show_id, '_tm_show_venue', $show_venue);
        } else {
            delete_post_meta($show_id, '_tm_show_venue');
        }
        update_post_meta($show_id, '_tm_show_tickets_url', $show_tickets_url);
        update_post_meta($show_id, '_tm_show_season', $season_id);
        update_post_meta($show_id, '_tm_show_sm_image', tm_season_builder_resolve_media_value($row['sm_image'] ?? '', $row['sm_image_id'] ?? 0));

        $program_id = absint($row['program_id'] ?? 0);
        $program_url = esc_url_raw($row['program_url'] ?? '');
        if ($program_id > 0 && get_post_type($program_id) === 'attachment') {
            update_post_meta($show_id, '_tm_show_program', $program_id);
            update_post_meta($show_id, '_tm_show_program_url', esc_url_raw(wp_get_attachment_url($program_id)));
        } else {
            $resolved_program_id = $program_url ? attachment_url_to_postid($program_url) : 0;
            if ($resolved_program_id > 0) {
                update_post_meta($show_id, '_tm_show_program', $resolved_program_id);
                update_post_meta($show_id, '_tm_show_program_url', esc_url_raw(wp_get_attachment_url($resolved_program_id)));
            } else {
                update_post_meta($show_id, '_tm_show_program', '');
                update_post_meta($show_id, '_tm_show_program_url', $program_url);
            }
        }

        $show_row_key_to_id[(string) $row_key] = (int) $show_id;
        $submitted_show_ids[] = (int) $show_id;
        $saved_shows++;
    }

    $casts_in = isset($_POST['casts']) && is_array($_POST['casts']) ? $_POST['casts'] : array();
    $saved_cast = 0;
    $submitted_cast_ids = array();
    foreach ($casts_in as $row) {
        if (!is_array($row)) {
            continue;
        }

        $show_ref = (string) ($row['show_ref'] ?? '');
        $show_id = $show_row_key_to_id[$show_ref] ?? 0;
        $character_name = sanitize_text_field($row['character_name'] ?? '');
        $actor_name = sanitize_text_field($row['actor_name'] ?? '');
        $cast_bio = sanitize_textarea_field($row['bio'] ?? '');
        $cast_id = absint($row['id'] ?? 0);

        if ($show_id <= 0 || ($character_name === '' && $actor_name === '')) {
            continue;
        }

        if ($cast_id > 0 && get_post_type($cast_id) !== 'cast') {
            $cast_id = 0;
        }

        if ($cast_id > 0) {
            wp_update_post(array(
                'ID' => $cast_id,
                'post_title' => $character_name,
            ));
        } else {
            $cast_id = wp_insert_post(array(
                'post_type' => 'cast',
                'post_status' => 'publish',
                'post_title' => $character_name,
            ));
            if (is_wp_error($cast_id)) {
                continue;
            }
        }

        update_post_meta($cast_id, '_tm_cast_show', $show_id);
        update_post_meta($cast_id, '_tm_cast_character_name', $character_name);
        update_post_meta($cast_id, '_tm_cast_actor_name', $actor_name);
        update_post_meta($cast_id, '_tm_cast_bio', $cast_bio);
        update_post_meta($cast_id, '_tm_cast_picture', tm_season_builder_resolve_media_value($row['picture'] ?? '', $row['picture_id'] ?? 0));
        $submitted_cast_ids[] = (int) $cast_id;
        $saved_cast++;
    }

    $awards_in = isset($_POST['awards']) && is_array($_POST['awards']) ? $_POST['awards'] : array();
    $saved_awards = 0;
    $submitted_award_ids = array();
    $valid_categories = array('Musical', 'Drama', 'Comedy');
    $valid_statuses = array('Nominated', 'THEA Winner');

    foreach ($awards_in as $row) {
        if (!is_array($row)) {
            continue;
        }

        $show_ref = (string) ($row['show_ref'] ?? '');
        $show_id = $show_row_key_to_id[$show_ref] ?? 0;
        $award_post_id = absint($row['id'] ?? 0);
        $category = sanitize_text_field($row['category'] ?? 'Drama');
        $award_name = sanitize_text_field($row['award_name'] ?? '');
        $recipient = sanitize_text_field($row['recipient'] ?? '');
        $status = sanitize_text_field($row['status'] ?? 'Nominated');

        if ($show_id <= 0 || $award_name === '') {
            continue;
        }

        if (!in_array($category, $valid_categories, true)) {
            $category = 'Drama';
        }

        if (!in_array($status, $valid_statuses, true)) {
            $status = 'Nominated';
        }

        if ($award_post_id > 0 && get_post_type($award_post_id) !== 'award') {
            $award_post_id = 0;
        }

        if ($award_post_id > 0) {
            wp_update_post(array(
                'ID' => $award_post_id,
                'post_title' => $award_name,
            ));
        } else {
            $award_post_id = wp_insert_post(array(
                'post_type' => 'award',
                'post_status' => 'publish',
                'post_title' => $award_name,
            ));
            if (is_wp_error($award_post_id)) {
                continue;
            }
        }

        update_post_meta($award_post_id, '_tm_award_id', (string) $award_post_id);
        update_post_meta($award_post_id, '_tm_award_show_id', $show_id);
        update_post_meta($award_post_id, '_tm_award_category', $category);
        update_post_meta($award_post_id, '_tm_award_name', $award_name);
        update_post_meta($award_post_id, '_tm_award_recipient', $recipient);
        update_post_meta($award_post_id, '_tm_award_status', $status);
        $submitted_award_ids[] = (int) $award_post_id;
        $saved_awards++;
    }

    $trashed_shows = 0;
    $trashed_cast = 0;
    $trashed_awards = 0;

    $shows_to_trash = array_diff($existing_show_ids, $submitted_show_ids);
    foreach ($shows_to_trash as $show_to_trash) {
        if (get_post_type($show_to_trash) === 'show') {
            wp_trash_post((int) $show_to_trash);
            $trashed_shows++;
        }
    }

    $casts_to_trash = array_diff($existing_cast_ids, $submitted_cast_ids);
    foreach ($casts_to_trash as $cast_to_trash) {
        if (get_post_type($cast_to_trash) === 'cast') {
            wp_trash_post((int) $cast_to_trash);
            $trashed_cast++;
        }
    }

    $awards_to_trash = array_diff($existing_award_ids, $submitted_award_ids);
    foreach ($awards_to_trash as $award_to_trash) {
        if (get_post_type($award_to_trash) === 'award') {
            wp_trash_post((int) $award_to_trash);
            $trashed_awards++;
        }
    }

    return array(
        'type' => 'success',
        'message' => sprintf(
            'Saved season and linked data. Shows: %d, Cast: %d, Awards: %d. Trashed - Shows: %d, Cast: %d, Awards: %d.',
            $saved_shows,
            $saved_cast,
            $saved_awards,
            $trashed_shows,
            $trashed_cast,
            $trashed_awards
        ),
        'season_id' => $season_id,
    );
}

/**
 * Render Season Builder admin screen.
 */
function tm_render_season_builder_page() {
    if (function_exists('wp_enqueue_editor')) {
        wp_enqueue_editor();
    }

    $result = tm_season_builder_handle_save();
    $active_tab = sanitize_key($_POST['tm_season_builder_active_tab'] ?? ($_GET['tab'] ?? 'details'));
    if (!in_array($active_tab, array('details', 'media'), true)) {
        $active_tab = 'details';
    }
    if (is_array($result) && ($result['type'] ?? '') === 'error') {
        $active_tab = 'details';
    }

    $season_id = 0;
    if (is_array($result) && !empty($result['season_id'])) {
        $season_id = absint($result['season_id']);
    } elseif (!empty($_GET['season_id'])) {
        $season_id = absint($_GET['season_id']);
    }

    $season_name = '';
    $season_start = '';
    $season_end = '';
    $season_tickets_url = '';
    $season_is_current = 0;
    $season_is_upcoming = 0;
    $season_status = 'past';
    $season_images = array(
        'image_front' => '',
        'image_back' => '',
        'social_banner' => '',
        'sm_square' => '',
        'sm_portrait' => '',
    );
    $show_rows = array();
    $cast_rows = array();
    $award_rows = array();

    if ($season_id > 0 && get_post_type($season_id) === 'season') {
        $season_name = get_post_meta($season_id, '_tm_season_name', true);
        if ($season_name === '') {
            $season_name = get_the_title($season_id);
        }
        $season_start = get_post_meta($season_id, '_tm_season_start_date', true);
        $season_end = get_post_meta($season_id, '_tm_season_end_date', true);
        $season_tickets_url = get_post_meta($season_id, '_tm_season_tickets_url', true);
        $season_is_current = absint(get_post_meta($season_id, '_tm_season_is_current', true));
        $season_is_upcoming = absint(get_post_meta($season_id, '_tm_season_is_upcoming', true));
        
        // Calculate status from flags
        if ($season_is_current) {
            $season_status = 'current';
        } elseif ($season_is_upcoming) {
            $season_status = 'upcoming';
        } else {
            $season_status = 'past';
        }
        
        $season_images = array(
            'image_front' => get_post_meta($season_id, '_tm_season_image_front', true),
            'image_back' => get_post_meta($season_id, '_tm_season_image_back', true),
            'social_banner' => get_post_meta($season_id, '_tm_season_social_banner', true),
            'sm_square' => get_post_meta($season_id, '_tm_season_sm_square', true),
            'sm_portrait' => get_post_meta($season_id, '_tm_season_sm_portrait', true),
        );

        $shows = get_posts(array(
            'post_type' => 'show',
            'numberposts' => -1,
            'meta_key' => '_tm_show_season',
            'meta_value' => $season_id,
            'orderby' => 'title',
            'order' => 'ASC',
        ));

        $show_id_to_row_key = array();
        foreach ($shows as $show) {
            $row_key = 'show_' . $show->ID;
            $show_id_to_row_key[$show->ID] = $row_key;
            $show_rows[$row_key] = array(
                'id' => $show->ID,
                'name' => get_post_meta($show->ID, '_tm_show_name', true) ?: $show->post_title,
                'author' => get_post_meta($show->ID, '_tm_show_author', true),
                'sub_authors' => get_post_meta($show->ID, '_tm_show_sub_authors', true),
                'synopsis' => get_post_meta($show->ID, '_tm_show_synopsis', true),
                'audition_date' => get_post_meta($show->ID, '_tm_show_audition_date', true),
                'audition_details' => get_post_meta($show->ID, '_tm_show_audition_details', true),
                'genre' => get_post_meta($show->ID, '_tm_show_genre', true),
                'director' => get_post_meta($show->ID, '_tm_show_director', true),
                'associate_director' => get_post_meta($show->ID, '_tm_show_associate_director', true),
                'producer' => get_post_meta($show->ID, '_tm_show_producer', true),
                'stage_manager' => get_post_meta($show->ID, '_tm_show_stage_manager', true),
                'time_slot' => get_post_meta($show->ID, '_tm_show_time_slot', true),
                'show_dates' => get_post_meta($show->ID, '_tm_show_show_dates', true),
                'venue' => get_post_meta($show->ID, '_tm_show_venue', true),
                'tickets_url' => get_post_meta($show->ID, '_tm_show_tickets_url', true),
                'sm_image' => get_post_meta($show->ID, '_tm_show_sm_image', true),
                'program_id' => get_post_meta($show->ID, '_tm_show_program', true),
                'program_url' => get_post_meta($show->ID, '_tm_show_program_url', true) ?: wp_get_attachment_url(absint(get_post_meta($show->ID, '_tm_show_program', true))),
            );
        }

        if (!empty($shows)) {
            $show_ids = wp_list_pluck($shows, 'ID');

            $casts = get_posts(array(
                'post_type' => 'cast',
                'numberposts' => -1,
                'meta_query' => array(
                    array(
                        'key' => '_tm_cast_show',
                        'value' => $show_ids,
                        'compare' => 'IN',
                    ),
                ),
                'orderby' => 'title',
                'order' => 'ASC',
            ));

            foreach ($casts as $cast) {
                $cast_show_id = absint(get_post_meta($cast->ID, '_tm_cast_show', true));
                $cast_rows['cast_' . $cast->ID] = array(
                    'id' => $cast->ID,
                    'show_ref' => $show_id_to_row_key[$cast_show_id] ?? '',
                    'character_name' => get_post_meta($cast->ID, '_tm_cast_character_name', true),
                    'actor_name' => get_post_meta($cast->ID, '_tm_cast_actor_name', true),
                    'bio' => get_post_meta($cast->ID, '_tm_cast_bio', true),
                    'picture' => get_post_meta($cast->ID, '_tm_cast_picture', true),
                );
            }

            $awards = get_posts(array(
                'post_type' => 'award',
                'numberposts' => -1,
                'meta_query' => array(
                    array(
                        'key' => '_tm_award_show_id',
                        'value' => $show_ids,
                        'compare' => 'IN',
                    ),
                ),
                'orderby' => 'title',
                'order' => 'ASC',
            ));

            foreach ($awards as $award) {
                $award_show_id = absint(get_post_meta($award->ID, '_tm_award_show_id', true));
                $award_rows['award_' . $award->ID] = array(
                    'id' => $award->ID,
                    'show_ref' => $show_id_to_row_key[$award_show_id] ?? '',
                    'category' => get_post_meta($award->ID, '_tm_award_category', true),
                    'award_name' => get_post_meta($award->ID, '_tm_award_name', true) ?: $award->post_title,
                    'recipient' => get_post_meta($award->ID, '_tm_award_recipient', true),
                    'status' => get_post_meta($award->ID, '_tm_award_status', true),
                );
            }
        }
    }

    if (empty($show_rows)) {
        $show_rows = array(
            'show_new_1' => array('id' => '', 'name' => '', 'author' => '', 'sub_authors' => '', 'synopsis' => '', 'audition_date' => '', 'audition_details' => '', 'genre' => '', 'director' => '', 'associate_director' => '', 'producer' => '', 'stage_manager' => '', 'time_slot' => '', 'show_dates' => '', 'venue' => '', 'tickets_url' => '', 'sm_image' => '', 'program_id' => '', 'program_url' => ''),
        );
    }
    $casts_by_show = array();
    foreach ($cast_rows as $row_key => $row) {
        $show_ref = (string) ($row['show_ref'] ?? '');
        if ($show_ref === '') {
            continue;
        }
        if (!isset($casts_by_show[$show_ref])) {
            $casts_by_show[$show_ref] = array();
        }
        $casts_by_show[$show_ref][$row_key] = $row;
    }

    $awards_by_show = array();
    foreach ($award_rows as $row_key => $row) {
        $show_ref = (string) ($row['show_ref'] ?? '');
        if ($show_ref === '') {
            continue;
        }
        if (!isset($awards_by_show[$show_ref])) {
            $awards_by_show[$show_ref] = array();
        }
        $awards_by_show[$show_ref][$row_key] = $row;
    }

    $all_seasons = get_posts(array(
        'post_type' => 'season',
        'numberposts' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
    ));
    ?>
    <div class="wrap">
        <h1>Season Builder (v1.4)</h1>
        <p>Edit a season and its linked Shows, Cast, and Awards in one screen. Use the Media tab for season images, show media, and cast headshots. Rows removed from this screen are moved to Trash on save.</p>

        <?php if (is_array($result) && !empty($result['message'])) : ?>
            <div class="notice notice-<?php echo esc_attr($result['type'] === 'success' ? 'success' : 'error'); ?> is-dismissible">
                <p><?php echo esc_html($result['message']); ?></p>
            </div>
        <?php endif; ?>

        <form method="get" style="margin: 16px 0 24px;">
            <input type="hidden" name="page" value="tm-season-builder" />
            <label for="tm-season-builder-season-id"><strong>Load Existing Season:</strong></label>
            <select id="tm-season-builder-season-id" name="season_id" style="min-width: 260px; margin-left: 8px;">
                <option value="0">New Season</option>
                <?php foreach ($all_seasons as $season) : ?>
                    <option value="<?php echo esc_attr($season->ID); ?>" <?php selected($season_id, $season->ID); ?>>
                        <?php echo esc_html($season->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="button">Load</button>
        </form>

        <form method="post">
            <?php wp_nonce_field('tm_season_builder_save', 'tm_season_builder_nonce'); ?>
            <input type="hidden" name="season[id]" value="<?php echo esc_attr($season_id); ?>" />
            <input type="hidden" name="tm_season_builder_submit" value="1" />
            <input type="hidden" name="tm_season_builder_active_tab" id="tm-season-builder-active-tab" value="<?php echo esc_attr($active_tab); ?>" />

            <nav class="nav-tab-wrapper" style="margin-bottom: 20px;">
                <a href="#" class="nav-tab <?php echo $active_tab === 'details' ? 'nav-tab-active' : ''; ?> js-sb-tab" data-tab="details">Details</a>
                <a href="#" class="nav-tab <?php echo $active_tab === 'media' ? 'nav-tab-active' : ''; ?> js-sb-tab" data-tab="media">Media</a>
            </nav>

            <div class="tm-sb-tab-panel" data-tab-panel="details" style="<?php echo $active_tab === 'details' ? '' : 'display: none;'; ?>">
                <h2>Season</h2>
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="tm-season-name">Season Name</label></th>
                            <td><input id="tm-season-name" type="text" class="regular-text" name="season[name]" value="<?php echo esc_attr($season_name); ?>" required /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="tm-season-start">Start Date</label></th>
                            <td><input id="tm-season-start" type="date" name="season[start_date]" value="<?php echo esc_attr($season_start); ?>" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="tm-season-end">End Date</label></th>
                            <td><input id="tm-season-end" type="date" name="season[end_date]" value="<?php echo esc_attr($season_end); ?>" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="tm-season-tickets">Tickets URL</label></th>
                            <td><input id="tm-season-tickets" type="url" class="regular-text" name="season[tickets_url]" value="<?php echo esc_attr($season_tickets_url); ?>" placeholder="https://example.com/tickets" /></td>
                        </tr>
                        <tr>
                            <th scope="row">Season Status</th>
                            <td>
                                <select id="tm-season-status" name="season[status]">
                                    <option value="past" <?php selected($season_status, 'past'); ?>>Past Season</option>
                                    <option value="current" <?php selected($season_status, 'current'); ?>>Current Season</option>
                                    <option value="upcoming" <?php selected($season_status, 'upcoming'); ?>>Upcoming Season</option>
                                </select>
                                <p class="description">Only one season can be Current, and only one can be Upcoming.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <h2>Shows</h2>
                <div id="tm-sb-shows-container">
                <?php foreach ($show_rows as $row_key => $row) : ?>
                    <div class="tm-sb-show-card postbox" data-show-ref="<?php echo esc_attr($row_key); ?>" style="margin-bottom: 20px;">
                        <div class="postbox-header" style="padding: 12px 16px;">
                            <h2 class="hndle" style="margin: 0;"><span class="js-show-title"><?php echo esc_html($row['name'] !== '' ? $row['name'] : 'New Show'); ?></span></h2>
                        </div>
                        <div class="inside">
                            <table class="form-table" role="presentation">
                                <tbody>
                                    <tr>
                                        <th scope="row">Show Name</th>
                                        <td>
                                            <input type="hidden" class="js-show-row-key" value="<?php echo esc_attr($row_key); ?>" />
                                            <input type="hidden" name="shows[<?php echo esc_attr($row_key); ?>][id]" value="<?php echo esc_attr($row['id']); ?>" />
                                            <input type="text" class="regular-text js-show-name" name="shows[<?php echo esc_attr($row_key); ?>][name]" value="<?php echo esc_attr($row['name']); ?>" />
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Author</th>
                                        <td><input type="text" class="regular-text" name="shows[<?php echo esc_attr($row_key); ?>][author]" value="<?php echo esc_attr($row['author']); ?>" /></td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Sub-authors</th>
                                        <td><input type="text" class="regular-text" name="shows[<?php echo esc_attr($row_key); ?>][sub_authors]" value="<?php echo esc_attr($row['sub_authors']); ?>" /></td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Genre</th>
                                        <td>
                                            <select name="shows[<?php echo esc_attr($row_key); ?>][genre]">
                                                <option value=""></option>
                                                <option value="Comedy" <?php selected($row['genre'], 'Comedy'); ?>>Comedy</option>
                                                <option value="Farce" <?php selected($row['genre'], 'Farce'); ?>>Farce</option>
                                                <option value="Mystery" <?php selected($row['genre'], 'Mystery'); ?>>Mystery</option>
                                                <option value="Drama" <?php selected($row['genre'], 'Drama'); ?>>Drama</option>
                                                <option value="Musical" <?php selected($row['genre'], 'Musical'); ?>>Musical</option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Audition Date</th>
                                        <td><input type="date" name="shows[<?php echo esc_attr($row_key); ?>][audition_date]" value="<?php echo esc_attr($row['audition_date']); ?>" /></td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Venue</th>
                                        <td>
                                            <select name="shows[<?php echo esc_attr($row_key); ?>][venue]">
                                                <option value="">-- Select Venue --</option>
                                                <?php
                                                $venues_list = get_posts(array(
                                                    'post_type' => 'venue',
                                                    'numberposts' => -1,
                                                    'orderby' => 'title',
                                                    'order' => 'ASC',
                                                ));
                                                foreach ($venues_list as $venue_post) {
                                                    $venue_name = get_post_meta($venue_post->ID, '_tm_venue_name', true) ?: $venue_post->post_title;
                                                    echo '<option value="' . esc_attr($venue_post->ID) . '" ' . selected($row['venue'], $venue_post->ID, false) . '>' . esc_html($venue_name) . '</option>';
                                                }
                                                ?>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Tickets URL</th>
                                        <td><input type="url" class="regular-text" name="shows[<?php echo esc_attr($row_key); ?>][tickets_url]" value="<?php echo esc_attr($row['tickets_url']); ?>" placeholder="https://example.com/tickets" /></td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Director</th>
                                        <td><input type="text" class="regular-text" name="shows[<?php echo esc_attr($row_key); ?>][director]" value="<?php echo esc_attr($row['director']); ?>" /></td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Associate Director</th>
                                        <td><input type="text" class="regular-text" name="shows[<?php echo esc_attr($row_key); ?>][associate_director]" value="<?php echo esc_attr($row['associate_director']); ?>" /></td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Producer</th>
                                        <td><input type="text" class="regular-text" name="shows[<?php echo esc_attr($row_key); ?>][producer]" value="<?php echo esc_attr($row['producer']); ?>" /></td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Stage Manager</th>
                                        <td><input type="text" class="regular-text" name="shows[<?php echo esc_attr($row_key); ?>][stage_manager]" value="<?php echo esc_attr($row['stage_manager']); ?>" /></td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Time Slot</th>
                                        <td>
                                            <select name="shows[<?php echo esc_attr($row_key); ?>][time_slot]">
                                                <option value=""></option>
                                                <option value="Fall" <?php selected($row['time_slot'], 'Fall'); ?>>Fall</option>
                                                <option value="Winter" <?php selected($row['time_slot'], 'Winter'); ?>>Winter</option>
                                                <option value="Spring" <?php selected($row['time_slot'], 'Spring'); ?>>Spring</option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Show Dates</th>
                                        <td><?php tm_season_builder_render_show_editor($row_key, 'show_dates', $row['show_dates'], ''); ?></td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Synopsis</th>
                                        <td><textarea class="large-text" rows="4" name="shows[<?php echo esc_attr($row_key); ?>][synopsis]"><?php echo esc_textarea($row['synopsis']); ?></textarea></td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Audition Details</th>
                                        <td><?php tm_season_builder_render_show_editor($row_key, 'audition_details', $row['audition_details'], ''); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                            <p><button type="button" class="button button-link-delete js-remove-show">Remove Show</button></p>

                            <h3>Cast</h3>
                            <table class="widefat striped tm-sb-cast-table">
                                <thead>
                                    <tr>
                                        <th style="width: 28%;">Character Name</th>
                                        <th style="width: 28%;">Actor Name</th>
                                        <th style="width: 28%;">Bio</th>
                                        <th style="width: 16%;">Remove</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($casts_by_show[$row_key] ?? array() as $cast_key => $cast_row) : ?>
                                        <tr class="tm-sb-cast-row">
                                            <td>
                                                <input type="hidden" name="casts[<?php echo esc_attr($cast_key); ?>][id]" value="<?php echo esc_attr($cast_row['id']); ?>" />
                                                <input type="hidden" name="casts[<?php echo esc_attr($cast_key); ?>][show_ref]" value="<?php echo esc_attr($row_key); ?>" />
                                                <input type="text" class="widefat" name="casts[<?php echo esc_attr($cast_key); ?>][character_name]" value="<?php echo esc_attr($cast_row['character_name']); ?>" />
                                            </td>
                                            <td><input type="text" class="widefat" name="casts[<?php echo esc_attr($cast_key); ?>][actor_name]" value="<?php echo esc_attr($cast_row['actor_name']); ?>" /></td>
                                            <td><textarea class="widefat" rows="2" name="casts[<?php echo esc_attr($cast_key); ?>][bio]"><?php echo esc_textarea($cast_row['bio']); ?></textarea></td>
                                            <td><button type="button" class="button button-link-delete js-remove-cast">Remove</button></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <p><button type="button" class="button js-add-cast" data-show-ref="<?php echo esc_attr($row_key); ?>">Add Cast Row</button></p>

                            <h3>Awards</h3>
                            <table class="widefat striped tm-sb-awards-table">
                                <thead>
                                    <tr>
                                        <th style="width: 22%;">Category</th>
                                        <th style="width: 30%;">Award Name</th>
                                        <th style="width: 26%;">Recipient</th>
                                        <th style="width: 14%;">Status</th>
                                        <th style="width: 8%;">Remove</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($awards_by_show[$row_key] ?? array() as $award_key => $award_row) : ?>
                                        <tr class="tm-sb-award-row">
                                            <td>
                                                <input type="hidden" name="awards[<?php echo esc_attr($award_key); ?>][id]" value="<?php echo esc_attr($award_row['id']); ?>" />
                                                <input type="hidden" name="awards[<?php echo esc_attr($award_key); ?>][show_ref]" value="<?php echo esc_attr($row_key); ?>" />
                                                <select class="widefat" name="awards[<?php echo esc_attr($award_key); ?>][category]">
                                                    <option value="Musical" <?php selected($award_row['category'], 'Musical'); ?>>Musical</option>
                                                    <option value="Drama" <?php selected($award_row['category'], 'Drama'); ?>>Drama</option>
                                                    <option value="Comedy" <?php selected($award_row['category'], 'Comedy'); ?>>Comedy</option>
                                                </select>
                                            </td>
                                            <td><input type="text" class="widefat" name="awards[<?php echo esc_attr($award_key); ?>][award_name]" value="<?php echo esc_attr($award_row['award_name']); ?>" /></td>
                                            <td><input type="text" class="widefat" name="awards[<?php echo esc_attr($award_key); ?>][recipient]" value="<?php echo esc_attr($award_row['recipient']); ?>" /></td>
                                            <td>
                                                <select class="widefat" name="awards[<?php echo esc_attr($award_key); ?>][status]">
                                                    <option value="Nominated" <?php selected($award_row['status'], 'Nominated'); ?>>Nominated</option>
                                                    <option value="THEA Winner" <?php selected($award_row['status'], 'THEA Winner'); ?>>THEA Winner</option>
                                                </select>
                                            </td>
                                            <td><button type="button" class="button button-link-delete js-remove-award">Remove</button></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <p><button type="button" class="button js-add-award" data-show-ref="<?php echo esc_attr($row_key); ?>">Add Award Row</button></p>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
                <p><button type="button" class="button" id="tm-sb-add-show">Add Show</button></p>
            </div>

            <div class="tm-sb-tab-panel" data-tab-panel="media" style="<?php echo $active_tab === 'media' ? '' : 'display: none;'; ?>">
                <div class="postbox" style="margin-bottom: 20px;">
                    <div class="postbox-header" style="padding: 12px 16px;">
                        <h2 class="hndle" style="margin: 0;">Season Media</h2>
                    </div>
                    <div class="inside">
                        <?php
                        tm_season_builder_render_media_field(array(
                            'label' => '3-up Front Image',
                            'input_name' => 'season[image_front]',
                            'input_id' => 'tm-season-image-front',
                            'value' => tm_get_image_url($season_images['image_front']),
                            'attachment_id_name' => 'season[image_front_id]',
                            'attachment_id' => is_numeric($season_images['image_front']) ? $season_images['image_front'] : '',
                            'preview_id' => 'tm-season-image-front-preview',
                            'preview_url' => tm_get_image_url($season_images['image_front']),
                            'button_text' => 'Select Image',
                        ));
                        tm_season_builder_render_media_field(array(
                            'label' => '3-up Back Image',
                            'input_name' => 'season[image_back]',
                            'input_id' => 'tm-season-image-back',
                            'value' => tm_get_image_url($season_images['image_back']),
                            'attachment_id_name' => 'season[image_back_id]',
                            'attachment_id' => is_numeric($season_images['image_back']) ? $season_images['image_back'] : '',
                            'preview_id' => 'tm-season-image-back-preview',
                            'preview_url' => tm_get_image_url($season_images['image_back']),
                            'button_text' => 'Select Image',
                        ));
                        tm_season_builder_render_media_field(array(
                            'label' => 'Website Banner',
                            'input_name' => 'season[social_banner]',
                            'input_id' => 'tm-season-social-banner',
                            'value' => tm_get_image_url($season_images['social_banner']),
                            'attachment_id_name' => 'season[social_banner_id]',
                            'attachment_id' => is_numeric($season_images['social_banner']) ? $season_images['social_banner'] : '',
                            'preview_id' => 'tm-season-social-banner-preview',
                            'preview_url' => tm_get_image_url($season_images['social_banner']),
                            'button_text' => 'Select Image',
                        ));
                        tm_season_builder_render_media_field(array(
                            'label' => 'Social Media Square',
                            'input_name' => 'season[sm_square]',
                            'input_id' => 'tm-season-sm-square',
                            'value' => tm_get_image_url($season_images['sm_square']),
                            'attachment_id_name' => 'season[sm_square_id]',
                            'attachment_id' => is_numeric($season_images['sm_square']) ? $season_images['sm_square'] : '',
                            'preview_id' => 'tm-season-sm-square-preview',
                            'preview_url' => tm_get_image_url($season_images['sm_square']),
                            'button_text' => 'Select Image',
                        ));
                        tm_season_builder_render_media_field(array(
                            'label' => 'Social Media Portrait',
                            'input_name' => 'season[sm_portrait]',
                            'input_id' => 'tm-season-sm-portrait',
                            'value' => tm_get_image_url($season_images['sm_portrait']),
                            'attachment_id_name' => 'season[sm_portrait_id]',
                            'attachment_id' => is_numeric($season_images['sm_portrait']) ? $season_images['sm_portrait'] : '',
                            'preview_id' => 'tm-season-sm-portrait-preview',
                            'preview_url' => tm_get_image_url($season_images['sm_portrait']),
                            'button_text' => 'Select Image',
                        ));
                        ?>
                    </div>
                </div>

                <div id="tm-sb-show-media-container">
                    <?php foreach ($show_rows as $row_key => $row) : ?>
                        <div class="tm-sb-show-media-card postbox" data-show-ref="<?php echo esc_attr($row_key); ?>" style="margin-bottom: 20px;">
                            <div class="postbox-header" style="padding: 12px 16px;">
                                <h2 class="hndle" style="margin: 0;"><span class="js-show-title"><?php echo esc_html($row['name'] !== '' ? $row['name'] : 'New Show'); ?></span> Media</h2>
                            </div>
                            <div class="inside">
                                <?php
                                tm_season_builder_render_media_field(array(
                                    'label' => 'Show Image',
                                    'input_name' => 'shows[' . $row_key . '][sm_image]',
                                    'input_id' => 'tm-show-sm-image-' . $row_key,
                                    'value' => tm_get_image_url($row['sm_image']),
                                    'attachment_id_name' => 'shows[' . $row_key . '][sm_image_id]',
                                    'attachment_id' => is_numeric($row['sm_image']) ? $row['sm_image'] : '',
                                    'preview_id' => 'tm-show-sm-image-preview-' . $row_key,
                                    'preview_url' => tm_get_image_url($row['sm_image']),
                                    'button_text' => 'Select Image',
                                ));
                                tm_season_builder_render_media_field(array(
                                    'label' => 'Program PDF',
                                    'input_name' => 'shows[' . $row_key . '][program_url]',
                                    'input_id' => 'tm-show-program-' . $row_key,
                                    'value' => $row['program_url'],
                                    'attachment_id_name' => 'shows[' . $row_key . '][program_id]',
                                    'attachment_id' => $row['program_id'],
                                    'preview_id' => 'tm-show-program-preview-' . $row_key,
                                    'preview_url' => tm_season_builder_get_program_preview_url($row['program_id'], $row['program_url']),
                                    'button_text' => 'Select PDF',
                                    'description' => 'Upload a PDF or paste a direct program URL.',
                                ));
                                ?>

                                <h3>Cast Headshots</h3>
                                <div class="tm-sb-cast-media-list">
                                    <?php foreach ($casts_by_show[$row_key] ?? array() as $cast_key => $cast_row) : ?>
                                        <div class="tm-sb-cast-media-item" data-cast-key="<?php echo esc_attr($cast_key); ?>" style="border-top: 1px solid #dcdcde; padding-top: 12px; margin-top: 12px;">
                                            <p><strong class="js-cast-label"><?php echo esc_html($cast_row['character_name'] !== '' ? $cast_row['character_name'] : 'New Cast Row'); ?></strong></p>
                                            <?php
                                            tm_season_builder_render_media_field(array(
                                                'label' => 'Headshot',
                                                'input_name' => 'casts[' . $cast_key . '][picture]',
                                                'input_id' => 'tm-cast-picture-' . $cast_key,
                                                'value' => tm_get_image_url($cast_row['picture'] ?? ''),
                                                'attachment_id_name' => 'casts[' . $cast_key . '][picture_id]',
                                                'attachment_id' => is_numeric($cast_row['picture'] ?? '') ? $cast_row['picture'] : '',
                                                'preview_id' => 'tm-cast-picture-preview-' . $cast_key,
                                                'preview_url' => tm_get_image_url($cast_row['picture'] ?? ''),
                                                'button_text' => 'Select Image',
                                            ));
                                            ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <p class="description">Add cast rows on the Details tab to manage their headshots here.</p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <p>
                <button type="submit" class="button button-primary">Save Season Builder</button>
            </p>
        </form>
    </div>

    <script>
    (function($){
        var showCounter = 2;
        var castCounter = 2;
        var awardCounter = 2;
        var builderForm = $('form[method="post"]').first();
        var activeTabInput = $('#tm-season-builder-active-tab');
        var currentActiveTab = activeTabInput.val() || 'details';

        function escHtml(value) {
            return String(value || '').replace(/[&<>'"]/g, function(ch) {
                return {'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[ch];
            });
        }

        function showTab(tab) {
            currentActiveTab = tab;
            activeTabInput.val(tab);
            $('.js-sb-tab').removeClass('nav-tab-active');
            $('.js-sb-tab[data-tab="' + tab + '"]').addClass('nav-tab-active');
            $('.tm-sb-tab-panel').hide();
            $('.tm-sb-tab-panel[data-tab-panel="' + tab + '"]').show();
        }

        function initRichEditor(editorId) {
            if (typeof wp === 'undefined' || !wp.editor || !wp.editor.initialize) {
                return;
            }
            if ($('#' + editorId + '-wrap').length) {
                return;
            }
            wp.editor.initialize(editorId, {
                tinymce: {
                    wpautop: true,
                    plugins: 'lists,paste,wordpress,wpautoresize,wpeditimage,wplink',
                    toolbar1: 'bold,italic,bullist,numlist,blockquote,link,unlink,undo,redo'
                },
                quicktags: true,
                mediaButtons: false
            });
        }

        function getMediaCard(showRef) {
            return $('.tm-sb-show-media-card[data-show-ref="' + showRef + '"]');
        }

        function updateShowTitles(showRef, title) {
            var nextTitle = title || 'New Show';
            $('.tm-sb-show-card[data-show-ref="' + showRef + '"] .js-show-title').first().text(nextTitle);
            getMediaCard(showRef).find('.js-show-title').first().text(nextTitle);
        }

        function createCastMediaItem(showRef, rowKey, label) {
            return '' +
                '<div class="tm-sb-cast-media-item" data-cast-key="' + escHtml(rowKey) + '" style="border-top: 1px solid #dcdcde; padding-top: 12px; margin-top: 12px;">' +
                '  <p><strong class="js-cast-label">' + escHtml(label || 'New Cast Row') + '</strong></p>' +
                '  <div class="tm-sb-media-field" style="margin-bottom: 16px;">' +
                '    <label for="tm-cast-picture-' + escHtml(rowKey) + '"><strong>Headshot</strong></label>' +
                '    <div style="margin-top: 6px;">' +
                '      <input type="text" class="regular-text" style="min-width: 320px;" name="casts[' + escHtml(rowKey) + '][picture]" id="tm-cast-picture-' + escHtml(rowKey) + '" value="" />' +
                '      <input type="hidden" name="casts[' + escHtml(rowKey) + '][picture_id]" id="tm-cast-picture-' + escHtml(rowKey) + '_id" value="" />' +
                '      <button type="button" class="button tm-media-button" data-target="tm-cast-picture-' + escHtml(rowKey) + '" data-preview="tm-cast-picture-preview-' + escHtml(rowKey) + '">Select Image</button>' +
                '    </div>' +
                '    <div style="margin-top: 8px;"><img id="tm-cast-picture-preview-' + escHtml(rowKey) + '" src="" style="max-width: 180px; display:none;" /></div>' +
                '  </div>' +
                '</div>';
        }

        function createShowMediaCard(rowKey) {
            return '' +
                '<div class="tm-sb-show-media-card postbox" data-show-ref="' + escHtml(rowKey) + '" style="margin-bottom: 20px;">' +
                '  <div class="postbox-header" style="padding: 12px 16px;">' +
                '    <h2 class="hndle" style="margin: 0;"><span class="js-show-title">New Show</span> Media</h2>' +
                '  </div>' +
                '  <div class="inside">' +
                '    <div class="tm-sb-media-field" style="margin-bottom: 16px;">' +
                '      <label for="tm-show-sm-image-' + escHtml(rowKey) + '"><strong>Show Image</strong></label>' +
                '      <div style="margin-top: 6px;">' +
                '        <input type="text" class="regular-text" style="min-width: 320px;" name="shows[' + escHtml(rowKey) + '][sm_image]" id="tm-show-sm-image-' + escHtml(rowKey) + '" value="" />' +
                '        <input type="hidden" name="shows[' + escHtml(rowKey) + '][sm_image_id]" id="tm-show-sm-image-' + escHtml(rowKey) + '_id" value="" />' +
                '        <button type="button" class="button tm-media-button" data-target="tm-show-sm-image-' + escHtml(rowKey) + '" data-preview="tm-show-sm-image-preview-' + escHtml(rowKey) + '">Select Image</button>' +
                '      </div>' +
                '      <div style="margin-top: 8px;"><img id="tm-show-sm-image-preview-' + escHtml(rowKey) + '" src="" style="max-width: 180px; display:none;" /></div>' +
                '    </div>' +
                '    <div class="tm-sb-media-field" style="margin-bottom: 16px;">' +
                '      <label for="tm-show-program-' + escHtml(rowKey) + '"><strong>Program PDF</strong></label>' +
                '      <div style="margin-top: 6px;">' +
                '        <input type="text" class="regular-text" style="min-width: 320px;" name="shows[' + escHtml(rowKey) + '][program_url]" id="tm-show-program-' + escHtml(rowKey) + '" value="" />' +
                '        <input type="hidden" name="shows[' + escHtml(rowKey) + '][program_id]" id="tm-show-program-' + escHtml(rowKey) + '_id" value="" />' +
                '        <button type="button" class="button tm-media-button" data-target="tm-show-program-' + escHtml(rowKey) + '" data-preview="tm-show-program-preview-' + escHtml(rowKey) + '">Select PDF</button>' +
                '      </div>' +
                '      <div style="margin-top: 8px;"><img id="tm-show-program-preview-' + escHtml(rowKey) + '" src="" style="max-width: 180px; display:none;" /></div>' +
                '      <p class="description">Upload a PDF or paste a direct program URL.</p>' +
                '    </div>' +
                '    <h3>Cast Headshots</h3>' +
                '    <div class="tm-sb-cast-media-list"></div>' +
                '    <p class="description">Add cast rows on the Details tab to manage their headshots here.</p>' +
                '  </div>' +
                '</div>';
        }

        function addShowRow() {
            var rowKey = 'show_new_' + showCounter++;
            var html = '' +
                '<div class="tm-sb-show-card postbox" data-show-ref="' + escHtml(rowKey) + '" style="margin-bottom: 20px;">' +
                '  <div class="postbox-header" style="padding: 12px 16px;">' +
                '    <h2 class="hndle" style="margin: 0;"><span class="js-show-title">New Show</span></h2>' +
                '  </div>' +
                '  <div class="inside">' +
                '    <table class="form-table" role="presentation">' +
                '      <tbody>' +
                '        <tr>' +
                '          <th scope="row">Show Name</th>' +
                '          <td>' +
                '            <input type="hidden" class="js-show-row-key" value="' + escHtml(rowKey) + '" />' +
                '            <input type="hidden" name="shows[' + escHtml(rowKey) + '][id]" value="" />' +
                '            <input type="text" class="regular-text js-show-name" name="shows[' + escHtml(rowKey) + '][name]" value="" />' +
                '          </td>' +
                '        </tr>' +
                '        <tr><th scope="row">Author</th><td><input type="text" class="regular-text" name="shows[' + escHtml(rowKey) + '][author]" value="" /></td></tr>' +
                '        <tr><th scope="row">Sub-authors</th><td><input type="text" class="regular-text" name="shows[' + escHtml(rowKey) + '][sub_authors]" value="" /></td></tr>' +
                '        <tr>' +
                '          <th scope="row">Genre</th>' +
                '          <td>' +
                '            <select name="shows[' + escHtml(rowKey) + '][genre]">' +
                '              <option value=""></option>' +
                '              <option value="Comedy">Comedy</option>' +
                '              <option value="Farce">Farce</option>' +
                '              <option value="Mystery">Mystery</option>' +
                '              <option value="Drama">Drama</option>' +
                '              <option value="Musical">Musical</option>' +
                '            </select>' +
                '          </td>' +
                '        </tr>' +
                '        <tr><th scope="row">Audition Date</th><td><input type="date" name="shows[' + escHtml(rowKey) + '][audition_date]" value="" /></td></tr>' +
                '        <tr><th scope="row">Director</th><td><input type="text" class="regular-text" name="shows[' + escHtml(rowKey) + '][director]" value="" /></td></tr>' +
                '        <tr><th scope="row">Associate Director</th><td><input type="text" class="regular-text" name="shows[' + escHtml(rowKey) + '][associate_director]" value="" /></td></tr>' +
                '        <tr><th scope="row">Producer</th><td><input type="text" class="regular-text" name="shows[' + escHtml(rowKey) + '][producer]" value="" /></td></tr>' +
                '        <tr><th scope="row">Stage Manager</th><td><input type="text" class="regular-text" name="shows[' + escHtml(rowKey) + '][stage_manager]" value="" /></td></tr>' +
                '        <tr>' +
                '          <th scope="row">Time Slot</th>' +
                '          <td>' +
                '            <select name="shows[' + escHtml(rowKey) + '][time_slot]">' +
                '              <option value=""></option>' +
                '              <option value="Fall">Fall</option>' +
                '              <option value="Winter">Winter</option>' +
                '              <option value="Spring">Spring</option>' +
                '            </select>' +
                '          </td>' +
                '        </tr>' +
                '        <tr><th scope="row">Show Dates</th><td><textarea id="tm_sb_show_dates_' + escHtml(rowKey) + '" class="large-text" rows="3" name="shows[' + escHtml(rowKey) + '][show_dates]"></textarea></td></tr>' +
                '        <tr><th scope="row">Synopsis</th><td><textarea class="large-text" rows="4" name="shows[' + escHtml(rowKey) + '][synopsis]"></textarea></td></tr>' +
                '        <tr><th scope="row">Audition Details</th><td><textarea id="tm_sb_audition_details_' + escHtml(rowKey) + '" name="shows[' + escHtml(rowKey) + '][audition_details]" rows="6"></textarea></td></tr>' +
                '      </tbody>' +
                '    </table>' +
                '    <p><button type="button" class="button button-link-delete js-remove-show">Remove Show</button></p>' +
                '    <h3>Cast</h3>' +
                '    <table class="widefat striped tm-sb-cast-table">' +
                '      <thead><tr><th style="width: 28%;">Character Name</th><th style="width: 28%;">Actor Name</th><th style="width: 28%;">Bio</th><th style="width: 16%;">Remove</th></tr></thead>' +
                '      <tbody></tbody>' +
                '    </table>' +
                '    <p><button type="button" class="button js-add-cast" data-show-ref="' + escHtml(rowKey) + '">Add Cast Row</button></p>' +
                '    <h3>Awards</h3>' +
                '    <table class="widefat striped tm-sb-awards-table">' +
                '      <thead><tr><th style="width: 22%;">Category</th><th style="width: 30%;">Award Name</th><th style="width: 26%;">Recipient</th><th style="width: 14%;">Status</th><th style="width: 8%;">Remove</th></tr></thead>' +
                '      <tbody></tbody>' +
                '    </table>' +
                '    <p><button type="button" class="button js-add-award" data-show-ref="' + escHtml(rowKey) + '">Add Award Row</button></p>' +
                '  </div>' +
                '</div>';
            $('#tm-sb-shows-container').append(html);
            $('#tm-sb-show-media-container').append(createShowMediaCard(rowKey));
            updateShowTitles(rowKey, 'New Show');
            initRichEditor('tm_sb_show_dates_' + rowKey);
            initRichEditor('tm_sb_audition_details_' + rowKey);
        }

        function addCastRow(showRef) {
            var rowKey = 'cast_new_' + castCounter++;
            var html = '' +
                '<tr class="tm-sb-cast-row">' +
                '  <td>' +
                '    <input type="hidden" name="casts[' + escHtml(rowKey) + '][id]" value="" />' +
                '    <input type="hidden" name="casts[' + escHtml(rowKey) + '][show_ref]" value="' + escHtml(showRef) + '" />' +
                '    <input type="text" class="widefat" name="casts[' + escHtml(rowKey) + '][character_name]" value="" />' +
                '  </td>' +
                '  <td><input type="text" class="widefat" name="casts[' + escHtml(rowKey) + '][actor_name]" value="" /></td>' +
                '  <td><textarea class="widefat" rows="2" name="casts[' + escHtml(rowKey) + '][bio]"></textarea></td>' +
                '  <td><button type="button" class="button button-link-delete js-remove-cast">Remove</button></td>' +
                '</tr>';
            $('.tm-sb-show-card[data-show-ref="' + showRef + '"] .tm-sb-cast-table tbody').append(html);
            getMediaCard(showRef).find('.tm-sb-cast-media-list').append(createCastMediaItem(showRef, rowKey, 'New Cast Row'));
        }

        function addAwardRow(showRef) {
            var rowKey = 'award_new_' + awardCounter++;
            var html = '' +
                '<tr class="tm-sb-award-row">' +
                '  <td>' +
                '    <input type="hidden" name="awards[' + escHtml(rowKey) + '][id]" value="" />' +
                '    <input type="hidden" name="awards[' + escHtml(rowKey) + '][show_ref]" value="' + escHtml(showRef) + '" />' +
                '    <select class="widefat" name="awards[' + escHtml(rowKey) + '][category]">' +
                '      <option value="Musical">Musical</option>' +
                '      <option value="Drama" selected>Drama</option>' +
                '      <option value="Comedy">Comedy</option>' +
                '    </select>' +
                '  </td>' +
                '  <td><input type="text" class="widefat" name="awards[' + escHtml(rowKey) + '][award_name]" value="" /></td>' +
                '  <td><input type="text" class="widefat" name="awards[' + escHtml(rowKey) + '][recipient]" value="" /></td>' +
                '  <td>' +
                '    <select class="widefat" name="awards[' + escHtml(rowKey) + '][status]">' +
                '      <option value="Nominated" selected>Nominated</option>' +
                '      <option value="THEA Winner">THEA Winner</option>' +
                '    </select>' +
                '  </td>' +
                '  <td><button type="button" class="button button-link-delete js-remove-award">Remove</button></td>' +
                '</tr>';
            $('.tm-sb-show-card[data-show-ref="' + showRef + '"] .tm-sb-awards-table tbody').append(html);
        }

        $(document).on('input', '.js-show-name', function(){
            var showCard = $(this).closest('.tm-sb-show-card');
            updateShowTitles(showCard.attr('data-show-ref'), $(this).val());
        });
        $(document).on('click', '.js-sb-tab', function(e){
            e.preventDefault();
            var tab = $(this).attr('data-tab');
            if (tab === currentActiveTab) {
                return;
            }
            activeTabInput.val(tab);
            builderForm.trigger('submit');
        });
        $(document).on('click', '#tm-sb-add-show', addShowRow);
        $(document).on('click', '.js-add-cast', function(){
            addCastRow($(this).attr('data-show-ref'));
        });
        $(document).on('click', '.js-add-award', function(){
            addAwardRow($(this).attr('data-show-ref'));
        });

        $(document).on('click', '.js-remove-show', function(){
            if ($('#tm-sb-shows-container .tm-sb-show-card').length > 1) {
                var card = $(this).closest('.tm-sb-show-card');
                var showRef = card.attr('data-show-ref');
                card.remove();
                getMediaCard(showRef).remove();
            }
        });

        $(document).on('click', '.js-remove-cast', function(){
            var row = $(this).closest('tr');
            var input = row.find('input[name*="[id]"]');
            var rowName = input.attr('name') || '';
            var match = rowName.match(/^casts\[([^\]]+)\]/);
            if (match) {
                $('.tm-sb-cast-media-item[data-cast-key="' + match[1] + '"]').remove();
            }
            row.remove();
        });

        $(document).on('click', '.js-remove-award', function(){
            $(this).closest('tr').remove();
        });

        $(document).on('input', '.tm-sb-cast-table input[name*="[character_name]"]', function(){
            var row = $(this).closest('tr');
            var input = row.find('input[name*="[id]"]');
            var rowName = input.attr('name') || '';
            var match = rowName.match(/^casts\[([^\]]+)\]/);
            if (match) {
                $('.tm-sb-cast-media-item[data-cast-key="' + match[1] + '"] .js-cast-label').text($(this).val() || 'New Cast Row');
            }
        });

        showTab(currentActiveTab);
        $('.tm-sb-richtext-field textarea[id]').each(function(){
            initRichEditor($(this).attr('id'));
        });

    })(jQuery);
    </script>
    <?php
}
