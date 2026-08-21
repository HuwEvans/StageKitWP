<?php
defined( 'ABSPATH' ) || exit;

$layout       = isset( $settings->layout )       ? sanitize_key( $settings->layout )              : 'banner';
$show_limit   = isset( $settings->show_limit )   ? intval( $settings->show_limit )                 : 4;
$show_image   = isset( $settings->show_image )   ? $settings->show_image                           : 'true';
$show_dates   = isset( $settings->show_dates )   ? $settings->show_dates                           : 'true';
$show_genre   = isset( $settings->show_genre )   ? $settings->show_genre                           : 'false';
$label_season = isset( $settings->label_season ) ? sanitize_text_field( $settings->label_season )  : '';
$label_show   = isset( $settings->label_show )   ? sanitize_text_field( $settings->label_show )    : '';
$button_text  = isset( $settings->button_text )  ? sanitize_text_field( $settings->button_text )   : '';

$atts = array();
$atts[] = 'layout="' . esc_attr( $layout ) . '"';
if ( 4 !== $show_limit )      { $atts[] = 'show_limit="' . $show_limit . '"'; }
if ( 'true' !== $show_image ) { $atts[] = 'show_image="false"'; }
if ( 'true' !== $show_dates ) { $atts[] = 'show_dates="false"'; }
if ( 'true' === $show_genre ) { $atts[] = 'show_genre="true"'; }
if ( ! empty( $label_season ) ) { $atts[] = 'label_season="' . esc_attr( $label_season ) . '"'; }
if ( ! empty( $label_show ) )   { $atts[] = 'label_show="'   . esc_attr( $label_show )   . '"'; }
if ( ! empty( $button_text ) )  { $atts[] = 'button_text="'  . esc_attr( $button_text )  . '"'; }

echo do_shortcode( '[stagekitwp_tickets ' . implode( ' ', $atts ) . ']' );
