<?php
/**
 * Admin screen for managing member invitations: create, list, resend, revoke.
 * Gated on the CAP_MANAGE_MEMBERS capability (Executive + administrators).
 */

if (!defined('ABSPATH')) {
    exit;
}

class STAGEKITWP_MEMBERS_Invitations_Admin {

    const CAP = 'stagekitwp_members_manage_members';

    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_menu']);
        add_action('admin_init', [__CLASS__, 'handle_actions']);
    }

    public static function add_menu() {
        add_submenu_page(
            'stagekitwp',
            'Invitations',
            'Invitations',
            self::CAP,
            'stagekitwp-ma-invitations',
            [__CLASS__, 'render']
        );
    }

    private static function page_url() {
        return admin_url('admin.php?page=stagekitwp-ma-invitations');
    }

    /**
     * Process create / resend / revoke actions.
     */
    public static function handle_actions() {

        if (!isset($_GET['page']) || $_GET['page'] !== 'stagekitwp-ma-invitations') {
            return;
        }
        if (!current_user_can(self::CAP)) {
            return;
        }

        // Create.
        if (
            isset($_POST['stagekitwp_members_create_invite']) &&
            check_admin_referer('stagekitwp_members_create_invite')
        ) {
            $email  = isset($_POST['stagekitwp_members_invite_email']) ? sanitize_email(wp_unslash($_POST['stagekitwp_members_invite_email'])) : '';
            $result = STAGEKITWP_MEMBERS_Invitations::create_invite($email);
            $notice = is_wp_error($result) ? 'error:' . $result->get_error_message() : 'created';
            wp_safe_redirect(add_query_arg('stagekitwp_members_notice', rawurlencode($notice), self::page_url()));
            exit;
        }

        // Resend.
        if (isset($_GET['resend']) && check_admin_referer('stagekitwp_members_invite_row')) {
            $ok = STAGEKITWP_MEMBERS_Invitations::resend((int) $_GET['resend']);
            wp_safe_redirect(add_query_arg('stagekitwp_members_notice', $ok ? 'resent' : 'error:Could not resend.', self::page_url()));
            exit;
        }

        // Revoke.
        if (isset($_GET['revoke']) && check_admin_referer('stagekitwp_members_invite_row')) {
            STAGEKITWP_MEMBERS_Invitations::revoke((int) $_GET['revoke']);
            wp_safe_redirect(add_query_arg('stagekitwp_members_notice', 'revoked', self::page_url()));
            exit;
        }
    }

    public static function render() {

        if (!current_user_can(self::CAP)) {
            wp_die('Access denied');
        }

        self::render_notice();
        $invites = STAGEKITWP_MEMBERS_Invitations::all();
        ?>
        <div class="wrap">
            <h1>Member Invitations</h1>

            <h2>Send an Invitation</h2>
            <form method="post">
                <?php wp_nonce_field('stagekitwp_members_create_invite'); ?>
                <input type="email" name="stagekitwp_members_invite_email" placeholder="member@example.com" required style="min-width:280px;">
                <button class="button button-primary" name="stagekitwp_members_create_invite">Send Invitation</button>
            </form>

            <h2 style="margin-top:2em;">Invitations</h2>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Expires</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($invites)): ?>
                        <tr><td colspan="5">No invitations yet.</td></tr>
                    <?php else: foreach ($invites as $inv): ?>
                        <tr>
                            <td><?php echo esc_html($inv->email); ?></td>
                            <td><?php echo esc_html(ucfirst($inv->status)); ?></td>
                            <td><?php echo esc_html($inv->created_at); ?></td>
                            <td><?php echo esc_html($inv->expires); ?></td>
                            <td><?php self::row_actions($inv); ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    private static function row_actions($inv) {

        if ($inv->status !== STAGEKITWP_MEMBERS_Invitations::STATUS_PENDING) {
            echo '&mdash;';
            return;
        }

        $resend = wp_nonce_url(
            add_query_arg('resend', (int) $inv->id, self::page_url()),
            'stagekitwp_members_invite_row'
        );
        $revoke = wp_nonce_url(
            add_query_arg('revoke', (int) $inv->id, self::page_url()),
            'stagekitwp_members_invite_row'
        );

        echo '<a href="' . esc_url($resend) . '">Resend</a> | ';
        echo '<a href="' . esc_url($revoke) . '" style="color:#b32d2e;" onclick="return confirm(\'Revoke this invitation?\');">Revoke</a>';
    }

    private static function render_notice() {

        if (empty($_GET['stagekitwp_members_notice'])) {
            return;
        }

        $raw = sanitize_text_field(wp_unslash($_GET['stagekitwp_members_notice']));

        if (strpos($raw, 'error:') === 0) {
            $msg = substr($raw, 6);
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($msg) . '</p></div>';
            return;
        }

        $map = [
            'created' => 'Invitation sent.',
            'resent'  => 'Invitation resent.',
            'revoked' => 'Invitation revoked.',
        ];
        $msg = $map[$raw] ?? 'Done.';
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($msg) . '</p></div>';
    }
}
