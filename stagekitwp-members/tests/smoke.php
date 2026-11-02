<?php
/**
 * Read-only integration smoke test for the local WordPress installation.
 *
 * Run with:
 * studio wp --path <site-path> eval-file <plugin-path>\tests\smoke.php
 */

if (!defined('WP_CLI') || !WP_CLI) {
    return;
}

WP_CLI::add_hook('after_wp_load', static function (): void {
    global $wpdb;

    $checks = 0;
    $expect = static function (bool $condition, string $message) use (&$checks): void {
        if (!$condition) {
            throw new RuntimeException($message);
        }
        $checks++;
    };

    try {
        $expect(defined('STAGEKITWPMA_VERSION') && STAGEKITWPMA_VERSION === '3.2.7', 'Plugin version constant is not 3.2.7.');

        foreach ([
            'STAGEKITWP_MEMBERS_DB',
            'STAGEKITWP_MEMBERS_Roles',
            'STAGEKITWP_MEMBERS_Access',
            'STAGEKITWP_MEMBERS_Membership',
            'STAGEKITWP_MEMBERS_Volunteers',
            'STAGEKITWP_MEMBERS_Campaigns',
            'STAGEKITWP_MEMBERS_Email_Queue',
            'STAGEKITWP_MEMBERS_Email_Log',
            'STAGEKITWP_MEMBERS_REST',
        ] as $class) {
            $expect(class_exists($class), 'Expected plugin class is not loaded: ' . $class);
        }

        $expect(
            get_option(STAGEKITWP_MEMBERS_DB::OPTION_KEY) === STAGEKITWP_MEMBERS_DB::DB_VERSION,
            'Database migration version does not match the current schema.'
        );

        foreach ([
            'stagekitwp_invitations',
            'stagekitwp_members_email_log',
            'stagekitwp_members_rsvps',
            'stagekitwp_members_availability',
            'stagekitwp_members_memberships',
            'stagekitwp_members_membership_history',
            'stagekitwp_members_positions',
            'stagekitwp_members_volunteers',
        ] as $table_suffix) {
            $table = $wpdb->prefix . $table_suffix;
            $expect(STAGEKITWP_MEMBERS_DB::table_exists($table), 'Expected plugin table is missing: ' . $table);
        }

        foreach ([
            'stagekitwp_member' => STAGEKITWP_MEMBERS_Roles::CAP_RSVP_EVENTS,
            'stagekitwp_producer' => STAGEKITWP_MEMBERS_Roles::CAP_SEND_EMAIL,
            'stagekitwp_executive' => STAGEKITWP_MEMBERS_Roles::CAP_MANAGE_SETTINGS,
        ] as $role_name => $capability) {
            $role = get_role($role_name);
            $expect($role && $role->has_cap($capability), 'Expected role capability is missing: ' . $role_name . ' / ' . $capability);
        }

        $shortcodes = [
            'stagekitwp_members_only',
            'stagekitwp_members_events',
            'stagekitwp_members_volunteer',
            'stagekitwp_members_my_volunteering',
            'stagekitwp_members_producer_dashboard',
            'stagekitwp_members_availability',
        ];
        foreach ($shortcodes as $shortcode) {
            $expect(shortcode_exists($shortcode), 'Expected shortcode is not registered: [' . $shortcode . ']');
        }

        $previous_user_id = get_current_user_id();
        wp_set_current_user(0);
        $expect(stagekitwp_members_user_can_access('logged_out'), 'Logged-out access rule should allow logged-out visitors.');
        $expect(!stagekitwp_members_user_can_access('logged_in'), 'Logged-in access rule should deny logged-out visitors.');
        $expect(stagekitwp_members_get_user_level(0) === '', 'Logged-out visitors should not have a member level.');
        wp_set_current_user($previous_user_id);

        WP_CLI::success(sprintf('Members read-only smoke tests passed (%d checks).', $checks));
    } catch (Throwable $error) {
        if (isset($previous_user_id)) {
            wp_set_current_user($previous_user_id);
        }
        WP_CLI::error($error->getMessage());
    }
});
