<?php
/**
 * STAGEKITWP_IMPORT_EXPORT_Mod_Sync – module definition for stagekitwp-sync (stagekitwp-sync).
 *
 * stagekitwp-sync is a connectivity / replication plugin. Its export payload covers:
 *  - Configuration options (client secret, remote endpoint, etc.)
 *  - No CPTs – sync state is ephemeral and NOT exported.
 *
 * Sensitive values (client_secret) are redacted in the export manifest unless
 * the caller explicitly opts in with [ 'include_secrets' => true ].
 *
 * @package STAGEKITWP_IO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class STAGEKITWP_IMPORT_EXPORT_Mod_Sync extends STAGEKITWP_IMPORT_EXPORT_Module {

	public function id(): string {
		return 'stagekitwp-sync';
	}

	public function label(): string {
		return __( 'StageKitWP Sync', 'stagekitwp-io' );
	}

	public function description(): string {
		return __( 'Sync configuration and connection settings (secrets redacted by default).', 'stagekitwp-io' );
	}

	/** No CPTs. */
	public function post_types(): array {
		return [];
	}

	public function option_keys(): array {
		return [
			'stagekitwp_sync_*', // wildcard covers client_secret + any future keys
		];
	}

	public function is_available(): bool {
		return is_plugin_active( 'stagekitwp-sync/stagekitwp-sync.php' )
			|| is_plugin_active( 'stagekitwp-sync/stagekitwp-sync.php' );
	}

	// ── Export: redact secrets by default ─────────────────────────────────────

	public function export_options(): array {
		$options = parent::export_options();

		// Remove sensitive values unless the exporter explicitly opts in.
		$include_secrets = apply_filters( 'stagekitwp_import_export_sync_export_include_secrets', false );
		if ( ! $include_secrets ) {
			foreach ( array_keys( $options ) as $key ) {
				if ( str_contains( $key, 'secret' ) || str_contains( $key, 'token' ) || str_contains( $key, 'password' ) ) {
					$options[ $key ] = '*** REDACTED ***';
				}
			}
		}

		return $options;
	}

	// ── Import: skip redacted values ─────────────────────────────────────────

	public function import_options( array $options ): void {
		foreach ( $options as $key => $value ) {
			if ( '*** REDACTED ***' === $value ) {
				continue; // Never overwrite a real secret with a placeholder.
			}
			update_option( sanitize_key( $key ), $value );
		}
	}

	public function export_extra(): array {
		return [
			'sync_version' => get_option( 'stagekitwp_sync_version', '' ),
			'note'         => 'Sync state (queued records, last-run timestamps) is ephemeral and is not exported.',
		];
	}
}
