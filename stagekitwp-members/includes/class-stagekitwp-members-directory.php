<?php
/**
 * Member Directory (Phase C).
 *
 * Front-end shortcodes:
 *   [stagekitwp_members_directory]        Searchable/filterable grid of opted-in members.
 *   [stagekitwp_members_member_profile]   Read-only profile for a single member (?member=<id>).
 *
 * Access: members only (CAP_SEARCH_MEMBERS). Visibility is opt-in — members are
 * hidden by default and appear only after enabling "List me in the member
 * directory" on their profile (stagekitwp_members_directory_listed meta = '1').
 */

if (!defined('ABSPATH')) {
    exit;
}

class STAGEKITWP_MEMBERS_Directory {

    public static function init() {
        add_shortcode('stagekitwp_members_directory', [__CLASS__, 'render_directory']);
        add_shortcode('stagekitwp_members_member_profile', [__CLASS__, 'render_profile']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue']);
    }

    /**
     * Load directory CSS only on pages that use one of our shortcodes.
     */
    public static function enqueue() {
        if (!is_singular()) {
            return;
        }
        $post = get_post();
        if (!$post || (!has_shortcode($post->post_content, 'stagekitwp_members_directory')
            && !has_shortcode($post->post_content, 'stagekitwp_members_member_profile'))) {
            return;
        }
        wp_enqueue_style(
            'stagekitwp-ma-directory',
            STAGEKITWPMA_URL . 'assets/css/directory.css',
            [],
            STAGEKITWPMA_VERSION
        );
    }

    /**
     * Can the current viewer use the directory at all?
     */
    private static function viewer_allowed() {
        return is_user_logged_in() && current_user_can(STAGEKITWP_MEMBERS_Roles::CAP_SEARCH_MEMBERS);
    }

    /**
     * Is a given member visible in the directory to others?
     */
    public static function is_listed($user_id) {
        return get_user_meta($user_id, 'stagekitwp_members_directory_listed', true) === '1';
    }

    /**
     * Build the link to a member's read-only profile page.
     */
    public static function profile_url($user_id) {
        $base = get_permalink(self::directory_page_id());
        if (!$base) {
            return '';
        }
        return add_query_arg('member', (int) $user_id, $base);
    }

    /**
     * ID of the page that hosts [stagekitwp_members_directory]. Cached in an option and
     * re-resolved by slug if the stored page is missing.
     */
    public static function directory_page_id() {
        $id = (int) get_option('stagekitwp_members_directory_page_id');
        if ($id && get_post_status($id) === 'publish') {
            return $id;
        }
        $page = get_page_by_path('directory');
        if ($page) {
            update_option('stagekitwp_members_directory_page_id', $page->ID);
            return $page->ID;
        }
        return 0;
    }

    /**
     * [stagekitwp_members_directory] — search form + member grid.
     */
    public static function render_directory($atts = []) {

        if (!self::viewer_allowed()) {
            return '<p>Please log in as a member to view the directory.</p>';
        }

        // Single-profile view when ?member=<id> is present.
        if (!empty($_GET['member'])) {
            return self::render_profile(['id' => (int) $_GET['member']]);
        }

        $name     = isset($_GET['stagekitwp_dir_name']) ? sanitize_text_field(wp_unslash($_GET['stagekitwp_dir_name'])) : '';
        $interest = isset($_GET['stagekitwp_dir_interest']) ? (int) $_GET['stagekitwp_dir_interest'] : 0;

        $members = STAGEKITWP_MEMBERS_Search::query_members($name, $interest, 500, true);

        ob_start();
        ?>
        <div class="stagekitwp-dir">
            <form class="stagekitwp-dir-search" method="get">
                <input type="text" name="stagekitwp_dir_name" placeholder="Search by name"
                       value="<?php echo esc_attr($name); ?>">
                <select name="stagekitwp_dir_interest">
                    <option value="0">All interests</option>
                    <?php
                    $terms = get_terms(['taxonomy' => 'stagekitwp_interest', 'hide_empty' => false]);
                    if (!is_wp_error($terms)) {
                        foreach ($terms as $term) {
                            printf(
                                '<option value="%d" %s>%s</option>',
                                $term->term_id,
                                selected($interest, $term->term_id, false),
                                esc_html($term->name)
                            );
                        }
                    }
                    ?>
                </select>
                <button type="submit">Search</button>
                <?php if ($name || $interest): ?>
                    <a class="stagekitwp-dir-reset" href="<?php echo esc_url(get_permalink()); ?>">Reset</a>
                <?php endif; ?>
            </form>

            <p class="stagekitwp-dir-count"><?php echo count($members); ?> member<?php echo count($members) === 1 ? '' : 's'; ?> found</p>

            <?php if (empty($members)): ?>
                <p class="stagekitwp-dir-empty">No members match. Note that members only appear here after they opt in from their profile.</p>
            <?php else: ?>
                <div class="stagekitwp-dir-grid">
                    <?php foreach ($members as $m) { echo self::card($m); } ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * [stagekitwp_members_member_profile] — single read-only profile.
     */
    public static function render_profile($atts = []) {

        if (!self::viewer_allowed()) {
            return '<p>Please log in as a member to view profiles.</p>';
        }

        $atts = shortcode_atts(['id' => 0], $atts);
        $id   = (int) $atts['id'];
        if (!$id && !empty($_GET['member'])) {
            $id = (int) $_GET['member'];
        }

        $user = $id ? get_userdata($id) : null;

        // Members can always view their own profile even if unlisted; others
        // only see opted-in members.
        $is_self = $user && get_current_user_id() === $user->ID;
        if (!$user || (!self::is_listed($user->ID) && !$is_self)) {
            return '<p>This member profile is not available.</p>';
        }

        $bio   = get_user_meta($user->ID, 'stagekitwp_members_bio', true);
        $terms = wp_get_object_terms($user->ID, 'stagekitwp_interest', ['fields' => 'names']);
        $role  = self::role_label($user);
        $back  = get_permalink(self::directory_page_id());
        $msg   = self::message_url();

        ob_start();
        ?>
        <div class="stagekitwp-profile">
            <?php if ($back): ?>
                <a class="stagekitwp-profile-back" href="<?php echo esc_url($back); ?>">← Back to directory</a>
            <?php endif; ?>

            <div class="stagekitwp-profile-head">
                <?php echo self::avatar_html($user, 'stagekitwp-profile-avatar'); ?>
                <div>
                    <h2 class="stagekitwp-profile-name"><?php echo esc_html($user->display_name); ?></h2>
                    <?php if ($role): ?>
                        <p class="stagekitwp-profile-role"><?php echo esc_html($role); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!is_wp_error($terms) && $terms): ?>
                <div class="stagekitwp-profile-interests">
                    <?php foreach ($terms as $t): ?>
                        <span class="stagekitwp-profile-tag"><?php echo esc_html($t); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($bio): ?>
                <div class="stagekitwp-profile-bio"><?php echo wp_kses_post(wpautop($bio)); ?></div>
            <?php endif; ?>

            <?php if (!$is_self && $msg): ?>
                <a class="stagekitwp-profile-message" href="<?php echo esc_url($msg); ?>">Message this member</a>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * URL of the Messages page, if one exists.
     */
    private static function message_url() {
        $page = get_page_by_path('messages');
        return $page ? get_permalink($page->ID) : '';
    }

    /**
     * Render a member avatar with graceful fallbacks.
     *
     * Order: uploaded profile image (with an onerror fallback to the
     * initial placeholder if the file was deleted) → an initial-letter
     * placeholder when no image is set. This fixes broken-image icons
     * when a stored profile-image URL no longer resolves.
     *
     * @param WP_User $user
     * @param string  $class Base CSS class (e.g. stagekitwp-dir-avatar).
     */
    public static function avatar_html($user, $class) {
        $img     = get_user_meta($user->ID, 'stagekitwp_members_profile_image', true);
        $initial = esc_html(strtoupper(substr($user->display_name, 0, 1)));

        $placeholder = '<span class="' . esc_attr($class) . ' ' . esc_attr($class) . '--placeholder">'
            . $initial . '</span>';

        if (!$img) {
            return $placeholder;
        }

        // Render the image AND an always-present placeholder sibling. If the
        // image fails to load (deleted file), onerror hides the broken image
        // and reveals the placeholder — no broken-image icon, no escaped HTML.
        $onerror = "this.style.display='none';"
            . "var s=this.nextElementSibling; if(s){s.style.display='flex';}";

        return '<img class="' . esc_attr($class) . '" src="' . esc_url($img) . '" alt="" '
            . 'onerror="' . esc_attr($onerror) . '">'
            . '<span class="' . esc_attr($class) . ' ' . esc_attr($class) . '--placeholder" '
            . 'style="display:none;">' . $initial . '</span>';
    }

    /**
     * Render one member card for the grid.
     */
    private static function card($user) {
        $terms = wp_get_object_terms($user->ID, 'stagekitwp_interest', ['fields' => 'names']);
        $role  = self::role_label($user);
        $url   = self::profile_url($user->ID);

        ob_start();
        ?>
        <a class="stagekitwp-dir-card" href="<?php echo esc_url($url); ?>">
            <?php echo self::avatar_html($user, 'stagekitwp-dir-avatar'); ?>
            <span class="stagekitwp-dir-name"><?php echo esc_html($user->display_name); ?></span>
            <?php if ($role): ?>
                <span class="stagekitwp-dir-role"><?php echo esc_html($role); ?></span>
            <?php endif; ?>
            <?php if (!is_wp_error($terms) && $terms): ?>
                <span class="stagekitwp-dir-interests"><?php echo esc_html(implode(' · ', $terms)); ?></span>
            <?php endif; ?>
        </a>
        <?php
        return ob_get_clean();
    }

    /**
     * Human-friendly primary role label for a member.
     */
    public static function role_label($user) {
        // Accept a user ID or a WP_User object.
        if (is_numeric($user)) {
            $user = get_userdata((int) $user);
        }
        if (!$user) {
            return '';
        }
        $map = [
            'stagekitwp_executive' => 'Executive',
            'stagekitwp_producer'  => 'Producer',
            'stagekitwp_member'    => 'Member',
        ];
        foreach ($map as $slug => $label) {
            if (in_array($slug, (array) $user->roles, true)) {
                return $label;
            }
        }
        return '';
    }
}
