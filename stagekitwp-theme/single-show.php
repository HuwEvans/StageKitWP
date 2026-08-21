<?php
/**
 * Template for single Show CPT posts.
 *
 * When the StageKitWP plugin's Show Front-end Display feature is active,
 * the_content filter replaces post content with the configured shortcode view.
 * This template deliberately omits the archive title, post date, and author
 * meta that index.php outputs for generic posts.
 *
 * @package StageKitWP_Theme
 */

get_header();
?>

<main id="primary" class="site-main">
    <div class="stagekitwp-container" style="max-width:var(--stagekitwp-site-max-width, 1200px);margin:40px auto;padding:0 20px;">
        <?php while ( have_posts() ) : the_post(); ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <div class="entry-content">
                    <?php the_content(); ?>
                </div>
            </article>
        <?php endwhile; ?>
    </div>
</main>

<?php get_footer();
