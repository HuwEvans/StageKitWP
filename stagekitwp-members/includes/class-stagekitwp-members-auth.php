<?php
/**
 * TM Members Area – Authentication & Registration
 *
 * Replaces the native WordPress login/register screens with custom pages
 * (configurable in Settings). Can be disabled to fall back to /wp-login.php.
 *
 * Settings options:
 *   stagekitwp_members_custom_login_enabled   – 0|1  use custom login page
 *   stagekitwp_members_login_page_id          – page ID containing [stagekitwp_members_login]
 *   stagekitwp_members_open_reg_enabled       – 0|1  allow public (open) registration
 *   stagekitwp_members_reg_page_id            – page ID containing [stagekitwp_members_open_register]
 *   stagekitwp_members_turnstile_enabled      – 0|1  add Turnstile to login form
 *   stagekitwp_members_turnstile_site_key     – Cloudflare site key
 *   stagekitwp_members_turnstile_secret_key   – Cloudflare secret key
 *   stagekitwp_members_login_redirect_page_id – page to send members after login
 */

if (!defined('ABSPATH')) exit;

class STAGEKITWP_MEMBERS_Auth {

    public static function init() {
        add_shortcode('stagekitwp_members_login',         [__CLASS__, 'shortcode_login']);
        add_shortcode('stagekitwp_members_open_register', [__CLASS__, 'shortcode_register']);
        add_action('login_init',             [__CLASS__, 'maybe_redirect_login']);
        add_action('login_form_register',    [__CLASS__, 'maybe_redirect_register']);
        add_action('init',                   [__CLASS__, 'handle_login_post']);
        add_action('init',                   [__CLASS__, 'handle_register_post']);
        add_action('wp_enqueue_scripts',     [__CLASS__, 'maybe_enqueue_turnstile']);
    }

    /* ------------------------------------------------------------------ */
    /*  Helpers                                                             */
    /* ------------------------------------------------------------------ */

    public static function custom_login_enabled(): bool {
        return (bool) get_option('stagekitwp_members_custom_login_enabled', 0);
    }

    public static function open_reg_enabled(): bool {
        return (bool) get_option('stagekitwp_members_open_reg_enabled', 0);
    }

    private static function turnstile_host_allowed(): bool {
        $host = wp_parse_url(home_url('/'), PHP_URL_HOST);

        if (!is_string($host) || '' === $host) {
            return false;
        }

        $host = strtolower($host);

        if (function_exists('wp_get_environment_type') && 'local' === wp_get_environment_type()) {
            return false;
        }

        if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            return false;
        }

        foreach (['.local', '.test', '.invalid'] as $suffix) {
            if (substr($host, -strlen($suffix)) === $suffix) {
                return false;
            }
        }

        return (bool) apply_filters('stagekitwp_members_turnstile_host_allowed', true, $host);
    }

    public static function turnstile_enabled(): bool {
        return (bool) get_option('stagekitwp_members_turnstile_enabled', 0)
            && '' !== trim((string) get_option('stagekitwp_members_turnstile_site_key', ''))
            && '' !== trim((string) get_option('stagekitwp_members_turnstile_secret_key', ''))
            && self::turnstile_host_allowed();
    }

    public static function login_url(): string {
        if (self::custom_login_enabled()) {
            $id = (int) get_option('stagekitwp_members_login_page_id', 0);
            if ($id) return (string) get_permalink($id);
        }
        return wp_login_url();
    }

    public static function register_url(): string {
        if (self::open_reg_enabled()) {
            $id = (int) get_option('stagekitwp_members_reg_page_id', 0);
            if ($id) return (string) get_permalink($id);
        }
        return wp_registration_url();
    }

    public static function logout_url(string $redirect = ''): string {
        if (!$redirect) $redirect = self::login_url();
        return wp_logout_url($redirect);
    }

    private static function after_login_url(): string {
        $id = (int) get_option('stagekitwp_members_login_redirect_page_id', 0);
        return $id ? (string) get_permalink($id) : home_url('/');
    }

    /* ------------------------------------------------------------------ */
    /*  Redirect wp-login.php                                               */
    /* ------------------------------------------------------------------ */

    public static function maybe_redirect_login() {
        $action = $_REQUEST['action'] ?? 'login';
        $bypass = ['logout', 'lostpassword', 'rp', 'resetpass', 'postpass', 'confirm_admin_email'];
        if (in_array($action, $bypass, true)) return;
        if (!self::custom_login_enabled()) return;

        $url = self::login_url();
        if ($url && $url !== wp_login_url()) {
            if (!empty($_GET['redirect_to'])) {
                $url = add_query_arg('redirect_to', rawurlencode($_GET['redirect_to']), $url);
            }
            wp_safe_redirect($url);
            exit;
        }
    }

    public static function maybe_redirect_register() {
        if (!self::custom_login_enabled()) return;
        if (self::open_reg_enabled()) {
            $url = self::register_url();
            if ($url) { wp_safe_redirect($url); exit; }
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Turnstile enqueue                                                   */
    /* ------------------------------------------------------------------ */

    public static function maybe_enqueue_turnstile() {
        if (!self::turnstile_enabled()) return;
        $login_id = (int) get_option('stagekitwp_members_login_page_id', 0);
        if ($login_id && is_page($login_id)) {
            wp_enqueue_script('cf-turnstile',
                'https://challenges.cloudflare.com/turnstile/v0/api.js',
                [], null, true);
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Turnstile verify                                                    */
    /* ------------------------------------------------------------------ */

    private static function verify_turnstile(): bool {
        if (!self::turnstile_enabled()) return true;
        $token  = sanitize_text_field($_POST['cf-turnstile-response'] ?? '');
        $secret = trim((string) get_option('stagekitwp_members_turnstile_secret_key', ''));
        if ('' === $token) return false;
        $resp = wp_remote_post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'body'    => ['secret' => $secret, 'response' => $token, 'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''],
            'timeout' => 10,
        ]);
        if (is_wp_error($resp)) return false;
        $data = json_decode(wp_remote_retrieve_body($resp), true);
        return !empty($data['success']);
    }

    /* ------------------------------------------------------------------ */
    /*  Handle login POST                                                   */
    /* ------------------------------------------------------------------ */

    public static function handle_login_post() {
        if (!isset($_POST['stagekitwp_members_do_login'])) return;
        if (!wp_verify_nonce($_POST['stagekitwp_members_login_nonce'] ?? '', 'stagekitwp_members_login')) {
            self::set_auth_error(__('Security check failed. Please try again.', 'stagekitwp-members-area')); return;
        }
        if (!self::verify_turnstile()) {
            self::set_auth_error(__('Bot verification failed. Please complete the challenge.', 'stagekitwp-members-area')); return;
        }
        $user = wp_signon([
            'user_login'    => sanitize_user($_POST['log'] ?? ''),
            'user_password' => $_POST['pwd'] ?? '',
            'remember'      => !empty($_POST['rememberme']),
        ], false);
        if (is_wp_error($user)) {
            self::set_auth_error(__('Incorrect username or password.', 'stagekitwp-members-area')); return;
        }
        $redirect = !empty($_POST['redirect_to']) ? wp_sanitize_redirect($_POST['redirect_to']) : self::after_login_url();
        wp_safe_redirect($redirect);
        exit;
    }

    /* ------------------------------------------------------------------ */
    /*  Handle open-registration POST                                       */
    /* ------------------------------------------------------------------ */

    public static function handle_register_post() {
        if (!isset($_POST['stagekitwp_members_do_register'])) return;
        if (!self::open_reg_enabled()) {
            self::set_reg_error(__('Registration is currently closed.', 'stagekitwp-members-area')); return;
        }
        if (!wp_verify_nonce($_POST['stagekitwp_members_reg_nonce'] ?? '', 'stagekitwp_members_open_register')) {
            self::set_reg_error(__('Security check failed. Please try again.', 'stagekitwp-members-area')); return;
        }
        $username  = sanitize_user($_POST['user_login'] ?? '');
        $email     = sanitize_email($_POST['user_email'] ?? '');
        $pass      = $_POST['pass1'] ?? '';
        $pass2     = $_POST['pass2'] ?? '';
        $firstname = sanitize_text_field($_POST['first_name'] ?? '');
        $lastname  = sanitize_text_field($_POST['last_name'] ?? '');

        if ('' === $username)         { self::set_reg_error(__('Please enter a username.', 'stagekitwp-members-area')); return; }
        if (!is_email($email))        { self::set_reg_error(__('Please enter a valid email address.', 'stagekitwp-members-area')); return; }
        if (strlen($pass) < 8)        { self::set_reg_error(__('Password must be at least 8 characters.', 'stagekitwp-members-area')); return; }
        if ($pass !== $pass2)         { self::set_reg_error(__('Passwords do not match.', 'stagekitwp-members-area')); return; }
        if (username_exists($username)){ self::set_reg_error(__('That username is already taken.', 'stagekitwp-members-area')); return; }
        if (email_exists($email))     { self::set_reg_error(__('An account with that email already exists.', 'stagekitwp-members-area')); return; }

        $user_id = wp_create_user($username, $pass, $email);
        if (is_wp_error($user_id)) { self::set_reg_error($user_id->get_error_message()); return; }

        $user = new WP_User($user_id);
        $user->set_role('stagekitwp_member');
        wp_update_user(['ID' => $user_id, 'first_name' => $firstname, 'last_name' => $lastname]);
        wp_new_user_notification($user_id, null, 'admin');
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);

        wp_safe_redirect(add_query_arg('stagekitwp_registered', '1', self::after_login_url()));
        exit;
    }

    /* ------------------------------------------------------------------ */
    /*  Transient-based error messaging                                     */
    /* ------------------------------------------------------------------ */

    private static function set_auth_error(string $msg) {
        set_transient('stagekitwp_members_login_error_' . md5($_SERVER['REMOTE_ADDR'] ?? ''), $msg, 60);
    }
    public static function get_auth_error(): string {
        $key = 'stagekitwp_members_login_error_' . md5($_SERVER['REMOTE_ADDR'] ?? '');
        $msg = (string) get_transient($key);
        if ($msg) delete_transient($key);
        return $msg;
    }
    private static function set_reg_error(string $msg) {
        set_transient('stagekitwp_members_reg_error_' . md5($_SERVER['REMOTE_ADDR'] ?? ''), $msg, 60);
    }
    public static function get_reg_error(): string {
        $key = 'stagekitwp_members_reg_error_' . md5($_SERVER['REMOTE_ADDR'] ?? '');
        $msg = (string) get_transient($key);
        if ($msg) delete_transient($key);
        return $msg;
    }

    /* ------------------------------------------------------------------ */
    /*  Shortcode placeholders – filled by Edit below                       */
    /* ------------------------------------------------------------------ */

    public static function shortcode_login(): string {
        if (is_user_logged_in()) {
            $name = esc_html(wp_get_current_user()->display_name);
            return sprintf(
                '<div class="stagekitwp-ma-auth-box stagekitwp-ma-already-in"><p>%s</p><p><a href="%s" class="stagekitwp-ma-btn">%s</a></p></div>',
                sprintf(esc_html__('You are already logged in as %s.', 'stagekitwp-members-area'), '<strong>' . $name . '</strong>'),
                esc_url(self::logout_url()),
                esc_html__('Log Out', 'stagekitwp-members-area')
            );
        }

        $error       = self::get_auth_error();
        $redirect_to = esc_attr($_GET['redirect_to'] ?? '');
        $turnstile   = self::turnstile_enabled();
        $site_key    = esc_attr((string) get_option('stagekitwp_members_turnstile_site_key', ''));
        $reg_url     = self::open_reg_enabled() ? self::register_url() : '';

        ob_start(); ?>
        <div class="stagekitwp-ma-auth-box stagekitwp-ma-login-box">
            <?php if ($error): ?>
                <div class="stagekitwp-ma-auth-error"><?php echo esc_html($error); ?></div>
            <?php endif; ?>
            <form method="post" class="stagekitwp-ma-login-form" autocomplete="on">
                <?php wp_nonce_field('stagekitwp_members_login', 'stagekitwp_members_login_nonce'); ?>
                <input type="hidden" name="redirect_to" value="<?php echo $redirect_to; ?>">
                <div class="stagekitwp-ma-field">
                    <label for="stagekitwp-ma-log"><?php esc_html_e('Username or Email', 'stagekitwp-members-area'); ?></label>
                    <input type="text" id="stagekitwp-ma-log" name="log" required autocomplete="username"
                           value="<?php echo esc_attr($_POST['log'] ?? ''); ?>">
                </div>
                <div class="stagekitwp-ma-field">
                    <label for="stagekitwp-ma-pwd"><?php esc_html_e('Password', 'stagekitwp-members-area'); ?></label>
                    <input type="password" id="stagekitwp-ma-pwd" name="pwd" required autocomplete="current-password">
                </div>
                <div class="stagekitwp-ma-field stagekitwp-ma-field-inline">
                    <input type="checkbox" id="stagekitwp-ma-remember" name="rememberme" value="forever">
                    <label for="stagekitwp-ma-remember"><?php esc_html_e('Remember me', 'stagekitwp-members-area'); ?></label>
                </div>
                <?php if ($turnstile): ?>
                    <div class="stagekitwp-ma-field">
                        <div class="cf-turnstile" data-sitekey="<?php echo $site_key; ?>"></div>
                    </div>
                <?php endif; ?>
                <div class="stagekitwp-ma-field">
                    <button type="submit" name="stagekitwp_members_do_login" value="1" class="stagekitwp-ma-btn stagekitwp-ma-btn-primary">
                        <?php esc_html_e('Log In', 'stagekitwp-members-area'); ?>
                    </button>
                </div>
                <div class="stagekitwp-ma-auth-links">
                    <a href="<?php echo esc_url(wp_lostpassword_url()); ?>"><?php esc_html_e('Forgot your password?', 'stagekitwp-members-area'); ?></a>
                    <?php if ($reg_url): ?>
                        <span class="stagekitwp-ma-auth-sep">·</span>
                        <a href="<?php echo esc_url($reg_url); ?>"><?php esc_html_e('Create an account', 'stagekitwp-members-area'); ?></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <?php return ob_get_clean();
    }
    public static function shortcode_register(): string {
        if (!self::open_reg_enabled()) {
            return '<div class="stagekitwp-ma-auth-box"><p>' . esc_html__('Registration is currently closed.', 'stagekitwp-members-area') . '</p></div>';
        }
        if (is_user_logged_in()) {
            return '<div class="stagekitwp-ma-auth-box"><p>' . esc_html__('You are already registered and logged in.', 'stagekitwp-members-area') . '</p></div>';
        }
        if (!empty($_GET['stagekitwp_registered'])) {
            return '<div class="stagekitwp-ma-auth-box stagekitwp-ma-auth-success"><p>' . esc_html__('Welcome! Your account has been created and you are now logged in.', 'stagekitwp-members-area') . '</p></div>';
        }

        $error     = self::get_reg_error();
        $v         = array_map('esc_attr', [
            'user_login' => $_POST['user_login'] ?? '',
            'user_email' => $_POST['user_email'] ?? '',
            'first_name' => $_POST['first_name'] ?? '',
            'last_name'  => $_POST['last_name']  ?? '',
        ]);
        $login_url = self::login_url();

        ob_start(); ?>
        <div class="stagekitwp-ma-auth-box stagekitwp-ma-register-box">
            <?php if ($error): ?>
                <div class="stagekitwp-ma-auth-error"><?php echo esc_html($error); ?></div>
            <?php endif; ?>
            <form method="post" class="stagekitwp-ma-register-form" autocomplete="on">
                <?php wp_nonce_field('stagekitwp_members_open_register', 'stagekitwp_members_reg_nonce'); ?>
                <div class="stagekitwp-ma-field-row">
                    <div class="stagekitwp-ma-field">
                        <label for="stagekitwp-ma-first"><?php esc_html_e('First Name', 'stagekitwp-members-area'); ?></label>
                        <input type="text" id="stagekitwp-ma-first" name="first_name" value="<?php echo $v['first_name']; ?>" autocomplete="given-name">
                    </div>
                    <div class="stagekitwp-ma-field">
                        <label for="stagekitwp-ma-last"><?php esc_html_e('Last Name', 'stagekitwp-members-area'); ?></label>
                        <input type="text" id="stagekitwp-ma-last" name="last_name" value="<?php echo $v['last_name']; ?>" autocomplete="family-name">
                    </div>
                </div>
                <div class="stagekitwp-ma-field">
                    <label for="stagekitwp-ma-ulogin"><?php esc_html_e('Username', 'stagekitwp-members-area'); ?></label>
                    <input type="text" id="stagekitwp-ma-ulogin" name="user_login" required value="<?php echo $v['user_login']; ?>" autocomplete="username">
                </div>
                <div class="stagekitwp-ma-field">
                    <label for="stagekitwp-ma-email"><?php esc_html_e('Email Address', 'stagekitwp-members-area'); ?></label>
                    <input type="email" id="stagekitwp-ma-email" name="user_email" required value="<?php echo $v['user_email']; ?>" autocomplete="email">
                </div>
                <div class="stagekitwp-ma-field">
                    <label for="stagekitwp-ma-pass1"><?php esc_html_e('Password', 'stagekitwp-members-area'); ?></label>
                    <input type="password" id="stagekitwp-ma-pass1" name="pass1" required minlength="8" autocomplete="new-password">
                    <p class="stagekitwp-ma-field-hint"><?php esc_html_e('At least 8 characters.', 'stagekitwp-members-area'); ?></p>
                </div>
                <div class="stagekitwp-ma-field">
                    <label for="stagekitwp-ma-pass2"><?php esc_html_e('Confirm Password', 'stagekitwp-members-area'); ?></label>
                    <input type="password" id="stagekitwp-ma-pass2" name="pass2" required minlength="8" autocomplete="new-password">
                </div>
                <div class="stagekitwp-ma-field">
                    <button type="submit" name="stagekitwp_members_do_register" value="1" class="stagekitwp-ma-btn stagekitwp-ma-btn-primary">
                        <?php esc_html_e('Create Account', 'stagekitwp-members-area'); ?>
                    </button>
                </div>
                <?php if ($login_url): ?>
                    <div class="stagekitwp-ma-auth-links">
                        <?php esc_html_e('Already have an account?', 'stagekitwp-members-area'); ?>
                        <a href="<?php echo esc_url($login_url); ?>"><?php esc_html_e('Log in', 'stagekitwp-members-area'); ?></a>
                    </div>
                <?php endif; ?>
            </form>
        </div>
        <?php return ob_get_clean();
    }
}
