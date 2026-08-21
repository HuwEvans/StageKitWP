# Changelog

All notable changes to **TM Members Area** are documented here.
This project adheres to [Semantic Versioning](https://semver.org/).

## [3.0.0] — 2026-08-10

### Changed — Tabbed ecosystem admin release
- TM Members Area now fits inside the shared Theatre Manager admin hub, with the main member workflow and supporting screens rendered as embedded tabs instead of scattered top-level pages.
- Announcements, Events, and Email Templates remain in the embedded workflow so member admin stays compact and consistent with the rest of the ecosystem.
- Version metadata was advanced to match the ecosystem-wide admin restructuring.

## [Unreleased]

### Added — `layout` attribute for `[tm_ma_events]`
- New `layout="all-details"` attribute value for the `[tm_ma_events]` shortcode.
- When set, all events are displayed in a single list (no upcoming/past split) with full details: date, start time, end time, visibility badge, excerpt, full content, RSVP counts (going/maybe/declined/attended), and RSVP buttons.
- Usage: `[tm_ma_events layout="all-details"]`

## [2.1.4] — 2026-07-16

### Changed — Theme light/dark token support for member shortcodes
- Front-end shortcode styles now consume Theatre Manager theme color tokens
  (`--tm-surface-bg`, `--tm-body-text`, `--tm-heading-text`, `--tm-link`,
  `--tm-link-hover`, `--tm-primary`) so they follow the global light/dark
  mode toggle.
- Updated shortcode CSS packs: events, availability, announcements,
  directory/profile, messaging, auth forms, and nav-menu injected items.
- `[tm_ma_events]` heading, cards, links, RSVP buttons, notices, and status
  pills now switch consistently with theme mode.

## [2.1.3] — 2026-07-16

### Changed — Events shortcode public-mode cleanup
- `[tm_ma_events]` no longer renders duplicate section headings when using the
  `title` attribute; custom title now replaces the default Upcoming heading.
- Public (logged-out) event cards now hide RSVP response counts
  (`going` / `maybe`) for a cleaner unauthenticated view.

## [2.1.2] — 2026-07-16

### Added — Event visibility + shortcode title option
- Event editor now includes a **Visibility** field (`Public` / `Private`).
- Public events are visible to logged-out visitors and non-members.
- Private events are visible only to logged-in members with event access.
- `[tm_ma_events]` now supports a `title` attribute for custom heading text,
  for example: `[tm_ma_events title="Community Events"]`.

### Changed
- Public events shortcode view no longer shows iCal subscribe links when the
  visitor is logged out.
- Public `/events.ics` feed now includes only events marked `Public`.

### Documentation
- Instructions metadata now lists all plugin shortcodes, including
  `[tm_ma_member_profile]` and `[tm_ma_rsvp]`.

## [2.1.1] — 2026-07-07

### Fixed — User role assignment & nav menu visibility
- **Root cause identified**: users assigned the built-in WordPress `subscriber`
  role held none of the TM capability flags, so every member menu item was
  correctly hidden by the capability filter — but silently, with no error.
  Users must have the `tm_member` (or higher) role for member menu items to
  appear. Existing subscribers should be re-assigned via
  **Users → Edit User → Role → Member** or WP-CLI
  `wp user set-role <email> tm_member`.
- **Log Out safety net**: `tm_logout` visibility is now explicitly documented
  as applying to *any* logged-in user regardless of TM role, so Editors,
  Authors, and other non-TM roles always get a Log Out link.

## [2.1.0] — 2026-07-07

### Added — Custom Login, Open Registration, Cloudflare Turnstile & Nav Menu

#### Custom Login & Registration (`class-tm-ma-auth.php`)
- **`[tm_ma_login]`** shortcode — a fully themed login form that replaces
  `/wp-login.php`. Shows username/password fields, a Remember&nbsp;me
  checkbox, Forgot&nbsp;password link, and (when open registration is on) a
  *Create an account* link. If the visitor is already logged in it shows
  their name and a Log&nbsp;Out button instead.
- **`[tm_ma_open_register]`** shortcode — a public self-registration form
  (first name, last name, username, email, password + confirm). New accounts
  receive the **Member** role automatically and are logged in immediately.
  Completely separate from the invitation-only `[tm_ma_register]` flow.
- **Custom login redirect** — when *Custom Login Page* is enabled, any visit
  to `/wp-login.php` (login action only) is transparently redirected to the
  configured page. Password reset, logout, and all other WP-native login
  flows are unaffected.
- **Error handling** — form errors survive the POST→redirect cycle via
  60-second transients keyed by client IP, so the message appears reliably
  without session state.
- **After-login redirect** — a configurable page to send members to after a
  successful login (defaults to the site home).
- All form POSTs are nonce-protected. The registration handler validates
  username availability, email uniqueness, password length (≥ 8), and
  password confirmation before creating any user.

#### Cloudflare Turnstile (`class-tm-ma-auth.php`)
- Optional bot-protection widget on the login form using Cloudflare Turnstile
  (free tier available).
- The Turnstile JS (`api.js`) is enqueued **only** on the login page —
  no global script load on every page.
- Server-side token verification via
  `https://challenges.cloudflare.com/turnstile/v0/siteverify`.
- Requires both a site key and a secret key to be saved. If either is
  missing the widget is silently disabled so the login form is never broken
  by an incomplete configuration.
- Supports all Cloudflare widget modes (Managed, Non-Interactive, Invisible)
  — the widget type is chosen on the Cloudflare dashboard.

#### Permission-aware Nav Menu (`class-tm-ma-nav-menu.php`)
- **Custom meta box** in Appearance → Menus: a **TM Members** panel lists
  nine ready-made link types. Editors tick items and click *Add to Menu*,
  exactly like the built-in Pages or Custom Links panels.
- Each item stores its type in `_tm_ma_type` post meta. At render time a
  `wp_nav_menu_objects` filter removes items the current visitor cannot see
  and rewrites placeholder URLs to live ones — no page-ID configuration
  required.
- **URL resolution**: Settings override first, then slug-based auto-detect
  (e.g. a page with slug `/profile` is found automatically). Login and
  Logout URLs are resolved through `TM_MA_Auth`.
- **Nine link types** with built-in visibility rules:

  | Item | Visible when |
  |---|---|
  | Log In | Logged out only |
  | Log Out | Any logged-in user |
  | My Profile | Member+ (`tm_ma_manage_profile`) |
  | Events | Member+ (`tm_ma_rsvp_events`) |
  | Announcements | Member+ (`tm_ma_rsvp_events`) |
  | Availability | Member+ (`tm_ma_rsvp_events`) |
  | Messages | Member+ (`tm_ma_message_members`) |
  | Directory | Member+ (`tm_ma_search_members`) |
  | Producer Dashboard | Producer+ (`tm_ma_manage_events`) |

- Items carry semantic CSS classes (`tm-ma-nav-login`, `tm-ma-nav-logout`,
  `tm-ma-nav-dashboard`, etc.) for easy theme styling.
- **Important**: member items only appear when the user has the `tm_member`
  (or higher) TM role. Users with the built-in WordPress `subscriber` role
  have no TM capabilities and will see only Log In / Log Out.

#### Settings — new sections
- **Login & Registration**: enable/disable the custom login page; select the
  Login page, Registration page, and After-Login Redirect page.
- **Cloudflare Turnstile**: enable/disable; site key and secret key fields.
- **Navigation Menu**: direct link to Appearance → Menus; optional page URL
  overrides for sites whose member pages use non-standard slugs.

## [2.0.0] — 2026-07-06

### Security & hardening (Phase 5)
- **Profile image uploads** are now validated: images only
  (JPG/PNG/GIF/WEBP), a 2 MB size cap, and a real-image check via
  `getimagesize()` — a renamed `.php` or oversized file is rejected.
- **Avatar fallback**: a deleted or missing profile image now degrades to
  an initial-letter placeholder instead of a broken-image icon, in both
  the directory grid and the single profile view.
- **`uninstall.php`**: deleting the plugin is a no-op by default (data is
  preserved). A new "Delete all data on uninstall" setting must be enabled
  first; only then are the custom tables, options, roles, CPT content, and
  plugin user meta removed.
- **i18n**: the `tm-members-area` text domain is now loaded from
  `/languages`, with a POT template added.
- **Nonce/capability audit**: verified every state-changing handler
  (profile, events, announcements, RSVP, availability, check-in, export,
  email queue, messaging, REST) enforces a nonce and the correct capability
  or access check.

## [1.8.0] — 2026-07-06

### Added — REST API (Phase H)
- New `tm-ma/v1` REST namespace, all endpoints capability-gated:
  - `GET /members`, `GET /members/<id>` — directory-listed members only
    (`tm_ma_search_members`); never returns email or private meta, and
    404s for unlisted members so their existence is not leaked.
  - `GET /events`, `GET /events/<id>` — published events
    (`tm_ma_rsvp_events`), with `?when=upcoming|past|all`.
  - `GET /events/<id>/rsvps` — RSVP roster + counts (`tm_ma_manage_events`).
  - `POST /events/<id>/rsvp` — set the current user's RSVP
    (`tm_ma_rsvp_events`).

### Fixed
- Members now have the `tm_ma_search_members` and `tm_ma_message_members`
  capabilities, so the opt-in member directory is browsable by any logged-in
  member as designed (previously Producer-only). Capability map bumped to
  1.0.2 and re-synced.
- `TM_MA_Directory::role_label()` and `directory_page_id()` are now public
  helpers; `role_label()` accepts a user ID or object.

## [1.7.0] — 2026-07-06

### Added — iCal feed (Phase G)
- Subscribe-able calendar feed of published events:
  `/events.ics` (public) and `/?tm_ma_ical=me&uid=&token=` (a member's
  RSVP'd events, secured by a stable per-user token).
- RFC 5545 output: all-day vs timed events, text escaping, and 75-octet
  line folding.
- "Subscribe to all events" and "Subscribe to my events" links on the
  Events page.
- Canonical trailing-slash redirect suppressed for `/events.ics`.

## [1.6.0] — 2026-07-06

### Added — Announcements (Phase F)
- New `tm_announcement` post type with an admin **Delivery** panel.
- `[tm_ma_announcements]` feed for members, with important notices
  highlighted.
- On publish, announcements are delivered on-site and (by default) by email
  to members who have not opted out. Executives can mark an announcement
  **Important** to bypass the announcement opt-out.
- One-time delivery guard prevents re-sending when a published announcement
  is edited.

## [1.5.0] — 2026-07-06

### Added — Casting & Availability (Phase E)
- New `tm_ma_availability` table and `TM_MA_Availability` API for member
  availability ranges (available / unavailable / tentative) with notes.
- `[tm_ma_availability]` shortcode: members add and remove date ranges from
  the front end.
- **Cast Availability** grid on the event editor: for every member who has
  RSVP'd, shows their availability against the event date so producers can
  spot conflicts at a glance.

### Added — Events & Attendance (Phase D)
- New `tm_ma_rsvps` table and `TM_MA_RSVP` API — a queryable, scalable
  replacement for the old per-user RSVP meta. One-time, idempotent migration
  copies legacy RSVPs into the table.
- `[tm_ma_events]` shortcode: upcoming/past productions with three-state RSVP
  (Going / Maybe / Can't make it) and live response counts.
- `[tm_ma_my_rsvps]` shortcode: a member's RSVP history with attendance.
- Attendance check-in roster on the event editor (mark attended).
- RSVP CSV export now includes RSVP status and attendance columns.

### Added — Member Directory (Phase C)
- `[tm_ma_directory]` shortcode: searchable, filterable member grid
  (opt-in; members are hidden until they choose to be listed).
- Read-only member profiles with bio, interests, and a message link.
- "List me in the member directory" privacy toggle on the profile form.

### Added — Producer Tools (Phase B)
- Real member search filtering by name/email and interest.
- CSV export for members, event RSVPs, and the email log
  (nonce-protected, capability-gated, UTF-8 BOM for Excel).

### Added — Notification Preferences (Phase A)
- `TM_MA_Notifications` with four notification types and opt-out defaults.
- Preference-aware routing for messages and bulk announcements.
- Executive/admin "Important" override to bypass announcement opt-out.
- Notification checkboxes on the profile form.

### Changed / Fixed
- Parent admin menu now registers at priority 9 so submenus (Export,
  Invitations) resolve correctly — fixes a 403 on those pages.
- PHP 8.4 compatibility: explicit `fputcsv()` escape argument.
- All new tables and queries are portable across MySQL and SQLite.

## [1.4.0] — Email Queue (Phase 4)
- Rebuilt `TM_MA_Email_Queue` with durable job records, batching
  (25/run), retry with attempt ceiling (3), pruning, and scheduled sends.
- Admin queue viewer with retry/delete/clear.

## [1.3.0] — Messaging (Phase 3)
- `[tm_ma_conversation]` thread view and participant-guarded reply form.
- Improved `[tm_ma_conversations]` list; fixed serialized meta_query bug.
- Notifications routed through the central logged send path.

## [1.2.0] — Invitations & Registration (Phase 2)
- Full invitation lifecycle (pending → accepted / revoked) with tokens,
  resend, and revoke.
- `[tm_ma_register]` shortcode and invitations admin screen.

## [1.1.0] — Roles & Capabilities (Phase 1)
- Member, Producer, and Executive roles with eight custom capabilities.
- Version-gated role re-sync; mapped event capabilities.

## [1.0.0] — Foundation (Phase 0)
- Central versioned migration runner (`TM_MA_DB`) on `plugins_loaded`.
- Portable table-existence checks; fixed cron interval and activation hooks.
- Initial membership system integrated with Theatre Manager.

[2.0.0]: https://miltonplayers.example/tm-members-area
[1.8.0]: https://miltonplayers.example/tm-members-area
[1.7.0]: https://miltonplayers.example/tm-members-area
[1.6.0]: https://miltonplayers.example/tm-members-area
[1.5.0]: https://miltonplayers.example/tm-members-area
