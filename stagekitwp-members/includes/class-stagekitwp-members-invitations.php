<?php
/**
 * Invitation lifecycle: create -> email -> register -> accepted (or revoked).
 *
 * Producers/Executives create token invitations. The recipient opens a link
 * to a page containing [stagekitwp_members_register], which validates the token, lets them
 * set a password, creates a WordPress user with the Member role, and marks the
 * invitation accepted. Tokens expire after 7 days.
 */

if (!defined('ABSPATH')) {
    exit;
}

class STAGEKITWP_MEMBERS_Invitations {

    const STATUS_PENDING  = 'pending';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_REVOKED  = 'revoked';

    public static function init() {
        // Table creation is owned by STAGEKITWP_MEMBERS_DB (runs on plugins_loaded).
        add_shortcode('stagekitwp_members_register', [__CLASS__, 'render_registration']);
    }

    private static function table() {
        global $wpdb;
        return $wpdb->prefix . 'stagekitwp_invitations';
    }

    /**
     * Create + email an invitation. Returns the token, or WP_Error.
     */
    public static function create_invite($email) {

        global $wpdb;

        $email = sanitize_email($email);

        if (!is_email($email)) {
            return new WP_Error('invalid_email', 'Please provide a valid email address.');
        }

        if (email_exists($email)) {
            return new WP_Error('user_exists', 'A user with that email already exists.');
        }

        $token   = wp_generate_password(32, false);
        $expires = date('Y-m-d H:i:s', strtotime('+7 days'));

        $wpdb->insert(
            self::table(),
            [
                'email'      => $email,
                'token'      => $token,
                'status'     => self::STATUS_PENDING,
                'expires'    => $expires,
                'created_at' => current_time('mysql'),
            ]
        );

        self::send_invite_email($email, $token);

        return $token;
    }

    /**
     * Send (or resend) the invitation email.
     */
    public static function send_invite_email($email, $token) {

        $link = add_query_arg('stagekitwp_token', rawurlencode($token), self::register_url());

        $subject = sprintf('You are invited to join %s', get_bloginfo('name'));
        $message =
            "You have been invited to join " . get_bloginfo('name') . ".\n\n" .
            "Complete your registration here:\n" . esc_url_raw($link) . "\n\n" .
            "This link expires in 7 days.";

        if (class_exists('STAGEKITWP_MEMBERS_Health')) {
            return STAGEKITWP_MEMBERS_Health::send_email($email, $subject, $message);
        }

        return wp_mail($email, $subject, $message);
    }

    /**
     * URL of the page that hosts the [stagekitwp_members_register] shortcode.
     * Filterable so sites can point it at any page.
     */
    public static function register_url() {
        $page = get_page_by_path('register');
        $url  = $page ? get_permalink($page) : home_url('/register/');
        return apply_filters('stagekitwp_members_register_url', $url);
    }

    /**
     * Fetch an invitation row by token.
     */
    public static function get_by_token($token) {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM " . self::table() . " WHERE token = %s", $token)
        );
    }

    /**
     * Validate a token. Returns the invite row or a WP_Error.
     */
    public static function validate_token($token) {

        $invite = self::get_by_token($token);

        if (!$invite) {
            return new WP_Error('invalid', 'This invitation link is not valid.');
        }
        if ($invite->status === self::STATUS_ACCEPTED) {
            return new WP_Error('used', 'This invitation has already been used.');
        }
        if ($invite->status === self::STATUS_REVOKED) {
            return new WP_Error('revoked', 'This invitation has been revoked.');
        }
        if (strtotime($invite->expires) < time()) {
            return new WP_Error('expired', 'This invitation has expired.');
        }

        return $invite;
    }

    /**
     * Mark an invitation accepted.
     */
    public static function mark_accepted($id) {
        global $wpdb;
        $wpdb->update(self::table(), ['status' => self::STATUS_ACCEPTED], ['id' => (int) $id]);
    }

    /**
     * Revoke an invitation (admin action).
     */
    public static function revoke($id) {
        global $wpdb;
        $wpdb->update(self::table(), ['status' => self::STATUS_REVOKED], ['id' => (int) $id]);
    }

    /**
     * Resend a pending invitation email.
     */
    public static function resend($id) {
        global $wpdb;
        $invite = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM " . self::table() . " WHERE id = %d", (int) $id)
        );
        if (!$invite || $invite->status !== self::STATUS_PENDING) {
            return false;
        }
        return self::send_invite_email($invite->email, $invite->token);
    }

    /**
     * List invitations, newest first.
     */
    public static function all($limit = 200) {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM " . self::table() . " ORDER BY created_at DESC LIMIT %d", (int) $limit)
        );
    }

    /**
     * Front-end registration form + handler.
     */
    public static function render_registration($atts = []) {
        return self::registration_form();
    }

    private static function registration_form() {

        if (is_user_logged_in()) {
            return '<p>You are already registered and logged in.</p>';
        }

        // Token comes from the email link (stagekitwp_token) or the submitted form.
        $token = '';
        if (isset($_POST['stagekitwp_members_reg_token'])) {
            $token = sanitize_text_field(wp_unslash($_POST['stagekitwp_members_reg_token']));
        } elseif (isset($_GET['stagekitwp_token'])) {
            $token = sanitize_text_field(wp_unslash($_GET['stagekitwp_token']));
        }

        if ($token === '') {
            return '<p>No invitation token provided. Please use the link from your invitation email.</p>';
        }

        $invite = self::validate_token($token);
        if (is_wp_error($invite)) {
            return '<p>' . esc_html($invite->get_error_message()) . '</p>';
        }

        $error   = '';
        $success = false;

        // Handle submission.
        if (
            isset($_POST['stagekitwp_members_register_submit']) &&
            isset($_POST['stagekitwp_members_register_nonce']) &&
            wp_verify_nonce($_POST['stagekitwp_members_register_nonce'], 'stagekitwp_members_register')
        ) {
            $result = self::process_registration($invite);
            if (is_wp_error($result)) {
                $error = $result->get_error_message();
            } else {
                $success = true;
            }
        }

        if ($success) {
            $login = wp_login_url();
            return '<div class="stagekitwp-ma-register-success"><p>✅ Your account has been created. ' .
                '<a href="' . esc_url($login) . '">Log in</a> to complete your profile.</p></div>';
        }

        ob_start();
        ?>
        <form method="post" class="stagekitwp-ma-register-form">

            <?php wp_nonce_field('stagekitwp_members_register', 'stagekitwp_members_register_nonce'); ?>
            <input type="hidden" name="stagekitwp_members_reg_token" value="<?php echo esc_attr($token); ?>">

            <?php if ($error): ?>
                <p class="stagekitwp-ma-register-error" style="color:#b32d2e;"><?php echo esc_html($error); ?></p>
            <?php endif; ?>

            <p>
                <label>Email<br>
                    <input type="email" value="<?php echo esc_attr($invite->email); ?>" disabled>
                </label>
            </p>

            <p>
                <label>Username<br>
                    <input type="text" name="stagekitwp_members_username" required
                        value="<?php echo isset($_POST['stagekitwp_members_username']) ? esc_attr(wp_unslash($_POST['stagekitwp_members_username'])) : ''; ?>">
                </label>
            </p>

            <p>
                <label>Password<br>
                    <input type="password" name="stagekitwp_members_password" required autocomplete="new-password">
                </label>
            </p>

            <p>
                <label>Confirm Password<br>
                    <input type="password" name="stagekitwp_members_password_confirm" required autocomplete="new-password">
                </label>
            </p>

            <p>
                <button type="submit" name="stagekitwp_members_register_submit">Create Account</button>
            </p>

        </form>
        <?php
        return ob_get_clean();
    }

    /**
     * Validate input and create the Member user. Returns user ID or WP_Error.
     */
    private static function process_registration($invite) {

        $username = isset($_POST['stagekitwp_members_username']) ? sanitize_user(wp_unslash($_POST['stagekitwp_members_username'])) : '';
        $password = isset($_POST['stagekitwp_members_password']) ? (string) $_POST['stagekitwp_members_password'] : '';
        $confirm  = isset($_POST['stagekitwp_members_password_confirm']) ? (string) $_POST['stagekitwp_members_password_confirm'] : '';

        if ($username === '' || !validate_username($username)) {
            return new WP_Error('bad_username', 'Please choose a valid username.');
        }
        if (username_exists($username)) {
            return new WP_Error('username_taken', 'That username is already taken.');
        }
        if (strlen($password) < 8) {
            return new WP_Error('weak_password', 'Password must be at least 8 characters.');
        }
        if ($password !== $confirm) {
            return new WP_Error('password_mismatch', 'Passwords do not match.');
        }

        // Guard against a race where the email was registered meanwhile.
        if (email_exists($invite->email)) {
            return new WP_Error('user_exists', 'A user with that email already exists.');
        }

        $user_id = wp_insert_user([
            'user_login' => $username,
            'user_pass'  => $password,
            'user_email' => $invite->email,
            'role'       => 'stagekitwp_member',
        ]);

        if (is_wp_error($user_id)) {
            return $user_id;
        }

        self::mark_accepted($invite->id);

        do_action('stagekitwp_members_member_registered', $user_id, $invite);

        return $user_id;
    }
}
