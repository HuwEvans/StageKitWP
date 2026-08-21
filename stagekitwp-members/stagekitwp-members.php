<?php
/**
 * Plugin Name: StageKitWP - Members
 * Plugin URI:  https://github.com/stagekitwp/stagekitwp-members
 * Description: Full membership system for theatre groups — roles &amp; invitations, member directory, private messaging, events with RSVP &amp; attendance, cast availability, bulk email with a queue, and per-member notification preferences. Integrates with StageKitWP.
 * Version:     3.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author:      Huw Evans
 * Text Domain: stagekitwp-members
 * License:     GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class STAGEKITWP_Member_Area {

    private static $instance = null;

    public static function instance() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->define_constants();

        // HARD REQUIREMENT: StageKitWP must be active
        if (!class_exists('StageKitWP_Core')) {
            add_action('admin_notices', [$this, 'missing_dependency_notice']);
            return;
        }

        $this->includes();
        $this->init_hooks();
    }

    public function missing_dependency_notice() {
        if (!current_user_can('activate_plugins')) return;

        echo '<div class="notice notice-error"><p><strong>Members:</strong> Requires StageKitWP Core to be installed and active.</p></div>';
    }

    private function define_constants() {
        define('STAGEKITWPMA_PATH', plugin_dir_path(__FILE__));
        define('STAGEKITWPMA_URL', plugin_dir_url(__FILE__));
        define('STAGEKITWPMA_VERSION', '2.1.4');

    }

    /**
     * Load class files using new naming convention
     */
    private function includes() {

        $files = [
            'class-stagekitwp-members-db.php',
            'class-stagekitwp-members-roles.php',
            'class-stagekitwp-members-notifications.php',
            'class-stagekitwp-members-profile.php',
            'class-stagekitwp-members-taxonomy.php',
            'class-stagekitwp-members-search.php',
            'class-stagekitwp-members-export.php',
            'class-stagekitwp-members-directory.php',
            'class-stagekitwp-members-rsvp.php',
            'class-stagekitwp-members-events-frontend.php',
            'class-stagekitwp-members-availability.php',
            'class-stagekitwp-members-availability-frontend.php',
            'class-stagekitwp-members-announcements.php',
            'class-stagekitwp-members-ical.php',
            'class-stagekitwp-members-rest.php',
            'class-stagekitwp-members-events.php',
            'class-stagekitwp-members-invitations.php',
            'class-stagekitwp-members-invitations-admin.php',
            'class-stagekitwp-members-messaging.php',
            'class-stagekitwp-members-admin.php',
			'class-stagekitwp-members-health.php',
			'class-stagekitwp-members-members.php',
            'class-stagekitwp-members-settings.php',
			'class-stagekitwp-members-email-log.php',
			'class-stagekitwp-members-email-templates.php',
			'class-stagekitwp-members-email-helper.php',
			'class-stagekitwp-members-email-queue.php',
			'class-stagekitwp-members-email-queue-admin.php',
			'class-stagekitwp-members-auth.php',
			'class-stagekitwp-members-nav-menu.php',
        ];

        foreach ($files as $file) {
            $path = STAGEKITWPMA_PATH . 'includes/' . $file;

            if (file_exists($path)) {
                require_once $path;
            } else {
                error_log('TM Members Area missing file: ' . $path);
            }
        }
    }

    private function init_hooks() {

        // Load translations so the declared text domain is usable.
        add_action('init', function () {
            load_plugin_textdomain(
                'stagekitwp-members-area',
                false,
                dirname(plugin_basename(STAGEKITWPMA_PATH . 'stagekitwp-members-area.php')) . '/languages'
            );
        });

        // Defensive checks in case a class fails to load
        if (class_exists('STAGEKITWP_MEMBERS_DB')) STAGEKITWP_MEMBERS_DB::init();
        if (class_exists('STAGEKITWP_MEMBERS_Roles')) STAGEKITWP_MEMBERS_Roles::init();
        if (class_exists('STAGEKITWP_MEMBERS_Notifications')) STAGEKITWP_MEMBERS_Notifications::init();
        if (class_exists('STAGEKITWP_MEMBERS_Profile')) STAGEKITWP_MEMBERS_Profile::init();
        if (class_exists('STAGEKITWP_MEMBERS_Taxonomy')) STAGEKITWP_MEMBERS_Taxonomy::init();
        if (class_exists('STAGEKITWP_MEMBERS_Search')) STAGEKITWP_MEMBERS_Search::init();
        if (class_exists('STAGEKITWP_MEMBERS_Export')) STAGEKITWP_MEMBERS_Export::init();
        if (class_exists('STAGEKITWP_MEMBERS_Directory')) STAGEKITWP_MEMBERS_Directory::init();
        if (class_exists('STAGEKITWP_MEMBERS_RSVP')) STAGEKITWP_MEMBERS_RSVP::init();
        if (class_exists('STAGEKITWP_MEMBERS_Events_Frontend')) STAGEKITWP_MEMBERS_Events_Frontend::init();
        if (class_exists('STAGEKITWP_MEMBERS_Availability_Frontend')) STAGEKITWP_MEMBERS_Availability_Frontend::init();
        if (class_exists('STAGEKITWP_MEMBERS_Announcements')) STAGEKITWP_MEMBERS_Announcements::init();
        if (class_exists('STAGEKITWP_MEMBERS_ICal')) STAGEKITWP_MEMBERS_ICal::init();
        if (class_exists('STAGEKITWP_MEMBERS_REST')) STAGEKITWP_MEMBERS_REST::init();
        if (class_exists('STAGEKITWP_MEMBERS_Events')) STAGEKITWP_MEMBERS_Events::init();
        if (class_exists('STAGEKITWP_MEMBERS_Invitations')) STAGEKITWP_MEMBERS_Invitations::init();
        if (class_exists('STAGEKITWP_MEMBERS_Invitations_Admin')) STAGEKITWP_MEMBERS_Invitations_Admin::init();
        if (class_exists('STAGEKITWP_MEMBERS_Messaging')) STAGEKITWP_MEMBERS_Messaging::init();
		if (class_exists('STAGEKITWP_MEMBERS_Admin')) STAGEKITWP_MEMBERS_Admin::init();
		if (class_exists('STAGEKITWP_MEMBERS_Health')) STAGEKITWP_MEMBERS_Health::init();
		if (class_exists('STAGEKITWP_MEMBERS_Members')) STAGEKITWP_MEMBERS_Members::init();
		if (class_exists('STAGEKITWP_MEMBERS_Settings')) STAGEKITWP_MEMBERS_Settings::init();
		if (class_exists('STAGEKITWP_MEMBERS_Email_Log')) STAGEKITWP_MEMBERS_Email_Log::init();
		if (class_exists('STAGEKITWP_MEMBERS_Email_Templates')) STAGEKITWP_MEMBERS_Email_Templates::init();
		if (class_exists('STAGEKITWP_MEMBERS_Email_Helper')) STAGEKITWP_MEMBERS_Email_Helper::init();
		if (class_exists('STAGEKITWP_MEMBERS_Email_Queue')) STAGEKITWP_MEMBERS_Email_Queue::init();
		if (class_exists('STAGEKITWP_MEMBERS_Email_Queue_Admin')) STAGEKITWP_MEMBERS_Email_Queue_Admin::init();
		if (class_exists('STAGEKITWP_MEMBERS_Auth')) STAGEKITWP_MEMBERS_Auth::init();
		if (class_exists('STAGEKITWP_MEMBERS_Nav_Menu')) STAGEKITWP_MEMBERS_Nav_Menu::init();

		// Enqueue auth CSS on the frontend.
		add_action('wp_enqueue_scripts', function() {
			wp_enqueue_style('stagekitwp-ma-auth', STAGEKITWPMA_URL . 'assets/css/auth.css', [], STAGEKITWPMA_VERSION);
		});
    }
	
	
}

/**
 * Bootstrap plugin
 */
function STAGEKITWP_Member_Area() {
    return STAGEKITWP_Member_Area::instance();
}

if ( ! function_exists( 'stagekitwp_members' ) ) {
    /**
     * StageKitWP alias bootstrap for members plugin.
     *
     * @return STAGEKITWP_Member_Area
     */
    function stagekitwp_members() {
        return STAGEKITWP_Member_Area::instance();
    }
}

function stagekitwp_members_bootstrap() {
    STAGEKITWP_Member_Area();
}

if ( did_action( 'plugins_loaded' ) ) {
    stagekitwp_members_bootstrap();
} else {
    add_action( 'plugins_loaded', 'stagekitwp_members_bootstrap', 20 );
}


/**
 * Activation: run DB migrations directly (bypasses the dependency guard in
 * the constructor so tables are created even if StageKitWP loads later).
 */
register_activation_hook(__FILE__, function() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-stagekitwp-members-db.php';
    STAGEKITWP_MEMBERS_DB::migrate();
    update_option(STAGEKITWP_MEMBERS_DB::OPTION_KEY, STAGEKITWP_MEMBERS_DB::DB_VERSION);

    // Register membership roles & capabilities.
    require_once plugin_dir_path(__FILE__) . 'includes/class-stagekitwp-members-roles.php';
    STAGEKITWP_MEMBERS_Roles::register_roles();
    update_option(STAGEKITWP_MEMBERS_Roles::OPTION_KEY, STAGEKITWP_MEMBERS_Roles::CAPS_VERSION);

    // Register the iCal rewrite, then flush so /events.ics resolves.
    require_once plugin_dir_path(__FILE__) . 'includes/class-stagekitwp-members-ical.php';
    STAGEKITWP_MEMBERS_ICal::add_rewrite();
    flush_rewrite_rules();
});

/**
 * Deactivation: remove roles/caps and unschedule the queue cron.
 */
register_deactivation_hook(__FILE__, function() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-stagekitwp-members-roles.php';
    STAGEKITWP_MEMBERS_Roles::remove_roles();

    require_once plugin_dir_path(__FILE__) . 'includes/class-stagekitwp-members-email-queue.php';
    STAGEKITWP_MEMBERS_Email_Queue::clear_cron();

    flush_rewrite_rules();
});

