<?php
defined( 'ABSPATH' ) || exit;

$show_name        = isset( $settings->show_name )        ? $settings->show_name        : 'true';
$show_company     = isset( $settings->show_company )     ? $settings->show_company     : 'true';
$show_description = isset( $settings->show_description ) ? $settings->show_description : 'true';
$show_logo        = isset( $settings->show_logo )        ? $settings->show_logo        : 'true';
$show_headers     = isset( $settings->show_headers )     ? $settings->show_headers     : 'true';

$atts = array();
if ( 'true' !== $show_name )        { $atts[] = 'show_name="false"'; }
if ( 'true' !== $show_company )     { $atts[] = 'show_company="false"'; }
if ( 'true' !== $show_description ) { $atts[] = 'show_description="false"'; }
if ( 'true' !== $show_logo )        { $atts[] = 'show_logo="false"'; }
if ( 'true' !== $show_headers )     { $atts[] = 'show_headers="false"'; }

echo do_shortcode( '[stagekitwp_sponsors ' . implode( ' ', $atts ) . ']' );
