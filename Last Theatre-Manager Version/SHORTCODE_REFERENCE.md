# Theatre Manager Shortcodes Reference

Complete documentation of all available shortcodes and their parameters.

---

## 1. [tm_auditions]

**Purpose:** Display upcoming auditions for shows, ordered by audition date (earliest first).

**Parameters:**
- `days_past` (integer, default: 7) - Number of days back to include auditions. Only shows with audition dates no older than this many days ago are displayed.

**Example Usage:**
```
[tm_auditions]
[tm_auditions days_past="14"]
```

**Display Options Affected:**
- `tm_auditions_bg_color` - Background color
- `tm_auditions_text_color` - Text color

---

## 2. [tm_season_shows]

**Purpose:** Display shows grouped by season with section headers (Current, Upcoming, Past).

**Parameters:**
- `season_id` (integer, optional) - Display only shows from a specific season. If omitted, displays shows from all seasons.
- `which` (string, default: "all") - Controls which seasons to display when `season_id` is not specified:
  - `all` - Show all seasons
  - `current` - Show only the current season (based on today's date)
  - `next` - Show the next season after the current one (or first upcoming if no current)
  - `current_and_next` - Show current season and the one following it

**Example Usage:**
```
[tm_season_shows]
[tm_season_shows season_id="177"]
[tm_season_shows which="current_and_next"]
```

**Display Organization:**
- Shows are grouped by season status: Current → Upcoming → (hard break) → Past
- Section headers display: "Current Season", "Upcoming Seasons", "Past Seasons"
- Shows within each season are grouped by time slot (Fall, Winter, Spring)

**Display Options Affected:**
- `tm_show_text_color` - Text color for show cards
- `tm_show_border_color` - Border color
- `tm_show_border_width` - Border width (pixels)
- `tm_show_radius` - Border radius (pixels)
- `tm_show_shadow` - Enable/disable shadow effect

---

## 3. [tm_seasons]

**Purpose:** Display all seasons in a table format with sortable columns.

**Parameters:**
- `exclude` (string, optional) - Comma-separated list of fields to hide. Available fields to exclude:
  - `start_date` - Season start date
  - `end_date` - Season end date
  - Other season fields as needed

**Example Usage:**
```
[tm_seasons]
[tm_seasons exclude="start_date,end_date"]
```

**Display Options Affected:**
- `tm_season_bg_color` - Background color
- `tm_season_text_color` - Text color
- `tm_season_h2_color` - Heading color

---

## 4. [TM_Current_Season] / [tm_current_season]

**Purpose:** Display only the current season (where today's date falls within the season dates).

**Parameters:** None

**Example Usage:**
```
[TM_Current_Season]
[tm_current_season]
```

**Notes:**
- Both shortcode names work identically
- Returns empty if no current season exists

**Display Options Affected:**
- `tm_season_bg_color` - Background color
- `tm_season_text_color` - Text color

---

## 5. [tm_season_cast]

**Purpose:** Display cast members for selected season(s), with optional images and grouping.

**Parameters:**
- `season_id` (integer, optional) - Display cast only for a specific season
- `show_cast_images` (boolean, default: "true") - Show/hide cast member headshots
- `cast_layout` (string, default: "grid") - Layout style: "grid" or other layout options
- `which` (string, default: "all") - When `season_id` not specified, controls season selection:
  - `all` - All seasons
  - `current` - Current season only
  - `next` - Next season only
  - `current_and_next` - Current and next season

**Example Usage:**
```
[tm_season_cast]
[tm_season_cast season_id="177"]
[tm_season_cast which="current" show_cast_images="true"]
[tm_season_cast which="current_and_next" cast_layout="grid"]
```

**Display Options Affected:**
- `tm_show_text_color` - Text color
- `tm_show_border_color` - Border color
- `tm_show_radius` - Border radius (pixels)
- `tm_show_shadow` - Enable/disable shadow effect

---

## 6. [tm_shows]

**Purpose:** Display shows from selected season(s) with detailed information.

**Parameters:**
- `exclude` (string, optional) - Comma-separated list of fields to hide from show display:
  - `genre` - Show genre
  - `director` - Director name
  - `associate_director` - Associate director name
  - Other show fields as needed
- `season_id` (integer, optional) - Display shows only from a specific season
- `which` (string, default: "all") - When `season_id` not specified, controls season selection:
  - `all` - All seasons
  - `current` - Current season only
  - `next` - Next season only
  - `current_and_next` - Current and next season

**Example Usage:**
```
[tm_shows]
[tm_shows season_id="177"]
[tm_shows exclude="genre,director"]
[tm_shows which="current" exclude="associate_director"]
```

**Display Options Affected:**
- `tm_show_text_color` - Text color
- `tm_show_border_color` - Border color
- `tm_show_border_width` - Border width (pixels)
- `tm_show_radius` - Border radius (pixels)
- `tm_show_shadow` - Enable/disable shadow effect

---

## 7. [tm_cast]

**Purpose:** Display cast members with optional filtering and grouping.

**Parameters:**
- `exclude` (string, optional) - Comma-separated list of fields to hide:
  - `picture` - Cast member headshot
  - `actor_name` - Actor name
  - Other cast fields as needed
- `show_id` (integer, optional) - Display cast only for a specific show
- `group_by` (string, optional) - Grouping strategy (e.g., "show")
- `orderby` (string, optional) - Field to sort by (default: "title")
- `order` (string, optional) - Sort order: "ASC" or "DESC"

**Example Usage:**
```
[tm_cast]
[tm_cast show_id="123"]
[tm_cast exclude="picture"]
[tm_cast show_id="123" orderby="title" order="ASC"]
```

---

## 8. [tm_show_cast]

**Purpose:** Display cast members for a specific show.

**Parameters:**
- `show_id` (integer, required) - ID of the show to display cast for

**Example Usage:**
```
[tm_show_cast show_id="123"]
```

**Display:** Shows cast member pictures (if available), character name, and actor name.

---

## 9. [tm_season_banner]

**Purpose:** Display season banner/header image.

**Parameters:**
- `season_id` (integer, required) - ID of the season to display banner for

**Example Usage:**
```
[tm_season_banner season_id="177"]
```

**Notes:**
- Displays the social banner image for the season
- Returns empty if no banner is set

---

## 10. [tm_season_images]

**Purpose:** Display all season images (social banner, front, and back cover).

**Parameters:**
- `season_id` (integer, required) - ID of the season to display images for

**Example Usage:**
```
[tm_season_images season_id="177"]
```

**Image Display:**
- Social banner displayed full-width
- Front and back images displayed at 45% width side-by-side

---

## 11. [tm_sponsors]

**Purpose:** Display sponsor listings, optionally grouped by sponsorship level.

**Parameters:**
- `show_name` (boolean, default: "true") - Display sponsor name
- `show_company` (boolean, default: "true") - Display company name
- `show_logo` (boolean, default: "true") - Display sponsor logo
- `show_website` (boolean, default: "true") - Display website link

**Example Usage:**
```
[tm_sponsors]
[tm_sponsors show_logo="true" show_website="false"]
```

**Display Options Affected:**
- `tm_sponsor_bg_color` - Background color
- `tm_sponsor_text_color` - Text color
- `tm_sponsor_border_color` - Border color
- `tm_sponsor_border_width` - Border width (pixels)
- `tm_sponsor_radius` - Border radius (pixels)
- `tm_sponsor_shadow` - Enable/disable shadow effect

---

## 12. [tm_sponsor_slider]

**Purpose:** Display sponsors in an automated carousel/slider format (uses Slick Slider).

**Parameters:** None (uses all sponsors)

**Example Usage:**
```
[tm_sponsor_slider]
```

**Display Options Affected:**
- `tm_sponsor_bg_color` - Background color
- `tm_sponsor_border_color` - Border color
- `tm_sponsor_border_width` - Border width (pixels)
- `tm_sponsor_radius` - Border radius (pixels)
- `tm_sponsor_shadow` - Enable/disable shadow effect

---

## 13. [tm_testimonials]

**Purpose:** Display testimonials in an automated carousel using Slick Slider.

**Parameters:** None

**Example Usage:**
```
[tm_testimonials]
```

**Display Options Affected:**
- `tm_testimonials_bg_color` - Background color
- `tm_testimonials_text_color` - Text color
- `tm_testimonials_border_color` - Border color
- `tm_testimonials_border_width` - Border width (pixels)
- `tm_testimonials_radius` - Border radius (pixels)
- `tm_testimonials_shadow` - Enable/disable shadow effect
- `tm_testimonials_rating_symbol` - Rating display style: Stars (default), Thumbs Up, Rockets, Hearts, Theatre Masks

---

## 14. [tm_board_members]

**Purpose:** Display board members with titles and photos, automatically sorted by role priority.

**Parameters:** None

**Example Usage:**
```
[tm_board_members]
```

**Sort Priority:**
1. President
2. Vice-President
3. Treasurer
4. Secretary
5. All other roles (alphabetically)

**Display Options Affected:**
- `tm_board_member_bg_color` - Background color
- `tm_board_member_text_color` - Text color
- `tm_board_member_border_color` - Border color
- `tm_board_member_border_width` - Border width (pixels)
- `tm_board_member_radius` - Border radius (pixels)
- `tm_board_member_shadow` - Enable/disable shadow effect

---

## 15. [tm_contributors]

**Purpose:** Display contributor listings, optionally grouped by contribution level.

**Parameters:** None

**Example Usage:**
```
[tm_contributors]
```

**Display Options Affected:**
- `tm_contributor_bg_color` - Background color
- `tm_contributor_text_color` - Text color
- `tm_contributor_border_color` - Border color
- `tm_contributor_border_width` - Border width (pixels)
- `tm_contributor_radius` - Border radius (pixels)
- `tm_contributor_shadow` - Enable/disable shadow effect

---

## 16. [tm_advertisers]

**Purpose:** Display advertiser listings with optional filtering.

**Parameters:**
- `category` (string, optional) - Filter by category:
  - `restaurant` - Display only restaurant advertisers

**Example Usage:**
```
[tm_advertisers]
[tm_advertisers category="restaurant"]
```

**Display Options Affected:**
- `tm_advertiser_bg_color` - Background color
- `tm_advertiser_text_color` - Text color
- `tm_advertiser_border_color` - Border color
- `tm_advertiser_border_width` - Border width (pixels)
- `tm_advertiser_radius` - Border radius (pixels)
- `tm_advertiser_shadow` - Enable/disable shadow effect

---

## 17. [tm_programs]

**Purpose:** Display downloadable program PDFs grouped by season or for a specific season.

**Parameters:**
- `season` (integer or slug, optional) - Display programs for a specific season. If omitted, shows programs for all seasons grouped by season.
- `columns` (integer, default: 3) - Number of columns in the program gallery
- `size` (string, default: "medium") - Thumbnail size: "small", "medium", "large"

**Example Usage:**
```
[tm_programs]
[tm_programs season="177"]
[tm_programs season="177" columns="2"]
[tm_programs columns="4" size="large"]
```

---

## Display Options

All shortcodes reference the Display Options settings configured in Theatre Manager → Display Options. Key settings include:

- **Colors**: Background, text, border, header colors for each section
- **Styling**: Border width, border radius, shadow effects
- **Layout**: Grid/list layouts where applicable
- **Symbols**: Rating symbols for testimonials

To modify these globally, edit them in the WordPress admin panel under Theatre Manager → Display Options.

---

## Season Status Logic

Several shortcodes use "current", "next", and "current_and_next" filters:

- **Current Season**: Today's date falls between the season's start_date and end_date
- **Next Season**: The first season where start_date is in the future (relative to today)
- **Upcoming**: Any season where start_date is in the future
- **Past**: Any season where end_date is in the past

---

## Notes

- All shortcodes support WordPress standard escaping and sanitization
- Images are pulled from post meta fields and automatically converted from attachment IDs to URLs
- Most shortcodes respect the CPT menu visibility setting (tm_show_builder_cpt_menus)
- Seasons are automatically sorted by start date (earliest first)
