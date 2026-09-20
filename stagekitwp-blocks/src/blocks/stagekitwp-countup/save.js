import { useBlockProps } from '@wordpress/block-editor';

export default function save( { attributes } ) {

	const {
		preText = '',
		startValue = 0,
		number = 100,
		postText = '',
		colorMode = 'auto',
		duration = 2000,
		delay = 0,
		animationStyle = 'ease-out',
		separator = true,
		alignment = 'center',
		numberSize = 48,
		textSize = 24,
		marginTop = 0,
		marginBottom = 20,
		textColor = '',
		numberColor = '',
	} = attributes;

	const modeClass =
		colorMode === 'dark'
			? 'is-dark-mode'
			: colorMode === 'light'
				? 'is-light-mode'
				: '';

	return (
		<div
			{ ...useBlockProps.save( {
				className:
					`stagekitwp-countup ${ modeClass } align-${ alignment }`,

				'data-start': startValue,
				'data-target': number,
				'data-duration': duration,
				'data-delay': delay,
				'data-animation-style': animationStyle,
				'data-separator': separator,
				'data-color-mode': colorMode,

				style: {
					textAlign: alignment,

					marginTop: `${ marginTop }px`,
					marginBottom: `${ marginBottom }px`,

					'--stagekitwp-countup-number-size':
						`${ numberSize }px`,

					'--stagekitwp-countup-text-size':
						`${ textSize }px`,

					'--stagekitwp-countup-text-color':
						textColor,

					'--stagekitwp-countup-number-color':
						numberColor,
				},
			} ) }
		>

			{ preText && (
				<span className="stagekitwp-countup__pre">
					{ preText }
				</span>
			) }

			<span className="stagekitwp-countup__number">
				<span className="stagekitwp-countup__screen-reader-text">
					{ `${ preText }${ number }${ postText }` }
				</span>
				<span aria-hidden="true">
					{ startValue }
				</span>
			</span>

			{ postText && (
				<span className="stagekitwp-countup__post">
					{ postText }
				</span>
			) }

		</div>
	);
}