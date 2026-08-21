<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class STAGEKITWP_RC_Library {
    
    public function __construct() {
        // Register the custom Gutenberg block category
        add_filter( 'block_categories_all', array( $this, 'register_gutenberg_block_category' ), 10, 2 );
    }

    public function run() {
        // Initialize CPTs
        $cpts = new STAGEKITWP_RC_LIBRARY_CPTs();
        add_action( 'init', array( $cpts, 'register_custom_post_types' ) );
        add_action( 'add_meta_boxes', array( $cpts, 'add_custom_meta_boxes' ) );
        add_action( 'save_post', array( $cpts, 'save_custom_meta_data' ) );

        // Register block engine hooks
        add_action( 'init', array( $this, 'register_evaluation_block_engine' ) );

        // Initialize Admin Menus
        $admin = new STAGEKITWP_RC_LIBRARY_Admin();
        add_action( 'admin_menu', array( $admin, 'register_admin_menus' ) );
    }

    /**
     * Complete block configuration parameters using schema-driven controls.
     */
    public function register_evaluation_block_engine() {
        global $wpdb;

        // 1. Gather Group options using a clean, standardized suffix delimiter ( #ID )
        $group_choices = array( __( '— Select a Circle Group — (#0)', 'stagekitwp-rc-library' ) );
        $table_groups = $wpdb->prefix . 'stagekitwp_rc_library_groups';
        if ( $wpdb->get_var("SHOW TABLES LIKE '$table_groups'") ) {
            $groups = $wpdb->get_results("SELECT id, title FROM $table_groups ORDER BY id DESC");
            foreach ( $groups as $g ) {
                $group_choices[] = esc_html( $g->title ) . ' (#' . $g->id . ')';
            }
        }

        // 2. Gather Book options using a clean, standardized suffix delimiter ( #ID )
        $book_choices = array( __( '— Select a Script Profile — (#0)', 'stagekitwp-rc-library' ) );
        $books = get_posts( array(
            'post_type'      => 'stagekitwp_book',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC'
        ) );
        foreach ( $books as $b ) {
            $book_choices[] = esc_html( $b->post_title ) . ' (#' . $b->ID . ')';
        }

        register_block_type( 'stagekitwp-rcl/evaluation-form', array(
            'title'           => __( 'Script Rubric Evaluation Form', 'stagekitwp-rc-library' ),
            'description'     => __( 'Renders active evaluation criteria tracking layout sheets.', 'stagekitwp-rc-library' ),
            'category'        => 'stagekitwp-rc-library',
            'icon'            => 'book-alt',
            'keywords'        => array( 'theatre', 'manager', 'rubric', 'evaluation' ),
            'attributes'      => array(
                'group_id' => array( 
                    'type'    => 'string',
                    'default' => $group_choices[0],
                    'enum'    => $group_choices,
                    'label'   => __( 'Circle Group Reference', 'stagekitwp-rc-library' )
                ),
                'book_id'  => array( 
                    'type'    => 'string',
                    'default' => $book_choices[0],
                    'enum'    => $book_choices,
                    'label'   => __( 'Target Script Profile', 'stagekitwp-rc-library' )
                ),
            ),
            'supports'        => array(
                'autoRegister' => true, 
                'html'         => false,
            ),
            'render_callback' => array( $this, 'render_block_fallback_html' )
        ));
    }

    /**
     * Execution proxy router mapping data to the master class layout file
     */
    public function render_block_fallback_html( $attributes ) {
        $raw_group = isset( $attributes['group_id'] ) ? $attributes['group_id'] : '';
        $raw_book  = isset( $attributes['book_id'] ) ? $attributes['book_id'] : '';

        $group_id = 0;
        $book_id  = 0;

        // FIXED: Bulletproof string parsing using a clear delimiter token split
        if ( ! empty( $raw_group ) && strpos( $raw_group, '(#' ) !== false ) {
            $parts = explode( '(#', $raw_group );
            $group_id = intval( rtrim( end( $parts ), ')' ) );
        }
        if ( ! empty( $raw_book ) && strpos( $raw_book, '(#' ) !== false ) {
            $parts = explode( '(#', $raw_book );
            $book_id = intval( rtrim( end( $parts ), ')' ) );
        }

        $clean_attributes = array(
            'group_id' => $group_id,
            'book_id'  => $book_id,
        );

        // Render localized preview info dashboard if viewed inside Gutenberg back-end workspace
        if ( is_admin() && defined( 'REST_REQUEST' ) && REST_REQUEST ) {
            $clean_group_label = preg_replace( '/\s*\(#\d+\)/', '', $raw_group );
            $clean_book_label  = preg_replace( '/\s*\(#\d+\)/', '', $raw_book );

            return '<div style="background:#f0f6fa; border:2px dashed #2271b1; padding:25px; border-radius:6px; font-family:sans-serif;">' .
                   '<h4 style="margin:0 0 10px 0; color:#2271b1;">📋 Script Rubric Evaluation Assignment Workspace</h4>' .
                   '<p style="font-size:13px; margin:4px 0;"><strong>Active Target Group:</strong> ' . esc_html( $clean_group_label ) . ' <span style="color:#646970;">(Parsed ID: ' . $group_id . ')</span></p>' .
                   '<p style="font-size:13px; margin:4px 0;"><strong>Assigned Evaluated Book:</strong> ' . esc_html( $clean_book_label ) . ' <span style="color:#646970;">(Parsed ID: ' . $book_id . ')</span></p>' .
                   '</div>';
        }

        if ( class_exists( 'STAGEKITWP_RC_LIBRARY_Evaluation_Form' ) ) {
            $form_engine = new STAGEKITWP_RC_LIBRARY_Evaluation_Form();
            return $form_engine->generate_form_html( $clean_attributes );
        }
        return '<p>' . esc_html__( 'Form engine initialization error.', 'stagekitwp-rc-library' ) . '</p>';
    }

    /**
     * Registers the custom RC Library category in the Gutenberg block inserter.
     */
    public function register_gutenberg_block_category( $categories, $post ) {
        return array_merge(
            $categories,
            array(
                array(
                    'slug'  => 'stagekitwp-rc-library',
                    'title' => __( 'RC Library', 'stagekitwp-rc-library' ),
                    'icon'  => 'book-alt',
                ),
            )
        );
    }
}