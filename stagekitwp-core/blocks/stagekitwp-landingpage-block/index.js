/**
 * StageKitWP - Show Landing Page Block
 * 
 * Vanilla JS implementation for Gutenberg block
 * Provides UI for the stagekitwp_landingpage shortcode configuration
 */

(function() {
    const el = wp.element.createElement;
    const { registerBlockType } = wp.blocks;
    const { InspectorControls, BlockControls } = wp.blockEditor;
    const { 
        PanelBody, TextControl, CheckboxControl, SelectControl, Button, Spinner
    } = wp.components;
    const { useState, useEffect } = wp.element;

    const AVAILABLE_FIELDS = [
        { id: 'show_name', label: 'Show Name', description: 'Title of the show' },
        { id: 'show_image', label: 'Show Image', description: 'Show poster/main image' },
        { id: 'author', label: 'Author', description: 'Primary playwright' },
        { id: 'sub_authors', label: 'Co-writers', description: 'Additional authors' },
        { id: 'director', label: 'Director', description: 'Director name' },
        { id: 'associate_director', label: 'Assistant Director', description: 'Associate/Assistant director' },
        { id: 'producer', label: 'Producer', description: 'Producer name' },
        { id: 'stage_manager', label: 'Stage Manager', description: 'Stage manager name' },
        { id: 'synopsis', label: 'Synopsis', description: 'Show description' },
        { id: 'show_dates', label: 'Show Dates', description: 'Performance dates and times' },
        { id: 'ticket_url', label: 'Ticket URL', description: 'Link to purchase tickets' },
        { id: 'cast', label: 'Cast List', description: 'Simple cast member list' },
        { id: 'castwithbio', label: 'Cast with Photos', description: 'Cast in responsive grid with photos' },
        { id: 'venue', label: 'Venue', description: 'Theatre location info' }
    ];

    const DEFAULT_FIELDS = ['show_name', 'show_image', 'author', 'director', 'producer', 'stage_manager', 'synopsis', 'show_dates', 'ticket_url', 'castwithbio', 'venue'];

    const BUTTON_FORMATS = [
        { label: 'Default', value: 'default' },
        { label: 'Modern', value: 'modern' },
        { label: 'Minimal', value: 'minimal' },
        { label: 'Outline', value: 'outline' },
        { label: 'Gradient', value: 'gradient' },
        { label: 'Prominent', value: 'prominent' },
        { label: 'Success', value: 'success' },
        { label: 'Ghost', value: 'ghost' },
        { label: 'Glass', value: 'glass' }
    ];

    registerBlockType('stagekitwp/landingpage', {
        title: 'Show Landing Page',
        description: 'Display comprehensive information about a theatre show',
        category: 'stagekitwp-core',
        icon: 'theater',
        keywords: ['show', 'theatre', 'landing', 'cast', 'venue'],
        
        attributes: {
            showId: { type: 'string', default: 'current' },
            showTitle: { type: 'string', default: 'Current Show' },
            fieldList: { type: 'array', default: DEFAULT_FIELDS },
            castcols: { type: 'number', default: 3 },
            urlbutton: { type: 'boolean', default: true },
            buttonformat: { type: 'string', default: 'default' },
            searchValue: { type: 'string', default: '' }
        },

        edit: function(props) {
            const { attributes, setAttributes } = props;
            const [shows, setShows] = useState([]);
            const [loading, setLoading] = useState(false);
            const [searchTerm, setSearchTerm] = useState(attributes.searchValue);
            const [filteredShows, setFilteredShows] = useState([]);
            const [showResults, setShowResults] = useState(false);
            const [draggedField, setDraggedField] = useState(null);

            useEffect(() => {
                setLoading(true);
                fetch('/wp-json/wp/v2/show?per_page=100')
                    .then(res => res.json())
                    .then(data => {
                        if (Array.isArray(data)) {
                            setShows(data);
                        }
                        setLoading(false);
                    })
                    .catch(() => setLoading(false));
            }, []);

            useEffect(() => {
                if (searchTerm) {
                    const filtered = shows.filter(show =>
                        show.title.rendered.toLowerCase().includes(searchTerm.toLowerCase())
                    );
                    setFilteredShows(filtered);
                    setShowResults(true);
                } else {
                    setShowResults(false);
                }
            }, [searchTerm, shows]);

            const selectShow = (showId, showTitle) => {
                setAttributes({ showId: showId.toString(), showTitle });
                setSearchTerm('');
                setShowResults(false);
            };

            const toggleField = (fieldId) => {
                const currentFields = attributes.fieldList || [];
                if (currentFields.includes(fieldId)) {
                    setAttributes({ fieldList: currentFields.filter(f => f !== fieldId) });
                } else {
                    setAttributes({ fieldList: [...currentFields, fieldId] });
                }
            };

            const moveField = (fromIndex, toIndex) => {
                const newFields = [...(attributes.fieldList || [])];
                const [moved] = newFields.splice(fromIndex, 1);
                newFields.splice(toIndex, 0, moved);
                setAttributes({ fieldList: newFields });
            };

            const shortcodeText = `[stagekitwp_landingpage show_id="${attributes.showId}" field_list="${(attributes.fieldList || []).join(',')}" castcols="${attributes.castcols}" urlbutton="${attributes.urlbutton ? 'true' : 'false'}" buttonformat="${attributes.buttonformat}"]`;

            return el('div', { className: 'stagekitwp-landingpage-block-editor' },
                el(InspectorControls, null,
                    el(PanelBody, { title: 'Show Selection', initialOpen: true },
                        el('div', { className: 'stagekitwp-show-search-wrapper' },
                            el(TextControl, {
                                label: 'Search Show',
                                value: searchTerm,
                                onChange: (value) => setSearchTerm(value),
                                placeholder: 'Type show name...'
                            }),
                            loading && el(Spinner),
                            showResults && filteredShows.length > 0 && el('div', { className: 'stagekitwp-show-results' },
                                filteredShows.slice(0, 5).map(show =>
                                    el('div', {
                                        key: show.id,
                                        className: 'stagekitwp-show-result-item',
                                        onClick: () => selectShow(show.id, show.title.rendered)
                                    }, show.title.rendered)
                                )
                            ),
                            el(Button, {
                                isSecondary: true,
                                onClick: () => selectShow('current', 'Current Show'),
                                style: { marginTop: '8px' }
                            }, 'Use Current Show')
                        ),
                        attributes.showId && el('div', {
                            style: { marginTop: '10px', padding: '8px', background: '#f0f0f0', borderRadius: '4px' }
                        },
                            el('strong', null, 'Selected: '), attributes.showTitle
                        )
                    ),
                    el(PanelBody, { title: 'Field Selection', initialOpen: true },
                        el('div', { className: 'stagekitwp-field-selector' },
                            el('p', { style: { fontSize: '12px', color: '#666' } }, 'Check fields to include. Drag to reorder.'),
                            el('div', { className: 'stagekitwp-fields-list' },
                                AVAILABLE_FIELDS.map((field, index) =>
                                    el('div', {
                                        key: field.id,
                                        className: `stagekitwp-field-item ${attributes.fieldList?.includes(field.id) ? 'active' : ''}`,
                                        draggable: attributes.fieldList?.includes(field.id),
                                        onDragStart: () => setDraggedField(index),
                                        onDragOver: (e) => e.preventDefault(),
                                        onDrop: () => {
                                            if (draggedField !== null && draggedField !== index) {
                                                moveField(draggedField, index);
                                                setDraggedField(null);
                                            }
                                        }
                                    },
                                        el(CheckboxControl, {
                                            label: field.label,
                                            help: field.description,
                                            checked: attributes.fieldList?.includes(field.id) || false,
                                            onChange: () => toggleField(field.id)
                                        })
                                    )
                                )
                            )
                        )
                    ),
                    attributes.fieldList?.includes('castwithbio') && el(PanelBody, { title: 'Cast Options', initialOpen: false },
                        el(TextControl, {
                            label: 'Cast Columns',
                            type: 'number',
                            min: '1',
                            max: '6',
                            value: attributes.castcols,
                            onChange: (value) => setAttributes({ castcols: parseInt(value) || 3 }),
                            help: 'Number of columns for cast grid (1-6)'
                        })
                    ),
                    attributes.fieldList?.includes('ticket_url') && el(PanelBody, { title: 'Ticket Button Options', initialOpen: false },
                        el(CheckboxControl, {
                            label: 'Display as Button',
                            checked: attributes.urlbutton,
                            onChange: (value) => setAttributes({ urlbutton: value })
                        }),
                        attributes.urlbutton && el(SelectControl, {
                            label: 'Button Style',
                            value: attributes.buttonformat,
                            options: BUTTON_FORMATS,
                            onChange: (value) => setAttributes({ buttonformat: value })
                        })
                    )
                ),
                el('div', { className: 'stagekitwp-landingpage-block-preview' },
                    el('div', {
                        style: { padding: '20px', background: '#fff', border: '1px solid #e0e0e0', borderRadius: '4px' }
                    },
                        el('h3', { style: { marginTop: 0 } }, 'Landing Page Preview'),
                        el('p', { style: { fontSize: '13px', color: '#666', marginBottom: '15px' } }, 'Generated shortcode will display all selected fields.'),
                        el('div', {
                            style: {
                                background: '#f8f8f8',
                                padding: '12px',
                                borderRadius: '4px',
                                fontFamily: 'monospace',
                                fontSize: '11px',
                                overflow: 'auto',
                                maxHeight: '100px'
                            }
                        }, shortcodeText),
                        el('div', {
                            style: {
                                marginTop: '15px',
                                padding: '12px',
                                background: '#e7f3ff',
                                borderLeft: '4px solid #0073aa',
                                borderRadius: '4px'
                            }
                        },
                            el('div', null, el('strong', null, 'Selected Show: '), attributes.showTitle),
                            el('div', null, el('strong', null, 'Selected Fields: '), (attributes.fieldList || []).length, ' of ', AVAILABLE_FIELDS.length),
                            attributes.fieldList?.includes('castwithbio') && el('div', null, el('strong', null, 'Cast Columns: '), attributes.castcols),
                            attributes.fieldList?.includes('ticket_url') && el('div', null, el('strong', null, 'Ticket Button: '), attributes.urlbutton ? `Yes (${attributes.buttonformat})` : 'No')
                        )
                    )
                )
            );
        },

        save: function(props) {
            const { attributes } = props;
            const shortcode = `[stagekitwp_landingpage show_id="${attributes.showId}" field_list="${(attributes.fieldList || []).join(',')}" castcols="${attributes.castcols}" urlbutton="${attributes.urlbutton ? 'true' : 'false'}" buttonformat="${attributes.buttonformat}"]`;
            return el('div', { dangerouslySetInnerHTML: { __html: shortcode } });
        }
    });
})();
