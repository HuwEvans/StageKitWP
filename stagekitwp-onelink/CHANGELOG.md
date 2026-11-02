# StageKitWP OneLink Changelog

## Version 2.0.0 — 2026-09-23

### Added

- Dashicon or uploaded-image icon selection for every StageKitWP Ecosystem link and each Extra (custom) link.
- StageKitWP Core CPT wiring: season and show ticket links now resolve live from the `season`/`show` post types instead of static settings.
- Season & Show Tickets broken out into 8 individually toggleable elements: Current/Next Season ticket link, and Current/Next Fall, Winter, and Spring show links.
- Dependency checks for StageKitWP Core, Members, and Media — ecosystem controls are disabled and explained when the required companion plugin isn't active.
- Three new visual styles: Editorial Programme, Ticket Booth, and Marquee Board (in addition to Classic, Grid, Hero, Glass, Spotlight).
- Full paired light/dark color palette (brand, highlight, hero, hero button, link, canvas, surface, heading, border, muted text, shadow, Spotlight, hero badge) applied consistently across every style.
- Configurable OneLink header title and subtitle text.
- Font selector (System Sans, Inter, DM Sans, Space Grotesk, Source Sans 3, Merriweather) with a live preview sample, loaded only when the shortcode renders.
- Per-social-network placement: each enabled network can independently render as a bottom icon or as its own entry in the link tree.
- Live Style & Display preview panel with a Light/Dark toggle that mirrors the saved palette, style, header text, and font in real time.
- New drag-and-drop "Order" tab: every Ecosystem, Custom Link, and tree-placed Social entry can be arranged into one cohesive, saved order.

### Fixed

- Dark mode text no longer inherits the active theme's global `.entry-content a` link color; OneLink's configured dark palette now takes precedence.
- Light-mode styles (Glass, Ticket Booth, Editorial, Hero) no longer default to dark, low-contrast surfaces.
- Custom link ordering could silently fail to save because generated link IDs could contain uppercase characters that the order-key sanitizer stripped; IDs are now lowercase-safe end to end.

### Changed

- Version bumped 1.6.0 → 2.0.0 to reflect the scope of the ecosystem wiring, theming, and ordering work above.
