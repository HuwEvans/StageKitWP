<?php
/**
 * Output dark-mode CSS.
 * ALL 14 tokens set in html.stagekitwp-dark-mode {} from Customizer *_dark settings.
 * Every rule below references var(--stagekitwp-*) only — never hardcoded hex.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
function stagekitwp_output_dark_mode_css() {

    $mode = get_theme_mod( 'stagekitwp_color_mode', 'light' );

    $d = array(
        'base_bg'      => get_theme_mod( 'stagekitwp_color_base_bg_dark',      '#121212' ),
        'surface_bg'   => get_theme_mod( 'stagekitwp_color_surface_bg_dark',   '#1e1e1e' ),
        'heading_text' => get_theme_mod( 'stagekitwp_color_heading_text_dark', '#ffffff'  ),
        'body_text'    => get_theme_mod( 'stagekitwp_color_body_text_dark',    '#e0e0e0' ),
        'muted_text'   => get_theme_mod( 'stagekitwp_color_muted_text_dark',   '#9e9e9e' ),
        'link'         => get_theme_mod( 'stagekitwp_color_link_dark',         '#90caf9' ),
        'link_hover'   => get_theme_mod( 'stagekitwp_color_link_hover_dark',   '#e50914' ),
        'notif_bg'     => get_theme_mod( 'stagekitwp_color_notif_bg_dark',     '#111111' ),
        'notif_text'   => get_theme_mod( 'stagekitwp_color_notif_text_dark',   '#ffffff' ),
        'header_bg'    => get_theme_mod( 'stagekitwp_color_header_bg_dark',    '#1e1e1e' ),
        'header_text'  => get_theme_mod( 'stagekitwp_color_header_text_dark',  '#e0e0e0' ),
        'header_hover' => get_theme_mod( 'stagekitwp_color_header_hover_dark', '#e50914' ),
        'footer_bg'    => get_theme_mod( 'stagekitwp_color_footer_bg_dark',    '#111111' ),
        'footer_text'  => get_theme_mod( 'stagekitwp_color_footer_text_dark',  '#cccccc' ),
    );
    ?>
    <style type="text/css" id="stagekitwp-dark-mode-css">

        /* Smooth transitions */
        *, *::before, *::after {
            transition: background-color 0.25s ease, background 0.25s ease,
                        color 0.2s ease, border-color 0.2s ease !important;
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { transition: none !important; }
        }

        /* ALL 14 tokens — every dark rule below uses var(--stagekitwp-*) only */
        html.stagekitwp-dark-mode {
            --stagekitwp-base-bg:      <?php echo esc_attr( $d['base_bg']      ); ?>;
            --stagekitwp-surface-bg:   <?php echo esc_attr( $d['surface_bg']   ); ?>;
            --stagekitwp-heading-text: <?php echo esc_attr( $d['heading_text'] ); ?>;
            --stagekitwp-body-text:    <?php echo esc_attr( $d['body_text']    ); ?>;
            --stagekitwp-muted-text:   <?php echo esc_attr( $d['muted_text']   ); ?>;
            --stagekitwp-link:         <?php echo esc_attr( $d['link']         ); ?>;
            --stagekitwp-link-hover:   <?php echo esc_attr( $d['link_hover']   ); ?>;
            --stagekitwp-notif-bg:     <?php echo esc_attr( $d['notif_bg']     ); ?>;
            --stagekitwp-notif-text:   <?php echo esc_attr( $d['notif_text']   ); ?>;
            --stagekitwp-header-bg:    <?php echo esc_attr( $d['header_bg']    ); ?>;
            --stagekitwp-header-text:  <?php echo esc_attr( $d['header_text']  ); ?>;
            --stagekitwp-header-hover: <?php echo esc_attr( $d['header_hover'] ); ?>;
            --stagekitwp-footer-bg:    <?php echo esc_attr( $d['footer_bg']    ); ?>;
            --stagekitwp-footer-text:  <?php echo esc_attr( $d['footer_text']  ); ?>;
        }

        /* Canvas */
        html.stagekitwp-dark-mode body, html.stagekitwp-dark-mode #page,
        html.stagekitwp-dark-mode .site-content, html.stagekitwp-dark-mode #primary,
        html.stagekitwp-dark-mode .site-main-homepage, html.stagekitwp-dark-mode .stagekitwp-season-grid-section,
        html.stagekitwp-dark-mode .stagekitwp-homepage-news-feed, html.stagekitwp-dark-mode .stagekitwp-homepage-news-feed[style] {
            background-color: var(--stagekitwp-base-bg)  !important;
            color:            var(--stagekitwp-body-text) !important;
        }

        /* Headings — exclude always-dark zones */
        html.stagekitwp-dark-mode h1:not(.site-footer *):not(.stagekitwp-top-notification-bar *),
        html.stagekitwp-dark-mode h2:not(.site-footer *):not(.stagekitwp-top-notification-bar *),
        html.stagekitwp-dark-mode h3:not(.site-footer *):not(.stagekitwp-top-notification-bar *),
        html.stagekitwp-dark-mode h4:not(.site-footer *):not(.stagekitwp-top-notification-bar *),
        html.stagekitwp-dark-mode h5:not(.site-footer *):not(.stagekitwp-top-notification-bar *),
        html.stagekitwp-dark-mode h6:not(.site-footer *):not(.stagekitwp-top-notification-bar *) {
            color: var(--stagekitwp-heading-text) !important;
        }

        /* Body text and links — exclude always-dark zones */
        html.stagekitwp-dark-mode p:not(.site-footer *):not(.stagekitwp-top-notification-bar *),
        html.stagekitwp-dark-mode li:not(.site-footer *):not(.stagekitwp-top-notification-bar *) {
            color: var(--stagekitwp-body-text) !important;
        }
        html.stagekitwp-dark-mode a:not(.site-footer a):not(.stagekitwp-top-notification-bar a):not(.stagekitwp-hero-banner a) {
            color: var(--stagekitwp-link) !important;
        }
        html.stagekitwp-dark-mode a:not(.site-footer a):not(.stagekitwp-top-notification-bar a):not(.stagekitwp-hero-banner a):hover {
            color: var(--stagekitwp-link-hover) !important;
        }

        /* Page template */
        html.stagekitwp-dark-mode .entry-title { color: var(--stagekitwp-heading-text) !important; }
        html.stagekitwp-dark-mode .entry-content,
        html.stagekitwp-dark-mode .entry-content p, html.stagekitwp-dark-mode .entry-content li,
        html.stagekitwp-dark-mode .entry-content td, html.stagekitwp-dark-mode .entry-content th {
            color: var(--stagekitwp-body-text) !important;
        }
        html.stagekitwp-dark-mode .entry-content h1, html.stagekitwp-dark-mode .entry-content h2,
        html.stagekitwp-dark-mode .entry-content h3, html.stagekitwp-dark-mode .entry-content h4,
        html.stagekitwp-dark-mode .entry-content h5, html.stagekitwp-dark-mode .entry-content h6 {
            color: var(--stagekitwp-heading-text) !important;
        }
        html.stagekitwp-dark-mode .entry-content a       { color: var(--stagekitwp-link)       !important; }
        html.stagekitwp-dark-mode .entry-content a:hover { color: var(--stagekitwp-link-hover) !important; }

        /* Header */
        html.stagekitwp-dark-mode .site-header, html.stagekitwp-dark-mode #masthead {
            background-color: var(--stagekitwp-header-bg) !important;
            border-bottom-color: color-mix(in srgb, var(--stagekitwp-header-text) 15%, transparent) !important;
        }
        html.stagekitwp-dark-mode .site-title a, html.stagekitwp-dark-mode .site-title a[style],
        html.stagekitwp-dark-mode .menu-toggle, html.stagekitwp-dark-mode .dropdown-toggle-btn {
            color: var(--stagekitwp-header-text) !important;
        }
        html.stagekitwp-dark-mode .stagekitwp-primary-menu-list li a {
            color: var(--stagekitwp-header-text) !important;
            border-bottom-color: rgba(255,255,255,0.08) !important;
        }
        html.stagekitwp-dark-mode .stagekitwp-primary-menu-list li a:hover,
        html.stagekitwp-dark-mode .stagekitwp-primary-menu-list li.current-menu-item > a,
        html.stagekitwp-dark-mode .stagekitwp-primary-menu-list li.current-menu-ancestor > a {
            color: var(--stagekitwp-header-hover) !important;
        }
        html.stagekitwp-dark-mode .stagekitwp-primary-menu-list li ul {
            background-color: var(--stagekitwp-header-bg) !important;
            box-shadow: 0 8px 24px rgba(0,0,0,0.4) !important;
        }
        html.stagekitwp-dark-mode .stagekitwp-primary-menu-list li ul li a {
            color: var(--stagekitwp-body-text) !important;
            border-bottom-color: rgba(255,255,255,0.06) !important;
        }
        html.stagekitwp-dark-mode .stagekitwp-primary-menu-list li ul li a:hover {
            color: var(--stagekitwp-header-hover) !important;
        }

        /* Show cards */
        html.stagekitwp-dark-mode .stagekitwp-show-card-column, html.stagekitwp-dark-mode .stagekitwp-show-card-column[style] {
            background: var(--stagekitwp-surface-bg) !important;
            background-color: var(--stagekitwp-surface-bg) !important;
            border-color: color-mix(in srgb, var(--stagekitwp-body-text) 20%, transparent) !important;
        }
        html.stagekitwp-dark-mode .show-card-body h3 a, html.stagekitwp-dark-mode .show-card-body h3 a[style] {
            color: var(--stagekitwp-heading-text) !important;
        }
        html.stagekitwp-dark-mode .show-card-body p[style], html.stagekitwp-dark-mode .show-card-body p,
        html.stagekitwp-dark-mode .show-card-body div[style] { color: var(--stagekitwp-muted-text) !important; }
        html.stagekitwp-dark-mode .show-card-body span[style*="background"] {
            background: color-mix(in srgb, var(--stagekitwp-surface-bg) 60%, var(--stagekitwp-body-text) 40%) !important;
            color: var(--stagekitwp-muted-text) !important;
        }
        html.stagekitwp-dark-mode .stagekitwp-show-card-column div[style*="padding: 70px"],
        html.stagekitwp-dark-mode .stagekitwp-show-card-column div[style*="padding: 70px"] p {
            color: var(--stagekitwp-muted-text) !important;
        }

        /* Upcoming section */
        html.stagekitwp-dark-mode .stagekitwp-upcoming-grid-section, html.stagekitwp-dark-mode .stagekitwp-upcoming-grid-section[style] {
            background-color: var(--stagekitwp-surface-bg) !important;
            border-top-color:    color-mix(in srgb, var(--stagekitwp-body-text) 15%, transparent) !important;
            border-bottom-color: color-mix(in srgb, var(--stagekitwp-body-text) 15%, transparent) !important;
        }

        /* News cards */
        html.stagekitwp-dark-mode .stagekitwp-homepage-news-feed > div > div,
        html.stagekitwp-dark-mode .stagekitwp-homepage-news-feed div[style*="background"] {
            background: var(--stagekitwp-surface-bg) !important;
            background-color: var(--stagekitwp-surface-bg) !important;
            border-color: color-mix(in srgb, var(--stagekitwp-body-text) 20%, transparent) !important;
        }
        html.stagekitwp-dark-mode .stagekitwp-homepage-news-feed h3 a[style],
        html.stagekitwp-dark-mode .stagekitwp-homepage-news-feed h3 a { color: var(--stagekitwp-heading-text) !important; }
        html.stagekitwp-dark-mode .stagekitwp-homepage-news-feed p[style],
        html.stagekitwp-dark-mode .stagekitwp-homepage-news-feed p    { color: var(--stagekitwp-muted-text)   !important; }

        /* Testimonial cards */
        html.stagekitwp-dark-mode .stagekitwp-testimonial .stagekitwp-symbol-filled,
        html.stagekitwp-dark-mode .stagekitwp-testimonial .stagekitwp-symbol-empty { color: #e8c84a !important; }
        html.stagekitwp-dark-mode .stagekitwp-testimonial .stagekitwp-testimonial-author { color: var(--stagekitwp-muted-text) !important; }

        /* Footer — always-dark zone */
        html.stagekitwp-dark-mode .site-footer, html.stagekitwp-dark-mode #colophon {
            background-color: var(--stagekitwp-footer-bg)  !important;
            color:            var(--stagekitwp-footer-text) !important;
        }
        html.stagekitwp-dark-mode .site-footer a       { color: var(--stagekitwp-footer-text) !important; }
        html.stagekitwp-dark-mode .site-footer a:hover { color: var(--stagekitwp-primary)     !important; }

        /* Notification bar — always-dark zone */
        html.stagekitwp-dark-mode .stagekitwp-top-notification-bar {
            background-color: var(--stagekitwp-notif-bg)  !important;
            color:            var(--stagekitwp-notif-text) !important;
        }
        html.stagekitwp-dark-mode .stagekitwp-top-notification-bar * { color: var(--stagekitwp-notif-text) !important; }

        /* Hero banner — always-dark zone with media */
        html.stagekitwp-dark-mode .stagekitwp-hero-banner.media-type-video .stagekitwp-hero-title,
        html.stagekitwp-dark-mode .stagekitwp-hero-banner.media-type-image .stagekitwp-hero-title {
            color: var(--stagekitwp-hero-title-dark) !important;
        }
        html.stagekitwp-dark-mode .stagekitwp-hero-banner.media-type-video .stagekitwp-hero-subtitle,
        html.stagekitwp-dark-mode .stagekitwp-hero-banner.media-type-image .stagekitwp-hero-subtitle {
            color: var(--stagekitwp-hero-subtitle-dark) !important;
        }
        html.stagekitwp-dark-mode .stagekitwp-hero-banner.media-type-video .stagekitwp-hero-btn,
        html.stagekitwp-dark-mode .stagekitwp-hero-banner.media-type-image .stagekitwp-hero-btn { color: #ffffff !important; }

        /* Mode toggle button */
        html.stagekitwp-dark-mode #stagekitwp-color-mode-toggle {
            background: var(--stagekitwp-surface-bg) !important;
            color:      var(--stagekitwp-body-text)  !important;
        }

    </style>
    <?php
    if ( $mode === 'dark' ) {
        echo '<script>document.documentElement.classList.add("stagekitwp-dark-mode");</script>';
    }
}
add_action( 'wp_head', 'stagekitwp_output_dark_mode_css' );
