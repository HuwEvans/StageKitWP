<?php
namespace SKWPM;

defined( 'ABSPATH' ) || exit;

class Settings {
	public static function init(): void {
		add_action( 'admin_menu', [ __CLASS__, 'menu' ] );
		add_action( 'admin_init', [ __CLASS__, 'register' ] );
	}

	/**
	 * Nest Settings under the Gallery CPT's own top-level menu (rather than
	 * WP Settings) so the whole plugin lives under one "StageKit Media" entry.
	 */
	public static function menu(): void {
		add_submenu_page(
			'edit.php?post_type=skwpm_gallery',
			__( 'StageKit Media Settings', 'stagekitwp-media' ),
			__( 'Settings', 'stagekitwp-media' ),
			'manage_options',
			'skwpm-settings',
			[ __CLASS__, 'page' ]
		);
	}

	public static function register(): void {
		// API Key Providers
		register_setting( 'skwpm_settings', 'skwpm_pexels_key', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( 'skwpm_settings', 'skwpm_unsplash_key', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( 'skwpm_settings', 'skwpm_youtube_key', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( 'skwpm_settings', 'skwpm_vimeo_token', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( 'skwpm_settings', 'skwpm_flickr_key', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( 'skwpm_settings', 'skwpm_dropbox_client_id', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( 'skwpm_settings', 'skwpm_dropbox_client_secret', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( 'skwpm_settings', 'skwpm_gdrive_client_id', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( 'skwpm_settings', 'skwpm_gdrive_client_secret', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( 'skwpm_settings', 'skwpm_gphotos_client_id', [ 'sanitize_callback' => 'sanitize_text_field' ] );
		register_setting( 'skwpm_settings', 'skwpm_gphotos_client_secret', [ 'sanitize_callback' => 'sanitize_text_field' ] );

		// Folder Defaults
		register_setting( 'skwpm_settings', 'skwpm_default_gdrive_folder', [ 'sanitize_callback' => 'absint' ] );
		register_setting( 'skwpm_settings', 'skwpm_default_gphotos_folder', [ 'sanitize_callback' => 'absint' ] );
		register_setting( 'skwpm_settings', 'skwpm_default_dropbox_folder', [ 'sanitize_callback' => 'absint' ] );
		register_setting( 'skwpm_settings', 'skwpm_default_upload_folder', [ 'sanitize_callback' => 'absint' ] );
	}

	public static function page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$current_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'providers';
		self::render_oauth_notice();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'StageKit Media Settings', 'stagekitwp-media' ); ?></h1>

			<nav class="nav-tab-wrapper" style="margin-bottom: 20px;">
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=skwpm_gallery&page=skwpm-settings&tab=providers' ) ); ?>" class="nav-tab <?php echo 'providers' === $current_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Media Providers & OAuth', 'stagekitwp-media' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=skwpm_gallery&page=skwpm-settings&tab=folders' ) ); ?>" class="nav-tab <?php echo 'folders' === $current_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Default Destination Folders', 'stagekitwp-media' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=skwpm_gallery&page=skwpm-settings&tab=diagnostics' ) ); ?>" class="nav-tab <?php echo 'diagnostics' === $current_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Storage Health & Diagnostics', 'stagekitwp-media' ); ?>
				</a>
			</nav>

			<?php if ( 'providers' === $current_tab ) : ?>
				<form method="post" action="options.php">
					<?php settings_fields( 'skwpm_settings' ); ?>
					<h2><?php esc_html_e( 'API Key Providers', 'stagekitwp-media' ); ?></h2>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="skwpm_pexels_key"><?php esc_html_e( 'Pexels API Key', 'stagekitwp-media' ); ?></label></th>
							<td>
								<input type="password" id="skwpm_pexels_key" name="skwpm_pexels_key" value="<?php echo esc_attr( get_option( 'skwpm_pexels_key' ) ); ?>" class="regular-text" autocomplete="off">
								<p class="description"><a href="https://www.pexels.com/api/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Get a free Pexels API key', 'stagekitwp-media' ); ?></a></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="skwpm_unsplash_key"><?php esc_html_e( 'Unsplash Access Key', 'stagekitwp-media' ); ?></label></th>
							<td>
								<input type="password" id="skwpm_unsplash_key" name="skwpm_unsplash_key" value="<?php echo esc_attr( get_option( 'skwpm_unsplash_key' ) ); ?>" class="regular-text" autocomplete="off">
								<p class="description"><a href="https://unsplash.com/developers" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Get a free Unsplash Access Key', 'stagekitwp-media' ); ?></a></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="skwpm_flickr_key"><?php esc_html_e( 'Flickr API Key', 'stagekitwp-media' ); ?></label></th>
							<td>
								<input type="password" id="skwpm_flickr_key" name="skwpm_flickr_key" value="<?php echo esc_attr( get_option( 'skwpm_flickr_key' ) ); ?>" class="regular-text" autocomplete="off">
								<p class="description"><a href="https://www.flickr.com/services/apps/create/apply" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Get a free Flickr API key', 'stagekitwp-media' ); ?></a></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="skwpm_youtube_key"><?php esc_html_e( 'YouTube Data API Key', 'stagekitwp-media' ); ?></label></th>
							<td>
								<input type="password" id="skwpm_youtube_key" name="skwpm_youtube_key" value="<?php echo esc_attr( get_option( 'skwpm_youtube_key' ) ); ?>" class="regular-text" autocomplete="off">
								<p class="description"><a href="https://console.cloud.google.com/apis/library/youtube.googleapis.com" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Enable the YouTube Data API v3 and create a key', 'stagekitwp-media' ); ?></a></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="skwpm_vimeo_token"><?php esc_html_e( 'Vimeo Access Token', 'stagekitwp-media' ); ?></label></th>
							<td>
								<input type="password" id="skwpm_vimeo_token" name="skwpm_vimeo_token" value="<?php echo esc_attr( get_option( 'skwpm_vimeo_token' ) ); ?>" class="regular-text" autocomplete="off">
								<p class="description"><a href="https://developer.vimeo.com/apps" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Create a Vimeo app and generate a Personal Access Token (public + private scopes)', 'stagekitwp-media' ); ?></a></p>
							</td>
						</tr>
					</table>

					<h2><?php esc_html_e( 'Connected Accounts', 'stagekitwp-media' ); ?></h2>
					<p class="description">
						<?php
						printf(
							/* translators: %s: redirect URI to paste into the provider's developer console */
							esc_html__( 'Dropbox, Google Drive, and Google Photos require a full account sign-in rather than just a key. Create an app in each provider\'s developer console, then register this exact Redirect URI: %s', 'stagekitwp-media' ),
							'<code>' . esc_html( OAuth::redirect_uri() ) . '</code>'
						);
						?>
					</p>
					<table class="form-table" role="presentation">
						<?php self::oauth_provider_row( 'dropbox', __( 'Dropbox', 'stagekitwp-media' ), 'skwpm_dropbox_client_id', 'skwpm_dropbox_client_secret', 'https://www.dropbox.com/developers/apps' ); ?>
						<?php self::oauth_provider_row( 'google-drive', __( 'Google Drive', 'stagekitwp-media' ), 'skwpm_gdrive_client_id', 'skwpm_gdrive_client_secret', 'https://console.cloud.google.com/apis/credentials' ); ?>
						<?php self::oauth_provider_row( 'google-photos', __( 'Google Photos', 'stagekitwp-media' ), 'skwpm_gphotos_client_id', 'skwpm_gphotos_client_secret', 'https://console.cloud.google.com/apis/credentials' ); ?>
					</table>

					<?php submit_button( __( 'Save Credentials', 'stagekitwp-media' ) ); ?>
				</form>

			<?php elseif ( 'folders' === $current_tab ) : ?>
				<form method="post" action="options.php">
					<?php settings_fields( 'skwpm_settings' ); ?>
					<h2><?php esc_html_e( 'Default Sync & Import Destination Folders', 'stagekitwp-media' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Choose which StageKit Media folder new imports and synced assets should be placed in automatically.', 'stagekitwp-media' ); ?></p>
					
					<?php $folder_tree = FolderRepository::get_tree(); ?>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="skwpm_default_gdrive_folder"><?php esc_html_e( 'Google Drive Imports', 'stagekitwp-media' ); ?></label></th>
							<td>
								<select id="skwpm_default_gdrive_folder" name="skwpm_default_gdrive_folder" class="regular-text">
									<option value="0"><?php esc_html_e( '— Auto-create / Provider Folder —', 'stagekitwp-media' ); ?></option>
									<?php self::render_folder_select_options( $folder_tree, (int) get_option( 'skwpm_default_gdrive_folder' ) ); ?>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="skwpm_default_gphotos_folder"><?php esc_html_e( 'Google Photos Imports', 'stagekitwp-media' ); ?></label></th>
							<td>
								<select id="skwpm_default_gphotos_folder" name="skwpm_default_gphotos_folder" class="regular-text">
									<option value="0"><?php esc_html_e( '— Auto-create / Provider Folder —', 'stagekitwp-media' ); ?></option>
									<?php self::render_folder_select_options( $folder_tree, (int) get_option( 'skwpm_default_gphotos_folder' ) ); ?>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="skwpm_default_dropbox_folder"><?php esc_html_e( 'Dropbox Imports', 'stagekitwp-media' ); ?></label></th>
							<td>
								<select id="skwpm_default_dropbox_folder" name="skwpm_default_dropbox_folder" class="regular-text">
									<option value="0"><?php esc_html_e( '— Auto-create / Provider Folder —', 'stagekitwp-media' ); ?></option>
									<?php self::render_folder_select_options( $folder_tree, (int) get_option( 'skwpm_default_dropbox_folder' ) ); ?>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="skwpm_default_upload_folder"><?php esc_html_e( 'Direct Uploads Default', 'stagekitwp-media' ); ?></label></th>
							<td>
								<select id="skwpm_default_upload_folder" name="skwpm_default_upload_folder" class="regular-text">
									<option value="0"><?php esc_html_e( '— Root Folder (Home) —', 'stagekitwp-media' ); ?></option>
									<?php self::render_folder_select_options( $folder_tree, (int) get_option( 'skwpm_default_upload_folder' ) ); ?>
								</select>
							</td>
						</tr>
					</table>

					<?php submit_button( __( 'Save Folder Settings', 'stagekitwp-media' ) ); ?>
				</form>

			<?php elseif ( 'diagnostics' === $current_tab ) : ?>
				<?php $stats = Diagnostics::get_storage_stats(); ?>
				<h2><?php esc_html_e( 'Storage & Health Overview', 'stagekitwp-media' ); ?></h2>

				<table class="widefat striped" style="max-width: 800px; margin-top: 15px;">
					<tbody>
						<tr>
							<td><strong><?php esc_html_e( 'Storage Directory Path', 'stagekitwp-media' ); ?></strong></td>
							<td><code><?php echo esc_html( $stats['storage_dir'] ); ?></code></td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'Directory Status', 'stagekitwp-media' ); ?></strong></td>
							<td>
								<?php if ( $stats['storage_writable'] ) : ?>
									<span style="color:#008a20;font-weight:600;">&#10003; <?php esc_html_e( 'Exists and Writable', 'stagekitwp-media' ); ?></span>
								<?php else : ?>
									<span style="color:#d63638;font-weight:600;">&#9888; <?php esc_html_e( 'Directory is not writable or missing', 'stagekitwp-media' ); ?></span>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'Total Disk Usage', 'stagekitwp-media' ); ?></strong></td>
							<td><?php echo esc_html( $stats['physical_size_fmt'] ); ?> (<?php echo esc_html( number_format_i18n( $stats['physical_files'] ) ); ?> <?php esc_html_e( 'files on disk including thumbnails', 'stagekitwp-media' ); ?>)</td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'Registered Media Records', 'stagekitwp-media' ); ?></strong></td>
							<td><?php echo esc_html( number_format_i18n( $stats['total_records'] ) ); ?></td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'Total Folders', 'stagekitwp-media' ); ?></strong></td>
							<td><?php echo esc_html( number_format_i18n( $stats['total_folders'] ) ); ?></td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'Media Types Breakdown', 'stagekitwp-media' ); ?></strong></td>
							<td>
								<?php esc_html_e( 'Images:', 'stagekitwp-media' ); ?> <?php echo esc_html( $stats['types_breakdown']['images'] ); ?> &bull;
								<?php esc_html_e( 'Videos:', 'stagekitwp-media' ); ?> <?php echo esc_html( $stats['types_breakdown']['videos'] ); ?> &bull;
								<?php esc_html_e( 'Documents:', 'stagekitwp-media' ); ?> <?php echo esc_html( $stats['types_breakdown']['documents'] ); ?>
							</td>
						</tr>
					</tbody>
				</table>

				<h2 style="margin-top: 30px;"><?php esc_html_e( 'Maintenance & Diagnostics Tools', 'stagekitwp-media' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Run automated integrity checks and disk maintenance tools.', 'stagekitwp-media' ); ?></p>

				<div style="display:flex; flex-direction:column; gap:15px; max-width:800px; margin-top:15px;">
					<div class="card" style="margin:0; padding:15px;">
						<h3><?php esc_html_e( 'Orphaned Files Cleanup', 'stagekitwp-media' ); ?></h3>
						<p><?php esc_html_e( 'Scans the stagekit-media directory for files that have no database record.', 'stagekitwp-media' ); ?></p>
						<button type="button" class="button" id="skwpm-diag-scan-orphans"><?php esc_html_e( 'Scan for Orphaned Files', 'stagekitwp-media' ); ?></button>
						<button type="button" class="button button-link-delete" id="skwpm-diag-clean-orphans" style="display:none;"><?php esc_html_e( 'Delete Orphaned Files', 'stagekitwp-media' ); ?></button>
						<div id="skwpm-diag-orphans-result" style="margin-top:10px;"></div>
					</div>

					<div class="card" style="margin:0; padding:15px;">
						<h3><?php esc_html_e( 'Missing Physical Files Check', 'stagekitwp-media' ); ?></h3>
						<p><?php esc_html_e( 'Finds database records whose actual files have been deleted or moved from disk.', 'stagekitwp-media' ); ?></p>
						<button type="button" class="button" id="skwpm-diag-scan-missing"><?php esc_html_e( 'Scan for Missing Files', 'stagekitwp-media' ); ?></button>
						<button type="button" class="button button-link-delete" id="skwpm-diag-clean-missing" style="display:none;"><?php esc_html_e( 'Clean Missing DB Records', 'stagekitwp-media' ); ?></button>
						<div id="skwpm-diag-missing-result" style="margin-top:10px;"></div>
					</div>

					<div class="card" style="margin:0; padding:15px;">
						<h3><?php esc_html_e( 'Regenerate Thumbnails', 'stagekitwp-media' ); ?></h3>
						<p><?php esc_html_e( 'Re-generate responsive thumbnail sizes (thumb, medium, large) for all StageKit Media image assets.', 'stagekitwp-media' ); ?></p>
						<button type="button" class="button button-primary" id="skwpm-diag-regen-thumbs"><?php esc_html_e( 'Regenerate All Thumbnails', 'stagekitwp-media' ); ?></button>
						<div id="skwpm-diag-thumbs-result" style="margin-top:10px;"></div>
					</div>
				</div>

				<script>
				jQuery(function($) {
					var restUrl = '<?php echo esc_url_raw( rest_url( 'stagekit-media/v1' ) ); ?>';
					var nonce   = '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>';

					function postApi(endpoint) {
						return $.ajax({
							url: restUrl + '/' + endpoint,
							method: 'POST',
							headers: { 'X-WP-Nonce': nonce }
						});
					}

					$('#skwpm-diag-scan-orphans').on('click', function() {
						var $btn = $(this).prop('disabled', true).text('Scanning...');
						$.ajax({
							url: restUrl + '/diagnostics/scan-orphans',
							method: 'GET',
							headers: { 'X-WP-Nonce': nonce }
						}).done(function(res) {
							$btn.prop('disabled', false).text('Scan for Orphaned Files');
							if (res && res.success) {
								var orphans = res.orphans || [];
								if (orphans.length > 0) {
									$('#skwpm-diag-orphans-result').html('<span style="color:#d63638;font-weight:600;">Found ' + orphans.length + ' orphaned files on disk.</span>');
									$('#skwpm-diag-clean-orphans').show();
								} else {
									$('#skwpm-diag-orphans-result').html('<span style="color:#008a20;font-weight:600;">&#10003; No orphaned files found on disk.</span>');
									$('#skwpm-diag-clean-orphans').hide();
								}
							}
						});
					});

					$('#skwpm-diag-clean-orphans').on('click', function() {
						if (!confirm('Are you sure you want to permanently delete orphaned files?')) return;
						var $btn = $(this).prop('disabled', true).text('Cleaning...');
						postApi('diagnostics/cleanup-orphans').done(function(res) {
							$btn.prop('disabled', false).hide();
							$('#skwpm-diag-orphans-result').html('<span style="color:#008a20;font-weight:600;">Cleaned ' + (res.deleted || 0) + ' orphaned files.</span>');
						});
					});

					$('#skwpm-diag-scan-missing').on('click', function() {
						var $btn = $(this).prop('disabled', true).text('Scanning...');
						$.ajax({
							url: restUrl + '/diagnostics/scan-missing',
							method: 'GET',
							headers: { 'X-WP-Nonce': nonce }
						}).done(function(res) {
							$btn.prop('disabled', false).text('Scan for Missing Files');
							if (res && res.success) {
								var missing = res.missing || [];
								if (missing.length > 0) {
									$('#skwpm-diag-missing-result').html('<span style="color:#d63638;font-weight:600;">Found ' + missing.length + ' missing records.</span>');
									$('#skwpm-diag-clean-missing').show();
								} else {
									$('#skwpm-diag-missing-result').html('<span style="color:#008a20;font-weight:600;">&#10003; All database records have valid files on disk.</span>');
									$('#skwpm-diag-clean-missing').hide();
								}
							}
						});
					});

					$('#skwpm-diag-clean-missing').on('click', function() {
						if (!confirm('Clean missing database records?')) return;
						var $btn = $(this).prop('disabled', true).text('Cleaning...');
						postApi('diagnostics/cleanup-missing').done(function(res) {
							$btn.prop('disabled', false).hide();
							$('#skwpm-diag-missing-result').html('<span style="color:#008a20;font-weight:600;">Cleaned ' + (res.cleaned || 0) + ' records.</span>');
						});
					});

					$('#skwpm-diag-regen-thumbs').on('click', function() {
						var $btn = $(this).prop('disabled', true).text('Regenerating thumbnails...');
						postApi('diagnostics/regenerate-thumbs').done(function(res) {
							$btn.prop('disabled', false).text('Regenerate All Thumbnails');
							if (res && res.success) {
								$('#skwpm-diag-thumbs-result').html('<span style="color:#008a20;font-weight:600;">Successfully processed ' + res.result.total + ' images (' + res.result.success + ' updated).</span>');
							}
						});
					});
				});
				</script>
			<?php endif; ?>
		</div>
		<?php
	}

	private static function render_folder_select_options( array $nodes, int $selected_id, int $depth = 0 ): void {
		$prefix = str_repeat( '— ', $depth );
		foreach ( $nodes as $node ) {
			printf(
				'<option value="%d" %s>%s%s</option>',
				esc_attr( $node['id'] ),
				selected( $node['id'], $selected_id, false ),
				esc_html( $prefix ),
				esc_html( $node['name'] )
			);
			if ( ! empty( $node['children'] ) ) {
				self::render_folder_select_options( $node['children'], $selected_id, $depth + 1 );
			}
		}
	}

	private static function oauth_provider_row( string $slug, string $label, string $client_id_option, string $client_secret_option, string $console_url ): void {
		$connected = OAuth::is_connected( $slug );
		?>
		<tr>
			<th scope="row"><?php echo esc_html( $label ); ?></th>
			<td>
				<p>
					<label for="<?php echo esc_attr( $client_id_option ); ?>"><?php esc_html_e( 'Client ID', 'stagekitwp-media' ); ?></label><br>
					<input type="text" id="<?php echo esc_attr( $client_id_option ); ?>" name="<?php echo esc_attr( $client_id_option ); ?>" value="<?php echo esc_attr( get_option( $client_id_option ) ); ?>" class="regular-text" autocomplete="off">
				</p>
				<p>
					<label for="<?php echo esc_attr( $client_secret_option ); ?>"><?php esc_html_e( 'Client Secret', 'stagekitwp-media' ); ?></label><br>
					<input type="password" id="<?php echo esc_attr( $client_secret_option ); ?>" name="<?php echo esc_attr( $client_secret_option ); ?>" value="<?php echo esc_attr( get_option( $client_secret_option ) ); ?>" class="regular-text" autocomplete="off">
				</p>
				<p class="description"><a href="<?php echo esc_url( $console_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open developer console', 'stagekitwp-media' ); ?></a></p>
				<p>
					<?php if ( $connected ) : ?>
						<span style="color:#008a20;font-weight:600;">&#10003; <?php esc_html_e( 'Connected', 'stagekitwp-media' ); ?></span>
						&nbsp;
						<a class="button" href="<?php echo esc_url( OAuth::disconnect_url( $slug ) ); ?>"><?php esc_html_e( 'Disconnect', 'stagekitwp-media' ); ?></a>
					<?php else : ?>
						<span style="color:#646970;"><?php esc_html_e( 'Not connected', 'stagekitwp-media' ); ?></span>
						&nbsp;
						<a class="button button-primary" href="<?php echo esc_url( OAuth::connect_url( $slug ) ); ?>"><?php
							/* translators: %s: provider name, e.g. "Dropbox" */
							printf( esc_html__( 'Connect %s', 'stagekitwp-media' ), esc_html( $label ) );
						?></a>
						<p class="description"><?php esc_html_e( 'Save the Client ID/Secret above first, then connect.', 'stagekitwp-media' ); ?></p>
					<?php endif; ?>
				</p>
			</td>
		</tr>
		<?php
	}

	private static function render_oauth_notice(): void {
		$status   = isset( $_GET['skwpm_oauth'] ) ? sanitize_key( wp_unslash( $_GET['skwpm_oauth'] ) ) : '';
		$provider = isset( $_GET['skwpm_provider'] ) ? sanitize_key( wp_unslash( $_GET['skwpm_provider'] ) ) : '';
		if ( '' === $status ) {
			return;
		}

		$labels = [ 'dropbox' => 'Dropbox', 'google-drive' => 'Google Drive', 'google-photos' => 'Google Photos' ];
		$name   = $labels[ $provider ] ?? $provider;

		$messages = [
			'connected'            => [ 'success', sprintf( /* translators: %s: provider name */ __( '%s connected successfully.', 'stagekitwp-media' ), $name ) ],
			'disconnected'         => [ 'info', sprintf( /* translators: %s: provider name */ __( '%s disconnected.', 'stagekitwp-media' ), $name ) ],
			'error'                => [ 'error', sprintf( /* translators: %s: provider name */ __( 'Could not connect %s. Double-check the Client ID/Secret and Redirect URI, then try again.', 'stagekitwp-media' ), $name ) ],
			'missing_credentials'  => [ 'error', sprintf( /* translators: %s: provider name */ __( 'Save a Client ID and Client Secret for %s before connecting.', 'stagekitwp-media' ), $name ) ],
		];

		if ( ! isset( $messages[ $status ] ) ) {
			return;
		}

		[ $type, $text ] = $messages[ $status ];
		printf(
			'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
			esc_attr( $type ),
			esc_html( $text )
		);
	}
}
