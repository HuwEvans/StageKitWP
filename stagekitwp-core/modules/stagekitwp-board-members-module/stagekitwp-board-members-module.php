<?php
/**
 * StageKitWP - Board Members Beaver Builder Module
 *
 * @package StageKitWP
 * @subpackage Beaver Builder Modules
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'FLBuilderModule' ) ) {
    return;
}

class STAGEKITWP_Board_Members_BB_Module extends FLBuilderModule {
    public function __construct() {
        parent::__construct( array(
            'name'            => __( 'Board Members', 'stagekitwp-core' ),
            'description'     => __( 'Display board members in a responsive grid', 'stagekitwp-core' ),
            'group'           => 'stagekitwp-core',
            'category'        => 'stagekitwp-core',
            'dir'             => plugin_dir_path( __FILE__ ),
            'url'             => plugin_dir_url( __FILE__ ),
            'enabled'         => true,
            'partial_refresh' => true,
        ) );
    }
}

FLBuilder::register_module( 'STAGEKITWP_Board_Members_BB_Module', array(
    'display' => array(
        'title'    => __( 'Display Options', 'stagekitwp-core' ),
        'sections' => array(
            'layout' => array(
                'title'  => __( 'Layout', 'stagekitwp-core' ),
                'fields' => array(
                    'layout' => array(
                        'type'    => 'select',
                        'label'   => __( 'Layout Style', 'stagekitwp-core' ),
                        'options' => array(
                            'grid'      => __( 'Grid — photo cards in columns', 'stagekitwp-core' ),
                            'list'      => __( 'List — horizontal photo + name rows', 'stagekitwp-core' ),
                            'table'     => __( 'Table — sortable name/position table', 'stagekitwp-core' ),
                            'spotlight' => __( 'Spotlight — tall portrait cards', 'stagekitwp-core' ),
                        ),
                        'default' => 'grid',
                    ),
                    'columns' => array(
                        'type'    => 'text',
                        'label'   => __( 'Columns (grid / spotlight)', 'stagekitwp-core' ),
                        'default' => 3,
                    ),
                    'photo_size' => array(
                        'type'    => 'text',
                        'label'   => __( 'Grid Photo Size (px)', 'stagekitwp-core' ),
                        'default' => 120,
                        'help'    => __( 'Grid layout only. Range 60-220.', 'stagekitwp-core' ),
                    ),
                    'show_photos' => array(
                        'type'    => 'select',
                        'label'   => __( 'Show Photos', 'stagekitwp-core' ),
                        'options' => array(
                            'true'  => __( 'Yes', 'stagekitwp-core' ),
                            'false' => __( 'No', 'stagekitwp-core' ),
                        ),
                        'default' => 'true',
                    ),
                    'show_bio' => array(
                        'type'    => 'select',
                        'label'   => __( 'Show Bio', 'stagekitwp-core' ),
                        'options' => array(
                            'false' => __( 'No', 'stagekitwp-core' ),
                            'true'  => __( 'Yes', 'stagekitwp-core' ),
                        ),
                        'default' => 'false',
                    ),
                ),
            ),
        ),
    ),
) );
