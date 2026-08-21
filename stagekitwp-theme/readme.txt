=== StageKitWP Theme ===
Contributors: StageKitWP Developer
Requires at least: 6.3
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 3.1.0
License: GNU General Public License v2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A highly specialized, high-conversion theme framework custom-engineered for
independent theatres, playhouses, and performance venues.

== Description ==

StageKitWP Theme is a purpose-built WordPress theme for theatre companies
and performance venues. It includes:

* Season grid homepage with Now Playing and Coming Soon sections
* Hero banner with image, desktop video, and optional mobile video support
* Hero CTA placement controls with corners, edges, center, safe-area, and padding options
* Dark / Light mode toggle with full-coverage CSS + JS inline patching
* Dual logo support (separate light and dark logos with auto-invert fallback)
* Live Customizer preview via postMessage for all colour and layout controls
* Term badge display with configurable position (top-bar, over-image, inside-card)
* Site tagline with configurable position (below-logo, beside-logo, header-end)
* Testimonials slider section
* News & Audition Updates feed section
* Footer with three widget columns and a socket bar
* Notification bar with configurable message and link
* Custom colour palette with dark mode overrides via Customizer
* Advertiser/sponsor widget with logo tiles
* Social icons widget

== Installation ==

1. Upload the `stagekitwp-theme` folder to the `/wp-content/themes/` directory.
2. Activate the theme through the Appearance > Themes screen in WordPress.
3. Navigate to Appearance > Customize to configure colours, logos, and layout options.
4. Add widgets to the Footer - Column 1, 2, 3 and Footer - Bottom Socket Bar sidebars.
5. Assign menus to the Primary Navigation and Footer Navigation locations.

== Frequently Asked Questions ==

= Does this theme require any plugins? =

The theme is designed to work with the StageKitWP Core plugin for season and show
management (custom post types, meta boxes). It functions as a general theme
without it, but season grid sections will be empty.

= How do I set up the dark mode toggle? =

The dark/light mode toggle button appears automatically in the bottom-right corner
of every page. Users' preferences are saved to localStorage. You can set the
default mode under Appearance > Customize > Global Color Mode.

= How do I add a dark logo? =

Go to Appearance > Customize > Branding & Colour Palette and upload a separate
dark mode logo. If none is uploaded, the light logo is auto-inverted via CSS filter.

== Changelog ==

= 3.1.0 =
* Release synchronization with StageKitWP Core plugin 5.2.0 (media picker fixes on admin hub screens, Documentation link fix).
* Minor version bump; no theme-specific functional changes in this release.

= 2.2.26 =
* Release synchronization with StageKitWP Core plugin 4.6.17 security/standards remediation.
* Hardened admin output escaping in dashboard panels and improved nonce/input handling in theme dashboard actions.
* Replaced deprecated page lookup helper with query-based page resolution.
* Version bump to 2.2.26.

= 2.2.25 =
* Release sync update for the latest StageKitWP Core plugin patch.
* Updated theme version metadata and internal cache-busting version constant.
* Added release-note entry for updated 404.php template.
* Version bump to 2.2.25.

= 2.2.24 =
* Added hero CTA placement controls in the Customizer, including content, corners, edges, center, adjustable media padding, and an optional safe-area mode.
* Added an optional mobile hero video upload for screens 768px and below, with Customizer documentation for recommended dimensions on both desktop and mobile hero videos.
* Version bump to 2.2.24.

= 2.2.23 =
* Fixed: the homepage "News & Audition Updates" carousel could push the page into horizontal left-right scrolling on some content/viewport combinations. Added min-width:0 to the carousel's clipping wrapper (a flex-item overflow fix) so it stays within the page bounds.
* Version bump to 2.2.23.

= 2.2.22 =
* New "Site Max Width (px)" Customizer setting in Theatre Global Elements — controls the max width of the header, footer, and page content containers site-wide (default 1200, range 960-1920).
* Live preview updates instantly without a page reload.
* Version bump to 2.2.22.

= 2.2.21 =
* Review Alignment setting now includes a new "Alternating (left, right)" choice, cycling left → right → repeat (no center), in addition to the existing "Alternating (right, left, center)" choice.
* Version bump to 2.2.21.

= 2.2.20 =
* Homepage Testimonials Customizer section now supports the plugin's new Per Show Slider display mode: one show's card at a time in a Slick carousel with arrows, dots, adaptive height, and autoplay.
* No new settings needed — reuses the Reviews Per Show and Review Alignment settings added in 2.2.19.
* Version bump to 2.2.20.

= 2.2.19 =
* Homepage Testimonials Customizer section now supports the plugin's new Per Show display mode.
* New settings: Reviews Per Show (1-20, default 4) and Review Alignment (Left/Center/Right/Alternating, default Left), both used only when Display Mode is Per Show.
* Version bump to 2.2.19.

= 2.2.18 =
* Release metadata sync for the Season Builder testimonials/media update shipped with StageKitWP plugin 4.6.7.
* Bumped theme version and cache constant to 2.2.18.

= 2.2.17 =
* Added a new StageKitWP Posts pattern set for Gutenberg Query Loop layouts: grid, spotlight, full post, summary list, and text-over-graphic.
* Added theatre-specific Query Loop patterns for shows, seasons, and venues.
* Version bump to 2.2.17.

= 2.2.16 =
* Added Theatre Global Elements controls to configure Donate link URL and auto-inserted main-menu Donate position from enumerated menu items.
* Donate URL behavior now preserves admin-defined custom menu links while still replacing legacy placeholder Donate links.
* Added a dedicated TM Donate widget that reuses the global Donate image + URL and can be placed in footer widget areas.
* Main menu Donate fallback insertion now works on existing menus and honors Customizer position selections.
* Version bump to 2.2.16.

= 2.2.15 =
* Notification bar countdown copy now uses a structured label + content layout so wrapped text stays aligned on the right.
* Small-screen fallback now centers the notification text cleanly when the available width is too narrow for the inline layout.
* Version bump to 2.2.15.

= 2.2.14 =
* Search results template now uses theme-aware token styling instead of hardcoded light-mode colours.
* Improved dark-mode readability for search cards, borders, excerpt text, and relevance badges.
* Version bump to 2.2.14.

= 2.2.13 =
* Frontend search now includes StageKitWP custom-field and taxonomy content, improving discovery for CPT-driven pages.
* Search SQL hardened for SQLite-backed local environments.
* Added weighted relevance scoring so stronger title matches rank above excerpt, content, meta, and taxonomy-only matches.
* Search results template now displays a visible relevance label and numeric score.
* Version bump to 2.2.13.

= 2.1.8 =
* SECURITY: Added esc_url() to thumbnail URL in single-shows.php CSS inline style (XSS).
* SECURITY: Added current_user_can() capability check before nonce verify in dashboard POST handler (privilege bypass).
* SECURITY: Added esc_html() to get_the_date() in front-page.php and index.php.
* SECURITY: Added esc_html() to wp_trim_words(get_the_excerpt()) in front-page.php.
* SECURITY: Whitelist-validated display_style in widget update() (was sanitize_text_field only).
* SECURITY: Validated localStorage value before use in color-mode-switcher.js (must be 'dark' or 'light').
* SECURITY: Added esc_url() to home_url() in single-shows.php.
* SECURITY: Added ABSPATH direct-access guards to all 13 inc/, widget, functions.php, and template-parts/ files.

= 2.1.7 =
* Notification Bar Front-End Live Preview in Theme Dashboard now shows a live ticking countdown instead of static placeholder text.
* Graceful empty-state when no target date found; invalid date shows inline error.
* Added customizer-preview.js bindings for stagekitwp_enable_countdown, stagekitwp_notification_alignment, and stagekitwp_next_show_timestamp (all had postMessage transport but no JS handler).

= 2.1.6 =
* Hamburger menu icon set to pure white (#ffffff) in dark mode for maximum contrast on any header background colour.
* Header JS patcher now uses setProperty('background', …, 'important') to beat inline style shorthand.
* restoreEl() clears !important priority via removeProperty() before restoring original values.
* CSS dark mode rule adds background shorthand override alongside background-color.
* Corrected stagekitwp_color_header_bg_dark theme mod back to #1e1e1e (was #930e00 from testing).

= 2.1.5 =
* Complete dark mode colour mapping overhaul: all 14 CSS tokens now driven by Customizer dark settings, zero hardcoded hex.
* JS DARK map replaced with buildDarkMap() reading live CSS vars after class is applied.
* Fixed PHP context bug preventing classList.add(stagekitwp-dark-mode) from emitting.
* Fixed header background inconsistency between CSS rule and token block.

= 2.1.4 =
* Fixed mobile dark mode: hamburger (☠), submenu arrows, site title link all invisible due to hardcoded inline color: #111111 / #444444.
* Fixed site header background not switching in dark mode (inline style overriding CSS).
* Nav menu colours now use CSS variables throughout.
* Dark mode CSS block extended with mobile menu, header, and submenu rules.

= 2.1.3 =
* Fixed Customizer dark colour pickers not updating the preview in real time.
* Added isDark(), bindDarkColor(), applyDarkVars(), applyLightVars() to customizer-preview.js.
* All zone DOM helpers now have light + dark variants guarded by isDark().
* stagekitwp_color_mode binding calls applyDarkVars/applyLightVars on switch.

= 2.1.1 =
* Fixed dark mode text colour on regular pages — removed hardcoded inline colours from page.php so entry-title and entry-content now inherit correctly.
* Dark mode CSS variable block now reads from Customizer dark colour settings.
* Added CSS rules for .entry-title and .entry-content using CSS variables for both modes.

= 2.1.0 =
* Light + dark colour pairs in Customizer — each row shows both pickers side by side.
* Dark mode defaults pre-populated from the JS DARK colour map.
* Brightness warning for dark-mode background colours above 25% luminance.
* Added 404, search, archive, comments templates.
* Added readme.txt, $content_width, required PHP/WP version headers.
* Version bump to 2.1.0.

= 1.0.0 =
* Initial release.

== Credits ==

* Based on Underscores (https://underscores.me/), (C) 2012-2024 Automattic, Inc.
  Licensed under GNU General Public License v2 or later.

== License ==

StageKitWP Theme is licensed under the GNU General Public License v2 or later.
http://www.gnu.org/licenses/gpl-2.0.html
