<?php
/**
 * StageKitWP_Import_Export_Plugin – central registry and bootstrap.
 *
 * Responsibilities:
 *  - Instantiate and store module objects.
 *  - Register all WordPress hooks (admin menu, AJAX, REST, WP-CLI).
 *  - Expose the exporter, importer, and queue as lazy singletons.
 *
 * @package STAGEKITWP_IO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class StageKitWP_Import_Export_Plugin {

	// ── Singleton ─────────────────────────────────────────────────────────────

	private static ?StageKitWP_Import_Export_Plugin $instance = null;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->init_hooks();
	}

	// ── Lazy singletons ───────────────────────────────────────────────────────

	private ?STAGEKITWP_IMPORT_EXPORT_Exporter $exporter = null;
	private ?STAGEKITWP_IMPORT_EXPORT_Importer $importer = null;
	private ?STAGEKITWP_IMPORT_EXPORT_Queue    $queue    = null;

	public function exporter(): STAGEKITWP_IMPORT_EXPORT_Exporter {
		if ( null === $this->exporter ) {
			$this->exporter = new STAGEKITWP_IMPORT_EXPORT_Exporter( $this->modules() );
		}
		return $this->exporter;
	}

	public function importer(): STAGEKITWP_IMPORT_EXPORT_Importer {
		if ( null === $this->importer ) {
			$this->importer = new STAGEKITWP_IMPORT_EXPORT_Importer( $this->modules() );
		}
		return $this->importer;
	}

	public function queue(): STAGEKITWP_IMPORT_EXPORT_Queue {
		if ( null === $this->queue ) {
			$this->queue = new STAGEKITWP_IMPORT_EXPORT_Queue();
		}
		return $this->queue;
	}

	// ── Module registry ───────────────────────────────────────────────────────

	/** @var STAGEKITWP_IMPORT_EXPORT_Module[] */
	private array $modules = [];

	/**
	 * Returns all registered module instances, keyed by module ID.
	 *
	 * @return STAGEKITWP_IMPORT_EXPORT_Module[]
	 */
	public function modules(): array {
		return $this->modules;
	}

	/**
	 * Returns a single module by ID, or null if not found.
	 */
	public function module( string $id ): ?STAGEKITWP_IMPORT_EXPORT_Module {
		return $this->modules[ $id ] ?? null;
	}

	/**
	 * Returns the most recent export downloads that should remain available to the user.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function recent_downloads(): array {
		$downloads = get_option( 'stagekitwp_import_export_recent_downloads', [] );
		if ( ! is_array( $downloads ) ) {
			return [];
		}

		$downloads = array_values( array_filter(
			$downloads,
			static function ( $download ): bool {
				return is_array( $download )
					&& ! empty( $download['filename'] )
					&& ! empty( $download['download_url'] );
			}
		) );

		usort(
			$downloads,
			static function ( $a, $b ): int {
				return ( $b['created_at'] ?? 0 ) <=> ( $a['created_at'] ?? 0 );
			}
		);

		return array_slice( $downloads, 0, 5 );
	}

	/**
	 * Remember a newly generated export so it remains available on the screen.
	 */
	private function remember_download( string $zip_path, string $download_url, string $token ): void {
		$filename = basename( $zip_path );
		if ( '' === $filename || ! file_exists( $zip_path ) ) {
			return;
		}

		$downloads = $this->recent_downloads();
		$downloads = array_values( array_filter(
			$downloads,
			static function ( array $download ) use ( $filename ): bool {
				return ( $download['filename'] ?? '' ) !== $filename;
			}
		) );

		array_unshift( $downloads, [
			'token'        => $token,
			'filename'     => $filename,
			'download_url' => $download_url,
			'created_at'   => time(),
			'filesize'     => (int) filesize( $zip_path ),
		] );

		update_option( 'stagekitwp_import_export_recent_downloads', array_slice( $downloads, 0, 5 ) );
	}

	// ── Hooks ─────────────────────────────────────────────────────────────────

	private function init_hooks(): void {
		// Register modules after all plugins are loaded so every CPT exists.
		add_action( 'plugins_loaded', [ $this, 'register_modules' ], 20 );

		// Admin UI.
		add_action( 'admin_menu',             [ $this, 'register_admin_menu' ] );
		add_action( 'admin_init',             [ $this, 'register_settings' ] );
		add_action( 'admin_enqueue_scripts',  [ $this, 'enqueue_admin_assets' ] );

		// AJAX handlers (logged-in only – capability check done inside handler).
		add_action( 'wp_ajax_stagekitwp_import_export_export',          [ $this, 'ajax_export' ] );
		add_action( 'wp_ajax_stagekitwp_import_export_import',          [ $this, 'ajax_import' ] );
		add_action( 'wp_ajax_stagekitwp_import_export_import_csv',      [ $this, 'ajax_import_csv' ] );
		add_action( 'wp_ajax_stagekitwp_import_export_import_progress', [ $this, 'ajax_import_progress' ] );
		add_action( 'wp_ajax_stagekitwp_import_export_download',        [ $this, 'ajax_download' ] );
		add_action( 'wp_ajax_stagekitwp_import_export_dry_run',         [ $this, 'ajax_dry_run' ] );
		add_action( 'wp_ajax_stagekitwp_import_export_save_remap',      [ $this, 'ajax_save_remap' ] );
		add_action( 'wp_ajax_stagekitwp_import_export_csv_template',    [ $this, 'ajax_csv_template' ] );
		add_action( 'wp_ajax_stagekitwp_import_export_csv_rollback',    [ $this, 'ajax_csv_rollback' ] );
		add_action( 'wp_ajax_stagekitwp_import_export_purge',           [ $this, 'ajax_purge' ] );
		add_action( 'wp_ajax_stagekitwp_import_export_purge_batch',     [ $this, 'ajax_purge_batch' ] );

		// WP-CLI commands.
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			// Registered after full bootstrap so modules are available.
			add_action( 'plugins_loaded', [ $this, 'register_cli_commands' ], 30 );
		}
	}

	// ── Module registration ───────────────────────────────────────────────────

	/**
	 * Instantiates all built-in module classes and fires a filter so third-party
	 * code (or future TM add-ons) can add their own modules.
	 *
	 * Hook: `stagekitwp_import_export_modules`
	 * Filter receives and must return: STAGEKITWP_IMPORT_EXPORT_Module[]  (keyed by module ID)
	 */
	public function register_modules(): void {
		$defaults = [
			'stagekitwp-core'       => new STAGEKITWP_IMPORT_EXPORT_Mod_StageKitWP_Core(),
			'stagekitwp-members'    => new STAGEKITWP_IMPORT_EXPORT_Mod_Members_Area(),
			'stagekitwp-rc-library' => new STAGEKITWP_IMPORT_EXPORT_Mod_Rc_Library(),
			'stagekitwp-sync'       => new STAGEKITWP_IMPORT_EXPORT_Mod_Sync(),
			'stagekitwp-theme'      => new STAGEKITWP_IMPORT_EXPORT_Mod_Theme(),
		];

		/** @var STAGEKITWP_IMPORT_EXPORT_Module[] */
		$this->modules = apply_filters( 'stagekitwp_import_export_modules', $defaults );
	}

	// ── Admin menu ────────────────────────────────────────────────────────────

	public function register_admin_menu(): void {
		$parent_slug = 'stagekitwp-core';

		add_submenu_page(
			$parent_slug,
			__( 'Export', 'stagekitwp-io' ),
			__( 'Import-Export', 'stagekitwp-io' ),
			'manage_options',
			'stagekitwp-io',
			[ $this, 'render_admin_page' ]
		);

		add_submenu_page(
			$parent_slug,
			__( 'Import', 'stagekitwp-io' ),
			__( 'Import', 'stagekitwp-io' ),
			'manage_options',
			'stagekitwp-io-import',
			[ $this, 'render_import_page' ]
		);

		add_submenu_page(
			$parent_slug,
			__( 'Page ID Remap', 'stagekitwp-io' ),
			__( 'Page Remap', 'stagekitwp-io' ),
			'manage_options',
			'stagekitwp-io-remap',
			[ $this, 'render_remap_page' ]
		);

		add_submenu_page(
			$parent_slug,
			__( 'Clear Data', 'stagekitwp-io' ),
			__( 'Clear Data', 'stagekitwp-io' ),
			'manage_options',
			'stagekitwp-io-purge',
			[ $this, 'render_purge_page' ]
		);

		add_submenu_page(
			$parent_slug,
			__( 'Settings', 'stagekitwp-io' ),
			__( 'Settings', 'stagekitwp-io' ),
			'manage_options',
			'stagekitwp-io-settings',
			[ $this, 'render_settings_page' ]
		);

		add_submenu_page(
			$parent_slug,
			__( 'Instructions', 'stagekitwp-io' ),
			__( 'Instructions', 'stagekitwp-io' ),
			'manage_options',
			'stagekitwp-io-help',
			[ $this, 'render_help_page' ]
		);
	}

	/**
	 * Register the Import-Export settings (Settings API).
	 */
	public function register_settings(): void {
		register_setting(
			'stagekitwp_import_export_settings',
			'stagekitwp_import_export_batch_size',
			[
				'type'              => 'integer',
				'sanitize_callback' => [ $this, 'sanitize_batch_size' ],
				'default'           => STAGEKITWP_IMPORT_EXPORT_BATCH_DEFAULT,
			]
		);

		add_settings_section(
			'stagekitwp_import_export_settings_performance',
			__( 'Performance', 'stagekitwp-io' ),
			function () {
				echo '<p>' . esc_html__( 'Control how many records Import-Export processes per batch. Batching keeps each request short so large deletes, imports, and exports never hit the PHP execution-time limit.', 'stagekitwp-io' ) . '</p>';
			},
			'stagekitwp-io-settings'
		);

		add_settings_field(
			'stagekitwp_import_export_batch_size',
			__( 'Batch size', 'stagekitwp-io' ),
			[ $this, 'field_batch_size' ],
			'stagekitwp-io-settings',
			'stagekitwp_import_export_settings_performance',
			[ 'label_for' => 'stagekitwp_import_export_batch_size' ]
		);
	}

	/**
	 * Sanitize + clamp the batch-size option.
	 *
	 * @param mixed $value
	 * @return int
	 */
	public function sanitize_batch_size( $value ): int {
		$value = absint( $value );
		if ( $value <= 0 ) {
			$value = STAGEKITWP_IMPORT_EXPORT_BATCH_DEFAULT;
		}
		return max( STAGEKITWP_IMPORT_EXPORT_BATCH_MIN, min( STAGEKITWP_IMPORT_EXPORT_BATCH_MAX, $value ) );
	}

	/**
	 * Render the batch-size number field.
	 */
	public function field_batch_size(): void {
		$value = stagekitwp_import_export_batch_size();
		printf(
			'<input type="number" id="stagekitwp_import_export_batch_size" name="stagekitwp_import_export_batch_size" value="%1$d" min="%2$d" max="%3$d" step="5" class="small-text"> <span class="description">%4$s</span>',
			(int) $value,
			(int) STAGEKITWP_IMPORT_EXPORT_BATCH_MIN,
			(int) STAGEKITWP_IMPORT_EXPORT_BATCH_MAX,
			esc_html(
				sprintf(
					/* translators: 1: min, 2: max, 3: default */
					__( 'Records per batch (%1$d–%2$d). Default %3$d. Lower this on slow or memory-limited hosts; raise it to finish faster on fast servers.', 'stagekitwp-io' ),
					(int) STAGEKITWP_IMPORT_EXPORT_BATCH_MIN,
					(int) STAGEKITWP_IMPORT_EXPORT_BATCH_MAX,
					(int) STAGEKITWP_IMPORT_EXPORT_BATCH_DEFAULT
				)
			)
		);
	}

	public function render_admin_page(): void {
		$this->render_admin_shell( 'export' );
	}

	public function render_import_page(): void {
		$this->render_admin_shell( 'import' );
	}

	public function render_remap_page(): void {
		$this->render_admin_shell( 'remap' );
	}

	public function render_purge_page(): void {
		$this->render_admin_shell( 'purge' );
	}

	public function render_help_page(): void {
		$this->render_admin_shell( 'help' );
	}

	public function render_settings_page(): void {
		$this->render_admin_shell( 'settings' );
	}

	private function render_admin_shell( string $active ): void {
		$panels = [
			'export'   => function () { require STAGEKITWP_IMPORT_EXPORT_DIR . 'admin/views/export.php'; },
			'import'   => function () { require STAGEKITWP_IMPORT_EXPORT_DIR . 'admin/views/import.php'; },
			'remap'    => function () { require STAGEKITWP_IMPORT_EXPORT_DIR . 'admin/views/remap.php'; },
			'purge'    => function () { require STAGEKITWP_IMPORT_EXPORT_DIR . 'admin/views/purge.php'; },
			'settings' => function () { require STAGEKITWP_IMPORT_EXPORT_DIR . 'admin/views/settings.php'; },
			'help'     => function () { require STAGEKITWP_IMPORT_EXPORT_DIR . 'admin/views/instructions.php'; },
		];

		$active = isset( $panels[ $active ] ) ? $active : 'export';

		echo '<div class="wrap stagekitwp-io-hub-wrap">';
		echo '<h1>' . esc_html__( 'Import-Export', 'stagekitwp-io' ) . '</h1>';
		echo $this->render_admin_tabs( $active );
		echo '<div class="stagekitwp-io-hub-panels">';

		foreach ( $panels as $key => $callback ) {
			echo '<div class="stagekitwp-io-hub-panel" data-stagekitwp-io-panel="' . esc_attr( $key ) . '" style="' . ( $key === $active ? '' : 'display:none;' ) . '">';
			call_user_func( $callback );
			echo '</div>';
		}

		echo '</div>';
		echo '</div>';

		echo '<script>(function(){const tabs=document.querySelectorAll("[data-stagekitwp-io-tab]");const panels=document.querySelectorAll("[data-stagekitwp-io-panel]");if(!tabs.length||!panels.length)return;function activate(tabKey){tabs.forEach(tab=>tab.classList.toggle("nav-tab-active",tab.dataset.stagekitwpIoTab===tabKey));panels.forEach(panel=>panel.style.display=(panel.dataset.stagekitwpIoPanel===tabKey)?"":"none");const url=new URL(window.location.href);url.searchParams.set("stagekitwp_import_export_tab",tabKey);history.replaceState({},"",url.toString());}tabs.forEach(tab=>tab.addEventListener("click",function(){activate(this.dataset.stagekitwpIoTab);}));})();</script>';
	}

	/**
	 * Render the Import-Export hub tabs.
	 */
	private function render_admin_tabs( string $active ): string {
		$tabs = [
			'export'   => [ 'label' => __( 'Export', 'stagekitwp-io' ), 'page' => 'stagekitwp-io' ],
			'import'   => [ 'label' => __( 'Import', 'stagekitwp-io' ), 'page' => 'stagekitwp-io-import' ],
			'remap'    => [ 'label' => __( 'Page Remap', 'stagekitwp-io' ), 'page' => 'stagekitwp-io-remap' ],
			'purge'    => [ 'label' => __( 'Clear Data', 'stagekitwp-io' ), 'page' => 'stagekitwp-io-purge' ],
			'settings' => [ 'label' => __( 'Settings', 'stagekitwp-io' ), 'page' => 'stagekitwp-io-settings' ],
			'help'     => [ 'label' => __( 'Instructions', 'stagekitwp-io' ), 'page' => 'stagekitwp-io-help' ],
		];

		$html = '<nav class="nav-tab-wrapper stagekitwp-io-hub-tabs" style="margin-bottom:16px;">';
		foreach ( $tabs as $key => $tab ) {
			$classes = 'nav-tab' . ( $key === $active ? ' nav-tab-active' : '' );
			$html   .= sprintf(
				'<button type="button" class="%1$s" data-stagekitwp-io-tab="%2$s">%3$s</button>',
				esc_attr( $classes ),
				esc_attr( $key ),
				esc_html( $tab['label'] )
			);
		}
		$html .= '</nav>';

		return $html;
	}

	// ── Admin assets ──────────────────────────────────────────────────────────

	public function enqueue_admin_assets( string $hook ): void {
		// Only load on our own pages.
		$our_hooks = [
			'toplevel_page_stagekitwp-io',
			'stagekitwp_page_stagekitwp-io',
			'stagekitwp_page_stagekitwp-io-import',
			'stagekitwp_page_stagekitwp-io-remap',
			'stagekitwp_page_stagekitwp-io-purge',
			'stagekitwp_page_stagekitwp-io-settings',
			'stagekitwp_page_stagekitwp-io-help',
			'stagekitwp-i-o_page_stagekitwp-io',
			'stagekitwp-i-o_page_stagekitwp-io-import',
			'stagekitwp-i-o_page_stagekitwp-io-remap',
			'stagekitwp-i-o_page_stagekitwp-io-purge',
			'stagekitwp-i-o_page_stagekitwp-io-settings',
			'stagekitwp-i-o_page_stagekitwp-io-help',
			'stagekitwp-core_page_stagekitwp-io',
			'stagekitwp-core_page_stagekitwp-io-import',
			'stagekitwp-core_page_stagekitwp-io-remap',
			'stagekitwp-core_page_stagekitwp-io-purge',
			'stagekitwp-core_page_stagekitwp-io-settings',
			'stagekitwp-core_page_stagekitwp-io-help',
		];
		if ( ! in_array( $hook, $our_hooks, true ) ) {
			return;
		}

		$asset_version = STAGEKITWP_IMPORT_EXPORT_VERSION;
		$css_path      = STAGEKITWP_IMPORT_EXPORT_DIR . 'admin/assets/stagekitwp-io-admin.css';
		$js_path       = STAGEKITWP_IMPORT_EXPORT_DIR . 'admin/assets/stagekitwp-io-admin.js';
		if ( file_exists( $css_path ) ) {
			$asset_version = filemtime( $css_path ) ?: STAGEKITWP_IMPORT_EXPORT_VERSION;
		}
		if ( file_exists( $js_path ) ) {
			$asset_version = filemtime( $js_path ) ?: $asset_version;
		}

		wp_enqueue_style(
			'stagekitwp-io-admin',
			STAGEKITWP_IMPORT_EXPORT_URL . 'admin/assets/stagekitwp-io-admin.css',
			[],
			$asset_version
		);

		wp_enqueue_script(
			'stagekitwp-io-admin',
			STAGEKITWP_IMPORT_EXPORT_URL . 'admin/assets/stagekitwp-io-admin.js',
			[ 'jquery' ],
			$asset_version,
			true
		);

		wp_localize_script( 'stagekitwp-io-admin', 'stagekitwpImportExport', [
			'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
			'nonce'     => wp_create_nonce( 'stagekitwp_import_export_nonce' ),
			'threshold' => STAGEKITWP_IMPORT_EXPORT_ASYNC_THRESHOLD,
			'batchSize' => stagekitwp_import_export_batch_size(),
			'i18n'      => [
				'exporting'  => __( 'Exporting…', 'stagekitwp-io' ),
				'importing'  => __( 'Importing…', 'stagekitwp-io' ),
				'done'       => __( 'Done!', 'stagekitwp-io' ),
				'error'      => __( 'An error occurred. Please try again.', 'stagekitwp-io' ),
			],
		] );
	}

	// ── AJAX handlers ─────────────────────────────────────────────────────────

	/**
	 * POST  admin-ajax.php  action=stagekitwp_import_export_export
	 * Body: modules[] (comma-sep IDs or "all"), format (always "zip" for now)
	 */
	public function ajax_export(): void {
		check_ajax_referer( 'stagekitwp_import_export_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'stagekitwp-io' ), 403 );
		}

		$raw_modules = isset( $_POST['modules'] ) ? sanitize_text_field( wp_unslash( $_POST['modules'] ) ) : 'all';
		$module_ids  = ( 'all' === $raw_modules )
			? array_keys( $this->modules() )
			: array_map( 'sanitize_key', explode( ',', $raw_modules ) );

		// Include media by default; the UI toggle can turn it off.
		$include_media = ! isset( $_POST['include_media'] )
			|| in_array( (string) $_POST['include_media'], [ '1', 'true', 'on', 'yes' ], true );

		$stats    = [];
		$zip_path = $this->quiet_query( function () use ( $module_ids, $include_media, &$stats ) {
			return $this->exporter()->export(
				$module_ids,
				[ 'include_media' => $include_media ],
				$stats
			);
		} );

		if ( is_wp_error( $zip_path ) ) {
			wp_send_json_error( [ 'message' => $zip_path->get_error_message() ] );
		}

		// Return a signed download URL that streams the ZIP through the plugin's
		// dedicated handler with an attachment header, which is the most reliable
		// way to trigger a browser download in Firefox.
		$token = wp_generate_uuid4();
		$exp   = 300; // 5 minutes
		set_transient( 'stagekitwp_import_export_dl_' . $token, $zip_path, $exp );

		$download_url = add_query_arg( [
			'action'   => 'stagekitwp_import_export_download',
			'token'    => $token,
			'_wpnonce' => wp_create_nonce( 'stagekitwp_import_export_download_' . $token ),
		], admin_url( 'admin-ajax.php' ) );

		$this->remember_download( $zip_path, $download_url, $token );

		wp_send_json_success( [
			'download_url' => $download_url,
			'filename'     => basename( $zip_path ),
			'history'      => $this->recent_downloads(),
			'stats'        => [
				'post_count'    => (int) ( $stats['post_count'] ?? 0 ),
				'media_count'   => (int) ( $stats['media_count'] ?? 0 ),
				'media_files'   => (int) ( $stats['media_files'] ?? 0 ),
				'include_media' => (bool) ( $stats['include_media'] ?? false ),
				'filesize'      => (int) ( $stats['filesize'] ?? 0 ),
				'filesize_h'    => size_format( (int) ( $stats['filesize'] ?? 0 ), 1 ),
			],
		] );
	}

	/**
	 * POST  admin-ajax.php  action=stagekitwp_import_export_import
	 * Accepts a multipart file upload OR a JSON body with a remote URL.
	 */
	public function ajax_import(): void {
		check_ajax_referer( 'stagekitwp_import_export_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'stagekitwp-io' ), 403 );
		}

		$conflict = isset( $_POST['conflict'] ) ? sanitize_key( $_POST['conflict'] ) : 'skip';
		$modules  = isset( $_POST['modules'] )  ? sanitize_text_field( wp_unslash( $_POST['modules'] ) ) : 'all';

		// Determine source: uploaded file or remote URL.
		$original_name = '';
		if ( ! empty( $_FILES['stagekitwp_import_export_file']['tmp_name'] ) ) {
			$source        = $_FILES['stagekitwp_import_export_file']['tmp_name'];
			$original_name = sanitize_file_name( $_FILES['stagekitwp_import_export_file']['name'] ?? '' );
		} elseif ( ! empty( $_POST['remote_url'] ) ) {
			$source = esc_url_raw( wp_unslash( $_POST['remote_url'] ) );
		} else {
			wp_send_json_error( [ 'message' => __( 'No import source provided.', 'stagekitwp-io' ) ] );
			return;
		}

		$result = $this->quiet_query( fn() => $this->importer()->import( $source, [
			'conflict'      => $conflict,
			'modules'       => $modules,
			'original_name' => $original_name,
		] ) );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		} elseif ( isset( $result['queued'] ) && $result['queued'] ) {
			wp_send_json_success( [
				'async'    => true,
				'job_id'   => $result['job_id'],
				'message'  => __( 'Large import queued. Processing in the background…', 'stagekitwp-io' ),
			] );
		} else {
			wp_send_json_success( [
				'async'    => false,
				'summary'  => $result,
			] );
		}
	}

	/**
	 * GET  admin-ajax.php  action=stagekitwp_import_export_download&token=XYZ&_wpnonce=...
	 * Streams the ZIP file to the browser then deletes it.
	 */
	public function ajax_download(): void {
		$token = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';
		if ( '' === $token || ! preg_match( '/^[0-9a-fA-F-]+$/', $token ) ) {
			wp_die( esc_html__( 'Invalid download token.', 'stagekitwp-io' ), 400 );
		}

		check_ajax_referer( 'stagekitwp_import_export_download_' . $token );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'stagekitwp-io' ), 403 );
		}

		$zip_path = get_transient( 'stagekitwp_import_export_dl_' . $token );
		if ( ! $zip_path || ! file_exists( $zip_path ) ) {
			wp_die( esc_html__( 'Download link has expired or the file was not found. Please export again.', 'stagekitwp-io' ) );
		}

		$filename = basename( $zip_path );
		delete_transient( 'stagekitwp_import_export_dl_' . $token );

		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . filesize( $zip_path ) );
		header( 'Cache-Control: no-cache, must-revalidate' );
		header( 'Pragma: no-cache' );
		// Disable output buffering so large ZIPs stream cleanly.
		if ( ob_get_level() ) {
			ob_end_clean();
		}
		readfile( $zip_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		exit;
	}

	/**
	 * POST  admin-ajax.php  action=stagekitwp_import_export_import_csv
	 */
	public function ajax_import_csv(): void {
		check_ajax_referer( 'stagekitwp_import_export_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'stagekitwp-io' ), 'code' => 'forbidden' ] );
			return;
		}

		if ( empty( $_FILES['stagekitwp_import_export_csv']['tmp_name'] ) ) {
			$upload_err = $_FILES['stagekitwp_import_export_csv']['error'] ?? UPLOAD_ERR_NO_FILE;
			wp_send_json_error( [
				'message' => __( 'No CSV file received.', 'stagekitwp-io' ),
				'detail'  => $this->upload_error_message( $upload_err ),
				'code'    => 'no_file',
			] );
			return;
		}

		// Validate MIME / extension before we do anything with the file.
		$original_name = sanitize_file_name( $_FILES['stagekitwp_import_export_csv']['name'] ?? 'upload.csv' );
		$ext = strtolower( pathinfo( $original_name, PATHINFO_EXTENSION ) );
		if ( ! in_array( $ext, [ 'csv', 'txt' ], true ) ) {
			wp_send_json_error( [
				'message' => sprintf(
					/* translators: %s file extension */
					__( 'Unexpected file type “.%s”. Please upload a .csv file.', 'stagekitwp-io' ),
					esc_html( $ext )
				),
				'code' => 'bad_type',
			] );
			return;
		}

		$conflict = isset( $_POST['conflict'] ) ? sanitize_key( $_POST['conflict'] ) : 'skip';
		$dry_run  = ! empty( $_POST['dry_run'] );
		$season   = isset( $_POST['season_id'] ) ? (int) $_POST['season_id'] : 0;

		require_once STAGEKITWP_IMPORT_EXPORT_DIR . 'includes/class-stagekitwp-import-export-csv-importer.php';

		try {
			$file_path = $_FILES['stagekitwp_import_export_csv']['tmp_name'];
			$opts      = [ 'conflict' => $conflict, 'dry_run' => $dry_run, 'season_id' => $season ];
			$importer  = new STAGEKITWP_IMPORT_EXPORT_CSV_Importer();
			$result    = $this->quiet_query( fn() => $importer->import( $file_path, $opts ) );
		} catch ( \Throwable $e ) {
			$detail = defined( 'WP_DEBUG' ) && WP_DEBUG
				? $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine()
				: __( 'An unexpected PHP error occurred. Enable WP_DEBUG for details.', 'stagekitwp-io' );
			error_log( 'TM IO CSV import error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() );
			wp_send_json_error( [ 'message' => __( 'Import failed due to a server error.', 'stagekitwp-io' ), 'detail' => $detail, 'code' => 'exception' ] );
			return;
		}

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [
				'message' => $result->get_error_message(),
				'code'    => $result->get_error_code(),
				'detail'  => implode( ' | ', array_map(
					fn( $d ) => is_string( $d ) ? $d : wp_json_encode( $d ),
					(array) $result->get_error_data()
				) ),
			] );
		} else {
			wp_send_json_success( $result );
		}
	}

	/**
	 * Run a callable while suppressing $wpdb error output and buffering any
	 * stray PHP notices. This prevents SQLite-driver "WordPress database error:[]"
	 * lines from corrupting AJAX JSON responses when WP_DEBUG_DISPLAY is on.
	 *
	 * @param callable $fn
	 * @return mixed  Return value of $fn
	 */
	private function quiet_query( callable $fn ): mixed {
		global $wpdb;
		$prev = $wpdb->show_errors;
		$wpdb->suppress_errors( true );
		ob_start();
		try {
			$result = $fn();
		} finally {
			$noise = ob_get_clean();
			$wpdb->show_errors = $prev; // phpcs:ignore WordPress.DB.RestrictedFunctions
			if ( $noise !== '' && $noise !== false ) {
				error_log( 'TM IO – suppressed output: ' . substr( $noise, 0, 500 ) );
			}
		}
		return $result;
	}

	/**
	 * Human-readable PHP upload error string.
	 */
	private function upload_error_message( int $code ): string {
		$map = [
			UPLOAD_ERR_INI_SIZE   => 'File exceeds upload_max_filesize in php.ini.',
			UPLOAD_ERR_FORM_SIZE  => 'File exceeds MAX_FILE_SIZE in the form.',
			UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
			UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
			UPLOAD_ERR_NO_TMP_DIR => 'Missing server temp folder.',
			UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
			UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the upload.',
		];
		return $map[ $code ] ?? "Unknown upload error (code $code).";
	}

	/**
	 * POST  admin-ajax.php  action=stagekitwp_import_export_dry_run
	 * Same as import but dry_run=true forced; works for ZIP + CSV.
	 */
	public function ajax_dry_run(): void {
		check_ajax_referer( 'stagekitwp_import_export_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'stagekitwp-io' ), 403 );
		}

		$conflict = isset( $_POST['conflict'] ) ? sanitize_key( $_POST['conflict'] ) : 'skip';
		$modules  = isset( $_POST['modules'] )  ? sanitize_text_field( wp_unslash( $_POST['modules'] ) ) : 'all';

		$original_name = '';
		if ( ! empty( $_FILES['stagekitwp_import_export_file']['tmp_name'] ) ) {
			$source        = $_FILES['stagekitwp_import_export_file']['tmp_name'];
			$original_name = sanitize_file_name( $_FILES['stagekitwp_import_export_file']['name'] ?? '' );
		} elseif ( ! empty( $_POST['remote_url'] ) ) {
			$source = esc_url_raw( wp_unslash( $_POST['remote_url'] ) );
		} else {
			wp_send_json_error( [ 'message' => __( 'No source provided.', 'stagekitwp-io' ) ] );
			return;
		}

		$result = $this->quiet_query( fn() => $this->importer()->import( $source, [
			'conflict'      => $conflict,
			'modules'       => $modules,
			'dry_run'       => true,
			'original_name' => $original_name,
		] ) );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		} else {
			wp_send_json_success( $result );
		}
	}

	/**
	 * POST  admin-ajax.php  action=stagekitwp_import_export_save_remap
	 * Saves page ID remappings submitted from the remap admin view.
	 */
	public function ajax_save_remap(): void {
		check_ajax_referer( 'stagekitwp_import_export_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'stagekitwp-io' ), 403 );
		}

		$maps = isset( $_POST['remap'] ) ? (array) $_POST['remap'] : [];
		foreach ( $maps as $staging_key => $new_id ) {
			$staging_key = sanitize_key( $staging_key );
			$new_id      = (int) $new_id;
			if ( ! $staging_key || $new_id <= 0 ) {
				continue;
			}
			// Derive the real option key: stagekitwp_import_export_remap_{key} → {key}.
			$real_key = str_replace( 'stagekitwp_import_export_remap_', '', $staging_key );
			update_option( $real_key, $new_id );
			delete_option( $staging_key ); // Clear the staging key.
		}

		wp_send_json_success( [ 'message' => __( 'Page mappings saved.', 'stagekitwp-io' ) ] );
	}

	/**
	 * GET  admin-ajax.php  action=stagekitwp_import_export_import_progress&job_id=XYZ
	 */
	public function ajax_import_progress(): void {
		check_ajax_referer( 'stagekitwp_import_export_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'stagekitwp-io' ), 403 );
		}

		$job_id = isset( $_GET['job_id'] ) ? sanitize_key( $_GET['job_id'] ) : '';

		// Self-drive the queue: process one batch per poll so the import advances
		// reliably even when WP-Cron / Action Scheduler do not fire on their own
		// (common on local dev servers). The background runner, if it fires, just
		// shares the same offset state — no double-processing of the same posts.
		$progress = $this->quiet_query( fn() => $this->queue()->process_next_batch( $job_id ) );

		wp_send_json_success( $progress );
	}

	/**
	 * GET  admin-ajax.php  action=stagekitwp_import_export_csv_template
	 * Streams a blank CSV template file to the browser.
	 */
	public function ajax_csv_template(): void {
		check_ajax_referer( 'stagekitwp_import_export_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'stagekitwp-io' ), 403 );
		}
		require_once STAGEKITWP_IMPORT_EXPORT_DIR . 'includes/class-stagekitwp-import-export-csv-importer.php';
		STAGEKITWP_IMPORT_EXPORT_CSV_Importer::stream_template();
	}

	/**
	 * POST  admin-ajax.php  action=stagekitwp_import_export_purge
	 * scope = 'all' | plugin-group slug | individual CPT slug
	 * dry_run = '1' for count-only, '' for real delete
	 */
	public function ajax_purge(): void {
		check_ajax_referer( 'stagekitwp_import_export_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'stagekitwp-io' ), 'code' => 'forbidden' ] );
			return;
		}
		$scope   = isset( $_POST['scope'] )   ? sanitize_key( wp_unslash( $_POST['scope'] ) ) : '';
		$dry_run = ! empty( $_POST['dry_run'] );
		if ( '' === $scope ) {
			wp_send_json_error( [ 'message' => __( 'No scope provided.', 'stagekitwp-io' ), 'code' => 'no_scope' ] );
			return;
		}
		require_once STAGEKITWP_IMPORT_EXPORT_DIR . 'includes/class-stagekitwp-import-export-purger.php';
		$post_types = STAGEKITWP_IMPORT_EXPORT_Purger::resolve_scope( $scope );
		if ( is_wp_error( $post_types ) ) {
			wp_send_json_error( [ 'message' => $post_types->get_error_message(), 'code' => $post_types->get_error_code() ] );
			return;
		}
		try {
			$result = $this->quiet_query( fn() => STAGEKITWP_IMPORT_EXPORT_Purger::purge( $post_types, $dry_run ) );
		} catch ( \Throwable $e ) {
			error_log( 'TM IO purge error: ' . $e->getMessage() );
			wp_send_json_error( [ 'message' => __( 'Purge failed due to a server error.', 'stagekitwp-io' ), 'detail' => $e->getMessage(), 'code' => 'exception' ] );
			return;
		}
		wp_send_json_success( array_merge( $result, [ 'dry_run' => $dry_run, 'scope' => $scope ] ) );
	}

	/**
	 * POST  admin-ajax.php  action=stagekitwp_import_export_purge_batch
	 *
	 * Deletes ONE small batch of posts and returns progress so the browser can
	 * loop and render a progress bar. This keeps every request short and avoids
	 * the PHP execution timeout that a single full-ecosystem delete would hit.
	 *
	 * scope      = 'all' | plugin-group slug | individual CPT slug
	 * batch_size = optional int (defaults to the configured stagekitwp_import_export_batch_size,
	 *              clamped STAGEKITWP_IMPORT_EXPORT_BATCH_MIN–STAGEKITWP_IMPORT_EXPORT_BATCH_MAX)
	 */
	public function ajax_purge_batch(): void {
		check_ajax_referer( 'stagekitwp_import_export_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'stagekitwp-io' ), 'code' => 'forbidden' ] );
			return;
		}
		$scope = isset( $_POST['scope'] ) ? sanitize_key( wp_unslash( $_POST['scope'] ) ) : '';
		if ( '' === $scope ) {
			wp_send_json_error( [ 'message' => __( 'No scope provided.', 'stagekitwp-io' ), 'code' => 'no_scope' ] );
			return;
		}
		// Default to the configured batch size; an explicit request value still wins.
		$batch_size = isset( $_POST['batch_size'] ) ? absint( $_POST['batch_size'] ) : stagekitwp_import_export_batch_size();
		$batch_size = max( STAGEKITWP_IMPORT_EXPORT_BATCH_MIN, min( STAGEKITWP_IMPORT_EXPORT_BATCH_MAX, $batch_size ) );

		require_once STAGEKITWP_IMPORT_EXPORT_DIR . 'includes/class-stagekitwp-import-export-purger.php';
		$post_types = STAGEKITWP_IMPORT_EXPORT_Purger::resolve_scope( $scope );
		if ( is_wp_error( $post_types ) ) {
			wp_send_json_error( [ 'message' => $post_types->get_error_message(), 'code' => $post_types->get_error_code() ] );
			return;
		}
		try {
			$result = $this->quiet_query( fn() => STAGEKITWP_IMPORT_EXPORT_Purger::purge_batch( $post_types, $batch_size ) );
		} catch ( \Throwable $e ) {
			error_log( 'TM IO purge_batch error: ' . $e->getMessage() );
			wp_send_json_error( [ 'message' => __( 'Purge failed due to a server error.', 'stagekitwp-io' ), 'detail' => $e->getMessage(), 'code' => 'exception' ] );
			return;
		}
		wp_send_json_success( array_merge( $result, [ 'scope' => $scope ] ) );
	}

	/**
	 * POST  admin-ajax.php  action=stagekitwp_import_export_csv_rollback
	 */
	public function ajax_csv_rollback(): void {
		check_ajax_referer( 'stagekitwp_import_export_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'stagekitwp-io' ), 'code' => 'forbidden' ] );
			return;
		}
		$token = isset( $_POST['rollback_token'] ) ? sanitize_text_field( wp_unslash( $_POST['rollback_token'] ) ) : '';
		if ( ! $token || ! str_starts_with( $token, 'tmio_rb_' ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid or missing rollback token.', 'stagekitwp-io' ), 'code' => 'bad_token' ] );
			return;
		}
		require_once STAGEKITWP_IMPORT_EXPORT_DIR . 'includes/class-stagekitwp-import-export-csv-importer.php';
		try {
			$result = $this->quiet_query( fn() => ( new STAGEKITWP_IMPORT_EXPORT_CSV_Importer() )->rollback( $token ) );
		} catch ( \Throwable $e ) {
			error_log( 'TM IO rollback error: ' . $e->getMessage() );
			wp_send_json_error( [ 'message' => __( 'Rollback failed due to a server error.', 'stagekitwp-io' ), 'detail' => $e->getMessage(), 'code' => 'exception' ] );
			return;
		}
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message(), 'code' => $result->get_error_code() ] );
		} else {
			wp_send_json_success( $result );
		}
	}

	// ── WP-CLI ────────────────────────────────────────────────────────────────

	public function register_cli_commands(): void {
		require_once STAGEKITWP_IMPORT_EXPORT_DIR . 'includes/class-stagekitwp-import-export-cli.php';
		WP_CLI::add_command( 'stagekitwp-io', 'STAGEKITWP_IMPORT_EXPORT_CLI' );
	}

	// ── Activation / deactivation ─────────────────────────────────────────────

	public function activate(): void {
		// Create the tmp export directory inside uploads.
		$upload_dir = wp_upload_dir();
		$stagekitwp_import_export_dir  = trailingslashit( $upload_dir['basedir'] ) . 'stagekitwp-io';
		wp_mkdir_p( $stagekitwp_import_export_dir );
		// Drop a .htaccess so the raw files aren't browsable.
		file_put_contents( $stagekitwp_import_export_dir . '/.htaccess', 'deny from all' . PHP_EOL );

		$this->queue()->install_tables();
	}

	public function deactivate(): void {
		$this->queue()->clear_scheduled_hooks();
	}
}
