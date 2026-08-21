# Theatre Manager v3.4 - Release Package

**Release Date:** May 23, 2026

## Installation

1. Extract `Theatre-Manager-3.4.zip` into your WordPress plugins directory
2. The folder should be named `Theatre-Manager`
3. Activate the plugin from WordPress Admin
4. Optionally: Install Theatre Manager Sync v2.6+ for SharePoint integration

## What's Included

- Complete Theatre Manager plugin v3.4
- 9 Custom Post Type definitions (Advertisers, Awards, Board Members, Cast, Contributors, Seasons, Shows, Sponsors, Testimonials)
- 15+ shortcodes for frontend display
- Admin settings and display customization options
- Full compatibility with Theatre Manager Sync v2.6+

## Key Features in v3.4

✅ **Auditions Shortcode** - New `TM_Auditions` / `tm_auditions` shortcode shows upcoming auditions ordered by audition date

## Prior Features

✅ **Season Builder (v1.4)** - Shows now include audition date and rich-text audition details in the unified builder

✅ **Show Auditions** - Added dedicated Show fields for audition date and formatted audition details

✅ **Builder Clarity** - `Actions` columns were renamed to `Remove` because they are row-control columns, not stored data

## Prior Features

✅ **Season Builder (v1.3)** - Remaining Season and Show detail fields are now editable from the builder, including season status flags and full show details

✅ **Save Before Tab Switch** - Changing between `Details` and `Media` now saves the builder first, then reopens on the requested tab

✅ **Season Builder (v1.2)** - Added a dedicated Media tab for season images, show image/program media, and cast headshots

✅ **Season Builder (v1.1)** - Removing linked rows (Shows/Cast/Awards) now moves those records to Trash on save

✅ **Season Builder (v1)** - New single admin screen to edit Season + Shows + Cast + Awards together
✅ **Linked Save Workflow** - One save action preserves season/show/cast/award relationships
✅ **Awards Management** - New Awards CPT with category/status constraints and show linkage

✅ **Security Hardening** - CSS color output sanitized for shortcode-generated style blocks
✅ **Cast Image Reliability** - Attachment-ID based cast images now render correctly in all affected shortcodes
✅ **Escaping Improvements** - Show and season titles in `tm_shows` are now safely escaped
✅ **Display Option Reliability** - Fixed malformed inline style output and box-shadow option handling
✅ **Admin Input Validation** - Display options tab query parameter is now sanitized and validated
✅ **Version Consistency** - Plugin metadata and public version constant now set to 3.4

✅ **Complete Seasons Display** - All season fields and images working properly
✅ **Complete Shows Display** - Full show details with season relationships
✅ **Complete Cast Display** - Cast members linked to shows with images
✅ **Relationship Display** - Seasons→Shows→Cast hierarchy working correctly
✅ **Image Display Fixes** - All shortcodes now properly display synced images
✅ **Board Member Display** - Fixed names and photos showing correctly
✅ **Testimonials Ratings** - Star ratings display from SharePoint data
✅ **Attachment ID Support** - Compatible with Theatre Manager Sync plugin's image management
✅ **Display Options** - All shortcodes respect WordPress styling settings

## Shortcodes Available

- `[tm_cast]` - Display cast members
- `[tm_sponsors]` - Display sponsors by level
- `[tm_sponsor_slider]` - Sponsor carousel
- `[tm_advertisers]` - Display advertisers
- `[tm_board_members]` - Display board members
- `[tm_shows]` - Display shows
- `[tm_seasons]` - Display seasons
- `[tm_contributors]` - Display contributors
- `[tm_testimonials]` - Display testimonials with ratings

Season-specific shortcodes:
- `[tm_season_cast]` - Cast for current season
- `[tm_season_shows]` - Shows for current season
- `[tm_season_banner]` - Season banner image
- `[tm_season_images]` - Season images
- `[tm_season_cast]` - Season cast table

## Requirements

- WordPress 5.0+
- PHP 7.4+
- Theatre Manager Sync v2.6+ (for complete field syncing)
- Optional: Theatre Manager Sync plugin 2.5+ for SharePoint integration

## Documentation

See `CHANGELOG.md` in the plugin folder for complete list of changes and fixes.

## Display Options

All shortcodes support WordPress customization through the admin settings:
- Background colors
- Text colors
- Border styles
- Border radius
- Shadow effects
- Grid layouts (where applicable)

## Version History

- **v3.4** (2026-05-25): Added the auditions shortcode for upcoming audition listings
- **v3.3** (2026-05-23): Season Builder v1.4 audition fields and clearer remove-column labels
- **v3.2** (2026-05-23): Season Builder v1.2/v1.3 media tab, remaining Season/Show fields, and save-before-switch tab flow
- **v3.1** (2026-05-23): Season Builder v1.1 safe-trash behavior for removed linked rows
- **v3.0** (2026-05-23): Added Season Builder v1 and integrated Awards into single-screen linked editing
- **v2.8** (2026-05-23): Security hardening, cast image fixes, escaping updates, style generation fixes, version consistency updates
- **v2.5** (2025-11-04): Image display fixes, attachment ID support, testimonials rating fix
- **v2.1** (Previous): Initial feature-complete release
