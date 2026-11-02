# StageKitWP Members — hooks reference

Members never handles money or card data. A payment plugin (for example `stagekitwp-payment`) connects through the hooks below. With no payment plugin active, nothing is gated by payment.

## Turning the payment layer on

```php
add_filter( 'stagekitwp_members_payments_enabled', '__return_true' );
```

Until this returns true, `stagekitwp_members_payment_required()` is always false and the "Paid / active membership" access rule passes for any member.

## Filters

| Filter | Arguments | Purpose |
|---|---|---|
| `stagekitwp_members_payments_enabled` | `bool $enabled` (default false) | Payment plugin switches the layer on. |
| `stagekitwp_members_payment_required` | `bool $required, array $context` | Does this item need payment? `$context` has `type` (`event`, `content`, `level`), `id`, `user_id`. Only called when payments are enabled. |
| `stagekitwp_members_membership_status` | `string $status, int $user_id` | Override the resolved status: `active`, `lapsed`, `pending`, `none`. |
| `stagekitwp_members_checkout_url` | `string $url, array $context` | Where to send a user who must pay. |
| `stagekitwp_members_can_access` | `bool $allowed, string\|array $rule, int $user_id, array $context` | Final say on any access decision. |

Helper functions: `stagekitwp_members_payments_enabled()`, `stagekitwp_members_membership_status( $user_id )`, `stagekitwp_members_payment_required( $context )`, `stagekitwp_members_checkout_url( $context )`, `stagekitwp_members_user_can_access( $rule, $user_id, $context )`, `stagekitwp_members_get_user_level( $user_id )`.

## Actions

| Action | Arguments | Fired by |
|---|---|---|
| `stagekitwp_members_payment_complete` | `int $user_id, array $args` | **The payment plugin fires this.** Members records an active membership. |
| `stagekitwp_members_access_granted` | `int $user_id, array $record` | Members, when a membership becomes active. |
| `stagekitwp_members_access_revoked` | `int $user_id, array $record` | Members, when an active membership ends or is cancelled. |
| `stagekitwp_members_before_signup` | `int $event_id, int $position_id, int $user_id, array $context` | Fired before a volunteer signup or cancellation is processed. |
| `stagekitwp_members_after_signup` | `int $event_id, int $position_id, int $user_id, array $context` | Fired after a volunteer signup or cancellation is saved. |

`$args` for `payment_complete`: `level` (role slug, optional), `expiry` (`Y-m-d H:i:s` UTC, optional), `source` (default `payment`), `external_ref` (gateway reference), `note`.

## Example: payment plugin

```php
add_filter( 'stagekitwp_members_payments_enabled', '__return_true' );

add_filter( 'stagekitwp_members_payment_required', function ( $required, $context ) {
    return 'event' === $context['type'] && my_event_has_fee( $context['id'] );
}, 10, 2 );

add_filter( 'stagekitwp_members_checkout_url', function ( $url, $context ) {
    return add_query_arg( $context, home_url( '/checkout/' ) );
}, 10, 2 );

// After the gateway confirms payment:
do_action( 'stagekitwp_members_payment_complete', $user_id, array(
    'expiry'       => gmdate( 'Y-m-d H:i:s', strtotime( '+1 year' ) ),
    'external_ref' => $gateway_transaction_id,
) );
```

## Data

- `{prefix}stagekitwp_members_memberships` — one current record per user (level, status, start, expiry, source, external_ref).
- `{prefix}stagekitwp_members_membership_history` — append-only log of status changes.
- An active record past its expiry reads as `lapsed`; no cron is needed.
- Executives (capability `stagekitwp_members_manage_members`) and administrators edit a user's membership on the user profile screen.
- Event post meta `_stagekitwp_members_event_member_price` holds the optional member price (blank means the regular price).

## Volunteer signup integration

The signup context contains `type` (`event`), `id` (event ID), `position_id`, and `user_id`. Members fires the before/after actions for front-end and REST signup/cancel requests. A payment integration can use these actions for deposits or other side effects. These are notifications, not veto filters; use the REST response / checkout flow in the payment plugin if payment must be completed before confirming a signup.

Volunteer positions are stored in `{prefix}stagekitwp_members_positions`; member rows are in `{prefix}stagekitwp_members_volunteers`. The latter stores only the member's position, status, optional note and timestamps. Confirmation and waitlist promotion are capacity-aware. Event reminders are queued for confirmed volunteers one day before the event, subject to the member's Event notifications preference.
