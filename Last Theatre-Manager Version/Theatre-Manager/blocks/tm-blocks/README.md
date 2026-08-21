# Theatre Manager Blocks - Comprehensive Guide

## Overview

Theatre Manager provides a complete suite of 20 Gutenberg blocks:
- **Show Landing Page**: Standalone block with advanced features (drag-to-reorder fields, live preview)
- **19 Other Blocks**: Unified group under "Theatre Manager Blocks" category with visual configuration

All blocks provide visual configuration in the WordPress block editor with live shortcode preview.

## Block Organization

### Standalone Block
- **Show Landing Page** - Advanced features, separate UI from unified system

### Unified Theatre Manager Blocks Group (19 blocks)

### Show & Performance (3 blocks)

#### 1. **Shows List**
- **Purpose**: Display all shows grouped by season
- **Parameters**:
  - Exclude Fields: Hide specific fields
  - Season Filter: All, current, next, or current & next
  - Season: Filter by specific season
- **Best For**: Season overview page, show directory
- **Note**: Shows automatically grouped by season

#### 3. **Season Banner**
- **Purpose**: Display current season banner with information
- **Parameters**:
  - Season: Select which season to display
- **Best For**: Homepage banner, season landing page

#### 4. **Season Shows**
- **Purpose**: Display shows for a specific season
- **Parameters**:
  - Season: Select season
  - Exclude Fields: Hide specific fields
- **Best For**: Season detail pages, season schedules

### Cast & Crew (5 blocks)

#### 5. **Cast List**
- **Purpose**: Display cast members with details
- **Parameters**:
  - Exclude Fields: Hide specific fields
  - Show: Filter by show
  - Group By: Group by show or none
  - Order By: title, date, or meta value
  - Order: Ascending or descending
- **Best For**: Cast pages, cast directory, show detail pages

#### 6. **Season Cast**
- **Purpose**: Display all cast members for a season
- **Parameters**:
  - Season: Select season
- **Best For**: Season cast roster, cast gallery for season

#### 7. **Cast Member Show Roles**
- **Purpose**: Show all roles a cast member has played
- **Parameters**:
  - Cast Member: Select cast person
- **Best For**: Cast member profile/bio page

#### 8. **Board Members**
- **Purpose**: Display board member listings with photos and roles
- **Parameters**:
  - Exclude Fields: Hide specific fields
- **Best For**: Board page, organizational structure page, about us section

#### 9. **Contributors**
- **Purpose**: Display staff and volunteer contributors
- **Parameters**:
  - Exclude Fields: Hide specific fields
- **Best For**: Staff page, volunteer recognition, credits page

### Sponsors & Supporters (2 blocks)

#### 10. **Sponsors**
- **Purpose**: Display sponsor listings with logos
- **Parameters**:
  - Exclude Fields: Hide specific fields
  - Order By: title or date
- **Best For**: Sponsors page, partner listings

#### 11. **Sponsor Slider**
- **Purpose**: Sponsors in responsive carousel/slider
- **Parameters**:
  - Items to Show: 1-20 sponsors per view
- **Best For**: Homepage partner slider, responsive sponsor gallery

### Advertising & Awards (2 blocks)

#### 12. **Advertisers**
- **Purpose**: Display advertiser listings with business info
- **Parameters**:
  - Exclude Fields: Hide specific fields
- **Best For**: Advertisers page, partner directory

#### 13. **Awards**
- **Purpose**: Display show awards and recognitions
- **Parameters**:
  - Exclude Fields: Hide specific fields
- **Best For**: Awards page, achievements gallery

### Venue & Location (1 block)

#### 14. **Venues**
- **Purpose**: Display theatre venue information
- **Parameters**:
  - Exclude Fields: Hide specific fields
- **Best For**: Venue page, theater information, location details

### Audience Experience (2 blocks)

#### 15. **Testimonials**
- **Purpose**: Display audience testimonials and ratings
- **Parameters**:
  - Exclude Fields: Hide specific fields
  - Limit: Number of testimonials to show (-1 for all)
- **Best For**: Homepage testimonials, reviews section, audience feedback

#### 16. **Tickets**
- **Purpose**: Display ticket purchase information
- **Parameters**:
  - Exclude Fields: Hide specific fields
- **Best For**: Ticket info page, purchase details

### Season & Gallery (2 blocks)

#### 17. **Seasons**
- **Purpose**: Display theatre seasons with schedules
- **Parameters**:
  - Exclude Fields: Hide specific fields
- **Best For**: Seasons overview, archive page, season history

#### 18. **Season Gallery**
- **Purpose**: Display photo gallery for a season
- **Parameters**:
  - Season: Select season
  - Columns: 1-6 columns for gallery grid
- **Best For**: Season photo album, opening night photos

### Special Content (3 blocks)

#### 19. **Auditions**
- **Purpose**: Display audition information and schedules
- **Parameters**:
  - Exclude Fields: Hide specific fields
- **Best For**: Auditions page, casting calls, participation info

#### 20. **Programs**
- **Purpose**: Display downloadable show programs
- **Parameters**:
  - Exclude Fields: Hide specific fields
- **Best For**: Program downloads, archives, digital programs

## How to Use

### Adding a Block

1. **Edit a Page**: Open WordPress block editor
2. **Add Block**: Click "+" button to add block
3. **Find Block**: Search in "Theatre Manager Blocks" category OR search by name
4. **Configure**: Use right sidebar to set parameters
5. **Preview**: See generated shortcode in real-time
6. **Save**: Publish page

### Setting Parameters

- **Select Fields**: Dropdown menus for predefined options
- **Text Input**: Enter comma-separated values or custom text
- **Number Sliders**: Drag or click to set numeric values
- **Toggle Switches**: Enable/disable features
- **Multi-select**: Check multiple items (visible in preview)

### Shortcode Preview

Every block shows the generated WordPress shortcode in the preview area. You can also:
- Copy the shortcode and use elsewhere
- View exactly what parameters are being sent
- Verify configuration before saving

## Default Configurations

All blocks come with sensible defaults:
- **Shows List**: All shows, all fields
- **Cast List**: All cast, grouped by show, sorted by name
- **Sponsor Slider**: 6 sponsors per view
- **Season Gallery**: 3 columns
- **Testimonials**: All testimonials
- **Auditions**: All content displayed

## Tips & Best Practices

### Exclusion Fields
Most blocks support `Exclude Fields` to hide unwanted information:
```
Example: "genre,audition_date,director"
```

### Field Availability
Different CPTs have different fields available. Check the admin UI for each post type to see what fields are configured.

### Responsive Design
All blocks are responsive:
- **Desktop**: Full width, full column count
- **Tablet**: Constrained columns
- **Mobile**: Single column (or minimal columns)

### Performance
- Blocks fetch data efficiently
- REST API queries are cached
- Large galleries use lazy loading
- Pagination handled per block

### Combining Blocks
Create compelling pages by combining multiple blocks:
- **Homepage**: Landing page + Sponsor slider + Testimonials
- **Season Page**: Season banner + Season shows + Season gallery
- **Cast Page**: Cast list + Season cast
- **About Page**: Board members + Contributors + Awards

### Custom Styling
All block output inherits theme styling. Override with custom CSS:
```css
.tm-entry { /* Customize entry styling */ }
.tm-cast-image { /* Customize cast images */ }
.tm-show-title { /* Customize show names */ }
```

## Troubleshooting

### Block Not Appearing
- Clear browser cache
- Re-save permalinks: Settings → Permalinks
- Ensure REST API is enabled
- Check WordPress version (requires 6.8.2+)

### Missing Data in Search
- Verify posts are published
- Check REST API endpoints: `/wp-json/wp/v2/show`
- Ensure CPT has REST API support enabled

### Shortcode Not Rendering
- Copy shortcode from preview
- Paste into page as text
- Ensure shortcode file is loaded

## API Reference

### Block Category
```
theatre-manager
```

### Block Names
- `theatre-manager/landingpage`
- `theatre-manager/tm-shows`
- `theatre-manager/tm-cast`
- `theatre-manager/tm-board-members`
- `theatre-manager/tm-sponsors`
- `theatre-manager/tm-advertisers`
- `theatre-manager/tm-seasons`
- `theatre-manager/tm-venues`
- `theatre-manager/tm-testimonials`
- `theatre-manager/tm-awards`
- `theatre-manager/tm-contributors`
- `theatre-manager/tm-auditions`
- `theatre-manager/tm-sponsor-slider`
- `theatre-manager/tm-season-banner`
- `theatre-manager/tm-season-shows`
- `theatre-manager/tm-season-cast`
- `theatre-manager/tm-season-images`
- `theatre-manager/tm-cast-show`
- `theatre-manager/tm-programs`
- `theatre-manager/tm-tickets`

## Support

For issues with specific blocks, check the Theatre Manager documentation or contact support at https://miltonplayers.com/plugin
