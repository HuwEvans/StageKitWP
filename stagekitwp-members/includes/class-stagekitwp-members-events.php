<?php

if (!defined('ABSPATH')) {
    exit;
}

class STAGEKITWP_MEMBERS_Events {

    const META_VISIBILITY = '_stagekitwp_members_event_visibility';

    public static function init() {
        add_action('init', [__CLASS__, 'register']);

        // ✅ Admin enhancements
        add_action('add_meta_boxes', [__CLASS__, 'add_meta_boxes']);
        add_action('save_post', [__CLASS__, 'save_event_meta']);

  		add_action('add_meta_boxes', [__CLASS__, 'add_attendees_metabox']);
  		add_action('add_meta_boxes', [__CLASS__, 'add_availability_metabox']);
  
        // ✅ Shortcodes
        add_shortcode('stagekitwp_members_rsvp', [__CLASS__, 'render_rsvp']);

        // ✅ Attendance check-in (admin-post)
        add_action('admin_post_stagekitwp_members_checkin', [__CLASS__, 'handle_checkin']);

        // Frontend visibility enforcement for private events.
        add_action('pre_get_posts', [__CLASS__, 'filter_frontend_event_queries']);
        add_action('template_redirect', [__CLASS__, 'enforce_single_event_visibility']);
    }

    private static function can_view_private_events() {
        return is_user_logged_in() && current_user_can(STAGEKITWP_MEMBERS_Roles::CAP_RSVP_EVENTS);
    }

    public static function is_public_event($event_id) {
        $visibility = get_post_meta((int) $event_id, self::META_VISIBILITY, true);
        return $visibility === 'public';
    }

    /**
     * Register Event CPT
     */
    public static function register() {

        register_post_type('stagekitwp_event', [
            'label' => 'Events',
            'public' => true,
            'menu_icon' => 'dashicons-calendar',
            'show_in_menu' => false,
            'supports' => ['title', 'editor'],
            'has_archive' => true,
            'show_in_rest' => true,
            // Map to the plugin's own capability so Producers/Executives can
            // manage events. map_meta_cap resolves the primitive caps below
            // from these; we grant them to the roles in register_events_caps().
            'capability_type' => ['stagekitwp_event', 'stagekitwp_events'],
            'map_meta_cap' => true,
        ]);
    }

    /**
     * Add Meta Boxes
     */
    public static function add_meta_boxes() {
        add_meta_box(
            'stagekitwp_members_event_details',
            'Event Details',
            [__CLASS__, 'render_meta_box'],
            'stagekitwp_event',
            'normal',
            'default'
        );
    }

    /**
     * Render Meta Fields
     */
    public static function render_meta_box($post) {

        wp_nonce_field('stagekitwp_members_event_save', 'stagekitwp_members_event_nonce');

        $date  = get_post_meta($post->ID, '_stagekitwp_members_event_date', true);
        $start = get_post_meta($post->ID, '_stagekitwp_members_start_time', true);
        $end   = get_post_meta($post->ID, '_stagekitwp_members_end_time', true);
        $visibility = get_post_meta($post->ID, self::META_VISIBILITY, true);
        if (!in_array($visibility, ['public', 'private'], true)) {
            $visibility = 'private';
        }

        ?>

        <p>
            <label><strong>Date:</strong></label><br>
            <input type="date" name="stagekitwp_members_event_date" value="<?php echo esc_attr($date); ?>">
        </p>

        <p>
            <label><strong>Start Time:</strong></label><br>
            <input type="time" name="stagekitwp_members_start_time" value="<?php echo esc_attr($start); ?>">
        </p>

        <p>
            <label><strong>End Time:</strong></label><br>
            <input type="time" name="stagekitwp_members_end_time" value="<?php echo esc_attr($end); ?>">
        </p>

        <p>
            <label><strong>Visibility:</strong></label><br>
            <select name="stagekitwp_members_event_visibility">
                <option value="private" <?php selected($visibility, 'private'); ?>>Private (members only)</option>
                <option value="public" <?php selected($visibility, 'public'); ?>>Public (visible when logged out)</option>
            </select>
        </p>

        <?php
    }

    /**
     * Save Meta Fields
     */
    public static function save_event_meta($post_id) {

        // ✅ Verify nonce
        if (
            !isset($_POST['stagekitwp_members_event_nonce']) ||
            !wp_verify_nonce($_POST['stagekitwp_members_event_nonce'], 'stagekitwp_members_event_save')
        ) {
            return;
        }

        // ✅ Prevent autosave overwrite
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // ✅ Permission check
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // ✅ Save fields
        if (isset($_POST['stagekitwp_members_event_date'])) {
            update_post_meta($post_id, '_stagekitwp_members_event_date', sanitize_text_field($_POST['stagekitwp_members_event_date']));
        }

        if (isset($_POST['stagekitwp_members_start_time'])) {
            update_post_meta($post_id, '_stagekitwp_members_start_time', sanitize_text_field($_POST['stagekitwp_members_start_time']));
        }

        if (isset($_POST['stagekitwp_members_end_time'])) {
            update_post_meta($post_id, '_stagekitwp_members_end_time', sanitize_text_field($_POST['stagekitwp_members_end_time']));
        }

        $visibility = isset($_POST['stagekitwp_members_event_visibility']) ? sanitize_key($_POST['stagekitwp_members_event_visibility']) : 'private';
        if (!in_array($visibility, ['public', 'private'], true)) {
            $visibility = 'private';
        }
        update_post_meta($post_id, self::META_VISIBILITY, $visibility);
    }

    /**
     * Hide private events from non-members on frontend stagekitwp_event archives/queries.
     */
    public static function filter_frontend_event_queries($query) {
        if (is_admin() || !($query instanceof WP_Query)) {
            return;
        }
        if (self::can_view_private_events()) {
            return;
        }

        $post_type = $query->get('post_type');
        $is_stagekitwp_event_query = false;

        // Only scope this visibility rule to queries that request EXCLUSIVELY
        // stagekitwp_event. A multi-CPT query (e.g. Import/Export's bulk export,
        // which queries all Core post types together) must never get this
        // meta_query applied, since the INNER JOIN it adds would silently
        // discard every post of every OTHER post type that lacks this meta key.
        if ($query->is_post_type_archive('stagekitwp_event')) {
            $is_stagekitwp_event_query = true;
        } elseif ($post_type === 'stagekitwp_event') {
            $is_stagekitwp_event_query = true;
        } elseif (is_array($post_type) && [ 'stagekitwp_event' ] === array_values(array_unique($post_type))) {
            $is_stagekitwp_event_query = true;
        }

        if (!$is_stagekitwp_event_query) {
            return;
        }

        $meta_query = $query->get('meta_query');
        if (!is_array($meta_query)) {
            $meta_query = [];
        }
        $meta_query[] = [
            'key'     => self::META_VISIBILITY,
            'value'   => 'public',
            'compare' => '=',
        ];
        $query->set('meta_query', $meta_query);
    }

    /**
     * Block direct access to private event single pages for non-members.
     */
    public static function enforce_single_event_visibility() {
        if (!is_singular('stagekitwp_event') || self::can_view_private_events()) {
            return;
        }

        $event_id = get_queried_object_id();
        if ($event_id && !self::is_public_event($event_id)) {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            nocache_headers();
            include get_404_template();
            exit;
        }
    }

    /**
     * RSVP Save
     */
    public static function rsvp($event_id, $user_id, $status = 'going') {
        // Write through the new RSVP table (source of truth) and keep the
        // legacy meta in sync as a fallback per the migration choice.
        if (class_exists('STAGEKITWP_MEMBERS_RSVP')) {
            STAGEKITWP_MEMBERS_RSVP::set_status($event_id, $user_id, $status);
        }
        update_user_meta($user_id, 'stagekitwp_members_rsvp_' . $event_id, 1);
    }

    /**
     * Check RSVP. Prefers the table, falls back to legacy meta.
     */
    public static function has_rsvp($event_id, $user_id) {
        if (class_exists('STAGEKITWP_MEMBERS_RSVP')) {
            $status = STAGEKITWP_MEMBERS_RSVP::get_status($event_id, $user_id);
            if ($status !== '') {
                return $status !== 'declined';
            }
        }
        return get_user_meta($user_id, 'stagekitwp_members_rsvp_' . $event_id, true);
    }

    /**
     * RSVP UI Shortcode
     */
    public static function render_rsvp() {

        if (!is_user_logged_in()) {
            return '<p>Please log in to RSVP.</p>';
        }

        if (get_post_type() !== 'stagekitwp_event') {
            return '';
        }

        $event_id = get_the_ID();
        $user_id  = get_current_user_id();

        if (
            isset($_POST['stagekitwp_members_rsvp_submit']) &&
            isset($_POST['stagekitwp_members_rsvp_nonce']) &&
            wp_verify_nonce($_POST['stagekitwp_members_rsvp_nonce'], 'stagekitwp_members_rsvp_action')
        ) {
            self::rsvp($event_id, $user_id);
            echo '<p>✅ RSVP confirmed!</p>';
        }

        if (self::has_rsvp($event_id, $user_id)) {
            return '<p>You are attending this event.</p>';
        }

        ob_start();
        ?>

        <form method="post">
            <?php wp_nonce_field('stagekitwp_members_rsvp_action', 'stagekitwp_members_rsvp_nonce'); ?>
            <button type="submit" name="stagekitwp_members_rsvp_submit">
                RSVP to this event
            </button>
        </form>

        <?php

        return ob_get_clean();
    }
	public static function add_attendees_metabox() {
	    add_meta_box(
	        'stagekitwp_members_event_attendees',
	        'Event Attendees',
	        [__CLASS__, 'render_attendees'],
	        'stagekitwp_event',
	        'side',
	        'default'
	    );
	}
	public static function render_attendees($post) {

	    $event_id = (int) $post->ID;

	    if (!class_exists('STAGEKITWP_MEMBERS_RSVP')) {
	        echo '<p>RSVP module unavailable.</p>';
	        return;
	    }

	    $rows   = STAGEKITWP_MEMBERS_RSVP::get_for_event($event_id);
	    $counts = STAGEKITWP_MEMBERS_RSVP::counts($event_id);

	    echo '<p><strong>' . (int) $counts['going'] . '</strong> going, <strong>'
	        . (int) $counts['maybe'] . '</strong> maybe, <strong>'
	        . (int) $counts['attended'] . '</strong> attended</p>';

	    if (empty($rows)) {
	        echo '<p>No RSVPs yet.</p>';
	        return;
	    }

	    $can_checkin = current_user_can(STAGEKITWP_MEMBERS_Roles::CAP_MANAGE_EVENTS);

	    echo '<table style="width:100%;font-size:12px;"><tbody>';
	    foreach ($rows as $r) {
	        $u = get_userdata($r->user_id);
	        if (!$u) { continue; }

	        echo '<tr><td>' . esc_html($u->display_name)
	            . ' <span style="color:#888;">(' . esc_html($r->status) . ')</span></td>';
	        echo '<td style="text-align:right;">';

	        if ($can_checkin) {
	            $toggle = $r->attended ? 0 : 1;
	            $url = wp_nonce_url(
	                admin_url('admin-post.php?action=stagekitwp_members_checkin&event=' . $event_id
	                    . '&user=' . (int) $r->user_id . '&attended=' . $toggle),
	                'stagekitwp_members_checkin_' . $event_id . '_' . (int) $r->user_id
	            );
	            $label = $r->attended ? '✓ Attended' : 'Mark attended';
	            $style = $r->attended ? 'color:#1f7a3d;font-weight:600;' : '';
	            echo '<a href="' . esc_url($url) . '" style="' . $style . '">' . esc_html($label) . '</a>';
	        } else {
	            echo $r->attended ? '✓' : '—';
	        }

	        echo '</td></tr>';
	    }
	    echo '</tbody></table>';
	}

	/**
	 * Toggle a member's attendance for an event (Producers/Executives).
	 */
	public static function handle_checkin() {

	    $event_id = isset($_GET['event']) ? (int) $_GET['event'] : 0;
	    $user_id  = isset($_GET['user']) ? (int) $_GET['user'] : 0;
	    $attended = !empty($_GET['attended']);

	    check_admin_referer('stagekitwp_members_checkin_' . $event_id . '_' . $user_id);

	    if (!current_user_can(STAGEKITWP_MEMBERS_Roles::CAP_MANAGE_EVENTS)) {
	        wp_die('Access denied.');
	    }
	    if (get_post_type($event_id) !== 'stagekitwp_event') {
	        wp_die('Invalid event.');
	    }

	    STAGEKITWP_MEMBERS_RSVP::mark_attended($event_id, $user_id, $attended);

	    wp_safe_redirect(get_edit_post_link($event_id, 'redirect'));
	    exit;
	}

	/**
	 * Availability grid metabox (Phase E3): shows each RSVP'd member's
	 * availability status against the event date.
	 */
	public static function add_availability_metabox() {
	    add_meta_box(
	        'stagekitwp_members_event_availability',
	        'Cast Availability',
	        [__CLASS__, 'render_availability_grid'],
	        'stagekitwp_event',
	        'normal',
	        'default'
	    );
	}

	public static function render_availability_grid($post) {

	    if (!class_exists('STAGEKITWP_MEMBERS_Availability') || !class_exists('STAGEKITWP_MEMBERS_RSVP')) {
	        echo '<p>Availability module unavailable.</p>';
	        return;
	    }

	    $event_id = (int) $post->ID;
	    $date     = get_post_meta($event_id, '_stagekitwp_members_event_date', true);

	    if (!$date) {
	        echo '<p>Set an event date to see cast availability.</p>';
	        return;
	    }

	    $rows = STAGEKITWP_MEMBERS_RSVP::get_for_event($event_id);
	    if (empty($rows)) {
	        echo '<p>No RSVPs yet — availability appears once members respond.</p>';
	        return;
	    }

	    $labels = [
	        'available'   => ['✓ Available',   '#1f7a3d', '#e7f6ec'],
	        'unavailable' => ['✕ Unavailable', '#9a1f1f', '#f6e7e7'],
	        'tentative'   => ['~ Tentative',   '#9a6b12', '#fdf3e0'],
	        ''            => ['— Not set',     '#666',    '#f1f1f1'],
	    ];

	    echo '<p style="color:#666;">Availability on <strong>' . esc_html($date) . '</strong>:</p>';
	    echo '<table class="widefat striped"><thead><tr><th>Member</th><th>RSVP</th><th>Availability</th><th>Note</th></tr></thead><tbody>';

	    foreach ($rows as $r) {
	        $u = get_userdata($r->user_id);
	        if (!$u) { continue; }

	        $status = STAGEKITWP_MEMBERS_Availability::status_on($r->user_id, $date);
	        [$text, $fg, $bg] = $labels[$status] ?? $labels[''];

	        // Find a matching note for that date, if any.
	        $note = '';
	        foreach (STAGEKITWP_MEMBERS_Availability::get_for_user($r->user_id) as $range) {
	            if ($range->start_date <= $date && $range->end_date >= $date && $range->note) {
	                $note = $range->note;
	                break;
	            }
	        }

	        echo '<tr>';
	        echo '<td>' . esc_html($u->display_name) . '</td>';
	        echo '<td>' . esc_html($r->status) . '</td>';
	        echo '<td><span style="display:inline-block;padding:2px 8px;border-radius:999px;font-size:12px;font-weight:600;color:' . esc_attr($fg) . ';background:' . esc_attr($bg) . ';">' . esc_html($text) . '</span></td>';
	        echo '<td style="color:#777;">' . esc_html($note) . '</td>';
	        echo '</tr>';
	    }

	    echo '</tbody></table>';
	}
}