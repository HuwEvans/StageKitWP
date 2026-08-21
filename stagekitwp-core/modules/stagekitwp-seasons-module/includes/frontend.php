<?php
/**
 * StageKitWP - Seasons Beaver Builder Module Frontend
 * 
 * Renders the seasons shortcode with module settings
 * 
 * @package StageKitWP
 * @subpackage Beaver Builder Modules
 */

// Extract module settings
$which = isset( $settings->which ) ? $settings->which : 'all';
$layout = isset( $settings->layout ) ? $settings->layout : 'grid';
$columns_lg = isset( $settings->columns_lg ) ? intval( $settings->columns_lg ) : 3;
$columns_md = isset( $settings->columns_md ) ? intval( $settings->columns_md ) : 2;
$columns_sm = isset( $settings->columns_sm ) ? intval( $settings->columns_sm ) : 1;
$orderby = isset( $settings->orderby ) ? $settings->orderby : 'start_date';
$order = isset( $settings->order ) ? $settings->order : 'ASC';
$limit = isset( $settings->limit ) ? intval( $settings->limit ) : -1;
$hide_image = isset( $settings->hide_image ) && $settings->hide_image === 'true' ? 'true' : 'false';
$hide_name = isset( $settings->hide_name ) && $settings->hide_name === 'true' ? 'true' : 'false';
$hide_dates = isset( $settings->hide_dates ) && $settings->hide_dates === 'true' ? 'true' : 'false';
$hide_tickets = isset( $settings->hide_tickets ) && $settings->hide_tickets === 'true' ? 'true' : 'false';
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

if ( ! empty( $which ) && 'all' !== $which ) {
    $shortcode_atts[] = 'which="' . esc_attr( $which ) . '"';
}

if ( ! empty( $layout ) && 'grid' !== $layout ) {
    $shortcode_atts[] = 'layout="' . esc_attr( $layout ) . '"';
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

if ( ! empty( $orderby ) && 'start_date' !== $orderby ) {
    $shortcode_atts[] = 'orderby="' . esc_attr( $orderby ) . '"';
}

if ( ! empty( $order ) && 'ASC' !== $order ) {
    $shortcode_atts[] = 'order="' . esc_attr( $order ) . '"';
}

if ( -1 !== $limit ) {
    $shortcode_atts[] = 'limit="' . esc_attr( $limit ) . '"';
}

if ( 'true' === $hide_image ) {
    $shortcode_atts[] = 'hide_image="true"';
}

if ( 'true' === $hide_name ) {
    $shortcode_atts[] = 'hide_name="true"';
}

if ( 'true' === $hide_dates ) {
    $shortcode_atts[] = 'hide_dates="true"';
}

if ( 'true' === $hide_tickets ) {
    $shortcode_atts[] = 'hide_tickets="true"';
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
$shortcode_string = '[stagekitwp_seasons ' . implode( ' ', $shortcode_atts ) . ']';
echo do_shortcode( $shortcode_string );
