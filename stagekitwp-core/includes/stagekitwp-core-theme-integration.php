<?php
/**
 * StageKitWP — Theme Integration
 *
 * Bridges the plugin's shortcode display options with the StageKitWP
 * Theme's dark/light mode system. When the TM theme is active:
 *
 *  1. Each shortcode tab's display-option colours are emitted as CSS custom
 *     properties on a scoped selector (e.g. .stagekitwp-sc-board_member).
 *  2. A dark-mode override block rewrites those variables when
 *     html.stagekitwp-dark-mode is active, pulling values from the theme's own
 *     Customizer dark tokens so the shortcards flip in sync with the
 *     rest of the site without any JS patching.
 *  3. The JS color-mode-switcher is notified of the shortcode wrapper
 *     selectors so it can include them in its inline-style restore cycle.
 *
 * When the TM theme is NOT active, this file does nothing — the shortcodes
 * continue to render hardcoded inline styles exactly as before.
 */

defined( 'ABSPATH' ) || exit;

// ─────────────────────────────────────────────────────────────────────────────
// 1. Detection helper
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Read a theme mod using StageKitWP-compatible fallback keys.
 *
 * The theme currently exposes the frontend switcher as stagekitwp_* keys, while the
 * plugin code was previously checking stagekitwp_* keys. This helper resolves
 * both names so the plugin can read the active setting reliably.
 */
function stagekitwp_get_theme_mod( $name, $default = false ) {
    $candidates = array( $name );

    if ( strpos( $name, 'stagekitwp_' ) === 0 ) {
        $candidates[] = str_replace( 'stagekitwp_', 'stagekitwp_', $name );
    } elseif ( strpos( $name, 'stagekitwp_' ) === 0 ) {
        $candidates[] = str_replace( 'stagekitwp_', 'stagekitwp_', $name );
    }

    foreach ( $candidates as $candidate ) {
        $value = get_theme_mod( $candidate, '__stagekitwp_mod_unset__' );
        if ( $value !== '__stagekitwp_mod_unset__' ) {
            return $value;
        }
    }

    return $default;
}

/**
 * Returns true when the StageKitWP theme (or the legacy StageKitWP
 * theme) is the active theme. Checks both stylesheet (child theme) and
 * template (parent theme) slugs.
 */
function stagekitwp_theme_is_active() {
    static $result = null;
    if ( $result !== null ) { return $result; }

    $stylesheet = get_stylesheet(); // active theme slug (child if child is active)
    $template   = get_template();  // parent theme slug
    $active_theme = wp_get_theme();
    $parent_theme = $active_theme->parent();
    $theme_slugs = array_filter( array( $stylesheet, $template ) );
    $theme_names = array_filter( array(
        $active_theme->get( 'TextDomain' ),
        $active_theme->get( 'Name' ),
        $parent_theme ? $parent_theme->get( 'TextDomain' ) : '',
        $parent_theme ? $parent_theme->get( 'Name' ) : '',
    ) );

    $result = in_array( 'stagekitwp-theme', $theme_slugs, true );
    if ( ! $result ) {
        foreach ( $theme_names as $theme_name ) {
            if ( 'stagekitwp-theme' === $theme_name || false !== stripos( (string) $theme_name, 'StageKitWP' ) ) {
                $result = true;
                break;
            }
        }
    }

    return $result;
}

// ─────────────────────────────────────────────────────────────────────────────
// 2. Per-tab CSS variable tokens
// ─────────────────────────────────────────────────────────────────────────────

/**
 * The tabs whose display options we bridge. Keys match the option prefix used
 * in admin-menu.php (stagekitwp_{tab}_bg_color etc.).
 * Values are the CSS class applied to the shortcode wrapper (stagekitwp-sc-{tab} or
 * the existing wrapper class where it differs).
 */
function stagekitwp_integration_tabs() {
    return array(
        'board_member' => '.stagekitwp-board-members-block',
        'advertiser'   => '.stagekitwp-advertiser-wrapper, .stagekitwp-advertiser-entry',
        'sponsor'      => '.stagekitwp-sponsor-card',
        'contributor'  => '.stagekitwp-contributor-entry',
        'testimonials' => '.stagekitwp-testimonial',
        'season'       => '.stagekitwp-shortcode-wrapper.stagekitwp-season-wrapper',
        'show'         => '.stagekitwp-shortcode-wrapper.stagekitwp-show-wrapper, .stagekitwp-show-cast',
        'auditions'    => '.stagekitwp-shortcode-wrapper.stagekitwp-auditions-wrapper',
        'awards'       => '.stagekitwp-awards-block',
			'past_shows'   => '.stagekitwp-past-shows-block',
        'season_shows' => '.stagekitwp-season-shows-block',
        'seasons'      => '.stagekitwp-seasons-block',
        'venues'       => '.stagekitwp-venue-card',
        'tickets'      => '.stagekitwp-tickets-block',
        'landing_page' => '.stagekitwp-landingpage-wrapper',
    );
}

// ─────────────────────────────────────────────────────────────────────────────
// 3. Output the CSS variable bridge + dark-mode overrides
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Prefix every comma-separated selector in $selectors_string with $prefix.
 *
 * Handles multi-selector strings like '.foo, .bar' or '.foo,\n.bar' reliably
 * by splitting on ANY comma (ignoring surrounding whitespace), prefixing each
 * part individually, and rejoining with ",\n".
 *
 * This is the ONLY safe way to build scoped CSS selector lists from PHP when
 * the input contains multi-class strings — bare string-replace approaches
 * silently produce unscoped global selectors whenever a comma has no newline.
 *
 * @param string $selectors_string  One or more CSS selectors, comma-separated.
 * @param string $prefix            Scope prefix, e.g. 'html.stagekitwp-dark-mode'.
 * @return string                   Prefixed selector string ready to echo.
 */
function stagekitwp_prefix_selectors( $selectors_string, $prefix ) {
    $parts = preg_split( '/\s*,\s*/', trim( $selectors_string ) );
    $prefixed = array();
    foreach ( $parts as $part ) {
        $part = trim( $part );
        if ( $part !== '' ) {
            $prefixed[] = $prefix . ' ' . $part;
        }
    }
    return implode( ",\n", $prefixed );
}

/**
 * Hooked to wp_head (priority 20, after the theme's own CSS variable block).
 * Emits two <style> blocks:
 *   A) Light-mode: scoped CSS variable declarations per tab selector.
 *   B) Dark-mode:  html.stagekitwp-dark-mode overrides using the theme's dark tokens.
 */
function stagekitwp_output_shortcode_theme_integration_css() {
    if ( is_admin() ) { return; } // never output frontend CSS on admin pages
    if ( ! stagekitwp_theme_is_active() ) { return; }
    if ( ! stagekitwp_get_theme_mod( 'stagekitwp_enable_frontend_switcher', false ) ) { return; }

    $tabs = stagekitwp_integration_tabs();

    // ── Grab theme dark-mode palette (same tokens the theme uses) ────────────
    $dark = array(
        'base_bg'      => stagekitwp_get_theme_mod( 'stagekitwp_color_base_bg_dark',      '#121212' ),
        'surface_bg'   => stagekitwp_get_theme_mod( 'stagekitwp_color_surface_bg_dark',   '#1e1e1e' ),
        'body_text'    => stagekitwp_get_theme_mod( 'stagekitwp_color_body_text_dark',    '#e0e0e0' ),
        'heading_text' => stagekitwp_get_theme_mod( 'stagekitwp_color_heading_text_dark', '#ffffff'  ),
        'muted_text'   => stagekitwp_get_theme_mod( 'stagekitwp_color_muted_text_dark',   '#9e9e9e' ),
        'link'         => stagekitwp_get_theme_mod( 'stagekitwp_color_link_dark',         '#90caf9' ),
        'border'       => 'rgba(255,255,255,0.12)',
    );

    // ── Block A: light-mode CSS variable declarations ─────────────────────────
    echo "\n<style id=\"stagekitwp-sc-integration-light\">\n";
    foreach ( $tabs as $tab => $selectors ) {
        $bg     = get_option( "stagekitwp_{$tab}_bg_color",     '' );
        $text   = get_option( "stagekitwp_{$tab}_text_color",   '' );
        $border = get_option( "stagekitwp_{$tab}_border_color", '' );
        $font   = get_option( "stagekitwp_{$tab}_base_font",    '' );

        // Only emit the block when at least one value is set
        if ( ! $bg && ! $text && ! $border && ! $font ) { continue; }

        // $selectors may be a multi-class string like '.foo, .bar' — no scoping
        // needed for light mode, just output as-is (already a valid selector list).
        echo $selectors . " {\n";
        if ( $bg     ) { echo '    --stagekitwp-sc-bg:     ' . esc_attr( $bg )     . ";\n"; }
        if ( $text   ) { echo '    --stagekitwp-sc-text:   ' . esc_attr( $text )   . ";\n"; }
        if ( $border ) { echo '    --stagekitwp-sc-border: ' . esc_attr( $border ) . ";\n"; }
        if ( $font   ) { echo '    --stagekitwp-sc-font:   ' . esc_attr( $font )   . ";\n"; }
        echo "}\n";
    }
    echo "</style>\n";

    // ── Block B: dark-mode overrides ─────────────────────────────────────────
    echo "\n<style id=\"stagekitwp-sc-integration-dark\">\n";

    // Per-tab dark blocks: use Display Options dark pickers when set.
    // Per-tab rules are emitted first and win via specificity over the generic fallback.
    foreach ( $tabs as $tab => $selectors ) {
        $tab_bg   = get_option( "stagekitwp_{$tab}_bg_color_dark",     '' );
        $tab_text = get_option( "stagekitwp_{$tab}_text_color_dark",   '' );
        $tab_bord = get_option( "stagekitwp_{$tab}_border_color_dark", '' );
        if ( ! $tab_bg && ! $tab_text && ! $tab_bord ) { continue; }
        $prefixed = stagekitwp_prefix_selectors( $selectors, 'html.stagekitwp-dark-mode' );
        echo $prefixed . " {\n";
        if ( $tab_bg   ) { echo '    --stagekitwp-sc-bg:     ' . esc_attr( $tab_bg   ) . " !important;\n"; }
        if ( $tab_text ) { echo '    --stagekitwp-sc-text:   ' . esc_attr( $tab_text ) . " !important;\n"; }
        if ( $tab_bord ) { echo '    --stagekitwp-sc-border: ' . esc_attr( $tab_bord ) . " !important;\n"; }
        echo "}\n\n";
    }

    // Generic fallback — covers every tab using theme Customizer dark tokens.
    // No !important here so per-tab blocks above win via specificity.
    // Collect every individual selector from every tab value, prefix each one.
    $all_parts = array();
    foreach ( array_values( $tabs ) as $tab_sel ) {
        $parts = preg_split( '/\s*,\s*/', trim( $tab_sel ) );
        foreach ( $parts as $p ) {
            $p = trim( $p );
            if ( $p !== '' ) { $all_parts[] = 'html.stagekitwp-dark-mode ' . $p; }
        }
    }
    echo implode( ",\n", $all_parts ) . " {\n";
    echo '    --stagekitwp-sc-bg:     ' . esc_attr( $dark['surface_bg']   ) . ";\n";
    echo '    --stagekitwp-sc-text:   ' . esc_attr( $dark['body_text']    ) . ";\n";
    echo '    --stagekitwp-sc-border: ' . esc_attr( $dark['border']       ) . ";\n";
    echo "}\n\n";

    // Build properly-scoped selector lists.
    // IMPORTANT: every tag in a comma-separated CSS selector list must carry
    // the full scope prefix. Bare tags like `label` or `li` without a scope
    // become global selectors that fire on EVERY element on the page, not just
    // inside the dark-mode shortcode wrappers.
    $wrapper_classes = array(
        '.stagekitwp-board-members-grid', '.stagekitwp-sponsor-card', '.stagekitwp-advertiser-entry',
        '.stagekitwp-contributor-entry', '.stagekitwp-testimonial',
        '.stagekitwp-shortcode-wrapper', '.stagekitwp-venue-card', '.stagekitwp-cast-entry',
    );

    // Heading colours inside shortcode wrappers.
    // Build selector list with every tag individually scoped to avoid
    // generating unscoped global selectors (PHP 5.6+ compatible — no arrow fns).
    $heading_tags = array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' );
    foreach ( $wrapper_classes as $cls ) {
        $selectors = array();
        foreach ( $heading_tags as $tag ) {
            $selectors[] = "html.stagekitwp-dark-mode {$cls} {$tag}";
        }
        echo implode( ",\n", $selectors ) . " {\n";
        echo '    color: ' . esc_attr( $dark['heading_text'] ) . " !important;\n";
        echo "}\n";
    }

    // Paragraph / list / inline colours inside shortcode wrappers.
    $text_tags = array( 'p', 'li', 'td', 'th', 'span', 'label', 'strong' );
    foreach ( $wrapper_classes as $cls ) {
        $selectors = array();
        foreach ( $text_tags as $tag ) {
            $selectors[] = "html.stagekitwp-dark-mode {$cls} {$tag}";
        }
        echo implode( ",\n", $selectors ) . " {\n";
        echo '    color: ' . esc_attr( $dark['body_text'] ) . " !important;\n";
        echo "}\n";
    }

    // Link colours
    foreach ( $wrapper_classes as $cls ) {
        echo "html.stagekitwp-dark-mode {$cls} a {\n";
        echo '    color: ' . esc_attr( $dark['link'] ) . " !important;\n";
        echo "}\n";
    }

    // Apply the CSS variables as actual properties where shortcodes use them
    // (fallback for shortcodes that still use inline style= rather than CSS vars)
    $all_wrapper_str = implode( ', ', $wrapper_classes );
    echo stagekitwp_prefix_selectors( $all_wrapper_str, 'html.stagekitwp-dark-mode' ) . " {\n";
    echo '    background-color: var(--stagekitwp-sc-bg)   !important;' . "\n";
    echo '    color:            var(--stagekitwp-sc-text)  !important;' . "\n";
    echo "}\n\n";


        // Venue cards
    echo "html.stagekitwp-dark-mode .stagekitwp-venue-card {\n";
    echo '    background-color: ' . esc_attr( $dark['surface_bg'] ) . " !important;\n";
    echo '    color:            ' . esc_attr( $dark['body_text']  ) . " !important;\n";
    echo '    border-color:     ' . esc_attr( $dark['border']     ) . " !important;\n";
    echo "}\n\n";

    // Seasons / shows inline-coloured headings
    echo "html.stagekitwp-dark-mode .stagekitwp-season-title,\n";
    echo "html.stagekitwp-dark-mode .stagekitwp-slot-title,\n";
    echo "html.stagekitwp-dark-mode .stagekitwp-show-title,\n";
    echo "html.stagekitwp-dark-mode .stagekitwp-program-header {\n";
    echo '    color: ' . esc_attr( $dark['heading_text'] ) . " !important;\n";
    echo "}\n\n";

    // Contributor & cast entries
    echo "html.stagekitwp-dark-mode .stagekitwp-contributor-entry,\n";
    echo "html.stagekitwp-dark-mode .stagekitwp-cast-entry {\n";
    echo '    background-color: ' . esc_attr( $dark['surface_bg'] ) . " !important;\n";
    echo '    color:            ' . esc_attr( $dark['body_text']  ) . " !important;\n";
    echo "}\n\n";

    // Board members block — emitted later (after $safe_surface / $safe_text are defined)

    // Sponsor cards
    // Use scoped --stagekitwp-sc-* vars so Sponsor Display Options dark pickers
    // (stagekitwp_sponsor_*_dark) win when set, with theme dark tokens as fallback.
    echo "html.stagekitwp-dark-mode .stagekitwp-sponsor-card {\n";
    echo "    background-color: var(--stagekitwp-sc-bg) !important;\n";
    echo "    color:            var(--stagekitwp-sc-text) !important;\n";
    echo "    border-color:     var(--stagekitwp-sc-border) !important;\n";
    echo "}\n";
    echo "html.stagekitwp-dark-mode .stagekitwp-sponsor-card h3,\n";
    echo "html.stagekitwp-dark-mode .stagekitwp-sponsor-card h4,\n";
    echo "html.stagekitwp-dark-mode .stagekitwp-sponsor-card p,\n";
    echo "html.stagekitwp-dark-mode .stagekitwp-sponsor-card a {\n";
    echo "    color: var(--stagekitwp-sc-text) !important;\n";
    echo "}\n\n";

    // Advertiser entries
    echo "html.stagekitwp-dark-mode .stagekitwp-advertiser-entry {\n";
    echo '    background-color: ' . esc_attr( $dark['surface_bg'] ) . " !important;\n";
    echo '    border-color:     ' . esc_attr( $dark['border']     ) . " !important;\n";
    echo "}\n\n";

    // Auditions wrapper
    echo "html.stagekitwp-dark-mode .stagekitwp-auditions-wrapper {\n";
    echo '    background-color: ' . esc_attr( $dark['surface_bg'] ) . " !important;\n";
    echo '    color:            ' . esc_attr( $dark['body_text']  ) . " !important;\n";
    echo "}\n\n";

        // -- Tickets dark-mode: redefine --stagekitwp-tk-* vars on the wrapper ---------------
    //
    // The shortcode sets --stagekitwp-tk-* on .stagekitwp-tickets-block.
    // Dark mode only needs to redefine those vars + override a few structural
    // element colours that don't cascade from the wrapper (headings, etc.).
    //
    // Block 1: always emitted — redefines all --stagekitwp-tk-* vars using theme dark
    //          tokens, so every rule using var(--stagekitwp-tk-*) auto-updates.
    // Block 2: Display Options hex overrides — only when explicitly saved.

    // -- Tickets dark CSS vars ------------------------------------------------
    // Resolve theme dark tokens via PHP rather than CSS var() chains so that
    // a misconfigured Customizer value (e.g. surface_bg = #000000) never
    // produces invisible elements.  We validate each value and substitute a
    // safe legible default when the saved value is clearly wrong.

    /**
     * Returns $value if it's a valid, non-black hex colour; otherwise $default.
     * "Too dark" = luminance below 4% (catches #000000 and near-blacks).
     */
    $safe_surface = function( $value, $default ) {
        if ( ! $value || strlen( $value ) < 4 ) { return $default; }
        $hex = ltrim( $value, '#' );
        if ( strlen( $hex ) === 3 ) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        if ( strlen( $hex ) !== 6 ) { return $default; }
        $r = hexdec( substr( $hex, 0, 2 ) ) / 255;
        $g = hexdec( substr( $hex, 2, 2 ) ) / 255;
        $b = hexdec( substr( $hex, 4, 2 ) ) / 255;
        $lum = 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
        return $lum < 0.04 ? $default : $value; // too dark = use default
    };

    $safe_text = function( $value, $default ) {
        if ( ! $value || strlen( $value ) < 4 ) { return $default; }
        $hex = ltrim( $value, '#' );
        if ( strlen( $hex ) === 3 ) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        if ( strlen( $hex ) !== 6 ) { return $default; }
        $r = hexdec( substr( $hex, 0, 2 ) ) / 255;
        $g = hexdec( substr( $hex, 2, 2 ) ) / 255;
        $b = hexdec( substr( $hex, 4, 2 ) ) / 255;
        $lum = 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
        // Reject near-white (lum > 0.85) AND near-black (lum < 0.04) —
        // both are illegible as body text on a dark background.
        return ( $lum > 0.85 || $lum < 0.04 ) ? $default : $value;
    };

    $d_safe_bg   = call_user_func( $safe_surface, $dark['surface_bg'],   '#1e1e1e' );
    $d_safe_text = call_user_func( $safe_text,    $dark['body_text'],    '#e0e0e0' );

    // Board members block — CSS-var override using validated safe values
    // Display Options _dark pickers override these vars from the shortcode shortcode itself.
    $bm_do_bg     = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_board_member_bg_color_dark',     '' ) );
    $bm_do_text   = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_board_member_text_color_dark',   '' ) );
    $bm_do_border = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_board_member_border_color_dark', '' ) );
    $bm_dark_bg     = $bm_do_bg     ? call_user_func( $safe_surface, $bm_do_bg,     $d_safe_bg )   : $d_safe_bg;
    $bm_dark_text   = $bm_do_text   ? call_user_func( $safe_text,    $bm_do_text,   $d_safe_text ) : $d_safe_text;
    // Border on dark bg: #000000 is invisible; fall back to a subtle white-alpha
    $bm_safe_border_raw = $bm_do_border ?: $dark['border'];
    $bm_border_hex = ltrim( $bm_safe_border_raw, '#' );
    if ( strlen( $bm_border_hex ) === 3 ) { $bm_border_hex = $bm_border_hex[0].$bm_border_hex[0].$bm_border_hex[1].$bm_border_hex[1].$bm_border_hex[2].$bm_border_hex[2]; }
    $bm_border_lum = ( strlen( $bm_border_hex ) === 6 )
        ? ( 0.2126 * hexdec( substr($bm_border_hex,0,2) ) / 255
          + 0.7152 * hexdec( substr($bm_border_hex,2,2) ) / 255
          + 0.0722 * hexdec( substr($bm_border_hex,4,2) ) / 255 )
        : 0;
    $bm_dark_border = ( $bm_border_lum < 0.04 ) ? 'rgba(255,255,255,0.15)' : $bm_safe_border_raw;
    echo "html.stagekitwp-dark-mode .stagekitwp-board-members-block {\n";
    echo '    --stagekitwp-bm-bg:     ' . esc_attr( $bm_dark_bg )     . " !important;\n";
    echo '    --stagekitwp-bm-text:   ' . esc_attr( $bm_dark_text )   . " !important;\n";
    echo '    --stagekitwp-bm-border: ' . esc_attr( $bm_dark_border ) . " !important;\n";
    echo "}\n\n";

    // Awards block — CSS-var override (all 4 layouts inherit from --stagekitwp-aw-* vars)
    // Must be after $safe_surface / $safe_text / $d_safe_bg / $d_safe_text are defined.
    $aw_dark_bg  = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_awards_bg_color_dark',     '' ) );
    $aw_dark_tx  = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_awards_text_color_dark',   '' ) );
    $aw_dark_bd  = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_awards_border_color_dark', '' ) );
    $aw_dark_h2  = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_awards_h2_color_dark',     '' ) );
    $aw_dark_h3  = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_awards_h3_color_dark',     '' ) );
    $aw_bg     = $aw_dark_bg ? call_user_func( $safe_surface, $aw_dark_bg, $d_safe_bg )   : $d_safe_bg;
    $aw_text   = $aw_dark_tx ? call_user_func( $safe_text,    $aw_dark_tx, $d_safe_text ) : $d_safe_text;
    $aw_bd_raw = $aw_dark_bd ?: $dark['border'];
    $aw_bd_hex = ltrim( $aw_bd_raw, '#' );
    if ( strlen( $aw_bd_hex ) === 3 ) { $aw_bd_hex = $aw_bd_hex[0].$aw_bd_hex[0].$aw_bd_hex[1].$aw_bd_hex[1].$aw_bd_hex[2].$aw_bd_hex[2]; }
    $aw_bd_lum = ( strlen( $aw_bd_hex ) === 6 ) ? ( 0.2126*hexdec(substr($aw_bd_hex,0,2))/255 + 0.7152*hexdec(substr($aw_bd_hex,2,2))/255 + 0.0722*hexdec(substr($aw_bd_hex,4,2))/255 ) : 0;
    $aw_border = ( $aw_bd_lum < 0.04 ) ? 'rgba(255,255,255,0.15)' : $aw_bd_raw;
    $aw_h2 = $aw_dark_h2 ? call_user_func( $safe_text, $aw_dark_h2, '#e0e0e0' ) : '#e0e0e0';
    $aw_h3 = $aw_dark_h3 ? call_user_func( $safe_text, $aw_dark_h3, '#b0b0b0' ) : '#b0b0b0';
    // Past Shows block — inherits --stagekitwp-ps-* vars; same safe values as shows.
    echo "html.stagekitwp-dark-mode .stagekitwp-past-shows-block {\n";
    echo '    --stagekitwp-ps-bg:     ' . esc_attr( $aw_bg )     . " !important;\n";
    echo '    --stagekitwp-ps-text:   ' . esc_attr( $aw_text )   . " !important;\n";
    echo '    --stagekitwp-ps-border: ' . esc_attr( $aw_border ) . " !important;\n";
    echo '    --stagekitwp-ps-h2:     ' . esc_attr( $aw_h2 )     . " !important;\n";
    echo '    --stagekitwp-ps-h3:     ' . esc_attr( $aw_h3 )     . " !important;\n";
    echo "}\n\n";

    // Season Shows block — reads stagekitwp_show_* dark DO options; same safe-value pattern.
    $ss_dark_bg  = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_show_bg_color_dark',     '' ) );
    $ss_dark_tx  = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_show_text_color_dark',   '' ) );
    $ss_dark_bd  = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_show_border_color_dark', '' ) );
    $ss_dark_h2  = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_show_h2_color_dark',     '' ) );
    $ss_dark_h3  = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_show_h3_color_dark',     '' ) );
    $ss_bg     = $ss_dark_bg ? call_user_func( $safe_surface, $ss_dark_bg, $d_safe_bg )   : $d_safe_bg;
    $ss_text   = $ss_dark_tx ? call_user_func( $safe_text,    $ss_dark_tx, $d_safe_text ) : $d_safe_text;
    $ss_bd_raw = $ss_dark_bd ?: $dark['border'];
    $ss_bd_hex = ltrim( $ss_bd_raw, '#' );
    if ( strlen( $ss_bd_hex ) === 3 ) { $ss_bd_hex = $ss_bd_hex[0].$ss_bd_hex[0].$ss_bd_hex[1].$ss_bd_hex[1].$ss_bd_hex[2].$ss_bd_hex[2]; }
    $ss_bd_lum = ( strlen( $ss_bd_hex ) === 6 ) ? ( 0.2126*hexdec(substr($ss_bd_hex,0,2))/255 + 0.7152*hexdec(substr($ss_bd_hex,2,2))/255 + 0.0722*hexdec(substr($ss_bd_hex,4,2))/255 ) : 0;
    $ss_border = ( $ss_bd_lum < 0.04 ) ? 'rgba(255,255,255,0.15)' : $ss_bd_raw;
    $ss_h2     = $ss_dark_h2 ? call_user_func( $safe_text, $ss_dark_h2, '#e0e0e0' ) : '#e0e0e0';
    $ss_h3     = $ss_dark_h3 ? call_user_func( $safe_text, $ss_dark_h3, '#b0b0b0' ) : '#b0b0b0';
    echo "html.stagekitwp-dark-mode .stagekitwp-season-shows-block {\n";
    echo '    --stagekitwp-ss-bg:     ' . esc_attr( $ss_bg )     . " !important;\n";
    echo '    --stagekitwp-ss-text:   ' . esc_attr( $ss_text )   . " !important;\n";
    echo '    --stagekitwp-ss-border: ' . esc_attr( $ss_border ) . " !important;\n";
    echo '    --stagekitwp-ss-h2:     ' . esc_attr( $ss_h2 )     . " !important;\n";
    echo '    --stagekitwp-ss-h3:     ' . esc_attr( $ss_h3 )     . " !important;\n";
    echo '    --stagekitwp-ss-acc:    ' . esc_attr( $ss_h2 )     . " !important;\n";
    echo '    --stagekitwp-ss-btn-bg: #1a78c2'                    . " !important;\n";
    echo '    --stagekitwp-ss-btn-text: #ffffff'                  . " !important;\n";
    echo "}\n\n";

    // Seasons block — reads stagekitwp_season_* dark DO options
    $sn_dark_bg  = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_season_bg_color_dark',     '' ) );
    $sn_dark_tx  = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_season_text_color_dark',   '' ) );
    $sn_dark_bd  = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_season_border_color_dark', '' ) );
    $sn_dark_h2  = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_season_h2_color_dark',     '' ) );
    $sn_dark_h3  = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_season_h3_color_dark',     '' ) );
    $sn_bg     = $sn_dark_bg ? call_user_func( $safe_surface, $sn_dark_bg, $d_safe_bg )   : $d_safe_bg;
    $sn_text   = $sn_dark_tx ? call_user_func( $safe_text,    $sn_dark_tx, $d_safe_text ) : $d_safe_text;
    $sn_bd_raw = $sn_dark_bd ?: $dark['border'];
    $sn_bd_hex = ltrim( $sn_bd_raw, '#' );
    if ( strlen( $sn_bd_hex ) === 3 ) { $sn_bd_hex = $sn_bd_hex[0].$sn_bd_hex[0].$sn_bd_hex[1].$sn_bd_hex[1].$sn_bd_hex[2].$sn_bd_hex[2]; }
    $sn_bd_lum = ( strlen( $sn_bd_hex ) === 6 ) ? ( 0.2126*hexdec(substr($sn_bd_hex,0,2))/255 + 0.7152*hexdec(substr($sn_bd_hex,2,2))/255 + 0.0722*hexdec(substr($sn_bd_hex,4,2))/255 ) : 0;
    $sn_border = ( $sn_bd_lum < 0.04 ) ? 'rgba(255,255,255,0.15)' : $sn_bd_raw;
    $sn_h2     = $sn_dark_h2 ? call_user_func( $safe_text, $sn_dark_h2, '#e0e0e0' ) : '#e0e0e0';
    $sn_h3     = $sn_dark_h3 ? call_user_func( $safe_text, $sn_dark_h3, '#b0b0b0' ) : '#b0b0b0';
    echo "html.stagekitwp-dark-mode .stagekitwp-seasons-block {\n";
    echo '    --stagekitwp-sn-bg:       ' . esc_attr( $sn_bg )     . " !important;\n";
    echo '    --stagekitwp-sn-text:     ' . esc_attr( $sn_text )   . " !important;\n";
    echo '    --stagekitwp-sn-border:   ' . esc_attr( $sn_border ) . " !important;\n";
    echo '    --stagekitwp-sn-h2:       ' . esc_attr( $sn_h2 )     . " !important;\n";
    echo '    --stagekitwp-sn-h3:       ' . esc_attr( $sn_h3 )     . " !important;\n";
    echo '    --stagekitwp-sn-acc:      ' . esc_attr( $sn_h2 )     . " !important;\n";
    echo '    --stagekitwp-sn-btn-bg:   #1a78c2'                   . " !important;\n";
    echo '    --stagekitwp-sn-btn-text: #ffffff'                   . " !important;\n";
    echo "}\n\n";

    echo "html.stagekitwp-dark-mode .stagekitwp-awards-block {\n";
    echo '    --stagekitwp-aw-bg:     ' . esc_attr( $aw_bg )     . " !important;\n";
    echo '    --stagekitwp-aw-text:   ' . esc_attr( $aw_text )   . " !important;\n";
    echo '    --stagekitwp-aw-border: ' . esc_attr( $aw_border ) . " !important;\n";
    echo '    --stagekitwp-aw-h2:     ' . esc_attr( $aw_h2 )     . " !important;\n";
    echo '    --stagekitwp-aw-h3:     ' . esc_attr( $aw_h3 )     . " !important;\n";
    echo "}\n\n";

    // In dark mode, buttons need enough contrast against white text (WCAG AA ~4.5:1).
    // #90caf9 (the Material Design light-blue used as a text/accent colour on dark pages)
    // only achieves ~1.9:1 against white — far too low for button backgrounds.
    // Use #1a78c2 as the safe dark-mode button colour (4.6:1 vs white, AA pass).
    // If the admin has explicitly saved a dark link colour use it, but validate contrast.
    $raw_dark_link = $dark['link'];
    $d_safe_link   = '#1a78c2'; // default safe dark button
    if ( $raw_dark_link && strlen( $raw_dark_link ) >= 4 ) {
        $hex = ltrim( $raw_dark_link, '#' );
        if ( strlen( $hex ) === 3 ) { $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2]; }
        if ( strlen( $hex ) === 6 ) {
            $r = hexdec( substr( $hex, 0, 2 ) ) / 255;
            $g = hexdec( substr( $hex, 2, 2 ) ) / 255;
            $b = hexdec( substr( $hex, 4, 2 ) ) / 255;
            $lum = 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
            // Accept only if dark enough to contrast white text (lum < 0.18 ≈ 4.5:1 vs #fff)
            if ( $lum < 0.18 ) { $d_safe_link = $raw_dark_link; }
        }
    }

    echo "/* [stagekitwp_tickets] dark-mode overrides */
";

    echo 'html.stagekitwp-dark-mode .stagekitwp-tickets-block {
    --stagekitwp-tk-bg:      ' . esc_attr( $d_safe_bg )   . ';
    --stagekitwp-tk-text:    ' . esc_attr( $d_safe_text ) . ';
    --stagekitwp-tk-btn:     ' . esc_attr( $d_safe_link ) . ';
    --stagekitwp-tk-btn-hov: ' . esc_attr( $d_safe_link ) . ';
    --stagekitwp-tk-border:  rgba(255,255,255,0.15);
    color: ' . esc_attr( $d_safe_text ) . ' !important;
}
/* Force text on anchor elements that inherit theme main a { color } */
html.stagekitwp-dark-mode .stagekitwp-ticket-btn,
html.stagekitwp-dark-mode .stagekitwp-ticket-card,
html.stagekitwp-dark-mode .stagekitwp-ticket-min-row,
html.stagekitwp-dark-mode .stagekitwp-ticket-spotlight-tile {
    color: ' . esc_attr( $d_safe_text ) . ' !important;
}
/* Buttons/CTAs: accent background, always white text */
html.stagekitwp-dark-mode .stagekitwp-ticket-btn,
html.stagekitwp-dark-mode .stagekitwp-ticket-card-cta,
html.stagekitwp-dark-mode .stagekitwp-ticket-table-link,
html.stagekitwp-dark-mode .stagekitwp-ticket-min-arrow,
html.stagekitwp-dark-mode .stagekitwp-ticket-spotlight-tile-cta {
    background-color: ' . esc_attr( $d_safe_link ) . ' !important;
    color: #ffffff !important;
}
/* Additional per-element overrides outside .stagekitwp-tickets-block var scope */
html.stagekitwp-dark-mode .stagekitwp-tickets-table-wrap { background: ' . esc_attr( $d_safe_bg ) . '; }
html.stagekitwp-dark-mode .stagekitwp-tickets-table thead th { background: ' . esc_attr( $d_safe_bg ) . '; color: ' . esc_attr( $d_safe_link ) . ' !important; border-bottom-color: rgba(255,255,255,0.15) !important; }
html.stagekitwp-dark-mode .stagekitwp-tickets-table tbody td { color: ' . esc_attr( $d_safe_text ) . ' !important; border-bottom-color: rgba(255,255,255,0.10) !important; background: transparent !important; }
html.stagekitwp-dark-mode .stagekitwp-tickets-table tbody tr:hover td { background: rgba(255,255,255,0.06) !important; }
html.stagekitwp-dark-mode .stagekitwp-ticket-season-row td { background: rgba(255,255,255,0.08) !important; color: ' . esc_attr( $d_safe_text ) . ' !important; }
html.stagekitwp-dark-mode .stagekitwp-ticket-table-name { color: ' . esc_attr( $d_safe_text ) . ' !important; }
html.stagekitwp-dark-mode .stagekitwp-ticket-table-tag { background: rgba(255,255,255,0.12) !important; color: ' . esc_attr( $d_safe_link ) . ' !important; }
html.stagekitwp-dark-mode .stagekitwp-ticket-table-badge { background: rgba(255,255,255,0.10) !important; color: rgba(255,255,255,0.55) !important; }
html.stagekitwp-dark-mode .stagekitwp-ticket-card-label { color: ' . esc_attr( $d_safe_link ) . ' !important; }
html.stagekitwp-dark-mode .stagekitwp-ticket-card-title,
html.stagekitwp-dark-mode .stagekitwp-ticket-card-meta { color: ' . esc_attr( $d_safe_text ) . ' !important; }
html.stagekitwp-dark-mode .stagekitwp-ticket-card-genre { background: rgba(255,255,255,0.12) !important; color: ' . esc_attr( $d_safe_link ) . ' !important; }
html.stagekitwp-dark-mode .stagekitwp-ticket-min-type { color: ' . esc_attr( $d_safe_link ) . ' !important; }
html.stagekitwp-dark-mode .stagekitwp-ticket-min-title { color: ' . esc_attr( $d_safe_text ) . ' !important; }
html.stagekitwp-dark-mode .stagekitwp-ticket-min-dates { color: rgba(255,255,255,0.55) !important; }
html.stagekitwp-dark-mode .stagekitwp-ticket-spotlight-tile-label { color: ' . esc_attr( $d_safe_link ) . ' !important; }
html.stagekitwp-dark-mode .stagekitwp-ticket-spotlight-tile-title { color: ' . esc_attr( $d_safe_text ) . ' !important; }
html.stagekitwp-dark-mode .stagekitwp-ticket-spotlight-tile-dates { color: rgba(255,255,255,0.55) !important; }
';

    // Block 2: Display Options hex overrides (only when explicitly saved)
    $tk_btn_dark   = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_tickets_button_color_dark',       '' ) );
    $tk_hover_dark = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_tickets_button_hover_color_dark', '' ) );
    $tk_bg_dark    = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_tickets_bg_color_dark',           '' ) );
    $tk_text_dark  = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_tickets_text_color_dark',         '' ) );
    $tk_bord_dark  = stagekitwp_sanitize_css_color( get_option( 'stagekitwp_tickets_border_color_dark',       '' ) );

    if ( $tk_btn_dark || $tk_bg_dark || $tk_text_dark || $tk_bord_dark || $tk_hover_dark ) {
        echo "/* [stagekitwp_tickets] Display Options dark overrides */\nhtml.stagekitwp-dark-mode .stagekitwp-tickets-block {\n";
        if ( $tk_bg_dark   ) { echo '    --stagekitwp-tk-bg:      ' . esc_attr( $tk_bg_dark )   . " !important;\n"; }
        if ( $tk_text_dark ) { echo '    --stagekitwp-tk-text:    ' . esc_attr( $tk_text_dark ) . " !important;\n"; }
        if ( $tk_btn_dark  ) { echo '    --stagekitwp-tk-btn:     ' . esc_attr( $tk_btn_dark )  . " !important;\n"; }
        if ( $tk_hover_dark) { echo '    --stagekitwp-tk-btn-hov: ' . esc_attr( $tk_hover_dark) . " !important;\n"; }
        if ( $tk_bord_dark ) { echo '    --stagekitwp-tk-border:  ' . esc_attr( $tk_bord_dark ) . " !important;\n"; }
        echo "}\n\n";
    }

    // ── [stagekitwp_landingpage] Display Options light overrides ─────────────────
    // Raw-check before sanitize (stagekitwp_sanitize_css_color('') returns '#000000' not '')
    $lp_bg_raw     = get_option( 'stagekitwp_landing_page_bg_color',     '' );
    $lp_text_raw   = get_option( 'stagekitwp_landing_page_text_color',   '' );
    $lp_h1_raw     = get_option( 'stagekitwp_landing_page_h1_color',     '' );
    $lp_h2_raw     = get_option( 'stagekitwp_landing_page_h2_color',     '' );
    $lp_border_raw = get_option( 'stagekitwp_landing_page_border_color', '' );
    $lp_bg     = $lp_bg_raw     ? stagekitwp_sanitize_css_color( $lp_bg_raw )     : '';
    $lp_text   = $lp_text_raw   ? stagekitwp_sanitize_css_color( $lp_text_raw )   : '';
    $lp_h1     = $lp_h1_raw     ? stagekitwp_sanitize_css_color( $lp_h1_raw )     : '';
    $lp_h2     = $lp_h2_raw     ? stagekitwp_sanitize_css_color( $lp_h2_raw )     : '';
    $lp_border = $lp_border_raw ? stagekitwp_sanitize_css_color( $lp_border_raw ) : '';
    $lp_font_slug  = sanitize_key( get_option( 'stagekitwp_landing_page_base_font',    '' ) );
    $lp_font       = function_exists( 'stagekitwp_lp_font_stack' ) ? stagekitwp_lp_font_stack( $lp_font_slug ) : '';
    $lp_hfont_slug = sanitize_key( get_option( 'stagekitwp_landing_page_heading_font',  '' ) );
    $lp_hfont      = function_exists( 'stagekitwp_lp_font_stack' ) ? stagekitwp_lp_font_stack( $lp_hfont_slug ) : '';
    $lp_size_map   = [ 'xs' => '0.75', 'sm' => '0.875', 'md' => '1', 'lg' => '1.2', 'xl' => '1.45', 'xxl' => '1.7' ];
    $lp_hsize_raw  = sanitize_key( get_option( 'stagekitwp_landing_page_heading_size',  '' ) );
    $lp_hscale     = isset( $lp_size_map[$lp_hsize_raw] ) ? $lp_size_map[$lp_hsize_raw] : '';
    $lp_tsize_raw  = sanitize_key( get_option( 'stagekitwp_landing_page_text_size',     '' ) );
    $lp_tscale     = isset( $lp_size_map[$lp_tsize_raw] ) ? $lp_size_map[$lp_tsize_raw] : '';
    $allowed_lp_aligns = [ 'left', 'center', 'right', 'justify' ];
    $lp_align_raw  = sanitize_key( get_option( 'stagekitwp_landing_page_text_align',    '' ) );
    $lp_align      = in_array( $lp_align_raw, $allowed_lp_aligns, true ) ? $lp_align_raw : '';
    if ( $lp_bg || $lp_text || $lp_h1 || $lp_h2 || $lp_border || $lp_font || $lp_hfont || $lp_hscale || $lp_tscale || $lp_align ) {
        echo "/* [stagekitwp_landingpage] Display Options light overrides */\n.stagekitwp-landingpage-wrapper {\n";
        if ( $lp_bg     ) { echo '    --stagekitwp-lp-bg:      ' . esc_attr( $lp_bg )     . ";\n"; }
        if ( $lp_text   ) { echo '    --stagekitwp-lp-text:    ' . esc_attr( $lp_text )   . ";\n"; }
        if ( $lp_h1     ) { echo '    --stagekitwp-lp-heading: ' . esc_attr( $lp_h1 )     . ";\n"; }
        if ( $lp_h2     ) {
            echo '    --stagekitwp-lp-accent:  ' . esc_attr( $lp_h2 )   . ";\n";
            echo '    --stagekitwp-lp-btn-bg:  ' . esc_attr( $lp_h2 )   . ";\n";
        }
        if ( $lp_border ) { echo '    --stagekitwp-lp-border:  ' . esc_attr( $lp_border ) . ";\n"; }
        if ( $lp_font ) {
            echo '    --stagekitwp-lp-font:         ' . esc_attr( $lp_font )  . ";\n";
            if ( function_exists( 'stagekitwp_lp_maybe_enqueue_google_font' ) ) {
                stagekitwp_lp_maybe_enqueue_google_font( $lp_font_slug );
            }
        }
        if ( $lp_hfont ) {
            echo '    --stagekitwp-lp-heading-font: ' . esc_attr( $lp_hfont ) . ";\n";
            if ( function_exists( 'stagekitwp_lp_maybe_enqueue_google_font' ) ) {
                stagekitwp_lp_maybe_enqueue_google_font( $lp_hfont_slug );
            }
        }
        if ( $lp_hscale ) { echo '    --stagekitwp-lp-heading-scale:' . $lp_hscale . ";\n"; }
        if ( $lp_tscale ) { echo '    --stagekitwp-lp-text-scale:   ' . $lp_tscale . ";\n"; }
        if ( $lp_align )  { echo '    --stagekitwp-lp-align:        ' . esc_attr( $lp_align ) . ";\n"; }
        echo "}\n\n";
    }

    // ── [stagekitwp_landingpage] Dark mode overrides ────────────────────────────────
    // Raw-check before sanitize: stagekitwp_sanitize_css_color('') returns '#000000' not ''
    $lp_bg_d_raw     = get_option( 'stagekitwp_landing_page_bg_color_dark',     '' );
    $lp_text_d_raw   = get_option( 'stagekitwp_landing_page_text_color_dark',   '' );
    $lp_h1_d_raw     = get_option( 'stagekitwp_landing_page_h1_color_dark',     '' );
    $lp_h2_d_raw     = get_option( 'stagekitwp_landing_page_h2_color_dark',     '' );
    $lp_border_d_raw = get_option( 'stagekitwp_landing_page_border_color_dark', '' );
    $lp_bg_d     = $lp_bg_d_raw     ? stagekitwp_sanitize_css_color( $lp_bg_d_raw )     : '';
    $lp_text_d   = $lp_text_d_raw   ? stagekitwp_sanitize_css_color( $lp_text_d_raw )   : '';
    $lp_h1_d     = $lp_h1_d_raw     ? stagekitwp_sanitize_css_color( $lp_h1_d_raw )     : '';
    $lp_h2_d     = $lp_h2_d_raw     ? stagekitwp_sanitize_css_color( $lp_h2_d_raw )     : '';
    $lp_border_d = $lp_border_d_raw ? stagekitwp_sanitize_css_color( $lp_border_d_raw ) : '';

    // Generic dark mode fallback (always applied)
    echo "/* [stagekitwp_landingpage] dark mode fallback */\n";
    echo "html.stagekitwp-dark-mode .stagekitwp-landingpage-wrapper {\n";
    echo '    --stagekitwp-lp-bg:       ' . esc_attr( $d_safe_bg ?: '#1a1a2e' ) . ";\n";
    echo '    --stagekitwp-lp-text:     ' . esc_attr( $d_safe_text    ?: '#e0e0e0' ) . ";\n";
    echo '    --stagekitwp-lp-heading:  #ffffff;' . "\n";
    echo '    --stagekitwp-lp-accent:   #c0392b;' . "\n";
    echo '    --stagekitwp-lp-border:   rgba(255,255,255,0.12);' . "\n";
    echo '    --stagekitwp-lp-meta-bg:  rgba(255,255,255,0.05);' . "\n";
    echo '    --stagekitwp-lp-btn-bg:   #c0392b;' . "\n";
    echo '    --stagekitwp-lp-btn-text: #ffffff;' . "\n";
    echo "}\n\n";

    // Display Options dark overrides (only when explicitly saved)
    if ( $lp_bg_d || $lp_text_d || $lp_h1_d || $lp_h2_d || $lp_border_d ) {
        echo "/* [stagekitwp_landingpage] Display Options dark overrides */\nhtml.stagekitwp-dark-mode .stagekitwp-landingpage-wrapper {\n";
        if ( $lp_bg_d     ) { echo '    --stagekitwp-lp-bg:      ' . esc_attr( $lp_bg_d )     . " !important;\n"; }
        if ( $lp_text_d   ) { echo '    --stagekitwp-lp-text:    ' . esc_attr( $lp_text_d )   . " !important;\n"; }
        if ( $lp_h1_d     ) { echo '    --stagekitwp-lp-heading: ' . esc_attr( $lp_h1_d )     . " !important;\n"; }
        if ( $lp_h2_d     ) {
            echo '    --stagekitwp-lp-accent:  ' . esc_attr( $lp_h2_d ) . " !important;\n";
            echo '    --stagekitwp-lp-btn-bg:  ' . esc_attr( $lp_h2_d ) . " !important;\n";
        }
        if ( $lp_border_d ) { echo '    --stagekitwp-lp-border:  ' . esc_attr( $lp_border_d ) . " !important;\n"; }
        echo "}\n\n";
    }

echo "</style>\n";
}
add_action( 'wp_head', 'stagekitwp_output_shortcode_theme_integration_css', 20 );

// ─────────────────────────────────────────────────────────────────────────────
// 4. Extend the JS color-mode-switcher with shortcode selector knowledge
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Pass the shortcode wrapper selector list to the JS switcher so it can
 * include them in its inline-style restore/patch cycle (applyDark / applyLight).
 * Only runs when the TM theme is active and the switcher is enabled.
 */
function stagekitwp_integration_js_data() {
    if ( is_admin() ) { return; } // never inject frontend JS data on admin pages
    if ( ! stagekitwp_theme_is_active() ) { return; }
    if ( ! stagekitwp_get_theme_mod( 'stagekitwp_enable_frontend_switcher', false ) ) { return; }
    if ( ! wp_script_is( 'stagekitwp-color-mode-switcher', 'enqueued' ) ) { return; }

    // Generic dark tokens (from theme Customizer)
    $d_surf  = stagekitwp_get_theme_mod( 'stagekitwp_color_surface_bg_dark',   '#1e1e1e' );
    $d_text  = stagekitwp_get_theme_mod( 'stagekitwp_color_body_text_dark',    '#e0e0e0' );
    $d_head  = stagekitwp_get_theme_mod( 'stagekitwp_color_heading_text_dark', '#ffffff'  );
    $d_bord  = 'rgba(255,255,255,0.12)';
    $d_meta  = 'rgba(255,255,255,0.05)';
    $d_accent = '#c0392b';

    // Tickets dark button colour — mirrors the luminance-check in stagekitwp_output_shortcode_theme_integration_css().
    // $d_safe_link lives in that function's scope only, so we derive it again here.
    $raw_dark_link = get_option( 'stagekitwp_tickets_link_color_dark', '' );
    $d_safe_link   = '#1a78c2'; // WCAG AA vs white (~4.6:1)
    if ( $raw_dark_link && strlen( $raw_dark_link ) >= 4 ) {
        $hex = ltrim( $raw_dark_link, '#' );
        if ( strlen( $hex ) === 3 ) { $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2]; }
        if ( strlen( $hex ) === 6 ) {
            $r = hexdec( substr( $hex, 0, 2 ) ) / 255;
            $g = hexdec( substr( $hex, 2, 2 ) ) / 255;
            $b = hexdec( substr( $hex, 4, 2 ) ) / 255;
            $lum = 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
            if ( $lum < 0.18 ) { $d_safe_link = $raw_dark_link; }
        }
    }

    // Landing page Display Options dark overrides (fall back to generic dark tokens).
    // Must check raw option value first — stagekitwp_sanitize_css_color('') returns '#000000', not ''.
    $lp_d_bg_raw     = get_option( 'stagekitwp_landing_page_bg_color_dark',     '' );
    $lp_d_text_raw   = get_option( 'stagekitwp_landing_page_text_color_dark',   '' );
    $lp_d_h1_raw     = get_option( 'stagekitwp_landing_page_h1_color_dark',     '' );
    $lp_d_h2_raw     = get_option( 'stagekitwp_landing_page_h2_color_dark',     '' );
    $lp_d_border_raw = get_option( 'stagekitwp_landing_page_border_color_dark', '' );
    $lp_d_bg     = $lp_d_bg_raw     ? stagekitwp_sanitize_css_color( $lp_d_bg_raw )     : $d_surf;
    $lp_d_text   = $lp_d_text_raw   ? stagekitwp_sanitize_css_color( $lp_d_text_raw )   : $d_text;
    $lp_d_h1     = $lp_d_h1_raw     ? stagekitwp_sanitize_css_color( $lp_d_h1_raw )     : $d_head;
    $lp_d_h2     = $lp_d_h2_raw     ? stagekitwp_sanitize_css_color( $lp_d_h2_raw )     : $d_accent;
    $lp_d_border = $lp_d_border_raw ? stagekitwp_sanitize_css_color( $lp_d_border_raw ) : $d_bord;

    $dark = array(
        'surface_bg'    => $d_surf,
        'body_text'     => $d_text,
        'heading_text'  => $d_head,
        'border'        => $d_bord,
        // Tickets block — JS patcher uses vars-only approach for clean restore
        'tk_btn'        => $d_safe_link,
        // Landing page vars — always present so JS has everything it needs
        'lp_bg'         => $lp_d_bg,
        'lp_text'       => $lp_d_text,
        'lp_heading'    => $lp_d_h1,
        'lp_accent'     => $lp_d_h2,
        'lp_border'     => $lp_d_border,
        'lp_meta_bg'    => $d_meta,
        'lp_btn_bg'     => $lp_d_h2,
        'lp_btn_text'   => '#ffffff',
    );

    // Selectors the JS switcher should patch for dark mode.
    // The CSS rules above handle the class-based toggle; this covers any
    // residual inline style="" attributes that CSS !important can't beat.
    $selectors = array_keys( stagekitwp_integration_tabs() );

    wp_add_inline_script(
        'stagekitwp-color-mode-switcher',
        'var stagekitwpShortcodeDark = ' . wp_json_encode( $dark ) . ';
var stagekitwpShortcodeSelectors = ' . wp_json_encode( array_values( stagekitwp_integration_tabs() ) ) . ';',
        'before'
    );
}
add_action( 'wp_enqueue_scripts', 'stagekitwp_integration_js_data', 15 );
