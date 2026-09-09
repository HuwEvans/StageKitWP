<?php
namespace SKWPM;

class Shortcode {
	/** Valid values for the `layout` attribute when listing galleries. */
	private const LAYOUTS = [ 'list', 'grid', 'mosaic' ];

	/** Tracks whether the gallery-list styles have already been printed on this page load. */
	private static bool $styles_printed = false;

	public static function init(): void {
		add_shortcode( 'skwpm_gallery', [ __CLASS__, 'render' ] );
	}

	private static function enqueue_lightbox_assets(): void {
		wp_enqueue_style( 'skwpm-gallery-lightbox', SKWPM_URL . 'assets/css/gallery-lightbox.css', [], SKWPM_VERSION );
		wp_enqueue_script( 'skwpm-gallery-lightbox', SKWPM_URL . 'assets/js/gallery-lightbox.js', [], SKWPM_VERSION, true );
	}

	public static function render( $atts ): string {
		$atts = shortcode_atts( [
			'id'         => 0,
			'columns'    => 3,
			'layout'     => 'grid',
			'show_image' => 'yes',
			'limit'      => -1,
			'orderby'    => 'title',
			'order'      => 'ASC',
		], $atts, 'skwpm_gallery' );

		if ( (int) $atts['id'] > 0 ) {
			return self::render_items( (int) $atts['id'], (int) $atts['columns'] );
		}

		return self::render_gallery_list( $atts );
	}

	/** Render the list of Gallery posts (List, Grid, or Mosaic layout). */
	private static function render_gallery_list( array $atts ): string {
		$layout = in_array( $atts['layout'], self::LAYOUTS, true ) ? $atts['layout'] : 'grid';
		$show_image = ! in_array( strtolower( (string) $atts['show_image'] ), [ 'no', 'false', '0' ], true );
		$cols   = max( 1, min( 6, (int) $atts['columns'] ) );

		$galleries = CPT::get_galleries( [
			'posts_per_page' => (int) $atts['limit'],
			'orderby'        => sanitize_key( $atts['orderby'] ),
			'order'          => 'DESC' === strtoupper( $atts['order'] ) ? 'DESC' : 'ASC',
		] );

		if ( empty( $galleries ) ) {
			return '';
		}

		$html  = self::styles();
		$html .= sprintf(
			'<div class="skwpm-gallery-list skwpm-gallery-list--%s skwpm-gallery-cols-%d" style="--skwpm-cols:%d;">',
			esc_attr( $layout ),
			$cols,
			$cols
		);

		foreach ( $galleries as $gallery ) {
			$html .= self::gallery_entry( $gallery, $show_image );
		}

		return $html . '</div>';
	}

	private static function gallery_entry( \WP_Post $gallery, bool $show_image ): string {
		$link  = get_permalink( $gallery );
		$title = get_the_title( $gallery );
		$image = '';

		if ( $show_image && has_post_thumbnail( $gallery ) ) {
			$image = get_the_post_thumbnail( $gallery, 'medium', [ 'loading' => 'lazy' ] );
		}

		return sprintf(
			'<a class="skwpm-gallery-entry" href="%s">%s<span class="skwpm-gallery-entry-title">%s</span></a>',
			esc_url( $link ),
			$image,
			esc_html( $title )
		);
	}

	/** Print the (once-per-request) inline stylesheet for the gallery-list layouts. */
	private static function styles(): string {
		if ( self::$styles_printed ) {
			return '';
		}
		self::$styles_printed = true;

		return '<style>'
			. '.skwpm-gallery-list{margin:0;padding:0;}'
			. '.skwpm-gallery-entry{display:block;text-decoration:none;color:inherit;}'
			. '.skwpm-gallery-entry img{width:100%;height:100%;object-fit:cover;display:block;}'
			. '.skwpm-gallery-entry-title{display:block;padding:.5rem 0;font-weight:600;}'
			. '.skwpm-gallery-list--list{display:flex;flex-direction:column;gap:1rem;}'
			. '.skwpm-gallery-list--list .skwpm-gallery-entry{display:flex;align-items:center;gap:1rem;}'
			. '.skwpm-gallery-list--list .skwpm-gallery-entry img{width:120px;height:80px;flex:0 0 auto;}'
			. '.skwpm-gallery-list--list .skwpm-gallery-entry-title{padding:0;}'
			. '.skwpm-gallery-list--grid{display:grid;grid-template-columns:repeat(var(--skwpm-cols,3),1fr);gap:1rem;}'
			. '.skwpm-gallery-list--grid .skwpm-gallery-entry img{aspect-ratio:4/3;}'
			. '.skwpm-gallery-list--mosaic{display:grid;grid-auto-flow:dense;grid-template-columns:repeat(var(--skwpm-cols,4),1fr);grid-auto-rows:120px;gap:.75rem;}'
			. '.skwpm-gallery-list--mosaic .skwpm-gallery-entry{position:relative;overflow:hidden;}'
			. '.skwpm-gallery-list--mosaic .skwpm-gallery-entry:nth-child(5n+1){grid-column:span 2;grid-row:span 2;}'
			. '.skwpm-gallery-list--mosaic .skwpm-gallery-entry img{width:100%;height:100%;}'
			. '.skwpm-gallery-list--mosaic .skwpm-gallery-entry-title{position:absolute;inset:auto 0 0 0;margin:0;padding:.5rem;background:rgba(0,0,0,.55);color:#fff;}'
			. '.skwpm-gallery{display:grid;grid-template-columns:repeat(var(--skwpm-cols,3),minmax(0,1fr));gap:1rem;}'
			. '.skwpm-gallery figure{min-width:0;margin:0;}'
			. '@media(max-width:900px){.skwpm-gallery-cols-4,.skwpm-gallery-cols-5,.skwpm-gallery-cols-6{--skwpm-cols:3!important;}}'
			. '@media(max-width:640px){.skwpm-gallery-cols-3,.skwpm-gallery-cols-4,.skwpm-gallery-cols-5,.skwpm-gallery-cols-6{--skwpm-cols:2!important;}.skwpm-gallery-list--mosaic{grid-auto-rows:100px;}}'
			. '@media(max-width:420px){.skwpm-gallery-cols-2,.skwpm-gallery-cols-3,.skwpm-gallery-cols-4,.skwpm-gallery-cols-5,.skwpm-gallery-cols-6{--skwpm-cols:1!important;}.skwpm-gallery-list--mosaic .skwpm-gallery-entry:nth-child(5n+1){grid-column:span 1;grid-row:span 1;}}'
			. '</style>';
	}

	/** Render the media items belonging to a single gallery (original `id`-based behaviour). */
	private static function render_items( int $gallery_id, int $columns ): string {
		$items = CPT::get_items( $gallery_id );
		if ( empty( $items ) ) {
			return '';
		}

		self::enqueue_lightbox_assets();
		$cols = max( 1, min( 6, $columns ) );
		$html = self::styles();
		$html .= '<div class="skwpm-gallery skwpm-gallery-cols-' . $cols . '" style="--skwpm-cols:' . $cols . ';display:grid;grid-template-columns:repeat(var(--skwpm-cols,3),minmax(0,1fr));gap:1rem;">';
		foreach ( $items as $index => $item ) {
			$credit = trim( ( $item['author'] ?? '' ) . ( ! empty( $item['author'] ) && ! empty( $item['license'] ) ? ' · ' : '' ) . ( $item['license'] ?? '' ) );
			$caption_html = $credit ? sprintf( '<figcaption>%s</figcaption>', esc_html( $credit ) ) : '';
			$type = $item['type'] ?? 'image';
			if ( in_array( $type, [ 'image', 'photo' ], true ) && ! empty( $item['url'] ) ) {
				$html .= sprintf(
					'<figure class="skwpm-item skwpm-item-%1$s"><button class="skwpm-lightbox-trigger" type="button" data-skwpm-lightbox-src="%2$s" data-skwpm-lightbox-alt="%3$s" data-skwpm-lightbox-caption="%4$s" data-skwpm-lightbox-index="%5$d" aria-label="View %3$s in full size">%6$s</button>%7$s</figure>',
					esc_attr( $type ),
					esc_url( $item['url'] ),
					esc_attr( $item['title'] ?? '' ),
					esc_attr( $credit ),
					$index,
					self::media( $item ),
					$caption_html
				);
			} else {
				$html .= sprintf(
					'<figure class="skwpm-item skwpm-item-%s">%s%s</figure>',
					esc_attr( $type ),
					self::media( $item ),
					$caption_html
				);
			}
		}
		return $html . '</div>';
	}

	/** Providers whose videos live on a hosted player (need an <iframe>). */
	private const IFRAME_PROVIDERS = [ 'youtube', 'vimeo' ];

	/**
	 * Render the media element for one item: an <iframe> embed for hosted-
	 * player video providers (YouTube, Vimeo), a native <video> tag for
	 * direct-file video providers (Dropbox, Google Drive, StageKit Media), or a plain image.
	 */
	private static function media( array $item ): string {
		if ( 'video' === ( $item['type'] ?? 'image' ) ) {
			$provider = $item['provider'] ?? '';

			if ( in_array( $provider, self::IFRAME_PROVIDERS, true ) ) {
				$src = self::embed_url( $item );
				if ( $src ) {
					return sprintf(
						'<span class="skwpm-video-wrap" style="display:block;position:relative;padding-top:56.25%%;"><iframe src="%s" style="position:absolute;inset:0;width:100%%;height:100%%;border:0;" loading="lazy" allowfullscreen title="%s"></iframe></span>',
						esc_url( $src ),
						esc_attr( $item['title'] ?? '' )
					);
				}
			} elseif ( ! empty( $item['url'] ) ) {
				// Direct-file providers (Dropbox, Google Drive, StageKit Media) serve a playable URL directly.
				return sprintf(
					'<video controls preload="metadata" style="width:100%%;height:auto;display:block;" poster="%s"><source src="%s"></video>',
					esc_url( $item['thumb'] ?? '' ),
					esc_url( $item['url'] )
				);
			}
		}

		$src = ! empty( $item['thumb'] ) ? $item['thumb'] : ( $item['url'] ?? '' );
		return sprintf(
			'<img src="%s" alt="%s" loading="lazy" style="width:100%%;height:auto;display:block;">',
			esc_url( $src ),
			esc_attr( $item['title'] ?? '' )
		);
	}

	private static function embed_url( array $item ): string {
		$provider = $item['provider'] ?? '';
		$id       = $item['external_id'] ?? '';
		if ( '' === $id ) {
			return '';
		}
		if ( 'youtube' === $provider ) {
			return 'https://www.youtube.com/embed/' . rawurlencode( $id );
		}
		if ( 'vimeo' === $provider ) {
			return 'https://player.vimeo.com/video/' . rawurlencode( $id );
		}
		return '';
	}
}
