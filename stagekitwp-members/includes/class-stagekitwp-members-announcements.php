<?php
/**
 * Announcements / notice board (Phase F).
 *
 * A `stagekitwp_ann` CPT that Producers post and members read. On publish,
 * announcements are delivered on-site (the feed) and, by default, by email
 * to members who have not opted out of announcements. Executives can flag an
 * announcement as "Important" to bypass the announcement opt-out (using the
 * Phase A notification rules).
 *
 * Shortcode: [stagekitwp_members_announcements]
 */

if (!defined('ABSPATH')) {
    exit;
}

class STAGEKITWP_MEMBERS_Announcements {

    const CPT = 'stagekitwp_ann';

    public static function init() {
        add_action('init', [__CLASS__, 'register_cpt']);
        add_action('add_meta_boxes', [__CLASS__, 'add_meta_box']);
        add_action('save_post_' . self::CPT, [__CLASS__, 'save_meta'], 10, 2);

        // Fire delivery when an announcement transitions to "publish".
        add_action('transition_post_status', [__CLASS__, 'on_publish'], 10, 3);

        add_shortcode('stagekitwp_members_announcements', [__CLASS__, 'render_feed']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue']);
    }

    public static function register_cpt() {

        register_post_type(self::CPT, [
            'labels' => [
                'name'          => 'Announcements',
                'singular_name' => 'Announcement',
                'add_new_item'  => 'Add Announcement',
                'edit_item'     => 'Edit Announcement',
                'menu_name'     => 'Announcements',
            ],
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => false,
            'menu_icon'           => 'dashicons-megaphone',
            'menu_position'       => 26,
            'exclude_from_search' => true,
            'publicly_queryable'  => false,
            'has_archive'         => false,
            'supports'            => ['title', 'editor', 'author'],
            'capability_type'     => 'post',
            'map_meta_cap'        => true,
        ]);
    }

    public static function enqueue() {
        if (!is_singular()) {
            return;
        }
        $post = get_post();
        if ($post && has_shortcode($post->post_content, 'stagekitwp_members_announcements')) {
            wp_enqueue_style('stagekitwp-ma-announcements', STAGEKITWPMA_URL . 'assets/css/announcements.css', [], STAGEKITWPMA_VERSION);
        }
    }

    public static function add_meta_box() {
        add_meta_box(
            'stagekitwp_members_announcement_options',
            'Delivery',
            [__CLASS__, 'render_meta_box'],
            self::CPT,
            'side',
            'high'
        );
    }

    public static function render_meta_box($post) {

        wp_nonce_field('stagekitwp_members_announcement_save', 'stagekitwp_members_announcement_nonce');

        $email     = get_post_meta($post->ID, '_stagekitwp_members_send_email', true);
        $important = get_post_meta($post->ID, '_stagekitwp_members_important', true);

        // Default "send email" to on for a brand-new (auto-draft) announcement.
        if ($post->post_status === 'auto-draft') {
            $email = '1';
        }

        // Only Executives/admins may flag Important (bypasses opt-out).
        $can_important = current_user_can(STAGEKITWP_MEMBERS_Roles::CAP_MANAGE_MEMBERS);
        ?>
        <p>
            <label>
                <input type="checkbox" name="stagekitwp_members_send_email" value="1" <?php checked($email, '1'); ?>>
                Also email members
            </label>
        </p>
        <p style="color:#666;font-size:12px;">
            Members who opted out of announcement emails won&rsquo;t receive it
            unless it is marked Important.
        </p>
        <?php if ($can_important): ?>
        <p>
            <label>
                <input type="checkbox" name="stagekitwp_members_important" value="1" <?php checked($important, '1'); ?>>
                <strong>Important</strong> (bypass opt-out)
            </label>
        </p>
        <?php elseif ($important): ?>
            <p><strong>Marked Important.</strong></p>
            <input type="hidden" name="stagekitwp_members_important" value="1">
        <?php endif; ?>
        <?php
    }

    public static function save_meta($post_id, $post) {

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!isset($_POST['stagekitwp_members_announcement_nonce'])
            || !wp_verify_nonce($_POST['stagekitwp_members_announcement_nonce'], 'stagekitwp_members_announcement_save')) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        update_post_meta($post_id, '_stagekitwp_members_send_email', !empty($_POST['stagekitwp_members_send_email']) ? '1' : '0');

        // Only allow setting Important if the user is permitted; otherwise keep
        // whatever value already exists (do not let a Producer clear it).
        if (current_user_can(STAGEKITWP_MEMBERS_Roles::CAP_MANAGE_MEMBERS)) {
            update_post_meta($post_id, '_stagekitwp_members_important', !empty($_POST['stagekitwp_members_important']) ? '1' : '0');
        }
    }

    public static function on_publish($new_status, $old_status, $post) {

        if ($post->post_type !== self::CPT) {
            return;
        }
        // Only when transitioning INTO publish (not on every save of a
        // published post).
        if ($new_status !== 'publish' || $old_status === 'publish') {
            return;
        }
        // Guard: never deliver twice for the same announcement.
        if (get_post_meta($post->ID, '_stagekitwp_members_delivered', true)) {
            return;
        }
        update_post_meta($post->ID, '_stagekitwp_members_delivered', current_time('mysql'));

        // On-site delivery is implicit: the feed shows all published
        // announcements. Only email needs an explicit opt.
        if (get_post_meta($post->ID, '_stagekitwp_members_send_email', true) !== '1') {
            return;
        }
        if (!class_exists('STAGEKITWP_MEMBERS_Health')) {
            return;
        }

        $important = get_post_meta($post->ID, '_stagekitwp_members_important', true) === '1';
        $subject   = ($important ? '[Important] ' : '') . $post->post_title;
        $body      = wpautop(wp_strip_all_tags($post->post_content, false));

        $recipients = get_users(['fields' => ['ID', 'user_email']]);

        foreach ($recipients as $u) {
            if (!$u->user_email) {
                continue;
            }
            $notify = class_exists('STAGEKITWP_MEMBERS_Notifications')
                ? STAGEKITWP_MEMBERS_Notifications::should_notify(
                    $u->ID,
                    STAGEKITWP_MEMBERS_Notifications::TYPE_ANNOUNCEMENT,
                    $important
                )
                : true;

            if ($notify) {
                STAGEKITWP_MEMBERS_Health::send_email($u->user_email, $subject, $body);
            }
        }
    }

    public static function render_feed($atts = []) {

        if (!is_user_logged_in()) {
            return '<p>Please log in to read announcements.</p>';
        }

        $atts = shortcode_atts(['limit' => 20], $atts);

        $posts = get_posts([
            'post_type'   => self::CPT,
            'post_status' => 'publish',
            'numberposts' => (int) $atts['limit'],
            'orderby'     => 'date',
            'order'       => 'DESC',
        ]);

        if (empty($posts)) {
            return '<div class="stagekitwp-ann"><p class="stagekitwp-ann-empty">No announcements yet.</p></div>';
        }

        $fmt = get_option('date_format');

        ob_start();
        echo '<div class="stagekitwp-ann">';
        foreach ($posts as $p) {
            $important = get_post_meta($p->ID, '_stagekitwp_members_important', true) === '1';
            $author    = get_the_author_meta('display_name', $p->post_author);
            ?>
            <article class="stagekitwp-ann-item <?php echo $important ? 'stagekitwp-ann-item--important' : ''; ?>">
                <header class="stagekitwp-ann-head">
                    <?php if ($important): ?>
                        <span class="stagekitwp-ann-flag">Important</span>
                    <?php endif; ?>
                    <h3 class="stagekitwp-ann-title"><?php echo esc_html($p->post_title); ?></h3>
                    <p class="stagekitwp-ann-meta">
                        <?php echo esc_html(date_i18n($fmt, strtotime($p->post_date))); ?>
                        <?php if ($author): ?> &middot; <?php echo esc_html($author); ?><?php endif; ?>
                    </p>
                </header>
                <div class="stagekitwp-ann-body">
                    <?php echo wp_kses_post(wpautop($p->post_content)); ?>
                </div>
            </article>
            <?php
        }
        echo '</div>';
        return ob_get_clean();
    }
}
