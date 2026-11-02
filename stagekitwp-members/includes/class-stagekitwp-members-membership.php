<?php
/**
 * Membership records: the contract a payment plugin writes to.
 *
 * Members never handles money. A payment plugin fires
 * do_action( 'stagekitwp_members_payment_complete', $user_id, $args ) and this
 * class records the result. See docs/hooks.md.
 */

if (!defined('ABSPATH')) {
    exit;
}

class STAGEKITWP_MEMBERS_Membership {

    const STATUSES = ['active', 'lapsed', 'pending', 'none'];

    public static function init() {
        add_action('stagekitwp_members_payment_complete', [__CLASS__, 'on_payment_complete'], 10, 2);

        add_action('show_user_profile', [__CLASS__, 'render_user_fields']);
        add_action('edit_user_profile', [__CLASS__, 'render_user_fields']);
        add_action('edit_user_profile_update', [__CLASS__, 'save_user_fields']);
        add_action('personal_options_update', [__CLASS__, 'save_user_fields']);
    }

    public static function manage_cap(): bool {
        return current_user_can(STAGEKITWP_MEMBERS_Roles::CAP_MANAGE_MEMBERS) || current_user_can('manage_options');
    }

    private static function table(): string {
        global $wpdb;
        return $wpdb->prefix . 'stagekitwp_members_memberships';
    }

    private static function history_table(): string {
        global $wpdb;
        return $wpdb->prefix . 'stagekitwp_members_membership_history';
    }

    /** @return array|null Row as array, or null when the user has no record. */
    public static function get(int $user_id) {
        global $wpdb;
        if (!$user_id) {
            return null;
        }
        $row = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . self::table() . ' WHERE user_id = %d', $user_id), ARRAY_A);
        return $row ?: null;
    }

    /** Stored status; an active record past its expiry reads as lapsed. */
    public static function stored_status(int $user_id): string {
        $row = self::get($user_id);
        if (!$row) {
            return 'none';
        }
        $status = in_array($row['status'], self::STATUSES, true) ? $row['status'] : 'none';
        if ($status === 'active' && !empty($row['expiry_date']) && strtotime($row['expiry_date'] . ' UTC') < time()) {
            return 'lapsed';
        }
        return $status;
    }

    /**
     * Create or update a membership record and log the change.
     *
     * @param int   $user_id User ID.
     * @param array $args    status, level, start, expiry (Y-m-d H:i:s, UTC), source, external_ref, note.
     * @return bool
     */
    public static function set(int $user_id, array $args): bool {
        global $wpdb;
        if (!$user_id || !get_userdata($user_id)) {
            return false;
        }

        $old     = self::get($user_id);
        $old_st  = $old ? self::stored_status($user_id) : 'none';
        $status  = isset($args['status']) && in_array($args['status'], self::STATUSES, true) ? $args['status'] : ($old['status'] ?? 'none');
        $now     = current_time('mysql', true);
        $date    = static function ($v) {
            $t = $v ? strtotime((string) $v . ' UTC') : false;
            return $t ? gmdate('Y-m-d H:i:s', $t) : null;
        };

        $data = [
            'level'        => isset($args['level']) ? sanitize_key($args['level']) : ($old['level'] ?? ''),
            'status'       => $status,
            'start_date'   => array_key_exists('start', $args) ? $date($args['start']) : ($old['start_date'] ?? null),
            'expiry_date'  => array_key_exists('expiry', $args) ? $date($args['expiry']) : ($old['expiry_date'] ?? null),
            'source'       => isset($args['source']) ? sanitize_key($args['source']) : ($old['source'] ?? 'manual'),
            'external_ref' => isset($args['external_ref']) ? sanitize_text_field($args['external_ref']) : ($old['external_ref'] ?? ''),
            'updated_at'   => $now,
        ];

        if ($old) {
            $ok = $wpdb->update(self::table(), $data, ['user_id' => $user_id]);
        } else {
            $ok = $wpdb->insert(self::table(), $data + ['user_id' => $user_id, 'created_at' => $now]);
        }
        if ($ok === false) {
            return false;
        }

        $wpdb->insert(self::history_table(), [
            'user_id'      => $user_id,
            'old_status'   => $old_st,
            'new_status'   => $status,
            'source'       => $data['source'],
            'external_ref' => $data['external_ref'],
            'actor_id'     => get_current_user_id(),
            'note'         => isset($args['note']) ? mb_substr(sanitize_text_field($args['note']), 0, 255) : '',
            'created_at'   => $now,
        ]);

        if ($status === 'active' && $old_st !== 'active') {
            /** Fires when a user's membership becomes active. */
            do_action('stagekitwp_members_access_granted', $user_id, $data);
        } elseif ($old_st === 'active' && $status !== 'active') {
            /** Fires when a user's active membership ends or is cancelled. */
            do_action('stagekitwp_members_access_revoked', $user_id, $data);
        }
        return true;
    }

    /**
     * Payment plugin callback. $args: level, expiry, source, external_ref, note.
     */
    public static function on_payment_complete($user_id, $args = []) {
        $args = is_array($args) ? $args : [];
        $args['status'] = 'active';
        $args += ['start' => gmdate('Y-m-d H:i:s'), 'source' => 'payment'];
        self::set((int) $user_id, $args);
    }

    /* ------------------------------------------------------------------ */
    /*  User profile fields (Executives only)                              */
    /* ------------------------------------------------------------------ */

    public static function render_user_fields($user): void {
        if (!self::manage_cap()) {
            return;
        }
        $row    = self::get((int) $user->ID) ?: [];
        $status = self::stored_status((int) $user->ID);
        $expiry = !empty($row['expiry_date']) ? substr($row['expiry_date'], 0, 10) : '';
        wp_nonce_field('stagekitwp_members_membership_save', 'stagekitwp_members_membership_nonce');
        ?>
        <h2><?php esc_html_e('Membership', 'stagekitwp-members-area'); ?></h2>
        <table class="form-table" role="presentation">
            <tr>
                <th><label for="stagekitwp_members_ms_status"><?php esc_html_e('Status', 'stagekitwp-members-area'); ?></label></th>
                <td>
                    <select name="stagekitwp_members_ms_status" id="stagekitwp_members_ms_status">
                        <?php foreach (self::STATUSES as $s) : ?>
                            <option value="<?php echo esc_attr($s); ?>" <?php selected($status, $s); ?>><?php echo esc_html(ucfirst($s)); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($row['source'])) : ?>
                        <span class="description"><?php echo esc_html(sprintf(__('Source: %s', 'stagekitwp-members-area'), $row['source'])); ?></span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th><label for="stagekitwp_members_ms_expiry"><?php esc_html_e('Expires', 'stagekitwp-members-area'); ?></label></th>
                <td><input type="date" name="stagekitwp_members_ms_expiry" id="stagekitwp_members_ms_expiry" value="<?php echo esc_attr($expiry); ?>">
                    <span class="description"><?php esc_html_e('Leave blank for no expiry.', 'stagekitwp-members-area'); ?></span></td>
            </tr>
        </table>
        <?php
    }

    public static function save_user_fields($user_id): void {
        if (!isset($_POST['stagekitwp_members_membership_nonce'])
            || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['stagekitwp_members_membership_nonce'])), 'stagekitwp_members_membership_save')
            || !self::manage_cap()) {
            return;
        }
        $status = isset($_POST['stagekitwp_members_ms_status']) ? sanitize_key(wp_unslash($_POST['stagekitwp_members_ms_status'])) : 'none';
        $expiry = isset($_POST['stagekitwp_members_ms_expiry']) ? sanitize_text_field(wp_unslash($_POST['stagekitwp_members_ms_expiry'])) : '';
        $cur    = self::get((int) $user_id);

        // Skip when nothing changed so the history log stays meaningful.
        $cur_expiry = $cur && !empty($cur['expiry_date']) ? substr($cur['expiry_date'], 0, 10) : '';
        if ($status === self::stored_status((int) $user_id) && $expiry === $cur_expiry) {
            return;
        }
        $args = ['status' => $status, 'expiry' => $expiry ? $expiry . ' 23:59:59' : null, 'note' => 'Edited by user ' . get_current_user_id()];
        if ($status === 'active' && (!$cur || empty($cur['start_date']))) {
            $args['start'] = gmdate('Y-m-d H:i:s');
        }
        self::set((int) $user_id, $args);
    }
}
