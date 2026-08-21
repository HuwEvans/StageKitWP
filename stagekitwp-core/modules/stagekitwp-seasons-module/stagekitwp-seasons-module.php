<?php
/**
 * StageKitWP - Seasons Beaver Builder Module
 * 
 * Custom Beaver Builder module for [stagekitwp_seasons] shortcode
 * 
 * @package StageKitWP
 * @subpackage Beaver Builder Modules
 */

if ( ! class_exists( 'FLBuilderModule' ) ) {
    return;
}

class STAGEKITWP_Seasons_BB_Module extends FLBuilderModule {
    public function __construct() {
        parent::__construct(array(
            'name'            => __('StageKitWP: Seasons', 'stagekitwp-core'),
            'description'     => __('Display theatre seasons with filtering and styling', 'stagekitwp-core'),
            'group'           => 'stagekitwp-core',
            'category'        => 'stagekitwp-core',
            'dir'             => STAGEKITWP_CORE_DIR . 'modules/stagekitwp-seasons-module/',
            'url'             => STAGEKITWP_CORE_URL . 'modules/stagekitwp-seasons-module/',
            'enabled'         => true,
        ));
    }
}

FLBuilder::register_module('STAGEKITWP_Seasons_BB_Module', array(
    'data_config' => array(
        'title'    => __('Data Configuration', 'stagekitwp-core'),
        'sections' => array(
            'filtering' => array(
                'title'  => __('Filtering & Sorting', 'stagekitwp-core'),
                'fields' => array(
                    'which' => array(
                        'type'    => 'select',
                        'label'   => __('Season Filter', 'stagekitwp-core'),
                        'options' => array(
                            'all'      => __('All Seasons', 'stagekitwp-core'),
                            'current'  => __('Current Season', 'stagekitwp-core'),
                            'upcoming' => __('Upcoming Seasons', 'stagekitwp-core'),
                            'past'     => __('Past Seasons', 'stagekitwp-core'),
                        ),
                        'default' => 'all'
                    ),
                    'orderby' => array(
                        'type'    => 'select',
                        'label'   => __('Order By', 'stagekitwp-core'),
                        'options' => array(
                            'start_date' => __('Start Date', 'stagekitwp-core'),
                            'end_date'   => __('End Date', 'stagekitwp-core'),
                            'title'      => __('Title', 'stagekitwp-core'),
                        ),
                        'default' => 'start_date'
                    ),
                    'order' => array(
                        'type'    => 'select',
                        'label'   => __('Order', 'stagekitwp-core'),
                        'options' => array(
                            'ASC'  => __('Ascending', 'stagekitwp-core'),
                            'DESC' => __('Descending', 'stagekitwp-core'),
                        ),
                        'default' => 'ASC'
                    ),
                    'limit' => array(
                        'type'        => 'text',
                        'label'       => __('Limit', 'stagekitwp-core'),
                        'placeholder' => '-1',
                        'default'     => '-1',
                        'help'        => __('-1 for unlimited', 'stagekitwp-core')
                    ),
                )
            ),
            'layout' => array(
                'title'  => __('Layout', 'stagekitwp-core'),
                'fields' => array(
                    'layout' => array(
                        'type'    => 'select',
                        'label'   => __('Layout Type', 'stagekitwp-core'),
                        'options' => array(
                            'grid'  => __('Grid', 'stagekitwp-core'),
                            'table' => __('Table', 'stagekitwp-core'),
                        ),
                        'default' => 'grid'
                    ),
                    'columns_lg' => array(
                        'type'        => 'text',
                        'label'       => __('Desktop Columns', 'stagekitwp-core'),
                        'placeholder' => '3',
                        'default'     => 3,
                        'help'        => __('Grid layout only', 'stagekitwp-core')
                    ),
                    'columns_md' => array(
                        'type'        => 'text',
                        'label'       => __('Tablet Columns', 'stagekitwp-core'),
                        'placeholder' => '2',
                        'default'     => 2,
                        'help'        => __('Grid layout only', 'stagekitwp-core')
                    ),
                    'columns_sm' => array(
                        'type'        => 'text',
                        'label'       => __('Mobile Columns', 'stagekitwp-core'),
                        'placeholder' => '1',
                        'default'     => 1,
                        'help'        => __('Grid layout only', 'stagekitwp-core')
                    ),
                )
            ),
        )
    ),
    'visibility' => array(
        'title'  => __('Content Visibility', 'stagekitwp-core'),
        'fields' => array(
            'hide_image' => array(
                'type'    => 'select',
                'label'   => __('Hide Image', 'stagekitwp-core'),
                'options' => array(
                    ''      => __('Show', 'stagekitwp-core'),
                    'true'  => __('Hide', 'stagekitwp-core')
                ),
                'default' => ''
            ),
            'hide_name' => array(
                'type'    => 'select',
                'label'   => __('Hide Season Name', 'stagekitwp-core'),
                'options' => array(
                    ''      => __('Show', 'stagekitwp-core'),
                    'true'  => __('Hide', 'stagekitwp-core')
                ),
                'default' => ''
            ),
            'hide_dates' => array(
                'type'    => 'select',
                'label'   => __('Hide Dates', 'stagekitwp-core'),
                'options' => array(
                    ''      => __('Show', 'stagekitwp-core'),
                    'true'  => __('Hide', 'stagekitwp-core')
                ),
                'default' => ''
            ),
            'hide_tickets' => array(
                'type'    => 'select',
                'label'   => __('Hide Tickets Button', 'stagekitwp-core'),
                'options' => array(
                    ''      => __('Show', 'stagekitwp-core'),
                    'true'  => __('Hide', 'stagekitwp-core')
                ),
                'default' => ''
            ),
        )
    ),
    'styling' => array(
        'title'    => __('Styling', 'stagekitwp-core'),
        'sections' => array(
            'colors' => array(
                'title'  => __('Colors', 'stagekitwp-core'),
                'fields' => array(
                    'color_bg' => array(
                        'type'        => 'color',
                        'label'       => __('Background Color', 'stagekitwp-core'),
                        'default'     => '#ffffff'
                    ),
                    'color_text' => array(
                        'type'        => 'color',
                        'label'       => __('Text Color', 'stagekitwp-core'),
                        'default'     => '#000000'
                    ),
                    'color_heading' => array(
                        'type'        => 'color',
                        'label'       => __('Heading Color', 'stagekitwp-core'),
                        'default'     => '#333333'
                    ),
                )
            ),
            'borders' => array(
                'title'  => __('Borders', 'stagekitwp-core'),
                'fields' => array(
                    'border_color' => array(
                        'type'        => 'color',
                        'label'       => __('Border Color', 'stagekitwp-core'),
                        'default'     => '#dddddd'
                    ),
                    'border_width' => array(
                        'type'        => 'text',
                        'label'       => __('Border Width', 'stagekitwp-core'),
                        'placeholder' => '1',
                        'default'     => 1,
                    ),
                    'border_radius' => array(
                        'type'        => 'text',
                        'label'       => __('Border Radius', 'stagekitwp-core'),
                        'placeholder' => '8',
                        'default'     => 8,
                    ),
                )
            ),
            'spacing' => array(
                'title'  => __('Spacing & Effects', 'stagekitwp-core'),
                'fields' => array(
                    'padding' => array(
                        'type'        => 'text',
                        'label'       => __('Padding', 'stagekitwp-core'),
                        'placeholder' => '15px',
                        'default'     => '15px'
                    ),
                    'shadow' => array(
                        'type'        => 'text',
                        'label'       => __('Drop Shadow', 'stagekitwp-core'),
                        'placeholder' => '0 2px 8px rgba(0,0,0,0.1)',
                        'default'     => '0 2px 8px rgba(0,0,0,0.1)'
                    ),
                )
            ),
        )
    ),
));
