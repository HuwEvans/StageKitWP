<?php
/**
 * StageKitWP - Sponsors Beaver Builder Module
 *
 * @package StageKitWP
 * @subpackage Beaver Builder Modules
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'FLBuilderModule' ) ) {
    return;
}

class STAGEKITWP_Sponsors_BB_Module extends FLBuilderModule {
    public function __construct() {
        parent::__construct( array(
            'name'            => __( 'Sponsors', 'stagekitwp-core' ),
            'description'     => __( 'Display sponsors grouped by level (grid) or as an auto-scrolling logo carousel (slider)', 'stagekitwp-core' ),
            'group'           => 'stagekitwp-core',
            'category'        => 'stagekitwp-core',
            'dir'             => plugin_dir_path( __FILE__ ),
            'url'             => plugin_dir_url( __FILE__ ),
            'enabled'         => true,
            'partial_refresh' => true,
        ) );
    }
}

FLBuilder::register_module( 'STAGEKITWP_Sponsors_BB_Module', array(
    'display' => array(
        'title'    => __( 'Display Options', 'stagekitwp-core' ),
        'sections' => array(
            'layout_section' => array(
                'title'  => __( 'Layout', 'stagekitwp-core' ),
                'fields' => array(
                    'layout' => array(
                        'type'    => 'select',
                        'label'   => __( 'Layout', 'stagekitwp-core' ),
                        'options' => array(
                            'grid'   => __( 'Grid — grouped by level', 'stagekitwp-core' ),
                            'slider' => __( 'Slider — auto-scrolling carousel', 'stagekitwp-core' ),
                        ),
                        'default' => 'grid',
                        'toggle'  => array(
                            'grid'   => array( 'sections' => array( 'grid_options' ) ),
                            'slider' => array( 'sections' => array( 'slider_options' ) ),
                        ),
                    ),
                ),
            ),
            'grid_options' => array(
                'title'  => __( 'Grid Options', 'stagekitwp-core' ),
                'fields' => array(
                    'show_headers' => array(
                        'type'    => 'select',
                        'label'   => __( 'Show Tier Headers', 'stagekitwp-core' ),
                        'options' => array( 'true' => __( 'Yes', 'stagekitwp-core' ), 'false' => __( 'No', 'stagekitwp-core' ) ),
                        'default' => 'true',
                    ),
                    'show_name' => array(
                        'type'    => 'select',
                        'label'   => __( 'Show Name', 'stagekitwp-core' ),
                        'options' => array( 'true' => __( 'Yes', 'stagekitwp-core' ), 'false' => __( 'No', 'stagekitwp-core' ) ),
                        'default' => 'true',
                    ),
                    'show_company' => array(
                        'type'    => 'select',
                        'label'   => __( 'Show Company', 'stagekitwp-core' ),
                        'options' => array( 'true' => __( 'Yes', 'stagekitwp-core' ), 'false' => __( 'No', 'stagekitwp-core' ) ),
                        'default' => 'true',
                    ),
                    'show_logo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Show Logo', 'stagekitwp-core' ),
                        'options' => array( 'true' => __( 'Yes', 'stagekitwp-core' ), 'false' => __( 'No', 'stagekitwp-core' ) ),
                        'default' => 'true',
                    ),
                    'show_website' => array(
                        'type'    => 'select',
                        'label'   => __( 'Show Website Link', 'stagekitwp-core' ),
                        'options' => array( 'true' => __( 'Yes', 'stagekitwp-core' ), 'false' => __( 'No', 'stagekitwp-core' ) ),
                        'default' => 'true',
                    ),
                ),
            ),
            'slider_options' => array(
                'title'  => __( 'Slider Options', 'stagekitwp-core' ),
                'fields' => array(
                    'slides_visible' => array(
                        'type'    => 'text',
                        'label'   => __( 'Logos Visible', 'stagekitwp-core' ),
                        'default' => '4',
                    ),
                    'autoplay' => array(
                        'type'    => 'select',
                        'label'   => __( 'Autoplay', 'stagekitwp-core' ),
                        'options' => array( 'true' => __( 'Yes', 'stagekitwp-core' ), 'false' => __( 'No', 'stagekitwp-core' ) ),
                        'default' => 'true',
                    ),
                    'speed' => array(
                        'type'    => 'text',
                        'label'   => __( 'Slide Interval (ms)', 'stagekitwp-core' ),
                        'default' => '3000',
                    ),
                ),
            ),
        ),
    ),
) );
