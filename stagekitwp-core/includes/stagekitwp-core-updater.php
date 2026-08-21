<?php
/**
 * StageKitWP Core — Self-hosted plugin updater
 *
 * Hooks into WordPress's built-in plugin update pipeline so updates appear on
 * Dashboard → Updates and can be installed with one click, served from your
 * own endpoint at STAGEKITWP_UPDATE_MANIFEST_URL.
 *
 * Remote manifest format (JSON at STAGEKITWP_UPDATE_MANIFEST_URL):
 *
 *   {
 *     "name":         "StageKitWP",
 *     "slug":         "stagekitwp-core",
 *     "version":      "4.1.0",
 *     "requires":     "6.0",
 *     "tested":       "6.8",
 *     "requires_php": "7.4",
 *     "author":       "<a href=\"http://github.com/HuwEvans\">Huw Evans</a>",
 *     "download_url": "https://hsctjourney.com/updates/stagekitwp/stagekitwp-4.1.5.zip",
 *     "details_url":  "https://hsctjourney.com/updates/stagekitwp/stagekitwp-changelog.html",
 *     "last_updated": "2026-07-04",
 *     "sections": {
 *       "description": "<p>Manage theatre-related content…</p>",
 *       "changelog":   "<h4>4.1.0</h4><ul><li>…</li></ul>"
 *     },
 *     "banners": {
 *       "low":  "https://hsctjourney.com/updates/stagekitwp/banner-772x250.jpg",
 *       "high": "https://hsctjourney.com/updates/stagekitwp/banner-1544x500.jpg"
 *     }
 *   }
 *
 * Only "version" and "download_url" are strictly required.
 */

defined( 'ABSPATH' ) || exit;

/**
 * URL of the JSON manifest on your update server.
 *
 * Served from the HuwEvans/StageKitWP GitHub repo, "StageKitWP" branch,
 * dist/stagekitwp-core directory: https://github.com/HuwEvans/StageKitWP/tree/StageKitWP/dist/stagekitwp-core
 * wp_remote_get() needs the raw file content, not GitHub's HTML tree-browsing
 * page, so this uses the matching raw.githubusercontent.com URL.
 */
define( 'STAGEKITWP_UPDATE_MANIFEST_URL', 'https://raw.githubusercontent.com/HuwEvans/StageKitWP/StageKitWP/dist/stagekitwp-core/stagekitwp-core.json' );

/** Transient key used to cache the remote manifest. */
define( 'STAGEKITWP_UPDATE_TRANSIENT', 'stagekitwp_plugin_update_manifest' );

/** Cache TTL — 12 hours. */
define( 'STAGEKITWP_UPDATE_CACHE_TTL', 12 * HOUR_IN_SECONDS );

// ---------------------------------------------------------------------------
// 1. Manifest fetcher
// ---------------------------------------------------------------------------

/**
 * Fetch and decode the remote update manifest (cached for STAGEKITWP_UPDATE_CACHE_TTL).
 * Pass $force = true to bypass the cache (e.g. after a manual re-check).
 *
 * @param  bool        $force  Skip cache and re-fetch.
 * @return object|false        Decoded JSON object, or false on failure.
 */
function stagekitwp_updater_fetch_manifest( $force = false ) {
    if ( ! $force ) {
        $cached = get_transient( STAGEKITWP_UPDATE_TRANSIENT );
        if ( false !== $cached ) {
            return $cached;
        }
    }

    $response = wp_remote_get(
        STAGEKITWP_UPDATE_MANIFEST_URL,
        array(
            'timeout'    => 10,
            'user-agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url(),
        )
    );

    if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
        // Short negative cache — don't hammer the server on every page load.
        set_transient( STAGEKITWP_UPDATE_TRANSIENT, false, 30 * MINUTE_IN_SECONDS );
        return false;
    }

    $data = json_decode( wp_remote_retrieve_body( $response ) );

    if ( ! is_object( $data ) || empty( $data->version ) ) {
        set_transient( STAGEKITWP_UPDATE_TRANSIENT, false, 30 * MINUTE_IN_SECONDS );
        return false;
    }

    set_transient( STAGEKITWP_UPDATE_TRANSIENT, $data, STAGEKITWP_UPDATE_CACHE_TTL );
    return $data;
}

// ---------------------------------------------------------------------------
// 2. Inject into WP's update transient
// ---------------------------------------------------------------------------

/**
 * Hooked to pre_set_site_transient_update_plugins.
 * Adds an update entry when the remote manifest reports a newer version.
 *
 * @param  object $transient  WP update transient.
 * @return object
 */
function stagekitwp_updater_check( $transient ) {
    if ( empty( $transient->checked ) ) {
        return $transient;
    }

    $manifest    = stagekitwp_updater_fetch_manifest();
    $plugin_file = 'stagekitwp-core/stagekitwp-core.php';

    if ( $manifest && version_compare( $manifest->version, STAGEKITWP_CORE_VERSION, '>' ) ) {
        $transient->response[ $plugin_file ] = (object) array(
            'id'           => STAGEKITWP_UPDATE_MANIFEST_URL,
            'slug'         => 'stagekitwp-core',
            'plugin'       => $plugin_file,
            'new_version'  => $manifest->version,
            'url'          => isset( $manifest->details_url )  ? $manifest->details_url  : STAGEKITWP_UPDATE_MANIFEST_URL,
            'package'      => isset( $manifest->download_url ) ? $manifest->download_url : '',
            'icons'        => isset( $manifest->icons )
                                ? (array) $manifest->icons
                                : array(
                                    '1x' => 'https://hsctjourney.com/updates/stagekitwp/icon-128x128.svg',
                                    '2x' => 'https://hsctjourney.com/updates/stagekitwp/icon-256x256.svg',
                                  ),
            'banners'      => isset( $manifest->banners )      ? (array) $manifest->banners : array(
                                    'low'  => 'https://hsctjourney.com/updates/stagekitwp/banner-772x250.svg',
                                    'high' => 'https://hsctjourney.com/updates/stagekitwp/banner-772x250.svg',
                                  ),
            'requires'     => isset( $manifest->requires )     ? $manifest->requires     : '',
            'tested'       => isset( $manifest->tested )       ? $manifest->tested       : '',
            'requires_php' => isset( $manifest->requires_php ) ? $manifest->requires_php : '',
        );
    } else {
        // Explicitly mark as up-to-date so WP doesn't show a stale notice.
        if ( ! isset( $transient->no_update[ $plugin_file ] ) ) {
            $transient->no_update[ $plugin_file ] = (object) array(
                'id'          => STAGEKITWP_UPDATE_MANIFEST_URL,
                'slug'        => 'stagekitwp-core',
                'plugin'      => $plugin_file,
                'new_version' => STAGEKITWP_CORE_VERSION,
                'url'         => '',
                'package'     => '',
                'icons'       => array(),
                'banners'     => array(),
            );
        }
    }

    return $transient;
}
add_filter( 'pre_set_site_transient_update_plugins', 'stagekitwp_updater_check' );

// ---------------------------------------------------------------------------
// 3. Populate the "View details" modal (plugins_api)
// ---------------------------------------------------------------------------

/**
 * Return rich plugin info when the admin opens the "View version x.x details"
 * thickbox on the Plugins or Updates screen.
 *
 * @param  false|object $result
 * @param  string       $action
 * @param  object       $args
 * @return false|object
 */
function stagekitwp_updater_plugin_info( $result, $action, $args ) {
    if ( 'plugin_information' !== $action ) { return $result; }
    // WP normalises slugs via sanitize_title() before passing them here,
    // so 'StageKitWP Core' becomes 'stagekitwp-core' (lowercase, hyphenated).
    if ( empty( $args->slug ) || 'stagekitwp-core' !== strtolower( $args->slug ) ) { return $result; }

    $manifest = stagekitwp_updater_fetch_manifest();
    if ( ! $manifest ) { return $result; }

    // The published manifest's "name" field currently just mirrors the slug
    // ("stagekitwp-core") rather than a display title. Prefer the friendly
    // name unless the manifest supplies something meaningfully different.
    $display_name = 'StageKitWP Core';
    if ( isset( $manifest->name ) && isset( $manifest->slug ) && strtolower( $manifest->name ) !== strtolower( $manifest->slug ) ) {
        $display_name = $manifest->name;
    }

    return (object) array(
        'name'          => $display_name,
        'slug'          => 'stagekitwp-core',
        'version'       => $manifest->version,
        'author'        => isset( $manifest->author )       ? $manifest->author       : '<a href="https://github.com/HuwEvans">Huw Evans</a>',
        'homepage'      => isset( $manifest->details_url )  ? $manifest->details_url  : 'https://github.com/HuwEvans/StageKitWP/tree/StageKitWP/dist/stagekitwp-core',
        'requires'      => isset( $manifest->requires )     ? $manifest->requires     : '6.8',
        'tested'        => isset( $manifest->tested )       ? $manifest->tested       : '6.8.2', // single version only — WP uses version_compare() on this field
        'requires_php'  => isset( $manifest->requires_php ) ? $manifest->requires_php : '7.4',
        'download_link' => isset( $manifest->download_url ) ? $manifest->download_url : '',
        'last_updated'  => isset( $manifest->last_updated ) ? $manifest->last_updated : '2026-07-04',
        'banners'       => isset( $manifest->banners )      ? (array) $manifest->banners : array(),
        'sections'      => array(
            'description' => isset( $manifest->sections->description )
                                ? $manifest->sections->description
                                : '<p><strong>StageKitWP</strong> is a comprehensive WordPress plugin built for community theatre groups and drama organisations. It provides a complete toolkit for managing and displaying season, show, cast, and company information on your WordPress site.</p><h4>Custom Post Types</h4><ul><li><strong>Seasons</strong> &#8212; track theatre seasons with dates, images, and current/upcoming/past status</li><li><strong>Shows</strong> &#8212; manage productions within seasons including genre, director, audition details, and ticket URLs</li><li><strong>Cast</strong> &#8212; display cast members with headshots, roles, and biographies</li><li><strong>Venues</strong> &#8212; manage performance venues with addresses, phone numbers, and Google Maps links</li><li><strong>Awards</strong> &#8212; track awards and nominations (Musical / Drama / Comedy categories)</li><li><strong>Board Members</strong> &#8212; maintain board member listings with positions and photos</li><li><strong>Sponsors &amp; Advertisers</strong> &#8212; manage sponsor logos and local business advertising</li><li><strong>Contributors</strong> &#8212; acknowledge donors and supporters</li><li><strong>Testimonials</strong> &#8212; display audience reviews with star ratings</li></ul><h4>16 Shortcodes</h4><p>Every content type is surfaced via a matching shortcode &#8212; from <code>[stagekitwp_tickets]</code> (five display layouts: banner, cards, table, minimal, spotlight) to <code>[stagekitwp_landingpage]</code> (full show landing page with cast, bio, tickets, and program PDF). All shortcodes accept attribute-level control over fields, layout, filtering, and ordering.</p><h4>Gutenberg Blocks &amp; Beaver Builder Modules</h4><p>All 16 shortcodes have matching Gutenberg block configs under the <em>StageKitWP</em> block category. 14 shortcodes have Beaver Builder modules with bespoke SVG icons. <code>[stagekitwp_landingpage]</code> ships a full React-powered Gutenberg block with live field management and shortcode preview.</p><h4>Season Builder</h4><p>A single admin screen for creating and editing a season: season metadata, shows, cast rosters, award entries, and media (images, programs) &#8212; all from one tabbed form.</p><h4>Display Options &amp; Dark / Light Mode</h4><p>11 Display Options tabs (one per shortcode group) provide colour, typography, border, and layout controls. The Show tab is shared by <code>[stagekitwp_shows]</code>, <code>[stagekitwp_show_cast]</code>, <code>[stagekitwp_season_shows]</code>, <code>[stagekitwp_past_shows]</code>, and <code>[stagekitwp_programs]</code>; the Season tab is shared by <code>[stagekitwp_seasons]</code> and <code>[stagekitwp_season_shows]</code>. When paired with the <strong>StageKitWP Theme</strong>, all shortcodes participate in the site-wide dark/light mode toggle. Light and dark colour pickers appear side-by-side on every Display Options tab.</p><h4>Self-hosted Updates</h4><p>Updates are delivered through a self-hosted JSON manifest. New versions appear on the standard WordPress Updates screen and install with one click.</p>',
            'changelog'    => isset( $manifest->sections->changelog )
                                ? $manifest->sections->changelog
                                : '<h3>4.5.x &#8212; Shortcode Consolidation</h3><ul><li><strong>4.5.1</strong> &#8212; Removed orphaned Cast Display Options tab (nothing read <code>stagekitwp_cast_*</code> options after 4.5.0). <code>[stagekitwp_show_cast]</code> and <code>[stagekitwp_programs]</code> both use the Show tab. Display Options now has 11 tabs. <code>theme-integration.php</code>: removed <code>cast</code> key from integration map; <code>.stagekitwp-show-cast</code> selector merged into <code>show</code> entry. Dark-mode <code>.stagekitwp-cast-entry</code> styling retained via generic theme tokens.</li><li><strong>4.5.0</strong> &#8212; Removed <code>[stagekitwp_cast]</code> and <code>[stagekitwp_season_cast]</code>. Merged <code>[stagekitwp_sponsor_slider]</code> into <code>[stagekitwp_sponsors layout=&quot;slider&quot;]</code> (old tag kept as alias). Removed 3 BB modules (stagekitwp-cast, stagekitwp-season-cast, stagekitwp-sponsor-slider). Updated Sponsors BB module with layout + slider fields. Plugin now ships 16 shortcodes, 16 Gutenberg blocks, 14 BB modules.</li></ul><h3>4.4.x &#8212; Shortcode Cleanup</h3><ul><li><strong>4.4.0</strong> &#8212; Removed deprecated shortcodes: <code>[stagekitwp_current_season]</code>, <code>[stagekitwp_season]</code>, <code>[stagekitwp_season_banner]</code>, <code>[stagekitwp_season_images]</code>. Replaced by <code>[stagekitwp_seasons which=&quot;current&quot;]</code> and <code>[stagekitwp_seasons layout=&quot;field&quot;]</code>. Removed 4 Gutenberg block configs and 4 BB modules. Instructions page renumbered 1&#8211;19. Season Display Options tab now shows which shortcodes use it.</li></ul><h3>4.1.x &#8212; Plugin Details &amp; Updater Polish</h3><ul><li><strong>4.1.2</strong> &#8212; Banner and icon added to the plugin details popup: stage with red curtains/gold valance (banner) and comedy/tragedy masks (icon).</li><li><strong>4.1.1</strong> &#8212; Added Installation, FAQ, Screenshots, and Other Notes tabs to the plugin details popup; full shortcode reference in Other Notes.</li><li><strong>4.1.0</strong> &#8212; Plugin details popup: full description and changelog replacing placeholder text; WP/PHP version fields populated; author linked to hsctjourney.com.</li></ul><h3>4.0.x &#8212; Tickets Overhaul &amp; Updater</h3><ul><li><strong>4.0.9</strong> &#8212; All URLs upgraded to https://hsctjourney.com.</li><li><strong>4.0.8</strong> &#8212; Spotlight hero text invisible (blue-on-blue) fixed; tile hover blur removed.</li><li><strong>4.0.7</strong> &#8212; Spotlight tiles now have card background at rest; dark banner buttons use WCAG-AA colour with luminance guard.</li><li><strong>4.0.6</strong> &#8212; PHP-level Customizer colour validation prevents black-on-black cards in dark mode.</li><li><strong>4.0.5</strong> &#8212; Updater slug fix: &#8220;invalid plugin slug&#8221; resolved.</li><li><strong>4.0.4</strong> &#8212; Self-hosted plugin updater with JSON manifest and one-click updates.</li><li><strong>4.0.3</strong> &#8212; Colour picker clear (&times;) button on all Display Options fields.</li><li><strong>4.0.2</strong> &#8212; Tickets CSS-var-first architecture; no inline style= colour attributes.</li><li><strong>4.0.1</strong> &#8212; Global link-colour bleed fixed; is_admin() guards added.</li><li><strong>4.0.0</strong> &#8212; [stagekitwp_tickets] Display Options integration and full dark/light mode support.</li></ul><h3>3.9.x &#8212; Audit, Blocks, BB Modules &amp; Ticket Layouts</h3><ul><li><strong>3.9.9</strong> &#8212; Full visual overhaul of all five ticket layouts; CSS delivery fixed.</li><li><strong>3.9.6</strong> &#8212; Canonical show slot sort order (Fall&#8594;Winter&#8594;Spring).</li><li><strong>3.9.5</strong> &#8212; Instructions page: all shortcodes documented.</li><li><strong>3.9.4</strong> &#8212; Five ticket layouts: banner, cards, table, minimal, spotlight.</li><li><strong>3.9.3</strong> &#8212; BB module icons: 21 bespoke SVGs.</li><li><strong>3.9.2</strong> &#8212; Full 1:1:1 shortcode / block / BB-module parity; 18 new BB modules.</li><li><strong>3.9.1</strong> &#8212; StageKitWP_Core singleton class restored.</li><li><strong>3.9.0</strong> &#8212; Full expert audit: security, blocks, BB loader, REST API for all CPTs.</li></ul><h3>3.8.x &#8212; Production Release &amp; Dark/Light Mode</h3><ul><li><strong>3.8.2</strong> &#8212; PHP 5.6 compatibility: arrow functions replaced.</li><li><strong>3.8.1</strong> &#8212; Unscoped CSS selectors fixed; CF7 light-mode labels restored.</li><li><strong>3.8.0</strong> &#8212; Production release: full dark/light mode, Display Options pickers, admin dashboard.</li></ul><h3>3.7.x &#8212; Theme Integration &amp; Dashboard</h3><ul><li><strong>3.7.22</strong> &#8212; Dark Mode status panel; side-by-side Light/Dark colour pickers.</li><li><strong>3.7.21</strong> &#8212; theme-integration.php: CSS variable bridge for TM Theme toggle.</li><li><strong>3.7.20</strong> &#8212; Duplicate admin hook fixed; conditional asset loading; dashboard page.</li><li><strong>3.7.19</strong> &#8212; [stagekitwp_landingpage] Gutenberg block with live field management.</li></ul><h3>3.x &#8212; Season Builder &amp; Shortcode Foundation</h3><ul><li><strong>3.4</strong> &#8212; [stagekitwp_auditions] shortcode added.</li><li><strong>3.0</strong> &#8212; Season Builder first release; Awards CPT.</li><li><strong>2.8</strong> &#8212; Security: escaping, CSS sanitization, cast image fixes.</li><li><strong>2.0</strong> &#8212; Major release: consolidated shortcodes, sample content.</li><li><strong>1.0</strong> &#8212; Initial release: 10 CPTs, core shortcodes.</li></ul>',
            'installation' => isset( $manifest->sections->installation )
                                ? $manifest->sections->installation
                                : '<h4>Requirements</h4><ul><li>WordPress 6.8 or higher</li><li>PHP 7.4 or higher</li><li>No required third-party plugins (Beaver Builder and StageKitWP Theme are optional enhancements)</li></ul><h4>Installation</h4><ol><li>Upload the <code>stagekitwp-core</code> folder to <code>/wp-content/plugins/</code>, or install via <strong>Plugins &rarr; Add New &rarr; Upload Plugin</strong>.</li><li>Activate the plugin from <strong>Plugins &rarr; Installed Plugins</strong>.</li><li>Navigate to <strong>StageKitWP &rarr; Dashboard</strong> to confirm the plugin is running.</li><li>Use <strong>StageKitWP &rarr; Season Builder</strong> to create your first season, shows, and cast.</li><li>Add shortcodes (e.g. <code>[stagekitwp_tickets]</code>, <code>[stagekitwp_shows]</code>) to any page or post. All shortcodes are also available as Gutenberg blocks under the <em>StageKitWP</em> block category.</li></ol><h4>Optional: StageKitWP Theme</h4><p>Install the companion <strong>StageKitWP Theme</strong> to unlock the full dark/light mode toggle. Once active, every shortcode automatically participates in the site-wide colour mode switch, and Display Options shows side-by-side Light &#9728; / Dark &#127769; colour pickers on every tab.</p><h4>Optional: Beaver Builder</h4><p>All 14 content shortcodes have matching Beaver Builder modules that appear automatically in the <em>StageKitWP</em> module group. No configuration required beyond activating the plugin.</p><h4>Self-hosted Updates</h4><p>Updates are delivered via a JSON manifest at <code>https://hsctjourney.com/updates/stagekitwp/stagekitwp.json</code>. New versions appear on <strong>Dashboard &rarr; Updates</strong> and install with one click. Use the <strong>Force update check</strong> link on the Plugins screen to check immediately.</p>',
            'faq'          => isset( $manifest->sections->faq )
                                ? $manifest->sections->faq
                                : '<h4>Does this work with any WordPress theme?</h4><p>Yes. All shortcodes render correctly with any theme. The dark/light mode toggle and the side-by-side colour pickers in Display Options require the companion <strong>StageKitWP Theme</strong> to be active.</p><h4>Do I need Beaver Builder?</h4><p>No. Beaver Builder is entirely optional. All features are accessible via shortcodes or Gutenberg blocks. Beaver Builder modules are registered automatically if BB is active.</p><h4>How do I set the current season?</h4><p>Open <strong>StageKitWP &rarr; Season Builder</strong>, select the season, and check the <em>Current Season</em> flag on the Season tab. Only one season should be marked current at a time. Shortcodes that use <code>which="current"</code> read this flag.</p><h4>Tickets are not showing &#8212; what is wrong?</h4><p>The <code>[stagekitwp_tickets]</code> shortcode only displays shows that have a <em>Tickets URL</em> saved in Season Builder. Shows with no ticket URL are silently skipped. Check the Show form in Season Builder to confirm the URL is set.</p><h4>Why does the "View details" popup show outdated information?</h4><p>Plugin details are cached in a WordPress transient for 12 hours. Use the <strong>Force update check</strong> link on the Plugins screen to flush the cache immediately.</p><h4>How do I add screenshots to the details popup?</h4><p>Upload JPEG or PNG images to <code>https://hsctjourney.com/updates/stagekitwp/</code> and reference them in the <code>sections.screenshots</code> field of <code>stagekitwp.json</code>.</p><h4>Can I override colours without the StageKitWP Theme?</h4><p>Yes. <strong>StageKitWP &rarr; Display Options</strong> lets you set background, text, border, heading, and button colours for each shortcode independently of the active theme. The dark colour pickers are only visible when the TM Theme switcher is enabled.</p><h4>What is the Season Builder?</h4><p>A single admin screen that lets you create and edit an entire season in one place: season metadata, all shows in the season, cast rosters, award entries, and media uploads. It replaces the need to edit each CPT post individually.</p><h4>Is the plugin compatible with PHP 8.x?</h4><p>Yes. The plugin targets PHP 7.4 as its minimum and has been tested on PHP 8.1 and 8.2. Arrow functions and named arguments are not used; all syntax is PHP 7.4-compatible.</p><h4>How does the self-hosted updater work?</h4><p>The updater checks <code>https://hsctjourney.com/updates/stagekitwp/stagekitwp.json</code> every 12 hours and compares the <code>version</code> field against the installed version. When a newer version is found, a standard WordPress update notification appears. WordPress then downloads from the <code>download_url</code> in the JSON and installs normally.</p>',
            'screenshots'  => isset( $manifest->sections->screenshots )
                                ? $manifest->sections->screenshots
                                : '<ol><li><img src="https://hsctjourney.com/updates/stagekitwp/screenshots/screenshot-1.jpg" alt="Admin Dashboard" /><p><strong>Admin Dashboard</strong> &#8212; Theme &amp; dark mode status panel with quick-access card grid.</p></li><li><img src="https://hsctjourney.com/updates/stagekitwp/screenshots/screenshot-2.jpg" alt="Season Builder" /><p><strong>Season Builder</strong> &#8212; Single-screen editor for season, shows, cast, awards, and media.</p></li><li><img src="https://hsctjourney.com/updates/stagekitwp/screenshots/screenshot-3.jpg" alt="Display Options" /><p><strong>Display Options</strong> &#8212; Side-by-side Light / Dark colour pickers on every tab.</p></li><li><img src="https://hsctjourney.com/updates/stagekitwp/screenshots/screenshot-4.jpg" alt="Ticket layouts" /><p><strong>[stagekitwp_tickets] layouts</strong> &#8212; Banner, Cards, Table, Minimal, and Spotlight variants in light mode.</p></li><li><img src="https://hsctjourney.com/updates/stagekitwp/screenshots/screenshot-5.jpg" alt="Dark mode" /><p><strong>Dark mode</strong> &#8212; All shortcodes participate in the site-wide dark/light toggle.</p></li><li><img src="https://hsctjourney.com/updates/stagekitwp/screenshots/screenshot-6.jpg" alt="Gutenberg blocks in editor" /><p><strong>Gutenberg blocks</strong> &#8212; StageKitWP blocks in the block editor with live shortcode preview.</p></li></ol>',
            'other_notes'  => isset( $manifest->sections->other_notes )
                                ? $manifest->sections->other_notes
                                : '<h3>Requirements &amp; Compatibility</h3><ul><li><strong>WordPress:</strong> 6.8 minimum &mdash; tested on 6.8, 6.8.1, 6.8.2 (latest)</li><li><strong>PHP:</strong> 7.4 minimum &mdash; tested on 7.4, 8.0, 8.1, 8.2</li><li><strong>MySQL/MariaDB:</strong> standard WordPress minimum</li><li><strong>StageKitWP Theme:</strong> optional &#8212; required for dark/light mode toggle and side-by-side Display Options pickers</li><li><strong>Beaver Builder:</strong> optional &#8212; 14 BB modules register automatically when BB is active</li><li><strong>Contact Form 7:</strong> compatible &#8212; CF7 forms inside stagekitwp-themed pages fully support both light and dark modes</li></ul><h4>REST API</h4><p>All 10 Custom Post Types have <code>show_in_rest: true</code>. Gutenberg block dropdowns use the WP REST API (<code>/wp/v2/</code>) to populate show and season selectors. REST requests are authenticated via the <code>X-WP-Nonce</code> header and the <code>stagekitwpBlocksData.restNonce</code> global injected by the plugin.</p><h4>Known Conflicts</h4><ul><li>Global CSS resets that set <code>a { color }</code> without scoping may conflict with ticket layout button colours. Scope such rules to content zones or use the Display Options colour pickers to override.</li><li>Beaver Builder&#8217;s &#8220;Global Styles&#8221; link colour can override shortcode link colours on BB-built pages. Use Display Options to pin the desired colour.</li></ul><h3>Full Shortcode Reference</h3><h4>[stagekitwp_tickets]</h4><p>Display ticket purchase links for shows and seasons in the current season.</p><p><code>layout</code> (banner|cards|table|minimal|spotlight, default: banner) &bull; <code>show_limit</code> (int) &bull; <code>show_image</code> (bool) &bull; <code>show_dates</code> (bool) &bull; <code>show_genre</code> (bool) &bull; <code>label_season</code> (string) &bull; <code>label_show</code> (string) &bull; <code>button_text</code> (string)</p><p>Example: <code>[stagekitwp_tickets layout="spotlight" show_image="true"]</code></p><h4>[stagekitwp_landingpage]</h4><p>Full show landing page: show name, image, director, cast, synopsis, dates, tickets, program PDF. Also available as a dedicated Gutenberg block with live field management.</p><p><code>show_id</code> (int, required) &bull; <code>field_list</code> (comma-separated) &bull; <code>castcols</code> (1&#8211;6) &bull; <code>urlbutton</code> (bool) &bull; <code>buttonformat</code> (default|modern|minimal|outline|gradient|prominent|success|ghost|glass)</p><h4>[stagekitwp_auditions]</h4><p>Upcoming auditions sorted by date. <code>days_past</code> (int, default: 7)</p><h4>[stagekitwp_shows]</h4><p>Shows from selected season(s). <code>season_id</code> &bull; <code>which</code> (all|current|next|current_and_next) &bull; <code>exclude</code> (fields)</p><h4>[stagekitwp_season_shows]</h4><p>Season banner + show grid. <code>layout</code> (spotlight|cards) &bull; <code>season_id</code> &bull; <code>which</code> (current|next|all) &bull; <code>show_auditions</code> (bool)</p><h4>[stagekitwp_seasons]</h4><p>Season cards or single-field output. <code>layout</code> (cards|field) &bull; <code>which</code> (all|current|upcoming|next|past) &bull; <code>season_id</code> &bull; <code>field</code> (name|start_date|end_date|dates_range|status|description|tickets_url|tickets_link|image_front|image_back|social_banner|sm_square|sm_portrait) &bull; <code>show_image/name/status/dates/tickets/description</code> (bool)</p><h4>[stagekitwp_show_cast]</h4><p>Cast for a specific show. <code>show_id</code> (int, required)</p><h4>[stagekitwp_board_members]</h4><p>Board member grid. Display Options: bg, text, border, heading colours.</p><h4>[stagekitwp_sponsors]</h4><p>Sponsor grid or Swiper carousel. <code>layout</code> (grid|slider, default: grid). Grid: <code>show_name/company/logo/website</code> (bool). Slider: <code>slides_visible</code> (int) &bull; <code>autoplay</code> (bool) &bull; <code>speed</code> (ms). Old <code>[stagekitwp_sponsor_slider]</code> tag still works as an alias for <code>layout=&quot;slider&quot;</code>.</p><h4>[stagekitwp_advertisers]</h4><p>Advertiser listing. <code>category</code> (string) for filtering by type.</p><h4>[stagekitwp_contributors]</h4><p>Contributor/donor acknowledgement list.</p><h4>[stagekitwp_testimonials]</h4><p>Audience reviews with star ratings. Slick slider loaded on demand.</p><h4>[stagekitwp_awards]</h4><p>Awards and nominations table by category.</p><h4>[stagekitwp_venues]</h4><p>Venue cards with address, phone, website, and Google Maps link.</p><h4>[stagekitwp_programs]</h4><p>Program PDF viewer with PDF.js (loaded on demand). <code>show_id</code> (int, required)</p>'
        ),
    );
}
add_filter( 'plugins_api', 'stagekitwp_updater_plugin_info', 10, 3 );

// ---------------------------------------------------------------------------
// 4. Bust cache after a successful update
// ---------------------------------------------------------------------------

/**
 * After the plugin is updated, delete the cached manifest so the next check
 * fetches fresh data (otherwise WP would still see the old version as current).
 *
 * @param  WP_Upgrader $upgrader
 * @param  array       $hook_extra
 */
function stagekitwp_updater_purge_cache( $upgrader, $hook_extra ) {
    if (
        isset( $hook_extra['type'], $hook_extra['action'] ) &&
        'plugin' === $hook_extra['type'] &&
        'update'  === $hook_extra['action'] &&
        isset( $hook_extra['plugins'] ) &&
        in_array( 'stagekitwp-core/stagekitwp-plugin.php', $hook_extra['plugins'], true )
    ) {
        delete_transient( STAGEKITWP_UPDATE_TRANSIENT );
    }
}
add_action( 'upgrader_process_complete', 'stagekitwp_updater_purge_cache', 10, 2 );

// ---------------------------------------------------------------------------
// 5. "Force update check" action link on Plugins screen
// ---------------------------------------------------------------------------

/**
 * Add a "Force update check" link under the plugin description so you can
 * trigger a fresh manifest fetch without waiting for the 12-hour cache.
 *
 * @param  array $links
 * @return array
 */
function stagekitwp_updater_action_links( $links ) {
    $url = wp_nonce_url(
        add_query_arg(
            array(
                'stagekitwp_force_update_check' => '1',
                'plugin'                => 'stagekitwp-core/stagekitwp-core.php',
            ),
            admin_url( 'plugins.php' )
        ),
        'stagekitwp_force_update_check'
    );
    $links[] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Force update check', 'stagekitwp-core' ) . '</a>';
    return $links;
}
add_filter( 'plugin_action_links_stagekitwp-core/stagekitwp-core.php', 'stagekitwp_updater_action_links' );

/** Handle the force-check request. */
function stagekitwp_updater_handle_force_check() {
    $force_check = isset( $_GET['stagekitwp_force_update_check'] ) ? sanitize_text_field( wp_unslash( $_GET['stagekitwp_force_update_check'] ) ) : '';
    if ( '' === $force_check ) { return; }
    if ( ! current_user_can( 'update_plugins' ) )  { return; }
    check_admin_referer( 'stagekitwp_force_update_check' );

    delete_transient( STAGEKITWP_UPDATE_TRANSIENT );   // bust our cache
    delete_site_transient( 'update_plugins' ); // bust WP's cache
    wp_update_plugins();                        // trigger fresh check now

    wp_safe_redirect( add_query_arg( array( 'stagekitwp_update_checked' => '1' ), admin_url( 'plugins.php' ) ) );
    exit;
}
add_action( 'admin_init', 'stagekitwp_updater_handle_force_check' );

/** Show a notice after the force-check completes. */
function stagekitwp_updater_force_check_notice() {
    $update_checked = isset( $_GET['stagekitwp_update_checked'] ) ? sanitize_text_field( wp_unslash( $_GET['stagekitwp_update_checked'] ) ) : '';
    if ( '' === $update_checked ) { return; }

    $manifest = stagekitwp_updater_fetch_manifest();
    $msg = $manifest
        ? sprintf(
            /* translators: %s: version number */
            esc_html__( 'StageKitWP Core update check complete. Latest available version: %s', 'stagekitwp-core' ),
            '<strong>' . esc_html( $manifest->version ) . '</strong>'
          )
        : esc_html__( 'StageKitWP Core update check complete. Could not reach the update server — check that https://raw.githubusercontent.com/HuwEvans/StageKitWP/StageKitWP/dist/stagekitwp-core/stagekitwp-core.json is accessible.', 'stagekitwp-core' );

    echo '<div class="notice notice-success is-dismissible"><p>' . $msg . '</p></div>';
}
add_action( 'admin_notices', 'stagekitwp_updater_force_check_notice' );
