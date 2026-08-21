<?php
/**
 * STAGEKITWP_IMPORT_EXPORT_CLI – WP-CLI command: `wp stagekitwp-io`
 *
 * Usage examples:
 *
 *   # Export all modules
 *   wp stagekitwp-io export
 *
 *   # Export specific modules to a named file
 *   wp stagekitwp-io export --modules=stagekitwp,stagekitwp-rc-library --output=/tmp/bundle.zip
 *
 *   # Import a bundle (skip conflicts)
 *   wp stagekitwp-io import /path/to/bundle.zip
 *
 *   # Import with overwrite + only certain modules
 *   wp stagekitwp-io import /path/to/bundle.zip --conflict=overwrite --modules=stagekitwp
 *
 *   # Import from a remote URL
 *   wp stagekitwp-io import https://example.com/stagekitwp-export-bundle.zip
 *
 *   # Show progress for a queued async job
 *   wp stagekitwp-io progress <job-id>
 *
 *   # List available modules
 *   wp stagekitwp-io modules
 *
 * @package STAGEKITWP_IO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Import and export StageKitWP ecosystem data.
 */
class STAGEKITWP_IMPORT_EXPORT_CLI {

	/**
	 * Export one or more modules as a ZIP bundle.
	 *
	 * ## OPTIONS
	 *
	 * [--modules=<modules>]
	 * : Comma-separated list of module IDs to export, or "all" (default).
	 *
	 * [--output=<path>]
	 * : Destination file path for the ZIP. Defaults to the current directory.
	 *
	 * ## EXAMPLES
	 *
	 *     wp stagekitwp-io export
	 *     wp stagekitwp-io export --modules=stagekitwp,stagekitwp-rc-library --output=/tmp/bundle.zip
	 *
	 * @when after_wp_load
	 */
	public function export( array $args, array $assoc_args ): void {
		$modules_input = \WP_CLI\Utils\get_flag_value( $assoc_args, 'modules', 'all' );
		$output        = \WP_CLI\Utils\get_flag_value( $assoc_args, 'output', '' );

		$module_ids = ( 'all' === $modules_input )
			? array_keys( stagekitwp_io()->modules() )
			: array_map( 'sanitize_key', explode( ',', $modules_input ) );

		\WP_CLI::log( sprintf( 'Exporting modules: %s', implode( ', ', $module_ids ) ) );

		$zip_path = stagekitwp_io()->exporter()->export( $module_ids );

		if ( is_wp_error( $zip_path ) ) {
			\WP_CLI::error( $zip_path->get_error_message() );
			return;
		}

		// Move to requested output path if specified.
		if ( $output ) {
			$output = trailingslashit( dirname( $output ) ) . basename( $output );
			rename( $zip_path, $output );
			$zip_path = $output;
		}

		\WP_CLI::success( sprintf( 'Export complete: %s', $zip_path ) );
	}

	/**
	 * Import a TM IO bundle (ZIP), a single-module JSON, or a remote URL.
	 *
	 * ## OPTIONS
	 *
	 * <source>
	 * : Path to a .zip or .json file, or an HTTPS URL.
	 *
	 * [--modules=<modules>]
	 * : Comma-separated module IDs to import, or "all" (default).
	 *
	 * [--conflict=<strategy>]
	 * : How to handle existing records: skip (default), overwrite, duplicate.
	 *
	 * [--force-sync]
	 * : Process synchronously even if the bundle exceeds the async threshold.
	 *
	 * ## EXAMPLES
	 *
	 *     wp stagekitwp-io import /path/to/bundle.zip
	 *     wp stagekitwp-io import /path/to/bundle.zip --conflict=overwrite --modules=stagekitwp
	 *     wp stagekitwp-io import https://staging.example.com/stagekitwp-export.zip
	 *
	 * @when after_wp_load
	 */
	public function import( array $args, array $assoc_args ): void {
		if ( empty( $args[0] ) ) {
			\WP_CLI::error( 'Please provide a source file path or URL.' );
			return;
		}

		$source     = $args[0];
		$conflict   = \WP_CLI\Utils\get_flag_value( $assoc_args, 'conflict',    'skip' );
		$modules    = \WP_CLI\Utils\get_flag_value( $assoc_args, 'modules',     'all' );
		$force_sync = \WP_CLI\Utils\get_flag_value( $assoc_args, 'force-sync',  false );

		// When running via CLI we almost always want sync (no browser timeout risk).
		if ( $force_sync || defined( 'WP_CLI' ) ) {
			add_filter( 'stagekitwp_import_export_async_threshold', fn() => PHP_INT_MAX );
		}

		\WP_CLI::log( sprintf( 'Importing from: %s', $source ) );
		\WP_CLI::log( sprintf( 'Conflict strategy: %s', $conflict ) );

		$result = stagekitwp_io()->importer()->import( $source, [
			'conflict' => $conflict,
			'modules'  => $modules,
		] );

		if ( is_wp_error( $result ) ) {
			\WP_CLI::error( $result->get_error_message() );
			return;
		}

		if ( ! empty( $result['queued'] ) ) {
			\WP_CLI::success( sprintf( 'Job queued (ID: %s). Run `wp stagekitwp-io progress %s` to check status.', $result['job_id'], $result['job_id'] ) );
			return;
		}

		\WP_CLI::success( sprintf(
			'Import complete. Imported: %d  |  Skipped: %d  |  Errors: %d',
			$result['imported'] ?? 0,
			$result['skipped']  ?? 0,
			count( $result['errors'] ?? [] )
		) );

		if ( ! empty( $result['errors'] ) ) {
			\WP_CLI::warning( 'Errors:' );
			foreach ( $result['errors'] as $err ) {
				\WP_CLI::log( '  - ' . $err );
			}
		}
	}

	/**
	 * Check the progress of a queued async import job.
	 *
	 * ## OPTIONS
	 *
	 * <job-id>
	 * : The job UUID returned when the import was queued.
	 *
	 * ## EXAMPLES
	 *
	 *     wp stagekitwp-io progress 550e8400-e29b-41d4-a716-446655440000
	 *
	 * @when after_wp_load
	 */
	public function progress( array $args, array $assoc_args ): void {
		if ( empty( $args[0] ) ) {
			\WP_CLI::error( 'Please provide a job ID.' );
			return;
		}

		$job_id   = sanitize_key( $args[0] );
		$progress = stagekitwp_io()->queue()->get_progress( $job_id );

		\WP_CLI::log( sprintf( 'Status  : %s',  $progress['status']   ?? 'unknown' ) );
		\WP_CLI::log( sprintf( 'Imported: %d',  $progress['imported'] ?? 0 ) );
		\WP_CLI::log( sprintf( 'Skipped : %d',  $progress['skipped']  ?? 0 ) );
		\WP_CLI::log( sprintf( 'Errors  : %d',  count( $progress['errors'] ?? [] ) ) );

		if ( ! empty( $progress['errors'] ) ) {
			foreach ( $progress['errors'] as $err ) {
				\WP_CLI::log( '  - ' . $err );
			}
		}
	}

	/**
	 * List all registered TM IO modules and their availability on this site.
	 *
	 * ## EXAMPLES
	 *
	 *     wp stagekitwp-io modules
	 *
	 * @when after_wp_load
	 */
	public function modules( array $args, array $assoc_args ): void {
		$rows = [];
		foreach ( stagekitwp_io()->modules() as $id => $module ) {
			$rows[] = [
				'id'          => $id,
				'label'       => $module->label(),
				'post_types'  => implode( ', ', $module->post_types() ),
				'available'   => $module->is_available() ? 'yes' : 'no',
			];
		}

		\WP_CLI\Utils\format_items( 'table', $rows, [ 'id', 'label', 'post_types', 'available' ] );
	}
}
