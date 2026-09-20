import { useBlockProps } from '@wordpress/block-editor';

export default function save( { attributes } ) {
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
	const modeClass = colorMode === 'dark'
		? 'is-dark-mode'
		: colorMode === 'light' ? 'is-light-mode' : '';
	const units = [
		[ 'days', 'Days', showDays ],
		[ 'hours', 'Hours', showHours ],
		[ 'minutes', 'Minutes', showMinutes ],
		[ 'seconds', 'Seconds', showSeconds ],
	].filter( ( unit ) => unit[ 2 ] );

	return (
		<div
			{ ...useBlockProps.save( {
				className: `stagekitwp-countdown ${ modeClass } align-${ alignment }`,
				'aria-label': targetDate ? `Countdown to ${ targetDate }` : 'Countdown',
				'data-target': targetDate,
				'data-expired-text': expiredText,
				style: {
					textAlign: alignment,
					'--stagekitwp-countdown-number-size': `${ numberSize }px`,
					'--stagekitwp-countdown-label-size': `${ labelSize }px`,
					'--stagekitwp-countdown-number-color': numberColor,
					'--stagekitwp-countdown-label-color': labelColor,
				},
			} ) }
		>
			<div className="stagekitwp-countdown__units" aria-live="polite">
				{ units.map( ( [ key, label ] ) => (
					<div className="stagekitwp-countdown__unit" key={ key } data-unit={ key }>
						<span className="stagekitwp-countdown__number">00</span>
						<span className="stagekitwp-countdown__label">{ label }</span>
					</div>
				) ) }
			</div>
			<p className="stagekitwp-countdown__expired" hidden>{ expiredText }</p>
		</div>
	);
}
