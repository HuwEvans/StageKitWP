function formatNumber(
	number,
	useSeparator
) {

	if ( ! useSeparator ) {
		return number;
	}

	return number.toLocaleString();
}

function applyEasing(
	progress,
	style
) {

	switch ( style ) {

		case 'linear':
			return progress;

		case 'ease-in-out':
			return progress < 0.5
				? 2 * progress * progress
				: 1 - Math.pow(
						-2 * progress + 2,
						2
				  ) / 2;

		case 'bounce':

			if ( progress < 1 / 2.75 ) {
				return (
					7.5625 *
					progress *
					progress
				);
			}

			if ( progress < 2 / 2.75 ) {

				progress -=
					1.5 / 2.75;

				return (
					7.5625 *
						progress *
						progress +
					0.75
				);

			}

			if ( progress < 2.5 / 2.75 ) {

				progress -=
					2.25 / 2.75;

				return (
					7.5625 *
						progress *
						progress +
					0.9375
				);

			}

			progress -=
				2.625 / 2.75;

			return (
				7.5625 *
					progress *
					progress +
				0.984375
			);

		case 'ease-out':
		default:
			return (
				1 -
				Math.pow(
					1 - progress,
					3
				)
			);
	}
}

function runAnimation(
	container
) {

	const start =
		parseInt(
			container.dataset.start,
			10
		) || 0;

	const target =
		parseInt(
			container.dataset.target,
			10
		) || 0;

	const duration =
		parseInt(
			container.dataset.duration,
			10
		) || 2000;

	const animationStyle =
		container.dataset
			.animationStyle ||
		'ease-out';

	const useSeparator =
		container.dataset
			.separator ===
		'true';

	const numberEl =
		container.querySelector(
			'.stagekitwp-countup__number'
		);

	const visibleNumberEl =
		container.querySelector(
			'.stagekitwp-countup__number > span[aria-hidden="true"]'
		);

	if ( ! numberEl || ! visibleNumberEl ) {
		return;
	}

	const reducedMotion =
		window.matchMedia(
			'(prefers-reduced-motion: reduce)'
		).matches;

	if ( reducedMotion ) {

		visibleNumberEl.textContent =
			formatNumber(
				target,
				useSeparator
			);

		return;
	}

	let startTime = null;

	function step(
		timestamp
	) {

		if ( ! startTime ) {
			startTime = timestamp;
		}

		const rawProgress =
			Math.min(
				(
					timestamp -
					startTime
				) /
					duration,
				1
			);

		const easedProgress =
			applyEasing(
				rawProgress,
				animationStyle
			);

		const current =
			Math.floor(
				start +
				(
					target -
					start
				) *
					easedProgress
			);

		visibleNumberEl.textContent =
			formatNumber(
				current,
				useSeparator
			);

		if (
			rawProgress < 1
		) {

			requestAnimationFrame(
				step
			);

		}
		else {

			visibleNumberEl.textContent =
				formatNumber(
					target,
					useSeparator
				);

		}
	}

	requestAnimationFrame(
		step
	);
}

function animateCounter(
	container
) {

	const delay =
		parseInt(
			container.dataset.delay,
			10
		) || 0;

	if ( delay > 0 ) {

		setTimeout(
			() =>
				runAnimation(
					container
				),
			delay
		);

		return;
	}

	runAnimation(
		container
	);
}

document.addEventListener(
	'DOMContentLoaded',
	() => {

		const counters =
			document.querySelectorAll(
				'.stagekitwp-countup'
			);

		if (
			! counters.length
		) {

			return;

		}

		if ( typeof IntersectionObserver === 'undefined' ) {
			counters.forEach(
				( counter ) => animateCounter( counter )
			);
			return;
		}

		const observer =
			new IntersectionObserver(
				(
					entries
				) => {

					entries.forEach(
						(
							entry
						) => {

							if (
								entry.isIntersecting
							) {

								animateCounter(
									entry.target
								);

								observer.unobserve(
									entry.target
								);

							}
						}
					);

				},
				{
					threshold:
						0.3,
				}
			);

		counters.forEach(
			(
				counter
			) => {

				observer.observe(
					counter
				);

			}
		);

	}
);