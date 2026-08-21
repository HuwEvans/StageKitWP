# Theatre Manager - Changelog

## [3.8.2] - 2026-06-09

### Fixed
- **Show Search Error Handling**: Fixed preview search functionality with comprehensive error handling
  - Added robust error handling for REST API fetch with proper error states
  - Handle both REST API response formats (title.rendered and title field)
  - Add graceful fallback if show CPT doesn't have show_in_rest enabled
  - Display error messages in both sidebar and preview with helpful text
  - Add loading indicator with "Loading shows..." message while fetching
  - Add "no shows available" message when list is empty
  - Add "no results" message when search finds nothing
  - Prevent undefined property access errors (show.title.rendered)
  - Log detailed error messages to browser console for debugging
- All search interactions now provide clear feedback to user

## [3.8.1] - 2026-06-09

### Added
- **Interactive Show Selection & Color Customization** (Landing Page Block):
  - **Enhanced Show Selection**: Prominent interactive show search directly in block preview with real-time results
  - **"Change Show" Button**: Quick way to modify show selection without reopening sidebar
  - **Color Customization Panel**: Four color pickers in inspector:
    - **Heading Color**: Controls show name and section headings (default: #1a1a1a / Black)
    - **Text Color**: Controls all body text and descriptions (default: #333333 / Dark Gray)
    - **Accent Color**: Controls links, highlights, and interactive elements (default: #0073aa / Blue)
    - **Background Color**: Controls page background (default: #ffffff / White)
  - **Predefined Color Palettes**: Hand-selected colors for each picker ensuring excellent readability and accessibility
  - **Visual Color Preview**: Color swatches displayed in configuration summary showing selected scheme
  - **Live Color Preview**: Block preview updates in real-time as colors are adjusted

### Improved
- **Block Preview Interface**: Reorganized to show show selection prominently at top with search results
- **Interactive Search Results**: Improved hover states and click handling in results dropdown
- **Editor Styling**: Enhanced contrast, focus states, and visual hierarchy throughout inspector
- **Color Contrast**: Default color scheme ensures WCAG AAA compliance for all text/background combinations

### Technical
- Added ColorPalette component to block editor
- Extended shortcode parameters: text_color, bg_color, accent_color, heading_color
- Implemented CSS color variables for flexible styling
- Applied inline styles to shortcode output for color application
- Enhanced show_name field with h2 heading tag and proper color styling

## [3.8.0] - 2026-06-09

### Added
- **Theatre Manager Blocks Group**: Complete block editor integration for all Theatre Manager shortcodes
  - **Unified Custom Category**: All 20 blocks grouped under "Theatre Manager Blocks" category with theatre icon
  - **Visual Block Editor**: Intuitive sidebar interface for configuring all shortcode parameters
  - **Live Shortcode Preview**: Real-time display of generated shortcodes as you configure each block
  - **20 Comprehensive Blocks**:
    - **Show & Performance** (4): Landing Page, Shows List, Season Banner, Season Shows
    - **Cast & Crew** (5): Cast List, Season Cast, Cast Show Roles, Board Members, Contributors
    - **Sponsors & Supporters** (2): Sponsors, Sponsor Slider
    - **Advertising & Awards** (2): Advertisers, Awards
    - **Venue & Location** (1): Venues
    - **Audience Experience** (2): Testimonials, Tickets
    - **Season & Gallery** (2): Seasons, Season Gallery
    - **Special Content** (2): Auditions, Programs

### Technical Features
- **Unified Block Infrastructure**: Single vanilla JavaScript system supporting all 20 shortcode variations
- **Custom Category Registration**: Theatre Manager Blocks category with theatre icon and description
- **Smart Parameter Controls**:
  - Select dropdowns for shows, seasons, cast members (auto-populated from REST API)
  - Toggle switches for boolean parameters
  - Range sliders for numeric values (columns, limits)
  - Text inputs for custom field configurations
- **REST API Integration**: Automatic data fetching for all select dropdowns
- **Responsive & Mobile-Friendly**: All blocks display correctly across all device sizes
- **Backward Compatibility**: v3.7.19 landing page block continues to work alongside new unified system
- **Performance Optimized**: Efficient parameter rendering and shortcode generation

### Block Categories Explained
- **Show & Performance**: Display theatre productions and seasonal information
- **Cast & Crew**: Manage cast rosters, crew lists, and personnel
- **Sponsors & Supporters**: Showcase financial and in-kind sponsors
- **Awards**: Display recognition and achievements
- **Venue**: Theatre location and facility information
- **Audience**: Testimonials and ticket information
- **Gallery**: Season-specific photo galleries
- **Special**: Auditions, programs, and other content

## [3.7.19] - 2026-06-09

### Fixed
- Converted Gutenberg block to vanilla JavaScript (no build process required)
- Updated block.json to work without webpack transpilation
- Added REST API support to Show CPT for block search functionality
- Simplified PHP registration for direct asset enqueuing

### Added
- Show Landing Page Gutenberg block with live preview and field management

## [3.7.18] - 2026-06-08

### Documentation
- **Comprehensive tm_landingpage Shortcode Requirements**: Created comprehensive requirements documentation (`TM_LANDINGPAGE_REQUIREMENTS.md`) specifying:
  - Shortcode name, purpose, and usage
  - All parameters (show_id, field_list, castcols, urlbutton, buttonformat)
  - All 13 available fields (show_name, show_image, author, sub_authors, director, associate_director, producer, stage_manager, synopsis, show_dates, ticket_url, cast, castwithbio, venue)
  - Field display rules: each field on separate line, no plugin-specific styling, parent page formatting inheritance
  - Default output structure with headings, separators, and layout
  - Cast with bio grid responsiveness (desktop/tablet/mobile breakpoints)
  - Alignment inheritance from Gutenberg, Beaver Builder, and theme-specific classes
  - Technical implementation details and CSS class structure
- **Enhanced Shortcode Documentation**: Updated tm_landingpage.php header with detailed parameter descriptions, field list, default output structure, and alignment inheritance explanation

### Features
- All 13 fields documented with descriptions and default display format
- Clear parameter documentation for show_id, field_list, castcols, urlbutton, buttonformat
- Responsive cast grid with column-based image sizing (1-6 columns)
- Full alignment inheritance from surrounding page context
- WCAG AAA accessibility compliance with proper color contrast

## [3.7.17] - 2026-06-08

### Fixed
- **Color Contrast Accessibility**: Fixed WCAG contrast issues in button and rating components:
  - **Glass Button**: Changed from white text (#ffffff) on transparent white background to dark gray text (#333) on subtle dark overlay (rgba(0,0,0,0.05)). This ensures the button remains readable on all background colors.
  - **Empty Rating Stars**: Changed from white color with filters to dark gray (#333) at 30% opacity for better visibility and cleaner appearance.
  - These changes improve accessibility and ensure all interactive elements meet WCAG AAA contrast standards.

## [3.7.16] - 2026-06-08

### Fixed
- **Cast Image Display**: Changed cast member images from `object-fit: cover` (which cropped images) to `object-fit: contain` so the entire picture is visible within the allocated space. Added light gray background (#f0f0f0) for clean appearance when images don't fill the full aspect ratio. Images remain fully responsive and scale based on castcols parameter.

## [3.7.15] - 2026-06-08

### Fixed
- **Critical Cast Grid Layout Bug**: Fixed issue where castwithbio field was displaying only one column with very large images. The CSS rule at line 974 was using `display: flex` which was overriding the grid layout. Restored proper `display: grid` with `grid-template-columns: repeat(var(--cast-cols, 3), 1fr)` to respect the castcols parameter and display multiple columns correctly.

## [3.7.14] - 2026-06-08

### Fixed
- **Custom Post Type Capabilities**: Added `capability_type` and `map_meta_cap` to all 10 custom post types (show, cast, season, venue, award, advertiser, board_member, sponsor, testimonial, contributor). This fixes the "Sorry, you are not allowed to edit posts in this post type" error by enabling proper capability mapping for WordPress.

- **Responsive Cast Image Sizing**: Cast member photos in castwithbio field now scale responsively based on the castcols parameter:
  - Images use aspect-ratio: 3/4 (portrait) for consistent headshots
  - Desktop: Height scales from 300px (1 col) to 210px (6 cols) using formula: calc(300px - (castcols * 15px))
  - Tablet: Height scales from 270px to 200px with tighter constraints
  - Mobile: Height scales from 220px to 160px for optimal mobile viewing
  - All images remain fully responsive to container width
  - Images never exceed max constraints for performance and aesthetics

- **Castcols Parameter Now Honored at All Breakpoints**: Fixed responsive breakpoints in castwithbio field to properly respect the castcols parameter:
  - Desktop (>1024px): Displays castcols columns as specified
  - Tablet (1024px-768px): Displays min(castcols, 2) columns for readability
  - Mobile (<768px): Displays min(castcols, 1) column for mobile usability
  - Previously mobile breakpoints ignored castcols and hardcoded to single column

- **Comprehensive Field Alignment & Text Setting Inheritance**: Enhanced tm_landingpage shortcode to ensure ALL fields (show name, author, director, cast, venue, etc.) inherit alignment and text styling from the surrounding page context. All fields now respect:
  - Gutenberg block alignment settings (center/left/right/justify)
  - Beaver Builder alignment settings
  - Theme-specific alignment classes
  - All inherited text properties: font-family, font-size, color, font-weight, line-height, letter-spacing, text-transform, and more
  - Nested elements (cast lists, venue info) fully inherit parent styling
  - Images automatically center within centered content

- **Enhanced CSS Sibling Selectors**: Extended alignment detection to support:
  - Gutenberg: `has-text-align-*` and `wp-block-paragraph` classes
  - Beaver Builder: `fl-col` text alignment classes
  - Theme-specific: `centered`, `center`, and other common alignment classes
  - Support for blocks separated by horizontal rules (`<hr>`)

## [3.7.13] - 2026-06-08

### Fixed
- **Block Alignment Inheritance**: Button now respects the alignment of its parent Gutenberg block, Beaver Builder block, or any container. Changed `.tm-landingpage-wrapper` to use `text-align: inherit` instead of hardcoded center, allowing buttons to align left, center, or right based on the block's alignment setting.

## [3.7.12] - 2026-06-08

### Fixed
- **Button Centering**: Fixed issue where Get Tickets button was not centering with page content. Explicitly set `.tm-landingpage-wrapper` to `text-align: center` to ensure proper button alignment.

## [3.7.11] - 2026-06-08

### Added
- **URL Button Display**: Added `urlbutton` parameter to tm_landingpage shortcode to display ticket URLs as styled buttons.
- **Button Format Options**: Added `buttonformat` parameter with 9 comprehensive styling options (default, modern, minimal, outline, gradient, prominent, success, ghost, glass).
- **Button Styling**: Comprehensive CSS button styles with hover effects, transitions, and responsive sizing.
- **Fixed castcols Parameter**: Fixed issue where castcols parameter wasn't being passed to field rendering function for castwithbio field.

### Changed
- **Function Signature**: Updated `tm_render_landingpage_field()` to accept $atts parameter for proper parameter passing.
- **Ticket URL Rendering**: Enhanced to support both link and button display modes based on urlbutton parameter.
- **Page Alignment Support**: Landing page wrapper and field divs now inherit text-align from parent containers (WordPress center alignment, custom CSS classes, etc.), ensuring buttons conform to page alignment.
- **Button CSS**: Removed hardcoded `text-align: center` from `.tm-url-button` base class to allow proper alignment inheritance from parent containers.
- **Version Update**: Plugin version moved from 3.7.10 to 3.7.11.

### Fixed
- **Button Alignment Inheritance**: Buttons now properly respect page/container text-align properties for center, left, and right alignment without hardcoded overrides.

## [3.7.10] - 2026-06-08

### Added
- **Show Dates Rich Editor**: Show dates field in Season Builder now supports HTML formatting with TinyMCE editor for bold, italic, lists, links, etc.
- **Castwithbio Labels**: Actor names in castwithbio field now display with "Played by" prefix for better readability.

### Changed
- **Show Dates Sanitization**: Updated to use `wp_kses_post()` to allow HTML formatting in show dates.
- **Version Update**: Plugin version moved from 3.7.9 to 3.7.10.

## [3.7.9] - 2026-06-08

### Added
- **Cast Bio in Season Builder**: Added bio textarea field to cast rows in Season Builder for editing actor biographies.
- **Show Producer & Stage Manager**: Added producer and stage_manager fields to Show form in Season Builder.
- **JavaScript Templates**: Updated dynamic row creation for cast and show rows to include new fields.

### Changed
- **Season Builder Cast Form**: Updated table column widths (28%/28%/28%/16%) to accommodate bio field.
- **Data Sanitization**: Applied appropriate sanitization (textarea vs text) for new fields.
- **Version Update**: Plugin version moved from 3.7.8 to 3.7.9.

## [3.7.8] - 2026-06-08

### Added
- **Column Control Parameter**: Added `castcols` parameter to tm_landingpage shortcode for castwithbio field (range 1-6, default 3).
- **Responsive Grid Updates**: Enhanced CSS media queries with grid-column min() function for tablet breakpoint adaptation.

### Changed
- **Image Sizing**: Reduced cast image heights from 250px to 180px (desktop), 160px (tablet), 140px (mobile) for better column fit.
- **Object-fit**: Applied `object-fit: cover` for consistent aspect ratios in fixed-size containers.
- **CSS Grid Implementation**: Updated `.tm-landingpage-castwithbio` to use CSS custom property `--cast-cols` for dynamic column control.
- **Version Update**: Plugin version moved from 3.7.7 to 3.7.8.

### Documentation
- **shortcode_usage.txt**: Added castcols parameter documentation and usage examples for 2-column and 4-column layouts.

## [3.7.7] - 2026-06-08

### Added
- **Cast Biography Integration**: Added bio field to cast posts and integrated into castwithbio shortcode field.
- **Bio Display**: Bio now displays below actor name in castwithbio responsive grid with HTML preservation via `wp_kses_post()`.

### Changed
- **Cast CPT**: Added _tm_cast_bio meta field to cast post type.
- **Shortcode Output**: Enhanced castwithbio to include bio text below actor information.
- **Version Update**: Plugin version moved from 3.7.6 to 3.7.7.

## [3.7.6] - 2026-06-08

### Added
- **Castwithbio Field**: New responsive grid field for tm_landingpage shortcode displaying cast members with picture, character name, actor name, and biography.
- **CSS Grid Layout**: Implemented responsive grid with auto-fit columns, gap spacing, and proper image sizing.
- **Hard Breaks Documentation**: Added section to shortcode_usage.txt explaining how hard_breaks true/false impacts block-level vs inline display of shortcode output.

### Changed
- **Image Display Logic**: Smart conditional wrapping based on content type (image-only uses inline-block, mixed content uses centered div, text-only uses raw output).
- **CSS Custom Properties**: Implemented --cast-cols for dynamic column control in responsive layouts.
- **Responsive Breakpoints**: Added media queries at 1024px, 768px, and 480px for mobile-first design.
- **Version Update**: Plugin version moved from 3.7.5 to 3.7.6.

### Documentation
- **shortcode_usage.txt**: Added comprehensive castwithbio field documentation with parameter explanations and responsive behavior notes.

## [3.4] - 2026-05-25

### Added
- **Auditions Shortcode**: Added `TM_Auditions` / `tm_auditions` to display upcoming auditions ordered by audition date.

### Changed
- **Version Update**: Plugin version moved from 3.3 to 3.4.

## [3.3] - 2026-05-23

### Added
- **Show Auditions**: Added `Audition Date` and rich-text `Audition Details` fields to Shows.
- **Season Builder (v1.4)**: Added audition fields to the unified Show editor in the builder.

### Changed
- **Builder UI**: Renamed the `Actions` column in Cast and Awards to `Remove` so it is clearly a row-control column, not saved content.
- **Version Update**: Plugin version moved from 3.2 to 3.3.

## [3.2] - 2026-05-23

### Added
- **Season Builder (v1.2)**: Added a dedicated `Media` tab for season images, show image/program media, and cast headshots.
- **Season Builder (v1.3)**: Added the remaining Show detail fields to the builder, including sub-authors, genre, director, associate director, show dates, and synopsis.
- **Season Builder Coverage**: Added season `Current` and `Upcoming` flags to the builder so all Season updates can be completed from the same screen.

### Changed
- **Tab Save Flow**: Switching between `Details` and `Media` now saves the current builder state before changing tabs and reopens on the requested tab after save.
- **Version Update**: Plugin version moved from 3.1 to 3.2.

## [3.1] - 2026-05-23

### Added
- **Season Builder (v1.1)**: Added safe delete behavior. Shows, Cast, and Awards rows removed from the builder are moved to Trash on save.

### Changed
- **Version Update**: Plugin version moved from 3.0 to 3.1.
- **Versioning Technique (standard)**: Going forward, every release updates all of the following together:
  - Plugin header `Version` in `theatre-manager-plugin.php`
  - `THEATRE_MANAGER_VERSION` constant in `theatre-manager-plugin.php`
  - `CHANGELOG.md`
  - `RELEASE-NOTES.md`
  - `Theatre-Manager/ChangeLog.txt`
  - Packaged zip artifact (`Theatre-Manager-x.y.zip`)

## [3.0] - 2026-05-23

### Added
- **Season Builder (v1)**: New single-screen admin page to edit Season + linked Shows + Cast + Awards in one workflow.
- **Season Builder Save Flow**: Supports creating/updating related posts and preserving links (`season -> show -> cast/award`) in one save action.
- **Awards Integration**: Awards are now included in the unified builder UI with category and status constraints.

### Changed
- **Version Update**: Plugin version moved from 2.8 to 3.0.

### Notes
- **Rollback Path**: The repository keeps a 2.8 package (`Theatre-Manager-2.8.zip`) and includes rollback instructions in `ROLLBACK-TO-2.8.md`.

## [2.8] - 2026-05-23

### Fixed
- **Image Rendering**: Fixed cast images in `tm_show_cast` and `tm_season_cast` grid mode to support attachment IDs via `tm_get_image_url()`.
- **Output Escaping**: Escaped show and season title output in `tm_shows` to prevent unsafe/unexpected HTML rendering.
- **Display Options Styling**: Repaired malformed inline style generation in season shortcodes (`tm_season_cast`, `tm_season_shows`) and restored box-shadow option behavior.
- **Admin Robustness**: Sanitized and validated display-options tab query parameter before using it to build settings page/group IDs.
- **Version Consistency**: Updated plugin header and `THEATRE_MANAGER_VERSION` constant to `2.8`.

### Added
- **Awards CPT**: Added a new `award` post type linked to Shows (and indirectly Seasons) with fields for Award ID, Show ID, Award Category, Award Name, Award Recipient, and Status.
- **Awards Constraints**: Enforced allowed status values (`Nominated`, `THEA Winner`) and category values (`Musical`, `Drama`, `Comedy`) in admin save handling.

### Security
- **CSS Value Hardening**: Added `tm_sanitize_css_color()` helper and applied it to shortcode-generated CSS color values to prevent unsafe style injection from option values.

## [2.6] - 2025-11-04

### Fixed
- **Sponsors Sync Support**: Plugin now properly supports all sponsor fields synced by tm-sync 2.6
- **Shows/Seasons/Cast Display**: Updated to work with new complete field mapping from tm-sync

### Changed
- **Compatibility**: Now requires tm-sync 2.6+ for full Seasons/Shows/Cast functionality

## [2.5] - 2025-11-04

### Added
- **Attachment ID Support**: All shortcodes now support attachment IDs from tm-sync plugin for better image management
- **Image URL Helper Function**: New `tm_get_image_url()` function for backwards compatibility with both attachment IDs and direct URLs
- **Enhanced Testimonials Display**: Proper star rating display with configurable symbols (Stars, Thumbs Up, Rockets, Hearts, Theatre Masks)

### Fixed
- **Critical**: All 10 shortcodes now properly display images from SharePoint sync
- **Board Members Display**: 
  - Fixed photos not showing (was looking for wrong meta field `_tm_media_urls` instead of `_tm_photo`)
  - Fixed board member names not showing (changed `get_the_title($post)` to `get_the_title($post->ID)`)
- **Testimonials Rating Display**: Now properly shows star ratings from SharePoint
- **Image Display Chain**: Fixed image rendering in:
  - cast-shortcode.php
  - sponsors-shortcode.php
  - sponsor-slider-shortcode.php
  - advertisers-shortcode.php
  - shows-shortcode.php (2 locations)
  - tm_season_images.php
  - tm_season_banner.php
  - tm_season_shows.php
  - tm_season_cast.php
  - board-members-shortcode.php

### Changed
- **Image Handling**: All shortcodes now use `tm_get_image_url()` helper function
- **Meta Field References**: Board members shortcode updated to use correct field names
- **Display Options**: Verified all shortcodes continue to respect WordPress display options

### Technical Details
- **Files Modified**:
  - `includes/helpers.php` - Added `tm_get_image_url()` function
  - `includes/shortcodes/cast-shortcode.php` - Use helper for images
  - `includes/shortcodes/sponsors-shortcode.php` - Use helper for images
  - `includes/shortcodes/sponsor-slider-shortcode.php` - Use helper for images
  - `includes/shortcodes/advertisers-shortcode.php` - Use helper for images
  - `includes/shortcodes/shows-shortcode.php` - Use helper for images (2 places)
  - `includes/shortcodes/tm_season_images.php` - Use helper for images
  - `includes/shortcodes/tm_season_banner.php` - Use helper for images
  - `includes/shortcodes/tm_season_shows.php` - Use helper for images
  - `includes/shortcodes/tm_season_cast.php` - Use helper for images
  - `includes/shortcodes/board-members-shortcode.php` - Fixed field names and ID parameter
  - `cpt/testimonials.php` - Rating display in admin columns

- **Commits**:
  - `121430a` - Fix board-members shortcode names and photos
  - `b619df1` - Update shortcodes to support attachment IDs from tm-sync
  - And related commits from session

### Backwards Compatibility
✅ All changes maintain backwards compatibility with existing sites:
- `tm_get_image_url()` handles both attachment IDs and direct URLs
- Display options continue to control shortcode styling
- Existing posts with URL-based images continue to work

### Known Issues
- None

### Dependencies
- WordPress 5.0+
- PHP 7.4+
- Theatre Manager Sync plugin 2.5+ (for proper image syncing)

---

## [2.4] - Previous Release
See git history for details on earlier versions.
