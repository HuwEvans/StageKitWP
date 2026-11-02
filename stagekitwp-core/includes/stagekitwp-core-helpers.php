<?php
// Exit if accessed directly
defined('ABSPATH') || exit;

/**
 * Get image URL from attachment ID or direct URL
 * Handles both old format (direct URLs) and new format (attachment IDs from stagekitwp-sync)
 *
 * @param mixed $image_value Attachment ID (numeric) or URL string
 * @return string The image URL, or empty string if not found
 */
function stagekitwp_get_image_url($image_value) {
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
function stagekitwp_get_image_html($url, $alt = '', $class = '', $fallback = '') {
    if ($url) {
        return '<img src="' . esc_url($url) . '" alt="' . esc_attr($alt) . '" class="' . esc_attr($class) . '" />';
    } elseif ($fallback) {
        return '<img src="' . esc_url($fallback) . '" alt="' . esc_attr($alt) . '" class="' . esc_attr($class) . '" />';
    }
    return '';
}

/**
 * Sanitize a CSS color value used in inline styles.
 *
 * Accepts:
 *   - 3- or 6-digit hex  (#fff, #ffffff)
 *   - rgb()  / rgba()    (rgb(0,0,0) / rgba(0,0,0,0.5))
 *   - hsl()  / hsla()    (hsl(0,0%,0%) / hsla(0,0%,0%,0.5))
 *   - CSS named colors   (transparent, currentColor, red …)
 *
 * Returns a sanitized value, or $fallback when the input is empty / unrecognised.
 */
function stagekitwp_sanitize_css_color($value, $fallback = '#000000') {
    if ($value === null || $value === '') {
        return $fallback;
    }

    $value = trim($value);

    // 1. Hex colours (3 or 6 digits, with or without #)
    $hex = sanitize_hex_color($value);
    if ($hex) {
        return $hex;
    }

    // 2. rgb() / rgba() / hsl() / hsla() — allow digits, spaces, commas,
    //    dots, slashes, and % inside parens; no JS or expressions allowed.
    if (preg_match('/^(rgba?|hsla?)\([\d\s,\.\/%]+\)$/i', $value)) {
        return $value;
    }

    // 3. CSS named colors and keywords (transparent, currentColor, inherit, …)
    //    Only letters and hyphens — no expressions, no url(), nothing executable.
    if (preg_match('/^[a-zA-Z\-]+$/', $value)) {
        return $value;
    }

    // Unrecognised format — return the fallback.
    if ($fallback === '') {
        return '';
    }
    $fallback_sanitized = sanitize_hex_color($fallback);
    return $fallback_sanitized ? $fallback_sanitized : '#000000';
}

/**
 * Canonical show time-slot sort order for a July–June theatre season.
 *
 * Seasons run July – June, so the production year goes:
 *   Fall (Jul–Nov)  →  Winter (Dec–Mar)  →  Spring (Apr–Jun)
 *
 * Returns an associative array suitable for index lookup:
 *   array( 'Fall' => 0, 'Winter' => 1, 'Spring' => 2 )
 *
 * Usage in a usort callback:
 *   $order = stagekitwp_show_slot_order();
 *   $ia = isset( $order[$sa] ) ? $order[$sa] : 999;
 *   $ib = isset( $order[$sb] ) ? $order[$sb] : 999;
 *   return $ia - $ib;
 *
 * @return array
 */
function stagekitwp_show_slot_order() {
    return array(
        'Fall'   => 0,
        'Winter' => 1,
        'Spring' => 2,
    );
}

/**
 * Sort an array of show WP_Post objects by time slot (Fall, Winter, Spring).
 * Shows with no slot assigned sort last.
 *
 * @param  WP_Post[] $shows     Array of show post objects.
 * @return WP_Post[]            Sorted array (original array not modified).
 */
function stagekitwp_sort_shows_by_slot( $shows ) {
    $order = stagekitwp_show_slot_order();
    usort( $shows, function( $a, $b ) use ( $order ) {
        $sa = get_post_meta( $a->ID, '_stagekitwp_show_time_slot', true );
        $sb = get_post_meta( $b->ID, '_stagekitwp_show_time_slot', true );
        $ia = isset( $order[ $sa ] ) ? $order[ $sa ] : 999;
        $ib = isset( $order[ $sb ] ) ? $order[ $sb ] : 999;
        return $ia - $ib;
    } );
    return $shows;
}

/**
 * Return the canonical front-end URL for a show CPT post.
 *
 * Resolution order:
 *   1. The show's own CPT permalink (get_permalink), which routes through
 *      single-show.php and renders the Front-end Display (landing page etc.).
 *   2. Falls back to '' if the show has no published permalink.
 *
 * Use this everywhere a show name or image is linked so that all shortcodes
 * stay consistent and can benefit from the Front-end Display system.
 *
 * @param int|WP_Post $show  Show post ID or object.
 * @return string  Absolute URL, or '' when unavailable (draft, trashed, etc.).
 */
function stagekitwp_show_page_url( $show ) {
    $id  = ( $show instanceof WP_Post ) ? $show->ID : intval( $show );
    if ( ! $id ) { return ''; }
    $url = get_permalink( $id );
    return ( $url && $url !== false ) ? (string) $url : '';
}

/**
 * Default sponsorship/contribution level labels.
 *
 * @return array
 */
function stagekitwp_default_level_labels() {
    return array(
        'Platinum' => 'Platinum',
        'Gold'     => 'Gold',
        'Silver'   => 'Silver',
        'Bronze'   => 'Bronze',
    );
}

/**
 * Context-aware level labels.
 *
 * Sponsors include Diamond as the top tier.
 * Contributors keep the original four levels.
 *
 * @param string $context Either 'sponsor' or 'contributor'.
 * @return array
 */
function stagekitwp_level_labels_for_context( $context ) {
    $context = sanitize_key( (string) $context );

    if ( $context === 'sponsor' ) {
        return array(
            'Diamond'  => 'Diamond',
            'Platinum' => 'Platinum',
            'Gold'     => 'Gold',
            'Silver'   => 'Silver',
            'Bronze'   => 'Bronze',
        );
    }

    return stagekitwp_default_level_labels();
}

/**
 * Resolve level display labels from options and optional shortcode overrides.
 *
 * Supported shortcode override attrs:
 * - diamond_label
 * - platinum_label
 * - gold_label
 * - silver_label
 * - bronze_label
 *
 * @param string $context Either 'sponsor' or 'contributor'.
 * @param array  $atts    Shortcode attributes.
 * @return array
 */
function stagekitwp_get_level_display_labels( $context, $atts = array() ) {
    $context = sanitize_key( (string) $context );
    $defaults = stagekitwp_level_labels_for_context( $context );

    $option_map = array(
        'Diamond'  => "stagekitwp_{$context}_level_label_diamond",
        'Platinum' => "stagekitwp_{$context}_level_label_platinum",
        'Gold'     => "stagekitwp_{$context}_level_label_gold",
        'Silver'   => "stagekitwp_{$context}_level_label_silver",
        'Bronze'   => "stagekitwp_{$context}_level_label_bronze",
    );

    $attr_map = array(
        'Diamond'  => 'diamond_label',
        'Platinum' => 'platinum_label',
        'Gold'     => 'gold_label',
        'Silver'   => 'silver_label',
        'Bronze'   => 'bronze_label',
    );

    $labels = array();
    foreach ( $defaults as $level => $fallback ) {
        $option_key = isset( $option_map[ $level ] ) ? $option_map[ $level ] : '';
        $saved = $option_key ? trim( sanitize_text_field( (string) get_option( $option_key, '' ) ) ) : '';
        $labels[ $level ] = ( $saved !== '' ) ? $saved : $fallback;

        $attr_key = isset( $attr_map[ $level ] ) ? $attr_map[ $level ] : '';
        if ( $attr_key && isset( $atts[ $attr_key ] ) ) {
            $override = trim( sanitize_text_field( (string) $atts[ $attr_key ] ) );
            if ( $override !== '' ) {
                $labels[ $level ] = $override;
            }
        }
    }

    return $labels;
}

/**
 * Get the configured Organization Name, falling back to Site Identity (blog name).
 *
 * @return string
 */
function stagekitwp_get_organization_name() {
    $org = get_option( 'stagekitwp_organization_name', '' );
    if ( is_string( $org ) && '' !== trim( $org ) ) {
        return trim( $org );
    }
    return get_bloginfo( 'name' );
}

/**
 * Get the configured Organization Address, used as a location fallback in schema markup.
 *
 * @return string
 */
function stagekitwp_get_organization_address() {
    $address = get_option( 'stagekitwp_organization_address', '' );
    return is_string( $address ) ? trim( $address ) : '';
}

/**
 * Resolve the "next" season: the season immediately after the current one
 * (by date range), or the earliest future season if no season is currently
 * running. Returns null when there is no next season.
 *
 * @return WP_Post|null
 */
function stagekitwp_get_next_season() {
    static $cached      = null;
    static $has_resolved = false;
    if ( $has_resolved ) {
        return $cached;
    }
    $has_resolved = true;

    $season_ids = get_posts( array(
        'post_type'      => 'season',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ) );
    if ( empty( $season_ids ) ) {
        return $cached;
    }

    $now     = current_time( 'timestamp' );
    $seasons = array();
    foreach ( $season_ids as $season_id ) {
        $start_raw = get_post_meta( $season_id, '_stagekitwp_season_start_date', true );
        $end_raw   = get_post_meta( $season_id, '_stagekitwp_season_end_date', true );
        $seasons[] = array(
            'id'    => $season_id,
            'start' => $start_raw ? strtotime( $start_raw ) : 0,
            'end'   => $end_raw ? strtotime( $end_raw ) + 86399 : 0,
        );
    }
    usort( $seasons, function ( $a, $b ) {
        return $a['start'] - $b['start'];
    } );

    $current_index = null;
    foreach ( $seasons as $index => $season ) {
        if ( $season['start'] && $season['end'] && $now >= $season['start'] && $now <= $season['end'] ) {
            $current_index = $index;
            break;
        }
    }

    if ( null !== $current_index ) {
        $next = isset( $seasons[ $current_index + 1 ] ) ? $seasons[ $current_index + 1 ] : null;
        return $cached = ( $next ? get_post( $next['id'] ) : null );
    }

    foreach ( $seasons as $season ) {
        if ( $season['start'] > $now ) {
            return $cached = get_post( $season['id'] );
        }
    }

    return $cached;
}

/**
 * Whether a given season has any published shows assigned to it.
 *
 * @param int $season_id
 * @return bool
 */
function stagekitwp_season_has_shows( $season_id ) {
    $season_id = absint( $season_id );
    if ( ! $season_id ) {
        return false;
    }
    $show_ids = get_posts( array(
        'post_type'      => 'show',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'meta_key'       => '_stagekitwp_show_season',
        'meta_value'     => $season_id,
    ) );
    return ! empty( $show_ids );
}

/**
 * Whether the next season (if any) has any published shows. No next season
 * always resolves to false.
 *
 * @return bool
 */
function stagekitwp_next_season_has_shows() {
    $season = stagekitwp_get_next_season();
    return $season ? stagekitwp_season_has_shows( $season->ID ) : false;
}

/**
 * Render a <select> of seasons for a CPT meta box.
 *
 * @param string $name     Form field name.
 * @param mixed  $selected Selected season ID.
 * @param string $blank    Label for the empty option.
 */
function stagekitwp_render_season_select( $name, $selected = '', $blank = '— All / No Season —' ) {
    $seasons = get_posts( array( 'post_type' => 'season', 'numberposts' => -1, 'post_status' => 'publish', 'orderby' => 'title', 'order' => 'DESC' ) );
    echo '<select name="' . esc_attr( $name ) . '">';
    echo '<option value="">' . esc_html( $blank ) . '</option>';
    foreach ( $seasons as $season ) {
        echo '<option value="' . esc_attr( $season->ID ) . '" ' . selected( (int) $selected, $season->ID, false ) . '>' . esc_html( $season->post_title ) . '</option>';
    }
    echo '</select>';
}

/**
 * Render a <select> of published sponsors (for featured sponsor fields).
 */
function stagekitwp_render_sponsor_select( $name, $selected = '', $blank = '— None —' ) {
    $sponsors = get_posts( array( 'post_type' => 'sponsor', 'numberposts' => -1, 'post_status' => 'publish', 'orderby' => 'title', 'order' => 'ASC' ) );
    echo '<select name="' . esc_attr( $name ) . '">';
    echo '<option value="">' . esc_html( $blank ) . '</option>';
    foreach ( $sponsors as $sponsor ) {
        echo '<option value="' . esc_attr( $sponsor->ID ) . '" ' . selected( (int) $selected, $sponsor->ID, false ) . '>' . esc_html( $sponsor->post_title ) . '</option>';
    }
    echo '</select>';
}

/**
 * Sanitize a posted ID of a given post type (returns '' if invalid).
 */
function stagekitwp_sanitize_post_id_of_type( $value, $post_type ) {
    $id = absint( $value );
    return ( $id && get_post_type( $id ) === $post_type ) ? $id : '';
}

/**
 * Resolve a shortcode "season" attribute to a season post ID.
 * Accepts an ID, a slug/title, "current" or "upcoming". Returns 0 when the
 * attribute is empty, or -1 when a value was given but no season matches.
 */
function stagekitwp_resolve_season_attr( $value ) {
    $value = trim( (string) $value );
    if ( $value === '' ) {
        return 0;
    }
    if ( ctype_digit( $value ) ) {
        return get_post_type( (int) $value ) === 'season' ? (int) $value : -1;
    }
    $key = strtolower( $value );
    if ( $key === 'next' ) {
        $next = stagekitwp_get_next_season();
        return $next ? (int) $next->ID : -1;
    }
    if ( $key === 'current' ) {
        // Same date-range logic as [stagekitwp_season_shows which="current"].
        $now = current_time( 'timestamp' );
        foreach ( get_posts( array( 'post_type' => 'season', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids' ) ) as $sid ) {
            $start = get_post_meta( $sid, '_stagekitwp_season_start_date', true );
            $end   = get_post_meta( $sid, '_stagekitwp_season_end_date', true );
            if ( $start && $end && $now >= strtotime( $start ) && $now <= strtotime( $end ) + 86399 ) {
                return (int) $sid;
            }
        }
    }
    if ( $key === 'current' || $key === 'upcoming' ) {
        $found = get_posts( array(
            'post_type' => 'season', 'numberposts' => 1, 'fields' => 'ids',
            'meta_key' => '_stagekitwp_season_is_' . $key, 'meta_value' => '1',
        ) );
        return $found ? (int) $found[0] : -1;
    }
    $post = get_page_by_path( sanitize_title( $value ), OBJECT, 'season' );
    if ( ! $post ) {
        $by_title = get_posts( array(
            'post_type' => 'season', 'title' => $value, 'numberposts' => 1,
            'post_status' => 'publish', 'orderby' => 'ID', 'order' => 'ASC',
        ) );
        $post = $by_title ? $by_title[0] : null;
    }
    return $post ? (int) $post->ID : -1;
}

/**
 * Build a meta_query clause filtering by the shared `_stagekitwp_season` meta.
 * Returns an empty array when no filtering is requested. If the season cannot
 * be resolved, the clause matches nothing.
 */
function stagekitwp_season_meta_query( $season_attr ) {
    $season_id = stagekitwp_resolve_season_attr( $season_attr );
    if ( $season_id === 0 ) {
        return array();
    }
    return array(
        'key'     => '_stagekitwp_season',
        'value'   => $season_id > 0 ? (string) $season_id : '0',
        'compare' => '=',
    );
}

/**
 * Per-performance sponsor map for a show: [ 'Y-m-d\TH:i' => sponsor_id ].
 */
function stagekitwp_get_performance_sponsors( $show_id ) {
    $map = get_post_meta( $show_id, '_stagekitwp_show_performance_sponsors', true );
    return is_array( $map ) ? $map : array();
}

/**
 * Resolve the sponsor for a show, optionally for one performance.
 * Fallback order: performance -> show featured sponsor -> season featured sponsor.
 * Returns a published sponsor post ID, or 0.
 */
function stagekitwp_get_show_sponsor_id( $show_id, $datetime = '' ) {
    $candidates = array();
    if ( $datetime !== '' ) {
        $map          = stagekitwp_get_performance_sponsors( $show_id );
        $candidates[] = isset( $map[ $datetime ] ) ? $map[ $datetime ] : 0;
    }
    $candidates[] = get_post_meta( $show_id, '_stagekitwp_show_featured_sponsor', true );
    $season_id    = (int) get_post_meta( $show_id, '_stagekitwp_show_season', true );
    if ( $season_id ) {
        $candidates[] = get_post_meta( $season_id, '_stagekitwp_season_featured_sponsor', true );
    }
    foreach ( $candidates as $candidate ) {
        $id = absint( $candidate );
        if ( $id && get_post_type( $id ) === 'sponsor' && get_post_status( $id ) === 'publish' ) {
            return $id;
        }
    }
    return 0;
}

/**
 * HTML credit line for a sponsor (name linked to website when set).
 */
function stagekitwp_sponsor_credit_html( $sponsor_id, $label = 'Sponsored by', $show_banner = true ) {
    if ( ! $sponsor_id ) {
        return '';
    }
    $banner_html = '';
    if ( $show_banner ) {
        $banner_url = stagekitwp_get_image_url( get_post_meta( $sponsor_id, '_stagekitwp_banner', true ) );
        if ( $banner_url ) {
            $b_site = get_post_meta( $sponsor_id, '_stagekitwp_website', true );
            $b_alt  = get_post_meta( $sponsor_id, '_stagekitwp_company', true ) ?: get_the_title( $sponsor_id );
            $b_img  = '<img src="' . esc_url( $banner_url ) . '" alt="' . esc_attr( $b_alt ) . '" style="max-width:100%;height:auto;" loading="lazy">';
            $banner_html = '<span class="stagekitwp-sponsor-credit-banner" style="display:block;margin-bottom:8px;">'
                . ( $b_site ? '<a href="' . esc_url( $b_site ) . '" target="_blank" rel="noopener">' . $b_img . '</a>' : $b_img )
                . '</span>';
        }
    }
    $name = get_post_meta( $sponsor_id, '_stagekitwp_company', true );
    if ( $name === '' ) {
        $name = get_the_title( $sponsor_id );
    }
    $website = get_post_meta( $sponsor_id, '_stagekitwp_website', true );
    $name_html = $website
        ? '<a href="' . esc_url( $website ) . '" target="_blank" rel="noopener">' . esc_html( $name ) . '</a>'
        : esc_html( $name );
    return $banner_html . '<span class="stagekitwp-sponsor-credit">' . ( $label !== '' ? esc_html( $label ) . ' ' : '' ) . $name_html . '</span>';
}

/**
 * Sponsors grouped by level (Diamond..Bronze) with all available elements:
 * logo, banner, name, company, website. Filtered to a season when $season_id > 0.
 */
function stagekitwp_sponsors_by_level_html( $season_id = 0 ) {
    $args = array(
        'post_type'      => 'sponsor',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    );
    if ( $season_id > 0 ) {
        $args['meta_query'] = array( array( 'key' => '_stagekitwp_season', 'value' => (int) $season_id, 'compare' => '=' ) );
    }
    $sponsors = get_posts( $args );
    if ( ! $sponsors ) {
        return '';
    }
    $labels = stagekitwp_get_level_display_labels( 'sponsor' );
    $groups = array();
    foreach ( $sponsors as $s ) {
        $lvl = get_post_meta( $s->ID, '_stagekitwp_level', true );
        $groups[ isset( $labels[ $lvl ] ) ? $lvl : '' ][] = $s;
    }
    $order = array_keys( $labels );
    $order[] = '';
    $out = '';
    foreach ( $order as $lvl ) {
        if ( empty( $groups[ $lvl ] ) ) {
            continue;
        }
        $out .= '<div class="stagekitwp-lp-sponsor-level stagekitwp-lp-sponsor-level-' . esc_attr( strtolower( $lvl ?: 'other' ) ) . '" style="margin-bottom:20px;">';
        if ( $lvl !== '' ) {
            $out .= '<h4 class="stagekitwp-lp-sponsor-level-title">' . esc_html( $labels[ $lvl ] ) . '</h4>';
        }
        $out .= '<div style="display:flex;flex-wrap:wrap;gap:20px;align-items:flex-start;">';
        foreach ( $groups[ $lvl ] as $s ) {
            $company = get_post_meta( $s->ID, '_stagekitwp_company', true );
            $name    = get_post_meta( $s->ID, '_stagekitwp_name', true );
            $site    = get_post_meta( $s->ID, '_stagekitwp_website', true );
            $logo    = stagekitwp_get_image_url( get_post_meta( $s->ID, '_stagekitwp_logo', true ) );
            $banner  = stagekitwp_get_image_url( get_post_meta( $s->ID, '_stagekitwp_banner', true ) );
            $title   = $company ?: ( $name ?: $s->post_title );
            $wrap    = function ( $inner ) use ( $site ) {
                return $site ? '<a href="' . esc_url( $site ) . '" target="_blank" rel="noopener">' . $inner . '</a>' : $inner;
            };
            $out .= '<div class="stagekitwp-lp-sponsor" style="max-width:100%;">';
            if ( $banner ) {
                $out .= '<div class="stagekitwp-lp-sponsor-banner">' . $wrap( '<img src="' . esc_url( $banner ) . '" alt="' . esc_attr( $title ) . '" style="max-width:100%;height:auto;" loading="lazy">' ) . '</div>';
            }
            if ( $logo ) {
                $out .= '<div class="stagekitwp-lp-sponsor-logo">' . $wrap( '<img src="' . esc_url( $logo ) . '" alt="' . esc_attr( $title ) . '" style="max-width:200px;height:auto;" loading="lazy">' ) . '</div>';
            }
            $out .= '<div class="stagekitwp-lp-sponsor-company"><strong>' . $wrap( esc_html( $title ) ) . '</strong></div>';
            if ( $name && $name !== $title ) {
                $out .= '<div class="stagekitwp-lp-sponsor-name">' . esc_html( $name ) . '</div>';
            }
            if ( $site ) {
                $out .= '<div class="stagekitwp-lp-sponsor-website"><a href="' . esc_url( $site ) . '" target="_blank" rel="noopener">' . esc_html( preg_replace( '#^https?://(www\.)?#', '', untrailingslashit( $site ) ) ) . '</a></div>';
            }
            $out .= '</div>';
        }
        $out .= '</div></div>';
    }
    return $out;
}

/**
 * List of performances that have their own sponsor, as HTML.
 */
function stagekitwp_performance_sponsors_html( $show_id ) {
    $map = stagekitwp_get_performance_sponsors( $show_id );
    if ( ! $map ) {
        return '';
    }
    ksort( $map );
    $items = '';
    foreach ( $map as $datetime => $sponsor_id ) {
        $ts = strtotime( $datetime );
        if ( ! $ts || get_post_type( $sponsor_id ) !== 'sponsor' || get_post_status( $sponsor_id ) !== 'publish' ) {
            continue;
        }
        $items .= '<li>' . esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $ts, wp_timezone() ) )
                . ' &ndash; ' . stagekitwp_sponsor_credit_html( (int) $sponsor_id, 'Sponsored by', false ) . '</li>';
    }
    return $items ? '<ul class="stagekitwp-performance-sponsors">' . $items . '</ul>' : '';
}

/**
 * [stagekitwp_sponsor_credit show_id="" season="" label="Sponsored by" performances="false"]
 * Shows the featured sponsor for a show (falls back to the season's), or for a
 * season when `season` is given. Set performances="true" to list per-performance sponsors.
 */
function stagekitwp_sponsor_credit_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'show_id'      => '',
        'season'       => '',
        'label'        => 'Sponsored by',
        'performances' => 'false',
    ), $atts, 'stagekitwp_sponsor_credit' );

    $html = '';
    if ( $atts['season'] !== '' ) {
        $season_id = stagekitwp_resolve_season_attr( $atts['season'] );
        $sid       = $season_id > 0 ? absint( get_post_meta( $season_id, '_stagekitwp_season_featured_sponsor', true ) ) : 0;
        if ( $sid && get_post_status( $sid ) === 'publish' ) {
            $html = stagekitwp_sponsor_credit_html( $sid, $atts['label'] );
        }
    } else {
        $show_id = $atts['show_id'] !== '' ? absint( $atts['show_id'] ) : (int) get_the_ID();
        if ( $show_id && get_post_type( $show_id ) === 'show' ) {
            $html = stagekitwp_sponsor_credit_html( stagekitwp_get_show_sponsor_id( $show_id ), $atts['label'] );
            if ( in_array( strtolower( $atts['performances'] ), array( '1', 'true', 'yes' ), true ) ) {
                $html .= stagekitwp_performance_sponsors_html( $show_id );
            }
        }
    }
    return $html ? '<div class="stagekitwp-sponsor-credit-wrap">' . $html . '</div>' : '';
}
add_shortcode( 'stagekitwp_sponsor_credit', 'stagekitwp_sponsor_credit_shortcode' );

/* ---------------------------------------------------------------------
 * Audition slots: each has a start (Y-m-d\TH:i), a duration in minutes
 * and an optional venue. Stored in _stagekitwp_show_audition_slots.
 * ------------------------------------------------------------------ */

define( 'STAGEKITWP_AUDITION_DEFAULT_MINUTES', 120 );

/**
 * Saved audition slots for a show, sorted by start. Legacy single-date
 * auditions (no time) are not returned here.
 *
 * @return array[] Each: array( 'start' => string, 'duration' => int, 'venue' => int ).
 */
function stagekitwp_get_audition_slots( $show_id ) {
    $slots = get_post_meta( $show_id, '_stagekitwp_show_audition_slots', true );
    if ( ! is_array( $slots ) ) {
        return array();
    }
    $clean = array();
    foreach ( $slots as $slot ) {
        if ( ! is_array( $slot ) || empty( $slot['start'] ) || ! is_string( $slot['start'] ) ) {
            continue;
        }
        $clean[] = array(
            'start'    => $slot['start'],
            'duration' => max( 1, (int) ( $slot['duration'] ?? STAGEKITWP_AUDITION_DEFAULT_MINUTES ) ),
            'venue'    => absint( $slot['venue'] ?? 0 ),
        );
    }
    usort( $clean, function ( $a, $b ) {
        return strcmp( $a['start'], $b['start'] );
    } );
    return $clean;
}

/**
 * Validate parallel posted arrays into a list of slots. Invalid or duplicate
 * start values are dropped.
 */
function stagekitwp_sanitize_audition_slots( $starts, $durations, $venues ) {
    $starts    = is_array( $starts ) ? array_values( $starts ) : array();
    $durations = is_array( $durations ) ? array_values( $durations ) : array();
    $venues    = is_array( $venues ) ? array_values( $venues ) : array();
    $slots     = array();
    foreach ( $starts as $i => $start ) {
        if ( ! is_string( $start ) ) {
            continue;
        }
        $start = sanitize_text_field( $start );
        if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})$/', $start, $m )
            || ! checkdate( (int) $m[2], (int) $m[3], (int) $m[1] )
            || (int) $m[4] > 23 || (int) $m[5] > 59 || isset( $slots[ $start ] ) ) {
            continue;
        }
        $minutes = isset( $durations[ $i ] ) && is_scalar( $durations[ $i ] ) ? absint( $durations[ $i ] ) : 0;
        if ( $minutes < 1 || $minutes > 1440 ) {
            $minutes = STAGEKITWP_AUDITION_DEFAULT_MINUTES;
        }
        $venue = isset( $venues[ $i ] ) && is_scalar( $venues[ $i ] ) ? stagekitwp_sanitize_post_id_of_type( $venues[ $i ], 'venue' ) : '';
        $slots[ $start ] = array(
            'start'    => $start,
            'duration' => $minutes,
            'venue'    => (int) $venue,
        );
    }
    ksort( $slots );
    return array_values( $slots );
}

/**
 * Save slots and keep the legacy single audition date (used by the
 * [stagekitwp_auditions] shortcode) in step: the next upcoming slot's date,
 * or the last slot's date when all are past. With no slots the legacy date
 * is left as entered.
 */
function stagekitwp_save_audition_slots( $show_id, $slots ) {
    if ( ! $slots ) {
        delete_post_meta( $show_id, '_stagekitwp_show_audition_slots' );
        return;
    }
    update_post_meta( $show_id, '_stagekitwp_show_audition_slots', $slots );
    $now    = current_time( 'Y-m-d\TH:i' );
    $chosen = end( $slots );
    foreach ( $slots as $slot ) {
        if ( $slot['start'] >= $now ) {
            $chosen = $slot;
            break;
        }
    }
    update_post_meta( $show_id, '_stagekitwp_show_audition_date', substr( $chosen['start'], 0, 10 ) );
}

/**
 * Markup for one audition slot row. $names maps start/duration/venue to input names.
 */
function stagekitwp_audition_slot_row_html( $names, $slot = array(), $row_class = 'stagekitwp-audition-slot-row', $remove_class = 'stagekitwp-remove-audition-slot' ) {
    $slot   = wp_parse_args( $slot, array( 'start' => '', 'duration' => STAGEKITWP_AUDITION_DEFAULT_MINUTES, 'venue' => 0 ) );
    $venues = get_posts( array( 'post_type' => 'venue', 'numberposts' => -1, 'post_status' => 'publish', 'orderby' => 'title', 'order' => 'ASC' ) );
    $html   = '<p class="' . esc_attr( $row_class ) . '">';
    $html  .= '<input type="datetime-local" name="' . esc_attr( $names['start'] ) . '" value="' . esc_attr( $slot['start'] ) . '" /> ';
    $html  .= '<label>Duration (min) <input type="number" min="1" max="1440" step="5" style="width:80px;" name="' . esc_attr( $names['duration'] ) . '" value="' . esc_attr( (string) $slot['duration'] ) . '" /></label> ';
    $html  .= '<select name="' . esc_attr( $names['venue'] ) . '"><option value="">— Show venue —</option>';
    foreach ( $venues as $venue ) {
        $label = get_post_meta( $venue->ID, '_stagekitwp_venue_name', true ) ?: $venue->post_title;
        $html .= '<option value="' . esc_attr( $venue->ID ) . '" ' . selected( (int) $slot['venue'], $venue->ID, false ) . '>' . esc_html( $label ) . '</option>';
    }
    $html .= '</select> <button type="button" class="button ' . esc_attr( $remove_class ) . '">Remove</button></p>';
    return $html;
}
