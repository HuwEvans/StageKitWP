<?php

if (!defined('ABSPATH')) {
    exit;
}

class STAGEKITWP_MEMBERS_Profile {

    public static function init() {
        add_action('show_user_profile', [__CLASS__, 'render_fields']);
        add_action('edit_user_profile', [__CLASS__, 'render_fields']);

        add_action('personal_options_update', [__CLASS__, 'save']);
        add_action('edit_user_profile_update', [__CLASS__, 'save']);

        // ✅ Frontend shortcode
        add_shortcode('stagekitwp_members_profile', [__CLASS__, 'frontend_form']);
    }

    /**
     * Admin Profile Fields
     */
    public static function render_fields($user) {

        wp_nonce_field('stagekitwp_members_profile_save', 'stagekitwp_members_profile_nonce');

        // ✅ Get user interests
        $user_terms = wp_get_object_terms($user->ID, 'stagekitwp_interest', ['fields' => 'ids']);

        ?>

        <h2>Member Profile</h2>

        <table class="form-table">

            <!-- BIO -->
            <tr>
                <th><label for="stagekitwp_members_bio">Bio</label></th>
                <td>
                    <?php
                    wp_editor(
                        get_user_meta($user->ID, 'stagekitwp_members_bio', true),
                        'stagekitwp_members_bio',
                        ['textarea_name' => 'stagekitwp_members_bio']
                    );
                    ?>
                </td>
            </tr>

            <!-- RESUME -->
            <tr>
                <th><label for="stagekitwp_members_resume">Resume</label></th>
                <td>
                    <?php
                    wp_editor(
                        get_user_meta($user->ID, 'stagekitwp_members_resume', true),
                        'stagekitwp_members_resume',
                        ['textarea_name' => 'stagekitwp_members_resume']
                    );
                    ?>
                </td>
            </tr>

            <!-- PROFILE IMAGE -->
            <tr>
                <th><label for="stagekitwp_members_profile_image">Profile Image</label></th>
                <td>
                    <input type="file" name="stagekitwp_members_profile_image" id="stagekitwp_members_profile_image">

                    <?php
                    $image = get_user_meta($user->ID, 'stagekitwp_members_profile_image', true);
                    if ($image) {
                        echo '<br><img src="' . esc_url($image) . '" style="max-width:100px; margin-top:10px;">';
                    }
                    ?>
                </td>
            </tr>

            <!-- INTERESTS -->
            <tr>
                <th><label>Interests</label></th>
                <td>

                    <?php
                    $terms = get_terms([
                        'taxonomy' => 'stagekitwp_interest',
                        'hide_empty' => false,
                    ]);

                    if (!empty($terms) && !is_wp_error($terms)) {

                        foreach ($terms as $term) {

                            $checked = in_array($term->term_id, $user_terms) ? 'checked' : '';

                            echo '<label style="display:block; margin-bottom:4px;">';
                            echo '<input type="checkbox" name="stagekitwp_members_interests[]" value="' . esc_attr($term->term_id) . '" ' . $checked . '>';
                            echo ' ' . esc_html($term->name);
                            echo '</label>';
                        }
                    } else {
                        echo '<p>No interests found.</p>';
                    }
                    ?>

                </td>
            </tr>
			<tr>
		    <th><label>Selected Interests</label></th>
		    <td>
		        <?php
		        $user_terms = wp_get_object_terms($user->ID, 'stagekitwp_interest');
		
		        if (!empty($user_terms) && !is_wp_error($user_terms)) {
		            $names = wp_list_pluck($user_terms, 'name');
		            echo '<p>' . esc_html(implode(', ', $names)) . '</p>';
		        } else {
		            echo '<p>No interests selected.</p>';
		        }
		        ?>
		    </td>
		</tr>

        </table>

        <?php
    }

    /**
     * Save Profile Data
     */
    public static function save($user_id) {

        // ✅ Nonce check
        if (
            !isset($_POST['stagekitwp_members_profile_nonce']) ||
            !wp_verify_nonce($_POST['stagekitwp_members_profile_nonce'], 'stagekitwp_members_profile_save')
        ) {
            return;
        }

        // ✅ Capability check
        if (!current_user_can('edit_user', $user_id)) {
            return;
        }

        // ✅ Save Bio
        if (isset($_POST['stagekitwp_members_bio'])) {
            update_user_meta($user_id, 'stagekitwp_members_bio', wp_kses_post($_POST['stagekitwp_members_bio']));
        }

        // ✅ Save Resume
        if (isset($_POST['stagekitwp_members_resume'])) {
            update_user_meta($user_id, 'stagekitwp_members_resume', wp_kses_post($_POST['stagekitwp_members_resume']));
        }

        // ✅ Save Interests (taxonomy)
        if (isset($_POST['stagekitwp_members_interests']) && is_array($_POST['stagekitwp_members_interests'])) {

            $terms = array_map('intval', $_POST['stagekitwp_members_interests']);

            wp_set_object_terms($user_id, $terms, 'stagekitwp_interest', false);

        } else {
            // Clear interests if none selected
            wp_set_object_terms($user_id, [], 'stagekitwp_interest', false);
        }

        // ✅ Notification preferences. Only touch these when the front-end
        // profile form (which renders the checkboxes) was submitted, so the
        // admin profile screen never clears a member's prefs. Unchecked boxes
        // are simply absent from POST.
        if (class_exists('STAGEKITWP_MEMBERS_Notifications') && isset($_POST['stagekitwp_members_save_profile'])) {
            $submitted = (isset($_POST['stagekitwp_members_notify']) && is_array($_POST['stagekitwp_members_notify']))
                ? $_POST['stagekitwp_members_notify']
                : [];
            $prefs = [];
            foreach (array_keys(STAGEKITWP_MEMBERS_Notifications::types()) as $type) {
                $prefs[$type] = !empty($submitted[$type]) ? 1 : 0;
            }
            STAGEKITWP_MEMBERS_Notifications::save_prefs($user_id, $prefs);
        }

        // ✅ Directory visibility (opt-in; hidden by default). Only touch this
        // when the front-end profile form was submitted so the admin profile
        // screen never flips a member's choice. Unchecked = absent from POST.
        if (isset($_POST['stagekitwp_members_save_profile'])) {
            $listed = !empty($_POST['stagekitwp_members_directory_listed']) ? '1' : '0';
            update_user_meta($user_id, 'stagekitwp_members_directory_listed', $listed);
        }

        // File upload (validated: images only, size-capped).
        if (!empty($_FILES['stagekitwp_members_profile_image']['name'])) {
            self::handle_avatar_upload($user_id, $_FILES['stagekitwp_members_profile_image']);
        }
    }

    /**
     * Allowed avatar image types and the maximum upload size.
     */
    const AVATAR_MAX_BYTES = 2097152; // 2 MB

    public static function avatar_mimes() {
        return [
            'jpg|jpeg|jpe' => 'image/jpeg',
            'png'          => 'image/png',
            'gif'          => 'image/gif',
            'webp'         => 'image/webp',
        ];
    }

    /**
     * Validate and store a profile image upload. Rejects non-images,
     * oversized files, and anything that is not a real image.
     *
     * @return string|WP_Error The stored URL on success.
     */
    public static function handle_avatar_upload($user_id, $file) {

        // Bubble up PHP upload errors.
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            return new WP_Error('stagekitwp_members_upload', 'The file could not be uploaded.');
        }

        // Size cap.
        if (!empty($file['size']) && $file['size'] > self::AVATAR_MAX_BYTES) {
            return new WP_Error('stagekitwp_members_upload', 'Image must be 2 MB or smaller.');
        }

        // Confirm it is a real image (defends against a renamed .php etc.).
        $check = @getimagesize($file['tmp_name']);
        if ($check === false) {
            return new WP_Error('stagekitwp_members_upload', 'That file is not a valid image.');
        }

        // Restrict extension/MIME to our image whitelist.
        $ext = wp_check_filetype($file['name'], self::avatar_mimes());
        if (empty($ext['type'])) {
            return new WP_Error('stagekitwp_members_upload', 'Allowed image types: JPG, PNG, GIF, WEBP.');
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';

        $uploaded = wp_handle_upload($file, [
            'test_form' => false,
            'mimes'     => self::avatar_mimes(),
        ]);

        if (isset($uploaded['url']) && empty($uploaded['error'])) {
            update_user_meta($user_id, 'stagekitwp_members_profile_image', esc_url_raw($uploaded['url']));
            return $uploaded['url'];
        }
        return new WP_Error('stagekitwp_members_upload', !empty($uploaded['error']) ? $uploaded['error'] : 'Upload failed.');
    }

    /**
     * Frontend Profile Form
     */
    public static function frontend_form() {

        if (!is_user_logged_in()) {
            return '<p>Please log in to edit your profile.</p>';
        }

        $user = wp_get_current_user();

        ob_start();

        ?>

        <form method="post" enctype="multipart/form-data">

            <?php wp_nonce_field('stagekitwp_members_profile_save', 'stagekitwp_members_profile_nonce'); ?>

            <h3>Bio</h3>
            <?php
            wp_editor(
                get_user_meta($user->ID, 'stagekitwp_members_bio', true),
                'stagekitwp_members_bio_frontend',
                ['textarea_name' => 'stagekitwp_members_bio']
            );
            ?>

            <h3>Resume</h3>
            <?php
            wp_editor(
                get_user_meta($user->ID, 'stagekitwp_members_resume', true),
                'stagekitwp_members_resume_frontend',
                ['textarea_name' => 'stagekitwp_members_resume']
            );
            ?>

            <h3>Profile Image</h3>
            <input type="file" name="stagekitwp_members_profile_image">

			<h3>Your Interests</h3>
			
			<?php
			$user_terms = wp_get_object_terms($user->ID, 'stagekitwp_interest');
			
			if (!empty($user_terms) && !is_wp_error($user_terms)) {
			    $names = wp_list_pluck($user_terms, 'name');
			    echo '<p><strong>' . esc_html(implode(', ', $names)) . '</strong></p>';
			} else {
			    echo '<p>No interests selected.</p>';
			}
			?>
            <?php
            $terms = get_terms([
                'taxonomy' => 'stagekitwp_interest',
                'hide_empty' => false,
            ]);

            $user_terms = wp_get_object_terms($user->ID, 'stagekitwp_interest', ['fields' => 'ids']);

            foreach ($terms as $term) {

                $checked = in_array($term->term_id, $user_terms) ? 'checked' : '';

                echo '<label style="display:block;">';
                echo '<input type="checkbox" name="stagekitwp_members_interests[]" value="' . esc_attr($term->term_id) . '" ' . $checked . '>';
                echo ' ' . esc_html($term->name);
                echo '</label>';
            }
            ?>

            <h3>Email Notifications</h3>
            <?php
            $prefs = STAGEKITWP_MEMBERS_Notifications::get_prefs($user->ID);
            foreach (STAGEKITWP_MEMBERS_Notifications::types() as $type => $label) {
                $checked = !empty($prefs[$type]) ? 'checked' : '';
                echo '<label style="display:block;">';
                echo '<input type="checkbox" name="stagekitwp_members_notify[' . esc_attr($type) . ']" value="1" ' . $checked . '>';
                echo ' ' . esc_html($label);
                echo '</label>';
            }
            ?>

            <h3>Member Directory</h3>
            <?php
            $listed = get_user_meta($user->ID, 'stagekitwp_members_directory_listed', true);
            ?>
            <label style="display:block;">
                <input type="checkbox" name="stagekitwp_members_directory_listed" value="1" <?php checked($listed, '1'); ?>>
                List me in the member directory
            </label>
            <p style="color:#666;font-size:.9em;margin-top:4px;">
                When enabled, other members can find you in the directory and see your
                name, role, interests, and bio. You are hidden by default.
            </p>

            <br>

            <button type="submit" name="stagekitwp_members_save_profile">Save Profile</button>

        </form>

        <?php

        if (isset($_POST['stagekitwp_members_save_profile'])) {
            self::save($user->ID);
            echo '<p>✅ Profile updated successfully.</p>';
        }

        return ob_get_clean();
    }
}