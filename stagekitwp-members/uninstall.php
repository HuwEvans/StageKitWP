<?php
/**
 * Uninstall handler for TM Members Area.
 *
 * Runs ONLY when the plugin is deleted from the WordPress admin. By default
 * this is a no-op: member data (profiles, RSVPs, availability, invitations,
 * announcements, email logs) is preserved so an accidental delete/reinstall
 * never destroys real data.
 *
 * To purge everything, an administrator must first enable the
 * "Delete all data on uninstall" option (stored as
 * `stagekitwp_members_delete_data_on_uninstall`). Only then does this file drop the
 * custom tables, options, roles, CPT content, and user meta.
 */

// Bail if not called by WordPress during uninstall.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Safety guard: keep data unless the admin explicitly opted in.
if (!get_option('stagekitwp_members_delete_data_on_uninstall')) {
    return;
}

global $wpdb;

/* -------------------------------------------------------------------- */
/* 1. Drop custom tables                                                */
/* -------------------------------------------------------------------- */

$tables = ['stagekitwp_invitations', 'stagekitwp_members_email_log', 'stagekitwp_members_rsvps', 'stagekitwp_members_availability'];
foreach ($tables as $t) {
    $table = $wpdb->prefix . $t;
    // Table name cannot be parameterized; it is a fixed internal constant.
    $wpdb->query("DROP TABLE IF EXISTS {$table}");
}

/* -------------------------------------------------------------------- */
/* 2. Delete options / scheduled events                                 */
/* -------------------------------------------------------------------- */

$options = [
    'stagekitwp_members_db_version',
    'stagekitwp_members_roles_version',
    'stagekitwp_members_directory_page_id',
    'stagekitwp_members_email_queue',
    'stagekitwp_members_email_status',
    'stagekitwp_members_last_email_attempt',
    'stagekitwp_members_email_enabled',
    'stagekitwp_members_delete_data_on_uninstall',
];
foreach ($options as $opt) {
    delete_option($opt);
}

// Clear the queue cron.
$ts = wp_next_scheduled('stagekitwp_members_process_queue');
if ($ts) {
    wp_unschedule_event($ts, 'stagekitwp_members_process_queue');
}

/* -------------------------------------------------------------------- */
/* 3. Delete CPT content (announcements, conversations, templates)      */
/* -------------------------------------------------------------------- */

$cpts = ['stagekitwp_ann', 'stagekitwp_conv', 'stagekitwp_email', 'stagekitwp_event'];
foreach ($cpts as $cpt) {
    $posts = get_posts([
        'post_type'   => $cpt,
        'post_status' => 'any',
        'numberposts' => -1,
        'fields'      => 'ids',
    ]);
    foreach ($posts as $pid) {
        wp_delete_post($pid, true);
    }
}

/* -------------------------------------------------------------------- */
/* 4. Remove custom roles                                               */
/* -------------------------------------------------------------------- */

foreach (['stagekitwp_member', 'stagekitwp_producer', 'stagekitwp_executive'] as $role) {
    if (get_role($role)) {
        remove_role($role);
    }
}

/* -------------------------------------------------------------------- */
/* 5. Delete plugin user meta                                           */
/* -------------------------------------------------------------------- */

// Fixed-key meta.
$meta_keys = [
    'stagekitwp_members_profile_image',
    'stagekitwp_members_bio',
    'stagekitwp_members_resume',
    'stagekitwp_members_directory_listed',
    'stagekitwp_members_notify_prefs',
    'stagekitwp_members_rsvp_meta_migrated',
];
foreach ($meta_keys as $key) {
    delete_metadata('user', 0, $key, '', true);
}

// Legacy per-event RSVP meta (stagekitwp_members_rsvp_<id>) uses a LIKE match.
$like = $wpdb->esc_like('stagekitwp_members_rsvp_') . '%';
$wpdb->query(
    $wpdb->prepare("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s", $like)
);
