import { useBlockProps } from '@wordpress/block-editor';
import { FundraisingMeter } from './meter';

function getModeClass( colorMode ) {
	if ( colorMode === 'dark' ) return 'is-dark-mode';
	if ( colorMode === 'light' ) return 'is-light-mode';
	return '';
}

export default function save( { attributes } ) {
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
	const percentage =
		goal > 0 ? Math.min( Math.max( ( current / goal ) * 100, 0 ), 100 ) : 0;
	const modeClass = getModeClass( colorMode );
	return (
		<div
			{ ...useBlockProps.save( {
				className: `stagekitwp-thermometer theme-${ theme } ${ modeClass }`,
				role: 'progressbar',
				'aria-valuemin': 0,
				'aria-valuemax': goal,
				'aria-valuenow': current,
				'aria-label': title,
				style: {
					'--stagekitwp-thermometer-accent': accentColor,
					'--stagekitwp-thermometer-percent': `${ percentage }%`,
					'--stagekitwp-thermometer-progress': `${ percentage }%`,
				},
			} ) }
		>
			<h3 className="stagekitwp-thermometer__title">{ title }</h3>
			<div className="stagekitwp-thermometer__visual">
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
	);
}
