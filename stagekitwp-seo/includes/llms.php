<?php
/**
 * llms.txt and llms-full.txt generation (virtual files, like WordPress sitemaps).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

const STAGEKITWP_SEO_LLMS_RULES_VERSION = '2';

function stagekitwp_seo_llms_defaults() {
    return array(
        'enabled'            => 1,
        'full_enabled'       => 1,
        'intro'              => '',
        'about_enabled'      => 1,
        'about_text'         => '',
        'last_updated'       => 1,
        'include_cast_names' => 1,
        'sponsor_mode'       => 'details', // details | link | none
        'include_pages'      => 1,
        'include_venues'     => 1,
        'include_events'     => 1,
        'include_past'       => 1,
        'full_max_chars'     => 3000,
    );
}

function stagekitwp_seo_llms_settings() {
    $saved = get_option( 'stagekitwp_seo_llms', array() );
    return wp_parse_args( is_array( $saved ) ? $saved : array(), stagekitwp_seo_llms_defaults() );
}

/* ---------- Rewrite rules, query var, output ---------- */

function stagekitwp_seo_add_llms_rewrite_rule() {
    add_rewrite_rule( '^llms\.txt$', 'index.php?stagekitwp_seo_llms=index', 'top' );
    add_rewrite_rule( '^llms-full\.txt$', 'index.php?stagekitwp_seo_llms=full', 'top' );
}
add_action( 'init', 'stagekitwp_seo_add_llms_rewrite_rule' );

add_action( 'init', function () {
    if ( get_option( 'stagekitwp_seo_llms_rules_version' ) !== STAGEKITWP_SEO_LLMS_RULES_VERSION ) {
        flush_rewrite_rules();
        update_option( 'stagekitwp_seo_llms_rules_version', STAGEKITWP_SEO_LLMS_RULES_VERSION, false );
    }
}, 99 );

add_filter( 'query_vars', function ( $query_vars ) {
    $query_vars[] = 'stagekitwp_seo_llms';
    return $query_vars;
} );

function stagekitwp_seo_render_llms_file() {
    $type = get_query_var( 'stagekitwp_seo_llms' );
    if ( ! $type ) {
        return;
    }
    if ( '1' === $type ) {
        $type = 'index';
    }

    $settings = stagekitwp_seo_llms_settings();
    if ( ! in_array( $type, array( 'index', 'full' ), true ) || empty( $settings['enabled'] ) || ( 'full' === $type && empty( $settings['full_enabled'] ) ) ) {
        global $wp_query;
        $wp_query->set_404();
        status_header( 404 );
        nocache_headers();
        return;
    }

    $version   = (int) get_option( 'stagekitwp_seo_llms_cache_version', 1 );
    $cache_key = 'stagekitwp_seo_llms_' . $type . '_' . $version;
    $output    = get_transient( $cache_key );
    if ( false === $output ) {
        // Some shortcodes echo directly; discard that output so it cannot leak into the file.
        ob_start();
        $output = 'full' === $type ? stagekitwp_seo_llms_build_full( $settings ) : stagekitwp_seo_llms_build_index( $settings );
        ob_end_clean();
        set_transient( $cache_key, $output, DAY_IN_SECONDS );
    }

    $etag = '"' . md5( $output ) . '"';
    header( 'Content-Type: text/plain; charset=utf-8' );
    header( 'X-Robots-Tag: noindex' );
    header( 'Cache-Control: public, max-age=3600' );
    header( 'ETag: ' . $etag );
    if ( isset( $_SERVER['HTTP_IF_NONE_MATCH'] ) && trim( wp_unslash( $_SERVER['HTTP_IF_NONE_MATCH'] ) ) === $etag ) {
        status_header( 304 );
        exit;
    }
    status_header( 200 );
    echo $output; // phpcs:ignore WordPress.Security.EscapeOutput -- plain text.
    exit;
}
add_action( 'template_redirect', 'stagekitwp_seo_render_llms_file' );

function stagekitwp_seo_llms_bump_cache() {
    update_option( 'stagekitwp_seo_llms_cache_version', (int) get_option( 'stagekitwp_seo_llms_cache_version', 1 ) + 1, false );
}
add_action( 'save_post', 'stagekitwp_seo_llms_bump_cache' );
add_action( 'deleted_post', 'stagekitwp_seo_llms_bump_cache' );
add_action( 'update_option_stagekitwp_seo_llms', 'stagekitwp_seo_llms_bump_cache' );
add_action( 'update_option_stagekitwp_organization_name', 'stagekitwp_seo_llms_bump_cache' );

/* ---------- Data helpers ---------- */

function stagekitwp_seo_llms_excluded( $post ) {
    if ( get_post_meta( $post->ID, '_seo_schema_robots_noindex', true ) === '1' ) {
        return true;
    }
    if ( stagekitwp_seo_is_restricted( $post ) ) {
        return true;
    }
    return 'stagekitwp_event' === $post->post_type && 'public' !== get_post_meta( $post->ID, '_stagekitwp_members_event_visibility', true );
}

function stagekitwp_seo_llms_clean( $text ) {
    return trim( preg_replace( '/\s+/', ' ', html_entity_decode( wp_strip_all_tags( (string) $text ), ENT_QUOTES, 'UTF-8' ) ) );
}

function stagekitwp_seo_llms_posts( $post_type, $args = array() ) {
    if ( ! post_type_exists( $post_type ) ) {
        return array();
    }
    $posts = get_posts( array_merge( array(
        'post_type'      => $post_type,
        'post_status'    => 'publish',
        'posts_per_page' => 500,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ), $args ) );
    return array_values( array_filter( $posts, function ( $p ) {
        return ! stagekitwp_seo_llms_excluded( $p );
    } ) );
}

function stagekitwp_seo_llms_format_datetime( $value ) {
    try {
        $dt = new DateTime( $value, wp_timezone() );
        return $dt->format( 'F j, Y \a\t g:i a' );
    } catch ( Exception $e ) {
        return $value;
    }
}

function stagekitwp_seo_llms_show_dates( $show_id ) {
    $dates = get_post_meta( $show_id, '_stagekitwp_show_actual_dates', true );
    $dates = is_array( $dates ) ? array_values( array_filter( $dates, 'is_string' ) ) : array();
    sort( $dates );
    return $dates;
}

/**
 * Seasons, newest first, with the current season identified.
 */
function stagekitwp_seo_llms_seasons() {
    $seasons = stagekitwp_seo_llms_posts( 'season', array(
        'meta_key' => '_stagekitwp_season_start_date',
        'orderby'  => 'meta_value',
        'order'    => 'DESC',
    ) );
    $current = 0;
    foreach ( $seasons as $season ) {
        if ( get_post_meta( $season->ID, '_stagekitwp_season_is_current', true ) ) {
            $current = $season->ID;
            break;
        }
    }
    if ( ! $current ) {
        $today = current_time( 'Y-m-d' );
        foreach ( $seasons as $season ) {
            $start = get_post_meta( $season->ID, '_stagekitwp_season_start_date', true );
            $end   = get_post_meta( $season->ID, '_stagekitwp_season_end_date', true );
            if ( $start && $start <= $today && ( ! $end || $end >= $today ) ) {
                $current = $season->ID;
                break;
            }
        }
    }
    return array( $seasons, $current );
}

function stagekitwp_seo_llms_season_shows( $season_id ) {
    $shows = stagekitwp_seo_llms_posts( 'show', array(
        'meta_key'   => '_stagekitwp_show_season',
        'meta_value' => $season_id,
    ) );
    $slots = array( 'fall' => 1, 'winter' => 2, 'spring' => 3 );
    usort( $shows, function ( $a, $b ) use ( $slots ) {
        $da = stagekitwp_seo_llms_show_dates( $a->ID );
        $db = stagekitwp_seo_llms_show_dates( $b->ID );
        $ka = array( $da ? $da[0] : '9999', $slots[ strtolower( (string) get_post_meta( $a->ID, '_stagekitwp_show_time_slot', true ) ) ] ?? 99 );
        $kb = array( $db ? $db[0] : '9999', $slots[ strtolower( (string) get_post_meta( $b->ID, '_stagekitwp_show_time_slot', true ) ) ] ?? 99 );
        return $ka <=> $kb;
    } );
    return $shows;
}

function stagekitwp_seo_llms_venue_name( $show_id ) {
    $venue_id = absint( get_post_meta( $show_id, '_stagekitwp_show_venue', true ) );
    if ( ! $venue_id ) {
        return '';
    }
    return get_post_meta( $venue_id, '_stagekitwp_venue_name', true ) ?: get_the_title( $venue_id );
}

function stagekitwp_seo_llms_show_line( $show ) {
    $line  = '- [' . stagekitwp_seo_llms_clean( get_the_title( $show ) ) . '](' . esc_url_raw( get_permalink( $show ) ) . ')';
    $bits  = array();
    $dates = stagekitwp_seo_llms_show_dates( $show->ID );
    if ( $dates ) {
        $first  = stagekitwp_seo_llms_format_datetime( $dates[0] );
        $last   = stagekitwp_seo_llms_format_datetime( $dates[ count( $dates ) - 1 ] );
        $bits[] = count( $dates ) > 1 ? $first . ' to ' . $last : $first;
    }
    $venue = stagekitwp_seo_llms_venue_name( $show->ID );
    if ( $venue ) {
        $bits[] = $venue;
    }
    $desc = stagekitwp_seo_description( $show );
    if ( $desc ) {
        $bits[] = $desc;
    }
    return $line . ( $bits ? ': ' . implode( '. ', $bits ) : '' ) . "\n";
}

/* ---------- Sponsors ---------- */

function stagekitwp_seo_llms_sponsors_section( $settings, $season_id ) {
    if ( 'none' === $settings['sponsor_mode'] || ! post_type_exists( 'sponsor' ) ) {
        return '';
    }
    $args = array();
    if ( $season_id ) {
        $args['meta_query'] = array( array( 'key' => '_stagekitwp_season', 'value' => $season_id ) );
    }
    $sponsors = stagekitwp_seo_llms_posts( 'sponsor', $args );
    if ( ! $sponsors ) {
        return '';
    }

    $by_level = array();
    foreach ( $sponsors as $sponsor ) {
        $level = get_post_meta( $sponsor->ID, '_stagekitwp_level', true );
        $by_level[ $level ? $level : 'Sponsors' ][] = $sponsor;
    }

    $out = "## Sponsors\n\n";
    foreach ( $by_level as $level => $items ) {
        $out .= '### ' . stagekitwp_seo_llms_clean( $level ) . "\n\n";
        foreach ( $items as $sponsor ) {
            $name    = get_post_meta( $sponsor->ID, '_stagekitwp_name', true );
            $company = get_post_meta( $sponsor->ID, '_stagekitwp_company', true );
            $site    = esc_url_raw( (string) get_post_meta( $sponsor->ID, '_stagekitwp_website', true ) );
            $label   = stagekitwp_seo_llms_clean( $company ? $company : ( $name ? $name : $sponsor->post_title ) );
            $line    = '- ' . ( $site ? '[' . $label . '](' . $site . ')' : $label );
            if ( 'details' === $settings['sponsor_mode'] ) {
                $extra = array();
                if ( $company && $name ) {
                    $extra[] = stagekitwp_seo_llms_clean( $name );
                }
                $desc = stagekitwp_seo_description( $sponsor );
                if ( $desc ) {
                    $extra[] = $desc;
                }
                if ( $extra ) {
                    $line .= ': ' . implode( '. ', $extra );
                }
            }
            $out .= $line . "\n";
        }
        $out .= "\n";
    }
    return $out;
}

/* ---------- llms.txt ---------- */

function stagekitwp_seo_llms_header( $settings ) {
    $out     = '# ' . stagekitwp_seo_llms_clean( stagekitwp_seo_get_organization_name() ) . "\n\n";
    $summary = stagekitwp_seo_llms_clean( get_bloginfo( 'description' ) );
    if ( $summary ) {
        $out .= '> ' . $summary . "\n\n";
    }
    if ( '' !== trim( (string) $settings['intro'] ) ) {
        $out .= trim( str_replace( "\r", '', $settings['intro'] ) ) . "\n\n";
    }
    $address = stagekitwp_seo_llms_clean( stagekitwp_seo_get_organization_address() );
    if ( $address ) {
        $out .= 'Address: ' . $address . "\n\n";
    }
    $out .= stagekitwp_seo_llms_about_block( $settings );
    return $out;
}

/**
 * Factual notes for readers of the file (source of truth, time zone, citation name),
 * plus an automatic "last updated" line.
 */
function stagekitwp_seo_llms_default_about() {
    $name = stagekitwp_seo_llms_clean( stagekitwp_seo_get_organization_name() );
    $tz   = wp_timezone_string();
    $tz   = false === strpos( $tz, '/' ) && 'UTC' !== $tz ? 'UTC' . $tz : $tz;
    return "- Show pages are the authoritative source for dates, times, venues and ticket links.\n"
        . '- All times are in ' . $tz . ".\n"
        . "- Cast lists cover the current season; cast and crew roles are volunteer.\n"
        . '- When citing us, please use the name "' . $name . '" and link to the relevant page.';
}

function stagekitwp_seo_llms_last_updated() {
    global $wpdb;
    $types = array( 'show', 'season', 'sponsor', 'venue', 'page' );
    $in    = "'" . implode( "','", array_map( 'esc_sql', $types ) ) . "'";
    $date  = $wpdb->get_var( "SELECT MAX(post_modified_gmt) FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type IN ($in)" ); // phpcs:ignore WordPress.DB.PreparedSQL
    return $date ? gmdate( 'Y-m-d', strtotime( $date . ' UTC' ) ) : '';
}

function stagekitwp_seo_llms_about_block( $settings ) {
    $out = '';
    if ( ! empty( $settings['about_enabled'] ) ) {
        $text = '' !== trim( (string) $settings['about_text'] ) ? trim( str_replace( "\r", '', $settings['about_text'] ) ) : stagekitwp_seo_llms_default_about();
        $out .= "## About this file\n\n" . $text . "\n\n";
    }
    if ( ! empty( $settings['last_updated'] ) ) {
        $date = stagekitwp_seo_llms_last_updated();
        if ( $date ) {
            $out .= 'Last updated: ' . $date . "\n\n";
        }
    }
    return $out;
}

function stagekitwp_seo_llms_extra_sections( $settings ) {
    $sections = array();
    if ( ! empty( $settings['include_venues'] ) ) {
        $sections['Venues'] = 'venue';
    }
    if ( ! empty( $settings['include_events'] ) ) {
        $sections['Events'] = 'stagekitwp_event';
    }
    if ( ! empty( $settings['include_pages'] ) ) {
        $sections['Pages'] = 'page';
    }
    return $sections;
}

function stagekitwp_seo_llms_auditions_section() {
    if ( ! function_exists( 'stagekitwp_seo_audition_slots' ) ) {
        return '';
    }
    $out = '';
    foreach ( stagekitwp_seo_llms_posts( 'show' ) as $show ) {
        $slots = stagekitwp_seo_audition_slots( $show->ID );
        if ( ! $slots ) {
            continue;
        }
        $show_venue = absint( get_post_meta( $show->ID, '_stagekitwp_show_venue', true ) );
        $out .= '- [' . stagekitwp_seo_llms_clean( get_the_title( $show ) ) . '](' . esc_url_raw( get_permalink( $show ) ) . ')' . "\n";
        foreach ( $slots as $slot ) {
            $venue = $slot['venue'] ? $slot['venue'] : $show_venue;
            $when  = $slot['all_day'] ? $slot['start']->format( 'F j, Y' ) : $slot['start']->format( 'F j, Y \a\t g:i a' ) . ' (' . ( (int) ( ( $slot['end']->getTimestamp() - $slot['start']->getTimestamp() ) / 60 ) ) . ' min)';
            $out  .= '  - ' . $when . ( $venue ? ' at ' . stagekitwp_seo_llms_clean( get_the_title( $venue ) ) : '' ) . "\n";
        }
    }
    return $out ? "## Auditions\n\nOpen auditions (volunteer, free to attend).\n\n" . $out . "\n" : '';
}

function stagekitwp_seo_llms_build_index( $settings ) {
    $out = stagekitwp_seo_llms_header( $settings );
    list( $seasons, $current ) = stagekitwp_seo_llms_seasons();

    if ( $current ) {
        $out .= '## Current Season: ' . stagekitwp_seo_llms_clean( get_the_title( $current ) ) . "\n\n";
        $out .= '- [Season page](' . esc_url_raw( get_permalink( $current ) ) . ")\n";
        foreach ( stagekitwp_seo_llms_season_shows( $current ) as $show ) {
            $out .= stagekitwp_seo_llms_show_line( $show );
        }
        $out .= "\n";
    }

    if ( ! empty( $settings['include_past'] ) ) {
        $past = '';
        foreach ( $seasons as $season ) {
            if ( $season->ID === $current ) {
                continue;
            }
            $past .= '### ' . stagekitwp_seo_llms_clean( get_the_title( $season ) ) . "\n\n";
            $past .= '- [Season page](' . esc_url_raw( get_permalink( $season ) ) . ")\n";
            foreach ( stagekitwp_seo_llms_season_shows( $season->ID ) as $show ) {
                $past .= stagekitwp_seo_llms_show_line( $show );
            }
            $past .= "\n";
        }
        if ( $past ) {
            $out .= "## Other Seasons\n\n" . $past;
        }
    }

    $out .= stagekitwp_seo_llms_sponsors_section( $settings, $current );
    $out .= stagekitwp_seo_llms_auditions_section();

    foreach ( stagekitwp_seo_llms_extra_sections( $settings ) as $heading => $type ) {
        $posts = stagekitwp_seo_llms_posts( $type, 'page' === $type ? array( 'orderby' => 'menu_order title' ) : array() );
        if ( ! $posts ) {
            continue;
        }
        $out .= '## ' . $heading . "\n\n";
        foreach ( $posts as $post ) {
            $desc = stagekitwp_seo_description( $post );
            if ( 'stagekitwp_event' === $type && function_exists( 'stagekitwp_seo_member_event_summary' ) ) {
                $summary = stagekitwp_seo_member_event_summary( $post );
                $desc    = $summary ? $summary . ( $desc ? '. ' . $desc : '' ) : $desc;
            }
            $out .= '- [' . stagekitwp_seo_llms_clean( get_the_title( $post ) ) . '](' . esc_url_raw( get_permalink( $post ) ) . ')' . ( $desc ? ': ' . $desc : '' ) . "\n";
        }
        $out .= "\n";
    }

    if ( ! empty( $settings['full_enabled'] ) ) {
        $out .= "## Optional\n\n- [Full content](" . esc_url_raw( home_url( '/llms-full.txt' ) ) . "): Complete text of the pages and shows above.\n";
    }

    return apply_filters( 'stagekitwp_seo_llms_index', rtrim( $out ) . "\n", $settings );
}

/* ---------- llms-full.txt ---------- */

function stagekitwp_seo_llms_html_to_markdown( $html ) {
    $html = preg_replace( '#<(script|style)\b[^>]*>.*?</\1>#is', '', $html );
    $html = preg_replace_callback( '#<h([1-6])[^>]*>(.*?)</h\1>#is', function ( $m ) {
        return "\n\n" . str_repeat( '#', min( 6, (int) $m[1] + 2 ) ) . ' ' . trim( wp_strip_all_tags( $m[2] ) ) . "\n\n";
    }, $html );
    $html = preg_replace_callback( '#<a\b[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)</a>#is', function ( $m ) {
        $text = trim( wp_strip_all_tags( $m[2] ) );
        return '' === $text ? '' : '[' . $text . '](' . $m[1] . ')';
    }, $html );
    $html = preg_replace( '#<li[^>]*>#i', "\n- ", $html );
    $html = preg_replace( '#<(br|/p|/div|/tr|/ul|/ol)[^>]*>#i', "\n", $html );
    $text = html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES, 'UTF-8' );
    $text = preg_replace( "/[ \t]+/", ' ', $text );
    return trim( preg_replace( "/\n{3,}/", "\n\n", $text ) );
}

function stagekitwp_seo_llms_post_text( $post, $max ) {
    $GLOBALS['post'] = $post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
    setup_postdata( $post );
    $content = apply_filters( 'the_content', $post->post_content );
    wp_reset_postdata();
    $text = stagekitwp_seo_llms_html_to_markdown( $content );
    // Drop shortcodes that were not registered and so did not expand (curly quotes included).
    $text = trim( preg_replace( "/\n{3,}/", "\n\n", preg_replace( '/^\[\w+[^\]\n]*\]\s*$/m', '', $text ) ) );
    if ( $max > 0 && mb_strlen( $text ) > $max ) {
        $text = rtrim( mb_substr( $text, 0, $max ) ) . '… (continued at ' . esc_url_raw( get_permalink( $post ) ) . ')';
    }
    return $text;
}

function stagekitwp_seo_llms_cast_block( $show_id, $settings ) {
    $cast = get_posts( array(
        'post_type'      => 'cast',
        'post_status'    => 'publish',
        'posts_per_page' => 200,
        'meta_key'       => '_stagekitwp_cast_show',
        'meta_value'     => $show_id,
    ) );
    if ( ! $cast ) {
        return '';
    }
    $lines = '';
    foreach ( $cast as $member ) {
        $character = stagekitwp_seo_llms_clean( get_post_meta( $member->ID, '_stagekitwp_cast_character_name', true ) );
        $actor     = stagekitwp_seo_llms_clean( get_post_meta( $member->ID, '_stagekitwp_cast_actor_name', true ) );
        if ( ! empty( $settings['include_cast_names'] ) ) {
            if ( $character || $actor ) {
                $lines .= '- ' . $character . ( $character && $actor ? ' — ' : '' ) . $actor . "\n";
            }
        } elseif ( $character ) {
            $lines .= '- ' . $character . "\n";
        }
    }
    return $lines ? "#### Cast\n\n" . $lines . "\n" : '';
}

function stagekitwp_seo_llms_show_block( $show, $settings ) {
    $out   = '### ' . stagekitwp_seo_llms_clean( get_the_title( $show ) ) . "\n\n";
    $out  .= 'URL: ' . esc_url_raw( get_permalink( $show ) ) . "\n";
    $facts = array(
        'Author'             => '_stagekitwp_show_author',
        'Director'           => '_stagekitwp_show_director',
        'Associate Director' => '_stagekitwp_show_associate_director',
        'Producer'           => '_stagekitwp_show_producer',
        'Stage Manager'      => '_stagekitwp_show_stage_manager',
        'Genre'              => '_stagekitwp_show_genre',
    );
    foreach ( $facts as $label => $key ) {
        $value = stagekitwp_seo_llms_clean( get_post_meta( $show->ID, $key, true ) );
        if ( $value ) {
            $out .= $label . ': ' . $value . "\n";
        }
    }
    $venue = stagekitwp_seo_llms_venue_name( $show->ID );
    if ( $venue ) {
        $out .= 'Venue: ' . stagekitwp_seo_llms_clean( $venue ) . "\n";
    }
    $dates = stagekitwp_seo_llms_show_dates( $show->ID );
    if ( $dates ) {
        $out .= 'Performances: ' . implode( '; ', array_map( 'stagekitwp_seo_llms_format_datetime', $dates ) ) . "\n";
    }
    $tickets = esc_url_raw( (string) get_post_meta( $show->ID, '_stagekitwp_show_tickets_url', true ) );
    if ( $tickets ) {
        $out .= 'Tickets: ' . $tickets . "\n";
    }
    $synopsis = trim( (string) get_post_meta( $show->ID, '_stagekitwp_show_synopsis', true ) );
    if ( $synopsis ) {
        $out .= "\n" . html_entity_decode( wp_strip_all_tags( $synopsis ), ENT_QUOTES, 'UTF-8' ) . "\n";
    }

    if ( 'none' !== $settings['sponsor_mode'] && function_exists( 'stagekitwp_get_show_sponsor_id' ) ) {
        $sponsor_id = stagekitwp_get_show_sponsor_id( $show->ID );
        if ( $sponsor_id ) {
            $company = get_post_meta( $sponsor_id, '_stagekitwp_company', true ) ?: get_the_title( $sponsor_id );
            $site    = esc_url_raw( (string) get_post_meta( $sponsor_id, '_stagekitwp_website', true ) );
            $name    = stagekitwp_seo_llms_clean( $company );
            if ( 'link' === $settings['sponsor_mode'] ) {
                $out .= "\nSponsored by " . ( $site ? '[' . $name . '](' . $site . ')' : $name ) . "\n";
            } else {
                $person = stagekitwp_seo_llms_clean( get_post_meta( $sponsor_id, '_stagekitwp_name', true ) );
                $level  = stagekitwp_seo_llms_clean( get_post_meta( $sponsor_id, '_stagekitwp_level', true ) );
                $extra  = array_filter( array( $person && $person !== $name ? $person : '', $level ? $level . ' sponsor' : '', $site ) );
                $out   .= "\nSponsored by " . $name . ( $extra ? ' (' . implode( ', ', $extra ) . ')' : '' ) . "\n";
            }
        }
    }
    return $out . "\n" . stagekitwp_seo_llms_cast_block( $show->ID, $settings );
}

function stagekitwp_seo_llms_build_full( $settings ) {
    $out = stagekitwp_seo_llms_header( $settings );
    list( $seasons, $current ) = stagekitwp_seo_llms_seasons();
    $max = (int) $settings['full_max_chars'];

    foreach ( $seasons as $season ) {
        if ( $season->ID !== $current && empty( $settings['include_past'] ) ) {
            continue;
        }
        $out .= '## ' . ( $season->ID === $current ? 'Current Season: ' : 'Season: ' ) . stagekitwp_seo_llms_clean( get_the_title( $season ) ) . "\n\n";
        $out .= 'URL: ' . esc_url_raw( get_permalink( $season ) ) . "\n\n";
        foreach ( stagekitwp_seo_llms_season_shows( $season->ID ) as $show ) {
            $out .= stagekitwp_seo_llms_show_block( $show, $settings );
        }
    }

    $out .= stagekitwp_seo_llms_sponsors_section( $settings, $current );
    $out .= stagekitwp_seo_llms_auditions_section();

    foreach ( stagekitwp_seo_llms_extra_sections( $settings ) as $heading => $type ) {
        $posts = stagekitwp_seo_llms_posts( $type, 'page' === $type ? array( 'orderby' => 'menu_order title' ) : array() );
        if ( ! $posts ) {
            continue;
        }
        $out .= '## ' . $heading . "\n\n";
        foreach ( $posts as $post ) {
            $out .= '### ' . stagekitwp_seo_llms_clean( get_the_title( $post ) ) . "\n\nURL: " . esc_url_raw( get_permalink( $post ) ) . "\n\n";
            if ( 'stagekitwp_event' === $type && function_exists( 'stagekitwp_seo_member_event_summary' ) ) {
                $summary = stagekitwp_seo_member_event_summary( $post );
                if ( $summary ) {
                    $out .= 'When and where: ' . $summary . "\n\n";
                }
            }
            if ( $text ) {
                $out .= $text . "\n\n";
            }
        }
    }

    return apply_filters( 'stagekitwp_seo_llms_full', rtrim( $out ) . "\n", $settings );
}

/* ---------- Settings page ---------- */

add_action( 'admin_menu', function () {
    add_options_page( 'LLMs.txt', 'LLMs.txt', 'manage_options', 'stagekitwp-seo-llms', 'stagekitwp_seo_llms_settings_page' );
} );

add_action( 'admin_init', function () {
    register_setting( 'stagekitwp_seo_llms_group', 'stagekitwp_seo_llms', array(
        'type'              => 'array',
        'sanitize_callback' => 'stagekitwp_seo_llms_sanitize',
        'default'           => stagekitwp_seo_llms_defaults(),
    ) );
} );

function stagekitwp_seo_llms_sanitize( $input ) {
    $input = is_array( $input ) ? $input : array();
    $out   = array();
    foreach ( array( 'enabled', 'full_enabled', 'include_cast_names', 'include_pages', 'include_venues', 'include_events', 'include_past', 'about_enabled', 'last_updated' ) as $flag ) {
        $out[ $flag ] = empty( $input[ $flag ] ) ? 0 : 1;
    }
    $out['about_text']     = isset( $input['about_text'] ) ? sanitize_textarea_field( $input['about_text'] ) : '';
    $out['intro']          = isset( $input['intro'] ) ? sanitize_textarea_field( $input['intro'] ) : '';
    $out['sponsor_mode']   = isset( $input['sponsor_mode'] ) && in_array( $input['sponsor_mode'], array( 'details', 'link', 'none' ), true ) ? $input['sponsor_mode'] : 'details';
    $out['full_max_chars'] = isset( $input['full_max_chars'] ) ? min( 50000, absint( $input['full_max_chars'] ) ) : 3000;
    return $out;
}

function stagekitwp_seo_llms_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    $s     = stagekitwp_seo_llms_settings();
    $n     = 'stagekitwp_seo_llms';
    $check = function ( $key, $label ) use ( $s, $n ) {
        echo '<label><input type="checkbox" name="' . esc_attr( $n . '[' . $key . ']' ) . '" value="1" ' . checked( ! empty( $s[ $key ] ), true, false ) . ' /> ' . esc_html( $label ) . '</label><br />';
    };
    ?>
    <div class="wrap">
        <h1>LLMs.txt</h1>
        <p>Generates <a href="<?php echo esc_url( home_url( '/llms.txt' ) ); ?>" target="_blank" rel="noopener">/llms.txt</a> (curated index) and <a href="<?php echo esc_url( home_url( '/llms-full.txt' ) ); ?>" target="_blank" rel="noopener">/llms-full.txt</a> (full content) from your site content. Pages marked "noindex" and non-public events are never included.</p>
        <?php if ( stagekitwp_seo_has_competing_plugin() ) : ?>
            <div class="notice notice-warning inline"><p>Another SEO plugin is active. Check it is not also serving an llms.txt file.</p></div>
        <?php endif; ?>
        <?php if ( file_exists( ABSPATH . 'llms.txt' ) || file_exists( ABSPATH . 'llms-full.txt' ) ) : ?>
            <div class="notice notice-warning inline"><p>A physical llms.txt or llms-full.txt file exists in the site root and will be served instead of the generated one.</p></div>
        <?php endif; ?>
        <form method="post" action="options.php">
            <?php settings_fields( 'stagekitwp_seo_llms_group' ); ?>
            <table class="form-table" role="presentation">
                <tr><th scope="row">Files</th><td>
                    <?php $check( 'enabled', 'Enable /llms.txt' ); $check( 'full_enabled', 'Enable /llms-full.txt' ); ?>
                </td></tr>
                <tr><th scope="row"><label for="stagekitwp-llms-intro">Introduction</label></th><td>
                    <textarea id="stagekitwp-llms-intro" name="<?php echo esc_attr( $n ); ?>[intro]" rows="6" class="large-text"><?php echo esc_textarea( $s['intro'] ); ?></textarea>
                    <p class="description">Shown at the top of both files, under the site name and tagline. Plain text or Markdown.</p>
                </td></tr>
                <tr><th scope="row">About this file</th><td>
                    <?php $check( 'about_enabled', 'Include an "About this file" notes block' ); $check( 'last_updated', 'Include a "Last updated" line (latest edit to a show, season, sponsor, venue or page)' ); ?>
                    <textarea name="<?php echo esc_attr( $n ); ?>[about_text]" rows="6" class="large-text" placeholder="<?php echo esc_attr( stagekitwp_seo_llms_default_about() ); ?>"><?php echo esc_textarea( $s['about_text'] ); ?></textarea>
                    <p class="description">Factual notes for readers (AI tools and people). Leave blank to use the default text shown in grey. Avoid commands; models tend to ignore them.</p>
                </td></tr>
                <tr><th scope="row">Cast</th><td>
                    <?php $check( 'include_cast_names', 'Include actor names (uncheck to list characters only)' ); ?>
                </td></tr>
                <tr><th scope="row">Sponsors</th><td>
                    <select name="<?php echo esc_attr( $n ); ?>[sponsor_mode]">
                        <option value="details" <?php selected( $s['sponsor_mode'], 'details' ); ?>>Details (name, company, website, description)</option>
                        <option value="link" <?php selected( $s['sponsor_mode'], 'link' ); ?>>Link only (name linked to website)</option>
                        <option value="none" <?php selected( $s['sponsor_mode'], 'none' ); ?>>Do not include sponsors</option>
                    </select>
                    <p class="description">Sponsors listed are those assigned to the current season.</p>
                </td></tr>
                <tr><th scope="row">Content</th><td>
                    <?php
                    $check( 'include_past', 'Past seasons and shows' );
                    $check( 'include_venues', 'Venues' );
                    $check( 'include_events', 'Events' );
                    $check( 'include_pages', 'Pages' );
                    ?>
                </td></tr>
                <tr><th scope="row"><label for="stagekitwp-llms-max">Full file: max characters per page</label></th><td>
                    <input id="stagekitwp-llms-max" type="number" min="0" max="50000" step="100" name="<?php echo esc_attr( $n ); ?>[full_max_chars]" value="<?php echo esc_attr( $s['full_max_chars'] ); ?>" />
                    <p class="description">Longer pages are truncated with a link to the live page. 0 means no limit.</p>
                </td></tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
