<?php
/**
 * The template for displaying comments.
 *
 * @package StageKitWP_Theme
 */

if ( post_password_required() ) {
    return;
}
?>

<div id="comments" class="comments-area" style="margin-top:48px;padding-top:32px;border-top:1px solid #e0e0e0;">

    <?php if ( have_comments() ) : ?>

        <h2 class="comments-title" style="font-size:1.4rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:24px;">
            <?php
            $stagekitwp_comment_count = get_comments_number();
            if ( 1 === (int) $stagekitwp_comment_count ) {
                printf(
                    /* translators: %s: post title */
                    esc_html__( 'One comment on &ldquo;%s&rdquo;', 'stagekitwp-theme' ),
                    '<span>' . get_the_title() . '</span>'
                );
            } else {
                printf(
                    /* translators: 1: comment count, 2: post title */
                    esc_html__( '%1$s comments on &ldquo;%2$s&rdquo;', 'stagekitwp-theme' ),
                    number_format_i18n( $stagekitwp_comment_count ),
                    '<span>' . get_the_title() . '</span>'
                );
            }
            ?>
        </h2>

        <ol class="comment-list" style="list-style:none;padding:0;margin:0 0 32px;">
            <?php
            wp_list_comments( array(
                'style'      => 'ol',
                'short_ping' => true,
            ) );
            ?>
        </ol>

        <?php the_comments_navigation(); ?>

    <?php endif; // have_comments() ?>

    <?php if ( ! comments_open() && get_comments_number() && post_type_supports( get_post_type(), 'comments' ) ) : ?>
        <p class="no-comments" style="color:var(--stagekitwp-muted-text,#666);">
            <?php esc_html_e( 'Comments are closed.', 'stagekitwp-theme' ); ?>
        </p>
    <?php endif; ?>

    <?php
    comment_form( array(
        'title_reply_before' => '<h2 id="reply-title" class="comment-reply-title" style="font-size:1.4rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:20px;">',
        'title_reply_after'  => '</h2>',
    ) );
    ?>

</div>
