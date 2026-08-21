
import {
    useBlockProps,
    RichText
} from '@wordpress/block-editor';

import {
    buildAffiliateUrl
} from './amazon-parser';


export default function save( { attributes } ) {

    const {
        bookTitle,
        authorName,
        asin,
        domain,
        affiliateTag,
        coverImage
    } = attributes;

    const affiliateUrl = buildAffiliateUrl({
        asin,
        domain,
        affiliateTag
    });

    return (
        <div
            { ...useBlockProps.save( {
                className: 'stagekitwp-bookshelf-item'
            } ) }
        >
            <a
                className="stagekitwp-bookshelf-card"
                data-book-title={bookTitle || ''}
                data-author-name={authorName || ''}
                href={affiliateUrl || '#'}
                rel="nofollow sponsored noopener noreferrer"
                target="_blank"
            >

                <div className="stagekitwp-bookshelf-cover-wrap">
                    {coverImage ? (
                        <img
                            src={coverImage}
                            alt={bookTitle || 'Book cover'}
                        />
                    ) : (
                        <div className="stagekitwp-cover-fallback">
                            📖
                        </div>
                    )}
                </div>

                <div className="stagekitwp-bookshelf-metadata">
<RichText.Content
    tagName="h3"
    value={bookTitle}
/>

<RichText.Content
    tagName="p"
    value={authorName}
/>
                </div>

            </a>
        </div>
    );
}