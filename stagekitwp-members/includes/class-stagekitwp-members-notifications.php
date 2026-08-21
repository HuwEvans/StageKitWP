<?php
/**
 * Per-member email notification preferences.
 *
 * Stored as a single user meta array (stagekitwp_members_notify_prefs) keyed by type.
 * New members are opted into everything (opt-out model). Producers may flag a
 * bulk announcement as "important" to bypass the bulk-announcement opt-out;
 * personal message/conversation preferences are always respected.
 */

if (!defined('ABSPATH')) {
    exit;
}

class STAGEKITWP_MEMBERS_Notifications {

    const META_KEY = 'stagekitwp_members_notify_prefs';

    // Notification types.
    const TYPE_MESSAGE      = 'message';        // new reply in a conversation
    const TYPE_CONVERSATION = 'conversation';   // a new conversation started
    const TYPE_EVENT        = 'event';          // event-related notifications
    const TYPE_ANNOUNCEMENT = 'announcement';   // bulk announcements

    public static function init() {
        // No hooks needed here; preferences are read on demand and the
        // profile UI is handled by STAGEKITWP_MEMBERS_Profile.
    }

    /**
     * All types with human labels (for the profile UI).
     */
    public static function types() {
        return [
            self::TYPE_MESSAGE      => 'Direct messages',
            self::TYPE_CONVERSATION => 'New conversations',
            self::TYPE_EVENT        => 'Event notifications',
            self::TYPE_ANNOUNCEMENT => 'Announcements & bulk emails',
        ];
    }

    /**
     * Default preferences: everything on.
     */
    public static function defaults() {
        $defaults = [];
        foreach (array_keys(self::types()) as $type) {
            $defaults[$type] = 1;
        }
        return $defaults;
    }

    /**
     * A user's stored preferences merged over defaults (so new types added
     * later default to on for existing members).
     */
    public static function get_prefs($user_id) {
        $stored = get_user_meta($user_id, self::META_KEY, true);
        if (!is_array($stored)) {
            $stored = [];
        }
        return array_merge(self::defaults(), $stored);
    }

    /**
     * Save preferences from an array of type => bool-ish.
     */
    public static function save_prefs($user_id, $prefs) {
        $clean = [];
        foreach (array_keys(self::types()) as $type) {
            $clean[$type] = !empty($prefs[$type]) ? 1 : 0;
        }
        update_user_meta($user_id, self::META_KEY, $clean);
    }

    /**
     * Should this user receive a notification of the given type?
     *
     * @param int    $user_id
     * @param string $type      One of the TYPE_* constants.
     * @param bool   $important When true, an announcement bypasses the opt-out.
     */
    public static function should_notify($user_id, $type, $important = false) {

        if (!$user_id) {
            return false;
        }

        // Global site setting still wins: if email is disabled entirely,
        // STAGEKITWP_MEMBERS_Health::send_email already returns false. This is per-type.
        $prefs = self::get_prefs($user_id);

        // Important announcements bypass ONLY the announcement opt-out.
        if ($important && $type === self::TYPE_ANNOUNCEMENT) {
            return true;
        }

        return !empty($prefs[$type]);
    }

    /**
     * Convenience: check by email address (used in send paths that only
     * have the recipient's email).
     */
    public static function should_notify_email($email, $type, $important = false) {
        $user = get_user_by('email', $email);
        // Non-members (no account) always receive; prefs only apply to users.
        if (!$user) {
            return true;
        }
        return self::should_notify($user->ID, $type, $important);
    }
}
