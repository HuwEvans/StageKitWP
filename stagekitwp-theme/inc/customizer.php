<?php
/**
 * StageKitWP Theme Customizer Functionality
 *
 * @package StageKitWP_Theme
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function stagekitwp_theme_customize_register( $wp_customize ) {

    // =========================================================================
    // SECTION: Logo Settings & Positioning
    // =========================================================================
    $wp_customize->add_section( 'stagekitwp_logo_section', array(
        'title'       => __( 'Logo Settings', 'stagekitwp-theme' ),
        'priority'    => 25,
    ) );

    $wp_customize->add_setting( 'stagekitwp_custom_logo', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'stagekitwp_custom_logo', array(
        'label'    => __( 'Site Logo Image', 'stagekitwp-theme' ),
        'section'  => 'stagekitwp_logo_section',
    ) ) );

    $wp_customize->add_setting( 'stagekitwp_logo_dark', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'stagekitwp_logo_dark', array(
        'label'       => __( 'Dark Mode Logo Image', 'stagekitwp-theme' ),
        'description' => __( 'Shown when dark mode is active. If left empty, the light logo will be automatically inverted instead.', 'stagekitwp-theme' ),
        'section'     => 'stagekitwp_logo_section',
    ) ) );

    $wp_customize->add_setting( 'stagekitwp_logo_position', array(
        'default'           => 'left',
        'sanitize_callback' => 'stagekitwp_sanitize_logo_position',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'stagekitwp_logo_position', array(
        'label'    => __( 'Logo Placement Position', 'stagekitwp-theme' ),
        'section'  => 'stagekitwp_logo_section',
        'type'     => 'radio',
        'choices'  => array(
            'left'   => __( 'Left Side', 'stagekitwp-theme' ),
            'center' => __( 'Center Column', 'stagekitwp-theme' ),
            'right'  => __( 'Right Side', 'stagekitwp-theme' ),
        ),
    ) );

    // ── Tagline display ─────────────────────────────────────────────────────
    $wp_customize->add_setting( 'stagekitwp_show_tagline', array(
        'default'           => false,
        'sanitize_callback' => 'stagekitwp_sanitize_checkbox',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'stagekitwp_show_tagline', array(
        'label'       => __( 'Display Site Tagline', 'stagekitwp-theme' ),
        'description' => __( 'Show the tagline set in Customizer › Site Identity, or under Settings › General.', 'stagekitwp-theme' ),
        'section'     => 'stagekitwp_logo_section',
        'type'        => 'checkbox',
    ) );

    $wp_customize->add_setting( 'stagekitwp_tagline_position', array(
        'default'           => 'below-logo',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'stagekitwp_tagline_position', array(
        'label'   => __( 'Tagline Position', 'stagekitwp-theme' ),
        'section' => 'stagekitwp_logo_section',
        'type'    => 'select',
        'choices' => array(
            'below-logo'  => __( 'Below Logo / Site Name', 'stagekitwp-theme' ),
            'beside-logo' => __( 'Beside Logo (inline, right of image)', 'stagekitwp-theme' ),
            'header-end'  => __( 'Far End of Header (opposite the logo)', 'stagekitwp-theme' ),
        ),
    ) );

    // =========================================================================
    // SECTION: Header & Navigation Layout
    // =========================================================================
    $wp_customize->add_section( 'stagekitwp_header_section', array(
        'title'       => __( 'Header & Navigation Settings', 'stagekitwp-theme' ),
        'priority'    => 30,
    ) );

    $wp_customize->add_setting( 'stagekitwp_menu_alignment', array(
        'default'           => 'center',
        'sanitize_callback' => 'stagekitwp_sanitize_radio_alignment',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'stagekitwp_menu_alignment', array(
        'label'    => __( 'Main Menu Alignment', 'stagekitwp-theme' ),
        'section'  => 'stagekitwp_header_section',
        'type'     => 'radio',
        'choices'  => array(
            'left'   => __( 'Left Aligned Menu', 'stagekitwp-theme' ),
            'center' => __( 'Center Aligned Menu', 'stagekitwp-theme' ),
            'right'  => __( 'Right Aligned Menu', 'stagekitwp-theme' ),
        ),
    ) );

    $wp_customize->add_setting( 'stagekitwp_enable_sticky_header', array(
        'default'           => false,
        'sanitize_callback' => 'stagekitwp_sanitize_checkbox',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'stagekitwp_enable_sticky_header', array(
        'label'       => __( 'Enable Sticky Header', 'stagekitwp-theme' ),
        'description' => __( 'Keeps the header fixed at the top while scrolling.', 'stagekitwp-theme' ),
        'section'     => 'stagekitwp_header_section',
        'type'        => 'checkbox',
    ) );

    // =========================================================================
    // SECTION: Search Settings
    // =========================================================================
    $wp_customize->add_section( 'stagekitwp_search_section', array(
        'title'       => __( 'Search Settings', 'stagekitwp-theme' ),
        'priority'    => 31,
        'description' => __( 'Control which content types are included in frontend site search.', 'stagekitwp-theme' ),
    ) );

    $wp_customize->add_setting( 'stagekitwp_search_include_pages', array(
        'default'           => true,
        'sanitize_callback' => 'stagekitwp_sanitize_checkbox',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'stagekitwp_search_include_pages', array(
        'label'       => __( 'Include Pages In Search', 'stagekitwp-theme' ),
        'description' => __( 'When disabled, WordPress pages are removed from search results.', 'stagekitwp-theme' ),
        'section'     => 'stagekitwp_search_section',
        'type'        => 'checkbox',
    ) );

    $wp_customize->add_setting( 'stagekitwp_search_only_theatre_cpts', array(
        'default'           => false,
        'sanitize_callback' => 'stagekitwp_sanitize_checkbox',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'stagekitwp_search_only_theatre_cpts', array(
        'label'       => __( 'Only Search StageKitWP Content Types', 'stagekitwp-theme' ),
        'description' => __( 'Limits search to StageKitWP custom post types. If pages are enabled above, pages are still included.', 'stagekitwp-theme' ),
        'section'     => 'stagekitwp_search_section',
        'type'        => 'checkbox',
    ) );

    // =========================================================================
    // SECTION: Global Homepage Component Visibility Toggles
    // =========================================================================
    $wp_customize->add_section( 'stagekitwp_homepage_visibility_section', array(
        'title'       => __( 'Homepage Block Toggles', 'stagekitwp-theme' ),
        'priority'    => 31,
        'description' => __( 'Enable or disable major display grids across the home screen layout.', 'stagekitwp-theme' ),
    ) );

    $blocks = array(
        'stagekitwp_enable_hero'           => __( 'Display Hero Video/Image Banner', 'stagekitwp-theme' ),
        'stagekitwp_enable_current_season' => __( 'Display Current Season Grid', 'stagekitwp-theme' ),
        'stagekitwp_enable_news'           => __( 'Display News & Auditions Feed', 'stagekitwp-theme' ),
        'stagekitwp_enable_testimonials'   => __( 'Display Audience Testimonials Panel', 'stagekitwp-theme' ),
    );

    foreach ( $blocks as $setting_id => $label_text ) {
        $wp_customize->add_setting( $setting_id, array(
            'default'           => true,
            'sanitize_callback' => 'stagekitwp_sanitize_checkbox',
            'transport'         => 'postMessage',
        ) );
        $wp_customize->add_control( $setting_id, array(
            'label'           => $label_text,
            'section'         => 'stagekitwp_homepage_visibility_section',
            'type'            => 'checkbox',
            'active_callback' => 'is_front_page',
        ) );
    }

    // Upcoming Season Grid gets its own control: on/off plus two data-driven modes
    // that key off the StageKitWP Core "next season" helpers.
    $wp_customize->add_setting( 'stagekitwp_enable_upcoming_season', array(
        'default'           => 'on',
        'sanitize_callback' => 'stagekitwp_sanitize_upcoming_season_mode',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'stagekitwp_enable_upcoming_season', array(
        'label'           => __( 'Display Upcoming Season Grid', 'stagekitwp-theme' ),
        'description'     => __( 'Choose when the "Coming Soon" grid appears on the homepage.', 'stagekitwp-theme' ),
        'section'         => 'stagekitwp_homepage_visibility_section',
        'type'            => 'select',
        'choices'         => array(
            'off'          => __( 'Off', 'stagekitwp-theme' ),
            'on'           => __( 'On', 'stagekitwp-theme' ),
            'on_if_season' => __( 'On, if a next season exists', 'stagekitwp-theme' ),
            'on_if_shows'  => __( 'On, if the next season has show(s)', 'stagekitwp-theme' ),
        ),
        'active_callback' => 'is_front_page',
    ) );

    // =========================================================================
    // SECTION: Homepage News & Auditions Feed Options
    // =========================================================================
    $wp_customize->add_section( 'stagekitwp_homepage_news_section', array(
        'title'       => __( 'Homepage News Feed Options', 'stagekitwp-theme' ),
        'priority'    => 34,
        'description' => __( 'Filter which post categories appear and optionally include audition cards from the Shows content type.', 'stagekitwp-theme' ),
    ) );

    $wp_customize->add_setting( 'stagekitwp_news_post_categories', array(
        'default'           => '',
        'sanitize_callback' => 'stagekitwp_sanitize_slug_csv',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'stagekitwp_news_post_categories', array(
        'label'           => __( 'Include Post Categories (Slugs)', 'stagekitwp-theme' ),
        'description'     => __( 'Optional. Enter comma-separated category slugs (for example: news,announcements). Leave empty to include all categories.', 'stagekitwp-theme' ),
        'section'         => 'stagekitwp_homepage_news_section',
        'type'            => 'text',
        'active_callback' => 'is_front_page',
    ) );

    $wp_customize->add_setting( 'stagekitwp_news_include_auditions', array(
        'default'           => true,
        'sanitize_callback' => 'stagekitwp_sanitize_checkbox',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'stagekitwp_news_include_auditions', array(
        'label'           => __( 'Include Show Auditions', 'stagekitwp-theme' ),
        'description'     => __( 'When enabled, upcoming audition entries from Shows are merged into the homepage News feed.', 'stagekitwp-theme' ),
        'section'         => 'stagekitwp_homepage_news_section',
        'type'            => 'checkbox',
        'active_callback' => 'is_front_page',
    ) );

    $wp_customize->add_setting( 'stagekitwp_news_items_limit', array(
        'default'           => 6,
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'stagekitwp_news_items_limit', array(
        'label'           => __( 'Number of Feed Items', 'stagekitwp-theme' ),
        'description'     => __( 'Set how many cards appear in the homepage news carousel (3 to 10).', 'stagekitwp-theme' ),
        'section'         => 'stagekitwp_homepage_news_section',
        'type'            => 'number',
        'input_attrs'     => array(
            'min'  => 3,
            'max'  => 10,
            'step' => 1,
        ),
        'active_callback' => 'is_front_page',
    ) );

    $wp_customize->add_setting( 'stagekitwp_news_all_page_id', array(
        'default'           => 0,
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'stagekitwp_news_all_page_id', array(
        'label'           => __( 'All News Link Page', 'stagekitwp-theme' ),
        'description'     => __( 'Optional. Select a page to show an "All News" link under the homepage feed. Leave unselected to hide the link.', 'stagekitwp-theme' ),
        'section'         => 'stagekitwp_homepage_news_section',
        'type'            => 'dropdown-pages',
        'allow_addition'  => true,
        'active_callback' => 'is_front_page',
    ) );

    // =========================================================================
    // SECTION: Hero Banner Sub-Settings Panel
    // =========================================================================
    $wp_customize->add_section( 'stagekitwp_hero_section', array(
        'title'    => __( 'Hero Banner Media Assets', 'stagekitwp-theme' ),
        'priority' => 32,
    ) );

    $wp_customize->add_setting( 'stagekitwp_hero_headline', array(
        'default'           => __( 'Experience Live Performance', 'stagekitwp-theme' ),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'stagekitwp_hero_headline', array(
        'label'           => __( 'Hero Main Title', 'stagekitwp-theme' ),
        'section'         => 'stagekitwp_hero_section',
        'type'            => 'text',
        'active_callback' => 'is_front_page',
    ) );

    $wp_customize->add_setting( 'stagekitwp_hero_subheading', array(
        'default'           => __( 'Discover a thrilling season of award-winning dramas, comedies, and musicals.', 'stagekitwp-theme' ),
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'stagekitwp_hero_subheading', array(
        'label'           => __( 'Hero Subheading Description', 'stagekitwp-theme' ),
        'section'         => 'stagekitwp_hero_section',
        'type'            => 'textarea',
        'active_callback' => 'is_front_page',
    ) );

    $wp_customize->add_setting( 'stagekitwp_hero_btn_url', array(
        'default'           => home_url( '/tickets' ),
        'sanitize_callback' => 'esc_url_raw',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'stagekitwp_hero_btn_url', array(
        'label'           => __( 'Primary Button URL Link', 'stagekitwp-theme' ),
        'section'         => 'stagekitwp_hero_section',
        'type'            => 'text',
        'active_callback' => 'is_front_page',
    ) );

    $wp_customize->add_setting( 'stagekitwp_hero_btn_placement', array(
        'default'           => 'content',
        'sanitize_callback' => 'stagekitwp_sanitize_hero_cta_placement',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'stagekitwp_hero_btn_placement', array(
        'label'           => __( 'Primary Button Placement', 'stagekitwp-theme' ),
        'description'     => __( 'Choose where the hero CTA button sits within the banner.', 'stagekitwp-theme' ),
        'section'         => 'stagekitwp_hero_section',
        'type'            => 'select',
        'choices'         => array(
            'content'       => __( 'Below Hero Text', 'stagekitwp-theme' ),
            'top-left'      => __( 'Top Left', 'stagekitwp-theme' ),
            'top-center'    => __( 'Top Center', 'stagekitwp-theme' ),
            'top-right'     => __( 'Top Right', 'stagekitwp-theme' ),
            'center-left'   => __( 'Center Left', 'stagekitwp-theme' ),
            'center'        => __( 'Center', 'stagekitwp-theme' ),
            'center-right'  => __( 'Center Right', 'stagekitwp-theme' ),
            'bottom-left'   => __( 'Bottom Left', 'stagekitwp-theme' ),
            'bottom-center' => __( 'Bottom Center', 'stagekitwp-theme' ),
            'bottom-right'  => __( 'Bottom Right', 'stagekitwp-theme' ),
        ),
        'active_callback' => 'is_front_page',
    ) );

    $wp_customize->add_setting( 'stagekitwp_hero_btn_pad_x', array(
        'default'           => 24,
        'sanitize_callback' => 'stagekitwp_sanitize_hero_cta_padding',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'stagekitwp_hero_btn_pad_x', array(
        'label'           => __( 'CTA Horizontal Padding (px)', 'stagekitwp-theme' ),
        'description'     => __( 'Distance between the CTA and the left or right edge of the hero media.', 'stagekitwp-theme' ),
        'section'         => 'stagekitwp_hero_section',
        'type'            => 'number',
        'input_attrs'     => array(
            'min'  => 0,
            'max'  => 160,
            'step' => 2,
        ),
        'active_callback' => 'is_front_page',
    ) );

    $wp_customize->add_setting( 'stagekitwp_hero_btn_pad_y', array(
        'default'           => 24,
        'sanitize_callback' => 'stagekitwp_sanitize_hero_cta_padding',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'stagekitwp_hero_btn_pad_y', array(
        'label'           => __( 'CTA Vertical Padding (px)', 'stagekitwp-theme' ),
        'description'     => __( 'Distance between the CTA and the top or bottom edge of the hero media.', 'stagekitwp-theme' ),
        'section'         => 'stagekitwp_hero_section',
        'type'            => 'number',
        'input_attrs'     => array(
            'min'  => 0,
            'max'  => 160,
            'step' => 2,
        ),
        'active_callback' => 'is_front_page',
    ) );

    $wp_customize->add_setting( 'stagekitwp_hero_btn_safe_area', array(
        'default'           => true,
        'sanitize_callback' => 'stagekitwp_sanitize_checkbox',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'stagekitwp_hero_btn_safe_area', array(
        'label'           => __( 'Enable CTA Safe Area', 'stagekitwp-theme' ),
        'description'     => __( 'Keeps left and right CTA placements out of the central hero text block on wide screens.', 'stagekitwp-theme' ),
        'section'         => 'stagekitwp_hero_section',
        'type'            => 'checkbox',
        'active_callback' => 'is_front_page',
    ) );

    $wp_customize->add_setting( 'stagekitwp_hero_media_type', array(
        'default'           => 'image',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'stagekitwp_hero_media_type', array(
        'label'           => __( 'Hero Background Media Type', 'stagekitwp-theme' ),
        'section'         => 'stagekitwp_hero_section',
        'type'            => 'select',
        'choices'         => array(
            'image' => __( 'Static Featured Image', 'stagekitwp-theme' ),
            'video' => __( 'Background Video Loop', 'stagekitwp-theme' ),
        ),
        'active_callback' => 'is_front_page',
    ) );

    $wp_customize->add_setting( 'stagekitwp_hero_bg_image', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'stagekitwp_hero_bg_image', array(
        'label'           => __( 'Hero Background Image', 'stagekitwp-theme' ),
        'section'         => 'stagekitwp_hero_section',
        'active_callback' => 'is_front_page',
    ) ) );

    $wp_customize->add_setting( 'stagekitwp_hero_bg_video', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( new WP_Customize_Upload_Control( $wp_customize, 'stagekitwp_hero_bg_video', array(
        'label'           => __( 'Hero Background Video (.mp4)', 'stagekitwp-theme' ),
        'description'     => __( 'Recommended main video size: 1920 x 1080 px MP4 in landscape orientation. Keep the focal point centred because the video scales responsively.', 'stagekitwp-theme' ),
        'mime_type'       => 'video',
        'button_labels'   => array(
            'select'       => __( 'Select Video', 'stagekitwp-theme' ),
            'change'       => __( 'Change Video', 'stagekitwp-theme' ),
            'remove'       => __( 'Remove', 'stagekitwp-theme' ),
            'default'      => __( 'Default', 'stagekitwp-theme' ),
            'placeholder'  => __( 'No video selected', 'stagekitwp-theme' ),
            'frame_title'  => __( 'Select Hero Video', 'stagekitwp-theme' ),
            'frame_button' => __( 'Choose Video', 'stagekitwp-theme' ),
        ),
        'section'         => 'stagekitwp_hero_section',
        'active_callback' => 'is_front_page',
    ) ) );

    $wp_customize->add_setting( 'stagekitwp_hero_bg_video_mobile', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( new WP_Customize_Upload_Control( $wp_customize, 'stagekitwp_hero_bg_video_mobile', array(
        'label'           => __( 'Hero Mobile Video (.mp4)', 'stagekitwp-theme' ),
        'description'     => __( 'Optional. Used on screens 768 px wide and below. Recommended mobile video size: 1080 x 1920 px MP4 in portrait orientation.', 'stagekitwp-theme' ),
        'mime_type'       => 'video',
        'button_labels'   => array(
            'select'       => __( 'Select Mobile Video', 'stagekitwp-theme' ),
            'change'       => __( 'Change Mobile Video', 'stagekitwp-theme' ),
            'remove'       => __( 'Remove', 'stagekitwp-theme' ),
            'default'      => __( 'Default', 'stagekitwp-theme' ),
            'placeholder'  => __( 'No video selected', 'stagekitwp-theme' ),
            'frame_title'  => __( 'Select Hero Mobile Video', 'stagekitwp-theme' ),
            'frame_button' => __( 'Choose Mobile Video', 'stagekitwp-theme' ),
        ),
        'section'         => 'stagekitwp_hero_section',
        'active_callback' => 'is_front_page',
    ) ) );

    // ── Hero Text Colours ─────────────────────────────────────────────────────
    $wp_customize->add_setting( 'stagekitwp_hero_title_color_light', array(
        'default'           => '#ffffff',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'stagekitwp_hero_title_color_light', array(
        'label'           => __( 'Title Colour — Light Mode', 'stagekitwp-theme' ),
        'description'     => __( 'Hero main title colour when light mode is active.', 'stagekitwp-theme' ),
        'section'         => 'stagekitwp_hero_section',
        'active_callback' => 'is_front_page',
    ) ) );

    $wp_customize->add_setting( 'stagekitwp_hero_title_color_dark', array(
        'default'           => '#ffffff',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'stagekitwp_hero_title_color_dark', array(
        'label'           => __( 'Title Colour — Dark Mode', 'stagekitwp-theme' ),
        'description'     => __( 'Hero main title colour when dark mode is active.', 'stagekitwp-theme' ),
        'section'         => 'stagekitwp_hero_section',
        'active_callback' => 'is_front_page',
    ) ) );

    $wp_customize->add_setting( 'stagekitwp_hero_subtitle_color_light', array(
        'default'           => '#dddddd',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'stagekitwp_hero_subtitle_color_light', array(
        'label'           => __( 'Subheading Colour — Light Mode', 'stagekitwp-theme' ),
        'description'     => __( 'Hero subtitle colour when light mode is active.', 'stagekitwp-theme' ),
        'section'         => 'stagekitwp_hero_section',
        'active_callback' => 'is_front_page',
    ) ) );

    $wp_customize->add_setting( 'stagekitwp_hero_subtitle_color_dark', array(
        'default'           => '#dddddd',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'stagekitwp_hero_subtitle_color_dark', array(
        'label'           => __( 'Subheading Colour — Dark Mode', 'stagekitwp-theme' ),
        'description'     => __( 'Hero subtitle colour when dark mode is active.', 'stagekitwp-theme' ),
        'section'         => 'stagekitwp_hero_section',
        'active_callback' => 'is_front_page',
    ) ) );

    // ── Hero Overlay Controls ─────────────────────────────────────────────────
    $wp_customize->add_setting( 'stagekitwp_hero_overlay_color', array(
        'default'           => '#000000',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'stagekitwp_hero_overlay_color', array(
        'label'           => __( 'Overlay Colour', 'stagekitwp-theme' ),
        'description'     => __( 'Tint colour for the overlay sheet placed over hero media.', 'stagekitwp-theme' ),
        'section'         => 'stagekitwp_hero_section',
        'active_callback' => 'is_front_page',
    ) ) );

    $wp_customize->add_setting( 'stagekitwp_hero_overlay_opacity', array(
        'default'           => 60,
        'sanitize_callback' => 'stagekitwp_sanitize_hero_overlay_opacity',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'stagekitwp_hero_overlay_opacity', array(
        'label'           => __( 'Overlay Opacity (%)', 'stagekitwp-theme' ),
        'description'     => __( 'Percentage of overlay opacity (0% = transparent, 100% = fully opaque).', 'stagekitwp-theme' ),
        'section'         => 'stagekitwp_hero_section',
        'type'            => 'number',
        'input_attrs'     => array(
            'min'  => 0,
            'max'  => 100,
            'step' => 5,
        ),
        'active_callback' => 'is_front_page',
    ) );

    // =========================================================================
    // SECTION: Show Card — Term Badge
    // =========================================================================
    $wp_customize->add_section( 'stagekitwp_term_badge_section', array(
        'title'       => __( 'Show Card — Term Badge', 'stagekitwp-theme' ),
        'description' => __( 'Controls the season slot label (e.g. "Fall Production") shown on each show card.', 'stagekitwp-theme' ),
        'priority'    => 33,
    ) );

    // Show / hide toggle
    $wp_customize->add_setting( 'stagekitwp_show_term_badge', array(
        'default'           => true,
        'sanitize_callback' => 'stagekitwp_sanitize_checkbox',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'stagekitwp_show_term_badge', array(
        'label'   => __( 'Display Term Badge', 'stagekitwp-theme' ),
        'section' => 'stagekitwp_term_badge_section',
        'type'    => 'checkbox',
    ) );

    // Position
    $wp_customize->add_setting( 'stagekitwp_term_badge_position', array(
        'default'           => 'top-bar',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'stagekitwp_term_badge_position', array(
        'label'   => __( 'Badge Position', 'stagekitwp-theme' ),
        'section' => 'stagekitwp_term_badge_section',
        'type'    => 'select',
        'choices' => array(
            'top-bar'       => __( 'Top Bar (full-width strip above image)', 'stagekitwp-theme' ),
            'over-image'    => __( 'Over Image (bottom-left overlay on poster)', 'stagekitwp-theme' ),
            'inside-card'   => __( 'Inside Card (below image, above title)', 'stagekitwp-theme' ),
        ),
    ) );

    // =========================================================================
    // SECTION: Notification & Countdown Bar Options
    // =========================================================================
    $wp_customize->add_section( 'stagekitwp_notification_section', array(
        'title'    => __( 'Notification & Countdown Bar', 'stagekitwp-theme' ),
        'priority' => 35,
    ) );

    $wp_customize->add_setting( 'stagekitwp_enable_countdown', array(
        'default'           => true,
        'sanitize_callback' => 'stagekitwp_sanitize_checkbox',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'stagekitwp_enable_countdown', array(
        'label'       => __( 'Enable Top Countdown Bar', 'stagekitwp-theme' ),
        'section'     => 'stagekitwp_notification_section',
        'type'        => 'checkbox',
    ) );

    // NEW: Notification bar layout alignment options
    $wp_customize->add_setting( 'stagekitwp_notification_alignment', array(
        'default'           => 'center',
        'sanitize_callback' => 'stagekitwp_sanitize_radio_alignment',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'stagekitwp_notification_alignment', array(
        'label'    => __( 'Notification Text Alignment', 'stagekitwp-theme' ),
        'section'  => 'stagekitwp_notification_section',
        'type'     => 'select',
        'choices'  => array(
            'left'   => __( 'Left Align', 'stagekitwp-theme' ),
            'center' => __( 'Center Align', 'stagekitwp-theme' ),
            'right'  => __( 'Right Align', 'stagekitwp-theme' ),
        ),
    ) );

    // CALCULATE AUTOMATED SHOW FOR CUSTOMIZER DISPLAY NOTICE
    $automated_show_status = __( 'No automated show active (Check date windows or active season toggle).', 'stagekitwp-theme' );
    
    if ( post_type_exists( 'show' ) && post_type_exists( 'season' ) ) {
        $current_month_day = date( 'md' );
        $target_slot       = '';

        if ( $current_month_day >= '0701' && $current_month_day <= '1115' ) {
            $target_slot = 'Fall';
        } elseif ( ( $current_month_day >= '1116' && $current_month_day <= '1231' ) || ( $current_month_day >= '0101' && $current_month_day <= '0217' ) ) {
            $target_slot = 'Winter';
        } elseif ( $current_month_day >= '0218' && $current_month_day <= '0630' ) {
            $target_slot = 'Spring';
        }

        if ( ! empty( $target_slot ) ) {
            $season_lookup = get_posts( array(
                'post_type'      => 'season',
                'posts_per_page' => 1,
                'meta_key'       => '_stagekitwp_season_is_current',
                'meta_value'     => '1',
                'fields'         => 'ids'
            ) );
            $season_id = ! empty( $season_lookup ) ? $season_lookup[0] : 0;

            if ( $season_id ) {
                $show_query = new WP_Query( array(
                    'post_type'      => 'show',
                    'posts_per_page' => 1,
                    'meta_query'     => array(
                        'relation' => 'AND',
                        array( 'key' => '_stagekitwp_show_time_slot', 'value' => $target_slot, 'compare' => '=' ),
                        array( 'key' => '_stagekitwp_show_season', 'value' => $season_id, 'compare' => '=' )
                    )
                ) );

                if ( $show_query->have_posts() ) {
                    $show_query->the_post();
                    $show_dates = get_post_meta( get_the_ID(), '_stagekitwp_show_show_dates', true );
                    $automated_show_status = sprintf( 
                        __( 'Active Automated Production: "%1$s" [%2$s Slot] targeting date: %3$s', 'stagekitwp-theme' ), 
                        get_the_title(), 
                        $target_slot,
                        ( ! empty( $show_dates ) ? $show_dates : __( 'No Meta Date Specified', 'stagekitwp-theme' ) )
                    );
                }
                wp_reset_postdata();
            }
        }
    }

    // Customizer UI Informational Section
    $wp_customize->add_setting( 'stagekitwp_automated_status_placeholder', array(
        'sanitize_callback' => 'sanitize_text_field'
    ) );
    $wp_customize->add_control( 'stagekitwp_automated_status_placeholder', array(
        'label'       => __( 'Automated Detection Status', 'stagekitwp-theme' ),
        'description' => '<span style="display:block; padding:8px; background:#f0f0f1; border-left:3px solid #e50914; font-weight:500; font-style:italic; color:#2c3338;">' . esc_html( $automated_show_status ) . '</span>',
        'section'     => 'stagekitwp_notification_section',
        'type'        => 'hidden', 
    ) );

    $wp_customize->add_setting( 'stagekitwp_next_show_timestamp', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'stagekitwp_next_show_timestamp', array(
        'label'       => __( 'Fallback Static Target Date/Time', 'stagekitwp-theme' ),
        'description' => __( 'Used if automated calculation cannot find an active show production loop entry.', 'stagekitwp-theme' ),
        'section'     => 'stagekitwp_notification_section',
        'type'        => 'text',
    ) );

    // =========================================================================
    // SECTION: Footer Options
    // =========================================================================
    $wp_customize->add_section( 'stagekitwp_footer_section', array(
        'title'    => __( 'Footer Options', 'stagekitwp-theme' ),
        'priority' => 45,
    ) );
    $wp_customize->add_setting( 'stagekitwp_footer_width_layout', array(
        'default'           => 'fixed',
        'sanitize_callback' => 'stagekitwp_sanitize_footer_layout',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'stagekitwp_footer_width_layout', array(
        'label'    => __( 'Footer Outer Layout Width', 'stagekitwp-theme' ),
        'section'  => 'stagekitwp_footer_section',
        'type'     => 'select',
        'choices'  => array(
            'fixed' => __( 'Fixed / Boxed Contained Width', 'stagekitwp-theme' ),
            'full'  => __( 'Full Width Fluid Edge-to-Edge', 'stagekitwp-theme' ),
        ),
    ) );
}
add_action( 'customize_register', 'stagekitwp_theme_customize_register' );

function stagekitwp_sanitize_checkbox( $checked ) {
    // Accept boolean true, integer 1, or string "1" — all valid truthy checkbox states.
    return ( isset( $checked ) && ( $checked === true || $checked === 1 || $checked === '1' ) );
}

function stagekitwp_sanitize_upcoming_season_mode( $value ) {
    $valid = array( 'off', 'on', 'on_if_season', 'on_if_shows' );
    return in_array( $value, $valid, true ) ? $value : 'on';
}

/**
 * Whether the homepage Upcoming Season Grid should render, based on the
 * `stagekitwp_enable_upcoming_season` mode and (for the data-driven modes)
 * the StageKitWP Core "next season" helpers.
 *
 * @return bool
 */
function stagekitwp_theme_should_display_upcoming_season() {
    $mode = get_theme_mod( 'stagekitwp_enable_upcoming_season', 'on' );

    switch ( $mode ) {
        case 'off':
            return false;
        case 'on_if_season':
            return function_exists( 'stagekitwp_get_next_season' ) && (bool) stagekitwp_get_next_season();
        case 'on_if_shows':
            return function_exists( 'stagekitwp_next_season_has_shows' ) && stagekitwp_next_season_has_shows();
        case 'on':
        default:
            return true;
    }
}

function stagekitwp_sanitize_radio_alignment( $input ) {
    $valid = array( 'left' => 'left', 'center' => 'center', 'right' => 'right' );
    return array_key_exists( $input, $valid ) ? $input : 'center';
}
function stagekitwp_sanitize_logo_position( $input ) {
    $valid = array( 'left' => 'left', 'center' => 'center', 'right' => 'right' );
    return array_key_exists( $input, $valid ) ? $input : 'left';
}
function stagekitwp_sanitize_footer_layout( $input ) {
    $valid = array( 'fixed' => 'fixed', 'full' => 'full' );
    return array_key_exists( $input, $valid ) ? $input : 'fixed';
}

function stagekitwp_sanitize_hero_cta_placement( $input ) {
    $valid = array(
        'content'       => 'content',
        'top-left'      => 'top-left',
        'top-center'    => 'top-center',
        'top-right'     => 'top-right',
        'center-left'   => 'center-left',
        'center'        => 'center',
        'center-right'  => 'center-right',
        'bottom-left'   => 'bottom-left',
        'bottom-center' => 'bottom-center',
        'bottom-right'  => 'bottom-right',
    );

    return array_key_exists( $input, $valid ) ? $input : 'content';
}

function stagekitwp_sanitize_hero_cta_padding( $value ) {
    $value = absint( $value );

    if ( $value > 160 ) {
        $value = 160;
    }

    return $value;
}

function stagekitwp_sanitize_hero_overlay_opacity( $value ) {
    $value = absint( $value );

    if ( $value > 100 ) {
        $value = 100;
    }

    return $value;
}

function stagekitwp_sanitize_slug_csv( $value ) {
    if ( ! is_string( $value ) ) {
        return '';
    }

    $parts = array_filter( array_map( 'trim', explode( ',', $value ) ) );
    if ( empty( $parts ) ) {
        return '';
    }

    $parts = array_map( 'sanitize_title', $parts );
    $parts = array_filter( $parts );

    return implode( ',', array_unique( $parts ) );
}

/**
 * Build donate placement choices from current primary menu items.
 *
 * @return array<string,string>
 */
function stagekitwp_get_donate_menu_position_choices() {
    $choices = array(
        'end'   => __( 'End of Main Menu', 'stagekitwp-theme' ),
        'start' => __( 'Start of Main Menu', 'stagekitwp-theme' ),
    );

    $locations = get_nav_menu_locations();
    if ( empty( $locations['primary-menu'] ) ) {
        return $choices;
    }

    $menu_items = wp_get_nav_menu_items( (int) $locations['primary-menu'] );
    if ( empty( $menu_items ) || is_wp_error( $menu_items ) ) {
        return $choices;
    }

    foreach ( $menu_items as $menu_item ) {
        if ( empty( $menu_item->ID ) ) {
            continue;
        }

        $title = wp_strip_all_tags( (string) $menu_item->title );
        if ( '' === $title ) {
            $title = __( '(Untitled Menu Item)', 'stagekitwp-theme' );
        }

        $choices[ 'after_' . (int) $menu_item->ID ] = sprintf(
            __( 'After: %s', 'stagekitwp-theme' ),
            $title
        );
    }

    return $choices;
}

/**
 * Sanitize donate menu position value.
 */
function stagekitwp_sanitize_donate_menu_position( $input ) {
    $input   = sanitize_text_field( (string) $input );
    $choices = stagekitwp_get_donate_menu_position_choices();

    return array_key_exists( $input, $choices ) ? $input : 'end';
}

/**
 * Register StageKitWP Donate Image Customizer Settings
 */
function stagekitwp_theme_customize_register_donate( $wp_customize ) {
    // 1. Create a Header/Footer Elements Section
    $wp_customize->add_section( 'stagekitwp_theme_design_elements', array(
        'title'       => __( 'Theatre Global Elements', 'stagekitwp-theme' ),
        'priority'    => 30,
        'description' => __( 'Manage the reusable donate icon image and link used across menus and footer widgets.', 'stagekitwp-theme' ),
    ) );

    // 2. Add the Image Setting
    $wp_customize->add_setting( 'stagekitwp_donate_button_image', array(
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
        'transport'         => 'postMessage', 
    ) );

    // 3. Add the Image Upload Control
    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'stagekitwp_donate_button_image_control', array(
        'label'    => __( 'Donate Button Image Banner', 'stagekitwp-theme' ),
        'section'  => 'stagekitwp_theme_design_elements',
        'settings' => 'stagekitwp_donate_button_image',
    ) ) );

    // 4. Add the Donate Link Setting
    $wp_customize->add_setting( 'stagekitwp_donate_button_url', array(
        'default'           => home_url( '/donate' ),
        'sanitize_callback' => 'esc_url_raw',
        'transport'         => 'refresh',
    ) );

    // 5. Add the Donate Link Control
    $wp_customize->add_control( 'stagekitwp_donate_button_url_control', array(
        'label'       => __( 'Donate Button Link URL', 'stagekitwp-theme' ),
        'description' => __( 'Used by the donate icon in the main menu and footer widget areas.', 'stagekitwp-theme' ),
        'section'     => 'stagekitwp_theme_design_elements',
        'settings'    => 'stagekitwp_donate_button_url',
        'type'        => 'url',
    ) );

    // 6. Add Donate Placement Setting
    $wp_customize->add_setting( 'stagekitwp_donate_menu_position', array(
        'default'           => 'end',
        'sanitize_callback' => 'stagekitwp_sanitize_donate_menu_position',
        'transport'         => 'refresh',
    ) );

    // 7. Add Donate Placement Control
    $wp_customize->add_control( 'stagekitwp_donate_menu_position_control', array(
        'label'       => __( 'Main Menu Donate Position', 'stagekitwp-theme' ),
        'description' => __( 'Choose where the auto-inserted donate item appears when no manual donate item exists in the main menu.', 'stagekitwp-theme' ),
        'section'     => 'stagekitwp_theme_design_elements',
        'settings'    => 'stagekitwp_donate_menu_position',
        'type'        => 'select',
        'choices'     => stagekitwp_get_donate_menu_position_choices(),
    ) );

    // 8. Add Site Max Width Setting
    $wp_customize->add_setting( 'stagekitwp_site_max_width', array(
        'default'           => 1200,
        'sanitize_callback' => 'stagekitwp_sanitize_site_max_width',
        'transport'         => 'postMessage',
    ) );

    // 9. Add Site Max Width Control
    $wp_customize->add_control( 'stagekitwp_site_max_width_control', array(
        'label'       => __( 'Site Max Width (px)', 'stagekitwp-theme' ),
        'description' => __( 'Controls the maximum width of the header, footer, and page content containers site-wide. Default 1200px.', 'stagekitwp-theme' ),
        'section'     => 'stagekitwp_theme_design_elements',
        'settings'    => 'stagekitwp_site_max_width',
        'type'        => 'number',
        'input_attrs' => array(
            'min'  => 960,
            'max'  => 1920,
            'step' => 10,
        ),
    ) );
}
add_action( 'customize_register', 'stagekitwp_theme_customize_register_donate' );

/**
 * Sanitize the site max width Customizer setting to a safe pixel value.
 */
function stagekitwp_sanitize_site_max_width( $value ) {
    $value = absint( $value );
    if ( $value < 960 ) {
        $value = 960;
    }
    if ( $value > 1920 ) {
        $value = 1920;
    }
    return $value;
}

/**
 * Register Footer Grid Layout and Alignment Controls
 */
function stagekitwp_customize_register_footer_controls( $wp_customize ) {
    
    $wp_customize->add_section( 'stagekitwp_footer_section', array(
        'title'    => __( 'Footer Settings', 'stagekitwp-theme' ),
        'priority' => 120,
    ) );

    // 1. Full-Length vs 1-Column Layout Mode
    $wp_customize->add_setting( 'stagekitwp_footer_layout_mode', array(
        'default'           => 'three-column',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ) );

    $wp_customize->add_control( 'stagekitwp_footer_layout_mode', array(
        'label'    => __( 'Footer Container Width', 'stagekitwp-theme' ),
        'section'  => 'stagekitwp_footer_section',
        'type'     => 'radio',
        'choices'  => array(
            'three-column' => __( 'Standard 3-Column Width (1200px max)', 'stagekitwp-theme' ),
            'full-width'   => __( 'Full-Length Edge-to-Edge Fluid Container', 'stagekitwp-theme' ),
            'one-column'   => __( 'Single 1-Column Centered Stack Block', 'stagekitwp-theme' ),
        ),
    ) );

    // 2. Individual Alignment Vectors
    $footer_columns = array(
        'stagekitwp_footer_align_left'   => array( 'label' => __( 'Left Column Alignment', 'stagekitwp-theme' ), 'default' => 'left' ),
        'stagekitwp_footer_align_middle' => array( 'label' => __( 'Middle Column Alignment', 'stagekitwp-theme' ), 'default' => 'center' ),
        'stagekitwp_footer_align_right'  => array( 'label' => __( 'Right Column Alignment', 'stagekitwp-theme' ), 'default' => 'right' ),
    );

    foreach ( $footer_columns as $id => $data ) {
        $wp_customize->add_setting( $id, array(
            'default'           => $data['default'],
            'sanitize_callback' => 'sanitize_text_field',
            'transport'         => 'postMessage',
        ) );

        $wp_customize->add_control( $id, array(
            'label'    => $data['label'],
            'section'  => 'stagekitwp_footer_section',
            'type'     => 'select',
            'choices'  => array(
                'left'   => __( 'Left', 'stagekitwp-theme' ),
                'center' => __( 'Center', 'stagekitwp-theme' ),
                'right'  => __( 'Right', 'stagekitwp-theme' ),
            ),
        ) );
    }
}
add_action( 'customize_register', 'stagekitwp_customize_register_footer_controls' );

// =============================================================================
// PANEL: Color Settings  (light + dark pairs, v2.1.0)
//
// Each colour has a Light and Dark variant shown on the same visual row.
// Dark defaults are pre-populated from the JS color-mode-switcher DARK map.
// customizer-controls.js reads data-stagekitwp-pair-* attrs to lay them side by side.
// =============================================================================

/**
 * Helper: register a light+dark colour pair.
 */
function stagekitwp_add_color_pair( $wp_customize, $key, $label, $desc, $section, $default_l, $default_d ) {
    $id_l = 'stagekitwp_color_' . $key;
    $id_d = 'stagekitwp_color_' . $key . '_dark';

    $wp_customize->add_setting( $id_l, array(
        'default'           => $default_l,
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $id_l, array(
        'label'       => $label . ' — ☀ Light',
        'description' => '<span data-stagekitwp-pair="light" data-stagekitwp-pair-key="' . esc_attr( $key ) . '"></span>' . esc_html( $desc ),
        'section'     => $section,
    ) ) );

    $wp_customize->add_setting( $id_d, array(
        'default'           => $default_d,
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $id_d, array(
        'label'       => $label . ' — ☾ Dark',
        'description' => '<span data-stagekitwp-pair="dark" data-stagekitwp-pair-key="' . esc_attr( $key ) . '"></span>',
        'section'     => $section,
    ) ) );
}

/**
 * Register the Color Settings panel and all its sections/controls.
 */
function stagekitwp_customize_register_color_panel( $wp_customize ) {

    $wp_customize->add_panel( 'stagekitwp_color_panel', array(
        'title'       => __( '🎨 Color Settings', 'stagekitwp-theme' ),
        'description' => __( 'Each row shows a Light picker and a Dark picker side by side. Dark backgrounds above 25% luminance will show a brightness warning.', 'stagekitwp-theme' ),
        'priority'    => 28,
    ) );

    // ------------------------------------------------------------------
    // SECTION 1: Global Theme Mode
    // ------------------------------------------------------------------
    $wp_customize->add_section( 'stagekitwp_color_mode_section', array(
        'title'    => __( 'Global Theme Mode', 'stagekitwp-theme' ),
        'panel'    => 'stagekitwp_color_panel',
        'priority' => 10,
    ) );
    $wp_customize->add_setting( 'stagekitwp_color_mode', array(
        'default'           => 'light',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'stagekitwp_color_mode', array(
        'label'       => __( 'Default Color Mode', 'stagekitwp-theme' ),
        'description' => __( 'Whether the site defaults to a light or dark canvas.', 'stagekitwp-theme' ),
        'section'     => 'stagekitwp_color_mode_section',
        'type'        => 'select',
        'choices'     => array(
            'light' => __( 'Light', 'stagekitwp-theme' ),
            'dark'  => __( 'Dark', 'stagekitwp-theme' ),
        ),
    ) );
    $wp_customize->add_setting( 'stagekitwp_enable_frontend_switcher', array(
        'default'           => false,
        'sanitize_callback' => 'stagekitwp_sanitize_checkbox',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'stagekitwp_enable_frontend_switcher', array(
        'label'       => __( 'Enable Frontend Mode Switcher', 'stagekitwp-theme' ),
        'description' => __( 'Adds a 🌙/☀ toggle button to the front end for visitors.', 'stagekitwp-theme' ),
        'section'     => 'stagekitwp_color_mode_section',
        'type'        => 'checkbox',
    ) );

    // ------------------------------------------------------------------
    // SECTION 2: Global Branding & Palette
    // ------------------------------------------------------------------
    $wp_customize->add_section( 'stagekitwp_color_palette_section', array(
        'title'       => __( 'Branding & Palette', 'stagekitwp-theme' ),
        'description' => __( 'Core palette tokens. Each row: ☀ Light | ☾ Dark.', 'stagekitwp-theme' ),
        'panel'       => 'stagekitwp_color_panel',
        'priority'    => 20,
    ) );
    // Accent colours — single (same in both modes).
    $wp_customize->add_setting( 'stagekitwp_color_primary_accent', array(
        'default' => '#e50914', 'sanitize_callback' => 'sanitize_hex_color', 'transport' => 'postMessage',
    ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'stagekitwp_color_primary_accent', array(
        'label' => __( 'Primary Accent (both modes)', 'stagekitwp-theme' ),
        'description' => __( 'Buttons, active states, key branding highlights.', 'stagekitwp-theme' ),
        'section' => 'stagekitwp_color_palette_section',
    ) ) );
    $wp_customize->add_setting( 'stagekitwp_color_secondary_accent', array(
        'default' => '#b8070f', 'sanitize_callback' => 'sanitize_hex_color', 'transport' => 'postMessage',
    ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'stagekitwp_color_secondary_accent', array(
        'label' => __( 'Secondary Accent (both modes)', 'stagekitwp-theme' ),
        'description' => __( 'Hovers, badges, sub-headings.', 'stagekitwp-theme' ),
        'section' => 'stagekitwp_color_palette_section',
    ) ) );
    // Base background — dark: #121212 (canvas.bg)
    stagekitwp_add_color_pair( $wp_customize, 'base_bg',
        __( 'Base Background', 'stagekitwp-theme' ),
        __( 'Main canvas / page background.', 'stagekitwp-theme' ),
        'stagekitwp_color_palette_section', '#ffffff', '#121212' );
    // Surface background — dark: #1e1e1e (card.bg)
    stagekitwp_add_color_pair( $wp_customize, 'surface_bg',
        __( 'Surface / Card Background', 'stagekitwp-theme' ),
        __( 'Cards, widget containers, section fills.', 'stagekitwp-theme' ),
        'stagekitwp_color_palette_section', '#f8f9fa', '#1e1e1e' );

    // ------------------------------------------------------------------
    // SECTION 3: Typography
    // ------------------------------------------------------------------
    $wp_customize->add_section( 'stagekitwp_color_typography_section', array(
        'title'       => __( 'Typography', 'stagekitwp-theme' ),
        'description' => __( 'Text colour tokens. Each row: ☀ Light | ☾ Dark.', 'stagekitwp-theme' ),
        'panel'       => 'stagekitwp_color_panel',
        'priority'    => 30,
    ) );
    // Heading — dark: #ffffff (heading.color)
    stagekitwp_add_color_pair( $wp_customize, 'heading_text',
        __( 'Heading Text', 'stagekitwp-theme' ),
        __( 'H1–H6 elements globally.', 'stagekitwp-theme' ),
        'stagekitwp_color_typography_section', '#111111', '#ffffff' );
    // Body — dark: #e0e0e0 (canvas.color)
    stagekitwp_add_color_pair( $wp_customize, 'body_text',
        __( 'Body Text', 'stagekitwp-theme' ),
        __( 'Paragraphs, lists, main readable content.', 'stagekitwp-theme' ),
        'stagekitwp_color_typography_section', '#333333', '#e0e0e0' );
    // Muted — dark: #9e9e9e (mutedText.color)
    stagekitwp_add_color_pair( $wp_customize, 'muted_text',
        __( 'Muted / Meta Text', 'stagekitwp-theme' ),
        __( 'Captions, post dates, secondary meta-data.', 'stagekitwp-theme' ),
        'stagekitwp_color_typography_section', '#6c757d', '#9e9e9e' );

    // ------------------------------------------------------------------
    // SECTION 4: Interactive Elements
    // ------------------------------------------------------------------
    $wp_customize->add_section( 'stagekitwp_color_interactive_section', array(
        'title'       => __( 'Interactive Elements', 'stagekitwp-theme' ),
        'description' => __( 'Hyperlink colours. Each row: ☀ Light | ☾ Dark.', 'stagekitwp-theme' ),
        'panel'       => 'stagekitwp_color_panel',
        'priority'    => 40,
    ) );
    // Link — dark: #e0e0e0 (headingLink.color)
    stagekitwp_add_color_pair( $wp_customize, 'link',
        __( 'Link Color', 'stagekitwp-theme' ),
        __( 'Default inline text link state.', 'stagekitwp-theme' ),
        'stagekitwp_color_interactive_section', '#0073aa', '#e0e0e0' );
    // Link hover — dark stays accent red
    stagekitwp_add_color_pair( $wp_customize, 'link_hover',
        __( 'Link Hover', 'stagekitwp-theme' ),
        __( 'Colour on hover and keyboard focus.', 'stagekitwp-theme' ),
        'stagekitwp_color_interactive_section', '#e50914', '#e50914' );

    // ------------------------------------------------------------------
    // SECTION 5: Zone Overrides — Structural Areas
    // ------------------------------------------------------------------
    $wp_customize->add_section( 'stagekitwp_color_zones_section', array(
        'title'       => __( 'Zone Overrides — Structural Areas', 'stagekitwp-theme' ),
        'description' => __( 'Per-zone overrides. Each row: ☀ Light | ☾ Dark. Notification bar and footer are always dark — both values shown for flexibility.', 'stagekitwp-theme' ),
        'panel'       => 'stagekitwp_color_panel',
        'priority'    => 50,
    ) );
    // Notification bar — always dark zone; dark default same as light
    stagekitwp_add_color_pair( $wp_customize, 'notif_bg',
        __( 'Notification Bar Background', 'stagekitwp-theme' ),
        __( 'Background of the top alert / countdown bar.', 'stagekitwp-theme' ),
        'stagekitwp_color_zones_section', '#111111', '#111111' );
    stagekitwp_add_color_pair( $wp_customize, 'notif_text',
        __( 'Notification Bar Text', 'stagekitwp-theme' ),
        __( 'Text and link colour within the notification bar.', 'stagekitwp-theme' ),
        'stagekitwp_color_zones_section', '#ffffff', '#ffffff' );
    // Header — dark: #1e1e1e surface
    stagekitwp_add_color_pair( $wp_customize, 'header_bg',
        __( 'Header Background', 'stagekitwp-theme' ),
        __( 'Main site navigation wrapper background.', 'stagekitwp-theme' ),
        'stagekitwp_color_zones_section', '#ffffff', '#1e1e1e' );
    stagekitwp_add_color_pair( $wp_customize, 'header_text',
        __( 'Header Link / Text', 'stagekitwp-theme' ),
        __( 'Menu items and branding text colour.', 'stagekitwp-theme' ),
        'stagekitwp_color_zones_section', '#444444', '#e0e0e0' );
    stagekitwp_add_color_pair( $wp_customize, 'header_hover',
        __( 'Header Link Hover / Active', 'stagekitwp-theme' ),
        __( 'Menu hover and current-page indicator colour.', 'stagekitwp-theme' ),
        'stagekitwp_color_zones_section', '#e50914', '#e50914' );
    // Footer — always dark zone; dark default same as light
    stagekitwp_add_color_pair( $wp_customize, 'footer_bg',
        __( 'Footer Background', 'stagekitwp-theme' ),
        __( 'Bottom wrapper and widget area background.', 'stagekitwp-theme' ),
        'stagekitwp_color_zones_section', '#111111', '#111111' );
    stagekitwp_add_color_pair( $wp_customize, 'footer_text',
        __( 'Footer Text / Link', 'stagekitwp-theme' ),
        __( 'Footer widget content, links, and copyright text.', 'stagekitwp-theme' ),
        'stagekitwp_color_zones_section', '#cccccc', '#cccccc' );
}
add_action( 'customize_register', 'stagekitwp_customize_register_color_panel' );

