export function isISBN(
    value = ''
) {
        const candidate = normalizeISBN( value );
        return /^(97(8|9))?\d{9}(\d|X)$/i.test( candidate );
}
    export function normalizeISBN( value = '' ) {
        return String( value )
            .toUpperCase()
            .replace( /[^0-9X]/g, '' );
    }

export function isASIN(
    value = ''
) {
    return /^[A-Z0-9]{10}$/i
        .test(value);
}