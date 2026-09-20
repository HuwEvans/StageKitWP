function pad( value ) {
	return String( value ).padStart( 2, '0' );
}

function updateCountdown( container ) {
	const target = new Date( container.dataset.target ).getTime();
	const expired = container.querySelector( '.stagekitwp-countdown__expired' );
	const units = container.querySelectorAll( '[data-unit]' );

	if ( ! Number.isFinite( target ) ) {
		return;
	}

	const remaining = target - Date.now();
	if ( remaining <= 0 ) {
		container.classList.add( 'is-expired' );
		if ( expired ) {
			expired.hidden = false;
		}
		units.forEach( ( unit ) => {
			const number = unit.querySelector( '.stagekitwp-countdown__number' );
			if ( number ) {
				number.textContent = '00';
			}
		} );
		return false;
	}

	const values = {
		days: Math.floor( remaining / 86400000 ),
		hours: Math.floor( remaining / 3600000 ) % 24,
		minutes: Math.floor( remaining / 60000 ) % 60,
		seconds: Math.floor( remaining / 1000 ) % 60,
	};
	units.forEach( ( unit ) => {
		const number = unit.querySelector( '.stagekitwp-countdown__number' );
		if ( number && values[ unit.dataset.unit ] !== undefined ) {
			number.textContent = pad( values[ unit.dataset.unit ] );
		}
	} );
	return true;
}

function initCountdown( container ) {
	const tick = () => {
		if ( updateCountdown( container ) === false && container.countdownTimer ) {
			window.clearInterval( container.countdownTimer );
			container.countdownTimer = null;
		}
	};
	tick();
	if ( ! container.classList.contains( 'is-expired' ) ) {
		container.countdownTimer = window.setInterval( tick, 1000 );
	}
}

document.addEventListener( 'DOMContentLoaded', () => {
	document.querySelectorAll( '.stagekitwp-countdown' ).forEach( initCountdown );
} );
