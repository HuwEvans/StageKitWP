<?php
/**
 * PHPUnit bootstrap for TM IO plugin tests.
 *
 * Run from the plugin root:
 *   composer install
 *   vendor/bin/phpunit
 *
 * Or via WP-CLI:
 *   wp scaffold plugin-tests stagekitwp-io
 *   bash bin/install-wp-tests.sh wordpress_test root '' localhost latest
 *   vendor/bin/phpunit
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' ) ?: sys_get_temp_dir() . '/wordpress-tests-lib';

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find WordPress test suite at {$_tests_dir}.\n";
	echo "Set the WP_TESTS_DIR environment variable.\n";
	exit( 1 );
}

// Load the WP test suite.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually activate the plugin during bootstrap.
 */
function _manually_load_plugin(): void {
	require dirname( __DIR__ ) . '/stagekitwp-io.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';
