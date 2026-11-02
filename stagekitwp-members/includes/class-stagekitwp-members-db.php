<?php
/**
 * Central database migration runner.
 *
 * Replaces scattered register_activation_hook() calls with a single,
 * version-gated migration that runs on plugins_loaded. Portable across
 * MySQL (production) and SQLite (Studio) because it relies on dbDelta
 * and an option flag instead of MySQL-only "SHOW TABLES".
 */

if (!defined('ABSPATH')) {
    exit;
}

class StageKitWP_MEMBERS_DB {

    /**
     * Bump this whenever a table schema changes.
     */
    const DB_VERSION = '1.6.0';

    const OPTION_KEY = 'stagekitwp_members_db_version';

    public static function init() {
        // Run migrations on load; dbDelta is idempotent so this is cheap
        // and self-heals installs where activation hooks never fired.
        add_action('plugins_loaded', [__CLASS__, 'maybe_migrate'], 5);
    }

    /**
     * Run migrations only when the stored version is behind.
     */
    public static function maybe_migrate() {

        $installed = get_option(self::OPTION_KEY);

        if ($installed === self::DB_VERSION) {
            return;
        }

        self::migrate();

        update_option(self::OPTION_KEY, self::DB_VERSION);
    }

    /**
     * Force all tables to current schema. Safe to call repeatedly.
     */
    public static function migrate() {

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        self::create_invitations_table();

        self::create_invitations_table();
        self::create_email_log_table();
        self::create_rsvps_table();
        self::create_availability_table();
        self::create_memberships_tables();
        self::create_volunteer_tables();
    }

    /**
     * Portable table-existence check (MySQL + SQLite).
     */
    public static function table_exists($table) {

        global $wpdb;

        // information_schema works on MySQL; the SQLite integration plugin
        // maps it to sqlite_master, so this is portable.
        $found = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT table_name FROM information_schema.tables WHERE table_name = %s",
                $table
            )
        );

        return $found === $table;
    }

    private static function create_invitations_table() {

        global $wpdb;

        $table = $wpdb->prefix . 'stagekitwp_invitations';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            email varchar(255) NOT NULL,
            token varchar(255) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            expires datetime NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY token (token)
        ) {$charset_collate};";

        dbDelta($sql);
    }

    private static function create_email_log_table() {

        global $wpdb;

        $table = $wpdb->prefix . 'stagekitwp_members_email_log';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            email_to varchar(255) NOT NULL,
            subject text NOT NULL,
            message text NOT NULL,
            status varchar(20) NOT NULL,
            group_id varchar(64) NOT NULL DEFAULT '',
            created_at datetime NOT NULL,
            PRIMARY KEY  (id)
        ) {$charset_collate};";

        dbDelta($sql);
    }

    /**
     * Event RSVPs (Phase D). Replaces per-user 'stagekitwp_members_rsvp_<event_id>' meta
     * with a queryable table so we can answer "who is attending event X?"
     * and track check-in/attendance. The unique (event_id,user_id) index
     * keeps one row per member per event.
     */
    private static function create_rsvps_table() {

        global $wpdb;

        $table = $wpdb->prefix . 'stagekitwp_members_rsvps';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            event_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'going',
            attended tinyint(1) NOT NULL DEFAULT 0,
            responded_at datetime NOT NULL,
            checked_in_at datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY event_user (event_id, user_id),
            KEY event_id (event_id),
            KEY user_id (user_id)
        ) {$charset_collate};";

        dbDelta($sql);
    }

    /**
     * Member availability ranges (Phase E). Each row is a date range with a
     * status, so Producers can see who is available/unavailable/tentative for
     * an event's date. Portable across MySQL + SQLite.
     */
    private static function create_availability_table() {

        global $wpdb;

        $table = $wpdb->prefix . 'stagekitwp_members_availability';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            start_date date NOT NULL,
            end_date date NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'unavailable',
            note varchar(255) DEFAULT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY start_date (start_date),
            KEY end_date (end_date)
        ) {$charset_collate};";

        dbDelta($sql);
    }

    /**
     * Membership records and their history (Phase 4 payment contract).
     * One current row per user plus an append-only history log.
     */
    private static function create_memberships_tables() {

        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $table = $wpdb->prefix . 'stagekitwp_members_memberships';
        dbDelta("CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            level varchar(40) NOT NULL DEFAULT '',
            status varchar(20) NOT NULL DEFAULT 'none',
            start_date datetime DEFAULT NULL,
            expiry_date datetime DEFAULT NULL,
            source varchar(40) NOT NULL DEFAULT 'manual',
            external_ref varchar(190) NOT NULL DEFAULT '',
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY user_id (user_id),
            KEY status (status),
            KEY expiry_date (expiry_date)
        ) {$charset_collate};");

        $history = $wpdb->prefix . 'stagekitwp_members_membership_history';
        dbDelta("CREATE TABLE {$history} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            old_status varchar(20) NOT NULL DEFAULT '',
            new_status varchar(20) NOT NULL DEFAULT '',
            source varchar(40) NOT NULL DEFAULT '',
            external_ref varchar(190) NOT NULL DEFAULT '',
            actor_id bigint(20) unsigned NOT NULL DEFAULT 0,
            note varchar(255) NOT NULL DEFAULT '',
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id)
        ) {$charset_collate};");
    }

    /**
     * Volunteer positions (including reusable templates) and member signups.
     */
    private static function create_volunteer_tables() {

        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $positions = $wpdb->prefix . 'stagekitwp_members_positions';
        dbDelta("CREATE TABLE {$positions} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            event_id bigint(20) unsigned NOT NULL DEFAULT 0,
            title varchar(190) NOT NULL,
            description text NOT NULL,
            slots int(10) unsigned NOT NULL DEFAULT 1,
            confirmed_count int(10) unsigned NOT NULL DEFAULT 0,
            required_role varchar(40) NOT NULL DEFAULT '',
            required_interest varchar(100) NOT NULL DEFAULT '',
            sort_order int(10) unsigned NOT NULL DEFAULT 0,
            active tinyint(1) NOT NULL DEFAULT 1,
            is_template tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY event_active (event_id, active),
            KEY template_active (is_template, active)
        ) {$charset_collate};");

        $volunteers = $wpdb->prefix . 'stagekitwp_members_volunteers';
        dbDelta("CREATE TABLE {$volunteers} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            event_id bigint(20) unsigned NOT NULL,
            position_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'confirmed',
            note varchar(255) NOT NULL DEFAULT '',
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            reminder_sent_at datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY position_user (position_id, user_id),
            KEY event_status (event_id, status),
            KEY user_status (user_id, status),
            KEY reminders (status, reminder_sent_at)
        ) {$charset_collate};");
    }
}