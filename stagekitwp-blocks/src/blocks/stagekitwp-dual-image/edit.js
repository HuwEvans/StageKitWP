import {
	InspectorControls,
	MediaUpload,
	MediaUploadCheck,
	RichText,
	useBlockProps,
} from '@wordpress/block-editor';
import {
	Button,
	PanelBody,
	RangeControl,
	SelectControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';

function ImagePicker( { label, imageId, imageUrl, onSelect, onRemove } ) {
	return (
		<div className="stagekitwp-dual-image__picker">
			<strong>{ label }</strong>
			<MediaUploadCheck>
				<MediaUpload
					onSelect={ onSelect }
					allowedTypes={ [ 'image' ] }
					value={ imageId || 0 }
					render={ ( { open } ) => (
						<Button variant="secondary" onClick={ open }>
							{ imageUrl ? 'Replace image' : 'Choose image' }
						</Button>
					) }
				/>
			</MediaUploadCheck>
			{ imageUrl && (
				<Button variant="link" isDestructive onClick={ onRemove }>
					Remove image
				</Button>
			) }
		</div>
	);
}

export default function Edit( { attributes, setAttributes } ) {
	const {
		lightImageUrl,
		lightImageAlt,
		darkImageUrl,
		darkImageAlt,
		alignment,
		imageWidth,
		aspectRatio,
		objectFit,
		borderRadius,
		linkUrl,
		linkTargetBlank,
	} = attributes;

	const blockProps = useBlockProps( {
		className: `stagekitwp-dual-image align-${ alignment }`,
		style: {
			'--stagekitwp-dual-image-width': `${ imageWidth }%`,
			'--stagekitwp-dual-image-ratio': aspectRatio,
			'--stagekitwp-dual-image-fit': objectFit,
			'--stagekitwp-dual-image-radius': `${ borderRadius }px`,
		},
	} );

	const selectImage = ( mode ) => ( media ) => {
		setAttributes( {
			[ `${ mode }ImageId` ]: media.id,
			[ `${ mode }ImageUrl` ]: media.url,
			[ `${ mode }ImageAlt` ]: media.alt || '',
		} );
	};

	const removeImage = ( mode ) => () => {
		setAttributes( {
			[ `${ mode }ImageId` ]: 0,
			[ `${ mode }ImageUrl` ]: '',
			[ `${ mode }ImageAlt` ]: '',
		} );
	};

	return (
		<>
			<InspectorControls>
				<PanelBody title="Theme Images" initialOpen>
					<ImagePicker
						label="Light mode image"
						imageId={ attributes.lightImageId }
						imageUrl={ lightImageUrl }
						onSelect={ selectImage( 'light' ) }
						onRemove={ removeImage( 'light' ) }
					/>
					<ImagePicker
						label="Dark mode image"
						imageId={ attributes.darkImageId }
						imageUrl={ darkImageUrl }
						onSelect={ selectImage( 'dark' ) }
						onRemove={ removeImage( 'dark' ) }
					/>
				</PanelBody>
				<PanelBody title="Alternative Text" initialOpen={ false }>
					<RichText
						tagName="label"
						value={ lightImageAlt }
						onChange={ ( value ) => setAttributes( { lightImageAlt: value } ) }
						placeholder="Light image description"
						allowedFormats={ [] }
					/>
					<RichText
						tagName="label"
						value={ darkImageAlt }
						onChange={ ( value ) => setAttributes( { darkImageAlt: value } ) }
						placeholder="Dark image description"
						allowedFormats={ [] }
					/>
				</PanelBody>
				<PanelBody title="Image Settings" initialOpen={ false }>
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
					<RangeControl
						label="Image Width (%)"
						value={ imageWidth }
						onChange={ ( value ) => setAttributes( { imageWidth: value } ) }
						min={ 10 }
						max={ 100 }
						step={ 5 }
					/>
					<SelectControl
						label="Aspect Ratio"
						value={ aspectRatio }
						options={ [
							{ label: 'Original', value: 'auto' },
							{ label: 'Square (1:1)', value: '1 / 1' },
							{ label: 'Landscape (4:3)', value: '4 / 3' },
							{ label: 'Widescreen (16:9)', value: '16 / 9' },
							{ label: 'Portrait (3:4)', value: '3 / 4' },
						] }
						onChange={ ( value ) => setAttributes( { aspectRatio: value } ) }
					/>
					<SelectControl
						label="Image Fit"
						value={ objectFit }
						options={ [
							{ label: 'Contain', value: 'contain' },
							{ label: 'Cover', value: 'cover' },
							{ label: 'Fill', value: 'fill' },
						] }
						onChange={ ( value ) => setAttributes( { objectFit: value } ) }
					/>
					<RangeControl
						label="Border Radius (px)"
						value={ borderRadius }
						onChange={ ( value ) => setAttributes( { borderRadius: value } ) }
						min={ 0 }
						max={ 50 }
					/>
					<TextControl
						label="Link URL"
						value={ linkUrl }
						onChange={ ( value ) => setAttributes( { linkUrl: value } ) }
						type="url"
					/>
					<ToggleControl
						label="Open link in new tab"
						checked={ linkTargetBlank }
						onChange={ ( value ) => setAttributes( { linkTargetBlank: value } ) }
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<div className="stagekitwp-dual-image__preview">
					<div className="stagekitwp-dual-image__variant stagekitwp-dual-image__variant--light">
						<strong>Light mode</strong>
						{ lightImageUrl ? <img src={ lightImageUrl } alt={ lightImageAlt } /> : <span>Select a light mode image</span> }
					</div>
					<div className="stagekitwp-dual-image__variant stagekitwp-dual-image__variant--dark">
						<strong>Dark mode</strong>
						{ darkImageUrl ? <img src={ darkImageUrl } alt={ darkImageAlt } /> : <span>Select a dark mode image</span> }
					</div>
				</div>
			</div>
		</>
	);
}
