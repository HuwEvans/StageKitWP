import {
    InnerBlocks,
    useBlockProps
} from '@wordpress/block-editor';

export default function save({ attributes }) {

    const containerStyle = {
        '--stagekitwp-books-per-shelf': attributes.booksPerShelf,
        '--stagekitwp-shelf-height': `${attributes.shelfHeight}px`,
        '--stagekitwp-bookcase-width': `${attributes.bookcaseWidth}px`,
        '--stagekitwp-shelf-gap': `${attributes.shelfGap}px`
    };

    return (
        <div
            {...useBlockProps.save({
                className:
    `stagekitwp-bookshelf-container layout-${attributes.layoutStyle} theme-${attributes.shelfTheme}`,
                style: containerStyle
            })}
        >
            <InnerBlocks.Content />
        </div>
    );
}