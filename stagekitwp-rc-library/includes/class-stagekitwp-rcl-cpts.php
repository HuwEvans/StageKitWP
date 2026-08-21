<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class STAGEKITWP_RC_LIBRARY_CPTs {

    public function __construct() {
        // Custom Columns for Rubric Items Index List
        add_filter( 'manage_stagekitwp_rubric_posts_columns', array( $this, 'set_custom_rubric_item_columns' ) );
        add_action( 'manage_stagekitwp_rubric_posts_custom_column', array( $this, 'render_custom_rubric_item_columns' ), 10, 2 );

        // Custom Columns for Rubric Templates Index List
        add_filter( 'manage_stagekitwp_template_posts_columns', array( $this, 'set_custom_template_columns' ) );
        add_action( 'manage_stagekitwp_template_posts_custom_column', array( $this, 'render_custom_template_columns' ), 10, 2 );

        // Custom Columns for Books & Scripts Index List
        add_filter( 'manage_stagekitwp_book_posts_columns', array( $this, 'set_custom_book_columns' ) );
        add_action( 'manage_stagekitwp_book_posts_custom_column', array( $this, 'render_custom_book_columns' ), 10, 2 );

        // AJAX Inline Metric Processing Endpoints
        add_action( 'wp_ajax_stagekitwp_rc_library_quick_add_metric', array( $this, 'ajax_quick_add_metric' ) );
        add_action( 'wp_ajax_stagekitwp_rc_library_remove_metric', array( $this, 'ajax_remove_metric' ) );
    }

    /**
     * Registers all custom post types with the default block editor suppressed.
     */
    public function register_custom_post_types() {
        // 1. Rubric Items CPT
        register_post_type( 'stagekitwp_rubric', array(
            'labels'      => array(
                'name'          => __( 'Rubric Items', 'stagekitwp-rc-library' ),
                'singular_name' => __( 'Rubric Item', 'stagekitwp-rc-library' ),
            ),
            'public'      => false,
            'show_ui'     => true,
            'show_in_menu'=> false, 
            'supports'    => array( '' ), 
        ));

        // 2. Rubric Templates CPT
        register_post_type( 'stagekitwp_template', array(
            'labels'      => array(
                'name'          => __( 'Rubric Templates', 'stagekitwp-rc-library' ),
                'singular_name' => __( 'Rubric Template', 'stagekitwp-rc-library' ),
            ),
            'public'      => false,
            'show_ui'     => true,
            'show_in_menu'=> false,
            'supports'    => array( '' ), 
        ));

        // 3. Books & Scripts Profile CPT
        register_post_type( 'stagekitwp_book', array(
            'labels'      => array(
                'name'               => __( 'Books & Scripts', 'stagekitwp-rc-library' ),
                'singular_name'      => __( 'Book/Script', 'stagekitwp-rc-library' ),
                'add_new_item'       => __( 'Add New Script Profile', 'stagekitwp-rc-library' ),
                'edit_item'          => __( 'Edit Script Profile', 'stagekitwp-rc-library' ),
                'all_items'          => __( 'All Books & Scripts', 'stagekitwp-rc-library' ),
            ),
            'public'      => true,
            'show_ui'     => true,
            'show_in_menu'=> false,
            'supports'    => array( '' ),
            'has_archive' => true,
        ));
    }

    /**
     * Contextually maps and builds custom admin metadata forms.
     */
    public function add_custom_meta_boxes() {
        // Enforce the custom header-styled large Title box across all 3 screens
        $screens = array( 'stagekitwp_rubric', 'stagekitwp_template', 'stagekitwp_book' );
        foreach ( $screens as $screen ) {
            add_meta_box(
                'stagekitwp_rc_library_title_meta_box',
                __( 'Item Details', 'stagekitwp-rc-library' ),
                array( $this, 'render_title_meta_box' ),
                $screen,
                'normal',
                'high'
            );
        }

        // Dedicated profile parameter metrics editor for individual Rubric Items
        add_meta_box(
            'stagekitwp_rc_library_item_scoring_box',
            __( 'Scoring Configuration', 'stagekitwp-rc-library' ),
            array( $this, 'render_item_scoring_meta_box' ),
            'stagekitwp_rubric',
            'normal',
            'default'
        );

        // Inline Management Table displayed inside the Rubric Template screen
        add_meta_box(
            'stagekitwp_rc_library_template_items_box',
            __( 'Assigned Rubric Metrics (Criteria)', 'stagekitwp-rc-library' ),
            array( $this, 'render_inline_child_items_table' ),
            'stagekitwp_template',
            'normal',
            'default'
        );

        // Dedicated Book Specifications box
        add_meta_box(
            'stagekitwp_rc_library_book_profile_box',
            __( 'Script Specifications & Media Assets', 'stagekitwp-rc-library' ),
            array( $this, 'render_book_profile_meta_box' ),
            'stagekitwp_book',
            'normal',
            'default'
        );
    }

    /**
     * Renders Title Input styling it like a standard native WordPress Page Header.
     */
    public function render_title_meta_box( $post ) {
        wp_nonce_field( 'stagekitwp_rc_library_save_title', 'stagekitwp_rc_library_title_nonce' );
        $title = get_the_title( $post->ID );
        
        if ( $post->post_type === 'stagekitwp_template' ) {
            $placeholder = __( 'Enter template name here (e.g., Spring Play Selection)...', 'stagekitwp-rc-library' );
        } elseif ( $post->post_type === 'stagekitwp_book' ) {
            $placeholder = __( 'Enter book or script title here...', 'stagekitwp-rc-library' );
        } else {
            $placeholder = __( 'Enter rubric metric name here (e.g., Dialogue Quality)...', 'stagekitwp-rc-library' );
        }
        ?>
        <div class="stagekitwp-rcl-title-field-wrapper" style="margin: 10px 0;">
            <input type="text" 
                   id="stagekitwp_rc_library_post_title" 
                   name="stagekitwp_rc_library_post_title" 
                   value="<?php echo esc_attr( $title ); ?>" 
                   placeholder="<?php echo esc_attr( $placeholder ); ?>"
                   style="width: 100%; padding: 12px 15px; font-size: 1.7em; line-height: 1.5; height: auto; border: 1px solid #8c8f94; border-radius: 4px; box-shadow: inset 0 1px 2px rgba(0,0,0,0.07);" 
                   required />
            <p class="description" style="margin-top: 8px; font-size: 11px; color: #dc3232;" id="stagekitwp-title-validation-msg"></p>
        </div>

        <script type="text/javascript">
            jQuery(document).ready(function($) {
                $('#post').on('submit', function(e) {
                    var titleInput = $('#stagekitwp_rc_library_post_title').val().trim();
                    if (!titleInput) {
                        e.preventDefault();
                        $('#stagekitwp_rc_library_post_title').css('border-color', '#dc3232').focus();
                        $('#stagekitwp-title-validation-msg').text('⚠️ This field is strictly required before saving.');
                        $('#publish').removeClass('button-primary-disabled');
                        $('.spinner').removeClass('is-active');
                    }
                });
            });
        </script>
        <?php
    }

    /**
     * Renders full editing controls directly on a single Rubric Item edit screen.
     */
    public function render_item_scoring_meta_box( $post ) {
        wp_nonce_field( 'stagekitwp_rc_library_save_item_specs', 'stagekitwp_rc_library_item_specs_nonce' );

        $score_type = get_post_meta( $post->ID, '_stagekitwp_rc_library_score_type', true );
        $score_type = ! empty( $score_type ) ? $score_type : 'number';

        $max_value  = get_post_meta( $post->ID, '_stagekitwp_rc_library_max_value', true );
        $max_value  = ! empty( $max_value ) ? $max_value : '5';

        $weight     = get_post_meta( $post->ID, '_stagekitwp_rc_library_item_weight', true );
        $weight     = ! empty( $weight ) ? $weight : '1.0';
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="stagekitwp_rc_library_edit_score_type"><strong>Scoring Method</strong></label></th>
                <td>
                    <select id="stagekitwp_rc_library_edit_score_type" name="stagekitwp_rc_library_edit_score_type" class="regular-text">
                        <option value="number" <?php selected( $score_type, 'number' ); ?>>1-X Discrete Scale</option>
                        <option value="slider" <?php selected( $score_type, 'slider' ); ?>>Continuous Slider</option>
                        <option value="boolean" <?php selected( $score_type, 'boolean' ); ?>>Pass / Fail (0 or 1)</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="stagekitwp_rc_library_edit_max_value"><strong>Maximum Point Value</strong></label></th>
                <td>
                    <input type="number" id="stagekitwp_rc_library_edit_max_value" name="stagekitwp_rc_library_edit_max_value" value="<?php echo esc_attr( $max_value ); ?>" min="1" max="100" class="small-text" <?php disabled( $score_type, 'boolean' ); ?> />
                    <p class="description">The maximum score a reviewer can award for this metric.</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="stagekitwp_rc_library_edit_weight"><strong>Calculation Multiplier Weight</strong></label></th>
                <td>
                    <input type="number" id="stagekitwp_rc_library_edit_weight" name="stagekitwp_rc_library_edit_weight" value="<?php echo esc_attr( $weight ); ?>" step="0.1" min="0.1" max="10" class="small-text" /><strong> x</strong>
                    <p class="description">Adjusts how heavily this category impacts the overall composite average calculation.</p>
                </td>
            </tr>
        </table>

        <script type="text/javascript">
            jQuery(document).ready(function($) {
                $('#stagekitwp_rc_library_edit_score_type').on('change', function() {
                    if($(this).val() === 'boolean') {
                        $('#stagekitwp_rc_library_edit_max_value').val(1).prop('disabled', true);
                    } else {
                        $('#stagekitwp_rc_library_edit_max_value').prop('disabled', false);
                    }
                });
            });
        </script>
        <?php
    }

    /**
     * Renders inline relational table grids inside the Rubric Template screen.
     */
    public function render_inline_child_items_table( $post ) {
        global $wpdb;
        $table_scores = $wpdb->prefix . 'stagekitwp_rc_library_scores';

        $child_items = get_posts( array(
            'post_type'      => 'stagekitwp_rubric',
            'posts_per_page' => -1,
            'meta_key'       => '_stagekitwp_rc_library_parent_template_id',
            'meta_value'     => $post->ID,
            'orderby'        => 'title',
            'order'          => 'ASC'
        ) );

        // --- ENFORCE DATA LOCK AND FREEZE ---
        $is_locked = false;
        if ( ! empty( $child_items ) ) {
            $item_ids = wp_list_pluck( $child_items, 'ID' );
            $ids_string = implode( ',', array_map( 'intval', $item_ids ) );
            $score_count = $wpdb->get_var( "SELECT COUNT(*) FROM $table_scores WHERE rubric_item_id IN ($ids_string)" );
            if ( $score_count > 0 ) {
                $is_locked = true;
            }
        }
        ?>
        <div class="stagekitwp-rcl-inline-table-container">
            <?php if ( $is_locked ) : ?>
                <div class="notice notice-warning inline" style="margin: 0 0 15px 0; padding: 10px; border-left-color: #ffb900;">
                    <p style="margin:0; font-weight:600; color:#444;">
                        🔒 This template is LOCKED. Evaluations have already been submitted using these metrics. Configuration options are now read-only to preserve score integrity.
                    </p>
                </div>
            <?php endif; ?>

            <table class="wp-list-table widefat fixed striped" id="stagekitwp-rcl-inline-metrics-table">
                <thead>
                    <tr>
                        <th style="width: 40%;"><strong>Metric Criteria Title</strong></th>
                        <th style="width: 20%;"><strong>Scoring Method</strong></th>
                        <th style="width: 15%;"><strong>Max Value</strong></th>
                        <th style="width: 15%;"><strong>Weight</strong></th>
                        <th style="width: 10%;"><strong>Actions</strong></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( ! empty( $child_items ) ) : ?>
                        <?php foreach ( $child_items as $item ) : 
                            $weight = get_post_meta( $item->ID, '_stagekitwp_rc_library_item_weight', true );
                            $weight = ! empty( $weight ) ? esc_html( $weight ) : '1.0';
                            
                            $score_type = get_post_meta( $item->ID, '_stagekitwp_rc_library_score_type', true );
                            $score_type = ! empty( $score_type ) ? esc_html( $score_type ) : 'number';
                            
                            $max_value = get_post_meta( $item->ID, '_stagekitwp_rc_library_max_value', true );
                            $max_value = ! empty( $max_value ) ? esc_html( $max_value ) : '5';
                            
                            $type_label = ( $score_type === 'slider' ) ? __('Slider', 'stagekitwp-rc-library') : (( $score_type === 'boolean' ) ? __('Pass/Fail', 'stagekitwp-rc-library') : __('1-X Scale', 'stagekitwp-rc-library'));
                            ?>
                            <tr id="metric-row-<?php echo $item->ID; ?>">
                                <td>
                                    <strong>
                                        <?php if ( $is_locked ) : ?>
                                            <?php echo esc_html( $item->post_title ); ?>
                                        <?php else : ?>
                                            <a href="<?php echo get_edit_post_link( $item->ID ); ?>"><?php echo esc_html( $item->post_title ); ?></a>
                                        <?php endif; ?>
                                    </strong>
                                </td>
                                <td><span class="dashicons dashicons-forms" style="font-size:16px; vertical-align:middle; margin-right:5px;"></span><?php echo $type_label; ?></td>
                                <td><code><?php echo $max_value; ?></code> points</td>
                                <td><code><?php echo $weight; ?>x</code></td>
                                <td>
                                    <?php if ( $is_locked ) : ?>
                                        <span style="color: #a7aaad; font-style: italic;">Locked</span>
                                    <?php else : ?>
                                        <a href="#" class="submitdelete stagekitwp-rcl-unlink-metric" data-id="<?php echo $item->ID; ?>" style="color: #b32d2e; text-decoration: none;">Remove</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr class="no-items-row"><td colspan="5" style="text-align: center; color: #646970; font-style: italic;">No specific assessment metrics added to this template yet. Build one below.</td></tr>
                    <?php endif; ?>
                </tbody>
                
                <?php if ( ! $is_locked ) : ?>
                <tfoot>
                    <tr style="background: #f0f6f7;">
                        <td>
                            <input type="text" id="new_metric_title" list="existing_metrics_list" placeholder="Type new title OR select existing to clone..." class="widefat" style="padding: 6px;" />
                            <datalist id="existing_metrics_list">
                                <?php 
                                $global_metrics = get_posts( array( 'post_type' => 'stagekitwp_rubric', 'posts_per_page' => 50, 'orderby' => 'title', 'order' => 'ASC' ) );
                                foreach ( $global_metrics as $gm ) {
                                    echo '<option value="' . esc_attr( $gm->post_title ) . '">';
                                }
                                ?>
                            </datalist>
                        </td>
                        <td>
                            <select id="new_metric_type" class="widefat" style="padding: 5px; min-height: 32px;">
                                <option value="number">1-X Discrete Scale</option>
                                <option value="slider">Continuous Slider</option>
                                <option value="boolean">Pass / Fail (0 or 1)</option>
                            </select>
                        </td>
                        <td><input type="number" id="new_metric_max" min="1" max="100" value="5" style="width: 100%; padding: 6px;" /></td>
                        <td><input type="number" id="new_metric_weight" step="0.1" min="0.1" max="10" value="1.0" style="width: 70px; padding: 6px;" /><strong> x</strong></td>
                        <td><button type="button" class="button button-secondary" id="stagekitwp-rcl-quick-add-btn">➕ Add</button></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>

        <script type="text/javascript">
            jQuery(document).ready(function($) {
                <?php if ( $is_locked ) : ?>
                    $('#stagekitwp_rc_library_post_title').prop('disabled', true).css('background', '#f6f7f7');
                    $('#publish').prop('disabled', true).addClass('button-disabled');
                <?php endif; ?>

                $('#new_metric_type').on('change', function() {
                    if($(this).val() === 'boolean') {
                        $('#new_metric_max').val(1).prop('disabled', true);
                    } else {
                        $('#new_metric_max').prop('disabled', false).val(5);
                    }
                });

                $('#stagekitwp-rcl-quick-add-btn').on('click', function(e) {
                    e.preventDefault();
                    var title = $('#new_metric_title').val().trim();
                    var type = $('#new_metric_type').val();
                    var maxVal = $('#new_metric_max').val();
                    var weight = $('#new_metric_weight').val();
                    var templateId = '<?php echo $post->ID; ?>';

                    if (!title) { return; }

                    var data = {
                        action: 'stagekitwp_rc_library_quick_add_metric',
                        title: title,
                        type: type,
                        max_value: maxVal,
                        weight: weight,
                        template_id: templateId,
                        nonce: '<?php echo wp_create_nonce("stagekitwp_rc_library_inline_nonce"); ?>'
                    };

                    $.post(ajaxurl, data, function(response) {
                        if (response.success) {
                            location.reload();
                        }
                    });
                });

                $(document).on('click', '.stagekitwp-rcl-unlink-metric', function(e) {
                    e.preventDefault();
                    var metricId = $(this).data('id');
                    var data = {
                        action: 'stagekitwp_rc_library_remove_metric',
                        id: metricId,
                        nonce: '<?php echo wp_create_nonce("stagekitwp_rc_library_inline_nonce"); ?>'
                    };
                    if(confirm('Are you sure you want to remove this metric?')) {
                        $.post(ajaxurl, data, function() { $('#metric-row-' + metricId).remove(); });
                    }
                });
            });
        </script>
        <?php
    }

    /**
     * Renders individual metadata profile editors for Scripts and Books.
     */
    public function render_book_profile_meta_box( $post ) {
        wp_nonce_field( 'stagekitwp_rc_library_save_book_profile', 'stagekitwp_rc_library_book_nonce' );

        $playwright = get_post_meta( $post->ID, '_stagekitwp_rc_library_book_playwright', true );
        $genre      = get_post_meta( $post->ID, '_stagekitwp_rc_library_book_genre', true );
        $cast_size  = get_post_meta( $post->ID, '_stagekitwp_rc_library_book_cast_size', true );
        $pdf_id     = get_post_meta( $post->ID, '_stagekitwp_rc_library_book_pdf_id', true );
        
        $pdf_filename = $pdf_id ? basename( wp_get_attachment_url( $pdf_id ) ) : '';
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="stagekitwp_rc_library_book_playwright"><strong>Playwright / Author</strong></label></th>
                <td><input type="text" id="stagekitwp_rc_library_book_playwright" name="stagekitwp_rc_library_book_playwright" value="<?php echo esc_attr( $playwright ); ?>" class="regular-text" placeholder="e.g., Arthur Miller" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="stagekitwp_rc_library_book_genre"><strong>Genre Classification</strong></label></th>
                <td><input type="text" id="stagekitwp_rc_library_book_genre" name="stagekitwp_rc_library_book_genre" value="<?php echo esc_attr( $genre ); ?>" class="regular-text" placeholder="e.g., Dramatic Tragedy / Dark Comedy" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="stagekitwp_rc_library_book_cast_size"><strong>Target Cast Size Required</strong></label></th>
                <td><input type="number" id="stagekitwp_rc_library_book_cast_size" name="stagekitwp_rc_library_book_cast_size" value="<?php echo esc_attr( $cast_size ); ?>" class="small-text" min="1" /> characters</td>
            </tr>
            <tr>
                <th scope="row"><strong>Script Document Source File (PDF)</strong></th>
                <td>
                    <input type="hidden" id="stagekitwp_rc_library_book_pdf_id" name="stagekitwp_rc_library_book_pdf_id" value="<?php echo esc_attr( $pdf_id ); ?>" />
                    <button type="button" class="button stagekitwp-rcl-upload-pdf-btn" data-target="stagekitwp_rc_library_book_pdf_id" data-preview="book_pdf_preview_meta">Choose PDF from Media Library</button>
                    <span id="book_pdf_preview_meta" style="margin-left: 12px; font-size: 13px;">
                        <?php if ( $pdf_id ) : ?>
                            <strong>✅ Linked:</strong> <?php echo esc_html( $pdf_filename ); ?> (<a href="<?php echo esc_url( wp_get_attachment_url( $pdf_id ) ); ?>" target="_blank">View File</a>)
                        <?php else : ?>
                            <span style="color: #646970; font-style: italic;">No file attached to this script record yet.</span>
                        <?php endif; ?>
                    </span>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Global interceptor saving raw post meta values securely.
     */
    public function save_custom_meta_data( $post_id ) {
        if ( ! isset( $_POST['stagekitwp_rc_library_title_nonce'] ) || ! wp_verify_nonce( $_POST['stagekitwp_rc_library_title_nonce'], 'stagekitwp_rc_library_save_title' ) ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // 1. Process Large Custom Title Box Updates
        if ( isset( $_POST['stagekitwp_rc_library_post_title'] ) ) {
            remove_action( 'save_post', array( $this, 'save_custom_meta_data' ) );
            wp_update_post( array(
                'ID'         => $post_id,
                'post_title' => sanitize_text_field( $_POST['stagekitwp_rc_library_post_title'] ),
            ) );
            add_action( 'save_post', array( $this, 'save_custom_meta_data' ) );
        }

        // 2. Process Individual Rubric Item Property Configuration Updates
        if ( isset( $_POST['stagekitwp_rc_library_item_specs_nonce'] ) && wp_verify_nonce( $_POST['stagekitwp_rc_library_item_specs_nonce'], 'stagekitwp_rc_library_save_item_specs' ) ) {
            if ( isset( $_POST['stagekitwp_rc_library_edit_score_type'] ) ) {
                update_post_meta( $post_id, '_stagekitwp_rc_library_score_type', sanitize_text_field( $_POST['stagekitwp_rc_library_edit_score_type'] ) );
            }
            if ( isset( $_POST['stagekitwp_rc_library_edit_max_value'] ) ) {
                update_post_meta( $post_id, '_stagekitwp_rc_library_max_value', intval( $_POST['stagekitwp_rc_library_edit_max_value'] ) );
            }
            if ( isset( $_POST['stagekitwp_rc_library_edit_weight'] ) ) {
                update_post_meta( $post_id, '_stagekitwp_rc_library_item_weight', floatval( $_POST['stagekitwp_rc_library_edit_weight'] ) );
            }
        }

        // 3. Process Custom Books & Scripts Profiles Updates
        if ( isset( $_POST['stagekitwp_rc_library_book_nonce'] ) && wp_verify_nonce( $_POST['stagekitwp_rc_library_book_nonce'], 'stagekitwp_rc_library_save_book_profile' ) ) {
            if ( isset( $_POST['stagekitwp_rc_library_book_playwright'] ) ) {
                update_post_meta( $post_id, '_stagekitwp_rc_library_book_playwright', sanitize_text_field( $_POST['stagekitwp_rc_library_book_playwright'] ) );
            }
            if ( isset( $_POST['stagekitwp_rc_library_book_genre'] ) ) {
                update_post_meta( $post_id, '_stagekitwp_rc_library_book_genre', sanitize_text_field( $_POST['stagekitwp_rc_library_book_genre'] ) );
            }
            if ( isset( $_POST['stagekitwp_rc_library_book_cast_size'] ) ) {
                update_post_meta( $post_id, '_stagekitwp_rc_library_book_cast_size', intval( $_POST['stagekitwp_rc_library_book_cast_size'] ) );
            }
            if ( isset( $_POST['stagekitwp_rc_library_book_pdf_id'] ) ) {
                update_post_meta( $post_id, '_stagekitwp_rc_library_book_pdf_id', intval( $_POST['stagekitwp_rc_library_book_pdf_id'] ) );
            }
        }
    }

    /**
     * Column Configuration Framework for Rubric Items List.
     */
    public function set_custom_rubric_item_columns( $columns ) {
        $columns['title'] = __( 'Metric Title', 'stagekitwp-rc-library' );
        $columns['stagekitwp_parent_template'] = __( 'Assigned Template', 'stagekitwp-rc-library' );
        $columns['stagekitwp_scoring_specs'] = __( 'Scoring Configuration', 'stagekitwp-rc-library' );
        $columns['stagekitwp_weight'] = __( 'Weight Multiplier', 'stagekitwp-rc-library' );
        return $columns;
    }

    public function render_custom_rubric_item_columns( $column, $post_id ) {
        switch ( $column ) {
            case 'stagekitwp_parent_template':
                $template_id = get_post_meta( $post_id, '_stagekitwp_rc_library_parent_template_id', true );
                if ( ! empty( $template_id ) ) {
                    echo '<a href="' . esc_url( get_edit_post_link( $template_id ) ) . '"><strong>' . esc_html( get_the_title( $template_id ) ) . '</strong></a>';
                } else {
                    echo '<span style="color:#cca000; font-style:italic;">Unassigned</span>';
                }
                break;

            case 'stagekitwp_scoring_specs':
                $score_type = get_post_meta( $post_id, '_stagekitwp_rc_library_score_type', true );
                $max_value  = get_post_meta( $post_id, '_stagekitwp_rc_library_max_value', true );
                $score_type = ! empty( $score_type ) ? $score_type : 'number';
                $max_value  = ! empty( $max_value ) ? $max_value : '5';
                $type_label = ( $score_type === 'slider' ) ? __('Slider', 'stagekitwp-rc-library') : (( $score_type === 'boolean' ) ? __('Pass/Fail', 'stagekitwp-rc-library') : __('Scale', 'stagekitwp-rc-library'));
                echo '<strong>' . $type_label . '</strong> (Max: ' . $max_value . ' pts)';
                break;

            case 'stagekitwp_weight':
                $weight = get_post_meta( $post_id, '_stagekitwp_rc_library_item_weight', true );
                echo ! empty( $weight ) ? esc_html( $weight ) . 'x' : '1.00x';
                break;
        }
    }

    /**
     * Column Configuration Framework for Rubric Templates List.
     */
    public function set_custom_template_columns( $columns ) {
        $columns['title'] = __( 'Template Name', 'stagekitwp-rc-library' );
        $columns['stagekitwp_total_metrics'] = __( 'Total Metrics Bound', 'stagekitwp-rc-library' );
        return $columns;
    }

    public function render_custom_template_columns( $column, $post_id ) {
        if ( $column === 'stagekitwp_total_metrics' ) {
            $items = get_posts( array(
                'post_type' => 'stagekitwp_rubric',
                'meta_key'  => '_stagekitwp_rc_library_parent_template_id',
                'meta_value'=> $post_id,
                'fields'    => 'ids'
            ));
            echo '<strong>' . count( $items ) . '</strong> assessment criteria items';
        }
    }

    /**
     * Column Configuration Framework for Books & Scripts List view.
     */
    public function set_custom_book_columns( $columns ) {
        $columns['title'] = __( 'Script Title', 'stagekitwp-rc-library' );
        $columns['stagekitwp_playwright'] = __( 'Playwright', 'stagekitwp-rc-library' );
        $columns['stagekitwp_genre'] = __( 'Genre', 'stagekitwp-rc-library' );
        $columns['stagekitwp_cast'] = __( 'Cast Req.', 'stagekitwp-rc-library' );
        $columns['stagekitwp_asset'] = __( 'PDF Status', 'stagekitwp-rc-library' );
        return $columns;
    }

    public function render_custom_book_columns( $column, $post_id ) {
        switch ( $column ) {
            case 'stagekitwp_playwright':
                $author = get_post_meta( $post_id, '_stagekitwp_rc_library_book_playwright', true );
                echo ! empty( $author ) ? esc_html( $author ) : '<span style="color:#a7aaad;">—</span>';
                break;
            case 'stagekitwp_genre':
                $genre = get_post_meta( $post_id, '_stagekitwp_rc_library_book_genre', true );
                echo ! empty( $genre ) ? esc_html( $genre ) : '<span style="color:#a7aaad;">—</span>';
                break;
            case 'stagekitwp_cast':
                $cast = get_post_meta( $post_id, '_stagekitwp_rc_library_book_cast_size', true );
                echo ! empty( $cast ) ? '<strong>' . intval($cast) . '</strong> roles' : '<span style="color:#a7aaad;">—</span>';
                break;
            case 'stagekitwp_asset':
                $pdf = get_post_meta( $post_id, '_stagekitwp_rc_library_book_pdf_id', true );
                echo ! empty( $pdf ) ? '<span style="color:#135e23; font-weight:600;">✅ Linked</span>' : '<span style="color:#b32d2e; font-style:italic;">Missing File</span>';
                break;
        }
    }

    /**
     * Inline AJAX Handler: Adds or clones metrics asynchronously.
     */
    public function ajax_quick_add_metric() {
        check_ajax_referer( 'stagekitwp_rc_library_inline_nonce', 'nonce' );
        $title       = sanitize_text_field( $_POST['title'] );
        $type        = sanitize_text_field( $_POST['type'] );
        $max_value   = intval( $_POST['max_value'] );
        $weight      = floatval( $_POST['weight'] );
        $template_id = intval( $_POST['template_id'] );

        if ( empty( $title ) || empty( $template_id ) ) {
            wp_send_json_error();
        }

        $new_id = wp_insert_post( array(
            'post_title'  => $title,
            'post_type'   => 'stagekitwp_rubric',
            'post_status' => 'publish'
        ) );

        if ( ! is_wp_error( $new_id ) ) {
            update_post_meta( $new_id, '_stagekitwp_rc_library_parent_template_id', $template_id );
            update_post_meta( $new_id, '_stagekitwp_rc_library_item_weight', $weight );
            update_post_meta( $new_id, '_stagekitwp_rc_library_score_type', $type );
            update_post_meta( $new_id, '_stagekitwp_rc_library_max_value', $max_value );
            wp_send_json_success( array( 'id' => $new_id ) );
        }
        wp_send_json_error();
    }

    /**
     * Inline AJAX Handler: Unlinks and purges target child metrics.
     */
    public function ajax_remove_metric() {
        check_ajax_referer( 'stagekitwp_rc_library_inline_nonce', 'nonce' );
        $metric_id = intval( $_POST['id'] );
        if ( $metric_id ) {
            wp_delete_post( $metric_id, true );
            wp_send_json_success();
        }
        wp_send_json_error();
    }
}