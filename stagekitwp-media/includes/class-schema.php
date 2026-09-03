<?php
namespace SKWPM;

defined( 'ABSPATH' ) || exit;

/**
 * Handles database table installation, updates, and schema version management
 * for StageKit WP Media (folders and media items).
 */
class Schema {

	public const DB_VERSION = '1.0.0';
	public const DB_VERSION_OPTION = 'skwpm_db_version';

	/**
	 * Get the table name for folders.
	 */
	public static function folders_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'skwpm_folders';
	}

	/**
	 * Get the table name for media items.
	 */
	public static function media_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'skwpm_media';
	}

	/**
	 * Run table creation / upgrades if DB version is outdated.
	 */
	public static function maybe_update(): void {
		$installed_version = get_option( self::DB_VERSION_OPTION, '0.0.0' );
		if ( version_compare( $installed_version, self::DB_VERSION, '<' ) ) {
			self::install();
		}
	}

	/**
	 * Create or update the custom database tables using dbDelta.
	 */
	public static function install(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$folders_table   = self::folders_table();
		$media_table     = self::media_table();

		$sql_folders = "CREATE TABLE {$folders_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			slug varchar(255) NOT NULL,
			parent_id bigint(20) unsigned NOT NULL DEFAULT 0,
			provider varchar(64) NOT NULL DEFAULT 'local',
			color varchar(32) DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY parent_id (parent_id),
			KEY provider (provider)
		) {$charset_collate};";

		$sql_media = "CREATE TABLE {$media_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			folder_id bigint(20) unsigned NOT NULL DEFAULT 0,
			filename varchar(255) NOT NULL,
			file_path varchar(500) NOT NULL,
			file_url varchar(500) NOT NULL,
			mime_type varchar(100) NOT NULL,
			file_size bigint(20) unsigned NOT NULL DEFAULT 0,
			width int(10) unsigned DEFAULT NULL,
			height int(10) unsigned DEFAULT NULL,
			thumbnails longtext DEFAULT NULL,
			provider varchar(64) NOT NULL DEFAULT 'local',
			external_id varchar(255) DEFAULT NULL,
			title varchar(255) NOT NULL DEFAULT '',
			caption text DEFAULT NULL,
			alt_text varchar(255) NOT NULL DEFAULT '',
			author varchar(255) NOT NULL DEFAULT '',
			license varchar(255) NOT NULL DEFAULT '',
			sync_hash varchar(64) DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY folder_id (folder_id),
			KEY provider (provider),
			KEY mime_type (mime_type),
			KEY external_id (external_id),
			KEY sync_hash (sync_hash)
		) {$charset_collate};";

		dbDelta( $sql_folders );
		dbDelta( $sql_media );

		update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
	}
}
