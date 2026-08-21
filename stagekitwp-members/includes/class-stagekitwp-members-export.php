<?php
/**
 * CSV exports for Producers/Executives: members, event RSVPs, and email logs.
 *
 * Downloads run through admin-post.php so we can stream proper CSV headers.
 * Each export is nonce-protected and capability-gated.
 */

if (!defined('ABSPATH')) {
    exit;
}

class STAGEKITWP_MEMBERS_Export {

    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_menu']);
        add_action('admin_post_stagekitwp_members_export', [__CLASS__, 'handle_export']);
    }

    public static function add_menu() {
        add_submenu_page(
            'stagekitwp',
            'Export',
            'Export',
            STAGEKITWP_MEMBERS_Roles::CAP_SEARCH_MEMBERS,
            'stagekitwp-ma-export',
            [__CLASS__, 'render']
        );
    }

    /**
     * Build a nonce-protected export URL for a given dataset.
     */
    private static function export_url($type) {
        return wp_nonce_url(
            admin_url('admin-post.php?action=stagekitwp_members_export&type=' . $type),
            'stagekitwp_members_export_' . $type
        );
    }

    public static function render() {

        if (!current_user_can(STAGEKITWP_MEMBERS_Roles::CAP_SEARCH_MEMBERS)) {
            wp_die('Access denied');
        }

        $can_logs = current_user_can(STAGEKITWP_MEMBERS_Roles::CAP_MANAGE_MEMBERS);
        ?>
        <div class="wrap">
            <h1>Export Data</h1>
            <p>Download CSV files of member data. Files open in Excel, Numbers, or Google Sheets.</p>

            <table class="widefat striped" style="max-width:640px;">
                <tbody>
                    <tr>
                        <td><strong>Members</strong><br><span style="color:#666;">Name, email, role, interests.</span></td>
                        <td><a class="button button-primary" href="<?php echo esc_url(self::export_url('members')); ?>">Download CSV</a></td>
                    </tr>
                    <tr>
                        <td><strong>Event RSVPs</strong><br><span style="color:#666;">Who has RSVP'd to each event.</span></td>
                        <td><a class="button button-primary" href="<?php echo esc_url(self::export_url('rsvps')); ?>">Download CSV</a></td>
                    </tr>
                    <?php if ($can_logs): ?>
                    <tr>
                        <td><strong>Email Log</strong><br><span style="color:#666;">Recent sent/failed emails.</span></td>
                        <td><a class="button button-primary" href="<?php echo esc_url(self::export_url('email_log')); ?>">Download CSV</a></td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Route + stream a CSV download.
     */
    public static function handle_export() {

        $type = isset($_GET['type']) ? sanitize_key($_GET['type']) : '';

        if (!in_array($type, ['members', 'rsvps', 'email_log'], true)) {
            wp_die('Unknown export type.');
        }

        check_admin_referer('stagekitwp_members_export_' . $type);

        // Capability: logs require manage_members; others require search.
        $needed = ($type === 'email_log')
            ? STAGEKITWP_MEMBERS_Roles::CAP_MANAGE_MEMBERS
            : STAGEKITWP_MEMBERS_Roles::CAP_SEARCH_MEMBERS;

        if (!current_user_can($needed)) {
            wp_die('Access denied.');
        }

        $filename = 'stagekitwp-' . $type . '-' . date('Y-m-d') . '.csv';

        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        // UTF-8 BOM so Excel reads accents correctly.
        fprintf($out, "\xEF\xBB\xBF");

        switch ($type) {
            case 'members':   self::rows_members($out);   break;
            case 'rsvps':     self::rows_rsvps($out);      break;
            case 'email_log': self::rows_email_log($out);  break;
        }

        fclose($out);
        exit;
    }

    private static function rows_members($out) {

        self::put_row($out, ['Name', 'Email', 'Roles', 'Interests', 'Registered']);

        $users = get_users(['orderby' => 'display_name', 'order' => 'ASC']);

        foreach ($users as $user) {
            $terms = wp_get_object_terms($user->ID, 'stagekitwp_interest', ['fields' => 'names']);
            $interests = (!is_wp_error($terms) && $terms) ? implode('; ', $terms) : '';

            self::put_row($out, [
                $user->display_name,
                $user->user_email,
                implode('; ', (array) $user->roles),
                $interests,
                $user->user_registered,
            ]);
        }
    }

    /**
     * PHP 8.4-safe CSV line writer (explicit delimiter/enclosure/escape).
     */
    private static function put_row($out, array $fields) {
        fputcsv($out, $fields, ',', '"', '\\');
    }

    private static function rows_rsvps($out) {

        self::put_row($out, ['Event', 'Event Date', 'Attendee', 'Attendee Email', 'RSVP', 'Attended']);

        $events = get_posts([
            'post_type'   => 'stagekitwp_event',
            'numberposts' => -1,
            'post_status' => 'publish',
        ]);

        $have_api = class_exists('STAGEKITWP_MEMBERS_RSVP');

        foreach ($events as $event) {
            $date = get_post_meta($event->ID, '_stagekitwp_members_event_date', true);

            $rows = $have_api ? STAGEKITWP_MEMBERS_RSVP::get_for_event($event->ID) : [];

            if (empty($rows)) {
                // Show events with no RSVPs too, for completeness.
                self::put_row($out, [$event->post_title, $date, '(no RSVPs)', '', '', '']);
                continue;
            }

            foreach ($rows as $r) {
                $user = get_userdata($r->user_id);
                if (!$user) { continue; }
                self::put_row($out, [
                    $event->post_title,
                    $date,
                    $user->display_name,
                    $user->user_email,
                    $r->status,
                    $r->attended ? 'Yes' : 'No',
                ]);
            }
        }
    }

    private static function rows_email_log($out) {

        global $wpdb;
        $table = $wpdb->prefix . 'stagekitwp_members_email_log';

        self::put_row($out, ['Date', 'To', 'Subject', 'Status']);

        if (class_exists('STAGEKITWP_MEMBERS_DB') && !STAGEKITWP_MEMBERS_DB::table_exists($table)) {
            return;
        }

        $rows = $wpdb->get_results("SELECT created_at, email_to, subject, status FROM {$table} ORDER BY created_at DESC LIMIT 5000");

        foreach ((array) $rows as $row) {
            self::put_row($out, [$row->created_at, $row->email_to, $row->subject, $row->status]);
        }
    }
}
