# StageKitWP Search Optimizer Changelog

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