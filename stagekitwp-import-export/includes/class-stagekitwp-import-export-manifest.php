<?php
/**
 * STAGEKITWP_IMPORT_EXPORT_Manifest – builds and parses the manifest.json inside every bundle.
 *
 * The manifest is the first file read on import. It lets the importer:
 *  1. Verify bundle integrity (plugin version, WP version compatibility).
 *  2. Know which modules are present and how many records each has.
 *  3. Decide sync vs async processing (record_count > STAGEKITWP_IMPORT_EXPORT_ASYNC_THRESHOLD).
 *  4. Surface warnings when a module is missing on the destination site.
 *
 * @package STAGEKITWP_IO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class STAGEKITWP_IMPORT_EXPORT_Manifest {

	public const FILENAME     = 'manifest.json';
	public const SCHEMA_VER   = '1.0';

	// ── Build ─────────────────────────────────────────────────────────────────

	/**
	 * Build a manifest array for the given module payloads.
	 *
	 * @param array<string, array<string,mixed>> $module_payloads  module_id → export() result
	 * @return array<string, mixed>
	 */
	public static function build( array $module_payloads ): array {
		$modules_meta = [];
		$total_posts  = 0;

		foreach ( $module_payloads as $id => $payload ) {
			$post_count       = count( $payload['posts']   ?? [] );
			$option_count     = count( $payload['options'] ?? [] );
			$total_posts     += $post_count;

			$modules_meta[ $id ] = [
				'label'        => $payload['label']       ?? $id,
				'exported_at'  => $payload['exported_at'] ?? gmdate( 'c' ),
				'post_count'   => $post_count,
				'option_count' => $option_count,
				'has_extra'    => ! empty( $payload['extra'] ),
			];
		}

		return [
			'schema_version' => self::SCHEMA_VER,
			'plugin_version' => STAGEKITWP_IMPORT_EXPORT_VERSION,
			'wp_version'     => get_bloginfo( 'version' ),
			'site_url'       => home_url(),
			'site_name'      => get_bloginfo( 'name' ),
			'exported_at'    => gmdate( 'c' ),
			'total_posts'    => $total_posts,
			'modules'        => $modules_meta,
		];
	}

	// ── Parse & validate ──────────────────────────────────────────────────────

	/**
	 * Parse a manifest JSON string and validate its schema version.
	 *
	 * @param string $json  Raw JSON string from manifest.json.
	 * @return array<string,mixed>|WP_Error
	 */
	public static function parse( string $json ): array|\WP_Error {
		$data = json_decode( $json, true );

		if ( JSON_ERROR_NONE !== json_last_error() ) {
			return new \WP_Error(
				'stagekitwp_import_export_manifest_parse',
				sprintf( __( 'manifest.json is not valid JSON: %s', 'stagekitwp-io' ), json_last_error_msg() )
			);
		}

		if ( empty( $data['schema_version'] ) ) {
			// Theatre Manager v1 bundles predate schema_version. The importer
			// normalizes their module IDs and payload identifiers after parsing.
			$data['schema_version'] = '1.0';
		}

		if ( version_compare( $data['schema_version'], self::SCHEMA_VER, '>' ) ) {
			return new \WP_Error(
				'stagekitwp_import_export_manifest_version',
				sprintf(
					/* translators: 1: bundle schema version, 2: supported schema version */
					__( 'Bundle schema v%1$s is newer than this plugin supports (v%2$s). Please update TM IO.', 'stagekitwp-io' ),
					$data['schema_version'],
					self::SCHEMA_VER
				)
			);
		}

		return $data;
	}

	// ── Helpers ───────────────────────────────────────────────────────────────

	/**
	 * Returns a human-readable summary string for use in admin notices.
	 *
	 * @param array<string,mixed> $manifest  Parsed manifest array.
	 */
	public static function summary( array $manifest ): string {
		$lines = [
			sprintf(
				/* translators: 1: site name, 2: site URL, 3: export date */
				__( 'Exported from <strong>%1$s</strong> (%2$s) on %3$s.', 'stagekitwp-io' ),
				esc_html( $manifest['site_name'] ?? '?' ),
				esc_html( $manifest['site_url']  ?? '?' ),
				esc_html( wp_date( get_option( 'date_format' ), strtotime( $manifest['exported_at'] ?? '' ) ) )
			),
			sprintf(
				/* translators: number of posts */
				_n( '%s post/record total.', '%s posts/records total.', (int) ( $manifest['total_posts'] ?? 0 ), 'stagekitwp-io' ),
				number_format_i18n( (int) ( $manifest['total_posts'] ?? 0 ) )
			),
		];

		return implode( ' ', $lines );
	}

	/**
	 * Total record count across all modules (posts only).
	 *
	 * @param array<string,mixed> $manifest  Parsed manifest array.
	 */
	public static function total_records( array $manifest ): int {
		return (int) ( $manifest['total_posts'] ?? 0 );
	}
}
