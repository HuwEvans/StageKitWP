import lottie from 'lottie-web';

function isDarkMode(container) {

	const forcedMode =
		container.dataset.colorMode;

	if (forcedMode === 'dark') {
		return true;
	}

	if (forcedMode === 'light') {
		return false;
	}

	return (
		document.body.classList.contains(
			'stagekitwp-dark-mode'
		) ||
		document.documentElement.classList.contains(
			'stagekitwp-dark-mode'
		)
	);
}

function getAnimationPath(container) {

	const light =
		container.dataset.light || '';

	const dark =
		container.dataset.dark || '';

	const darkMode =
		isDarkMode(container);

	/*
	 * Both animations supplied.
	 */

	if (light && dark) {
		return darkMode
			? dark
			: light;
	}

	/*
	 * Only light supplied.
	 */

	if (light) {
		return light;
	}

	/*
	 * Only dark supplied.
	 */

	if (dark) {
		return dark;
	}

	return '';
}

function createAnimation(
	player,
	container,
	path
) {

	const reducedMotion =
		window.matchMedia(
			'(prefers-reduced-motion: reduce)'
		).matches;

	const animation =
		lottie.loadAnimation({
			container: player,
			renderer: 'svg',
			loop:
				reducedMotion
					? false
					: container.dataset.loop ===
					  'true',
			autoplay:
				reducedMotion
					? false
					: container.dataset.autoplay ===
					  'true',
			path,
		});

	animation.setSpeed(
		parseFloat(
			container.dataset.speed || 1
		)
	);

	animation.__path = path;

	return animation;
}

function loadOrReloadAnimation(
	container,
	player,
	currentAnimation
) {

	const newPath =
		getAnimationPath(
			container
		);

	if (!newPath) {
		return currentAnimation;
	}

	/*
	 * Same animation already loaded.
	 */

	if (
		currentAnimation &&
		currentAnimation.__path ===
			newPath
	) {
		return currentAnimation;
	}

	/*
	 * Destroy existing animation.
	 */

	if (currentAnimation) {
		currentAnimation.destroy();
	}

	player.innerHTML = '';

	return createAnimation(
		player,
		container,
		newPath
	);
}

function initializeLottie(
	container
) {

	const player =
		container.querySelector(
			'.stagekitwp-lottie-player'
		);

	if (!player) {
		return;
	}

	let animation =
		loadOrReloadAnimation(
			container,
			player,
			null
		);

	let currentMode =
		isDarkMode(container);

	const themeObserver =
		new MutationObserver(
			() => {

				const newMode =
					isDarkMode(
						container
					);

				if (
					newMode ===
					currentMode
				) {
					return;
				}

				currentMode =
					newMode;

				animation =
					loadOrReloadAnimation(
						container,
						player,
						animation
					);

			}
		);

	themeObserver.observe(
		document.body,
		{
			attributes: true,
			attributeFilter: [
				'class',
			],
		}
	);

	/*
	 * Watch html class changes too.
	 */

	themeObserver.observe(
		document.documentElement,
		{
			attributes: true,
			attributeFilter: [
				'class',
			],
		}
	);
}

document.addEventListener(
	'DOMContentLoaded',
	() => {

		document
			.querySelectorAll(
				'.stagekitwp-lottie-wrapper'
			)
			.forEach(
				initializeLottie
			);

	}
);