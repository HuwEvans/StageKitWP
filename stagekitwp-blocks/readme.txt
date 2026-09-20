=== StageKitWP Blocks ===
Contributors:      Huw Evans
Tags:              blocks, tabs, carousel, bookshelf, affiliate
Tested up to:      6.8
Stable tag:        1.7.0
License:           GPL-2.0-or-later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Additional Gutenberg blocks for the StageKitWP and Theatre Manager ecosystems.

== Description ==

StageKitWP Blocks adds reusable Gutenberg blocks for tabbed content, post carousels, and book displays.

The plugin registers six blocks in the StageKitWP Blocks category:

* **StageKit Tabs**: A configurable tabs container with underline, pills, cards, vertical, bubbles, accent bar, and folder styles.
* **Tab Item**: A child block for StageKit Tabs with an editable title and nested block content.
* **Post Carousel**: A dynamic carousel of published posts with category filtering, excerpts, autoplay, looping, and light or dark color modes.
* **Bookshelf Container**: A parent block that displays book items as book spines or a cover gallery, with adjustable shelf dimensions and wood themes.
* **Bookshelf Item**: An affiliate book card that can parse Amazon links, build affiliate URLs, and retrieve book metadata from public book services, with a fallback chain across sources and Concord Theatricals link detection.
* **Dark Mode Image**: A media-backed image block that switches between separate light and dark images using the Theatre Manager theme's color mode. Shared image settings include alignment, width, aspect ratio, image fit, border radius, and optional linking.

Tab color controls support active, inactive, hover, and folder-header colors. Color preset values are normalized for valid editor and frontend CSS output.

The Post Carousel uses the Slick Carousel assets supplied through jsDelivr. Bookshelf metadata lookup checks Open Library, Google Books, and UPCitemdb in turn when an ISBN is provided, merging whichever fields each source is missing (for example, using Google Books' cover art when Open Library has none, or UPCitemdb's real Amazon ASIN). Concord Theatricals play/musical links are also detected for the title and reference URL, though Concord has no public metadata API so author, description, and cover art must be added manually.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/stagekitwp-blocks` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate **StageKitWP Blocks** through the Plugins screen in WordPress.
3. Open the block inserter and choose a block from the **StageKitWP Blocks** category.

The plugin requires WordPress 6.8 or later and PHP 7.4 or later.


== Changelog ==

= 1.7.0 =
* Fixed the Bookshelf Container editor view so every Bookshelf Item is visible and editable in the shelf/cover grid, instead of being clipped inside an extra wrapper element.
* Bookshelf Item metadata lookup now merges results across Open Library, Google Books, and UPCitemdb instead of stopping at the first source, so a missing cover or author can be filled in from another source.
* Added UPCitemdb as a keyless ASIN lookup source so Amazon affiliate links can use the real ASIN for an edition instead of assuming it matches the ISBN.
* Added Concord Theatricals link detection for the Bookshelf Item block, extracting the play/musical title and building a direct reference link.
* Added a "Search Plays By Title" tool to the Bookshelf Item block that looks up a title across Concord Theatricals and the Canadian Play Outlet and fills in the title, author, description, and cover art from the selected result.

= 1.6.0 =
* Added Gutenberg inserter examples for all StageKitWP Blocks, including nested tabs and bookshelf samples.
* Added a visual-only Bookshelf Item editor preview so metadata remains in the inspector.
* Added common image settings to Dark Mode Image, including alignment, width, aspect ratio, fit, radius, and linking.
* Added light and dark image switching based on the Theatre Manager theme color mode.
* Improved tab color handling, including WordPress preset colors and transparent alpha values.

= 1.1.0 =
* Added Dark Mode Image with separate Media Library images for light and dark theme modes.
* Added StageKit Tabs and Tab Item blocks with multiple style presets and configurable colors.
* Added Post Carousel with category filtering, excerpts, autoplay, looping, and color modes.
* Added Bookshelf Container and Bookshelf Item blocks with spine and cover-gallery layouts.
* Added Amazon affiliate URL parsing and public book metadata lookup through Google Books and Open Library.
* Fixed inactive tab colors across tab style presets, including Folder Tabs.
* Added an editor tab navigation preview and normalized WordPress color preset values for the frontend.
* Fixed fully transparent eight-digit color values being applied when a visible color was selected.

= 0.1.0 =
* Initial scaffold release.

