import { InnerBlocks, RichText, useBlockProps } from '@wordpress/block-editor';

export default function save( { attributes } ) {
	const { title = 'Accordion item', open = false } = attributes;
	const panelId = 'stagekitwp-accordion-panel';
	return (
		<div
			{ ...useBlockProps.save( {
				className: 'stagekitwp-accordion__item',
				'data-open': open,
			} ) }
		>
			<h3 className="stagekitwp-accordion__heading">
				<button
					type="button"
					className="stagekitwp-accordion__trigger"
					aria-expanded={ open }
					aria-controls={ panelId }
				>
					<RichText.Content tagName="span" value={ title } />
					<span
						className="stagekitwp-accordion__icon"
						aria-hidden="true"
					>
						+
					</span>
				</button>
			</h3>
			<div
				id={ panelId }
				className="stagekitwp-accordion__panel"
				hidden={ ! open }
			>
				<div className="stagekitwp-accordion__content">
					<InnerBlocks.Content />
				</div>
			</div>
		</div>
	);
}
