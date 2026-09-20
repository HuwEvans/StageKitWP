import {
	InspectorControls,
	PanelColorSettings,
	useBlockProps,
} from '@wordpress/block-editor';
import {
	PanelBody,
	TextControl,
	ToggleControl,
	SelectControl,
} from '@wordpress/components';
import { FundraisingMeter } from './meter';

function progress( current, goal ) {
	return goal > 0
		? Math.min( Math.max( ( current / goal ) * 100, 0 ), 100 )
		: 0;
}

function getModeClass( colorMode ) {
	if ( colorMode === 'dark' ) return 'is-dark-mode';
	if ( colorMode === 'light' ) return 'is-light-mode';
	return '';
}

export default function Edit( { attributes, setAttributes } ) {
	const {
		title = 'Our fundraising goal',
		current = 2500,
		goal = 10000,
		currency = '$',
		theme = 'horizontal',
		showAmounts = true,
		showPercentage = true,
		colorMode = 'auto',
		accentColor = '#c2410c',
	} = attributes;
	const percentage = progress( current, goal );
	const modeClass = getModeClass( colorMode );
	const props = useBlockProps( {
		className: `stagekitwp-thermometer theme-${ theme } ${ modeClass }`,
		style: { '--stagekitwp-thermometer-accent': accentColor },
	} );
	return (
		<>
			<InspectorControls>
				<PanelBody title="Campaign" initialOpen={ true }>
					<TextControl
						label="Title"
						value={ title }
						onChange={ ( value ) =>
							setAttributes( { title: value } )
						}
					/>
					<TextControl
						label="Current amount"
						type="number"
						value={ current }
						onChange={ ( value ) =>
							setAttributes( { current: Number( value ) || 0 } )
						}
					/>
					<TextControl
						label="Goal amount"
						type="number"
						value={ goal }
						onChange={ ( value ) =>
							setAttributes( { goal: Number( value ) || 0 } )
						}
					/>
					<TextControl
						label="Currency symbol"
						value={ currency }
						onChange={ ( value ) =>
							setAttributes( { currency: value } )
						}
					/>
				</PanelBody>
				<PanelBody title="Appearance" initialOpen={ false }>
					<SelectControl
						label="Theme"
						value={ theme }
						options={ [
							{ label: 'Horizontal bar', value: 'horizontal' },
							{ label: 'Classic vertical', value: 'classic' },
							{ label: 'Ring', value: 'ring' },
							{ label: 'Stage curtain', value: 'stage' },
						] }
						onChange={ ( value ) =>
							setAttributes( { theme: value } )
						}
					/>
					<ToggleControl
						label="Show amounts"
						checked={ showAmounts }
						onChange={ ( value ) =>
							setAttributes( { showAmounts: value } )
						}
					/>
					<ToggleControl
						label="Show percentage"
						checked={ showPercentage }
						onChange={ ( value ) =>
							setAttributes( { showPercentage: value } )
						}
					/>
					<PanelColorSettings
						colorSettings={ [
							{
								label: 'Campaign accent',
								value: accentColor,
								onChange: ( value ) =>
									setAttributes( {
										accentColor: value || '#c2410c',
									} ),
							},
						] }
					/>
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
			<div { ...props }>
				<h3 className="stagekitwp-thermometer__title">{ title }</h3>
				<div className="stagekitwp-thermometer__visual" aria-hidden="true">
					<FundraisingMeter theme={ theme } percentage={ percentage } />
				</div>
				<div className="stagekitwp-thermometer__summary">
					{ showAmounts && (
						<span>
							{ currency }
							{ current.toLocaleString() } / { currency }
							{ goal.toLocaleString() }
						</span>
					) }
					{ showPercentage && (
						<strong>{ Math.round( percentage ) }%</strong>
					) }
				</div>
			</div>
		</>
	);
}
