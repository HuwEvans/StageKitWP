# Changelog

All notable changes to **TM IO – Import & Export** are documented here.
This project adheres to [Semantic Versioning](https://semver.org/).

## [2.0.0] — 2026-08-10

### Added — Tabbed ecosystem admin release
- TM I/O now sits under the shared Theatre Manager admin hub, matching the tabbed menu structure used across the ecosystem.
- Clear Data, Import, Export, Page Remap, Settings, and Instructions remain grouped in the compact hub so the whole suite is easier to navigate from one menu.
- Version metadata was advanced to mark the ecosystem-wide admin reorganization.

## [1.3.3] — 2026-07-07

### Performance — Imports are ~2.6× faster
- Meta was the bottleneck: `update_post_meta` ran a SELECT + write per key, and
  on SQLite each is its own commit. Fresh inserts now write all of a post’s meta
  in **one batched SQL INSERT**, and the batch runs with term counting deferred,
  cache invalidation suspended, and the per-post `save_post` handlers (60+
  callbacks from other plugins that only read `$_POST`, which is empty during
  import) temporarily detached. A 226-record import dropped from ~19s to ~7s.

### Fixed — Relationship remap now also works on older export files
- Relationship remapping needs each post’s original ID. Files exported before
  1.3.2 don’t carry that field, so the importer now also **derives the old ID
  from the post’s GUID** (`?p=123`) as a fallback. (Note: posts whose GUID is a
  pretty permalink with no ID, or references to posts that were already deleted
  before export, still can’t be reconstructed — re-export from the source with
  1.3.2+ for complete relationship data.)

### Note on the “38 imported / 181 skipped” report
- That happened on a pre-1.3.2 build: with no concurrency lock, overlapping
  runners re-fed the same batches and (once GUIDs were preserved) skipped them
  the second time. The 1.3.2 processing lock removed the re-feeding; a clean
  re-import now reports every record imported with 0 wrongly skipped.

## [1.3.2] — 2026-07-07

### Fixed — Posts imported 2–3 times (duplicates)
- The batched importer had no concurrency lock, so Action Scheduler, the
  self-rescheduling chain, and the browser progress-poll could all run the same
  job at the same batch offset at once — each re-importing the same slice.
  Deduplication couldn’t catch it because freshly inserted posts were given a
  brand-new GUID, so the “already imported?” check by GUID always missed.
- Added an atomic per-job **processing lock** (only one runner touches a batch
  at a time; stale locks are safely taken over) and the importer now
  **preserves the source GUID** on insert, so re-imports and any overlapping
  runner correctly detect existing posts and skip/update instead of duplicating.

### Fixed — Shows not linked to their seasons (and empty shortcodes)
- Relationships between posts are stored as **post IDs** in meta
  (`_tm_show_season`, `_tm_show_venue`, `_tm_cast_show`, `_tm_award_show_id`)
  and via `post_parent`. On import each post gets a new ID, but these references
  were never updated — so shows pointed at dead season IDs and the shortcodes
  (which query `meta_value = <season id>`) returned nothing.
- Export now records each post’s original ID, import builds an old→new ID map
  across all modules, and a finalize pass **remaps every relationship meta key**
  (and resolves `post_parent` by GUID) once all posts exist — in both the
  synchronous and batched/queued paths. Shows reconnect to their seasons and the
  shortcodes display their entries again.

## [1.3.1] — 2026-07-07

### Added — Export summary with media count
- The Export success message now reports what was bundled, e.g.
  *“✅ Export complete • 192 records • 12 images (36 files incl. sizes) — 16.3 MB”*.
  When media is off it reads *“media not included”*, and when no images are
  referenced it reads *“no media referenced”*.
- `TM_IO_Exporter::export()` now populates an optional `$stats` out-parameter
  (`post_count`, `media_count`, `media_files`, `filesize`, `include_media`) and
  the export AJAX response returns it for the UI.

## [1.3.0] — 2026-07-07

### Fixed — Media was never exported
- The exporter only collected media from the featured image and meta keys ending
  in `_image_id` / `_thumbnail_id`. Theatre Manager stores images in keys like
  `_tm_logo`, `_tm_show_sm_image`, `_tm_season_image_front`, `_tm_photo`,
  `_tm_venue_image`, and `picture` — and each value can be **either an**
  **attachment ID or a raw URL** — so no media was ever bundled.
- The collector now scans **every** meta value (scalar or nested array),
  detecting both attachment IDs and `/wp-content/uploads/` URLs, resolves URLs
  (including resized `-WxH` variants) back to their attachment, and also pulls
  upload URLs out of `post_content`. Original files plus all generated size
  variants are packed into the ZIP with a `media/index.json`.

### Added — “Include media files” toggle
- The Export page has a new **Include media files** checkbox (on by default).
  Uncheck it for a smaller, data-only ZIP when the destination already has the
  same media.

### Fixed — Imported media is now reconnected
- Previously, even when media was present the importer added the files but left
  posts pointing at the **old** attachment IDs/URLs, so images didn’t show on
  the destination. Import now runs media **first**, builds an old→new ID and URL
  map (covering size variants), and rewrites featured images, image meta values
  (IDs and URLs), and inline `post_content` URLs — in both the synchronous and
  the batched/queued import paths.

## [1.2.1] — 2026-07-07

### Fixed — Import progress bar never moved
- Queued (large) imports showed a progress bar stuck at 0% that never advanced,
  even though the import was actually queued. Two causes:
  1. **Percent was never calculated.** The progress record only ever held 0%
     (on start) or 100% (on completion), so the bar had nothing to animate. The
     queue now stores the job's total post count and recomputes the percentage
     after every batch (capped at 99% until the job is marked complete), and
     reports a `processed`/`total` readout.
  2. **The background runner did not fire.** Queued batches relied on WP-Cron /
     Action Scheduler ticking on their own, which is unreliable on local and
     many shared hosts, so the job sat idle. The progress-poll request now
     **self-drives the queue** — it processes one batch per poll — guaranteeing
     the import advances regardless of cron. It shares the same offset state as
     the background runner, so posts are never processed twice.
- The queue now rolls through empty / options-only modules within a single poll
  (instead of burning one poll each) and cleanly skips the internal `_media`
  pseudo-module, so the bar reaches 100% smoothly.
- The progress label now reads e.g. `55% • 125 of 226 records (imported 125,
  skipped 0)` and polling is faster (1.2s).

## [1.2.0] — 2026-07-07

### Fixed — Import of uploaded files
- Uploaded bundles were rejected with *"Unsupported import file type: .tmp"*
  because the file-type check read the PHP upload's temporary path (which ends
  in `.tmp`) instead of the real filename. The importer now uses the original
  upload name and, as a safety net, **sniffs the file's magic bytes**
  (`PK` for ZIP, leading `{`/`[` for JSON), so imports work even when the
  extension is missing or misleading. Both the Import and Dry Run handlers were
  fixed.

### Added — Configurable batch size
- New **TM I/O → Settings** page with a **Batch size** control (10–500,
  default 50). One setting governs every chunked operation.
- **Applied consistently across delete, import, and export**:
  - Clear Data deletion now uses the configured batch size (previously a
    hard-coded 50) and gains a per-run **Batch size** field on the page.
  - The background import queue processes posts in `tm_io_batch_size()` groups
    instead of a fixed constant.
  - Module export pages its read query by the configured batch size, so large
    sites are exported in bounded chunks.
- Added `tm_io_batch_size()` helper plus `TM_IO_BATCH_DEFAULT` / `_MIN` / `_MAX`
  constants; all consumers clamp to the safe range.

## [1.1.0] — 2026-07-07

### Added — Batched Clear Data & progress feedback
- **Batched delete**: the Clear Data (purge) tool now deletes posts in small
  server-side batches instead of one long request. Each batch removes at most
  50 posts and returns progress, so no single request can hit the PHP
  `max_execution_time` limit on large ecosystems.
- **Live progress bar**: the Clear Data page shows an animated progress bar
  with a live percentage and an "X of Y deleted (–N this batch)" readout. The
  on-page CPT count badges update as each batch completes.
- **New AJAX endpoint** `tm_io_purge_batch` (`batch_size` clamped 10–200) and
  a new `TM_IO_Purger::purge_batch()` method returning `deleted_this_batch`,
  `deleted_by_type`, `remaining`, `remaining_by_type`, `errors`, and `done`.
- **Stall guard**: if a batch deletes nothing but posts remain (undeletable),
  the loop stops and reports the errors instead of running forever.

### Added — In-plugin Instructions page
- New **TM I/O → Instructions** screen: a complete in-admin guide covering
  modules, export, import (ZIP/JSON and the CSV Season Builder), Page Remap,
  batched Clear Data, a full WP-CLI reference, and an FAQ.

### Added — Publishing assets
- Plugin icon (SVG + 128/256 PNG), banner (SVG + 772×250 / 1544×500 PNG),
  and admin screenshots under `assets/`.

## [1.0.0] — 2026-07-06

### Added — Initial release
- **Modular export/import** for the Theatre Manager ecosystem: Theatre Manager
  (core), Members Area, RC Library, TM Sync, and the Theatre Manager Theme.
  Any combination of modules can be exported to a single ZIP bundle (JSON data
  + manifest + referenced media) and imported on another site.
- **Import sources**: ZIP bundle, single-module JSON, or a remote HTTPS URL.
- **Conflict strategies**: skip (keep existing) or overwrite (replace matching
  posts), with a **Dry Run** that reports create/update/skip counts without
  changing anything.
- **Hybrid sync/async imports**: imports above the async threshold
  (200 records) are queued via Action Scheduler (if available) or WP-Cron and
  processed in the background with a progress readout, so large imports never
  time out.
- **CSV Season Builder**: build an entire season of Shows (and the Season that
  groups them) from a spreadsheet. Includes a downloadable template, title-based
  matching (so re-uploads update rather than duplicate), and a one-click
  **rollback token** to undo the last CSV import.
- **Page ID Remap**: after moving a site, reconnect page-based plugin settings
  (Directory, Events, Login, etc.) to the correct new page IDs from one screen.
- **Clear Data (purge)**: permanently delete TM content by full ecosystem,
  by plugin group, or by single CPT, with a count-first dry run and a typed
  `DELETE` confirmation.
- **WP-CLI**: `wp tm-io export`, `import`, `progress`, and `modules`.
- **Security**: TM Sync connection secrets are redacted from exports by default.
- **Portability**: all queries work on both MySQL and SQLite.

[1.3.3]: https://github.com/theatre-manager/tm-io
[1.3.2]: https://github.com/theatre-manager/tm-io
[1.3.1]: https://github.com/theatre-manager/tm-io
[1.3.0]: https://github.com/theatre-manager/tm-io
[1.2.1]: https://github.com/theatre-manager/tm-io
[1.2.0]: https://github.com/theatre-manager/tm-io
[1.1.0]: https://github.com/theatre-manager/tm-io
[1.0.0]: https://github.com/theatre-manager/tm-io
