# StageKitWP Search Optimizer Changelog

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