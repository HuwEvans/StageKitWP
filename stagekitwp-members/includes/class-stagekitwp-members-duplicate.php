<?php
/**
 * "Duplicate" row action for Events, Announcements and Email Templates on the
 * native WordPress list screens. Trash / delete come from core.
 */

if (!defined('ABSPATH')) {
    exit;
}

class STAGEKITWP_MEMBERS_Duplicate {

    const POST_TYPES = ['stagekitwp_event', 'stagekitwp_ann', 'stagekitwp_email'];

    // Meta that must not be carried to the copy.
    const SKIP_META = ['_stagekitwp_members_delivered', '_edit_lock', '_edit_last'];

    public static function init() {
        add_filter('post_row_actions', [__CLASS__, 'row_actions'], 10, 2);
        add_action('admin_action_stagekitwp_members_duplicate', [__CLASS__, 'handle']);
    }

    public static function row_actions($actions, $post) {
        if (!in_array($post->post_type, self::POST_TYPES, true) || !current_user_can('edit_post', $post->ID)) {
            return $actions;
        }
        $url = wp_nonce_url(
            admin_url('admin.php?action=stagekitwp_members_duplicate&post=' . (int) $post->ID),
            'stagekitwp_members_duplicate_' . $post->ID
        );
        $actions['stagekitwp_duplicate'] = '<a href="' . esc_url($url) . '">Duplicate</a>';
        return $actions;
    }

    public static function handle() {
        $id = isset($_GET['post']) ? absint($_GET['post']) : 0;
        check_admin_referer('stagekitwp_members_duplicate_' . $id);

        $post = $id ? get_post($id) : null;
        if (!$post || !in_array($post->post_type, self::POST_TYPES, true) || !current_user_can('edit_post', $id)) {
            wp_die('You are not allowed to duplicate this item.');
        }

        $new_id = wp_insert_post([
            'post_type'    => $post->post_type,
            'post_status'  => 'draft',
            'post_title'   => $post->post_title . ' (Copy)',
            'post_content' => $post->post_content,
            'post_excerpt' => $post->post_excerpt,
            'post_author'  => get_current_user_id(),
        ], true);

        if (is_wp_error($new_id)) {
            wp_die(esc_html($new_id->get_error_message()));
        }

        foreach (get_post_meta($id) as $key => $values) {
            if (in_array($key, self::SKIP_META, true)) {
                continue;
            }
            foreach ($values as $value) {
                add_post_meta($new_id, $key, maybe_unserialize($value));
            }
        }

        foreach (get_object_taxonomies($post->post_type) as $taxonomy) {
            $terms = wp_get_object_terms($id, $taxonomy, ['fields' => 'ids']);
            if (!is_wp_error($terms) && $terms) {
                wp_set_object_terms($new_id, $terms, $taxonomy);
            }
        }

        wp_safe_redirect(admin_url('post.php?action=edit&post=' . $new_id));
        exit;
    }
}
