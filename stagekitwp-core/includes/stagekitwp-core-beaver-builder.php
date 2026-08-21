<?php
/**
 * StageKitWP - Beaver Builder Integration
 * 
 * Initializes and registers custom category for StageKitWP Beaver Builder modules
 * 
 * @package StageKitWP
 * @subpackage Beaver Builder
 */

// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Register the StageKitWP module group for Beaver Builder.
 */
function stagekitwp_register_beaver_builder_category() {
    if ( ! class_exists( 'FLBuilder' ) ) {
        return;
    }

    add_filter( 'fl_builder_module_groups', function( $groups ) {
        if ( ! is_array( $groups ) ) {
            $groups = array();
        }

        if ( ! isset( $groups['stagekitwp-core'] ) ) {
            $groups['stagekitwp-core'] = __( 'StageKitWP', 'stagekitwp-core' );
        }

        return $groups;
    }, 10, 1 );
}
add_action( 'init', 'stagekitwp_register_beaver_builder_category', 5 );

/**
 * Load all Beaver Builder modules
 */
function stagekitwp_load_beaver_builder_modules() {
    if ( ! class_exists( 'FLBuilder' ) ) {
        return;
    }

    $modules_dir = STAGEKITWP_CORE_DIR . 'modules/';

    if ( is_dir( $modules_dir ) ) {
        foreach ( glob( $modules_dir . '*/stagekitwp-*.php' ) as $module_file ) {
            require_once $module_file;
        }
    }
}
add_action( 'wp_loaded', 'stagekitwp_load_beaver_builder_modules', 10 );
