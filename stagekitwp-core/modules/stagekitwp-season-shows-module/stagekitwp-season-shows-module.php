<?php
/**
 * StageKitWP - Season Shows Beaver Builder Module
 * 
 * Registers and configures the Season Shows custom module for Beaver Builder
 * 
 * @package StageKitWP
 * @subpackage Beaver Builder Modules
 */

if ( class_exists( 'FLBuilderModule' ) ) {
    class STAGEKITWP_Season_Shows_BB_Module extends FLBuilderModule {
        public function __construct() {
            parent::__construct(array(
                'name'            => __( 'Season Shows', 'stagekitwp-core' ),
                'description'     => __( 'Display shows organized by season with current/upcoming/past headers', 'stagekitwp-core' ),
                'group'           => 'stagekitwp-core',
                'category'        => 'stagekitwp-core',
                'dir'             => plugin_dir_path( __FILE__ ),
                'url'             => plugin_dir_url( __FILE__ ),
                'enabled'         => true,
                'partial_refresh' => true
            ));
        }
    }

    /**
     * Helper function to get seasons for dropdown
     */
    function stagekitwp_get_bb_seasons_dropdown() {
        $options = array( '' => __( '— All Seasons —', 'stagekitwp-core' ) );
        $seasons = get_posts( array(
            'post_type'      => 'season',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'meta_value',
            'meta_key'       => '_stagekitwp_season_start_date',
            'order'          => 'DESC'
        ) );
        foreach ( $seasons as $season ) {
            $options[$season->ID] = esc_html( $season->post_title );
        }
        return $options;
    }

    // Register module options and settings
    FLBuilder::register_module( 'STAGEKITWP_Season_Shows_BB_Module', array(
        // Data Configuration Tab
        'data' => array(
            'title'    => __( 'Data Configuration', 'stagekitwp-core' ),
            'sections' => array(
                'filtering' => array(
                    'title'  => __( 'Filtering', 'stagekitwp-core' ),
                    'fields' => array(
                        'season_id' => array(
                            'type'    => 'select',
                            'label'   => __( 'Season', 'stagekitwp-core' ),
                            'options' => 'stagekitwp_get_bb_seasons_dropdown',
                            'help'    => __( 'Leave empty to show all seasons', 'stagekitwp-core' )
                        ),
                        'which' => array(
                            'type'    => 'select',
                            'label'   => __( 'Season Filter', 'stagekitwp-core' ),
                            'options' => array(
                                'all'              => __( 'All Seasons', 'stagekitwp-core' ),
                                'current'          => __( 'Current Season Only', 'stagekitwp-core' ),
                                'next'             => __( 'Next Season Only', 'stagekitwp-core' ),
                                'current_and_next' => __( 'Current & Next', 'stagekitwp-core' )
                            ),
                            'default' => 'all'
                        )
                    )
                ),
                'sorting' => array(
                    'title'  => __( 'Sorting', 'stagekitwp-core' ),
                    'fields' => array(
                        'orderby' => array(
                            'type'    => 'select',
                            'label'   => __( 'Order By', 'stagekitwp-core' ),
                            'options' => array(
                                'title'     => __( 'Show Title', 'stagekitwp-core' ),
                                'time_slot' => __( 'Time Slot (Fall/Winter/Spring)', 'stagekitwp-core' ),
                                'date'      => __( 'Date', 'stagekitwp-core' )
                            ),
                            'default' => 'title'
                        ),
                        'order' => array(
                            'type'    => 'select',
                            'label'   => __( 'Order', 'stagekitwp-core' ),
                            'options' => array(
                                'ASC'  => __( 'Ascending', 'stagekitwp-core' ),
                                'DESC' => __( 'Descending', 'stagekitwp-core' )
                            ),
                            'default' => 'ASC'
                        )
                    )
                )
            )
        ),

        // Display Options Tab
        'display' => array(
            'title'    => __( 'Display Options', 'stagekitwp-core' ),
            'sections' => array(
                'layout' => array(
                    'title'  => __( 'Layout', 'stagekitwp-core' ),
                    'fields' => array(
                        'layout' => array(
                            'type'    => 'select',
                            'label'   => __( 'Layout Type', 'stagekitwp-core' ),
                            'options' => array(
                                'list' => __( 'List', 'stagekitwp-core' ),
                                'grid' => __( 'Card Grid', 'stagekitwp-core' )
                            ),
                            'default' => 'grid'
                        ),
                        'columns_lg' => array(
                            'type'        => 'text',
                            'label'       => __( 'Desktop Columns', 'stagekitwp-core' ),
                            'placeholder' => '3',
                            'default'     => 3,
                            'help'        => __( 'Grid layout only', 'stagekitwp-core' )
                        ),
                        'columns_md' => array(
                            'type'        => 'text',
                            'label'       => __( 'Tablet Columns', 'stagekitwp-core' ),
                            'placeholder' => '2',
                            'default'     => 2,
                            'help'        => __( 'Grid layout only', 'stagekitwp-core' )
                        ),
                        'columns_sm' => array(
                            'type'        => 'text',
                            'label'       => __( 'Mobile Columns', 'stagekitwp-core' ),
                            'placeholder' => '1',
                            'default'     => 1,
                            'help'        => __( 'Grid layout only', 'stagekitwp-core' )
                        )
                    )
                ),
                'visibility' => array(
                    'title'  => __( 'Content Visibility', 'stagekitwp-core' ),
                    'fields' => array(
                        'hide_description' => array(
                            'type'    => 'select',
                            'label'   => __( 'Hide Synopsis', 'stagekitwp-core' ),
                            'options' => array(
                                ''      => __( 'Show', 'stagekitwp-core' ),
                                'true'  => __( 'Hide', 'stagekitwp-core' )
                            ),
                            'default' => ''
                        ),
                        'hide_image' => array(
                            'type'    => 'select',
                            'label'   => __( 'Hide Show Image', 'stagekitwp-core' ),
                            'options' => array(
                                ''      => __( 'Show', 'stagekitwp-core' ),
                                'true'  => __( 'Hide', 'stagekitwp-core' )
                            ),
                            'default' => ''
                        ),
                        'hide_director' => array(
                            'type'    => 'select',
                            'label'   => __( 'Hide Director', 'stagekitwp-core' ),
                            'options' => array(
                                ''      => __( 'Show', 'stagekitwp-core' ),
                                'true'  => __( 'Hide', 'stagekitwp-core' )
                            ),
                            'default' => ''
                        ),
                        'hide_author' => array(
                            'type'    => 'select',
                            'label'   => __( 'Hide Author', 'stagekitwp-core' ),
                            'options' => array(
                                ''      => __( 'Show', 'stagekitwp-core' ),
                                'true'  => __( 'Hide', 'stagekitwp-core' )
                            ),
                            'default' => ''
                        ),
                        'hide_producer' => array(
                            'type'    => 'select',
                            'label'   => __( 'Hide Producer', 'stagekitwp-core' ),
                            'options' => array(
                                ''      => __( 'Show', 'stagekitwp-core' ),
                                'true'  => __( 'Hide', 'stagekitwp-core' )
                            ),
                            'default' => ''
                        ),
                        'hide_stage_manager' => array(
                            'type'    => 'select',
                            'label'   => __( 'Hide Stage Manager', 'stagekitwp-core' ),
                            'options' => array(
                                ''      => __( 'Show', 'stagekitwp-core' ),
                                'true'  => __( 'Hide', 'stagekitwp-core' )
                            ),
                            'default' => ''
                        ),
                        'hide_dates' => array(
                            'type'    => 'select',
                            'label'   => __( 'Hide Show Dates', 'stagekitwp-core' ),
                            'options' => array(
                                ''      => __( 'Show', 'stagekitwp-core' ),
                                'true'  => __( 'Hide', 'stagekitwp-core' )
                            ),
                            'default' => ''
                        )
                    )
                )
            )
        ),

        // Styling Tab
        'styling' => array(
            'title'    => __( 'Styling', 'stagekitwp-core' ),
            'sections' => array(
                'colors' => array(
                    'title'  => __( 'Colors', 'stagekitwp-core' ),
                    'fields' => array(
                        'color_bg' => array(
                            'type'        => 'color',
                            'label'       => __( 'Background Color', 'stagekitwp-core' ),
                            'default'     => '#ffffff'
                        ),
                        'color_text' => array(
                            'type'        => 'color',
                            'label'       => __( 'Text Color', 'stagekitwp-core' ),
                            'default'     => '#000000'
                        ),
                        'color_heading' => array(
                            'type'        => 'color',
                            'label'       => __( 'Heading Color', 'stagekitwp-core' ),
                            'default'     => '#333333'
                        ),
                    )
                ),
                'borders' => array(
                    'title'  => __( 'Borders', 'stagekitwp-core' ),
                    'fields' => array(
                        'border_color' => array(
                            'type'        => 'color',
                            'label'       => __( 'Border Color', 'stagekitwp-core' ),
                            'default'     => '#dddddd'
                        ),
                        'border_width' => array(
                            'type'        => 'text',
                            'label'       => __( 'Border Width (px)', 'stagekitwp-core' ),
                            'placeholder' => '1',
                            'default'     => 1
                        ),
                        'border_radius' => array(
                            'type'        => 'text',
                            'label'       => __( 'Border Radius (px)', 'stagekitwp-core' ),
                            'placeholder' => '8',
                            'default'     => 8
                        )
                    )
                ),
                'spacing' => array(
                    'title'  => __( 'Spacing & Effects', 'stagekitwp-core' ),
                    'fields' => array(
                        'padding' => array(
                            'type'        => 'text',
                            'label'       => __( 'Padding', 'stagekitwp-core' ),
                            'placeholder' => '15px',
                            'default'     => '15px',
                            'help'        => __( 'e.g., 10px, 10px 20px, etc.', 'stagekitwp-core' )
                        ),
                        'shadow' => array(
                            'type'        => 'text',
                            'label'       => __( 'Drop Shadow', 'stagekitwp-core' ),
                            'placeholder' => '0 2px 8px rgba(0,0,0,0.1)',
                            'default'     => '0 2px 8px rgba(0,0,0,0.1)',
                            'help'        => __( 'CSS box-shadow format', 'stagekitwp-core' )
                        )
                    )
                )
            )
        )
    ));
}
