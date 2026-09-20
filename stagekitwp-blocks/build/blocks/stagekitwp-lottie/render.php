<?php
/**
 * StageKitWP Lottie Render Template
 */

$light_animation = ! empty( $attributes['lightAnimationUrl'] )
	? $attributes['lightAnimationUrl']
	: '';

$dark_animation = ! empty( $attributes['darkAnimationUrl'] )
	? $attributes['darkAnimationUrl']
	: '';

$color_mode = ! empty( $attributes['colorMode'] )
	? $attributes['colorMode']
	: 'auto';

$autoplay = isset( $attributes['autoplay'] )
	? (bool) $attributes['autoplay']
	: true;

$loop = isset( $attributes['loop'] )
	? (bool) $attributes['loop']
	: true;

$speed = isset( $attributes['speed'] )
	? (float) $attributes['speed']
	: 1;

$width = isset( $attributes['width'] )
	? intval( $attributes['width'] )
	: 100;

$max_width = isset( $attributes['maxWidth'] )
	? intval( $attributes['maxWidth'] )
	: 500;

$aspect_ratio = ! empty( $attributes['aspectRatio'] )
	? $attributes['aspectRatio']
	: '1 / 1';

$classes = array(
	'stagekitwp-lottie-wrapper',
);

if ( 'light' === $color_mode ) {
	$classes[] = 'is-light-mode';
}

if ( 'dark' === $color_mode ) {
	$classes[] = 'is-dark-mode';
}

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => implode( ' ', $classes ),
		'style' => sprintf(
			'--stagekitwp-lottie-width:%1$s%%;--stagekitwp-lottie-max-width:%2$spx;--stagekitwp-lottie-ratio:%3$s;',
			$width,
			$max_width,
			esc_attr( $aspect_ratio )
		),
	)
);
?>

<div
	<?php echo $wrapper_attributes; ?>
	data-light="<?php echo esc_url( $light_animation ); ?>"
	data-dark="<?php echo esc_url( $dark_animation ); ?>"
	data-color-mode="<?php echo esc_attr( $color_mode ); ?>"
	data-autoplay="<?php echo $autoplay ? 'true' : 'false'; ?>"
	data-loop="<?php echo $loop ? 'true' : 'false'; ?>"
	data-speed="<?php echo esc_attr( $speed ); ?>"
	data-reload-on-theme-change="true"
>
	<div
		class="stagekitwp-lottie-player"
		aria-hidden="true"
	></div>

	<?php if ( empty( $light_animation ) && empty( $dark_animation ) ) : ?>
		<div class="stagekitwp-lottie-empty">
			<?php esc_html_e(
				'No animation selected.',
				'stagekitwp-blocks'
			); ?>
		</div>
	<?php endif; ?>
</div>
