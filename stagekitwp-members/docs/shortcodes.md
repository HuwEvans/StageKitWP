# StageKitWP Members shortcodes and testing

For release validation across the access-control, payment-contract, volunteer, email-campaign, mobile, accessibility, and uninstall features, use the [comprehensive test plan](./test-plan.md). A read-only local installation smoke test is available at `tests/smoke.php`.

## Events and volunteer signup

| Shortcode | Purpose |
|---|---|
| `[stagekitwp_members_events]` | Event list with existing RSVP controls. Volunteer positions and signup controls are included on eligible event cards. |
| `[stagekitwp_members_volunteer event_id="123"]` | Positions and signup controls for a specific event. On an event page, `event_id` may be omitted. |
| `[stagekitwp_members_my_volunteering]` | The current member's volunteer signup and waitlist statuses. |
| `[stagekitwp_members_my_rsvps]` | The current member's RSVP history. |
| `[stagekitwp_members_rsvp]` | Single-event RSVP form for event templates. |

Signups require an authenticated Member, Producer or Executive with the event RSVP capability. Producers and Executives manage positions in the event editor or StageKitWP Members Events panel. Position capacity can be unlimited (0); when full, eligible signups enter the waitlist and the first waitlisted member is promoted when a confirmed signup is cancelled.

Reusable templates are created by selecting “Save as reusable template” while editing a position and can be added to later events from the position editor. Public position visibility is off by default; enable it in Members Settings or override it per event. Public visitors see open positions and counts only, never member names, and must log in to sign up.

The REST API adds `GET /wp-json/stagekitwp-ma/v1/events/<id>/volunteers` for the Producer/Executive roster and `POST /wp-json/stagekitwp-ma/v1/events/<id>/volunteer` for a member signup or cancellation. The POST body accepts `position_id`, optional `intent` (`signup` or `cancel`) and `note`. Both routes use the existing Members capabilities.

## Producer email campaigns

The `[stagekitwp_members_producer_dashboard]` dashboard supports recipient filters for name, interest, WordPress role, availability on a date, RSVP status for an event and volunteer status for an event. Senders can save the current filters as a named recipient group, preview the recipient count and announcement opt-outs, then select that group in the bulk email form.

Campaign templates/messages support `{name}`, `{first_name}`, `{last_name}`, `{email}`, `{role}`, `{interests}`, `{site_name}`, `{site_url}`, `{profile_link}`, `{event}`, `{position}` and `{date}`. Select an event and, optionally, one of its volunteer position IDs to populate the event-specific fields. Campaign emails include a signed unsubscribe confirmation and a link to the member profile preferences.

Bulk campaigns are bounded to 500 recipients and queued for delivery in batches of 25 messages per minute. The queue holds at most 1,000 pending jobs. Group sends are recorded with their group identifier in the Email Logs screen; deleting a group archives it so its history remains filterable.

## Other member pages

Use the other front-end shortcodes listed in the on-site Member Instructions page and the plugin's `plugin-info.json` / `stagekitwp-members-area.json` metadata. This document highlights event and volunteer shortcodes from Members 3.1.4 and producer email campaigns from 3.1.5.
