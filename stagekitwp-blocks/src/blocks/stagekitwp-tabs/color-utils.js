export function normalizeColorValue( value, fallback ) {
	if ( ! value ) {
		return fallback;
	}

	const presetMatch = String( value ).match( /^var:preset\|color\|(.+)$/ );

	if ( presetMatch ) {
		return `var(--wp--preset--color--${ presetMatch[ 1 ] })`;
	}

	const hexMatch = String( value ).match( /^#([0-9a-f]{6})(00)$/i );

	if ( hexMatch ) {
		return `#${ hexMatch[ 1 ] }`;
	}

	return value;
}