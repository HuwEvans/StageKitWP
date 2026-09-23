<?php
/**
 * The header for our theme
 *
 * @package StageKitWP_Theme
 */

?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div id="page" class="site">
    <a class="skip-link screen-reader-text" href="#primary"><?php esc_html_e( 'Skip to content', 'stagekitwp-theme' ); ?></a>

    <?php $stagekitwp_hide_header = in_array( stagekitwp_get_page_chrome_mode(), array( 'no-header', 'no-header-footer' ), true ); ?>
    <?php if ( ! $stagekitwp_hide_header ) : ?>

    <?php 
    // Check global toggle visibility setting first
    if ( get_theme_mod( 'stagekitwp_enable_countdown', true ) ) : 

        $calculated_timestamp = '';
        $active_show_title = '';

        if ( post_type_exists( 'show' ) && post_type_exists( 'season' ) ) {
            $current_month_day = date( 'md' ); // Format as MMDD for clean chronological boundary queries
            $current_year      = (int) date( 'Y' );
            $target_slot       = '';

            // 1. CHRONOLOGICAL MATRIX BOUNDARY DEFINITIONS
            if ( $current_month_day >= '0701' && $current_month_day <= '1115' ) {
                $target_slot = 'Fall';
            } elseif ( ( $current_month_day >= '1116' && $current_month_day <= '1231' ) || ( $current_month_day >= '0101' && $current_month_day <= '0217' ) ) {
                $target_slot = 'Winter';
            } elseif ( $current_month_day >= '0218' && $current_month_day <= '0630' ) {
                $target_slot = 'Spring';
            }

            if ( ! empty( $target_slot ) ) {
                // Find the primary active playing season frame
                $season_lookup = get_posts( array(
                    'post_type'      => 'season',
                    'posts_per_page' => 1,
                    'meta_key'       => '_stagekitwp_season_is_current',
                    'meta_value'     => '1',
                    'fields'         => 'ids'
                ) );

                $season_id = ! empty( $season_lookup ) ? $season_lookup[0] : 0;

                if ( $season_id ) {
                    // Query for the specific singular show assigned to this slot
                    $show_query = new WP_Query( array(
                        'post_type'      => 'show',
                        'posts_per_page' => 1,
                        'meta_query'     => array(
                            'relation' => 'AND',
                            array(
                                'key'     => '_stagekitwp_show_time_slot',
                                'value'   => $target_slot,
                                'compare' => '='
                            ),
                            array(
                                'key'     => '_stagekitwp_show_season',
                                'value'   => $season_id,
                                'compare' => '='
                            )
                        )
                    ) );

                    if ( $show_query->have_posts() ) {
                        $show_query->the_post();
                        $active_show_title = get_the_title();
                        
                        // Grab raw production timestamp or plain date text field value
                        $raw_show_date = get_post_meta( get_the_ID(), '_stagekitwp_show_show_dates', true );
                        
                        if ( ! empty( $raw_show_date ) ) {
                            $parsed_time = strtotime( $raw_show_date );
                            if ( $parsed_time ) {
                                $calculated_timestamp = date( 'Y-m-d H:i:s', $parsed_time );
                            }
                        }
                    }
                    wp_reset_postdata();
                }
            }
        }

        // Fallback engine parameters if calculation matrix is open/empty
        if ( empty( $calculated_timestamp ) ) {
            $calculated_timestamp = get_theme_mod( 'stagekitwp_next_show_timestamp' );
        }
        
        if ( ! empty( $calculated_timestamp ) ) : 
            // Fetch alignment customizer value and translate it to flex justify tokens
            $noti_alignment = get_theme_mod( 'stagekitwp_notification_alignment', 'center' );
            $noti_justify   = 'center';
            
            if ( $noti_alignment === 'left' ) {
                $noti_justify = 'flex-start';
            } elseif ( $noti_alignment === 'right' ) {
                $noti_justify = 'flex-end';
            }
            ?>
            <div class="stagekitwp-top-notification-bar" style="background-color: #111111; color: #ffffff; padding: 10px 0; font-size: 0.9rem; border-bottom: 2px solid #e50914;">
                <div class="stagekitwp-container" style="max-width: var(--stagekitwp-site-max-width, 1200px); margin: 0 auto; padding: 0 20px; display: flex; justify-content: <?php echo esc_attr( $noti_justify ); ?>; align-items: center; flex-wrap: wrap; gap: 10px;">
                    <div class="notification-text">
                        <span class="stagekitwp-notification-label" style="background: #e50914; padding: 2px 6px; font-weight: 700; font-size: 0.75rem; text-transform: uppercase; margin-right: 8px; border-radius: 2px;">
                            <?php _e( 'Next Production', 'stagekitwp-theme' ); ?>
                        </span>
                        <span class="stagekitwp-countdown-copy">
                            <?php if ( ! empty( $active_show_title ) ) : ?>
                                <strong><?php echo esc_html( $active_show_title ); ?></strong> — 
                            <?php endif; ?>
                            <span id="stagekitwp-countdown-label"><?php _e( 'Counting down to opening night...', 'stagekitwp-theme' ); ?></span>
                        </span>
                    </div>
                    <script type="text/javascript">
                        document.addEventListener('DOMContentLoaded', function() {
                            var targetDate = new Date("<?php echo esc_js( $calculated_timestamp ); ?>").getTime();
                            if (isNaN(targetDate)) return;

                            var timer = setInterval(function() {
                                var now = new Date().getTime();
                                var diff = targetDate - now;

                                if (diff < 0) {
                                    clearInterval(timer);
                                    document.getElementById('stagekitwp-countdown-label').innerHTML = "<?php echo esc_js( __( 'Performance Live! Visit Box Office for Entry.', 'stagekitwp-theme' ) ); ?>";
                                    return;
                                }

                                var days = Math.floor(diff / (1000 * 60 * 60 * 24));
                                var hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                                var minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));

                                document.getElementById('stagekitwp-countdown-label').innerHTML = "<?php echo esc_js( __( 'Curtain rises in:', 'stagekitwp-theme' ) ); ?> <strong>" + days + "d " + hours + "h " + minutes + "m</strong>";
                            }, 1000);
                        });
                    </script>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php 
    $menu_alignment    = get_theme_mod( 'stagekitwp_menu_alignment', 'center' );
    $logo_position     = get_theme_mod( 'stagekitwp_logo_position', 'left' );
    $custom_logo_url   = get_theme_mod( 'stagekitwp_custom_logo' );
    $dark_logo_url     = get_theme_mod( 'stagekitwp_logo_dark', '' );
    $has_dark_logo     = ! empty( $dark_logo_url );
    $show_tagline      = (bool) get_theme_mod( 'stagekitwp_show_tagline', false );
    $tagline_pos       = get_theme_mod( 'stagekitwp_tagline_position', 'below-logo' );
    $tagline_text      = get_bloginfo( 'description' );

    // Determine spatial arrangement ordering vectors
    $header_flex_direction = 'row';
    $header_justify        = 'space-between';

    if ( $logo_position === 'center' ) {
        $header_flex_direction = 'column';
        $header_justify        = 'center';
    } elseif ( $logo_position === 'right' ) {
        $header_flex_direction = 'row-reverse';
        $header_justify        = 'space-between';
    }
    ?>
    <header id="masthead" class="site-header" style="background-color: #ffffff; border-bottom: 1px solid #f0f0f1; padding: 20px 0;">
        <div class="stagekitwp-container" style="max-width: var(--stagekitwp-site-max-width, 1200px); margin: 0 auto; padding: 0 20px; display: flex; flex-direction: <?php echo esc_attr( $header_flex_direction ); ?>; align-items: center; justify-content: <?php echo esc_attr( $header_justify ); ?>; gap: 20px; flex-wrap: wrap;">
            
            <div class="site-branding stagekitwp-tagline-pos-<?php echo esc_attr( $tagline_pos ); ?>" style="display: flex; align-items: <?php echo ( $tagline_pos === 'below-logo' ) ? 'flex-start' : 'center'; ?>; flex-direction: <?php echo ( $tagline_pos === 'below-logo' ) ? 'column' : 'row'; ?>; gap: 10px;">
                <?php if ( ! empty( $custom_logo_url ) ) : ?>
                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" class="custom-theme-logo-link stagekitwp-logo-wrap">
                        <img
                            src="<?php echo esc_url( $custom_logo_url ); ?>"
                            alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"
                            class="stagekitwp-logo-light"
                            style="max-height: 60px; width: auto; display: block;">
                        <?php if ( $has_dark_logo ) : ?>
                        <img
                            src="<?php echo esc_url( $dark_logo_url ); ?>"
                            alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"
                            class="stagekitwp-logo-dark-img"
                            style="max-height: 60px; width: auto; display: none;">
                        <?php endif; ?>
                    </a>
                <?php else : ?>
                    <div class="site-title-wrapper">
                        <p class="site-title" style="margin: 0; font-size: 1.5rem; font-weight: 900; text-transform: uppercase;">
                            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" style="text-decoration: none;">
                                🎭 <?php bloginfo( 'name' ); ?>
                            </a>
                        </p>
                    </div>
                <?php endif; ?>

                <?php if ( $show_tagline && $tagline_text && in_array( $tagline_pos, array( 'below-logo', 'beside-logo' ), true ) ) : ?>
                    <p class="stagekitwp-site-tagline stagekitwp-site-tagline--<?php echo esc_attr( $tagline_pos ); ?>" style="margin: 0;">
                        <?php echo esc_html( $tagline_text ); ?>
                    </p>
                <?php endif; ?>
            </div>

            <?php if ( $show_tagline && $tagline_text && $tagline_pos === 'header-end' ) : ?>
                <p class="stagekitwp-site-tagline stagekitwp-site-tagline--header-end" style="margin: 0;">
                    <?php echo esc_html( $tagline_text ); ?>
                </p>
            <?php endif; ?>

            <?php
            if ( ! function_exists( 'stagekitwp_theme_force_donate_html_text' ) ) {
                function stagekitwp_theme_force_donate_html_text( $item_output, $item, $depth, $args ) {
                    if ( in_array( 'menu-item-donate-btn', $item->classes ) ) {
                        if ( empty( $item->title ) || trim( $item->title ) === '' || ! strpos( $item_output, 'Donate' ) ) {
                            $item_output = sprintf(
                                '<a href="%1$s" class="menu-item-donate-link">%2$s</a>',
                                esc_url( get_theme_mod( 'stagekitwp_donate_button_url', home_url( '/donate' ) ) ),
                                __( '🎁 Donate Now', 'stagekitwp-theme' )
                            );
                        }
                    }
                    return $item_output;
                }
            }
            add_filter( 'walker_nav_menu_start_el', 'stagekitwp_theme_force_donate_html_text', 10, 4 );
            ?>

            <button class="menu-toggle" aria-controls="primary-menu" aria-expanded="false" style="display: none; background: none; border: none; font-size: 1.5rem; cursor: pointer; padding: 5px;">
                ☰
            </button>

            <nav id="site-navigation" class="main-navigation" style="display: flex; align-items: center; justify-content: <?php echo esc_attr( $menu_alignment ); ?>; flex-grow: 1;">
                <?php
                wp_nav_menu( array(
                    'theme_location' => 'primary-menu',
                    'menu_id'        => 'primary-menu',
                    'container'      => false,
                    'fallback_cb'    => false,
                    'menu_class'     => 'stagekitwp-primary-menu-list',
                ) );
                ?>
            </nav>

            <?php remove_filter( 'walker_nav_menu_start_el', 'stagekitwp_theme_force_donate_html_text', 10 ); ?>

        </div>
    </header>

    <style type="text/css">
        .stagekitwp-primary-menu-list {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
            align-items: center;
            gap: 25px;
        }
        .stagekitwp-primary-menu-list li { position: relative; }
        .stagekitwp-primary-menu-list li a {
            color: var(--stagekitwp-header-text, #444444);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: color 0.15s ease-in-out;
        }
        .stagekitwp-primary-menu-list li a:hover { color: var(--stagekitwp-header-hover, #e50914); }
        .site-title a { color: var(--stagekitwp-header-text, #444444); }
        .menu-toggle { color: var(--stagekitwp-header-text, #444444); }

        .stagekitwp-primary-menu-list li ul {
            position: absolute;
            top: 100%;
            left: 0;
            background: var(--stagekitwp-header-bg, #ffffff);
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
            list-style: none;
            padding: 10px 0;
            margin: 0;
            min-width: 220px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.2s ease-in-out;
            z-index: 999;
            border-top: 2px solid #e50914;
        }
        .stagekitwp-primary-menu-list li:hover > ul {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        .stagekitwp-primary-menu-list li ul li a {
            display: block;
            padding: 10px 20px;
            color: var(--stagekitwp-body-text, #333333);
            font-weight: 500;
        }

        /* RESPONSIVE TABLET & MOBILE BREAKPOINT */
        @media (max-width: 768px) {
            .site-header .stagekitwp-container {
                justify-content: space-between !important;
                flex-direction: row !important;
            }
            .menu-toggle {
                display: block !important;
            }
            #site-navigation {
                display: none !important; /* Hide menu layer by default */
                width: 100%;
                order: 3; 
            }
            #site-navigation.active {
                display: block !important;
            }
            .stagekitwp-primary-menu-list {
                flex-direction: column;
                align-items: flex-start;
                gap: 0;
                width: 100%;
                padding: 15px 0 0 0;
                border-top: 1px solid #f0f0f1;
                margin-top: 10px;
            }
            .stagekitwp-primary-menu-list li {
                width: 100%;
                position: relative;
            }
            .stagekitwp-primary-menu-list li a {
                display: block;
                padding: 12px 0;
                width: 100%;
                border-bottom: 1px solid #f9f9f9;
            }
            
            /* Class injected into parent menu item if a submenu exists */
            .stagekitwp-primary-menu-list li.menu-item-has-children > a {
                padding-right: 40px;
            }
            
            /* Interactive Mobile Dropdown Indicator Button */
            .dropdown-toggle-btn {
                display: block !important;
                position: absolute;
                right: 0;
                top: 0;
                width: 44px;
                height: 44px;
                background: none;
                border: none;
                font-size: 0.8rem;
                color: var(--stagekitwp-header-text, #444444);
                cursor: pointer;
                transition: transform 0.2s ease;
            }
            .dropdown-toggle-btn.toggled {
                transform: rotate(180deg);
            }

            /* Submenus hidden by default on mobile accordion setup */
            .stagekitwp-primary-menu-list li ul {
                display: none; 
                position: static;
                opacity: 1;
                visibility: visible;
                box-shadow: none;
                transform: none;
                padding: 0 0 0 15px;
                border-top: none;
                width: 100%;
            }
            .stagekitwp-primary-menu-list li ul.open {
                display: block !important;
            }
            .stagekitwp-primary-menu-list li ul li a {
                padding: 10px 0;
            }
        }
    </style>

    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            var toggleButton = document.querySelector('.menu-toggle');
            var siteNavigation = document.getElementById('site-navigation');

            // 1. Primary Main Menu Toggle Control
            if (toggleButton && siteNavigation) {
                toggleButton.addEventListener('click', function() {
                    var isExpanded = toggleButton.getAttribute('aria-expanded') === 'true';
                    siteNavigation.classList.toggle('active');
                    toggleButton.setAttribute('aria-expanded', !isExpanded);
                    toggleButton.innerHTML = isExpanded ? '☰' : '✕';
                });
            }

            // 2. Mobile Submenu Dropdown Accordion Accordance Controls
            var parentMenuItems = document.querySelectorAll('.stagekitwp-primary-menu-list > .menu-item-has-children');
            
            parentMenuItems.forEach(function(parentItem) {
                var submenu = parentItem.querySelector('ul');
                if (submenu) {
                    // Create a clickable dropdown indicator button for better UX
                    var toggleBtn = document.createElement('button');
                    toggleBtn.className = 'dropdown-toggle-btn';
                    toggleBtn.setAttribute('type', 'button');
                    toggleBtn.setAttribute('aria-label', 'Toggle Submenu');
                    toggleBtn.innerHTML = '▼';
                    toggleBtn.style.display = 'none'; // Controlled by CSS display layout vectors

                    parentItem.appendChild(toggleBtn);

                    toggleBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        submenu.classList.toggle('open');
                        toggleBtn.classList.toggle('toggled');
                    });
                }
            });
        });
    </script>

    <?php endif; ?>

    <div id="content" class="site-content">