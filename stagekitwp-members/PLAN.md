# StageKitWP Members — Development Plan

Living document. Update the checkboxes, the Decisions log and the Progress log as work lands.
Baseline: Members 3.1.0 (see `CHANGELOG.md`). Requires `stagekitwp-core`.

## Goals

1. **Volunteer signup** for events (positions members can sign up for).
2. **Email system and interest search** (extend what exists).
3. **Role-based protection** for menu items, pages and posts (like a membership plugin).
4. **Payment hooks** so a separate `stagekitwp-payment` plugin can add paid options.

## Build order

0 → 1 → 4 → 2 → 3 → 5

Reason: access control and the payment contract shape everything else, and the payment plugin can start once Phase 4 is finished. Phases 2 and 3 are independent of payment.

## Current state (baseline)

- Roles: Member, Producer, Executive (cumulative). Admins mirror all caps. Cap version `CAPS_VERSION` re-syncs roles.
- Tables: invitations, email log, RSVPs, availability.
- Events (`stagekitwp_event`): date/time, visibility public/private, venue, location, format, online URL, status, price, tickets URL. RSVP going/maybe/declined, attended/check-in.
- Email: bulk email, templates (`stagekitwp_email`), queue (cron, batch 25, scheduled sends), log, per-member opt-out prefs, Important override.
- Search: producer dashboard searches members by name and interest (`stagekitwp_interest` user taxonomy).
- Nav menu: only the plugin's own items are permission-aware (`class-stagekitwp-members-nav-menu.php`, `wp_nav_menu_objects`).
- Hooks that exist today: `stagekitwp_members_register_url`, `stagekitwp_members_messages_url`, `stagekitwp_members_turnstile_host_allowed`, `stagekitwp_members_member_registered`.

## Phase 0 — Foundation cleanup

- [x] Replace legacy `tm_ma_*` / `tm_member` names in docs, `CHANGELOG.md`, `plugin-info.json`, `stagekitwp-members-area.json`, on-site instructions content.
- [x] Remove the ~100-byte `class-stagekitwp-ma-*.php` stubs and `stagekitwp-members-area.php` stub (confirm nothing requires them first).
- [x] Add public API: `stagekitwp_members_user_can_access( $rule, $user_id = 0 )`, `stagekitwp_members_get_user_level( $user_id )`.
- [x] Decide handling of the plain WordPress `subscriber` role: decided — not mapped; add Health-tab warning + "Convert to Member" action and document it.
- [x] Consolidate the three JSON metadata files (`plugin-info.json`, `stagekitwp-members.json`, `stagekitwp-members-area.json`) or document which is canonical. (Done: documented in the progress log; `plugin-info.json` and `stagekitwp-members.json` are the update-feed files, `stagekitwp-members-area.json` is the legacy descriptor. Keep all three version-synced.)

## Phase 1 — Access control (menus, pages, posts)

Single central decision function; everything calls it.

- [x] Rule model: `everyone` | `logged_in` | `roles` (list) | `logged_out` (+ optional `min_level`), stored as post meta `_stagekitwp_members_access` (array).
- [x] "Who can see this?" meta box on all public post types (pages, posts, shows, events, other public CPTs), gated by a setting listing which post types are protected.
- [x] Menu item field in Appearance > Menus using the same rule model. Migrate the existing `_stagekitwp_members_visibility` meta.
- [x] Server-side enforcement on `template_redirect`; hide restricted items from archives, search, feeds, sitemaps and REST (`pre_get_posts`, `rest_prepare_*`).
- [x] Blocked-visitor behaviour: login redirect (with return URL) | message | 404. Global default, per-item override.
- [x] Shortcode `[stagekitwp_members_only roles="" message=""]` for partial content.
- [x] Filter `stagekitwp_members_can_access` (bool, $rule, $user_id, $post_id) so payment/other plugins can extend.
- [x] Settings tab section for access control; admin list-table column showing the rule.
- [x] Interaction with the SEO plugin: restricted content must not appear in `llms.txt`, sitemaps or schema (verify and coordinate).
- [x] Tests: logged out, member, producer, executive, admin; menu, page, post, REST, search, archive.

## Phase 4 — Payment contract (build before the payment plugin)

Members never handles money or card data. It exposes a contract; `stagekitwp-payment` implements it.

- [x] Membership table `stagekitwp_members_memberships` (user_id, level, status `active|lapsed|pending|none`, start, expiry, source, external_ref, timestamps) plus history; decided: dedicated table.
- [x] Filters/actions (document each in `docs/hooks.md`):
  - `stagekitwp_members_payment_required` (filter) — does this item (event, content, level) need payment?
  - `stagekitwp_members_membership_status` (filter) — resolved status for a user.
  - `stagekitwp_members_before_signup` / `stagekitwp_members_after_signup` (actions).
  - `stagekitwp_members_payment_complete` (action the payment plugin fires; Members grants access/updates record).
  - `stagekitwp_members_access_granted` / `stagekitwp_members_access_revoked` (actions).
  - `stagekitwp_members_checkout_url` (filter) — where to send a user who must pay.
- [x] Optional fields: "requires paid membership" on content (Phase 1 rule), price on events already exists; add "member price".
- [x] Safe default: if no payment plugin is active, nothing is gated by payment.
- [x] Capability for managing membership records (Executive).
- [x] `docs/hooks.md` developer reference with examples for the payment plugin.

## Phase 2 — Volunteer signup

- [x] Table `stagekitwp_members_volunteers` (event_id, position_id, user_id, status `confirmed|waitlist|cancelled`, note, created, updated). Use `dbDelta`; bump DB version; MySQL/SQLite safe.
- [x] Table `stagekitwp_members_positions` (event_id, title, description, slots, required interest/role, sort) and positions can be saved as reusable templates; decided: dedicated table.
- [x] Positions editor in the event meta box and the admin Events panel (reuse shared render/save code).
- [x] Front end: position list with Sign up / Cancel in `[stagekitwp_members_events]` and a dedicated `[stagekitwp_members_volunteer]` shortcode; “Volunteers needed” badge.
- [x] Capacity enforcement and waitlist promotion when someone cancels.
- [x] Producer roster + CSV export (nonce- and capability-gated).
- [x] Emails: confirmation, waitlist promotion, reminder one day before the event; honour notification prefs and the global email setting.
- [x] Member view: `[stagekitwp_members_my_volunteering]`.
- [x] REST: roster read for Producers; sign-up/cancel POST for members.
- [x] `stagekitwp_members_before_signup` and `stagekitwp_members_after_signup` actions fire for signup/cancel requests.
- [x] Optional public read-only view of open positions (counts only, no names; login required to sign up). Global setting, off by default, with per-event override.
- [x] Update uninstall to drop the new tables and setting and clear the reminder schedule.

## Phase 3 — Email and interest search
- [x] Producer dashboard filters: interest, role, availability on a date, RSVP status for an event, volunteer status.
- [x] Saved groups (stored filter definition) usable as recipient lists; "Email this group".
- [x] Recipient preview with count and opt-out exclusions shown before sending.
- [x] Merge fields in templates: member name, event, position, date.
- [x] Per-email unsubscribe/preferences link.
- [x] Per-group send history in the email log.
- [x] Rate and size safeguards in the queue for large groups.

## Phase 5 — Polish and release

- [x] Add a reusable WP-CLI smoke test and a role-by-role manual acceptance matrix for all phases.
- [x] Document keyboard, screen-reader, mobile, zoom, and theme checks; improve event-control focus visibility/tap targets and make RSVP history accessible on narrow screens.
- [x] Complete opt-in uninstall cleanup for plugin settings, access/volunteer metadata, tables, user meta, roles and Administrator capabilities, and scheduled queue/reminder hooks.
- [x] Update shortcode/testing documentation, on-site Member Instructions metadata, and JSON changelog entries.
- [x] Record Phase 5 as 3.1.6, then bump the completed release to 3.2.0 after all phases; synchronize plugin header, `STAGEKITWPMA_VERSION`, and all JSON metadata. (Follow-up invitation fix released as 3.2.1.)

## Decisions log

| Date | Decision |
|---|---|
| 2026-10-31 | Phased plan and build order 0 → 1 → 4 → 2 → 3 → 5 approved. |
| 2026-10-31 | Roles stay Member / Producer / Executive; paid/lapsed is a separate membership status, not new roles. |
| 2026-10-31 | Members exposes hooks only; `stagekitwp-payment` owns checkout, gateways and card data. |
| 2026-10-31 | Payment layer is optional; with no payment plugin everything behaves as today. |
| 2026-10-31 | WordPress `subscriber` is NOT mapped to Member. Document it; add a Health-tab warning listing subscriber users with a "Convert to Member" action. |
| 2026-10-31 | Membership records live in a dedicated table (level, status, start, expiry, source, external payment ref, history). Payment plugin writes only via a Members function or `stagekitwp_members_payment_complete`. |
| 2026-10-31 | Volunteer positions get their own table (stable IDs, reusable templates) alongside the signups table. |
| 2026-10-31 | Volunteer opportunities may be shown publicly read-only (counts only, no names), signup requires login. Off by default; global setting with per-event override; no schema output. |

## Open questions

None currently. (Resolved 2026-10-31: subscriber role, membership record storage, volunteer positions storage, public volunteer visibility. See Decisions log.)

## Conventions

- **Versioning:** bump the patch version after each completed phase (Phase 0 = 3.1.1, Phase 1 = 3.1.2, ...). When all phases are complete, bump the minor version (3.2.0). Update the plugin header, `STAGEKITWPMA_VERSION`, the three JSON files and CHANGELOG.md each time.

- Prefix: `stagekitwp_members_` for options, meta, hooks, shortcodes; classes `STAGEKITWP_MEMBERS_*`.
- Share render/save code between the CPT meta box and the admin panel (see `render_detail_fields` / `save_detail_fields`).
- Every handler: nonce + capability check; sanitize on input, escape on output.
- Schema changes via `dbDelta` with a DB version bump; keep MySQL and SQLite compatible.
- Each release: update `CHANGELOG.md`, header version, `STAGEKITWPMA_VERSION`, and the JSON metadata files.

## Progress log

- 2026-10-31: Plan created. Members 3.1.0 baseline (event location/format/status/price fields shown in the events listing).

- 2026-10-31 — Phase 0 complete: stubs removed, `includes/stagekitwp-members-api.php` added, subscriber Health warning with Convert action, legacy TM naming replaced in code and metadata (historical changelog entries left as-is). API and Convert action are syntax-checked only; test when Phase 1 starts.

- 2026-10-31 — Phase 1 complete (3.1.2): `class-stagekitwp-members-access.php`; rule model, meta box, menu item rules, enforcement, query/sitemap/REST exclusion, `[stagekitwp_members_only]`, Settings section, list column. Notes: no legacy `_stagekitwp_members_visibility` meta existed, so nothing to migrate (built-in Members menu links keep their own visibility). Feeds/search/archives are covered by `pre_get_posts`. SEO plugin 1.5.1 skips restricted posts in descriptions and llms. Tested via HTTP and eval-file as logged-out, member, subscriber and admin; the meta box and menu item UI were not exercised in the browser. Users with `edit_post` on an item can always view it.

- 2026-10-31 — Phase 4 complete (3.1.3): `class-stagekitwp-members-membership.php`, membership + history tables (DB 1.3.0), hooks and helper functions in the API file, `paid` access rule, event member price, profile-screen membership editor, `docs/hooks.md`. Tested via eval-file (status, expiry to lapsed, granted/revoked actions, history, filter override, paid rule with payments off and on). `stagekitwp_members_before_signup` / `after_signup` are documented as reserved and will be fired in Phase 2. Management capability reuses the Executive-only `stagekitwp_members_manage_members` (no new capability). The profile-screen UI and member price field were not exercised in the browser.

- 2026-11-01 — Phase 2 complete (3.1.4): volunteer positions/templates and volunteer signups tables (DB 1.4.0); shared event position editor; event and dedicated volunteer shortcodes; capacity reservation, waitlist promotion, email notices/reminders; public counts setting and per-event override; Producer roster/CSV export; REST roster and signup endpoints; signup hooks; uninstall cleanup; docs/shortcodes.md. Local Studio integration test confirmed capacity 1 gives one confirmed and one waitlisted signup, cancellation promotes the next volunteer, confirmed count remains 1, both hooks fire, and tables exist. PHP syntax and JSON metadata were checked. UI was not browser-tested; REST routes were not exercised over HTTP. The Members Settings class had a pre-existing early return that prevented it loading; removed to make its Settings UI (including the public volunteer visibility setting) operational.

- 2026-11-01 — Phase 3 complete (3.1.5): expanded Producer recipient search to role, date-specific availability, RSVP and volunteer state; saved reusable group filters; preview counts include announcement opt-outs; campaigns support `{event}`, `{position}` and `{date}` merge fields plus signed unsubscribe/preferences links. Group campaigns are queued at up to 500 recipients, with 25 messages processed per minute and a 1,000 pending-job cap. Email log rows retain group IDs for campaign history. Email-log schema advanced to DB 1.5.0; saved groups are removed only on opted-in uninstall. Validated by PHP lint, JSON/version checks and focused Studio integration tests.

- 2026-11-01 — Phase 5 complete (3.1.6); all approved phases complete, final release 3.2.0: added `tests/smoke.php` and `docs/test-plan.md` covering role/capability, access, payment, volunteer, email, responsive/accessibility, and lifecycle checks. Expanded opt-in uninstall cleanup to remove all plugin settings, access and volunteer post meta, Administrator capabilities, and every scheduled queue/reminder event. Added visible keyboard focus, 44px mobile targets, and a keyboard-navigable RSVP history scroll region with column-scoped headers. Updated shortcode/testing docs, on-site instructions, changelog, and synchronized release metadata. Validated the smoke test, PHP lint, JSON/version consistency, and focused Studio integration checks. Full interactive screen-reader/browser acceptance and destructive uninstall cases remain manual checks described in `docs/test-plan.md`.

- 2026-11-01 — Invitation hotfix (3.2.1): found `create_invitations_table()` was not called by the migration runner. Added it and advanced the schema to 1.6.0 so existing installs migrate. Invitation creation now reports DB write failures; when email fails after saving, the invitation remains in the list and the admin receives a warning with resend guidance. Validation: migration/table smoke test plus captured-success and simulated-mail-failure integration checks.

- 2026-11-01 — Native admin screens for Events, Announcements, Email Templates (3.2.7): the Members hub's Events/Announcements/Email Templates tabs now link to the standard `edit.php?post_type=...` list and `post-new.php?post_type=...` add-new screens instead of a custom in-hub form, so Trash, Delete, screen options, search/filter and bulk actions all come from WordPress core. Added a "Duplicate" row action (`class-stagekitwp-members-duplicate.php`) covering all three post types. Found and fixed a gap: Announcements and Email Templates were still using the generic `post` capability type, which only Administrator/Editor hold, so Producers/Executives could see the native screens but not actually add/edit/trash/delete. Both now use dedicated capability types (mirroring the existing Events pattern); `STAGEKITWP_MEMBERS_Roles` grants the new primitive caps to Producer/Executive (Announcements) and Executive (Email Templates), capability map bumped to 1.0.3. Validation: PHP lint on all changed files and JSON metadata parse checks; capability behavior was not exercised in the browser as Producer/Executive users.

- 2026-11-01 — Capability re-sync hotfix (3.2.7 follow-up): user reported Administrators still lacked full access. WP-CLI inspection on the running Studio site showed `stagekitwp_members_roles_version` stuck at `1.0.2` even after the `1.0.3` code change, and Administrator was missing every new `stagekitwp_ann`/`stagekitwp_email` capability. Root cause: `STAGEKITWP_MEMBERS_Roles::init()` registered `maybe_sync()` on `plugins_loaded` at priority 6, but `init()` itself only ever runs from this plugin's own `plugins_loaded` bootstrap at priority 20 — so the priority-6 callback was added after that priority had already fired and never ran, on every request, since whenever that bootstrap priority was introduced. `init()` now calls `maybe_sync()` directly. Validation: WP-CLI (`studio wp eval-file`) confirmed the version option advanced to `1.0.3` on the next request and that Administrator, Producer and Executive all hold the expected `edit_/delete_/publish_` capabilities for all three post types, plus a passing `tests/smoke.php` run.
