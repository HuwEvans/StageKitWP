# Theatre Manager Shortcode Audit

**Audit Date:** May 28, 2026  
**Location:** `Theatre-Manager\includes\shortcodes\`  
**Total Shortcode Files:** 16

---

## Standard Display Options Reference

All display-based shortcodes should consistently support these options:
- `base_font` - Font family
- `bg_color` - Background color
- `text_color` - Text color
- `border_color` - Border color
- `border_width` - Border width in pixels
- `rounded` - Boolean flag to enable rounded corners
- `radius` - Border radius in pixels
- `shadow` - Boolean flag to enable box shadow

---

## Shortcode Audit Results

### 1. advertisers-shortcode.php
- **Shortcode Tag:** `[tm_advertisers]`
- **Category:** `tm_advertiser`
- **Currently Uses:**
  - ✅ base_font (`tm_advertiser_base_font`)
  - ✅ bg_color (`tm_advertiser_bg_color`)
  - ✅ text_color (`tm_advertiser_text_color`)
  - ✅ border_color (`tm_advertiser_border_color`)
  - ✅ border_width (`tm_advertiser_border_width`)
  - ✅ rounded (`tm_advertiser_rounded`)
  - ✅ radius (`tm_advertiser_radius`)
  - ✅ shadow (`tm_advertiser_shadow`)
  - 🔧 grid_columns (`tm_advertiser_grid_columns`) - EXTRA
- **Should Use:** All standard options
- **Missing:** NONE
- **Status:** ✅ COMPLETE

---

### 2. board-members-shortcode.php
- **Shortcode Tag:** `[tm_board_members]`
- **Category:** `tm_board_member`
- **Currently Uses:**
  - ✅ base_font (`tm_board_member_base_font`)
  - ✅ bg_color (`tm_board_member_bg_color`)
  - ✅ text_color (`tm_board_member_text_color`)
  - ✅ border_color (`tm_board_member_border_color`)
  - ✅ border_width (`tm_board_member_border_width`)
  - ✅ rounded (`tm_board_member_rounded`)
  - ✅ radius (`tm_board_member_radius`)
  - ✅ shadow (`tm_board_member_shadow`)
  - 🔧 grid_columns (`tm_board_member_grid_columns`) - EXTRA
- **Should Use:** All standard options
- **Missing:** NONE
- **Status:** ✅ COMPLETE

---

### 3. cast-shortcode.php
- **Shortcode Tag:** `[tm_cast]`
- **Category:** `tm_cast`
- **Currently Uses:**
  - ✅ base_font (`tm_cast_base_font`)
  - ✅ bg_color (`tm_cast_bg_color`)
  - ✅ text_color (`tm_cast_text_color`)
  - ✅ border_width (`tm_cast_border_width`)
  - ✅ rounded (`tm_cast_rounded`)
  - ✅ radius (`tm_cast_radius`)
  - ✅ shadow (`tm_cast_shadow`)
- **Should Use:** All standard options
- **Missing:** 
  - ❌ border_color (`tm_cast_border_color`)
- **Status:** ⚠️ INCOMPLETE (1 missing)

---

### 4. contributors-shortcode.php
- **Shortcode Tag:** `[tm_contributors]`
- **Category:** `tm_contributor`
- **Currently Uses:**
  - ✅ base_font (`tm_contributor_base_font`)
  - ✅ bg_color (`tm_contributor_bg_color`)
  - ✅ text_color (`tm_contributor_text_color`)
  - ✅ border_color (`tm_contributor_border_color`)
  - ✅ border_width (`tm_contributor_border_width`)
  - ✅ rounded (`tm_contributor_rounded`)
  - ✅ radius (`tm_contributor_radius`)
  - ✅ shadow (`tm_contributor_shadow`)
- **Should Use:** All standard options
- **Missing:** NONE
- **Status:** ✅ COMPLETE

---

### 5. programs-shortcode.php
- **Shortcode Tag:** `[tm_programs]`
- **Category:** N/A (Listing/Gallery - minimal styling)
- **Currently Uses:**
  - ❌ NONE
- **Should Use:** All standard options (or could be styled consistently)
- **Missing:** 
  - ❌ base_font
  - ❌ bg_color
  - ❌ text_color
  - ❌ border_color
  - ❌ border_width
  - ❌ rounded
  - ❌ radius
  - ❌ shadow
- **Status:** ⚠️ NO DISPLAY OPTIONS (design decision needed)

---

### 6. seasons-shortcode.php
- **Shortcode Tag:** `[tm_seasons]`
- **Category:** `tm_season`
- **Currently Uses:**
  - ✅ base_font (`tm_season_base_font`)
  - ✅ bg_color (`tm_season_bg_color`)
  - ✅ text_color (`tm_season_text_color`)
  - ✅ border_width (`tm_season_border_width`)
  - ✅ rounded (`tm_season_rounded`)
  - ✅ radius (`tm_season_radius`)
  - ✅ shadow (`tm_season_shadow`)
- **Should Use:** All standard options
- **Missing:** 
  - ❌ border_color (`tm_season_border_color`)
- **Status:** ⚠️ INCOMPLETE (1 missing)

---

### 7. shows-shortcode.php
- **Shortcode Tag:** `[tm_shows]`
- **Category:** `tm_show`
- **Currently Uses:**
  - ✅ base_font (`tm_show_base_font`)
  - ✅ bg_color (`tm_show_bg_color`)
  - ✅ text_color (`tm_show_text_color`)
  - ✅ border_width (`tm_show_border_width`)
  - ✅ rounded (`tm_show_rounded`)
  - ✅ radius (`tm_show_radius`)
  - ✅ shadow (`tm_show_shadow`)
- **Should Use:** All standard options
- **Missing:** 
  - ❌ border_color (`tm_show_border_color`)
- **Status:** ⚠️ INCOMPLETE (1 missing)

---

### 8. sponsor-slider-shortcode.php
- **Shortcode Tag:** `[tm_sponsor_slider]`
- **Category:** `tm_sponsor`
- **Currently Uses:**
  - ✅ base_font (`tm_sponsor_base_font`)
  - ✅ bg_color (`tm_sponsor_bg_color`)
  - ✅ border_color (`tm_sponsor_border_color`)
  - ✅ border_width (`tm_sponsor_border_width`)
  - ✅ rounded (`tm_sponsor_rounded`)
  - ✅ radius (`tm_sponsor_radius`)
  - ✅ shadow (`tm_sponsor_shadow`)
- **Should Use:** All standard options
- **Missing:** 
  - ❌ text_color (`tm_sponsor_text_color`)
- **Status:** ⚠️ INCOMPLETE (1 missing)

---

### 9. sponsors-shortcode.php
- **Shortcode Tag:** `[tm_sponsors]`
- **Category:** `tm_sponsor`
- **Currently Uses:**
  - ✅ base_font (`tm_sponsor_base_font`)
  - ✅ bg_color (`tm_sponsor_bg_color`)
  - ✅ text_color (`tm_sponsor_text_color`)
  - ✅ border_color (`tm_sponsor_border_color`)
  - ✅ border_width (`tm_sponsor_border_width`)
  - ✅ rounded (`tm_sponsor_rounded`)
  - ✅ radius (`tm_sponsor_radius`)
  - ✅ shadow (`tm_sponsor_shadow`)
- **Should Use:** All standard options
- **Missing:** NONE
- **Status:** ✅ COMPLETE

---

### 10. testimonials-shortcode.php
- **Shortcode Tag:** `[tm_testimonials]`
- **Category:** `tm_testimonials`
- **Currently Uses:**
  - ✅ base_font (`tm_testimonials_base_font`)
  - ✅ bg_color (`tm_testimonials_bg_color`)
  - ✅ text_color (`tm_testimonials_text_color`)
  - ✅ border_color (`tm_testimonials_border_color`)
  - ✅ border_width (`tm_testimonials_border_width`)
  - ✅ rounded (`tm_testimonials_rounded`)
  - ✅ radius (`tm_testimonials_radius`)
  - ✅ shadow (`tm_testimonials_shadow`)
  - 🔧 rating_symbol (`tm_testimonials_rating_symbol`) - EXTRA
- **Should Use:** All standard options
- **Missing:** NONE
- **Status:** ✅ COMPLETE (+ custom rating_symbol option)

---

### 11. tm_auditions.php
- **Shortcode Tag:** `[TM_Auditions]` / `[tm_auditions]`
- **Category:** `tm_auditions`
- **Currently Uses:**
  - ✅ base_font (`tm_auditions_base_font`)
  - ✅ bg_color (`tm_auditions_bg_color`)
  - ✅ text_color (`tm_auditions_text_color`)
- **Should Use:** All standard options
- **Missing:** 
  - ❌ border_color (`tm_auditions_border_color`)
  - ❌ border_width (`tm_auditions_border_width`)
  - ❌ rounded (`tm_auditions_rounded`)
  - ❌ radius (`tm_auditions_radius`)
  - ❌ shadow (`tm_auditions_shadow`)
- **Status:** ⚠️ INCOMPLETE (5 missing)

---

### 12. tm_cast_show.php
- **Shortcode Tag:** `[tm_show_cast]`
- **Category:** `tm_show` (helper shortcode)
- **Currently Uses:**
  - ✅ base_font (`tm_show_base_font`)
  - 🔧 h1-h6 colors (`tm_show_h{1-6}_color`) - HEADER COLORS
- **Should Use:** All standard options
- **Missing:** 
  - ❌ bg_color (`tm_show_bg_color`)
  - ❌ text_color (`tm_show_text_color`)
  - ❌ border_color (`tm_show_border_color`)
  - ❌ border_width (`tm_show_border_width`)
  - ❌ rounded (`tm_show_rounded`)
  - ❌ radius (`tm_show_radius`)
  - ❌ shadow (`tm_show_shadow`)
- **Status:** ⚠️ INCOMPLETE (7 missing) - Minimal styling, header colors only

---

### 13. tm_season_banner.php
- **Shortcode Tag:** `[tm_season_banner]`
- **Category:** N/A (Image display helper - no styling)
- **Currently Uses:**
  - NONE (inline image display)
- **Should Use:** N/A (specialized image display)
- **Missing:** N/A
- **Status:** ℹ️ HELPER SHORTCODE (no display options needed)

---

### 14. tm_season_cast.php
- **Shortcode Tag:** `[tm_season_cast]`
- **Category:** `tm_show` (helper shortcode for season cast display)
- **Currently Uses:**
  - ✅ base_font (`tm_show_base_font`)
  - ✅ text_color (`tm_show_text_color`)
  - ✅ border_color (`tm_show_border_color`)
  - ✅ border_width (`tm_show_border_width`)
  - ✅ radius (`tm_show_radius`)
  - ✅ shadow (`tm_show_shadow`)
- **Should Use:** All standard options
- **Missing:** 
  - ❌ bg_color (`tm_show_bg_color`)
  - ❌ rounded (`tm_show_rounded`)
- **Status:** ⚠️ INCOMPLETE (2 missing)

---

### 15. tm_season_images.php
- **Shortcode Tag:** `[tm_season_images]`
- **Category:** N/A (Image display helper - no styling)
- **Currently Uses:**
  - NONE (inline image display)
- **Should Use:** N/A (specialized image display)
- **Missing:** N/A
- **Status:** ℹ️ HELPER SHORTCODE (no display options needed)

---

### 16. tm_season_shows.php
- **Shortcode Tag:** `[tm_season_shows]`
- **Category:** `tm_show` (helper shortcode for season shows)
- **Currently Uses:**
  - ✅ base_font (`tm_show_base_font`)
  - ✅ text_color (`tm_show_text_color`)
  - ✅ border_color (`tm_show_border_color`)
  - ✅ border_width (`tm_show_border_width`)
  - ✅ radius (`tm_show_radius`)
  - ✅ shadow (`tm_show_shadow`)
- **Should Use:** All standard options
- **Missing:** 
  - ❌ bg_color (`tm_show_bg_color`)
  - ❌ rounded (`tm_show_rounded`)
- **Status:** ⚠️ INCOMPLETE (2 missing)

---

## Summary Statistics

| Status | Count | Shortcodes |
|--------|-------|-----------|
| ✅ Complete | 4 | advertisers, board-members, contributors, sponsors, testimonials |
| ⚠️ Incomplete | 7 | cast (1 missing), seasons (1 missing), shows (1 missing), sponsor-slider (1 missing), auditions (5 missing), tm_season_cast (2 missing), tm_season_shows (2 missing) |
| ℹ️ Helper/Special | 3 | programs (no styling), tm_season_banner, tm_season_images |
| 🔧 Partial | 1 | tm_cast_show (header colors only) |
| **TOTAL** | **16** | - |

---

## Issues Found

### Critical Issues (Missing Core Options)

1. **tm_auditions.php** - Missing 5 display options (75% incomplete)
   - Missing: border_color, border_width, rounded, radius, shadow

2. **tm_cast_show.php** - Missing 7 display options (88% incomplete)
   - Only implements header colors, not standard display options
   - Missing: bg_color, text_color, border_color, border_width, rounded, radius, shadow

3. **tm_season_cast.php** - Missing 2 display options (25% incomplete)
   - Missing: bg_color, rounded

4. **tm_season_shows.php** - Missing 2 display options (25% incomplete)
   - Missing: bg_color, rounded

### Minor Issues (Single Missing Options)

5. **cast-shortcode.php** - Missing: border_color
6. **seasons-shortcode.php** - Missing: border_color
7. **shows-shortcode.php** - Missing: border_color
8. **sponsor-slider-shortcode.php** - Missing: text_color

### Design Questions

9. **programs-shortcode.php** - No display options implemented
   - Should this be styled consistently with other shortcodes?
   - Currently minimal styling with inline CSS only

---

## Recommendations

### Priority 1: Fix Incomplete Shortcodes
- Add missing `border_color` option to: cast, seasons, shows, sponsor-slider
- Add missing `text_color` to: sponsor-slider
- Add missing `bg_color` and `rounded` to: tm_season_cast, tm_season_shows
- Add missing 5 options to: tm_auditions

### Priority 2: Review Helper Shortcodes
- **tm_cast_show**: Decide if it needs full display options or keep as header-color-only helper
- **programs**: Decide if gallery needs full display option support

### Priority 3: Standardization
- Ensure all main display shortcodes use exactly the same set of 8 standard options
- Consider extracting option retrieval into a reusable helper function:
  ```php
  function tm_get_display_options($category) {
    return [
      'base_font' => get_option("tm_{$category}_base_font", 'Arial, sans-serif'),
      'bg_color' => get_option("tm_{$category}_bg_color", '#ffffff'),
      'text_color' => get_option("tm_{$category}_text_color", '#000000'),
      'border_color' => get_option("tm_{$category}_border_color", '#000000'),
      'border_width' => get_option("tm_{$category}_border_width", '0'),
      'rounded' => get_option("tm_{$category}_rounded", false),
      'radius' => get_option("tm_{$category}_radius", '20'),
      'shadow' => get_option("tm_{$category}_shadow", false),
    ];
  }
  ```

---

## Notes

- **Extra Options**: Some shortcodes have category-specific extras (e.g., `grid_columns` for advertisers/board-members, `rating_symbol` for testimonials) - these are appropriate for their specific use cases
- **Header Colors**: Some shortcodes use `h1-h6_color` options for styling headers separately - this is not tracked in the standard set
- **Safe Defaults**: Most options have sensible defaults if not set in WordPress options table
- **Consistency**: Categories sharing a base option prefix (e.g., all `tm_show_*`) should be kept in sync
