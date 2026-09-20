document.addEventListener( 'DOMContentLoaded', () => {
	document
		.querySelectorAll( '.stagekitwp-accordion' )
		.forEach( ( accordion, accordionIndex ) => {
			const allowMultiple = accordion.dataset.allowMultiple === 'true';
			const openFirst = accordion.dataset.openFirst !== 'false';
			const items = accordion.querySelectorAll(
				'.stagekitwp-accordion__item'
			);
			let firstOpenIndex = -1;
			items.forEach( ( item, itemIndex ) => {
				if ( firstOpenIndex === -1 && item.dataset.open === 'true' )
					firstOpenIndex = itemIndex;
			} );
			if (
				! allowMultiple &&
				openFirst &&
				firstOpenIndex === -1 &&
				items.length
			)
				firstOpenIndex = 0;
			items.forEach( ( item, itemIndex ) => {
				const trigger = item.querySelector(
					'.stagekitwp-accordion__trigger'
				);
				const panel = item.querySelector(
					'.stagekitwp-accordion__panel'
				);
				const icon = item.querySelector(
					'.stagekitwp-accordion__icon'
				);
				if ( ! trigger || ! panel ) {
					return;
				}
				const panelId = `stagekitwp-accordion-panel-${ accordionIndex }-${ itemIndex }`;
				panel.id = panelId;
				trigger.setAttribute( 'aria-controls', panelId );
				const setOpen = ( open ) => {
					item.classList.toggle( 'is-open', open );
					trigger.setAttribute( 'aria-expanded', String( open ) );
					panel.hidden = ! open;
					if ( icon ) {
						icon.textContent = open ? '\u2212' : '+';
					}
				};
				setOpen(
					allowMultiple
						? item.dataset.open === 'true'
						: itemIndex === firstOpenIndex
				);
				trigger.addEventListener( 'click', () => {
					const open =
						trigger.getAttribute( 'aria-expanded' ) === 'true';
					if ( ! open && ! allowMultiple ) {
						items.forEach( ( other ) => {
							const otherTrigger = other.querySelector(
								'.stagekitwp-accordion__trigger'
							);
							if ( otherTrigger && otherTrigger !== trigger ) {
								const otherPanel = other.querySelector(
									'.stagekitwp-accordion__panel'
								);
								const otherIcon = other.querySelector(
									'.stagekitwp-accordion__icon'
								);
								other.classList.remove( 'is-open' );
								otherTrigger.setAttribute(
									'aria-expanded',
									'false'
								);
								if ( otherPanel ) {
									otherPanel.hidden = true;
								}
								if ( otherIcon ) {
									otherIcon.textContent = '+';
								}
							}
						} );
					}
					setOpen( ! open );
				} );
			} );
		} );
} );
