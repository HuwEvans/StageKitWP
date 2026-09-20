import {
	InspectorControls,
	MediaUpload,
	MediaUploadCheck,
	useBlockProps,
} from '@wordpress/block-editor';

import {
	Button,
	PanelBody,
	RangeControl,
	SelectControl,
	ToggleControl,
	Notice,
} from '@wordpress/components';

import {
	useEffect,
	useRef,
	useState,
} from '@wordpress/element';

import lottie from 'lottie-web';

function AnimationPicker({
	label,
	animationId,
	animationUrl,
	onSelect,
	onRemove,
}) {
	return (
		<div className="stagekitwp-lottie__picker">
			<strong>{label}</strong>

			<div style={{ marginTop: '10px' }}>
				<MediaUploadCheck>
					<MediaUpload
						value={animationId || 0}
						allowedTypes={[
							'application/json',
						]}
						onSelect={onSelect}
						render={({ open }) => (
							<Button
								variant="secondary"
								onClick={open}
							>
								{animationUrl
									? 'Replace Animation'
									: 'Choose Animation'}
							</Button>
						)}
					/>
				</MediaUploadCheck>

				{animationUrl && (
					<Button
						variant="link"
						isDestructive
						onClick={onRemove}
					>
						Remove Animation
					</Button>
				)}
			</div>
		</div>
	);
}

function LottiePreview({ url }) {
	const containerRef = useRef();
	const animationRef = useRef();

	useEffect(() => {
		if (!url || !containerRef.current) {
			return undefined;
		}

		let mounted = true;

		fetch(url)
			.then((response) =>
				response.json()
			)
			.then((data) => {
				if (!mounted) {
					return;
				}

				if (animationRef.current) {
					animationRef.current.destroy();
				}

				animationRef.current =
					lottie.loadAnimation({
						container:
							containerRef.current,
						renderer: 'svg',
						loop: true,
						autoplay: true,
						animationData:
							data,
					});
			})
			.catch(() => {});

		return () => {
			mounted = false;

			if (animationRef.current) {
				animationRef.current.destroy();
			}
		};
	}, [url]);

	if (!url) {
		return (
			<div className="stagekitwp-lottie-editor__placeholder">
				No Animation Selected
			</div>
		);
	}

	return (
		<div
			ref={containerRef}
			className="stagekitwp-lottie-editor__player"
		/>
	);
}

export default function Edit({
	attributes,
	setAttributes,
}) {
	const {
		lightAnimationId = 0,
		lightAnimationUrl = '',
		darkAnimationId = 0,
		darkAnimationUrl = '',
		colorMode = 'auto',
		autoplay = true,
		loop = true,
		speed = 1,
		width = 100,
		maxWidth = 500,
		aspectRatio = '1 / 1',
	} = attributes;

	const [
		lightValidation,
		setLightValidation,
	] = useState(null);

	const [
		darkValidation,
		setDarkValidation,
	] = useState(null);

	const [
		activePreviewUrl,
		setActivePreviewUrl,
	] = useState('');

	const validateLottie =
		async (
			url,
			setResult
		) => {
			if (!url) {
				setResult(null);
				return;
			}

			try {
				const json =
					await fetch(url).then(
						(response) =>
							response.json()
					);

				const valid =
					typeof json ===
						'object' &&
					typeof json.v ===
						'string' &&
					Array.isArray(
						json.layers
					) &&
					json.layers.length > 0 &&
					json.fr &&
					json.w &&
					json.h;

				setResult(valid);
			} catch (error) {
				setResult(false);
			}
		};

	useEffect(() => {
		validateLottie(
			lightAnimationUrl,
			setLightValidation
		);
	}, [lightAnimationUrl]);

	useEffect(() => {
		validateLottie(
			darkAnimationUrl,
			setDarkValidation
		);
	}, [darkAnimationUrl]);

	useEffect(() => {
		const updatePreview = () => {
			if (
				lightAnimationUrl &&
				darkAnimationUrl
			) {
				if (
					colorMode ===
					'light'
				) {
					setActivePreviewUrl(
						lightAnimationUrl
					);
					return;
				}

				if (
					colorMode ===
					'dark'
				) {
					setActivePreviewUrl(
						darkAnimationUrl
					);
					return;
				}

				const isDark =
					document.body.classList.contains(
						'stagekitwp-dark-mode'
					) ||
					document.documentElement.classList.contains(
						'stagekitwp-dark-mode'
					);

				setActivePreviewUrl(
					isDark
						? darkAnimationUrl
						: lightAnimationUrl
				);

				return;
			}

			if (
				lightAnimationUrl
			) {
				setActivePreviewUrl(
					lightAnimationUrl
				);
				return;
			}

			if (
				darkAnimationUrl
			) {
				setActivePreviewUrl(
					darkAnimationUrl
				);
				return;
			}

			setActivePreviewUrl('');
		};

		updatePreview();

		const observer =
			new MutationObserver(
				updatePreview
			);

		observer.observe(
			document.body,
			{
				attributes: true,
				attributeFilter: [
					'class',
				],
			}
		);

		return () =>
			observer.disconnect();
	}, [
		colorMode,
		lightAnimationUrl,
		darkAnimationUrl,
	]);

	const blockProps =
		useBlockProps({
			className:
				'stagekitwp-lottie-editor',
			style: {
				width: `${width}%`,
				maxWidth: `${maxWidth}px`,
				margin: '0 auto',
			},
		});

	const selectAnimation =
		(type) =>
		(media) => {
			setAttributes({
				[
					`${type}AnimationId`
				]: media.id,
				[
					`${type}AnimationUrl`
				]: media.url,
			});
		};

	const removeAnimation =
		(type) =>
		() => {
			setAttributes({
				[
					`${type}AnimationId`
				]: 0,
				[
					`${type}AnimationUrl`
				]: '',
			});
		};

	return (
		<>
			<InspectorControls>
				<PanelBody
					title="Animations"
					initialOpen={true}
				>
					<AnimationPicker
						label="Light Mode Animation"
						animationId={
							lightAnimationId
						}
						animationUrl={
							lightAnimationUrl
						}
						onSelect={selectAnimation(
							'light'
						)}
						onRemove={removeAnimation(
							'light'
						)}
					/>

					<AnimationPicker
						label="Dark Mode Animation"
						animationId={
							darkAnimationId
						}
						animationUrl={
							darkAnimationUrl
						}
						onSelect={selectAnimation(
							'dark'
						)}
						onRemove={removeAnimation(
							'dark'
						)}
					/>
				</PanelBody>

				<PanelBody title="Theme Mode">
					<SelectControl
						label="Color Mode"
						value={colorMode}
						options={[
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
						]}
						onChange={(value) =>
							setAttributes({
								colorMode:
									value,
							})
						}
					/>
				</PanelBody>

				<PanelBody title="Playback">
					<ToggleControl
						label="Autoplay"
						checked={autoplay}
						onChange={(value) =>
							setAttributes({
								autoplay:
									value,
							})
						}
					/>

					<ToggleControl
						label="Loop"
						checked={loop}
						onChange={(value) =>
							setAttributes({
								loop:
									value,
							})
						}
					/>

					<RangeControl
						label="Speed"
						value={speed}
						min={0.25}
						max={3}
						step={0.25}
						onChange={(value) =>
							setAttributes({
								speed:
									value,
							})
						}
					/>
				</PanelBody>

				<PanelBody title="Responsive Sizing">
					<RangeControl
						label="Width (%)"
						value={width}
						min={10}
						max={100}
						step={5}
						onChange={(value) =>
							setAttributes({
								width:
									value,
							})
						}
					/>

					<RangeControl
						label="Maximum Width (px)"
						value={maxWidth}
						min={100}
						max={2000}
						step={25}
						onChange={(value) =>
							setAttributes({
								maxWidth:
									value,
							})
						}
					/>

					<SelectControl
						label="Aspect Ratio"
						value={aspectRatio}
						options={[
							{
								label:
									'Square (1:1)',
								value:
									'1 / 1',
							},
							{
								label:
									'Landscape (4:3)',
								value:
									'4 / 3',
							},
							{
								label:
									'Widescreen (16:9)',
								value:
									'16 / 9',
							},
							{
								label:
									'Portrait (3:4)',
								value:
									'3 / 4',
							},
							{
								label:
									'Portrait (9:16)',
								value:
									'9 / 16',
							},
						]}
						onChange={(value) =>
							setAttributes({
								aspectRatio:
									value,
							})
						}
					/>
				</PanelBody>
			</InspectorControls>

			<div {...blockProps}>

				{(
					(lightAnimationUrl &&
						!darkAnimationUrl) ||
					(darkAnimationUrl &&
						!lightAnimationUrl)
				) && (
					<Notice
						status="warning"
						isDismissible={
							false
						}
					>
						Only one animation
						has been supplied.
						The same animation
						will be used for
						both light and dark
						modes.
					</Notice>
				)}

				<div className="stagekitwp-lottie-editor__active-preview">
					<h3>
						Active Theme
						Preview
					</h3>

					<p>
						This preview
						matches what
						visitors currently
						see.
					</p>

					<LottiePreview
						url={
							activePreviewUrl
						}
					/>
				</div>

				<div className="stagekitwp-lottie-editor__preview">

					<div className="stagekitwp-lottie-editor__column">

						<h4>
							Light Animation
							Preview
						</h4>

						{lightValidation ===
							true && (
							<Notice
								status="success"
								isDismissible={
									false
								}
							>
								Valid Lottie
								File
							</Notice>
						)}

						{lightValidation ===
							false && (
							<Notice
								status="error"
								isDismissible={
									false
								}
							>
								Invalid Lottie
								JSON
							</Notice>
						)}

						<LottiePreview
							url={
								lightAnimationUrl
							}
						/>
					</div>

					<div className="stagekitwp-lottie-editor__column">

						<h4>
							Dark Animation
							Preview
						</h4>

						{darkValidation ===
							true && (
							<Notice
								status="success"
								isDismissible={
									false
								}
							>
								Valid Lottie
								File
							</Notice>
						)}

						{darkValidation ===
							false && (
							<Notice
								status="error"
								isDismissible={
									false
								}
							>
								Invalid Lottie
								JSON
							</Notice>
						)}

						<LottiePreview
							url={
								darkAnimationUrl
							}
						/>
					</div>

				</div>

			</div>
		</>
	);
}