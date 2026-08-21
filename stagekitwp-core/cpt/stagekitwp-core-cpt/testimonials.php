<?php
// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Register Testimonials Custom Post Type
 */
function stagekitwp_register_testimonial_cpt() {
    $labels = array(
        'name'               => 'Testimonials',
        'singular_name'      => 'Testimonial',
        'menu_name'          => 'Testimonials',
        'name_admin_bar'     => 'Testimonial',
        'add_new'            => 'Add New',
        'add_new_item'       => 'Add New Testimonial',
        'new_item'           => 'New Testimonial',
        'edit_item'          => 'Edit Testimonial',
        'view_item'          => 'View Testimonial',
        'all_items'          => 'Testimonials',
        'search_items'       => 'Search Testimonials',
        'not_found'          => 'No testimonials found.',
        'not_found_in_trash' => 'No testimonials found in Trash.'
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'has_archive'        => false,
        'menu_position'      => 5,
        'menu_icon'          => 'dashicons-format-quote',
        'supports'           => array(''),
        'show_in_menu'       => false,
        'show_in_rest'       => true,  // Required for Gutenberg block dropdowns
        'capability_type'    => 'post',
        'map_meta_cap'       => true,
    );

    register_post_type('testimonial', $args);
}
add_action('init', 'stagekitwp_register_testimonial_cpt');

/**
 * Add Meta Box
 */
function stagekitwp_add_testimonial_meta_boxes() {
    add_meta_box(
        'stagekitwp_testimonial_details',
        'Testimonial Details',
        'stagekitwp_render_testimonial_meta_box',
        'testimonial',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'stagekitwp_add_testimonial_meta_boxes');

/**
 * Render Meta Box
 */
function stagekitwp_render_testimonial_meta_box($post) {
    wp_nonce_field('stagekitwp_save_testimonial_meta', 'stagekitwp_testimonial_nonce');

    $name = get_post_meta($post->ID, '_stagekitwp_name', true);
    $comment = get_post_meta($post->ID, '_stagekitwp_comment', true);
    $rating = get_post_meta($post->ID, '_stagekitwp_rating', true);
    $show_id = absint(get_post_meta($post->ID, '_stagekitwp_testimonial_show_id', true));
    $testimonial_date = get_post_meta($post->ID, '_stagekitwp_testimonial_date', true);
    $shows = get_posts(array(
        'post_type' => 'show',
        'post_status' => array('publish', 'draft', 'future', 'private'),
        'numberposts' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
    ));
    ?>
    <p><label>Name:<br>
        <input type="text" name="stagekitwp_name" value="<?php echo esc_attr($name); ?>" style="width:100%;" />
    </label></p>
    <p><label>Comment:<br>
        <textarea name="stagekitwp_comment" rows="4" style="width:100%;"><?php echo esc_textarea($comment); ?></textarea>
    </label></p>
    <p><label>Rating:<br>
        <select name="stagekitwp_rating">
            <?php for ($i = 1; $i <= 5; $i++) {
                echo '<option value="' . $i . '"' . selected($rating, $i, false) . '>' . $i . ' Star' . ($i > 1 ? 's' : '') . '</option>';
            } ?>
        </select>
    </label></p>
    <p><label>Show (optional):<br>
        <select name="stagekitwp_testimonial_show_id" style="width:100%;">
            <option value="">-- No associated show --</option>
            <?php foreach ($shows as $show) {
                echo '<option value="' . esc_attr($show->ID) . '"' . selected($show_id, $show->ID, false) . '>' . esc_html($show->post_title) . '</option>';
            } ?>
        </select>
    </label></p>
    <p><label>Testimonial Date (optional):<br>
        <input type="date" name="stagekitwp_testimonial_date" value="<?php echo esc_attr($testimonial_date); ?>" style="width:100%;" />
    </label></p>
    <?php
}

/**
 * Save Meta Box Data
 */
function stagekitwp_save_testimonial_meta($post_id) {
    $nonce = isset($_POST['stagekitwp_testimonial_nonce']) ? sanitize_text_field(wp_unslash($_POST['stagekitwp_testimonial_nonce'])) : '';
    if (!$nonce || !wp_verify_nonce($nonce, 'stagekitwp_save_testimonial_meta')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    $post_type = isset($_POST['post_type']) ? sanitize_key(wp_unslash($_POST['post_type'])) : '';
    if ('testimonial' !== $post_type) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $name = isset($_POST['stagekitwp_name']) ? sanitize_text_field(wp_unslash($_POST['stagekitwp_name'])) : '';
    $comment = isset($_POST['stagekitwp_comment']) ? sanitize_textarea_field(wp_unslash($_POST['stagekitwp_comment'])) : '';
    $rating = isset($_POST['stagekitwp_rating']) ? absint($_POST['stagekitwp_rating']) : 0;
    $show_id = isset($_POST['stagekitwp_testimonial_show_id']) ? absint($_POST['stagekitwp_testimonial_show_id']) : 0;
    $testimonial_date = isset($_POST['stagekitwp_testimonial_date']) ? sanitize_text_field(wp_unslash($_POST['stagekitwp_testimonial_date'])) : '';

    update_post_meta($post_id, '_stagekitwp_name', $name);
    update_post_meta($post_id, '_stagekitwp_comment', $comment);
    update_post_meta($post_id, '_stagekitwp_rating', $rating);
    update_post_meta($post_id, '_stagekitwp_testimonial_show_id', $show_id);
    update_post_meta($post_id, '_stagekitwp_testimonial_date', $testimonial_date);

    // Prevent infinite loop
    remove_action('save_post', 'stagekitwp_save_testimonial_meta');
    wp_update_post(array(
        'ID' => $post_id,
        'post_title' => $name
    ));
    add_action('save_post', 'stagekitwp_save_testimonial_meta');
}
add_action('save_post', 'stagekitwp_save_testimonial_meta');

/**
 * Customize Admin Columns
 */
function stagekitwp_testimonial_columns($columns) {
    return array(
        'cb'      => '<input type="checkbox" />',
        'title'   => 'Name',
        'comment' => 'Comment',
        'rating'  => 'Rating',
        'show'    => 'Show',
        'date'    => 'Date'
    );
}
add_filter('manage_testimonial_posts_columns', 'stagekitwp_testimonial_columns');

function stagekitwp_testimonial_custom_column($column, $post_id) {
    switch ($column) {
        case 'comment':
            echo esc_html(get_post_meta($post_id, '_stagekitwp_comment', true));
            break;
        case 'rating':
            echo esc_html(get_post_meta($post_id, '_stagekitwp_rating', true)) . ' Stars';
            break;
        case 'show':
            $show_id = absint(get_post_meta($post_id, '_stagekitwp_testimonial_show_id', true));
            echo $show_id ? esc_html(get_the_title($show_id)) : '&mdash;';
            break;
        case 'date':
            $date = get_post_meta($post_id, '_stagekitwp_testimonial_date', true);
            echo $date ? esc_html($date) : '&mdash;';
            break;
    }
}
add_action('manage_testimonial_posts_custom_column', 'stagekitwp_testimonial_custom_column', 10, 2);
?>
