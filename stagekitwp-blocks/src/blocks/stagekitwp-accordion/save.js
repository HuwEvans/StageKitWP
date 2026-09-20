import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';

function getModeClass( colorMode ) {
	if ( colorMode === 'dark' ) return 'is-dark-mode';
	if ( colorMode === 'light' ) return 'is-light-mode';
	return '';
}

export default function save( { attributes } ) {
	const {
		allowMultiple = false,
		openFirst = true,
		stylePreset = 'minimal',
		colorMode = 'auto',
		headerTextLight = '#1e293b', headerBackgroundLight = '#f8fafc', bodyTextLight = '#334155', bodyBackgroundLight = '#ffffff', borderLight = '#cbd5e1', accentLight = '#b42318',
		headerTextDark = '#f8fafc', headerBackgroundDark = '#1e293b', bodyTextDark = '#e2e8f0', bodyBackgroundDark = '#0f172a', borderDark = '#475569', accentDark = '#f59e0b',
	} = attributes;
	const modeClass = getModeClass( colorMode );
	return (
		<div
			{ ...useBlockProps.save( {
				className: `stagekitwp-accordion preset-${ stylePreset } ${ modeClass }`,
				'data-allow-multiple': allowMultiple,
				'data-open-first': openFirst,
				style: {
					'--stagekitwp-accordion-header-text-light': headerTextLight, '--stagekitwp-accordion-header-bg-light': headerBackgroundLight, '--stagekitwp-accordion-body-text-light': bodyTextLight, '--stagekitwp-accordion-body-bg-light': bodyBackgroundLight, '--stagekitwp-accordion-border-light': borderLight, '--stagekitwp-accordion-accent-light': accentLight,
					'--stagekitwp-accordion-header-text-dark': headerTextDark, '--stagekitwp-accordion-header-bg-dark': headerBackgroundDark, '--stagekitwp-accordion-body-text-dark': bodyTextDark, '--stagekitwp-accordion-body-bg-dark': bodyBackgroundDark, '--stagekitwp-accordion-border-dark': borderDark, '--stagekitwp-accordion-accent-dark': accentDark,
				},
			} ) }
		>
			<InnerBlocks.Content />
		</div>
	);
}
