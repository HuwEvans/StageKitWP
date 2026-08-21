<?php
/**
 * Admin view – Export page.
 *
 * @package STAGEKITWP_IO
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$modules = stagekitwp_io()->modules();
$recent_downloads = stagekitwp_io()->recent_downloads();
?>
<div class="wrap stagekitwp-io-wrap">
	<h1><?php esc_html_e( 'Import-Export – Export', 'stagekitwp-io' ); ?></h1>
	<p class="description"><?php esc_html_e( 'Select the modules you want to export. A ZIP bundle containing JSON data and media will be generated and downloaded.', 'stagekitwp-io' ); ?></p>

	<form id="stagekitwp-io-export-form" method="post">
		<?php wp_nonce_field( 'stagekitwp_import_export_nonce', 'nonce' ); ?>

		<table class="widefat stagekitwp-io-module-table">
			<thead>
				<tr>
					<th style="width:30px;"></th>
					<th><?php esc_html_e( 'Module', 'stagekitwp-io' ); ?></th>
					<th><?php esc_html_e( 'Contents', 'stagekitwp-io' ); ?></th>
					<th><?php esc_html_e( 'Status', 'stagekitwp-io' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $modules as $id => $module ) : ?>
				<tr class="<?php echo $module->is_available() ? '' : 'stagekitwp-io-unavailable'; ?>">
					<td>
						<input type="checkbox"
							name="modules[]"
							value="<?php echo esc_attr( $id ); ?>"
							id="mod-<?php echo esc_attr( $id ); ?>"
							<?php checked( $module->is_available() ); ?>
							<?php disabled( ! $module->is_available() ); ?>>
					</td>
					<td>
						<label for="mod-<?php echo esc_attr( $id ); ?>">
							<strong><?php echo esc_html( $module->label() ); ?></strong>
							<br><code><?php echo esc_html( $id ); ?></code>
						</label>
					</td>
					<td class="stagekitwp-io-description"><?php echo esc_html( $module->description() ); ?></td>
					<td>
						<?php if ( $module->is_available() ) : ?>
							<span class="stagekitwp-io-badge stagekitwp-io-badge--ok"><?php esc_html_e( 'Active', 'stagekitwp-io' ); ?></span>
						<?php else : ?>
							<span class="stagekitwp-io-badge stagekitwp-io-badge--warn"><?php esc_html_e( 'Plugin inactive', 'stagekitwp-io' ); ?></span>
						<?php endif; ?>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<div class="stagekitwp-io-export-options">
			<label for="stagekitwp-io-include-media">
				<input type="checkbox" id="stagekitwp-io-include-media" name="include_media" value="1" checked>
				<strong><?php esc_html_e( 'Include media files', 'stagekitwp-io' ); ?></strong>
			</label>
			<p class="description">
				<?php esc_html_e( 'Bundles every image referenced by the exported posts (posters, logos, photos, venue images, gallery and inline images) plus their resized variants. Uncheck for a smaller, data-only ZIP when the destination site already has the same media.', 'stagekitwp-io' ); ?>
			</p>
		</div>

		<p class="stagekitwp-io-actions">
			<button type="button" id="stagekitwp-io-select-all" class="button"><?php esc_html_e( 'Select All', 'stagekitwp-io' ); ?></button>
			<button type="submit" id="stagekitwp-io-export-btn" class="button button-primary">
				<?php esc_html_e( 'Export Selected Modules', 'stagekitwp-io' ); ?>
			</button>
		</p>

		<div id="stagekitwp-io-export-status" class="stagekitwp-io-status" style="display:none;"></div>
	</form>

	<div id="stagekitwp-io-recent-downloads" class="stagekitwp-io-recent-downloads" style="margin-top:24px;">
		<h2><?php esc_html_e( 'Recent Downloads', 'stagekitwp-io' ); ?></h2>
		<?php if ( empty( $recent_downloads ) ) : ?>
			<p class="description">
				<?php esc_html_e( 'Exports you generate here will appear here so you can download them again later.', 'stagekitwp-io' ); ?>
			</p>
		<?php else : ?>
			<ul id="stagekitwp-io-recent-download-list">
				<?php foreach ( $recent_downloads as $download ) : ?>
					<li>
						<a href="<?php echo esc_url( $download['download_url'] ); ?>" target="_blank" rel="noopener">
							<?php echo esc_html( $download['filename'] ); ?>
						</a>
						<span class="description"> — <?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) ( $download['created_at'] ?? 0 ) ) ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
</div>
