<?php
namespace SKWPM;

/**
 * "Gallery Items" meta box: search configured providers and build the list
 * of items stored in _skwpm_items post meta.
 */
class AdminGallery {

	public static function init(): void {
		add_action( 'add_meta_boxes', [ __CLASS__, 'meta_box' ] );
		add_action( 'save_post_skwpm_gallery', [ __CLASS__, 'save' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'assets' ] );
	}

	public static function assets( string $hook ): void {
		global $post_type;
		if ( 'skwpm_gallery' !== $post_type || ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
			return;
		}

		wp_enqueue_style( 'skwpm-admin-gallery', SKWPM_URL . 'assets/css/admin-gallery.css', [], SKWPM_VERSION );
		wp_enqueue_script( 'skwpm-admin-gallery', SKWPM_URL . 'assets/js/admin-gallery.js', [ 'jquery' ], SKWPM_VERSION, true );
		wp_localize_script( 'skwpm-admin-gallery', 'skwpmGallery', [
			'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
			'restUrl'   => rest_url( 'stagekit-media/v1' ),
			'nonce'     => wp_create_nonce( 'skwpm_gallery_nonce' ),
			'restNonce' => wp_create_nonce( 'wp_rest' ),
			'providers' => self::provider_options(),
			'folders'   => FolderRepository::get_tree(),
			'i18n'      => [
				'allFolders'   => __( 'All Folders (or select one)', 'stagekitwp-media' ),
				'noResults'    => __( 'No results.', 'stagekitwp-media' ),
				'noneSelected' => __( 'No items added yet.', 'stagekitwp-media' ),
				'add'          => __( 'Add', 'stagekitwp-media' ),
				'remove'       => __( 'Remove', 'stagekitwp-media' ),
				'searchFailed' => __( 'Search request failed.', 'stagekitwp-media' ),
				'notConfigured'=> __( 'not configured', 'stagekitwp-media' ),
				'gphotosOpenPicker'   => __( 'Open Google Photos Picker', 'stagekitwp-media' ),
				'gphotosWaiting'      => __( 'Waiting for you to finish selecting photos in the Google Photos tab…', 'stagekitwp-media' ),
				'gphotosReady'        => __( 'Click “Add” below on any photo you want to include.', 'stagekitwp-media' ),
				'gphotosImporting'    => __( 'Importing…', 'stagekitwp-media' ),
				'gphotosImportFailed' => __( 'Could not import that item.', 'stagekitwp-media' ),
				'gphotosStartFailed'  => __( 'Could not start the Google Photos picker.', 'stagekitwp-media' ),
			],
		] );
	}

	private static function provider_options(): array {
		$options = [];
		foreach ( Registry::all() as $provider ) {
			$options[] = [
				'slug'       => $provider->slug(),
				'label'      => $provider->label(),
				'configured' => $provider->is_configured(),
			];
		}
		return $options;
	}

	public static function meta_box(): void {
		add_meta_box(
			'skwpm_gallery_items',
			__( 'Gallery Items', 'stagekitwp-media' ),
			[ __CLASS__, 'render' ],
			'skwpm_gallery',
			'normal',
			'high'
		);
		add_meta_box(
			'skwpm_gallery_display',
			__( 'Display Settings', 'stagekitwp-media' ),
			[ __CLASS__, 'render_display_settings' ],
			'skwpm_gallery',
			'side',
			'default'
		);
	}

	/** Options for the `[skwpm_gallery id="…"]` shortcode used on this gallery's own front-end page. */
	public static function render_display_settings( \WP_Post $post ): void {
		$columns   = (int) get_post_meta( $post->ID, '_skwpm_columns', true ) ?: 3;
		$show_hero = (bool) get_post_meta( $post->ID, '_skwpm_show_hero', true );
		// Default to true (shown) when the meta has never been saved yet.
		if ( '' === get_post_meta( $post->ID, '_skwpm_show_hero', true ) ) {
			$show_hero = true;
		}
		?>
		<p>
			<label for="skwpm_columns"><?php esc_html_e( 'Columns', 'stagekitwp-media' ); ?></label><br>
			<input type="number" id="skwpm_columns" name="skwpm_columns" min="1" max="6" value="<?php echo esc_attr( $columns ); ?>" style="width:100%;">
		</p>
		<p class="description"><?php esc_html_e( 'Number of columns used when this gallery\'s items are displayed on its front-end page.', 'stagekitwp-media' ); ?></p>

		<hr style="margin:12px 0;">

		<p>
			<label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
				<input type="checkbox" id="skwpm_show_hero" name="skwpm_show_hero" value="1" <?php checked( $show_hero ); ?>>
				<strong><?php esc_html_e( 'Show Feature Image', 'stagekitwp-media' ); ?></strong>
			</label>
		</p>
		<p class="description"><?php esc_html_e( 'Display the hero image at the top of this gallery page. When unchecked the image is hidden visually but still used for SEO and social sharing (Open Graph).', 'stagekitwp-media' ); ?></p>
		<?php
	}

	public static function render( \WP_Post $post ): void {
		wp_nonce_field( 'skwpm_save_gallery', 'skwpm_gallery_nonce_field' );
		$items = CPT::get_items( $post->ID );
		?>
		<div id="skwpm-gallery-editor" data-items='<?php echo esc_attr( wp_json_encode( $items ) ); ?>'>
			<div class="skwpm-search-row" id="skwpm-search-row">
				<select id="skwpm-provider"></select>
				<input type="text" id="skwpm-query" placeholder="<?php esc_attr_e( 'Search…', 'stagekitwp-media' ); ?>">
				<button type="button" class="button button-primary" id="skwpm-search-btn"><?php esc_html_e( 'Search', 'stagekitwp-media' ); ?></button>
			</div>
			<div class="skwpm-search-row" id="skwpm-gphotos-row" style="display:none;">
				<button type="button" class="button button-primary" id="skwpm-gphotos-start-btn"><?php esc_html_e( 'Open Google Photos Picker', 'stagekitwp-media' ); ?></button>
				<span class="description" id="skwpm-gphotos-status"></span>
			</div>
			<p class="description" id="skwpm-provider-hint"
				data-hint-youtube="<?php esc_attr_e( 'Tip: paste a channel URL, @handle, or channel ID (starts with “UC…”) instead of a keyword to browse that channel’s uploaded videos.', 'stagekitwp-media' ); ?>"
				data-hint-google-drive="<?php esc_attr_e( 'Drive matches file names only, not photo content — leave the search box empty and click Search to browse your most recently modified images/videos instead.', 'stagekitwp-media' ); ?>"
				style="display:none;"></p>
			<p class="description" id="skwpm-notice" style="display:none;"></p>

			<h4><?php esc_html_e( 'Search Results', 'stagekitwp-media' ); ?></h4>
			<div id="skwpm-search-results" class="skwpm-grid"></div>

			<h4><?php esc_html_e( 'Selected Items', 'stagekitwp-media' ); ?></h4>
			<div id="skwpm-selected-items" class="skwpm-grid"></div>

			<input type="hidden" name="skwpm_items_json" id="skwpm_items_json" value="<?php echo esc_attr( wp_json_encode( $items ) ); ?>">
		</div>
		<?php
	}

	public static function save( int $post_id ): void {
		if ( ! isset( $_POST['skwpm_gallery_nonce_field'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['skwpm_gallery_nonce_field'] ) ), 'skwpm_save_gallery' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$raw     = isset( $_POST['skwpm_items_json'] ) ? wp_unslash( $_POST['skwpm_items_json'] ) : '[]';
		$decoded = json_decode( $raw, true );
		if ( ! is_array( $decoded ) ) {
			$decoded = [];
		}

		$clean = [];
		foreach ( $decoded as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$type    = $item['type'] ?? 'image';
			$clean[] = [
				'provider'    => sanitize_key( $item['provider'] ?? '' ),
				'external_id' => sanitize_text_field( $item['external_id'] ?? '' ),
				'type'        => in_array( $type, [ 'image', 'video' ], true ) ? $type : 'image',
				'url'         => esc_url_raw( $item['url'] ?? '' ),
				'thumb'       => esc_url_raw( $item['thumb'] ?? '' ),
				'title'       => sanitize_text_field( $item['title'] ?? '' ),
				'author'      => sanitize_text_field( $item['author'] ?? '' ),
				'license'     => sanitize_text_field( $item['license'] ?? '' ),
			];
		}

		CPT::save_items( $post_id, $clean );

		if ( isset( $_POST['skwpm_columns'] ) ) {
			$columns = max( 1, min( 6, (int) $_POST['skwpm_columns'] ) );
			update_post_meta( $post_id, '_skwpm_columns', $columns );
		}

		// Save show_hero — store 1 or 0 explicitly so we can distinguish "not set" from "false".
		update_post_meta( $post_id, '_skwpm_show_hero', isset( $_POST['skwpm_show_hero'] ) ? 1 : 0 );
	}
}
