<?php
/**
 * Landing Page Shortcode
 * Displays detailed information about a specific show
 * 
 * SHORTCODE NAME: tm_landingpage
 * PURPOSE: Displays comprehensive information about a specific theatre show
 * 
 * USAGE:
 * [tm_landingpage show_id="current"]
 * [tm_landingpage show_id="123"]
 * [tm_landingpage show_id="123" field_list="show_name,show_image,author,director,synopsis,castwithbio,venue"]
 * 
 * PARAMETERS:
 * - show_id (required): Show ID or "current" to display the current show (default: "current")
 * - field_list (optional): Comma-separated list of fields in display order (default: all fields)
 * - castcols (optional): Number of columns for castwithbio field, 1-6 (default: 3)
 * - urlbutton (optional): Display ticket URL as button "true" or "false" (default: "false")
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
 * Produced by: [producer]
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
function tm_get_current_show() {
    $today_ts = strtotime(date('Y-m-d'));
    $shows = get_posts([
        'post_type' => 'show',
        'posts_per_page' => -1
    ]);

    foreach ($shows as $show) {
        $season_id = get_post_meta($show->ID, '_tm_show_season', true);
        if (!$season_id) continue;

        $season = get_post($season_id);
        if (!$season) continue;

        $start_raw = get_post_meta($season_id, '_tm_season_start_date', true);
        $end_raw = get_post_meta($season_id, '_tm_season_end_date', true);

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
function tm_render_landingpage_field($show_id, $field_name, $hard_breaks = true, $atts = array()) {
    $output = '';
    
    // Extract color attributes if available
    $heading_color = isset($atts['heading_color']) ? sanitize_hex_color($atts['heading_color']) ?: '#1a1a1a' : '#1a1a1a';
    $accent_color = isset($atts['accent_color']) ? sanitize_hex_color($atts['accent_color']) ?: '#0073aa' : '#0073aa';
    
    switch ($field_name) {
        case 'show_name':
            $title = get_the_title($show_id);
            if ($title) {
                $output .= '<h2 style="color: ' . esc_attr($heading_color) . '; margin: 0 0 10px 0;">' . esc_html($title) . '</h2>';
            }
            break;

        case 'show_image':
            // Images always output as HTML regardless of hard_breaks setting
            $image_value = get_post_meta($show_id, '_tm_show_sm_image', true);
            if ($image_value) {
                $image_url = tm_get_image_url($image_value);
                if ($image_url) {
                    $output .= '<img src="' . esc_url($image_url) . '" alt="' . esc_attr(get_the_title($show_id)) . '" />';
                }
            }
            break;

        case 'author':
            $value = get_post_meta($show_id, '_tm_show_author', true);
            if ($value) {
                $output .= 'Written by: ' . esc_html($value);
            }
            break;

        case 'sub_authors':
            $value = get_post_meta($show_id, '_tm_show_sub_authors', true);
            if ($value) {
                $output .= 'Co-Written by: ' . esc_html($value);
            }
            break;

        case 'director':
            $value = get_post_meta($show_id, '_tm_show_director', true);
            if ($value) {
                $output .= 'Directed by: ' . esc_html($value);
            }
            break;

        case 'associate_director':
            $value = get_post_meta($show_id, '_tm_show_associate_director', true);
            if ($value) {
                $output .= 'Assistant Directed by: ' . esc_html($value);
            }
            break;

        case 'producer':
            $value = get_post_meta($show_id, '_tm_show_producer', true);
            if ($value) {
                $output .= 'Produced by: ' . esc_html($value);
            }
            break;

        case 'stage_manager':
            $value = get_post_meta($show_id, '_tm_show_stage_manager', true);
            if ($value) {
                $output .= 'Stage managed by: ' . esc_html($value);
            }
            break;

        case 'synopsis':
            $value = get_post_meta($show_id, '_tm_show_synopsis', true);
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
            $value = get_post_meta($show_id, '_tm_show_show_dates', true);
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
            $url = trim((string) get_post_meta($show_id, '_tm_show_tickets_url', true));
            if ($url) {
                if (!preg_match('#^https?://#i', $url)) {
                    $url = 'https://' . ltrim($url, '/');
                }

                $url = esc_url_raw($url);
                if (!$url) {
                    break;
                }

                $use_button = strtolower($atts['urlbutton']) === 'true' || $atts['urlbutton'] === '1';
                if ($use_button && $hard_breaks) {
                    // Display as a button
                    $allowed_formats = array('default', 'modern', 'minimal', 'outline', 'gradient', 'prominent', 'success', 'ghost', 'glass');
                    $buttonformat = sanitize_key($atts['buttonformat']);
                    if (!in_array($buttonformat, $allowed_formats, true)) {
                        $buttonformat = 'default';
                    }
                    $button_class = 'tm-url-button tm-button-' . esc_attr($buttonformat);
                    $output .= '<a href="' . esc_url($url) . '" class="' . $button_class . '" target="_blank" rel="noopener noreferrer">Get Tickets</a>';
                } else {
                    // Display as regular link
                    if ($hard_breaks) {
                        $output .= '<a href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer">' . esc_html($url) . '</a>';
                    } else {
                        // Just the URL text when hard_breaks is false
                        $output .= esc_html($url);
                    }
                }
            }
            break;

        case 'cast':
            // Get all cast members for this show
            $cast_args = [
                'post_type' => 'cast',
                'posts_per_page' => -1,
                'meta_query' => [
                    [
                        'key' => '_tm_cast_show',
                        'value' => $show_id,
                        'compare' => '='
                    ]
                ]
            ];
            $cast_members = get_posts($cast_args);

            if ($cast_members) {
                if ($hard_breaks) {
                    // Formatted HTML output with list
                    $output .= '<ul class="tm-landingpage-cast-list">';
                    foreach ($cast_members as $cast_member) {
                        $output .= '<li class="tm-landingpage-cast-item">';
                        
                        $character = get_post_meta($cast_member->ID, '_tm_cast_character_name', true);
                        $actor = get_post_meta($cast_member->ID, '_tm_cast_actor_name', true);
                        $bio = get_the_excerpt($cast_member->ID);
                        
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
                        $character = get_post_meta($cast_member->ID, '_tm_cast_character_name', true);
                        $actor = get_post_meta($cast_member->ID, '_tm_cast_actor_name', true);
                        
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
                        'key' => '_tm_cast_show',
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

                $cast_image_size = isset($atts['cast_image_size']) ? sanitize_key($atts['cast_image_size']) : 'large';
                $cast_image_sizes = array(
                    'small' => '50%',
                    'medium' => '75%',
                    'large' => '100%'
                );

                if (!isset($cast_image_sizes[$cast_image_size])) {
                    $cast_image_size = 'medium';
                }

                $cast_img_width = $cast_image_sizes[$cast_image_size];
                
                // Pass castcols as CSS custom property: respects castcols at desktop, 
                // constrains to 2 columns on tablet (max-width: 1024px), 
                // and 1 column on mobile (max-width: 480px)
                $output .= '<div class="tm-landingpage-castwithbio" style="--cast-cols: ' . intval($castcols) . '; --cast-image-width: ' . esc_attr($cast_img_width) . ';">';
                
                foreach ($cast_members as $cast_member) {
                    $character = get_post_meta($cast_member->ID, '_tm_cast_character_name', true);
                    $actor = get_post_meta($cast_member->ID, '_tm_cast_actor_name', true);
                    $picture = get_post_meta($cast_member->ID, '_tm_cast_picture', true);
                    $bio = get_post_meta($cast_member->ID, '_tm_cast_bio', true);

                    $output .= '<div class="tm-cast-column">';
                    
                    // Image
                    if ($picture) {
                        $picture_url = tm_get_image_url($picture);
                        if ($picture_url) {
                            $output .= '<img src="' . esc_url($picture_url) . '" alt="' . esc_attr($actor ?: $character) . '" class="tm-cast-image">';
                        }
                    }
                    
                    // Character Name
                    if ($character) {
                        $output .= '<h4 class="tm-cast-character">' . esc_html($character) . '</h4>';
                    }
                    
                    // Actor Name
                    if ($actor) {
                        $output .= '<p class="tm-cast-actor">Played by ' . esc_html($actor) . '</p>';
                    }
                    
                    // Bio
                    if ($bio) {
                        $output .= '<p class="tm-cast-bio">' . wp_kses_post($bio) . '</p>';
                    }
                    
                    $output .= '</div>';
                }
                
                $output .= '</div>';
            }
            break;

        case 'venue':
            $venue_id = get_post_meta($show_id, '_tm_show_venue', true);
            if ($venue_id) {
                $venue = get_post($venue_id);
                if ($venue) {
                    if ($hard_breaks) {
                        // Formatted HTML output with div
                        $output .= '<div class="tm-landingpage-venue">';
                        $output .= '<strong>' . esc_html($venue->post_title) . '</strong>';
                        
                        $address = get_post_meta($venue_id, '_tm_venue_address', true);
                        if ($address) {
                            $output .= '<br>' . wp_kses_post($address);
                        }
                        
                        $phone = get_post_meta($venue_id, '_tm_venue_phone', true);
                        if ($phone) {
                            $output .= '<br>' . esc_html($phone);
                        }
                        
                        $website = get_post_meta($venue_id, '_tm_venue_website', true);
                        if ($website) {
                            $output .= '<br><a href="' . esc_url($website) . '" target="_blank">' . esc_html($website) . '</a>';
                        }
                        
                        $output .= '</div>';
                    } else {
                        // Plain text output when hard_breaks is false
                        $venue_parts = [esc_html($venue->post_title)];
                        
                        $address = get_post_meta($venue_id, '_tm_venue_address', true);
                        if ($address) {
                            $venue_parts[] = wp_strip_all_tags($address);
                        }
                        
                        $phone = get_post_meta($venue_id, '_tm_venue_phone', true);
                        if ($phone) {
                            $venue_parts[] = esc_html($phone);
                        }
                        
                        $website = get_post_meta($venue_id, '_tm_venue_website', true);
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
function tm_shortcode_landingpage($atts) {
    $atts = shortcode_atts([
        'show_id' => 'current',
        'field_list' => 'show_name,show_image,author,director,producer,stage_manager,synopsis,show_dates,ticket_url,cast,venue',
        'hard_breaks' => 'true',
        'castcols' => '3',
        'cast_image_size' => 'large',
        'urlbutton' => 'false',
        'buttonformat' => 'default',
        'text_align' => 'inherit',
        'font_family' => 'inherit',
        'text_size' => 'inherit',
        'text_color' => '#333333',
        'bg_color' => '#ffffff',
        'accent_color' => '#0073aa',
        'heading_color' => '#1a1a1a'
    ], $atts, 'tm_landingpage');

    // Determine which show to display
    $show_id = null;
    if (strtolower($atts['show_id']) === 'current') {
        $show_id = tm_get_current_show();
        if (!$show_id) {
            return '<!-- No current show available -->';
        }
    } else {
        $show_id = intval($atts['show_id']);
        if ($show_id <= 0) {
            return '<!-- Invalid show ID -->';
        }
        // Verify the show exists
        $show = get_post($show_id);
        if (!$show || $show->post_type !== 'show') {
            return '<!-- Show not found -->';
        }
    }

    // Parse field list
    $field_list = array_map('trim', explode(',', $atts['field_list']));
    $field_list = array_filter($field_list);

    if (empty($field_list)) {
        return '<!-- No fields specified -->';
    }

    // Parse hard_breaks parameter
    $hard_breaks = strtolower($atts['hard_breaks']) === 'true' || $atts['hard_breaks'] === '1';

    // Sanitize color parameters
    $text_color = sanitize_hex_color($atts['text_color']) ?: '#333333';
    $bg_color = sanitize_hex_color($atts['bg_color']) ?: '#ffffff';
    $accent_color = sanitize_hex_color($atts['accent_color']) ?: '#0073aa';
    $heading_color = sanitize_hex_color($atts['heading_color']) ?: '#1a1a1a';

    $text_align = sanitize_key($atts['text_align']);
    $allowed_alignments = array('inherit', 'left', 'center', 'right', 'justify');
    if (!in_array($text_align, $allowed_alignments, true)) {
        $text_align = 'inherit';
    }

    $font_family_key = sanitize_key($atts['font_family']);
    $font_families = array(
        'inherit' => 'inherit',
        'system' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
        'sans' => 'Arial, Helvetica, sans-serif',
        'serif' => 'Georgia, "Times New Roman", serif',
        'georgia' => 'Georgia, serif',
        'times' => '"Times New Roman", Times, serif',
        'trebuchet' => '"Trebuchet MS", Helvetica, sans-serif'
    );
    if (!isset($font_families[$font_family_key])) {
        $font_family_key = 'inherit';
    }
    $font_family_css = $font_families[$font_family_key];

    $text_size_key = sanitize_key($atts['text_size']);
    $text_sizes = array(
        'inherit' => 'inherit',
        'small' => '0.9rem',
        'medium' => '1rem',
        'large' => '1.15rem',
        'xlarge' => '1.3rem'
    );
    if (!isset($text_sizes[$text_size_key])) {
        $text_size_key = 'inherit';
    }
    $text_size_css = $text_sizes[$text_size_key];

    // Render the landing page
    if ($hard_breaks) {
        // With hard breaks: wrap in divs for styling control
        $style = sprintf(
            'style="color: %s; background-color: %s; --tm-accent-color: %s; --tm-heading-color: %s; text-align: %s; font-family: %s; font-size: %s;"',
            esc_attr($text_color),
            esc_attr($bg_color),
            esc_attr($accent_color),
            esc_attr($heading_color),
            esc_attr($text_align),
            esc_attr($font_family_css),
            esc_attr($text_size_css)
        );
        $output = '<div class="tm-landingpage-wrapper tm-landingpage" ' . $style . '>';

        foreach ($field_list as $field) {
            $field = sanitize_key($field);
            $field_output = tm_render_landingpage_field($show_id, $field, $hard_breaks, $atts);

            if ($field_output) {
                // Images don't need field div wrapper - they're self-contained
                if ($field === 'show_image') {
                    $output .= $field_output;
                } else {
                    $output .= '<div class="tm-landingpage-field tm-landingpage-field-' . esc_attr($field) . '">' . $field_output . '<br></div>';
                }
            }
        }

        $output .= '</div>';
    } else {
        // Without hard breaks: output raw content
        $output = '';
        $has_image = false;
        $image_output = '';
        
        foreach ($field_list as $field) {
            $field = sanitize_key($field);
            $field_output = tm_render_landingpage_field($show_id, $field, $hard_breaks, $atts);

            if ($field_output) {
                // Track image output separately
                if ($field === 'show_image') {
                    $has_image = true;
                    $image_output = $field_output;
                } else {
                    $output .= $field_output;
                }
            }
        }
        
        // Handle image placement based on content
        if ($has_image) {
            if (count($field_list) == 1) {
                // Image only: wrap in inline-block span to stay in text flow
                $output = '<span class="tm-landingpage-image-inline">' . $image_output . '</span>';
            } else {
                // Mixed content: image + text - wrap everything in centered div
                $output = '<div class="tm-landingpage-mixed" style="text-align: ' . esc_attr($text_align) . '; font-family: ' . esc_attr($font_family_css) . '; font-size: ' . esc_attr($text_size_css) . ';">' . $image_output . $output . '</div>';
            }
        }
    }

    return $output;
}

// Register the shortcode
add_shortcode('tm_landingpage', 'tm_shortcode_landingpage');