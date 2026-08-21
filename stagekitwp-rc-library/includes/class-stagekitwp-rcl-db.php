<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class STAGEKITWP_RC_LIBRARY_DB {

    /**
     * Initializes custom database tables during plugin activation.
     */
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // 1. Evaluations and Rubric Submissions Scoring Table
        $table_scores = $wpdb->prefix . 'stagekitwp_rc_library_scores';
        $sql_scores = "CREATE TABLE $table_scores (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            book_id bigint(20) NOT NULL,
            group_id bigint(20) NOT NULL,
            rubric_item_id bigint(20) NOT NULL,
            score float NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY book_id (book_id),
            KEY group_id (group_id),
            KEY rubric_item_id (rubric_item_id)
        ) $charset_collate;";

        // 2. Core Workspace Groups Table
        $table_groups = $wpdb->prefix . 'stagekitwp_rc_library_groups';
        $sql_groups = "CREATE TABLE $table_groups (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            template_id bigint(20) NOT NULL,
            lead_user_id bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        // 3. Many-to-Many Relationships Bridge Table (Supports multiple books & multiple users per group)
        $table_rel = $wpdb->prefix . 'stagekitwp_rc_library_group_relationships';
        $sql_rel = "CREATE TABLE $table_rel (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            group_id bigint(20) NOT NULL,
            object_id bigint(20) NOT NULL,
            object_type varchar(50) NOT NULL,
            PRIMARY KEY  (id),
            KEY group_id (group_id),
            KEY object_lookup (object_id, object_type)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql_scores );
        dbDelta( $sql_groups );
        dbDelta( $sql_rel );
    }

    /**
     * Safely inserts or updates a validated evaluation score into the custom database.
     * Rejects inputs that violate metric maximum limits or scoring configurations.
     */
    public static function insert_validated_score( $user_id, $book_id, $group_id, $rubric_item_id, $raw_score ) {
        global $wpdb;
        $table_scores = $wpdb->prefix . 'stagekitwp_rc_library_scores';

        // 1. Fetch metric constraints directly from post metadata
        $score_type = get_post_meta( $rubric_item_id, '_stagekitwp_rc_library_score_type', true );
        $max_value  = intval( get_post_meta( $rubric_item_id, '_stagekitwp_rc_library_max_value', true ) );

        $score_type = ! empty( $score_type ) ? $score_type : 'number';
        $max_value  = ! empty( $max_value ) ? $max_value : 5;
        $clean_score = floatval( $raw_score );

        // 2. Strict Server-Side Validation Boundaries
        if ( $clean_score < 0 ) {
            return new WP_Error( 'score_underflow', __( 'Evaluation scores cannot be negative values.', 'stagekitwp-rc-library' ) );
        }

        if ( $score_type === 'boolean' && $clean_score > 1 ) {
            return new WP_Error( 'boolean_overflow', __( 'Pass/Fail criteria metrics can only record 0 or 1.', 'stagekitwp-rc-library' ) );
        }

        if ( $score_type !== 'boolean' && $clean_score > $max_value ) {
            return new WP_Error( 'score_overflow', sprintf( __( 'Submitted evaluation score exceeds maximum parameter ceiling of %d.', 'stagekitwp-rc-library' ), $max_value ) );
        }

        // 3. Check for existing evaluation records by the same user for the same metric inside this group
        $existing_id = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM $table_scores WHERE user_id = %d AND book_id = %d AND group_id = %d AND rubric_item_id = %d",
            $user_id, $book_id, $group_id, $rubric_item_id
        ) );

        if ( $existing_id ) {
            // Update existing score record
            $result = $wpdb->update(
                $table_scores,
                array( 'score' => $clean_score, 'created_at' => current_time( 'mysql' ) ),
                array( 'id' => $existing_id ),
                array( '%f', '%s' ),
                array( '%d' )
            );
            return ( $result !== false ) ? $existing_id : new WP_Error( 'db_update_error', __( 'Failed to modify score records.', 'stagekitwp-rc-library' ) );
        } else {
            // Insert brand-new score record
            $result = $wpdb->insert(
                $table_scores,
                array(
                    'user_id'        => intval( $user_id ),
                    'book_id'        => intval( $book_id ),
                    'group_id'       => intval( $group_id ),
                    'rubric_item_id' => intval( $rubric_item_id ),
                    'score'          => $clean_score,
                ),
                array( '%d', '%d', '%d', '%d', '%f' )
            );
            return $result ? $wpdb->insert_id : new WP_Error( 'db_insert_error', __( 'Failed to register score records.', 'stagekitwp-rc-library' ) );
        }
    }
}