<?php
/**
 * Functions for creating sample content pages
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Delete all sample pages and menu items
 */
function stagekitwp_delete_sample_pages() {
    // Get the parent page
    $parent_page = get_page_by_path('stagekitwp-core', OBJECT, 'page');
    $parent_page_id = $parent_page ? $parent_page->ID : 0;
    
    $sample_page_slugs = array(
        'stagekitwp-shows', 'stagekitwp-current-season', 'stagekitwp-cast-members', 'stagekitwp-board-members',
        'stagekitwp-sponsors', 'stagekitwp-advertisers', 'stagekitwp-contributors', 'stagekitwp-seasons',
        'stagekitwp-programs', 'stagekitwp-testimonials', 'stagekitwp-season-cast', 'stagekitwp-show-cast',
        'stagekitwp-season-images', 'stagekitwp-season-shows', 'stagekitwp-media', 'stagekitwp-auditions', 'stagekitwp-awards', 'stagekitwp-venues', 'stagekitwp-tickets'
    );
    
    // Delete individual sample pages by slug
    foreach ($sample_page_slugs as $slug) {
        $page = get_page_by_path($slug, OBJECT, 'page');
        if ($page) {
            wp_delete_post($page->ID, true); // Force delete (skip trash)
        }
    }
    
    // Delete parent page last (this may also delete children, but we ensure all are deleted above)
    if ($parent_page_id > 0) {
        wp_delete_post($parent_page_id, true); // Force delete (skip trash)
    }
    
    // Remove menu items for StageKitWP pages
    $menu_name = 'primary';
    $locations = get_nav_menu_locations();
    
    if (isset($locations[$menu_name])) {
        $menu = wp_get_nav_menu_object($locations[$menu_name]);
        if ($menu) {
            $menu_items = wp_get_nav_menu_items($menu->term_id);
            if ($menu_items) {
                foreach ($menu_items as $item) {
                    // Delete items that link to our StageKitWP pages
                    if ($item->object === 'page' && ($item->object_id == $parent_page_id || in_array($item->object_id, wp_list_pluck(get_pages(array('post_name__in' => $sample_page_slugs)), 'ID')))) {
                        wp_delete_post($item->ID, true); // Menu items are posts of type nav_menu_item
                    }
                }
            }
        }
    }
    
    return true;
}

/**
 * Create sample pages for each CPT with appropriate shortcodes
 */
function stagekitwp_create_sample_pages() {
    $sample_pages = array(
        'STAGEKITWP_Shows' => array(
            'content' => '<h2>Our Upcoming and Past Theatre Productions</h2><p>Browse all shows from our current and upcoming seasons.</p>' . "\n\n" . '[stagekitwp_shows]',
            'template' => 'default'
        ),
        'STAGEKITWP_Current_Season' => array(
            'content' => '<h2>Current Season</h2><p>Welcome to our current season! Here you can find shows and season information.</p>' . "\n\n" . '[stagekitwp_seasons which="current"]' . "\n" . '[stagekitwp_season_shows which="current"]',
            'template' => 'default'
        ),
        'STAGEKITWP_Board_Members' => array(
            'content' => '<h2>Meet Our Dedicated Board Members</h2><p>The Board of Directors that make our productions possible.</p>' . "\n\n" . '[stagekitwp_board_members]',
            'template' => 'default'
        ),
        'STAGEKITWP_Sponsors' => array(
            'content' => '<h2>Theatre Sponsors</h2><p>We are grateful for the generous support of our sponsors!</p>' . "\n\n" . '[stagekitwp_sponsors]' . "\n\n" . '<h3>Featured Sponsor Slider</h3>' . "\n" . '[stagekitwp_sponsors layout="slider"]',
            'template' => 'default'
        ),
        'STAGEKITWP_Advertisers' => array(
            'content' => '<h2>Local Businesses & Advertisers</h2><p>Support our advertisers who help make our productions possible.</p>' . "\n\n" . '[stagekitwp_advertisers]',
            'template' => 'default'
        ),
        'STAGEKITWP_Contributors' => array(
            'content' => '<h2>Contributors & Donors</h2><p>Thank you to all our contributors and donors.</p>' . "\n\n" . '[stagekitwp_contributors]',
            'template' => 'default'
        ),
        'STAGEKITWP_Seasons' => array(
            'content' => '<h2>Theatre Seasons</h2><p>Browse all our theatre seasons.</p>' . "\n\n" . '[stagekitwp_seasons]',
            'template' => 'default'
        ),
        'STAGEKITWP_Programs' => array(
            'content' => '<h2>Show Programs</h2><p>View and download our show programs and playbills.</p>' . "\n\n" . '[stagekitwp_programs]',
            'template' => 'default'
        ),
        'STAGEKITWP_Testimonials' => array(
            'content' => '<h2>What Patrons Are Saying</h2><p>Read testimonials from our wonderful theatre patrons.</p>' . "\n\n" . '[stagekitwp_testimonials]',
            'template' => 'default'
        ),

        'STAGEKITWP_Show_Cast' => array(
            'content' => '<h2>Cast for a Show</h2><p>To display cast for a specific show, edit this page and replace show_id with the actual show ID.</p>' . "\n\n" . '[stagekitwp_show_cast show_id="1"]',
            'template' => 'default'
        ),
        'STAGEKITWP_Season_Images' => array(
            'content' => '<h2>Season Images</h2><p>Season images are now managed via <code>[stagekitwp_seasons]</code> with field toggles. Example: <code>[stagekitwp_seasons season_id="1" show_image="true" show_image_back="true" show_social_banner="true"]</code></p>' . "\n\n" . '[stagekitwp_seasons which="current" show_image="true" show_image_back="true" show_social_banner="true" show_sm_square="true" show_sm_portrait="true"]',
            'template' => 'default'
        ),
        'STAGEKITWP_Season_Shows' => array(
            'content' => '<h2>Shows by Season</h2><p>View shows organized by current, upcoming, and past seasons.</p>' . "\n\n" . '[stagekitwp_season_shows which="current_and_next"]',
            'template' => 'default'
        ),
        'STAGEKITWP_Media' => array(
            'content' => '<h2>Media Library</h2><p>This page is intended for downloadable programmes and season imagery.</p>'
                . "\n\n" . '<h3>Programmes</h3>'
                . "\n" . '[stagekitwp_programs]'
                . "\n\n" . '<h3>Season Image Gallery</h3>'
                . "\n" . '[stagekitwp_seasons which="current" show_image="true" show_image_back="true" show_social_banner="true" show_sm_square="true" show_sm_portrait="true"]',
            'template' => 'default'
        ),
        'STAGEKITWP_Auditions' => array(
            'content' => '<h2>Upcoming Auditions</h2><p>Check out our upcoming audition dates and details.</p>' . "\n\n" . '[stagekitwp_auditions]',
            'template' => 'default'
        ),
        'STAGEKITWP_Awards' => array(
            'content' => '<h2>Theatre Awards & Honors</h2><p>Celebrating the achievements and accolades of our talented performers and productions.</p>' . "\n\n" . '[stagekitwp_awards]',
            'template' => 'default'
        ),
        'STAGEKITWP_Venues' => array(
            'content' => '<h2>Performance Venues</h2><p>Discover the theatres and venues where our shows take place. Click on any venue for directions and more information.</p>' . "\n\n" . '[stagekitwp_venues]',
            'template' => 'default'
        ),
        'STAGEKITWP_Tickets' => array(
            'content' => '<h2>Get Your Tickets</h2><p>Purchase tickets for the current season or individual shows. Click the buttons below to buy your tickets online.</p>' . "\n\n" . '[stagekitwp_tickets]',
            'template' => 'default'
        ),
    );

    // Create a parent page for theatre content
    $parent_page_id = wp_insert_post(array(
        'post_title' => 'StageKitWP',
        'post_content' => '<h2>StageKitWP Sample Pages</h2><p>Welcome to the StageKitWP sample content! This section demonstrates all the available shortcodes and displays. Each page below shows a different feature of the plugin.</p><p>You can customize these pages as needed, or create your own pages using the shortcodes referenced in <strong>StageKitWP → Instructions</strong>.</p>',
        'post_status' => 'publish',
        'post_type' => 'page',
        'post_name' => 'stagekitwp-core'
    ));

    if (is_wp_error($parent_page_id)) {
        return false;
    }

    // Create each sample page
    foreach ($sample_pages as $title => $details) {
        // Check if page already exists by post_name
        $post_name = sanitize_title($title);
        $existing_page = get_page_by_path($post_name, OBJECT, 'page');
        if ($existing_page) {
            continue;
        }

        // Create the page
        $page_id = wp_insert_post(array(
            'post_title' => $title,
            'post_content' => $details['content'],
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_name' => $post_name,
            'post_parent' => $parent_page_id,
            'page_template' => $details['template']
        ));

        if (is_wp_error($page_id)) {
            error_log('TM Sample Page Error: ' . $title . ' - ' . $page_id->get_error_message());
            continue;
        }

        if ( $title === 'STAGEKITWP_Media' ) {
            update_option( 'stagekitwp_media_page_id', intval( $page_id ) );
        }
    }

    // Create a menu item for the parent page and sub-items for each sample page
    $menu_name = 'primary';
    $locations = get_nav_menu_locations();
    
    if (isset($locations[$menu_name])) {
        $menu = wp_get_nav_menu_object($locations[$menu_name]);
        
        if ($menu) {
            // Create parent menu item
            $parent_menu_item_id = wp_update_nav_menu_item($menu->term_id, 0, array(
                'menu-item-title' => 'StageKitWP',
                'menu-item-object-id' => $parent_page_id,
                'menu-item-object' => 'page',
                'menu-item-status' => 'publish',
                'menu-item-type' => 'post_type',
                'menu-item-position' => -1
            ));
            
            // Create sub-menu items for each sample page
            if ($parent_menu_item_id && !is_wp_error($parent_menu_item_id)) {
                $sub_position = 0;
                foreach ($sample_pages as $title => $details) {
                    $post_name = sanitize_title($title);
                    $page = get_page_by_path($post_name, OBJECT, 'page');
                    if ($page) {
                        wp_update_nav_menu_item($menu->term_id, 0, array(
                            'menu-item-title' => $title,
                            'menu-item-object-id' => $page->ID,
                            'menu-item-object' => 'page',
                            'menu-item-parent-id' => $parent_menu_item_id,
                            'menu-item-status' => 'publish',
                            'menu-item-type' => 'post_type',
                            'menu-item-position' => $sub_position
                        ));
                        $sub_position++;
                    }
                }
            }
        }
    }

    return true;
}

/**
 * Add menu item to Tools menu
 */
function stagekitwp_add_sample_content_page() {
    add_management_page(
        'StageKitWP Sample Content',
        'TM Sample Content',
        'manage_options',
        'stagekitwp-sample-content',
        'stagekitwp_render_sample_content_page'
    );
}
add_action('admin_menu', 'stagekitwp_add_sample_content_page');

/**
 * Render the sample content admin page
 */
function stagekitwp_render_sample_content_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $message = '';
    
    // Handle delete action
    if (isset($_POST['stagekitwp_delete_sample_pages']) && check_admin_referer('stagekitwp_delete_sample_pages')) {
        stagekitwp_delete_sample_pages();
        $message = '<div class="notice notice-info"><p>' . esc_html__( 'Sample pages have been deleted.', 'stagekitwp-core' ) . '</p></div>';
    }
    
    // Handle create action
    if (isset($_POST['stagekitwp_create_sample_pages']) && check_admin_referer('stagekitwp_create_sample_pages')) {
        // Delete existing sample pages first
        stagekitwp_delete_sample_pages();
        // Then create new ones
        if (stagekitwp_create_sample_pages()) {
            $message = '<div class="notice notice-success"><p>'
                . sprintf(
                    /* translators: %s: admin pages URL */
                    wp_kses_post( __( 'Sample pages have been created successfully! You can view them in the <a href="%s">Pages</a> section.', 'stagekitwp-core' ) ),
                    esc_url( admin_url('edit.php?post_type=page') )
                )
                . '</p></div>';
        } else {
            $message = '<div class="notice notice-error"><p>' . esc_html__( 'There was an error creating the sample pages. Check your server error log for details.', 'stagekitwp-core' ) . '</p></div>';
        }
    }
    
    // Check if sample pages exist
    $parent_page = get_page_by_path('stagekitwp-core', OBJECT, 'page');
    $pages_exist = !empty($parent_page);
    
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <?php echo wp_kses_post( $message ); ?>
        <div class="card">
            <h2><?php esc_html_e( 'StageKitWP Sample Pages', 'stagekitwp-core' ); ?></h2>
            <p><?php esc_html_e( 'Create demonstration pages for all StageKitWP shortcodes. Each sample page displays a different shortcode with examples.', 'stagekitwp-core' ); ?></p>
            
            <?php if ($pages_exist) : ?>
                <p style="color: green;"><strong><?php esc_html_e( '✓ Sample pages have been created.', 'stagekitwp-core' ); ?></strong></p>
                <p><?php esc_html_e( 'The following pages are available:', 'stagekitwp-core' ); ?></p>
                <ul style="list-style-type: disc; margin-left: 20px; columns: 2;">
                    <li>StageKitWP (parent)</li>
                    <li>STAGEKITWP_Shows</li>
                    <li>STAGEKITWP_Current_Season</li>
                    <li>STAGEKITWP_Board_Members</li>
                    <li>STAGEKITWP_Sponsors</li>
                    <li>STAGEKITWP_Advertisers</li>
                    <li>STAGEKITWP_Contributors</li>
                    <li>STAGEKITWP_Seasons</li>
                    <li>STAGEKITWP_Programs</li>
                    <li>STAGEKITWP_Testimonials</li>
                    <li>STAGEKITWP_Show_Cast</li>
                    <li>STAGEKITWP_Season_Images</li>
                    <li>STAGEKITWP_Season_Shows</li>
                    <li>STAGEKITWP_Media</li>
                    <li>STAGEKITWP_Auditions</li>
                </ul>
                <p><a href="<?php echo esc_url(admin_url('edit.php?post_type=page')); ?>" class="button"><?php esc_html_e( 'View All Pages', 'stagekitwp-core' ); ?></a></p>
                <form method="post" style="margin-top: 20px;">
                    <?php wp_nonce_field('stagekitwp_delete_sample_pages'); ?>
                    <p><input type="submit" name="stagekitwp_delete_sample_pages" class="button button-secondary" value="<?php echo esc_attr__( 'Delete Sample Pages', 'stagekitwp-core' ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Are you sure? This will delete all sample pages.', 'stagekitwp-core' ) ); ?>');"></p>
                </form>
            <?php else : ?>
                <p style="color: orange;"><strong><?php esc_html_e( '✕ Sample pages have not been created yet.', 'stagekitwp-core' ); ?></strong></p>
                <p><?php esc_html_e( 'Click the button below to create 17 demonstration pages showing all available StageKitWP shortcodes.', 'stagekitwp-core' ); ?></p>
                <form method="post">
                    <?php wp_nonce_field('stagekitwp_create_sample_pages'); ?>
                    <p><input type="submit" name="stagekitwp_create_sample_pages" class="button button-primary" value="<?php echo esc_attr__( 'Create Sample Pages', 'stagekitwp-core' ); ?>"></p>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <?php
}