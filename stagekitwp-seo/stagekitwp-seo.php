<?php
/**
 * Plugin Name: StageKitWP Search Optimizer
 * Description: Generates SEO tags, OpenGraph, Twitter Cards, JSON-LD Schema, and Microformats2.
 * Version:     1.4.1
 * Author:      Huw Evans
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function stagekitwp_seo_supported_post_types() {
    return apply_filters(
        'stagekitwp_seo_meta_box_screens',
        array( 'post', 'page', 'show', 'season', 'venue', 'stagekitwp_event', 'award', 'cast', 'sponsor', 'contributor', 'board_member', 'advertiser', 'testimonial' )
    );
}

/**
 * 1. Add Meta Box
 */
add_action( 'add_meta_boxes', 'seo_schema_add_meta_box' );
function seo_schema_add_meta_box() {
    $screens = stagekitwp_seo_supported_post_types();
    foreach ( $screens as $screen ) {
        if ( ! post_type_exists( $screen ) ) {
            continue;
        }
        add_meta_box(
            'seo_schema_meta_box',
            'SEO & Schema Settings',
            'seo_schema_meta_box_html',
            $screen,
            'side',
            'high'
        );

        add_meta_box(
            'stagekitwp_seo_heading_audit',
            'SEO Heading Audit',
            'stagekitwp_seo_heading_audit_html',
            $screen,
            'side',
            'default'
        );

        add_meta_box(
            'stagekitwp_seo_image_audit',
            'SEO Image Audit',
            'stagekitwp_seo_image_audit_html',
            $screen,
            'side',
            'default'
        );
    }
}

function stagekitwp_seo_heading_audit_html( $post ) {
    preg_match_all( '/<h([1-6])\b[^>]*>/i', $post->post_content, $matches );
    $levels = array_map( 'intval', $matches[1] );
    $issues = array();

    if ( in_array( 1, $levels, true ) ) {
        $issues[] = 'The page template supplies the primary H1. Change H1 headings in the editor content to H2 or lower.';
    }

    $previous_level = 1;
    foreach ( $levels as $level ) {
        if ( $level > $previous_level + 1 ) {
            $issues[] = sprintf( 'Heading order skips from H%d to H%d.', $previous_level, $level );
            break;
        }
        $previous_level = $level;
    }
    ?>
    <p><?php echo esc_html( sprintf( '%d heading%s found in editor content.', count( $levels ), count( $levels ) === 1 ? '' : 's' ) ); ?></p>
    <?php if ( empty( $issues ) ) : ?>
        <p style="color:#008a20;"><strong>Heading structure looks good.</strong></p>
    <?php else : ?>
        <ul style="list-style:disc; margin-left:18px;">
            <?php foreach ( $issues as $issue ) : ?>
                <li><?php echo esc_html( $issue ); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <?php
}

function stagekitwp_seo_image_audit_html( $post ) {
    preg_match_all( '/<img\b[^>]*>/i', $post->post_content, $matches );
    $missing_alt = 0;
    foreach ( $matches[0] as $image ) {
        if ( ! preg_match( '/\balt\s*=/i', $image ) ) {
            $missing_alt++;
        }
    }

    $featured_image_id = get_post_thumbnail_id( $post->ID );
    $featured_alt = $featured_image_id ? get_post_meta( $featured_image_id, '_wp_attachment_image_alt', true ) : '';
    ?>
    <p><?php echo esc_html( sprintf( '%d content image%s found.', count( $matches[0] ), count( $matches[0] ) === 1 ? '' : 's' ) ); ?></p>
    <?php if ( $missing_alt ) : ?>
        <p style="color:#b32d2e;"><strong><?php echo esc_html( sprintf( '%d image%s need alt text.', $missing_alt, $missing_alt === 1 ? '' : 's' ) ); ?></strong></p>
    <?php else : ?>
        <p style="color:#008a20;"><strong>All content images have alt text.</strong></p>
    <?php endif; ?>
    <?php if ( $featured_image_id && empty( $featured_alt ) ) : ?>
        <p style="color:#b32d2e;">The featured image needs alt text in the Media Library.</p>
    <?php endif; ?>
    <?php
}

/**
 * Render Admin Meta Box Form Fields & JS Counter
 */
function seo_schema_meta_box_html( $post ) {
    wp_nonce_field( 'seo_schema_save_data', 'seo_schema_nonce' );

    $custom_title    = get_post_meta( $post->ID, '_seo_schema_title', true );
    $custom_desc     = get_post_meta( $post->ID, '_seo_schema_desc', true );
    $custom_keywords = get_post_meta( $post->ID, '_seo_schema_keywords', true );
    $noindex         = get_post_meta( $post->ID, '_seo_schema_robots_noindex', true );
    $nofollow        = get_post_meta( $post->ID, '_seo_schema_robots_nofollow', true );
    ?>
    <style>
        .seo-status-badge {
            display: inline-block;
            padding: 2px 8px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 4px;
            margin-left: 8px;
            background-color: #e0e0e0;
            color: #50575e;
            transition: all 0.2s ease;
        }
        .seo-status-badge.status-good {
            background-color: #008a20;
            color: #ffffff;
        }
    </style>

    <p>
        <label for="seo_schema_title"><strong>Meta / Social Title:</strong></label>
        <span id="seo_title_counter" class="seo-status-badge">0 characters (recommended: 50-60)</span><br>
        <input type="text" id="seo_schema_title" name="seo_schema_title" value="<?php echo esc_attr( $custom_title ); ?>" style="width:100%;">
        <small>Used for search and social sharing. It does not change the WordPress title or navigation label.</small>
    </p>

    <p>
        <label for="seo_schema_desc"><strong>Meta / Social Description:</strong></label>
        <span id="seo_desc_counter" class="seo-status-badge">0 characters (recommended: 150-160)</span><br>
        <textarea id="seo_schema_desc" name="seo_schema_desc" rows="3" style="width:100%;"><?php echo esc_textarea( $custom_desc ); ?></textarea>
    </p>

    <p>
        <label for="seo_schema_keywords"><strong>Meta Keywords:</strong> <small>(Comma-separated)</small></label><br>
        <input type="text" id="seo_schema_keywords" name="seo_schema_keywords" value="<?php echo esc_attr( $custom_keywords ); ?>" placeholder="e.g. wordpress, seo, schema" style="width:100%;">
    </p>

    <p>
        <strong>Search Visibility:</strong><br>
        <label><input type="checkbox" name="seo_schema_robots_noindex" value="1" <?php checked( $noindex, '1' ); ?>> Hide this page from search results</label><br>
        <label><input type="checkbox" name="seo_schema_robots_nofollow" value="1" <?php checked( $nofollow, '1' ); ?>> Ask search engines not to follow links</label>
    </p>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const titleInput = document.getElementById('seo_schema_title');
            const titleCounter = document.getElementById('seo_title_counter');
            const descInput = document.getElementById('seo_schema_desc');
            const descCounter = document.getElementById('seo_desc_counter');

            function updateTitleCounter() {
                const len = titleInput.value.length;
                titleCounter.textContent = len + ' characters (recommended: 50-60)';
                titleCounter.classList.toggle('status-good', len >= 50 && len <= 60);
            }

            function updateDescriptionCounter() {
                const len = descInput.value.length;
                descCounter.textContent = len + ' characters (recommended: 150-160)';
                descCounter.classList.toggle('status-good', len >= 150 && len <= 160);
            }

            if (titleInput && titleCounter) {
                titleInput.addEventListener('input', updateTitleCounter);
                updateTitleCounter();
            }
            if (descInput && descCounter) {
                descInput.addEventListener('input', updateDescriptionCounter);
                updateDescriptionCounter();
            }
        });
    </script>
    <?php
}

/**
 * 2. Save Meta Box Input Data
 */
add_action( 'save_post', 'seo_schema_save_postdata' );
function seo_schema_save_postdata( $post_id ) {
    $nonce = isset( $_POST['seo_schema_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['seo_schema_nonce'] ) ) : '';
    if ( ! $nonce || ! wp_verify_nonce( $nonce, 'seo_schema_save_data' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    if ( isset( $_POST['seo_schema_title'] ) ) {
        update_post_meta( $post_id, '_seo_schema_title', sanitize_text_field( $_POST['seo_schema_title'] ) );
    }
    if ( isset( $_POST['seo_schema_desc'] ) ) {
        update_post_meta( $post_id, '_seo_schema_desc', sanitize_textarea_field( $_POST['seo_schema_desc'] ) );
    }
    if ( isset( $_POST['seo_schema_keywords'] ) ) {
        update_post_meta( $post_id, '_seo_schema_keywords', sanitize_text_field( $_POST['seo_schema_keywords'] ) );
    }

    if ( isset( $_POST['seo_schema_robots_noindex'] ) ) {
        update_post_meta( $post_id, '_seo_schema_robots_noindex', '1' );
    } else {
        delete_post_meta( $post_id, '_seo_schema_robots_noindex' );
    }

    if ( isset( $_POST['seo_schema_robots_nofollow'] ) ) {
        update_post_meta( $post_id, '_seo_schema_robots_nofollow', '1' );
    } else {
        delete_post_meta( $post_id, '_seo_schema_robots_nofollow' );
    }
}

function stagekitwp_seo_description( $post ) {
    $description = get_post_meta( $post->ID, '_seo_schema_desc', true );
    if ( ! empty( $description ) ) {
        return $description;
    }

    $description = get_the_excerpt( $post );
    if ( empty( $description ) && 'show' === $post->post_type ) {
        $description = get_post_meta( $post->ID, '_stagekitwp_show_synopsis', true );
    }
    if ( empty( $description ) ) {
        $description = $post->post_content;
    }

    $description = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( strip_shortcodes( $description ) ) ) );
    return wp_html_excerpt( $description, 160, '' );
}

function stagekitwp_seo_landingpage_show( $post ) {
    if ( ! $post instanceof WP_Post || ! has_shortcode( $post->post_content, 'stagekitwp_landingpage' ) ) {
        return null;
    }

    $pattern = get_shortcode_regex( array( 'stagekitwp_landingpage' ) );
    if ( ! preg_match( '/'.$pattern.'/s', $post->post_content, $matches ) ) {
        return null;
    }

    $attributes = shortcode_parse_atts( $matches[3] );
    $show_reference = isset( $attributes['show_id'] ) ? $attributes['show_id'] : 'current';
    if ( 'current' === strtolower( (string) $show_reference ) ) {
        $show_id = function_exists( 'stagekitwp_get_current_show' ) ? stagekitwp_get_current_show() : 0;
    } else {
        $show_id = absint( $show_reference );
    }

    $show = $show_id ? get_post( $show_id ) : null;
    return $show && 'show' === $show->post_type ? $show : null;
}

function stagekitwp_seo_landingpage_image( $show ) {
    $image = get_post_meta( $show->ID, '_stagekitwp_show_sm_image', true );
    $image_id = absint( $image );
    $image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'full' ) : '';
    if ( ! $image_url && function_exists( 'stagekitwp_get_image_url' ) ) {
        $image_url = stagekitwp_get_image_url( $image );
    }
    if ( ! $image_url ) {
        $image_id = get_post_thumbnail_id( $show->ID );
        $image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'full' ) : '';
    }

    return array(
        'url' => $image_url,
        'alt' => $image_id ? get_post_meta( $image_id, '_wp_attachment_image_alt', true ) : '',
    );
}

function stagekitwp_seo_has_competing_plugin() {
    return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) || function_exists( 'aioseo' );
}

function stagekitwp_seo_robots( $robots ) {
    if ( ! is_singular() ) {
        return $robots;
    }

    $post_id = get_queried_object_id();
    if ( ! $post_id ) {
        return $robots;
    }

    if ( get_post_meta( $post_id, '_seo_schema_robots_noindex', true ) === '1' || ( 'stagekitwp_event' === get_post_type( $post_id ) && 'public' !== get_post_meta( $post_id, '_stagekitwp_members_event_visibility', true ) ) ) {
        $robots['noindex'] = true;
    }
    if ( get_post_meta( $post_id, '_seo_schema_robots_nofollow', true ) === '1' ) {
        $robots['nofollow'] = true;
    }

    return $robots;
}
add_filter( 'wp_robots', 'stagekitwp_seo_robots' );

function stagekitwp_seo_add_sitemap_post_types( $post_types ) {
    foreach ( array( 'show', 'season', 'venue', 'stagekitwp_event' ) as $post_type ) {
        $post_type_object = get_post_type_object( $post_type );
        if ( $post_type_object && $post_type_object->publicly_queryable && $post_type_object->show_in_rest ) {
            $post_types[ $post_type ] = $post_type_object;
        }
    }

    return $post_types;
}
add_filter( 'wp_sitemaps_post_types', 'stagekitwp_seo_add_sitemap_post_types' );

function stagekitwp_seo_add_llms_rewrite_rule() {
    add_rewrite_rule( '^llms\.txt$', 'index.php?stagekitwp_seo_llms=1', 'top' );
}
add_action( 'init', 'stagekitwp_seo_add_llms_rewrite_rule' );

function stagekitwp_seo_add_llms_query_var( $query_vars ) {
    $query_vars[] = 'stagekitwp_seo_llms';
    return $query_vars;
}
add_filter( 'query_vars', 'stagekitwp_seo_add_llms_query_var' );

function stagekitwp_seo_render_llms_file() {
    if ( ! get_query_var( 'stagekitwp_seo_llms' ) ) {
        return;
    }

    $post_types = array_values( array_filter( array( 'page', 'show', 'season', 'venue', 'stagekitwp_event' ), 'post_type_exists' ) );
    $posts = get_posts( array(
        'post_type'      => $post_types,
        'post_status'    => 'publish',
        'posts_per_page' => 100,
        'orderby'        => 'menu_order title',
        'order'          => 'ASC',
    ) );

    status_header( 200 );
    nocache_headers();
    header( 'Content-Type: text/plain; charset=utf-8' );
    echo '# ' . wp_strip_all_tags( get_bloginfo( 'name' ) ) . "\n\n";
    echo '> ' . wp_strip_all_tags( get_bloginfo( 'description' ) ) . "\n\n";
    echo '## Public Content' . "\n\n";
    foreach ( $posts as $post ) {
        if ( get_post_meta( $post->ID, '_seo_schema_robots_noindex', true ) === '1' || ( 'stagekitwp_event' === $post->post_type && 'public' !== get_post_meta( $post->ID, '_stagekitwp_members_event_visibility', true ) ) ) {
            continue;
        }
        echo '- [' . wp_strip_all_tags( get_the_title( $post ) ) . '](' . esc_url_raw( get_permalink( $post ) ) . ')';
        $description = stagekitwp_seo_description( $post );
        if ( $description ) {
            echo ': ' . $description;
        }
        echo "\n";
    }
    exit;
}
add_action( 'template_redirect', 'stagekitwp_seo_render_llms_file' );

function stagekitwp_seo_activate() {
    stagekitwp_seo_add_llms_rewrite_rule();
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'stagekitwp_seo_activate' );

function stagekitwp_seo_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'stagekitwp_seo_deactivate' );

/**
 * 3. Output Meta Tags, Twitter Cards & JSON-LD Schema in <head>
 */
add_action( 'wp_head', 'seo_schema_render_head_tags', 1 );
function seo_schema_render_head_tags() {
    global $post;

    $meta_title    = '';
    $meta_desc     = '';
    $meta_keywords = '';
    $permalink     = home_url( '/' );
    $image_url     = '';
    $image_alt     = '';

    if ( is_singular() && isset( $post->ID ) ) {
        $landingpage_show = stagekitwp_seo_landingpage_show( $post );
        $seo_source_post = $landingpage_show ? $landingpage_show : $post;
        $meta_title = get_post_meta( $post->ID, '_seo_schema_title', true );
        if ( empty( $meta_title ) ) {
            $meta_title = get_the_title( $seo_source_post );
        }

        $meta_desc = get_post_meta( $post->ID, '_seo_schema_desc', true );
        if ( empty( $meta_desc ) ) {
            $meta_desc = stagekitwp_seo_description( $seo_source_post );
        }

        $meta_keywords = get_post_meta( $post->ID, '_seo_schema_keywords', true );
        $permalink     = get_permalink( $post );
        $image_url     = get_the_post_thumbnail_url( $post, 'full' );
        $image_id      = get_post_thumbnail_id( $post );
        $image_alt     = $image_id ? get_post_meta( $image_id, '_wp_attachment_image_alt', true ) : '';
        if ( $landingpage_show ) {
            $landingpage_image = stagekitwp_seo_landingpage_image( $landingpage_show );
            if ( $landingpage_image['url'] ) {
                $image_url = $landingpage_image['url'];
                $image_alt = $landingpage_image['alt'];
            }
        }
    } elseif ( is_front_page() || is_home() ) {
        $meta_title = get_bloginfo( 'name' ) . ' - ' . get_bloginfo( 'description' );
        $meta_desc  = get_bloginfo( 'description' );
    } else {
        return;
    }

    if ( ! stagekitwp_seo_has_competing_plugin() ) {
        echo "\n<!-- StageKitWP SEO & Social Tags -->\n";
        if ( ! empty( $meta_desc ) ) {
            echo '<meta name="description" content="' . esc_attr( $meta_desc ) . '" />' . "\n";
        }
        if ( ! empty( $meta_keywords ) ) {
            echo '<meta name="keywords" content="' . esc_attr( $meta_keywords ) . '" />' . "\n";
        }

        echo '<meta property="og:title" content="' . esc_attr( $meta_title ) . '" />' . "\n";
        if ( ! empty( $meta_desc ) ) {
            echo '<meta property="og:description" content="' . esc_attr( $meta_desc ) . '" />' . "\n";
        }
        echo '<meta property="og:url" content="' . esc_url( $permalink ) . '" />' . "\n";
        echo '<meta property="og:type" content="' . ( is_singular() ? 'article' : 'website' ) . '" />' . "\n";
        if ( $image_url ) {
            echo '<meta property="og:image" content="' . esc_url( $image_url ) . '" />' . "\n";
            if ( $image_alt ) {
                echo '<meta property="og:image:alt" content="' . esc_attr( $image_alt ) . '" />' . "\n";
            }
        }

        $card_type = $image_url ? 'summary_large_image' : 'summary';
        echo '<meta name="twitter:card" content="' . esc_attr( $card_type ) . '" />' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr( $meta_title ) . '" />' . "\n";
        if ( ! empty( $meta_desc ) ) {
            echo '<meta name="twitter:description" content="' . esc_attr( $meta_desc ) . '" />' . "\n";
        }
        if ( $image_url ) {
            echo '<meta name="twitter:image" content="' . esc_url( $image_url ) . '" />' . "\n";
        }
    }

    // JSON-LD Schema Output
    $schema = array(
        '@context' => 'https://schema.org',
        '@type'    => is_singular() ? 'Article' : 'WebSite',
        '@id'      => esc_url( $permalink ) . ( is_singular() ? '#article' : '#website' ),
        'headline' => $meta_title,
        'url'      => esc_url( $permalink ),
    );

    if ( ! empty( $meta_desc ) ) {
        $schema['description'] = $meta_desc;
    }

    if ( is_singular() && isset( $post ) ) {
        $schema['datePublished']    = get_the_date( 'c', $post );
        $schema['dateModified']     = get_the_modified_date( 'c', $post );
        $schema['mainEntityOfPage'] = esc_url( $permalink );
        $schema['author']           = array(
            '@type' => 'Person',
            'name'  => get_the_author_meta( 'display_name', $post->post_author ),
        );
    }

    $schema_source_post = isset( $seo_source_post ) ? $seo_source_post : $post;
    $schema_source_post_type = $schema_source_post instanceof WP_Post ? $schema_source_post->post_type : '';

    if ( 'show' === $schema_source_post_type ) {
        $schema['@type'] = 'TheaterEvent';
        $genre = get_post_meta( $schema_source_post->ID, '_stagekitwp_show_genre', true );
        $director = get_post_meta( $schema_source_post->ID, '_stagekitwp_show_director', true );
        if ( $genre ) {
            $schema['genre'] = $genre;
        }
        if ( $director ) {
            $schema['director'] = array( '@type' => 'Person', 'name' => $director );
        }
        $tickets_url = get_post_meta( $schema_source_post->ID, '_stagekitwp_show_tickets_url', true );
        if ( $tickets_url ) {
            $schema['offers'] = array( '@type' => 'Offer', 'url' => esc_url_raw( $tickets_url ) );
        }
        preg_match_all( '/\b\d{4}-\d{2}-\d{2}\b/', get_post_meta( $schema_source_post->ID, '_stagekitwp_show_show_dates', true ), $show_dates );
        if ( ! empty( $show_dates[0] ) ) {
            $schema['startDate'] = $show_dates[0][0];
            if ( count( $show_dates[0] ) > 1 ) {
                $schema['endDate'] = end( $show_dates[0] );
            }
        }
        $venue_id = absint( get_post_meta( $schema_source_post->ID, '_stagekitwp_show_venue', true ) );
        if ( $venue_id ) {
            $schema['location'] = array( '@type' => 'PerformingArtsTheater', 'name' => get_the_title( $venue_id ), 'url' => get_permalink( $venue_id ) );
        }
    } elseif ( 'venue' === $schema_source_post_type ) {
        $schema['@type'] = 'PerformingArtsTheater';
        $telephone = get_post_meta( $schema_source_post->ID, '_stagekitwp_venue_phone', true );
        $address = get_post_meta( $schema_source_post->ID, '_stagekitwp_venue_address', true );
        $website = get_post_meta( $schema_source_post->ID, '_stagekitwp_venue_website', true );
        if ( $telephone ) {
            $schema['telephone'] = $telephone;
        }
        if ( $address ) {
            $schema['address'] = $address;
        }
        if ( $website ) {
            $schema['sameAs'] = esc_url_raw( $website );
        }
        $latitude = get_post_meta( $schema_source_post->ID, '_stagekitwp_venue_latitude', true );
        $longitude = get_post_meta( $schema_source_post->ID, '_stagekitwp_venue_longitude', true );
        if ( is_numeric( $latitude ) && is_numeric( $longitude ) ) {
            $schema['geo'] = array( '@type' => 'GeoCoordinates', 'latitude' => (float) $latitude, 'longitude' => (float) $longitude );
        }
    } elseif ( 'season' === $schema_source_post_type ) {
        $schema['@type'] = 'CreativeWorkSeason';
        $start_date = get_post_meta( $schema_source_post->ID, '_stagekitwp_season_start_date', true );
        $end_date = get_post_meta( $schema_source_post->ID, '_stagekitwp_season_end_date', true );
        if ( $start_date ) {
            $schema['startDate'] = $start_date;
        }
        if ( $end_date ) {
            $schema['endDate'] = $end_date;
        }
    } elseif ( 'stagekitwp_event' === $schema_source_post_type ) {
        $schema['@type'] = 'Event';
        $event_date = get_post_meta( $schema_source_post->ID, '_stagekitwp_members_event_date', true );
        $start_time = get_post_meta( $schema_source_post->ID, '_stagekitwp_members_start_time', true );
        $end_time = get_post_meta( $schema_source_post->ID, '_stagekitwp_members_end_time', true );
        if ( $event_date ) {
            $schema['startDate'] = $event_date . ( $start_time ? 'T' . $start_time : '' );
            if ( $end_time ) {
                $schema['endDate'] = $event_date . 'T' . $end_time;
            }
        }
    }

    if ( $image_url ) {
        $schema['image'] = array(
            '@type'       => 'ImageObject',
            'contentUrl'  => esc_url( $image_url ),
        );
        if ( $image_alt ) {
            $schema['image']['description'] = $image_alt;
        }
    }

    if ( ! empty( $meta_keywords ) ) {
        $schema['keywords'] = $meta_keywords;
    }

    if ( is_front_page() || is_home() ) {
        $schema['publisher'] = array(
            '@type' => 'PerformingGroup',
            'name'  => get_bloginfo( 'name' ),
            'url'   => home_url( '/' ),
        );
    }

    if ( stagekitwp_seo_has_competing_plugin() && ! in_array( $schema_source_post_type, array( 'show', 'season', 'venue', 'stagekitwp_event' ), true ) ) {
        return;
    }

    echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . '</script>' . "\n";
    echo "<!-- / Custom SEO & Social Tags -->\n\n";
}

/**
 * 4. Microformats2 Integration
 */
add_filter( 'the_content', 'seo_schema_add_microformats' );
function seo_schema_add_microformats( $content ) {
    if ( ! is_singular( stagekitwp_seo_supported_post_types() ) || ! in_the_loop() || ! is_main_query() ) {
        return $content;
    }

    global $post;

    $author_id   = $post->post_author;
    $author_name = get_the_author_meta( 'display_name', $author_id );
    $author_url  = get_author_posts_url( $author_id );
    $published   = get_the_date( 'c', $post );
    $updated     = get_the_modified_date( 'c', $post );
    $permalink   = get_permalink( $post );
    $title       = get_the_title( $post );

    $microformats_header  = '<div class="h-entry">';
    $microformats_header .= '<span class="p-name d-none" style="display:none;">' . esc_html( $title ) . '</span>';
    $microformats_header .= '<a class="u-url d-none" href="' . esc_url( $permalink ) . '" style="display:none;"></a>';
    
    $microformats_header .= '<div class="entry-meta-mf2" style="font-size:0.85em; margin-bottom:12px; color:#666;">';
    $microformats_header .= 'Published by <a class="p-author h-card" href="' . esc_url( $author_url ) . '">' . esc_html( $author_name ) . '</a> ';
    $microformats_header .= 'on <time class="dt-published" datetime="' . esc_attr( $published ) . '">' . esc_html( get_the_date( '', $post ) ) . '</time>';
    if ( $published !== $updated ) {
        $microformats_header .= ' (Updated <time class="dt-updated" datetime="' . esc_attr( $updated ) . '">' . esc_html( get_the_modified_date( '', $post ) ) . '</time>)';
    }
    $microformats_header .= '</div>';

    $wrapped_content = '<div class="e-content">' . $content . '</div>';
    $microformats_footer = '</div>';

    return $microformats_header . $wrapped_content . $microformats_footer;
}