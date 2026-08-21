# Theatre Manager - Show Landing Page Gutenberg Block

## Overview

The Show Landing Page Gutenberg block provides an intuitive interface for inserting `tm_landingpage` shortcodes into WordPress pages using the block editor.

## Features

### Show Selection
- **Search Interface**: Type to search for shows by name
- **Current Show Option**: Quickly select the current/active show
- **Selected Display**: See which show is currently selected

### Field Management
- **Drag-to-Reorder**: Reorder fields by dragging checkboxes
- **Visual Selection**: Check fields to include in the output
- **All Fields Available**: Access all 14 available fields:
  - Show Name
  - Show Image
  - Author
  - Co-writers (Sub-authors)
  - Director
  - Assistant Director
  - Producer
  - Stage Manager
  - Synopsis
  - Show Dates
  - Ticket URL
  - Cast List
  - Cast with Photos (Grid)
  - Venue Information

### Configuration Options
- **Cast Columns**: Set 1-6 columns for cast grid (appears when "Cast with Photos" is selected)
- **Ticket Button**: Display ticket URL as a styled button
- **Button Style**: Choose from 9 different button styles (Default, Modern, Minimal, Outline, Gradient, Prominent, Success, Ghost, Glass)

### Live Preview
- **Real-time Display**: See the generated shortcode as you configure
- **Configuration Summary**: Shows selected show, number of fields, cast columns, and button settings

## How to Use

### Adding the Block

1. Open or create a page in the WordPress block editor
2. Click the "+" button to add a new block
3. Search for "Show Landing Page" or "Landing Page"
4. Click to insert the block

### Selecting a Show

1. In the "Show Selection" panel on the right:
   - Type the show name in the search field to find it
   - Click on a show from the results to select it
   - Or click "Use Current Show" to select today's active show

### Choosing Fields

1. In the "Field Selection" panel:
   - Check fields you want to display
   - Uncheck to hide fields
   - Drag fields to change their display order
   - Active (checked) fields are highlighted in blue

### Configuring Cast Display

If you've selected "Cast with Photos":
1. Open the "Cast Options" panel
2. Set the number of columns (1-6, default is 3)
3. The grid will be responsive: 2 columns on tablet, 1 column on mobile

### Configuring Ticket Button

If you've selected "Ticket URL":
1. Open the "Ticket Button Options" panel
2. Check "Display as Button" to show as styled button (recommended)
3. Select a button style from the dropdown
4. Button will inherit the ticket URL from the show

## Default Configuration

When first inserted, the block includes all fields in this order:
1. Show Name
2. Show Image
3. Author
4. Director
5. Producer
6. Stage Manager
7. Synopsis
8. Show Dates
9. Ticket URL (as button)
10. Cast with Photos (3 columns)
11. Venue

## Technical Details

### Block Name
`theatre-manager/landingpage`

### Block Files
- `index.js` - Main block component (React)
- `editor.scss` - Editor styling
- `block.json` - Block metadata and configuration

### Generated Shortcode
The block generates a `tm_landingpage` shortcode with the following attributes:
- `show_id` - Selected show ID or "current"
- `field_list` - Comma-separated list of selected fields in order
- `castcols` - Number of columns for cast grid (1-6)
- `urlbutton` - Display ticket URL as button (true/false)
- `buttonformat` - Button style format

### REST API Requirements
- The "Show" post type must be queryable via REST API for the search functionality
- This is enabled automatically when the plugin loads

## Tips

- **Reordering**: Drag any checked field up or down to change display order
- **Field Order Matters**: Put more important information first (e.g., Show Name, Image, then Synopsis)
- **Cast Grid**: Consider your page width when choosing columns (3 columns works well for standard page widths)
- **Responsive**: All content automatically adjusts for tablet and mobile devices
- **No Plugin Styling**: All text inherits styling from your theme/page formatting

## Troubleshooting

### Shows Not Appearing in Search
- Ensure shows are published
- Make sure the "Show" CPT has REST API support enabled
- Check that WordPress REST API is accessible

### Block Not Appearing
- Clear WordPress cache
- Re-save permalinks: Settings → Permalinks → Save Changes
- Rebuild the block assets if using a build process

### Shortcode Not Rendering
- Verify `tm_landingpage` shortcode is registered
- Check that the show ID exists
- Ensure at least one field is selected
