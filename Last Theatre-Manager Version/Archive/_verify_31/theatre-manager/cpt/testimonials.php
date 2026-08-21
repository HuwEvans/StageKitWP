<?php
// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Register Testimonials Custom Post Type
 */
function tm_register_testimonial_cpt() {
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
        'all_items'          => 'All Testimonials',
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
        'show_in_menu'       => 'theatre-manager',
        'show_in_rest'       => false,
    );

    register_post_type('testimonial', $args);
}
add_action('init', 'tm_register_testimonial_cpt');

/**
 * Add Meta Box
 */
function tm_add_testimonial_meta_boxes() {
    add_meta_box(
        'tm_testimonial_details',
        'Testimonial Details',
        'tm_render_testimonial_meta_box',
        'testimonial',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'tm_add_testimonial_meta_boxes');

/**
 * Render Meta Box
 */
function tm_render_testimonial_meta_box($post) {
    wp_nonce_field('tm_save_testimonial_meta', 'tm_testimonial_nonce');

    $name = get_post_meta($post->ID, '_tm_name', true);
    $comment = get_post_meta($post->ID, '_tm_comment', true);
    $rating = get_post_meta($post->ID, '_tm_rating', true);
    ?>
    <p><label>Name:<br>
        <input type="text" name="tm_name" value="<?php echo esc_attr($name); ?>" style="width:100%;" />
    </label></p>
    <p><label>Comment:<br>
        <textarea name="tm_comment" rows="4" style="width:100%;"><?php echo esc_textarea($comment); ?></textarea>
    </label></p>
    <p><label>Rating:<br>
        <select name="tm_rating">
            <?php for ($i = 1; $i <= 5; $i++) {
                echo '<option value="' . $i . '"' . selected($rating, $i, false) . '>' . $i . ' Star' . ($i > 1 ? 's' : '') . '</option>';
            } ?>
        </select>
    </label></p>
    <?php
}

/**
 * Save Meta Box Data
 */
function tm_save_testimonial_meta($post_id) {
    if (!isset($_POST['tm_testimonial_nonce']) || !wp_verify_nonce($_POST['tm_testimonial_nonce'], 'tm_save_testimonial_meta')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!isset($_POST['post_type']) || 'testimonial' !== $_POST['post_type']) return;
    if (!current_user_can('edit_post', $post_id)) return;

    update_post_meta($post_id, '_tm_name', sanitize_text_field($_POST['tm_name']));
    update_post_meta($post_id, '_tm_comment', sanitize_textarea_field($_POST['tm_comment']));
    update_post_meta($post_id, '_tm_rating', intval($_POST['tm_rating']));

    // Prevent infinite loop
    remove_action('save_post', 'tm_save_testimonial_meta');
    wp_update_post(array(
        'ID' => $post_id,
        'post_title' => sanitize_text_field($_POST['tm_name'])
    ));
    add_action('save_post', 'tm_save_testimonial_meta');
}
add_action('save_post', 'tm_save_testimonial_meta');

/**
 * Customize Admin Columns
 */
function tm_testimonial_columns($columns) {
    return array(
        'cb'      => '<input type="checkbox" />',
        'title'   => 'Name',
        'comment' => 'Comment',
        'rating'  => 'Rating'
    );
}
add_filter('manage_testimonial_posts_columns', 'tm_testimonial_columns');

function tm_testimonial_custom_column($column, $post_id) {
    switch ($column) {
        case 'comment':
            echo esc_html(get_post_meta($post_id, '_tm_comment', true));
            break;
        case 'rating':
            echo esc_html(get_post_meta($post_id, '_tm_rating', true)) . ' Stars';
            break;
    }
}
add_action('manage_testimonial_posts_custom_column', 'tm_testimonial_custom_column', 10, 2);
?>
