import {
	InspectorControls,
	MediaUpload,
	MediaUploadCheck,
	PanelColorSettings,
	useBlockProps,
} from '@wordpress/block-editor';
import {
	Button,
	PanelBody,
	Popover,
	RangeControl,
	SelectControl,
	TextControl,
} from '@wordpress/components';
import { useState } from '@wordpress/element';

const ICON_OPTIONS = [ '✦', '★', '◆', '✿', '❖', '♣', '♥', '☼', '•', '—' ];

function getModeClass( colorMode ) {
	if ( colorMode === 'dark' ) return 'is-dark-mode';
	if ( colorMode === 'light' ) return 'is-light-mode';
	return '';
}

export default function Edit( { attributes, setAttributes } ) {
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
	const props = useBlockProps( {
		className: `stagekitwp-divider type-${ dividerType } style-${ lineStyle } shape-${ shape } ${ modeClass }`,
		style: {
			'--stagekitwp-divider-color': color,
			'--stagekitwp-divider-thickness': `${ thickness }px`,
			'--stagekitwp-divider-width': `${ width }%`,
			'--stagekitwp-divider-spacing': `${ spacing }px`,
		},
	} );
	const [ isIconPickerOpen, setIsIconPickerOpen ] = useState( false );
	return (
		<>
			<InspectorControls>
				<PanelBody title="Divider type" initialOpen={ true }>
					<SelectControl
						label="Type"
						value={ dividerType }
						options={ [
							{ label: 'Line', value: 'line' },
							{ label: 'Icon', value: 'icon' },
							{ label: 'Image', value: 'image' },
							{ label: 'Shape preset', value: 'shape' },
						] }
						onChange={ ( value ) =>
							setAttributes( { dividerType: value } )
						}
					/>
					{ dividerType === 'line' && (
						<SelectControl
							label="Line style"
							value={ lineStyle }
							options={ [
								{ label: 'Solid', value: 'solid' },
								{ label: 'Dashed', value: 'dashed' },
								{ label: 'Dotted', value: 'dotted' },
								{ label: 'Double', value: 'double' },
							] }
							onChange={ ( value ) =>
								setAttributes( { lineStyle: value } )
							}
						/>
					) }
					{ dividerType === 'icon' && (
						<>
							<Button
								variant="secondary"
								onClick={ () =>
									setIsIconPickerOpen( ! isIconPickerOpen )
								}
							>
								Choose icon
							</Button>
							<span
								className="stagekitwp-divider__selected-icon"
								aria-live="polite"
							>
								{ icon }
							</span>
							{ isIconPickerOpen && (
								<Popover
									placement="bottom-start"
									onClose={ () =>
										setIsIconPickerOpen( false )
									}
								>
									<div
										className="stagekitwp-divider__icon-picker"
										role="listbox"
										aria-label="Divider icons"
									>
										{ ICON_OPTIONS.map( ( option ) => (
											<Button
												key={ option }
												className={
													option === icon
														? 'is-selected'
														: ''
												}
												aria-label={ `Use ${ option } icon` }
												onClick={ () => {
													setAttributes( {
														icon: option,
													} );
													setIsIconPickerOpen(
														false
													);
												} }
											>
												{ option }
											</Button>
										) ) }
									</div>
								</Popover>
							) }
							<TextControl
								label="Custom icon or symbol"
								value={ icon }
								onChange={ ( value ) =>
									setAttributes( { icon: value } )
								}
							/>
						</>
					) }
					{ dividerType === 'shape' && (
						<SelectControl
							label="Shape preset"
							value={ shape }
							options={ [
								{ label: 'Wave', value: 'wave' },
								{ label: 'Arch', value: 'arch' },
								{ label: 'Zigzag', value: 'zigzag' },
								{ label: 'Scallop', value: 'scallop' },
							] }
							onChange={ ( value ) =>
								setAttributes( { shape: value } )
							}
						/>
					) }
					{ dividerType === 'image' && (
						<MediaUploadCheck>
							<MediaUpload
								onSelect={ ( media ) =>
									setAttributes( {
										imageUrl: media.url,
										imageAlt: media.alt || '',
									} )
								}
								allowedTypes={ [ 'image' ] }
								render={ ( { open } ) => (
									<Button
										variant="secondary"
										onClick={ open }
									>
										{ imageUrl
											? 'Replace image'
											: 'Choose image' }
									</Button>
								) }
							/>
						</MediaUploadCheck>
					) }
				</PanelBody>
				<PanelBody title="Sizing" initialOpen={ false }>
					<RangeControl
						label="Width (%)"
						value={ width }
						min={ 10 }
						max={ 100 }
						onChange={ ( value ) =>
							setAttributes( { width: value } )
						}
					/>
					<RangeControl
						label="Thickness (px)"
						value={ thickness }
						min={ 1 }
						max={ 12 }
						onChange={ ( value ) =>
							setAttributes( { thickness: value } )
						}
					/>
					<RangeControl
						label="Vertical spacing (px)"
						value={ spacing }
						min={ 0 }
						max={ 120 }
						onChange={ ( value ) =>
							setAttributes( { spacing: value } )
						}
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
					<PanelColorSettings
						colorSettings={ [
							{
								label: 'Divider color',
								value: color,
								onChange: ( value ) =>
									setAttributes( { color: value || '' } ),
							},
						] }
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...props }>
				{ dividerType === 'icon' && (
					<span
						className="stagekitwp-divider__icon"
						aria-hidden="true"
					>
						{ icon }
					</span>
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
		</>
	);
}
