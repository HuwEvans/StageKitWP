<?php
// Register Board Members CPT
function stagekitwp_register_board_members_cpt() {
    $labels = array(
        'name' => 'Board Members',
        'singular_name' => 'Board Member',
        'menu_name' => 'Board Members',
        'name_admin_bar' => 'Board Member',
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => false,
        'menu_position' => 5,
        'menu_icon' => 'dashicons-groups',
        'supports' => array(''), // No editor or custom fields
        'show_in_rest' => true,  // Required for Gutenberg block dropdowns
        'show_in_menu' => false,
        'capability_type' => 'post',
        'map_meta_cap' => true,
    );

    register_post_type('board_member', $args);
}
add_action('init', 'stagekitwp_register_board_members_cpt');

// Add Meta Boxes
function stagekitwp_add_board_member_meta_boxes() {
    add_meta_box('stagekitwp_board_member_details', 'Board Member Details', 'stagekitwp_board_member_meta_box_callback', 'board_member', 'normal', 'default');
}
add_action('add_meta_boxes', 'stagekitwp_add_board_member_meta_boxes');

// Enqueue media uploader JS on board_member edit screens, and on any
// StageKitWP hub page (e.g. People, Places & Partners renders the Board
// Member editor directly in-page, outside the normal post.php screen).
function stagekitwp_board_member_enqueue_media( $hook ) {
    $is_post_editor = in_array( $hook, array( 'post.php', 'post-new.php' ), true );
    $is_stagekitwp_hub = strpos( (string) $hook, 'stagekitwp' ) !== false;

    if ( $is_post_editor ) {
        $screen = get_current_screen();
        if ( ! $screen || 'board_member' !== $screen->post_type ) {
            return;
        }
    } elseif ( ! $is_stagekitwp_hub ) {
        return;
    }

    wp_enqueue_media();
    wp_enqueue_script(
        'stagekitwp-board-member-media',
        STAGEKITWP_CORE_URL . 'assets/js/board-member-media.js',
        array( 'jquery' ),
        STAGEKITWP_CORE_VERSION,
        true
    );
}
add_action( 'admin_enqueue_scripts', 'stagekitwp_board_member_enqueue_media' );

// Meta Box Callback
function stagekitwp_board_member_meta_box_callback($post) {
    wp_nonce_field('stagekitwp_save_board_member_meta', 'stagekitwp_board_member_nonce');

    $position = get_post_meta($post->ID, '_stagekitwp_position', true);
    $name     = get_post_meta($post->ID, '_stagekitwp_name', true);
    $photo    = get_post_meta($post->ID, '_stagekitwp_photo', true);

    // Resolve preview URL
    $photo_url = '';
    if ( ! empty( $photo ) ) {
        if ( is_numeric( $photo ) ) {
            $photo_url = wp_get_attachment_url( intval( $photo ) );
        } else {
            $photo_url = $photo;
        }
    }

    echo '<div class="stagekitwp-board-member">';
    echo '<table class="form-table" style="max-width:600px"><tbody>';

    echo '<tr><th scope="row"><label for="stagekitwp_position">Position</label></th>';
    echo '<td><input type="text" id="stagekitwp_position" name="stagekitwp_position" value="' . esc_attr( $position ) . '" class="regular-text" /></td></tr>';

    echo '<tr><th scope="row"><label for="stagekitwp_name">Name</label></th>';
    echo '<td><input type="text" id="stagekitwp_name" name="stagekitwp_name" value="' . esc_attr( $name ) . '" class="regular-text" /></td></tr>';

    $bio = get_post_meta( $post->ID, '_stagekitwp_bio', true );

    echo '<tr><th scope="row"><label for="stagekitwp_bio">Bio</label></th>';
    echo '<td><textarea id="stagekitwp_bio" name="stagekitwp_bio" rows="4" class="large-text">' . esc_textarea( $bio ) . '</textarea>';
    echo '<p class="description">Optional short biography. Shown when <code>show_bio="true"</code> is set on the shortcode.</p></td></tr>';

    echo '<tr><th scope="row">Photo</th><td>';
    echo '<div id="stagekitwp-bm-photo-preview" style="margin-bottom:10px;">';
    if ( $photo_url ) {
        echo '<img src="' . esc_url( $photo_url ) . '" style="max-width:200px;height:auto;display:block;margin-bottom:8px;" />';
    }
    echo '</div>';
    // Store the attachment ID (preferred) or URL
    echo '<input type="hidden" name="stagekitwp_photo" id="stagekitwp_photo" value="' . esc_attr( $photo ) . '" />';
    echo '<button type="button" class="button" id="stagekitwp-bm-photo-select">Select / Upload Photo</button>';
    echo ' <button type="button" class="button" id="stagekitwp-bm-photo-remove"' . ( $photo_url ? '' : ' style="display:none"' ) . '>Remove Photo</button>';
    echo '</td></tr>';

    echo '</tbody></table>';
    echo '</div>';
}


// Save Meta Data
function stagekitwp_save_board_member_meta($post_id) {
    $nonce = isset($_POST['stagekitwp_board_member_nonce']) ? sanitize_text_field(wp_unslash($_POST['stagekitwp_board_member_nonce'])) : '';
    if (!$nonce || !wp_verify_nonce($nonce, 'stagekitwp_save_board_member_meta')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    $post_type = isset($_POST['post_type']) ? sanitize_key(wp_unslash($_POST['post_type'])) : '';
    if ('board_member' !== $post_type) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $position = isset($_POST['stagekitwp_position']) ? sanitize_text_field(wp_unslash($_POST['stagekitwp_position'])) : '';
    $name = isset($_POST['stagekitwp_name']) ? sanitize_text_field(wp_unslash($_POST['stagekitwp_name'])) : '';
    // Accept attachment ID (integer) or URL fallback
    $bio      = isset($_POST['stagekitwp_bio']) ? sanitize_textarea_field( wp_unslash($_POST['stagekitwp_bio']) ) : '';
    update_post_meta( $post_id, '_stagekitwp_bio', $bio );

    $photo_raw = isset($_POST['stagekitwp_photo']) ? wp_unslash($_POST['stagekitwp_photo']) : '';
    $photo     = is_numeric( $photo_raw ) ? absint( $photo_raw ) : esc_url_raw( $photo_raw );

    update_post_meta($post_id, '_stagekitwp_position', $position);
    update_post_meta($post_id, '_stagekitwp_name', $name);
    update_post_meta($post_id, '_stagekitwp_photo', $photo);

    // Auto-set post title from Name
    remove_action('save_post', 'stagekitwp_save_board_member_meta');
    wp_update_post(array('ID' => $post_id, 'post_title' => $name));
    add_action('save_post', 'stagekitwp_save_board_member_meta');
}
add_action('save_post', 'stagekitwp_save_board_member_meta');

// Customize admin columns for Board Members
function stagekitwp_board_member_columns($columns) {
    $columns = array(
        'cb' => '<input type="checkbox" />',
        'picture' => 'Media',
        'name' => 'Name',
        'position' => 'Position',
        'date' => 'Date'
    );
    return $columns;
}
add_filter('manage_board_member_posts_columns', 'stagekitwp_board_member_columns');

// Populate custom columns
function stagekitwp_board_member_custom_column($column, $post_id) {
    if ($column === 'position') {
        echo esc_html(get_post_meta($post_id, '_stagekitwp_position', true));
    } elseif ($column === 'name') {
        echo esc_html(get_post_meta($post_id, '_stagekitwp_name', true));
    } elseif ($column === 'picture') {
        $photo = get_post_meta($post_id, '_stagekitwp_photo', true);
        if (!empty($photo)) {
            if (is_numeric($photo)) {
                // It's an attachment ID
                $photo_url = wp_get_attachment_url($photo);
                if ($photo_url) {
                    echo '<img src="' . esc_url($photo_url) . '" style="max-width:50px; height:auto; margin-right:5px;" />';
                }
            } else {
                // It's a direct URL
                echo '<img src="' . esc_url($photo) . '" style="max-width:50px; height:auto; margin-right:5px;" />';
            }
        }
    }
}
add_action('manage_board_member_posts_custom_column', 'stagekitwp_board_member_custom_column', 10, 2);

// Make columns sortable if needed
function stagekitwp_board_member_sortable_columns($columns) {
    $columns['name'] = 'title';
    return $columns;
}
add_filter('manage_edit-board_member_sortable_columns', 'stagekitwp_board_member_sortable_columns');
