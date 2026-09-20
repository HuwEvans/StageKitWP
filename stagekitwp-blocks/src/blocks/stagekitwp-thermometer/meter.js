export function FundraisingMeter( { theme, percentage } ) {
	const progress = Math.max( 0, Math.min( percentage, 100 ) );
	const gradientId = `stagekitwp-meter-gradient-${ theme }`;

	if ( theme === 'classic' ) {
		const fillHeight = 132 * ( progress / 100 );
		return (
			<svg className="stagekitwp-thermometer__svg stagekitwp-thermometer__svg--classic" viewBox="0 0 100 230" role="img" aria-label={ `${ Math.round( progress ) } percent raised` }>
				<defs>
					<linearGradient id={ gradientId } x1="0" x2="1" y1="0" y2="0">
						<stop offset="0" stopColor="color-mix(in srgb, var(--stagekitwp-thermometer-accent) 70%, #7f1d1d)" />
						<stop offset="0.55" stopColor="var(--stagekitwp-thermometer-accent)" />
						<stop offset="1" stopColor="color-mix(in srgb, var(--stagekitwp-thermometer-accent) 65%, white)" />
					</linearGradient>
				</defs>
				<rect x="38" y="18" width="24" height="150" rx="12" className="stagekitwp-thermometer__track" />
				<rect x="42" y={ 168 - fillHeight } width="16" height={ fillHeight } rx="8" fill={ `url(#${ gradientId })` } />
				<circle cx="50" cy="184" r="29" className="stagekitwp-thermometer__bulb-face" />
				<circle cx="50" cy="184" r="21" fill={ `url(#${ gradientId })` } />
				<g className="stagekitwp-thermometer__ticks">
					{ [ 0, 1, 2, 3, 4, 5 ].map( ( tick ) => <line key={ tick } x1="65" x2={ tick % 2 === 0 ? 78 : 73 } y1={ 35 + tick * 24 } y2={ 35 + tick * 24 } /> ) }
				</g>
			</svg>
		);
	}

	if ( theme === 'ring' ) {
		const circumference = 2 * Math.PI * 48;
		return (
			<svg className="stagekitwp-thermometer__svg stagekitwp-thermometer__svg--ring" viewBox="0 0 120 120" role="img" aria-label={ `${ Math.round( progress ) } percent raised` }>
				<circle cx="60" cy="60" r="48" className="stagekitwp-thermometer__ring-track" />
				<circle cx="60" cy="60" r="48" className="stagekitwp-thermometer__ring-progress" strokeDasharray={ `${ ( circumference * progress ) / 100 } ${ circumference }` } />
			</svg>
		);
	}

	if ( theme === 'stage' ) {
		return (
			<svg className="stagekitwp-thermometer__svg stagekitwp-thermometer__svg--stage" viewBox="0 0 720 96" role="img" aria-label={ `${ Math.round( progress ) } percent raised` }>
				<rect x="8" y="22" width="704" height="52" rx="8" className="stagekitwp-thermometer__stage-frame" />
				{ Array.from( { length: 12 }, ( _, index ) => {
					const segmentProgress = progress - index * ( 100 / 12 );
					return <rect key={ index } x={ 22 + index * 57 } y="35" width="42" height="26" rx="3" className={ segmentProgress > 0 ? 'stagekitwp-thermometer__stage-segment is-filled' : 'stagekitwp-thermometer__stage-segment' } />;
				} ) }
			</svg>
		);
	}

	return (
		<svg className="stagekitwp-thermometer__svg stagekitwp-thermometer__svg--horizontal" viewBox="0 0 720 104" role="img" aria-label={ `${ Math.round( progress ) } percent raised` }>
			<defs>
				<linearGradient id={ gradientId } x1="0" x2="1" y1="0" y2="0">
					<stop offset="0" stopColor="color-mix(in srgb, var(--stagekitwp-thermometer-accent) 70%, #7f1d1d)" />
					<stop offset="0.5" stopColor="var(--stagekitwp-thermometer-accent)" />
					<stop offset="1" stopColor="color-mix(in srgb, var(--stagekitwp-thermometer-accent) 65%, white)" />
				</linearGradient>
			</defs>
			<rect x="8" y="26" width="704" height="52" rx="26" className="stagekitwp-thermometer__tube-track" />
			<rect x="14" y="32" width={ 692 * ( progress / 100 ) } height="40" rx="20" fill={ `url(#${ gradientId })` } />
			<circle cx={ 14 + 692 * ( progress / 100 ) } cy="52" r="20" className="stagekitwp-thermometer__shine" />
			<circle cx="28" cy="42" r="5" className="stagekitwp-thermometer__highlight" />
		</svg>
	);
}
