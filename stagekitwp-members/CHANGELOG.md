# Changelog

All notable changes to **StageKitWP Members** are documented here.
This project adheres to [Semantic Versioning](https://semver.org/).

## [3.2.7] — 2026-11-01

### Changed — Events, Announcements and Email Templates use the native admin screens
- The Members hub's Events, Announcements and Email Templates tabs now link to the standard WordPress list/edit screens (`edit.php?post_type=...`) instead of a custom in-hub form, giving them the native screen options, search, filters, quick edit and bulk actions.
- Added a "Duplicate" row action on the list screens for all three post types (`class-stagekitwp-members-duplicate.php`); it copies the title, content, meta and taxonomy terms into a new draft.
- Trash and Delete Permanently already come from WordPress core for any registered post type; no code was required beyond registering the types for admin UI.

### Fixed — Producers/Executives could not edit Announcements or Email Templates
- Announcements and Email Templates were registered with the generic `post` capability type, which only built-in roles like Administrator and Editor hold (`edit_posts`, `delete_posts`, etc.). Producers and Executives — who only have this plugin's custom capabilities — could see the native list screens but got "Sorry, you are not allowed" when trying to add, edit, trash or delete an item.
- Both post types now use dedicated capability types (`stagekitwp_ann`/`stagekitwp_anns` and `stagekitwp_email`/`stagekitwp_emails`, mirroring the existing Events pattern) with `map_meta_cap`. `STAGEKITWP_MEMBERS_Roles` grants the new primitive capabilities to Producer/Executive (Announcements, alongside Events) and Executive (Email Templates), and mirrors them onto Administrator. Capability version bumped to `1.0.3` so existing sites re-sync automatically.

### Fixed — Capability re-sync never actually ran, so Administrators (and everyone else) never received new capabilities
- `STAGEKITWP_MEMBERS_Roles::init()` registered its re-sync on `plugins_loaded` at priority 6, but `init()` is only ever called from this plugin's own `plugins_loaded` bootstrap at priority 20 — by then `plugins_loaded` has already passed priority 6, so the newly-added callback could never fire on that pass, and `plugins_loaded` only fires once per request. In practice this meant the capability map was silently frozen at whatever it was when the site was last activated/reactivated; every subsequent `CAPS_VERSION` bump (including the Announcements/Email Templates fix above) was never applied, leaving Administrators without the new `edit_stagekitwp_ann`/`edit_stagekitwp_email`-family capabilities.
- `init()` now calls `maybe_sync()` directly instead of hooking it, since it is already running inside `plugins_loaded`. Verified via WP-CLI that Administrators, Producers and Executives now hold the expected capabilities after a normal page load, with no reactivation required.

## [3.2.6] — 2026-11-01

### Fixed — New announcements did not send email
- The Members hub announcement form published the post before saving the "Also email members" flag, so the publish hook saw the flag unset, skipped email, and locked the one-time delivery guard. The flags are now saved with the post (`meta_input`) so they exist when the publish transition fires.

## [3.2.5] — 2026-11-01

### Added — Register button on login page
- The custom login form now shows a "Register Here" button. It links to the open-registration page when open registration is on, otherwise to the invitation Register page.

## [3.2.4] — 2026-11-01

### Added — Front-end-only login
- New Settings option "Front-End Only" (on by default): everyone except Administrators (`manage_options`) is redirected to the front end after login, has the admin bar hidden, and is redirected away from `/wp-admin/` (AJAX, admin-post and uploads still work). Filter: `stagekitwp_members_frontend_only_user`.

## [3.2.3] — 2026-11-01

### Fixed — Invitation link
- The invitation email is sent as HTML but the link was plain text with newlines, so it was not clickable. The email now has a proper HTML link.
- The link pointed to `/register/`, which 404'd when no Register page existed. A published `register` page containing `[stagekitwp_members_register]` is now created automatically on first use (disable with the `stagekitwp_members_autocreate_register_page` filter).

## [3.2.2] — 2026-11-01

### Fixed — Invitation form did nothing
- The Invitations form is rendered inside the Members hub (`page=stagekitwp-ma-admin`), but the submit handler only ran on `page=stagekitwp-ma-invitations`, so the POST was silently ignored. Send, resend and revoke now use `admin-post.php` handlers and redirect back to the hub's Invitations tab.

## [3.2.1] — 2026-11-01

### Fixed — Member invitation creation
- Added the invitations table to the database migration runner and bumped the schema version to 1.6.0 so existing installs create the previously omitted table.
- Check database insert and email delivery results. Failed writes now show an actionable error instead of reporting success; failed email sends clearly say the invitation was saved and can be resent after mail configuration is fixed.

## [3.2.0] — 2026-11-01

### Completed — Members development plan
- Completed the approved Phase 0 → 1 → 4 → 2 → 3 → 5 plan and consolidated the finished work into the planned minor release.
- Added a reusable read-only WP-CLI smoke test and a comprehensive cross-phase acceptance plan covering roles, access rules, payment hooks, volunteer workflows, email campaigns, accessibility/mobile, and uninstall.
- Completed guarded uninstall cleanup for plugin settings, access/volunteer metadata, membership and volunteer tables, user metadata, custom roles and Administrator capabilities, and scheduled queue/reminder jobs.
- Improved keyboard focus visibility and mobile tap targets for event RSVP and volunteer controls; made RSVP history horizontally navigable on narrow screens with accessible table headings.
- Updated shortcode/testing documentation, on-site Member Instructions metadata, and release JSON changelog entries.

## [3.1.6] — 2026-11-01

### Added — Phase 5 polish and release validation
- Added `tests/smoke.php` and `docs/test-plan.md` for repeatable smoke and manual acceptance testing across all completed phases.
- Expanded explicit-purge uninstall handling to remove all plugin settings, access/volunteer post metadata, Administrator capabilities, and all scheduled queue/reminder events.
- Added visible keyboard focus, mobile-sized event RSVP/volunteer controls, and an accessible horizontally scrollable RSVP history table.

## [3.1.5] — 2026-11-01

### Added — Phase 3 email and interest search
- Extended the Producer dashboard filters to include role, availability on a date, event RSVP status and volunteer signup status, alongside name and interest.
- Added reusable recipient groups from saved filter definitions, current-filter recipient previews, opt-out exclusions and per-group delivery counts/history.
- Added event, volunteer position and date merge fields (`{event}`, `{position}`, `{date}`) to campaign messages and subjects.
- Added signed, confirmation-based unsubscribe links and a member email-preferences link to bulk campaigns.
- Campaigns now enter the bounded queue (500 recipients per campaign, 1,000 pending jobs total, 25 sends per minute); campaign logs retain the saved group identifier.
- Bumped the email-log schema to DB version 1.5.0 and added cleanup for saved groups on explicit uninstall.

## [3.1.4] — 2026-11-01

### Added — Phase 2 volunteer signup
- Added the `stagekitwp_members_positions` and `stagekitwp_members_volunteers` tables (DB version 1.4.0), including reusable position templates, capacity counts, signup notes and reminder tracking.
- Added position editors to both event editing surfaces, with role/interest requirements, per-event public-visibility override and reusable templates.
- Added signup/cancel and waitlist handling with capacity-safe slot reservation, waitlist promotion on cancellation, plus confirmation and promotion emails respecting event notification preferences.
- Added `[stagekitwp_members_volunteer]` and `[stagekitwp_members_my_volunteering]`; event cards now include eligible volunteer opportunities and a “Volunteers needed” heading.
- Added public read-only open-position counts (off by default), Producer/Executive roster screen and CSV export, plus capability-gated REST roster and signup endpoints.
- Implemented the `stagekitwp_members_before_signup` and `stagekitwp_members_after_signup` actions. Added daily reminders for confirmed volunteers one day before the event.
- Updated uninstall cleanup for the new tables, setting and reminder schedule; repaired the Settings class early-return that prevented its settings UI from loading.
- Added `docs/shortcodes.md` and documented volunteer hooks in `docs/hooks.md`.

## [3.1.3] — 2026-10-31

### Added - Phase 4 payment contract
- Membership tables (`stagekitwp_members_memberships` and a history log), DB version 1.3.0. Expiry reads as lapsed without cron.
- Hooks for a payment plugin: `stagekitwp_members_payments_enabled`, `payment_required`, `membership_status`, `checkout_url` filters and `payment_complete`, `access_granted`, `access_revoked` actions. See `docs/hooks.md`.
- New access rule "Paid / active membership"; passes for any member while no payment plugin is active.
- Optional event "Member price" field.
- Membership status and expiry editor on the user profile screen for Executives and administrators.
- Uninstall drops the new tables.

## [3.1.2] — 2026-10-31

### Added - Phase 1 access control
- "Who can see this?" meta box on pages, posts and other chosen public post types, plus a per-menu-item rule in Appearance > Menus.
- Rules: everyone, logged in, logged out, any member, minimum level, or specific roles.
- Server-side enforcement with blocked behaviour of login redirect, message or 404 (site default and per-item override).
- Restricted content is excluded from archives, search, feeds, sitemaps and REST.
- Shortcode `[stagekitwp_members_only]` (attributes roles, level, rule, message).
- Settings > Access Control section and an Access column in post lists.
- SEO plugin: restricted posts are left out of descriptions and llms files.

## [3.1.1] — 2026-10-31

### Changed - Phase 0 cleanup
- Added public access API (`stagekitwp_members_get_user_level`, `stagekitwp_members_user_can_access`, filter `stagekitwp_members_can_access`).
- Health tab warns about plain WordPress subscribers and offers a Convert to Member action.
- Removed unused legacy stub files; replaced legacy TM naming.

## [3.0.0] — 2026-08-10

### Changed — Tabbed ecosystem admin release
- TM Members Area now fits inside the shared Theatre Manager admin hub, with the main member workflow and supporting screens rendered as embedded tabs instead of scattered top-level pages.
- Announcements, Events, and Email Templates remain in the embedded workflow so member admin stays compact and consistent with the rest of the ecosystem.
- Version metadata was advanced to match the ecosystem-wide admin restructuring.

## [3.1.0] — 2026-10-31

### Added — Event location, format, status and cost
- `[stagekitwp_members_events]` (default and `layout="all-details"`) now shows the location, format, status (cancelled/postponed), price ("Free" when blank or 0) and a tickets link. The online link is shown to members only.
- Events now have a Venue (Core venues CPT), free-text Location, Format (in person/online/hybrid), online link, Event status, Price and tickets link, in both the event editor and the admin Events panel. Events also support a featured image and excerpt.
- iCal feeds include `LOCATION`; the REST event payload includes `location` and `status`.

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
