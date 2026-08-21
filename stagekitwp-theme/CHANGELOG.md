# Theatre Manager Theme — Changelog
<!-- markdownlint-disable-file MD022 MD024 MD032 -->

## Version 3.1.0 — 2026-08-21 — Ecosystem release sync

### Updates

- Release synchronization with StageKitWP Core plugin 5.2.0 (admin hub media picker fixes and Documentation link fix). No theme-specific functional changes in this release.
- Bumped the theme cache-busting version constants for the release.

### Files changed

- `functions.php`
- `style.css`
- `readme.txt`
- `CHANGELOG.md`
- `stagekitwp-theme.json`

## Version 3.0.0 — 2026-08-10 — Tabbed ecosystem menu sync

### Updates

- Release synchronization with the ecosystem-wide tabbed Theatre Manager admin menu.
- Updated theme release metadata to stay aligned with the new shared hub structure.
- Bumped the theme cache-busting version constants for the release.

### Files changed

- `functions.php`
- `style.css`
- `readme.txt`
- `CHANGELOG.md`

## Version 2.2.26 — 2026-08-09 — Security/output hardening sync release

### Updates

- Release synchronization with Theatre Manager plugin 4.6.17 hardening patch.
- Escaped dynamic output in Dashboard Health and Theme Dashboard admin views to address standards findings.
- Hardened dashboard nonce handling and inline-style value output in theme admin UI.
- Replaced deprecated page-title lookup helper with a query-based page resolver.
- Updated version metadata and cache-busting constants for this release.

### Files changed

- `functions.php`
- `inc/dashboard-health.php`
- `inc/theme-dashboard.php`
- `style.css`
- `CHANGELOG.md`
- `readme.txt`

## Version 2.2.25 — 2026-08-06 — Release sync + 404 template update note

### Updates

- Release synchronization with the latest Theatre Manager plugin patch.
- Updated version metadata and cache-busting constants for this theme release.
- Added release-note tracking entry for: **Updated the `404.php` template**.

### Files changed

- `404.php`
- `functions.php`
- `style.css`
- `CHANGELOG.md`
- `readme.txt`

## Version 2.2.24 — 2026-08-04 — Hero CTA placement + optional mobile hero video

### Enhancements

- **Hero CTA placement controls:** Added a new Customizer placement setting for the hero button with positions for below-content, all four corners, top/bottom center, and left/right center.
- **Full-media positioning:** CTA placements now anchor against the full hero media frame rather than the text block, with configurable horizontal and vertical padding.
- **Safe-area mode:** Added an optional safe-area toggle that keeps left and right CTA placements out of the central text column on wide screens.
- **Optional mobile hero video:** Added a second Customizer upload for a portrait/mobile MP4 used on screens `768px` and below, with automatic desktop/mobile source switching.
- **Customizer guidance:** The main and mobile hero video controls now document recommended upload dimensions directly in the Customizer.

### Files changed

- `inc/customizer.php`
- `template-parts/sections/section-hero.php`
- `assets/js/customizer-preview.js`
- `functions.php`
- `style.css`
- `CHANGELOG.md`
- `readme.txt`

## Version 2.2.23 — 2026-08-04 — Fix: News carousel could overflow the page horizontally

### Bug fix

- **Root cause:** `.tm-news-carousel-viewport` (the `overflow:hidden` clipping wrapper around the scrollable news track) is a flex item inside `.tm-news-carousel-shell` (`display:flex`). Flex items default to `min-width: auto`, meaning the browser lets the item's minimum width be dictated by its content's intrinsic size instead of `0` — even though the viewport itself declared `width:100%` and `overflow:hidden`. Because the track holds several non-wrapping `flex: 0 0 ...` cards, the viewport's intrinsic content width could exceed the shell/container width, pushing the whole homepage into horizontal (left-right) page scroll on some content/viewport combinations.
- **Fix:** Added `min-width: 0` to `.tm-news-carousel-viewport` (the standard fix for flex-item content overflow) and `max-width: 100%` to `.tm-news-carousel-shell` as a defensive cap. The internal horizontal scroller (prev/next buttons + swipe/scroll-snap) is unaffected — only the outer page-level overflow is eliminated.
- Verified via `inspect_design`: `<html>`/`<body>` width now stays exactly equal to the viewport width (no horizontal scrollbar) at both desktop (1040px) and mobile (390px), with the carousel still fully functional (prev/next, snap-scroll).
- Files changed: `front-page.php`, `functions.php` (version bump), `style.css`, `CHANGELOG.md`, `readme.txt`.

## Version 2.2.22 — 2026-08-04 — New: Site Max Width option

### Enhancements

- **New Customizer setting — Site Max Width (px):** Added to the existing "Theatre Global Elements" section. Controls the maximum width of the header, footer, and every main content container site-wide (homepage sections, single show, page, archive, 404, search results). Default `1200`, range `960`–1920, in 10px steps.
- Implemented as a single CSS custom property (`--tm-site-max-width`) set in `:root` by `tm_inject_customizer_css_variables()` and consumed by every template that previously hardcoded `max-width: 1200px` inline.
- Live preview: updates instantly in the Customizer via a new `postMessage` binding (no full page reload needed).
- The footer's existing `full-width` layout mode is untouched — it still explicitly sets `max-width: 100%` regardless of this setting, since that mode is meant to ignore the site width entirely.

### Files changed

- `inc/customizer.php` (new setting + control + sanitize callback)
- `functions.php` (`--tm-site-max-width` CSS variable, `_S_VERSION` bump)
- `assets/js/customizer-preview.js` (live preview binding)
- `header.php`, `footer.php`, `page.php`, `index.php`, `archive.php`, `404.php`, `single-show.php`, `single-shows.php`, `front-page.php`, `search.php` (swapped hardcoded `1200px` for `var(--tm-site-max-width, 1200px)`)
- `style.css` (version bump)
- `CHANGELOG.md`, `readme.txt`

## Version 2.2.21 — 2026-08-04 — Review Alignment: new left/right alternating option

### Enhancements

- **Customizer (Homepage Testimonials section):** "Review Alignment" select now includes a new `alternating_lr` choice, matching plugin v4.6.13's `review_align="alternating_lr"`. Cycles each review left → right → left → right (no center), starting left, complementing the existing Alternating (right, left, center) choice.
- `section-testimonials.php`'s allowed-values whitelist for `tm_testimonials_review_align` updated to accept the new value so it passes through correctly to the shortcode/block render paths.

### Files changed

- `functions.php`
- `template-parts/sections/section-testimonials.php`
- `style.css`
- `CHANGELOG.md`
- `readme.txt`

## Version 2.2.20 — 2026-08-04 — Homepage Testimonials Customizer: Per Show Slider mode

### Enhancements

- **Customizer (Homepage Testimonials section):** "Display Mode" select now includes the plugin's new `per_show_slider` option, matching plugin v4.6.11's `[tm_testimonials mode="per_show_slider"]` view.
- `per_show_slider` is identical to `per_show` mode (same query, single-card layout, and `reviews_per_show`/`review_align` theme mods already added in 2.2.19), but shows one show's card at a time in a Slick carousel with arrows, dots, adaptive height, and autoplay — the same carousel behavior used by the existing Slider mode.
- No new theme mods were needed: `section-testimonials.php` passes `$testimonial_mode` straight through to the shortcode without a hardcoded mode whitelist, so the new mode value works automatically once selected in the Customizer.

### Files changed

- **`functions.php`**
- **`style.css`**
- **`CHANGELOG.md`**

## Version 2.2.19 — 2026-08-04 — Homepage Testimonials Customizer: Per Show mode parity

### Enhancements

- **Customizer (Homepage Testimonials section):** "Display Mode" select now includes the plugin's new `per_show` option, alongside Slider/Grid/Full Page List.
- **New setting — Reviews Per Show:** number control (1-20, default 4). Only used when Display Mode is Per Show.
- **New setting — Review Alignment:** select control (Left/Center/Right/Alternating, default Left). Only used when Display Mode is Per Show. Alternating cycles right → left → center → repeat per show group.
- **`section-testimonials.php`:** reads the two new theme mods and passes `reviews_per_show` / `review_align` through to both the direct block-render path and the shortcode fallback path, matching plugin v4.6.9's `[tm_testimonials mode="per_show"]` view.

### Files changed

- **`functions.php`**
- **`template-parts/sections/section-testimonials.php`**
- **`style.css`**
- **`CHANGELOG.md`**

## Version 2.2.18 — 2026-07-17 — Release sync for testimonials + season builder parity

### Enhancements

- **Release sync:** Theme release metadata updated to align with the corresponding Theatre Manager plugin Season Builder testimonials/media release.
- **`functions.php`** — `_S_VERSION` constant bumped from `2.2.17` to `2.2.18` for cache busting.
- **`style.css`** — Theme header version updated to `2.2.18`.

### Files changed

- **`functions.php`**
- **`style.css`**
- **`readme.txt`**
- **`CHANGELOG.md`**

## Version 2.2.17 — 2026-07-16 — Query Loop post pattern library

### Enhancements

- **`patterns/`** — Added Query Loop-based post patterns for creative layouts: grid cards, spotlight stack, full-feature post, summary list, and text-over-graphic.
- **`patterns/`** — Added companion patterns for shows, seasons, and venues so content editors can reuse the same layout language across theatre post types.
- **`functions.php`** — `_S_VERSION` constant bumped from `2.2.16` to `2.2.17` for cache busting.
- **`style.css`** — Theme header version updated to `2.2.17`.

### Files changed

- **`functions.php`**
- **`style.css`**
- **`readme.txt`**
- **`CHANGELOG.md`**
- **`patterns/posts-grid-cards.php`**
- **`patterns/posts-spotlight-stack.php`**
- **`patterns/posts-full-feature.php`**
- **`patterns/posts-summary-list.php`**
- **`patterns/posts-text-over-graphic.php`**
- **`patterns/shows-grid-cards.php`**
- **`patterns/seasons-spotlight.php`**
- **`patterns/venues-summary-list.php`**

## Version 2.2.16 — 2026-07-15 — Donate controls + menu placement control

### Enhancements

- **`inc/customizer.php`** — Added Theatre Global Elements settings for Donate URL and Donate position in the primary menu, with dynamic choices enumerated from current menu items.
- **`functions.php`** — Donate menu URL override now preserves admin-defined custom URLs and only replaces legacy placeholder/default Donate links.
- **`functions.php`** — Auto Donate insertion moved to object-level menu injection and now supports configurable placement (`start`, `end`, or `after <item>`).
- **`inc/widgets/class-tm-donate-widget.php`** — Added a dedicated Donate widget that renders the global Donate image and URL in footer widget areas.
- **`assets/js/customizer-preview.js`** — Added live preview bindings for Donate URL and widget image updates.
- **`header.php`** and **`inc/theme-dashboard.php`** — Donate URL fallback paths now source from the shared Customizer Donate URL setting.
- **`functions.php`** — `_S_VERSION` constant bumped from `2.2.15` to `2.2.16` for cache busting.
- **`style.css`** — Theme header version updated to `2.2.16`.

### Files changed

- **`inc/customizer.php`**
- **`functions.php`**
- **`inc/widgets/class-tm-donate-widget.php`**
- **`assets/js/customizer-preview.js`**
- **`header.php`**
- **`inc/theme-dashboard.php`**
- **`style.css`**
- **`CHANGELOG.md`**
- **`readme.txt`**

## Version 2.2.15 — 2026-07-15 — Notification bar wrap fix

### Enhancements

- **`header.php`** — Split the top countdown notification into a label column and a copy column so wrapped lines stay aligned to the right of the label instead of looking offset.
- **`functions.php`** — Added responsive notification-bar rules so the copy preserves a clean indent on larger phones and centers cleanly on the smallest screens.
- **`functions.php`** — `_S_VERSION` constant bumped from `2.2.14` to `2.2.15` for cache busting.
- **`style.css`** — Theme header version updated to `2.2.15`.

### Files changed

- **`header.php`**
- **`functions.php`**
- **`style.css`**
- **`CHANGELOG.md`**
- **`readme.txt`**

## Version 2.2.14 — 2026-07-14 — Dark-mode search results cleanup

### Enhancements

- **`search.php`** — Replaced hardcoded white card, border, and text colours with theme token-driven styling so search results render cleanly in dark mode.
- **`search.php`** — Added theme-aware hover, thumbnail, excerpt, and relevance badge styling for better contrast and consistency.
- **`functions.php`** — `_S_VERSION` constant bumped from `2.2.13` to `2.2.14` for cache busting.
- **`style.css`** — Theme header version updated to `2.2.14`.

### Files changed

- **`search.php`**
- **`functions.php`**
- **`style.css`**
- **`CHANGELOG.md`**
- **`readme.txt`**

## Version 2.2.13 — 2026-07-14 — Complete search + visible relevance

### Enhancements

- **`functions.php`** — Frontend main search now includes searchable Theatre Manager CPT content stored in post meta and taxonomy terms, not just post title/content/excerpt.
- **`functions.php`** — Search SQL hardened for SQLite-backed local sites by removing fragile escape assumptions and fixing JOIN alias detection for taxonomy tables.
- **`functions.php`** — Added weighted relevance scoring so exact/partial title matches rank above excerpt, content, meta, and taxonomy-only matches.
- **`search.php`** — Search result cards now display a visible relevance label and numeric score.
- **`functions.php`** — `_S_VERSION` constant bumped from `2.2.12` to `2.2.13` for cache busting.
- **`style.css`** — Theme header version updated to `2.2.13`.

### Files changed

- **`functions.php`**
- **`search.php`**
- **`style.css`**
- **`CHANGELOG.md`**
- **`readme.txt`**

## Version 2.2.4 — 2026-07-04 — Admin bar dark-mode bleed fix

### Bug fixes
- **`functions.php`** — Dark-mode heading, body-text, and link rules were using
  `:not(.site-footer *):not(.tm-top-notification-bar *)` pseudo-exclusions.
  `:not()` does **not** accept descendant selectors in CSS Selectors Level 3;
  in Level 4 it is accepted but produces very high specificity and still bled
  into `#wpadminbar` (the WP admin bar) because the admin bar is neither
  `.site-footer` nor `.tm-top-notification-bar`.
  All three blocks (headings, `p`/`li`, links) have been rewritten as
  **explicit positive content-zone selectors**:
  `.entry-content`, `.widget-area`, `.wp-block-group`, `.wp-block-column`,
  `main` — mirroring the light-mode selector set added in v2.2.3.
  The admin bar, site header, and hero zones are now naturally excluded
  because they are outside every content zone.

---

## Version 2.2.3 — 2026-07-04 — Global link colour bleed fix

### Bug fixes
- **`functions.php`** — Replaced bare `a { color: var(--tm-link) }` with
  content-scoped selectors (`.entry-content a`, `.widget-area a`,
  `.wp-block-group a`, `.wp-block-column a`,
  `main a:not(.site-header a):not(.site-footer a):not(.wp-block-button__link)`)
  so body-copy link colour no longer bleeds into the site header, admin bar,
  or WP admin pages.
- **`functions.php`** — Added `.site-header .site-branding a`,
  `.site-header .custom-theme-logo-link`, `.site-header .tm-logo-wrap` to
  the header text-colour block so the logo link matches nav link colour.
- **`functions.php`** — Dark-mode bare `a` rule now excludes `.site-header a`
  via `:not(.site-header a)` to prevent overriding header colours in dark mode.
- **`functions.php`** — `tm_inject_customizer_css_variables()` and
  `tm_output_dark_mode_css()` now return early on admin pages
  (`is_admin() && !is_customize_preview()`) so frontend theme CSS tokens
  do not bleed into the WP admin interface.
- **`functions.php`** — `_S_VERSION` constant bumped from `2.1.10` to `2.2.3`
  so style.css cache is properly busted on update.
- **`style.css`** — Version header updated to `2.2.3`.

---

## Version 2.2.2 — 2026-07-04
- CF7 label near-white bug fixed.
- CF7 Light/Dark integration.
