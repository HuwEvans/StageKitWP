import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';
import { normalizeColorValue } from './color-utils';

export default function Save( { attributes } ) {
	const {
		tabStyle = 'underline',
		tabAlignment = 'flex-start',
		activeColor = '#2563eb',
		activeBgColor = '#ffffff',
		inactiveColor = '#64748b',
		inactiveBgColor = 'transparent',
		hoverColor = '#1e293b',
		hoverBgColor = '#f1f5f9',
		folderHeaderBg = '#f1f5f9',
		tabPadding = 'medium',
		borderRadius = 8,
	} = attributes;
	const normalizedActiveColor = normalizeColorValue( activeColor, '#2563eb' );
	const normalizedActiveBgColor = normalizeColorValue( activeBgColor, '#ffffff' );
	const normalizedInactiveColor = normalizeColorValue( inactiveColor, '#64748b' );
	const normalizedInactiveBgColor = normalizeColorValue( inactiveBgColor, 'transparent' );
	const normalizedHoverColor = normalizeColorValue( hoverColor, '#1e293b' );
	const normalizedHoverBgColor = normalizeColorValue( hoverBgColor, '#f1f5f9' );
	const normalizedFolderHeaderBg = normalizeColorValue( folderHeaderBg, '#f1f5f9' );

	const blockProps = useBlockProps.save( {
		className: `stagekit-tabs-wrapper style-${ tabStyle } align-${ tabAlignment } padding-${ tabPadding }`,
		'data-style': tabStyle,
		'data-align': tabAlignment,
		'data-padding': tabPadding,
		'data-active-color': normalizedActiveColor,
		'data-active-bg': normalizedActiveBgColor,
		'data-inactive-color': normalizedInactiveColor,
		'data-inactive-bg': normalizedInactiveBgColor,
		'data-hover-color': normalizedHoverColor,
		'data-hover-bg': normalizedHoverBgColor,
		'data-folder-header-bg': normalizedFolderHeaderBg,
		'data-radius': `${ borderRadius }px`,
		style: {
			'--stagekit-active-color': normalizedActiveColor,
			'--stagekit-active-bg': normalizedActiveBgColor,
			'--stagekit-inactive-color': normalizedInactiveColor,
			'--stagekit-inactive-bg': normalizedInactiveBgColor,
			'--stagekit-hover-color': normalizedHoverColor,
			'--stagekit-hover-bg': normalizedHoverBgColor,
			'--stagekit-folder-header-bg': normalizedFolderHeaderBg,
			'--stagekit-radius': `${ borderRadius }px`,
		},
	} );

	return (
		<div { ...blockProps }>
			<InnerBlocks.Content />
		</div>
	);
}