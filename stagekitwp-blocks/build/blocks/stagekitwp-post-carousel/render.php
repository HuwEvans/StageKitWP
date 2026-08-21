<?php
/**
 * Dynamic Post Carousel Render Template (Slick Version)
 */

$posts_per_page = isset( $attributes['postsPerPage'] ) ? intval( $attributes['postsPerPage'] ) : 6;
$category_id    = ! empty( $attributes['category'] ) ? intval( $attributes['category'] ) : 0;
$show_excerpt   = isset( $attributes['showExcerpt'] ) ? (bool) $attributes['showExcerpt'] : true;
$autoplay       = ! empty( $attributes['autoplay'] ) ? 'true' : 'false';
$autoplay_delay = isset( $attributes['autoplayDelay'] ) ? intval( $attributes['autoplayDelay'] ) : 3000;
$loop           = isset( $attributes['loop'] ) ? ( (bool) $attributes['loop'] ? 'true' : 'false' ) : 'true';
$color_mode = isset( $attributes['colorMode'] ) && ! empty( $attributes['colorMode'] ) ? $attributes['colorMode'] : 'auto';

// Check theme active status
$is_theme_active = function_exists( 'stagekitwp_is_theme_active' ) ? stagekitwp_is_theme_active() : false;

$classes = array( 'stagekit-carousel-wrapper' );

if ( $is_theme_active ) {
	$classes[] = 'has-stagekitwp-theme';
}

if ( 'light' === $color_mode ) {
	$classes[] = 'is-light-mode';
} elseif ( 'dark' === $color_mode ) {
	$classes[] = 'is-dark-mode';
}

$wrapper_attributes = get_block_wrapper_attributes( array(
	'class'             => implode( ' ', $classes ),
	'data-color-mode'   => esc_attr( $color_mode ),
	'data-theme-active' => $is_theme_active ? 'true' : 'false',
) );

$args = array(
	'posts_per_page' => $posts_per_page,
	'post_status'    => 'publish',
);

if ( $category_id > 0 ) {
	$args['cat'] = $category_id;
}

$recent_posts = new WP_Query( $args );

if ( $recent_posts->have_posts() ) : ?>
	<div <?php echo $wrapper_attributes; ?>>
		<div 
			class="stagekit-slick-slider"
			data-autoplay="<?php echo esc_attr( $autoplay ); ?>"
			data-autoplay-speed="<?php echo esc_attr( $autoplay_delay ); ?>"
			data-infinite="<?php echo esc_attr( $loop ); ?>"
		>
			<?php while ( $recent_posts->have_posts() ) : $recent_posts->the_post(); ?>
				<div class="stagekit-slide-item">
					<article class="stagekit-card">
						<?php if ( has_post_thumbnail() ) : ?>
							<div class="stagekit-card-image">
								<a href="<?php the_permalink(); ?>">
									<?php the_post_thumbnail( 'medium_large' ); ?>
								</a>
							</div>
						<?php endif; ?>

						<div class="stagekit-card-content">
							<div class="stagekit-card-meta">
								<span class="stagekit-card-date"><?php echo get_the_date( 'M j, Y' ); ?></span>
							</div>

							<h3 class="stagekit-card-title">
								<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
							</h3>

							<?php if ( $show_excerpt ) : ?>
								<div class="stagekit-card-excerpt">
									<?php echo wp_trim_words( get_the_excerpt(), 18, '...' ); ?>
								</div>
							<?php endif; ?>

							<div class="stagekit-card-footer">
								<a href="<?php the_permalink(); ?>" class="stagekit-card-btn">
									<?php esc_html_e( 'Read More', 'stagekitwp-blocks' ); ?> &rarr;
								</a>
							</div>
						</div>
					</article>
				</div>
			<?php endwhile; ?>
		</div>
	</div>
	<?php
	wp_reset_postdata();
endif;