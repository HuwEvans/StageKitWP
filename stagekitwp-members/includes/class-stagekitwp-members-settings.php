<?php

if (!defined('ABSPATH')) {
    exit;
}

class STAGEKITWP_MEMBERS_Settings {

    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_menu']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
    }

    public static function add_menu() {
        add_submenu_page(
            'stagekitwp',
            'Settings',
            'Settings',
            STAGEKITWP_MEMBERS_Roles::CAP_MANAGE_SETTINGS,
            'stagekitwp-ma-settings',
            [__CLASS__, 'render']
        );
    }

    public static function register_settings() {
        // Original
        register_setting('stagekitwp_members_settings_group', 'stagekitwp_members_email_enabled');
        register_setting('stagekitwp_members_settings_group', 'stagekitwp_members_delete_data_on_uninstall');
        // Login & Registration
        register_setting('stagekitwp_members_settings_group', 'stagekitwp_members_custom_login_enabled');
        register_setting('stagekitwp_members_settings_group', 'stagekitwp_members_login_page_id',          ['sanitize_callback' => 'absint']);
        register_setting('stagekitwp_members_settings_group', 'stagekitwp_members_frontend_only');
        register_setting('stagekitwp_members_settings_group', 'stagekitwp_members_open_reg_enabled');
        register_setting('stagekitwp_members_settings_group', 'stagekitwp_members_reg_page_id',            ['sanitize_callback' => 'absint']);
        register_setting('stagekitwp_members_settings_group', 'stagekitwp_members_login_redirect_page_id', ['sanitize_callback' => 'absint']);
        // Turnstile
        register_setting('stagekitwp_members_settings_group', 'stagekitwp_members_turnstile_enabled');
        register_setting('stagekitwp_members_settings_group', 'stagekitwp_members_turnstile_site_key',   ['sanitize_callback' => 'sanitize_text_field']);
        register_setting('stagekitwp_members_settings_group', 'stagekitwp_members_turnstile_secret_key', ['sanitize_callback' => 'sanitize_text_field']);
        // Nav Menu
        register_setting('stagekitwp_members_settings_group', 'stagekitwp_members_nav_auto_inject');
        register_setting('stagekitwp_members_settings_group', 'stagekitwp_members_nav_menu_location',   ['sanitize_callback' => 'sanitize_text_field']);
        register_setting('stagekitwp_members_settings_group', 'stagekitwp_members_page_profile',        ['sanitize_callback' => 'absint']);
        register_setting('stagekitwp_members_settings_group', 'stagekitwp_members_page_events',         ['sanitize_callback' => 'absint']);
        register_setting('stagekitwp_members_settings_group', 'stagekitwp_members_page_announcements',  ['sanitize_callback' => 'absint']);
        register_setting('stagekitwp_members_settings_group', 'stagekitwp_members_page_availability',   ['sanitize_callback' => 'absint']);
        register_setting('stagekitwp_members_settings_group', 'stagekitwp_members_page_messages',       ['sanitize_callback' => 'absint']);
        register_setting('stagekitwp_members_settings_group', 'stagekitwp_members_page_directory',      ['sanitize_callback' => 'absint']);
        register_setting('stagekitwp_members_settings_group', 'stagekitwp_members_page_dashboard',      ['sanitize_callback' => 'absint']);

        register_setting('stagekitwp_members_settings_group', 'stagekitwp_members_access_post_types', [
            'sanitize_callback' => static function ($v) {
                return is_array($v) ? array_values(array_filter(array_map('sanitize_key', $v))) : [];
            },
        ]);
        register_setting('stagekitwp_members_settings_group', 'stagekitwp_members_access_default_behavior', [
            'sanitize_callback' => static function ($v) {
                return in_array($v, ['login', 'message', '404'], true) ? $v : 'login';
            },
        ]);
        register_setting('stagekitwp_members_settings_group', 'stagekitwp_members_access_message', ['sanitize_callback' => 'sanitize_textarea_field']);
        register_setting('stagekitwp_members_settings_group', STAGEKITWP_MEMBERS_Volunteers::VISIBILITY_OPTION, [
            'sanitize_callback' => static function ($value) {
                return empty($value) ? 0 : 1;
            },
        ]);
    }

    private static function section_access(): void {
        $selected = class_exists('STAGEKITWP_MEMBERS_Access') ? STAGEKITWP_MEMBERS_Access::protected_types() : ['page', 'post'];
        $behavior = class_exists('STAGEKITWP_MEMBERS_Access') ? STAGEKITWP_MEMBERS_Access::default_behavior() : 'login';
        $message  = (string) get_option('stagekitwp_members_access_message', '');
        $types    = get_post_types(['public' => true], 'objects');
        unset($types['attachment']);
        ?>
        <h2 class="title">Access Control</h2>
        <p class="description">Adds a "Who can see this?" box to the selected content types and to menu items.</p>
        <input type="hidden" name="stagekitwp_members_access_post_types[]" value="">
        <table class="form-table">
            <tr>
                <th>Protected content types</th>
                <td>
                    <?php foreach ($types as $slug => $obj) : ?>
                        <label style="display:block">
                            <input type="checkbox" name="stagekitwp_members_access_post_types[]" value="<?php echo esc_attr($slug); ?>" <?php checked(in_array($slug, $selected, true)); ?>>
                            <?php echo esc_html($obj->labels->name); ?>
                        </label>
                    <?php endforeach; ?>
                </td>
            </tr>
            <tr>
                <th>When access is blocked</th>
                <td>
                    <select name="stagekitwp_members_access_default_behavior">
                        <option value="login" <?php selected($behavior, 'login'); ?>>Redirect logged-out visitors to login</option>
                        <option value="message" <?php selected($behavior, 'message'); ?>>Show a message</option>
                        <option value="404" <?php selected($behavior, '404'); ?>>Show a 404 page</option>
                    </select>
                    <p class="description">Logged-in users without access always see the message. Each item can override this.</p>
                </td>
            </tr>
            <tr>
                <th>Blocked message</th>
                <td><textarea name="stagekitwp_members_access_message" rows="3" class="large-text"><?php echo esc_textarea($message); ?></textarea></td>
            </tr>
        </table>
        <?php
    }

    /* --- page-select helper (echo) --- */
    private static function page_select(string $name, int $selected, array $pages): void {
        echo '<select name="' . esc_attr($name) . '">';
        echo '<option value="0">— select a page —</option>';
        foreach ($pages as $p) {
            printf('<option value="%d"%s>%s</option>',
                $p->ID, selected($selected, $p->ID, false), esc_html($p->post_title));
        }
        echo '</select>';
    }

    /* ------------------------------------------------------------------ */
    /*  Render – split into section helpers to stay under size limits      */
    /* ------------------------------------------------------------------ */

    public static function render() {
		$published_pages = get_pages(['post_status' => 'publish', 'sort_column' => 'post_title', 'number' => 200]);
        ?>
        <div class="wrap">
            <h1>Members Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('stagekitwp_members_settings_group');
                self::section_general();
				self::section_login($published_pages);
                self::section_turnstile();
				self::section_nav($published_pages);
                self::section_access();
                self::section_volunteers();
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    private static function section_volunteers(): void {
        $public = (bool) get_option(STAGEKITWP_MEMBERS_Volunteers::VISIBILITY_OPTION, 0);
        ?>
        <h2 class="title">Volunteer Signups</h2>
        <table class="form-table">
            <tr>
                <th>Open positions</th>
                <td>
                    <input type="hidden" name="<?php echo esc_attr(STAGEKITWP_MEMBERS_Volunteers::VISIBILITY_OPTION); ?>" value="0">
                    <label><input type="checkbox" name="<?php echo esc_attr(STAGEKITWP_MEMBERS_Volunteers::VISIBILITY_OPTION); ?>" value="1" <?php checked($public); ?>>
                        Show open positions and counts publicly (read-only)</label>
                    <p class="description">Signups always require a logged-in member. Events can override this setting individually.</p>
                </td>
            </tr>
        </table>
        <?php
    }

    /* ------------------------------------------------------------------ */
    private static function section_general(): void {
        $email_enabled = get_option('stagekitwp_members_email_enabled', 1);
        $purge         = get_option('stagekitwp_members_delete_data_on_uninstall', 0);
        ?>
        <h2 class="title">General</h2>
        <table class="form-table">
            <tr>
                <th>Email Notifications</th>
                <td><label>
                    <input type="checkbox" name="stagekitwp_members_email_enabled" value="1" <?php checked($email_enabled,1); ?>>
                    Enable email notifications
                </label></td>
            </tr>
            <tr>
                <th>Data on Uninstall</th>
                <td>
                    <label>
                        <input type="checkbox" name="stagekitwp_members_delete_data_on_uninstall" value="1" <?php checked($purge,1); ?>>
                        Delete all plugin data when the plugin is uninstalled
                    </label>
                    <p class="description" style="color:#a00;">
                        <strong>Warning:</strong> permanently removes all members-area data on uninstall.
                    </p>
                </td>
            </tr>
        </table>
        <?php
    }

    /* ------------------------------------------------------------------ */
    private static function section_login(array $pages): void {
        $custom_login  = get_option('stagekitwp_members_custom_login_enabled', 0);
        $login_page    = (int) get_option('stagekitwp_members_login_page_id', 0);
        $open_reg      = get_option('stagekitwp_members_open_reg_enabled', 0);
        $reg_page      = (int) get_option('stagekitwp_members_reg_page_id', 0);
        $redirect_page = (int) get_option('stagekitwp_members_login_redirect_page_id', 0);
        ?>
        <h2 class="title">Login &amp; Registration</h2>
        <p class="description">
            Replace <code>/wp-login.php</code> with custom pages that match your theme.
            Create a page, add the shortcode, then select it below.
        </p>
        <table class="form-table">
            <tr>
                <th>Custom Login Page</th>
                <td><label>
                    <input type="checkbox" name="stagekitwp_members_custom_login_enabled" value="1" <?php checked($custom_login,1); ?>>
                    Enable custom login page (redirects <code>/wp-login.php</code>)
                </label></td>
            </tr>
            <tr>
                <th>Login Page <code>[stagekitwp_members_login]</code></th>
                <td><?php self::page_select('stagekitwp_members_login_page_id', $login_page, $pages); ?></td>
            </tr>
            <tr>
                <th>After-Login Redirect</th>
                <td>
                    <?php self::page_select('stagekitwp_members_login_redirect_page_id', $redirect_page, $pages); ?>
                    <p class="description">Where to send users after a successful login. Defaults to the home page.</p>
                </td>
            </tr>
            <tr>
                <th>Front-End Only</th>
                <td>
                    <label>
                        <input type="checkbox" name="stagekitwp_members_frontend_only" value="1" <?php checked(get_option('stagekitwp_members_frontend_only', 1), 1); ?>>
                        Keep everyone except Administrators out of the WordPress dashboard
                    </label>
                    <p class="description">Non-administrators are sent to the front end after login, the admin bar is hidden, and <code>/wp-admin/</code> redirects to the front end.</p>
                </td>
            </tr>
            <tr>
                <th>Open Registration</th>
                <td>
                    <label>
                        <input type="checkbox" name="stagekitwp_members_open_reg_enabled" value="1" <?php checked($open_reg,1); ?>>
                        Allow anyone to register without an invitation
                    </label>
                    <p class="description">
                        When enabled a <em>Create an account</em> link appears on the login form.
                        New registrants receive the <strong>Member</strong> role automatically.
                    </p>
                </td>
            </tr>
            <tr>
                <th>Registration Page <code>[stagekitwp_members_open_register]</code></th>
                <td><?php self::page_select('stagekitwp_members_reg_page_id', $reg_page, $pages); ?></td>
            </tr>
        </table>
        <?php
    }

    /* ------------------------------------------------------------------ */
    private static function section_turnstile(): void {
        $ts_enabled = get_option('stagekitwp_members_turnstile_enabled', 0);
        $ts_site    = get_option('stagekitwp_members_turnstile_site_key', '');
        $ts_secret  = get_option('stagekitwp_members_turnstile_secret_key', '');
        ?>
        <h2 class="title">Cloudflare Turnstile</h2>
        <p class="description">
            Bot-protection widget for the login form. Get keys at
            <a href="https://dash.cloudflare.com/?to=/:account/turnstile" target="_blank" rel="noopener">
                dash.cloudflare.com → Turnstile
            </a>. Choose widget type <strong>Managed</strong>.
            Both keys must be filled in for Turnstile to activate. The site key must match the current domain; the widget is automatically disabled on local-only hosts such as <code>localhost</code>.
        </p>
        <table class="form-table">
            <tr>
                <th>Enable Turnstile</th>
                <td><label>
                    <input type="checkbox" name="stagekitwp_members_turnstile_enabled" value="1" <?php checked($ts_enabled,1); ?>>
                    Add Turnstile verification to the login form
                </label></td>
            </tr>
            <tr>
                <th>Site Key</th>
                <td>
                    <input type="text" name="stagekitwp_members_turnstile_site_key"
                           value="<?php echo esc_attr($ts_site); ?>" class="regular-text" autocomplete="off">
                    <p class="description">Public key used in the browser widget.</p>
                </td>
            </tr>
            <tr>
                <th>Secret Key</th>
                <td>
                    <input type="password" name="stagekitwp_members_turnstile_secret_key"
                           value="<?php echo esc_attr($ts_secret); ?>" class="regular-text" autocomplete="new-password">
                    <p class="description">Private key for server-side verification. Never share this.</p>
                </td>
            </tr>
        </table>
        <?php
    }

    /* ------------------------------------------------------------------ */
    private static function section_nav(array $pages): void {
        $opts = [
            ['stagekitwp_members_page_profile',       'Member Profile',     '[stagekitwp_members_profile]'],
            ['stagekitwp_members_page_events',        'Events',             '[stagekitwp_members_events]'],
            ['stagekitwp_members_page_announcements', 'Announcements',      '[stagekitwp_members_announcements]'],
            ['stagekitwp_members_page_availability',  'Availability',       '[stagekitwp_members_availability]'],
            ['stagekitwp_members_page_messages',      'Messages',           '[stagekitwp_members_conversations]'],
            ['stagekitwp_members_page_directory',     'Member Directory',   '[stagekitwp_members_directory]'],
            ['stagekitwp_members_page_dashboard',     'Producer Dashboard', '[stagekitwp_members_producer_dashboard]'],
        ];
        ?>
        <h2 class="title">Navigation Menu</h2>
        <p class="description">
			Members items are added to any nav menu via <strong>Appearance &rarr; Menus</strong>.
			Open the <em>Members</em> panel in the menu editor, tick the items you want
            (Log In, Log Out, My Profile, Events, Messages, etc.), and click <strong>Add to Menu</strong>.
            Each item shows or hides automatically based on the visitor&#8217;s login status and role &mdash;
            no configuration here is needed for that to work.
        </p>
        <p class="description" style="margin-top:.5em;">
            <a href="<?php echo esc_url(admin_url('nav-menus.php')); ?>">&rarr; Go to Appearance &rarr; Menus</a>
        </p>

        <h3>Page URL Overrides <span style="font-weight:400;font-size:.85em;">(optional)</span></h3>
        <p class="description">
            By default, menu item URLs are resolved by matching published page slugs
            (<code>/profile</code>, <code>/events</code>, etc.). Use these dropdowns only if your
            pages have non-standard slugs.
        </p>
        <table class="form-table">
            <?php foreach ($opts as [$option, $label, $shortcode]): ?>
            <tr>
                <th><?php echo esc_html($label); ?> <code><?php echo esc_html($shortcode); ?></code></th>
                <td><?php self::page_select($option, (int) get_option($option, 0), $pages); ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php
    }
}
