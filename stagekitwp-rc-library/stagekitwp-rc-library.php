<?php
/**
 * Plugin Name:       StageKitWP - RC Library
 * Plugin URI:        https://github.com/stagekitwp/stagekitwp-rc-library
 * Description:       Manages PDF book libraries, reading groups, rubrics, and selection workflows for StageKitWP.
 * Version:           2.0.0
 * Author:            Huw Evans
 * Requires at least: 6.8
 * Requires PHP:      8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Define Constants dynamically
define( 'STAGEKITWP_RC_LIBRARY_VERSION', '2.0.0' );
define( 'STAGEKITWP_RC_LIBRARY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'STAGEKITWP_RC_LIBRARY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Core include files following the class-stagekitwp-rcl- prefix pattern
require_once STAGEKITWP_RC_LIBRARY_PLUGIN_DIR . 'includes/class-stagekitwp-rcl-evaluation-form.php';
require_once STAGEKITWP_RC_LIBRARY_PLUGIN_DIR . 'includes/class-stagekitwp-rcl-library.php';
require_once STAGEKITWP_RC_LIBRARY_PLUGIN_DIR . 'includes/class-stagekitwp-rcl-cpts.php';
require_once STAGEKITWP_RC_LIBRARY_PLUGIN_DIR . 'includes/class-stagekitwp-rcl-db.php';
require_once STAGEKITWP_RC_LIBRARY_PLUGIN_DIR . 'includes/class-stagekitwp-rcl-admin.php';
require_once STAGEKITWP_RC_LIBRARY_PLUGIN_DIR . 'includes/class-stagekitwp-rcl-scoring-engine.php';

// Activation Hooks
register_activation_hook( __FILE__, array( 'STAGEKITWP_RC_LIBRARY_DB', 'create_tables' ) );

// FIX: Run Beaver Builder initialization inside the correct, safe wrapper hook
add_action( 'wp_loaded', 'stagekitwp_rc_library_load_beaver_builder_modules' );
function stagekitwp_rc_library_load_beaver_builder_modules() {
    if ( class_exists( 'FLBuilder' ) ) {
        require_once STAGEKITWP_RC_LIBRARY_PLUGIN_DIR . 'modules/stagekitwp-eval-form-module/stagekitwp-eval-form-module.php';
    }
}

// Main execution container block to boot up the orchestration classes securely
function run_stagekitwp_rc_library() {
    if ( class_exists( 'STAGEKITWP_RC_Library' ) ) {
        $plugin = new STAGEKITWP_RC_Library();
        $plugin->run();
    }
    
    if ( class_exists( 'STAGEKITWP_RC_LIBRARY_Evaluation_Form' ) ) {
        new STAGEKITWP_RC_LIBRARY_Evaluation_Form();
    }
}
run_stagekitwp_rc_library();

if ( ! function_exists( 'stagekitwp_run_rc_library' ) ) {
    /**
     * StageKitWP wrapper for RC Library bootstrap.
     *
     * @return void
     */
    function stagekitwp_run_rc_library() {
        run_stagekitwp_rc_library();
    }
}

