<?php
/**
 * StageKitWP - Auditions Beaver Builder Module Frontend
 * 
 * Renders the auditions shortcode with module settings
 * 
 * @package StageKitWP
 * @subpackage Beaver Builder Modules
 */

// Extract module settings
$show_id = isset( $settings->show_id ) ? $settings->show_id : '';
$season_id = isset( $settings->season_id ) ? $settings->season_id : '';
$limit = isset( $settings->limit ) ? intval( $settings->limit ) : 0;
$layout = isset( $settings->layout ) ? $settings->layout : 'list';
$orderby = isset( $settings->orderby ) ? $settings->orderby : 'date';
$order = isset( $settings->order ) ? $settings->order : 'ASC';
$hide_description = isset( $settings->hide_description ) && $settings->hide_description === 'true' ? 'true' : 'false';
$hide_venue = isset( $settings->hide_venue ) && $settings->hide_venue === 'true' ? 'true' : 'false';
$hide_contact = isset( $settings->hide_contact ) && $settings->hide_contact === 'true' ? 'true' : 'false';
$hide_image = isset( $settings->hide_image ) && $settings->hide_image === 'true' ? 'true' : 'false';
$columns_lg = isset( $settings->columns_lg ) ? intval( $settings->columns_lg ) : 3;
$columns_md = isset( $settings->columns_md ) ? intval( $settings->columns_md ) : 2;
$columns_sm = isset( $settings->columns_sm ) ? intval( $settings->columns_sm ) : 1;
$color_bg = isset( $settings->color_bg ) ? $settings->color_bg : '';
$color_text = isset( $settings->color_text ) ? $settings->color_text : '';
$color_heading = isset( $settings->color_heading ) ? $settings->color_heading : '';
$border_color = isset( $settings->border_color ) ? $settings->border_color : '';
$border_width = isset( $settings->border_width ) ? intval( $settings->border_width ) : 1;
$border_radius = isset( $settings->border_radius ) ? intval( $settings->border_radius ) : 8;
$padding = isset( $settings->padding ) ? $settings->padding : '15px';
$shadow = isset( $settings->shadow ) ? $settings->shadow : '0 2px 8px rgba(0,0,0,0.1)';

// Build shortcode attributes
$shortcode_atts = array();

if ( ! empty( $show_id ) ) {
    $shortcode_atts[] = 'show_id="' . esc_attr( $show_id ) . '"';
}

if ( ! empty( $season_id ) ) {
    $shortcode_atts[] = 'season_id="' . esc_attr( $season_id ) . '"';
}

if ( $limit > 0 ) {
    $shortcode_atts[] = 'limit="' . esc_attr( $limit ) . '"';
}

if ( ! empty( $layout ) && 'list' !== $layout ) {
    $shortcode_atts[] = 'layout="' . esc_attr( $layout ) . '"';
}

if ( ! empty( $orderby ) && 'date' !== $orderby ) {
    $shortcode_atts[] = 'orderby="' . esc_attr( $orderby ) . '"';
}

if ( ! empty( $order ) && 'ASC' !== $order ) {
    $shortcode_atts[] = 'order="' . esc_attr( $order ) . '"';
}

if ( 'true' === $hide_description ) {
    $shortcode_atts[] = 'hide_description="true"';
}

if ( 'true' === $hide_venue ) {
    $shortcode_atts[] = 'hide_venue="true"';
}

if ( 'true' === $hide_contact ) {
    $shortcode_atts[] = 'hide_contact="true"';
}

if ( 'true' === $hide_image ) {
    $shortcode_atts[] = 'hide_image="true"';
}

if ( 3 !== $columns_lg ) {
    $shortcode_atts[] = 'columns_lg="' . esc_attr( $columns_lg ) . '"';
}

if ( 2 !== $columns_md ) {
    $shortcode_atts[] = 'columns_md="' . esc_attr( $columns_md ) . '"';
}

if ( 1 !== $columns_sm ) {
    $shortcode_atts[] = 'columns_sm="' . esc_attr( $columns_sm ) . '"';
}

if ( ! empty( $color_bg ) ) {
    $shortcode_atts[] = 'color_bg="' . esc_attr( $color_bg ) . '"';
}

if ( ! empty( $color_text ) ) {
    $shortcode_atts[] = 'color_text="' . esc_attr( $color_text ) . '"';
}

if ( ! empty( $color_heading ) ) {
    $shortcode_atts[] = 'color_heading="' . esc_attr( $color_heading ) . '"';
}

if ( ! empty( $border_color ) ) {
    $shortcode_atts[] = 'border_color="' . esc_attr( $border_color ) . '"';
}

if ( 1 !== $border_width ) {
    $shortcode_atts[] = 'border_width="' . esc_attr( $border_width ) . '"';
}

if ( 8 !== $border_radius ) {
    $shortcode_atts[] = 'border_radius="' . esc_attr( $border_radius ) . '"';
}

if ( ! empty( $padding ) ) {
    $shortcode_atts[] = 'padding="' . esc_attr( $padding ) . '"';
}

if ( ! empty( $shadow ) ) {
    $shortcode_atts[] = 'shadow="' . esc_attr( $shadow ) . '"';
}

// Build and render the shortcode
$shortcode_string = '[stagekitwp_auditions ' . implode( ' ', $shortcode_atts ) . ']';
echo do_shortcode( $shortcode_string );
