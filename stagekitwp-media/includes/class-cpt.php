<?php
namespace SKWPM;

class CPT {
	public static function init(): void {
 add_action( 'init', [ __CLASS__, 'register' ] );
 add_action( 'template_redirect', [ __CLASS__, 'render_single_content' ] );
 add_action( 'wp_loaded', [ __CLASS__, 'maybe_flush_rewrite_rules' ] );
 add_filter( 'template_include', [ __CLASS__, 'template_include' ] );
	}

	/** Let an active theme override the gallery template; otherwise fall back to the plugin's own copy. */
	public static function template_include( string $template ): string {
		if ( ! is_singular( 'skwpm_gallery' ) ) {
			return $template;
		}
		$theme_template = locate_template( 'single-skwpm_gallery.php' );
		return $theme_template ?: SKWPM_PATH . 'templates/single-skwpm_gallery.php';
	}

	/** On a single Gallery's front-end page, replace the (editor-less) post content with its `[skwpm_gallery]` items display. */
	public static function render_single_content(): void {
		if ( is_admin() || ! is_singular( 'skwpm_gallery' ) ) {
			return;
		}
		$post_id = get_queried_object_id();
		if ( ! $post_id ) {
			return;
		}

		add_filter( 'the_content', function ( $content ) use ( $post_id ) {
			if ( ! in_the_loop() || ! is_main_query() || get_the_ID() !== $post_id ) {
				return $content;
			}
			$columns = (int) get_post_meta( $post_id, '_skwpm_columns', true ) ?: 3;
			return do_shortcode( sprintf( '[skwpm_gallery id="%d" columns="%d"]', $post_id, $columns ) );
		}, 1 );
	}

	/**
	 * Self-heal installs whose saved rewrite rules don't yet include this CPT
	 * (which otherwise causes single Gallery links to 404). Checks the actual
	 * stored rules rather than a version flag, so it recovers regardless of
	 * why the rules went stale (fresh activation, multisite, migration, etc.).
	 */
	public static function maybe_flush_rewrite_rules(): void {
		$rules = get_option( 'rewrite_rules' );
		if ( is_array( $rules ) ) {
			foreach ( $rules as $rewrite ) {
				if ( false !== strpos( $rewrite, 'post_type=skwpm_gallery' ) ) {
					return;
				}
			}
		}
		flush_rewrite_rules();
	}

	public static function register(): void {
	    $icon_url = plugins_url( 'assets/icons/stagekitwp-icon.svg', dirname( __DIR__ ) . '/stagekitwp-media.php' );
 register_post_type( 'skwpm_gallery', [
 'labels' => [
 'name' => 'Galleries',
 'singular_name' => 'Gallery',
 'menu_name' => 'StageKit Media',
 'add_new_item' => 'Add New Gallery',
 'edit_item' => 'Edit Gallery',
 'all_items' => 'All Galleries',
 ],
 'public' => true,
 'show_in_rest' => true,
 'menu_position' => 11,
 'menu_icon' => $icon_url,
 'supports' => [ 'title', 'thumbnail' ],
 ] );
	}

	/** @return array[] */
	public static function get_items( int $gallery_id ): array {
 return get_post_meta( $gallery_id, '_skwpm_items', true )?: [];
	}

	public static function save_items( int $gallery_id, array $items ): void {
 update_post_meta( $gallery_id, '_skwpm_items', array_values( $items ) );
	}

	/**
	 * Fetch published galleries for the "list of galleries" shortcode display.
	 *
	 * @return \WP_Post[]
	 */
	public static function get_galleries( array $args = [] ): array {
		$defaults = [
			'post_type'      => 'skwpm_gallery',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'no_found_rows'  => true,
		];

		return get_posts( array_merge( $defaults, $args ) );
	}

	public static function get_gallery_list_url(): string {
		$gallery_pages = get_posts( [
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			's'              => '[skwpm_gallery]',
			'no_found_rows'  => true,
		] );

		if ( ! empty( $gallery_pages ) && has_shortcode( $gallery_pages[0]->post_content, 'skwpm_gallery' ) ) {
			return get_permalink( $gallery_pages[0] );
		}

		$galleries_page = get_page_by_path( 'galleries' );
		return $galleries_page ? get_permalink( $galleries_page ) : home_url( '/' );
	}
}