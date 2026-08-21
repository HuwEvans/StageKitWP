import { useBlockProps, RichText, InnerBlocks } from '@wordpress/block-editor';

export default function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps( {
		className: 'stagekit-tab-item-editor',
	} );

	return (
		<div { ...blockProps } style={ { border: '1px solid #cbd5e1', padding: '16px', marginBottom: '16px', borderRadius: '6px' } }>
			<div className="tab-title-field" style={ { marginBottom: '12px' } }>
				<strong>Tab Title: </strong>
				<RichText
					tagName="span"
					value={ attributes.title }
					onChange={ ( title ) => setAttributes( { title } ) }
					placeholder="Tab Name..."
					allowedFormats={ [] }
				/>
			</div>
			<div className="tab-nested-content" style={ { background: '#f8fafc', padding: '12px', borderRadius: '4px' } }>
				<InnerBlocks />
			</div>
		</div>
	);
}