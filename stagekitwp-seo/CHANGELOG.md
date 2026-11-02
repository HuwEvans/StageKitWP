# StageKitWP Search Optimizer Changelog

## Version 1.5.1 — 2026-10-31

### Changed
- Posts restricted by the Members plugin are left out of meta descriptions, schema descriptions and the llms files.

## Version 1.5.0 — 2026-10-31

### Added

- Event schema for public Members events: timezone-aware start/end, venue or free-text `Place`, attendance mode (in person/online/hybrid), status (scheduled/cancelled/postponed) and a free-by-default `Offer`. The image falls back to the theme logo (Customizer > Logo Settings), then the core custom logo, then the site icon, when the event has no featured image. Events with no usable location (or private/undated) fall back to a plain Article. Uses the currency from the Audition Schema settings.
- llms.txt / llms-full.txt: Members event entries now include date, time, place and a cancelled/postponed flag.
- llms.txt / llms-full.txt: optional "About this file" notes block (editable, with factual defaults) and an automatic "Last updated" line.

- Audition `Event` schema on show pages: one free (price 0) Event per upcoming audition slot with start, duration and venue (slot venue, else show venue). Settings > Audition Schema (enable, currency). Auditions section added to llms.txt.

- Performance carousel schema: a URL-only `ItemList` of upcoming shows on the front page, season pages and pages using `[stagekitwp_season_shows]`. Settings > Carousel Schema lets you disable it, choose contexts, set a maximum, and include past shows.
- `/llms.txt` now lists the current season, its shows, sponsors, venues, events and pages, plus other seasons.
- New `/llms-full.txt` with show details, performances, cast and page text.
- Settings > LLMs.txt: enable/disable each file, editable intro, cast names on/off, sponsor output (details, link or none), content toggles and page text length.
- Output is cached and refreshed when content or settings change.

## Version 1.4.4 — 2026-10-07

### Added

- Show pages now include a standalone Schema.org `Event` in the JSON-LD graph for each valid performance date/time, with an end time calculated from the Show duration.

## Version 1.4.3 — 2026-09-19

### Fixed

- Show `TheaterEvent` schema `startDate` is now guaranteed: the season+time-slot fallback formula was corrected to season start date + 4/7/10 months (Fall/Winter/Spring) instead of fixed calendar dates, falls back to the season start date itself when no time slot is set, and falls back to the show's publish date as a last resort when no season is assigned.
- `stagekitwp_event` `Event` schema now falls back to the event's publish date when no event date is set, so `startDate` is never empty.

## Version 1.4.2 — 2026-09-18

### Fixed

- Show `Offer` schema now sets `validFrom` to the start date of the show's associated season instead of leaving it unset.
- Venue `location.image` now outputs a proper `ImageObject` (`@type` + `contentUrl`) instead of a bare URL string.

## Version 1.4.1 — 2026-09-07

### Added

- SEO controls, heading and image audits, and robots directives for StageKitWP public content types.
- Theatre-aware JSON-LD for shows, venues, seasons, and member events.
- An `/llms.txt` public-content discovery endpoint.

### Changed

- Added title and description length guidance of 50–60 and 150–160 characters.
- Landing-page shortcodes inherit show title, description, image, and TheaterEvent schema as fallbacks for their host page.
- Social and schema images now include available featured-image alt text.

### Fixed

- Removed the hidden Microformats H1 that duplicated the document heading.
- Suppressed generic metadata and schema that would duplicate a full SEO plugin's output.