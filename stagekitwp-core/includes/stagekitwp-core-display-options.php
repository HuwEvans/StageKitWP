<?php
/**
 * StageKitWP — Display Options (with Light/Dark side-by-side pickers)
 *
 * Replaces the settings registration and callback functions previously in
 * admin-menu.php. Every colour field now renders two pickers side-by-side:
 *   ☀  Light (existing option key, e.g. stagekitwp_board_member_bg_color)
 *   🌙  Dark  (new option key,      e.g. stagekitwp_board_member_bg_color_dark)
 *
 * The dark column is only shown when the StageKitWP Theme is active
 * AND the frontend switcher is enabled — detected via stagekitwp_theme_is_active()
 * from theme-integration.php (always loaded before this file).
 */

defined( 'ABSPATH' ) || exit;

// ─────────────────────────────────────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────────────────────────────────────

/** True when the TM theme is active and the dark switcher is on. */
function stagekitwp_dark_mode_admin_enabled() {
    if ( ! function_exists( 'stagekitwp_theme_is_active' ) ) { return false; }
    if ( ! stagekitwp_theme_is_active() ) { return false; }
    return (bool) stagekitwp_get_theme_mod( 'stagekitwp_enable_frontend_switcher', false );
}

// ─────────────────────────────────────────────────────────────────────────────
// Enqueue admin assets on Display Options page
// (merged into main stagekitwp_enqueue_admin_assets in functions.php — just CSS/JS)
// ─────────────────────────────────────────────────────────────────────────────

// ─────────────────────────────────────────────────────────────────────────────
// Settings registration
// ─────────────────────────────────────────────────────────────────────────────

function stagekitwp_register_display_settings() {
    $tabs = ['board_member','advertiser','sponsor','contributor','testimonials',
             'season','show','auditions','awards','venues','tickets','landing_page'];

    $dark_enabled = stagekitwp_dark_mode_admin_enabled();

    foreach ( $tabs as $tab ) {
        $section_id = "stagekitwp_{$tab}_section";
        $page       = "stagekitwp-display-options-{$tab}";
        $group      = "stagekitwp_display_options_{$tab}";
        $label      = ucfirst( str_replace( '_', ' ', $tab ) );

        // Section description callback — notes for show and season tabs.
        $section_cb = null;
        if ( $tab === 'show' ) {
            $section_cb = function() {
                echo '<p class="description" style="margin-bottom:12px">';
                echo 'These settings apply to <code>[stagekitwp_shows]</code>, <code>[stagekitwp_show_cast]</code>, '
                   . '<code>[stagekitwp_season_shows]</code>, <code>[stagekitwp_past_shows]</code>, and <code>[stagekitwp_programs]</code>.';
                echo '</p>';
            };
        } elseif ( $tab === 'season' ) {
            $section_cb = function() {
                echo '<p class="description" style="margin-bottom:12px">';
                echo 'These settings apply to <strong><code>[stagekitwp_seasons]</code></strong> (season cards &amp; field layout) '
                   . 'and <strong><code>[stagekitwp_season_shows]</code></strong> (season banner + show grid). '
                   . 'The Show tab controls show-level colours within <code>[stagekitwp_season_shows]</code>.';
                echo '</p>';
            };
        } elseif ( $tab === 'auditions' ) {
            $section_cb = function() {
                echo '<p class="description" style="margin-bottom:12px">';
                echo 'These settings apply to <code>[stagekitwp_auditions]</code> in all three layouts '
                   . '(<code>list</code>, <code>cards</code>, <code>compact</code>). '
                   . '<strong>H2 Color</strong> styles show titles; '
                   . '<strong>H3 Color</strong> styles the accent, date labels, and the card date badge. '
                   . 'Dark-mode values are used automatically when the StageKitWP Theme dark toggle is on.';
                echo '</p>';
            };
        } elseif ( $tab === 'landing_page' ) {
            $section_cb = function() {
                echo '<p class="description" style="margin-bottom:12px">';
                echo 'These settings apply to <code>[stagekitwp_landingpage]</code> — the per-show landing page rendered at each Show\'s URL. '
                   . '<strong>Heading Color</strong> controls the show title. '
                   . '<strong>H2 Color</strong> controls the accent/label colour. '
                   . '<strong>Background Color</strong> also sets the card background.';
                echo '</p>';
            };
        }
        add_settings_section( $section_id, $label . ' Display Settings', $section_cb, $page );

        // ── Colour fields (side-by-side when dark mode admin is enabled) ──
        // h1 used by landing_page; h2/h3 used by awards, show, season, venues;
        // h4/h5/h6 are never read by any shortcode so are not registered.
        $color_fields = [
            "stagekitwp_{$tab}_bg_color"     => 'Background Color',
            "stagekitwp_{$tab}_text_color"   => 'Text Color',
            "stagekitwp_{$tab}_border_color" => 'Border Color',
        ];
        if ( in_array( $tab, [ 'landing_page' ], true ) ) {
            $color_fields["stagekitwp_{$tab}_h1_color"] = 'H1 Color (Show Title)';
        }
        if ( in_array( $tab, [ 'awards', 'show', 'season', 'venues', 'auditions', 'landing_page' ], true ) ) {
            $color_fields["stagekitwp_{$tab}_h2_color"] = 'H2 Color'
                . ( $tab === 'landing_page' ? ' (Accent / Label)' : ( $tab === 'auditions' ? ' (Show Title)' : '' ) );
        }
        if ( in_array( $tab, [ 'awards', 'show', 'season', 'venues', 'auditions' ], true ) ) {
            $color_fields["stagekitwp_{$tab}_h3_color"] = 'H3 Color'
                . ( $tab === 'auditions' ? ' (Accent / Labels)' : '' );
        }
        if ( $tab === 'tickets' ) {
            $color_fields["stagekitwp_{$tab}_button_color"]       = 'Button / Accent Color';
            $color_fields["stagekitwp_{$tab}_button_hover_color"] = 'Button Hover Color';
            // button_color_dark & button_hover_color_dark are auto-registered
            // below via the $color_fields loop (same as all other colour fields).
        }

        foreach ( $color_fields as $option_key => $field_label ) {
            register_setting( $group, $option_key );
            if ( $dark_enabled ) {
                register_setting( $group, $option_key . '_dark' );
            }
            add_settings_field(
                $option_key,
                $field_label,
                'stagekitwp_color_pair_callback',
                $page,
                $section_id,
                [ 'option_key' => $option_key, 'dark_enabled' => $dark_enabled ]
            );
        }

        // ── Non-colour fields ─────────────────────────────────────────────
        add_settings_field( "stagekitwp_{$tab}_base_font", 'Base Font Family', 'stagekitwp_font_family_callback', $page, $section_id, [ 'label_for' => "stagekitwp_{$tab}_base_font" ] );
        register_setting( $group, "stagekitwp_{$tab}_base_font" );

        if ( in_array( $tab, [ 'sponsor', 'contributor' ], true ) ) {
            $level_defaults = stagekitwp_level_labels_for_context( $tab );
            foreach ( $level_defaults as $level => $default_label ) {
                $level_slug  = strtolower( $level );
                $option_key  = "stagekitwp_{$tab}_level_label_{$level_slug}";
                $field_label = $level . ' Header Label';

                add_settings_field(
                    $option_key,
                    $field_label,
                    'stagekitwp_level_label_callback',
                    $page,
                    $section_id,
                    [
                        'label_for' => $option_key,
                        'default'   => $default_label,
                    ]
                );

                register_setting( $group, $option_key, 'sanitize_text_field' );
            }
        }

        if ( $tab === 'landing_page' ) {
            add_settings_field( 'stagekitwp_landing_page_heading_font', 'Heading Font',   'stagekitwp_lp_heading_font_callback', $page, $section_id, [ 'label_for' => 'stagekitwp_landing_page_heading_font' ] );
            register_setting( $group, 'stagekitwp_landing_page_heading_font', 'sanitize_key' );

            add_settings_field( 'stagekitwp_landing_page_heading_size', 'Heading Size',   'stagekitwp_lp_size_callback', $page, $section_id, [ 'option' => 'stagekitwp_landing_page_heading_size', 'label' => 'Heading' ] );
            register_setting( $group, 'stagekitwp_landing_page_heading_size', 'sanitize_key' );

            add_settings_field( 'stagekitwp_landing_page_text_size', 'Body Text Size', 'stagekitwp_lp_size_callback', $page, $section_id, [ 'option' => 'stagekitwp_landing_page_text_size', 'label' => 'Body text' ] );
            register_setting( $group, 'stagekitwp_landing_page_text_size', 'sanitize_key' );

            add_settings_field( 'stagekitwp_landing_page_text_align', 'Text Alignment', 'stagekitwp_lp_text_align_callback', $page, $section_id, [ 'label_for' => 'stagekitwp_landing_page_text_align' ] );
            register_setting( $group, 'stagekitwp_landing_page_text_align', 'sanitize_key' );
        }

        add_settings_field( "stagekitwp_{$tab}_border_width", 'Border Width (px)', 'stagekitwp_text_input_callback', $page, $section_id, [ 'label_for' => "stagekitwp_{$tab}_border_width" ] );
        register_setting( $group, "stagekitwp_{$tab}_border_width" );

        add_settings_field( "stagekitwp_{$tab}_rounded", 'Rounded Corners', 'stagekitwp_checkbox_callback', $page, $section_id, [ 'label_for' => "stagekitwp_{$tab}_rounded" ] );
        register_setting( $group, "stagekitwp_{$tab}_rounded" );

        add_settings_field( "stagekitwp_{$tab}_radius", 'Border Radius (px)', 'stagekitwp_text_input_callback', $page, $section_id, [ 'label_for' => "stagekitwp_{$tab}_radius" ] );
        register_setting( $group, "stagekitwp_{$tab}_radius" );

        add_settings_field( "stagekitwp_{$tab}_shadow", 'Box Shadow', 'stagekitwp_checkbox_callback', $page, $section_id, [ 'label_for' => "stagekitwp_{$tab}_shadow" ] );
        register_setting( $group, "stagekitwp_{$tab}_shadow" );

        add_settings_field( "stagekitwp_{$tab}_disable_border", 'Disable Border', 'stagekitwp_checkbox_callback', $page, $section_id, [ 'label_for' => "stagekitwp_{$tab}_disable_border" ] );
        register_setting( $group, "stagekitwp_{$tab}_disable_border" );

        if ( $tab === 'testimonials' ) {
            add_settings_field( "stagekitwp_{$tab}_rating_symbol", 'Rating Symbol', 'stagekitwp_rating_symbol_callback', $page, $section_id, [ 'label_for' => "stagekitwp_{$tab}_rating_symbol" ] );
            register_setting( $group, "stagekitwp_{$tab}_rating_symbol" );

            add_settings_field( "stagekitwp_{$tab}_layout", 'Default Layout', 'stagekitwp_testimonials_layout_callback', $page, $section_id, [ 'label_for' => "stagekitwp_{$tab}_layout" ] );
            register_setting( $group, "stagekitwp_{$tab}_layout" );

            add_settings_field( "stagekitwp_{$tab}_mode", 'Default Mode', 'stagekitwp_testimonials_mode_callback', $page, $section_id, [ 'label_for' => "stagekitwp_{$tab}_mode" ] );
            register_setting( $group, "stagekitwp_{$tab}_mode" );

            add_settings_field( "stagekitwp_{$tab}_image_width", 'Image Width (px)', 'stagekitwp_text_input_callback', $page, $section_id, [ 'label_for' => "stagekitwp_{$tab}_image_width" ] );
            register_setting( $group, "stagekitwp_{$tab}_image_width" );

            add_settings_field( "stagekitwp_{$tab}_image_height", 'Image Height (px)', 'stagekitwp_text_input_callback', $page, $section_id, [ 'label_for' => "stagekitwp_{$tab}_image_height" ] );
            register_setting( $group, "stagekitwp_{$tab}_image_height" );

            add_settings_field( "stagekitwp_{$tab}_image_fit", 'Image Fit', 'stagekitwp_testimonials_image_fit_callback', $page, $section_id, [ 'label_for' => "stagekitwp_{$tab}_image_fit" ] );
            register_setting( $group, "stagekitwp_{$tab}_image_fit" );

            add_settings_field( "stagekitwp_{$tab}_image_position", 'Image Position', 'stagekitwp_testimonials_image_position_callback', $page, $section_id, [ 'label_for' => "stagekitwp_{$tab}_image_position" ] );
            register_setting( $group, "stagekitwp_{$tab}_image_position" );

            add_settings_field( "stagekitwp_{$tab}_image_focus", 'Image Focus', 'stagekitwp_testimonials_image_focus_callback', $page, $section_id, [ 'label_for' => "stagekitwp_{$tab}_image_focus" ] );
            register_setting( $group, "stagekitwp_{$tab}_image_focus" );

            add_settings_field( "stagekitwp_{$tab}_text_overlay", 'Enable Text Overlay On Image', 'stagekitwp_checkbox_callback', $page, $section_id, [ 'label_for' => "stagekitwp_{$tab}_text_overlay" ] );
            register_setting( $group, "stagekitwp_{$tab}_text_overlay" );

            add_settings_field( "stagekitwp_{$tab}_image_opacity", 'Overlay Image Opacity (0.1 - 1)', 'stagekitwp_text_input_callback', $page, $section_id, [ 'label_for' => "stagekitwp_{$tab}_image_opacity" ] );
            register_setting( $group, "stagekitwp_{$tab}_image_opacity" );

            add_settings_field( "stagekitwp_{$tab}_show_name", 'Show Name', 'stagekitwp_checkbox_callback', $page, $section_id, [ 'label_for' => "stagekitwp_{$tab}_show_name" ] );
            register_setting( $group, "stagekitwp_{$tab}_show_name" );

            add_settings_field( "stagekitwp_{$tab}_show_comment", 'Show Comment', 'stagekitwp_checkbox_callback', $page, $section_id, [ 'label_for' => "stagekitwp_{$tab}_show_comment" ] );
            register_setting( $group, "stagekitwp_{$tab}_show_comment" );

            add_settings_field( "stagekitwp_{$tab}_show_rating", 'Show Rating', 'stagekitwp_checkbox_callback', $page, $section_id, [ 'label_for' => "stagekitwp_{$tab}_show_rating" ] );
            register_setting( $group, "stagekitwp_{$tab}_show_rating" );

            add_settings_field( "stagekitwp_{$tab}_show_show", 'Show Associated Show', 'stagekitwp_checkbox_callback', $page, $section_id, [ 'label_for' => "stagekitwp_{$tab}_show_show" ] );
            register_setting( $group, "stagekitwp_{$tab}_show_show" );

            add_settings_field( "stagekitwp_{$tab}_show_date", 'Show Date', 'stagekitwp_checkbox_callback', $page, $section_id, [ 'label_for' => "stagekitwp_{$tab}_show_date" ] );
            register_setting( $group, "stagekitwp_{$tab}_show_date" );

            add_settings_field( "stagekitwp_{$tab}_show_media", 'Show Media', 'stagekitwp_checkbox_callback', $page, $section_id, [ 'label_for' => "stagekitwp_{$tab}_show_media" ] );
            register_setting( $group, "stagekitwp_{$tab}_show_media" );

            add_settings_field( "stagekitwp_{$tab}_show_name_placement", 'Show Name Placement', 'stagekitwp_testimonials_show_name_placement_callback', $page, $section_id, [ 'label_for' => "stagekitwp_{$tab}_show_name_placement" ] );
            register_setting( $group, "stagekitwp_{$tab}_show_name_placement" );

            add_settings_field( "stagekitwp_{$tab}_tag_icon_source", 'Show Tag Icon Source', 'stagekitwp_testimonials_tag_icon_source_callback', $page, $section_id, [ 'label_for' => "stagekitwp_{$tab}_tag_icon_source" ] );
            register_setting( $group, "stagekitwp_{$tab}_tag_icon_source" );

            add_settings_field( "stagekitwp_{$tab}_tag_icon_url", 'Custom Tag Icon URL', 'stagekitwp_text_input_callback', $page, $section_id, [ 'label_for' => "stagekitwp_{$tab}_tag_icon_url" ] );
            register_setting( $group, "stagekitwp_{$tab}_tag_icon_url" );

            add_settings_field( "stagekitwp_{$tab}_tag_icon_size", 'Tag Icon Size (px)', 'stagekitwp_text_input_callback', $page, $section_id, [ 'label_for' => "stagekitwp_{$tab}_tag_icon_size" ] );
            register_setting( $group, "stagekitwp_{$tab}_tag_icon_size" );

            add_settings_field( "stagekitwp_{$tab}_reviews_per_show", 'Per Show: Reviews Per Show', 'stagekitwp_text_input_callback', $page, $section_id, [ 'label_for' => "stagekitwp_{$tab}_reviews_per_show" ] );
            register_setting( $group, "stagekitwp_{$tab}_reviews_per_show" );

            add_settings_field( "stagekitwp_{$tab}_review_align", 'Per Show: Review Alignment', 'stagekitwp_testimonials_review_align_callback', $page, $section_id, [ 'label_for' => "stagekitwp_{$tab}_review_align" ] );
            register_setting( $group, "stagekitwp_{$tab}_review_align" );
        }

        if ( $tab === 'board_member' || $tab === 'advertiser' ) {
            add_settings_field( "stagekitwp_{$tab}_grid_columns", 'Grid Columns', 'stagekitwp_grid_columns_callback', $page, $section_id, [ 'label_for' => "stagekitwp_{$tab}_grid_columns" ] );
            register_setting( $group, "stagekitwp_{$tab}_grid_columns" );
        }

        if ( $tab === 'board_member' ) {
            add_settings_field( 'stagekitwp_board_member_photo_size', 'Grid Photo Size (px)', 'stagekitwp_board_member_photo_size_callback', $page, $section_id, [ 'label_for' => 'stagekitwp_board_member_photo_size' ] );
            register_setting( $group, 'stagekitwp_board_member_photo_size' );
        }
    }
}
add_action( 'admin_init', 'stagekitwp_register_display_settings' );

// ─────────────────────────────────────────────────────────────────────────────
// Callbacks
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Side-by-side Light / Dark colour picker pair.
 */
/**
 * Render a single colour picker + its "clear to default" button.
 * The clear button empties the input so the option is saved as '' (empty string),
 * which causes all shortcodes to fall back to the theme / hard-coded defaults.
 */
function stagekitwp_render_color_picker( $id, $name, $value, $extra_class = '' ) {
    $class = 'stagekitwp-color-picker' . ( $extra_class ? ' ' . $extra_class : '' );
    echo '<div class="stagekitwp-color-picker-wrap">';
    echo '<input type="text" class="' . esc_attr( $class ) . '" '
       . 'id="' . esc_attr( $id ) . '" '
       . 'name="' . esc_attr( $name ) . '" '
       . 'value="' . esc_attr( $value ) . '" />';
    echo '<button type="button" '
       . 'class="stagekitwp-color-clear" '
       . 'data-target="' . esc_attr( $id ) . '" '
       . 'title="Clear — revert to theme default" '
       . 'aria-label="Clear colour, revert to theme default"'
       . '>&#x2715;</button>'; // ✕ = × MULTIPLICATION SIGN
    echo '</div>'; // .stagekitwp-color-picker-wrap
}

function stagekitwp_color_pair_callback( $args ) {
    $key          = $args['option_key'];
    $dark_enabled = ! empty( $args['dark_enabled'] );
    $light_val    = get_option( $key, '' );
    $dark_val     = $dark_enabled ? get_option( $key . '_dark', '' ) : '';

    if ( $dark_enabled ) {
        echo '<div class="stagekitwp-color-pair-row">';

        // Light picker
        echo '<div class="stagekitwp-color-pair-cell">';
        echo '<span class="stagekitwp-pair-label">☀ Light</span>';
        stagekitwp_render_color_picker( $key, $key, $light_val );
        echo '</div>';

        // Dark picker
        echo '<div class="stagekitwp-color-pair-cell">';
        echo '<span class="stagekitwp-pair-label">🌙 Dark</span>';
        stagekitwp_render_color_picker( $key . '_dark', $key . '_dark', $dark_val, 'stagekitwp-color-picker-dark' );
        echo '</div>';

        echo '</div>'; // .stagekitwp-color-pair-row
    } else {
        // Single picker (TM theme not active or switcher off)
        stagekitwp_render_color_picker( $key, $key, $light_val );
        echo '<p class="description" style="margin-top:6px;">'
           . '<em>Dark color column appears when the StageKitWP Theme is active and the frontend mode switcher is enabled.</em>'
           . '</p>';
    }
}

/** Legacy single-picker callback — now also renders a clear button. */
function stagekitwp_color_picker_callback_legacy( $args ) {
    $option = get_option( $args['label_for'], '' );
    stagekitwp_render_color_picker( $args['label_for'], $args['label_for'], $option );
}

/** Legacy single-picker callback — kept for any external callers. */
function stagekitwp_color_picker_callback( $args ) {
    $option = get_option( $args['label_for'], '' );
    echo '<input type="text" class="stagekitwp-color-picker" '
       . 'id="' . esc_attr( $args['label_for'] ) . '" '
       . 'name="' . esc_attr( $args['label_for'] ) . '" '
       . 'value="' . esc_attr( $option ) . '" />';
}

function stagekitwp_text_input_callback( $args ) {
    $option = get_option( $args['label_for'], '' );
    echo '<input type="text" '
       . 'id="' . esc_attr( $args['label_for'] ) . '" '
       . 'name="' . esc_attr( $args['label_for'] ) . '" '
       . 'value="' . esc_attr( $option ) . '" '
       . 'style="width:80px;" />';
}

function stagekitwp_level_label_callback( $args ) {
     $key     = $args['label_for'];
     $default = isset( $args['default'] ) ? (string) $args['default'] : '';
     $option  = sanitize_text_field( (string) get_option( $key, '' ) );

     echo '<input type="text" '
         . 'id="' . esc_attr( $key ) . '" '
         . 'name="' . esc_attr( $key ) . '" '
         . 'value="' . esc_attr( $option ) . '" '
         . 'placeholder="' . esc_attr( $default ) . '" '
         . 'class="regular-text" />';

     echo '<p class="description">'
         . 'Leave blank to use the default label: <strong>' . esc_html( $default ) . '</strong>.'
         . '</p>';
}

function stagekitwp_checkbox_callback( $args ) {
    $option = get_option( $args['label_for'] );
    echo '<input type="checkbox" '
       . 'id="' . esc_attr( $args['label_for'] ) . '" '
       . 'name="' . esc_attr( $args['label_for'] ) . '" '
       . 'value="1"' . checked( 1, $option, false ) . ' />';
}

function stagekitwp_rating_symbol_callback( $args ) {
    $option  = get_option( $args['label_for'] );
    $symbols = [ 'Stars', 'Thumbs Up', 'Rockets', 'Hearts', 'Theatre Masks' ];
    echo '<select id="' . esc_attr( $args['label_for'] ) . '" name="' . esc_attr( $args['label_for'] ) . '">';
    foreach ( $symbols as $symbol ) {
        echo '<option value="' . esc_attr( $symbol ) . '"' . selected( $option, $symbol, false ) . '>' . esc_html( $symbol ) . '</option>';
    }
    echo '</select>';
}

function stagekitwp_testimonials_layout_callback( $args ) {
    $option = get_option( $args['label_for'], 'classic' );
    $layouts = [
        'classic'   => 'Classic',
        'quote'     => 'Quote',
        'minimal'   => 'Minimal',
        'spotlight' => 'Spotlight',
        'overlay'   => 'Overlay',
    ];

    echo '<select id="' . esc_attr( $args['label_for'] ) . '" name="' . esc_attr( $args['label_for'] ) . '">';
    foreach ( $layouts as $value => $label ) {
        echo '<option value="' . esc_attr( $value ) . '"' . selected( $option, $value, false ) . '>' . esc_html( $label ) . '</option>';
    }
    echo '</select>';
}

function stagekitwp_testimonials_mode_callback( $args ) {
    $option = get_option( $args['label_for'], 'slider' );
    $modes = [
        'slider'          => 'Slider',
        'grid'            => 'Grid',
        'full'            => 'Full Page List',
        'per_show'        => 'Per Show',
        'per_show_slider' => 'Per Show Slider',
    ];

    echo '<select id="' . esc_attr( $args['label_for'] ) . '" name="' . esc_attr( $args['label_for'] ) . '">';
    foreach ( $modes as $value => $label ) {
        echo '<option value="' . esc_attr( $value ) . '"' . selected( $option, $value, false ) . '>' . esc_html( $label ) . '</option>';
    }
    echo '</select>';
}

function stagekitwp_testimonials_image_fit_callback( $args ) {
    $option = get_option( $args['label_for'], 'cover' );
    $fits = [
        'cover'   => 'Cover',
        'contain' => 'Contain',
    ];

    echo '<select id="' . esc_attr( $args['label_for'] ) . '" name="' . esc_attr( $args['label_for'] ) . '">';
    foreach ( $fits as $value => $label ) {
        echo '<option value="' . esc_attr( $value ) . '"' . selected( $option, $value, false ) . '>' . esc_html( $label ) . '</option>';
    }
    echo '</select>';
}

function stagekitwp_testimonials_image_position_callback( $args ) {
    $option = get_option( $args['label_for'], 'center' );
    $positions = [
        'center' => 'Centered',
        'left'   => 'Left',
        'right'  => 'Right',
        'top'    => 'Top',
    ];

    echo '<select id="' . esc_attr( $args['label_for'] ) . '" name="' . esc_attr( $args['label_for'] ) . '">';
    foreach ( $positions as $value => $label ) {
        echo '<option value="' . esc_attr( $value ) . '"' . selected( $option, $value, false ) . '>' . esc_html( $label ) . '</option>';
    }
    echo '</select>';
}

function stagekitwp_testimonials_image_focus_callback( $args ) {
    $option = get_option( $args['label_for'], 'center_center' );
    $focuses = [
        'center_center' => 'Center',
        'center_top'    => 'Top',
        'center_bottom' => 'Bottom',
        'left_center'   => 'Left',
        'right_center'  => 'Right',
    ];

    echo '<select id="' . esc_attr( $args['label_for'] ) . '" name="' . esc_attr( $args['label_for'] ) . '">';
    foreach ( $focuses as $value => $label ) {
        echo '<option value="' . esc_attr( $value ) . '"' . selected( $option, $value, false ) . '>' . esc_html( $label ) . '</option>';
    }
    echo '</select>';
}

function stagekitwp_testimonials_review_align_callback( $args ) {
    $option = get_option( $args['label_for'], 'left' );
    $aligns = [
        'left'        => 'Left',
        'center'      => 'Center',
        'right'       => 'Right',
        'alternating' => 'Alternating (right, left, center)',
        'alternating_lr' => 'Alternating (left, right)',
    ];

    echo '<select id="' . esc_attr( $args['label_for'] ) . '" name="' . esc_attr( $args['label_for'] ) . '">';
    foreach ( $aligns as $value => $label ) {
        echo '<option value="' . esc_attr( $value ) . '"' . selected( $option, $value, false ) . '>' . esc_html( $label ) . '</option>';
    }
    echo '</select>';
}

function stagekitwp_testimonials_show_name_placement_callback( $args ) {
    $option = get_option( $args['label_for'], 'meta' );
    $placements = [
        'meta'         => 'Meta Line (default)',
        'header'       => 'Header Above Content',
        'slug'         => 'Bullet Slug Above Content',
        'image_indent' => 'Indented Tag On Image',
    ];

    echo '<select id="' . esc_attr( $args['label_for'] ) . '" name="' . esc_attr( $args['label_for'] ) . '">';
    foreach ( $placements as $value => $label ) {
        echo '<option value="' . esc_attr( $value ) . '"' . selected( $option, $value, false ) . '>' . esc_html( $label ) . '</option>';
    }
    echo '</select>';
}

function stagekitwp_testimonials_tag_icon_source_callback( $args ) {
    $option = get_option( $args['label_for'], 'none' );
    $sources = [
        'none'      => 'No Image (fallback icon)',
        'site_icon' => 'Use Site Icon',
        'miltonman' => 'Use Miltonman Image',
        'custom'    => 'Use Custom URL',
    ];

    echo '<select id="' . esc_attr( $args['label_for'] ) . '" name="' . esc_attr( $args['label_for'] ) . '">';
    foreach ( $sources as $value => $label ) {
        echo '<option value="' . esc_attr( $value ) . '"' . selected( $option, $value, false ) . '>' . esc_html( $label ) . '</option>';
    }
    echo '</select>';
}

function stagekitwp_grid_columns_callback( $args ) {
    $option  = get_option( $args['label_for'] );
    $numbers = [ '1','2','3','4','5','6' ];
    echo '<select id="' . esc_attr( $args['label_for'] ) . '" name="' . esc_attr( $args['label_for'] ) . '">';
    foreach ( $numbers as $n ) {
        echo '<option value="' . esc_attr( $n ) . '"' . selected( $option, $n, false ) . '>' . esc_html( $n ) . '</option>';
    }
    echo '</select>';
}

function stagekitwp_board_member_photo_size_callback( $args ) {
    $key    = $args['label_for'];
    $option = absint( get_option( $key, '120' ) );
    if ( $option < 60 || $option > 220 ) {
        $option = 120;
    }
    echo '<input type="number" min="60" max="220" step="2" '
       . 'id="' . esc_attr( $key ) . '" '
       . 'name="' . esc_attr( $key ) . '" '
       . 'value="' . esc_attr( $option ) . '" '
       . 'style="width:90px;" />';
    echo '<p class="description" style="margin-top:6px;">'
         . 'Controls circular image size in <code>[stagekitwp_board_members layout="grid"]</code>. Range: 60&ndash;220px.'
         . '</p>';
}

function stagekitwp_lp_heading_font_callback( $args ) {
    $saved  = get_option( 'stagekitwp_landing_page_heading_font', '' );
    $id     = 'stagekitwp_landing_page_heading_font';
    $fonts  = [ '' => '(Same as body font — no separate heading font)' ];
    if ( function_exists( 'stagekitwp_lp_font_list' ) ) {
        foreach ( stagekitwp_lp_font_list() as $slug => $data ) {
            if ( $slug === 'inherit' ) { continue; }
            $fonts[ $slug ] = $data['label'];
        }
    }
    $preview_stack = ( $saved && function_exists( 'stagekitwp_lp_font_stack' ) ) ? stagekitwp_lp_font_stack( $saved ) : 'inherit';
    echo '<select id="' . esc_attr($id) . '" name="' . esc_attr($id) . '" onchange="stagekitwpUpdateFontPreview(this)">';
    foreach ( $fonts as $val => $label ) {
        echo '<option value="' . esc_attr($val) . '"' . selected($saved,$val,false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select>';
    echo '<p class="description" style="margin-top:4px;">Set a separate font for show titles and section headings. Leave blank to use the same font as the body text.</p>';
    echo '<div style="margin-top:6px;padding:6px 10px;background:#f9f9f9;border:1px solid #ddd;border-radius:3px;font-family:' . esc_attr($preview_stack) . ';font-weight:700;font-size:1.1em">';
    echo 'Love Lies, Laughter &amp; Lattes &mdash; <em>Heading Preview</em>';
    echo '</div>';
}

function stagekitwp_lp_size_callback( $args ) {
    $option = $args['option'];
    $label  = $args['label'];
    $saved  = get_option( $option, '' );
    $sizes  = [ '' => 'Default', 'xs' => 'XS (75%)', 'sm' => 'Small (87.5%)', 'md' => 'Medium (100%)', 'lg' => 'Large (120%)', 'xl' => 'XL (145%)', 'xxl' => 'XXL (170%)' ];
    echo '<fieldset><legend class="screen-reader-text">' . esc_html($label) . ' Size</legend>';
    echo '<div style="display:flex;gap:16px;flex-wrap:wrap;">';
    foreach ( $sizes as $val => $size_label ) {
        printf(
            '<label style="display:flex;align-items:center;gap:5px;cursor:pointer;"><input type="radio" name="%s" value="%s"%s> %s</label>',
            esc_attr( $option ),
            esc_attr( $val ),
            checked( $saved, $val, false ),
            esc_html( $size_label )
        );
    }
    echo '</div>';
    echo '<p class="description" style="margin-top:6px;">Scales the ' . esc_html(strtolower($label)) . ' size relative to the default. Overridden by per-show settings or the shortcode attribute.</p>';
    echo '</fieldset>';
}

function stagekitwp_lp_text_align_callback( $args ) {
    $saved = get_option( 'stagekitwp_landing_page_text_align', '' );
    $opts  = [
        ''        => '(Default — Left)',
        'left'    => 'Left',
        'center'  => 'Centre',
        'right'   => 'Right',
        'justify' => 'Justify',
    ];
    echo '<fieldset><legend class="screen-reader-text">Text Alignment</legend>';
    echo '<div style="display:flex;gap:16px;flex-wrap:wrap;">';
    foreach ( $opts as $val => $label ) {
        printf(
            '<label style="display:flex;align-items:center;gap:5px;cursor:pointer;"><input type="radio" name="stagekitwp_landing_page_text_align" value="%s"%s> %s</label>',
            esc_attr( $val ),
            checked( $saved, $val, false ),
            esc_html( $label )
        );
    }
    echo '</div>';
    echo '<p class="description" style="margin-top:6px;">Controls <code>text-align</code> for all text inside the landing page. Overridden by per-show settings or the <code>align=</code> shortcode attribute.</p>';
    echo '</fieldset>';
}

function stagekitwp_font_family_callback( $args ) {
    $option = get_option( $args['label_for'], '' );
    $id     = esc_attr( $args['label_for'] );

    // Use the expanded curated list for the landing page tab.
    $is_lp = ( strpos( $args['label_for'], 'landing_page' ) !== false );
    if ( $is_lp && function_exists( 'stagekitwp_lp_font_list' ) ) {
        // Remap slug => label for the <select>
        $raw   = stagekitwp_lp_font_list();
        $fonts = [ '' => '(Theme default)' ];
        foreach ( $raw as $slug => $data ) {
            if ( $slug === 'inherit' ) { continue; }
            $fonts[ $slug ] = $data['label'];
        }
    } else {
        $fonts = [
            ''                          => '(Theme default)',
            'Arial, sans-serif'         => 'Arial',
            'Georgia, serif'            => 'Georgia',
            'Times New Roman, serif'    => 'Times New Roman',
            'Courier New, monospace'    => 'Courier New',
            'Verdana, sans-serif'       => 'Verdana',
            'Trebuchet MS, sans-serif'  => 'Trebuchet MS',
            'Palatino Linotype, serif'  => 'Palatino Linotype',
        ];
    }

    // Resolve preview font (slug for LP, raw stack for other tabs)
    if ( $is_lp && function_exists( 'stagekitwp_lp_font_stack' ) ) {
        $preview_font = stagekitwp_lp_font_stack( $option ) ?: 'inherit';
    } else {
        $preview_font = ( $option && $option !== 'inherit' ) ? $option : 'inherit';
    }
    echo '<select id="' . $id . '" name="' . $id . '" onchange="stagekitwpUpdateFontPreview(this)">';
    foreach ( $fonts as $value => $label ) {
        echo '<option value="' . esc_attr( $value ) . '"' . selected( $option, $value, false ) . '>' . esc_html( $label ) . '</option>';
    }
    echo '</select>';
    if ( $is_lp ) {
        echo '<p class="description" style="margin:4px 0 6px">Fonts marked <em>&bull; Google</em> are loaded from Google Fonts on the frontend.</p>';
    }
    echo '<div class="stagekitwp-font-preview" style="font-family:' . esc_attr( $preview_font ) . ';margin-top:6px;padding:6px 10px;background:#f9f9f9;border:1px solid #ddd;border-radius:3px;">';
    echo '<span id="' . $id . '_preview">The quick brown fox jumps over the lazy dog — Theatre &amp; Performing Arts</span>';
    echo '</div>';
}
