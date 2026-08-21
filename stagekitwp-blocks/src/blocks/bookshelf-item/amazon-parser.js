export function extractDomain(url) {

    try {

        const parsed =
            new URL(url);

        return parsed.hostname
            .replace('www.', '');

    } catch {

        return '';
    }
}

export function extractASIN(url) {

    if (!url) {
        return '';
    }

    const patterns = [

        /\/dp\/([A-Z0-9]{10})/i,

        /\/gp\/product\/([A-Z0-9]{10})/i,

        /\/product\/([A-Z0-9]{10})/i
    ];

    for (const pattern of patterns) {

        const match =
            url.match(pattern);

        if (match) {

            return match[1];
        }
    }

    return '';
}

export function buildAffiliateUrl({
    asin,
    domain,
    affiliateTag
}) {

    if (!asin) {
        return '';
    }

    let url =
        `https://${domain}/dp/${asin}`;

    if (affiliateTag) {

        url +=
            `?tag=${encodeURIComponent(
                affiliateTag
            )}`;
    }

    return url;
}

function normalizeCoverUrl(url) {
    if (!url) {
        return '';
    }

    return String(url).replace('http://', 'https://');
}

async function fetchGoogleBooksMetadata(isbn) {
    const response = await fetch(
        `https://www.googleapis.com/books/v1/volumes?q=isbn:${encodeURIComponent(isbn)}&maxResults=1`
    );

    if (!response.ok) {
        return null;
    }

    const data = await response.json();
    const item = data?.items?.[0];

    if (!item?.volumeInfo) {
        return null;
    }

    const volumeInfo = item.volumeInfo;

    return {
        title: volumeInfo.title || '',
        author: Array.isArray(volumeInfo.authors) ? volumeInfo.authors.join(', ') : '',
        coverImage: normalizeCoverUrl(
            volumeInfo.imageLinks?.thumbnail ||
            volumeInfo.imageLinks?.smallThumbnail ||
            ''
        )
    };
}

async function fetchOpenLibraryMetadata(isbn) {
    const response = await fetch(
        `https://openlibrary.org/api/books?bibkeys=ISBN:${encodeURIComponent(isbn)}&format=json&jscmd=data`
    );

    if (!response.ok) {
        return null;
    }

    const data = await response.json();
    const item = data?.[`ISBN:${isbn}`];

    if (!item) {
        return null;
    }

    return {
        title: item.title || '',
        author: Array.isArray(item.authors)
            ? item.authors.map((author) => author.name).filter(Boolean).join(', ')
            : '',
        coverImage: normalizeCoverUrl(
            item.cover?.medium ||
            item.cover?.large ||
            item.cover?.small ||
            ''
        )
    };
}

export async function fetchBookMetadata({ isbn }) {
    const candidate = String(isbn || '').trim();

    if (!candidate) {
        return null;
    }

    const sources = [
        fetchGoogleBooksMetadata,
        fetchOpenLibraryMetadata
    ];

    for (const fetcher of sources) {
        try {
            const metadata = await fetcher(candidate);
            if (metadata && (metadata.title || metadata.author || metadata.coverImage)) {
                return metadata;
            }
        } catch {
            // Try the next public source.
        }
    }

    return null;
}