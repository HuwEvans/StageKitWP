<?php
/**
 * Layout execution pipeline mapping properties straight onto the front-end view canvas.
 */
if ( class_exists( 'STAGEKITWP_RC_LIBRARY_Evaluation_Form' ) ) {
    $form_engine = new STAGEKITWP_RC_LIBRARY_Evaluation_Form();
    
    // Safely extract the selection indices assigned via the drop-down menu selectors
    $group_id = isset( $settings->group_id ) ? intval( $settings->group_id ) : 0;
    $book_id  = isset( $settings->book_id ) ? intval( $settings->book_id ) : 0;

    echo $form_engine->generate_form_html( array(
        'group_id' => $group_id,
        'book_id'  => $book_id,
    ) );
} else {
    echo '<p>' . esc_html__( 'Error: Evaluation structural component classes not initialized.', 'stagekitwp-rc-library' ) . '</p>';
}