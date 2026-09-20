import apiFetch from '@wordpress/api-fetch';

export function extractDomain( url ) {
	try {
		return new URL( url ).hostname.replace( 'www.', '' );
	} catch {
		return '';
	}
}

export function extractASIN( url ) {
	if ( ! url ) return '';

	const patterns = [
		/\/(?:dp|gp\/product|product)\/([A-Z0-9]{10})/i,
		/\/(?:isbn|isbn13|isbn10)[\/=:-]+([0-9X-]{10,17})/i,
		/[?&](?:isbn|isbn13|isbn10|asin)=([A-Z0-9-]{10,17})/i,
		/(?:^|[^0-9])((?:97[89])?\d{9}[\dX])(?:$|[^0-9])/i,
	];

	for ( const pattern of patterns ) {
		const match = url.match( pattern );
		if ( match ) return match[ 1 ].replace( /[^0-9A-Z]/gi, '' );
	}
	return '';
}

export function extractConcordTheatricalsInfo( url ) {
	if ( ! url ) return null;
	const match = url.match( /concordtheatricals\.com\/p\/(\d+)\/([a-z0-9-]+)/i );
	if ( ! match ) return null;
	const [ , id, slug ] = match;
	const title = slug.replace( /-/g, ' ' ).replace( /\b\w/g, ( character ) => character.toUpperCase() );
	return { path: `p/${ id }/${ slug }`, id, title };
}

export function buildAffiliateUrl( { asin, domain, affiliateTag } ) {
	if ( ! asin || ! domain ) return '';
	const isAmazonDomain = /amazon\./i.test( domain );
	// Non-Amazon sources (e.g. Concord Theatricals) already have their full path stored in `asin`.
	let url = isAmazonDomain
		? `https://${ domain }/dp/${ asin }`
		: `https://${ domain }/${ String( asin ).replace( /^\/+/, '' ) }`;
	if ( isAmazonDomain && affiliateTag ) url += `?tag=${ encodeURIComponent( affiliateTag ) }`;
	return url;
}

function normalizeCoverUrl( url ) {
	return url ? String( url ).replace( 'http://', 'https://' ) : '';
}

function normalizeISBN( value ) {
	return String( value || '' ).toUpperCase().replace( /[^0-9X]/g, '' );
}

async function fetchGoogleBooksMetadata( isbn ) {
	const response = await fetch( `https://www.googleapis.com/books/v1/volumes?q=isbn:${ encodeURIComponent( isbn ) }&maxResults=1` );
	if ( ! response.ok ) return null;
	const item = ( await response.json() )?.items?.[ 0 ];
	if ( ! item?.volumeInfo ) return null;
	const volumeInfo = item.volumeInfo;
	return {
		title: volumeInfo.title || '',
		author: Array.isArray( volumeInfo.authors ) ? volumeInfo.authors.join( ', ' ) : '',
		coverImage: normalizeCoverUrl( volumeInfo.imageLinks?.thumbnail || volumeInfo.imageLinks?.smallThumbnail || '' ),
	};
}

async function fetchOpenLibraryMetadata( isbn ) {
	const response = await fetch( `https://openlibrary.org/search.json?isbn=${ encodeURIComponent( isbn ) }&limit=1` );
	if ( ! response.ok ) return null;
	const item = ( await response.json() )?.docs?.[ 0 ];
	if ( ! item ) return null;
	return {
		title: item.title || '',
		author: Array.isArray( item.author_name ) ? item.author_name.filter( Boolean ).join( ', ' ) : '',
		coverImage: item.cover_i ? `https://covers.openlibrary.org/b/id/${ item.cover_i }-M.jpg` : '',
	};
}

// UPCitemdb's keyless trial lookup resolves ISBNs to the real Amazon ASIN plus a cover image.
async function fetchUpcItemDbMetadata( isbn ) {
	const response = await fetch( `https://api.upcitemdb.com/prod/trial/lookup?upc=${ encodeURIComponent( isbn ) }` );
	if ( ! response.ok ) return null;
	const item = ( await response.json() )?.items?.[ 0 ];
	if ( ! item ) return null;
	return {
		title: item.title || '',
		author: item.brand || '',
		coverImage: normalizeCoverUrl( item.images?.[ 0 ] || '' ),
		asin: /^[A-Z0-9]{10}$/i.test( item.asin || '' ) ? item.asin : '',
	};
}

export async function fetchBookMetadata( { isbn } ) {
	const candidate = normalizeISBN( isbn );
	if ( ! candidate ) return null;
	const merged = { title: '', author: '', coverImage: '', asin: '' };
	for ( const fetcher of [ fetchOpenLibraryMetadata, fetchGoogleBooksMetadata, fetchUpcItemDbMetadata ] ) {
		if ( merged.title && merged.author && merged.coverImage && merged.asin ) break;
		try {
			const metadata = await fetcher( candidate );
			if ( ! metadata ) continue;
			merged.title = merged.title || metadata.title || '';
			merged.author = merged.author || metadata.author || '';
			merged.coverImage = merged.coverImage || metadata.coverImage || '';
			merged.asin = merged.asin || metadata.asin || '';
		} catch {
			// Try the next public source.
		}
	}
	if ( ! merged.title && ! merged.author && ! merged.coverImage ) return null;
	return merged;
}

// The same public search-only Algolia key used by Concord Theatricals' own site search widget.
const CONCORD_ALGOLIA_URL = 'https://nlrbd2abn6-dsn.algolia.net/1/indexes/production_us_products_perform/query'
	+ '?x-algolia-agent=Algolia%20for%20JavaScript'
	+ '&x-algolia-api-key=ae84c45952e8054ee34716e2316addd1'
	+ '&x-algolia-application-id=NLRBD2ABN6';

export async function searchConcordTheatricalsByTitle( title ) {
	const query = String( title || '' ).trim();
	if ( ! query ) return [];
	const response = await fetch( CONCORD_ALGOLIA_URL, {
		method: 'POST',
		body: JSON.stringify( { params: `query=${ encodeURIComponent( query ) }&hitsPerPage=5` } ),
	} );
	if ( ! response.ok ) return [];
	const hits = ( await response.json() )?.hits || [];
	return hits.map( ( hit ) => {
		const coverImage = hit.ProductImages?.find( ( image ) => image.Type === 'Shop' || image.Type === 'Perform' )?.ImageUrl
			|| hit.ProductImages?.[ 0 ]?.ImageUrl
			|| '';
		return {
			source: 'Concord Theatricals',
			domain: 'concordtheatricals.com',
			path: `p/${ hit.objectID }/${ hit.SeName }`,
			title: hit.Name || '',
			author: Array.isArray( hit.TitleAuthors ) ? hit.TitleAuthors.map( ( author ) => author.FullName ).filter( Boolean ).join( ', ' ) : '',
			description: hit.ShortDescription || '',
			coverImage: normalizeCoverUrl( coverImage ),
		};
	} );
}

// Canadian Play Outlet's storefront search has no CORS headers, so this goes through a WordPress REST proxy.
export async function searchCanadianPlayOutletByTitle( title ) {
	const query = String( title || '' ).trim();
	if ( ! query ) return [];
	const response = await apiFetch( { path: `/stagekitwp-blocks/v1/canadian-play-outlet-search?title=${ encodeURIComponent( query ) }` } );
	return ( response?.results || [] ).map( ( result ) => ( {
		source: 'Canadian Play Outlet',
		domain: 'canadianplayoutlet.com',
		path: String( result.path || '' ).replace( /^\/+/, '' ),
		title: result.title || '',
		author: '',
		description: result.description || '',
		coverImage: normalizeCoverUrl( result.coverImage || '' ),
	} ) );
}

export async function searchPlaysByTitle( title ) {
	const [ concordResults, canadianResults ] = await Promise.allSettled( [
		searchConcordTheatricalsByTitle( title ),
		searchCanadianPlayOutletByTitle( title ),
	] );
	return [
		...( concordResults.status === 'fulfilled' ? concordResults.value : [] ),
		...( canadianResults.status === 'fulfilled' ? canadianResults.value : [] ),
	].filter( ( result ) => result.path );
}
