<?php
/**
 * Per-post / per-page / per-CPT "Show Title" toggle.
 *
 * Adds a "Post Options" / "Page Options" / "{Type} Options" meta box with a
 * single checkbox that controls whether single.php, page.php, and index.php
 * (the generic CPT fallback template) render the entry title. Title is shown
 * by default (no meta = show), preserving existing content.
 *
 * @package StageKitWP_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'STAGEKITWP_THEME_SHOW_TITLE_META', '_stagekitwp_show_title' );

/**
 * Post types this toggle applies to: 'post', 'page', plus every public CPT
 * that falls back to index.php. 'show' is excluded — it already has its own
 * Front-end Display meta box whose field_list controls the title.
 *
 * @return string[]
 */
function stagekitwp_theme_title_toggle_post_types() {
	$cpts = get_post_types( array( 'public' => true, '_builtin' => false ), 'names' );
	unset( $cpts['show'] );
	return array_merge( array( 'post', 'page' ), array_values( $cpts ) );
}

/**
 * Register the meta box on 'post', 'page', and applicable CPT screens.
 */
function stagekitwp_theme_register_title_options_metabox() {
	foreach ( stagekitwp_theme_title_toggle_post_types() as $post_type ) {
		$type_obj = get_post_type_object( $post_type );
		if ( ! $type_obj ) {
			continue;
		}
		$label = ( 'post' === $post_type ) ? __( 'Post Options', 'stagekitwp' )
			: ( ( 'page' === $post_type ) ? __( 'Page Options', 'stagekitwp' )
			: sprintf( /* translators: %s: post type singular label */ __( '%s Options', 'stagekitwp' ), $type_obj->labels->singular_name ) );

		add_meta_box(
			'stagekitwp-title-options-' . $post_type,
			$label,
			'stagekitwp_theme_render_title_options_metabox',
			$post_type,
			'side',
			'default'
		);
	}
}
add_action( 'add_meta_boxes', 'stagekitwp_theme_register_title_options_metabox' );

/**
 * Render the "Show Title" checkbox.
 *
 * @param WP_Post $post Current post object.
 */
function stagekitwp_theme_render_title_options_metabox( $post ) {
	wp_nonce_field( 'stagekitwp_theme_save_title_options', 'stagekitwp_theme_title_options_nonce' );

	$saved       = get_post_meta( $post->ID, STAGEKITWP_THEME_SHOW_TITLE_META, true );
	$show_title  = ( $saved === '0' ) ? false : true; // default: shown
	?>
	<p>
		<label>
			<input type="checkbox" name="stagekitwp_show_title" value="1" <?php checked( $show_title ); ?>>
			<?php esc_html_e( 'Show title', 'stagekitwp' ); ?>
		</label>
	</p>
	<p class="description"><?php esc_html_e( 'Uncheck to hide the title on the front end.', 'stagekitwp' ); ?></p>
	<?php
}

/**
 * Save the "Show Title" checkbox value.
 *
 * @param int $post_id Post ID being saved.
 */
function stagekitwp_theme_save_title_options( $post_id ) {
	if ( ! isset( $_POST['stagekitwp_theme_title_options_nonce'] )
		|| ! wp_verify_nonce( $_POST['stagekitwp_theme_title_options_nonce'], 'stagekitwp_theme_save_title_options' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	$post_type = get_post_type( $post_id );
	if ( ! in_array( $post_type, stagekitwp_theme_title_toggle_post_types(), true ) ) {
		return;
	}

	$type_obj = get_post_type_object( $post_type );
	$cap      = $type_obj ? $type_obj->cap->edit_post : 'edit_post';
	if ( ! current_user_can( $cap, $post_id ) ) {
		return;
	}

	$show_title = isset( $_POST['stagekitwp_show_title'] ) ? '1' : '0';
	update_post_meta( $post_id, STAGEKITWP_THEME_SHOW_TITLE_META, $show_title );
}
add_action( 'save_post', 'stagekitwp_theme_save_title_options' );

/**
 * Whether the current post's title should be displayed. Defaults to true.
 *
 * @param int|WP_Post|null $post Optional post ID/object. Defaults to current post.
 * @return bool
 */
function stagekitwp_theme_display_title( $post = null ) {
	$post_id = $post ? ( is_object( $post ) ? $post->ID : $post ) : get_the_ID();
	if ( ! $post_id ) {
		return true;
	}
	return get_post_meta( $post_id, STAGEKITWP_THEME_SHOW_TITLE_META, true ) !== '0';
}
