<?php
/**
 * Admin view – Page ID Remap.
 *
 * After importing from another site, some options store page IDs that
 * referenced posts on the source site. This screen shows those staging keys
 * and lets the admin pick the correct page on THIS site.
 *
 * @package STAGEKITWP_IO
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

// Collect all stagekitwp_import_export_remap_* staging option keys.
global $wpdb;
$staging_rows = $wpdb->get_results(
	"SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE 'stagekitwp_import_export_remap_%' ORDER BY option_name"
);

// Map of staging key → human label.
$known_keys = [
	'stagekitwp_import_export_remap_auditions_page_id'     => __( 'Auditions Page (StageKitWP Core)', 'stagekitwp-io' ),
	'stagekitwp_import_export_remap_ma_directory_page_id'  => __( 'Members Directory Page (Members Area)', 'stagekitwp-io' ),
];

// All pages for the dropdowns.
$all_pages = get_posts( [
	'post_type'      => 'page',
	'post_status'    => 'publish',
	'posts_per_page' => -1,
	'orderby'        => 'title',
	'order'          => 'ASC',
] );
?>
<div class="wrap stagekitwp-io-wrap">
	<h1><?php esc_html_e( 'Import-Export – Page ID Remap', 'stagekitwp-io' ); ?></h1>

	<p class="description">
		<?php esc_html_e( 'When you import data from another site, options that reference page IDs need to be remapped because those IDs are different here. Select the correct page for each item and click Save.', 'stagekitwp-io' ); ?>
	</p>

	<?php if ( empty( $staging_rows ) ) : ?>
		<div class="notice notice-success inline" style="margin-top:16px;">
			<p><?php esc_html_e( '✅ No page remappings pending. Everything looks good!', 'stagekitwp-io' ); ?></p>
		</div>
	<?php else : ?>

	<form id="stagekitwp-io-remap-form" method="post">
		<?php wp_nonce_field( 'stagekitwp_import_export_nonce', 'nonce' ); ?>

		<table class="widefat stagekitwp-io-remap-table" style="max-width:800px;margin-top:16px;">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Setting', 'stagekitwp-io' ); ?></th>
					<th><?php esc_html_e( 'Original ID (source site)', 'stagekitwp-io' ); ?></th>
					<th><?php esc_html_e( 'Map to this page', 'stagekitwp-io' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $staging_rows as $row ) :
				$key       = $row->option_name;
				$old_id    = (int) $row->option_value;
				$label     = $known_keys[ $key ] ?? ucwords( str_replace( [ 'stagekitwp_import_export_remap_', '_' ], [ '', ' ' ], $key ) );
				?>
				<tr>
					<td>
						<strong><?php echo esc_html( $label ); ?></strong><br>
						<code style="font-size:11px;"><?php echo esc_html( $key ); ?></code>
					</td>
					<td>
						<span class="stagekitwp-io-badge stagekitwp-io-badge--warn"><?php echo esc_html( $old_id ); ?></span>
					</td>
					<td>
						<select name="remap[<?php echo esc_attr( $key ); ?>]" class="regular-text">
							<option value="0"><?php esc_html_e( '— Select a page —', 'stagekitwp-io' ); ?></option>
							<?php foreach ( $all_pages as $page ) : ?>
							<option value="<?php echo esc_attr( $page->ID ); ?>">
								<?php echo esc_html( $page->post_title ); ?> (ID <?php echo (int) $page->ID; ?>)
							</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>

		<p class="stagekitwp-io-actions" style="margin-top:16px;">
			<button type="submit" id="stagekitwp-io-remap-btn" class="button button-primary">
				<?php esc_html_e( 'Save Page Mappings', 'stagekitwp-io' ); ?>
			</button>
		</p>

		<div id="stagekitwp-io-remap-status" class="stagekitwp-io-status" style="display:none;"></div>
	</form>

	<?php endif; ?>

	<hr style="margin-top:32px;">
	<h3><?php esc_html_e( 'Current Option Values', 'stagekitwp-io' ); ?></h3>
	<p class="description"><?php esc_html_e( 'These are the live TM page-ID options on this site after any remapping.', 'stagekitwp-io' ); ?></p>
	<table class="widefat" style="max-width:600px;">
		<thead><tr><th><?php esc_html_e( 'Option', 'stagekitwp-io' ); ?></th><th><?php esc_html_e( 'Current Value', 'stagekitwp-io' ); ?></th><th><?php esc_html_e( 'Page Title', 'stagekitwp-io' ); ?></th></tr></thead>
		<tbody>
		<?php
		$live_opts = [
			'stagekitwp_auditions_page_id'     => __( 'Auditions Page', 'stagekitwp-io' ),
			'stagekitwp_members_directory_page_id'  => __( 'Members Directory', 'stagekitwp-io' ),
		];
		foreach ( $live_opts as $opt_key => $opt_label ) :
			$val  = (int) get_option( $opt_key, 0 );
			$page = $val ? get_post( $val ) : null;
		?>
		<tr>
			<td><?php echo esc_html( $opt_label ); ?><br><code style="font-size:11px;"><?php echo esc_html( $opt_key ); ?></code></td>
			<td><?php echo $val ? '<span class="stagekitwp-io-badge stagekitwp-io-badge--ok">' . esc_html( $val ) . '</span>' : '<span class="stagekitwp-io-badge stagekitwp-io-badge--warn">0</span>'; ?></td>
			<td><?php echo $page ? esc_html( $page->post_title ) : '<em>' . esc_html__( 'Not set', 'stagekitwp-io' ) . '</em>'; ?></td>
		</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
</div>
