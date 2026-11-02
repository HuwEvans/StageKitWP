<?php

if (!defined('ABSPATH')) {
    exit;
}

class STAGEKITWP_MEMBERS_Email_Log {

    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_menu']);
    }

    /**
     * Backward-compatible delegate. Table creation is now owned by STAGEKITWP_MEMBERS_DB.
     */
    public static function install() {
        if (class_exists('STAGEKITWP_MEMBERS_DB')) {
            STAGEKITWP_MEMBERS_DB::migrate();
        }
    }

    /**
     * Log emails
     */
    public static function log($to, $subject, $message, $status, $group_id = '') {

        global $wpdb;

        $wpdb->insert(
            $wpdb->prefix . 'stagekitwp_members_email_log',
            [
                'email_to'   => sanitize_email($to),
                'subject'    => sanitize_text_field($subject),
                'message'    => wp_kses_post($message),
                'status'     => sanitize_text_field($status),
                'group_id'   => sanitize_text_field($group_id),
                'created_at' => current_time('mysql')
            ]
        );
    }

    public static function group_counts($group_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'stagekitwp_members_email_log';
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT status, COUNT(*) AS total FROM {$table} WHERE group_id = %s GROUP BY status",
            sanitize_text_field($group_id)
        ));
        $counts = ['success' => 0, 'failed' => 0];
        foreach ((array) $rows as $row) {
            if (isset($counts[$row->status])) {
                $counts[$row->status] = (int) $row->total;
            }
        }
        return $counts;
    }

    /**
     * Admin menu
     */
    public static function add_menu() {

        add_submenu_page(
            'stagekitwp',
            'Email Logs',
            'Email Logs',
            'manage_options',
            'stagekitwp-ma-email-logs',
            [__CLASS__, 'render']
        );
    }

    /**
     * Render logs
     */
    public static function render() {

        global $wpdb;

        $table = $wpdb->prefix . 'stagekitwp_members_email_log';

        $exists = class_exists('STAGEKITWP_MEMBERS_DB') && STAGEKITWP_MEMBERS_DB::table_exists($table);

        ?>
        <div class="wrap">
            <h1>Email Logs</h1>

            <?php if (!$exists): ?>
                <div class="notice notice-error">
                    <p>Email log table does not exist.</p>
                </div>
            <?php return; endif; ?>

            <?php
            $group_id = isset($_GET['stagekitwp_members_group']) ? sanitize_text_field(wp_unslash($_GET['stagekitwp_members_group'])) : '';
            if ($group_id !== '') {
                $logs = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$table} WHERE group_id = %s ORDER BY created_at DESC LIMIT 500",
                    $group_id
                ));
            } else {
                $logs = $wpdb->get_results("SELECT * FROM {$table} ORDER BY created_at DESC LIMIT 100");
            }
            $groups = STAGEKITWP_MEMBERS_Campaigns::get_all_groups();
            ?>

            <form method="get" style="margin:1em 0">
                <input type="hidden" name="page" value="stagekitwp-ma-admin">
                <input type="hidden" name="stagekitwp_members_tab" value="email_logs">
                <label>Recipient group
                    <select name="stagekitwp_members_group">
                        <option value="">All email history</option>
                        <?php foreach ($groups as $group) : ?>
                            <option value="<?php echo esc_attr($group['id']); ?>" <?php selected($group_id, $group['id']); ?>>
                                <?php echo esc_html($group['name'] . (!empty($group['deleted_at']) ? ' (archived)' : '')); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button class="button">Filter history</button>
            </form>

            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>To</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Recipient group</th>
                    </tr>
                </thead>
                <tbody>

                <?php foreach ($logs as $log): ?>

                    <tr class="stagekitwp-ma-log-row" data-id="<?php echo esc_attr($log->id); ?>">
                        <td><?php echo esc_html($log->created_at); ?></td>
                        <td><?php echo esc_html($log->email_to); ?></td>
                        <td><?php echo esc_html($log->subject); ?></td>
                        <td>
                            <?php if ($log->status === 'success'): ?>
                                <span style="color:green;">✅ Sent</span>
                            <?php else: ?>
                                <span style="color:red;">❌ Failed</span>
                            <?php endif; ?>
                        </td>
                        <?php $log_group = $log->group_id ? STAGEKITWP_MEMBERS_Campaigns::get_group_history($log->group_id) : null; ?>
                        <td><?php echo esc_html($log_group ? $log_group['name'] : ($log->group_id ? 'Unknown group' : 'Individual / system')); ?></td>
                    </tr>

                    <tr class="stagekitwp-ma-log-detail" id="log-<?php echo esc_attr($log->id); ?>" style="display:none;">
                        <td colspan="5" style="background:#f9f9f9;">
                            <strong>Message:</strong>
                            <div style="margin-top:10px; white-space:pre-wrap;">
                                <?php echo esc_html($log->message); ?>
                            </div>
                        </td>
                    </tr>

                <?php endforeach; ?>

                </tbody>
            </table>
        </div>

        <script>
        document.querySelectorAll('.stagekitwp-ma-log-row').forEach(row => {
            row.addEventListener('click', function() {
                const detail = document.getElementById('log-' + this.dataset.id);
                detail.style.display = (detail.style.display === 'none') ? 'table-row' : 'none';
            });
        });
        </script>
        <?php
    }
}