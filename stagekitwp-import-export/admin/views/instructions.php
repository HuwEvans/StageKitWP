<?php
/**
 * Admin view – Instructions / Help page.
 *
 * A complete in-plugin guide to TM IO: exporting, importing (ZIP/JSON and the
 * CSV Season Builder), page-ID remapping, batched Clear Data, and WP-CLI.
 *
 * @package STAGEKITWP_IO
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class="wrap stagekitwp-io-wrap stagekitwp-io-help">
	<h1><?php esc_html_e( 'Import-Export – Instructions', 'stagekitwp-io' ); ?></h1>
	<p class="description">
		<?php esc_html_e( 'Import-Export moves StageKitWP ecosystem data between sites: content (CPTs), plugin settings, and theme customisations. Everything below is also available from WP-CLI.', 'stagekitwp-io' ); ?>
	</p>

	<div class="stagekitwp-io-help-toc">
		<strong><?php esc_html_e( 'On this page', 'stagekitwp-io' ); ?></strong>
		<ul>
			<li><a href="#tmio-overview"><?php esc_html_e( 'Overview &amp; modules', 'stagekitwp-io' ); ?></a></li>
			<li><a href="#tmio-export"><?php esc_html_e( 'Exporting data', 'stagekitwp-io' ); ?></a></li>
			<li><a href="#tmio-import"><?php esc_html_e( 'Importing a bundle (ZIP / JSON)', 'stagekitwp-io' ); ?></a></li>
			<li><a href="#tmio-csv"><?php esc_html_e( 'CSV Season Builder', 'stagekitwp-io' ); ?></a></li>
			<li><a href="#tmio-remap"><?php esc_html_e( 'Page ID Remap', 'stagekitwp-io' ); ?></a></li>
			<li><a href="#tmio-clear"><?php esc_html_e( 'Clear Data (batched delete)', 'stagekitwp-io' ); ?></a></li>
			<li><a href="#tmio-settings"><?php esc_html_e( 'Settings &amp; batch size', 'stagekitwp-io' ); ?></a></li>
			<li><a href="#tmio-cli"><?php esc_html_e( 'WP-CLI reference', 'stagekitwp-io' ); ?></a></li>
			<li><a href="#tmio-faq"><?php esc_html_e( 'FAQ &amp; troubleshooting', 'stagekitwp-io' ); ?></a></li>
		</ul>
	</div>

	<h2 id="tmio-overview"><?php esc_html_e( 'Overview &amp; modules', 'stagekitwp-io' ); ?></h2>
	<p><?php esc_html_e( 'Data is organised into modules. Each module owns a set of post types and/or options. You can export and import any combination of modules, so it is easy to move just content, just settings, or an entire site.', 'stagekitwp-io' ); ?></p>
	<table class="widefat striped stagekitwp-io-help-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Module', 'stagekitwp-io' ); ?></th>
				<th><?php esc_html_e( 'ID', 'stagekitwp-io' ); ?></th>
				<th><?php esc_html_e( 'What it contains', 'stagekitwp-io' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr><td><strong><?php esc_html_e( 'StageKitWP Core', 'stagekitwp-io' ); ?></strong></td><td><code>stagekitwp</code></td><td><?php esc_html_e( 'Shows, Seasons, Cast, Venues, Awards, Board Members, Contributors, Sponsors, Testimonials, Advertisers, Announcements, Events, Conversations, and Email Templates.', 'stagekitwp-io' ); ?></td></tr>
			<tr><td><strong><?php esc_html_e( 'Members Area', 'stagekitwp-io' ); ?></strong></td><td><code>stagekitwp-members-area</code></td><td><?php esc_html_e( 'Member settings, email configuration, RSVP status flags, and directory page mapping.', 'stagekitwp-io' ); ?></td></tr>
			<tr><td><strong><?php esc_html_e( 'RC Library', 'stagekitwp-io' ); ?></strong></td><td><code>stagekitwp-rc-library</code></td><td><?php esc_html_e( 'Books &amp; Scripts, Rubric Items, and Rubric Templates.', 'stagekitwp-io' ); ?></td></tr>
			<tr><td><strong><?php esc_html_e( 'Sync', 'stagekitwp-io' ); ?></strong></td><td><code>stagekitwp-sync</code></td><td><?php esc_html_e( 'Sync configuration and connection settings (secrets are redacted by default).', 'stagekitwp-io' ); ?></td></tr>
			<tr><td><strong><?php esc_html_e( 'StageKitWP Theme', 'stagekitwp-io' ); ?></strong></td><td><code>theme</code></td><td><?php esc_html_e( 'All Customizer theme mods, colour settings, nav menu locations, sidebar/widget layout, and logo/media references.', 'stagekitwp-io' ); ?></td></tr>
		</tbody>
	</table>
	<p class="description"><?php esc_html_e( 'A module whose plugin is not active on this site shows as unavailable and is skipped automatically.', 'stagekitwp-io' ); ?></p>

	<h2 id="tmio-export"><?php esc_html_e( 'Exporting data', 'stagekitwp-io' ); ?></h2>
	<ol>
		<li><?php printf( wp_kses_post( __( 'Go to <strong>Import-Export &rarr; Export</strong>.', 'stagekitwp-io' ) ) ); ?></li>
		<li><?php esc_html_e( 'Tick the modules you want to include. Use Select All to grab everything active.', 'stagekitwp-io' ); ?></li>
		<li><?php printf( wp_kses_post( __( 'Click <strong>Export Selected Modules</strong>. A single <code>.zip</code> bundle is generated and downloaded.', 'stagekitwp-io' ) ) ); ?></li>
	</ol>
	<p><?php esc_html_e( 'The bundle contains one JSON file per module plus a manifest, and any referenced media. Keep it as a backup or upload it on another site to import.', 'stagekitwp-io' ); ?></p>
	<p class="description"><?php esc_html_e( 'Tip: export before every import or Clear Data operation so you always have a rollback point.', 'stagekitwp-io' ); ?></p>

	<h2 id="tmio-import"><?php esc_html_e( 'Importing a bundle (ZIP / JSON)', 'stagekitwp-io' ); ?></h2>
	<ol>
		<li><?php printf( wp_kses_post( __( 'Go to <strong>Import-Export &rarr; Import</strong> and stay on the <strong>ZIP Bundle / JSON</strong> tab.', 'stagekitwp-io' ) ) ); ?></li>
		<li><?php esc_html_e( 'Upload a .zip bundle or a single-module .json, or paste a public HTTPS URL to one.', 'stagekitwp-io' ); ?></li>
		<li><?php printf( wp_kses_post( __( 'Choose a <strong>conflict strategy</strong>: <em>Skip</em> keeps existing posts, <em>Overwrite</em> replaces matching posts.', 'stagekitwp-io' ) ) ); ?></li>
		<li><?php printf( wp_kses_post( __( 'Optionally run a <strong>Dry Run</strong> first &mdash; it reports exactly what would be created, updated, or skipped without changing anything.', 'stagekitwp-io' ) ) ); ?></li>
		<li><?php esc_html_e( 'Click Import. Small imports run immediately; large imports are queued and processed in the background with a live progress readout.', 'stagekitwp-io' ); ?></li>
	</ol>
	<p class="description"><?php printf( wp_kses_post( __( 'Imports of more than %d records are queued automatically (via Action Scheduler if available, otherwise WP-Cron) so the request never times out.', 'stagekitwp-io' ) ), (int) STAGEKITWP_IMPORT_EXPORT_ASYNC_THRESHOLD ); ?></p>

	<h2 id="tmio-csv"><?php esc_html_e( 'CSV Season Builder', 'stagekitwp-io' ); ?></h2>
	<p><?php esc_html_e( 'The CSV Season Builder is a fast way to create a whole season of Shows (and the Season that groups them) from a spreadsheet — no ZIP bundle needed.', 'stagekitwp-io' ); ?></p>
	<ol>
		<li><?php printf( wp_kses_post( __( 'Go to <strong>Import-Export &rarr; Import</strong> and switch to the <strong>CSV Season Builder</strong> tab.', 'stagekitwp-io' ) ) ); ?></li>
		<li><?php printf( wp_kses_post( __( 'Click <strong>Download CSV template</strong> to get a correctly-formatted starter file with the expected column headers.', 'stagekitwp-io' ) ) ); ?></li>
		<li><?php esc_html_e( 'Fill in one row per show. Set the season title (a new season is created if it does not exist) and each show’s title, dates, and details.', 'stagekitwp-io' ); ?></li>
		<li><?php esc_html_e( 'Upload the file. A Dry Run is available to preview rows before committing.', 'stagekitwp-io' ); ?></li>
		<li><?php printf( wp_kses_post( __( 'After a real import you receive a <strong>rollback token</strong>. Use <em>Roll back last CSV import</em> to undo the whole batch if something looks wrong.', 'stagekitwp-io' ) ) ); ?></li>
	</ol>
	<p class="description"><?php esc_html_e( 'Shows are matched by title within the season, so re-uploading an edited file updates the same shows instead of duplicating them.', 'stagekitwp-io' ); ?></p>

	<h2 id="tmio-remap"><?php esc_html_e( 'Page ID Remap', 'stagekitwp-io' ); ?></h2>
	<p><?php esc_html_e( 'When you move a site, the numeric IDs of WordPress Pages usually change. Any plugin setting that stores a page ID (for example the Directory, Events, or Login page) can end up pointing at the wrong page. Page Remap fixes this in one screen.', 'stagekitwp-io' ); ?></p>
	<ol>
		<li><?php printf( wp_kses_post( __( 'Go to <strong>Import-Export &rarr; Page Remap</strong>.', 'stagekitwp-io' ) ) ); ?></li>
		<li><?php esc_html_e( 'For each page-based setting, pick the correct destination page from the dropdown.', 'stagekitwp-io' ); ?></li>
		<li><?php printf( wp_kses_post( __( 'Click <strong>Save Remapping</strong>. The stored option values are updated to the new page IDs.', 'stagekitwp-io' ) ) ); ?></li>
	</ol>
	<p class="description"><?php esc_html_e( 'Run this once after importing a bundle onto a fresh site to reconnect all page-based settings.', 'stagekitwp-io' ); ?></p>

	<h2 id="tmio-clear"><?php esc_html_e( 'Clear Data (batched delete)', 'stagekitwp-io' ); ?></h2>
	<div class="stagekitwp-io-help-callout stagekitwp-io-help-callout--danger">
		<span aria-hidden="true">⚠️</span>
		<?php esc_html_e( 'Clear Data permanently deletes posts and their meta — there is no undo and it bypasses Trash. Always export a backup first.', 'stagekitwp-io' ); ?>
	</div>
	<p><?php printf( wp_kses_post( __( 'Go to <strong>Import-Export &rarr; Clear Data</strong> and choose a scope:', 'stagekitwp-io' ) ) ); ?></p>
	<ul class="stagekitwp-io-help-bullets">
		<li><strong><?php esc_html_e( 'Full Ecosystem', 'stagekitwp-io' ); ?></strong> — <?php esc_html_e( 'every post from every StageKitWP CPT across all plugins (clean-slate before a full re-import).', 'stagekitwp-io' ); ?></li>
		<li><strong><?php esc_html_e( 'By Plugin', 'stagekitwp-io' ); ?></strong> — <?php esc_html_e( 'all CPTs owned by one plugin group.', 'stagekitwp-io' ); ?></li>
		<li><strong><?php esc_html_e( 'By CPT', 'stagekitwp-io' ); ?></strong> — <?php esc_html_e( 'a single post type.', 'stagekitwp-io' ); ?></li>
	</ul>
	<ol>
		<li><?php printf( wp_kses_post( __( 'Click <strong>Count Posts</strong> to preview how many posts will be removed (a safe dry run).', 'stagekitwp-io' ) ) ); ?></li>
		<li><?php printf( wp_kses_post( __( 'Click the red <strong>Delete</strong> button and type <code>DELETE</code> to confirm.', 'stagekitwp-io' ) ) ); ?></li>
	</ol>
	<h3><?php esc_html_e( 'How the delete avoids timeouts', 'stagekitwp-io' ); ?></h3>
	<p><?php esc_html_e( 'Large deletes are processed in small batches. The browser repeatedly asks the server to remove a chunk of posts, so no single request runs long enough to hit the PHP execution-time limit. A progress bar shows the live percentage and an “X of Y deleted” readout, and the on-page counts update as each batch completes. If some posts cannot be deleted, they are reported instead of silently stalling.', 'stagekitwp-io' ); ?></p>

	<h2 id="tmio-settings"><?php esc_html_e( 'Settings &amp; batch size', 'stagekitwp-io' ); ?></h2>
	<p><?php printf( wp_kses_post( __( 'On <strong>Import-Export &rarr; Settings</strong> you can set the <strong>Batch size</strong> (%1$d–%2$d, default %3$d) &mdash; the number of records processed per request.', 'stagekitwp-io' ) ), (int) STAGEKITWP_IMPORT_EXPORT_BATCH_MIN, (int) STAGEKITWP_IMPORT_EXPORT_BATCH_MAX, (int) STAGEKITWP_IMPORT_EXPORT_BATCH_DEFAULT ); ?></p>
	<p><?php esc_html_e( 'One setting governs every chunked operation, so the whole plugin behaves consistently:', 'stagekitwp-io' ); ?></p>
	<ul class="stagekitwp-io-help-bullets">
		<li><strong><?php esc_html_e( 'Clear Data', 'stagekitwp-io' ); ?></strong> — <?php esc_html_e( 'posts deleted per request (also overridable per run on the Clear Data page).', 'stagekitwp-io' ); ?></li>
		<li><strong><?php esc_html_e( 'Import', 'stagekitwp-io' ); ?></strong> — <?php esc_html_e( 'posts imported per background batch.', 'stagekitwp-io' ); ?></li>
		<li><strong><?php esc_html_e( 'Export', 'stagekitwp-io' ); ?></strong> — <?php esc_html_e( 'posts read per query while building the bundle.', 'stagekitwp-io' ); ?></li>
	</ul>
	<p class="description"><?php esc_html_e( 'Lower the batch size on slow or memory-limited hosting for maximum reliability; raise it on capable servers to finish in fewer requests.', 'stagekitwp-io' ); ?></p>

	<h2 id="tmio-cli"><?php esc_html_e( 'WP-CLI reference', 'stagekitwp-io' ); ?></h2>
	<p><?php printf( wp_kses_post( __( 'Everything above is scriptable with the <code>wp stagekitwp-io</code> command.', 'stagekitwp-io' ) ) ); ?></p>
	<table class="widefat striped stagekitwp-io-help-table">
		<thead><tr><th><?php esc_html_e( 'Command', 'stagekitwp-io' ); ?></th><th><?php esc_html_e( 'What it does', 'stagekitwp-io' ); ?></th></tr></thead>
		<tbody>
			<tr><td><code>wp stagekitwp-io modules</code></td><td><?php esc_html_e( 'List modules with their post types and availability.', 'stagekitwp-io' ); ?></td></tr>
			<tr><td><code>wp stagekitwp-io export</code></td><td><?php esc_html_e( 'Export all modules to a ZIP in the current directory.', 'stagekitwp-io' ); ?></td></tr>
			<tr><td><code>wp stagekitwp-io export --modules=stagekitwp,stagekitwp-rc-library --output=/tmp/bundle.zip</code></td><td><?php esc_html_e( 'Export specific modules to a named file.', 'stagekitwp-io' ); ?></td></tr>
			<tr><td><code>wp stagekitwp-io import /path/to/bundle.zip</code></td><td><?php esc_html_e( 'Import a bundle, skipping conflicts (default).', 'stagekitwp-io' ); ?></td></tr>
			<tr><td><code>wp stagekitwp-io import bundle.zip --conflict=overwrite --modules=stagekitwp</code></td><td><?php esc_html_e( 'Overwrite matching posts, only for the given modules.', 'stagekitwp-io' ); ?></td></tr>
			<tr><td><code>wp stagekitwp-io import https://example.com/bundle.zip</code></td><td><?php esc_html_e( 'Import directly from a remote URL.', 'stagekitwp-io' ); ?></td></tr>
			<tr><td><code>wp stagekitwp-io progress &lt;job-id&gt;</code></td><td><?php esc_html_e( 'Check the status of a queued async import.', 'stagekitwp-io' ); ?></td></tr>
		</tbody>
	</table>

	<h2 id="tmio-faq"><?php esc_html_e( 'FAQ &amp; troubleshooting', 'stagekitwp-io' ); ?></h2>
	<h3><?php esc_html_e( 'A module shows as “Plugin inactive”.', 'stagekitwp-io' ); ?></h3>
	<p><?php esc_html_e( 'That module’s plugin is not active on this site. Activate it to include the module, or leave it unticked to skip it.', 'stagekitwp-io' ); ?></p>
	<h3><?php esc_html_e( 'My import timed out or looked stuck.', 'stagekitwp-io' ); ?></h3>
	<p><?php printf( wp_kses_post( __( 'Large imports queue automatically and run in the background. Use <code>wp stagekitwp-io progress &lt;job-id&gt;</code> or the on-screen progress panel to follow them. If Action Scheduler is not installed, WP-Cron drives the queue — visiting the site keeps it moving.', 'stagekitwp-io' ) ) ); ?></p>
	<h3><?php esc_html_e( 'After importing, some pages point to the wrong content.', 'stagekitwp-io' ); ?></h3>
	<p><?php printf( wp_kses_post( __( 'Page IDs differ between sites. Run <strong>Import-Export &rarr; Page Remap</strong> to reconnect page-based settings.', 'stagekitwp-io' ) ) ); ?></p>
	<h3><?php esc_html_e( 'Does this work on SQLite?', 'stagekitwp-io' ); ?></h3>
	<p><?php esc_html_e( 'Yes. All queries are portable across MySQL and SQLite.', 'stagekitwp-io' ); ?></p>
	<h3><?php esc_html_e( 'Are my sync secrets exported?', 'stagekitwp-io' ); ?></h3>
	<p><?php esc_html_e( 'No. Sync connection secrets are redacted by default so bundles are safe to share.', 'stagekitwp-io' ); ?></p>
</div>
