<?php
/**
 * StageKitWP - Contributors Beaver Builder Module
 *
 * @package StageKitWP
 * @subpackage Beaver Builder Modules
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'FLBuilderModule' ) ) {
    return;
}

class STAGEKITWP_Contributors_BB_Module extends FLBuilderModule {
    public function __construct() {
        parent::__construct( array(
            'name'            => __( 'Contributors', 'stagekitwp-core' ),
            'description'     => __( 'Display contributors grouped by level', 'stagekitwp-core' ),
            'group'           => 'stagekitwp-core',
            'category'        => 'stagekitwp-core',
            'dir'             => plugin_dir_path( __FILE__ ),
            'url'             => plugin_dir_url( __FILE__ ),
            'enabled'         => true,
            'partial_refresh' => true,
        ) );
    }
}

FLBuilder::register_module( 'STAGEKITWP_Contributors_BB_Module', array(
    'display' => array(
        'title'    => __( 'Display Options', 'stagekitwp-core' ),
        'sections' => array(
            'visibility' => array(
                'title'  => __( 'Visibility', 'stagekitwp-core' ),
                'fields' => array(
                    'show_name' => array(
                        'type'    => 'select',
                        'label'   => __( 'Show Name', 'stagekitwp-core' ),
                        'options' => array( 'true' => __( 'Yes', 'stagekitwp-core' ), 'false' => __( 'No', 'stagekitwp-core' ) ),
                        'default' => 'true',
                    ),
                    'show_logo' => array(
                        'type'    => 'select',
                        'label'   => __( 'Show Logo', 'stagekitwp-core' ),
                        'options' => array( 'true' => __( 'Yes', 'stagekitwp-core' ), 'false' => __( 'No', 'stagekitwp-core' ) ),
                        'default' => 'true',
                    ),
                ),
            ),
        ),
    ),
) );
