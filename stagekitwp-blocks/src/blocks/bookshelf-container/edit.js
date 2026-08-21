import {
    InspectorControls,
    InnerBlocks,
    useBlockProps
} from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';

import {
    PanelBody,
    SelectControl,
    RangeControl
} from '@wordpress/components';

const ALLOWED = [
    'stagekitwp/bookshelf-item'
];

export default function Edit({ attributes, setAttributes, clientId }) {
    const childBlocks = useSelect(
        (select) => select('core/block-editor').getBlocks(clientId),
        [clientId]
    );

    const containerStyle = {
        '--stagekitwp-books-per-shelf': attributes.booksPerShelf,
        '--stagekitwp-shelf-height': `${attributes.shelfHeight}px`,
        '--stagekitwp-bookcase-width': `${attributes.bookcaseWidth}px`,
        '--stagekitwp-shelf-gap': `${attributes.shelfGap}px`
    };

    return (
        <>
            <InspectorControls>

                <PanelBody title="Bookshelf Layout">

                    <SelectControl
                        label="Layout"
                        value={attributes.layoutStyle}
                        options={[
                            {
                                label: 'Show Spines',
                                value: 'spines'
                            },
                            {
                                label: 'Show Covers',
                                value: 'covers'
                            }
                        ]}
                        onChange={(v) => {
                            setAttributes({
                                layoutStyle: v
                            });
                        }}
                    />

                    <RangeControl
                        label="Books Per Shelf"
                        value={attributes.booksPerShelf}
                        onChange={(value) => {
                            setAttributes({
                                booksPerShelf: value
                            });
                        }}
                        min={2}
                        max={10}
                        step={1}
                    />

                    <RangeControl
                        label="Shelf Height (px)"
                        value={attributes.shelfHeight}
                        onChange={(value) => {
                            setAttributes({
                                shelfHeight: value
                            });
                        }}
                        min={220}
                        max={560}
                        step={10}
                    />

                    <RangeControl
                        label="Bookcase Width (px)"
                        value={attributes.bookcaseWidth}
                        onChange={(value) => {
                            setAttributes({
                                bookcaseWidth: value
                            });
                        }}
                        min={200}
                        max={1800}
                        step={20}
                    />

                    <RangeControl
                        label="Shelf Gap (px)"
                        value={attributes.shelfGap}
                        onChange={(value) => {
                            setAttributes({
                                shelfGap: value
                            });
                        }}
                        min={0}
                        max={48}
                        step={2}
                    />
					<SelectControl
					    label="Shelf Theme"
					    value={attributes.shelfTheme}
					    options={[
					        {
					            label: 'Walnut',
					            value: 'walnut'
					        },
					        {
					            label: 'Oak',
					            value: 'oak'
					        },
					        {
					            label: 'Mahogany',
					            value: 'mahogany'
					        },
					        {
					            label: 'Ebony',
					            value: 'ebony'
					        }
					    ]}
					    onChange={(value) => {
					        setAttributes({
					            shelfTheme: value
					        });
					    }}
					/>
                </PanelBody>

            </InspectorControls>

            <div
                {...useBlockProps({
                    className: `stagekitwp-bookshelf-container layout-${attributes.layoutStyle} theme-${attributes.shelfTheme}`,
                    style: containerStyle
                })}
            >
                {!childBlocks.length && (
                    <div className="stagekitwp-bookshelf-editor-preview" aria-hidden="true">
                        <span className="stagekitwp-bookshelf-editor-preview__label">
                            {attributes.layoutStyle === 'covers' ? 'Cover gallery preview' : 'Book spine preview'}
                        </span>
                        <div className={`stagekitwp-bookshelf-editor-preview__books layout-${attributes.layoutStyle}`}>
                            {[...Array(Math.min(attributes.booksPerShelf || 4, 6))].map((_, index) => (
                                <span key={index} className="stagekitwp-bookshelf-editor-preview__book" />
                            ))}
                        </div>
                    </div>
                )}
                <InnerBlocks
                    allowedBlocks={ALLOWED}
                />
            </div>

        </>
    );
}