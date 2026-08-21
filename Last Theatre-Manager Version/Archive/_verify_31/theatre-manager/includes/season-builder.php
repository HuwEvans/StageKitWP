<?php
// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Register Season Builder submenu page.
 */
function tm_register_season_builder_menu() {
    add_submenu_page(
        'theatre-manager',
        'Season Builder',
        'Season Builder',
        'edit_posts',
        'tm-season-builder',
        'tm_render_season_builder_page'
    );
}
add_action('admin_menu', 'tm_register_season_builder_menu');

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

    update_post_meta($season_id, '_tm_season_name', $season_name);
    update_post_meta($season_id, '_tm_season_start_date', $season_start);
    update_post_meta($season_id, '_tm_season_end_date', $season_end);

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

    foreach ($shows_in as $row_key => $row) {
        if (!is_array($row)) {
            continue;
        }

        $show_id = absint($row['id'] ?? 0);
        $show_name = sanitize_text_field($row['name'] ?? '');
        $show_author = sanitize_text_field($row['author'] ?? '');
        $show_time_slot = sanitize_text_field($row['time_slot'] ?? '');

        if ($show_name === '') {
            continue;
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
        update_post_meta($show_id, '_tm_show_time_slot', $show_time_slot);
        update_post_meta($show_id, '_tm_show_season', $season_id);

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
        $award_business_id = sanitize_text_field($row['award_id'] ?? '');
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

        update_post_meta($award_post_id, '_tm_award_id', $award_business_id);
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
    $result = tm_season_builder_handle_save();

    $season_id = 0;
    if (is_array($result) && !empty($result['season_id'])) {
        $season_id = absint($result['season_id']);
    } elseif (!empty($_GET['season_id'])) {
        $season_id = absint($_GET['season_id']);
    }

    $season_name = '';
    $season_start = '';
    $season_end = '';
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
                'time_slot' => get_post_meta($show->ID, '_tm_show_time_slot', true),
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
                    'award_id' => get_post_meta($award->ID, '_tm_award_id', true),
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
            'show_new_1' => array('id' => '', 'name' => '', 'author' => '', 'time_slot' => ''),
        );
    }
    if (empty($cast_rows)) {
        $first_show_key = array_key_first($show_rows);
        $cast_rows = array(
            'cast_new_1' => array('id' => '', 'show_ref' => $first_show_key, 'character_name' => '', 'actor_name' => ''),
        );
    }
    if (empty($award_rows)) {
        $first_show_key = array_key_first($show_rows);
        $award_rows = array(
            'award_new_1' => array('id' => '', 'show_ref' => $first_show_key, 'award_id' => '', 'category' => 'Drama', 'award_name' => '', 'recipient' => '', 'status' => 'Nominated'),
        );
    }

    $all_seasons = get_posts(array(
        'post_type' => 'season',
        'numberposts' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
    ));
    ?>
    <div class="wrap">
        <h1>Season Builder (v1.1)</h1>
        <p>Edit a season and its linked Shows, Cast, and Awards in one screen. Rows removed from this screen are moved to Trash on save.</p>

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
                </tbody>
            </table>

            <h2>Shows</h2>
            <table class="widefat striped" id="tm-sb-shows-table">
                <thead>
                    <tr>
                        <th style="width: 32%;">Show Name</th>
                        <th style="width: 30%;">Author</th>
                        <th style="width: 20%;">Time Slot</th>
                        <th style="width: 18%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($show_rows as $row_key => $row) : ?>
                        <tr class="tm-sb-show-row">
                            <td>
                                <input type="hidden" class="js-show-row-key" value="<?php echo esc_attr($row_key); ?>" />
                                <input type="hidden" name="shows[<?php echo esc_attr($row_key); ?>][id]" value="<?php echo esc_attr($row['id']); ?>" />
                                <input type="text" class="widefat js-show-name" name="shows[<?php echo esc_attr($row_key); ?>][name]" value="<?php echo esc_attr($row['name']); ?>" />
                            </td>
                            <td><input type="text" class="widefat" name="shows[<?php echo esc_attr($row_key); ?>][author]" value="<?php echo esc_attr($row['author']); ?>" /></td>
                            <td>
                                <select class="widefat" name="shows[<?php echo esc_attr($row_key); ?>][time_slot]">
                                    <option value=""></option>
                                    <option value="Fall" <?php selected($row['time_slot'], 'Fall'); ?>>Fall</option>
                                    <option value="Winter" <?php selected($row['time_slot'], 'Winter'); ?>>Winter</option>
                                    <option value="Spring" <?php selected($row['time_slot'], 'Spring'); ?>>Spring</option>
                                </select>
                            </td>
                            <td>
                                <button type="button" class="button button-link-delete js-remove-show">Remove</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p><button type="button" class="button" id="tm-sb-add-show">Add Show</button></p>

            <h2>Cast</h2>
            <table class="widefat striped" id="tm-sb-cast-table">
                <thead>
                    <tr>
                        <th style="width: 26%;">Show</th>
                        <th style="width: 28%;">Character Name</th>
                        <th style="width: 28%;">Actor Name</th>
                        <th style="width: 18%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cast_rows as $row_key => $row) : ?>
                        <tr class="tm-sb-cast-row">
                            <td>
                                <input type="hidden" name="casts[<?php echo esc_attr($row_key); ?>][id]" value="<?php echo esc_attr($row['id']); ?>" />
                                <select class="widefat js-show-ref" name="casts[<?php echo esc_attr($row_key); ?>][show_ref]" data-selected="<?php echo esc_attr($row['show_ref']); ?>"></select>
                            </td>
                            <td><input type="text" class="widefat" name="casts[<?php echo esc_attr($row_key); ?>][character_name]" value="<?php echo esc_attr($row['character_name']); ?>" /></td>
                            <td><input type="text" class="widefat" name="casts[<?php echo esc_attr($row_key); ?>][actor_name]" value="<?php echo esc_attr($row['actor_name']); ?>" /></td>
                            <td><button type="button" class="button button-link-delete js-remove-cast">Remove</button></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p><button type="button" class="button" id="tm-sb-add-cast">Add Cast Row</button></p>

            <h2>Awards</h2>
            <table class="widefat striped" id="tm-sb-awards-table">
                <thead>
                    <tr>
                        <th style="width: 16%;">Award ID</th>
                        <th style="width: 18%;">Show</th>
                        <th style="width: 14%;">Category</th>
                        <th style="width: 20%;">Award Name</th>
                        <th style="width: 18%;">Recipient</th>
                        <th style="width: 10%;">Status</th>
                        <th style="width: 4%;"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($award_rows as $row_key => $row) : ?>
                        <tr class="tm-sb-award-row">
                            <td>
                                <input type="hidden" name="awards[<?php echo esc_attr($row_key); ?>][id]" value="<?php echo esc_attr($row['id']); ?>" />
                                <input type="text" class="widefat" name="awards[<?php echo esc_attr($row_key); ?>][award_id]" value="<?php echo esc_attr($row['award_id']); ?>" />
                            </td>
                            <td>
                                <select class="widefat js-show-ref" name="awards[<?php echo esc_attr($row_key); ?>][show_ref]" data-selected="<?php echo esc_attr($row['show_ref']); ?>"></select>
                            </td>
                            <td>
                                <select class="widefat" name="awards[<?php echo esc_attr($row_key); ?>][category]">
                                    <option value="Musical" <?php selected($row['category'], 'Musical'); ?>>Musical</option>
                                    <option value="Drama" <?php selected($row['category'], 'Drama'); ?>>Drama</option>
                                    <option value="Comedy" <?php selected($row['category'], 'Comedy'); ?>>Comedy</option>
                                </select>
                            </td>
                            <td><input type="text" class="widefat" name="awards[<?php echo esc_attr($row_key); ?>][award_name]" value="<?php echo esc_attr($row['award_name']); ?>" /></td>
                            <td><input type="text" class="widefat" name="awards[<?php echo esc_attr($row_key); ?>][recipient]" value="<?php echo esc_attr($row['recipient']); ?>" /></td>
                            <td>
                                <select class="widefat" name="awards[<?php echo esc_attr($row_key); ?>][status]">
                                    <option value="Nominated" <?php selected($row['status'], 'Nominated'); ?>>Nominated</option>
                                    <option value="THEA Winner" <?php selected($row['status'], 'THEA Winner'); ?>>THEA Winner</option>
                                </select>
                            </td>
                            <td><button type="button" class="button button-link-delete js-remove-award">Remove</button></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p><button type="button" class="button" id="tm-sb-add-award">Add Award Row</button></p>

            <p>
                <button type="submit" name="tm_season_builder_submit" value="1" class="button button-primary">Save Season Builder</button>
            </p>
        </form>
    </div>

    <script>
    (function($){
        var showCounter = 2;
        var castCounter = 2;
        var awardCounter = 2;

        function escHtml(value) {
            return String(value || '').replace(/[&<>'"]/g, function(ch) {
                return {'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[ch];
            });
        }

        function getShowOptions(selected) {
            var options = '';
            $('#tm-sb-shows-table tbody tr').each(function(){
                var rowKey = $(this).find('.js-show-row-key').val();
                var label = $(this).find('.js-show-name').val() || '(Untitled Show)';
                var isSelected = selected && String(selected) === String(rowKey);
                options += '<option value="' + escHtml(rowKey) + '"' + (isSelected ? ' selected' : '') + '>' + escHtml(label) + '</option>';
            });
            return options;
        }

        function refreshShowRefSelects() {
            $('.js-show-ref').each(function(){
                var selected = $(this).val() || $(this).attr('data-selected') || '';
                $(this).html(getShowOptions(selected));
                $(this).attr('data-selected', '');
            });
        }

        function addShowRow() {
            var rowKey = 'show_new_' + showCounter++;
            var html = '' +
                '<tr class="tm-sb-show-row">' +
                '  <td>' +
                '    <input type="hidden" class="js-show-row-key" value="' + escHtml(rowKey) + '" />' +
                '    <input type="hidden" name="shows[' + escHtml(rowKey) + '][id]" value="" />' +
                '    <input type="text" class="widefat js-show-name" name="shows[' + escHtml(rowKey) + '][name]" value="" />' +
                '  </td>' +
                '  <td><input type="text" class="widefat" name="shows[' + escHtml(rowKey) + '][author]" value="" /></td>' +
                '  <td>' +
                '    <select class="widefat" name="shows[' + escHtml(rowKey) + '][time_slot]">' +
                '      <option value=""></option>' +
                '      <option value="Fall">Fall</option>' +
                '      <option value="Winter">Winter</option>' +
                '      <option value="Spring">Spring</option>' +
                '    </select>' +
                '  </td>' +
                '  <td><button type="button" class="button button-link-delete js-remove-show">Remove</button></td>' +
                '</tr>';
            $('#tm-sb-shows-table tbody').append(html);
            refreshShowRefSelects();
        }

        function addCastRow() {
            var rowKey = 'cast_new_' + castCounter++;
            var html = '' +
                '<tr class="tm-sb-cast-row">' +
                '  <td>' +
                '    <input type="hidden" name="casts[' + escHtml(rowKey) + '][id]" value="" />' +
                '    <select class="widefat js-show-ref" name="casts[' + escHtml(rowKey) + '][show_ref]"></select>' +
                '  </td>' +
                '  <td><input type="text" class="widefat" name="casts[' + escHtml(rowKey) + '][character_name]" value="" /></td>' +
                '  <td><input type="text" class="widefat" name="casts[' + escHtml(rowKey) + '][actor_name]" value="" /></td>' +
                '  <td><button type="button" class="button button-link-delete js-remove-cast">Remove</button></td>' +
                '</tr>';
            $('#tm-sb-cast-table tbody').append(html);
            refreshShowRefSelects();
        }

        function addAwardRow() {
            var rowKey = 'award_new_' + awardCounter++;
            var html = '' +
                '<tr class="tm-sb-award-row">' +
                '  <td>' +
                '    <input type="hidden" name="awards[' + escHtml(rowKey) + '][id]" value="" />' +
                '    <input type="text" class="widefat" name="awards[' + escHtml(rowKey) + '][award_id]" value="" />' +
                '  </td>' +
                '  <td><select class="widefat js-show-ref" name="awards[' + escHtml(rowKey) + '][show_ref]"></select></td>' +
                '  <td>' +
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
            $('#tm-sb-awards-table tbody').append(html);
            refreshShowRefSelects();
        }

        $(document).on('input', '.js-show-name', refreshShowRefSelects);
        $(document).on('click', '#tm-sb-add-show', addShowRow);
        $(document).on('click', '#tm-sb-add-cast', addCastRow);
        $(document).on('click', '#tm-sb-add-award', addAwardRow);

        $(document).on('click', '.js-remove-show', function(){
            if ($('#tm-sb-shows-table tbody tr').length > 1) {
                $(this).closest('tr').remove();
                refreshShowRefSelects();
            }
        });

        $(document).on('click', '.js-remove-cast', function(){
            if ($('#tm-sb-cast-table tbody tr').length > 1) {
                $(this).closest('tr').remove();
            }
        });

        $(document).on('click', '.js-remove-award', function(){
            if ($('#tm-sb-awards-table tbody tr').length > 1) {
                $(this).closest('tr').remove();
            }
        });

        refreshShowRefSelects();
    })(jQuery);
    </script>
    <?php
}
