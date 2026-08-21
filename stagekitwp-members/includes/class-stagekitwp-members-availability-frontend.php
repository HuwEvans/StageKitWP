<?php
/**
 * Member-facing availability UI (Phase E2).
 *
 * Shortcode [stagekitwp_members_availability] lets a member add/remove date ranges with a
 * status (available/unavailable/tentative) and note. Writes go through
 * STAGEKITWP_MEMBERS_Availability. Requires login + CAP_RSVP_EVENTS. Nonce-protected,
 * PRG redirect to avoid resubmits.
 */

if (!defined('ABSPATH')) {
    exit;
}

class STAGEKITWP_MEMBERS_Availability_Frontend {

    public static function init() {
        add_shortcode('stagekitwp_members_availability', [__CLASS__, 'render']);
        add_action('init', [__CLASS__, 'handle_post']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue']);
    }

    public static function enqueue() {
        if (!is_singular()) {
            return;
        }
        $post = get_post();
        if ($post && has_shortcode($post->post_content, 'stagekitwp_members_availability')) {
            wp_enqueue_style('stagekitwp-ma-availability', STAGEKITWPMA_URL . 'assets/css/availability.css', [], STAGEKITWPMA_VERSION);
        }
    }

    private static function allowed() {
        return is_user_logged_in() && current_user_can(STAGEKITWP_MEMBERS_Roles::CAP_RSVP_EVENTS);
    }

    public static function handle_post() {

        if (!self::allowed()) {
            return;
        }
        $uid = get_current_user_id();

        // Add a range.
        if (isset($_POST['stagekitwp_members_avail_add'])) {
            if (!wp_verify_nonce($_POST['stagekitwp_members_avail_nonce'] ?? '', 'stagekitwp_members_avail_add')) {
                return;
            }
            $start  = sanitize_text_field($_POST['stagekitwp_members_avail_start'] ?? '');
            $end    = sanitize_text_field($_POST['stagekitwp_members_avail_end'] ?? '');
            $status = sanitize_key($_POST['stagekitwp_members_avail_status'] ?? 'unavailable');
            $note   = sanitize_text_field($_POST['stagekitwp_members_avail_note'] ?? '');
            if ($start && $end) {
                // If only one date given, treat as single day.
                STAGEKITWP_MEMBERS_Availability::add_range($uid, $start, $end ?: $start, $status, $note);
            }
            self::redirect_back();
        }

        // Delete a range.
        if (isset($_POST['stagekitwp_members_avail_delete'])) {
            $id = (int) $_POST['stagekitwp_members_avail_delete'];
            if (wp_verify_nonce($_POST['stagekitwp_members_avail_nonce'] ?? '', 'stagekitwp_members_avail_del_' . $id)) {
                STAGEKITWP_MEMBERS_Availability::delete_range($id, $uid);
            }
            self::redirect_back();
        }
    }

    private static function redirect_back() {
        $back = add_query_arg('stagekitwp_avail_done', 1, wp_get_referer() ?: home_url('/'));
        wp_safe_redirect($back);
        exit;
    }

    public static function render($atts = []) {

        if (!self::allowed()) {
            return '<p>Please log in as a member to manage your availability.</p>';
        }

        $uid    = get_current_user_id();
        $ranges = STAGEKITWP_MEMBERS_Availability::get_for_user($uid);
        $fmt    = get_option('date_format');

        ob_start();
        ?>
        <div class="stagekitwp-avail">

            <?php if (isset($_GET['stagekitwp_avail_done'])): ?>
                <p class="stagekitwp-avail-notice">✅ Your availability has been updated.</p>
            <?php endif; ?>

            <form class="stagekitwp-avail-form" method="post">
                <?php wp_nonce_field('stagekitwp_members_avail_add', 'stagekitwp_members_avail_nonce'); ?>
                <div class="stagekitwp-avail-row">
                    <label>From
                        <input type="date" name="stagekitwp_members_avail_start" required>
                    </label>
                    <label>To
                        <input type="date" name="stagekitwp_members_avail_end" required>
                    </label>
                    <label>Status
                        <select name="stagekitwp_members_avail_status">
                            <option value="unavailable">Unavailable</option>
                            <option value="tentative">Tentative</option>
                            <option value="available">Available</option>
                        </select>
                    </label>
                </div>
                <label class="stagekitwp-avail-note-label">Note (optional)
                    <input type="text" name="stagekitwp_members_avail_note" maxlength="255" placeholder="e.g. away on tour">
                </label>
                <button type="submit" name="stagekitwp_members_avail_add">Add</button>
            </form>

            <?php if (empty($ranges)): ?>
                <p class="stagekitwp-avail-empty">You haven&rsquo;t added any availability yet.</p>
            <?php else: ?>
                <ul class="stagekitwp-avail-list">
                    <?php foreach ($ranges as $r):
                        $s = strtotime($r->start_date);
                        $e = strtotime($r->end_date);
                        $range_label = ($r->start_date === $r->end_date)
                            ? date_i18n($fmt, $s)
                            : date_i18n($fmt, $s) . ' – ' . date_i18n($fmt, $e);
                    ?>
                        <li class="stagekitwp-avail-item">
                            <span class="stagekitwp-avail-badge stagekitwp-avail-badge--<?php echo esc_attr($r->status); ?>">
                                <?php echo esc_html(ucfirst($r->status)); ?>
                            </span>
                            <span class="stagekitwp-avail-dates"><?php echo esc_html($range_label); ?></span>
                            <?php if ($r->note): ?>
                                <span class="stagekitwp-avail-note"><?php echo esc_html($r->note); ?></span>
                            <?php endif; ?>
                            <form method="post" class="stagekitwp-avail-del">
                                <?php wp_nonce_field('stagekitwp_members_avail_del_' . $r->id, 'stagekitwp_members_avail_nonce'); ?>
                                <button type="submit" name="stagekitwp_members_avail_delete" value="<?php echo (int) $r->id; ?>" aria-label="Remove">×</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
