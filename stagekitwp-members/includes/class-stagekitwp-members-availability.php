<?php
/**
 * Member availability API (Phase E).
 *
 * Backed by {prefix}stagekitwp_members_availability. Each row is a date range with a
 * status (available / unavailable / tentative) and optional note, so
 * Producers can see who is free for an event's date.
 *
 * Portable across MySQL (production) and SQLite (Studio): plain prepared
 * statements, ISO date comparisons only.
 */

if (!defined('ABSPATH')) {
    exit;
}

class STAGEKITWP_MEMBERS_Availability {

    const STATUSES = ['available', 'unavailable', 'tentative'];

    private static function table() {
        global $wpdb;
        return $wpdb->prefix . 'stagekitwp_members_availability';
    }

    /**
     * Add an availability range for a member. Dates are Y-m-d strings.
     */
    public static function add_range($user_id, $start, $end, $status = 'unavailable', $note = '') {

        global $wpdb;

        $start = self::norm_date($start);
        $end   = self::norm_date($end);

        if (!$start || !$end) {
            return false;
        }
        // Normalise reversed ranges.
        if ($end < $start) {
            [$start, $end] = [$end, $start];
        }
        if (!in_array($status, self::STATUSES, true)) {
            $status = 'unavailable';
        }

        return (false !== $wpdb->insert(
            self::table(),
            [
                'user_id'    => (int) $user_id,
                'start_date' => $start,
                'end_date'   => $end,
                'status'     => $status,
                'note'       => $note !== '' ? mb_substr($note, 0, 255) : null,
                'created_at' => current_time('mysql'),
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s']
        ));
    }

    /**
     * Delete a range, but only if it belongs to the given user (guard).
     */
    public static function delete_range($id, $user_id) {

        global $wpdb;

        return (false !== $wpdb->delete(
            self::table(),
            ['id' => (int) $id, 'user_id' => (int) $user_id],
            ['%d', '%d']
        ));
    }

    /**
     * All ranges for a member, soonest first.
     */
    public static function get_for_user($user_id) {

        global $wpdb;
        $table = self::table();

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE user_id = %d ORDER BY start_date ASC",
                (int) $user_id
            )
        );
    }

    /**
     * A member's effective status on a specific date, or '' if unset.
     * If multiple ranges overlap, 'unavailable' wins, then 'tentative'.
     */
    public static function status_on($user_id, $date) {

        global $wpdb;
        $table = self::table();

        $date = self::norm_date($date);
        if (!$date) {
            return '';
        }

        $rows = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT status FROM {$table}
                 WHERE user_id = %d AND start_date <= %s AND end_date >= %s",
                (int) $user_id,
                $date,
                $date
            )
        );

        if (empty($rows)) {
            return '';
        }
        if (in_array('unavailable', $rows, true)) {
            return 'unavailable';
        }
        if (in_array('tentative', $rows, true)) {
            return 'tentative';
        }
        return 'available';
    }

    /**
     * Normalise a date input to Y-m-d, or '' if invalid.
     */
    private static function norm_date($value) {
        $ts = strtotime((string) $value);
        return $ts ? date('Y-m-d', $ts) : '';
    }
}
