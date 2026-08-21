# Shortcode Audit - Quick Reference

## Display Options Compliance Matrix

| Shortcode File | Tag | Category | base_font | bg_color | text_color | border_color | border_width | rounded | radius | shadow | Missing | Extra | Status |
|---|---|---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|---|---|---|
| **advertisers** | tm_advertisers | tm_advertiser | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | - | grid_columns | ✅ COMPLETE |
| **board-members** | tm_board_members | tm_board_member | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | - | grid_columns | ✅ COMPLETE |
| **cast** | tm_cast | tm_cast | ✅ | ✅ | ✅ | ❌ | ✅ | ✅ | ✅ | ✅ | border_color | - | ⚠️ 1 missing |
| **contributors** | tm_contributors | tm_contributor | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | - | - | ✅ COMPLETE |
| **programs** | tm_programs | N/A | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | All 8 | - | ❌ NO STYLING |
| **seasons** | tm_seasons | tm_season | ✅ | ✅ | ✅ | ❌ | ✅ | ✅ | ✅ | ✅ | border_color | - | ⚠️ 1 missing |
| **shows** | tm_shows | tm_show | ✅ | ✅ | ✅ | ❌ | ✅ | ✅ | ✅ | ✅ | border_color | - | ⚠️ 1 missing |
| **sponsor-slider** | tm_sponsor_slider | tm_sponsor | ✅ | ✅ | ❌ | ✅ | ✅ | ✅ | ✅ | ✅ | text_color | - | ⚠️ 1 missing |
| **sponsors** | tm_sponsors | tm_sponsor | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | - | - | ✅ COMPLETE |
| **testimonials** | tm_testimonials | tm_testimonials | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | - | rating_symbol | ✅ COMPLETE |
| **tm_auditions** | tm_auditions | tm_auditions | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | border_color, border_width, rounded, radius, shadow | - | ⚠️ 5 missing |
| **tm_cast_show** | tm_show_cast | tm_show | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | bg_color, text_color, border_color, border_width, rounded, radius, shadow | h1-h6_color | ⚠️ 7 missing |
| **tm_season_banner** | tm_season_banner | N/A | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | - | - | ℹ️ HELPER |
| **tm_season_cast** | tm_season_cast | tm_show | ✅ | ❌ | ✅ | ✅ | ✅ | ❌ | ✅ | ✅ | bg_color, rounded | - | ⚠️ 2 missing |
| **tm_season_images** | tm_season_images | N/A | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | - | - | ℹ️ HELPER |
| **tm_season_shows** | tm_season_shows | tm_show | ✅ | ❌ | ✅ | ✅ | ✅ | ❌ | ✅ | ✅ | bg_color, rounded | - | ⚠️ 2 missing |

---

## By Category

### tm_advertiser
- **Files:** advertisers-shortcode.php ✅
- **Compliance:** 8/8

### tm_board_member
- **Files:** board-members-shortcode.php ✅
- **Compliance:** 8/8

### tm_cast
- **Files:** cast-shortcode.php ⚠️
- **Compliance:** 7/8 (missing: border_color)
- **Fix:** Add `border_color` option retrieval

### tm_contributor
- **Files:** contributors-shortcode.php ✅
- **Compliance:** 8/8

### tm_season
- **Files:** seasons-shortcode.php ⚠️
- **Compliance:** 7/8 (missing: border_color)
- **Fix:** Add `border_color` option retrieval

### tm_show
- **Files:** 
  - shows-shortcode.php ⚠️ (7/8 - missing border_color)
  - tm_cast_show.php ⚠️ (1/8 - header colors only)
  - tm_season_cast.php ⚠️ (6/8 - missing bg_color, rounded)
  - tm_season_shows.php ⚠️ (6/8 - missing bg_color, rounded)
- **Compliance:** Inconsistent across files
- **Fix:** Standardize all tm_show files to use the same 8 options

### tm_sponsor
- **Files:** 
  - sponsors-shortcode.php ✅ (8/8)
  - sponsor-slider-shortcode.php ⚠️ (7/8 - missing text_color)
- **Compliance:** Inconsistent
- **Fix:** Add `text_color` to sponsor-slider

### tm_testimonials
- **Files:** testimonials-shortcode.php ✅
- **Compliance:** 8/8 (+ rating_symbol)

### tm_auditions
- **Files:** tm_auditions.php ⚠️
- **Compliance:** 3/8 (missing: border_color, border_width, rounded, radius, shadow)
- **Fix:** Add 5 missing options

### N/A (Helper Shortcodes)
- **Files:** programs-shortcode.php, tm_season_banner.php, tm_season_images.php
- **Status:** No display options (by design or needs decision)

---

## Gaps by Option

### border_color (Missing in 4)
- cast-shortcode.php
- seasons-shortcode.php
- shows-shortcode.php
- tm_auditions.php

### text_color (Missing in 3)
- tm_cast_show.php
- sponsor-slider-shortcode.php (also shows-shortcode but has it)

### bg_color (Missing in 4)
- programs-shortcode.php
- tm_cast_show.php
- tm_season_cast.php
- tm_season_shows.php

### border_width (Missing in 2)
- tm_auditions.php
- tm_cast_show.php

### rounded (Missing in 3)
- tm_auditions.php
- tm_cast_show.php
- tm_season_cast.php
- tm_season_shows.php

### radius (Missing in 2)
- tm_auditions.php
- tm_cast_show.php

### shadow (Missing in 2)
- tm_auditions.php
- tm_cast_show.php

---

## Quick Fix Checklist

### Easy Fixes (Add single option)
- [ ] **cast-shortcode.php** - Add `border_color` option retrieval (1 line)
- [ ] **seasons-shortcode.php** - Add `border_color` option retrieval (1 line)
- [ ] **shows-shortcode.php** - Add `border_color` option retrieval (1 line)
- [ ] **sponsor-slider-shortcode.php** - Add `text_color` option retrieval (1 line)

### Medium Fixes (Add 2+ options)
- [ ] **tm_season_cast.php** - Add `bg_color` and `rounded` (2 lines)
- [ ] **tm_season_shows.php** - Add `bg_color` and `rounded` (2 lines)

### Larger Fixes (Add 5+ options)
- [ ] **tm_auditions.php** - Add 5 missing options (5 lines)
- [ ] **tm_cast_show.php** - Add 7 missing options or refactor (7 lines)

### Design Decisions
- [ ] **programs-shortcode.php** - Decide if styled gallery is needed
- [ ] **tm_season_banner.php** - Confirm intended as image-only helper
- [ ] **tm_season_images.php** - Confirm intended as image-only helper
