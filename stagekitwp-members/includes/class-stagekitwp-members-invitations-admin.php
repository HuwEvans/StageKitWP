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
        add_action('admin_post_stagekitwp_members_invite_create', [__CLASS__, 'handle_create']);
        add_action('admin_post_stagekitwp_members_invite_row', [__CLASS__, 'handle_row']);
    }

    private static function redirect($notice) {
        wp_safe_redirect(add_query_arg('stagekitwp_members_notice', rawurlencode($notice), self::page_url()));
        exit;
    }

    public static function handle_create() {
        if (!current_user_can(self::CAP)) {
            wp_die('Access denied');
        }
        check_admin_referer('stagekitwp_members_create_invite');
        $email  = isset($_POST['stagekitwp_members_invite_email']) ? sanitize_email(wp_unslash($_POST['stagekitwp_members_invite_email'])) : '';
        $result = STAGEKITWP_MEMBERS_Invitations::create_invite($email);
        self::redirect(is_wp_error($result)
            ? (($result->get_error_code() === 'invite_email_failed' ? 'warning:' : 'error:') . $result->get_error_message())
            : 'created');
    }

    public static function handle_row() {
        if (!current_user_can(self::CAP)) {
            wp_die('Access denied');
        }
        check_admin_referer('stagekitwp_members_invite_row');
        if (isset($_GET['resend'])) {
            $ok = STAGEKITWP_MEMBERS_Invitations::resend((int) $_GET['resend']);
            self::redirect($ok ? 'resent' : 'error:Could not resend.');
        }
        if (isset($_GET['revoke'])) {
            STAGEKITWP_MEMBERS_Invitations::revoke((int) $_GET['revoke']);
            self::redirect('revoked');
        }
        self::redirect('error:Unknown action.');
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
        return admin_url('admin.php?page=stagekitwp-ma-admin&stagekitwp_members_tab=invitations');
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
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="stagekitwp_members_invite_create">
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

        $base   = admin_url('admin-post.php?action=stagekitwp_members_invite_row');
        $resend = wp_nonce_url(
            add_query_arg('resend', (int) $inv->id, $base),
            'stagekitwp_members_invite_row'
        );
        $revoke = wp_nonce_url(
            add_query_arg('revoke', (int) $inv->id, $base),
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

        if (strpos($raw, 'error:') === 0 || strpos($raw, 'warning:') === 0) {
            $warning = strpos($raw, 'warning:') === 0;
            $prefix = $warning ? 'warning:' : 'error:';
            $class = $warning ? 'notice-warning' : 'notice-error';
            $msg = substr($raw, strlen($prefix));
            echo '<div class="notice ' . esc_attr($class) . ' is-dismissible"><p>' . esc_html($msg) . '</p></div>';
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
