<?php
defined( 'ABSPATH' ) || exit;

$show_name = isset( $settings->show_name ) ? $settings->show_name : 'true';
$show_logo = isset( $settings->show_logo ) ? $settings->show_logo : 'true';

$atts = array();
if ( 'true' !== $show_name ) { $atts[] = 'show_name="false"'; }
if ( 'true' !== $show_logo ) { $atts[] = 'show_logo="false"'; }

echo do_shortcode( '[stagekitwp_contributors ' . implode( ' ', $atts ) . ']' );
