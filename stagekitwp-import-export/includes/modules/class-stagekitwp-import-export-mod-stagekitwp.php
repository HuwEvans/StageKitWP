<?php
/**
	 * STAGEKITWP_IMPORT_EXPORT_Mod_StageKitWP_Core – module definition for the StageKitWP core plugin.
 *
 * Owns CPTs: show, season, cast, venue, award, board_member, contributor,
 *            sponsor, testimonial, advertiser, stagekitwp_ann, stagekitwp_event,
 *            stagekitwp_conv, stagekitwp_email
 *
 * Owns options: all stagekitwp_* keys (wildcard) except those claimed by sub-modules.
 *
 * @package STAGEKITWP_IO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class STAGEKITWP_IMPORT_EXPORT_Mod_StageKitWP_Core extends STAGEKITWP_IMPORT_EXPORT_Module {

	public function id(): string {
		return 'stagekitwp-core';
	}

	public function label(): string {
		return __( 'StageKitWP Core', 'stagekitwp-io' );
	}

	public function description(): string {
		return __( 'Shows, Seasons, Cast, Venues, Awards, Board Members, Contributors, Sponsors, Testimonials, Advertisers, Announcements, Events, Conversations, and Email Templates.', 'stagekitwp-io' );
	}

	public function post_types(): array {
		return [
			'show',
			'season',
			'cast',
			'venue',
			'award',
			'board_member',
			'contributor',
			'sponsor',
			'testimonial',
			'advertiser',
			'stagekitwp_ann',
			'stagekitwp_event',
			'stagekitwp_conv',
			'stagekitwp_email',
		];
	}

	/**
	 * Meta keys that store a related post's ID. Remapped on import so shows stay
	 * linked to their seasons/venues, cast to shows, and awards to shows — and so
	 * the shortcodes that query these by meta_value keep working.
	 */
	public function relationship_meta_keys(): array {
		return [
			'_stagekitwp_show_season',   // show → season
			'_stagekitwp_show_venue',    // show → venue
			'_stagekitwp_cast_show',     // cast → show
			'_stagekitwp_award_show_id', // award → show
		];
	}

	/**
	 * Core TM options.
	 *
	 * We use a wildcard for the bulk of the stagekitwp_* namespace, but explicitly
	 * exclude sub-module prefixes so they aren't double-exported.
	 */
	public function option_keys(): array {
		return [
			'stagekitwp_color_mode',
			'stagekitwp_google_maps_api_key',
			'stagekitwp_show_builder_cpt_menus',
			'stagekitwp_auditions_page_id',
			// Advertiser display settings.
			'stagekitwp_advertiser_base_font',
			'stagekitwp_advertiser_bg_color',
			'stagekitwp_advertiser_border_color',
			'stagekitwp_advertiser_border_width',
			'stagekitwp_advertiser_disable_border',
			'stagekitwp_advertiser_grid_columns',
			'stagekitwp_advertiser_h1_color',
			'stagekitwp_advertiser_h2_color',
			'stagekitwp_advertiser_h3_color',
			'stagekitwp_advertiser_h4_color',
			'stagekitwp_advertiser_h5_color',
			'stagekitwp_advertiser_h6_color',
			'stagekitwp_advertiser_radius',
			'stagekitwp_advertiser_rounded',
			'stagekitwp_advertiser_shadow',
			'stagekitwp_advertiser_text_color',
		];
	}

	/**
	* Check that the StageKitWP Core plugin is actually active.
	 */
	public function is_available(): bool {
		return is_plugin_active( 'stagekitwp-core/stagekitwp-core.php' )
			|| is_plugin_active( 'stagekitwp-core/stagekitwp-plugin.php' )
			|| defined( 'STAGEKITWP_CORE_VERSION' )
			|| class_exists( 'StageKitWP_Core', false );
	}

	// ── Extra export: plugin db_version and page mappings ────────────────────

	public function export_extra(): array {
		return [
			'db_version'        => get_option( 'stagekitwp_db_version', '' ),
			'auditions_page_id' => get_option( 'stagekitwp_auditions_page_id', 0 ),
		];
	}

	public function import_extra( array $extra ): void {
		// Page IDs are site-specific; we store them but don't blindly overwrite
		// because the target site may have different page IDs. We store under a
		// staging key so the admin can review and remap.
		if ( ! empty( $extra['auditions_page_id'] ) ) {
			update_option( 'stagekitwp_import_export_remap_auditions_page_id', (int) $extra['auditions_page_id'] );
		}
	}
}
