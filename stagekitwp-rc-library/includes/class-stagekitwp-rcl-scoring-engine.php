<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class STAGEKITWP_RC_LIBRARY_Scoring_Engine {

    /**
     * Get all rubric items assigned to a specific Template/Group.
     * Assumes a meta relationship or template configuration.
     */
    public static function get_rubric_items_by_template( $template_id ) {
        // Returns an array of rubric item post objects assigned to this template
        return get_posts( array(
            'post_type'      => 'stagekitwp_rubric',
            'posts_per_page' => -1,
            'meta_query'     => array(
                array(
                    'key'     => '_stagekitwp_rc_library_parent_template_id',
                    'value'   => $template_id,
                    'compare' => '='
                )
            )
        ) );
    }

    /**
     * Calculates the composite weighted score for a given book within a group.
     * Formula: Sum(Score * Weight) / Sum(Weights)
     */
    public static function calculate_composite_score( $book_id, $group_id, $template_id ) {
        global $wpdb;
        $table_scores = $wpdb->prefix . 'stagekitwp_rc_library_scores';

        $rubric_items = self::get_rubric_items_by_template( $template_id );
        if ( empty( $rubric_items ) ) {
            return 0.00;
        }

        $total_weighted_score = 0;
        $total_weight         = 0;

        foreach ( $rubric_items as $item ) {
            // Retrieve weight assigned to this specific metric (defaulting to 1 if not set)
            $weight = get_post_meta( $item->ID, '_stagekitwp_rc_library_item_weight', true );
            $weight = ! empty( $weight ) ? floatval( $weight ) : 1.0;

            // Fetch average raw score given by all users for this specific book + metric combo
            $avg_raw_score = $wpdb->get_var( $wpdb->prepare(
                "SELECT AVG(score) FROM $table_scores 
                 WHERE book_id = %d AND group_id = %d AND rubric_item_id = %d",
                $book_id,
                $group_id,
                $item->ID
            ) );

            if ( $avg_raw_score !== null ) {
                $total_weighted_score += ( floatval( $avg_raw_score ) * $weight );
                $total_weight         += $weight;
            }
        }

        // Prevent division by zero if no evaluations have been submitted yet
        if ( $total_weight === 0 ) {
            return 0.00;
        }

        return round( ( $total_weighted_score / $total_weight ), 2 );
    }
	/**
     * Nicely structures scores into a standard float layout or returns a graceful placeholder.
     * Example: 4.5 -> "4.50 / 5.00"
     */
    public static function format_display_score( $current_score, $max_potential = null ) {
        if ( $current_score === null || $current_score === false || floatval( $current_score ) === 0.00 ) {
            return '<span style="color: #a7aaad; font-style: italic;">' . __( 'No entries yet', 'stagekitwp-rc-library' ) . '</span>';
        }

        $formatted = number_format( floatval( $current_score ), 2 );

        if ( $max_potential !== null ) {
            $formatted .= ' <span style="color: #646970; font-size: 0.9em;">/ ' . number_format( floatval( $max_potential ), 2 ) . '</span>';
        }

        return '<strong>' . $formatted . '</strong>';
    }
}