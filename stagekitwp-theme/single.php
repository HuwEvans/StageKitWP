<?php
/**
 * The template for displaying a single blog post.
 *
 * The featured image floats left (max 33% width) so entry content wraps
 * around it on the right, then continues full-width once the text runs
 * past the bottom of the image.
 *
 * @package StageKitWP_Theme
 */

get_header();
?>

<main id="primary" class="site-main stagekitwp-container" style="padding: 60px max(2rem, 5vw); max-width: var(--stagekitwp-site-max-width, 1200px); margin: 0 auto;">
    <?php
    while ( have_posts() ) :
        the_post();
        ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            <?php if ( stagekitwp_theme_display_title() ) : ?>
            <header class="entry-header" style="margin-bottom: 20px;">
                <h1 class="entry-title" style="font-size: 2.5rem; margin: 0 0 10px 0; font-weight: 800; color: #111111;">
                    <?php the_title(); ?>
                </h1>
                <div class="entry-meta" style="font-size: 0.85rem; color: #888888;">
                    <span class="posted-on" style="margin-right: 15px;">📅 <?php echo esc_html( get_the_date() ); ?></span>
                    <span class="posted-by">✍️ <?php the_author(); ?></span>
                </div>
            </header>

            <div class="title-accent" style="width: 50px; height: 3px; background-color: #e50914; margin-bottom: 30px;"></div>
            <?php endif; ?>

            <div class="stagekitwp-single-post-body">
                <?php if ( has_post_thumbnail() ) : ?>
                    <div class="post-thumbnail" style="float: left; width: 33%; max-width: 33%; margin: 0 24px 16px 0; border-radius: 4px; overflow: hidden;">
                        <?php the_post_thumbnail( 'medium_large', array( 'style' => 'width: 100%; height: auto; display: block;' ) ); ?>
                    </div>
                <?php endif; ?>

                <div class="entry-content" style="line-height: 1.8; font-size: 1.1rem; color: #333333;">
                    <?php the_content(); ?>
                </div>

                <div style="clear: both;"></div>
            </div>
        </article>

        <?php
        if ( comments_open() || get_comments_number() ) :
            comments_template();
        endif;
    endwhile;
    ?>
</main>

<?php
get_footer();
