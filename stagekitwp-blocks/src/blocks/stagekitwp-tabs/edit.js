import { useBlockProps, InnerBlocks, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, ColorPalette, RangeControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { normalizeColorValue } from './color-utils';

const ALLOWED_BLOCKS = [ 'stagekitwp/tab-item' ];
const TEMPLATE = [
	[ 'stagekitwp/tab-item', { title: 'Tab 1' } ],
	[ 'stagekitwp/tab-item', { title: 'Tab 2' } ],
];

export default function Edit( { attributes, setAttributes, clientId } ) {
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

	const blockProps = useBlockProps( {
		className: `stagekit-tabs-wrapper style-${ tabStyle } align-${ tabAlignment } padding-${ tabPadding }`,
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
	const tabBlocks = useSelect(
		( select ) => select( 'core/block-editor' ).getBlocks( clientId ),
		[ clientId ]
	);

	return (
		<>
			<InspectorControls>
				<PanelBody title="Tab Style Preset" initialOpen={ true }>
					<SelectControl
						label="Preset Style"
						value={ tabStyle }
						options={ [
							{ label: 'Classic Underline', value: 'underline' },
							{ label: 'Pill Buttons', value: 'pills' },
							{ label: 'Card Box', value: 'cards' },
							{ label: 'Vertical Sidebar', value: 'vertical' },
							{ label: 'Floating Bubbles', value: 'bubbles' },
							{ label: 'Accent Bar', value: 'accent-bar' },
							{ label: 'Folder Tabs', value: 'folder' },
						] }
						onChange={ ( val ) => setAttributes( { tabStyle: val } ) }
					/>

					<SelectControl
						label="Tab Alignment"
						value={ tabAlignment }
						options={ [
							{ label: 'Left', value: 'flex-start' },
							{ label: 'Center', value: 'center' },
							{ label: 'Right', value: 'flex-end' },
							{ label: 'Full Width (Stretch)', value: 'stretch' },
						] }
						onChange={ ( val ) => setAttributes( { tabAlignment: val } ) }
					/>

					<SelectControl
						label="Button Padding"
						value={ tabPadding }
						options={ [
							{ label: 'Compact', value: 'small' },
							{ label: 'Medium', value: 'medium' },
							{ label: 'Spacious', value: 'large' },
						] }
						onChange={ ( val ) => setAttributes( { tabPadding: val } ) }
					/>

					<RangeControl
						label="Border Radius (px)"
						value={ borderRadius }
						onChange={ ( val ) => setAttributes( { borderRadius: val } ) }
						min={ 0 }
						max={ 30 }
					/>
				</PanelBody>

				<PanelBody title="Tab Colors" initialOpen={ false }>
					<p><strong>Active Tab Accent / Text</strong></p>
					<ColorPalette
						value={ activeColor }
						onChange={ ( val ) => setAttributes( { activeColor: normalizeColorValue( val, '#2563eb' ) } ) }
					/>

					<p><strong>Active Tab Background</strong></p>
					<ColorPalette
						value={ activeBgColor }
						onChange={ ( val ) => setAttributes( { activeBgColor: normalizeColorValue( val, '#ffffff' ) } ) }
					/>

					<p><strong>Inactive Tab Text</strong></p>
					<ColorPalette
						value={ inactiveColor }
						onChange={ ( val ) => setAttributes( { inactiveColor: normalizeColorValue( val, '#64748b' ) } ) }
					/>

					<p><strong>Inactive Tab Background</strong></p>
					<ColorPalette
						value={ inactiveBgColor }
						onChange={ ( val ) => setAttributes( { inactiveBgColor: normalizeColorValue( val, 'transparent' ) } ) }
					/>

					<p><strong>Hover Tab Text</strong></p>
					<ColorPalette
						value={ hoverColor }
						onChange={ ( val ) => setAttributes( { hoverColor: normalizeColorValue( val, '#1e293b' ) } ) }
					/>

					<p><strong>Hover Tab Background</strong></p>
					<ColorPalette
						value={ hoverBgColor }
						onChange={ ( val ) => setAttributes( { hoverBgColor: normalizeColorValue( val, '#f1f5f9' ) } ) }
					/>

					{ tabStyle === 'folder' && (
						<>
							<p><strong>Folder Header Background</strong></p>
							<ColorPalette
								value={ folderHeaderBg }
								onChange={ ( val ) => setAttributes( { folderHeaderBg: normalizeColorValue( val, '#f1f5f9' ) } ) }
							/>
						</>
					) }
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<div className="stagekit-tabs-nav stagekit-tabs-nav-editor" role="tablist">
					{ tabBlocks.map( ( tabBlock, index ) => (
						<button
							key={ tabBlock.clientId }
							type="button"
							className={ `stagekit-tab-btn ${ index === 0 ? 'active' : '' }` }
							style={ index === 0 ? undefined : {
								'--stagekit-preview-inactive-color': normalizedInactiveColor,
								'--stagekit-preview-inactive-bg': normalizedInactiveBgColor,
							} }
							onClick={ ( event ) => event.preventDefault() }
						>
							{ tabBlock.attributes.title || `Tab ${ index + 1 }` }
						</button>
					) ) }
				</div>
				<InnerBlocks
					allowedBlocks={ ALLOWED_BLOCKS }
					template={ TEMPLATE }
					renderAppender={ InnerBlocks.ButtonBlockAppender }
				/>
			</div>
		</>
	);
}