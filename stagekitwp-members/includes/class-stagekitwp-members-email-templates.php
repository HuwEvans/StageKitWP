<?php

if (!defined('ABSPATH')) exit;

class STAGEKITWP_MEMBERS_Email_Templates {

    public static function init() {
        add_action('init', [__CLASS__, 'register_cpt']);
        add_action('add_meta_boxes', [__CLASS__, 'add_meta']);
        add_action('save_post', [__CLASS__, 'save_meta']);
    }

    public static function register_cpt() {

        register_post_type('stagekitwp_email', [
            'label' => 'Email Templates',
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => ['title', 'editor'],
        ]);
    }

    public static function add_meta() {

        add_meta_box(
            'stagekitwp_members_template_subject',
            'Email Subject',
            [__CLASS__, 'render_meta'],
            'stagekitwp_email'
        );
    }

    public static function render_meta($post) {

        $subject = get_post_meta($post->ID, '_stagekitwp_members_subject', true);

        echo '<input type="text" name="stagekitwp_members_subject" value="' . esc_attr($subject) . '" style="width:100%;">';
    }

    public static function save_meta($post_id) {

        if (isset($_POST['stagekitwp_members_subject'])) {
            update_post_meta($post_id, '_stagekitwp_members_subject', sanitize_text_field($_POST['stagekitwp_members_subject']));
        }
    }

    public static function get_templates() {

        return get_posts([
            'post_type' => 'stagekitwp_email',
            'numberposts' => -1
        ]);
    }
}