import {
	InspectorControls,
	PanelColorSettings,
	useBlockProps,
} from '@wordpress/block-editor';

import {
	PanelBody,
	TextControl,
	ToggleControl,
	RangeControl,
	SelectControl,
} from '@wordpress/components';

export default function Edit( {
	attributes,
	setAttributes,
} ) {
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

	const displayNumber = separator
		? Number( number ).toLocaleString()
		: number;

	const displayStartValue = separator
		? Number( startValue ).toLocaleString()
		: startValue;

	const blockProps = useBlockProps( {
		className: `stagekitwp-countup align-${ alignment }`,
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
	} );

	return (
		<>
			<InspectorControls>

				<PanelBody
					title="Counter Content"
					initialOpen={ true }
				>

					<TextControl
						label="Pre Text"
						value={ preText }
						onChange={ ( value ) =>
							setAttributes( {
								preText: value,
							} )
						}
					/>

					<TextControl
						label="Start Value"
						type="number"
						value={ startValue }
						onChange={ ( value ) =>
							setAttributes( {
								startValue:
									parseInt(
										value,
										10
									) || 0,
							} )
						}
					/>

					<TextControl
						label="Target Number"
						type="number"
						value={ number }
						onChange={ ( value ) =>
							setAttributes( {
								number:
									parseInt(
										value,
										10
									) || 0,
							} )
						}
					/>

					<TextControl
						label="Post Text"
						value={ postText }
						onChange={ ( value ) =>
							setAttributes( {
								postText: value,
							} )
						}
					/>

				</PanelBody>

				<PanelBody
					title="Animation"
					initialOpen={ false }
				>

					<RangeControl
						label="Animation Duration (ms)"
						value={ duration }
						min={ 500 }
						max={ 10000 }
						step={ 100 }
						onChange={ ( value ) =>
							setAttributes( {
								duration: value,
							} )
						}
					/>

					<RangeControl
						label="Animation Delay (ms)"
						value={ delay }
						min={ 0 }
						max={ 5000 }
						step={ 100 }
						onChange={ ( value ) =>
							setAttributes( {
								delay: value,
							} )
						}
					/>

					<SelectControl
						label="Animation Style"
						value={ animationStyle }
						options={ [
							{
								label: 'Linear',
								value: 'linear',
							},
							{
								label: 'Ease Out',
								value: 'ease-out',
							},
							{
								label: 'Ease In Out',
								value: 'ease-in-out',
							},
							{
								label: 'Bounce',
								value: 'bounce',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( {
								animationStyle:
									value,
							} )
						}
					/>

				</PanelBody>

				<PanelBody
					title="Formatting"
					initialOpen={ false }
				>

					<ToggleControl
						label="Use Thousands Separator"
						checked={ separator }
						onChange={ ( value ) =>
							setAttributes( {
								separator: value,
							} )
						}
					/>

					<SelectControl
						label="Alignment"
						value={ alignment }
						options={ [
							{
								label: 'Left',
								value: 'left',
							},
							{
								label: 'Center',
								value: 'center',
							},
							{
								label: 'Right',
								value: 'right',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( {
								alignment: value,
							} )
						}
					/>

				</PanelBody>

				<PanelBody
					title="Typography"
					initialOpen={ false }
				>

					<RangeControl
						label="Number Size"
						value={ numberSize }
						min={ 20 }
						max={ 150 }
						step={ 1 }
						onChange={ ( value ) =>
							setAttributes( {
								numberSize: value,
							} )
						}
					/>

					<RangeControl
						label="Text Size"
						value={ textSize }
						min={ 10 }
						max={ 72 }
						step={ 1 }
						onChange={ ( value ) =>
							setAttributes( {
								textSize: value,
							} )
						}
					/>

				</PanelBody>

				<PanelBody
					title="Spacing"
					initialOpen={ false }
				>

					<RangeControl
						label="Margin Top"
						value={ marginTop }
						min={ 0 }
						max={ 200 }
						step={ 5 }
						onChange={ ( value ) =>
							setAttributes( {
								marginTop: value,
							} )
						}
					/>

					<RangeControl
						label="Margin Bottom"
						value={ marginBottom }
						min={ 0 }
						max={ 200 }
						step={ 5 }
						onChange={ ( value ) =>
							setAttributes( {
								marginBottom: value,
							} )
						}
					/>

				</PanelBody>

				<PanelBody
					title="Theme Mode"
					initialOpen={ false }
				>

					<SelectControl
						label="Color Mode"
						value={ colorMode }
						options={ [
							{
								label: 'Auto',
								value: 'auto',
							},
							{
								label: 'Light',
								value: 'light',
							},
							{
								label: 'Dark',
								value: 'dark',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( {
								colorMode: value,
							} )
						}
					/>

				</PanelBody>

				<PanelColorSettings
					title="Colors"
					colorSettings={ [
						{
							label: 'Text Color',
							value: textColor,
							onChange: (
								value
							) =>
								setAttributes(
									{
										textColor:
											value,
									}
								),
						},
						{
							label: 'Number Color',
							value: numberColor,
							onChange: (
								value
							) =>
								setAttributes(
									{
										numberColor:
											value,
									}
								),
						},
					] }
				/>

			</InspectorControls>

			<div { ...blockProps }>

				<div className="stagekitwp-countup-editor-preview">

					{ preText && (
						<div className="stagekitwp-countup__pre">
							{ preText }
						</div>
					) }

					<div className="stagekitwp-countup__number">
						{ displayStartValue }
						{' → '}
						{ displayNumber }
					</div>

					{ postText && (
						<div className="stagekitwp-countup__post">
							{ postText }
						</div>
					) }

					<div
						style={ {
							marginTop: '10px',
							fontSize: '12px',
							opacity: 0.7,
						} }
					>
						{ animationStyle } •
						{' '}
						{ duration }ms
						{ delay > 0 &&
							` • ${ delay }ms delay` }
					</div>

				</div>

			</div>

		</>
	);
}