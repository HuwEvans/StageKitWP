<?php
// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Register StageKitWP menu and submenus.
 * Priority 9 so our entries land before CPTs (which register on init/admin_menu p10).
 */
function stagekitwp_register_plugin_menu() {
    $icon_url = plugins_url( 'assets/icons/stagekitwp-icon.svg', dirname( __DIR__ ) . '/stagekitwp-core.php' );

    add_menu_page(
		'StageKitWP',
		'StageKitWP',
		'edit_posts',
        'stagekitwp-core',
        'stagekitwp_dashboard_page',
        $icon_url,
        22.51
    );

    // Dashboard — same slug as parent so WP uses this as the top-level link target.
    add_submenu_page(
        'stagekitwp-core',
		'StageKitWP',
        'Dashboard',
		'edit_posts',
        'stagekitwp-core',
        'stagekitwp_dashboard_page'
    );

	// Note: using a raw external URL as the add_submenu_page() slug (a common
	// trick) is unreliable here — when no callback is registered, WP core's
	// menu-header.php runs file_exists( WP_PLUGIN_DIR . '/' . $slug ) against
	// the full URL to decide how to build the link. On Windows that mangles a
	// path containing '://' and throws a visible PHP warning inside the menu.
	// Instead, register a normal internal page and redirect on its 'load-'
	// hook (before wp-admin has sent any output), which is the standard,
	// cross-platform way to point a menu item at an external URL.
	$stagekitwp_docs_hook = add_submenu_page(
		'stagekitwp-core',
		'Documentation',
		'Documentation',
		'edit_posts',
		'stagekitwp-documentation',
		'__return_null'
	);
	if ( $stagekitwp_docs_hook ) {
		add_action( "load-{$stagekitwp_docs_hook}", 'stagekitwp_documentation_redirect' );
	}

	add_submenu_page(
		'stagekitwp-core',
		'Season Content',
		'Season Content',
		'edit_posts',
		'stagekitwp-season-content',
		'stagekitwp_season_content_page'
	);

	add_submenu_page(
		'stagekitwp-core',
		'People, Places & Partners',
		'People, Places & Partners',
		'edit_posts',
		'stagekitwp-people-places-partners',
		'stagekitwp_people_places_partners_page'
	);
	
}
add_action('admin_menu', 'stagekitwp_register_plugin_menu', 9);
add_action( 'admin_head', 'stagekitwp_admin_menu_palette_styles' );
add_action( 'admin_footer', 'stagekitwp_documentation_menu_link_new_tab' );

/**
 * Redirect the internal 'stagekitwp-documentation' page straight to the
 * GitHub wiki. Runs on the page's 'load-' hook, which fires before
 * wp-admin has sent any output, so wp_redirect() here is always safe.
 */
function stagekitwp_documentation_redirect() {
	wp_redirect( 'https://github.com/HuwEvans/StageKitWP/wiki' );
	exit;
}

/**
 * The Documentation submenu links to an internal redirect page (see above).
 * Open it in a new tab so users don't lose their place in wp-admin —
 * the new tab briefly hits the redirect page, then lands on the wiki.
 */
function stagekitwp_documentation_menu_link_new_tab() {
	// The StageKitWP menu is in the sidebar on every wp-admin screen, so this
	// runs globally rather than being restricted to StageKitWP's own pages.
	?>
	<script>
	( function() {
		var link = document.querySelector( '#adminmenu a[href*="page=stagekitwp-documentation"]' );
		if ( link ) {
			link.target = '_blank';
			link.rel = 'noopener noreferrer';
		}
	} )();
	</script>
	<?php
}

function stagekitwp_admin_menu_palette_styles() {
	echo '<style>
		#adminmenu .wp-menu-image img {
			display: flex;
			margin: 0 auto;
			object-fit: contain;
			padding: 0;
			align-items: center;
            justify-content: center;
			width: 25;
		}
		#adminmenu .toplevel_page_stagekitwp-core .wp-menu-image svg {
            fill: currentColor;
        }
		#adminmenu #toplevel_page_stagekitwp-core .wp-submenu a {
			color: #facc15 !important;
		}
		#adminmenu #toplevel_page_stagekitwp-core .wp-submenu a:hover,
		#adminmenu #toplevel_page_stagekitwp-core .wp-submenu a:focus,
		#adminmenu #toplevel_page_stagekitwp-core .wp-submenu a.current {
			color: #fde047 !important;
		}
			#adminmenu #toplevel_page_stagekitwp-core .wp-submenu a[href*="page=stagekitwp-io"],
			#adminmenu #toplevel_page_stagekitwp-core .wp-submenu a[href*="page=stagekitwp-sync"],
			#adminmenu #toplevel_page_stagekitwp-core .wp-submenu a[href*="page=stagekitwp-sync-"],
			#adminmenu #toplevel_page_stagekitwp-core .wp-submenu a[href*="page=stagekitwp-ma-admin"],
			#adminmenu #toplevel_page_stagekitwp-core .wp-submenu a[href*="page=stagekitwp-ma-"],
			#adminmenu #toplevel_page_stagekitwp-core .wp-submenu a[href*="page=stagekitwp-rc-library"],
			#adminmenu #toplevel_page_stagekitwp-core .wp-submenu a[href*="page=stagekitwp-rcl-"] {
				color: #ec4899 !important;
			}
			#adminmenu #toplevel_page_stagekitwp-core .wp-submenu a[href*="page=stagekitwp-io"]:hover,
			#adminmenu #toplevel_page_stagekitwp-core .wp-submenu a[href*="page=stagekitwp-sync"]:hover,
			#adminmenu #toplevel_page_stagekitwp-core .wp-submenu a[href*="page=stagekitwp-sync-"]:hover,
			#adminmenu #toplevel_page_stagekitwp-core .wp-submenu a[href*="page=stagekitwp-ma-admin"]:hover,
			#adminmenu #toplevel_page_stagekitwp-core .wp-submenu a[href*="page=stagekitwp-ma-"]:hover,
			#adminmenu #toplevel_page_stagekitwp-core .wp-submenu a[href*="page=stagekitwp-rc-library"]:hover,
			#adminmenu #toplevel_page_stagekitwp-core .wp-submenu a[href*="page=stagekitwp-rcl-"]:hover,
			#adminmenu #toplevel_page_stagekitwp-core .wp-submenu a[href*="page=stagekitwp-io"]:focus,
			#adminmenu #toplevel_page_stagekitwp-core .wp-submenu a[href*="page=stagekitwp-sync"]:focus,
			#adminmenu #toplevel_page_stagekitwp-core .wp-submenu a[href*="page=stagekitwp-sync-"]:focus,
			#adminmenu #toplevel_page_stagekitwp-core .wp-submenu a[href*="page=stagekitwp-ma-admin"]:focus,
			#adminmenu #toplevel_page_stagekitwp-core .wp-submenu a[href*="page=stagekitwp-ma-"]:focus,
			#adminmenu #toplevel_page_stagekitwp-core .wp-submenu a[href*="page=stagekitwp-rc-library"]:focus,
			#adminmenu #toplevel_page_stagekitwp-core .wp-submenu a[href*="page=stagekitwp-rcl-"]:focus,
			#adminmenu #toplevel_page_stagekitwp-core .wp-submenu a[href*="page=stagekitwp-io"].current,
			#adminmenu #toplevel_page_stagekitwp-core .wp-submenu a[href*="page=stagekitwp-sync"].current,
			#adminmenu #toplevel_page_stagekitwp-core .wp-submenu a[href*="page=stagekitwp-sync-"].current,
			#adminmenu #toplevel_page_stagekitwp-core .wp-submenu a[href*="page=stagekitwp-ma-admin"].current,
			#adminmenu #toplevel_page_stagekitwp-core .wp-submenu a[href*="page=stagekitwp-ma-"].current,
			#adminmenu #toplevel_page_stagekitwp-core .wp-submenu a[href*="page=stagekitwp-rc-library"].current,
			#adminmenu #toplevel_page_stagekitwp-core .wp-submenu a[href*="page=stagekitwp-rcl-"].current {
				color: #f472b6 !important;
			}
		#adminmenu #toplevel_page_stagekitwp-core .wp-submenu a[href*="page=stagekitwp-theme-dashboard"] {
			color: #22c55e !important;
		}
		#adminmenu #toplevel_page_stagekitwp-core .wp-submenu a[href*="page=stagekitwp-theme-dashboard"]:hover,
		#adminmenu #toplevel_page_stagekitwp-core .wp-submenu a[href*="page=stagekitwp-theme-dashboard"]:focus,
		#adminmenu #toplevel_page_stagekitwp-core .wp-submenu a[href*="page=stagekitwp-theme-dashboard"].current {
			color: #4ade80 !important;
		}
	</style>';
}

add_action( 'admin_post_stagekitwp_cpt_save', 'stagekitwp_handle_cpt_save' );
add_action( 'admin_post_stagekitwp_cpt_delete', 'stagekitwp_handle_cpt_delete' );
add_action( 'admin_post_stagekitwp_cpt_restore', 'stagekitwp_handle_cpt_restore' );

/**
 * Render an embedded hub for a group of CPT editor screens.
 *
 * @param string $page_title Title rendered at the top of the page.
 * @param array  $tabs       Tab definitions keyed by tab slug.
 * @param string $tab_param  Query arg used to persist the active tab.
 * @return void
 */
function stagekitwp_render_cpt_hub_page( $page_title, array $tabs, $tab_param, $page_slug ) {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( 'Insufficient permissions' );
	}

	if ( empty( $tabs ) ) {
		echo '<div class="wrap"><h1>' . esc_html( $page_title ) . '</h1><p>' . esc_html__( 'No content screens are available.', 'stagekitwp-core' ) . '</p></div>';
		return;
	}

	$active_tab = isset( $_GET[ $tab_param ] ) ? sanitize_key( wp_unslash( $_GET[ $tab_param ] ) ) : '';
	if ( ! isset( $tabs[ $active_tab ] ) ) {
		$active_tab = array_key_first( $tabs );
	}

	echo '<div class="wrap stagekitwp-cpt-hub">';
	echo '<h1>' . esc_html( $page_title ) . '</h1>';
	echo '<p class="description">' . esc_html__( 'Use the tabs below to create and edit content directly inside this hub.', 'stagekitwp-core' ) . '</p>';

	echo '<style>
		.stagekitwp-cpt-hub .stagekitwp-cpt-hub-tabs { margin-bottom: 16px; }
		.stagekitwp-cpt-hub .stagekitwp-cpt-hub-panel {
			background: #fff;
			border: 1px solid #c3c4c7;
			border-radius: 8px;
			padding: 16px;
		}
		.stagekitwp-cpt-hub .stagekitwp-cpt-hub-panel-grid {
			display: grid;
			grid-template-columns: minmax(280px, 360px) minmax(0, 1fr);
			gap: 20px;
			align-items: start;
		}
		.stagekitwp-cpt-hub .stagekitwp-cpt-hub-panel__header {
			display: flex;
			justify-content: space-between;
			gap: 12px;
			align-items: flex-start;
			margin-bottom: 12px;
		}
		.stagekitwp-cpt-hub .stagekitwp-cpt-hub-panel__title {
			margin: 0;
		}
		.stagekitwp-cpt-hub .stagekitwp-cpt-hub-panel__actions { margin-top: 8px; }
		.stagekitwp-cpt-hub .stagekitwp-cpt-list,
		.stagekitwp-cpt-hub .stagekitwp-cpt-editor {
			border: 1px solid #dcdcde;
			border-radius: 8px;
			background: #fff;
			padding: 16px;
		}
		.stagekitwp-cpt-hub .stagekitwp-cpt-list__items {
			margin: 0;
			padding: 0;
			list-style: none;
			max-height: 720px;
			overflow: auto;
		}
		.stagekitwp-cpt-hub .stagekitwp-cpt-list__item {
			display: flex;
			justify-content: space-between;
			gap: 12px;
			padding: 10px 0;
			border-bottom: 1px solid #f0f0f1;
		}
		.stagekitwp-cpt-hub .stagekitwp-cpt-list__item:last-child {
			border-bottom: 0;
		}
		.stagekitwp-cpt-hub .stagekitwp-cpt-list__meta {
			font-size: 12px;
			color: #646970;
		}
		.stagekitwp-cpt-hub .stagekitwp-cpt-editor form > .notice {
			margin: 0 0 16px;
		}
		.stagekitwp-cpt-hub .stagekitwp-cpt-editor .postbox-container {
			width: 100%;
			float: none;
		}
		.stagekitwp-cpt-hub .stagekitwp-cpt-editor .postbox {
			margin-bottom: 16px;
		}
	</style>';

	echo '<h2 class="nav-tab-wrapper stagekitwp-cpt-hub-tabs">';
	foreach ( $tabs as $key => $tab ) {
		$classes = 'nav-tab' . ( $key === $active_tab ? ' nav-tab-active' : '' );
		echo '<button type="button" class="' . esc_attr( $classes ) . '" data-stagekitwp-cpt-tab="' . esc_attr( $key ) . '">' . esc_html( $tab['label'] ) . '</button>';
	}
	echo '</h2>';

	echo '<div class="stagekitwp-cpt-hub-panels">';
	foreach ( $tabs as $key => $tab ) {
		echo '<section class="stagekitwp-cpt-hub-panel" data-stagekitwp-cpt-panel="' . esc_attr( $key ) . '" style="' . ( $key === $active_tab ? '' : 'display:none;' ) . '">';
		echo stagekitwp_render_cpt_workspace( $tab['post_type'], $tab['label'], $tab['description'] ?? '', $tab_param, $key, $page_slug );
		echo '</section>';
	}
	echo '</div>';

	echo '<script>(function(){const hub=document.querySelector(".stagekitwp-cpt-hub");if(!hub)return;const tabs=hub.querySelectorAll("[data-stagekitwp-cpt-tab]");const panels=hub.querySelectorAll("[data-stagekitwp-cpt-panel]");if(!tabs.length||!panels.length)return;const param=' . wp_json_encode( $tab_param ) . ';const active=' . wp_json_encode( $active_tab ) . ';function activate(tabKey){tabs.forEach(tab=>tab.classList.toggle("nav-tab-active",tab.dataset.stagekitwpCptTab===tabKey));panels.forEach(panel=>{panel.style.display=panel.dataset.stagekitwpCptPanel===tabKey?"":"none";});const url=new URL(window.location.href);url.searchParams.set(param,tabKey);history.replaceState({},"",url.toString());}tabs.forEach(tab=>tab.addEventListener("click",function(){activate(this.dataset.stagekitwpCptTab);}));activate(active);})();</script>';

	echo '</div>';
}

/**
 * Render a direct in-tab management workspace for one CPT.
 *
 * @param string $post_type Post type slug.
 * @param string $label     Human-readable label.
 * @param string $description Description text.
 * @param string $tab_param Query arg used for the active tab.
 * @param string $tab_key   Current tab key.
 * @param string $page_slug Owning submenu slug.
 * @return string
 */
function stagekitwp_render_cpt_workspace( $post_type, $label, $description, $tab_param, $tab_key, $page_slug ) {
	if ( ! post_type_exists( $post_type ) ) {
		$registrars = array(
			'advertiser'  => 'stagekitwp_register_advertisers_cpt',
			'award'       => 'stagekitwp_register_award_cpt',
			'board_member'=> 'stagekitwp_register_board_members_cpt',
			'cast'        => 'stagekitwp_register_cast_cpt',
			'contributor' => 'stagekitwp_register_contributor_cpt',
			'season'      => 'stagekitwp_register_season_cpt',
			'show'        => 'stagekitwp_register_show_cpt',
			'sponsor'     => 'stagekitwp_register_sponsor_cpt',
			'testimonial' => 'stagekitwp_register_testimonial_cpt',
			'venue'       => 'stagekitwp_register_venue_cpt',
		);
		$registrar = $registrars[ $post_type ] ?? '';
		if ( $registrar && function_exists( $registrar ) ) {
			call_user_func( $registrar );
		}
	}

	if ( ! post_type_exists( $post_type ) ) {
		return '<p>' . esc_html__( 'This content type is not available on the site.', 'stagekitwp-core' ) . '</p>';
	}

	$items = get_posts( array(
		'post_type'      => $post_type,
		'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
		'posts_per_page' => -1,
		'orderby'        => 'date',
		'order'          => 'DESC',
	) );
	$trashed_items = get_posts( array(
		'post_type'      => $post_type,
		'post_status'    => 'trash',
		'posts_per_page' => -1,
		'orderby'        => 'date',
		'order'          => 'DESC',
	) );

	$post_id = isset( $_GET['stagekitwp_cpt_item'] ) ? absint( wp_unslash( $_GET['stagekitwp_cpt_item'] ) ) : 0;
	$create_new = isset( $_GET['stagekitwp_cpt_new'] ) && '1' === (string) wp_unslash( $_GET['stagekitwp_cpt_new'] );
	if ( $post_id > 0 ) {
		$post = get_post( $post_id );
		if ( ! $post || $post->post_type !== $post_type ) {
			$post_id = 0;
			$create_new = false;
		}
	}

	if ( 0 === $post_id && ! $create_new ) {
		ob_start();
		wp_enqueue_editor();
		?>
		<div class="stagekitwp-cpt-hub-panel-grid">
			<aside class="stagekitwp-cpt-list">
				<h3><?php echo esc_html( $label ); ?> <?php esc_html_e( 'Items', 'stagekitwp-core' ); ?></h3>
				<p class="description"><?php echo esc_html( $description ); ?></p>
				<p>
					<a class="button button-primary" href="<?php echo esc_url( add_query_arg( array( 'page' => $page_slug, $tab_param => $tab_key, 'stagekitwp_cpt_new' => '1' ), admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Create New', 'stagekitwp-core' ); ?></a>
				</p>
				<ul class="stagekitwp-cpt-list__items">
					<?php foreach ( $items as $item ) : ?>
						<li class="stagekitwp-cpt-list__item">
							<div>
								<strong><?php echo esc_html( get_the_title( $item ) ?: __( '(no title)', 'stagekitwp-core' ) ); ?></strong>
								<div class="stagekitwp-cpt-list__meta"><?php echo esc_html( ucfirst( $item->post_status ) ); ?> · ID <?php echo (int) $item->ID; ?></div>
							</div>
							<div>
								<a class="button button-small" href="<?php echo esc_url( add_query_arg( array( 'page' => $page_slug, $tab_param => $tab_key, 'stagekitwp_cpt_item' => $item->ID ), admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Edit', 'stagekitwp-core' ); ?></a>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
				<?php if ( ! empty( $trashed_items ) ) : ?>
					<details class="stagekitwp-cpt-trash">
						<summary><?php printf( esc_html__( 'Trash (%d)', 'stagekitwp-core' ), count( $trashed_items ) ); ?></summary>
						<ul class="stagekitwp-cpt-list__items">
							<?php foreach ( $trashed_items as $item ) : ?>
								<li class="stagekitwp-cpt-list__item">
									<div>
										<strong><?php echo esc_html( get_the_title( $item ) ?: __( '(no title)', 'stagekitwp-core' ) ); ?></strong>
										<div class="stagekitwp-cpt-list__meta"><?php esc_html_e( 'Trashed', 'stagekitwp-core' ); ?> · ID <?php echo (int) $item->ID; ?></div>
									</div>
									<div>
										<a class="button button-small" href="<?php echo esc_url( add_query_arg( array( 'page' => $page_slug, $tab_param => $tab_key, 'stagekitwp_cpt_item' => $item->ID ), admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Edit', 'stagekitwp-core' ); ?></a>
										<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline; margin-left:6px;">
											<?php wp_nonce_field( 'stagekitwp_cpt_restore_' . $post_type . '_' . $item->ID, '_stagekitwp_cpt_nonce' ); ?>
											<input type="hidden" name="action" value="stagekitwp_cpt_restore">
											<input type="hidden" name="stagekitwp_cpt_type" value="<?php echo esc_attr( $post_type ); ?>">
											<input type="hidden" name="stagekitwp_cpt_post_id" value="<?php echo (int) $item->ID; ?>">
											<input type="hidden" name="stagekitwp_cpt_tab_param" value="<?php echo esc_attr( $tab_param ); ?>">
											<input type="hidden" name="stagekitwp_cpt_tab_key" value="<?php echo esc_attr( $tab_key ); ?>">
											<input type="hidden" name="stagekitwp_cpt_page_slug" value="<?php echo esc_attr( $page_slug ); ?>">
											<button type="submit" class="button button-small"><?php esc_html_e( 'Restore', 'stagekitwp-core' ); ?></button>
										</form>
									</div>
								</li>
							<?php endforeach; ?>
						</ul>
					</details>
				<?php endif; ?>
			</aside>
			<div class="stagekitwp-cpt-editor">
				<div class="notice notice-info inline"><p><?php esc_html_e( 'No item selected yet. Use Create New to open the editor.', 'stagekitwp-core' ); ?></p></div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	if ( 0 === $post_id ) {
		$post = new WP_Post( (object) array(
			'ID'           => 0,
			'post_author'  => get_current_user_id(),
			'post_date'    => current_time( 'mysql' ),
			'post_title'   => '',
			'post_status'  => 'draft',
			'post_type'    => $post_type,
			'post_name'    => '',
			'post_content' => '',
			'post_excerpt' => '',
		) );
	} else {
		$post = get_post( $post_id );
	}

	if ( ! $post instanceof WP_Post ) {
		return '<p>' . esc_html__( 'Unable to load the editor.', 'stagekitwp-core' ) . '</p>';
	}

	ob_start();
	wp_enqueue_editor();
	?>
	<div class="stagekitwp-cpt-hub-panel-grid">
		<aside class="stagekitwp-cpt-list">
			<h3><?php echo esc_html( $label ); ?> <?php esc_html_e( 'Items', 'stagekitwp-core' ); ?></h3>
			<p class="description"><?php echo esc_html( $description ); ?></p>
			<p>
				<a class="button button-primary" href="<?php echo esc_url( add_query_arg( array( 'page' => $page_slug, $tab_param => $tab_key, 'stagekitwp_cpt_new' => '1' ), admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Create New', 'stagekitwp-core' ); ?></a>
			</p>
			<ul class="stagekitwp-cpt-list__items">
				<?php foreach ( $items as $item ) : ?>
					<li class="stagekitwp-cpt-list__item">
						<div>
							<strong><?php echo esc_html( get_the_title( $item ) ?: __( '(no title)', 'stagekitwp-core' ) ); ?></strong>
							<div class="stagekitwp-cpt-list__meta"><?php echo esc_html( ucfirst( $item->post_status ) ); ?> · ID <?php echo (int) $item->ID; ?></div>
						</div>
						<div>
							<a class="button button-small" href="<?php echo esc_url( add_query_arg( array( 'page' => $page_slug, $tab_param => $tab_key, 'stagekitwp_cpt_item' => $item->ID ), admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Edit', 'stagekitwp-core' ); ?></a>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php if ( ! empty( $trashed_items ) ) : ?>
				<details class="stagekitwp-cpt-trash">
					<summary><?php printf( esc_html__( 'Trash (%d)', 'stagekitwp-core' ), count( $trashed_items ) ); ?></summary>
					<ul class="stagekitwp-cpt-list__items">
						<?php foreach ( $trashed_items as $item ) : ?>
							<li class="stagekitwp-cpt-list__item">
								<div>
									<strong><?php echo esc_html( get_the_title( $item ) ?: __( '(no title)', 'stagekitwp-core' ) ); ?></strong>
									<div class="stagekitwp-cpt-list__meta"><?php esc_html_e( 'Trashed', 'stagekitwp-core' ); ?> · ID <?php echo (int) $item->ID; ?></div>
								</div>
								<div>
									<a class="button button-small" href="<?php echo esc_url( add_query_arg( array( 'page' => $page_slug, $tab_param => $tab_key, 'stagekitwp_cpt_item' => $item->ID ), admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Edit', 'stagekitwp-core' ); ?></a>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline; margin-left:6px;">
										<?php wp_nonce_field( 'stagekitwp_cpt_restore_' . $post_type . '_' . $item->ID, '_stagekitwp_cpt_nonce' ); ?>
										<input type="hidden" name="action" value="stagekitwp_cpt_restore">
										<input type="hidden" name="stagekitwp_cpt_type" value="<?php echo esc_attr( $post_type ); ?>">
										<input type="hidden" name="stagekitwp_cpt_post_id" value="<?php echo (int) $item->ID; ?>">
										<input type="hidden" name="stagekitwp_cpt_tab_param" value="<?php echo esc_attr( $tab_param ); ?>">
										<input type="hidden" name="stagekitwp_cpt_tab_key" value="<?php echo esc_attr( $tab_key ); ?>">
										<input type="hidden" name="stagekitwp_cpt_page_slug" value="<?php echo esc_attr( $page_slug ); ?>">
										<button type="submit" class="button button-small"><?php esc_html_e( 'Restore', 'stagekitwp-core' ); ?></button>
									</form>
								</div>
							</li>
						<?php endforeach; ?>
					</ul>
				</details>
			<?php endif; ?>
		</aside>

		<div class="stagekitwp-cpt-editor">
			<h3><?php echo esc_html( $label ); ?> <?php esc_html_e( 'Editor', 'stagekitwp-core' ); ?></h3>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'stagekitwp_cpt_save_' . $post_type . '_' . $post->ID, '_stagekitwp_cpt_nonce' ); ?>
				<input type="hidden" name="action" value="stagekitwp_cpt_save">
				<input type="hidden" name="stagekitwp_cpt_type" value="<?php echo esc_attr( $post_type ); ?>">
				<input type="hidden" name="stagekitwp_cpt_post_id" value="<?php echo (int) $post->ID; ?>">
				<input type="hidden" name="stagekitwp_cpt_tab_param" value="<?php echo esc_attr( $tab_param ); ?>">
				<input type="hidden" name="stagekitwp_cpt_tab_key" value="<?php echo esc_attr( $tab_key ); ?>">
				<input type="hidden" name="stagekitwp_cpt_page_slug" value="<?php echo esc_attr( $page_slug ); ?>">
				<input type="hidden" name="post_type" value="<?php echo esc_attr( $post_type ); ?>">
				<input type="hidden" name="post_ID" value="<?php echo (int) $post->ID; ?>">
				<div class="notice notice-info inline"><p><?php esc_html_e( 'This editor saves directly inside the hub and keeps the current tab active.', 'stagekitwp-core' ); ?></p></div>
				<table class="form-table">
					<tr>
						<th scope="row"><label for="stagekitwp_cpt_post_title"><?php esc_html_e( 'Title', 'stagekitwp-core' ); ?></label></th>
						<td><input type="text" id="stagekitwp_cpt_post_title" name="stagekitwp_cpt_post_title" class="regular-text" value="<?php echo esc_attr( $post->post_title ); ?>"></td>
					</tr>
				</table>
				<?php
				do_action( 'add_meta_boxes', $post_type, $post );
				do_action( 'add_meta_boxes_' . $post_type, $post );
				do_meta_boxes( $post_type, 'normal', $post );
				do_meta_boxes( $post_type, 'advanced', $post );
				do_meta_boxes( $post_type, 'side', $post );
				?>
				<p class="submit">
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Save', 'stagekitwp-core' ); ?></button>
				</p>
			</form>
			<?php if ( $post->ID > 0 && 'auto-draft' !== $post->post_status ) : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('<?php echo esc_js( __( 'Delete this post? This will move it to the trash.', 'stagekitwp-core' ) ); ?>');">
					<?php wp_nonce_field( 'stagekitwp_cpt_save_' . $post_type . '_' . $post->ID, '_stagekitwp_cpt_nonce' ); ?>
					<input type="hidden" name="action" value="stagekitwp_cpt_delete">
					<input type="hidden" name="stagekitwp_cpt_type" value="<?php echo esc_attr( $post_type ); ?>">
					<input type="hidden" name="stagekitwp_cpt_post_id" value="<?php echo (int) $post->ID; ?>">
					<input type="hidden" name="stagekitwp_cpt_tab_param" value="<?php echo esc_attr( $tab_param ); ?>">
					<input type="hidden" name="stagekitwp_cpt_tab_key" value="<?php echo esc_attr( $tab_key ); ?>">
					<input type="hidden" name="stagekitwp_cpt_page_slug" value="<?php echo esc_attr( $page_slug ); ?>">
					<p class="submit">
						<button type="submit" class="button button-link-delete" formnovalidate><?php esc_html_e( 'Delete', 'stagekitwp-core' ); ?></button>
					</p>
				</form>
			<?php endif; ?>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Save a CPT item from the embedded hub.
 */
function stagekitwp_handle_cpt_save() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( esc_html__( 'Insufficient permissions', 'stagekitwp-core' ) );
	}

	$post_type = isset( $_POST['stagekitwp_cpt_type'] ) ? sanitize_key( wp_unslash( $_POST['stagekitwp_cpt_type'] ) ) : '';
	$post_id   = isset( $_POST['stagekitwp_cpt_post_id'] ) ? absint( $_POST['stagekitwp_cpt_post_id'] ) : 0;
	$nonce     = isset( $_POST['_stagekitwp_cpt_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_stagekitwp_cpt_nonce'] ) ) : '';
	$tab_param = isset( $_POST['stagekitwp_cpt_tab_param'] ) ? sanitize_key( wp_unslash( $_POST['stagekitwp_cpt_tab_param'] ) ) : '';
	$tab_key   = isset( $_POST['stagekitwp_cpt_tab_key'] ) ? sanitize_key( wp_unslash( $_POST['stagekitwp_cpt_tab_key'] ) ) : '';
	$page_slug = isset( $_POST['stagekitwp_cpt_page_slug'] ) ? sanitize_key( wp_unslash( $_POST['stagekitwp_cpt_page_slug'] ) ) : 'stagekitwp-core';

	if ( ! $post_type || ! post_type_exists( $post_type ) ) {
		wp_die( esc_html__( 'Invalid content type.', 'stagekitwp-core' ) );
	}

	if ( ! $nonce || ! wp_verify_nonce( $nonce, 'stagekitwp_cpt_save_' . $post_type . '_' . $post_id ) ) {
		wp_die( esc_html__( 'Nonce verification failed.', 'stagekitwp-core' ) );
	}

	$title = isset( $_POST['stagekitwp_cpt_post_title'] ) ? sanitize_text_field( wp_unslash( $_POST['stagekitwp_cpt_post_title'] ) ) : '';
	$status = isset( $_POST['post_status'] ) ? sanitize_key( wp_unslash( $_POST['post_status'] ) ) : 'publish';
	if ( ! in_array( $status, array( 'publish', 'draft', 'pending', 'private' ), true ) ) {
		$status = 'publish';
	}

	$save_args = array(
		'ID'          => $post_id,
		'post_type'   => $post_type,
		'post_title'  => $title,
		'post_status' => $status,
	);

	if ( 0 === $post_id ) {
		$post_id = wp_insert_post( $save_args, true );
	} else {
		$save_args['ID'] = $post_id;
		$post_id = wp_update_post( $save_args, true );
	}

	if ( is_wp_error( $post_id ) ) {
		wp_die( esc_html( $post_id->get_error_message() ) );
	}

	$redirect = add_query_arg(
		array(
			'page'         => $page_slug,
			$tab_param     => $tab_key,
			'stagekitwp_cpt_item'  => (int) $post_id,
			'stagekitwp_cpt_saved' => '1',
		),
		admin_url( 'admin.php' )
	);

	wp_safe_redirect( $redirect );
	exit;
}

/**
 * Delete a CPT item from the embedded hub.
 */
function stagekitwp_handle_cpt_delete() {
	if ( ! current_user_can( 'delete_posts' ) ) {
		wp_die( esc_html__( 'Insufficient permissions.', 'stagekitwp-core' ) );
	}

	$post_type = isset( $_POST['stagekitwp_cpt_type'] ) ? sanitize_key( wp_unslash( $_POST['stagekitwp_cpt_type'] ) ) : '';
	$post_id   = isset( $_POST['stagekitwp_cpt_post_id'] ) ? absint( $_POST['stagekitwp_cpt_post_id'] ) : 0;
	$nonce     = isset( $_POST['_stagekitwp_cpt_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_stagekitwp_cpt_nonce'] ) ) : '';
	$tab_param = isset( $_POST['stagekitwp_cpt_tab_param'] ) ? sanitize_key( wp_unslash( $_POST['stagekitwp_cpt_tab_param'] ) ) : '';
	$tab_key   = isset( $_POST['stagekitwp_cpt_tab_key'] ) ? sanitize_key( wp_unslash( $_POST['stagekitwp_cpt_tab_key'] ) ) : '';
	$page_slug = isset( $_POST['stagekitwp_cpt_page_slug'] ) ? sanitize_key( wp_unslash( $_POST['stagekitwp_cpt_page_slug'] ) ) : 'stagekitwp-core';

	if ( ! $post_type || ! post_type_exists( $post_type ) ) {
		wp_die( esc_html__( 'Invalid content type.', 'stagekitwp-core' ) );
	}

	if ( ! $post_id || ! $nonce || ! wp_verify_nonce( $nonce, 'stagekitwp_cpt_save_' . $post_type . '_' . $post_id ) ) {
		wp_die( esc_html__( 'Nonce verification failed.', 'stagekitwp-core' ) );
	}

	if ( ! current_user_can( 'delete_post', $post_id ) ) {
		wp_die( esc_html__( 'Insufficient permissions.', 'stagekitwp-core' ) );
	}

	wp_trash_post( $post_id );

	$redirect = add_query_arg(
		array(
			'page'         => $page_slug,
			$tab_param     => $tab_key,
			'stagekitwp_cpt_saved' => '1',
		),
		admin_url( 'admin.php' )
	);

	wp_safe_redirect( $redirect );
	exit;
}

/**
 * Restore a CPT item from trash.
 */
function stagekitwp_handle_cpt_restore() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( esc_html__( 'Insufficient permissions.', 'stagekitwp-core' ) );
	}

	$post_type = isset( $_POST['stagekitwp_cpt_type'] ) ? sanitize_key( wp_unslash( $_POST['stagekitwp_cpt_type'] ) ) : '';
	$post_id   = isset( $_POST['stagekitwp_cpt_post_id'] ) ? absint( $_POST['stagekitwp_cpt_post_id'] ) : 0;
	$nonce     = isset( $_POST['_stagekitwp_cpt_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_stagekitwp_cpt_nonce'] ) ) : '';
	$tab_param = isset( $_POST['stagekitwp_cpt_tab_param'] ) ? sanitize_key( wp_unslash( $_POST['stagekitwp_cpt_tab_param'] ) ) : '';
	$tab_key   = isset( $_POST['stagekitwp_cpt_tab_key'] ) ? sanitize_key( wp_unslash( $_POST['stagekitwp_cpt_tab_key'] ) ) : '';
	$page_slug = isset( $_POST['stagekitwp_cpt_page_slug'] ) ? sanitize_key( wp_unslash( $_POST['stagekitwp_cpt_page_slug'] ) ) : 'stagekitwp-core';

	if ( ! $post_type || ! post_type_exists( $post_type ) ) {
		wp_die( esc_html__( 'Invalid content type.', 'stagekitwp-core' ) );
	}

	if ( ! $post_id || ! $nonce || ! wp_verify_nonce( $nonce, 'stagekitwp_cpt_restore_' . $post_type . '_' . $post_id ) ) {
		wp_die( esc_html__( 'Nonce verification failed.', 'stagekitwp-core' ) );
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		wp_die( esc_html__( 'Insufficient permissions.', 'stagekitwp-core' ) );
	}

	wp_untrash_post( $post_id );

	$redirect = add_query_arg(
		array(
			'page'         => $page_slug,
			$tab_param     => $tab_key,
			'stagekitwp_cpt_item'  => $post_id,
			'stagekitwp_cpt_saved' => '1',
		),
		admin_url( 'admin.php' )
	);

	wp_safe_redirect( $redirect );
	exit;
}

/**
 * Season-linked CPT hub.
 */
function stagekitwp_season_content_page() {
	$tabs = array(
		'seasons' => array(
			'label'      => __( 'Seasons', 'stagekitwp-core' ),
			'post_type'   => 'season',
			'description' => __( 'Season records and date ranges.', 'stagekitwp-core' ),
		),
		'shows' => array(
			'label'      => __( 'Shows', 'stagekitwp-core' ),
			'post_type'   => 'show',
			'description' => __( 'Productions linked to each season.', 'stagekitwp-core' ),
		),
		'cast' => array(
			'label'      => __( 'Cast', 'stagekitwp-core' ),
			'post_type'   => 'cast',
			'description' => __( 'Cast members tied to shows.', 'stagekitwp-core' ),
		),
		'awards' => array(
			'label'      => __( 'Awards', 'stagekitwp-core' ),
			'post_type'   => 'award',
			'description' => __( 'Award records associated with shows and seasons.', 'stagekitwp-core' ),
		),
	);

	stagekitwp_render_cpt_hub_page( __( 'Season Content', 'stagekitwp-core' ), $tabs, 'stagekitwp_season_cpt_tab', 'stagekitwp-season-content' );
}

/**
 * Other StageKitWP CPT hub.
 */
function stagekitwp_people_places_partners_page() {
	$tabs = array(
		'venues' => array(
			'label'      => __( 'Venues', 'stagekitwp-core' ),
			'post_type'   => 'venue',
			'description' => __( 'Performance locations and venue details.', 'stagekitwp-core' ),
		),
		'board-members' => array(
			'label'      => __( 'Board Members', 'stagekitwp-core' ),
			'post_type'   => 'board_member',
			'description' => __( 'Board member profiles and roles.', 'stagekitwp-core' ),
		),
		'contributors' => array(
			'label'      => __( 'Contributors', 'stagekitwp-core' ),
			'post_type'   => 'contributor',
			'description' => __( 'Donors and supporters.', 'stagekitwp-core' ),
		),
		'sponsors' => array(
			'label'      => __( 'Sponsors', 'stagekitwp-core' ),
			'post_type'   => 'sponsor',
			'description' => __( 'Sponsor listings and logos.', 'stagekitwp-core' ),
		),
		'testimonials' => array(
			'label'      => __( 'Testimonials', 'stagekitwp-core' ),
			'post_type'   => 'testimonial',
			'description' => __( 'Audience testimonials and reviews.', 'stagekitwp-core' ),
		),
		'advertisers' => array(
			'label'      => __( 'Advertisers', 'stagekitwp-core' ),
			'post_type'   => 'advertiser',
			'description' => __( 'Advertiser and partner listings.', 'stagekitwp-core' ),
		),
	);

	stagekitwp_render_cpt_hub_page( __( 'People, Places & Partners', 'stagekitwp-core' ), $tabs, 'stagekitwp_partner_cpt_tab', 'stagekitwp-people-places-partners' );
}

/**
 * Render the StageKitWP hub tabs.
 *
 * @param string $active Active tab key.
 * @return string
 */
function stagekitwp_render_admin_hub_tabs( $active ) {
	$tabs = array(
		'dashboard'          => array( 'label' => __( 'Dashboard', 'stagekitwp-core' ) ),
		'season-builder'     => array( 'label' => __( 'Season Builder', 'stagekitwp-core' ) ),
		'display-options'    => array( 'label' => __( 'Display Options', 'stagekitwp-core' ) ),
		'instructions'       => array( 'label' => __( 'Instructions', 'stagekitwp-core' ) ),
		'regenerate-previews' => array( 'label' => __( 'PDF Previews', 'stagekitwp-core' ) ),
		'settings'           => array( 'label' => __( 'Settings', 'stagekitwp-core' ) ),
	);

	$html = '<nav class="nav-tab-wrapper stagekitwp-core-hub-tabs" style="margin-bottom:16px;">';
	foreach ( $tabs as $key => $tab ) {
		$classes = 'nav-tab' . ( $key === $active ? ' nav-tab-active' : '' );
		$html   .= sprintf(
			'<button type="button" class="%1$s" data-stagekitwp-core-tab="%2$s">%3$s</button>',
			esc_attr( $classes ),
			esc_attr( $key ),
			esc_html( $tab['label'] )
		);
	}
	$html .= '</nav>';

	return $html;
}

/**
 * Capture a page callback so it can be embedded inside the hub.
 *
 * @param callable $callback Callback to render.
 * @return string
 */
function stagekitwp_capture_admin_render( $callback ) {
	if ( ! is_callable( $callback ) ) {
		return '<p>' . esc_html__( 'Content unavailable.', 'stagekitwp-core' ) . '</p>';
	}

	ob_start();
	call_user_func( $callback );
	return ob_get_clean();
}

/**
 * Collect installation and activation status for the theme and StageKitWP plugins.
 */
function stagekitwp_get_dashboard_health_components() {
	include_once ABSPATH . 'wp-admin/includes/plugin.php';

	$components = array(
		'theme' => array(
			'name'      => __( 'StageKitWP Theme', 'stagekitwp-core' ),
			'installed' => wp_get_theme( 'stagekitwp-theme' )->exists(),
			'active'    => get_stylesheet() === 'stagekitwp-theme' || get_template() === 'stagekitwp-theme',
		),
		'core' => array(
			'name'      => __( 'StageKitWP Core', 'stagekitwp-core' ),
			'installed' => file_exists( WP_PLUGIN_DIR . '/stagekitwp-core/stagekitwp-core.php' ),
			'active'    => is_plugin_active( 'stagekitwp-core/stagekitwp-core.php' ),
		),
		'import_export' => array(
			'name'      => __( 'Import / Export', 'stagekitwp-core' ),
			'installed' => file_exists( WP_PLUGIN_DIR . '/stagekitwp-import-export/stagekitwp-import-export.php' ),
			'active'    => is_plugin_active( 'stagekitwp-import-export/stagekitwp-import-export.php' ),
		),
		'members' => array(
			'name'      => __( 'Members', 'stagekitwp-core' ),
			'installed' => file_exists( WP_PLUGIN_DIR . '/stagekitwp-members/stagekitwp-members.php' ),
			'active'    => is_plugin_active( 'stagekitwp-members/stagekitwp-members.php' ),
		),
		'rc_library' => array(
			'name'      => __( 'RC Library', 'stagekitwp-core' ),
			'installed' => file_exists( WP_PLUGIN_DIR . '/stagekitwp-rc-library/stagekitwp-rc-library.php' ),
			'active'    => is_plugin_active( 'stagekitwp-rc-library/stagekitwp-rc-library.php' ),
		),
		'sync' => array(
			'name'      => __( 'Sync', 'stagekitwp-core' ),
			'installed' => file_exists( WP_PLUGIN_DIR . '/stagekitwp-sync/stagekitwp-sync.php' ),
			'active'    => is_plugin_active( 'stagekitwp-sync/stagekitwp-sync.php' ),
		),
	);

	return $components;
}

/**
 * Render the health status page for the StageKitWP dashboard hub.
 */
function stagekitwp_render_health_page() {
	$components = stagekitwp_get_dashboard_health_components();

	echo '<div class="wrap stagekitwp-dashboard-wrap">';
	echo '<h2>🩺 ' . esc_html__( 'Health Check', 'stagekitwp-core' ) . '</h2>';
	echo '<p>' . esc_html__( 'Review the install state and active state of the StageKitWP theme and each supporting plugin.', 'stagekitwp-core' ) . '</p>';
	echo '<table class="widefat striped">';
	echo '<thead><tr><th>' . esc_html__( 'Component', 'stagekitwp-core' ) . '</th><th>' . esc_html__( 'Install Status', 'stagekitwp-core' ) . '</th><th>' . esc_html__( 'Activated Status', 'stagekitwp-core' ) . '</th></tr></thead>';
	echo '<tbody>';

	foreach ( $components as $component ) {
		echo '<tr>';
		echo '<td><strong>' . esc_html( $component['name'] ) . '</strong></td>';
		echo '<td>';
		if ( ! empty( $component['installed'] ) ) {
			echo '<span style="color:#1d7f3d; font-weight:700;">✅ ' . esc_html__( 'Installed', 'stagekitwp-core' ) . '</span>';
		} else {
			echo '<span style="color:#b32d16; font-weight:700;">❌ ' . esc_html__( 'Not Installed', 'stagekitwp-core' ) . '</span>';
		}
		echo '</td>';
		echo '<td>';
		if ( ! empty( $component['active'] ) ) {
			echo '<span style="color:#1d7f3d; font-weight:700;">✅ ' . esc_html__( 'Activated', 'stagekitwp-core' ) . '</span>';
		} else {
			echo '<span style="color:#b32d16; font-weight:700;">❌ ' . esc_html__( 'Inactive', 'stagekitwp-core' ) . '</span>';
		}
		echo '</td>';
		echo '</tr>';
	}

	echo '</tbody></table>';
	echo '</div>';
}

/**
 * Render the StageKitWP dashboard overview content.
 */
function stagekitwp_render_dashboard_overview() {
	global $wp_version;

	// =========================================================================
	// POST REQUEST CONTROLLER: DATA ENGINE PIPELINE ROUTING
	// =========================================================================
	$action_notice = '';
	
	$blueprint_nonce = isset( $_POST['stagekitwp_blueprint_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['stagekitwp_blueprint_nonce'] ) ) : '';
	if (
		current_user_can( 'edit_theme_options' ) &&
		$blueprint_nonce &&
		wp_verify_nonce( $blueprint_nonce, 'stagekitwp_blueprint_action' )
	) {
		
		if ( isset( $_POST['stagekitwp_add_blueprints'] ) ) {
			// --- 1. PROVISION MOCK POST DATA FOR CORE THEATRE CPTs ---
			$season_ids = array();
			if ( post_type_exists( 'season' ) ) {
				$season_ids['current'] = wp_insert_post( array(
					'post_title'  => '2026 Continental Mainstage Season',
					'post_status' => 'publish',
					'post_type'   => 'season',
					'meta_input'  => array( '_stagekitwp_season_is_current' => '1', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31' )
				) );
				$season_ids['next'] = wp_insert_post( array(
					'post_title'  => '2027 Avant-Garde Horizon Lineup',
					'post_status' => 'publish',
					'post_type'   => 'season',
					'meta_input'  => array( '_stagekitwp_season_is_current' => '0', 'start_date' => '2027-01-01', 'end_date' => '2027-12-31' )
				) );
			}

			if ( post_type_exists( 'show' ) ) {
				wp_insert_post( array(
					'post_title'  => 'The Mousetrap',
					'post_status' => 'publish',
					'post_type'   => 'show',
					'meta_input'  => array( '_stagekitwp_show_season' => isset( $season_ids['current'] ) ? $season_ids['current'] : 0 )
				) );
			}
		}
	}

	$plugin_version = defined( 'STAGEKITWP_CORE_VERSION' ) ? STAGEKITWP_CORE_VERSION : '—';
	$theme_active   = function_exists( 'stagekitwp_theme_is_active' ) && stagekitwp_theme_is_active();
	$switcher_on    = $theme_active && (bool) stagekitwp_get_theme_mod( 'stagekitwp_enable_frontend_switcher', false );
	$current_mode   = $theme_active ? stagekitwp_get_theme_mod( 'stagekitwp_color_mode', 'light' ) : 'n/a';
	$theme_version  = $theme_active ? wp_get_theme()->get( 'Version' ) : '—';

	echo '<div class="wrap stagekitwp-dashboard-wrap">';
	echo '<h1>🎭 StageKitWP <span class="stagekitwp-version-badge">v' . esc_html( $plugin_version ) . '</span></h1>';

	// ── Status Panel ──────────────────────────────────────────────────────────
	echo '<div class="stagekitwp-status-panel">';
	echo '<h2 class="stagekitwp-status-heading">📊 Theme &amp; Dark Mode Status</h2>';
	echo '<table class="stagekitwp-status-table widefat striped">';
	echo '<thead><tr><th>Check</th><th>Status</th><th>Details</th></tr></thead><tbody>';

	// Row 1 — Theme
	$t_ok = $theme_active;
	echo '<tr>';
	echo '<td><strong>' . ( $t_ok ? '✅' : '❌' ) . '&nbsp; StageKitWP Theme</strong></td>';
	echo '<td>' . ( $t_ok ? '<span class="stagekitwp-badge stagekitwp-badge-ok">Active</span>' : '<span class="stagekitwp-badge stagekitwp-badge-err">Inactive</span>' ) . '</td>';
	echo '<td class="stagekitwp-status-detail">' . ( $t_ok ? 'StageKitWP Theme v' . esc_html( $theme_version ) . ' is the active theme.' : 'The StageKitWP Theme is not active. Dark-mode shortcode integration is disabled.' ) . '</td>';
	echo '</tr>';

	// Row 2 — Switcher
	$s_ok = $switcher_on;
	echo '<tr>';
	echo '<td><strong>' . ( $s_ok ? '✅' : ( $theme_active ? '⚠️' : '—' ) ) . '&nbsp; Mode Switcher</strong></td>';
	echo '<td>' . ( $s_ok ? '<span class="stagekitwp-badge stagekitwp-badge-ok">Enabled</span>' : ( $theme_active ? '<span class="stagekitwp-badge stagekitwp-badge-warn">Disabled</span>' : '<span class="stagekitwp-badge stagekitwp-badge-muted">N/A</span>' ) ) . '</td>';
	echo '<td class="stagekitwp-status-detail">' . ( $s_ok ? 'Visitors see the 🌙/☀ toggle. Shortcode dark colours are active.' : ( $theme_active ? 'Enable in Appearance → Customize → 🎨 Color Settings → Global Theme Mode.' : 'Requires the StageKitWP Theme to be active.' ) ) . '</td>';
	echo '</tr>';

	// Row 3 — Default mode
	$mode_icon = $current_mode === 'dark' ? '🌙' : ( $current_mode === 'light' ? '☀️' : '—' );
	echo '<tr>';
	echo '<td><strong>' . $mode_icon . '&nbsp; Default Mode</strong></td>';
	echo '<td>' . ( $current_mode !== 'n/a' ? '<span class="stagekitwp-badge ' . ( $current_mode === 'dark' ? 'stagekitwp-badge-dark' : 'stagekitwp-badge-light' ) . '">' . ucfirst( esc_html( $current_mode ) ) . '</span>' : '<span class="stagekitwp-badge stagekitwp-badge-muted">N/A</span>' ) . '</td>';
	echo '<td class="stagekitwp-status-detail">' . ( $current_mode !== 'n/a' ? 'Server default. Visitors can override with the toggle (stored in localStorage).' : 'Not applicable — StageKitWP Theme is not active.' ) . '</td>';
	echo '</tr>';

	// Row 4 — Shortcode integration
	$i_ok = $theme_active && $switcher_on;
	echo '<tr>';
	echo '<td><strong>' . ( $i_ok ? '✅' : ( $theme_active ? '⚠️' : '❌' ) ) . '&nbsp; Shortcode Integration</strong></td>';
	echo '<td>' . ( $i_ok ? '<span class="stagekitwp-badge stagekitwp-badge-ok">Active</span>' : ( $theme_active ? '<span class="stagekitwp-badge stagekitwp-badge-warn">Partial</span>' : '<span class="stagekitwp-badge stagekitwp-badge-err">Off</span>' ) ) . '</td>';
	echo '<td class="stagekitwp-status-detail">' . ( $i_ok ? 'Dark colour overrides are live for all 12 shortcode tabs. Display Options dark pickers are visible.' : ( $theme_active ? 'Theme detected but switcher is off. Enable the switcher to activate dark-mode shortcode integration.' : 'Shortcodes use Display Options light colours only.' ) ) . '</td>';
	echo '</tr>';

	echo '</tbody></table>';

	if ( $theme_active ) {
		echo '<p class="stagekitwp-status-footer">';
		echo '<a href="' . esc_url( admin_url( 'customize.php' ) ) . '" class="button">🎨 Open Customizer</a>&nbsp;&nbsp;';
		if ( current_user_can( 'manage_options' ) ) {
			echo '<a href="' . esc_url( admin_url( 'admin.php?page=stagekitwp-core&stagekitwp_admin_tab=display-options' ) ) . '" class="button button-primary">Display Options</a>';
		}
		echo '</p>';
	}
	echo '</div>'; // .stagekitwp-status-panel

	// ── Quick-access card grid ─────────────────────────────────────────────
	echo '<h2 style="margin-top:32px;">Quick Access</h2>';
	echo '<div class="stagekitwp-dashboard-grid">';

	$cards = array(
		array(
			'icon'  => '📅',
			'title' => 'Season Builder',
			'desc'  => 'Manage seasons, shows, cast, and awards in one place.',
			'url'   => admin_url( 'admin.php?page=stagekitwp-core&stagekitwp_admin_tab=season-builder' ),
			'btn'   => 'Open Builder',
			'cap'   => 'edit_posts',
		),
		array(
			'icon'  => '🎨',
			'title' => 'Display Options',
			'desc'  => 'Customise fonts, colours, borders, and layouts for each shortcode.',
			'url'   => admin_url( 'admin.php?page=stagekitwp-core&stagekitwp_admin_tab=display-options' ),
			'btn'   => 'Display Options',
			'cap'   => 'manage_options',
		),
		array(
			'icon'  => '⚙️',
			'title' => 'Settings',
			'desc'  => 'Google Maps API key, sidebar CPT visibility, and more.',
			'url'   => admin_url( 'admin.php?page=stagekitwp-core&stagekitwp_admin_tab=settings' ),
			'btn'   => 'Settings',
			'cap'   => 'manage_options',
		),
		array(
			'icon'  => '📖',
			'title' => 'Instructions',
			'desc'  => 'Shortcode reference, parameters, and best-practice tips.',
			'url'   => admin_url( 'admin.php?page=stagekitwp-core&stagekitwp_admin_tab=instructions' ),
			'btn'   => 'View Docs',
			'cap'   => 'edit_posts',
		),
		array(
			'icon'  => '🖼️',
			'title' => 'Regenerate PDF Previews',
			'desc'  => 'Rebuild first-page JPEG previews for all PDF attachments.',
			'url'   => admin_url( 'admin.php?page=stagekitwp-core&stagekitwp_admin_tab=regenerate-previews' ),
			'btn'   => 'Regenerate',
			'cap'   => 'manage_options',
		),
		array(
			'icon'  => '📄',
			'title' => 'Sample Pages',
			'desc'  => 'Create or delete demo pages for every shortcode.',
			'url'   => admin_url( 'tools.php?page=stagekitwp-sample-content' ),
			'btn'   => 'Manage Pages',
			'cap'   => 'manage_options',
		),
	);

	foreach ( $cards as $card ) {
		if ( isset( $card['cap'] ) && ! current_user_can( $card['cap'] ) ) {
			continue;
		}
		echo '<div class="stagekitwp-dashboard-card">';
		echo '<div class="stagekitwp-dashboard-card-icon">' . $card['icon'] . '</div>';
		echo '<h3>' . esc_html( $card['title'] ) . '</h3>';
		echo '<p>' . esc_html( $card['desc'] ) . '</p>';
		echo '<a href="' . esc_url( $card['url'] ) . '" class="button button-primary">' . esc_html( $card['btn'] ) . '</a>';
		echo '</div>';
	}

	echo '</div>'; // .stagekitwp-dashboard-grid
	echo '</div>'; // .wrap
}

/**
 * Render the core hub shell and tab panels.
 */
function stagekitwp_dashboard_page() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( 'Insufficient permissions' );
	}

	$tabs = array(
		'dashboard'           => array( 'label' => __( 'Dashboard', 'stagekitwp-core' ), 'cap' => 'edit_posts', 'callback' => 'stagekitwp_render_dashboard_overview' ),
		'health'              => array( 'label' => __( 'Health', 'stagekitwp-core' ), 'cap' => 'edit_posts', 'callback' => 'stagekitwp_render_health_page' ),
		'season-builder'      => array( 'label' => __( 'Season Builder', 'stagekitwp-core' ), 'cap' => 'edit_posts', 'callback' => 'stagekitwp_render_season_builder_page' ),
		'display-options'     => array( 'label' => __( 'Display Options', 'stagekitwp-core' ), 'cap' => 'manage_options', 'callback' => 'stagekitwp_display_options_page' ),
		'instructions'        => array( 'label' => __( 'Instructions', 'stagekitwp-core' ), 'cap' => 'edit_posts', 'callback' => 'stagekitwp_instructions_page' ),
		'regenerate-previews' => array( 'label' => __( 'PDF Previews', 'stagekitwp-core' ), 'cap' => 'manage_options', 'callback' => 'stagekitwp_regenerate_previews_page' ),
		'settings'            => array( 'label' => __( 'Settings', 'stagekitwp-core' ), 'cap' => 'manage_options', 'callback' => 'stagekitwp_settings_page' ),
	);

	$tabs = array_filter(
		$tabs,
		function( $tab ) {
			return ! isset( $tab['cap'] ) || current_user_can( $tab['cap'] );
		}
	);

	$active_tab = isset( $_GET['stagekitwp_admin_tab'] ) ? sanitize_key( wp_unslash( $_GET['stagekitwp_admin_tab'] ) ) : 'dashboard';
	if ( ! isset( $tabs[ $active_tab ] ) ) {
		$active_tab = 'dashboard';
	}

	echo '<div class="wrap stagekitwp-core-hub">';
	echo '<div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;margin-bottom:12px;">';
	echo '<img src="' . esc_url( plugins_url( 'assets/icons/stagekitwp-icon.svg', dirname( __DIR__ ) . '/stagekitwp-core.php' ) ) . '" alt="StageKitWP" style="width:54px;height:54px;display:block;background-color:#000000" />';
	echo '<h1 style="margin:0;">StageKitWP</h1>';
	echo '</div>';
	echo stagekitwp_render_admin_hub_tabs( $active_tab );
	echo '<div class="stagekitwp-core-hub-panels">';

	foreach ( $tabs as $key => $tab ) {
		echo '<div class="stagekitwp-core-hub-panel" data-stagekitwp-core-panel="' . esc_attr( $key ) . '" style="' . ( $key === $active_tab ? '' : 'display:none;' ) . '">';
		echo stagekitwp_capture_admin_render( $tab['callback'] );
		echo '</div>';
	}

	echo '</div>';
	echo '</div>';

	echo '<script>(function(){const tabs=document.querySelectorAll("[data-stagekitwp-core-tab]");const panels=document.querySelectorAll("[data-stagekitwp-core-panel]");if(!tabs.length||!panels.length)return;function activate(tabKey){tabs.forEach(tab=>tab.classList.toggle("nav-tab-active",tab.dataset.stagekitwpCoreTab===tabKey));panels.forEach(panel=>panel.style.display=(panel.dataset.stagekitwpCorePanel===tabKey)?"":"none");const url=new URL(window.location.href);url.searchParams.set("stagekitwp_admin_tab",tabKey);history.replaceState({},"",url.toString());}tabs.forEach(tab=>tab.addEventListener("click",function(){activate(this.dataset.stagekitwpCoreTab);}));})();</script>';
}

/**
 * After ALL admin_menu hooks have fired (including CPT show_in_menu injections),
 * re-sort $submenu['stagekitwp-core'] so the Dashboard entry (slug = 'stagekitwp-core')
 * is always first. This ensures clicking the top-level menu item always opens
 * the dashboard regardless of how many CPTs inject themselves alphabetically.
 */
function stagekitwp_pin_dashboard_submenu() {
    global $submenu;
    if ( empty( $submenu['stagekitwp-core'] ) || ! is_array( $submenu['stagekitwp-core'] ) ) {
        return;
    }

    $dashboard_entry = null;
    $rest            = array();

    foreach ( $submenu['stagekitwp-core'] as $item ) {
        // The dashboard submenu entry has slug 'stagekitwp-core' (index 2).
        if ( isset( $item[2] ) && $item[2] === 'stagekitwp-core' && $dashboard_entry === null ) {
            $dashboard_entry = $item;
        } else {
            $rest[] = $item;
        }
    }

    if ( $dashboard_entry !== null ) {
        $submenu['stagekitwp-core'] = array_merge( array( $dashboard_entry ), $rest );
    }
}
add_action('admin_menu', 'stagekitwp_pin_dashboard_submenu', 9999);

/**
 * Keep the StageKitWP sidebar concise by hiding deep module pages.
 * Pages remain accessible by direct URL and via each module's hub screen.
 */
function stagekitwp_streamline_suite_submenus() {
	$parent_slug = 'stagekitwp-core';

	$hidden_slugs = array(
		// TM Sync.
		'stagekitwp-sync-auth',
		'stagekitwp-sync-logs',
		'stagekitwp-sync-details',
		'stagekitwp-sync-settings',

		// TM I/O.
		'stagekitwp-io-import',
		'stagekitwp-io-remap',
		'stagekitwp-io-purge',
		'stagekitwp-io-settings',
		'stagekitwp-io-help',

		// TM Members.
		'stagekitwp-ma-members',
		'stagekitwp-ma-invitations',
		'stagekitwp-ma-export',
		'stagekitwp-ma-settings',
		'stagekitwp-ma-health',
		'stagekitwp-ma-email-logs',
		'stagekitwp-ma-email-queue',
		'edit.php?post_type=stagekitwp_ann',
		'edit.php?post_type=stagekitwp_event',
		'edit.php?post_type=stagekitwp_email',

		// TM RC Library.
		'edit.php?post_type=stagekitwp_rubric',
		'edit.php?post_type=stagekitwp_template',
		'edit.php?post_type=stagekitwp_book',
		'stagekitwp-rcl-groups',
		'stagekitwp-rcl-reports',
		'stagekitwp-rcl-help',
	);

	foreach ( $hidden_slugs as $menu_slug ) {
		remove_submenu_page( $parent_slug, $menu_slug );
	}
}
add_action( 'admin_menu', 'stagekitwp_streamline_suite_submenus', 10000 );

/**
 * Admin page: Regenerate PDF previews for attachments
 */
function stagekitwp_regenerate_previews_page() {
	if (!current_user_can('manage_options')) {
		wp_die('Insufficient permissions');
	}

	echo '<div class="wrap">';
	echo '<h1>Regenerate PDF Previews</h1>';

	if (isset($_POST['stagekitwp_regenerate_previews_nonce'])) {
		$nonce = sanitize_text_field(wp_unslash($_POST['stagekitwp_regenerate_previews_nonce']));
		if (!wp_verify_nonce($nonce, 'stagekitwp_regenerate_previews_action')) {
			echo '<div class="notice notice-error"><p>Nonce verification failed.</p></div>';
		} else {
			// Run regeneration
			$args = array(
				'post_type' => 'attachment',
				'post_mime_type' => 'application/pdf',
				'numberposts' => -1
			);
			$pdfs = get_posts($args);
			$count = 0;
			$failed = 0;
			$messages = array();
			foreach ($pdfs as $p) {
				delete_post_meta($p->ID, '_stagekitwp_pdf_preview');
				// Call the generator directly and collect diagnostics
				if (function_exists('stagekitwp_generate_pdf_preview')) {
					$res = stagekitwp_generate_pdf_preview($p->ID);
					if (is_array($res) && !empty($res['success'])) {
						$count++;
					} else {
						$failed++;
						$msg = is_array($res) && !empty($res['message']) ? $res['message'] : 'Unknown error';
						$messages[] = sprintf('%s: FAILED (%s)', esc_html($p->post_title), esc_html($msg));
					}
				} else {
					$messages[] = sprintf('%s: FAILED (generator not available)', esc_html($p->post_title));
				}
			}

			echo '<div class="notice notice-success"><p>Processed ' . intval(count($pdfs)) . ' PDFs. Previews generated: ' . intval($count) . '. Failed: ' . intval($failed) . '.</p></div>';
			if (!empty($messages)) {
				foreach ($messages as $m) {
					echo '<li>' . esc_html($m) . '</li>';
				}
				echo '</ul></div>';
			}
		}
	}

	echo '<form method="post">';
	wp_nonce_field('stagekitwp_regenerate_previews_action', 'stagekitwp_regenerate_previews_nonce');
	echo '<p>This will attempt to generate a first-page JPEG preview for every PDF in the media library. Your server must have Imagick and PDF support installed for previews to be created.</p>';
	submit_button('Regenerate PDF Previews', 'primary', 'stagekitwp_regenerate_previews_submit');
	echo '</form>';

	echo '</div>';
}

/**
 * Settings Page
 */
function stagekitwp_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Insufficient permissions' );
	}

	echo '<div class="wrap">';
	echo '<h1>StageKitWP Settings</h1>';
	echo '<form method="post" action="options.php">';
	settings_fields( 'stagekitwp_settings_group' );
	do_settings_sections( 'stagekitwp-settings' );
	submit_button();
	echo '</form>';

	// Sample pages section
	echo '<hr>';
	echo '<h2>Sample Pages</h2>';
	echo '<p>Create demonstration pages for each shortcode with examples and documentation. Sample pages are managed from the <strong>Tools</strong> menu.</p>';
	echo '<p><a href="' . esc_url(admin_url('tools.php?page=stagekitwp-sample-content')) . '" class="button button-primary">Manage Sample Pages</a></p>';

	echo '</div>';
}

function stagekitwp_register_settings() {
	add_settings_section(
		'stagekitwp_season_builder_section',
		'Season Builder',
		null,
		'stagekitwp-settings'
	);

	add_settings_field(
		'stagekitwp_show_builder_cpt_menus',
		'Show CPT Menus',
		'stagekitwp_show_builder_cpt_menus_callback',
		'stagekitwp-settings',
		'stagekitwp_season_builder_section'
	);
	register_setting( 'stagekitwp_settings_group', 'stagekitwp_show_builder_cpt_menus' );

	add_settings_section(
		'stagekitwp_google_maps_section',
		'Google Maps Integration',
		'stagekitwp_google_maps_section_callback',
		'stagekitwp-settings'
	);

	add_settings_field(
		'stagekitwp_google_maps_api_key',
		'Google Maps API Key',
		'stagekitwp_google_maps_api_key_callback',
		'stagekitwp-settings',
		'stagekitwp_google_maps_section'
	);
	register_setting( 'stagekitwp_settings_group', 'stagekitwp_google_maps_api_key' );

	add_settings_section(
		'stagekitwp_auditions_section',
		'Auditions Page',
		'stagekitwp_auditions_section_callback',
		'stagekitwp-settings'
	);
	add_settings_field(
		'stagekitwp_auditions_page_id',
		'Auditions Page',
		'stagekitwp_auditions_page_id_callback',
		'stagekitwp-settings',
		'stagekitwp_auditions_section'
	);
	register_setting( 'stagekitwp_settings_group', 'stagekitwp_auditions_page_id', 'absint' );
		add_settings_section(
			'stagekitwp_media_section',
			'Media Page',
			'stagekitwp_media_section_callback',
			'stagekitwp-settings'
		);
		add_settings_field(
			'stagekitwp_media_page_id',
			'Media Page',
			'stagekitwp_media_page_id_callback',
			'stagekitwp-settings',
			'stagekitwp_media_section'
		);
		register_setting( 'stagekitwp_settings_group', 'stagekitwp_media_page_id', 'absint' );

		add_settings_section(
			'stagekitwp_shortcode_messages_section',
			'Shortcode Empty-State Messages',
			'stagekitwp_shortcode_messages_section_callback',
			'stagekitwp-settings'
		);

		add_settings_field(
			'stagekitwp_msg_no_seasons_html',
			'No Seasons Found (Season shortcodes)',
			'stagekitwp_msg_no_seasons_html_callback',
			'stagekitwp-settings',
			'stagekitwp_shortcode_messages_section'
		);
		register_setting( 'stagekitwp_settings_group', 'stagekitwp_msg_no_seasons_html', 'wp_kses_post' );

		add_settings_field(
			'stagekitwp_msg_no_shows_in_season_html',
			'No Shows In Season',
			'stagekitwp_msg_no_shows_in_season_html_callback',
			'stagekitwp-settings',
			'stagekitwp_shortcode_messages_section'
		);
		register_setting( 'stagekitwp_settings_group', 'stagekitwp_msg_no_shows_in_season_html', 'wp_kses_post' );
}
add_action( 'admin_init', 'stagekitwp_register_settings' );

// Landing Page section heading text fields — registered on the Display Options page.
add_action( 'admin_init', function() {
    $hkeys = [
        'author'        => 'Written by',
        'sub_authors'   => 'Music / Lyrics / Book',
        'director'      => 'Directed by',
        'assoc_dir'     => 'Associate Director',
        'producer'      => 'Produced by',
        'stage_manager' => 'Stage Manager',
        'synopsis'      => 'Synopsis',
        'show_dates'    => 'Performances',
		'program_pdf'   => 'Programme',
        'venue'         => 'Venue',
        'cast'          => 'Cast',
    ];
    $page    = 'stagekitwp-display-options-landing_page';
    $group   = 'stagekitwp_display_options_group_landing_page';
    $section = 'stagekitwp_landing_page_display_section';
    foreach ( $hkeys as $hk => $default ) {
        $key = 'stagekitwp_lp_heading_' . $hk;
        register_setting( $group, $key, 'sanitize_text_field' );
        add_settings_field(
            $key,
            esc_html( $default ) . ' heading',
            function() use ( $key, $default ) {
                $val = get_option( $key, '' );
                printf(
                    '<input type="text" name="%s" value="%s" placeholder="%s" class="regular-text">'
                  . '<p class="description">Default: &ldquo;%s&rdquo;</p>',
                    esc_attr( $key ), esc_attr( $val ), esc_attr( $default ), esc_html( $default )
                );
            },
            $page,
            $section
        );
    }
} );

function stagekitwp_google_maps_section_callback() {
	echo '<p>Configure Google Maps integration for venue map thumbnails in the [stagekitwp_venues] shortcode.</p>';
}

function stagekitwp_google_maps_api_key_callback() {
	$value = get_option( 'stagekitwp_google_maps_api_key', '' );
	echo '<input type="password" id="stagekitwp_google_maps_api_key" name="stagekitwp_google_maps_api_key" value="' . esc_attr($value) . '" size="50" />';
	echo '<p class="description">Obtain a free API key from <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a>. Required for map thumbnail display in the Venues shortcode.</p>';
}

function stagekitwp_auditions_section_callback() {
	echo '<p>Set the page that contains your <code>[stagekitwp_auditions]</code> shortcode. When configured, the “Audition Info” button in <code>[stagekitwp_season_shows]</code> will link directly to this page.</p>';
}

function stagekitwp_auditions_page_id_callback() {
	$saved = intval( get_option( 'stagekitwp_auditions_page_id', 0 ) );
	$pages = get_pages( array( 'post_status' => 'publish', 'sort_column' => 'post_title', 'sort_order' => 'ASC' ) );
	echo '<select id="stagekitwp_auditions_page_id" name="stagekitwp_auditions_page_id">';
	echo '<option value="0">' . esc_html__( '&mdash; None &mdash;' ) . '</option>';
	foreach ( $pages as $page ) {
		echo '<option value="' . esc_attr( $page->ID ) . '"' . selected( $saved, $page->ID, false ) . '>' . esc_html( $page->post_title ) . '</option>';
	}
	echo '</select>';
	echo '<p class="description">The selected page URL will be used as the audition info link in <code>[stagekitwp_season_shows]</code>.</p>';
}
function stagekitwp_media_section_callback() {
	echo '<p>Set the page that contains your theatre media content (for example <code>[stagekitwp_programs]</code> and season image shortcodes).</p>';
}

function stagekitwp_media_page_id_callback() {
	$saved = intval( get_option( 'stagekitwp_media_page_id', 0 ) );
	$pages = get_pages( array( 'post_status' => 'publish', 'sort_column' => 'post_title', 'sort_order' => 'ASC' ) );
	echo '<select id="stagekitwp_media_page_id" name="stagekitwp_media_page_id">';
	echo '<option value="0">' . esc_html__( '&mdash; None &mdash;' ) . '</option>';
	foreach ( $pages as $page ) {
		echo '<option value="' . esc_attr( $page->ID ) . '"' . selected( $saved, $page->ID, false ) . '>' . esc_html( $page->post_title ) . '</option>';
	}
	echo '</select>';
	echo '<p class="description">Use this as the canonical front-end media page (program PDFs, season images, and related content).</p>';
}

function stagekitwp_show_builder_cpt_menus_callback() {
	$value = get_option( 'stagekitwp_show_builder_cpt_menus', '1' );
	echo '<input type="checkbox" id="stagekitwp_show_builder_cpt_menus" name="stagekitwp_show_builder_cpt_menus" value="1"' . checked( '1', $value, false ) . ' />';
	echo '<label for="stagekitwp_show_builder_cpt_menus"> Show Seasons, Shows, Cast, and Awards in the admin sidebar menu</label>';
	echo '<p class="description">When unchecked, the Season Builder CPT menus (Seasons, Shows, Cast, Awards) are hidden from the sidebar. Use the Season Builder page to manage them instead.</p>';
}

/**
 * Shared helper for shortcode message overrides with safe HTML.
 */
function stagekitwp_get_shortcode_message_html( $option_key, $default_html ) {
	$raw = get_option( $option_key, '' );
	if ( is_string( $raw ) ) {
		$trimmed = trim( $raw );
		if ( '' !== $trimmed ) {
			return wp_kses_post( $trimmed );
		}
	}
	return wp_kses_post( $default_html );
}

function stagekitwp_shortcode_messages_section_callback() {
	echo '<p>Override empty-state text used by season-related shortcodes. HTML is allowed (sanitized with <code>wp_kses_post</code>).</p>';
}

function stagekitwp_msg_no_seasons_html_callback() {
	$value = get_option( 'stagekitwp_msg_no_seasons_html', '' );
	wp_editor(
		$value,
		'stagekitwp_msg_no_seasons_html_editor',
		array(
			'textarea_name' => 'stagekitwp_msg_no_seasons_html',
			'textarea_rows' => 5,
			'media_buttons' => true,
			'teeny' => false,
		)
	);
	echo '<p class="description">Used by season shortcodes when no seasons are available. Supports media-library image insertion. Leave blank to use built-in defaults.</p>';
}

function stagekitwp_msg_no_shows_in_season_html_callback() {
	$value = get_option( 'stagekitwp_msg_no_shows_in_season_html', '' );
	wp_editor(
		$value,
		'stagekitwp_msg_no_shows_in_season_html_editor',
		array(
			'textarea_name' => 'stagekitwp_msg_no_shows_in_season_html',
			'textarea_rows' => 5,
			'media_buttons' => true,
			'teeny' => false,
		)
	);
	echo '<p class="description">Used when a season has no associated shows (for example in <code>[stagekitwp_season_shows]</code>). Supports media-library image insertion. Leave blank to use built-in defaults.</p>';
}

/**
 * Display Options Page with Tabs
 */
function stagekitwp_display_options_page() {
	$tabs = ['board_member', 'advertiser', 'sponsor', 'contributor', 'testimonials', 'season', 'show', 'cast', 'auditions', 'awards', 'venues', 'tickets'];
	$active_tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'board_member';
	if (!in_array($active_tab, $tabs, true)) {
		$active_tab = 'board_member';
	}
	$base_url = admin_url( 'admin.php?page=stagekitwp-core&stagekitwp_admin_tab=display-options' );

    echo '<div class="wrap">';
    echo '<h1>Display Options</h1>';
    echo '<h2 class="nav-tab-wrapper">';
    foreach ($tabs as $tab) {
        $label = ucfirst(str_replace('_', ' ', $tab));
        $active = ($active_tab === $tab) ? 'nav-tab-active' : '';
		echo '<a href="' . esc_url( add_query_arg( 'tab', $tab, $base_url ) ) . '" class="nav-tab ' . esc_attr( $active ) . '">' . esc_html( $label ) . '</a>';
    }
    echo '</h2>';

    echo '<form method="post" action="options.php">';
    settings_fields('stagekitwp_display_options_' . $active_tab);
    do_settings_sections('stagekitwp-display-options-' . $active_tab);
    submit_button();
    echo '</form>';
    echo '</div>';
}

// ─────────────────────────────────────────────────────────────────────────────
// NOTE: stagekitwp_register_display_settings() and all colour/font/checkbox callbacks
// (stagekitwp_color_picker_callback, stagekitwp_text_input_callback, stagekitwp_checkbox_callback,
//  stagekitwp_rating_symbol_callback, stagekitwp_grid_columns_callback, stagekitwp_font_family_callback)
// have been moved to includes/display-options.php which is loaded by the plugin
// main file before this file. Do not re-declare them here.
// ─────────────────────────────────────────────────────────────────────────────

// DEAD CODE START — preserved for reference only, never executed
if ( false ) {
function _stagekitwp_register_display_settings_dead() {
    $tabs = ['board_member'];
    foreach ($tabs as $tab) {
        $section_id = "stagekitwp_{$tab}_section";
        $page = "stagekitwp-display-options-{$tab}";
        $group = "stagekitwp_display_options_{$tab}";

        add_settings_section($section_id, ucfirst(str_replace('_', ' ', $tab)) . ' Display Settings', null, $page);

        add_settings_field("stagekitwp_{$tab}_bg_color", 'Background Color', 'stagekitwp_color_picker_callback', $page, $section_id, ['label_for' => "stagekitwp_{$tab}_bg_color"]);
        register_setting($group, "stagekitwp_{$tab}_bg_color");

        add_settings_field("stagekitwp_{$tab}_text_color", 'Text Color', 'stagekitwp_color_picker_callback', $page, $section_id, ['label_for' => "stagekitwp_{$tab}_text_color"]);
        register_setting($group, "stagekitwp_{$tab}_text_color");

        add_settings_field("stagekitwp_{$tab}_base_font", 'Base Font Family', 'stagekitwp_font_family_callback', $page, $section_id, ['label_for' => "stagekitwp_{$tab}_base_font"]);
        register_setting($group, "stagekitwp_{$tab}_base_font");

        add_settings_field("stagekitwp_{$tab}_border_color", 'Border Color', 'stagekitwp_color_picker_callback', $page, $section_id, ['label_for' => "stagekitwp_{$tab}_border_color"]);
        register_setting($group, "stagekitwp_{$tab}_border_color");

        add_settings_field("stagekitwp_{$tab}_border_width", 'Border Width', 'stagekitwp_text_input_callback', $page, $section_id, ['label_for' => "stagekitwp_{$tab}_border_width"]);
        register_setting($group, "stagekitwp_{$tab}_border_width");

        add_settings_field("stagekitwp_{$tab}_rounded", 'Rounded Corners', 'stagekitwp_checkbox_callback', $page, $section_id, ['label_for' => "stagekitwp_{$tab}_rounded"]);
        register_setting($group, "stagekitwp_{$tab}_rounded");

        add_settings_field("stagekitwp_{$tab}_radius", 'Border Radius', 'stagekitwp_text_input_callback', $page, $section_id, ['label_for' => "stagekitwp_{$tab}_radius"]);
        register_setting($group, "stagekitwp_{$tab}_radius");

        add_settings_field("stagekitwp_{$tab}_shadow", 'Border Shadow', 'stagekitwp_checkbox_callback', $page, $section_id, ['label_for' => "stagekitwp_{$tab}_shadow"]);
        register_setting($group, "stagekitwp_{$tab}_shadow");

        // h1 used by landing_page; h2/h3 used by awards/show/season/venues/landing_page;
        // h4/h5/h6 removed — never consumed by any shortcode.
        if ( $tab === 'landing_page' ) {
            add_settings_field("stagekitwp_{$tab}_h1_color", 'H1 Text Color', 'stagekitwp_color_picker_callback', $page, $section_id, ['label_for' => "stagekitwp_{$tab}_h1_color"]);
            register_setting($group, "stagekitwp_{$tab}_h1_color");
        }
        if ( in_array( $tab, ['awards','show','season','venues','landing_page'], true ) ) {
            add_settings_field("stagekitwp_{$tab}_h2_color", 'H2 Text Color', 'stagekitwp_color_picker_callback', $page, $section_id, ['label_for' => "stagekitwp_{$tab}_h2_color"]);
            register_setting($group, "stagekitwp_{$tab}_h2_color");
        }
        if ( in_array( $tab, ['awards','show','season','venues'], true ) ) {
            add_settings_field("stagekitwp_{$tab}_h3_color", 'H3 Text Color', 'stagekitwp_color_picker_callback', $page, $section_id, ['label_for' => "stagekitwp_{$tab}_h3_color"]);
            register_setting($group, "stagekitwp_{$tab}_h3_color");
        }

        if ($tab === 'testimonials') {
            add_settings_field("stagekitwp_{$tab}_rating_symbol", 'Rating Symbol', 'stagekitwp_rating_symbol_callback', $page, $section_id, ['label_for' => "stagekitwp_{$tab}_rating_symbol"]);
            register_setting($group, "stagekitwp_{$tab}_rating_symbol");
        }
		
		if ($tab === 'tickets') {
			add_settings_field("stagekitwp_{$tab}_button_color", 'Button Color', 'stagekitwp_color_picker_callback', $page, $section_id, ['label_for' => "stagekitwp_{$tab}_button_color"]);
			register_setting($group, "stagekitwp_{$tab}_button_color");
			
			add_settings_field("stagekitwp_{$tab}_button_hover_color", 'Button Hover Color', 'stagekitwp_color_picker_callback', $page, $section_id, ['label_for' => "stagekitwp_{$tab}_button_hover_color"]);
			register_setting($group, "stagekitwp_{$tab}_button_hover_color");
		}
		
		if ($tab === 'board_member' || $tab === 'advertiser') {
			add_settings_field("stagekitwp_{$tab}_grid_columns", 'Grid Columns', 'stagekitwp_grid_columns_callback', $page, $section_id, ['label_for' => "stagekitwp_{$tab}_grid_columns"]);
			register_setting($group, "stagekitwp_{$tab}_grid_columns");
		}
		
        // (empty dead code stub)
    }
}
} // end if(false) dead code block
// Font preview JS now lives in assets/js/admin.js as stagekitwpUpdateFontPreview().

function stagekitwp_get_documentation_topics() {
	return array(
		'core' => array(
			'label'   => __( 'StageKitWP Core', 'stagekitwp-core' ),
			'summary' => __( 'Use the core hub to manage the stage content that powers the site: seasons, shows, cast, awards, venues, board members, contributors, sponsors, testimonials, and advertisers.', 'stagekitwp-core' ),
			'uses'    => array(
				__( 'Use Season Content for seasons, shows, cast, and awards.', 'stagekitwp-core' ),
				__( 'Use People, Places & Partners for venues, board members, contributors, sponsors, testimonials, and advertisers.', 'stagekitwp-core' ),
				__( 'Use Dashboard and Instructions when you need the overview, sample workflow, or shortcode reference.', 'stagekitwp-core' ),
			),
			'links'   => array(
				array( 'label' => __( 'Open Dashboard', 'stagekitwp-core' ), 'url' => admin_url( 'admin.php?page=stagekitwp-core' ) ),
				array( 'label' => __( 'Open Instructions', 'stagekitwp-core' ), 'url' => admin_url( 'admin.php?page=stagekitwp-core&stagekitwp_admin_tab=instructions' ) ),
				array( 'label' => __( 'Season Content', 'stagekitwp-core' ), 'url' => admin_url( 'admin.php?page=stagekitwp-season-content' ) ),
				array( 'label' => __( 'People, Places & Partners', 'stagekitwp-core' ), 'url' => admin_url( 'admin.php?page=stagekitwp-people-places-partners' ) ),
			),
		),
		'theme' => array(
			'label'   => __( 'StageKitWP Theme', 'stagekitwp-core' ),
			'summary' => __( 'The theme supplies the front-end layout system, dark/light mode, hero controls, homepage sections, widgets, and the Customizer-based colour palette.', 'stagekitwp-core' ),
			'uses'    => array(
				__( 'Go to Appearance > Customize to set branding, colours, hero media, widget areas, and layout controls.', 'stagekitwp-core' ),
				__( 'Use the dark/light toggle and dual-logo options to keep the front end consistent across modes.', 'stagekitwp-core' ),
				__( 'Configure homepage sections, footer widgets, and the notification bar from the Customizer.', 'stagekitwp-core' ),
			),
			'links'   => array(
				array( 'label' => __( 'Open Customizer', 'stagekitwp-core' ), 'url' => admin_url( 'customize.php' ) ),
			),
		),
		'io' => array(
			'label'   => __( 'Import-Export', 'stagekitwp-core' ),
			'summary' => __( 'Import-Export moves StageKitWP content, settings, and theme customisations in ZIP or JSON form, with page remapping, clear-data tools, and a CSV Season Builder.', 'stagekitwp-core' ),
			'uses'    => array(
				__( 'Use Export to bundle the modules you want to move.', 'stagekitwp-core' ),
				__( 'Use Import for ZIP, JSON, or remote URL restores, and use the settings tab to tune batch size.', 'stagekitwp-core' ),
				__( 'Use Instructions when you need the full module-by-module workflow guide.', 'stagekitwp-core' ),
			),
			'links'   => array(
				array( 'label' => __( 'Open Import-Export', 'stagekitwp-core' ), 'url' => admin_url( 'admin.php?page=stagekitwp-io' ) ),
				array( 'label' => __( 'Import-Export Instructions', 'stagekitwp-core' ), 'url' => admin_url( 'admin.php?page=stagekitwp-io-help' ) ),
			),
		),
		'sync' => array(
			'label'   => __( 'Sync', 'stagekitwp-core' ),
			'summary' => __( 'Sync moves image and content data from SharePoint into WordPress and caches folder discovery so recurring syncs are faster.', 'stagekitwp-core' ),
			'uses'    => array(
				__( 'Use Authentication to connect Microsoft/SharePoint credentials and site details.', 'stagekitwp-core' ),
				__( 'Use Settings to manage folder discovery, manual overrides, cache refresh, and cleanup.', 'stagekitwp-core' ),
				__( 'Use Logs and Details when you need to review what the sync engine processed.', 'stagekitwp-core' ),
			),
			'links'   => array(
				array( 'label' => __( 'Open Sync', 'stagekitwp-core' ), 'url' => admin_url( 'admin.php?page=stagekitwp-sync' ) ),
				array( 'label' => __( 'Sync Settings', 'stagekitwp-core' ), 'url' => admin_url( 'admin.php?page=stagekitwp-sync-settings' ) ),
			),
		),
		'members' => array(
			'label'   => __( 'Members', 'stagekitwp-core' ),
			'summary' => __( 'Members provides the private member site: login, registration, directory, announcements, events, availability, messaging, and producer tools.', 'stagekitwp-core' ),
			'uses'    => array(
				__( 'Add the member shortcodes to your front-end pages where you want the member experience to appear.', 'stagekitwp-core' ),
				__( 'Use the on-site Instructions page for the full member and producer workflow, including navigation and role expectations.', 'stagekitwp-core' ),
				__( 'Use the Members admin screens for configuration, announcements, and operational tasks.', 'stagekitwp-core' ),
			),
			'links'   => array(
				array( 'label' => __( 'Open Members', 'stagekitwp-core' ), 'url' => admin_url( 'admin.php?page=stagekitwp-ma-admin' ) ),
			),
		),
		'rcl' => array(
			'label'   => __( 'RC Library', 'stagekitwp-core' ),
			'summary' => __( 'RC Library manages rubric items, rubric templates, circle groups, and evaluation reporting for the Reading Circle workflow.', 'stagekitwp-core' ),
			'uses'    => array(
				__( 'Create rubric items first, then combine them into templates and assign those templates to circle groups.', 'stagekitwp-core' ),
				__( 'Use the Help & Documentation guide for the recommended setup order and operational workflow.', 'stagekitwp-core' ),
				__( 'Use Reports to review the evaluation output and the Circle Groups screen to manage the active library.', 'stagekitwp-core' ),
			),
			'links'   => array(
				array( 'label' => __( 'Open RC Library', 'stagekitwp-core' ), 'url' => admin_url( 'admin.php?page=stagekitwp-rc-library' ) ),
				array( 'label' => __( 'Help & Documentation', 'stagekitwp-core' ), 'url' => admin_url( 'admin.php?page=stagekitwp-rcl-help' ) ),
			),
		),
	);
}

function stagekitwp_documentation_page() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( 'Insufficient permissions' );
	}

	$topics = stagekitwp_get_documentation_topics();
	$active_topic = isset( $_GET['stagekitwp_docs_tab'] ) ? sanitize_key( wp_unslash( $_GET['stagekitwp_docs_tab'] ) ) : 'core';
	if ( ! isset( $topics[ $active_topic ] ) ) {
		$active_topic = array_key_first( $topics );
	}

	echo '<div class="wrap stagekitwp-docs-hub">';
	echo '<h1>' . esc_html__( 'Documentation', 'stagekitwp-core' ) . '</h1>';
	echo '<p class="description">' . esc_html__( 'Use these tabs for the practical setup and usage instructions for each part of the StageKitWP ecosystem.', 'stagekitwp-core' ) . '</p>';

	echo '<style>
		.stagekitwp-docs-hub .stagekitwp-docs-tabs { margin-bottom: 16px; }
		.stagekitwp-docs-hub .stagekitwp-docs-panel {
			background: #fff;
			border: 1px solid #c3c4c7;
			border-radius: 8px;
			padding: 20px;
		}
		.stagekitwp-docs-hub .stagekitwp-docs-panel-grid {
			display: grid;
			grid-template-columns: minmax(0, 1fr) minmax(260px, 320px);
			gap: 20px;
			align-items: start;
		}
		.stagekitwp-docs-hub .stagekitwp-docs-panel h2 { margin-top: 0; }
		.stagekitwp-docs-hub .stagekitwp-docs-links {
			border: 1px solid #dcdcde;
			border-radius: 8px;
			background: #f8f9fb;
			padding: 16px;
		}
		.stagekitwp-docs-hub .stagekitwp-docs-links .button { display: block; margin: 0 0 10px; text-align: center; }
		.stagekitwp-docs-hub .stagekitwp-docs-links .button:last-child { margin-bottom: 0; }
		@media (max-width: 960px) {
			.stagekitwp-docs-hub .stagekitwp-docs-panel-grid { grid-template-columns: 1fr; }
		}
	</style>';

	echo '<h2 class="nav-tab-wrapper stagekitwp-docs-tabs">';
	foreach ( $topics as $key => $topic ) {
		$classes = 'nav-tab' . ( $key === $active_topic ? ' nav-tab-active' : '' );
		echo '<button type="button" class="' . esc_attr( $classes ) . '" data-stagekitwp-docs-tab="' . esc_attr( $key ) . '">' . esc_html( $topic['label'] ) . '</button>';
	}
	echo '</h2>';

	echo '<div class="stagekitwp-docs-panels">';
	foreach ( $topics as $key => $topic ) {
		echo '<section class="stagekitwp-docs-panel" data-stagekitwp-docs-panel="' . esc_attr( $key ) . '" style="' . ( $key === $active_topic ? '' : 'display:none;' ) . '">';
		echo '<div class="stagekitwp-docs-panel-grid">';
		echo '<div>';
		echo '<h2>' . esc_html( $topic['label'] ) . '</h2>';
		echo '<p>' . esc_html( $topic['summary'] ) . '</p>';
		echo '<h3>' . esc_html__( 'How to use', 'stagekitwp-core' ) . '</h3>';
		echo '<ul>';
		foreach ( $topic['uses'] as $use ) {
			echo '<li>' . esc_html( $use ) . '</li>';
		}
		echo '</ul>';
		echo '</div>';
		echo '<aside class="stagekitwp-docs-links">';
		echo '<h3>' . esc_html__( 'Open screens', 'stagekitwp-core' ) . '</h3>';
		foreach ( $topic['links'] as $link ) {
			echo '<a class="button button-primary" href="' . esc_url( $link['url'] ) . '">' . esc_html( $link['label'] ) . '</a>';
		}
		echo '</aside>';
		echo '</div>';
		echo '</section>';
	}
	echo '</div>';

	echo '<script>(function(){const hub=document.querySelector(".stagekitwp-docs-hub");if(!hub)return;const tabs=hub.querySelectorAll("[data-stagekitwp-docs-tab]");const panels=hub.querySelectorAll("[data-stagekitwp-docs-panel]");if(!tabs.length||!panels.length)return;const param="stagekitwp_docs_tab";const active=' . wp_json_encode( $active_topic ) . ';function activate(tabKey){tabs.forEach(tab=>tab.classList.toggle("nav-tab-active",tab.dataset.stagekitwpDocsTab===tabKey));panels.forEach(panel=>panel.style.display=panel.dataset.stagekitwpDocsPanel===tabKey?"":"none");const url=new URL(window.location.href);url.searchParams.set(param,tabKey);history.replaceState({},"",url.toString());}tabs.forEach(tab=>tab.addEventListener("click",function(){activate(this.dataset.stagekitwpDocsTab);}));activate(active);})();</script>';

	echo '</div>';
}

function stagekitwp_instructions_page() {
	echo "<div class='wrap' style='max-width:900px'>";
	echo "<h1>StageKitWP Instructions</h1>";
	
	echo "<h2>Overview</h2>";
	echo "<p>StageKitWP is a comprehensive WordPress ecosystem for community theatre groups. It provides 16 shortcodes, 16 matching Gutenberg blocks, and 14 Beaver Builder modules for managing and displaying seasons, shows, cast, venues, awards, sponsors, and more. Every shortcode has a Display Options tab with colour, typography, border, and layout controls. Paired with the companion <strong>StageKitWP Theme</strong>, all shortcodes participate in the site-wide dark/light mode toggle.</p>";
	echo "<p>Show names and poster images in shortcodes like <code>[stagekitwp_season_shows]</code>, <code>[stagekitwp_past_shows]</code>, <code>[stagekitwp_tickets]</code>, and <code>[stagekitwp_awards]</code> automatically link to each show&rsquo;s front-end page, giving visitors one click to the full landing page, cast list, tickets, and programme.</p>";

	echo "<h2>Core Features</h2>";
	echo "<h3>Custom Post Types (10 CPTs)</h3>";
	echo "<ul>";
	echo "<li><strong>Seasons</strong> &mdash; Track theatre seasons with dates, images, and status (Current/Upcoming/Past)</li>";
	echo "<li><strong>Shows</strong> &mdash; Manage productions within seasons including genre, director, audition details, and ticket URLs</li>";
	echo "<li><strong>Cast</strong> &mdash; Display cast members with headshots, roles, and biographies</li>";
	echo "<li><strong>Venues</strong> &mdash; Manage performance venues with addresses, phone numbers, and Google Maps integration</li>";
	echo "<li><strong>Awards</strong> &mdash; Track awards and nominations (Musical / Drama / Comedy categories)</li>";
	echo "<li><strong>Board Members</strong> &mdash; Maintain board member listings with positions and photos</li>";
	echo "<li><strong>Sponsors</strong> &mdash; Manage sponsor logos and information</li>";
	echo "<li><strong>Advertisers</strong> &mdash; Track advertisers and local businesses</li>";
	echo "<li><strong>Contributors</strong> &mdash; Acknowledge donors and supporters</li>";
	echo "<li><strong>Testimonials</strong> &mdash; Display audience reviews with star ratings</li>";
	echo "</ul>";

	echo "<h2>Shortcodes Reference (16 total)</h2>";
	echo "<p>All parameters are optional unless marked <em>required</em>. Every shortcode is also available as a Gutenberg block under the <strong>StageKitWP</strong> block category and (except <code>[stagekitwp_landingpage]</code>) as a Beaver Builder module.</p>";
	
	echo "<h3>1. [stagekitwp_auditions]</h3>";
	echo "<p><strong>Purpose:</strong> Display upcoming auditions sorted by date (earliest first). Fully light/dark-mode aware &mdash; colours come from the <strong>Auditions</strong> tab in Display Options (light + dark) and swap automatically with the StageKitWP Theme dark toggle.</p>";
	echo "<p><strong>Parameters:</strong></p>";
	echo "<ul>";
	echo "<li><code>days_past</code> (integer, default: 7) &mdash; Number of days back to still include an audition.</li>";
	echo "<li><code>layout</code> (list|cards|compact, default: list) &mdash; <em>list</em>: stacked cards with full details; <em>cards</em>: responsive grid with a date badge; <em>compact</em>: a tight two-column date/production table for many auditions.</li>";
	echo "<li><code>show_details</code> (true|false, default: true) &mdash; Include the audition details body (ignored by the <em>compact</em> layout).</li>";
	echo "<li><code>heading</code> (text, default: empty) &mdash; Optional H2 heading rendered above the list.</li>";
	echo "<li><code>title</code> (text, default: empty) &mdash; Optional compact-layout heading rendered with the same heading style used by events.</li>";
	echo "</ul>";
	echo "<p><strong>Examples:</strong> <code>[stagekitwp_auditions]</code> &bull; <code>[stagekitwp_auditions layout=\"cards\"]</code> &bull; <code>[stagekitwp_auditions layout=\"compact\" days_past=\"14\" title=\"Auditions\"]</code> &bull; <code>[stagekitwp_auditions layout=\"cards\" heading=\"Now Casting\" show_details=\"false\"]</code></p>";
	
	echo "<h3>2. [stagekitwp_season_shows]</h3>";
	echo "<p><strong>Purpose:</strong> Season banner + show grid. Two layouts: <code>spotlight</code> (portrait cards) and <code>cards</code> (full-width stacked rows with all details).</p>";
	echo "<p><strong>Parameters:</strong></p>";
	echo "<ul>";
	echo "<li><code>layout</code> (spotlight|cards, default: spotlight)</li>";
	echo "<li><code>season_id</code> (integer) &mdash; Specific season. Overrides <code>which</code>.</li>";
	echo "<li><code>which</code> (current|next|all, default: current)</li>";
	echo "<li><code>show_auditions</code> (true|false, default: true) &mdash; Show audition date/link button.</li>";
	echo "</ul>";
	echo "<p><strong>Examples:</strong> <code>[stagekitwp_season_shows]</code> &bull; <code>[stagekitwp_season_shows layout=\"cards\" which=\"all\"]</code> &bull; <code>[stagekitwp_season_shows show_auditions=\"false\"]</code></p>";
	
	echo "<h3>3. [stagekitwp_seasons]</h3>";
	echo "<p><strong>Purpose:</strong> Season cards or single-field output. Cards layout shows status badge, name, dates, images, description, and a Get Tickets button &mdash; all individually togglable.</p>";
	echo "<p><strong>Parameters:</strong></p>";
	echo "<ul>";
	echo "<li><code>layout</code> (cards|field, default: cards)</li>";
	echo "<li><code>which</code> (all|current|upcoming|next|past, default: all)</li>";
	echo "<li><code>season_id</code> (integer) &mdash; Specific season.</li>";
	echo "<li><code>order</code> (ASC|DESC, default: DESC) &bull; <code>orderby</code> (start_date|end_date|title) &bull; <code>limit</code> (int)</li>";
	echo "<li>Card toggles (all default true): <code>show_image</code>, <code>show_name</code>, <code>show_status</code>, <code>show_dates</code>, <code>show_tickets</code></li>";
	echo "<li>Card toggles (default false): <code>show_description</code>, <code>show_image_back</code>, <code>show_social_banner</code>, <code>show_sm_square</code>, <code>show_sm_portrait</code></li>";
	echo "<li>Field layout: <code>field</code> (name|start_date|end_date|dates_range|status|description|tickets_url|tickets_link|image_front|image_back|social_banner|sm_square|sm_portrait)</li>";
	echo "</ul>";
	echo "<p><strong>Examples:</strong> <code>[stagekitwp_seasons which=\"current\"]</code> &bull; <code>[stagekitwp_seasons which=\"upcoming\" limit=\"1\"]</code> &bull; <code>[stagekitwp_seasons season_id=\"77\" layout=\"field\" field=\"image_front\"]</code></p>";
	
	echo "<h3>4. [stagekitwp_shows]</h3>";
	echo "<p><strong>Purpose:</strong> Display shows from selected season(s).</p>";
	echo "<p><strong>Parameters:</strong></p>";
	echo "<ul>";
	echo "<li><code>season_id</code> (integer) - Display shows for specific season</li>";
	echo "<li><code>which</code> (string, default: \"all\") - all | current | next | current_and_next</li>";
	echo "<li><code>exclude</code> (string) - Comma-separated fields to hide</li>";
	echo "</ul>";
	echo "<p><strong>Example:</strong> <code>[stagekitwp_shows which=\"current\" exclude=\"genre,director\"]</code></p>";
	
	echo "<h3>5. [stagekitwp_show_cast]</h3>";
	echo "<p><strong>Purpose:</strong> Display cast for a specific show.</p>";
	echo "<p><strong>Parameters:</strong></p>";
	echo "<ul>";
	echo "<li><code>show_id</code> (integer, required) - ID of the show</li>";
	echo "</ul>";
	echo "<p><strong>Example:</strong> <code>[stagekitwp_show_cast show_id=\"123\"]</code></p>";
	echo "<p><strong>Colours:</strong> Uses the <strong>Show</strong> Display Options tab.</p>";
	
	echo "<h3>6. [stagekitwp_sponsors]</h3>";
	echo "<p><strong>Purpose:</strong> Display sponsors grouped by level (grid) or as an auto-scrolling logo carousel (slider). The former <code>[stagekitwp_sponsor_slider]</code> shortcode is now <code>layout=\"slider\"</code> on this shortcode; the old tag still works as an alias. Grid mode now supports Diamond as the top tier, optional tier-label overrides, optional header hiding, and optional logo sizing.</p>";
	echo "<p><strong>Parameters:</strong></p>";
	echo "<ul>";
	echo "<li><code>layout</code> (grid|slider, default: grid)</li>";
	echo "<li><em>Grid options:</em> <code>show_headers</code>, <code>show_name</code>, <code>show_company</code>, <code>show_logo</code>, <code>show_website</code> (all boolean, default: true)</li>";
	echo "<li><code>logo_size</code> (text, optional) &mdash; set a percentage such as <code>60%</code>, a pixel value such as <code>180px</code>, or a plain number such as <code>180</code> (treated as pixels)</li>";
	echo "<li><code>diamond_label</code>, <code>platinum_label</code>, <code>gold_label</code>, <code>silver_label</code>, <code>bronze_label</code> (text, optional) &mdash; override the level headers shown above each sponsor group</li>";
	echo "<li><em>Slider options:</em> <code>slides_visible</code> (int, default: 4) &bull; <code>autoplay</code> (true|false, default: true) &bull; <code>speed</code> (ms, default: 3000)</li>";
	echo "</ul>";
	echo "<p><strong>Examples:</strong> <code>[stagekitwp_sponsors]</code> &bull; <code>[stagekitwp_sponsors show_headers=\"false\"]</code> &bull; <code>[stagekitwp_sponsors logo_size=\"180px\"]</code> &bull; <code>[stagekitwp_sponsors layout=\"slider\"]</code> &bull; <code>[stagekitwp_sponsors layout=\"slider\" slides_visible=\"5\" autoplay=\"false\"]</code></p>";
	
	echo "<h3>7. [stagekitwp_testimonials]</h3>";
	echo "<p><strong>Purpose:</strong> Display testimonials with optional show linkage, optional testimonial media from the related show, and multiple layouts.</p>";
	echo "<p><strong>Parameters:</strong></p>";
	echo "<ul>";
	echo "<li><code>mode</code> (slider|grid|full|per_show|per_show_slider, default from Display Options)</li>";
	echo "<li><code>layout</code> (classic|quote|minimal|spotlight|overlay, default from Display Options)</li>";
	echo "<li><code>limit</code> (int, default: -1)</li>";
	echo "<li><code>columns</code> (1-4, grid mode only, default: 3)</li>";
	echo "<li><code>image_width</code> (px), <code>image_height</code> (px), <code>image_fit</code> (cover|contain) &mdash; strict media sizing controls</li>";
	echo "<li><code>image_position</code> (center|left|right|top) and <code>image_focus</code> (center_center|center_top|center_bottom|left_center|right_center)</li>";
	echo "<li><code>text_overlay</code> (true|false) and <code>image_opacity</code> (0.1-1) &mdash; render testimonial content over the image</li>";
	echo "<li><code>show_id</code> (int) &mdash; only testimonials linked to this show</li>";
	echo "<li><code>show_name_placement</code> (meta|header|slug|image_indent) &mdash; controls where the linked show name appears</li>";
	echo "<li><code>tag_icon_source</code> (none|site_icon|miltonman|custom), <code>tag_icon_url</code>, <code>tag_icon_size</code> (px) &mdash; iconized category tag badge style</li>";
	echo "<li>Field toggles: <code>show_name</code>, <code>show_comment</code>, <code>show_rating</code>, <code>show_show</code>, <code>show_date</code>, <code>show_media</code></li>";
	echo "<li><code>reviews_per_show</code> (int, default: 4) &mdash; <code>mode=\"per_show\"</code>/<code>per_show_slider</code> only, max reviews shown per show</li>";
	echo "<li><code>review_align</code> (left|center|right|alternating|alternating_lr, default: left) &mdash; <code>mode=\"per_show\"</code>/<code>per_show_slider</code> only, per-review card text alignment; <code>alternating</code> cycles right &rarr; left &rarr; center &rarr; repeat, <code>alternating_lr</code> cycles left &rarr; right &rarr; repeat</li>";
	echo "</ul>";
	echo "<p><strong><code>per_show</code> mode:</strong> Groups testimonials by their linked show (only shows with at least one linked, published testimonial are shown, ordered alphabetically). Each show renders as a single card &mdash; reusing the <code>layout</code> styling (classic|quote|minimal|spotlight|overlay) and <code>image_position</code>/<code>image_fit</code>/<code>image_focus</code> &mdash; showing the show's Testimonial Media image (falling back to the show's Image field), the show title, and up to <code>reviews_per_show</code> most recent reviews stacked inside that same card. With <code>layout=\"overlay\"</code>, the review stack flows below the image instead of pinning over it.</p>";
	echo "<p><strong><code>per_show_slider</code> mode:</strong> Identical to <code>per_show</code> mode, but shows one show's card at a time in the same Slick carousel used by <code>mode=\"slider\"</code> (arrows, dots, adaptive height, autoplay).</p>";
	echo "<p><strong>Examples:</strong> <code>[stagekitwp_testimonials]</code> &bull; <code>[stagekitwp_testimonials mode=\"grid\" columns=\"2\" layout=\"quote\" show_name_placement=\"slug\" image_position=\"left\" image_focus=\"center_top\" image_height=\"340\"]</code> &bull; <code>[stagekitwp_testimonials mode=\"full\" layout=\"overlay\" text_overlay=\"true\" image_opacity=\"0.35\" show_name_placement=\"image_indent\" tag_icon_source=\"site_icon\" tag_icon_size=\"20\"]</code> &bull; <code>[stagekitwp_testimonials mode=\"per_show\" reviews_per_show=\"4\" review_align=\"alternating\"]</code> &bull; <code>[stagekitwp_testimonials mode=\"per_show_slider\" layout=\"spotlight\" reviews_per_show=\"3\"]</code></p>";
	
	echo "<h3>8. [stagekitwp_awards]</h3>";
	echo "<p><strong>Purpose:</strong> Display awards and nominations grouped by season and category. Fully integrated with Display Options and dark/light mode toggle.</p>";
	echo "<p><strong>Layouts:</strong></p>";
	echo "<ul>";
	echo "<li><code>table</code> (default) &mdash; Responsive scrollable table per category: Status badge, Award, optional Recipient, Show. Winners get a subtle gold row highlight.</li>";
	echo "<li><code>cards</code> &mdash; Award cards in an auto-fill grid with trophy/mask icon, award name, optional recipient, show, and status badge.</li>";
	echo "<li><code>list</code> &mdash; Compact single-line rows: badge, award name, optional recipient, show. Show column hidden on mobile.</li>";
	echo "<li><code>showcase</code> &mdash; Winners as prominent gold-bordered hero cards; nominations as a compact list beneath.</li>";
	echo "</ul>";
	echo "<p><strong>Parameters:</strong></p>";
	echo "<ul>";
	echo "<li><code>layout</code> (table|cards|list|showcase, default: <code>table</code>)</li>";
	echo "<li><code>season_id</code> (int) &mdash; Filter to a specific season.</li>";
	echo "<li><code>category</code> (string) &mdash; Filter to Musical, Drama, or Comedy.</li>";
	echo "<li><code>show_season</code> (true|false, default: <code>true</code>) &mdash; Show season heading.</li>";
	echo "<li><code>show_category</code> (true|false, default: <code>true</code>) &mdash; Show category sub-heading.</li>";
	echo "<li><code>show_recipient</code> (true|false, default: <code>true</code>) &mdash; Show recipient names in every layout. When false, the table removes the Recipient column entirely.</li>";
	echo "<li><code>winners_only</code> (true|false, default: <code>false</code>) &mdash; Hide nominations.</li>";
	echo "</ul>";
	echo "<p><strong>Examples:</strong> <code>[stagekitwp_awards]</code> &bull; <code>[stagekitwp_awards layout=\"showcase\" winners_only=\"true\"]</code> &bull; <code>[stagekitwp_awards layout=\"cards\" category=\"Musical\" show_recipient=\"false\"]</code></p>";
	echo "<p><strong>Notes:</strong> Awards created in Season Builder or Awards CPT. Colours in Display Options &rarr; Awards. Dark/light mode automatic when StageKitWP Theme is active.</p>";
	
	echo "<h3>9. [stagekitwp_venues]</h3>";
	echo "<p><strong>Purpose:</strong> Display venue information with addresses, phone numbers, websites, Google Maps links, and interactive map thumbnails.</p>";
	echo "<p><strong>Display:</strong> Each venue shows name, address, contact details, optional photo, Google Maps map thumbnail, and clickable Google Maps link.</p>";
	echo "<p><strong>Google Maps Integration:</strong></p>";
	echo "<ul>";
	echo "<li>If latitude and longitude are provided for a venue, a direct link to Google Maps is displayed</li>";
	echo "<li>If latitude/longitude provided AND a Google Maps API key is configured, a map thumbnail image is displayed showing the venue location with a red marker</li>";
	echo "<li>Otherwise, the address is used for the maps link</li>";
	echo "<li>To enable map thumbnails, add your Google Maps API key in StageKitWP → Settings → Google Maps API Key</li>";
	echo "</ul>";
	echo "<p><strong>Parameters:</strong></p>";
	echo "<ul>";
	echo "<li><code>show_id</code> (integer, optional) - Display the specific venue assigned to a show</li>";
	echo "</ul>";
	echo "<p><strong>Example:</strong> <code>[stagekitwp_venues]</code> or <code>[stagekitwp_venues show_id=\"123\"]</code></p>";
	echo "<p><strong>Note:</strong> Venues are managed through the Venues admin menu. Shows are linked to venues in the Season Builder (Details tab) or Show Details meta box.</p>";
	
	echo "<h3>10. [stagekitwp_board_members]</h3>";
	echo "<p><strong>Purpose:</strong> Display board members sorted by role priority (President, Vice-President, Treasurer, Secretary, then alphabetically by position). Fully integrated with Display Options colour pickers and the StageKitWP Theme dark/light mode toggle.</p>";
	echo "<p><strong>Parameters:</strong></p>";
	echo "<ul>";
	echo "<li><code>layout</code> (string, default: <code>grid</code>) &mdash; Display style. Options:";
	echo "<ul>";
	echo "<li><code>grid</code> &mdash; Circular headshot cards in responsive columns. Best for portrait photos.</li>";
	echo "<li><code>list</code> &mdash; Horizontal rows: thumbnail on the left, name and position on the right. Best for longer lists.</li>";
	echo "<li><code>table</code> &mdash; Compact two-column table (optional thumbnail, Name, Position). Best for formal or dense rosters.</li>";
	echo "<li><code>spotlight</code> &mdash; Tall portrait cards (3:4 aspect ratio). Best for a featured leadership team with strong photography.</li>";
	echo "</ul></li>";
	echo "<li><code>columns</code> (int 1&ndash;6, default: Display Options or 3) &mdash; Number of columns. Applies to <code>grid</code> and <code>spotlight</code> layouts. On mobile (&lt;600px) always collapses to 1 column; on tablet 2 columns.</li>";
	echo "<li><code>photo_size</code> (int 60&ndash;220, default: Display Options or 120) &mdash; Circular photo size in pixels for <code>grid</code> layout.</li>";
	echo "<li><code>show_photos</code> (true|false, default: <code>true</code>) &mdash; Show member photos. If false, only name and position are shown.</li>";
	echo "<li><code>show_bio</code> (true|false, default: <code>false</code>) &mdash; Show the member&rsquo;s Bio field (entered in the Board Member CPT). Appears below the position in all layouts.</li>";
	echo "</ul>";
	echo "<p><strong>Examples:</strong></p>";
	echo "<ul>";
	echo "<li><code>[stagekitwp_board_members]</code> &mdash; Default grid, 3 columns, photos on.</li>";
	echo "<li><code>[stagekitwp_board_members layout=\"spotlight\" columns=\"4\"]</code> &mdash; Tall portrait cards, 4 columns.</li>";
	echo "<li><code>[stagekitwp_board_members layout=\"list\" show_bio=\"true\"]</code> &mdash; Horizontal list with bio text.</li>";
	echo "<li><code>[stagekitwp_board_members layout=\"table\" show_photos=\"false\"]</code> &mdash; Compact table, no photos.</li>";
	echo "</ul>";
	echo "<p><strong>Notes:</strong> Colours (background, text, border) are set in <strong>StageKitWP &rarr; Display Options &rarr; Board Members</strong>. Grid image size is controlled by <strong>Grid Photo Size (px)</strong> in the same tab (or per-shortcode via <code>photo_size</code>). When the StageKitWP Theme is active, dark-mode variants of those colours are applied automatically when the visitor switches to dark mode. The <em>Bio</em> field is entered on each Board Member post in the admin.</p>";
	
	echo "<h3>11. [stagekitwp_contributors]</h3>";
	echo "<p><strong>Purpose:</strong> Display contributor/donor listings.</p>";
	echo "<p><strong>Example:</strong> <code>[stagekitwp_contributors]</code></p>";
	
	echo "<h3>12. [stagekitwp_advertisers]</h3>";
	echo "<p><strong>Purpose:</strong> Display advertiser listings.</p>";
	echo "<p><strong>Parameters:</strong></p>";
	echo "<ul>";
	echo "<li><code>category</code> (string) - Filter by category (e.g., \"restaurant\")</li>";
	echo "</ul>";
	echo "<p><strong>Example:</strong> <code>[stagekitwp_advertisers category=\"restaurant\"]</code></p>";
	
	echo "<h3>13. [stagekitwp_programs]</h3>";
	echo "<p><strong>Purpose:</strong> Display downloadable program PDFs grouped by season.</p>";
	echo "<p><strong>Layouts:</strong>";
	echo "<ul>";
	echo "<li><code>gallery</code> (default) &mdash; Program thumbnails grouped by season or filtered by a specific season.</li>";
	echo "<li><code>current_link</code> &mdash; Single link to the current show&rsquo;s program. If the current show has no program, it automatically falls back to the previous show that has one.</li>";
	echo "</ul></p>";
	echo "<p><strong>Parameters:</strong></p>";
	echo "<ul>";
	echo "<li><code>layout</code> (string, default: <code>gallery</code>) - <code>gallery</code> or <code>current_link</code></li>";
	echo "<li><code>season</code> (integer or slug) - Display programs for specific season</li>";
	echo "<li><code>columns</code> (integer, default: 3) - Number of columns in gallery</li>";
	echo "<li><code>size</code> (string, default: \"medium\") - Thumbnail size</li>";
	echo "<li><code>link_text</code> (string, default: \"View Program\") - Link text for <code>current_link</code> layout</li>";
	echo "<li><code>open_new</code> (true|false, default: true) - Open link in a new tab for <code>current_link</code> layout</li>";
	echo "</ul>";
	echo "<p><strong>Example:</strong> <code>[stagekitwp_programs season=\"177\" columns=\"2\"]</code></p>";
	echo "<p><strong>Example:</strong> <code>[stagekitwp_programs layout=\"current_link\" link_text=\"View Current Program\"]</code></p>";
	echo "<p><strong>Colours:</strong> Uses the <strong>Show</strong> Display Options tab.</p>";

	echo "<h3>14. [stagekitwp_tickets]</h3>";
	echo "<p><strong>Purpose:</strong> Display ticket purchase links for the current season and its shows. Five fully responsive layouts. Every show with a ticket URL gets a <strong>Get Info</strong> link beside the ticket button, linking to that show&rsquo;s CPT page (e.g. <code>/show/the-cottage/</code>). Dark/light mode fully supported via CSS custom properties.</p>";
	echo "<p><strong>Layouts:</strong></p>";
	echo "<ul>";
	echo "<li><code>banner</code> (default) &mdash; Full-width solid-colour stacked buttons, one per season/show. Label above, title below. Get Info appears as a small underline link below each button.</li>";
	echo "<li><code>cards</code> &mdash; Card grid: 2:3 poster image, label, title, dates, genre pill. Footer holds <strong>Get Tickets</strong> and <strong>Get Info</strong> as sibling links. Card is a <code>&lt;div&gt;</code> (not <code>&lt;a&gt;</code>) to avoid invalid nested links. Auto-wraps responsively.</li>";
	echo "<li><code>table</code> &mdash; Scrollable table: Production | Dates | Genre | Tickets. Season row highlighted. Get Info appears beside Get Tickets in the last cell.</li>";
	echo "<li><code>minimal</code> &mdash; Borderless ruled list: label, title, dates, animated circle-arrow. Get Info appears as a small pill link below each row.</li>";
	echo "<li><code>spotlight</code> &mdash; Full-width season hero banner (image + gradient + CTA), then a responsive equal-column grid of show tiles. Tile count drives column count automatically (3 shows = 3 equal thirds; PHP injects <code>--stagekitwp-spotlight-count</code>). Each tile: 4:3 image (<code>object-fit:contain</code>) on top, then label / title / dates / Get Tickets, Get Info below the tile. Responsive: &le;700px = 2 columns, &le;400px = 1 column.</li>";
	echo "</ul>";
	echo "<p><strong>Parameters:</strong></p>";
	echo "<ul>";
	echo "<li><code>layout</code> (banner|cards|table|minimal|spotlight, default: <code>banner</code>)</li>";
	echo "<li><code>show_limit</code> (integer, default: 4) &mdash; Maximum shows to display (0 = no limit).</li>";
	echo "<li><code>show_image</code> (true|false, default: true) &mdash; Show poster image. Cards and Spotlight only. Spotlight shows a placeholder emoji when false or no image is set.</li>";
	echo "<li><code>show_dates</code> (true|false, default: true) &mdash; Show performance date string.</li>";
	echo "<li><code>show_genre</code> (true|false, default: false) &mdash; Show genre badge (cards / spotlight) or genre column (table).</li>";
	echo "<li><code>label_season</code> (string, default: &quot;Season Tickets&quot;) &mdash; Override season-level type label.</li>";
	echo "<li><code>label_show</code> (string, default: &quot;Show Tickets&quot;) &mdash; Override show-level type label. In spotlight and minimal the time slot (Fall / Winter / Spring) is used automatically when set on the show.</li>";
	echo "<li><code>button_text</code> (string, default: &quot;Get Tickets&quot;) &mdash; CTA button text (cards, table, spotlight).</li>";
	echo "</ul>";
	echo "<p><strong>Get Info links:</strong> Every show gets a <strong>Get Info</strong> link to its CPT permalink. Hidden when the show has no published page. Always a separate element &mdash; never nested inside the ticket link (valid HTML).</p>";
	echo "<p><strong>Dark / Light mode:</strong> All colours are CSS custom properties (<code>--stagekitwp-tk-bg</code>, <code>--stagekitwp-tk-text</code>, <code>--stagekitwp-tk-btn</code>, <code>--stagekitwp-tk-border</code>) on the block wrapper. The JS switcher overrides these in dark mode and removes them on restore, so light-mode shortcode defaults apply cleanly. Configure colours under <strong>StageKitWP &rarr; Display Options &rarr; Tickets</strong>.</p>";
	echo "<p><strong>Examples:</strong></p>";
	echo "<ul>";
	echo "<li><code>[stagekitwp_tickets]</code> &mdash; Default banner style</li>";
	echo "<li><code>[stagekitwp_tickets layout=&quot;spotlight&quot;]</code> &mdash; Hero + equal-column tile grid</li>";
	echo "<li><code>[stagekitwp_tickets layout=&quot;spotlight&quot; show_image=&quot;false&quot;]</code> &mdash; Spotlight with placeholder tiles</li>";
	echo "<li><code>[stagekitwp_tickets layout=&quot;cards&quot; button_text=&quot;Book Now&quot; show_genre=&quot;true&quot;]</code></li>";
	echo "<li><code>[stagekitwp_tickets layout=&quot;table&quot; show_dates=&quot;true&quot; show_genre=&quot;true&quot;]</code></li>";
	echo "<li><code>[stagekitwp_tickets layout=&quot;minimal&quot; label_season=&quot;Full Season Pass&quot;]</code></li>";
	echo "</ul>";
	echo "<p><strong>Notes:</strong> Only the season marked <em>Current</em> is shown. Only shows with a saved Tickets URL appear. Spotlight tile count drives grid columns automatically &mdash; no manual column setting needed.</p>";

	echo "<h3>15. [stagekitwp_past_shows]</h3>";
	echo "<p><strong>Purpose:</strong> Displays past seasons and their shows. Automatically excludes current and upcoming seasons. Two layouts available.</p>";
	echo "<p><strong>Layouts:</strong></p>";
	echo "<ul>";
	echo "<li><code>list</code> (default) &mdash; Flat CSS-grid column list: Season | Slot | Show Name | Author | Programme | Awards. Season name on first row only per group; alternating row tint between seasons. Mobile (&le;640px): rows stack vertically so Season, Slot, Title, Author, Programme, and Awards all remain visible.</li>";
	echo "<li><code>cards</code> &mdash; One card per season. Season name in a coloured card header; each show as a row inside the card with Slot | Title + Author | PDF icon + badges. Cards in a responsive auto-fill grid (min 280px).</li>";
	echo "</ul>";
	echo "<p><strong>Sort order:</strong> Seasons newest-first; shows within each season Spring &rarr; Winter &rarr; Fall (descending).</p>";
	echo "<p><strong>Programme PDF link:</strong> Displays an inline red PDF icon (SVG) + &ldquo;Programme&rdquo; text link when a programme file is uploaded to the show. Clicking opens the PDF in a new tab.</p>";
	echo "<p><strong>Award badges:</strong> &#11088;&nbsp;Winner and &#127917;&nbsp;Nom. badges appear when the show has Award CPT records. Only the badges that apply are shown.</p>";
	echo "<p><strong>Parameters:</strong></p>";
	echo "<ul>";
	echo "<li><code>layout</code> (list|cards, default: <code>list</code>)</li>";
	echo "<li><code>limit</code> (int, default: <code>-1</code>) &mdash; Max past seasons. Use <code>3</code> to show only the three most recent.</li>";
	echo "<li><code>show_author</code> (true|false, default: <code>true</code>)</li>";
	echo "<li><code>show_program</code> (true|false, default: <code>true</code>) &mdash; Show PDF icon + link.</li>";
	echo "<li><code>show_awards</code> (true|false, default: <code>true</code>) &mdash; Show winner/nomination badges.</li>";
	echo "</ul>";
	echo "<p><strong>Examples:</strong></p>";
	echo "<ul>";
	echo "<li><code>[stagekitwp_past_shows]</code> &mdash; All past seasons, list layout.</li>";
	echo "<li><code>[stagekitwp_past_shows layout=\"cards\"]</code> &mdash; Season cards layout.</li>";
	echo "<li><code>[stagekitwp_past_shows layout=\"cards\" limit=\"3\"]</code> &mdash; Three most recent seasons as cards.</li>";
	echo "<li><code>[stagekitwp_past_shows show_awards=\"false\" show_program=\"false\"]</code> &mdash; Titles and authors only.</li>";
	echo "</ul>";
	echo "<p><strong>Colours:</strong> Shares the Show Display Options tab (stagekitwp_show_* keys). Dark/light mode fully supported when TM Theme is active.</p>";

	echo "<h3>16. [stagekitwp_landingpage]</h3>";
	echo "<p><strong>Purpose:</strong> Renders a rich per-show landing page directly at each Show&rsquo;s URL (e.g. <code>/show/the-cottage/</code>). Four layouts cover every use-case from a clean poster card to a full cinematic hero. Every aspect &mdash; typography, colours, alignment, font, and field list &mdash; is configurable via shortcode attributes, per-show meta, or Display Options globals.</p>";

	echo "<p><strong>Front-end Display routing:</strong> When the <strong>Show Front-end Display</strong> meta box is set to <em>Landing Page</em> for a show, visiting that show&rsquo;s CPT permalink automatically renders <code>[stagekitwp_landingpage]</code> with the correct show ID. No shortcode on the page is required.</p>";

	echo "<h4>Layouts</h4>";
	echo "<ul>";
	echo "<li><code>card</code> (default) &mdash; Two-column card: poster image on the left, all fields on the right. On mobile the image stacks above the info. A second Get Tickets button appears directly below the poster when a ticket URL is set.</li>";
	echo "<li><code>hero</code> &mdash; Full-width cinematic banner: poster fills the header with a dark gradient; show title overlays the bottom. Info and buttons sit below. No per-image ticket button (CTA is in the banner itself).</li>";
	echo "<li><code>programme</code> &mdash; Classic theatre programme: compact poster on the top-left; crew credits in a two-column grid beside it; synopsis, dates, venue, cast, and tickets below. A second Get Tickets button appears below the poster.</li>";
	echo "<li><code>minimal</code> &mdash; Single-column, no card chrome. Title, poster image (with ticket button below), then fields. Best for embedding inside an existing page.</li>";
	echo "</ul>";

	echo "<h4>Field list (<code>field_list=</code>)</h4>";
	echo "<p>Comma-separated list of fields to display. Default: <code>show_name,show_image,author,director,producer,stage_manager,synopsis,show_dates,ticket_url,program_pdf,cast,venue</code></p>";
	echo "<ul>";
	echo "<li><code>show_name</code> &mdash; Show title as H1 heading.</li>";
	echo "<li><code>show_image</code> &mdash; Poster / main image. Also controls the hero banner background and the second ticket button below the image.</li>";
	echo "<li><code>program_pdf</code> &mdash; Programme PDF icon + link (shown when a program file is saved on the show).</li>";
	echo "<li><code>author</code>, <code>sub_authors</code>, <code>director</code>, <code>associate_director</code>, <code>producer</code>, <code>stage_manager</code> &mdash; Crew credits (label + value rows).</li>";
	echo "<li><code>synopsis</code> &mdash; Show synopsis (italic body copy).</li>";
	echo "<li><code>show_dates</code> &mdash; Performance dates.</li>";
	echo "<li><code>venue</code> &mdash; Venue name, address, phone, website.</li>";
	echo "<li><code>ticket_url</code> &mdash; Get Tickets button (or label + link if <code>urlbutton=false</code>). Also controls the second ticket button under the image.</li>";
	echo "<li><code>cast</code> &mdash; Plain cast list (Character &mdash; Actor). Columns set via <code>castcols=</code>.</li>";
	echo "<li><code>castwithbio</code> &mdash; Cast list with headshots and bios. Mutually exclusive with <code>cast</code>.</li>";
	echo "</ul>";

	echo "<h4>Ticket button options</h4>";
	echo "<ul>";
	echo "<li><code>urlbutton</code> (true|false, default: Display Options or false) &mdash; <code>true</code> renders a styled <em>Get Tickets</em> button; <code>false</code> renders a plain label+link. Controls both the in-field button and the second button under the image.</li>";
	echo "<li><code>program_button</code> (true|false, default: false) &mdash; Shows a dedicated <em>Programme</em> button (if the show has a program PDF). Rendered in <code>card</code> and <code>hero</code> layouts.</li>";
	echo "<li><code>buttonformat</code> (string, default: <code>default</code>) &mdash; CSS modifier class for button style. Values: <code>default</code>, <code>outline</code>, <code>ghost</code>, <code>pill</code>.</li>";
	echo "</ul>";

	echo "<h4>Typography &amp; layout attributes</h4>";
	echo "<ul>";
	echo "<li><code>font=</code> (slug) &mdash; Body font. Use a slug from the 27-font list (e.g. <code>playfair</code>, <code>montserrat</code>, <code>georgia</code>). Leave blank to inherit the theme font.</li>";
	echo "<li><code>heading_font=</code> (slug) &mdash; Separate font for show title and section headings. Defaults to same as <code>font=</code> when blank.</li>";
	echo "<li><code>heading_size=</code> (xs|sm|md|lg|xl|xxl, default: md) &mdash; Scales all heading text by a multiplier (0.75&times; &ndash; 1.7&times;).</li>";
	echo "<li><code>text_size=</code> (xs|sm|md|lg|xl|xxl, default: md) &mdash; Scales all body text.</li>";
	echo "<li><code>align=</code> (left|center|right|justify, default: left) &mdash; Text alignment for all content inside the landing page.</li>";
	echo "<li><code>castcols=</code> (integer, default: 2) &mdash; Number of columns in the cast list.</li>";
	echo "<li><code>hard_breaks=</code> (true|false, default: false) &mdash; Convert newlines to &lt;br&gt; in synopsis.</li>";
	echo "</ul>";

	echo "<h4>Available font slugs (27 fonts)</h4>";
	echo "<p><em>System:</em> <code>inherit</code> (theme default), <code>georgia</code>, <code>times</code>, <code>palatino</code>, <code>arial</code>, <code>verdana</code>, <code>trebuchet</code>, <code>courier</code>, <code>comic-sans</code>, <code>impact</code></p>";
	echo "<p><em>Google Serif:</em> <code>playfair</code>, <code>eb-garamond</code>, <code>cormorant</code>, <code>libre-baskerville</code>, <code>lora</code>, <code>merriweather</code></p>";
	echo "<p><em>Google Sans:</em> <code>montserrat</code>, <code>raleway</code>, <code>josefin-sans</code>, <code>libre-franklin</code>, <code>nunito</code>, <code>poppins</code></p>";
	echo "<p><em>Google Display:</em> <code>oswald</code>, <code>cinzel</code>, <code>alfa-slab-one</code>, <code>dm-serif</code>, <code>spectral</code></p>";
	echo "<p>Google fonts are loaded automatically from Google Fonts with <code>display=swap</code> only when the slug is used on that page.</p>";

	echo "<h4>Season banner</h4>";
	echo "<p><code>show_season_banner=</code> (true|false, default: false) &mdash; Appends the season&rsquo;s <em>Social Banner</em> image as a full-width strip at the bottom of the card and hero layouts. Requires the show to be assigned to a season with a Social Banner uploaded in Season Builder.</p>";

	echo "<h4>Per-show overrides (Show meta box)</h4>";
	echo "<p>Every typography and layout option can be overridden per-show from the <strong>Front-end Display</strong> meta box on each Show post. The resolution order is: shortcode attribute &rarr; per-show meta &rarr; Display Options global &rarr; hard-coded default.</p>";
	echo "<p>Per-show heading labels (e.g. &ldquo;Written by&rdquo;, &ldquo;Directed by&rdquo;) can also be overridden per-show, letting you use &ldquo;Choreography&rdquo; instead of &ldquo;Producer&rdquo; for a specific production.</p>";

	echo "<h4>Dark / Light mode</h4>";
	echo "<p>When the StageKitWP Theme is active, the landing page participates in the site-wide dark/light toggle. Colours are set in <strong>Display Options &rarr; Landing Page</strong>. The JS patcher applies <code>!important</code> CSS custom properties to beat inline <code>style=</code> attributes already on the wrapper.</p>";

	echo "<h4>Examples</h4>";
	echo "<ul>";
	echo "<li><code>[stagekitwp_landingpage show_id=\"253\"]</code> &mdash; Card layout with all defaults for show ID 253.</li>";
	echo "<li><code>[stagekitwp_landingpage show_id=\"253\" layout=\"hero\"]</code> &mdash; Cinematic hero banner.</li>";
	echo "<li><code>[stagekitwp_landingpage show_id=\"253\" layout=\"programme\" font=\"playfair\" heading_size=\"lg\"]</code> &mdash; Programme style with Playfair Display, large headings.</li>";
	echo "<li><code>[stagekitwp_landingpage show_id=\"253\" urlbutton=\"true\" buttonformat=\"pill\" align=\"center\"]</code> &mdash; Centred text, pill-style ticket button.</li>";
	echo "<li><code>[stagekitwp_landingpage show_id=\"253\" layout=\"hero\" urlbutton=\"true\" program_button=\"true\" buttonformat=\"outline\"]</code> &mdash; Hero with both ticket and programme buttons.</li>";
	echo "<li><code>[stagekitwp_landingpage show_id=\"253\" field_list=\"show_name,show_image,synopsis,ticket_url\"]</code> &mdash; Minimal fields only.</li>";
	echo "</ul>";
	echo "<p><strong>Note:</strong> Also available as a Gutenberg block (<em>StageKitWP Landing Page</em>) with a full visual editor UI including live field-list management and shortcode preview. Not available as a Beaver Builder module.</p>";

	echo "<h2>Season Status Logic</h2>";
	echo "<p>Several shortcodes accept a <code>which</code> attribute. Status is determined at render time from season start/end dates:</p>";
	echo "<ul>";
	echo "<li><strong>current</strong> &mdash; Today falls between the season start and end dates</li>";
	echo "<li><strong>upcoming</strong> &mdash; Season start date is in the future</li>";
	echo "<li><strong>past</strong> &mdash; Season end date is in the past</li>";
	echo "<li><strong>next</strong> &mdash; The first upcoming season after the current one (or first upcoming when there is no current season)</li>";
	echo "<li><strong>all</strong> &mdash; All seasons regardless of status</li>";
	echo "</ul>";

	echo "<h2>Season Builder</h2>";
	echo "<p>Use <strong>StageKitWP &rarr; Season Builder</strong> to manage seasons, shows, cast, and awards in one unified screen.</p>";
	echo "<ul>";
	echo "<li>Two-tab interface: <strong>Details</strong> (metadata, shows, cast, venues, auditions, awards) and <strong>Media</strong> (season images, programme PDFs)</li>";
	echo "<li>Set season status (Past / Current / Upcoming) &mdash; only one season may be marked Current at a time</li>";
	echo "<li>Assign venues to individual shows for performance-location information</li>";
	echo "</ul>";

	echo "<h2>Display Options</h2>";
	echo "<p>Access via <strong>StageKitWP &rarr; Display Options</strong>. Twelve independent tabs &mdash; one per shortcode group:</p>";
	echo "<ul>";
	echo "<li><strong>Board Members</strong> &mdash; used by <code>[stagekitwp_board_members]</code>. Extra: <strong>Grid Columns</strong>, <strong>Grid Photo Size</strong></li>";
	echo "<li><strong>Advertiser</strong> &mdash; used by <code>[stagekitwp_advertisers]</code>. Extra: <strong>Grid Columns</strong></li>";
	echo "<li><strong>Sponsor</strong> &mdash; used by <code>[stagekitwp_sponsors]</code> (grid and slider layouts). Extra: <strong>Diamond</strong> top tier, tier header overrides, <strong>Show Tier Headers</strong>, and <strong>Logo Size</strong>.</li>";
	echo "<li><strong>Contributor</strong> &mdash; used by <code>[stagekitwp_contributors]</code></li>";
	echo "<li><strong>Testimonials</strong> &mdash; used by <code>[stagekitwp_testimonials]</code>. Extra: <strong>Rating Symbol</strong> (Stars, Thumbs Up, Rockets, Hearts, Theatre Masks), and Per Show mode defaults: <strong>Reviews Per Show</strong>, <strong>Review Alignment</strong> (left, center, right, alternating, alternating_lr)</li>";
	echo "<li><strong>Season</strong> &mdash; used by <code>[stagekitwp_seasons]</code> and <code>[stagekitwp_season_shows]</code>. Colour fields: Background, Text, Border, H2 (section headings), H3 (sub-headings).</li>";
	echo "<li><strong>Show</strong> &mdash; used by <code>[stagekitwp_shows]</code>, <code>[stagekitwp_show_cast]</code>, <code>[stagekitwp_season_shows]</code>, <code>[stagekitwp_past_shows]</code>, and <code>[stagekitwp_programs]</code>. Colour fields: Background, Text, Border, H2, H3.</li>";
	echo "<li><strong>Auditions</strong> &mdash; used by <code>[stagekitwp_auditions]</code></li>";
	echo "<li><strong>Awards</strong> &mdash; used by <code>[stagekitwp_awards]</code>. Colour fields: Background, Text, Border, H2 (season heading), H3 (category heading).</li>";
	echo "<li><strong>Venues</strong> &mdash; used by <code>[stagekitwp_venues]</code>. Colour fields: Background, Text, Border, H2 (venue name), H3 (section labels).</li>";
	echo "<li><strong>Tickets</strong> &mdash; used by <code>[stagekitwp_tickets]</code>. Extra: <strong>Button / Accent Color</strong> and <strong>Button Hover Color</strong></li>";
	echo "<li><strong>Landing Page</strong> &mdash; used by <code>[stagekitwp_landingpage]</code>. Colour fields: Background, Text, Border, H1 (show title), H2 (accent / label colour). Extra: <strong>Body Font</strong>, <strong>Heading Font</strong>, <strong>Heading Size</strong>, <strong>Body Text Size</strong>, <strong>Text Alignment</strong>.</li>";
	echo "</ul>";
	echo "<p>Tabs with only Background / Text / Border colours: Board Members, Advertiser, Sponsor, Contributor, Auditions, Tickets (plus its button colours).</p>";
	echo "<p>All colour fields show side-by-side <strong>Light / Dark</strong> pickers when the StageKitWP Theme is active and the frontend mode switcher is enabled. Click the <strong>&times;</strong> clear button next to any picker to revert that slot to its theme/shortcode default.</p>";
	echo "<p>All tabs also expose: <strong>Base Font Family</strong> &bull; <strong>Border Width / Radius / Shadow / Disable Border</strong> controls.</p>";

	echo "<h2>Settings</h2>";
	echo "<p>Access via <strong>StageKitWP &rarr; Settings</strong>.</p>";
	echo "<ul>";
	echo "<li><strong>Season Builder CPT Menus</strong> &mdash; Toggle visibility of Seasons, Shows, Cast, and Awards in the admin sidebar. When disabled, use the Season Builder exclusively.</li>";
	echo "<li><strong>Google Maps API Key</strong> &mdash; Enables map thumbnail images on venue cards in <code>[stagekitwp_venues]</code>.</li>";
	echo "<li><strong>Auditions Page</strong> &mdash; Select the page that <code>[stagekitwp_season_shows]</code> audition buttons should link to.</li>";
	echo "</ul>";
	echo "<p><strong>Sample Pages:</strong> Click <em>Create Sample Pages</em> to generate demo pages (one per shortcode). Edit them as needed. Click <em>Delete Sample Pages</em> to remove them all.</p>";

	echo "<h2>Image &amp; Banner Size Guide</h2>";
	echo "<p>Every image field has an ideal size. Upload at or above the recommended dimensions &mdash; WordPress generates the cropped sizes automatically. SVG files are not supported; use PNG or JPEG.</p>";

	echo "<h3>Show Poster (<code>_stagekitwp_show_sm_image</code>)</h3>";
	echo "<p>The single most-used image in the plugin &mdash; it appears in five shortcodes across seven different contexts.</p>";
	echo "<ul>";
	echo "<li><strong>Recommended:</strong> <strong>800 &times; 1200 px</strong> (portrait, 2:3 ratio)</li>";
	echo "<li><strong>Minimum:</strong> 600 &times; 900 px &mdash; below this cards will look blurry on retina screens</li>";
	echo "<li><strong>Format:</strong> JPEG (preferred for photos) or PNG (for graphic designs)</li>";
	echo "</ul>";
	echo "<p>How it is displayed:</p>";
	echo "<ul>";
	echo "<li><code>[stagekitwp_landingpage]</code> <strong>Card layout</strong> &mdash; left column, up to 38% width, max-height 420 px, <code>object-fit:cover</code>. The poster is letterboxed/cropped to fill the column.</li>";
	echo "<li><code>[stagekitwp_landingpage]</code> <strong>Hero layout</strong> &mdash; full-width <code>&lt;img&gt;</code>, natural aspect ratio, <code>object-fit:contain</code>. The entire poster is always visible; the height matches the image.</li>";
	echo "<li><code>[stagekitwp_landingpage]</code> <strong>Programme layout</strong> &mdash; fixed left column 200 px wide (120 px on mobile); height scales to ratio.</li>";
	echo "<li><code>[stagekitwp_landingpage]</code> <strong>Minimal layout</strong> &mdash; thumbnail strip up to 360 px wide.</li>";
	echo "<li><code>[stagekitwp_tickets]</code> <strong>Cards</strong> &mdash; card image region, 2:3 aspect ratio, <code>object-fit:cover</code>; served at WordPress <em>medium</em> size (300 &times; 300 max). Use at least 600 px wide to avoid blur.</li>";
	echo "<li><code>[stagekitwp_tickets]</code> <strong>Spotlight tiles</strong> &mdash; 4:3 tile image region, <code>object-fit:contain</code>; served at WordPress <em>medium</em> size. A 2:3 portrait poster will be letterboxed inside the 4:3 frame.</li>";
	echo "<li><code>[stagekitwp_season_shows]</code> <strong>Spotlight &amp; Cards</strong> &mdash; 4:3 image frame, <code>object-fit:contain</code>; shows at whatever size the column allows (typically 200&ndash;400 px).</li>";
	echo "</ul>";

	echo "<h3>Season Front Image (<code>_stagekitwp_season_image_front</code>)</h3>";
	echo "<ul>";
	echo "<li><strong>Recommended:</strong> <strong>900 &times; 1200 px</strong> (portrait, 3:4 ratio) &mdash; represents the front of a physical season brochure</li>";
	echo "<li>Used by <code>[stagekitwp_seasons]</code> (card layout hero frame, 16:9 display, <code>object-fit:contain</code>) and as a fallback when no Website Banner is uploaded</li>";
	echo "<li>Also appears as the <code>[stagekitwp_tickets]</code> <strong>Spotlight hero</strong> fallback (full-width, <code>object-fit:cover</code>, min-height 280 px)</li>";
	echo "</ul>";

	echo "<h3>Season Website Banner (<code>_stagekitwp_season_social_banner</code>)</h3>";
	echo "<p>The primary horizontal marketing image for a season. Appears prominently across multiple shortcodes.</p>";
	echo "<ul>";
	echo "<li><strong>Recommended:</strong> <strong>1920 &times; 600 px</strong> (landscape, roughly 16:5 ratio)</li>";
	echo "<li><strong>Minimum:</strong> 1200 &times; 375 px</li>";
	echo "<li>WordPress serves this at <em>full</em> size &mdash; upload at full resolution for best quality</li>";
	echo "</ul>";
	echo "<p>How it is displayed:</p>";
	echo "<ul>";
	echo "<li><code>[stagekitwp_season_shows]</code> &mdash; Full-width banner strip, height fixed at 220 px, <code>background-size:cover</code>. Only the centre of tall images is shown; wide banners work best.</li>";
	echo "<li><code>[stagekitwp_tickets]</code> <strong>Spotlight hero</strong> &mdash; full-width, <code>object-fit:cover</code>, min-height 280 px. The banner is preferred over the front image here.</li>";
	echo "<li><code>[stagekitwp_landingpage]</code> <strong>Hero &amp; Programme layouts</strong> &mdash; full-width strip below the main content (season-banner strip), height auto, <code>object-fit:contain</code>.</li>";
	echo "<li><code>[stagekitwp_seasons]</code> &mdash; Preferred over the front image for the 16:9 hero frame.</li>";
	echo "</ul>";

	echo "<h3>Cast Member Photo (<code>_stagekitwp_cast_picture</code>)</h3>";
	echo "<ul>";
	echo "<li><strong>Recommended:</strong> <strong>600 &times; 800 px</strong> (portrait, 3:4 ratio)</li>";
	echo "<li><strong>Minimum:</strong> 400 &times; 533 px</li>";
	echo "<li>Displayed in <code>[stagekitwp_landingpage]</code> cast grid: 3:4 frame, <code>object-fit:contain</code>. Card size varies by column count (3 columns = ~200&ndash;280 px wide).</li>";
	echo "<li>Landscape photos will be letterboxed inside the portrait frame; close-up head shots work best.</li>";
	echo "</ul>";

	echo "<h3>Board Member Photo (<code>_stagekitwp_photo</code>)</h3>";
	echo "<ul>";
	echo "<li><strong>Recommended:</strong> <strong>400 &times; 400 px</strong> (square) or <strong>400 &times; 533 px</strong> (portrait, 3:4 ratio)</li>";
	echo "<li><strong>Grid layout</strong> &mdash; circular crop 120 &times; 120 px, <code>object-fit:cover</code>. Square or head-shot crops work best.</li>";
	echo "<li><strong>List layout</strong> &mdash; circular crop 64 &times; 64 px, <code>object-fit:cover</code>.</li>";
	echo "<li><strong>Table layout</strong> &mdash; circular crop 44 &times; 44 px, <code>object-fit:cover</code>.</li>";
	echo "<li><strong>Spotlight layout</strong> &mdash; 3:4 portrait frame, <code>object-fit:cover</code>, full column width. Use a portrait crop for this layout.</li>";
	echo "</ul>";

	echo "<h3>Sponsor / Advertiser Logo (<code>_stagekitwp_logo</code>)</h3>";
	echo "<ul>";
	echo "<li><strong>Recommended:</strong> <strong>400 &times; 200 px</strong> (landscape) or <strong>400 &times; 400 px</strong> (square)</li>";
	echo "<li><strong>Format:</strong> PNG with transparent background strongly recommended</li>";
	echo "<li><strong>Grid/cards</strong> &mdash; constrained to max 200 &times; 200 px, <code>object-fit:contain</code>. Logos are never cropped.</li>";
	echo "<li><strong>Slider</strong> &mdash; max-height 80 px, <code>object-fit:contain</code>. Use wide logos for best appearance in the carousel.</li>";
	echo "</ul>";

	echo "<h3>Summary Table</h3>";
	echo "<table style='border-collapse:collapse;width:100%;font-size:13px;'>";
	echo "<thead><tr style='background:#f0f0f0;'>";
	echo "<th style='padding:6px 10px;text-align:left;border:1px solid #ddd;'>Image Field</th>";
	echo "<th style='padding:6px 10px;text-align:left;border:1px solid #ddd;'>CPT</th>";
	echo "<th style='padding:6px 10px;text-align:left;border:1px solid #ddd;'>Recommended Size</th>";
	echo "<th style='padding:6px 10px;text-align:left;border:1px solid #ddd;'>Ratio</th>";
	echo "<th style='padding:6px 10px;text-align:left;border:1px solid #ddd;'>Used In</th>";
	echo "</tr></thead>";
	echo "<tbody>";
	$img_rows = [
		['Show Poster', 'Show', '800 &times; 1200 px', '2:3 portrait', '<code>[stagekitwp_tickets]</code>, <code>[stagekitwp_landingpage]</code>, <code>[stagekitwp_season_shows]</code>'],
		['Season Front Image', 'Season', '900 &times; 1200 px', '3:4 portrait', '<code>[stagekitwp_seasons]</code>, <code>[stagekitwp_tickets]</code> spotlight fallback'],
		['Season Website Banner', 'Season', '1920 &times; 600 px', '~16:5 landscape', '<code>[stagekitwp_season_shows]</code>, <code>[stagekitwp_tickets]</code>, <code>[stagekitwp_seasons]</code>, <code>[stagekitwp_landingpage]</code>'],
		['Cast Photo', 'Cast', '600 &times; 800 px', '3:4 portrait', '<code>[stagekitwp_landingpage]</code> cast grid'],
		['Board Member Photo', 'Board Member', '400 &times; 400 px', 'Square', '<code>[stagekitwp_board_members]</code> all layouts'],
		['Sponsor / Advertiser Logo', 'Sponsor / Advertiser', '400 &times; 200 px (PNG)', 'Landscape', '<code>[stagekitwp_sponsors]</code>, <code>[stagekitwp_advertisers]</code>'],
	];
	foreach ( $img_rows as $row ) {
		echo "<tr>";
		foreach ( $row as $cell ) {
			echo "<td style='padding:6px 10px;border:1px solid #ddd;'>" . $cell . "</td>";
		}
		echo "</tr>";
	}
	echo "</tbody></table>";

	echo "<h2>Tips &amp; Best Practices";
	echo "<ul>";
	echo "<li>Always enter season start and end dates &mdash; they drive all <code>which=</code> filtering</li>";
	echo "<li>Only one season should be marked <em>Current</em> at a time</li>";
	echo "<li>Shows only appear in <code>[stagekitwp_tickets]</code> when a Tickets URL is saved in Season Builder</li>";
	echo "<li>Upload high-quality portrait images for best results with board-member and show cards</li>";
	echo "<li>Use the StageKitWP Theme to unlock side-by-side Light/Dark colour pickers and the site-wide dark mode toggle</li>";
	echo "<li>Use <strong>Force update check</strong> on the Plugins screen to pull a fresh manifest from the update server immediately</li>";
	echo "<li>Show names and images in shortcodes link automatically to each show&rsquo;s CPT page &mdash; set <strong>Front-end Display</strong> to <em>Landing Page</em> in the Show meta box to control what visitors see when they click through</li>";
	echo "<li>Leave H1&ndash;H3 colour pickers empty in Display Options to inherit the theme defaults &mdash; use the &times; clear button to reset a picker. H4&ndash;H6 fields have been removed as no shortcode uses them.</li>";
	echo "</ul>";

	echo "</div>";
}
?>
