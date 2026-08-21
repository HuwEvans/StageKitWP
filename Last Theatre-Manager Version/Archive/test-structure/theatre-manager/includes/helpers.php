<?php
// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Get image URL from attachment ID or direct URL
 * Handles both old format (direct URLs) and new format (attachment IDs from tm-sync)
 *
 * @param mixed $image_value Attachment ID (numeric) or URL string
 * @return string The image URL, or empty string if not found
 */
function tm_get_image_url($image_value) {
    if (empty($image_value)) {
        return '';
    }
    
    // If it's numeric, treat it as an attachment ID
    if (is_numeric($image_value)) {
        $url = wp_get_attachment_url($image_value);
        return $url ? $url : '';
    }
    
    // Otherwise, treat it as a direct URL
    return $image_value;
}

/**
 * Get image HTML or fallback
 */
function tm_get_image_html($url, $alt = '', $class = '', $fallback = '') {
    if ($url) {
        return '<img src="' . esc_url($url) . '" alt="' . esc_attr($alt) . '" class="' . esc_attr($class) . '" />';
    } elseif ($fallback) {
        return '<img src="' . esc_url($fallback) . '" alt="' . esc_attr($alt) . '" class="' . esc_attr($class) . '" />';
    }
    return '';
}

/**
 * Sanitize a CSS color value used in inline styles.
 * Accepts hex values and returns a safe fallback when invalid.
 */
function tm_sanitize_css_color($value, $fallback = '#000000') {
    if ($value === null || $value === '') {
        return $fallback;
    }

    $sanitized = sanitize_hex_color($value);
    if ($sanitized) {
        return $sanitized;
    }

    if ($fallback === '') {
        return '';
    }

    $fallback_sanitized = sanitize_hex_color($fallback);
    return $fallback_sanitized ? $fallback_sanitized : '#000000';
}
