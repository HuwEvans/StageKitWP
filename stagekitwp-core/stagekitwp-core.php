<?php
/**
 * Plugin Name: StageKitWP Core
 * Plugin URI: https://github.com/HuwEvans/StageKitWP
 * Description: Manage stage-production content including board members, shows, and more.
 * Version: 5.2.9
 * Requires at least: 6.8.2
 * Requires PHP: 7.4
 * Author: Huw Evans
 * Author URI: http://github.com/HuwEvans
 * License: GPL2
 * License URI: http://github.com/HuwEvans
 * Update URI: https://github.com/HuwEvans/StageKitWP/tree/StageKitWP/dist/stagekitwp-core
 */

defined( 'ABSPATH' ) || exit;

// ---------------------------------------------------------------------------
// Constants — defined before the class so they are available to all includes.
// ---------------------------------------------------------------------------

define( 'STAGEKITWP_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'STAGEKITWP_CORE_URL', plugin_dir_url( __FILE__ ) );

/**
 * Public version constant used by the StageKitWP Theme and companion
 * plugins (e.g. StageKitWP Members) to detect the plugin is active and to
 * check for a minimum version.
 */
if ( ! defined( 'STAGEKITWP_CORE_VERSION' ) ) {
    define( 'STAGEKITWP_CORE_VERSION', '5.2.7' );
}
// ---------------------------------------------------------------------------
// Core class
// ---------------------------------------------------------------------------

if ( ! class_exists( 'StageKitWP_Core' ) ) :

/**
 * StageKitWP_Core — singleton wrapper for the entire plugin.
 *
 * Why a singleton?
 *   • class_exists('StageKitWP_Core') is used by the StageKitWP Theme
 *     and companion plugins as a cheap "is this plugin active?" guard.
 *     Keeping the class name stable preserves that contract without requiring
 *     changes in every consumer.
 *   • All plugin bootstrap (requires, hook registrations) lives inside
 *     ::init() so it runs exactly once, at the right time, via plugins_loaded.
 *
 * Usage in consumer code (theme / companion plugin):
 *   if ( class_exists( 'StageKitWP_Core' ) ) { ... }           // detection
 *   $tm = StageKitWP_Core::instance();                          // accessor
 *   $ver = StageKitWP_Core::instance()->version();              // version
 */
class StageKitWP_Core {

    /**
     * Singleton instance.
     *
     * @var StageKitWP_Core|null
     */
    private static $instance = null;

    /**
     * Plugin version string — mirrors the STAGEKITWP_CORE_VERSION constant.
     *
     * @var string
     */
    private $version = '5.2.0';

    /**
     * Return (and lazily create) the singleton instance.
     *
     * @return StageKitWP_Core
     */
    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor — prevents `new StageKitWP_Core()` outside the class.
     */
    private function __construct() {
        $this->init();
    }

    /**
     * Bootstrap the plugin: load includes and register hooks.
     * Called once from the constructor.
     */
    private function init() {
        $this->load_core_files();
        $this->load_admin_files();
        $this->load_frontend_files();
        $this->load_cpt_files();
        $this->load_shortcodes();
        $this->load_gutenberg_blocks();
        $this->load_beaver_builder();
        $this->register_cpt_hooks();
    }

    /**
     * Load core utility files.
     */
    private function load_core_files() {
        require_once STAGEKITWP_CORE_DIR . 'includes/stagekitwp-core-functions.php';
        require_once STAGEKITWP_CORE_DIR . 'includes/stagekitwp-core-helpers.php';
    }

    /**
     * Load admin-related files.
     */
    private function load_admin_files() {
        // Admin UI. The menu/workspace definitions are loaded on every
        // request because local routers and embedded admin screens may not
        // report is_admin() during plugin bootstrap.
        require_once STAGEKITWP_CORE_DIR . 'includes/stagekitwp-core-admin-menu.php';

        if ( is_admin() ) {
            require_once STAGEKITWP_CORE_DIR . 'includes/stagekitwp-core-sample-content.php';
            require_once STAGEKITWP_CORE_DIR . 'includes/stagekitwp-core-season-builder.php';
            require_once STAGEKITWP_CORE_DIR . 'includes/stagekitwp-core-display-options.php';
        }

        // Self-hosted update checker
        require_once STAGEKITWP_CORE_DIR . 'includes/stagekitwp-core-updater.php';
    }

    /**
     * Load frontend-related files.
     */
    private function load_frontend_files() {
        // Theme integration bridge (dark/light mode, colour tokens)
        require_once STAGEKITWP_CORE_DIR . 'includes/stagekitwp-core-theme-integration.php';

        // Show front-end display (template_redirect + meta box + global setting)
        require_once STAGEKITWP_CORE_DIR . 'includes/stagekitwp-core-show-front-display.php';

        // Menu editor "Display" condition (next season / next season shows) + front-end filtering.
        require_once STAGEKITWP_CORE_DIR . 'includes/stagekitwp-core-nav-menu-conditions.php';
    }

    /**
     * Load Custom Post Type files.
     */
    private function load_cpt_files() {
        foreach ( glob( STAGEKITWP_CORE_DIR . 'cpt/stagekitwp-core-cpt/*.php' ) as $cpt_file ) {
            require_once $cpt_file;
        }
    }

    /**
     * Load shortcode files.
     */
    private function load_shortcodes() {
        foreach ( glob( STAGEKITWP_CORE_DIR . 'includes/stagekitwp-core-shortcodes/*.php' ) as $sc_file ) {
            require_once $sc_file;
        }
    }

    /**
     * Load Gutenberg blocks.
     */
    private function load_gutenberg_blocks() {
        require_once STAGEKITWP_CORE_DIR . 'includes/stagekitwp-core-blocks.php';
    }

    /**
     * Load Beaver Builder integration.
     */
    private function load_beaver_builder() {
        require_once STAGEKITWP_CORE_DIR . 'includes/stagekitwp-core-beaver-builder.php';
    }

    /**
     * Register CPT hooks.
     */
    private function register_cpt_hooks() {
        // Keep first-install CPT registration deterministic even if another
        // loader has altered the individual CPT init callbacks.
        add_action( 'init', function() {
            $cpt_registrars = array(
                'stagekitwp_register_advertisers_cpt' => 'advertiser',
                'stagekitwp_register_award_cpt'       => 'award',
                'stagekitwp_register_board_members_cpt' => 'board_member',
                'stagekitwp_register_cast_cpt'        => 'cast',
                'stagekitwp_register_contributor_cpt' => 'contributor',
                'stagekitwp_register_season_cpt'      => 'season',
                'stagekitwp_register_show_cpt'        => 'show',
                'stagekitwp_register_sponsor_cpt'     => 'sponsor',
                'stagekitwp_register_testimonial_cpt' => 'testimonial',
                'stagekitwp_register_venue_cpt'       => 'venue',
            );

            foreach ( $cpt_registrars as $registrar => $post_type ) {
                if ( function_exists( $registrar ) && ! post_type_exists( $post_type ) ) {
                    call_user_func( $registrar );
                }
            }
        }, 0 );
    }

    // -----------------------------------------------------------------------
    // Public accessors — available to the theme and companion plugins.
    // -----------------------------------------------------------------------

    /**
     * Return the plugin version string.
     *
     * @return string e.g. '3.9.1'
     */
    public function version() {
        return $this->version;
    }

    /**
     * Return the absolute path to the plugin directory (with trailing slash).
     *
     * @return string
     */
    public function plugin_dir() {
        return STAGEKITWP_CORE_DIR;
    }

    /**
     * Return the URL to the plugin directory (with trailing slash).
     *
     * @return string
     */
    public function plugin_url() {
        return STAGEKITWP_CORE_URL;
    }

    // -----------------------------------------------------------------------
    // Prevent cloning / unserialization of the singleton.
    // -----------------------------------------------------------------------

    public function __clone() {
        _doing_it_wrong( __FUNCTION__, 'StageKitWP_Core is a singleton.', '4.0.2' );
    }

    public function __wakeup() {
        _doing_it_wrong( __FUNCTION__, 'StageKitWP_Core cannot be unserialized.', '4.0.2' );
    }
}

endif; // class_exists( 'StageKitWP_Core' )

if ( ! function_exists( 'stagekitwp_core' ) ) {
    /**
     * StageKitWP wrapper for core singleton access.
     *
    * @return StageKitWP_Core
     */
    function stagekitwp_core() {
        return StageKitWP_Core::instance();
    }
}

// ---------------------------------------------------------------------------
// Instantiate.
// plugins_loaded fires after all plugins are loaded but before init, which is
// the correct time to require includes that themselves hook into 'init' or
// later (CPT registration, shortcode registration, etc.).
// ---------------------------------------------------------------------------

if ( did_action( 'plugins_loaded' ) ) {
    StageKitWP_Core::instance();
} else {
    add_action( 'plugins_loaded', array( 'StageKitWP_Core', 'instance' ) );
}
