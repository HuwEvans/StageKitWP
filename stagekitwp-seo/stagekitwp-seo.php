<?php
/**
 * Plugin Name: StageKitWP Search Optimizer
 * Description: Generates SEO tags, OpenGraph, Twitter Cards, JSON-LD Schema, and Microformats2.
 * Version:     1.4.0
 * Author:      Huw Evans
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * 1. Add Meta Box
 */
add_action( 'add_meta_boxes', 'seo_schema_add_meta_box' );
function seo_schema_add_meta_box() {
    $screens = array( 'post', 'page' );
    foreach ( $screens as $screen ) {
        add_meta_box(
            'seo_schema_meta_box',
            'SEO & Schema Settings',
            'seo_schema_meta_box_html',
            $screen,
            'side',
            'high'
        );
    }
}

/**
 * Render Admin Meta Box Form Fields & JS Counter
 */
function seo_schema_meta_box_html( $post ) {
    wp_nonce_field( 'seo_schema_save_data', 'seo_schema_nonce' );

    $custom_title    = get_post_meta( $post->ID, '_seo_schema_title', true );
    $custom_desc     = get_post_meta( $post->ID, '_seo_schema_desc', true );
    $custom_keywords = get_post_meta( $post->ID, '_seo_schema_keywords', true );
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
        <label for="seo_schema_title"><strong>Meta / Social Title:</strong></label><br>
        <input type="text" id="seo_schema_title" name="seo_schema_title" value="<?php echo esc_attr( $custom_title ); ?>" style="width:100%;">
    </p>

    <p>
        <label for="seo_schema_desc"><strong>Meta / Social Description:</strong></label>
        <span id="seo_desc_counter" class="seo-status-badge">0 characters</span><br>
        <textarea id="seo_schema_desc" name="seo_schema_desc" rows="3" style="width:100%;"><?php echo esc_textarea( $custom_desc ); ?></textarea>
    </p>

    <p>
        <label for="seo_schema_keywords"><strong>Meta Keywords:</strong> <small>(Comma-separated)</small></label><br>
        <input type="text" id="seo_schema_keywords" name="seo_schema_keywords" value="<?php echo esc_attr( $custom_keywords ); ?>" placeholder="e.g. wordpress, seo, schema" style="width:100%;">
    </p>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const descInput = document.getElementById('seo_schema_desc');
            const counter = document.getElementById('seo_desc_counter');

            function updateCounter() {
                const len = descInput.value.length;
                counter.textContent = len + ' characters';

                if (len >= 160 && len <= 300) {
                    counter.classList.add('status-good');
                } else {
                    counter.classList.remove('status-good');
                }
            }

            if (descInput && counter) {
                descInput.addEventListener('input', updateCounter);
                updateCounter();
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
    if ( ! isset( $_POST['seo_schema_nonce'] ) || ! wp_verify_nonce( $_POST['seo_schema_nonce'], 'seo_schema_save_data' ) ) {
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
}

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

    if ( is_singular() && isset( $post->ID ) ) {
        $meta_title = get_post_meta( $post->ID, '_seo_schema_title', true );
        if ( empty( $meta_title ) ) {
            $meta_title = get_the_title( $post );
        }

        $meta_desc = get_post_meta( $post->ID, '_seo_schema_desc', true );
        if ( empty( $meta_desc ) ) {
            $meta_desc = wp_strip_all_tags( get_the_excerpt( $post ) );
        }

        $meta_keywords = get_post_meta( $post->ID, '_seo_schema_keywords', true );
        $permalink     = get_permalink( $post );
        $image_url     = get_the_post_thumbnail_url( $post, 'full' );
    } elseif ( is_front_page() || is_home() ) {
        $meta_title = get_bloginfo( 'name' ) . ' - ' . get_bloginfo( 'description' );
        $meta_desc  = get_bloginfo( 'description' );
    } else {
        return;
    }

    // Standard Meta Tags
    echo "\n<!-- Custom SEO & Social Tags -->\n";
    if ( ! empty( $meta_desc ) ) {
        echo '<meta name="description" content="' . esc_attr( $meta_desc ) . '" />' . "\n";
    }
    if ( ! empty( $meta_keywords ) ) {
        echo '<meta name="keywords" content="' . esc_attr( $meta_keywords ) . '" />' . "\n";
    }
    echo '<link rel="canonical" href="' . esc_url( $permalink ) . '" />' . "\n";

    // OpenGraph Tags
    echo '<meta property="og:title" content="' . esc_attr( $meta_title ) . '" />' . "\n";
    if ( ! empty( $meta_desc ) ) {
        echo '<meta property="og:description" content="' . esc_attr( $meta_desc ) . '" />' . "\n";
    }
    echo '<meta property="og:url" content="' . esc_url( $permalink ) . '" />' . "\n";
    echo '<meta property="og:type" content="' . ( is_singular() ? 'article' : 'website' ) . '" />' . "\n";
    if ( $image_url ) {
        echo '<meta property="og:image" content="' . esc_url( $image_url ) . '" />' . "\n";
    }

    // Twitter Card Tags
    $card_type = $image_url ? 'summary_large_image' : 'summary';
    echo '<meta name="twitter:card" content="' . esc_attr( $card_type ) . '" />' . "\n";
    echo '<meta name="twitter:title" content="' . esc_attr( $meta_title ) . '" />' . "\n";
    if ( ! empty( $meta_desc ) ) {
        echo '<meta name="twitter:description" content="' . esc_attr( $meta_desc ) . '" />' . "\n";
    }
    if ( $image_url ) {
        echo '<meta name="twitter:image" content="' . esc_url( $image_url ) . '" />' . "\n";
    }

    // Optional: Twitter handle fallback if configured on author profile
    if ( is_singular() && isset( $post->post_author ) ) {
        $twitter_handle = get_the_author_meta( 'twitter', $post->post_author );
        if ( ! empty( $twitter_handle ) ) {
            $handle = '@' . ltrim( $twitter_handle, '@' );
            echo '<meta name="twitter:creator" content="' . esc_attr( $handle ) . '" />' . "\n";
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

    if ( $image_url ) {
        $schema['image'] = esc_url( $image_url );
    }

    if ( ! empty( $meta_keywords ) ) {
        $schema['keywords'] = $meta_keywords;
    }

    echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . '</script>' . "\n";
    echo "<!-- / Custom SEO & Social Tags -->\n\n";
}

/**
 * 4. Microformats2 Integration
 */
add_filter( 'the_content', 'seo_schema_add_microformats' );
function seo_schema_add_microformats( $content ) {
    if ( ! is_singular( array( 'post', 'page' ) ) || ! in_the_loop() || ! is_main_query() ) {
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
    $microformats_header .= '<h1 class="p-name d-none" style="display:none;">' . esc_html( $title ) . '</h1>';
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