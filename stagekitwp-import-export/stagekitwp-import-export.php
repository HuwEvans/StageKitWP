<?php
/**
 * Plugin Name:       StageKitWP - Import-Export
 * Plugin URI:        https://github.com/stagekitwp/stagekitwp-import-export
 * Description:       Import and export CPT data, settings, options, and theme customisations for the StageKitWP ecosystem (stagekitwp-core, stagekitwp-members, stagekitwp-rc-library, stagekitwp-sync, stagekitwp-theme). Supports single-module or full-bundle ZIP exports and hybrid sync/async imports.
 * Version:           2.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            StageKitWP
 * License:           GPL-2.0-or-later
 * Text Domain:       stagekitwp-import-export
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ─── Constants ────────────────────────────────────────────────────────────────
define( 'STAGEKITWP_IMPORT_EXPORT_VERSION',   '2.0.0' );
define( 'STAGEKITWP_IMPORT_EXPORT_FILE',      __FILE__ );
define( 'STAGEKITWP_IMPORT_EXPORT_DIR',       plugin_dir_path( __FILE__ ) );
define( 'STAGEKITWP_IMPORT_EXPORT_URL',       plugin_dir_url( __FILE__ ) );
define( 'STAGEKITWP_IMPORT_EXPORT_BASENAME',  plugin_basename( __FILE__ ) );

/**
 * Async threshold: imports with more records than this value are queued
 * via Action Scheduler (if available) or WP-Cron instead of running inline.
 */
define( 'STAGEKITWP_IMPORT_EXPORT_ASYNC_THRESHOLD', 200 );

/**
 * Default batch size for chunked operations (delete, import queue, export).
 * User-overridable via the stagekitwp_import_export_batch_size option on TM I/O → Settings.
 * Clamped to STAGEKITWP_IMPORT_EXPORT_BATCH_MIN–STAGEKITWP_IMPORT_EXPORT_BATCH_MAX wherever it is consumed.
 */
define( 'STAGEKITWP_IMPORT_EXPORT_BATCH_DEFAULT', 50 );
define( 'STAGEKITWP_IMPORT_EXPORT_BATCH_MIN', 10 );
define( 'STAGEKITWP_IMPORT_EXPORT_BATCH_MAX', 500 );

/**
 * Return the configured batch size, clamped to the safe range.
 *
 * @return int
 */
function stagekitwp_import_export_batch_size(): int {
	$size = (int) get_option( 'stagekitwp_import_export_batch_size', STAGEKITWP_IMPORT_EXPORT_BATCH_DEFAULT );
	if ( $size <= 0 ) {
		$size = STAGEKITWP_IMPORT_EXPORT_BATCH_DEFAULT;
	}
	return max( STAGEKITWP_IMPORT_EXPORT_BATCH_MIN, min( STAGEKITWP_IMPORT_EXPORT_BATCH_MAX, $size ) );
}

// ─── Autoloader ───────────────────────────────────────────────────────────────
// Eagerly load the abstract base so module subclasses always find it.
require_once STAGEKITWP_IMPORT_EXPORT_DIR . 'includes/modules/class-stagekitwp-import-export-module.php';

spl_autoload_register( function ( string $class ): void {
	// Only handle our own classes.
	if ( strpos( $class, 'STAGEKITWP_IMPORT_EXPORT_' ) !== 0 && strpos( $class, 'StageKitWP_Import_Export_' ) !== 0 ) {
		return;
	}

	/*
	 * Map class name → file path.
	* Convention:  StageKitWP_Import_Export_Foo_Bar  →  includes/class-stagekitwp-import-export-foo-bar.php
	*              StageKitWP_Import_Export_Mod_Foo  →  includes/modules/class-stagekitwp-import-export-mod-foo.php
	 */
	$slug = strtolower( str_replace( '_', '-', $class ) ); // stagekitwp-io-foo-bar

	if ( strpos( $slug, 'stagekitwp-import-export-mod-' ) === 0 ) {
		$module_slug = ( 'stagekitwp-import-export-mod-stagekitwp-core' === $slug )
			? 'stagekitwp-import-export-mod-stagekitwp'
			: $slug;
		$file = STAGEKITWP_IMPORT_EXPORT_DIR . 'includes/modules/class-' . $module_slug . '.php';
	} else {
		$file = STAGEKITWP_IMPORT_EXPORT_DIR . 'includes/class-' . $slug . '.php';
	}

	if ( file_exists( $file ) ) {
		require_once $file;
	}
} );

// ─── Bootstrap ────────────────────────────────────────────────────────────────

/**
 * Returns the single plugin instance.
 *
 * @return StageKitWP_Import_Export_Plugin
 */
function stagekitwp_io(): StageKitWP_Import_Export_Plugin {
	return StageKitWP_Import_Export_Plugin::instance();
}

if ( ! function_exists( 'stagekitwp_import_export' ) ) {
	/**
	 * StageKitWP wrapper for Import-Export singleton access.
	 *
	 * @return StageKitWP_Import_Export_Plugin
	 */
	function stagekitwp_import_export(): StageKitWP_Import_Export_Plugin {
		return stagekitwp_io();
	}
}

add_action( 'plugins_loaded', 'stagekitwp_io' );

// ─── Activation / deactivation hooks ─────────────────────────────────────────

register_activation_hook( __FILE__, function (): void {
	StageKitWP_Import_Export_Plugin::instance()->activate();
} );

register_deactivation_hook( __FILE__, function (): void {
	StageKitWP_Import_Export_Plugin::instance()->deactivate();
} );
