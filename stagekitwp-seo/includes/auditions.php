<?php
/**
 * Audition Event schema: one free Event per upcoming audition slot on a show page.
 * Slots come from the core plugin (_stagekitwp_show_audition_slots).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function stagekitwp_seo_audition_settings() {
    $saved = get_option( 'stagekitwp_seo_auditions', array() );
    return wp_parse_args( is_array( $saved ) ? $saved : array(), array( 'enabled' => 1, 'currency' => 'CAD' ) );
}

/**
 * Upcoming audition slots as normalised arrays: start (DateTimeImmutable), end, venue ID.
 * Falls back to the legacy date-only audition, which has no time and so is returned
 * with an all-day flag and no end.
 */
function stagekitwp_seo_audition_slots( $show_id ) {
    $tz    = wp_timezone();
    $now   = new DateTimeImmutable( 'now', $tz );
    $out   = array();
    $slots = function_exists( 'stagekitwp_get_audition_slots' ) ? stagekitwp_get_audition_slots( $show_id ) : array();

    foreach ( $slots as $slot ) {
        $start = DateTimeImmutable::createFromFormat( '!Y-m-d\TH:i', $slot['start'], $tz );
        if ( ! $start ) {
            continue;
        }
        $end = $start->modify( '+' . (int) $slot['duration'] . ' minutes' );
        if ( $end < $now ) {
            continue;
        }
        $out[] = array( 'start' => $start, 'end' => $end, 'venue' => (int) $slot['venue'], 'all_day' => false );
    }

    if ( ! $slots ) {
        $legacy = get_post_meta( $show_id, '_stagekitwp_show_audition_date', true );
        if ( is_string( $legacy ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $legacy ) ) {
            $start = DateTimeImmutable::createFromFormat( '!Y-m-d', $legacy, $tz );
            if ( $start && $start->modify( '+1 day' ) >= $now ) {
                $out[] = array( 'start' => $start, 'end' => null, 'venue' => 0, 'all_day' => true );
            }
        }
    }
    return $out;
}

function stagekitwp_seo_audition_place( $venue_id ) {
    $place = array( '@type' => 'Place', 'name' => get_the_title( $venue_id ), 'url' => get_permalink( $venue_id ) );
    $addr  = get_post_meta( $venue_id, '_stagekitwp_venue_address', true );
    $lat   = get_post_meta( $venue_id, '_stagekitwp_venue_latitude', true );
    $lng   = get_post_meta( $venue_id, '_stagekitwp_venue_longitude', true );
    if ( $addr ) {
        $place['address'] = $addr;
    }
    if ( is_numeric( $lat ) && is_numeric( $lng ) ) {
        $place['geo'] = array( '@type' => 'GeoCoordinates', 'latitude' => (float) $lat, 'longitude' => (float) $lng );
    }
    return $place;
}

/**
 * Build audition Event nodes for a show post. $show_node is the main show schema,
 * used for its organizer, image and fallback location.
 */
function stagekitwp_seo_audition_events( $show_post, $show_node ) {
    $settings = stagekitwp_seo_audition_settings();
    if ( empty( $settings['enabled'] ) ) {
        return array();
    }

    $show_venue = absint( get_post_meta( $show_post->ID, '_stagekitwp_show_venue', true ) );
    $details    = wp_strip_all_tags( (string) get_post_meta( $show_post->ID, '_stagekitwp_show_audition_details', true ) );
    $details    = trim( preg_replace( '/\s+/', ' ', html_entity_decode( $details, ENT_QUOTES, 'UTF-8' ) ) );
    $title      = get_the_title( $show_post );
    $url        = get_permalink( $show_post );
    $currency   = preg_match( '/^[A-Z]{3}$/', (string) $settings['currency'] ) ? $settings['currency'] : 'CAD';
    $events     = array();

    foreach ( stagekitwp_seo_audition_slots( $show_post->ID ) as $slot ) {
        $venue_id = $slot['venue'] ? $slot['venue'] : $show_venue;
        if ( ! $venue_id ) {
            continue; // Google requires a location.
        }
        $event = array(
            '@type'               => 'Event',
            '@id'                 => $url . '#audition-' . $slot['start']->format( 'Ymd\THi' ),
            'name'                => 'Auditions for ' . $title,
            'url'                 => $url,
            'startDate'           => $slot['all_day'] ? $slot['start']->format( 'Y-m-d' ) : $slot['start']->format( DateTimeInterface::ATOM ),
            'eventStatus'         => 'https://schema.org/EventScheduled',
            'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
            'location'            => stagekitwp_seo_audition_place( $venue_id ),
            'isAccessibleForFree' => true,
            'offers'              => array(
                '@type'         => 'Offer',
                'url'           => $url,
                'price'         => '0',
                'priceCurrency' => $currency,
                'availability'  => 'https://schema.org/InStock',
            ),
        );
        if ( $slot['end'] ) {
            $event['endDate'] = $slot['end']->format( DateTimeInterface::ATOM );
        }
        if ( $details ) {
            $event['description'] = $details;
        } else {
            $event['description'] = 'Open auditions for ' . $title . '.';
        }
        foreach ( array( 'organizer', 'image' ) as $shared ) {
            if ( isset( $show_node[ $shared ] ) ) {
                $event[ $shared ] = $show_node[ $shared ];
            }
        }
        $events[] = $event;
    }
    return $events;
}

/* ---------- Settings ---------- */

add_action( 'admin_menu', function () {
    add_options_page( 'Audition Schema', 'Audition Schema', 'manage_options', 'stagekitwp-seo-auditions', 'stagekitwp_seo_auditions_settings_page' );
} );

add_action( 'admin_init', function () {
    register_setting( 'stagekitwp_seo_auditions_group', 'stagekitwp_seo_auditions', array(
        'type'              => 'array',
        'sanitize_callback' => function ( $input ) {
            $input    = is_array( $input ) ? $input : array();
            $currency = strtoupper( sanitize_text_field( $input['currency'] ?? 'CAD' ) );
            return array(
                'enabled'  => empty( $input['enabled'] ) ? 0 : 1,
                'currency' => preg_match( '/^[A-Z]{3}$/', $currency ) ? $currency : 'CAD',
            );
        },
    ) );
} );

function stagekitwp_seo_auditions_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    $s = stagekitwp_seo_audition_settings();
    ?>
    <div class="wrap">
        <h1>Audition Schema</h1>
        <p>Adds a free <code>Event</code> to show pages for each upcoming audition slot (date, time, duration and venue set on the show). Show pages with only an audition date and no time get an all-day audition event.</p>
        <form method="post" action="options.php">
            <?php settings_fields( 'stagekitwp_seo_auditions_group' ); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">Enable</th>
                    <td><label><input type="checkbox" name="stagekitwp_seo_auditions[enabled]" value="1" <?php checked( ! empty( $s['enabled'] ) ); ?> /> Output audition Event schema</label></td>
                </tr>
                <tr>
                    <th scope="row"><label for="skwp-aud-cur">Currency</label></th>
                    <td><input id="skwp-aud-cur" type="text" maxlength="3" size="4" name="stagekitwp_seo_auditions[currency]" value="<?php echo esc_attr( $s['currency'] ); ?>" />
                    <p class="description">ISO 4217 code used for the $0 offer (for example CAD or USD).</p></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
