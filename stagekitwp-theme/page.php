<?php
/**
 * The template for displaying all individual sub-pages.
 *
 * @package StageKitWP_Theme
 */

get_header();
?>

<main id="primary" class="site-main stagekitwp-container" style="padding: 60px max(2rem, 5vw); max-width: var(--stagekitwp-site-max-width, 1200px); margin: 0 auto;">
    <?php
    while ( have_posts() ) : the_post();
        ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            <h1 class="entry-title" style="font-size: 2.5rem; margin-bottom: 20px; font-weight: 800;">
                <?php the_title(); ?>
            </h1>

            <div class="title-accent" style="width: 50px; height: 3px; background-color: #e50914; margin-bottom: 40px;"></div>

            <div class="entry-content" style="line-height: 1.8; font-size: 1.1rem;">
                <?php the_content(); ?>
            </div>
        </article>
        <?php
    endwhile;
    ?>
</main>

<?php
get_footer();