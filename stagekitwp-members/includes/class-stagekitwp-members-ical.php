<?php
/**
 * iCal (.ics) calendar feed (Phase G).
 *
 * Endpoints (query var `stagekitwp_members_ical`):
 *   /?stagekitwp_members_ical=all                     All published events (public feed).
 *   /events.ics                          Pretty permalink for the above.
 *   /?stagekitwp_members_ical=me&token=<user-token>   A member's RSVP'd events only.
 *
 * The per-member token is a stable secret derived from the user and the site
 * auth salt, so calendar apps (Google/Apple) can poll the feed without a
 * login session. Output is RFC 5545: CRLF line endings, escaped text.
 */

if (!defined('ABSPATH')) {
    exit;
}

class STAGEKITWP_MEMBERS_ICal {

    public static function init() {
        add_action('init', [__CLASS__, 'add_rewrite']);
        add_filter('query_vars', [__CLASS__, 'add_query_var']);
        add_action('template_redirect', [__CLASS__, 'maybe_output']);

        // Don't let WordPress append a trailing slash to /events.ics.
        add_filter('redirect_canonical', [__CLASS__, 'skip_canonical'], 10, 2);
    }

    public static function add_rewrite() {
        add_rewrite_rule('^events\.ics$', 'index.php?stagekitwp_members_ical=all', 'top');
    }

    /**
     * Prevent the canonical trailing-slash redirect for the .ics feed.
     */
    public static function skip_canonical($redirect_url, $requested_url) {
        if (get_query_var('stagekitwp_members_ical') || strpos((string) $requested_url, '/events.ics') !== false) {
            return false;
        }
        return $redirect_url;
    }

    public static function add_query_var($vars) {
        $vars[] = 'stagekitwp_members_ical';
        return $vars;
    }

    /**
     * A stable per-user feed token. Not reversible; safe to embed in a URL.
     */
    public static function user_token($user_id) {
        return substr(wp_hash('stagekitwp_members_ical|' . (int) $user_id, 'auth'), 0, 20);
    }

    /**
     * Build the personal feed URL for a member.
     */
    public static function personal_url($user_id) {
        return add_query_arg(
            ['stagekitwp_members_ical' => 'me', 'uid' => (int) $user_id, 'token' => self::user_token($user_id)],
            home_url('/')
        );
    }

    /**
     * The public all-events feed URL (pretty if permalinks allow).
     */
    public static function public_url() {
        return home_url('/events.ics');
    }

    public static function maybe_output() {

        $mode = get_query_var('stagekitwp_members_ical');
        if (!$mode) {
            return;
        }

        if ($mode === 'me') {
            $uid   = isset($_GET['uid']) ? (int) $_GET['uid'] : 0;
            $token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';

            // Constant-time compare against the expected per-user token.
            if (!$uid || !hash_equals(self::user_token($uid), $token)) {
                status_header(403);
                header('Content-Type: text/plain; charset=utf-8');
                echo 'Invalid or missing calendar token.';
                exit;
            }

            $user   = get_userdata($uid);
            $events = self::get_events('me', $uid);
            self::output_calendar($events, ($user ? $user->display_name . ' — ' : '') . 'My Events');
        }

        // Default: all published events.
        $events = self::get_events('all');
        self::output_calendar($events, get_bloginfo('name') . ' Events');
    }

    /**
     * Collect event post objects for a given feed mode.
     */
    private static function get_events($mode, $user_id = 0) {

        if ($mode === 'me' && $user_id && class_exists('STAGEKITWP_MEMBERS_RSVP')) {
            $rows = STAGEKITWP_MEMBERS_RSVP::get_for_user($user_id);
            $ids  = [];
            foreach ($rows as $r) {
                // Only include events the member is going to / maybe.
                if ($r->status !== 'declined') {
                    $ids[] = (int) $r->event_id;
                }
            }
            if (empty($ids)) {
                return [];
            }
            return get_posts([
                'post_type'   => 'stagekitwp_event',
                'post_status' => 'publish',
                'numberposts' => -1,
                'include'     => $ids,
            ]);
        }

        return get_posts([
            'post_type'   => 'stagekitwp_event',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby'     => 'meta_value',
            'meta_key'    => '_stagekitwp_members_event_date',
            'meta_query'  => [
                [
                    'key'     => '_stagekitwp_members_event_visibility',
                    'value'   => 'public',
                    'compare' => '=',
                ],
            ],
            'order'       => 'ASC',
        ]);
    }

    /**
     * Stream the VCALENDAR document and exit.
     */
    private static function output_calendar($events, $name) {

        $lines   = [];
        $lines[] = 'BEGIN:VCALENDAR';
        $lines[] = 'VERSION:2.0';
        $lines[] = 'PRODID:-//TM Members Area//Events//EN';
        $lines[] = 'CALSCALE:GREGORIAN';
        $lines[] = 'METHOD:PUBLISH';
        $lines[] = 'X-WR-CALNAME:' . self::esc($name);

        $stamp = gmdate('Ymd\THis\Z');

        foreach ($events as $event) {
            $date = get_post_meta($event->ID, '_stagekitwp_members_event_date', true);
            if (!$date || !strtotime($date)) {
                continue; // undated events can't go on a calendar
            }
            $start = get_post_meta($event->ID, '_stagekitwp_members_start_time', true);
            $end   = get_post_meta($event->ID, '_stagekitwp_members_end_time', true);

            $day = date('Ymd', strtotime($date));

            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:stagekitwp-event-' . $event->ID . '@' . parse_url(home_url(), PHP_URL_HOST);
            $lines[] = 'DTSTAMP:' . $stamp;

            if ($start && preg_match('/^\d{1,2}:\d{2}/', $start)) {
                // Timed event (local/floating time — no TZID, treated as local).
                $s = date('His', strtotime($date . ' ' . $start));
                $lines[] = 'DTSTART:' . $day . 'T' . $s;
                if ($end && preg_match('/^\d{1,2}:\d{2}/', $end)) {
                    $e = date('His', strtotime($date . ' ' . $end));
                    $lines[] = 'DTEND:' . $day . 'T' . $e;
                }
            } else {
                // All-day event: DTEND is exclusive (next day).
                $lines[] = 'DTSTART;VALUE=DATE:' . $day;
                $lines[] = 'DTEND;VALUE=DATE:' . date('Ymd', strtotime($date . ' +1 day'));
            }

            $lines[] = 'SUMMARY:' . self::esc($event->post_title);

            $raw  = $event->post_excerpt ?: $event->post_content;
            $raw  = strip_shortcodes($raw);
            $desc = trim(wp_strip_all_tags($raw));
            // Collapse runs of whitespace/newlines into single spaces.
            $desc = trim(preg_replace('/\s+/', ' ', $desc));
            if ($desc !== '') {
                $lines[] = 'DESCRIPTION:' . self::esc($desc);
            }

            $lines[] = 'URL:' . self::esc(get_permalink($event->ID));
            $lines[] = 'END:VEVENT';
        }

        $lines[] = 'END:VCALENDAR';

        // Fold long lines at 75 octets per RFC 5545, then join with CRLF.
        $folded = array_map([__CLASS__, 'fold'], $lines);
        $body   = implode("\r\n", $folded) . "\r\n";

        nocache_headers();
        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="events.ics"');
        echo $body;
        exit;
    }

    /**
     * Escape a value for an iCal text field (RFC 5545 section 3.3.11).
     */
    private static function esc($text) {
        $text = (string) $text;
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace(["\r\n", "\n", "\r"], '\\n', $text);
        $text = str_replace(',', '\\,', $text);
        $text = str_replace(';', '\\;', $text);
        return $text;
    }

    /**
     * Fold a content line to 75 octets with CRLF + space continuation.
     */
    private static function fold($line) {
        if (strlen($line) <= 75) {
            return $line;
        }
        $out = '';
        while (strlen($line) > 75) {
            $out .= substr($line, 0, 75) . "\r\n ";
            $line = substr($line, 75);
        }
        return $out . $line;
    }
}
