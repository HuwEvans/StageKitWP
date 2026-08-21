<?php
defined( 'ABSPATH' ) || exit;

$show_id = isset( $settings->show_id ) ? intval( $settings->show_id ) : 0;

$atts = array();
if ( $show_id > 0 ) { $atts[] = 'show_id="' . $show_id . '"'; }

echo do_shortcode( '[stagekitwp_venues ' . implode( ' ', $atts ) . ']' );
