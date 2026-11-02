<?php
/**
 * Volunteer positions, signups, waitlists and producer roster.
 */

if (!defined('ABSPATH')) {
    exit;
}

class STAGEKITWP_MEMBERS_Volunteers {

    const VISIBILITY_META = '_stagekitwp_members_volunteer_visibility';
    const VISIBILITY_OPTION = 'stagekitwp_members_volunteers_public';
    private static $notice_rendered = false;

    public static function init() {
        add_action('admin_post_stagekitwp_members_volunteer_export', [__CLASS__, 'handle_export']);
        add_action('admin_post_stagekitwp_members_volunteer_signup', [__CLASS__, 'handle_frontend_post']);
        add_action('admin_post_nopriv_stagekitwp_members_volunteer_signup', [__CLASS__, 'handle_frontend_post']);
        add_action('stagekitwp_members_volunteer_reminders', [__CLASS__, 'send_reminders']);
        add_action('init', [__CLASS__, 'schedule_reminders']);
        add_action('admin_menu', [__CLASS__, 'add_roster_menu']);
        add_shortcode('stagekitwp_members_volunteer', [__CLASS__, 'render_event_positions']);
        add_shortcode('stagekitwp_members_my_volunteering', [__CLASS__, 'render_my_volunteering']);
        add_action('add_meta_boxes', [__CLASS__, 'add_event_fields']);
        add_action('save_post_stagekitwp_event', [__CLASS__, 'save_event_fields'], 20, 2);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue']);
    }

    private static function positions_table() {
        global $wpdb;
        return $wpdb->prefix . 'stagekitwp_members_positions';
    }

    private static function volunteers_table() {
        global $wpdb;
        return $wpdb->prefix . 'stagekitwp_members_volunteers';
    }

    public static function schedule_reminders() {
        if (!wp_next_scheduled('stagekitwp_members_volunteer_reminders')) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'stagekitwp_members_volunteer_reminders');
        }
    }

    public static function add_event_fields() {
        add_meta_box(
            'stagekitwp_members_volunteer_positions',
            'Volunteer Positions',
            [__CLASS__, 'render_event_fields'],
            'stagekitwp_event',
            'normal',
            'default'
        );
    }

    public static function render_event_fields($post) {
        wp_nonce_field('stagekitwp_members_volunteer_positions', 'stagekitwp_members_volunteer_nonce');
        self::render_fields((int) $post->ID);
    }

    /**
     * Shared position editor used by the event editor and the Members Events panel.
     */
    public static function render_fields($event_id) {
        $positions = $event_id ? self::get_positions($event_id, false) : [];
        $templates = self::get_templates();
        $visibility = $event_id ? get_post_meta($event_id, self::VISIBILITY_META, true) : 'inherit';
        if (!in_array($visibility, ['inherit', 'public', 'private'], true)) {
            $visibility = 'inherit';
        }
        ?>
        <p>Define volunteer roles and capacity. A zero slot count means unlimited. Signups are restricted to logged-in members.</p>
        <p>
            <label><strong>Open-position visibility</strong>
                <select name="stagekitwp_members_volunteer_visibility">
                    <option value="inherit" <?php selected($visibility, 'inherit'); ?>>Use global setting</option>
                    <option value="public" <?php selected($visibility, 'public'); ?>>Public read-only counts</option>
                    <option value="private" <?php selected($visibility, 'private'); ?>>Members only</option>
                </select>
            </label>
        </p>
        <?php if ($templates) : ?>
            <p>
                <label for="stagekitwp-members-volunteer-template"><strong>Reusable position</strong></label>
                <select id="stagekitwp-members-volunteer-template">
                    <option value="">Choose a saved template</option>
                    <?php foreach ($templates as $template) : ?>
                        <option value="<?php echo esc_attr(wp_json_encode([
                            'title' => $template->title,
                            'description' => $template->description,
                            'slots' => (int) $template->slots,
                            'required_role' => $template->required_role,
                            'required_interest' => $template->required_interest,
                        ])); ?>"><?php echo esc_html($template->title); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="button" id="stagekitwp-members-add-template">Add template</button>
            </p>
        <?php endif; ?>
        <div class="stagekitwp-volunteer-position-list">
            <?php foreach ($positions as $index => $position) : self::render_position_row($position, $index); endforeach; ?>
        </div>
        <p><button type="button" class="button" data-stagekitwp-add-position>Add position</button></p>
        <script type="text/html" id="stagekitwp-members-position-template"><?php
            self::render_position_row((object) [
                'id' => 0, 'title' => '', 'description' => '', 'slots' => 1,
                'required_role' => '', 'required_interest' => '', 'sort_order' => 0,
            ], '__INDEX__');
        ?></script>
        <script>
        (function() {
            var list = document.querySelector('.stagekitwp-volunteer-position-list');
            if (!list) return;
            var source = document.getElementById('stagekitwp-members-position-template');
            var nextIndex = list.querySelectorAll('.stagekitwp-volunteer-position').length;
            var add = function(data) {
                var index = nextIndex++;
                var html = source.innerHTML.replace(/__INDEX__/g, index);
                var holder = document.createElement('div');
                holder.innerHTML = html;
                var row = holder.firstElementChild;
                if (data) {
                    row.querySelector('[name$="[title]"]').value = data.title || '';
                    row.querySelector('[name$="[description]"]').value = data.description || '';
                    row.querySelector('[name$="[slots]"]').value = data.slots || 0;
                    row.querySelector('[name$="[required_role]"]').value = data.required_role || '';
                    row.querySelector('[name$="[required_interest]"]').value = data.required_interest || '';
                }
                list.appendChild(row);
            };
            document.querySelector('[data-stagekitwp-add-position]').addEventListener('click', function() { add(); });
            list.addEventListener('click', function(event) {
                if (event.target.matches('[data-stagekitwp-remove-position]')) {
                    event.preventDefault();
                    event.target.closest('.stagekitwp-volunteer-position').remove();
                }
            });
            var templateButton = document.getElementById('stagekitwp-members-add-template');
            if (templateButton) templateButton.addEventListener('click', function() {
                var selector = document.getElementById('stagekitwp-members-volunteer-template');
                if (selector.value) add(JSON.parse(selector.value));
            });
            if (!list.children.length) add();
        })();
        </script>
        <?php
    }

    private static function render_position_row($position, $index) {
        ?>
        <fieldset class="stagekitwp-volunteer-position" style="border:1px solid #c3c4c7;padding:12px;margin:12px 0">
            <input type="hidden" name="stagekitwp_members_positions[<?php echo esc_attr($index); ?>][id]" value="<?php echo esc_attr((int) ($position->id ?? 0)); ?>">
            <p><label><strong>Position title</strong><br><input class="regular-text" required name="stagekitwp_members_positions[<?php echo esc_attr($index); ?>][title]" value="<?php echo esc_attr($position->title ?? ''); ?>"></label></p>
            <p><label><strong>Description</strong><br><textarea class="large-text" rows="2" name="stagekitwp_members_positions[<?php echo esc_attr($index); ?>][description]"><?php echo esc_textarea($position->description ?? ''); ?></textarea></label></p>
            <p>
                <label>Slots <input type="number" min="0" step="1" name="stagekitwp_members_positions[<?php echo esc_attr($index); ?>][slots]" value="<?php echo esc_attr((int) ($position->slots ?? 1)); ?>"></label>
                <label>Required role <input type="text" name="stagekitwp_members_positions[<?php echo esc_attr($index); ?>][required_role]" value="<?php echo esc_attr($position->required_role ?? ''); ?>" placeholder="Optional role slug"></label>
                <label>Required interest <input type="text" name="stagekitwp_members_positions[<?php echo esc_attr($index); ?>][required_interest]" value="<?php echo esc_attr($position->required_interest ?? ''); ?>" placeholder="Optional interest"></label>
            </p>
            <p><label><input type="checkbox" name="stagekitwp_members_positions[<?php echo esc_attr($index); ?>][save_template]" value="1"> Save as reusable template</label>
                <button type="button" class="button-link-delete" data-stagekitwp-remove-position>Remove position</button></p>
        </fieldset>
        <?php
    }

    public static function save_event_fields($post_id, $post) {
        if (wp_is_post_revision($post_id) || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            || !isset($_POST['stagekitwp_members_volunteer_nonce'])
            || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['stagekitwp_members_volunteer_nonce'])), 'stagekitwp_members_volunteer_positions')
            || !current_user_can('edit_post', $post_id)) {
            return;
        }
        self::save_positions($post_id);
        $visibility = isset($_POST['stagekitwp_members_volunteer_visibility'])
            ? sanitize_key(wp_unslash($_POST['stagekitwp_members_volunteer_visibility']))
            : 'inherit';
        if (in_array($visibility, ['inherit', 'public', 'private'], true)) {
            update_post_meta($post_id, self::VISIBILITY_META, $visibility);
        }
    }

    public static function save_positions($event_id) {
        global $wpdb;
        $table = self::positions_table();
        $rows = isset($_POST['stagekitwp_members_positions']) && is_array($_POST['stagekitwp_members_positions'])
            ? wp_unslash($_POST['stagekitwp_members_positions'])
            : [];
        $seen = [];
        $order = 0;
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $title = sanitize_text_field($row['title'] ?? '');
            if ($title === '') {
                continue;
            }
            $id = absint($row['id'] ?? 0);
            $data = [
                'title' => $title,
                'description' => sanitize_textarea_field($row['description'] ?? ''),
                'slots' => absint($row['slots'] ?? 0),
                'required_role' => sanitize_key($row['required_role'] ?? ''),
                'required_interest' => sanitize_text_field($row['required_interest'] ?? ''),
                'sort_order' => $order++,
                'active' => 1,
                'updated_at' => current_time('mysql'),
            ];
            if ($id && $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE id = %d AND event_id = %d", $id, $event_id))) {
                if ($wpdb->update($table, $data, ['id' => $id]) === false) {
                    error_log('StageKitWP Members could not update a volunteer position: ' . $wpdb->last_error);
                    return;
                }
                $seen[] = $id;
            } else {
                $data['event_id'] = (int) $event_id;
                $data['is_template'] = 0;
                $data['created_at'] = current_time('mysql');
                if ($wpdb->insert($table, $data) === false) {
                    error_log('StageKitWP Members could not create a volunteer position: ' . $wpdb->last_error);
                    return;
                } else {
                    $id = (int) $wpdb->insert_id;
                    if ($id) {
                        $seen[] = $id;
                    }
                }
            }
            if (!empty($row['save_template'])) {
                $template_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$table} WHERE event_id = 0 AND is_template = 1 AND title = %s LIMIT 1",
                    $data['title']
                ));
                $template_data = [
                    'title' => $data['title'],
                    'description' => $data['description'],
                    'slots' => $data['slots'],
                    'required_role' => $data['required_role'],
                    'required_interest' => $data['required_interest'],
                    'active' => 1,
                    'updated_at' => current_time('mysql'),
                ];
                if ($template_id) {
                    if ($wpdb->update($table, $template_data, ['id' => (int) $template_id]) === false) {
                        error_log('StageKitWP Members could not update a volunteer position template: ' . $wpdb->last_error);
                        return;
                    }
                } else {
                    if ($wpdb->insert($table, $template_data + [
                        'event_id' => 0,
                        'sort_order' => 0,
                        'is_template' => 1,
                        'created_at' => current_time('mysql'),
                    ]) === false) {
                        error_log('StageKitWP Members could not create a volunteer position template: ' . $wpdb->last_error);
                        return;
                    }
                }
            }
        }
        $sql = "UPDATE {$table} SET active = 0 WHERE event_id = %d";
        $values = [(int) $event_id];
        if ($seen) {
            $sql .= ' AND id NOT IN (' . implode(',', array_map('absint', $seen)) . ')';
        }
        if ($wpdb->query($wpdb->prepare($sql, $values)) === false) {
            error_log('StageKitWP Members could not deactivate removed volunteer positions: ' . $wpdb->last_error);
        }
    }

    public static function get_positions($event_id, $active_only = true) {
        global $wpdb;
        $table = self::positions_table();
        $sql = "SELECT * FROM {$table} WHERE event_id = %d";
        if ($active_only) {
            $sql .= ' AND active = 1';
        }
        $sql .= ' ORDER BY sort_order ASC, id ASC';
        return $wpdb->get_results($wpdb->prepare($sql, (int) $event_id));
    }

    private static function get_templates() {
        global $wpdb;
        $table = self::positions_table();
        return $wpdb->get_results("SELECT * FROM {$table} WHERE is_template = 1 AND active = 1 ORDER BY title ASC");
    }

    public static function counts($position_id) {
        global $wpdb;
        $table = self::volunteers_table();
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT status, COUNT(*) AS total FROM {$table} WHERE position_id = %d AND status IN ('confirmed','waitlist') GROUP BY status",
            (int) $position_id
        ));
        $counts = ['confirmed' => 0, 'waitlist' => 0];
        foreach ((array) $rows as $row) {
            $counts[$row->status] = (int) $row->total;
        }
        return $counts;
    }

    private static function visible_publicly($event_id) {
        $override = get_post_meta($event_id, self::VISIBILITY_META, true);
        if ($override === 'public') {
            return true;
        }
        if ($override === 'private') {
            return false;
        }
        return (bool) get_option(self::VISIBILITY_OPTION, 0);
    }

    private static function can_manage() {
        return current_user_can(STAGEKITWP_MEMBERS_Roles::CAP_MANAGE_EVENTS);
    }

    private static function can_signup($position, $user_id) {
        if (!is_user_logged_in() || !current_user_can(STAGEKITWP_MEMBERS_Roles::CAP_RSVP_EVENTS)) {
            return false;
        }
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }
        if ($position->required_role && !in_array($position->required_role, (array) $user->roles, true)) {
            return false;
        }
        if ($position->required_interest && !has_term($position->required_interest, 'stagekitwp_interest', $user_id)) {
            return false;
        }
        return true;
    }

    public static function render_event_positions($atts = []) {
        $atts = shortcode_atts(['event_id' => 0], $atts, 'stagekitwp_members_volunteer');
        $event_id = absint($atts['event_id']);
        if (!$event_id && is_singular('stagekitwp_event')) {
            $event_id = get_queried_object_id();
        }
        $event = get_post($event_id);
        if (!$event || $event->post_type !== 'stagekitwp_event' || $event->post_status !== 'publish') {
            return '';
        }
        $event_date = (string) get_post_meta($event_id, '_stagekitwp_members_event_date', true);
        if ($event_date && $event_date < current_time('Y-m-d')) {
            return '';
        }
        $member = is_user_logged_in() && current_user_can(STAGEKITWP_MEMBERS_Roles::CAP_RSVP_EVENTS);
        $manager = self::can_manage();
        if (!STAGEKITWP_MEMBERS_Events::is_public_event($event_id) && !$member && !$manager) {
            return '';
        }
        $public_view = !$member && !$manager;
        if ($public_view && !self::visible_publicly($event_id)) {
            return '';
        }
        $positions = self::get_positions($event_id);
        ob_start();
        if (!self::$notice_rendered && isset($_GET['stagekitwp_volunteer_done'])) {
            echo '<p class="stagekitwp-events-notice">Your volunteer signup has been updated.</p>';
            self::$notice_rendered = true;
        } elseif (!self::$notice_rendered && isset($_GET['stagekitwp_volunteer_error'])) {
            echo '<p class="stagekitwp-events-error">We could not update that volunteer signup. Please check your access and try again.</p>';
            self::$notice_rendered = true;
        }
        if (!$positions) {
            return ob_get_clean();
        }
        echo '<section class="stagekitwp-volunteers" aria-label="Volunteer opportunities"><h4><span class="stagekitwp-volunteer-badge">Volunteers needed</span></h4>';
        foreach ($positions as $position) {
            $counts = self::counts($position->id);
            $slots = (int) $position->slots;
            $remaining = $slots > 0 ? max(0, $slots - $counts['confirmed']) : null;
            if ($public_view && $remaining === 0) {
                continue;
            }
            $user_id = get_current_user_id();
            $mine = $user_id ? self::get_user_signup($position->id, $user_id) : null;
            ?>
            <article class="stagekitwp-volunteer-position">
                <h5><?php echo esc_html($position->title); ?></h5>
                <?php if ($position->description) : ?><p><?php echo esc_html($position->description); ?></p><?php endif; ?>
                <p class="stagekitwp-volunteer-count">
                    <?php echo $slots ? esc_html(sprintf('%d of %d filled', $counts['confirmed'], $slots)) : esc_html(sprintf('%d signed up', $counts['confirmed'])); ?>
                    <?php if ($counts['waitlist']) : ?> · <?php echo esc_html(sprintf('%d waitlisted', $counts['waitlist'])); ?><?php endif; ?>
                </p>
                <?php if (!$public_view && $member && self::can_signup($position, $user_id)) : ?>
                    <?php if ($mine && $mine->status !== 'cancelled') : ?>
                        <p>You are <?php echo esc_html($mine->status === 'waitlist' ? 'on the waitlist' : 'signed up'); ?>.</p>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                            <input type="hidden" name="action" value="stagekitwp_members_volunteer_signup">
                            <input type="hidden" name="event_id" value="<?php echo (int) $event_id; ?>">
                            <input type="hidden" name="position_id" value="<?php echo (int) $position->id; ?>">
                            <input type="hidden" name="intent" value="cancel">
                            <?php wp_nonce_field('stagekitwp_members_volunteer_' . $event_id, 'volunteer_nonce'); ?>
                            <button class="button" type="submit">Cancel signup</button>
                        </form>
                    <?php else : ?>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                            <input type="hidden" name="action" value="stagekitwp_members_volunteer_signup">
                            <input type="hidden" name="event_id" value="<?php echo (int) $event_id; ?>">
                            <input type="hidden" name="position_id" value="<?php echo (int) $position->id; ?>">
                            <label>Note (optional) <input type="text" name="note" maxlength="255"></label>
                            <?php wp_nonce_field('stagekitwp_members_volunteer_' . $event_id, 'volunteer_nonce'); ?>
                            <button class="button button-primary" type="submit">Sign up</button>
                        </form>
                    <?php endif; ?>
                <?php elseif (!$member && !$manager && !$public_view) : ?>
                    <p><a href="<?php echo esc_url(wp_login_url(get_permalink($event_id))); ?>">Log in to sign up</a></p>
                <?php elseif ($public_view && !is_user_logged_in()) : ?>
                    <p><a href="<?php echo esc_url(wp_login_url(get_permalink($event_id))); ?>">Log in to sign up</a></p>
                <?php elseif ($member && !self::can_signup($position, $user_id)) : ?>
                    <p>You do not meet the role or interest requirements for this position.</p>
                <?php endif; ?>
            </article>
            <?php
        }
        echo '</section>';
        return ob_get_clean();
    }

    private static function get_user_signup($position_id, $user_id) {
        global $wpdb;
        $table = self::volunteers_table();
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE position_id = %d AND user_id = %d",
            (int) $position_id,
            (int) $user_id
        ));
    }

    public static function handle_frontend_post() {
        $event_id = isset($_POST['event_id']) ? absint(wp_unslash($_POST['event_id'])) : 0;
        $position_id = isset($_POST['position_id']) ? absint(wp_unslash($_POST['position_id'])) : 0;
        $back = wp_get_referer() ?: home_url('/');
        if (!is_user_logged_in() || !current_user_can(STAGEKITWP_MEMBERS_Roles::CAP_RSVP_EVENTS)
            || !$event_id || !$position_id
            || !isset($_POST['volunteer_nonce'])
            || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['volunteer_nonce'])), 'stagekitwp_members_volunteer_' . $event_id)) {
            wp_safe_redirect(add_query_arg('stagekitwp_volunteer_error', 'unauthorized', remove_query_arg(['stagekitwp_volunteer_done', 'stagekitwp_volunteer_error'], $back)));
            exit;
        }
        global $wpdb;
        $position = $wpdb->get_row($wpdb->prepare(
            'SELECT * FROM ' . self::positions_table() . ' WHERE id = %d AND event_id = %d AND active = 1',
            $position_id,
            $event_id
        ));
        if (!$position || get_post_type($event_id) !== 'stagekitwp_event' || get_post_status($event_id) !== 'publish'
            || (!STAGEKITWP_MEMBERS_Events::is_public_event($event_id) && !current_user_can(STAGEKITWP_MEMBERS_Roles::CAP_RSVP_EVENTS) && !self::can_manage())) {
            wp_safe_redirect(add_query_arg('stagekitwp_volunteer_error', 'unavailable', remove_query_arg(['stagekitwp_volunteer_done', 'stagekitwp_volunteer_error'], $back)));
            exit;
        }
        $user_id = get_current_user_id();
        $context = ['type' => 'event', 'id' => $event_id, 'position_id' => $position_id, 'user_id' => $user_id];
        do_action('stagekitwp_members_before_signup', $event_id, $position_id, $user_id, $context);

        if (isset($_POST['intent']) && sanitize_key(wp_unslash($_POST['intent'])) === 'cancel') {
            $ok = self::cancel_signup($event_id, $position, $user_id);
        } elseif (self::can_signup($position, $user_id)) {
            $note = isset($_POST['note']) ? sanitize_text_field(wp_unslash($_POST['note'])) : '';
            $ok = self::create_signup($event_id, $position, $user_id, $note);
        } else {
            $ok = false;
        }
        if ($ok) {
            do_action('stagekitwp_members_after_signup', $event_id, $position_id, $user_id, $context);
        }
        $back = remove_query_arg(['stagekitwp_volunteer_done', 'stagekitwp_volunteer_error'], $back);
        wp_safe_redirect(add_query_arg($ok ? 'stagekitwp_volunteer_done' : 'stagekitwp_volunteer_error', $ok ? '1' : 'unavailable', $back));
        exit;
    }

    private static function create_signup($event_id, $position, $user_id, $note) {
        global $wpdb;
        $table = self::volunteers_table();
        $existing = self::get_user_signup($position->id, $user_id);
        if ($existing && in_array($existing->status, ['confirmed', 'waitlist'], true)) {
            return true;
        }
        $reserved = self::reserve_slot($position);
        if ($reserved === null) {
            return false;
        }
        $status = $reserved ? 'confirmed' : 'waitlist';
        $now = current_time('mysql');
        if ($existing) {
            $result = $wpdb->update(
                $table,
                ['status' => $status, 'note' => $note, 'updated_at' => $now, 'reminder_sent_at' => null],
                ['id' => (int) $existing->id, 'status' => 'cancelled']
            );
            if ($result !== 1) {
                if ($result === false) {
                    error_log('StageKitWP Members could not update a volunteer signup: ' . $wpdb->last_error);
                }
                if ($status === 'confirmed') {
                    self::release_slot($position->id);
                }
                $current = self::get_user_signup($position->id, $user_id);
                return $current && in_array($current->status, ['confirmed', 'waitlist'], true);
            }
        } else {
            $result = $wpdb->insert($table, [
                'event_id' => (int) $event_id, 'position_id' => (int) $position->id, 'user_id' => (int) $user_id,
                'status' => $status, 'note' => $note, 'created_at' => $now, 'updated_at' => $now,
            ]);
            if ($result === false) {
                if ($status === 'confirmed') {
                    self::release_slot($position->id);
                }
                $current = self::get_user_signup($position->id, $user_id);
                if ($current && in_array($current->status, ['confirmed', 'waitlist'], true)) {
                    return true;
                }
                error_log('StageKitWP Members could not create a volunteer signup: ' . $wpdb->last_error);
                return false;
            }
        }
        self::notify_user($user_id, $event_id, $position->title, $status === 'confirmed' ? 'confirmed' : 'waitlist');
        return true;
    }

    private static function reserve_slot($position) {
        global $wpdb;
        $table = self::positions_table();
        if ((int) $position->slots === 0) {
            $updated = $wpdb->query($wpdb->prepare(
                "UPDATE {$table} SET confirmed_count = confirmed_count + 1 WHERE id = %d AND active = 1",
                (int) $position->id
            ));
        } else {
            $updated = $wpdb->query($wpdb->prepare(
                "UPDATE {$table} SET confirmed_count = confirmed_count + 1 WHERE id = %d AND active = 1 AND confirmed_count < slots",
                (int) $position->id
            ));
        }
        if ($updated === false) {
            error_log('StageKitWP Members could not reserve volunteer capacity: ' . $wpdb->last_error);
            return null;
        }
        return $updated === 1;
    }

    private static function release_slot($position_id) {
        global $wpdb;
        $table = self::positions_table();
        if ($wpdb->query($wpdb->prepare("UPDATE {$table} SET confirmed_count = CASE WHEN confirmed_count > 0 THEN confirmed_count - 1 ELSE 0 END WHERE id = %d", (int) $position_id)) === false) {
            error_log('StageKitWP Members could not release volunteer capacity: ' . $wpdb->last_error);
        }
    }

    private static function cancel_signup($event_id, $position, $user_id) {
        global $wpdb;
        $existing = self::get_user_signup($position->id, $user_id);
        if (!$existing || !in_array($existing->status, ['confirmed', 'waitlist'], true)) {
            return true;
        }
        $table = self::volunteers_table();
        $updated = $wpdb->query($wpdb->prepare(
            "UPDATE {$table} SET status = 'cancelled', updated_at = %s WHERE id = %d AND status = %s",
            current_time('mysql'),
            (int) $existing->id,
            $existing->status
        ));
        if ($updated === false) {
            error_log('StageKitWP Members could not cancel a volunteer signup: ' . $wpdb->last_error);
            return false;
        }
        if ($updated === 0) {
            return true;
        }
        if ($existing->status === 'confirmed') {
            self::release_slot($position->id);
            self::promote_waitlist($event_id, $position);
        }
        return true;
    }

    private static function promote_waitlist($event_id, $position) {
        global $wpdb;
        $table = self::volunteers_table();
        $candidate = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE position_id = %d AND status = 'waitlist' ORDER BY created_at ASC, id ASC LIMIT 1",
            (int) $position->id
        ));
        if (!$candidate || !self::reserve_slot($position)) {
            return;
        }
        $updated = $wpdb->update(
            $table,
            ['status' => 'confirmed', 'updated_at' => current_time('mysql')],
            ['id' => (int) $candidate->id, 'status' => 'waitlist']
        );
        if ($updated !== 1) {
            self::release_slot($position->id);
            return;
        }
        self::notify_user((int) $candidate->user_id, $event_id, $position->title, 'promoted');
    }

    private static function notify_user($user_id, $event_id, $position, $kind) {
        $user = get_userdata($user_id);
        if (!get_option('stagekitwp_members_email_enabled', 1) || !$user || !$user->user_email || (class_exists('STAGEKITWP_MEMBERS_Notifications')
            && !STAGEKITWP_MEMBERS_Notifications::should_notify($user_id, STAGEKITWP_MEMBERS_Notifications::TYPE_EVENT))) {
            return;
        }
        $event = get_the_title($event_id);
        $subject = $kind === 'waitlist' ? 'Volunteer waitlist: ' . $event : 'Volunteer signup: ' . $event;
        $verb = ['confirmed' => 'You are confirmed for', 'waitlist' => 'You have been added to the waitlist for', 'promoted' => 'A place has opened; you are confirmed for'][$kind] ?? 'Your signup was updated for';
        $body = $verb . ' ' . $position . ' at ' . $event . '.';
        if (class_exists('STAGEKITWP_MEMBERS_Email_Queue')) {
            STAGEKITWP_MEMBERS_Email_Queue::add_to_queue($user->user_email, $subject, $body);
        } elseif (class_exists('STAGEKITWP_MEMBERS_Health')) {
            STAGEKITWP_MEMBERS_Health::send_email($user->user_email, $subject, $body);
        }
    }

    public static function render_my_volunteering() {
        if (!is_user_logged_in() || !current_user_can(STAGEKITWP_MEMBERS_Roles::CAP_RSVP_EVENTS)) {
            return '<p>Please log in as a member to view your volunteer signups.</p>';
        }
        global $wpdb;
        $rows = $wpdb->get_results($wpdb->prepare(
            'SELECT * FROM ' . self::volunteers_table() . ' WHERE user_id = %d ORDER BY updated_at DESC',
            get_current_user_id()
        ));
        if (!$rows) {
            return '<p>You have no volunteer signups.</p>';
        }
        $html = '<ul class="stagekitwp-my-volunteering">';
        foreach ($rows as $row) {
            $event = get_post((int) $row->event_id);
            $position = $wpdb->get_var($wpdb->prepare('SELECT title FROM ' . self::positions_table() . ' WHERE id = %d', (int) $row->position_id));
            if (!$event || !$position) {
                continue;
            }
            $html .= '<li><a href="' . esc_url(get_permalink($event)) . '">' . esc_html($event->post_title) . '</a> — ' . esc_html($position) . ' (' . esc_html($row->status) . ')</li>';
        }
        return $html . '</ul>';
    }

    public static function add_roster_menu() {
        add_submenu_page('stagekitwp', 'Volunteer Roster', 'Volunteer Roster', STAGEKITWP_MEMBERS_Roles::CAP_MANAGE_EVENTS, 'stagekitwp-ma-volunteers', [__CLASS__, 'render_roster']);
    }

    public static function render_roster() {
        if (!self::can_manage()) {
            wp_die(esc_html__('Access denied.', 'stagekitwp-members'));
        }
        $event_id = isset($_GET['event_id']) ? absint(wp_unslash($_GET['event_id'])) : 0;
        $events = get_posts(['post_type' => 'stagekitwp_event', 'post_status' => ['publish', 'private', 'draft'], 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC']);
        ?>
        <div class="wrap"><h1>Volunteer Roster</h1>
            <form method="get"><input type="hidden" name="page" value="stagekitwp-ma-volunteers">
                <label for="volunteer-event">Event</label>
                <select id="volunteer-event" name="event_id"><option value="0">Choose an event</option>
                    <?php foreach ($events as $event) : ?><option value="<?php echo (int) $event->ID; ?>" <?php selected($event_id, $event->ID); ?>><?php echo esc_html($event->post_title); ?></option><?php endforeach; ?>
                </select><button class="button">View roster</button>
            </form>
            <?php if ($event_id) :
                $url = wp_nonce_url(admin_url('admin-post.php?action=stagekitwp_members_volunteer_export&event_id=' . $event_id), 'stagekitwp_members_volunteer_export_' . $event_id); ?>
                <p><a class="button" href="<?php echo esc_url($url); ?>">Export CSV</a></p>
                <?php foreach (self::get_positions($event_id) as $position) : ?>
                    <h2><?php echo esc_html($position->title); ?></h2>
                    <table class="widefat striped"><thead><tr><th>Member</th><th>Email</th><th>Status</th><th>Note</th><th>Updated</th></tr></thead><tbody>
                    <?php $rows = self::get_roster_rows($event_id, $position->id); foreach ($rows as $row) : $user = get_userdata($row->user_id); ?>
                        <tr><td><?php echo esc_html($user ? $user->display_name : '(deleted user)'); ?></td><td><?php echo esc_html($user ? $user->user_email : ''); ?></td><td><?php echo esc_html($row->status); ?></td><td><?php echo esc_html($row->note); ?></td><td><?php echo esc_html($row->updated_at); ?></td></tr>
                    <?php endforeach; if (!$rows) : ?><tr><td colspan="5">No signups.</td></tr><?php endif; ?>
                    </tbody></table>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php
    }

    private static function get_roster_rows($event_id, $position_id = 0) {
        global $wpdb;
        $table = self::volunteers_table();
        if ($position_id) {
            return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE event_id = %d AND position_id = %d ORDER BY status, created_at", (int) $event_id, (int) $position_id));
        }
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE event_id = %d ORDER BY position_id, status, created_at", (int) $event_id));
    }

    public static function handle_export() {
        $event_id = isset($_GET['event_id']) ? absint(wp_unslash($_GET['event_id'])) : 0;
        check_admin_referer('stagekitwp_members_volunteer_export_' . $event_id);
        if (!self::can_manage() || !$event_id || get_post_type($event_id) !== 'stagekitwp_event') {
            wp_die(esc_html__('Access denied.', 'stagekitwp-members'));
        }
        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="volunteers-' . $event_id . '-' . gmdate('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Event', 'Position', 'Member', 'Email', 'Status', 'Note', 'Created', 'Updated'], ',', '"', '\\');
        $rows = self::get_roster_rows($event_id);
        global $wpdb;
        foreach ($rows as $row) {
            $user = get_userdata($row->user_id);
            $position = $wpdb->get_var($wpdb->prepare('SELECT title FROM ' . self::positions_table() . ' WHERE id = %d', (int) $row->position_id));
            $fields = [get_the_title($event_id), $position, $user ? $user->display_name : '(deleted user)', $user ? $user->user_email : '', $row->status, $row->note, $row->created_at, $row->updated_at];
            fputcsv($out, array_map([__CLASS__, 'spreadsheet_safe'], $fields), ',', '"', '\\');
        }
        fclose($out);
        exit;
    }

    private static function spreadsheet_safe($value) {
        $value = (string) $value;
        return preg_match('/^[\s]*[=+\-@]/', $value) ? "'" . $value : $value;
    }

    public static function get_rest_roster($event_id) {
        $rows = self::get_roster_rows($event_id);
        $result = [];
        foreach ($rows as $row) {
            $user = get_userdata($row->user_id);
            $result[] = [
                'position_id' => (int) $row->position_id,
                'member_id' => (int) $row->user_id,
                'name' => $user ? $user->display_name : '(deleted user)',
                'status' => $row->status,
                'note' => $row->note,
                'created_at' => $row->created_at,
            ];
        }
        return $result;
    }

    public static function rest_signup($event_id, $position_id, $user_id, $intent = 'signup', $note = '') {
        global $wpdb;
        $position = $wpdb->get_row($wpdb->prepare(
            'SELECT * FROM ' . self::positions_table() . ' WHERE id = %d AND event_id = %d AND active = 1',
            (int) $position_id, (int) $event_id
        ));
        if (!$position || get_post_status($event_id) !== 'publish') {
            return new WP_Error('stagekitwp_members_volunteer_not_found', 'Position not found.', ['status' => 404]);
        }
        $context = ['type' => 'event', 'id' => (int) $event_id, 'position_id' => (int) $position_id, 'user_id' => (int) $user_id];
        do_action('stagekitwp_members_before_signup', (int) $event_id, (int) $position_id, (int) $user_id, $context);
        $ok = $intent === 'cancel'
            ? self::cancel_signup($event_id, $position, $user_id)
            : (self::can_signup($position, $user_id) && self::create_signup($event_id, $position, $user_id, $note));
        if (!$ok) {
            return new WP_Error('stagekitwp_members_volunteer_signup_failed', 'Unable to save volunteer signup.', ['status' => 400]);
        }
        do_action('stagekitwp_members_after_signup', (int) $event_id, (int) $position_id, (int) $user_id, $context);
        $signup = self::get_user_signup($position_id, $user_id);
        if (!$signup) {
            return new WP_Error('stagekitwp_members_volunteer_signup_failed', 'Signup saved but could not be retrieved.', ['status' => 500]);
        }
        return ['event_id' => (int) $event_id, 'position_id' => (int) $position_id, 'status' => $signup->status];
    }

    public static function send_reminders() {
        global $wpdb;
        $tomorrow = wp_date('Y-m-d', time() + DAY_IN_SECONDS);
        $table = self::volunteers_table();
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT v.* FROM {$table} v INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = v.event_id AND pm.meta_key = '_stagekitwp_members_event_date' AND pm.meta_value = %s WHERE v.status = 'confirmed' AND v.reminder_sent_at IS NULL",
            $tomorrow
        ));
        foreach ((array) $rows as $row) {
            $event = get_post((int) $row->event_id);
            $position = $wpdb->get_var($wpdb->prepare('SELECT title FROM ' . self::positions_table() . ' WHERE id = %d', (int) $row->position_id));
            $user = get_userdata((int) $row->user_id);
            if (!$event || !$user || !$user->user_email) {
                continue;
            }
            if (get_option('stagekitwp_members_email_enabled', 1) && (!class_exists('STAGEKITWP_MEMBERS_Notifications') || STAGEKITWP_MEMBERS_Notifications::should_notify($user->ID, STAGEKITWP_MEMBERS_Notifications::TYPE_EVENT))) {
                $body = 'Reminder: you are scheduled to volunteer as ' . $position . ' for ' . $event->post_title . ' tomorrow.';
                if (class_exists('STAGEKITWP_MEMBERS_Email_Queue')) {
                    STAGEKITWP_MEMBERS_Email_Queue::add_to_queue($user->user_email, 'Volunteer reminder: ' . $event->post_title, $body);
                } elseif (class_exists('STAGEKITWP_MEMBERS_Health')) {
                    STAGEKITWP_MEMBERS_Health::send_email($user->user_email, 'Volunteer reminder: ' . $event->post_title, $body);
                }
            }
            $wpdb->update($table, ['reminder_sent_at' => current_time('mysql')], ['id' => (int) $row->id]);
        }
    }

    public static function enqueue() {
        if (!is_singular()) {
            return;
        }
        $post = get_post();
        if ($post && (has_shortcode($post->post_content, 'stagekitwp_members_volunteer') || has_shortcode($post->post_content, 'stagekitwp_members_events'))) {
            wp_enqueue_style('stagekitwp-ma-events', STAGEKITWPMA_URL . 'assets/css/events.css', [], STAGEKITWPMA_VERSION);
        }
    }
}
