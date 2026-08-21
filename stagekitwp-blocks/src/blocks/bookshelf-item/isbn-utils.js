export function isISBN(
    value = ''
) {
    return /^(97(8|9))?\d{9}(\d|X)$/i
        .test(value);
}

export function isASIN(
    value = ''
) {
    return /^[A-Z0-9]{10}$/i
        .test(value);
}