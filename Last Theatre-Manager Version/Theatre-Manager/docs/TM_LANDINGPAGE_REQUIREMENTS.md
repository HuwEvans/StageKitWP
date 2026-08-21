# Theatre Manager - tm_landingpage Shortcode Requirements

## Shortcode Name
`tm_landingpage`

## Purpose
Display comprehensive information about a specific theatre show including credits, synopsis, cast, dates, and venue information. All fields inherit alignment and text styling from the surrounding page context without plugin-specific formatting.

## Shortcode Parameters

### Required Parameters
- **show_id** (default: "current"): Show ID (numeric) or "current" to display the currently active show

### Optional Parameters
- **field_list** (default: all fields): Comma-separated list of fields to display in specified order
- **castcols** (default: 3): Number of columns for castwithbio grid (1-6)
- **urlbutton** (default: false): Display ticket URLs as styled buttons ("true"/"false")
- **buttonformat** (default: "default"): Button style (default, modern, minimal, outline, gradient, prominent, success, ghost, glass)
- **hard_breaks** (default: true): Internal parameter for field separation (do not change)

## Available Fields

### Information Fields
| Field | Description | Default Display |
|-------|-------------|-----------------|
| `show_name` | Show title | Show name |
| `show_image` | Show poster/main image | Centered image |
| `author` | Primary playwright/author | Written by: [name] |
| `sub_authors` | Additional authors | Co-writers: [names] |
| `director` | Director name | Directed by: [name] |
| `associate_director` | Assistant/Associate director | Assistant Director: [name] |
| `producer` | Producer name | Produced by: [name] |
| `stage_manager` | Stage manager name | Stage Managed by: [name] |
| `synopsis` | Show description | Under "About the Show" heading |
| `show_dates` | Performance dates/times | Under "Show Times and Ticket Info" heading |
| `ticket_url` | Link to purchase tickets | Button or link (respects urlbutton parameter) |
| `cast` | Simple cast list | Character Name - Actor Name (comma-separated) |
| `castwithbio` | Cast with photos in grid | Responsive grid with images, character, actor, bio |
| `venue` | Theatre location info | Under "Theatre Info" heading |

## Field Display Rules

### General Rules
- **Each field displays on its own line** (separated by line breaks)
- **No plugin-specific styling applied** - all text inherits from parent page/block
- **All text properties inherited** from parent: font-family, font-size, color, font-weight, line-height, letter-spacing, text-transform, direction (RTL support)
- **Images display responsive** with automatic sizing based on container
- **Entire wrapper is centered** (text-align: center)

### Alignment Inheritance
- Respects Gutenberg block alignment (center/left/right/justify)
- Respects Beaver Builder column alignment
- Respects theme-specific alignment classes (centered, center, etc.)
- Uses CSS sibling selectors to detect preceding block alignment
- All nested elements (cast lists, venue info, etc.) fully inherit parent alignment

## Default Output Structure

When displaying all fields (default field_list), the output follows this structure:

```
[Show Name]
[Show Image]

Written by: [Author]
Co-writers: [Sub-authors]
Directed by: [Director]
Assistant Director: [Associate Director]
Produced by: [Producer]
Stage Managed by: [Stage Manager]

───────────────────────────────

About the Show

[Synopsis]

[Get Tickets Button]

───────────────────────────────

Meet the Cast

[Cast Members in 3-column responsive grid]
  [Photo]
  [Character Name]
  Played by: [Actor Name]
  [Bio]

───────────────────────────────

Show Times and Ticket Info

[Show Dates and Times]

[Get Tickets Button]

───────────────────────────────

Theatre Info

[Venue Name]
[Address]
[Phone Number]
[Website Link]

───────────────────────────────
```

**All content is centered horizontally on the page.**

## Cast with Bio Grid Details (castwithbio field)

### Column Responsiveness
- **Desktop (>1024px)**: Displays `castcols` columns as specified (1-6)
- **Tablet (1024px-768px)**: Displays `min(castcols, 2)` columns for improved readability
- **Mobile (<768px)**: Displays `min(castcols, 1)` column (single column for mobile usability)

### Image Sizing
- **Aspect Ratio**: 3/4 (portrait orientation for headshots)
- **Responsive Height**: Scales inversely with column count
  - 1 column: 300px (desktop), 270px (tablet), 220px (mobile)
  - 2 columns: 285px (desktop), 255px (tablet), 205px (mobile)
  - 3 columns: 270px (desktop), 240px (tablet), 190px (mobile)
  - 6 columns: 225px (desktop), 195px (tablet), 160px (mobile)
- **Width**: 100% of container (responsive)
- **Display**: Full image visible (object-fit: contain) with light background

### Cast Member Display
Each cast member in the grid displays:
1. Portrait photo (3/4 aspect ratio, responsive sizing)
2. Character name as heading
3. "Played by: [Actor Name]"
4. Actor biography/description

## Usage Examples

### Display current show (all fields)
```
[tm_landingpage show_id="current"]
```

### Display specific show with custom fields
```
[tm_landingpage show_id="152" field_list="show_name,show_image,director,synopsis,castwithbio"]
```

### Display cast in 2 columns with ticket button
```
[tm_landingpage show_id="current" castcols="2" urlbutton="true" buttonformat="prominent"]
```

### Display specific fields in custom order
```
[tm_landingpage show_id="123" field_list="show_name,show_image,author,director,cast,ticket_url"]
```

## Technical Implementation Details

### CSS Inheritance
The shortcode relies on CSS sibling selectors to detect block alignment:
- `.has-text-align-center + .tm-landingpage-wrapper` → applies center alignment
- `.has-text-align-left + .tm-landingpage-wrapper` → applies left alignment
- `.has-text-align-right + .tm-landingpage-wrapper` → applies right alignment
- Supports Beaver Builder: `.fl-col.fl-col-text-center` detection
- Supports theme-specific: `.centered`, `.center` classes

### Field Classes
Each field div includes specific classes for targeting:
- `.tm-landingpage-field` - all fields
- `.tm-landingpage-field-[fieldname]` - specific field (e.g., `.tm-landingpage-field-synopsis`)
- `.tm-landingpage-castwithbio` - cast grid wrapper
- `.tm-cast-column` - individual cast member in grid
- `.tm-cast-image` - cast member photo
- `.tm-cast-character` - character name in grid
- `.tm-cast-actor` - "Played by" text in grid
- `.tm-cast-bio` - biography in grid

### Image Output
Images are displayed without field wrapping to maintain proper sizing and layout. Both `show_image` and `castwithbio` photos display at full container width with responsive heights.

## Version
- Updated: 3.7.18
- Comprehensive requirements documentation for all fields and default output structure
