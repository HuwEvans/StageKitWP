const initTabs = () => {
	const wrappers = document.querySelectorAll( '.stagekit-tabs-wrapper' );

	wrappers.forEach( ( wrapper ) => {
		if ( wrapper.dataset.tabsInitialized === 'true' ) return;
		wrapper.dataset.tabsInitialized = 'true';

		const panels = Array.from( wrapper.querySelectorAll( '.stagekit-tab-panel' ) );
		if ( ! panels.length ) return;

		// Create navigation bar dynamically if not present
		let nav = wrapper.querySelector( '.stagekit-tabs-nav' );
		if ( ! nav ) {
			nav = document.createElement( 'div' );
			nav.className = 'stagekit-tabs-nav';
			wrapper.insertBefore( nav, wrapper.firstChild );
		}

		panels.forEach( ( panel, index ) => {
			const title = panel.getAttribute( 'data-title' ) || `Tab ${ index + 1 }`;

			const btn = document.createElement( 'button' );
			btn.type = 'button';
			btn.className = `stagekit-tab-btn ${ index === 0 ? 'active' : '' }`;
			btn.innerText = title;

			if ( index === 0 ) {
				panel.classList.add( 'active' );
				panel.style.display = 'block';
			} else {
				panel.style.display = 'none';
			}

			btn.addEventListener( 'click', ( e ) => {
				e.preventDefault();

				wrapper.querySelectorAll( '.stagekit-tab-btn' ).forEach( ( b ) => b.classList.remove( 'active' ) );
				panels.forEach( ( p ) => {
					p.classList.remove( 'active' );
					p.style.display = 'none';
				} );

				btn.classList.add( 'active' );
				panel.classList.add( 'active' );
				panel.style.display = 'block';
			} );

			nav.appendChild( btn );
		} );
	} );
};

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', initTabs );
} else {
	initTabs();
}