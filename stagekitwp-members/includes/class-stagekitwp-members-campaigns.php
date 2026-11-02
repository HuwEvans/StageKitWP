<?php
/**
 * Recipient groups, campaign filters and email preference links.
 */

if (!defined('ABSPATH')) {
    exit;
}

class STAGEKITWP_MEMBERS_Campaigns {

    const GROUPS_OPTION = 'stagekitwp_members_email_groups';
    const ARCHIVED_GROUPS_OPTION = 'stagekitwp_members_email_groups_archive';
    const MAX_RECIPIENTS = 500;

    public static function init() {
        add_action('template_redirect', [__CLASS__, 'maybe_render_unsubscribe']);
        add_action('admin_post_stagekitwp_members_unsubscribe', [__CLASS__, 'handle_unsubscribe']);
        add_action('admin_post_nopriv_stagekitwp_members_unsubscribe', [__CLASS__, 'handle_unsubscribe']);
    }

    public static function get_filters($source) {
        $get = static function ($key, $default = '') use ($source) {
            return isset($source[$key]) && is_scalar($source[$key])
                ? sanitize_text_field(wp_unslash((string) $source[$key]))
                : $default;
        };

        $roles = wp_roles()->roles;
        $role = sanitize_key($get('stagekitwp_role'));
        if ($role !== '' && !isset($roles[$role])) {
            $role = '';
        }

        $availability_status = $get('stagekitwp_availability_status');
        if (!in_array($availability_status, ['', 'available', 'unavailable', 'tentative'], true)) {
            $availability_status = '';
        }

        $rsvp_status = $get('stagekitwp_rsvp_status');
        if (!in_array($rsvp_status, ['', 'going', 'maybe', 'declined', 'attended'], true)) {
            $rsvp_status = '';
        }

        $volunteer_status = $get('stagekitwp_volunteer_status');
        if (!in_array($volunteer_status, ['', 'confirmed', 'waitlist', 'cancelled'], true)) {
            $volunteer_status = '';
        }

        $availability_date = $get('stagekitwp_availability_date');
        if ($availability_date && !self::valid_date($availability_date)) {
            $availability_date = '';
        }

        return [
            'name' => $get('stagekitwp_name'),
            'interest' => absint($get('stagekitwp_interest', '0')),
            'role' => $role,
            'availability_date' => $availability_date,
            'availability_status' => $availability_status,
            'rsvp_event_id' => absint($get('stagekitwp_rsvp_event_id', '0')),
            'rsvp_status' => $rsvp_status,
            'volunteer_event_id' => absint($get('stagekitwp_volunteer_event_id', '0')),
            'volunteer_status' => $volunteer_status,
        ];
    }

    /**
     * Query recipients with the same filters used for previews and sends.
     */
    public static function query_users($filters, $limit = self::MAX_RECIPIENTS) {
        global $wpdb;

        $filters = self::normalize_filters($filters);
        $include = null;
        $constrained = false;

        if ($filters['interest']) {
            $constrained = true;
            $ids = get_objects_in_term($filters['interest'], 'stagekitwp_interest');
            if (is_wp_error($ids) || !$ids) {
                return [];
            }
            $include = array_map('intval', $ids);
        }

        $id_filters = [];
        if ($filters['availability_date'] && $filters['availability_status']) {
            $constrained = true;
            $table = $wpdb->prefix . 'stagekitwp_members_availability';
            $rows = $wpdb->get_results($wpdb->prepare(
                "SELECT user_id, status FROM {$table} WHERE start_date <= %s AND end_date >= %s",
                $filters['availability_date'],
                $filters['availability_date']
            ));
            $priorities = ['available' => 1, 'tentative' => 2, 'unavailable' => 3];
            $effective = [];
            foreach ((array) $rows as $row) {
                if (!isset($priorities[$row->status])) {
                    continue;
                }
                $id = (int) $row->user_id;
                if (!isset($effective[$id]) || $priorities[$row->status] > $priorities[$effective[$id]]) {
                    $effective[$id] = $row->status;
                }
            }
            $availability_ids = [];
            foreach ($effective as $id => $status) {
                if ($status === $filters['availability_status']) {
                    $availability_ids[] = (int) $id;
                }
            }
            $id_filters[] = $availability_ids;
        }

        if ($filters['rsvp_event_id'] && $filters['rsvp_status']) {
            $constrained = true;
            $table = $wpdb->prefix . 'stagekitwp_members_rsvps';
            if ($filters['rsvp_status'] === 'attended') {
                $ids = $wpdb->get_col($wpdb->prepare(
                    "SELECT user_id FROM {$table} WHERE event_id = %d AND attended = 1",
                    $filters['rsvp_event_id']
                ));
            } else {
                $ids = $wpdb->get_col($wpdb->prepare(
                    "SELECT user_id FROM {$table} WHERE event_id = %d AND status = %s",
                    $filters['rsvp_event_id'],
                    $filters['rsvp_status']
                ));
            }
            $id_filters[] = array_map('intval', (array) $ids);
        }

        if ($filters['volunteer_event_id'] && $filters['volunteer_status']) {
            $constrained = true;
            $table = $wpdb->prefix . 'stagekitwp_members_volunteers';
            $ids = $wpdb->get_col($wpdb->prepare(
                "SELECT DISTINCT user_id FROM {$table} WHERE event_id = %d AND status = %s",
                $filters['volunteer_event_id'],
                $filters['volunteer_status']
            ));
            $id_filters[] = array_map('intval', (array) $ids);
        }

        foreach ($id_filters as $ids) {
            $constrained = true;
            if (!$ids) {
                return [];
            }
            $include = $include === null ? $ids : array_values(array_intersect($include, $ids));
            if (!$include) {
                return [];
            }
        }

        $args = [
            'number' => min(self::MAX_RECIPIENTS, max(1, (int) $limit)),
            'orderby' => 'display_name',
            'order' => 'ASC',
        ];
        if ($filters['role']) {
            $args['role'] = $filters['role'];
        }
        if ($filters['name'] !== '') {
            $args['search'] = '*' . $filters['name'] . '*';
            $args['search_columns'] = ['display_name', 'user_login', 'user_email', 'user_nicename'];
        }
        if ($constrained) {
            $args['include'] = $include ?: [0];
        } elseif ($include !== null) {
            $args['include'] = $include;
        }

        return get_users($args);
    }

    public static function normalize_filters($filters) {
        $source = [];
        foreach ((array) $filters as $key => $value) {
            $source[$key] = $value;
        }

        // Saved definitions use short stable keys; dashboard submissions use
        // prefixed field names. Convert either form to the same sanitized shape.
        $aliases = [
            'stagekitwp_name' => 'name',
            'stagekitwp_interest' => 'interest',
            'stagekitwp_role' => 'role',
            'stagekitwp_availability_date' => 'availability_date',
            'stagekitwp_availability_status' => 'availability_status',
            'stagekitwp_rsvp_event_id' => 'rsvp_event_id',
            'stagekitwp_rsvp_status' => 'rsvp_status',
            'stagekitwp_volunteer_event_id' => 'volunteer_event_id',
            'stagekitwp_volunteer_status' => 'volunteer_status',
        ];
        foreach ($aliases as $external => $internal) {
            if (isset($source[$external]) && !isset($source[$internal])) {
                $source[$internal] = $source[$external];
            }
        }

        $roles = wp_roles()->roles;
        $role = sanitize_key($source['role'] ?? '');
        if ($role !== '' && !isset($roles[$role])) {
            $role = '';
        }
        $statuses = [
            'availability_status' => ['', 'available', 'unavailable', 'tentative'],
            'rsvp_status' => ['', 'going', 'maybe', 'declined', 'attended'],
            'volunteer_status' => ['', 'confirmed', 'waitlist', 'cancelled'],
        ];
        $clean = [
            'name' => sanitize_text_field((string) ($source['name'] ?? '')),
            'interest' => absint($source['interest'] ?? 0),
            'role' => $role,
            'availability_date' => '',
            'availability_status' => '',
            'rsvp_event_id' => absint($source['rsvp_event_id'] ?? 0),
            'rsvp_status' => '',
            'volunteer_event_id' => absint($source['volunteer_event_id'] ?? 0),
            'volunteer_status' => '',
        ];
        foreach (['availability_status', 'rsvp_status', 'volunteer_status'] as $key) {
            $value = sanitize_key($source[$key] ?? '');
            $clean[$key] = in_array($value, $statuses[$key], true) ? $value : '';
        }
        $date = sanitize_text_field((string) ($source['availability_date'] ?? ''));
        if (self::valid_date($date)) {
            $clean['availability_date'] = $date;
        }
        return $clean;
    }

    private static function valid_date($date) {
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $matches)) {
            return false;
        }
        return checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1]);
    }

    public static function get_groups() {
        $groups = get_option(self::GROUPS_OPTION, []);
        return is_array($groups) ? $groups : [];
    }

    public static function get_all_groups() {
        return array_merge(self::get_groups(), self::get_archived_groups());
    }

    public static function get_archived_groups() {
        $groups = get_option(self::ARCHIVED_GROUPS_OPTION, []);
        return is_array($groups) ? $groups : [];
    }

    public static function save_group($name, $filters) {
        $groups = self::get_groups();
        $id = wp_generate_uuid4();
        $groups[$id] = [
            'id' => $id,
            'name' => sanitize_text_field($name),
            'filters' => self::normalize_filters($filters),
            'created_at' => current_time('mysql'),
        ];
        update_option(self::GROUPS_OPTION, $groups, false);
        return $id;
    }

    public static function delete_group($id) {
        $groups = self::get_groups();
        if (!isset($groups[$id])) {
            return false;
        }
        $archived = self::get_archived_groups();
        $groups[$id]['deleted_at'] = current_time('mysql');
        $archived[$id] = $groups[$id];
        unset($groups[$id]);
        update_option(self::GROUPS_OPTION, $groups, false);
        update_option(self::ARCHIVED_GROUPS_OPTION, $archived, false);
        return true;
    }

    public static function get_group($id) {
        $groups = self::get_groups();
        return isset($groups[$id]) ? $groups[$id] : null;
    }

    public static function get_group_history($id) {
        $groups = self::get_all_groups();
        return isset($groups[$id]) ? $groups[$id] : null;
    }

    public static function event_options() {
        return get_posts([
            'post_type' => 'stagekitwp_event',
            'post_status' => 'publish',
            'numberposts' => 200,
            'orderby' => 'title',
            'order' => 'ASC',
        ]);
    }

    public static function position_options() {
        global $wpdb;
        $table = $wpdb->prefix . 'stagekitwp_members_positions';
        $positions = $wpdb->get_results(
            "SELECT id, event_id, title FROM {$table} WHERE event_id > 0 AND is_template = 0 AND active = 1 ORDER BY title ASC"
        );
        return array_values(array_filter((array) $positions, function ($position) {
            return get_post_status((int) $position->event_id) === 'publish';
        }));
    }

    public static function campaign_context($event_id, $position_id) {
        $context = [];
        $event_id = absint($event_id);
        $position_id = absint($position_id);
        if (!$event_id || get_post_type($event_id) !== 'stagekitwp_event' || get_post_status($event_id) !== 'publish') {
            return $context;
        }

        $context['event'] = get_the_title($event_id);
        $event_date = (string) get_post_meta($event_id, '_stagekitwp_members_event_date', true);
        $context['date'] = $event_date ? date_i18n(get_option('date_format'), strtotime($event_date)) : '';

        if ($position_id) {
            global $wpdb;
            $table = $wpdb->prefix . 'stagekitwp_members_positions';
            $context['position'] = (string) $wpdb->get_var($wpdb->prepare(
                "SELECT title FROM {$table} WHERE id = %d AND event_id = %d AND active = 1 AND is_template = 0",
                $position_id,
                $event_id
            ));
        }
        return $context;
    }

    public static function unsubscribe_url($user_id) {
        $user_id = absint($user_id);
        $signature = hash_hmac('sha256', (string) $user_id, wp_salt('auth'));
        return add_query_arg('stagekitwp_members_unsubscribe', $user_id . '.' . $signature, home_url('/'));
    }

    private static function valid_unsubscribe_token($token) {
        if (!preg_match('/^(\d+)\.([a-f0-9]{64})$/', (string) $token, $matches)) {
            return 0;
        }
        $user_id = absint($matches[1]);
        $expected = hash_hmac('sha256', (string) $user_id, wp_salt('auth'));
        return hash_equals($expected, $matches[2]) && get_userdata($user_id) ? $user_id : 0;
    }

    public static function maybe_render_unsubscribe() {
        if (!isset($_GET['stagekitwp_members_unsubscribe'])) {
            return;
        }
        $raw_token = wp_unslash($_GET['stagekitwp_members_unsubscribe']);
        $token = is_scalar($raw_token) ? sanitize_text_field((string) $raw_token) : '';
        if (!self::valid_unsubscribe_token($token)) {
            self::render_unsubscribe_page('', 'This unsubscribe link is invalid.');
        }
        self::render_unsubscribe_page($token, '');
    }

    public static function handle_unsubscribe() {
        $raw_token = isset($_POST['token']) ? wp_unslash($_POST['token']) : '';
        $token = is_scalar($raw_token) ? sanitize_text_field((string) $raw_token) : '';
        $user_id = self::valid_unsubscribe_token($token);
        if (!$user_id || !isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'stagekitwp_members_unsubscribe_' . $token)) {
            self::render_unsubscribe_page('', 'This unsubscribe request could not be verified.', 403);
        }

        $prefs = STAGEKITWP_MEMBERS_Notifications::get_prefs($user_id);
        $prefs[STAGEKITWP_MEMBERS_Notifications::TYPE_ANNOUNCEMENT] = 0;
        STAGEKITWP_MEMBERS_Notifications::save_prefs($user_id, $prefs);
        self::render_unsubscribe_page('', 'You have been unsubscribed from announcement emails.');
    }

    private static function render_unsubscribe_page($token, $notice, $status = 200) {
        if ($status === 200 && $notice && strpos($notice, 'invalid') !== false) {
            $status = 400;
        }
        status_header($status);
        nocache_headers();
        echo '<!doctype html><html><head><meta charset="' . esc_attr(get_bloginfo('charset')) . '"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Email preferences</title></head><body style="font-family:sans-serif;max-width:640px;margin:3rem auto;padding:0 1rem">';
        echo '<h1>Email preferences</h1>';
        if ($notice !== '') {
            echo '<p>' . esc_html($notice) . '</p>';
        } elseif ($token !== '') {
            echo '<p>Unsubscribe from announcement and bulk email messages sent to your member account?</p>';
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
            echo '<input type="hidden" name="action" value="stagekitwp_members_unsubscribe">';
            echo '<input type="hidden" name="token" value="' . esc_attr($token) . '">';
            wp_nonce_field('stagekitwp_members_unsubscribe_' . $token);
            echo '<button type="submit">Unsubscribe</button></form>';
        }
        echo '<p><a href="' . esc_url(home_url('/')) . '">Return to the site</a></p></body></html>';
        exit;
    }
}
