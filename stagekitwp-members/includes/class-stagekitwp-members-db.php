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
    const DB_VERSION = '1.2.0';

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
        self::create_email_log_table();
        self::create_rsvps_table();
        self::create_availability_table();
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
}
