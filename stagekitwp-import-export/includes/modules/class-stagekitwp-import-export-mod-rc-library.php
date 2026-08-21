<?php
/**
 * STAGEKITWP_IMPORT_EXPORT_Mod_Rc_Library – module definition for stagekitwp-rc-library.
 *
 * Owns CPTs: stagekitwp_rubric, stagekitwp_template, stagekitwp_book
 *
 * @package STAGEKITWP_IO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class STAGEKITWP_IMPORT_EXPORT_Mod_Rc_Library extends STAGEKITWP_IMPORT_EXPORT_Module {

	public function id(): string {
		return 'stagekitwp-rc-library';
	}

	public function label(): string {
		return __( 'StageKitWP RC Library', 'stagekitwp-io' );
	}

	public function description(): string {
		return __( 'Books & Scripts, Rubric Items, and Rubric Templates.', 'stagekitwp-io' );
	}

	public function post_types(): array {
		return [
			'stagekitwp_book',
			'stagekitwp_rubric',
			'stagekitwp_template',
		];
	}

	/**
	 * The RC Library currently has no dedicated options rows (no stagekitwp_rc_* keys
	 * were found on the scanned site). We declare the wildcard prefix so any
	 * future options are automatically captured.
	 */
	public function option_keys(): array {
		return [
			'stagekitwp_rc_*',             // wildcard – picks up any future stagekitwp_rc_ settings
			'stagekitwp_rc_db_version',    // explicit fallback in case wildcard yields nothing
		];
	}

	public function is_available(): bool {
		return is_plugin_active( 'stagekitwp-rc-library/stagekitwp-rc-library.php' )
			|| is_plugin_active( 'stagekitwp-rc-library/stagekitwp-rc-library.php' );
	}

	// ── Extra: rubric template → item relationships ───────────────────────────

	/**
	 * Export the relationships between rubric templates and their items.
	 * These are stored in post-meta on the template, but we surface them
	 * explicitly in the extra payload for easier remapping on import.
	 */
	public function export_extra(): array {
		$templates = get_posts( [
			'post_type'      => 'stagekitwp_template',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		] );

		$relationships = [];
		foreach ( $templates as $tmpl_id ) {
			$item_ids = get_post_meta( $tmpl_id, 'stagekitwp_rubrics', true );
			if ( is_array( $item_ids ) && ! empty( $item_ids ) ) {
				// Store slugs so they survive a site migration.
				$item_slugs = [];
				foreach ( $item_ids as $item_id ) {
					$item = get_post( $item_id );
					if ( $item ) {
						$item_slugs[] = $item->post_name;
					}
				}
				$relationships[ get_post_field( 'post_name', $tmpl_id ) ] = $item_slugs;
			}
		}

		return [ 'template_item_map' => $relationships ];
	}

	/**
	 * Re-hydrate rubric template → item relationships from slugs.
	 */
	public function import_extra( array $extra ): void {
		foreach ( $extra['template_item_map'] ?? [] as $tmpl_slug => $item_slugs ) {
			$template = get_page_by_path( $tmpl_slug, OBJECT, 'stagekitwp_template' );
			if ( ! $template ) {
				continue;
			}

			$item_ids = [];
			foreach ( $item_slugs as $slug ) {
				$item = get_page_by_path( $slug, OBJECT, 'stagekitwp_rubric' );
				if ( $item ) {
					$item_ids[] = $item->ID;
				}
			}

			if ( $item_ids ) {
				update_post_meta( $template->ID, 'stagekitwp_rubrics', $item_ids );
			}
		}
	}
}
