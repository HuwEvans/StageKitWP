<?php
/**
 * RSVP data API (Phase D).
 *
 * Single source of truth for event RSVPs, backed by {prefix}stagekitwp_members_rsvps.
 * Replaces the old per-user 'stagekitwp_members_rsvp_<event_id>' meta, which could not
 * answer "who is attending event X?" without scanning every user and had
 * no room for attendance/check-in state.
 *
 * All queries are portable across MySQL (production) and SQLite (Studio):
 * plain prepared statements, no MySQL-only syntax.
 */

if (!defined('ABSPATH')) {
    exit;
}

class STAGEKITWP_MEMBERS_RSVP {

    const STATUSES = ['going', 'maybe', 'declined'];

    public static function init() {
        // Copy legacy user-meta RSVPs into the table once (idempotent).
        add_action('plugins_loaded', [__CLASS__, 'maybe_migrate_meta'], 6);
    }

    private static function table() {
        global $wpdb;
        return $wpdb->prefix . 'stagekitwp_members_rsvps';
    }

    /**
     * Create or update a member's RSVP for an event.
     */
    public static function set_status($event_id, $user_id, $status) {

        global $wpdb;

        $event_id = (int) $event_id;
        $user_id  = (int) $user_id;

        if (!in_array($status, self::STATUSES, true)) {
            return false;
        }

        $table = self::table();
        $now   = current_time('mysql');

        $existing = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE event_id = %d AND user_id = %d",
                $event_id,
                $user_id
            )
        );

        if ($existing) {
            return (false !== $wpdb->update(
                $table,
                ['status' => $status, 'responded_at' => $now],
                ['id' => (int) $existing],
                ['%s', '%s'],
                ['%d']
            ));
        }

        return (false !== $wpdb->insert(
            $table,
            [
                'event_id'     => $event_id,
                'user_id'      => $user_id,
                'status'       => $status,
                'responded_at' => $now,
            ],
            ['%d', '%d', '%s', '%s']
        ));
    }

    /**
     * A member's RSVP status for an event, or '' if none.
     */
    public static function get_status($event_id, $user_id) {

        global $wpdb;
        $table = self::table();

        $status = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT status FROM {$table} WHERE event_id = %d AND user_id = %d",
                (int) $event_id,
                (int) $user_id
            )
        );

        return $status ? $status : '';
    }

    /**
     * All RSVP rows for an event (optionally filtered by status).
     */
    public static function get_for_event($event_id, $status = '') {

        global $wpdb;
        $table = self::table();

        if ($status && in_array($status, self::STATUSES, true)) {
            return $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$table} WHERE event_id = %d AND status = %s ORDER BY responded_at ASC",
                    (int) $event_id,
                    $status
                )
            );
        }

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE event_id = %d ORDER BY responded_at ASC",
                (int) $event_id
            )
        );
    }

    /**
     * All RSVP rows for a member.
     */
    public static function get_for_user($user_id) {

        global $wpdb;
        $table = self::table();

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE user_id = %d ORDER BY responded_at DESC",
                (int) $user_id
            )
        );
    }

    /**
     * Status counts for an event: ['going'=>n,'maybe'=>n,'declined'=>n,'attended'=>n].
     */
    public static function counts($event_id) {

        global $wpdb;
        $table = self::table();

        $out = ['going' => 0, 'maybe' => 0, 'declined' => 0, 'attended' => 0];

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT status, COUNT(*) AS n FROM {$table} WHERE event_id = %d GROUP BY status",
                (int) $event_id
            )
        );
        foreach ((array) $rows as $r) {
            if (isset($out[$r->status])) {
                $out[$r->status] = (int) $r->n;
            }
        }

        $out['attended'] = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE event_id = %d AND attended = 1",
                (int) $event_id
            )
        );

        return $out;
    }

    /**
     * Mark (or unmark) a member as attended for an event.
     */
    public static function mark_attended($event_id, $user_id, $attended = true) {

        global $wpdb;
        $table = self::table();

        return (false !== $wpdb->update(
            $table,
            [
                'attended'      => $attended ? 1 : 0,
                'checked_in_at' => $attended ? current_time('mysql') : null,
            ],
            ['event_id' => (int) $event_id, 'user_id' => (int) $user_id],
            ['%d', '%s'],
            ['%d', '%d']
        ));
    }

    /**
     * One-time copy of legacy 'stagekitwp_members_rsvp_<event_id>' user-meta into the
     * table. Idempotent: existing rows are left untouched. The meta is kept
     * as a safety fallback (per user choice) and can be pruned later.
     */
    public static function maybe_migrate_meta() {

        if (get_option('stagekitwp_members_rsvp_meta_migrated')) {
            return;
        }

        // Ensure the table exists before importing.
        if (class_exists('STAGEKITWP_MEMBERS_DB') && !STAGEKITWP_MEMBERS_DB::table_exists(self::table())) {
            return;
        }

        global $wpdb;

        // Find every user-meta key shaped like stagekitwp_members_rsvp_<digits>.
        $rows = $wpdb->get_results(
            "SELECT user_id, meta_key FROM {$wpdb->usermeta} WHERE meta_key LIKE 'stagekitwp_members_rsvp_%'"
        );

        foreach ((array) $rows as $row) {
            if (!preg_match('/^stagekitwp_members_rsvp_(\d+)$/', $row->meta_key, $m)) {
                continue;
            }
            $event_id = (int) $m[1];
            $user_id  = (int) $row->user_id;

            // Skip if already migrated.
            if (self::get_status($event_id, $user_id)) {
                continue;
            }
            // Legacy meta only recorded a truthy "going" RSVP.
            self::set_status($event_id, $user_id, 'going');
        }

        update_option('stagekitwp_members_rsvp_meta_migrated', 1);
    }
}
