<?php
/**
 * Member-facing events UI (Phase D2).
 *
 * Shortcodes:
 *   [stagekitwp_members_events]     Upcoming/past productions with three-state RSVP buttons.
 *   [stagekitwp_members_my_rsvps]   The current member's RSVPs.
 *
 * RSVP writes go through STAGEKITWP_MEMBERS_RSVP (the table-backed source of truth).
 * Access requires login + CAP_RSVP_EVENTS. Form posts are nonce-protected.
 */

if (!defined('ABSPATH')) {
    exit;
}

class STAGEKITWP_MEMBERS_Events_Frontend {

    public static function init() {
        add_shortcode('stagekitwp_members_events', [__CLASS__, 'render_events']);
        add_shortcode('stagekitwp_members_my_rsvps', [__CLASS__, 'render_my_rsvps']);
        add_action('init', [__CLASS__, 'handle_rsvp_post']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue']);
    }

    public static function enqueue() {
        if (!is_singular()) {
            return;
        }
        $post = get_post();
        if (!$post) {
            return;
        }
        if (has_shortcode($post->post_content, 'stagekitwp_members_events')
            || has_shortcode($post->post_content, 'stagekitwp_members_my_rsvps')) {
            wp_enqueue_style(
                'stagekitwp-ma-events',
                STAGEKITWPMA_URL . 'assets/css/events.css',
                [],
                STAGEKITWPMA_VERSION
            );
        }
    }

    private static function viewer_allowed() {
        return is_user_logged_in() && current_user_can(STAGEKITWP_MEMBERS_Roles::CAP_RSVP_EVENTS);
    }

    private static function event_is_public($event_id) {
        if (class_exists('STAGEKITWP_MEMBERS_Events')) {
            return STAGEKITWP_MEMBERS_Events::is_public_event($event_id);
        }
        return get_post_meta((int) $event_id, '_stagekitwp_members_event_visibility', true) === 'public';
    }

    /**
     * Process an RSVP form submission early on `init` so we can redirect
     * back (PRG pattern) and avoid resubmits.
     */
    public static function handle_rsvp_post() {

        if (empty($_POST['stagekitwp_members_rsvp_event']) || !isset($_POST['stagekitwp_members_rsvp_status'])) {
            return;
        }
        if (!self::viewer_allowed()) {
            return;
        }

        $event_id = (int) $_POST['stagekitwp_members_rsvp_event'];
        $status   = sanitize_key($_POST['stagekitwp_members_rsvp_status']);

        if (!wp_verify_nonce($_POST['stagekitwp_members_rsvp_nonce'] ?? '', 'stagekitwp_members_rsvp_' . $event_id)) {
            return;
        }
        if (!in_array($status, STAGEKITWP_MEMBERS_RSVP::STATUSES, true)) {
            return;
        }
        if (get_post_type($event_id) !== 'stagekitwp_event') {
            return;
        }

        STAGEKITWP_MEMBERS_RSVP::set_status($event_id, get_current_user_id(), $status);
        // Keep legacy meta in sync as a fallback.
        update_user_meta(get_current_user_id(), 'stagekitwp_members_rsvp_' . $event_id, $status === 'declined' ? 0 : 1);

        // Post/Redirect/Get to avoid resubmits; flag the updated event.
        $back = remove_query_arg(['stagekitwp_rsvp_done']);
        $back = add_query_arg('stagekitwp_rsvp_done', $event_id, wp_get_referer() ?: home_url('/'));
        wp_safe_redirect($back);
        exit;
    }

/**
 * [stagekitwp_members_events]
 */
public static function render_events($atts = []) {
    $can_manage_private = self::viewer_allowed();

    $atts = shortcode_atts([
        'show'  => 'upcoming',
        'title' => __('Events', 'stagekitwp-members-area'),
        'layout' => 'default',
    ], $atts, 'stagekitwp_members_events');

    $events = get_posts([
        'post_type'   => 'stagekitwp_event',
        'numberposts' => -1,
        'post_status' => 'publish',
        'orderby'     => 'meta_value',
        'meta_key'    => '_stagekitwp_members_event_date',
        'order'       => 'ASC',
    ]);

    // Non-members/logged-out visitors only see public events.
    if (!$can_manage_private) {
        $events = array_values(array_filter($events, function($e) {
            return self::event_is_public($e->ID);
        }));
    }

    $done = isset($_GET['stagekitwp_rsvp_done']) ? (int) $_GET['stagekitwp_rsvp_done'] : 0;

    ob_start();
    echo '<div class="stagekitwp-events">';

    $title = isset($atts['title']) ? trim((string) $atts['title']) : '';
    $upcoming_heading = ($title !== '') ? $title : 'Upcoming';

    if ($done && $can_manage_private) {
        echo '<p class="stagekitwp-events-notice">✅ Your RSVP has been saved.</p>';
    }

    // Calendar subscribe links (Phase G) are hidden for public (logged-out) views.
    if (is_user_logged_in() && class_exists('STAGEKITWP_MEMBERS_ICal')) {
        $all_url = STAGEKITWP_MEMBERS_ICal::public_url();
        echo '<p class="stagekitwp-events-ical">'
            . '<a href="' . esc_url($all_url) . '">📅 Subscribe to all events</a>'
            . '</p>';

        if ($can_manage_private) {
            $me_url = STAGEKITWP_MEMBERS_ICal::personal_url(get_current_user_id());
            echo '<p class="stagekitwp-events-ical">'
                . '<a href="' . esc_url($me_url) . '">Subscribe to my events</a>'
                . '</p>';
        }
    }

    if ($atts['layout'] === 'all-details') {
        echo '<h2 class="stagekitwp-events-heading">' . esc_html($upcoming_heading) . '</h2>';
        if ($events) {
            foreach ($events as $e) { echo self::event_card_all_details($e, $can_manage_private); }
        } else {
            echo '<p class="stagekitwp-events-empty">No events.</p>';
        }
    } else {
        // Split into upcoming/past.
        $upcoming = [];
        $past     = [];
        foreach ($events as $e) {
            if (self::is_upcoming($e->ID)) {
                $upcoming[] = $e;
            } else {
                $past[] = $e;
            }
        }
        $past = array_reverse($past); // most recent past first

        if ($atts['show'] !== 'past') {
            echo '<h2 class="stagekitwp-events-heading">' . esc_html($upcoming_heading) . '</h2>';
            if ($upcoming) {
                foreach ($upcoming as $e) { echo self::event_card($e, $can_manage_private); }
            } else {
                echo '<p class="stagekitwp-events-empty">No upcoming events.</p>';
            }
        }

        if ($atts['show'] !== 'upcoming') {
            echo '<h2 class="stagekitwp-events-heading">Past</h2>';
            if ($past) {
                foreach ($past as $e) { echo self::event_card($e, false); }
            } else {
                echo '<p class="stagekitwp-events-empty">No past events.</p>';
            }
        }
    }

    echo '</div>';
    return ob_get_clean();
}

/**
 * Render one event row with RSVP controls (upcoming) or a summary (past).
 */
private static function event_card($event, $can_rsvp) {

    $uid    = get_current_user_id();
    $status = STAGEKITWP_MEMBERS_RSVP::get_status($event->ID, $uid);
    $date   = self::event_date($event->ID);
    $counts = STAGEKITWP_MEMBERS_RSVP::counts($event->ID);

    ob_start();
    ?>
    <div class="stagekitwp-event">
        <div class="stagekitwp-event-main">
            <h3 class="stagekitwp-event-title"><?php echo esc_html($event->post_title); ?></h3>
            <?php if ($date): ?>
                <p class="stagekitwp-event-date"><?php echo esc_html($date); ?></p>
            <?php endif; ?>
            <?php if ($event->post_excerpt): ?>
                <p class="stagekitwp-event-blurb"><?php echo esc_html($event->post_excerpt); ?></p>
            <?php endif; ?>
            <?php if (is_user_logged_in()): ?>
                <p class="stagekitwp-event-counts"><?php echo (int) $counts['going']; ?> going · <?php echo (int) $counts['maybe']; ?> maybe</p>
            <?php endif; ?>
        </div>
        <div class="stagekitwp-event-rsvp">
            <?php if ($can_rsvp): ?>
                <?php foreach (['going' => 'Going', 'maybe' => 'Maybe', 'declined' => 'Can\'t make it'] as $key => $label): ?>
                    <form method="post" class="stagekitwp-rsvp-form">
                        <?php wp_nonce_field('stagekitwp_members_rsvp_' . $event->ID, 'stagekitwp_members_rsvp_nonce'); ?>
                        <input type="hidden" name="stagekitwp_members_rsvp_event" value="<?php echo (int) $event->ID; ?>">
                        <input type="hidden" name="stagekitwp_members_rsvp_status" value="<?php echo esc_attr($key); ?>">
                        <button type="submit" class="stagekitwp-rsvp-btn <?php echo $status === $key ? 'is-active' : ''; ?>">
                            <?php echo esc_html($label); ?>
                        </button>
                    </form>
                <?php endforeach; ?>
            <?php elseif ($status): ?>
                <span class="stagekitwp-rsvp-status">You: <?php echo esc_html(ucfirst($status)); ?></span>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render one event with all details: date, times, visibility, excerpt, content, and RSVP controls.
 */
private static function event_card_all_details($event, $can_rsvp) {

    $uid    = get_current_user_id();
    $status = STAGEKITWP_MEMBERS_RSVP::get_status($event->ID, $uid);
    $date   = self::event_date($event->ID);
    $start  = get_post_meta($event->ID, '_stagekitwp_members_start_time', true);
    $end    = get_post_meta($event->ID, '_stagekitwp_members_end_time', true);
    $visibility = get_post_meta($event->ID, STAGEKITWP_MEMBERS_Events::META_VISIBILITY, true);
    if (!in_array($visibility, ['public', 'private'], true)) {
        $visibility = 'private';
    }
    $counts = STAGEKITWP_MEMBERS_RSVP::counts($event->ID);
    $content = apply_filters('the_content', $event->post_content);

    ob_start();
    ?>
    <div class="stagekitwp-event-detail">
        <div class="stagekitwp-event-detail-header">
            <h3 class="stagekitwp-event-detail-title"><?php echo esc_html($event->post_title); ?></h3>
            <span class="stagekitwp-event-detail-visibility stagekitwp-visibility-badge stagekitwp-visibility-badge--<?php echo esc_attr($visibility); ?>"><?php echo esc_html(ucfirst($visibility)); ?></span>
        </div>
        <div class="stagekitwp-event-detail-meta">
            <?php if ($date): ?>
                <span class="stagekitwp-event-detail-meta-item">
                    <span class="stagekitwp-event-detail-label">Date:</span>
                    <span class="stagekitwp-event-detail-value"><?php echo esc_html($date); ?></span>
                </span>
            <?php endif; ?>
            <?php if ($start): ?>
                <span class="stagekitwp-event-detail-meta-item">
                    <span class="stagekitwp-event-detail-label">Start:</span>
                    <span class="stagekitwp-event-detail-value"><?php echo esc_html($start); ?></span>
                </span>
            <?php endif; ?>
            <?php if ($end): ?>
                <span class="stagekitwp-event-detail-meta-item">
                    <span class="stagekitwp-event-detail-label">End:</span>
                    <span class="stagekitwp-event-detail-value"><?php echo esc_html($end); ?></span>
                </span>
            <?php endif; ?>
        </div>
        <?php if ($event->post_excerpt): ?>
            <div class="stagekitwp-event-detail-excerpt">
                <?php echo esc_html($event->post_excerpt); ?>
            </div>
        <?php endif; ?>
        <?php if ($content): ?>
            <div class="stagekitwp-event-detail-content">
                <?php echo $content; ?>
            </div>
        <?php endif; ?>
        <?php if (is_user_logged_in()): ?>
            <div class="stagekitwp-event-detail-counts">
                <span><?php echo (int) $counts['going']; ?> going</span>
                <span><?php echo (int) $counts['maybe']; ?> maybe</span>
                <span><?php echo (int) $counts['declined']; ?> declined</span>
                <span><?php echo (int) $counts['attended']; ?> attended</span>
            </div>
        <?php endif; ?>
        <div class="stagekitwp-event-detail-rsvp">
            <?php if ($can_rsvp): ?>
                <?php foreach (['going' => 'Going', 'maybe' => 'Maybe', 'declined' => 'Can\'t make it'] as $key => $label): ?>
                    <form method="post" class="stagekitwp-rsvp-form">
                        <?php wp_nonce_field('stagekitwp_members_rsvp_' . $event->ID, 'stagekitwp_members_rsvp_nonce'); ?>
                        <input type="hidden" name="stagekitwp_members_rsvp_event" value="<?php echo (int) $event->ID; ?>">
                        <input type="hidden" name="stagekitwp_members_rsvp_status" value="<?php echo esc_attr($key); ?>">
                        <button type="submit" class="stagekitwp-rsvp-btn <?php echo $status === $key ? 'is-active' : ''; ?>">
                            <?php echo esc_html($label); ?>
                        </button>
                    </form>
                <?php endforeach; ?>
            <?php elseif ($status): ?>
                <span class="stagekitwp-rsvp-status">You: <?php echo esc_html(ucfirst($status)); ?></span>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

    /**
     * [stagekitwp_members_my_rsvps]
     */
    public static function render_my_rsvps($atts = []) {

        if (!self::viewer_allowed()) {
            return '<p>Please log in as a member to view your RSVPs.</p>';
        }

        $rows = STAGEKITWP_MEMBERS_RSVP::get_for_user(get_current_user_id());

        if (empty($rows)) {
            return '<div class="stagekitwp-events"><p class="stagekitwp-events-empty">You have not RSVP\'d to any events yet.</p></div>';
        }

        ob_start();
        echo '<div class="stagekitwp-events stagekitwp-myrsvps">';
        echo '<table class="stagekitwp-myrsvps-table"><thead><tr>';
        echo '<th>Event</th><th>Date</th><th>Your RSVP</th><th>Attended</th>';
        echo '</tr></thead><tbody>';

        foreach ($rows as $r) {
            $title = get_the_title($r->event_id);
            if (!$title) {
                continue; // event deleted
            }
            $date = self::event_date($r->event_id);
            printf(
                '<tr><td>%s</td><td>%s</td><td><span class="stagekitwp-rsvp-pill stagekitwp-rsvp-pill--%s">%s</span></td><td>%s</td></tr>',
                esc_html($title),
                esc_html($date),
                esc_attr($r->status),
                esc_html(ucfirst($r->status)),
                $r->attended ? '✓' : '—'
            );
        }

        echo '</tbody></table></div>';
        return ob_get_clean();
    }

    /**
     * Human-friendly event date (from _stagekitwp_members_event_date meta).
     */
    private static function event_date($event_id) {
        $raw = get_post_meta($event_id, '_stagekitwp_members_event_date', true);
        if (!$raw) {
            return '';
        }
        $ts = strtotime($raw);
        return $ts ? date_i18n(get_option('date_format'), $ts) : $raw;
    }

    /**
     * Is the event in the future (or undated)? Undated events count as upcoming.
     */
    private static function is_upcoming($event_id) {
        $raw = get_post_meta($event_id, '_stagekitwp_members_event_date', true);
        if (!$raw) {
            return true;
        }
        $ts = strtotime($raw);
        return $ts ? ($ts >= strtotime('today')) : true;
    }
}
