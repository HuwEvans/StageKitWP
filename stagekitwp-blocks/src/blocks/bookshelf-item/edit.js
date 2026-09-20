import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { useState } from '@wordpress/element';
import { Button, PanelBody, SelectControl, TextControl } from '@wordpress/components';
import { buildAffiliateUrl, extractASIN, extractConcordTheatricalsInfo, extractDomain, fetchBookMetadata, searchPlaysByTitle } from './amazon-parser';
import { isISBN, normalizeISBN } from './isbn-utils';

export default function Edit( { attributes, setAttributes } ) {
	const [ statusMessage, setStatusMessage ] = useState( '' );
	const [ isFetching, setIsFetching ] = useState( false );
	const [ titleQuery, setTitleQuery ] = useState( '' );
	const [ titleResults, setTitleResults ] = useState( [] );
	const [ isSearchingTitle, setIsSearchingTitle ] = useState( false );
	const { bookTitle, authorName, rawUrl, amazonUrl, asin, domain, affiliateTag, cardTheme, cardOrientation, imageAlignment, cardSize } = attributes;
	const amazonSource = amazonUrl || rawUrl || '';

	const populateFromLink = async () => {
		const concordInfo = extractConcordTheatricalsInfo( amazonSource );
		if ( concordInfo ) {
			setAttributes( {
				amazonUrl: amazonSource,
				rawUrl: amazonSource,
				asin: concordInfo.path,
				domain: 'concordtheatricals.com',
				bookTitle: bookTitle || concordInfo.title,
			} );
			setStatusMessage( 'Concord Theatricals link detected. Concord does not offer a public metadata API, so please confirm the title and add the author, description, and cover image manually.' );
			return;
		}

		const parsedAsin = extractASIN( amazonSource ) || normalizeISBN( asin ) || '';
		const parsedDomain = extractDomain( amazonSource ) || domain || 'amazon.com';
		setAttributes( { amazonUrl: amazonSource, rawUrl: amazonSource, asin: parsedAsin, domain: parsedDomain } );

		if ( ! isISBN( parsedAsin ) ) {
			setStatusMessage( 'The link did not contain a public ISBN. Kindle, region-specific, and non-book Amazon products may need manual details.' );
			return;
		}

		setIsFetching( true );
		setStatusMessage( 'Fetching title, author, and cover art from public sources...' );
		try {
			const metadata = await fetchBookMetadata( { isbn: parsedAsin } );
			if ( ! metadata ) {
				setStatusMessage( 'The ISBN was found, but Open Library, Google Books, and UPCitemdb returned no metadata. You can enter the remaining fields manually.' );
				return;
			}
			const isAmazonDomain = /amazon\./i.test( parsedDomain );
			setAttributes( {
				bookTitle: metadata.title || bookTitle,
				authorName: metadata.author || authorName,
				coverImage: metadata.coverImage || attributes.coverImage || '',
				// Prefer the real Amazon ASIN from UPCitemdb over the raw ISBN when we're linking to Amazon.
				asin: isAmazonDomain && metadata.asin ? metadata.asin : parsedAsin,
			} );
			setStatusMessage(
				metadata.title && metadata.author && metadata.coverImage
					? 'Metadata populated from public sources. Review and edit anything that needs adjustment.'
					: 'Some metadata was found, but not every field was available from public sources. Please review and fill in the rest manually.'
			);
		} catch {
			setStatusMessage( 'Public metadata lookup failed. You can fill the fields manually.' );
		} finally {
			setIsFetching( false );
		}
	};

	const searchByTitle = async () => {
		if ( ! titleQuery.trim() ) return;
		setIsSearchingTitle( true );
		setTitleResults( [] );
		setStatusMessage( 'Searching Concord Theatricals and Canadian Play Outlet...' );
		try {
			const results = await searchPlaysByTitle( titleQuery );
			setTitleResults( results );
			setStatusMessage( results.length ? `Found ${ results.length } result(s) below. Select one to populate the card.` : 'No matching plays or musicals were found on either site.' );
		} catch {
			setStatusMessage( 'The title search failed. You can fill the fields manually.' );
		} finally {
			setIsSearchingTitle( false );
		}
	};

	const applyTitleResult = ( result ) => {
		const referenceUrl = `https://${ result.domain }/${ result.path }`;
		setAttributes( {
			amazonUrl: referenceUrl,
			rawUrl: referenceUrl,
			asin: result.path,
			domain: result.domain,
			bookTitle: result.title || bookTitle,
			authorName: result.author || authorName,
			coverImage: result.coverImage || attributes.coverImage || '',
			description: result.description || attributes.description || '',
		} );
		setTitleResults( [] );
		setStatusMessage( `Populated from ${ result.source }. Review and edit anything that needs adjustment.` );
	};

	return (
		<>
			<InspectorControls>
				<PanelBody title="Book Details">
					<TextControl label="Title" value={ bookTitle } onChange={ ( value ) => setAttributes( { bookTitle: value } ) } />
					<TextControl label="Author" value={ authorName } onChange={ ( value ) => setAttributes( { authorName: value } ) } />
					<TextControl label="Raw URL" value={ rawUrl } onChange={ ( value ) => setAttributes( { rawUrl: value } ) } />
					<TextControl label="ASIN / ISBN" value={ asin } onChange={ ( value ) => setAttributes( { asin: value } ) } />
					<TextControl label="Amazon Domain" value={ domain } onChange={ ( value ) => setAttributes( { domain: value } ) } />
					<TextControl label="Affiliate Tag" value={ affiliateTag } onChange={ ( value ) => setAttributes( { affiliateTag: value } ) } />
					<TextControl label="Book Notes" value={ attributes.description || '' } onChange={ ( value ) => setAttributes( { description: value } ) } />
					<TextControl label="Amazon URL" value={ amazonUrl } onChange={ ( value ) => setAttributes( { amazonUrl: value } ) } onBlur={ populateFromLink } />
					<Button variant="primary" isBusy={ isFetching } disabled={ isFetching || ! amazonSource } onClick={ populateFromLink }>Auto-Fill From Link</Button>
					<TextControl label="Search Plays By Title" value={ titleQuery } onChange={ setTitleQuery } placeholder="e.g. Suburban Standoff" />
					<Button variant="secondary" isBusy={ isSearchingTitle } disabled={ isSearchingTitle || ! titleQuery.trim() } onClick={ searchByTitle }>Search Concord Theatricals &amp; Canadian Play Outlet</Button>
					{ titleResults.length > 0 && (
						<ul className="stagekitwp-bookshelf-item-editor__title-results">
							{ titleResults.map( ( result, index ) => (
								<li key={ `${ result.domain }-${ result.path }-${ index }` }>
									<Button variant="tertiary" onClick={ () => applyTitleResult( result ) }>
										{ result.title }{ result.author ? ` — ${ result.author }` : '' } ({ result.source })
									</Button>
								</li>
							) ) }
						</ul>
					) }
					<p><strong>ASIN:</strong> { attributes.asin || 'Not Found' }</p>
					<p><strong>Domain:</strong> { attributes.domain || 'Not Found' }</p>
					<p><strong>Affiliate URL Preview:</strong> { buildAffiliateUrl( { asin, domain, affiliateTag } ) || 'Not Available' }</p>
					<TextControl label="Cover Image URL" value={ attributes.coverImage || '' } onChange={ ( value ) => setAttributes( { coverImage: value } ) } />
					{ attributes.coverImage && <img src={ attributes.coverImage } alt={ bookTitle || 'Book cover preview' } style={ { maxWidth: '180px', height: 'auto', display: 'block' } } /> }
					<p className="description">{ statusMessage || 'Use the link above to auto-fill what public sources can find. You can always edit the title, author, and cover image manually.' }</p>
				</PanelBody>
				<PanelBody title="Appearance">
					<SelectControl label="Theme" value={ cardTheme } options={ [ { label: 'Light', value: 'light' }, { label: 'Dark', value: 'dark' } ] } onChange={ ( value ) => setAttributes( { cardTheme: value } ) } />
					<SelectControl label="Orientation" value={ cardOrientation } options={ [ { label: 'Horizontal', value: 'horizontal' }, { label: 'Vertical', value: 'vertical' } ] } onChange={ ( value ) => setAttributes( { cardOrientation: value } ) } />
					<SelectControl label="Alignment" value={ imageAlignment } options={ [ { label: 'Left', value: 'left' }, { label: 'Right', value: 'right' } ] } onChange={ ( value ) => setAttributes( { imageAlignment: value } ) } />
					<SelectControl label="Size" value={ cardSize } options={ [ { label: 'Compact', value: 'compact' }, { label: 'Normal', value: 'normal' }, { label: 'Maximum', value: 'max' } ] } onChange={ ( value ) => setAttributes( { cardSize: value } ) } />
				</PanelBody>
			</InspectorControls>
			<div { ...useBlockProps( { className: 'stagekitwp-bookshelf-item-editor' } ) }>
				<div className={ `stagekitwp-bookshelf-item-editor__visual card-${ cardSize } orientation-${ cardOrientation }` }>
					{ attributes.coverImage ? <img src={ attributes.coverImage } alt={ bookTitle || 'Book cover' } /> : <div className="stagekitwp-bookshelf-item-editor__cover-placeholder">{ cardOrientation === 'vertical' ? 'Book cover' : 'Book spine' }</div> }
				</div>
			</div>
		</>
	);
}
