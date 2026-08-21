<?php
/**
 * Functions for creating sample content pages
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Delete all sample pages
 */
function tm_delete_sample_pages() {
    $parent_page = get_page_by_path('theatre-manager', OBJECT, 'page');
    if ($parent_page) {
        wp_delete_post($parent_page->ID, true);
    }
    
    $sample_page_slugs = array(
        'tm-shows', 'tm-current-season', 'tm-cast-members', 'tm-board-members',
        'tm-sponsors', 'tm-advertisers', 'tm-contributors', 'tm-seasons',
        'tm-programs', 'tm-testimonials', 'tm-season-cast', 'tm-show-cast',
        'tm-season-images', 'tm-season-shows', 'tm-auditions'
    );
    
    foreach ($sample_page_slugs as $slug) {
        $page = get_page_by_path($slug, OBJECT, 'page');
        if ($page) {
            wp_delete_post($page->ID, true);
        }
    }
    
    return true;
}

/**
 * Create sample pages for each CPT with appropriate shortcodes
 */
function tm_create_sample_pages() {
    $sample_pages = array(
        'TM_Shows' => array(
            'content' => '<h2>Our Upcoming and Past Theatre Productions</h2><p>Browse all shows from our current and upcoming seasons.</p>' . "\n\n" . '[tm_shows]',
            'template' => 'default'
        ),
        'TM_Current_Season' => array(
            'content' => '<h2>Current Season</h2><p>Welcome to our current season! Here you can find shows, cast, and season information.</p>' . "\n\n" . '[tm_season_banner]' . "\n" . '[tm_season_shows which="current"]' . "\n" . '[tm_season_cast which="current"]',
            'template' => 'default'
        ),
        'TM_Cast_Members' => array(
            'content' => '<h2>Meet Our Talented Cast Members</h2><p>View cast members from all our productions.</p>' . "\n\n" . '[tm_cast]',
            'template' => 'default'
        ),
        'TM_Board_Members' => array(
            'content' => '<h2>Meet Our Dedicated Board Members</h2><p>The Board of Directors that make our productions possible.</p>' . "\n\n" . '[tm_board_members]',
            'template' => 'default'
        ),
        'TM_Sponsors' => array(
            'content' => '<h2>Theatre Sponsors</h2><p>We are grateful for the generous support of our sponsors!</p>' . "\n\n" . '[tm_sponsors]' . "\n\n" . '<h3>Featured Sponsors</h3>' . "\n" . '[tm_sponsor_slider]',
            'template' => 'default'
        ),
        'TM_Advertisers' => array(
            'content' => '<h2>Local Businesses & Advertisers</h2><p>Support our advertisers who help make our productions possible.</p>' . "\n\n" . '[tm_advertisers]',
            'template' => 'default'
        ),
        'TM_Contributors' => array(
            'content' => '<h2>Contributors & Donors</h2><p>Thank you to all our contributors and donors.</p>' . "\n\n" . '[tm_contributors]',
            'template' => 'default'
        ),
        'TM_Seasons' => array(
            'content' => '<h2>Theatre Seasons</h2><p>Browse all our theatre seasons.</p>' . "\n\n" . '[tm_seasons]',
            'template' => 'default'
        ),
        'TM_Programs' => array(
            'content' => '<h2>Show Programs</h2><p>View and download our show programs and playbills.</p>' . "\n\n" . '[tm_programs]',
            'template' => 'default'
        ),
        'TM_Testimonials' => array(
            'content' => '<h2>What Patrons Are Saying</h2><p>Read testimonials from our wonderful theatre patrons.</p>' . "\n\n" . '[tm_testimonials]',
            'template' => 'default'
        ),
        'TM_Season_Cast' => array(
            'content' => '<h2>Cast by Season</h2><p>View cast members organized by season.</p>' . "\n\n" . '[tm_season_cast which="all"]',
            'template' => 'default'
        ),
        'TM_Show_Cast' => array(
            'content' => '<h2>Cast for a Show</h2><p>To display cast for a specific show, edit this page and replace show_id with the actual show ID.</p>' . "\n\n" . '[tm_show_cast show_id="1"]',
            'template' => 'default'
        ),
        'TM_Season_Images' => array(
            'content' => '<h2>Season Images</h2><p>To display images for a specific season, edit this page and replace season_id with the actual season ID.</p>' . "\n\n" . '[tm_season_images season_id="1"]',
            'template' => 'default'
        ),
        'TM_Season_Shows' => array(
            'content' => '<h2>Shows by Season</h2><p>View shows organized by current, upcoming, and past seasons.</p>' . "\n\n" . '[tm_season_shows which="current_and_next"]',
            'template' => 'default'
        ),
        'TM_Auditions' => array(
            'content' => '<h2>Upcoming Auditions</h2><p>Check out our upcoming audition dates and details.</p>' . "\n\n" . '[tm_auditions]',
            'template' => 'default'
        ),
    );

    // Create a parent page for theatre content
    $parent_page_id = wp_insert_post(array(
        'post_title' => 'Theatre Manager',
        'post_content' => '<h2>Theatre Manager Sample Pages</h2><p>Welcome to the Theatre Manager sample content! This section demonstrates all the available shortcodes and displays. Each page below shows a different feature of the plugin.</p><p>You can customize these pages as needed, or create your own pages using the shortcodes referenced in <strong>Theatre Manager → Instructions</strong>.</p>',
        'post_status' => 'publish',
        'post_type' => 'page',
        'post_name' => 'theatre-manager'
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
    }

    // Create a menu item for the parent page
    $menu_name = 'primary';
    $locations = get_nav_menu_locations();
    
    if (isset($locations[$menu_name])) {
        $menu = wp_get_nav_menu_object($locations[$menu_name]);
        
            if ($menu) {
            wp_update_nav_menu_item($menu->term_id, 0, array(
                'menu-item-title' => 'TM_Theatre',
                'menu-item-object-id' => $parent_page_id,
                'menu-item-object' => 'page',
                'menu-item-status' => 'publish',
                'menu-item-type' => 'post_type',
                'menu-item-position' => -1
            ));
        }
    }

    return true;
}

/**
 * Add menu item to Tools menu
 */
function tm_add_sample_content_page() {
    add_management_page(
        'Theatre Manager Sample Content',
        'TM Sample Content',
        'manage_options',
        'tm-sample-content',
        'tm_render_sample_content_page'
    );
}
add_action('admin_menu', 'tm_add_sample_content_page');

/**
 * Render the sample content admin page
 */
function tm_render_sample_content_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $message = '';
    
    // Handle delete action
    if (isset($_POST['tm_delete_sample_pages']) && check_admin_referer('tm_delete_sample_pages')) {
        tm_delete_sample_pages();
        $message = '<div class="notice notice-info"><p>Sample pages have been deleted.</p></div>';
    }
    
    // Handle create action
    if (isset($_POST['tm_create_sample_pages']) && check_admin_referer('tm_create_sample_pages')) {
        // Delete existing sample pages first
        tm_delete_sample_pages();
        // Then create new ones
        if (tm_create_sample_pages()) {
            $message = '<div class="notice notice-success"><p>Sample pages have been created successfully! You can view them in the <a href="' . esc_url(admin_url('edit.php?post_type=page')) . '">Pages</a> section.</p></div>';
        } else {
            $message = '<div class="notice notice-error"><p>There was an error creating the sample pages. Check your server error log for details.</p></div>';
        }
    }
    
    // Check if sample pages exist
    $parent_page = get_page_by_path('theatre-manager', OBJECT, 'page');
    $pages_exist = !empty($parent_page);
    
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <?php echo $message; ?>
        <div class="card">
            <h2>Theatre Manager Sample Pages</h2>
            <p>Create demonstration pages for all Theatre Manager shortcodes. Each sample page displays a different shortcode with examples.</p>
            
            <?php if ($pages_exist) : ?>
                <p style="color: green;"><strong>✓ Sample pages have been created.</strong></p>
                <p>The following pages are available:</p>
                <ul style="list-style-type: disc; margin-left: 20px; columns: 2;">
                    <li>Theatre Manager (parent)</li>
                    <li>TM_Shows</li>
                    <li>TM_Current_Season</li>
                    <li>TM_Cast_Members</li>
                    <li>TM_Board_Members</li>
                    <li>TM_Sponsors</li>
                    <li>TM_Advertisers</li>
                    <li>TM_Contributors</li>
                    <li>TM_Seasons</li>
                    <li>TM_Programs</li>
                    <li>TM_Testimonials</li>
                    <li>TM_Season_Cast</li>
                    <li>TM_Show_Cast</li>
                    <li>TM_Season_Images</li>
                    <li>TM_Season_Shows</li>
                    <li>TM_Auditions</li>
                </ul>
                <p><a href="<?php echo esc_url(admin_url('edit.php?post_type=page')); ?>" class="button">View All Pages</a></p>
                <form method="post" style="margin-top: 20px;">
                    <?php wp_nonce_field('tm_delete_sample_pages'); ?>
                    <p><input type="submit" name="tm_delete_sample_pages" class="button button-secondary" value="Delete Sample Pages" onclick="return confirm('Are you sure? This will delete all sample pages.');"></p>
                </form>
            <?php else : ?>
                <p style="color: orange;"><strong>✕ Sample pages have not been created yet.</strong></p>
                <p>Click the button below to create 16 demonstration pages showing all available Theatre Manager shortcodes.</p>
                <form method="post">
                    <?php wp_nonce_field('tm_create_sample_pages'); ?>
                    <p><input type="submit" name="tm_create_sample_pages" class="button button-primary" value="Create Sample Pages"></p>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <?php
}