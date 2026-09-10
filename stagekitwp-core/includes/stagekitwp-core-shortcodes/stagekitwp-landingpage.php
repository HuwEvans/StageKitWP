<?php
/**
 * Landing Page Shortcode
 * Displays detailed information about a specific show
 * 
 * SHORTCODE NAME: stagekitwp_landingpage
 * PURPOSE: Displays comprehensive information about a specific theatre show
 * 
 * USAGE:
 * [stagekitwp_landingpage show_id="current"]
 * [stagekitwp_landingpage show_id="123"]
 * [stagekitwp_landingpage show_id="123" field_list="show_name,show_image,author,director,synopsis,castwithbio,venue"]
 * 
 * PARAMETERS:
 * - show_id (required): Show ID or "current" to display the current show (default: "current")
 * - field_list (optional): Comma-separated list of fields in display order (default: all fields)
 * - castcols (optional): Number of columns for castwithbio field, 1-6 (default: 3)
 * - urlbutton (optional): Display ticket URL as button "true" or "false" (default: "false")
 * - program_button (optional): Display program PDF as a button in card/hero layouts "true" or "false" (default: "false")
 * - buttonformat (optional): Button style format (default, modern, minimal, outline, gradient, prominent, success, ghost, glass)
 * - hard_breaks (optional): "true" or "false" - not recommended to change (default: "true")
 * 
 * AVAILABLE FIELDS:
 * - show_name: Show title
 * - show_image: Show poster/main image
 * - author: Primary author/playwright
 * - sub_authors: Additional authors/co-writers
 * - director: Director name
 * - associate_director: Associate/Assistant director name
 * - producer: Producer name
 * - stage_manager: Stage manager name
 * - synopsis: Show description/synopsis
 * - show_dates: Performance dates and times
 * - ticket_url: Link to ticket purchase page
 * - program_pdf: Link to downloadable program PDF
 * - cast: Cast member list (simple format: Character Name - Actor Name)
 * - castwithbio: Cast with photos in responsive grid (respects castcols parameter, 1-6 columns)
 * - venue: Venue name, address, phone, and website
 * 
 * FIELD FORMATTING:
 * - Each field displays on a separate line
 * - All fields inherit alignment and text styling from parent page/block context
 * - No plugin-specific styling applied (colors, fonts, sizes inherit from page)
 * - All text properties inherited: font-family, font-size, color, font-weight, line-height, letter-spacing, text-transform
 * - Images automatically center when parent is centered
 * - Images display at full width with responsive sizing
 * 
 * DEFAULT OUTPUT (when all fields displayed):
 * [Show name]
 * [Show image]
 * Written by: [author]
 * Co-writers: [sub_authors]
 * Directed by: [director]
 * Assistant Director: [associate_director]
 * Produced by: [producer]1257
 * Stage Managed by: [stage_manager]
 * ----
 * [Heading: About the Show]
 * [synopsis]
 * [Get Tickets button]
 * ----
 * [Heading: Meet the Cast]
 * [cast in responsive grid - 3 columns default]
 *   Character Name
 *   Played by: Actor Name
 *   Actor bio
 * ----
 * [Heading: Show Times and Ticket Info]
 * [show_dates]
 * [Get Tickets button]
 * ----
 * [Heading: Theatre Info]
 * [venue name, address, phone, map link]
 * ----
 * 
 * ENTIRE OUTPUT IS CENTERED
 * 
 * CAST WITH BIO DETAILS (castwithbio field):
 * - Displays in responsive grid layout
 * - castcols parameter controls column count (1-6, default 3)
 * - Desktop (>1024px): Full castcols columns
 * - Tablet (1024px-768px): min(castcols, 2) columns
 * - Mobile (<768px): min(castcols, 1) column (single column minimum)
 * - Images: Responsive sizing based on column count, maintain 3/4 aspect ratio
 * - Each cast member shows: Photo | Character Name | "Played by: Actor Name" | Bio
 * 
 * ALIGNMENT INHERITANCE:
 * - Gutenberg block alignment (center/left/right/justify)
 * - Beaver Builder column alignment
 * - Theme-specific alignment classes
 * - CSS uses sibling selectors to detect and apply alignment
 * - All nested elements fully inherit parent alignment and text properties
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get the current show (today's date falls within show dates)
 */
function stagekitwp_get_current_show() {
    $today_ts = strtotime(date('Y-m-d'));
    $shows = get_posts([
        'post_type' => 'show',
        'posts_per_page' => -1
    ]);

    foreach ($shows as $show) {
        $season_id = get_post_meta($show->ID, '_stagekitwp_show_season', true);
        if (!$season_id) continue;

        $season = get_post($season_id);
        if (!$season) continue;

        $start_raw = get_post_meta($season_id, '_stagekitwp_season_start_date', true);
        $end_raw = get_post_meta($season_id, '_stagekitwp_season_end_date', true);

        $start_ts = $start_raw ? strtotime($start_raw) : 0;
        $end_ts = $end_raw ? strtotime($end_raw) : 0;

        if (!empty($start_ts) && !empty($end_ts)) {
            if ($today_ts > $start_ts && $today_ts < $end_ts) {
                return $show->ID;
            }
        }
    }

    return null;
}

/**
 * Render field output based on field name
 */
function stagekitwp_render_landingpage_field($show_id, $field_name, $hard_breaks = true, $atts = array()) {
    $output = '';
    
    switch ($field_name) {
        case 'show_name':
            $title = get_the_title($show_id);
            if ($title) {
                $output .= esc_html($title);
            }
            break;

        case 'show_image':
            // Images always output as HTML regardless of hard_breaks setting
            $image_value = get_post_meta($show_id, '_stagekitwp_show_sm_image', true);
            if ($image_value) {
                $image_url = stagekitwp_get_image_url($image_value);
                if ($image_url) {
                    $output .= '<img src="' . esc_url($image_url) . '" alt="' . esc_attr(get_the_title($show_id)) . '" />';
                }
            }
            break;

        case 'author':
            $value = get_post_meta($show_id, '_stagekitwp_show_author', true);
            if ($value) {
                $output .= esc_html($value);
            }
            break;

        case 'sub_authors':
            $value = get_post_meta($show_id, '_stagekitwp_show_sub_authors', true);
            if ($value) {
                $output .= esc_html($value);
            }
            break;

        case 'director':
            $value = get_post_meta($show_id, '_stagekitwp_show_director', true);
            if ($value) {
                $output .= esc_html($value);
            }
            break;

        case 'associate_director':
            $value = get_post_meta($show_id, '_stagekitwp_show_associate_director', true);
            if ($value) {
                $output .= esc_html($value);
            }
            break;

        case 'producer':
            $value = get_post_meta($show_id, '_stagekitwp_show_producer', true);
            if ($value) {
                $output .= esc_html($value);
            }
            break;

        case 'stage_manager':
            $value = get_post_meta($show_id, '_stagekitwp_show_stage_manager', true);
            if ($value) {
                $output .= esc_html($value);
            }
            break;

        case 'synopsis':
            $value = get_post_meta($show_id, '_stagekitwp_show_synopsis', true);
            if ($value) {
                if ($hard_breaks) {
                    $output .= wp_kses_post($value);
                } else {
                    // Strip tags for plain text when hard_breaks is false
                    $output .= esc_html(wp_strip_all_tags($value));
                }
            }
            break;

        case 'show_dates':
            $value = get_post_meta($show_id, '_stagekitwp_show_show_dates', true);
            if ($value) {
                if ($hard_breaks) {
                    $output .= wp_kses_post($value);
                } else {
                    // Strip tags for plain text when hard_breaks is false
                    $output .= esc_html(wp_strip_all_tags($value));
                }
            }
            break;

        case 'ticket_url':
            $url = get_post_meta($show_id, '_stagekitwp_show_tickets_url', true);
            if ($url) {
                $use_button = strtolower($atts['urlbutton']) === 'true' || $atts['urlbutton'] === '1';
                if ($use_button && $hard_breaks) {
                    // Display as a button
                    $buttonformat = sanitize_key( $atts['buttonformat'] ?? 'default' );
                    $button_class = 'stagekitwp-url-button stagekitwp-button-' . esc_attr($buttonformat);
                    $output .= '<a href="' . esc_url($url) . '" class="' . $button_class . '" target="_blank">Get Tickets</a>';
                } else {
                    // Display as regular link
                    if ($hard_breaks) {
                        $output .= '<a href="' . esc_url($url) . '" target="_blank">' . esc_html($url) . '</a>';
                    } else {
                        // Just the URL text when hard_breaks is false
                        $output .= esc_html($url);
                    }
                }
            }
            break;

        case 'program_pdf':
            $program_id  = get_post_meta( $show_id, '_stagekitwp_show_program', true );
            $program_url = get_post_meta( $show_id, '_stagekitwp_show_program_url', true );
            if ( ! $program_url && $program_id ) {
                $program_url = wp_get_attachment_url( $program_id );
            }
            if ( $program_url ) {
                $output .= stagekitwp_lp_program_html( $program_url );
            }
            break;

        case 'cast':
            // Get all cast members for this show
            $cast_args = [
                'post_type' => 'cast',
                'posts_per_page' => -1,
                'meta_query' => [
                    [
                        'key' => '_stagekitwp_cast_show',
                        'value' => $show_id,
                        'compare' => '='
                    ]
                ]
            ];
            $cast_members = get_posts($cast_args);

            if ($cast_members) {
                if ($hard_breaks) {
                    // Formatted HTML output with list
                    $output .= '<ul class="stagekitwp-landingpage-cast-list">';
                    foreach ($cast_members as $cast_member) {
                        $output .= '<li class="stagekitwp-landingpage-cast-item">';
                        
                        $character = get_post_meta($cast_member->ID, '_stagekitwp_cast_character_name', true);
                        $actor = get_post_meta($cast_member->ID, '_stagekitwp_cast_actor_name', true);
                        $bio = get_post_meta($cast_member->ID, '_stagekitwp_cast_bio', true);
                        if ($character) {
                            $output .= '<strong>' . esc_html($character) . '</strong>';
                            if ($actor) {
                                $output .= ' — ' . esc_html($actor);
                            }
                        } elseif ($actor) {
                            $output .= esc_html($actor);
                        }
                        
                        if ($bio) {
                            $output .= '<br>' . wp_kses_post($bio);
                        }
                        
                        $output .= '</li>';
                    }
                    $output .= '</ul>';
                } else {
                    // Plain text output when hard_breaks is false
                    $cast_texts = [];
                    foreach ($cast_members as $cast_member) {
                        $character = get_post_meta($cast_member->ID, '_stagekitwp_cast_character_name', true);
                        $actor = get_post_meta($cast_member->ID, '_stagekitwp_cast_actor_name', true);
                        
                        if ($character && $actor) {
                            $cast_texts[] = esc_html($character) . ' - ' . esc_html($actor);
                        } elseif ($character) {
                            $cast_texts[] = esc_html($character);
                        } elseif ($actor) {
                            $cast_texts[] = esc_html($actor);
                        }
                    }
                    $output .= implode(', ', $cast_texts);
                }
            }
            break;

        case 'castwithbio':
            // Get all cast members for this show with bio in responsive columns
            // castcols parameter controls the number of columns (1-6, default 3)
            $cast_args = [
                'post_type' => 'cast',
                'posts_per_page' => -1,
                'meta_query' => [
                    [
                        'key' => '_stagekitwp_cast_show',
                        'value' => $show_id,
                        'compare' => '='
                    ]
                ]
            ];
            $cast_members = get_posts($cast_args);

            if ($cast_members) {
                // Validate and constrain castcols parameter
                $castcols = intval($atts['castcols']);
                if ($castcols < 1) $castcols = 3;  // Default to 3 columns
                if ($castcols > 6) $castcols = 6;  // Max 6 columns
                
                // Pass castcols as CSS custom property: respects castcols at desktop, 
                // constrains to 2 columns on tablet (max-width: 1024px), 
                // and 1 column on mobile (max-width: 480px)
                $output .= '<div class="stagekitwp-landingpage-castwithbio" style="--cast-cols: ' . intval($castcols) . '">';
                
                foreach ($cast_members as $cast_member) {
                    $character = get_post_meta($cast_member->ID, '_stagekitwp_cast_character_name', true);
                    $actor = get_post_meta($cast_member->ID, '_stagekitwp_cast_actor_name', true);
                    $picture = get_post_meta($cast_member->ID, '_stagekitwp_cast_picture', true);
                    $bio = get_post_meta($cast_member->ID, '_stagekitwp_cast_bio', true);

                    $output .= '<div class="stagekitwp-cast-column">';
                    
                    // Image
                    if ($picture) {
                        $picture_url = stagekitwp_get_image_url($picture);
                        if ($picture_url) {
                            $output .= '<img src="' . esc_url($picture_url) . '" alt="' . esc_attr($actor ?: $character) . '" class="stagekitwp-cast-image">';
                        }
                    }
                    
                    // Character Name
                    if ($character) {
                        $output .= '<h4 class="stagekitwp-cast-character">' . esc_html($character) . '</h4>';
                    }
                    
                    // Actor Name
                    if ($actor) {
                        $output .= '<p class="stagekitwp-cast-actor">Played by ' . esc_html($actor) . '</p>';
                    }
                    
                    // Bio
                    if ($bio) {
                        $output .= '<p class="stagekitwp-cast-bio">' . wp_kses_post($bio) . '</p>';
                    }
                    
                    $output .= '</div>';
                }
                
                $output .= '</div>';
            }
            break;

        case 'venue':
            $venue_id = get_post_meta($show_id, '_stagekitwp_show_venue', true);
            if ($venue_id) {
                $venue = get_post($venue_id);
                if ($venue) {
                    if ($hard_breaks) {
                        // Formatted HTML output with div
                        $output .= '<div class="stagekitwp-landingpage-venue">';
                        $output .= '<strong>' . esc_html($venue->post_title) . '</strong>';
                        
                        $address = get_post_meta($venue_id, '_stagekitwp_venue_address', true);
                        if ($address) {
                            $output .= '<br>' . wp_kses_post($address);
                        }
                        
                        $phone = get_post_meta($venue_id, '_stagekitwp_venue_phone', true);
                        if ($phone) {
                            $output .= '<br>' . esc_html($phone);
                        }
                        
                        $website = get_post_meta($venue_id, '_stagekitwp_venue_website', true);
                        if ($website) {
                            $output .= '<br><a href="' . esc_url($website) . '" target="_blank">' . esc_html($website) . '</a>';
                        }
                        
                        $output .= '</div>';
                    } else {
                        // Plain text output when hard_breaks is false
                        $venue_parts = [esc_html($venue->post_title)];
                        
                        $address = get_post_meta($venue_id, '_stagekitwp_venue_address', true);
                        if ($address) {
                            $venue_parts[] = wp_strip_all_tags($address);
                        }
                        
                        $phone = get_post_meta($venue_id, '_stagekitwp_venue_phone', true);
                        if ($phone) {
                            $venue_parts[] = esc_html($phone);
                        }
                        
                        $website = get_post_meta($venue_id, '_stagekitwp_venue_website', true);
                        if ($website) {
                            $venue_parts[] = esc_html($website);
                        }
                        
                        $output .= implode(' ', $venue_parts);
                    }
                }
            }
            break;
    }

    return $output;
}

/**
 * Shortcode handler
 * 
 * ALIGNMENT AND TEXT SETTINGS:
 * All fields in this shortcode automatically inherit alignment and text styling from the surrounding
 * page context. This includes:
 * 
 * - Gutenberg block alignment (has-text-align-center, has-text-align-left, has-text-align-right)
 * - Beaver Builder alignment settings (fl-col text alignment classes)
 * - Theme-specific alignment classes (centered, center, etc.)
 * - All inherited text properties: font-family, font-size, color, font-weight, line-height, etc.
 * 
 * The CSS in assets/css/shortcodes.css uses sibling selectors to detect preceding block alignment
 * and applies matching alignment to all shortcode fields. Images automatically center within centered
 * content. Nested elements (cast lists, venue info, etc.) fully inherit all text styling.
 * 
 * CASTCOLS PARAMETER (castwithbio field):
 * The castcols parameter controls the number of columns in the cast with bio grid (1-6, default 3).
 * - Desktop (>1024px): Displays castcols columns as specified
 * - Tablet (1024px-768px): Displays min(castcols, 2) columns for better readability
 * - Mobile (<768px): Displays min(castcols, 1) column (single column) for mobile-friendly layout
 * 
 * CSS custom property --cast-cols is set inline and constrained by responsive media queries
 * to ensure optimal display at all screen sizes while respecting the user's castcols preference
 * where practical.
 */
/**
 * Emit the landing-page CSS exactly once per page load.
 * Uses CSS custom properties so Display Options + dark mode can override everything.
 */

/**
 * Curated font list for [stagekitwp_landingpage].
 * Keys are URL-safe slugs used in meta/options/shortcode attrs.
 * Values are arrays: [ 'label' => string, 'stack' => string CSS font-family ]
 */
function stagekitwp_lp_font_list() {
    return [
        // ─ System / web-safe ───────────────────────────────────────────────────
        'inherit'           => [ 'label' => '(Inherit from theme)',     'stack' => 'inherit' ],
        'georgia'           => [ 'label' => 'Georgia',                  'stack' => 'Georgia, "Times New Roman", serif' ],
        'times'             => [ 'label' => 'Times New Roman',          'stack' => '"Times New Roman", Times, serif' ],
        'palatino'          => [ 'label' => 'Palatino',                 'stack' => '"Palatino Linotype", Palatino, serif' ],
        'arial'             => [ 'label' => 'Arial',                    'stack' => 'Arial, Helvetica, sans-serif' ],
        'verdana'           => [ 'label' => 'Verdana',                  'stack' => 'Verdana, Geneva, sans-serif' ],
        'trebuchet'         => [ 'label' => 'Trebuchet MS',               'stack' => '"Trebuchet MS", Arial, sans-serif' ],
        'courier'           => [ 'label' => 'Courier New (monospace)',     'stack' => '"Courier New", Courier, monospace' ],
        'comic-sans'        => [ 'label' => 'Comic Sans MS',              'stack' => '"Comic Sans MS", "Comic Sans", cursive' ],
        'impact'            => [ 'label' => 'Impact',                     'stack' => 'Impact, "Arial Narrow", sans-serif' ],
        // ─ Google – Serif ────────────────────────────────────────────────
        'playfair'          => [ 'label' => 'Playfair Display • Google', 'stack' => '"Playfair Display", Georgia, serif' ],
        'eb-garamond'       => [ 'label' => 'EB Garamond • Google',      'stack' => '"EB Garamond", Georgia, serif' ],
        'cormorant'         => [ 'label' => 'Cormorant Garamond • Google','stack' => '"Cormorant Garamond", Georgia, serif' ],
        'libre-baskerville' => [ 'label' => 'Libre Baskerville • Google','stack' => '"Libre Baskerville", Georgia, serif' ],
        'lora'              => [ 'label' => 'Lora • Google',             'stack' => 'Lora, Georgia, serif' ],
        'merriweather'      => [ 'label' => 'Merriweather • Google',      'stack' => 'Merriweather, Georgia, serif' ],
        // ─ Google – Sans-serif ───────────────────────────────────────────
        'montserrat'        => [ 'label' => 'Montserrat • Google',        'stack' => 'Montserrat, Arial, sans-serif' ],
        'raleway'           => [ 'label' => 'Raleway • Google',           'stack' => 'Raleway, Arial, sans-serif' ],
        'josefin-sans'      => [ 'label' => 'Josefin Sans • Google',      'stack' => '"Josefin Sans", Arial, sans-serif' ],
        'libre-franklin'    => [ 'label' => 'Libre Franklin • Google',    'stack' => '"Libre Franklin", Arial, sans-serif' ],
        'nunito'            => [ 'label' => 'Nunito • Google',            'stack' => 'Nunito, Arial, sans-serif' ],
        'poppins'           => [ 'label' => 'Poppins • Google',           'stack' => 'Poppins, Arial, sans-serif' ],
        // ─ Google – Display / Slab ──────────────────────────────────────
        'oswald'            => [ 'label' => 'Oswald • Google',            'stack' => 'Oswald, Arial, sans-serif' ],
        'cinzel'            => [ 'label' => 'Cinzel • Google',            'stack' => 'Cinzel, Georgia, serif' ],
        'alfa-slab-one'     => [ 'label' => 'Alfa Slab One • Google',     'stack' => '"Alfa Slab One", Georgia, serif' ],
        'dm-serif'          => [ 'label' => 'DM Serif Display • Google',  'stack' => '"DM Serif Display", Georgia, serif' ],
        'spectral'          => [ 'label' => 'Spectral • Google',          'stack' => 'Spectral, Georgia, serif' ],
    ];
}

/** Resolve a font slug to its CSS stack. Returns '' if slug is unknown or 'inherit'.
 *  Also accepts wplf-* slugs (WP Font Library) once stagekitwp_lp_wp_library_fonts() is loaded.
 *  Falls back to stagekitwp_lp_full_font_stack() which merges static + WP Library lists.
 */
function stagekitwp_lp_font_stack( $slug ) {
    if ( empty( $slug ) ) { return ''; }
    $list = stagekitwp_lp_font_list();
    if ( isset( $list[ $slug ] ) ) {
        $stack = $list[ $slug ]['stack'];
        return ( $stack === 'inherit' ) ? '' : $stack;
    }
    // WP Library slug (wplf-*) — resolved after stagekitwp_lp_wp_library_fonts() is defined
    if ( strpos( $slug, 'wplf-' ) === 0 && function_exists( 'stagekitwp_lp_full_font_stack' ) ) {
        return stagekitwp_lp_full_font_stack( $slug );
    }
    return '';
}

/**
 * Map from font slug to Google Fonts API query string.
 * Only slugs here will trigger a Google Fonts enqueue.
 */
function stagekitwp_lp_google_fonts_map() {
    return [
        'playfair'          => 'Playfair+Display:ital,wght@0,400;0,700;1,400',
        'eb-garamond'       => 'EB+Garamond:ital,wght@0,400;0,700;1,400',
        'cormorant'         => 'Cormorant+Garamond:ital,wght@0,400;0,700;1,400',
        'libre-baskerville' => 'Libre+Baskerville:ital,wght@0,400;0,700;1,400',
        'lora'              => 'Lora:ital,wght@0,400;0,700;1,400',
        'montserrat'        => 'Montserrat:wght@400;600;700;900',
        'raleway'           => 'Raleway:wght@400;600;700;900',
        'josefin-sans'      => 'Josefin+Sans:wght@400;600;700',
        'libre-franklin'    => 'Libre+Franklin:wght@400;600;700',
        'oswald'            => 'Oswald:wght@400;600;700',
        'cinzel'            => 'Cinzel:wght@400;700',
        'alfa-slab-one'     => 'Alfa+Slab+One',
        'merriweather'      => 'Merriweather:ital,wght@0,400;0,700;1,400',
        'nunito'            => 'Nunito:wght@400;600;700',
        'poppins'           => 'Poppins:wght@400;600;700',
        'dm-serif'          => 'DM+Serif+Display:ital@0;1',
        'spectral'          => 'Spectral:ital,wght@0,400;0,700;1,400',
    ];
}

/**
 * Enqueue Google Font for a given font slug (if it maps to a Google Font).
 * Safe to call multiple times — uses a static guard.
 */
function stagekitwp_lp_maybe_enqueue_google_font( $slug ) {
    static $enqueued = [];
    if ( empty( $slug ) || $slug === 'inherit' ) { return; }
    if ( isset( $enqueued[ $slug ] ) ) { return; }
    $map = stagekitwp_lp_google_fonts_map();
    if ( ! isset( $map[ $slug ] ) ) { return; }
    $enqueued[ $slug ] = true;
    $url = 'https://fonts.googleapis.com/css2?family=' . $map[ $slug ] . '&display=swap';
    wp_enqueue_style( 'stagekitwp-gf-' . $slug, $url, [], null );
}

/**
 * Return fonts registered in the WordPress Font Library (WP 6.5+ wp_font_family CPT).
 * Returns an array in the same format as stagekitwp_lp_font_list(): slug => [ label, stack ].
 * Slugs are prefixed with 'wplf-' to avoid collisions with the curated list.
 * The font-face CSS is already emitted by WP core when the font is active.
 */
function stagekitwp_lp_wp_library_fonts() {
    static $cache = null;
    if ( $cache !== null ) { return $cache; }
    $cache = [];
    if ( ! post_type_exists( 'wp_font_family' ) ) { return $cache; }
    $families = get_posts( [
        'post_type'      => 'wp_font_family',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ] );
    foreach ( $families as $family ) {
        $name  = $family->post_title; // e.g. "ADLaM Display"
        $slug  = 'wplf-' . sanitize_title( $name ); // e.g. 'wplf-adlam-display'
        $stack = '"' . $name . '", sans-serif';
        $cache[ $slug ] = [
            'label'  => $name . ' • WP Library',
            'stack'  => $stack,
            'wp_name'=> $name, // raw name for font-family CSS
        ];
    }
    return $cache;
}

/**
 * Merged font list: curated static list + WP Font Library fonts.
 * WP Library fonts appear at the top (after inherit) so they’re easy to find.
 */
function stagekitwp_lp_full_font_list() {
    $static  = stagekitwp_lp_font_list();
    $library = stagekitwp_lp_wp_library_fonts();
    if ( empty( $library ) ) { return $static; }
    // Splice library fonts in after 'inherit'
    $result = [];
    foreach ( $static as $k => $v ) {
        $result[ $k ] = $v;
        if ( $k === 'inherit' ) {
            foreach ( $library as $lk => $lv ) {
                $result[ $lk ] = $lv;
            }
        }
    }
    return $result;
}

/**
 * Resolve a slug from the full (merged) list to its CSS stack.
 * For WP Library fonts (wplf- prefix) the stack is built from the registered name.
 * Returns '' for unknown or 'inherit'.
 */
function stagekitwp_lp_full_font_stack( $slug ) {
    if ( empty( $slug ) ) { return ''; }
    $list = stagekitwp_lp_full_font_list();
    if ( ! isset( $list[ $slug ] ) ) { return ''; }
    $stack = $list[ $slug ]['stack'];
    return ( $stack === 'inherit' ) ? '' : $stack;
}

function stagekitwp_landingpage_styles() {
    $css = '
.stagekitwp-landingpage-wrapper {
    --stagekitwp-lp-bg:       #ffffff;
    --stagekitwp-lp-text:     #333333;
    --stagekitwp-lp-heading:  #111111;
    --stagekitwp-lp-accent:   #8b0000;
    --stagekitwp-lp-border:   #e0e0e0;
    --stagekitwp-lp-meta-bg:  #f7f7f7;
    --stagekitwp-lp-btn-bg:   #8b0000;
    --stagekitwp-lp-btn-text: #ffffff;
    --stagekitwp-lp-font:          inherit;
    --stagekitwp-lp-heading-font:  var(--stagekitwp-lp-font);
    --stagekitwp-lp-align:         left;
    --stagekitwp-lp-heading-scale: 1;
    --stagekitwp-lp-text-scale:    1;
    --stagekitwp-lp-cast-size:     1rem;
    --stagekitwp-lp-cast-bio-size: 0.85em;
    --stagekitwp-lp-cast-style:    normal;
    --stagekitwp-lp-cast-weight:   normal;
    --stagekitwp-lp-cast-font:     var(--stagekitwp-lp-font);
    background: var(--stagekitwp-lp-bg);
    color: var(--stagekitwp-lp-text);
    border: 1px solid var(--stagekitwp-lp-border);
    border-radius: 6px;
    overflow: hidden;
    font-family: var(--stagekitwp-lp-font);
    box-shadow: 0 2px 12px rgba(0,0,0,.07);
}
/* Alignment — driven by --stagekitwp-lp-align on wrapper */
.stagekitwp-lp-info-col,
.stagekitwp-lp-hero-body,
.stagekitwp-lp-hero-overlay,
.stagekitwp-lp-prog-body,
.stagekitwp-lp-prog-titleblock,
.stagekitwp-lp-minimal-body { text-align: var(--stagekitwp-lp-align); }

.stagekitwp-lp-title-rule { margin-left: auto; margin-right: auto; }
.stagekitwp-landingpage-wrapper[style*="--stagekitwp-lp-align:left"]  .stagekitwp-lp-title-rule,
.stagekitwp-landingpage-wrapper[style*="--stagekitwp-lp-align: left"] .stagekitwp-lp-title-rule { margin-left: 0; margin-right: 0; }

/* Align labels when centred so they don\'t look unbalanced */
.stagekitwp-lp-label { text-align: var(--stagekitwp-lp-align); }

/* Cast list: respect alignment */
.stagekitwp-landingpage-cast-list { align-items: flex-start; }
.stagekitwp-landingpage-wrapper[style*="--stagekitwp-lp-align:center"] .stagekitwp-landingpage-cast-list,
.stagekitwp-landingpage-wrapper[style*="--stagekitwp-lp-align: center"] .stagekitwp-landingpage-cast-list { align-items: center; }
.stagekitwp-landingpage-wrapper[style*="--stagekitwp-lp-align:right"] .stagekitwp-landingpage-cast-list,
.stagekitwp-landingpage-wrapper[style*="--stagekitwp-lp-align: right"] .stagekitwp-landingpage-cast-list { align-items: flex-end; }

/* Ticket button: inherit alignment */
.stagekitwp-lp-ticket-wrap { text-align: var(--stagekitwp-lp-align); }

/* Two-column shell */
.stagekitwp-lp-body {
    display: flex;
    flex-wrap: wrap;
    gap: 0;
}
.stagekitwp-lp-image-col {
    flex: 0 0 38%;
    max-width: 38%;
    background: var(--stagekitwp-lp-meta-bg);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-start;
    padding: 28px 20px;
    border-right: 1px solid var(--stagekitwp-lp-border);
}
.stagekitwp-lp-image-col .stagekitwp-lp-image-ticket {
    width: 100%;
    margin-top: 16px;
    text-align: center;
}
.stagekitwp-lp-image-col .stagekitwp-lp-image-ticket .stagekitwp-lp-ticket-wrap {
    text-align: center;
}
.stagekitwp-lp-image-col .stagekitwp-lp-image-ticket .stagekitwp-lp-ticket-btn {
    width: 100%;
    box-sizing: border-box;
    justify-content: center;
}
.stagekitwp-lp-image-col img {
    width: 100%;
    height: auto;
    max-height: 420px;
    object-fit: cover;
    border-radius: 4px;
    display: block;
    box-shadow: 0 4px 16px rgba(0,0,0,.15);
}
.stagekitwp-lp-info-col {
    flex: 1 1 0;
    min-width: 0;
    padding: 32px 36px;
}
/* Heading font — applied to every heading element */
.stagekitwp-lp-title,
.stagekitwp-lp-hero-title,
.stagekitwp-lp-hero-title-noimg,
.stagekitwp-lp-section-heading,
.stagekitwp-lp-label {
    font-family: var(--stagekitwp-lp-heading-font);
}
/* Show title */
.stagekitwp-lp-title {
    font-size: calc(clamp(1.6rem, 3.5vw, 2.4rem) * var(--stagekitwp-lp-heading-scale));
    font-weight: 900;
    color: var(--stagekitwp-lp-heading);
    text-transform: uppercase;
    letter-spacing: .04em;
    line-height: 1.15;
    margin: 0 0 6px;
    padding: 0;
}
.stagekitwp-lp-title-rule {
    width: 56px;
    height: 3px;
    background: var(--stagekitwp-lp-accent);
    border: none;
    margin: 0 0 22px;
    border-radius: 2px;
}
/* Field sections */
.stagekitwp-lp-section {
    margin-bottom: 20px;
}
.stagekitwp-lp-field {
    margin-bottom: 12px;
}
.stagekitwp-lp-label {
    display: block;
    font-size: .68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .1em;
    color: var(--stagekitwp-lp-accent);
    margin-bottom: 3px;
}
.stagekitwp-lp-value {
    display: block;
    font-size: calc(.95rem * var(--stagekitwp-lp-text-scale));
    color: var(--stagekitwp-lp-text);
    line-height: 1.55;
}
/* Synopsis gets slightly larger text */
.stagekitwp-lp-field-synopsis .stagekitwp-lp-value {
    font-size: calc(1rem * var(--stagekitwp-lp-text-scale));
    line-height: 1.7;
    font-style: italic;
    color: var(--stagekitwp-lp-text);
    opacity: .9;
}
/* Divider between sections */
.stagekitwp-lp-divider {
    border: none;
    border-top: 1px solid var(--stagekitwp-lp-border);
    margin: 18px 0;
}
/* Cast list */
.stagekitwp-landingpage-cast-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.stagekitwp-landingpage-cast-list li {
    font-size: .9rem;
    color: var(--stagekitwp-lp-text);
}
/* Venue block */
.stagekitwp-landingpage-venue {
    font-size: .9rem;
    line-height: 1.55;
}
/* Ticket button */
.stagekitwp-lp-ticket-btn {
    display: inline-block;
    margin-top: 18px;
    padding: 13px 28px;
    background: var(--stagekitwp-lp-btn-bg);
    color: var(--stagekitwp-lp-btn-text) !important;
    text-decoration: none !important;
    font-weight: 700;
    font-size: .95rem;
    text-transform: uppercase;
    letter-spacing: .06em;
    border-radius: 4px;
    transition: opacity .18s;
    border: none;
    cursor: pointer;
}
.stagekitwp-lp-ticket-btn:hover { opacity: .85; }
.stagekitwp-lp-program-btn{
    display:inline-block;
    margin-top:10px;
    padding:11px 22px;
    background:transparent;
    color:var(--stagekitwp-lp-btn-bg)!important;
    text-decoration:none!important;
    font-weight:700;
    font-size:.88rem;
    text-transform:uppercase;
    letter-spacing:.06em;
    border-radius:4px;
    border:1px solid var(--stagekitwp-lp-btn-bg);
    transition:opacity .18s;
    cursor:pointer;
}
.stagekitwp-lp-program-btn:hover{opacity:.85}
.stagekitwp-lp-program-link{display:inline-flex;align-items:center;gap:6px;color:var(--stagekitwp-lp-accent)!important;text-decoration:none;font-weight:600}
.stagekitwp-lp-program-link:hover{text-decoration:underline}
.stagekitwp-lp-program-link .stagekitwp-lp-pdf-icon{width:18px;height:18px;display:inline-block;vertical-align:middle;flex-shrink:0}
/* No-image: info col fills full width */
.stagekitwp-lp-body.stagekitwp-lp-no-image .stagekitwp-lp-info-col {
    padding: 32px 36px;
}
/* Header bar (always visible, accent strip) */
.stagekitwp-lp-header-bar {
    height: 5px;
    background: var(--stagekitwp-lp-accent);
}
/* Responsive */
@media (max-width: 680px) {
    .stagekitwp-lp-image-col {
        flex: 0 0 100%;
        max-width: 100%;
        border-right: none;
        border-bottom: 1px solid var(--stagekitwp-lp-border);
        padding: 20px;
    }
    .stagekitwp-lp-info-col { padding: 24px 20px; }
    .stagekitwp-lp-title { font-size: calc(1.5rem * var(--stagekitwp-lp-heading-scale)); }
}
/* ── Hero layout ─────────────────────────────────────────────────── */
.stagekitwp-lp-layout-hero {
    overflow: hidden;
    border-radius: 6px;
    box-shadow: 0 2px 12px rgba(0,0,0,.1);
}
.stagekitwp-lp-hero-banner {
    position: relative;
    width: 100%;
    /* Height is driven by the <img> inside; no min-height needed */
    line-height: 0; /* collapse whitespace around img */
}
.stagekitwp-lp-hero-img {
    display: block;
    width: 100%;
    height: auto;       /* natural aspect ratio — full image always visible */
    object-fit: contain;
}
.stagekitwp-lp-hero-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    padding: 48px 40px 32px;
    background: linear-gradient(to top, rgba(0,0,0,.82) 0%, rgba(0,0,0,.45) 55%, transparent 100%);
    box-sizing: border-box;
}
.stagekitwp-lp-hero-title {
    color: #ffffff;
    font-size: calc(clamp(2rem,5vw,3.2rem) * var(--stagekitwp-lp-heading-scale));
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: .03em;
    line-height: 1.1;
    margin: 0;
    text-shadow: 0 2px 12px rgba(0,0,0,.6);
}
.stagekitwp-lp-hero-title-bar {
    background: var(--stagekitwp-lp-accent);
    padding: 28px 40px;
}
.stagekitwp-lp-hero-title-noimg {
    color: #ffffff;
    font-size: calc(clamp(1.8rem,4vw,2.8rem) * var(--stagekitwp-lp-heading-scale));
    font-weight: 900;
    text-transform: uppercase;
    margin: 0;
}
.stagekitwp-lp-hero-body {
    padding: 32px 40px;
    background: var(--stagekitwp-lp-bg);
}
/* Season banner strip */
.stagekitwp-lp-season-banner {
    border-top: 1px solid var(--stagekitwp-lp-border);
    line-height: 0;
}
.stagekitwp-lp-season-banner img {
    width: 100%;
    height: auto;
    display: block;
}
@media (max-width: 680px) {
    /* hero height is now natural from the <img> — no min-height override needed */
    .stagekitwp-lp-hero-overlay { padding: 32px 20px 20px; }
    .stagekitwp-lp-hero-body { padding: 24px 20px; }
}
/* ── Programme layout ────────────────────────────────────────────── */
.stagekitwp-lp-layout-programme {
    overflow: hidden;
    border-radius: 6px;
    box-shadow: 0 2px 12px rgba(0,0,0,.07);
    background: var(--stagekitwp-lp-bg);
}
.stagekitwp-lp-prog-header {
    display: flex;
    gap: 28px;
    padding: 28px 32px;
    border-bottom: 1px solid var(--stagekitwp-lp-border);
    flex-wrap: wrap;
}
.stagekitwp-lp-prog-poster {
    flex: 0 0 200px;
    max-width: 200px;
}
.stagekitwp-lp-prog-poster .stagekitwp-lp-image-ticket {
    margin-top: 12px;
    text-align: center;
}
.stagekitwp-lp-prog-poster .stagekitwp-lp-image-ticket .stagekitwp-lp-ticket-btn {
    width: 100%;
    box-sizing: border-box;
    justify-content: center;
    font-size: .82em;
    padding: 8px 12px;
}
.stagekitwp-lp-prog-poster img {
    width: 100%;
    height: auto;
    display: block;
    border-radius: 3px;
    box-shadow: 0 3px 10px rgba(0,0,0,.18);
}
.stagekitwp-lp-prog-titleblock {
    flex: 1 1 0;
    min-width: 0;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0 24px;
    align-content: start;
}
.stagekitwp-lp-prog-titleblock .stagekitwp-lp-title,
.stagekitwp-lp-prog-titleblock .stagekitwp-lp-title-rule {
    grid-column: 1 / -1;
}
.stagekitwp-lp-prog-body {
    padding: 28px 32px;
    background: var(--stagekitwp-lp-bg);
}
.stagekitwp-lp-section-heading {
    font-size: calc(.75rem * var(--stagekitwp-lp-heading-scale));
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .1em;
    color: var(--stagekitwp-lp-accent);
    margin: 0 0 8px;
    padding: 0;
    border: none;
}
/* Cast section heading inherits cast font */
.stagekitwp-landingpage-castwithbio .stagekitwp-lp-section-heading {
    font-family: var(--stagekitwp-lp-cast-font, var(--stagekitwp-lp-font));
}
.stagekitwp-lp-prog-synopsis {
    margin-bottom: 24px;
    padding-bottom: 24px;
    border-bottom: 1px solid var(--stagekitwp-lp-border);
}
.stagekitwp-lp-prog-logistics {
    display: flex;
    gap: 32px;
    flex-wrap: wrap;
    margin-bottom: 24px;
    padding-bottom: 24px;
    border-bottom: 1px solid var(--stagekitwp-lp-border);
}
.stagekitwp-lp-prog-logistics-item { flex: 1 1 200px; }
.stagekitwp-lp-prog-cast { margin-bottom: 24px; }
.stagekitwp-lp-prog-ticket { padding-top: 16px; border-top: 1px solid var(--stagekitwp-lp-border); }
@media (max-width: 680px) {
    .stagekitwp-lp-prog-poster { flex: 0 0 120px; max-width: 120px; }
    .stagekitwp-lp-prog-titleblock { grid-template-columns: 1fr; }
    .stagekitwp-lp-prog-header { padding: 20px; }
    .stagekitwp-lp-prog-body { padding: 20px; }
}
/* ── Minimal layout ──────────────────────────────────────────────── */
.stagekitwp-lp-layout-minimal {
    background: transparent;
    border: none;
    box-shadow: none;
    border-radius: 0;
    padding: 0;
}
.stagekitwp-lp-layout-minimal .stagekitwp-lp-title { margin-bottom: 6px; }
.stagekitwp-lp-minimal-image { margin: 0 0 24px; }
.stagekitwp-lp-minimal-image .stagekitwp-lp-image-ticket {
    margin-top: 14px;
    text-align: var(--stagekitwp-lp-align, center);
}
.stagekitwp-lp-minimal-image img {
    max-width: 360px;
    width: 100%;
    height: auto;
    border-radius: 4px;
    display: block;
}
.stagekitwp-lp-minimal-body { max-width: 680px; }
';
    stagekitwp_add_shortcode_inline_style( 'stagekitwp-landingpage', $css );
}

/**
 * Resolve a section heading: per-show meta > Display Options global > hardcoded default.
 *
 * @param string $key      e.g. 'synopsis', 'cast', 'show_dates'
 * @param int    $show_id
 * @return string
 */
function stagekitwp_lp_heading( $key, $show_id ) {
    static $defaults = [
        'author'        => 'Written by',
        'sub_authors'   => 'Music / Lyrics / Book',
        'director'      => 'Directed by',
        'assoc_dir'     => 'Associate Director',
        'producer'      => 'Produced by',
        'stage_manager' => 'Stage Manager',
        'synopsis'      => 'Synopsis',
        'show_dates'    => 'Performances',
        'program_pdf'   => 'Programme',
        'venue'         => 'Venue',
        'cast'          => 'Cast',
    ];
    // 1. Per-show meta (set in Front-end Display meta box)
    $meta = get_post_meta( $show_id, '_stagekitwp_lp_heading_' . $key, true );
    if ( ! empty( $meta ) ) { return esc_html( $meta ); }
    // 2. Display Options global
    $opt = get_option( 'stagekitwp_lp_heading_' . $key, '' );
    if ( ! empty( $opt ) ) { return esc_html( $opt ); }
    // 3. Hardcoded default
    return esc_html( $defaults[ $key ] ?? ucfirst( str_replace( '_', ' ', $key ) ) );
}

/**
 * Build the ticket button/link HTML. Returns '' if no URL.
 */
function stagekitwp_lp_ticket_html( $show_id, $use_button, $buttonformat ) {
    $url = get_post_meta( $show_id, '_stagekitwp_show_tickets_url', true );
    if ( ! $url ) { return ''; }
    if ( $use_button ) {
        $fmt = sanitize_key( $buttonformat );
        return '<div class="stagekitwp-lp-ticket-wrap"><a href="' . esc_url( $url ) . '" class="stagekitwp-lp-ticket-btn stagekitwp-url-button stagekitwp-button-' . esc_attr( $fmt ) . '" target="_blank" rel="noopener">Get Tickets</a></div>';
    }
    return '<div class="stagekitwp-lp-field stagekitwp-lp-field-ticket_url">'
         . '<span class="stagekitwp-lp-label">Tickets</span>'
         . '<span class="stagekitwp-lp-value"><a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">Buy Tickets</a></span>'
         . '</div>';
}

function stagekitwp_lp_pdf_icon_svg() {
    return '<svg class="stagekitwp-lp-pdf-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false">'
         . '<path fill="#e53935" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>'
         . '<polyline fill="none" stroke="#fff" stroke-width="1.5" points="14 2 14 8 20 8"/>'
         . '<text x="12" y="17" text-anchor="middle" font-size="5.5" font-family="Arial,sans-serif" font-weight="700" fill="#fff" letter-spacing=".3">PDF</text>'
         . '</svg>';
}

function stagekitwp_lp_program_html( $program_url ) {
    if ( empty( $program_url ) ) {
        return '';
    }
    return '<a class="stagekitwp-lp-program-link" href="' . esc_url( $program_url ) . '" target="_blank" rel="noopener">'
         . stagekitwp_lp_pdf_icon_svg()
         . '<span>Programme PDF</span>'
         . '</a>';
}

function stagekitwp_lp_program_button_html( $show_id, $buttonformat = 'outline' ) {
    $program_id  = get_post_meta( $show_id, '_stagekitwp_show_program', true );
    $program_url = get_post_meta( $show_id, '_stagekitwp_show_program_url', true );
    if ( ! $program_url && $program_id ) {
        $program_url = wp_get_attachment_url( $program_id );
    }
    if ( empty( $program_url ) ) {
        return '';
    }
    $fmt = sanitize_key( $buttonformat ?: 'outline' );
    return '<div class="stagekitwp-lp-ticket-wrap"><a href="' . esc_url( $program_url ) . '" class="stagekitwp-lp-program-btn stagekitwp-url-button stagekitwp-button-' . esc_attr( $fmt ) . '" target="_blank" rel="noopener">Programme</a></div>';
}

/**
 * Season banner HTML (renders [stagekitwp_seasons] field shortcode).
 * Returns '' if show has no season or no social_banner image.
 */
function stagekitwp_lp_season_banner( $show_id ) {
    $season_id = (int) get_post_meta( $show_id, '_stagekitwp_show_season', true );
    if ( ! $season_id ) { return ''; }
    $banner_id = (int) get_post_meta( $season_id, '_stagekitwp_season_social_banner', true );
    if ( ! $banner_id ) { return ''; }
    $url = wp_get_attachment_image_src( $banner_id, 'full' );
    if ( ! $url ) { return ''; }
    $season_name = esc_html( get_post_meta( $season_id, '_stagekitwp_season_name', true ) ?: get_the_title( $season_id ) );
    return '<div class="stagekitwp-lp-season-banner">'
         . '<img src="' . esc_url( $url[0] ) . '" alt="' . $season_name . '" loading="lazy">'
         . '</div>';
}

/**
 * Shared crew + synopsis + logistics + cast + ticket block used by multiple layouts.
 * Returns HTML string.
 */
function stagekitwp_lp_info_sections( $fields_data, $show_id, $use_button, $buttonformat ) {
    $out = '';

    // Creative team
    $crew_map = [
        'author'             => 'author',
        'sub_authors'        => 'sub_authors',
        'director'           => 'director',
        'associate_director' => 'assoc_dir',
        'producer'           => 'producer',
        'stage_manager'      => 'stage_manager',
    ];
    $crew_html = '';
    foreach ( $crew_map as $field => $hkey ) {
        if ( isset( $fields_data[ $field ] ) ) {
            $crew_html .= '<div class="stagekitwp-lp-field stagekitwp-lp-field-' . esc_attr( $field ) . '">'
                        . '<span class="stagekitwp-lp-label">' . stagekitwp_lp_heading( $hkey, $show_id ) . '</span>'
                        . '<span class="stagekitwp-lp-value">' . $fields_data[ $field ] . '</span>'
                        . '</div>';
        }
    }
    if ( $crew_html ) {
        $out .= '<div class="stagekitwp-lp-section stagekitwp-lp-section-crew">' . $crew_html . '</div>';
    }

    // Synopsis
    if ( isset( $fields_data['synopsis'] ) ) {
        $out .= '<hr class="stagekitwp-lp-divider">'
              . '<div class="stagekitwp-lp-section stagekitwp-lp-section-synopsis">'
              . '<div class="stagekitwp-lp-field stagekitwp-lp-field-synopsis">'
              . '<span class="stagekitwp-lp-label">' . stagekitwp_lp_heading( 'synopsis', $show_id ) . '</span>'
              . '<span class="stagekitwp-lp-value">' . $fields_data['synopsis'] . '</span>'
              . '</div></div>';
    }

    // Dates + venue
    $log_html = '';
    if ( isset( $fields_data['show_dates'] ) ) {
        $log_html .= '<div class="stagekitwp-lp-field stagekitwp-lp-field-show_dates">'
                   . '<span class="stagekitwp-lp-label">' . stagekitwp_lp_heading( 'show_dates', $show_id ) . '</span>'
                   . '<span class="stagekitwp-lp-value">' . $fields_data['show_dates'] . '</span>'
                   . '</div>';
    }
    if ( isset( $fields_data['program_pdf'] ) ) {
        $log_html .= '<div class="stagekitwp-lp-field stagekitwp-lp-field-program_pdf">'
                   . '<span class="stagekitwp-lp-label">' . stagekitwp_lp_heading( 'program_pdf', $show_id ) . '</span>'
                   . '<span class="stagekitwp-lp-value">' . $fields_data['program_pdf'] . '</span>'
                   . '</div>';
    }
    if ( isset( $fields_data['venue'] ) ) {
        $log_html .= '<div class="stagekitwp-lp-field stagekitwp-lp-field-venue">'
                   . '<span class="stagekitwp-lp-label">' . stagekitwp_lp_heading( 'venue', $show_id ) . '</span>'
                   . '<span class="stagekitwp-lp-value">' . $fields_data['venue'] . '</span>'
                   . '</div>';
    }
    if ( $log_html ) {
        $out .= '<hr class="stagekitwp-lp-divider"><div class="stagekitwp-lp-section stagekitwp-lp-section-logistics">' . $log_html . '</div>';
    }

    // Cast
    foreach ( [ 'cast', 'castwithbio' ] as $f ) {
        if ( isset( $fields_data[ $f ] ) ) {
            $out .= '<hr class="stagekitwp-lp-divider">'
                  . '<div class="stagekitwp-lp-section stagekitwp-lp-section-cast">'
                  . '<div class="stagekitwp-lp-field stagekitwp-lp-field-' . esc_attr( $f ) . '">'
                  . '<span class="stagekitwp-lp-label">' . stagekitwp_lp_heading( 'cast', $show_id ) . '</span>'
                  . '<span class="stagekitwp-lp-value">' . $fields_data[ $f ] . '</span>'
                  . '</div></div>';
            break;
        }
    }

    // Ticket
    if ( isset( $fields_data['ticket_url'] ) ) {
        $tk = stagekitwp_lp_ticket_html( $show_id, $use_button, $buttonformat );
        if ( $tk ) { $out .= $tk; }
    }

    return $out;
}

function stagekitwp_shortcode_landingpage($atts) {
    $atts = shortcode_atts([
        'show_id'            => 'current',
        'layout'             => '',
        'field_list'         => 'show_name,show_image,author,director,producer,stage_manager,synopsis,show_dates,ticket_url,program_pdf,cast,venue',
        'hard_breaks'        => 'true',
        'castcols'           => '',
        'urlbutton'          => '',
        'program_button'     => '',
        'buttonformat'       => '',
        'show_season_banner' => '',
        'font'               => '',
        'heading_font'       => '',
        'heading_size'       => '',
        'text_size'          => '',
        'align'              => '',
        'cast_size'          => '',
        'cast_bio_size'      => '',
        'cast_style'         => '',
        'cast_font'          => '',
    ], $atts, 'stagekitwp_landingpage');

    // ── Resolve show ────────────────────────────────────────────────────────
    if ( strtolower( $atts['show_id'] ) === 'current' ) {
        $show_id = stagekitwp_get_current_show();
        if ( ! $show_id ) { return '<!-- No current show available -->'; }
    } else {
        $show_id = intval( $atts['show_id'] );
        if ( $show_id <= 0 ) { return '<!-- Invalid show ID -->'; }
        $show = get_post( $show_id );
        if ( ! $show || $show->post_type !== 'show' ) { return '<!-- Show not found -->'; }
    }

    // ── Parse attrs ─────────────────────────────────────────────────────────
    $field_list = array_filter( array_map( 'trim', explode( ',', $atts['field_list'] ) ) );
    if ( empty( $field_list ) ) { return '<!-- No fields specified -->'; }

    // layout: attr > per-show meta > 'card'
    $layout = sanitize_key( $atts['layout'] );
    if ( ! in_array( $layout, [ 'card', 'hero', 'programme', 'minimal' ], true ) ) {
        $layout = sanitize_key( get_post_meta( $show_id, '_stagekitwp_show_lp_layout', true ) );
    }
    if ( ! in_array( $layout, [ 'card', 'hero', 'programme', 'minimal' ], true ) ) { $layout = 'card'; }

    // urlbutton: attr > per-show meta > false
    $urlbtn_raw = strtolower( trim( $atts['urlbutton'] ) );
    if ( $urlbtn_raw === 'true' || $urlbtn_raw === '1' ) {
        $use_button = true;
    } elseif ( $urlbtn_raw === 'false' || $urlbtn_raw === '0' ) {
        $use_button = false;
    } else {
        // empty attr — check per-show meta
        $use_button = ( strtolower( get_post_meta( $show_id, '_stagekitwp_show_lp_urlbutton', true ) ) === 'true' );
    }

    // buttonformat: attr > per-show meta > 'default'
    $buttonformat = sanitize_key( $atts['buttonformat'] );
    if ( empty( $buttonformat ) ) {
        $buttonformat = sanitize_key( get_post_meta( $show_id, '_stagekitwp_show_lp_buttonformat', true ) );
    }
    if ( empty( $buttonformat ) ) { $buttonformat = 'default'; }

    // castcols: attr > per-show meta > 3
    $castcols_raw = intval( $atts['castcols'] );
    if ( $castcols_raw < 1 ) {
        $castcols_raw = intval( get_post_meta( $show_id, '_stagekitwp_show_lp_castcols', true ) );
    }
    $castcols = max( 1, min( 6, $castcols_raw ?: 3 ) );

    // program_button: attr > per-show meta > false
    $progbtn_raw = strtolower( trim( $atts['program_button'] ) );
    if ( $progbtn_raw === 'true' || $progbtn_raw === '1' ) {
        $show_program_button = true;
    } elseif ( $progbtn_raw === 'false' || $progbtn_raw === '0' ) {
        $show_program_button = false;
    } else {
        $show_program_button = ( strtolower( get_post_meta( $show_id, '_stagekitwp_show_lp_program_button', true ) ) === 'true' );
    }

    // show_season_banner: attr > per-show meta > false
    $show_banner_raw = strtolower( $atts['show_season_banner'] );
    if ( $show_banner_raw === 'true' ) {
        $show_banner = true;
    } elseif ( $show_banner_raw === 'false' ) {
        $show_banner = false;
    } else {
        // attr not explicitly set — check per-show meta
        $show_banner = ( strtolower( get_post_meta( $show_id, '_stagekitwp_show_lp_season_banner', true ) ) === 'true' );
    }

    // ── Resolve font slug: attr > per-show meta > Display Options global ────────
    $font_slug = sanitize_key( $atts['font'] );
    if ( empty( $font_slug ) ) {
        $font_slug = sanitize_key( get_post_meta( $show_id, '_stagekitwp_show_lp_font', true ) );
    }
    if ( empty( $font_slug ) ) {
        $font_slug = sanitize_key( get_option( 'stagekitwp_landing_page_base_font', '' ) );
    }
    $font_stack = stagekitwp_lp_full_font_stack( $font_slug ); // '' when inherit/unknown
    if ( ! empty( $font_stack ) ) {
        stagekitwp_lp_maybe_enqueue_google_font( $font_slug ); // no-op for wplf- / unknown slugs
    }

    // ── Emit CSS once ───────────────────────────────────────────────────────
    stagekitwp_landingpage_styles();

    // ── Collect field outputs ───────────────────────────────────────────────
    $fields_data = [];
    foreach ( $field_list as $f ) {
        $f   = sanitize_key( $f );
        $out = stagekitwp_render_landingpage_field( $show_id, $f, true, $atts );
        if ( $out !== '' ) {
            $fields_data[ $f ] = $out;
        }
    }

    // ── Resolve heading font slug ─────────────────────────────────────────
    $hfont_slug = sanitize_key( $atts['heading_font'] );
    if ( empty( $hfont_slug ) ) {
        $hfont_slug = sanitize_key( get_post_meta( $show_id, '_stagekitwp_show_lp_heading_font', true ) );
    }
    if ( empty( $hfont_slug ) ) {
        $hfont_slug = sanitize_key( get_option( 'stagekitwp_landing_page_heading_font', '' ) );
    }
    $hfont_stack = stagekitwp_lp_full_font_stack( $hfont_slug );
    if ( ! empty( $hfont_stack ) ) {
        stagekitwp_lp_maybe_enqueue_google_font( $hfont_slug );
    }

    // ── Size scales: t-shirt tokens ────────────────────────────────────────
    $size_map = [ 'xs' => '0.75', 'sm' => '0.875', 'md' => '1', 'lg' => '1.2', 'xl' => '1.45', 'xxl' => '1.7' ];

    // Heading size
    $hsize_raw = sanitize_key( $atts['heading_size'] );
    if ( empty( $hsize_raw ) ) {
        $hsize_raw = sanitize_key( get_post_meta( $show_id, '_stagekitwp_show_lp_heading_size', true ) );
    }
    if ( empty( $hsize_raw ) ) {
        $hsize_raw = sanitize_key( get_option( 'stagekitwp_landing_page_heading_size', '' ) );
    }
    $heading_scale = isset( $size_map[ $hsize_raw ] ) ? $size_map[ $hsize_raw ] : '';

    // Text (body) size
    $tsize_raw = sanitize_key( $atts['text_size'] );
    if ( empty( $tsize_raw ) ) {
        $tsize_raw = sanitize_key( get_post_meta( $show_id, '_stagekitwp_show_lp_text_size', true ) );
    }
    if ( empty( $tsize_raw ) ) {
        $tsize_raw = sanitize_key( get_option( 'stagekitwp_landing_page_text_size', '' ) );
    }
    $text_scale = isset( $size_map[ $tsize_raw ] ) ? $size_map[ $tsize_raw ] : '';

    // ── Resolve text alignment: attr > per-show meta > Display Options global ────
    $allowed_aligns = [ 'left', 'center', 'right', 'justify' ];
    $align = sanitize_key( $atts['align'] );
    if ( empty( $align ) || ! in_array( $align, $allowed_aligns, true ) ) {
        $align = sanitize_key( get_post_meta( $show_id, '_stagekitwp_show_lp_align', true ) );
    }
    if ( empty( $align ) || ! in_array( $align, $allowed_aligns, true ) ) {
        $align = sanitize_key( get_option( 'stagekitwp_landing_page_text_align', '' ) );
    }
    if ( ! in_array( $align, $allowed_aligns, true ) ) { $align = ''; }

    // ── Cast size: attr > per-show meta > Display Options global ────────────
    // Accepts CSS size tokens: xs/sm/md/lg/xl/xxl (mapped to rem) or bare CSS value (e.g. "14px", "0.9rem")
    $cast_size_tokens = [ 'xs' => '0.75rem', 'sm' => '0.85rem', 'md' => '1rem', 'lg' => '1.1rem', 'xl' => '1.25rem', 'xxl' => '1.5rem' ];
    $cast_size_raw = trim( $atts['cast_size'] );
    if ( empty( $cast_size_raw ) ) {
        $cast_size_raw = trim( get_post_meta( $show_id, '_stagekitwp_show_lp_cast_size', true ) );
    }
    if ( empty( $cast_size_raw ) ) {
        $cast_size_raw = trim( get_option( 'stagekitwp_landing_page_cast_size', '' ) );
    }
    // Resolve token to value; otherwise accept as raw CSS (e.g. '14px')
    $cast_size = isset( $cast_size_tokens[ $cast_size_raw ] ) ? $cast_size_tokens[ $cast_size_raw ] : esc_attr( $cast_size_raw );

    // ── Cast bio size: attr > per-show meta > Display Options global ──────────
    // Default: 0.85em (relative to card font-size). Accepts same tokens as cast_size.
    $cast_bio_size_raw = trim( $atts['cast_bio_size'] );
    if ( empty( $cast_bio_size_raw ) ) {
        $cast_bio_size_raw = trim( get_post_meta( $show_id, '_stagekitwp_show_lp_cast_bio_size', true ) );
    }
    if ( empty( $cast_bio_size_raw ) ) {
        $cast_bio_size_raw = trim( get_option( 'stagekitwp_landing_page_cast_bio_size', '' ) );
    }
    $cast_bio_size = isset( $cast_size_tokens[ $cast_bio_size_raw ] ) ? $cast_size_tokens[ $cast_bio_size_raw ] : esc_attr( $cast_bio_size_raw );

    // ── Cast style: attr > per-show meta > Display Options global ───────────
    // Accepts: normal | italic | bold | bold-italic
    $allowed_cast_styles = [ 'normal', 'italic', 'bold', 'bold-italic' ];
    $cast_style_raw = sanitize_key( $atts['cast_style'] );
    if ( empty( $cast_style_raw ) ) {
        $cast_style_raw = sanitize_key( get_post_meta( $show_id, '_stagekitwp_show_lp_cast_style', true ) );
    }
    if ( empty( $cast_style_raw ) ) {
        $cast_style_raw = sanitize_key( get_option( 'stagekitwp_landing_page_cast_style', '' ) );
    }
    $cast_style = in_array( $cast_style_raw, $allowed_cast_styles, true ) ? $cast_style_raw : '';

    // ── Cast font ────────────────────────────────────────────────────────────
    // Resolution: shortcode attr → per-show meta → Display Options → '' (inherits --stagekitwp-lp-font)
    $cast_font_slug_raw = sanitize_key( $atts['cast_font'] );
    if ( empty( $cast_font_slug_raw ) ) {
        $cast_font_slug_raw = sanitize_key( get_post_meta( $show_id, '_stagekitwp_show_lp_cast_font', true ) );
    }
    if ( empty( $cast_font_slug_raw ) ) {
        $cast_font_slug_raw = sanitize_key( get_option( 'stagekitwp_landing_page_cast_font', '' ) );
    }
    $cast_font_stack = $cast_font_slug_raw ? stagekitwp_lp_full_font_stack( $cast_font_slug_raw ) : '';
    if ( $cast_font_slug_raw && $cast_font_slug_raw !== 'inherit' ) {
        stagekitwp_lp_maybe_enqueue_google_font( $cast_font_slug_raw );
    }

    // ── Build composite wrapper style: font + heading-font + sizes + align + cast ─
    $inline_vars = [];
    if ( ! empty( $font_stack ) )       { $inline_vars[] = '--stagekitwp-lp-font:'         . $font_stack; }
    if ( ! empty( $hfont_stack ) )      { $inline_vars[] = '--stagekitwp-lp-heading-font:' . $hfont_stack; }
    if ( ! empty( $cast_font_stack ) )  { $inline_vars[] = '--stagekitwp-lp-cast-font:'    . $cast_font_stack; }
    if ( ! empty( $heading_scale ) )    { $inline_vars[] = '--stagekitwp-lp-heading-scale:'. $heading_scale; }
    if ( ! empty( $text_scale ) )       { $inline_vars[] = '--stagekitwp-lp-text-scale:'   . $text_scale; }
    if ( ! empty( $align ) )            { $inline_vars[] = '--stagekitwp-lp-align:'        . $align; }
    if ( ! empty( $cast_size ) )        { $inline_vars[] = '--stagekitwp-lp-cast-size:'     . $cast_size; }
    if ( ! empty( $cast_bio_size ) )    { $inline_vars[] = '--stagekitwp-lp-cast-bio-size:' . $cast_bio_size; }
    if ( ! empty( $cast_style ) ) {
        // bold-italic maps to two properties
        if ( $cast_style === 'bold-italic' ) {
            $inline_vars[] = '--stagekitwp-lp-cast-style:italic';
            $inline_vars[] = '--stagekitwp-lp-cast-weight:700';
        } elseif ( $cast_style === 'bold' ) {
            $inline_vars[] = '--stagekitwp-lp-cast-weight:700';
        } elseif ( $cast_style === 'italic' ) {
            $inline_vars[] = '--stagekitwp-lp-cast-style:italic';
        }
    }
    $wrapper_style = $inline_vars
        ? ' style="' . esc_attr( implode( ';', $inline_vars ) ) . '"'
        : '';

    // ── Shared computed values ──────────────────────────────────────────────────
    $has_image   = isset( $fields_data['show_image'] );
    $has_title   = isset( $fields_data['show_name'] );
    $show_title  = $has_title ? esc_html( get_the_title( $show_id ) ) : '';
    $show_heading_tag = ( is_singular( 'show' ) || is_front_page() ) ? 'h1' : 'h2';
    $info_html   = stagekitwp_lp_info_sections( $fields_data, $show_id, $use_button, $buttonformat );
    $banner_html = $show_banner ? stagekitwp_lp_season_banner( $show_id ) : '';
    $img_url     = '';
    if ( $has_image ) {
        $img_id  = (int) get_post_meta( $show_id, '_stagekitwp_show_sm_image', true );
        $img_src = $img_id ? wp_get_attachment_image_src( $img_id, 'large' ) : false;
        $img_url = $img_src ? $img_src[0] : stagekitwp_get_image_url( get_post_meta( $show_id, '_stagekitwp_show_sm_image', true ) );
    }

    // ── Layout router ─────────────────────────────────────────────────────────
    ob_start();
    switch ( $layout ) {

        // ───────────────────────────────────────────────────────────────────
        case 'card': default:
        // Two-column card: image left, info right
        ?>
        <div class="stagekitwp-landingpage-wrapper stagekitwp-landingpage stagekitwp-lp-layout-card"<?php echo $wrapper_style; ?>>
            <div class="stagekitwp-lp-header-bar"></div>
            <div class="stagekitwp-lp-body<?php echo $has_image ? '' : ' stagekitwp-lp-no-image'; ?>">
                <?php if ( $has_image && $img_url ) : ?>
                <div class="stagekitwp-lp-image-col">
                    <img src="<?php echo esc_url( $img_url ); ?>" alt="<?php echo $show_title; ?>">
                    <?php if ( isset( $fields_data['ticket_url'] ) ) :
                        $tk_img = stagekitwp_lp_ticket_html( $show_id, $use_button, $buttonformat ); if ( $tk_img ) : ?>
                    <div class="stagekitwp-lp-image-ticket"><?php echo $tk_img; ?></div>
                    <?php endif; endif; ?>
                    <?php if ( $show_program_button ) :
                        $pg_img = stagekitwp_lp_program_button_html( $show_id, $buttonformat );
                        if ( $pg_img ) : ?>
                    <div class="stagekitwp-lp-image-ticket"><?php echo $pg_img; ?></div>
                    <?php endif; endif; ?>
                </div>
                <?php endif; ?>
                <div class="stagekitwp-lp-info-col">
                    <?php if ( $show_title ) : ?>
                        <<?php echo $show_heading_tag; ?> class="stagekitwp-lp-title"><?php echo $show_title; ?></<?php echo $show_heading_tag; ?>>
                        <hr class="stagekitwp-lp-title-rule">
                    <?php endif; ?>
                    <?php echo $info_html; ?>
                </div>
            </div>
        </div>
        <?php break;

        // ───────────────────────────────────────────────────────────────────
        case 'hero':
        // Full-width cinematic hero: image as banner with gradient + title overlay
        ?>
        <div class="stagekitwp-landingpage-wrapper stagekitwp-landingpage stagekitwp-lp-layout-hero"<?php echo $wrapper_style; ?>>
            <?php if ( $has_image && $img_url ) : ?>
            <div class="stagekitwp-lp-hero-banner">
                <img src="<?php echo esc_url( $img_url ); ?>" alt="<?php echo esc_attr( $show_title ); ?>" class="stagekitwp-lp-hero-img">
                <div class="stagekitwp-lp-hero-overlay">
                    <?php if ( $show_title ) : ?>
                    <<?php echo $show_heading_tag; ?> class="stagekitwp-lp-hero-title"><?php echo $show_title; ?></<?php echo $show_heading_tag; ?>>
                    <?php endif; ?>
                </div>
            </div>
            <?php elseif ( $show_title ) : ?>
            <div class="stagekitwp-lp-hero-title-bar">
                <<?php echo $show_heading_tag; ?> class="stagekitwp-lp-hero-title-noimg"><?php echo $show_title; ?></<?php echo $show_heading_tag; ?>>
            </div>
            <?php endif; ?>
            <div class="stagekitwp-lp-hero-body">
                <?php if ( $show_program_button ) :
                    $pg_hero = stagekitwp_lp_program_button_html( $show_id, $buttonformat );
                    if ( $pg_hero ) {
                        echo $pg_hero;
                    }
                endif; ?>
                <?php echo $info_html; ?>
            </div>
            <?php echo $banner_html; ?>
        </div>
        <?php break;

        // ───────────────────────────────────────────────────────────────────
        case 'programme':
        // Classic theatre programme: image top-left, structured two-column details
        ?>
        <div class="stagekitwp-landingpage-wrapper stagekitwp-landingpage stagekitwp-lp-layout-programme"<?php echo $wrapper_style; ?>>
            <div class="stagekitwp-lp-header-bar"></div>
            <div class="stagekitwp-lp-prog-header">
                <?php if ( $has_image && $img_url ) : ?>
                <div class="stagekitwp-lp-prog-poster">
                    <img src="<?php echo esc_url( $img_url ); ?>" alt="<?php echo $show_title; ?>">
                    <?php if ( isset( $fields_data['ticket_url'] ) ) :
                        $tk_img = stagekitwp_lp_ticket_html( $show_id, $use_button, $buttonformat ); if ( $tk_img ) : ?>
                    <div class="stagekitwp-lp-image-ticket"><?php echo $tk_img; ?></div>
                    <?php endif; endif; ?>
                </div>
                <?php endif; ?>
                <div class="stagekitwp-lp-prog-titleblock">
                    <?php if ( $show_title ) : ?>
                    <<?php echo $show_heading_tag; ?> class="stagekitwp-lp-title"><?php echo $show_title; ?></<?php echo $show_heading_tag; ?>>
                    <hr class="stagekitwp-lp-title-rule">
                    <?php endif; ?>
                    <?php
                    // Crew fields in a compact two-column grid in the header
                    $crew_map = [ 'author'=>'author','sub_authors'=>'sub_authors','director'=>'director',
                                  'associate_director'=>'assoc_dir','producer'=>'producer','stage_manager'=>'stage_manager' ];
                    foreach ( $crew_map as $field => $hkey ) {
                        if ( isset( $fields_data[ $field ] ) ) {
                            echo '<div class="stagekitwp-lp-field stagekitwp-lp-field-' . esc_attr( $field ) . '">'
                               . '<span class="stagekitwp-lp-label">' . stagekitwp_lp_heading( $hkey, $show_id ) . '</span>'
                               . '<span class="stagekitwp-lp-value">' . $fields_data[ $field ] . '</span>'
                               . '</div>';
                        }
                    }
                    ?>
                </div>
            </div>
            <div class="stagekitwp-lp-prog-body">
                <?php
                // Synopsis full-width
                if ( isset( $fields_data['synopsis'] ) ) {
                    echo '<div class="stagekitwp-lp-prog-synopsis">'
                       . '<h2 class="stagekitwp-lp-section-heading">' . stagekitwp_lp_heading( 'synopsis', $show_id ) . '</h2>'
                       . '<div class="stagekitwp-lp-value">' . $fields_data['synopsis'] . '</div>'
                       . '</div>';
                }
                // Dates + venue side by side
                $has_dates = isset( $fields_data['show_dates'] );
                $has_venue = isset( $fields_data['venue'] );
                if ( $has_dates || $has_venue ) {
                    echo '<div class="stagekitwp-lp-prog-logistics">';
                    if ( $has_dates ) {
                        echo '<div class="stagekitwp-lp-prog-logistics-item">'
                           . '<h3 class="stagekitwp-lp-section-heading">' . stagekitwp_lp_heading( 'show_dates', $show_id ) . '</h3>'
                           . '<div class="stagekitwp-lp-value">' . $fields_data['show_dates'] . '</div>'
                           . '</div>';
                    }
                    if ( $has_venue ) {
                        echo '<div class="stagekitwp-lp-prog-logistics-item">'
                           . '<h3 class="stagekitwp-lp-section-heading">' . stagekitwp_lp_heading( 'venue', $show_id ) . '</h3>'
                           . '<div class="stagekitwp-lp-value">' . $fields_data['venue'] . '</div>'
                           . '</div>';
                    }
                    echo '</div>';
                }
                // Cast
                foreach ( [ 'cast', 'castwithbio' ] as $f ) {
                    if ( isset( $fields_data[ $f ] ) ) {
                        echo '<div class="stagekitwp-lp-prog-cast">'
                           . '<h2 class="stagekitwp-lp-section-heading">' . stagekitwp_lp_heading( 'cast', $show_id ) . '</h2>'
                           . '<div class="stagekitwp-lp-value">' . $fields_data[ $f ] . '</div>'
                           . '</div>';
                        break;
                    }
                }
                // Ticket
                if ( isset( $fields_data['ticket_url'] ) ) {
                    $tk = stagekitwp_lp_ticket_html( $show_id, $use_button, $buttonformat );
                    if ( $tk ) { echo '<div class="stagekitwp-lp-prog-ticket">' . $tk . '</div>'; }
                }
                ?>
            </div>
            <?php echo $banner_html; ?>
        </div>
        <?php break;

        // ───────────────────────────────────────────────────────────────────
        case 'minimal':
        // Clean single-column, no card chrome
        ?>
        <div class="stagekitwp-landingpage-wrapper stagekitwp-landingpage stagekitwp-lp-layout-minimal"<?php echo $wrapper_style; ?>>
            <?php if ( $show_title ) : ?>
            <<?php echo $show_heading_tag; ?> class="stagekitwp-lp-title"><?php echo $show_title; ?></<?php echo $show_heading_tag; ?>>
            <hr class="stagekitwp-lp-title-rule">
            <?php endif; ?>
            <?php if ( $has_image && $img_url ) : ?>
            <div class="stagekitwp-lp-minimal-image">
                <img src="<?php echo esc_url( $img_url ); ?>" alt="<?php echo $show_title; ?>">
                <?php if ( isset( $fields_data['ticket_url'] ) ) :
                    $tk_img = stagekitwp_lp_ticket_html( $show_id, $use_button, $buttonformat ); if ( $tk_img ) : ?>
                <div class="stagekitwp-lp-image-ticket"><?php echo $tk_img; ?></div>
                <?php endif; endif; ?>
            </div>
            <?php endif; ?>
            <div class="stagekitwp-lp-minimal-body">
                <?php echo $info_html; ?>
            </div>
        </div>
        <?php break;
    }
    return ob_get_clean();
}

// Register the shortcode
add_shortcode('stagekitwp_landingpage', 'stagekitwp_shortcode_landingpage');