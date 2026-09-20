import { useBlockProps } from '@wordpress/block-editor';

function getModeClass( colorMode ) {
	if ( colorMode === 'dark' ) return 'is-dark-mode';
	if ( colorMode === 'light' ) return 'is-light-mode';
	return '';
}

export default function save( { attributes } ) {
	const {
		dividerType = 'line',
		lineStyle = 'solid',
		shape = 'wave',
		icon = '✦',
		imageUrl = '',
		imageAlt = '',
		color = '',
		thickness = 2,
		width = 80,
		spacing = 28,
		colorMode = 'auto',
	} = attributes;
	const modeClass = getModeClass( colorMode );
	return (
		<div
			{ ...useBlockProps.save( {
				className: `stagekitwp-divider type-${ dividerType } style-${ lineStyle } shape-${ shape } ${ modeClass }`,
				'aria-hidden': 'true',
				style: {
					'--stagekitwp-divider-color': color,
					'--stagekitwp-divider-thickness': `${ thickness }px`,
					'--stagekitwp-divider-width': `${ width }%`,
					'--stagekitwp-divider-spacing': `${ spacing }px`,
				},
			} ) }
		>
			{ dividerType === 'icon' && (
				<span className="stagekitwp-divider__icon">{ icon }</span>
			) }
			{ dividerType === 'image' && imageUrl && (
				<img
					src={ imageUrl }
					alt={ imageAlt }
					className="stagekitwp-divider__image"
				/>
			) }
			<span className="stagekitwp-divider__line" />
		</div>
	);
}
