import { InnerBlocks, RichText, useBlockProps } from '@wordpress/block-editor';

export default function Edit( { attributes, setAttributes } ) {
	const { title = 'Accordion item', open = false } = attributes;
	return (
		<div
			{ ...useBlockProps( { className: 'stagekitwp-accordion__item' } ) }
		>
			<h3 className="stagekitwp-accordion__heading">
				<button
					type="button"
					className="stagekitwp-accordion__trigger"
					aria-expanded={ open }
					onClick={ ( event ) => event.preventDefault() }
				>
					<RichText
						tagName="span"
						value={ title }
						onChange={ ( value ) => setAttributes( { title: value } ) }
						placeholder="Panel title"
						allowedFormats={ [] }
					/>
					<span className="stagekitwp-accordion__icon" aria-hidden="true">
						{ open ? '\u2212' : '+' }
					</span>
				</button>
			</h3>
			<div className="stagekitwp-accordion__panel">
				<InnerBlocks />
			</div>
		</div>
	);
}
