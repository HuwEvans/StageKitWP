<?php
defined( 'ABSPATH' ) || exit;

$layout        = isset( $settings->layout )        ? $settings->layout        : 'table';
$show_season   = isset( $settings->show_season )   ? $settings->show_season   : 'true';
$show_category = isset( $settings->show_category ) ? $settings->show_category : 'true';
$winners_only  = isset( $settings->winners_only )  ? $settings->winners_only  : 'false';
$season_id     = isset( $settings->season_id )     ? intval( $settings->season_id ) : 0;
$category      = isset( $settings->category )      ? sanitize_text_field( $settings->category ) : '';

$atts = array();
if ( 'table' !== $layout )        { $atts[] = 'layout="'        . esc_attr( $layout )        . '"'; }
if ( 'true'  !== $show_season )   { $atts[] = 'show_season="'   . esc_attr( $show_season )   . '"'; }
if ( 'true'  !== $show_category ) { $atts[] = 'show_category="' . esc_attr( $show_category ) . '"'; }
if ( 'false' !== $winners_only )  { $atts[] = 'winners_only="'  . esc_attr( $winners_only )  . '"'; }
if ( $season_id > 0 )             { $atts[] = 'season_id="'     . $season_id               . '"'; }
if ( ! empty( $category ) )       { $atts[] = 'category="'      . esc_attr( $category )      . '"'; }

echo do_shortcode( '[stagekitwp_awards ' . implode( ' ', $atts ) . ']' );
