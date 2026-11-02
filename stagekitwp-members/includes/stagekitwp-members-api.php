<?php
/**
 * Public access API for StageKitWP Members.
 *
 * One place decides who may see or do something. Menus, pages, REST,
 * shortcodes and other plugins (for example payments) should call these
 * functions instead of checking roles directly.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Ordered member levels, lowest to highest.
 *
 * @return array<string,int> Role slug => rank.
 */
function stagekitwp_members_levels() {
    return [
        'stagekitwp_member'    => 1,
        'stagekitwp_producer'  => 2,
        'stagekitwp_executive' => 3,
    ];
}

/**
 * Highest member level for a user.
 *
 * Administrators count as executive. Plain WordPress roles (including
 * subscriber) have no member level.
 *
 * @param int $user_id User ID, 0 for the current user.
 * @return string Role slug, or '' when the user has no member level.
 */
function stagekitwp_members_get_user_level($user_id = 0) {
    $user_id = $user_id ? (int) $user_id : get_current_user_id();
    if (!$user_id) {
        return '';
    }

    $user = get_userdata($user_id);
    if (!$user) {
        return '';
    }

    if (user_can($user, 'manage_options')) {
        return 'stagekitwp_executive';
    }

    $best  = '';
    $rank  = 0;
    foreach (stagekitwp_members_levels() as $slug => $value) {
        if (in_array($slug, (array) $user->roles, true) && $value > $rank) {
            $best = $slug;
            $rank = $value;
        }
    }

    return $best;
}

/**
 * Whether a user satisfies an access rule.
 *
 * Rule forms:
 *  - 'everyone' | 'logged_in' | 'logged_out'
 *  - 'members' (any member level or higher)
 *  - 'paid' (active membership; passes for any member when no payment plugin is active)
 *  - array( 'type' => 'roles', 'roles' => [role slugs] )
 *  - array( 'type' => 'level', 'min_level' => 'stagekitwp_producer' )
 *
 * @param string|array $rule    Access rule.
 * @param int          $user_id User ID, 0 for the current user.
 * @return bool
 */
function stagekitwp_members_user_can_access($rule = 'everyone', $user_id = 0, $context = []) {
    $user_id   = $user_id ? (int) $user_id : get_current_user_id();
    $logged_in = $user_id > 0;
    $type      = is_array($rule) ? (isset($rule['type']) ? (string) $rule['type'] : 'everyone') : (string) $rule;
    $levels    = stagekitwp_members_levels();
    $level     = stagekitwp_members_get_user_level($user_id);

    switch ($type) {
        case 'logged_in':
            $allowed = $logged_in;
            break;

        case 'logged_out':
            $allowed = !$logged_in;
            break;

        case 'members':
            $allowed = ($level !== '');
            break;

        case 'paid':
            $allowed = $level !== '' && (!stagekitwp_members_payments_enabled() || stagekitwp_members_membership_status($user_id) === 'active');
            break;

        case 'roles':
            $roles   = is_array($rule) && !empty($rule['roles']) ? array_map('sanitize_key', (array) $rule['roles']) : [];
            $user    = $logged_in ? get_userdata($user_id) : false;
            $allowed = $user && array_intersect($roles, (array) $user->roles);
            if (!$allowed && $level === 'stagekitwp_executive' && user_can($user_id, 'manage_options')) {
                $allowed = true; // admins are never locked out
            }
            break;

        case 'level':
            $min     = is_array($rule) && isset($rule['min_level']) ? (string) $rule['min_level'] : 'stagekitwp_member';
            $allowed = $level !== '' && isset($levels[$min]) && $levels[$level] >= $levels[$min];
            break;

        case 'everyone':
        default:
            $allowed = true;
            break;
    }

    /**
     * Filters the access decision.
     *
     * @param bool         $allowed Decision.
     * @param string|array $rule    Rule being checked.
     * @param int          $user_id User ID (0 when logged out).
     * @param array        $context Optional context (for example post_id).
     */
    return (bool) apply_filters('stagekitwp_members_can_access', (bool) $allowed, $rule, $user_id, $context);
}

/**
 * Whether a payment plugin has switched the payment layer on.
 * Default false: with no payment plugin, nothing is gated by payment.
 *
 * @return bool
 */
function stagekitwp_members_payments_enabled() {
    return (bool) apply_filters('stagekitwp_members_payments_enabled', false);
}

/**
 * Resolved membership status for a user: active, lapsed, pending or none.
 *
 * @param int $user_id User ID, 0 for the current user.
 * @return string
 */
function stagekitwp_members_membership_status($user_id = 0) {
    $user_id = $user_id ? (int) $user_id : get_current_user_id();
    $status  = ($user_id && class_exists('STAGEKITWP_MEMBERS_Membership')) ? STAGEKITWP_MEMBERS_Membership::stored_status($user_id) : 'none';

    /**
     * Filters the resolved membership status.
     *
     * @param string $status  active|lapsed|pending|none.
     * @param int    $user_id User ID.
     */
    $status = (string) apply_filters('stagekitwp_members_membership_status', $status, $user_id);
    return in_array($status, ['active', 'lapsed', 'pending', 'none'], true) ? $status : 'none';
}

/**
 * Whether an item (event, content or level) needs payment from this user.
 *
 * @param array $context Keys such as type (event|content|level), id, user_id.
 * @return bool
 */
function stagekitwp_members_payment_required($context = []) {
    if (!stagekitwp_members_payments_enabled()) {
        return false;
    }
    return (bool) apply_filters('stagekitwp_members_payment_required', false, $context);
}

/**
 * Where to send a user who must pay. Empty string when no payment plugin supplies one.
 *
 * @param array $context Same context passed to the payment_required filter.
 * @return string
 */
function stagekitwp_members_checkout_url($context = []) {
    return (string) apply_filters('stagekitwp_members_checkout_url', '', $context);
}