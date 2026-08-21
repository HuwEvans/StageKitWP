import { useBlockProps } from '@wordpress/block-editor';

export default function save( { attributes } ) {
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

	const imageProps = {
		className: 'stagekitwp-dual-image__variant',
		style: {
			'--stagekitwp-dual-image-width': `${ imageWidth }%`,
			'--stagekitwp-dual-image-ratio': aspectRatio,
			'--stagekitwp-dual-image-fit': objectFit,
			'--stagekitwp-dual-image-radius': `${ borderRadius }px`,
		},
	};

	const imageLinkProps = linkUrl ? {
		href: linkUrl,
		target: linkTargetBlank ? '_blank' : undefined,
		rel: linkTargetBlank ? 'noopener noreferrer' : undefined,
	} : null;

	const renderImage = ( imageUrl, imageAlt, mode ) => {
		if ( ! imageUrl ) {
			return null;
		}

		const image = (
			<img
				{ ...imageProps }
				className={ `stagekitwp-dual-image__variant stagekitwp-dual-image__variant--${ mode }` }
				src={ imageUrl }
				alt={ imageAlt }
			/>
		);

		return imageLinkProps ? <a { ...imageLinkProps }>{ image }</a> : image;
	};

	return (
		<div { ...useBlockProps.save( { className: `stagekitwp-dual-image align-${ alignment }` } ) }>
			{ renderImage( lightImageUrl, lightImageAlt, 'light' ) }
			{ renderImage( darkImageUrl, darkImageAlt, 'dark' ) }
		</div>
	);
}
