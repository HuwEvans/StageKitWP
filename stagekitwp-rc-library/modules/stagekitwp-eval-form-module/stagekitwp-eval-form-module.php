<?php
if ( class_exists( 'FLBuilderModule' ) ) {
    class STAGEKITWP_RC_LIBRARY_Eval_Form_BB_Module extends FLBuilderModule {
        public function __construct() {
            parent::__construct(array(
                'name'            => __( 'Script Rubric Form', 'stagekitwp-rc-library' ),
                'description'     => __( 'Renders evaluation sheets mapping context profiles.', 'stagekitwp-rc-library' ),
                'category'        => __( 'RC Library', 'stagekitwp-rc-library' ),
                'dir'             => plugin_dir_path( __FILE__ ),
                'url'             => plugin_dir_url( __FILE__ ),
                'enabled'         => true,
                'partial_refresh' => true
            ));
        }
        // REMOVED: public function render() override block. Let BB look for standard frontend.php routing.
    }

    /**
     * Helper functions to build layout dropdown datasets dynamically inside the options panel
     */
    function stagekitwp_rc_library_get_bb_groups() {
        global $wpdb;
        $options = array( '' => __( '— Select a Circle Group —', 'stagekitwp-rc-library' ) );
        $table_groups = $wpdb->prefix . 'stagekitwp_rc_library_groups';
        
        if ( $wpdb->get_var("SHOW TABLES LIKE '$table_groups'") ) {
            $groups = $wpdb->get_results("SELECT id, title FROM $table_groups ORDER BY id DESC");
            foreach ( $groups as $g ) {
                $options[$g->id] = esc_html( $g->title ) . ' (ID: ' . $g->id . ')';
            }
        }
        return $options;
    }

    function stagekitwp_rc_library_get_bb_books() {
        $options = array( '' => __( '— Select a Script Profile —', 'stagekitwp-rc-library' ) );
        $books = get_posts( array(
            'post_type'      => 'stagekitwp_book',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC'
        ) );
        foreach ( $books as $b ) {
            $options[$b->ID] = esc_html( $b->post_title );
        }
        return $options;
    }

    // Register options properties dashboard controller parameters inside the editor workspace panels
    FLBuilder::register_module( 'STAGEKITWP_RC_LIBRARY_Eval_Form_BB_Module', array(
        'general' => array(
            'title'    => __( 'Data Configuration', 'stagekitwp-rc-library' ),
            'sections' => array(
                'general' => array(
                    'title'  => __( 'Context Mappings', 'stagekitwp-rc-library' ),
                    'fields' => array(
                        'group_id' => array(
                            'type'    => 'select',
                            'label'   => __( 'Circle Group Reference', 'stagekitwp-rc-library' ),
                            'options' => stagekitwp_rc_library_get_bb_groups(),
                        ),
                        'book_id'  => array(
                            'type'    => 'select',
                            'label'   => __( 'Target Script Profile', 'stagekitwp-rc-library' ),
                            'options' => stagekitwp_rc_library_get_bb_books(),
                        ),
                    )
                )
            )
        )
    ));
}