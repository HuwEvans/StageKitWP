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

function getPreviewParts( targetDate ) {
	const target = targetDate ? new Date( targetDate ).getTime() : Date.now();
	const remaining = Math.max( target - Date.now(), 0 );
	return {
		days: Math.floor( remaining / 86400000 ),
		hours: Math.floor( remaining / 3600000 ) % 24,
		minutes: Math.floor( remaining / 60000 ) % 60,
		seconds: Math.floor( remaining / 1000 ) % 60,
	};
}

export default function Edit( { attributes, setAttributes } ) {
	const {
		targetDate = '',
		expiredText = 'The event has started.',
		showDays = true,
		showHours = true,
		showMinutes = true,
		showSeconds = true,
		alignment = 'center',
		colorMode = 'auto',
		numberSize = 48,
		labelSize = 14,
		numberColor = '',
		labelColor = '',
	} = attributes;

	const parts = getPreviewParts( targetDate );
	const units = [
		[ 'days', 'Days', showDays ],
		[ 'hours', 'Hours', showHours ],
		[ 'minutes', 'Minutes', showMinutes ],
		[ 'seconds', 'Seconds', showSeconds ],
	].filter( ( unit ) => unit[ 2 ] );
	const modeClass = colorMode === 'dark'
		? 'is-dark-mode'
		: colorMode === 'light' ? 'is-light-mode' : '';

	const blockProps = useBlockProps( {
		className: `stagekitwp-countdown ${ modeClass } align-${ alignment }`,
		style: {
			textAlign: alignment,
			'--stagekitwp-countdown-number-size': `${ numberSize }px`,
			'--stagekitwp-countdown-label-size': `${ labelSize }px`,
			'--stagekitwp-countdown-number-color': numberColor,
			'--stagekitwp-countdown-label-color': labelColor,
		},
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody title="Countdown" initialOpen={ true }>
					<TextControl
						label="Target date and time"
						type="datetime-local"
						value={ targetDate }
						onChange={ ( value ) => setAttributes( { targetDate: value } ) }
					/>
					<TextControl
						label="Expired message"
						value={ expiredText }
						onChange={ ( value ) => setAttributes( { expiredText: value } ) }
					/>
					<SelectControl
						label="Alignment"
						value={ alignment }
						options={ [
							{ label: 'Left', value: 'left' },
							{ label: 'Center', value: 'center' },
							{ label: 'Right', value: 'right' },
						] }
						onChange={ ( value ) => setAttributes( { alignment: value } ) }
					/>
				</PanelBody>
				<PanelBody title="Visible units" initialOpen={ false }>
					<ToggleControl label="Days" checked={ showDays } onChange={ ( value ) => setAttributes( { showDays: value } ) } />
					<ToggleControl label="Hours" checked={ showHours } onChange={ ( value ) => setAttributes( { showHours: value } ) } />
					<ToggleControl label="Minutes" checked={ showMinutes } onChange={ ( value ) => setAttributes( { showMinutes: value } ) } />
					<ToggleControl label="Seconds" checked={ showSeconds } onChange={ ( value ) => setAttributes( { showSeconds: value } ) } />
				</PanelBody>
				<PanelBody title="Typography" initialOpen={ false }>
					<RangeControl label="Number size" value={ numberSize } min={ 20 } max={ 120 } onChange={ ( value ) => setAttributes( { numberSize: value } ) } />
					<RangeControl label="Label size" value={ labelSize } min={ 10 } max={ 40 } onChange={ ( value ) => setAttributes( { labelSize: value } ) } />
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
						onChange={ ( value ) => setAttributes( { colorMode: value } ) }
					/>
					<PanelColorSettings
						colorSettings={ [
							{ value: numberColor, onChange: ( value ) => setAttributes( { numberColor: value || '' } ), label: 'Number color' },
							{ value: labelColor, onChange: ( value ) => setAttributes( { labelColor: value || '' } ), label: 'Label color' },
						] }
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<div className="stagekitwp-countdown__units" aria-hidden="true">
					{ units.map( ( [ key, label ] ) => (
						<div className="stagekitwp-countdown__unit" key={ key }>
							<span className="stagekitwp-countdown__number">{ String( parts[ key ] ).padStart( 2, '0' ) }</span>
							<span className="stagekitwp-countdown__label">{ label }</span>
						</div>
					) ) }
				</div>
				<p className="stagekitwp-countdown__preview-note">{ targetDate ? `Counting down to ${ targetDate }` : 'Choose a target date and time' }</p>
			</div>
		</>
	);
}
