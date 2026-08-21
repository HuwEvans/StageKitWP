import {
    useBlockProps,
    InspectorControls
} from '@wordpress/block-editor';

import { useState } from '@wordpress/element';

import {
    PanelBody,
    TextControl,
    SelectControl
} from '@wordpress/components';

import {
    Button
} from '@wordpress/components';

import {
    extractASIN,
    extractDomain,
    buildAffiliateUrl,
    fetchBookMetadata
} from './amazon-parser';

import { isISBN } from './isbn-utils';

export default function Edit({ attributes, setAttributes }) {

    const [statusMessage, setStatusMessage] = useState('');
    const [isFetching, setIsFetching] = useState(false);

    const {
        bookTitle,
        authorName,
        rawUrl,
        amazonUrl,
        asin,
        domain,
        affiliateTag,
        cardTheme,
        cardOrientation,
        imageAlignment,
        cardSize
    } = attributes;

    const amazonSource = amazonUrl || rawUrl || '';

    const populateFromLink = async () => {
        const parsedAsin = extractASIN(amazonSource) || asin || '';
        const parsedDomain = extractDomain(amazonSource) || domain || 'amazon.com';

        setAttributes({
            amazonUrl: amazonSource,
            rawUrl: amazonSource,
            asin: parsedAsin,
            domain: parsedDomain
        });

        if (!isISBN(parsedAsin)) {
            setStatusMessage('The link parsed, but it did not look like a book ISBN. Fill the title, author, and cover manually if needed.');
            return;
        }

        setIsFetching(true);
        setStatusMessage('Fetching title, author, and cover art from public sources...');

        try {
            const metadata = await fetchBookMetadata({ isbn: parsedAsin });

            if (!metadata) {
                setStatusMessage('No public source returned a match. You can enter the remaining fields manually.');
                return;
            }

            setAttributes({
                bookTitle: metadata.title || bookTitle,
                authorName: metadata.author || authorName,
                coverImage: metadata.coverImage || attributes.coverImage || ''
            });

            setStatusMessage('Metadata populated from public sources. Review and edit anything that needs adjustment.');
        } catch {
            setStatusMessage('Public metadata lookup failed. You can fill the fields manually.');
        } finally {
            setIsFetching(false);
        }
    };

    return (
        <>
            <InspectorControls>

                <PanelBody title="Book Details">

                    <TextControl
                        label="Title"
                        value={bookTitle}
                        onChange={(v) => setAttributes({ bookTitle: v })}
                    />

                    <TextControl
                        label="Author"
                        value={authorName}
                        onChange={(v) => setAttributes({ authorName: v })}
                    />

                    <TextControl
                        label="Raw URL"
                        value={rawUrl}
                        onChange={(v) => setAttributes({ rawUrl: v })}
                    />

                    <TextControl
                        label="ASIN / ISBN"
                        value={asin}
                        onChange={(v) => setAttributes({ asin: v })}
                    />

                    <TextControl
                        label="Amazon Domain"
                        value={domain}
                        onChange={(v) => setAttributes({ domain: v })}
                    />

                    <TextControl
                        label="Affiliate Tag"
                        value={affiliateTag}
                        onChange={(v) => setAttributes({ affiliateTag: v })}
                    />

                    <TextControl
                        label="Book Notes"
                        value={attributes.description || ''}
                        onChange={(value) => setAttributes({ description: value })}
                        help="Optional notes or a short blurb to keep with this book."
                    />

                    <TextControl
                        label="Amazon URL"
                        value={amazonUrl}
                        onChange={(value) =>
                            setAttributes({
                                amazonUrl: value
                            })
                        }
                        onBlur={populateFromLink}
                    />

                    <Button
                        variant="primary"
                        isBusy={isFetching}
                        disabled={isFetching || !amazonSource}
                        onClick={populateFromLink}
                    >
                        Auto-Fill From Link
                    </Button>

                    <p>
                        <strong>ASIN:</strong>
                        {' '}
                        {attributes.asin || 'Not Found'}
                    </p>

                    <p>
                        <strong>Domain:</strong>
                        {' '}
                        {attributes.domain || 'Not Found'}
                    </p>

                    <p>
                        <strong>Affiliate URL Preview:</strong>
                        {' '}
                        {buildAffiliateUrl({
                            asin,
                            domain,
                            affiliateTag
                        }) || 'Not Available'}
                    </p>

                    <TextControl
                        label="Cover Image URL"
                        value={attributes.coverImage || ''}
                        onChange={(value) => setAttributes({ coverImage: value })}
                    />

                    {attributes.coverImage ? (
                        <div style={{ marginTop: '8px' }}>
                            <img
                                src={attributes.coverImage}
                                alt={bookTitle || 'Book cover preview'}
                                style={{ maxWidth: '180px', height: 'auto', display: 'block' }}
                            />
                        </div>
                    ) : null}

                    <p className="description">
                        {statusMessage || 'Use the link above to auto-fill what public sources can find. You can always edit the title, author, and cover image manually.'}
                    </p>
                </PanelBody>

                <PanelBody title="Appearance">

                    <SelectControl
                        label="Theme"
                        value={cardTheme}
                        options={[
                            { label: 'Light', value: 'light' },
                            { label: 'Dark', value: 'dark' }
                        ]}
                        onChange={(v) => setAttributes({ cardTheme: v })}
                    />

                    <SelectControl
                        label="Orientation"
                        value={cardOrientation}
                        options={[
                            { label: 'Horizontal', value: 'horizontal' },
                            { label: 'Vertical', value: 'vertical' }
                        ]}
                        onChange={(v) => setAttributes({ cardOrientation: v })}
                    />

                    <SelectControl
                        label="Alignment"
                        value={imageAlignment}
                        options={[
                            { label: 'Left', value: 'left' },
                            { label: 'Right', value: 'right' }
                        ]}
                        onChange={(v) => setAttributes({ imageAlignment: v })}
                    />

                    <SelectControl
                        label="Size"
                        value={cardSize}
                        options={[
                            { label: 'Compact', value: 'compact' },
                            { label: 'Normal', value: 'normal' },
                            { label: 'Maximum', value: 'max' }
                        ]}
                        onChange={(v) => setAttributes({ cardSize: v })}
                    />

                </PanelBody>

            </InspectorControls>

            <div {...useBlockProps({ className: 'stagekitwp-bookshelf-item-editor' })}>
                <div className={`stagekitwp-bookshelf-item-editor__visual card-${cardSize} orientation-${cardOrientation}`}>
                    {attributes.coverImage ? (
                        <img
                            src={attributes.coverImage}
                            alt={bookTitle || 'Book cover'}
                        />
                    ) : (
                        <div className="stagekitwp-bookshelf-item-editor__cover-placeholder">
                            {cardOrientation === 'vertical' ? 'Book cover' : 'Book spine'}
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}