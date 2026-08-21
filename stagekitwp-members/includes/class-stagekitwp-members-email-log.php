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
    public static function log($to, $subject, $message, $status) {

        global $wpdb;

        $wpdb->insert(
            $wpdb->prefix . 'stagekitwp_members_email_log',
            [
                'email_to'   => sanitize_email($to),
                'subject'    => sanitize_text_field($subject),
                'message'    => wp_kses_post($message),
                'status'     => sanitize_text_field($status),
                'created_at' => current_time('mysql')
            ]
        );
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
            $logs = $wpdb->get_results("SELECT * FROM {$table} ORDER BY created_at DESC LIMIT 100");
            ?>

            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>To</th>
                        <th>Subject</th>
                        <th>Status</th>
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
                    </tr>

                    <tr class="stagekitwp-ma-log-detail" id="log-<?php echo esc_attr($log->id); ?>" style="display:none;">
                        <td colspan="4" style="background:#f9f9f9;">
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