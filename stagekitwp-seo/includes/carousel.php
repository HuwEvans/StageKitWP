<?php
/**
 * Performance carousel schema: a Schema.org ItemList of upcoming shows (URL-only items),
 * output on the front page, season pages and pages using [stagekitwp_season_shows].
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function stagekitwp_seo_carousel_defaults() {
    return array(
        'enabled'         => 1,
        'front_page'      => 1,
        'season_pages'    => 1,
        'shortcode_pages' => 1,
        'max_items'       => 10,
        'include_past'    => 0,
    );
}

function stagekitwp_seo_carousel_settings() {
    $saved = get_option( 'stagekitwp_seo_carousel', array() );
    return wp_parse_args( is_array( $saved ) ? $saved : array(), stagekitwp_seo_carousel_defaults() );
}

/**
 * A show qualifies unless every one of its performances is in the past.
 * Shows with no dates yet are kept, since they are announced but unscheduled.
 */
function stagekitwp_seo_carousel_show_is_upcoming( $show_id ) {
    $dates = stagekitwp_seo_llms_show_dates( $show_id );
    if ( ! $dates ) {
        return true;
    }
    $now = current_time( 'Y-m-d\TH:i' );
    return end( $dates ) >= $now;
}

/**
 * Shows for a list of seasons, in lineup order, filtered and capped.
 *
 * @param WP_Post[] $seasons Season posts.
 * @return WP_Post[]
 */
function stagekitwp_seo_carousel_shows( $seasons, $settings ) {
    $shows = array();
    foreach ( $seasons as $season ) {
        foreach ( stagekitwp_seo_llms_season_shows( $season->ID ) as $show ) {
            if ( empty( $settings['include_past'] ) && ! stagekitwp_seo_carousel_show_is_upcoming( $show->ID ) ) {
                continue;
            }
            $shows[ $show->ID ] = $show;
        }
    }
    return array_slice( array_values( $shows ), 0, max( 2, (int) $settings['max_items'] ) );
}

/**
 * Seasons referenced by the first [stagekitwp_season_shows] on a page.
 */
function stagekitwp_seo_carousel_shortcode_seasons( $post ) {
    if ( ! has_shortcode( $post->post_content, 'stagekitwp_season_shows' ) ) {
        return null;
    }
    $attributes = array();
    if ( preg_match( '/' . get_shortcode_regex( array( 'stagekitwp_season_shows' ) ) . '/s', $post->post_content, $m ) ) {
        $attributes = (array) shortcode_parse_atts( $m[3] );
    }
    $which = isset( $attributes['which'] ) && in_array( $attributes['which'], array( 'current', 'next', 'all' ), true ) ? $attributes['which'] : 'current';
    if ( function_exists( 'stagekitwp_ss_resolve_seasons' ) ) {
        return stagekitwp_ss_resolve_seasons( isset( $attributes['season_id'] ) ? absint( $attributes['season_id'] ) : 0, $which );
    }
    list( , $current ) = stagekitwp_seo_llms_seasons();
    $current_post = $current ? get_post( $current ) : null;
    return $current_post ? array( $current_post ) : array();
}

function stagekitwp_seo_carousel_build() {
    $settings = stagekitwp_seo_carousel_settings();
    if ( empty( $settings['enabled'] ) ) {
        return null;
    }

    $seasons = null;
    $post    = is_singular() ? get_queried_object() : null;

    if ( $post instanceof WP_Post ) {
        if ( get_post_meta( $post->ID, '_seo_schema_robots_noindex', true ) === '1' ) {
            return null;
        }
        if ( 'season' === $post->post_type ) {
            $seasons = empty( $settings['season_pages'] ) ? null : array( $post );
        } elseif ( ! empty( $settings['shortcode_pages'] ) ) {
            $seasons = stagekitwp_seo_carousel_shortcode_seasons( $post );
        }
    }

    if ( null === $seasons && ! empty( $settings['front_page'] ) && ( is_front_page() || is_home() ) ) {
        list( , $current ) = stagekitwp_seo_llms_seasons();
        $current_post      = $current ? get_post( $current ) : null;
        $seasons           = $current_post ? array( $current_post ) : array();
    }

    if ( empty( $seasons ) ) {
        return null;
    }

    $shows = stagekitwp_seo_carousel_shows( $seasons, $settings );
    // Google requires at least two items for a carousel.
    if ( count( $shows ) < 2 ) {
        return null;
    }

    $items    = array();
    $position = 1;
    foreach ( $shows as $show ) {
        $items[] = array(
            '@type'    => 'ListItem',
            'position' => $position++,
            'url'      => get_permalink( $show ),
        );
    }

    return array(
        '@context'        => 'https://schema.org',
        '@type'           => 'ItemList',
        '@id'             => trailingslashit( $post ? get_permalink( $post ) : home_url( '/' ) ) . '#performance-carousel',
        'itemListOrder'   => 'https://schema.org/ItemListOrderAscending',
        'numberOfItems'   => count( $items ),
        'itemListElement' => $items,
    );
}

add_action( 'wp_head', function () {
    $carousel = stagekitwp_seo_carousel_build();
    if ( $carousel ) {
        echo '<script type="application/ld+json">' . wp_json_encode( $carousel, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . '</script>' . "\n";
    }
}, 2 );

/* ---------- Settings ---------- */

add_action( 'admin_menu', function () {
    add_options_page( 'Performance Carousel Schema', 'Carousel Schema', 'manage_options', 'stagekitwp-seo-carousel', 'stagekitwp_seo_carousel_settings_page' );
} );

add_action( 'admin_init', function () {
    register_setting( 'stagekitwp_seo_carousel_group', 'stagekitwp_seo_carousel', array(
        'type'              => 'array',
        'sanitize_callback' => function ( $input ) {
            $input = is_array( $input ) ? $input : array();
            $clean = array();
            foreach ( array( 'enabled', 'front_page', 'season_pages', 'shortcode_pages', 'include_past' ) as $flag ) {
                $clean[ $flag ] = empty( $input[ $flag ] ) ? 0 : 1;
            }
            $clean['max_items'] = min( 50, max( 2, absint( $input['max_items'] ?? 10 ) ) );
            return $clean;
        },
    ) );
} );

function stagekitwp_seo_carousel_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    $s     = stagekitwp_seo_carousel_settings();
    $name  = 'stagekitwp_seo_carousel';
    $check = function ( $key, $label, $help = '' ) use ( $s, $name ) {
        printf(
            '<tr><th scope="row">%1$s</th><td><label><input type="checkbox" name="%2$s[%3$s]" value="1" %4$s /> %5$s</label></td></tr>',
            esc_html( $label ),
            esc_attr( $name ),
            esc_attr( $key ),
            checked( ! empty( $s[ $key ] ), true, false ),
            esc_html( $help )
        );
    };
    ?>
    <div class="wrap">
        <h1>Performance Carousel Schema</h1>
        <p>Adds an <code>ItemList</code> of upcoming shows (URL only) so search engines can show a carousel. Each show page already carries its own Event markup.</p>
        <form method="post" action="options.php">
            <?php settings_fields( 'stagekitwp_seo_carousel_group' ); ?>
            <table class="form-table" role="presentation">
                <?php
                $check( 'enabled', 'Enable', 'Output carousel schema' );
                $check( 'front_page', 'Front page', 'Current season shows' );
                $check( 'season_pages', 'Season pages', 'Shows in that season' );
                $check( 'shortcode_pages', 'Shortcode pages', 'Pages using [stagekitwp_season_shows]' );
                $check( 'include_past', 'Include past shows', 'Off by default; search engines ignore expired events' );
                ?>
                <tr>
                    <th scope="row"><label for="skwp-carousel-max">Maximum items</label></th>
                    <td><input id="skwp-carousel-max" type="number" min="2" max="50" name="<?php echo esc_attr( $name ); ?>[max_items]" value="<?php echo esc_attr( $s['max_items'] ); ?>" />
                    <p class="description">Carousels need at least two shows; nothing is output with fewer.</p></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
