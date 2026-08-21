<?php
/**
 * REST API (Phase H).
 *
 * Namespace: stagekitwp-ma/v1
 *
 * Read endpoints, all capability-gated. Privacy rules are strict:
 *   - Member endpoints expose ONLY directory-listed members and NEVER
 *     return email addresses or other private meta.
 *   - Event and RSVP endpoints require the relevant membership caps.
 *
 * Endpoints:
 *   GET  /members                 Directory-listed members (CAP_SEARCH_MEMBERS)
 *   GET  /members/<id>            One listed member (CAP_SEARCH_MEMBERS)
 *   GET  /events                  Published events (CAP_RSVP_EVENTS)
 *   GET  /events/<id>            One event (CAP_RSVP_EVENTS)
 *   GET  /events/<id>/rsvps      RSVP roster + counts (CAP_MANAGE_EVENTS)
 *   POST /events/<id>/rsvp       Set the current user's RSVP (CAP_RSVP_EVENTS)
 */

if (!defined('ABSPATH')) {
    exit;
}

class STAGEKITWP_MEMBERS_REST {

    const NS = 'stagekitwp-ma/v1';

    public static function init() {
        add_action('rest_api_init', [__CLASS__, 'register_routes']);
    }

    public static function register_routes() {

        register_rest_route(self::NS, '/members', [
            'methods'             => 'GET',
            'callback'            => [__CLASS__, 'get_members'],
            'permission_callback' => [__CLASS__, 'can_search_members'],
            'args'                => [
                'search'   => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
                'interest' => ['type' => 'integer', 'sanitize_callback' => 'absint'],
            ],
        ]);

        register_rest_route(self::NS, '/members/(?P<id>\d+)', [
            'methods'             => 'GET',
            'callback'            => [__CLASS__, 'get_member'],
            'permission_callback' => [__CLASS__, 'can_search_members'],
            'args'                => ['id' => ['type' => 'integer', 'sanitize_callback' => 'absint']],
        ]);

        register_rest_route(self::NS, '/events', [
            'methods'             => 'GET',
            'callback'            => [__CLASS__, 'get_events'],
            'permission_callback' => [__CLASS__, 'can_rsvp'],
            'args'                => [
                'when' => ['type' => 'string', 'enum' => ['upcoming', 'past', 'all'], 'default' => 'upcoming'],
            ],
        ]);

        register_rest_route(self::NS, '/events/(?P<id>\d+)', [
            'methods'             => 'GET',
            'callback'            => [__CLASS__, 'get_event'],
            'permission_callback' => [__CLASS__, 'can_rsvp'],
            'args'                => ['id' => ['type' => 'integer', 'sanitize_callback' => 'absint']],
        ]);

        register_rest_route(self::NS, '/events/(?P<id>\d+)/rsvps', [
            'methods'             => 'GET',
            'callback'            => [__CLASS__, 'get_event_rsvps'],
            'permission_callback' => [__CLASS__, 'can_manage_events'],
            'args'                => ['id' => ['type' => 'integer', 'sanitize_callback' => 'absint']],
        ]);

        register_rest_route(self::NS, '/events/(?P<id>\d+)/rsvp', [
            'methods'             => 'POST',
            'callback'            => [__CLASS__, 'set_event_rsvp'],
            'permission_callback' => [__CLASS__, 'can_rsvp'],
            'args'                => [
                'id'     => ['type' => 'integer', 'sanitize_callback' => 'absint'],
                'status' => ['type' => 'string', 'required' => true],
            ],
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* Permission callbacks                                               */
    /* ------------------------------------------------------------------ */

    public static function can_search_members() {
        return is_user_logged_in() && current_user_can(STAGEKITWP_MEMBERS_Roles::CAP_SEARCH_MEMBERS);
    }

    public static function can_rsvp() {
        return is_user_logged_in() && current_user_can(STAGEKITWP_MEMBERS_Roles::CAP_RSVP_EVENTS);
    }

    public static function can_manage_events() {
        return is_user_logged_in() && current_user_can(STAGEKITWP_MEMBERS_Roles::CAP_MANAGE_EVENTS);
    }

    /* ------------------------------------------------------------------ */
    /* Handlers                                                           */
    /* ------------------------------------------------------------------ */

    public static function get_members($request) {

        $search   = (string) $request->get_param('search');
        $interest = (int) $request->get_param('interest');

        // Reuse the directory query (directory_only = true) so ONLY opted-in,
        // listed members are ever returned.
        $users = STAGEKITWP_MEMBERS_Search::query_members($search, $interest, 500, true);

        $out = [];
        foreach ($users as $u) {
            $out[] = self::shape_member($u);
        }
        return rest_ensure_response($out);
    }

    public static function get_member($request) {

        $id   = (int) $request['id'];
        $user = get_userdata($id);

        // 404 for missing users AND for users who are not directory-listed
        // (do not leak their existence).
        if (!$user || !STAGEKITWP_MEMBERS_Directory::is_listed($id)) {
            return new WP_Error('stagekitwp_members_not_found', 'Member not found.', ['status' => 404]);
        }
        return rest_ensure_response(self::shape_member($user));
    }

    public static function get_events($request) {

        $when  = (string) $request->get_param('when');
        $today = current_time('Y-m-d');

        $args = [
            'post_type'   => 'stagekitwp_event',
            'post_status' => 'publish',
            'numberposts' => 200,
            'orderby'     => 'meta_value',
            'meta_key'    => '_stagekitwp_members_event_date',
            'order'       => 'ASC',
        ];

        if ($when === 'upcoming') {
            $args['meta_query'] = [['key' => '_stagekitwp_members_event_date', 'value' => $today, 'compare' => '>=', 'type' => 'DATE']];
        } elseif ($when === 'past') {
            $args['meta_query'] = [['key' => '_stagekitwp_members_event_date', 'value' => $today, 'compare' => '<', 'type' => 'DATE']];
            $args['order']      = 'DESC';
        }

        $events = get_posts($args);
        $out = [];
        foreach ($events as $e) {
            $out[] = self::shape_event($e);
        }
        return rest_ensure_response($out);
    }

    public static function get_event($request) {

        $id   = (int) $request['id'];
        $post = get_post($id);
        if (!$post || $post->post_type !== 'stagekitwp_event' || $post->post_status !== 'publish') {
            return new WP_Error('stagekitwp_members_not_found', 'Event not found.', ['status' => 404]);
        }
        return rest_ensure_response(self::shape_event($post));
    }

    public static function get_event_rsvps($request) {

        $id   = (int) $request['id'];
        $post = get_post($id);
        if (!$post || $post->post_type !== 'stagekitwp_event') {
            return new WP_Error('stagekitwp_members_not_found', 'Event not found.', ['status' => 404]);
        }
        if (!class_exists('STAGEKITWP_MEMBERS_RSVP')) {
            return rest_ensure_response(['counts' => [], 'attendees' => []]);
        }

        $rows = STAGEKITWP_MEMBERS_RSVP::get_for_event($id);
        $attendees = [];
        foreach ($rows as $r) {
            $u = get_userdata($r->user_id);
            $attendees[] = [
                'user_id'  => (int) $r->user_id,
                'name'     => $u ? $u->display_name : '(deleted user)',
                'status'   => $r->status,
                'attended' => !empty($r->attended) ? (int) $r->attended : 0,
            ];
        }
        return rest_ensure_response([
            'event_id'  => $id,
            'counts'    => STAGEKITWP_MEMBERS_RSVP::counts($id),
            'attendees' => $attendees,
        ]);
    }

    public static function set_event_rsvp($request) {

        $id     = (int) $request['id'];
        $status = sanitize_text_field($request->get_param('status'));

        $post = get_post($id);
        if (!$post || $post->post_type !== 'stagekitwp_event' || $post->post_status !== 'publish') {
            return new WP_Error('stagekitwp_members_not_found', 'Event not found.', ['status' => 404]);
        }
        if (!class_exists('STAGEKITWP_MEMBERS_RSVP') || !in_array($status, STAGEKITWP_MEMBERS_RSVP::STATUSES, true)) {
            return new WP_Error(
                'stagekitwp_members_bad_status',
                'Status must be one of: ' . implode(', ', STAGEKITWP_MEMBERS_RSVP::STATUSES),
                ['status' => 400]
            );
        }

        $uid = get_current_user_id();
        STAGEKITWP_MEMBERS_RSVP::set_status($id, $uid, $status);

        return rest_ensure_response([
            'event_id' => $id,
            'user_id'  => $uid,
            'status'   => STAGEKITWP_MEMBERS_RSVP::get_status($id, $uid),
            'counts'   => STAGEKITWP_MEMBERS_RSVP::counts($id),
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* Shapers (safe, public-facing representations)                     */
    /* ------------------------------------------------------------------ */

    private static function shape_member($user) {

        // Interest taxonomy terms (names only).
        $interests = [];
        $terms = wp_get_object_terms($user->ID, 'stagekitwp_interest');
        if (!is_wp_error($terms)) {
            foreach ($terms as $t) {
                $interests[] = $t->name;
            }
        }

        // NOTE: deliberately NO email, login, or contact meta here.
        return [
            'id'        => (int) $user->ID,
            'name'      => $user->display_name,
            'role'      => class_exists('STAGEKITWP_MEMBERS_Directory')
                ? STAGEKITWP_MEMBERS_Directory::role_label($user->ID)
                : '',
            'bio'       => (string) get_user_meta($user->ID, 'stagekitwp_members_bio', true),
            'avatar'    => get_avatar_url($user->ID, ['size' => 96]),
            'interests' => $interests,
            'profile'   => class_exists('STAGEKITWP_MEMBERS_Directory') && STAGEKITWP_MEMBERS_Directory::directory_page_id()
                ? add_query_arg('member', $user->ID, get_permalink(STAGEKITWP_MEMBERS_Directory::directory_page_id()))
                : '',
        ];
    }

    private static function shape_event($post) {

        $counts = class_exists('STAGEKITWP_MEMBERS_RSVP') ? STAGEKITWP_MEMBERS_RSVP::counts($post->ID) : [];

        // The current user's own RSVP status (if any).
        $mine = '';
        if (class_exists('STAGEKITWP_MEMBERS_RSVP') && is_user_logged_in()) {
            $mine = STAGEKITWP_MEMBERS_RSVP::get_status($post->ID, get_current_user_id());
        }

        return [
            'id'          => (int) $post->ID,
            'title'       => $post->post_title,
            'date'        => (string) get_post_meta($post->ID, '_stagekitwp_members_event_date', true),
            'start_time'  => (string) get_post_meta($post->ID, '_stagekitwp_members_start_time', true),
            'end_time'    => (string) get_post_meta($post->ID, '_stagekitwp_members_end_time', true),
            'description' => wp_strip_all_tags(strip_shortcodes($post->post_content)),
            'url'         => get_permalink($post->ID),
            'rsvp_counts' => $counts,
            'my_rsvp'     => $mine ?: null,
        ];
    }
}
