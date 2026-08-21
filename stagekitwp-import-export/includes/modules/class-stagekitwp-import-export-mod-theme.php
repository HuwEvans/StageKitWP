<?php
/**
 * STAGEKITWP_IMPORT_EXPORT_Mod_Theme – module definition for stagekitwp-theme.
 *
 * Exports:
 *  - theme_mods_stagekitwp-theme  (all Customizer / theme-mod values)
 *  - nav_menu_locations                (slug-keyed so they survive a migration)
 *  - sidebar_widgets                   (widget arrangement)
 *
 * Media references (logo URLs, hero video URL) are rewritten to be relative
 * on export and re-resolved on import so they work across domains.
 *
 * @package STAGEKITWP_IO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class STAGEKITWP_IMPORT_EXPORT_Mod_Theme extends STAGEKITWP_IMPORT_EXPORT_Module {

	/** Theme mods option key. */
	private const MODS_KEY = 'theme_mods_stagekitwp-theme';

	public function id(): string {
		return 'stagekitwp-theme';
	}

	public function label(): string {
		return __( 'StageKitWP Theme', 'stagekitwp-io' );
	}

	public function description(): string {
		return __( 'All Customizer theme mods, colour settings, nav menu locations, sidebar/widget layout, and logo/media references.', 'stagekitwp-io' );
	}

	/** No CPTs. */
	public function post_types(): array {
		return [];
	}

	public function option_keys(): array {
		return [
			self::MODS_KEY,
			'theme_mods_stagekitwp-theme',
			'widget_nav_menu',
			'widget_block',
			'sidebars_widgets',
		];
	}

	public function is_available(): bool {
		return in_array( get_stylesheet(), [ 'stagekitwp-theme', 'stagekitwp-theme' ], true );
	}

	// ── Export ────────────────────────────────────────────────────────────────

	/**
	 * Override to perform URL → relative-path rewriting on media references
	 * and to capture nav menus by slug rather than term ID.
	 */
	public function export_options(): array {
		$options = parent::export_options();

		// Rewrite absolute upload URLs → relative paths so the bundle is portable.
		$upload_dir  = wp_upload_dir();
		$uploads_url = trailingslashit( $upload_dir['baseurl'] );

		array_walk_recursive( $options, function ( &$value ) use ( $uploads_url ): void {
			if ( is_string( $value ) && str_starts_with( $value, $uploads_url ) ) {
				$value = '{{UPLOADS}}/' . ltrim( str_replace( $uploads_url, '', $value ), '/' );
			} elseif ( is_string( $value ) && str_starts_with( $value, home_url() ) ) {
				$value = '{{HOME}}/' . ltrim( str_replace( trailingslashit( home_url() ), '', $value ), '/' );
			}
		} );

		return $options;
	}

	public function export_extra(): array {
		// Export nav menus by slug so they can be re-matched by name on any site.
		$locations = get_nav_menu_locations();
		$slug_map  = [];
		foreach ( $locations as $location => $term_id ) {
			$menu = wp_get_nav_menu_object( $term_id );
			if ( $menu ) {
				$slug_map[ $location ] = [
					'slug' => $menu->slug,
					'name' => $menu->name,
				];
			}
		}

		$theme_slug = 'stagekitwp-theme';
		$theme      = wp_get_theme( $theme_slug );
		if ( ! $theme->exists() ) {
			$theme_slug = 'stagekitwp-theme';
			$theme      = wp_get_theme( $theme_slug );
		}

		return [
			'theme_version'     => $theme->get( 'Version' ),
			'nav_menu_slug_map' => $slug_map,
		];
	}

	// ── Import ────────────────────────────────────────────────────────────────

	public function import_options( array $options ): void {
		$upload_dir  = wp_upload_dir();
		$uploads_url = trailingslashit( $upload_dir['baseurl'] );
		$home_url    = trailingslashit( home_url() );

		// Rewrite portable placeholders back to absolute URLs for this site.
		array_walk_recursive( $options, function ( &$value ) use ( $uploads_url, $home_url ): void {
			if ( is_string( $value ) ) {
				$value = str_replace( '{{UPLOADS}}/', $uploads_url, $value );
				$value = str_replace( '{{HOME}}/',    $home_url,    $value );
			}
		} );

		parent::import_options( $options );
	}

	/**
	 * Re-map nav menu locations using slug matching.
	 */
	public function import_extra( array $extra ): void {
		$slug_map = $extra['nav_menu_slug_map'] ?? [];
		if ( empty( $slug_map ) ) {
			return;
		}

		$current_locations = get_nav_menu_locations();
		foreach ( $slug_map as $location => $menu_info ) {
			$menu = get_term_by( 'slug', $menu_info['slug'], 'nav_menu' );
			if ( ! $menu ) {
				// Try matching by name.
				$menu = get_term_by( 'name', $menu_info['name'], 'nav_menu' );
			}
			if ( $menu ) {
				$current_locations[ $location ] = $menu->term_id;
			}
		}

		set_theme_mod( 'nav_menu_locations', $current_locations );
	}
}
