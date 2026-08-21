import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';

export default function Save( { attributes } ) {
	const { title = 'Tab' } = attributes;

	const blockProps = useBlockProps.save( {
		className: 'stagekit-tab-panel',
		'data-title': title,
	} );

	return (
		<div { ...blockProps }>
			<InnerBlocks.Content />
		</div>
	);
}