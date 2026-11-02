# StageKitWP Members — Comprehensive Test Plan

This plan covers the new and expanded functionality delivered by Members Phases 0–5, plus the key existing flows those features build on. Run it on a disposable WordPress site with StageKitWP Core active. Do not run purge/uninstall cases on a production site.

## Test environment and fixtures

- WordPress 6.0+ and PHP 7.4+; run the database cases on both MySQL and SQLite when both are supported by the deployment pipeline.
- StageKitWP Core and StageKitWP Members enabled; use the current plugin version and a clean browser session.
- Create accounts for an Administrator, Executive, Producer, Member, plain WordPress Subscriber, and logged-out visitor. Use unique test email addresses.
- Create two published events on different dates, one private and one public, and one draft event. Give one event at least two volunteer positions, including a one-slot position and an eligible role/interest restriction.
- Create at least three members with different interests, roles, notification preferences, RSVP states, availability ranges, and volunteer states. Keep one member opted out of announcement emails.
- For mobile checks, use browser widths of 320, 375, and 600 CSS pixels; also test desktop at 1280 pixels and browser zoom at 200%.
- Use a disposable database snapshot before testing schema migrations or uninstall. Keep test emails directed to a mail-capture service or disable outbound delivery.

## Fast read-only smoke test

From PowerShell, run the checked-in WP-CLI script:

```powershell
studio wp --path "C:\path\to\my-wordpress-website" eval-file "C:\path\to\stagekitwp-members\tests\smoke.php"
```

The script registers its checks on WP-CLI's `after_wp_load` hook and verifies plugin loading/version, current schema and tables, role capabilities, representative shortcode registration, and logged-out access decisions. It does not create or modify site data.

## Phase 0 — API and foundation

1. As an Administrator, open Members Health. Confirm the warning identifies plain Subscribers without a StageKitWP member level.
2. Convert a test Subscriber using the provided action. Confirm the account receives the Member role and the warning/count updates.
3. Check `stagekitwp_members_get_user_level()` for Member, Producer, Executive, Administrator, Subscriber, and logged-out visitor. Expected: the highest StageKitWP level, Administrator as Executive, and no level for Subscriber/logged-out.
4. Check `stagekitwp_members_user_can_access()` with `everyone`, `logged_in`, `logged_out`, `members`, `level`, and `roles` rules for each fixture. Confirm the `stagekitwp_members_can_access` filter can allow and deny access without bypassing the returned boolean.
5. Confirm the three plugin metadata JSON files identify the same release version and parse as valid JSON.

## Invitation creation and delivery

1. Upgrade an install whose stored Members DB schema version is older than 1.6.0. Confirm the `stagekitwp_invitations` table is created and the version advances only after migrations finish.
2. As an Executive/Administrator, invite a valid new address with mail capture enabled. Confirm one pending invitation is listed and one message containing the matching registration token URL is captured.
3. Configure `wp_mail` to fail. Submit a different valid address. Confirm the invitation remains in the list and the admin sees a warning that sending failed, with guidance to check email configuration and use Resend.
4. Force a database insert failure in a disposable test environment. Confirm no green success notice or email is produced, and the admin sees an actionable database/migration error.
5. Try invalid email, an existing user's address, a duplicate invite, an unauthorized role, and a request without a valid nonce. Confirm each produces a safe error/denial and no unintended invite row.
6. Resend a pending invite and verify delivery status is reflected; revoke a pending invite and confirm its status changes. Verify accepted/revoked/expired tokens cannot register.
7. Check Members Health > Send Test Email separately. A successful `wp_mail` handoff is not proof of inbox delivery; confirm delivery in the site's SMTP/transactional-email provider logs.

## Phase 1 — Access control

1. In Members Settings, select the protected post types and save each blocked-visitor default (`login`, `message`, `404`). Verify unselected post types do not receive the access meta box.
2. For a page, post, and public CPT, save each rule type: everyone, logged in, logged out, any member, paid membership, minimum level, and selected roles. Include a per-item blocked-behaviour override. Reload the editor and verify values persist.
3. Repeat with a navigation menu item. Verify a hidden parent also hides its children and accessible menu entries remain in their original order.
4. Visit the protected permalink as logged out, Subscriber, Member, Producer, Executive, and Administrator. Verify access matches the rule; denied visitors see the configured redirect/message/404; authorized editors retain the documented edit bypass.
5. Verify the same denied content is excluded from archive/search queries, feeds, WordPress sitemaps, and REST responses. Verify public content remains present in each.
6. Exercise `[stagekitwp_members_only]` with an allowed role, a denied role, a custom message, and empty/default attributes.
7. With the payment extension disabled, verify the `paid` access rule does not accidentally lock out members. In a local test fixture, enable the payment filter and test active and lapsed membership outcomes.
8. Confirm the SEO integration omits protected items from generated descriptions, llms output, and schema while continuing to expose public items.

## Phase 4 — Payment contract and memberships

1. As an Executive, edit a test user's membership status/expiry. Confirm a nonce-less request and a Producer/Member attempt cannot change the record.
2. Exercise `STAGEKITWP_MEMBERS_Membership::set()` for active, pending, lapsed, and none. Verify the current membership row, append-only history row, source/reference/note fields, and expiry-derived lapsed status.
3. Fire `stagekitwp_members_payment_complete` with a valid test user. Confirm an active record is created/updated, the payment source is recorded, and access-granted fires only when transitioning to active.
4. Change an active membership to lapsed/none and confirm access-revoked fires once. Check duplicate unchanged statuses do not create false transition actions.
5. Register test callbacks for `payment_required`, `membership_status`, `checkout_url`, before/after signup, access-granted/revoked. Confirm arguments and contexts match `docs/hooks.md`.
6. Verify event member price saves, reloads, and is displayed separately from the standard price. Confirm Members itself never accepts or stores payment-card data.

## Phase 2 — Volunteer signup

1. Create, edit, reorder, and delete positions in both the event editor and Members Events panel. Save a reusable template and apply it to a second event. Verify requirements, capacity, description, and sort order persist.
2. As a Member, sign up for an open position. Confirm the roster and `[stagekitwp_members_my_volunteering]` show the right event, position, and status. Try a position with an unmet role/interest requirement.
3. Fill a one-slot position with two members. Confirm one is confirmed and the next eligible signup is waitlisted. Cancel the confirmed signup and verify the earliest waiter is promoted exactly once.
4. Submit duplicate and stale requests, cancel a waitlisted signup, and attempt signup while logged out. Confirm no duplicate active signup or capacity overrun; invalid/unauthorized requests are rejected.
5. Verify signup/cancel fire `stagekitwp_members_before_signup` and `stagekitwp_members_after_signup` with the documented event, position, member, and context values.
6. Verify confirmation, promotion, and one-day reminder notices with notifications enabled, per-member event notices disabled, and global email disabled. Confirm opt-out cases do not send and no reminder is queued twice.
7. Check public visibility off by default. When enabled globally or for a specific event, logged-out visitors see open counts only, never names, notes, or private positions. Check both per-event overrides against the global setting.
8. As Producer/Executive, open the roster and export CSV. Verify event/position/status/member columns, correct escaping for commas/quotes/newlines, and denial for lower roles, invalid nonce, and unknown event.
9. Exercise the REST roster and signup/cancel routes with a valid logged-in nonce/capability, logged-out request, Member, Producer, and Executive. Confirm response shapes and status codes match the docs.
10. Render `[stagekitwp_members_events]`, `[stagekitwp_members_volunteer event_id="…"]`, and `[stagekitwp_members_my_volunteering]` for empty, open, full, waitlisted, public, and private states.

## Phase 3 — Email and interest search

1. In the Producer dashboard, combine name, interest, role, availability date, RSVP status, and volunteer state filters. Confirm each filter and intersections match the prepared users, including unavailable-over-tentative-over-available priority.
2. Save a named recipient group, reload it, use it for an email, rename/replace it, and delete it. Confirm deleted groups are archived and existing log history remains filterable.
3. Compare preview totals and opted-out exclusions with the fixture set. Check that unauthorized roles cannot save/delete groups, preview/send campaigns, or use the Executive-only important override.
4. Render campaign subject and body tokens for name, first/last name, email, role, interests, site, profile link, event, position, and date. Include special characters and unknown tokens; verify output is correctly escaped and unknown placeholders do not create broken markup.
5. Submit an unsubscribe URL with an invalid signature, view a valid confirmation without submitting, then confirm unsubscribe. Verify opening the link alone changes no preference; after confirmation, only the correct account opts out. Verify the profile-preferences link.
6. Queue campaigns at immediate and future send times. Confirm group identity is retained in email history; one run processes at most 25 messages; campaign recipient and total pending-job limits reject oversize work explicitly.
7. Confirm announcement opt-outs are skipped by default, while a permitted Executive/Admin Important send follows the documented override. Verify send logs show success/failure and the correct group.
8. Test missing subject/body, an empty recipient group, deleted event/position context, stale group IDs, and a full queue. Expect an explicit error and no partially enqueued campaign.

## Phase 5 — Release, accessibility, mobile, and lifecycle

### Keyboard and assistive technology

1. Navigate member event and volunteer controls, profile preferences, availability forms, member directory, and Producer dashboard using only Tab, Shift+Tab, Enter, and Space.
2. Verify visible focus on every link, button, input, and select; tab order follows the visual and task order; no keyboard traps occur.
3. Verify every form control has a persistent programmatic label, grouped choices have a meaningful legend/heading, and button/link names describe the action.
4. Confirm RSVP active state and volunteer confirmed/waitlist status are conveyed in text, not color alone. Verify status/count text is announced and table headings are associated with their columns.
5. Check browser accessibility tooling for new serious/critical findings on the event, volunteer, and producer campaign workflows. Record any third-party/theme findings separately.

### Mobile and responsive layout

1. At 320, 375, and 600 pixels, render event cards, RSVP controls, volunteer positions, member RSVP history, profile forms, availability forms, directory results, and dashboard filters.
2. Confirm controls wrap without clipping, overlapping, or horizontal page scrolling; RSVP and signup controls remain easy to tap and event details remain readable.
3. At 200% zoom, verify the same workflows remain usable and no content or actions disappear. Tables may scroll within their container only when necessary.
4. Repeat dark/light theme checks, including visible focus, active RSVP state, notices, and error messages.

### Uninstall and retained-data boundary

Run only on a disposable clone, first with a full database/files backup.

1. With **Delete all data on uninstall** disabled, uninstall/delete the plugin. Confirm all plugin tables, role assignments, user data, site content, options, access metadata, and scheduled work are preserved as designed.
2. Restore the clone; enable the explicit purge option, create representative rows in every Members table, settings/options, user preferences/profile metadata, RSVP legacy metadata, access rules on retained content/menu items, volunteer visibility metadata, plugin CPTs, and both scheduled hooks.
3. Delete the plugin. Confirm all Members tables, plugin-owned options, plugin user/post metadata, Members CPT content, custom roles/capabilities, and every scheduled instance of both queue/reminder hooks are removed.
4. Confirm unrelated WordPress users/content, Core data, other plugin tables/options, and media files are not removed. Document any intentionally retained uploads before changing this expected behavior.

## Release checks

- Run the read-only WP-CLI smoke script and PHP lint on every changed PHP file.
- Parse all three JSON files; verify the plugin header, `STAGEKITWPMA_VERSION`, JSON version fields, changelog current/entries, and versioned download URLs agree.
- Verify plugin docs and on-site Member Instructions mention the implemented volunteer controls, recipient groups/campaign safeguards, and the relevant shortcode/hook references.
- Record environment, role, steps, expected/actual result, pass/fail, and defect link for each manual scenario. Retest failures after fixes.
