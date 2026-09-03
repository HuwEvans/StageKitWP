<?php
/**
 * Default template for single Gallery (skwpm_gallery) CPT posts.
 *
 * Loaded via the `template_include` filter in CPT::template_include() unless
 * the active theme provides its own `single-skwpm_gallery.php` override.
 * CPT::render_single_content() replaces the_content with the gallery's
 * [skwpm_gallery] items display, so only the gallery's own title is shown
 * here — no generic "Archive" heading.
 *
 * Featured image behaviour:
 *  - The resolved hero image is ALWAYS injected into the <head> as Open Graph
 *    and Twitter Card meta tags for SEO / social sharing.
 *  - The visible hero banner is only rendered when "_skwpm_show_hero" is
 *    enabled (checkbox in Display Settings meta box). New galleries default
 *    to visible (true) until explicitly saved as hidden.
 *
 * Image resolution priority:
 *  1. WordPress featured image (post thumbnail).
 *  2. First image-type item in the gallery's _skwpm_items list.
 *
 * @package StageKitWP_Media
 */

defined( 'ABSPATH' ) || exit;

// ── Helper: resolve the best available hero image ──────────────────────────
if ( ! function_exists( 'skwpm_resolve_hero_image' ) ) {
	function skwpm_resolve_hero_image( int $post_id ): ?array {
		// 1. WordPress featured image.
		if ( has_post_thumbnail( $post_id ) ) {
			$img_url = get_the_post_thumbnail_url( $post_id, 'large' );
			$img_alt = get_post_meta( get_post_thumbnail_id( $post_id ), '_wp_attachment_image_alt', true );
			if ( $img_url ) {
				return [
					'url'    => $img_url,
					'alt'    => $img_alt ?: get_the_title( $post_id ),
					'width'  => null,
					'height' => null,
				];
			}
		}

		// 2. First image-type gallery item.
		$items = get_post_meta( $post_id, '_skwpm_items', true );
		if ( ! is_array( $items ) ) {
			return null;
		}

		foreach ( $items as $item ) {
			$type = $item['type'] ?? '';
			$url  = $item['thumb'] ?? $item['url'] ?? '';

			if ( ! $url ) {
				continue;
			}
			// Skip non-image types.
			if ( ! in_array( $type, [ 'image', 'photo', '' ], true ) ) {
				continue;
			}
			// Skip if mime_type is explicitly non-image.
			$mime = strtolower( $item['mime_type'] ?? '' );
			if ( $mime && ! str_starts_with( $mime, 'image/' ) ) {
				continue;
			}

			return [
				'url'    => $url,
				'alt'    => $item['title'] ?? get_the_title( $post_id ),
				'width'  => null,
				'height' => null,
			];
		}

		return null;
	}
}

// ── Resolve hero once, then wire OG tags into <head> ───────────────────────
$skwpm_post_id   = get_queried_object_id();
$skwpm_hero      = skwpm_resolve_hero_image( $skwpm_post_id );

// "Show hero" defaults to true for galleries that have never been saved.
$_show_hero_meta = get_post_meta( $skwpm_post_id, '_skwpm_show_hero', true );
$skwpm_show_hero = ( '' === $_show_hero_meta ) ? true : (bool) $_show_hero_meta;

if ( $skwpm_hero ) {
	// Inject OG / Twitter Card meta into <head> regardless of show_hero toggle.
	add_action( 'wp_head', function () use ( $skwpm_hero, $skwpm_post_id ) {
		$img_url  = esc_url( $skwpm_hero['url'] );
		$img_alt  = esc_attr( $skwpm_hero['alt'] );
		$title    = esc_attr( get_the_title( $skwpm_post_id ) );
		$site_url = esc_url( get_permalink( $skwpm_post_id ) );

		echo "\n<!-- StageKit Media: Open Graph / Twitter Card -->\n";

		// og:image
		printf( '<meta property="og:image" content="%s">' . "\n", $img_url );
		printf( '<meta property="og:image:alt" content="%s">' . "\n", $img_alt );
		if ( $skwpm_hero['width'] ) {
			printf( '<meta property="og:image:width" content="%d">' . "\n", (int) $skwpm_hero['width'] );
		}
		if ( $skwpm_hero['height'] ) {
			printf( '<meta property="og:image:height" content="%d">' . "\n", (int) $skwpm_hero['height'] );
		}

		// og:type / og:title / og:url
		echo '<meta property="og:type" content="website">' . "\n";
		printf( '<meta property="og:title" content="%s">' . "\n", $title );
		printf( '<meta property="og:url" content="%s">' . "\n", $site_url );

		// Twitter Card
		echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
		printf( '<meta name="twitter:title" content="%s">' . "\n", $title );
		printf( '<meta name="twitter:image" content="%s">' . "\n", $img_url );
		printf( '<meta name="twitter:image:alt" content="%s">' . "\n", $img_alt );

		echo "<!-- /StageKit Media -->\n";
	}, 5 ); // Priority 5 — before most SEO plugins so they can override.
}

get_header();
?>

<main id="primary" class="site-main">
	<div class="stagekitwp-container" style="max-width:var(--stagekitwp-site-max-width, 1200px);margin:40px auto;padding:0 20px;">
		<?php while ( have_posts() ) : the_post(); ?>
			<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
				<p class="skwpm-gallery-back" style="margin:0 0 16px;">
					<a href="<?php echo esc_url( \SKWPM\CPT::get_gallery_list_url() ); ?>">&larr; Back to Galleries</a>
				</p>

				<?php if ( $skwpm_show_hero && $skwpm_hero ) : ?>
					<div class="skwpm-gallery-hero" style="margin-bottom:24px;border-radius:8px;overflow:hidden;max-height:480px;line-height:0;">
						<img
							src="<?php echo esc_url( $skwpm_hero['url'] ); ?>"
							alt="<?php echo esc_attr( $skwpm_hero['alt'] ); ?>"
							style="width:100%;height:480px;object-fit:cover;display:block;"
							loading="eager"
							decoding="async"
						>
					</div>
				<?php endif; ?>

				<header class="entry-header" style="margin-bottom:20px;">
					<?php the_title( '<h1 class="entry-title" style="font-size:2rem;margin:0;color:#111111;">', '</h1>' ); ?>
				</header>
				<div class="entry-content">
					<?php the_content(); ?>
				</div>
			</article>
		<?php endwhile; ?>
	</div>
</main>

<?php get_footer();
