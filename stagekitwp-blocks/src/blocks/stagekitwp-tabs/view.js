import { normalizeColorValue } from './color-utils';

const initTabs = () => {
	const wrappers = document.querySelectorAll( '.stagekit-tabs-wrapper' );

	wrappers.forEach( ( wrapper ) => {
		if ( wrapper.dataset.tabsInitialized === 'true' ) return;
		wrapper.dataset.tabsInitialized = 'true';

		// Apply block attributes only if customized
		const activeColor = normalizeColorValue( wrapper.dataset.activeColor, '' );
		const activeBg = normalizeColorValue( wrapper.dataset.activeBg, '' );
		const inactiveColor = normalizeColorValue( wrapper.dataset.inactiveColor, '' );
		const inactiveBg = normalizeColorValue( wrapper.dataset.inactiveBg, '' );
		const hoverColor = normalizeColorValue( wrapper.dataset.hoverColor, '' );
		const hoverBg = normalizeColorValue( wrapper.dataset.hoverBg, '' );
		const radius = wrapper.dataset.radius;
		const folderHeaderBg = normalizeColorValue( wrapper.dataset.folderHeaderBg, '' );

		if ( folderHeaderBg ) wrapper.style.setProperty( '--stagekit-folder-header-bg', folderHeaderBg );
		if ( activeColor ) wrapper.style.setProperty( '--stagekit-active-color', activeColor );
		if ( activeBg ) wrapper.style.setProperty( '--stagekit-active-bg', activeBg );
		if ( inactiveColor ) wrapper.style.setProperty( '--stagekit-inactive-color', inactiveColor );
		if ( inactiveBg ) wrapper.style.setProperty( '--stagekit-inactive-bg', inactiveBg );
		if ( hoverColor ) wrapper.style.setProperty( '--stagekit-hover-color', hoverColor );
		if ( hoverBg ) wrapper.style.setProperty( '--stagekit-hover-bg', hoverBg );
		if ( radius ) wrapper.style.setProperty( '--stagekit-radius', radius );

		const panels = Array.from( wrapper.querySelectorAll( '.stagekit-tab-panel' ) );
		if ( ! panels.length ) return;

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

			if ( index !== 0 ) {
				if ( inactiveColor ) btn.style.setProperty( '--stagekit-inactive-color', inactiveColor );
				if ( inactiveBg ) btn.style.setProperty( '--stagekit-inactive-bg', inactiveBg );
			}

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