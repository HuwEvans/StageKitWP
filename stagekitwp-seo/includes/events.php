<?php
/**
 * Event schema for Members plugin events (stagekitwp_event).
 *
 * Emits a single schema.org Event for public events that have a location.
 * Events without a venue or location text are left as plain articles.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * One-line "date, time at place [status]" summary for llms.txt.
 */
function stagekitwp_seo_member_event_summary( $event_post ) {
    $id   = $event_post->ID;
    $date = get_post_meta( $id, '_stagekitwp_members_event_date', true );
    if ( ! $date || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
        return '';
    }
    $tz    = wp_timezone();
    $start = (string) get_post_meta( $id, '_stagekitwp_members_start_time', true );
    $end   = (string) get_post_meta( $id, '_stagekitwp_members_end_time', true );
    $day   = date_create_immutable( $date, $tz );
    if ( ! $day ) {
        return '';
    }
    $when = $day->format( 'F j, Y' );
    if ( preg_match( '/^\d{1,2}:\d{2}/', $start ) ) {
        $s     = date_create_immutable( $date . ' ' . substr( $start, 0, 5 ), $tz );
        $when .= $s ? ' at ' . $s->format( 'g:i a' ) : '';
        if ( $s && preg_match( '/^\d{1,2}:\d{2}/', $end ) ) {
            $e     = date_create_immutable( $date . ' ' . substr( $end, 0, 5 ), $tz );
            $when .= $e && $e > $s ? ' to ' . $e->format( 'g:i a' ) : '';
        }
    }

    $format   = get_post_meta( $id, '_stagekitwp_members_event_format', true );
    $venue_id = absint( get_post_meta( $id, '_stagekitwp_members_event_venue', true ) );
    $place    = $venue_id ? get_the_title( $venue_id ) : trim( (string) get_post_meta( $id, '_stagekitwp_members_event_location', true ) );
    if ( 'online' === $format ) {
        $place = 'online';
    } elseif ( 'hybrid' === $format && $place ) {
        $place .= ' and online';
    }
    $out = $when . ( $place ? ' — ' . $place : '' );

    $status = get_post_meta( $id, '_stagekitwp_members_event_status', true );
    if ( in_array( $status, array( 'cancelled', 'postponed' ), true ) ) {
        $out .= ' [' . strtoupper( $status ) . ']';
    }
    return $out;
}

/**
 * Apply Event properties to $schema for a members event post.
 * Returns the (possibly unchanged) schema array.
 */
function stagekitwp_seo_apply_member_event_schema( $schema, $event_post ) {
    $settings = function_exists( 'stagekitwp_seo_audition_settings' ) ? stagekitwp_seo_audition_settings() : array( 'currency' => 'CAD' );
    $currency = preg_match( '/^[A-Z]{3}$/', (string) ( $settings['currency'] ?? '' ) ) ? $settings['currency'] : 'CAD';
    $id       = $event_post->ID;

    if ( 'public' !== get_post_meta( $id, '_stagekitwp_members_event_visibility', true ) ) {
        return $schema;
    }

    $date = get_post_meta( $id, '_stagekitwp_members_event_date', true );
    if ( ! $date || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
        return $schema; // No usable date.
    }

    $format     = get_post_meta( $id, '_stagekitwp_members_event_format', true );
    $format     = in_array( $format, array( 'in_person', 'online', 'hybrid' ), true ) ? $format : 'in_person';
    $venue_id   = absint( get_post_meta( $id, '_stagekitwp_members_event_venue', true ) );
    $location   = trim( (string) get_post_meta( $id, '_stagekitwp_members_event_location', true ) );
    $online_url = (string) get_post_meta( $id, '_stagekitwp_members_event_online_url', true );

    $places = array();
    if ( $venue_id && 'venue' === get_post_type( $venue_id ) && function_exists( 'stagekitwp_seo_audition_place' ) ) {
        $places[] = stagekitwp_seo_audition_place( $venue_id );
    } elseif ( $location ) {
        $places[] = array( '@type' => 'Place', 'name' => $location, 'address' => $location );
    }
    if ( 'online' !== $format && ! $places ) {
        return $schema; // Physical events need a location.
    }
    if ( 'online' === $format && ! $online_url ) {
        return $schema;
    }
    if ( 'online' !== $format ) {
        $place = $places[0];
    }
    if ( 'hybrid' === $format && $online_url ) {
        $location_node = array( $place, array( '@type' => 'VirtualLocation', 'url' => $online_url ) );
    } elseif ( 'online' === $format ) {
        $location_node = array( '@type' => 'VirtualLocation', 'url' => $online_url );
    } else {
        $location_node = $place;
    }

    $tz         = wp_timezone();
    $start_time = (string) get_post_meta( $id, '_stagekitwp_members_start_time', true );
    $end_time   = (string) get_post_meta( $id, '_stagekitwp_members_end_time', true );
    $start      = null;
    $end        = null;
    if ( preg_match( '/^\d{1,2}:\d{2}/', $start_time ) ) {
        $start = date_create_immutable( $date . ' ' . substr( $start_time, 0, 5 ), $tz );
        if ( $start && preg_match( '/^\d{1,2}:\d{2}/', $end_time ) ) {
            $end = date_create_immutable( $date . ' ' . substr( $end_time, 0, 5 ), $tz );
            if ( $end && $end <= $start ) {
                $end = null;
            }
        }
    }

    $schema['@type'] = 'Event';
    $schema['@id']   = get_permalink( $event_post ) . '#event';
    $schema['name']  = get_the_title( $event_post );
    $schema['startDate'] = $start ? $start->format( DateTimeInterface::ATOM ) : $date;
    if ( $end ) {
        $schema['endDate'] = $end->format( DateTimeInterface::ATOM );
    }

    $status_map = array(
        'scheduled' => 'EventScheduled',
        'cancelled' => 'EventCancelled',
        'postponed' => 'EventPostponed',
    );
    $status = get_post_meta( $id, '_stagekitwp_members_event_status', true );
    $schema['eventStatus'] = 'https://schema.org/' . ( $status_map[ $status ] ?? 'EventScheduled' );

    $modes = array(
        'in_person' => 'OfflineEventAttendanceMode',
        'online'    => 'OnlineEventAttendanceMode',
        'hybrid'    => 'MixedEventAttendanceMode',
    );
    $schema['eventAttendanceMode'] = 'https://schema.org/' . $modes[ $format ];
    $schema['location']            = $location_node;

    $schema['organizer'] = array(
        '@type' => 'Organization',
        'name'  => stagekitwp_seo_get_organization_name(),
        'url'   => home_url( '/' ),
    );

    $price      = get_post_meta( $id, '_stagekitwp_members_event_price', true );
    $ticket_url = get_post_meta( $id, '_stagekitwp_members_event_ticket_url', true );
    $is_free    = ! is_numeric( $price ) || (float) $price <= 0;
    $offer      = array(
        '@type'         => 'Offer',
        'url'           => $ticket_url ? esc_url_raw( $ticket_url ) : get_permalink( $event_post ),
        'price'         => $is_free ? '0' : (string) (float) $price,
        'priceCurrency' => $currency,
        'availability'  => 'cancelled' === $status ? 'https://schema.org/Discontinued' : 'https://schema.org/InStock',
    );
    $schema['offers'] = $offer;
    if ( $is_free ) {
        $schema['isAccessibleForFree'] = true;
    }

    // Event pages should not carry article-only properties.
    unset( $schema['headline'], $schema['datePublished'], $schema['dateModified'], $schema['author'], $schema['mainEntityOfPage'] );

    return $schema;
}
