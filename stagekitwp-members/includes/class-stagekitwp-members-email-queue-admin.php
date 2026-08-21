<?php
/**
 * Admin viewer for the scheduled email queue: shows counts and jobs, and
 * lets an administrator run the queue now, retry failures, delete jobs, or
 * clear everything. Gated on manage_options.
 */

if (!defined('ABSPATH')) {
    exit;
}

class STAGEKITWP_MEMBERS_Email_Queue_Admin {

    const CAP = 'manage_options';

    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_menu']);
        add_action('admin_init', [__CLASS__, 'handle_actions']);
    }

    public static function add_menu() {
        add_submenu_page(
            'stagekitwp',
            'Email Queue',
            'Email Queue',
            self::CAP,
            'stagekitwp-ma-email-queue',
            [__CLASS__, 'render']
        );
    }

    private static function page_url() {
        return admin_url('admin.php?page=stagekitwp-ma-admin&stagekitwp_members_tab=email_queue');
    }

    public static function handle_actions() {

        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
        $tab  = isset($_GET['stagekitwp_members_tab']) ? sanitize_key(wp_unslash($_GET['stagekitwp_members_tab'])) : '';

        if ($page !== 'stagekitwp-ma-email-queue' && !($page === 'stagekitwp-ma-admin' && $tab === 'email_queue')) {
            return;
        }
        if (!current_user_can(self::CAP)) {
            return;
        }

        // Run the queue now.
        if (isset($_GET['run']) && check_admin_referer('stagekitwp_members_queue_action')) {
            STAGEKITWP_MEMBERS_Email_Queue::process_queue();
            self::redirect('ran');
        }

        // Retry a failed job.
        if (isset($_GET['retry']) && check_admin_referer('stagekitwp_members_queue_action')) {
            STAGEKITWP_MEMBERS_Email_Queue::retry_job((int) $_GET['retry']);
            STAGEKITWP_MEMBERS_Email_Queue::process_queue();
            self::redirect('retried');
        }

        // Delete a job.
        if (isset($_GET['delete']) && check_admin_referer('stagekitwp_members_queue_action')) {
            STAGEKITWP_MEMBERS_Email_Queue::delete_job((int) $_GET['delete']);
            self::redirect('deleted');
        }

        // Clear all.
        if (isset($_GET['clear']) && check_admin_referer('stagekitwp_members_queue_action')) {
            STAGEKITWP_MEMBERS_Email_Queue::clear_queue();
            self::redirect('cleared');
        }
    }

    private static function redirect($notice) {
        wp_safe_redirect(add_query_arg('stagekitwp_members_notice', $notice, self::page_url()));
        exit;
    }

    private static function action_link($action, $id = null) {
        $args = [$action => $id !== null ? (int) $id : 1];
        return wp_nonce_url(add_query_arg($args, self::page_url()), 'stagekitwp_members_queue_action');
    }

    public static function render() {

        if (!current_user_can(self::CAP)) {
            wp_die('Access denied');
        }

        self::render_notice();

        $counts = STAGEKITWP_MEMBERS_Email_Queue::counts();
        $jobs   = STAGEKITWP_MEMBERS_Email_Queue::get_queue();
        $next   = wp_next_scheduled('stagekitwp_members_process_queue');
        ?>
        <div class="wrap">
            <h1>Email Queue</h1>

            <p>
                <strong>Pending:</strong> <?php echo intval($counts['pending']); ?> &nbsp;|&nbsp;
                <strong>Sent:</strong> <?php echo intval($counts['sent']); ?> &nbsp;|&nbsp;
                <strong>Failed:</strong> <?php echo intval($counts['failed']); ?> &nbsp;|&nbsp;
                <strong>Total:</strong> <?php echo intval($counts['total']); ?>
            </p>

            <p>
                Next automatic run:
                <strong><?php echo $next ? esc_html(date_i18n('M j, Y g:i a', $next)) : 'not scheduled'; ?></strong>
            </p>

            <p>
                <a class="button button-primary" href="<?php echo esc_url(self::action_link('run')); ?>">Run Queue Now</a>
                <a class="button" href="<?php echo esc_url(self::action_link('clear')); ?>"
                   onclick="return confirm('Clear the entire queue?');">Clear Queue</a>
            </p>

            <?php self::render_table($jobs); ?>
        </div>
        <?php
    }

    private static function render_table($jobs) {
        ?>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>To</th>
                    <th>Subject</th>
                    <th>Send Time</th>
                    <th>Status</th>
                    <th>Attempts</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($jobs)): ?>
                    <tr><td colspan="7">Queue is empty.</td></tr>
                <?php else: foreach ($jobs as $job):
                    $status = $job['status'] ?? 'pending';
                    $color  = $status === 'sent' ? 'green' : ($status === 'failed' ? '#b32d2e' : '#b26a00');
                ?>
                    <tr>
                        <td><?php echo intval($job['id'] ?? 0); ?></td>
                        <td><?php echo esc_html($job['email'] ?? ''); ?></td>
                        <td><?php echo esc_html($job['subject'] ?? ''); ?></td>
                        <td><?php echo esc_html(date_i18n('M j, g:i a', (int) ($job['send_time'] ?? time()))); ?></td>
                        <td style="color:<?php echo esc_attr($color); ?>;"><?php echo esc_html(ucfirst($status)); ?></td>
                        <td><?php echo intval($job['attempts'] ?? 0); ?></td>
                        <td>
                            <?php if ($status === 'failed'): ?>
                                <a href="<?php echo esc_url(self::action_link('retry', $job['id'] ?? 0)); ?>">Retry</a> |
                            <?php endif; ?>
                            <a href="<?php echo esc_url(self::action_link('delete', $job['id'] ?? 0)); ?>"
                               style="color:#b32d2e;">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
        <?php
    }

    private static function render_notice() {
        if (empty($_GET['stagekitwp_members_notice'])) {
            return;
        }
        $map = [
            'ran'     => 'Queue processed.',
            'retried' => 'Job retried.',
            'deleted' => 'Job deleted.',
            'cleared' => 'Queue cleared.',
        ];
        $raw = sanitize_text_field(wp_unslash($_GET['stagekitwp_members_notice']));
        $msg = $map[$raw] ?? 'Done.';
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($msg) . '</p></div>';
    }
}
