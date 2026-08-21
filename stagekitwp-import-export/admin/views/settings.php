<?php
/**
 * Admin view – Settings page.
 *
 * @package STAGEKITWP_IO
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class="wrap stagekitwp-io-wrap">
	<h1><?php esc_html_e( 'Import-Export – Settings', 'stagekitwp-io' ); ?></h1>
	<p class="description">
		<?php esc_html_e( 'These settings apply to every chunked operation: Clear Data deletion, background imports, and exports.', 'stagekitwp-io' ); ?>
	</p>

	<form method="post" action="options.php">
		<?php
		settings_fields( 'stagekitwp_import_export_settings' );
		do_settings_sections( 'stagekitwp-io-settings' );
		submit_button();
		?>
	</form>

	<div class="stagekitwp-io-help-callout" style="background:#f0f6fc;border:1px solid #c5d9ed;border-left:4px solid #2271b1;padding:12px 16px;border-radius:6px;margin-top:8px;">
		<span aria-hidden="true">💡</span>
		<span>
			<?php esc_html_e( 'A smaller batch size means more, shorter requests (safest on shared or slow hosting). A larger batch size finishes in fewer requests (faster on capable servers). The default of 50 works well for most sites.', 'stagekitwp-io' ); ?>
		</span>
	</div>
</div>
