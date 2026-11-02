<?php

if (!defined('ABSPATH')) {
    exit;
}

class STAGEKITWP_MEMBERS_Health {

    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_menu']);
        add_action('admin_notices', [__CLASS__, 'email_admin_notice']);
        add_action('admin_init', [__CLASS__, 'handle_test_email']);
        add_action('admin_init', [__CLASS__, 'handle_convert_subscribers']);
    }

    /**
     * Subscriber users have no member capabilities by design.
     */
    private static function check_subscribers() {
        $ids = get_users(['role' => 'subscriber', 'fields' => 'ID', 'number' => 200]);
        echo '<tr><td>Subscriber users</td>';
        if (!$ids) {
            echo '<td style="color:green;">✅ OK</td><td>No plain WordPress subscribers.</td></tr>';
            return;
        }
        echo '<td style="color:orange;">⚠ ' . (int) count($ids) . ' found</td><td>';
        echo 'Subscribers have no member access (they only see Log In/Log Out). ';
        echo '<form method="post" style="display:inline;">';
        wp_nonce_field('stagekitwp_members_convert_subscribers', 'stagekitwp_members_convert_nonce');
        echo '<button class="button" name="stagekitwp_members_convert_subscribers" onclick="return confirm(\'Convert all subscribers to Member?\');">Convert to Member</button>';
        echo '</form></td></tr>';
    }

    /**
     * Convert subscribers to the Member role (admin action).
     */
    public static function handle_convert_subscribers() {
        if (
            !isset($_POST['stagekitwp_members_convert_subscribers'], $_POST['stagekitwp_members_convert_nonce']) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['stagekitwp_members_convert_nonce'])), 'stagekitwp_members_convert_subscribers') ||
            !current_user_can('manage_options')
        ) {
            return;
        }
        $count = 0;
        foreach (get_users(['role' => 'subscriber', 'fields' => 'ID']) as $uid) {
            $user = get_userdata((int) $uid);
            if ($user) {
                $user->set_role('stagekitwp_member');
                $count++;
            }
        }
        set_transient('stagekitwp_members_convert_result', $count, 30);
    }

    /**
     * Add menu
     */
    public static function add_menu() {

        add_submenu_page(
            'stagekitwp',
            'Health Check',
            'Health Check',
            'manage_options',
            'stagekitwp-ma-health',
            [__CLASS__, 'render']
        );
    }

    /**
     * Render page
     */
    public static function render() {
        ?>

        <div class="wrap">
            <h1>Members - Health Check</h1>
			<?php
			$current_user = wp_get_current_user();
			echo '<p>Test email will be sent to: <strong>' . esc_html($current_user->user_email) . '</strong></p>';?>
            <form method="post" style="margin-bottom:20px;">
                <?php wp_nonce_field('stagekitwp_members_send_test_email', 'stagekitwp_members_test_email_nonce'); ?>
                <button class="button button-primary" name="stagekitwp_members_send_test_email">
                    Send Test Email
                </button>
            </form>

            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Check</th>
                        <th>Status</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>

                    <?php self::check_stagekitwp_core(); ?>
                    <?php self::check_email(); ?>
                    <?php self::check_subscribers(); ?>

                </tbody>
            </table>

        </div>

        <?php
    }

    /**
    * StageKitWP Core check
     */
    private static function check_stagekitwp_core() {

        $status = class_exists('StageKitWP_Core');

        echo '<tr>';
        echo '<td>StageKitWP Core Plugin</td>';

        if ($status) {
            echo '<td style="color:green;">✅ OK</td>';
            echo '<td>Detected and active</td>';
        } else {
            echo '<td style="color:red;">❌ Missing</td>';
            echo '<td>Required plugin not active</td>';
        }

        echo '</tr>';
    }

    /**
     * Email Check (READ ONLY - no sending)
     */
    private static function check_email() {

        $status = get_option('stagekitwp_members_email_status');
        $last_attempt = get_option('stagekitwp_members_last_email_attempt');

        echo '<tr>';
        echo '<td>Email System</td>';

        echo '<td>';

        if ($status === 'ok') {
            echo '<span style="color:green;">✅ Working</span>';
        } elseif ($status === 'failed') {
            echo '<span style="color:red;">❌ Failed</span>';
        } else {
            echo '<span style="color:orange;">⚠ Not tested</span>';
        }

        echo '</td>';

        echo '<td>';

        if ($last_attempt) {
            echo 'Last tested: ' . esc_html(date('Y-m-d H:i:s', $last_attempt));
        } else {
            echo 'Use "Send Test Email" button';
        }

        echo '</td>';

        echo '</tr>';
    }

    /**
     * Central email sender
     */
public static function send_email($to, $subject, $message, $context = [], $campaign_id = '') {

    if (!get_option('stagekitwp_members_email_enabled', 1)) {
        return false;
    }

    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>',
    ];

    // ✅ Apply variables
    $user = get_user_by('email', $to);
    $context = is_array($context) ? $context : [];
    if (!empty($context['bulk_campaign']) && $user) {
        $context['unsubscribe_link'] = STAGEKITWP_MEMBERS_Campaigns::unsubscribe_url($user->ID);
        $context['preferences_link'] = home_url('/profile/');
        $message .= '<hr><p><a href="' . esc_url($context['unsubscribe_link']) . '">Unsubscribe from announcement emails</a> · ';
        $message .= '<a href="' . esc_url($context['preferences_link']) . '">Manage email preferences</a></p>';
    }
    $subject = STAGEKITWP_MEMBERS_Email_Helper::parse_variables($subject, $user, $context, false);
    $message = STAGEKITWP_MEMBERS_Email_Helper::parse_variables($message, $user, $context);

    $sent = wp_mail($to, $subject, $message, $headers);

    update_option('stagekitwp_members_email_status', $sent ? 'ok' : 'failed');

    STAGEKITWP_MEMBERS_Email_Log::log($to, $subject, $message, $sent ? 'success' : 'failed', $campaign_id);

    return $sent;
}
    /**
     * Handle test email button
     */
    public static function handle_test_email() {

        if (
            isset($_POST['stagekitwp_members_send_test_email']) &&
            isset($_POST['stagekitwp_members_test_email_nonce']) &&
            wp_verify_nonce($_POST['stagekitwp_members_test_email_nonce'], 'stagekitwp_members_send_test_email')
        ) {

            if (!current_user_can('manage_options')) {
                return;
            }

			$current_user = wp_get_current_user();
			
			if (!$current_user || empty($current_user->user_email)) {
			    return;
			}
			
			$to = $current_user->user_email;
			
			$sent = self::send_email(
			    $to,
                'Members Test Email',
                "This is a test email from Members Health Check.\n\nIf you received this, email is working correctly."
			);

            // ✅ Store result safely
            set_transient('stagekitwp_members_test_email_result', $sent ? 'success' : 'error', 30);
        }
    }

    /**
     * Admin notices (SAFE OUTPUT)
     */
    public static function email_admin_notice() {

        if (!current_user_can('manage_options')) {
            return;
        }

        // ✅ Test email result
        $converted = get_transient('stagekitwp_members_convert_result');
        if ($converted !== false) {
            echo '<div class="notice notice-success"><p>Converted ' . (int) $converted . ' subscriber(s) to Member.</p></div>';
            delete_transient('stagekitwp_members_convert_result');
        }
        $result = get_transient('stagekitwp_members_test_email_result');

        if ($result) {

            if ($result === 'success') {
                echo '<div class="notice notice-success"><p>✅ Test email sent successfully. Check your inbox.</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>❌ Test email failed to send.</p></div>';
            }

            delete_transient('stagekitwp_members_test_email_result');
        }

        // ✅ System status warning
        $status = get_option('stagekitwp_members_email_status');

        if ($status === 'failed') {

            echo '<div class="notice notice-error">';
            echo '<p><strong>Members:</strong> Email system is NOT working. Notifications will fail.</p>';
            echo '</div>';

        } elseif (!$status) {

            echo '<div class="notice notice-warning">';
            echo '<p><strong>Members:</strong> Email system has not been tested yet.</p>';
            echo '</div>';
        }
    }
}