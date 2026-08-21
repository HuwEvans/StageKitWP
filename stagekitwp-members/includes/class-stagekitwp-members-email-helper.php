<?php

if (!defined('ABSPATH')) exit;

class STAGEKITWP_MEMBERS_Email_Helper {

    public static function init() {}

    public static function parse_variables($message, $user = null) {

        if (!$user) return $message;

        $terms = wp_get_object_terms($user->ID, 'stagekitwp_interest', ['fields' => 'names']);
        $interests = !empty($terms) ? implode(', ', $terms) : '';

        $replacements = [
            '{name}'        => $user->display_name,
            '{first_name}'  => $user->first_name,
            '{last_name}'   => $user->last_name,
            '{email}'       => $user->user_email,
            '{role}'        => implode(', ', $user->roles),
            '{interests}'   => $interests,
            '{site_name}'   => get_bloginfo('name'),
            '{site_url}'    => site_url(),
            '{profile_link}'=> site_url('/profile/?user_id=' . $user->ID)
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $message);
    }
}