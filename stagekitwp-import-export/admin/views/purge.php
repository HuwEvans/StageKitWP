<?php
/**
 * TM IO - Clear Data admin page.
 *
 * @package STAGEKITWP_IO
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
require_once STAGEKITWP_IMPORT_EXPORT_DIR . 'includes/class-stagekitwp-import-export-purger.php';
?>
<div class="wrap stagekitwp-io-wrap">
	<h1><?php esc_html_e( 'Import-Export - Clear Data', 'stagekitwp-io' ); ?></h1>
	<p class="stagekitwp-io-page-intro">
		<?php esc_html_e( 'Permanently delete posts from StageKitWP ecosystem CPTs. This cannot be undone - make sure you have an export or backup first.', 'stagekitwp-io' ); ?>
	</p>

	<!-- ── Warning banner ──────────────────────────────────────────────── -->
	<div class="stagekitwp-io-purge-warning">
		<span class="stagekitwp-io-purge-warning__icon">⚠️</span>
		<strong><?php esc_html_e( 'Destructive action - permanently deletes posts & their meta. There is no undo.', 'stagekitwp-io' ); ?></strong>
	</div>

	<!-- ── Mode tabs ───────────────────────────────────────────────────── -->
	<div class="stagekitwp-io-purge-batch">
		<label for="stagekitwp-io-purge-batch-size"><strong><?php esc_html_e( 'Batch size', 'stagekitwp-io' ); ?></strong></label>
		<input type="number" id="stagekitwp-io-purge-batch-size" min="10" max="500" step="5"
		       value="<?php echo (int) stagekitwp_import_export_batch_size(); ?>" class="small-text">
		<span class="description">
			<?php esc_html_e( 'Posts deleted per request. Lower for slow hosts, higher to finish faster.', 'stagekitwp-io' ); ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=stagekitwp-io-settings' ) ); ?>"><?php esc_html_e( 'Change the default', 'stagekitwp-io' ); ?></a>
		</span>
	</div>

	<div class="stagekitwp-io-tabs stagekitwp-io-tabs--purge" id="stagekitwp-io-purge-tabs" role="tablist">
		<button type="button" class="stagekitwp-io-tab stagekitwp-io-tab--active" data-purge-tab="all" role="tab">
			🗑 <?php esc_html_e( 'Full Ecosystem', 'stagekitwp-io' ); ?>
		</button>
		<button type="button" class="stagekitwp-io-tab" data-purge-tab="plugin" role="tab">
			🔌 <?php esc_html_e( 'By Plugin', 'stagekitwp-io' ); ?>
		</button>
		<button type="button" class="stagekitwp-io-tab" data-purge-tab="cpt" role="tab">
			📋 <?php esc_html_e( 'By CPT', 'stagekitwp-io' ); ?>
		</button>
	</div>

	<!-- ══════════════ Full Ecosystem panel ════════════════════════════ -->
	<div class="stagekitwp-io-purge-panel is-active" id="stagekitwp-io-purge-tab-all">
		<p><?php esc_html_e( 'Delete every post from every StageKitWP CPT across all plugins. Useful for a clean-slate before a full re-import.', 'stagekitwp-io' ); ?></p>

		<table class="stagekitwp-io-purge-count-table" id="stagekitwp-io-purge-all-counts">
			<thead><tr>
				<th><?php esc_html_e( 'CPT', 'stagekitwp-io' ); ?></th>
				<th><?php esc_html_e( 'Plugin', 'stagekitwp-io' ); ?></th>
				<th class="num"><?php esc_html_e( 'Posts', 'stagekitwp-io' ); ?></th>
			</tr></thead>
			<tbody>
			<?php
			$all_cpts = [];
			foreach ( STAGEKITWP_IMPORT_EXPORT_Purger::PLUGIN_GROUPS as $group_slug => $group ) :
				foreach ( $group['cpts'] as $cpt ) :
					$all_cpts[] = $cpt;
					?>
					<tr data-cpt="<?php echo esc_attr( $cpt ); ?>">
						<td><code><?php echo esc_html( $cpt ); ?></code>
							<span class="stagekitwp-io-cpt-label"><?php echo esc_html( STAGEKITWP_IMPORT_EXPORT_Purger::CPT_LABELS[ $cpt ] ?? $cpt ); ?></span>
						</td>
						<td><?php echo esc_html( $group['label'] ); ?></td>
						<td class="num stagekitwp-io-count-cell">
							<span class="stagekitwp-io-spinner-inline">...</span>
						</td>
					</tr>
				<?php endforeach;
			endforeach; ?>
			</tbody>
			<tfoot><tr>
				<td colspan="2"><strong><?php esc_html_e( 'Total', 'stagekitwp-io' ); ?></strong></td>
				<td class="num"><strong id="stagekitwp-io-purge-all-total">...</strong></td>
			</tr></tfoot>
		</table>

		<div class="stagekitwp-io-purge-actions">
			<button type="button" class="button" id="stagekitwp-io-purge-all-preview-btn">
				🔍 <?php esc_html_e( 'Count Posts', 'stagekitwp-io' ); ?>
			</button>
			<button type="button" class="button button-primary stagekitwp-io-btn-danger" id="stagekitwp-io-purge-all-btn" disabled>
				🗑 <?php esc_html_e( 'Delete All StageKitWP Data', 'stagekitwp-io' ); ?>
			</button>
		</div>
		<div class="stagekitwp-io-purge-result" id="stagekitwp-io-purge-all-result"></div>
	</div>

	<!-- ══════════════ By Plugin panel ═════════════════════════════════ -->
	<div class="stagekitwp-io-purge-panel" id="stagekitwp-io-purge-tab-plugin">
		<p><?php esc_html_e( 'Delete all posts belonging to a single plugin&rsquo;s CPTs.', 'stagekitwp-io' ); ?></p>

		<div class="stagekitwp-io-purge-plugin-grid">
			<?php foreach ( STAGEKITWP_IMPORT_EXPORT_Purger::PLUGIN_GROUPS as $group_slug => $group ) : ?>
			<div class="stagekitwp-io-purge-plugin-card" data-plugin="<?php echo esc_attr( $group_slug ); ?>">
				<h3 class="stagekitwp-io-purge-plugin-card__title"><?php echo esc_html( $group['label'] ); ?></h3>
				<ul class="stagekitwp-io-purge-plugin-card__cpts">
					<?php foreach ( $group['cpts'] as $cpt ) : ?>
					<li>
						<code><?php echo esc_html( $cpt ); ?></code>
						<span class="stagekitwp-io-cpt-label"><?php echo esc_html( STAGEKITWP_IMPORT_EXPORT_Purger::CPT_LABELS[ $cpt ] ?? $cpt ); ?></span>
						<span class="stagekitwp-io-count-badge" data-cpt="<?php echo esc_attr( $cpt ); ?>">...</span>
					</li>
					<?php endforeach; ?>
				</ul>
				<div class="stagekitwp-io-purge-plugin-card__footer">
					<span class="stagekitwp-io-plugin-total">
						<?php esc_html_e( 'Total:', 'stagekitwp-io' ); ?>
						<strong class="stagekitwp-io-plugin-total-num">...</strong>
					</span>
						<button type="button" class="button" data-plugin-preview="<?php echo esc_attr( $group_slug ); ?>">
						🔍 <?php esc_html_e( 'Count', 'stagekitwp-io' ); ?>
					</button>
						<button type="button" class="button button-primary stagekitwp-io-btn-danger" data-plugin-delete="<?php echo esc_attr( $group_slug ); ?>" disabled>
						🗑 <?php esc_html_e( 'Delete', 'stagekitwp-io' ); ?>
					</button>
				</div>
				<div class="stagekitwp-io-purge-result stagekitwp-io-purge-plugin-result"></div>
			</div>
			<?php endforeach; ?>
		</div>
	</div>

	<!-- ══════════════ By CPT panel ════════════════════════════════════ -->
	<div class="stagekitwp-io-purge-panel" id="stagekitwp-io-purge-tab-cpt">
		<p><?php esc_html_e( 'Delete all posts from one specific post type.', 'stagekitwp-io' ); ?></p>

		<table class="stagekitwp-io-purge-count-table widefat">
			<thead><tr>
				<th><?php esc_html_e( 'CPT', 'stagekitwp-io' ); ?></th>
				<th><?php esc_html_e( 'Plugin', 'stagekitwp-io' ); ?></th>
				<th class="num"><?php esc_html_e( 'Posts', 'stagekitwp-io' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'stagekitwp-io' ); ?></th>
			</tr></thead>
			<tbody>
			<?php foreach ( STAGEKITWP_IMPORT_EXPORT_Purger::PLUGIN_GROUPS as $group_slug => $group ) :
				foreach ( $group['cpts'] as $cpt ) : ?>
				<tr data-cpt-row="<?php echo esc_attr( $cpt ); ?>">
					<td>
						<code><?php echo esc_html( $cpt ); ?></code>
						<span class="stagekitwp-io-cpt-label"><?php echo esc_html( STAGEKITWP_IMPORT_EXPORT_Purger::CPT_LABELS[ $cpt ] ?? $cpt ); ?></span>
					</td>
					<td><?php echo esc_html( $group['label'] ); ?></td>
					<td class="num">
						<span class="stagekitwp-io-count-badge" data-cpt="<?php echo esc_attr( $cpt ); ?>">...</span>
					</td>
					<td class="stagekitwp-io-cpt-actions">
							<button type="button" class="button button-small" data-cpt-preview="<?php echo esc_attr( $cpt ); ?>">
							🔍 <?php esc_html_e( 'Count', 'stagekitwp-io' ); ?>
						</button>
							<button type="button" class="button button-small stagekitwp-io-btn-danger" data-cpt-delete="<?php echo esc_attr( $cpt ); ?>" disabled>
							🗑 <?php esc_html_e( 'Delete', 'stagekitwp-io' ); ?>
						</button>
					</td>
				</tr>
			<?php endforeach; endforeach; ?>
			</tbody>
		</table>

		<div class="stagekitwp-io-purge-result" id="stagekitwp-io-purge-cpt-result"></div>
	</div>

	<!-- ── Shared spinner + result area ─────────────────────────────── -->
	<div class="stagekitwp-io-spinner" id="stagekitwp-io-purge-spinner">
		<div class="stagekitwp-io-spinner__dots"><span></span><span></span><span></span></div>
		<span id="stagekitwp-io-purge-spinner-msg"><?php esc_html_e( 'Working...', 'stagekitwp-io' ); ?></span>
	</div>

	<!-- Batched-delete progress panel (hidden until a delete starts) -->
	<div class="stagekitwp-io-purge-progress" id="stagekitwp-io-purge-progress" aria-live="polite" hidden>
		<div class="stagekitwp-io-purge-progress__head">
			<span class="stagekitwp-io-purge-progress__label" id="stagekitwp-io-purge-progress-label"><?php esc_html_e( 'Deleting…', 'stagekitwp-io' ); ?></span>
			<span class="stagekitwp-io-purge-progress__pct" id="stagekitwp-io-purge-progress-pct">0%</span>
		</div>
		<div class="stagekitwp-io-purge-progress__track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" id="stagekitwp-io-purge-progress-track">
			<div class="stagekitwp-io-purge-progress__bar" id="stagekitwp-io-purge-progress-bar"></div>
		</div>
		<div class="stagekitwp-io-purge-progress__stats" id="stagekitwp-io-purge-progress-stats"></div>
	</div>

</div><!-- .stagekitwp-io-wrap -->
