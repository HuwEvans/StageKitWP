<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class STAGEKITWP_RC_LIBRARY_Evaluation_Form {

    public function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_form_assets' ) );
    }

    /**
     * Enqueue layout styling and script handlers for the front-end view canvas
     */
    public function enqueue_form_assets() {
        wp_enqueue_style( 'stagekitwp-rcl-form-css', STAGEKITWP_RC_LIBRARY_PLUGIN_URL . 'assets/css/evaluation-form.css', array(), STAGEKITWP_RC_LIBRARY_VERSION );
        wp_enqueue_script( 'stagekitwp-rcl-form-js', STAGEKITWP_RC_LIBRARY_PLUGIN_URL . 'assets/js/evaluation-form.js', array('jquery'), STAGEKITWP_RC_LIBRARY_VERSION, true );
    }

    /**
     * Generates evaluation forms by reading Group Row -> Template ID -> stagekitwp_rubric CPT Posts
     */
    public function generate_form_html( $atts ) {
        global $wpdb;

        $group_id = isset( $atts['group_id'] ) ? intval( $atts['group_id'] ) : 0;
        $book_id  = isset( $atts['book_id'] ) ? intval( $atts['book_id'] ) : 0;

        if ( ! $group_id || ! $book_id ) {
            return '<p class="stagekitwp-rcl-error">' . esc_html__( 'Please select a valid Circle Group and Script Profile.', 'stagekitwp-rc-library' ) . '</p>';
        }

        // STEP 1: Fetch the template_id from your custom groups table
        $table_groups = $wpdb->prefix . 'stagekitwp_rc_library_groups';
        $template_id  = $wpdb->get_var( $wpdb->prepare(
            "SELECT template_id FROM $table_groups WHERE id = %d",
            $group_id
        ) );

        if ( ! $template_id ) {
            return '<p class="stagekitwp-rcl-warning">' . 
                   sprintf( esc_html__( 'Selected Group (ID: %d) does not have a rubric template assigned.', 'stagekitwp-rc-library' ), $group_id ) . 
                   '</p>';
        }

        // Get template title for cleaner heading displays
        $template_title = get_the_title( $template_id );
        if ( empty( $template_title ) ) {
            $template_title = sprintf( __( 'Template #%d', 'stagekitwp-rc-library' ), $template_id );
        }

        // STEP 2: Query Rubric items utilizing the verified explicit meta key
        $criteria_args = array(
            'post_type'      => 'stagekitwp_rubric',
            'posts_per_page' => -1,
            'post_status'    => array( 'publish', 'inherit' ),
            'meta_key'       => '_stagekitwp_rc_library_parent_template_id', // VERIFIED META KEY RELATIONSHIP
            'meta_value'     => $template_id,
            'orderby'        => 'title',
            'order'          => 'ASC'
        );

        $criteria_posts = get_posts( $criteria_args );

        // Validation check in case the query fails to return posts
        if ( empty( $criteria_posts ) ) {
            return '<p class="stagekitwp-rcl-warning">' . 
                   sprintf( 
                       esc_html__( 'No rubric criteria items found linked to "%1$s" (Template ID: %2$d). Please check that this template contains items.', 'stagekitwp-rc-library' ), 
                       esc_html( $template_title ), 
                       $template_id 
                   ) . 
                   '</p>';
        }

        // --- STEP 3: Compile the Form Markup Window ---
        ob_start();
        ?>
        <div class="stagekitwp-rcl-evaluation-container" id="stagekitwp-rcl-eval-form-wrap">
            <form method="POST" action="" class="stagekitwp-rcl-evaluation-form">
                <?php wp_nonce_field( 'stagekitwp_rc_library_save_evaluation', 'stagekitwp_rc_library_nonce' ); ?>
                
                <input type="hidden" name="group_id" value="<?php echo $group_id; ?>" />
                <input type="hidden" name="book_id" value="<?php echo $book_id; ?>" />
                <input type="hidden" name="template_id" value="<?php echo $template_id; ?>" />

                <div class="stagekitwp-rcl-form-header" style="margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #e2e8f0;">
                    <h3 style="margin: 0 0 5px 0; color: #1e293b; font-size: 1.6em;"><?php echo get_the_title( $book_id ); ?></h3>
                    <p class="sub-meta" style="color: #64748b; margin: 0; font-size: 14px;">
                        <?php printf( __( 'Evaluation Template Form Framework: %s', 'stagekitwp-rc-library' ), esc_html( $template_title ) ); ?>
                    </p>
                </div>

                <div class="stagekitwp-rcl-form-body-wrapper">
                    <?php foreach ( $criteria_posts as $criterion ) : 
                        // Fetch the specialized field type variables from your database profiles
                        $score_type = get_post_meta( $criterion->ID, '_stagekitwp_rc_library_score_type', true ); 
                        $max_points = get_post_meta( $criterion->ID, '_stagekitwp_rc_library_max_value', true );
                        
                        // Set fallback defaults if keys are empty
                        $score_type = ! empty( $score_type ) ? $score_type : 'number';
                        $max_points = ! empty( $max_points ) ? intval( $max_points ) : 5;
                        ?>
                        <div class="stagekitwp-rcl-criterion-row" style="margin-bottom: 20px; padding: 15px; background: #fafafa; border-left: 4px solid #2271b1; border-radius: 0 4px 4px 0;">
                            <label style="display: block; font-weight: bold; margin-bottom: 10px; color: #0f172a; font-size: 1.1em;">
                                <?php echo esc_html( $criterion->post_title ); ?>
                            </label>

                            <div class="stagekitwp-rcl-field-input-node">
                                <?php if ( $score_type === 'boolean' ) : ?>
                                    <label style="display: inline-flex; align-items: center; cursor: pointer; margin-right: 15px;">
                                        <input type="radio" name="criteria_<?php echo $criterion->ID; ?>" value="1" required style="margin-right: 6px;" /> 
                                        <span style="color: #16a34a; font-weight: 600;">🟢 Pass (1 pt)</span>
                                    </label>
                                    <label style="display: inline-flex; align-items: center; cursor: pointer;">
                                        <input type="radio" name="criteria_<?php echo $criterion->ID; ?>" value="0" required style="margin-right: 6px;" /> 
                                        <span style="color: #dc2626; font-weight: 600;">🔴 Fail (0 pts)</span>
                                    </label>

                                <?php elseif ( $score_type === 'slider' ) : ?>
                                    <div style="display: flex; align-items: center; gap: 15px;">
                                        <input type="range" name="criteria_<?php echo $criterion->ID; ?>" min="1" max="<?php echo $max_points; ?>" value="1" step="0.5" style="flex-grow: 1; max-width: 300px;" oninput="this.nextElementSibling.value = this.value" />
                                        <output style="font-weight: bold; background: #e2e8f0; padding: 2px 8px; border-radius: 4px; font-size: 13px;">1</output> <span style="color: #64748b; font-size: 13px;">/ <?php echo $max_points; ?> Max Points</span>
                                    </div>

                                <?php else : ?>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <input type="number" name="criteria_<?php echo $criterion->ID; ?>" min="1" max="<?php echo $max_points; ?>" placeholder="Enter score..." style="width:100%; max-width: 150px; border: 1px solid #cbd5e1; border-radius: 4px; padding: 8px;" required />
                                        <span style="color: #64748b; font-size: 13px;">Range Scale: 1 to <?php echo $max_points; ?> points</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="stagekitwp-rcl-form-footer" style="margin-top: 25px; padding-top: 15px; border-top: 1px solid #e2e8f0;">
                    <button type="submit" name="stagekitwp_rc_library_submit_evaluation" class="button button-primary" style="background: #2271b1; color: #fff; padding: 12px 24px; border: none; border-radius: 4px; font-weight: 600; font-size: 14px; cursor: pointer; transition: background 0.2s;">
                        <?php _e( 'Submit Script Evaluation Worksheet', 'stagekitwp-rc-library' ); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
}