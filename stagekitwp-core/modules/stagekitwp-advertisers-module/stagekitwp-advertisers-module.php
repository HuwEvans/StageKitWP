<?php
/**
 * StageKitWP - Advertisers Beaver Builder Module
 *
 * @package StageKitWP
 * @subpackage Beaver Builder Modules
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'FLBuilderModule' ) ) {
    return;
}

class StageKitWP_Advertisers_BB_Module extends FLBuilderModule {
    public function __construct() {
        parent::__construct( array(
            'name'            => __( 'Advertisers', 'stagekitwp-core' ),
            'description'     => __( 'Display advertisers in a grid or slider', 'stagekitwp-core' ),
            'group'           => 'stagekitwp-core',
            'category'        => 'stagekitwp-core',
            'dir'             => plugin_dir_path( __FILE__ ),
            'url'             => plugin_dir_url( __FILE__ ),
            'enabled'         => true,
            'partial_refresh' => true,
        ) );
    }
}

FLBuilder::register_module( 'StageKitWP_Advertisers_BB_Module', array(
    'display' => array(
        'title'    => __( 'Display Options', 'stagekitwp-core' ),
        'sections' => array(
            'layout' => array(
                'title'  => __( 'Layout', 'stagekitwp-core' ),
                'fields' => array(
                    'view' => array(
                        'type'    => 'select',
                        'label'   => __( 'View', 'stagekitwp-core' ),
                        'options' => array(
                            'grid'   => __( 'Grid', 'stagekitwp-core' ),
                            'slider' => __( 'Slider', 'stagekitwp-core' ),
                        ),
                        'default' => 'grid',
                    ),
                    'columns' => array(
                        'type'    => 'text',
                        'label'   => __( 'Grid Columns', 'stagekitwp-core' ),
                        'default' => 3,
                        'help'    => __( 'Grid view only', 'stagekitwp-core' ),
                    ),
                    'category' => array(
                        'type'        => 'text',
                        'label'       => __( 'Category Filter', 'stagekitwp-core' ),
                        'placeholder' => 'restaurant',
                        'help'        => __( 'Leave empty to show all', 'stagekitwp-core' ),
                    ),
                    'image_type' => array(
                        'type'    => 'select',
                        'label'   => __( 'Image Type', 'stagekitwp-core' ),
                        'options' => array(
                            'banner' => __( 'Banner', 'stagekitwp-core' ),
                            'logo'   => __( 'Logo', 'stagekitwp-core' ),
                        ),
                        'default' => 'banner',
                    ),
                ),
            ),
        ),
    ),
) );
