<?php
/**
 * STAGEKITWP_IMPORT_EXPORT_Mod_Pages – module definition for WordPress Pages.
 *
 * Pages aren't owned by any StageKitWP plugin, but several StageKitWP options
 * (Auditions Page, Members Directory Page, etc.) reference a page ID. Exporting
 * pages as their own module lets those references be remapped automatically via
 * the same old→new post ID map used for every other relationship.
 *
 * @package STAGEKITWP_IO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class STAGEKITWP_IMPORT_EXPORT_Mod_Pages extends STAGEKITWP_IMPORT_EXPORT_Module {

	public function id(): string {
		return 'stagekitwp-pages';
	}

	public function label(): string {
		return __( 'Pages', 'stagekitwp-io' );
	}

	public function description(): string {
		return __( 'WordPress Pages (content, featured images, and hierarchy). Needed so options that reference a page — such as the Auditions Page — can be remapped automatically on import.', 'stagekitwp-io' );
	}

	public function post_types(): array {
		return [ 'page' ];
	}

	public function option_keys(): array {
		return [];
	}
}
