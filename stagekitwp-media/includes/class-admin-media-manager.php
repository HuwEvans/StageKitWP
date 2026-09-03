<?php
namespace SKWPM;

defined( 'ABSPATH' ) || exit;

/**
 * Admin UI Controller for the dedicated StageKit Media Manager workspace.
 */
class AdminMediaManager {

	public const PAGE_SLUG = 'skwpm-media';

	public static function init(): void {
		add_action( 'admin_menu', [ __CLASS__, 'menu' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'assets' ] );
	}

	/**
	 * Register submenu page under StageKit Media.
	 */
	public static function menu(): void {
		add_submenu_page(
			'edit.php?post_type=skwpm_gallery',
			__( 'StageKit Media Library', 'stagekitwp-media' ),
			__( 'Media Library', 'stagekitwp-media' ),
			'upload_files',
			self::PAGE_SLUG,
			[ __CLASS__, 'render_page' ]
		);
	}

	/**
	 * Enqueue styles and scripts for the Media Manager admin page.
	 */
	public static function assets( string $hook ): void {
		// Only enqueue on our specific admin page
		if ( 'skwpm_gallery_page_' . self::PAGE_SLUG !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'skwpm-admin-media-manager',
			SKWPM_URL . 'assets/css/admin-media-manager.css',
			[ 'dashicons' ],
			SKWPM_VERSION
		);

		wp_enqueue_script(
			'skwpm-admin-media-manager',
			SKWPM_URL . 'assets/js/admin-media-manager.js',
			[ 'jquery' ],
			SKWPM_VERSION,
			true
		);

		wp_localize_script( 'skwpm-admin-media-manager', 'skwpmMediaManager', [
			'restUrl'       => rest_url( 'stagekit-media/v1' ),
			'nonce'         => wp_create_nonce( 'wp_rest' ),
			'maxUploadSize' => wp_max_upload_size(),
			'i18n'          => [
				'allFiles'        => __( 'All Files', 'stagekitwp-media' ),
				'rootFolder'      => __( 'Home / Root', 'stagekitwp-media' ),
				'newFolder'       => __( 'New Folder', 'stagekitwp-media' ),
				'renameFolder'    => __( 'Rename Folder', 'stagekitwp-media' ),
				'deleteFolder'    => __( 'Delete Folder', 'stagekitwp-media' ),
				'deleteConfirm'   => __( 'Are you sure you want to delete this item? This action cannot be undone.', 'stagekitwp-media' ),
				'deleteFolderMsg' => __( 'Are you sure you want to delete this folder? Items inside will be moved to the parent folder.', 'stagekitwp-media' ),
				'uploading'       => __( 'Uploading...', 'stagekitwp-media' ),
				'uploadSuccess'   => __( 'Upload complete.', 'stagekitwp-media' ),
				'uploadError'     => __( 'Upload failed.', 'stagekitwp-media' ),
				'noItems'         => __( 'No media items found in this folder.', 'stagekitwp-media' ),
				'dropToUpload'    => __( 'Drop files anywhere to upload', 'stagekitwp-media' ),
				'itemsSelected'   => __( 'selected', 'stagekitwp-media' ),
				'moveToFolder'    => __( 'Move to Folder', 'stagekitwp-media' ),
				'save'            => __( 'Save Changes', 'stagekitwp-media' ),
				'saved'           => __( 'Saved!', 'stagekitwp-media' ),
				'copyLink'        => __( 'Copy URL', 'stagekitwp-media' ),
				'copied'          => __( 'Copied to clipboard!', 'stagekitwp-media' ),
				'selectTarget'    => __( 'Select destination folder:', 'stagekitwp-media' ),
				'cancel'          => __( 'Cancel', 'stagekitwp-media' ),
				'confirm'         => __( 'Confirm', 'stagekitwp-media' ),
			],
		] );
	}

	/**
	 * Render the full Media Manager single-page application workspace.
	 */
	public static function render_page(): void {
		if ( ! current_user_can( 'upload_files' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'stagekitwp-media' ) );
		}
		?>
		<div class="wrap skwpm-manager-wrap">
			<!-- Header Toolbar -->
			<div class="skwpm-manager-header">
				<div class="skwpm-header-left">
					<h1 class="skwpm-title">
						<span class="dashicons dashicons-format-gallery"></span>
						<?php esc_html_e( 'StageKit Media Library', 'stagekitwp-media' ); ?>
					</h1>
				</div>
				<div class="skwpm-header-right">
					<button type="button" class="button button-primary skwpm-btn-upload" id="skwpm-btn-upload">
						<span class="dashicons dashicons-upload"></span> <?php esc_html_e( 'Upload Files', 'stagekitwp-media' ); ?>
					</button>
					<button type="button" class="button skwpm-btn-new-folder" id="skwpm-btn-new-folder">
						<span class="dashicons dashicons-category"></span> <?php esc_html_e( 'New Folder', 'stagekitwp-media' ); ?>
					</button>
					<input type="file" id="skwpm-file-input" multiple style="display:none;" />
				</div>
			</div>

			<!-- Main Application Layout -->
			<div class="skwpm-manager-body" id="skwpm-manager-app">
				<!-- Left Sidebar: Folders & Filters -->
				<aside class="skwpm-sidebar">
					<div class="skwpm-sidebar-section">
						<h3 class="skwpm-sidebar-heading"><?php esc_html_e( 'Library', 'stagekitwp-media' ); ?></h3>
						<ul class="skwpm-nav-list" id="skwpm-quick-filters">
							<li class="skwpm-nav-item active" data-filter="all">
								<a href="#"><span class="dashicons dashicons-admin-media"></span> <?php esc_html_e( 'All Files', 'stagekitwp-media' ); ?></a>
							</li>
							<li class="skwpm-nav-item" data-filter="images">
								<a href="#"><span class="dashicons dashicons-format-image"></span> <?php esc_html_e( 'Images', 'stagekitwp-media' ); ?></a>
							</li>
							<li class="skwpm-nav-item" data-filter="videos">
								<a href="#"><span class="dashicons dashicons-format-video"></span> <?php esc_html_e( 'Videos', 'stagekitwp-media' ); ?></a>
							</li>
							<li class="skwpm-nav-item" data-filter="documents">
								<a href="#"><span class="dashicons dashicons-media-document"></span> <?php esc_html_e( 'Documents', 'stagekitwp-media' ); ?></a>
							</li>
							<li class="skwpm-nav-item" data-filter="synced">
								<a href="#"><span class="dashicons dashicons-cloud"></span> <?php esc_html_e( 'Synced Media', 'stagekitwp-media' ); ?></a>
							</li>
						</ul>
					</div>

					<div class="skwpm-sidebar-section skwpm-folders-section">
						<div class="skwpm-section-header">
							<h3 class="skwpm-sidebar-heading"><?php esc_html_e( 'Folders', 'stagekitwp-media' ); ?></h3>
							<button type="button" class="skwpm-icon-btn" id="skwpm-add-folder-icon" title="<?php esc_attr_e( 'Create Folder', 'stagekitwp-media' ); ?>">
								<span class="dashicons dashicons-plus-alt2"></span>
							</button>
						</div>
						<div class="skwpm-folder-tree-container" id="skwpm-folder-tree">
							<!-- Populated dynamically via JS -->
						</div>
					</div>
				</aside>

				<!-- Main Content Area -->
				<main class="skwpm-main-content">
					<!-- Top Navigation & Controls Bar -->
					<div class="skwpm-controls-bar">
						<div class="skwpm-breadcrumbs" id="skwpm-breadcrumbs">
							<span class="skwpm-crumb active" data-folder-id="0"><?php esc_html_e( 'All Files', 'stagekitwp-media' ); ?></span>
						</div>

						<div class="skwpm-filters-group">
							<div class="skwpm-search-box">
								<span class="dashicons dashicons-search"></span>
								<input type="search" id="skwpm-search" placeholder="<?php esc_attr_e( 'Search files...', 'stagekitwp-media' ); ?>" />
							</div>

							<select id="skwpm-sort-by" class="skwpm-select">
								<option value="created_at-DESC"><?php esc_html_e( 'Newest First', 'stagekitwp-media' ); ?></option>
								<option value="created_at-ASC"><?php esc_html_e( 'Oldest First', 'stagekitwp-media' ); ?></option>
								<option value="title-ASC"><?php esc_html_e( 'Name (A-Z)', 'stagekitwp-media' ); ?></option>
								<option value="file_size-DESC"><?php esc_html_e( 'Size (Largest)', 'stagekitwp-media' ); ?></option>
							</select>

							<div class="skwpm-view-switcher">
								<button type="button" class="skwpm-view-btn active" data-view="grid" title="<?php esc_attr_e( 'Grid View', 'stagekitwp-media' ); ?>">
									<span class="dashicons dashicons-grid-view"></span>
								</button>
								<button type="button" class="skwpm-view-btn" data-view="list" title="<?php esc_attr_e( 'List View', 'stagekitwp-media' ); ?>">
									<span class="dashicons dashicons-list-view"></span>
								</button>
							</div>
						</div>
					</div>

					<!-- Bulk Actions Floating Bar -->
					<div class="skwpm-bulk-bar" id="skwpm-bulk-bar" style="display:none;">
						<div class="skwpm-bulk-info">
							<input type="checkbox" id="skwpm-select-all" />
							<span id="skwpm-bulk-count">0 items selected</span>
						</div>
						<div class="skwpm-bulk-actions">
							<button type="button" class="button" id="skwpm-bulk-move-btn">
								<span class="dashicons dashicons-category"></span> <?php esc_html_e( 'Move to Folder', 'stagekitwp-media' ); ?>
							</button>
							<button type="button" class="button button-link-delete" id="skwpm-bulk-delete-btn">
								<span class="dashicons dashicons-trash"></span> <?php esc_html_e( 'Delete Selected', 'stagekitwp-media' ); ?>
							</button>
							<button type="button" class="button-link" id="skwpm-bulk-clear-btn">
								<?php esc_html_e( 'Deselect', 'stagekitwp-media' ); ?>
							</button>
						</div>
					</div>

					<!-- Dropzone and File Grid Container -->
					<div class="skwpm-view-viewport" id="skwpm-dropzone">
						<div class="skwpm-dropzone-overlay" id="skwpm-drop-overlay">
							<div class="skwpm-drop-message">
								<span class="dashicons dashicons-upload"></span>
								<h2><?php esc_html_e( 'Drop files here to upload', 'stagekitwp-media' ); ?></h2>
							</div>
						</div>

						<!-- Upload Progress Bar Container -->
						<div class="skwpm-upload-progress-list" id="skwpm-upload-progress-list"></div>

						<!-- Media Items Container (Grid or List) -->
						<div class="skwpm-items-container skwpm-items-grid" id="skwpm-items-container">
							<!-- Dynamically populated -->
						</div>

						<!-- Empty State -->
						<div class="skwpm-empty-state" id="skwpm-empty-state" style="display:none;">
							<span class="dashicons dashicons-format-gallery"></span>
							<h3><?php esc_html_e( 'No media files here yet', 'stagekitwp-media' ); ?></h3>
							<p><?php esc_html_e( 'Drag and drop files into this folder or click Upload Files above.', 'stagekitwp-media' ); ?></p>
						</div>

						<!-- Pagination Container -->
						<div class="skwpm-pagination" id="skwpm-pagination" style="display:none;"></div>
					</div>
				</main>

				<!-- Right Inspector Drawer (File Details & Edit) -->
				<aside class="skwpm-inspector" id="skwpm-inspector" style="display:none;">
					<div class="skwpm-inspector-header">
						<h3><?php esc_html_e( 'File Details', 'stagekitwp-media' ); ?></h3>
						<button type="button" class="skwpm-icon-btn" id="skwpm-close-inspector">
							<span class="dashicons dashicons-no-alt"></span>
						</button>
					</div>
					<div class="skwpm-inspector-content" id="skwpm-inspector-content">
						<!-- Dynamically populated when an item is selected -->
					</div>
				</aside>
			</div>
		</div>

		<!-- Modal: Create / Rename Folder -->
		<div class="skwpm-modal-backdrop" id="skwpm-folder-modal" style="display:none;">
			<div class="skwpm-modal-dialog">
				<div class="skwpm-modal-header">
					<h3 id="skwpm-modal-title"><?php esc_html_e( 'New Folder', 'stagekitwp-media' ); ?></h3>
					<button type="button" class="skwpm-modal-close"><span class="dashicons dashicons-no-alt"></span></button>
				</div>
				<div class="skwpm-modal-body">
					<p>
						<label for="skwpm-folder-name-input"><strong><?php esc_html_e( 'Folder Name', 'stagekitwp-media' ); ?></strong></label>
						<input type="text" id="skwpm-folder-name-input" class="widefat" placeholder="<?php esc_attr_e( 'e.g. Production 2026', 'stagekitwp-media' ); ?>" />
					</p>
					<p>
						<label for="skwpm-folder-parent-select"><strong><?php esc_html_e( 'Parent Folder', 'stagekitwp-media' ); ?></strong></label>
						<select id="skwpm-folder-parent-select" class="widefat"></select>
					</p>
					<p>
						<label><strong><?php esc_html_e( 'Folder Color (Optional)', 'stagekitwp-media' ); ?></strong></label>
						<div class="skwpm-color-palette" id="skwpm-color-palette">
							<span class="skwpm-color-chip active" data-color=""></span>
							<span class="skwpm-color-chip" data-color="#2271b1" style="background:#2271b1;"></span>
							<span class="skwpm-color-chip" data-color="#008a20" style="background:#008a20;"></span>
							<span class="skwpm-color-chip" data-color="#d63638" style="background:#d63638;"></span>
							<span class="skwpm-color-chip" data-color="#e27c00" style="background:#e27c00;"></span>
							<span class="skwpm-color-chip" data-color="#8c5bdf" style="background:#8c5bdf;"></span>
							<span class="skwpm-color-chip" data-color="#135e96" style="background:#135e96;"></span>
						</div>
					</p>
				</div>
				<div class="skwpm-modal-footer">
					<button type="button" class="button skwpm-modal-cancel"><?php esc_html_e( 'Cancel', 'stagekitwp-media' ); ?></button>
					<button type="button" class="button button-primary" id="skwpm-modal-save-folder"><?php esc_html_e( 'Save Folder', 'stagekitwp-media' ); ?></button>
				</div>
			</div>
		</div>

		<!-- Modal: Move Items -->
		<div class="skwpm-modal-backdrop" id="skwpm-move-modal" style="display:none;">
			<div class="skwpm-modal-dialog">
				<div class="skwpm-modal-header">
					<h3><?php esc_html_e( 'Move to Folder', 'stagekitwp-media' ); ?></h3>
					<button type="button" class="skwpm-modal-close"><span class="dashicons dashicons-no-alt"></span></button>
				</div>
				<div class="skwpm-modal-body">
					<p><?php esc_html_e( 'Select the destination folder:', 'stagekitwp-media' ); ?></p>
					<div class="skwpm-move-folder-tree" id="skwpm-move-folder-tree"></div>
				</div>
				<div class="skwpm-modal-footer">
					<button type="button" class="button skwpm-modal-cancel"><?php esc_html_e( 'Cancel', 'stagekitwp-media' ); ?></button>
					<button type="button" class="button button-primary" id="skwpm-modal-confirm-move"><?php esc_html_e( 'Move Items', 'stagekitwp-media' ); ?></button>
				</div>
			</div>
		</div>
		<?php
	}
}
