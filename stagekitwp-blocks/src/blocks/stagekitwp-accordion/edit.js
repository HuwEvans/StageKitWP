import {
	InnerBlocks,
	InspectorControls,
	PanelColorSettings,
	useBlockProps,
} from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	ToggleControl,
} from '@wordpress/components';

function getModeClass( colorMode ) {
	if ( colorMode === 'dark' ) return 'is-dark-mode';
	if ( colorMode === 'light' ) return 'is-light-mode';
	return '';
}

const ALLOWED_BLOCKS = [ 'stagekitwp/accordion-item' ];
const TEMPLATE = [
	[ 'stagekitwp/accordion-item', { title: 'Accordion item 1', open: true } ],
	[ 'stagekitwp/accordion-item', { title: 'Accordion item 2' } ],
];

export default function Edit( { attributes, setAttributes } ) {
	const {
		allowMultiple = false,
		openFirst = true,
		stylePreset = 'minimal',
		colorMode = 'auto',
		headerTextLight = '#1e293b',
		headerBackgroundLight = '#f8fafc',
		bodyTextLight = '#334155',
		bodyBackgroundLight = '#ffffff',
		borderLight = '#cbd5e1',
		accentLight = '#b42318',
		headerTextDark = '#f8fafc',
		headerBackgroundDark = '#1e293b',
		bodyTextDark = '#e2e8f0',
		bodyBackgroundDark = '#0f172a',
		borderDark = '#475569',
		accentDark = '#f59e0b',
	} = attributes;
	const modeClass = getModeClass( colorMode );
	const blockProps = useBlockProps( {
		className: `stagekitwp-accordion preset-${ stylePreset } ${ modeClass }`,
		style: {
			'--stagekitwp-accordion-header-text-light': headerTextLight,
			'--stagekitwp-accordion-header-bg-light': headerBackgroundLight,
			'--stagekitwp-accordion-body-text-light': bodyTextLight,
			'--stagekitwp-accordion-body-bg-light': bodyBackgroundLight,
			'--stagekitwp-accordion-border-light': borderLight,
			'--stagekitwp-accordion-accent-light': accentLight,
			'--stagekitwp-accordion-header-text-dark': headerTextDark,
			'--stagekitwp-accordion-header-bg-dark': headerBackgroundDark,
			'--stagekitwp-accordion-body-text-dark': bodyTextDark,
			'--stagekitwp-accordion-body-bg-dark': bodyBackgroundDark,
			'--stagekitwp-accordion-border-dark': borderDark,
			'--stagekitwp-accordion-accent-dark': accentDark,
		},
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody title="Accordion behavior" initialOpen={ true }>
					<SelectControl
						label="Header style"
						value={ stylePreset }
						options={ [
							{ label: 'Minimal', value: 'minimal' },
							{ label: 'Cards', value: 'cards' },
							{ label: 'Underline', value: 'underline' },
							{ label: 'Pill', value: 'pill' },
							{ label: 'Stage', value: 'stage' },
							{ label: 'Boxed', value: 'boxed' },
						] }
						onChange={ ( value ) => setAttributes( { stylePreset: value } ) }
					/>
					<ToggleControl
						label="Allow multiple panels open"
						checked={ allowMultiple }
						onChange={ ( value ) =>
							setAttributes( { allowMultiple: value } )
						}
					/>
					<ToggleControl
						label="Open first panel"
						checked={ openFirst }
						onChange={ ( value ) =>
							setAttributes( { openFirst: value } )
						}
					/>
				</PanelBody>
				<PanelBody title="Light colors" initialOpen={ false }>
					<PanelColorSettings colorSettings={ [
						{ label: 'Header text', value: headerTextLight, onChange: ( value ) => setAttributes( { headerTextLight: value || '' } ) },
						{ label: 'Header background', value: headerBackgroundLight, onChange: ( value ) => setAttributes( { headerBackgroundLight: value || '' } ) },
						{ label: 'Body text', value: bodyTextLight, onChange: ( value ) => setAttributes( { bodyTextLight: value || '' } ) },
						{ label: 'Body background', value: bodyBackgroundLight, onChange: ( value ) => setAttributes( { bodyBackgroundLight: value || '' } ) },
						{ label: 'Border', value: borderLight, onChange: ( value ) => setAttributes( { borderLight: value || '' } ) },
						{ label: 'Accent', value: accentLight, onChange: ( value ) => setAttributes( { accentLight: value || '' } ) },
					] } />
				</PanelBody>
				<PanelBody title="Dark colors" initialOpen={ false }>
					<PanelColorSettings colorSettings={ [
						{ label: 'Header text', value: headerTextDark, onChange: ( value ) => setAttributes( { headerTextDark: value || '' } ) },
						{ label: 'Header background', value: headerBackgroundDark, onChange: ( value ) => setAttributes( { headerBackgroundDark: value || '' } ) },
						{ label: 'Body text', value: bodyTextDark, onChange: ( value ) => setAttributes( { bodyTextDark: value || '' } ) },
						{ label: 'Body background', value: bodyBackgroundDark, onChange: ( value ) => setAttributes( { bodyBackgroundDark: value || '' } ) },
						{ label: 'Border', value: borderDark, onChange: ( value ) => setAttributes( { borderDark: value || '' } ) },
						{ label: 'Accent', value: accentDark, onChange: ( value ) => setAttributes( { accentDark: value || '' } ) },
					] } />
				</PanelBody>
				<PanelBody title="Theme mode" initialOpen={ false }>
					<SelectControl
						label="Color mode"
						value={ colorMode }
						options={ [
							{ label: 'Automatic', value: 'auto' },
							{ label: 'Light', value: 'light' },
							{ label: 'Dark', value: 'dark' },
						] }
						onChange={ ( value ) =>
							setAttributes( { colorMode: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<InnerBlocks
					allowedBlocks={ ALLOWED_BLOCKS }
					template={ TEMPLATE }
					renderAppender={ InnerBlocks.ButtonBlockAppender }
				/>
			</div>
		</>
	);
}
