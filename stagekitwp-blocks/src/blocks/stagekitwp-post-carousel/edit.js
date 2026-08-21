import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl, SelectControl, ToggleControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import ServerSideRender from '@wordpress/server-side-render';
import metadata from './block.json';
import './editor.scss';

export default function Edit( { attributes, setAttributes } ) {
	const { postsPerPage, category, showExcerpt, autoplay, autoplayDelay, loop, colorMode } = attributes;

	const colorModeClass = colorMode && colorMode !== 'auto' ? `is-${colorMode}-mode` : '';
	const blockProps = useBlockProps( {
		className: colorModeClass,
		'data-color-mode': colorMode || 'auto',
	} );

	// Load categories for the sidebar dropdown controls
	const categories = useSelect( ( select ) => {
		return select( 'core' ).getEntityRecords( 'postType', 'category', { per_page: -1 } );
	}, [] );

	const categoryOptions = [
		{ label: __( 'All Categories', 'stagekitwp-blocks' ), value: '' },
		...( categories ? categories.map( ( cat ) => ( { label: cat.name, value: String( cat.id ) } ) ) : [] ),
	];

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Carousel Settings', 'stagekitwp-blocks' ) }>
					<RangeControl
						label={ __( 'Number of Posts', 'stagekitwp-blocks' ) }
						value={ postsPerPage }
						onChange={ ( value ) => setAttributes( { postsPerPage: value } ) }
						min={ 1 }
						max={ 20 }
					/>
					<SelectControl
						label={ __( 'Category', 'stagekitwp-blocks' ) }
						value={ category }
						options={ categoryOptions }
						onChange={ ( value ) => setAttributes( { category: value } ) }
					/>
					<ToggleControl
						label={ __( 'Show Excerpt', 'stagekitwp-blocks' ) }
						checked={ showExcerpt }
						onChange={ ( value ) => setAttributes( { showExcerpt: value } ) }
					/>
					<ToggleControl
						label={ __( 'Infinite Loop', 'stagekitwp-blocks' ) }
						checked={ loop }
						onChange={ ( value ) => setAttributes( { loop: value } ) }
					/>
					<ToggleControl
						label={ __( 'Auto Rotate', 'stagekitwp-blocks' ) }
						checked={ autoplay }
						onChange={ ( value ) => setAttributes( { autoplay: value } ) }
					/>
					{ autoplay && (
						<RangeControl
							label={ __( 'Delay (Seconds)', 'stagekitwp-blocks' ) }
							value={ autoplayDelay / 1000 }
							onChange={ ( value ) => setAttributes( { autoplayDelay: value * 1000 } ) }
							min={ 1 }
							max={ 10 }
							step={ 0.5 }
						/>
					) }
				</PanelBody>

				<PanelBody title={ __( 'Color Scheme', 'stagekitwp-blocks' ) } initialOpen={ false }>
					<SelectControl
						label={ __( 'Color Mode', 'stagekitwp-blocks' ) }
						value={ colorMode || 'auto' }
						options={ [
							{ label: __( 'Auto (Sync Theme / OS)', 'stagekitwp-blocks' ), value: 'auto' },
							{ label: __( 'Force Light Mode', 'stagekitwp-blocks' ), value: 'light' },
							{ label: __( 'Force Dark Mode', 'stagekitwp-blocks' ), value: 'dark' },
						] }
						onChange={ ( value ) => setAttributes( { colorMode: value } ) }
						help={ __( 'Auto will match StageKitWP/Theatre-Manager theme settings or OS preference.', 'stagekitwp-blocks' ) }
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<ServerSideRender
					block={ metadata.name }
					attributes={ attributes }
				/>
			</div>
		</>
	);
}