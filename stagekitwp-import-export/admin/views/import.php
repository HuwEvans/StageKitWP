<?php
/**
 * Admin view – Import page (ZIP / JSON / CSV).
 * @package STAGEKITWP_IO
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$max_upload = wp_max_upload_size();

// Fetch seasons for the dropdown.
$seasons = get_posts( [
	'post_type'      => 'season',
	'post_status'    => 'any',
	'posts_per_page' => -1,
	'orderby'        => 'title',
	'order'          => 'ASC',
] );
?>
<div class="wrap stagekitwp-io-wrap">
	<h1><?php esc_html_e( 'Import-Export – Import', 'stagekitwp-io' ); ?></h1>

	<nav class="stagekitwp-io-tabs" id="stagekitwp-io-source-tabs">
		<button type="button" class="stagekitwp-io-tab stagekitwp-io-tab--active" data-tab="bundle"><?php esc_html_e( 'ZIP Bundle / JSON', 'stagekitwp-io' ); ?></button>
		<button type="button" class="stagekitwp-io-tab" data-tab="csv"><?php esc_html_e( '📋 CSV Season Builder', 'stagekitwp-io' ); ?></button>
	</nav>

	<?php /* ══════════════ ZIP / JSON tab ══════════════ */ ?>
	<div id="stagekitwp-io-tab-bundle" class="stagekitwp-io-panel stagekitwp-io-panel--active">
		<form id="stagekitwp-io-import-form" method="post" enctype="multipart/form-data">
			<?php wp_nonce_field( 'stagekitwp_import_export_nonce', 'nonce' ); ?>

			<nav class="stagekitwp-io-tabs stagekitwp-io-tabs--inner">
				<button type="button" class="stagekitwp-io-tab stagekitwp-io-tab--active" data-tab="upload"><?php esc_html_e( 'Upload File', 'stagekitwp-io' ); ?></button>
				<button type="button" class="stagekitwp-io-tab" data-tab="url"><?php esc_html_e( 'Remote URL', 'stagekitwp-io' ); ?></button>
			</nav>

			<div id="stagekitwp-io-tab-upload" class="stagekitwp-io-panel stagekitwp-io-panel--active">
				<p class="description"><?php printf( esc_html__( 'Upload a .zip bundle or single-module .json. Max: %s.', 'stagekitwp-io' ), esc_html( size_format( $max_upload ) ) ); ?></p>
				<label class="stagekitwp-io-upload-label" for="stagekitwp-io-file-input">
					<span class="dashicons dashicons-upload"></span>
					<span id="stagekitwp-io-file-label"><?php esc_html_e( 'Click to choose or drag-and-drop a .zip or .json', 'stagekitwp-io' ); ?></span>
					<input type="file" id="stagekitwp-io-file-input" name="stagekitwp_import_export_file" accept=".zip,.json" style="display:none;">
				</label>
			</div>

			<div id="stagekitwp-io-tab-url" class="stagekitwp-io-panel">
				<p class="description"><?php esc_html_e( 'Enter a public HTTPS URL to a .zip or .json file.', 'stagekitwp-io' ); ?></p>
				<input type="url" name="remote_url" id="stagekitwp-io-remote-url" class="regular-text"
					placeholder="https://example.com/stagekitwp-export-bundle.zip">
			</div>

			<h3><?php esc_html_e( 'Import Options', 'stagekitwp-io' ); ?></h3>
			<table class="form-table stagekitwp-io-options-table">
				<tr>
					<th><label for="stagekitwp-io-conflict"><?php esc_html_e( 'Conflict strategy', 'stagekitwp-io' ); ?></label></th>
					<td>
						<select name="conflict" id="stagekitwp-io-conflict">
							<option value="skip"><?php esc_html_e( 'Skip – keep existing records unchanged (safe default)', 'stagekitwp-io' ); ?></option>
							<option value="overwrite"><?php esc_html_e( 'Overwrite – replace existing with imported data', 'stagekitwp-io' ); ?></option>
							<option value="duplicate"><?php esc_html_e( 'Duplicate – always insert as new records', 'stagekitwp-io' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Modules to import', 'stagekitwp-io' ); ?></th>
					<td>
						<label><input type="radio" name="modules" value="all" checked> <?php esc_html_e( 'All modules in bundle', 'stagekitwp-io' ); ?></label><br>
						<label><input type="radio" name="modules" value="custom" id="stagekitwp-io-modules-custom"> <?php esc_html_e( 'Choose specific modules:', 'stagekitwp-io' ); ?></label>
						<div id="stagekitwp-io-module-checkboxes" style="display:none;padding-left:24px;margin-top:6px;">
							<?php foreach ( stagekitwp_io()->modules() as $id => $module ) : ?>
							<label style="display:block;margin-bottom:4px;">
								<input type="checkbox" name="module_ids[]" value="<?php echo esc_attr( $id ); ?>" checked>
								<?php echo esc_html( $module->label() ); ?>
							</label>
							<?php endforeach; ?>
						</div>
					</td>
				</tr>
			</table>

			<p class="stagekitwp-io-actions">
				<button type="button" id="stagekitwp-io-dry-run-btn" class="button"><?php esc_html_e( '🔍 Dry Run (preview only)', 'stagekitwp-io' ); ?></button>
				<button type="submit" id="stagekitwp-io-import-btn" class="button button-primary"><?php esc_html_e( 'Start Import', 'stagekitwp-io' ); ?></button>
			</p>

			<div id="stagekitwp-io-import-status" class="stagekitwp-io-status" style="display:none;">
				<p id="stagekitwp-io-status-message"></p>
				<div id="stagekitwp-io-progress-wrap" style="display:none;">
					<div class="stagekitwp-io-progress-bar"><div id="stagekitwp-io-progress-inner"></div></div>
					<p id="stagekitwp-io-progress-label"></p>
				</div>
				<div id="stagekitwp-io-dry-run-results" style="display:none;"></div>
			</div>
		</form>
	</div>

	<?php /* ══════════════ CSV Season Builder tab ══════════════ */ ?>
	<div id="stagekitwp-io-tab-csv" class="stagekitwp-io-panel">

		<!-- Step indicator -->
		<ol class="stagekitwp-io-steps" id="stagekitwp-io-csv-steps" aria-label="<?php esc_attr_e( 'Import steps', 'stagekitwp-io' ); ?>">
			<li class="is-active" id="stagekitwp-io-step-1"><?php esc_html_e( 'Choose file &amp; options', 'stagekitwp-io' ); ?></li>
			<li id="stagekitwp-io-step-2"><?php esc_html_e( 'Preview rows', 'stagekitwp-io' ); ?></li>
			<li id="stagekitwp-io-step-3"><?php esc_html_e( 'Import', 'stagekitwp-io' ); ?></li>
		</ol>

		<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px;">
			<p style="margin:0;color:#50575e;"><?php esc_html_e( 'One row = one show. Images can be added later via the media library.', 'stagekitwp-io' ); ?></p>
			<a href="<?php echo esc_url( add_query_arg( [ 'action' => 'stagekitwp_import_export_csv_template', 'nonce' => wp_create_nonce( 'stagekitwp_import_export_nonce' ) ], admin_url( 'admin-ajax.php' ) ) ); ?>"
				class="button" id="stagekitwp-io-dl-template" style="white-space:nowrap;">
				⬇ <?php esc_html_e( 'Download Template', 'stagekitwp-io' ); ?>
			</a>
		</div>

		<form id="stagekitwp-io-csv-form" method="post" enctype="multipart/form-data">
			<?php wp_nonce_field( 'stagekitwp_import_export_nonce', 'nonce' ); ?>

			<label class="stagekitwp-io-upload-label" for="stagekitwp-io-csv-input">
				<span class="dashicons dashicons-media-spreadsheet"></span>
				<span id="stagekitwp-io-csv-label"><?php esc_html_e( 'Click to choose or drag-and-drop a .csv file', 'stagekitwp-io' ); ?></span>
				<small id="stagekitwp-io-csv-file-info" style="color:#2271b1;display:none;"></small>
				<input type="file" id="stagekitwp-io-csv-input" name="stagekitwp_import_export_csv" accept=".csv,.txt" style="display:none;">
			</label>

			<h3><?php esc_html_e( 'Options', 'stagekitwp-io' ); ?></h3>
			<table class="form-table stagekitwp-io-options-table">
				<tr>
					<th><label for="stagekitwp-io-csv-season"><?php esc_html_e( 'Assign to season', 'stagekitwp-io' ); ?></label></th>
					<td>
						<select name="season_id" id="stagekitwp-io-csv-season">
							<option value="0"><?php esc_html_e( '— Use season_title column in CSV —', 'stagekitwp-io' ); ?></option>
							<?php foreach ( $seasons as $s ) : ?>
							<option value="<?php echo esc_attr( $s->ID ); ?>"><?php echo esc_html( $s->post_title ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( 'Overrides any season_title column in the CSV.', 'stagekitwp-io' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><label for="stagekitwp-io-csv-conflict"><?php esc_html_e( 'If show already exists', 'stagekitwp-io' ); ?></label></th>
					<td>
						<select name="conflict" id="stagekitwp-io-csv-conflict">
							<option value="skip"><?php esc_html_e( 'Skip – do not change existing shows', 'stagekitwp-io' ); ?></option>
							<option value="overwrite"><?php esc_html_e( 'Overwrite – update existing shows with CSV data', 'stagekitwp-io' ); ?></option>
							<option value="duplicate"><?php esc_html_e( 'Duplicate – always create a new show post', 'stagekitwp-io' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Default post status', 'stagekitwp-io' ); ?></th>
					<td>
						<select name="csv_default_status" id="stagekitwp-io-csv-default-status">
							<option value="draft"><?php esc_html_e( 'Draft (recommended)', 'stagekitwp-io' ); ?></option>
							<option value="publish"><?php esc_html_e( 'Published', 'stagekitwp-io' ); ?></option>
							<option value="pending"><?php esc_html_e( 'Pending Review', 'stagekitwp-io' ); ?></option>
						</select>
						<p class="description"><?php esc_html_e( 'Used when a row has no post_status column.', 'stagekitwp-io' ); ?></p>
					</td>
				</tr>
			</table>

			<p class="stagekitwp-io-actions">
				<button type="button" id="stagekitwp-io-csv-dry-run-btn" class="button" disabled>
					🔍 <?php esc_html_e( 'Preview Rows (dry run)', 'stagekitwp-io' ); ?>
				</button>
				<button type="submit" id="stagekitwp-io-csv-btn" class="button button-primary" disabled>
					<?php esc_html_e( 'Import CSV', 'stagekitwp-io' ); ?>
				</button>
			</p>

			<!-- Spinner -->
			<div class="stagekitwp-io-spinner" id="stagekitwp-io-csv-spinner">
				<div class="stagekitwp-io-spinner__dots"><span></span><span></span><span></span></div>
				<span id="stagekitwp-io-csv-spinner-msg"><?php esc_html_e( 'Processing…', 'stagekitwp-io' ); ?></span>
			</div>

			<!-- Result banner (shown after dry-run or import) -->
			<div class="stagekitwp-io-result-banner" id="stagekitwp-io-csv-banner">
				<p class="stagekitwp-io-result-banner__title" id="stagekitwp-io-csv-banner-title"></p>
				<p class="stagekitwp-io-result-banner__sub"  id="stagekitwp-io-csv-banner-sub"></p>
			</div>

			<!-- Stat cards -->
			<div class="stagekitwp-io-summary" id="stagekitwp-io-csv-summary" style="display:none;"></div>

			<!-- Row preview table -->
			<div id="stagekitwp-io-csv-preview-wrap" style="display:none;">
				<h4 style="margin:0 0 8px;font-size:13px;color:#50575e;text-transform:uppercase;letter-spacing:.4px;">
					<?php esc_html_e( 'Row Preview', 'stagekitwp-io' ); ?>
				</h4>
				<div class="stagekitwp-io-preview-table-wrap">
					<table class="stagekitwp-io-preview-table" id="stagekitwp-io-csv-preview-table">
						<thead>
							<tr>
								<th class="col-row">#</th>
								<th><?php esc_html_e( 'Show Title', 'stagekitwp-io' ); ?></th>
								<th class="col-action"><?php esc_html_e( 'Action', 'stagekitwp-io' ); ?></th>
								<th class="col-season"><?php esc_html_e( 'Season', 'stagekitwp-io' ); ?></th>
								<th><?php esc_html_e( 'Slot', 'stagekitwp-io' ); ?></th>
								<th><?php esc_html_e( 'Dates', 'stagekitwp-io' ); ?></th>
								<th><?php esc_html_e( 'Venue', 'stagekitwp-io' ); ?></th>
							</tr>
						</thead>
						<tbody id="stagekitwp-io-csv-preview-tbody"></tbody>
					</table>
				</div>
			</div>

			<!-- Error list -->
			<ul class="stagekitwp-io-error-list" id="stagekitwp-io-csv-errors" style="display:none;"></ul>

			<!-- Rollback panel (shown after a successful real import) -->
			<div id="stagekitwp-io-csv-rollback-panel" style="display:none;">
				<div class="stagekitwp-io-rollback-bar">
					<span class="stagekitwp-io-rollback-bar__icon">↩</span>
					<span class="stagekitwp-io-rollback-bar__msg"><?php esc_html_e( 'Changed your mind? You can undo this import within 1 hour.', 'stagekitwp-io' ); ?></span>
					<button type="button" id="stagekitwp-io-csv-rollback-btn" class="button button-link-delete">
						<?php esc_html_e( 'Undo Import', 'stagekitwp-io' ); ?>
					</button>
					<span id="stagekitwp-io-rollback-countdown" class="stagekitwp-io-rollback-bar__timer"></span>
				</div>
			</div>

		</form>

		<div class="stagekitwp-io-csv-column-ref">
			<h4><?php esc_html_e( 'CSV Column Reference', 'stagekitwp-io' ); ?></h4>
			<table class="widefat striped" style="max-width:700px;">
				<thead><tr><th><?php esc_html_e( 'Column', 'stagekitwp-io' ); ?></th><th><?php esc_html_e( 'Description', 'stagekitwp-io' ); ?></th><th><?php esc_html_e( 'Required', 'stagekitwp-io' ); ?></th></tr></thead>
				<tbody>
				<?php
				$col_docs = [
					'show_title'           => [ __( 'Show/play title', 'stagekitwp-io' ),              true ],
					'author'               => [ __( 'Playwright / author', 'stagekitwp-io' ),           false ],
					'sub_authors'          => [ __( 'Additional authors (comma-sep)', 'stagekitwp-io' ), false ],
					'synopsis'             => [ __( 'Short description of the show', 'stagekitwp-io' ), false ],
					'genre'                => [ __( 'e.g. Comedy, Drama, Musical', 'stagekitwp-io' ),   false ],
					'director'             => [ __( 'Director name', 'stagekitwp-io' ),                 false ],
					'associate_director'   => [ __( 'Associate director name', 'stagekitwp-io' ),       false ],
					'producer'             => [ __( 'Producer name', 'stagekitwp-io' ),                 false ],
					'stage_manager'        => [ __( 'Stage manager name', 'stagekitwp-io' ),            false ],
					'time_slot'            => [ __( 'e.g. Fall, Winter, Spring', 'stagekitwp-io' ),     false ],
					'show_dates'           => [ __( 'Display string, e.g. "Oct 3, 4, 5"', 'stagekitwp-io' ), false ],
					'tickets_url'          => [ __( 'Full URL to the ticketing page', 'stagekitwp-io' ), false ],
					'audition_date'        => [ __( 'Audition date (display string)', 'stagekitwp-io' ), false ],
					'audition_details'     => [ __( 'Audition location / notes', 'stagekitwp-io' ),     false ],
					'season_title'         => [ __( 'Season title — matched by name, or <strong>created automatically</strong> if not found', 'stagekitwp-io' ), false ],
					'season_start_date'    => [ __( 'Season start date YYYY-MM-DD — only written when creating a new season', 'stagekitwp-io' ), false ],
					'season_end_date'      => [ __( 'Season end date YYYY-MM-DD — only written when creating a new season', 'stagekitwp-io' ), false ],
					'venue_name'           => [ __( 'Must match an existing Venue post title', 'stagekitwp-io' ),  false ],
					'post_status'          => [ __( 'publish, draft, or pending', 'stagekitwp-io' ),    false ],
				];
				foreach ( $col_docs as $col => [ $desc, $req ] ) : ?>
				<tr>
					<td><code><?php echo esc_html( $col ); ?></code></td>
					<td><?php echo esc_html( $desc ); ?></td>
					<td><?php echo $req ? '<strong>' . esc_html__( 'Yes', 'stagekitwp-io' ) . '</strong>' : esc_html__( 'No', 'stagekitwp-io' ); ?></td>
				</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
