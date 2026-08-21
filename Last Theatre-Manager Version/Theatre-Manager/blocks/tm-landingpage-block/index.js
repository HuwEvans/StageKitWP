/**
 * Theatre Manager - Show Landing Page Block
 * 
 * Vanilla JS implementation for Gutenberg block
 * Provides UI for tm_landingpage shortcode configuration
 */

(function() {
    const el = wp.element.createElement;
    const { registerBlockType } = wp.blocks;
    const { InspectorControls, BlockControls } = wp.blockEditor;
    const { 
        PanelBody, TextControl, CheckboxControl, SelectControl, Button, Spinner, ColorPalette
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

    const CAST_IMAGE_SIZES = [
        { label: 'Small', value: 'small' },
        { label: 'Medium', value: 'medium' },
        { label: 'Large', value: 'large' }
    ];

    const TEXT_ALIGN_OPTIONS = [
        { label: 'Inherit', value: 'inherit' },
        { label: 'Left', value: 'left' },
        { label: 'Center', value: 'center' },
        { label: 'Right', value: 'right' },
        { label: 'Justify', value: 'justify' }
    ];

    const FONT_FAMILY_OPTIONS = [
        { label: 'Inherit Theme Font', value: 'inherit' },
        { label: 'System UI', value: 'system' },
        { label: 'Sans Serif', value: 'sans' },
        { label: 'Serif', value: 'serif' },
        { label: 'Georgia', value: 'georgia' },
        { label: 'Times New Roman', value: 'times' },
        { label: 'Trebuchet MS', value: 'trebuchet' }
    ];

    const TEXT_SIZE_OPTIONS = [
        { label: 'Inherit', value: 'inherit' },
        { label: 'Small', value: 'small' },
        { label: 'Medium', value: 'medium' },
        { label: 'Large', value: 'large' },
        { label: 'Extra Large', value: 'xlarge' }
    ];

    const FONT_FAMILY_PREVIEW_STYLE = {
        inherit: 'inherit',
        system: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
        sans: 'Arial, Helvetica, sans-serif',
        serif: 'Georgia, "Times New Roman", serif',
        georgia: 'Georgia, serif',
        times: '"Times New Roman", Times, serif',
        trebuchet: '"Trebuchet MS", Helvetica, sans-serif'
    };

    const TEXT_SIZE_PREVIEW_STYLE = {
        inherit: 'inherit',
        small: '0.9rem',
        medium: '1rem',
        large: '1.15rem',
        xlarge: '1.3rem'
    };

    const decodeEntities = (value) => {
        if (typeof value !== 'string') {
            return '';
        }

        const textarea = document.createElement('textarea');
        textarea.innerHTML = value;
        return textarea.value;
    };

    const getShowTitleText = (show) => {
        const rawTitle = show && show.title ? (show.title.rendered || show.title) : '';
        const titleText = decodeEntities(String(rawTitle || '')).replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();

        if (titleText) {
            return titleText;
        }

        if (show && typeof show.slug === 'string' && show.slug.trim()) {
            return show.slug
                .replace(/[-_]+/g, ' ')
                .replace(/\s+/g, ' ')
                .trim()
                .replace(/\b\w/g, (char) => char.toUpperCase());
        }

        return '';
    };

    const normalizeSearchText = (value) => decodeEntities(String(value || ''))
        .replace(/<[^>]*>/g, ' ')
        .replace(/[-_]+/g, ' ')
        .replace(/\s+/g, ' ')
        .trim()
        .toLowerCase();

    const buildShowSearchText = (show) => {
        const parts = [
            getShowTitleText(show),
            show && typeof show.slug === 'string' ? show.slug : '',
            show && show.guid && typeof show.guid.rendered === 'string' ? show.guid.rendered : '',
            show && show.acf && typeof show.acf.show_name === 'string' ? show.acf.show_name : '',
            show && show.meta && typeof show.meta.show_name === 'string' ? show.meta.show_name : ''
        ];

        return normalizeSearchText(parts.join(' '));
    };

    registerBlockType('theatre-manager/landingpage', {
        title: 'Show Landing Page',
        description: 'Display comprehensive information about a theatre show',
        category: 'common',
        icon: 'theater',
        keywords: ['show', 'theatre', 'landing', 'cast', 'venue'],
        
        attributes: {
            showId: { type: 'string', default: 'current' },
            showTitle: { type: 'string', default: 'Current Show' },
            fieldList: { type: 'array', default: DEFAULT_FIELDS },
            castcols: { type: 'number', default: 3 },
            castImageSize: { type: 'string', default: 'large' },
            urlbutton: { type: 'boolean', default: true },
            buttonformat: { type: 'string', default: 'default' },
            searchValue: { type: 'string', default: '' },
            textAlign: { type: 'string', default: 'inherit' },
            fontFamily: { type: 'string', default: 'inherit' },
            textSize: { type: 'string', default: 'inherit' },
            textColor: { type: 'string', default: '#333333' },
            backgroundColor: { type: 'string', default: '#ffffff' },
            accentColor: { type: 'string', default: '#0073aa' },
            headingColor: { type: 'string', default: '#1a1a1a' }
        },

        edit: function(props) {
            const { attributes, setAttributes } = props;
            const [shows, setShows] = useState([]);
            const [loading, setLoading] = useState(false);
            const [searchTerm, setSearchTerm] = useState(attributes.searchValue);
            const [filteredShows, setFilteredShows] = useState([]);
            const [showResults, setShowResults] = useState(false);
            const [draggedField, setDraggedField] = useState(null);
            const [fetchError, setFetchError] = useState(null);

            useEffect(() => {
                let isMounted = true;

                setLoading(true);
                setFetchError(null);

                const fetchAllShows = async () => {
                    try {
                        const firstResponse = await fetch('/wp-json/wp/v2/show?per_page=100&page=1');
                        if (!firstResponse.ok) {
                            throw new Error(`HTTP error! status: ${firstResponse.status}`);
                        }

                        const firstData = await firstResponse.json();
                        if (!Array.isArray(firstData)) {
                            if (firstData && firstData.code === 'rest_no_route') {
                                throw new Error('Show REST endpoint not available. Make sure show CPT has show_in_rest enabled.');
                            }
                            throw new Error('Unexpected response while loading shows.');
                        }

                        const totalPages = parseInt(firstResponse.headers.get('X-WP-TotalPages') || '1', 10);
                        let allShows = [...firstData];

                        if (totalPages > 1) {
                            const pageRequests = [];
                            for (let page = 2; page <= totalPages; page++) {
                                pageRequests.push(fetch(`/wp-json/wp/v2/show?per_page=100&page=${page}`));
                            }

                            const pageResponses = await Promise.all(pageRequests);
                            for (const response of pageResponses) {
                                if (!response.ok) {
                                    throw new Error(`HTTP error! status: ${response.status}`);
                                }

                                const pageData = await response.json();
                                if (Array.isArray(pageData)) {
                                    allShows = allShows.concat(pageData);
                                }
                            }
                        }

                        if (isMounted) {
                            setShows(allShows);
                            setFetchError(null);
                            setLoading(false);
                        }
                    } catch (error) {
                        console.error('Error fetching shows:', error);
                        if (isMounted) {
                            setFetchError(`Error loading shows: ${error.message}`);
                            setShows([]);
                            setLoading(false);
                        }
                    }
                };

                fetchAllShows();

                return () => {
                    isMounted = false;
                };
            }, []);

            useEffect(() => {
                const normalizedSearch = normalizeSearchText(searchTerm);

                if (normalizedSearch && shows.length > 0) {
                    const filtered = shows.filter(show => {
                        const searchableText = buildShowSearchText(show);
                        return searchableText.includes(normalizedSearch);
                    });
                    setFilteredShows(filtered);
                    setShowResults(true);
                } else {
                    setFilteredShows([]);
                    setShowResults(false);
                }
            }, [searchTerm, shows]);

            const selectShow = (showId, showTitle) => {
                setAttributes({ showId: showId.toString(), showTitle });
                setSearchTerm('');
                setShowResults(false);
            };

            const startShowSearch = () => {
                setAttributes({ showId: '', showTitle: '' });
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

            const shortcodeText = `[tm_landingpage show_id="${attributes.showId}" field_list="${(attributes.fieldList || []).join(',')}" castcols="${attributes.castcols}" cast_image_size="${attributes.castImageSize}" urlbutton="${attributes.urlbutton ? 'true' : 'false'}" buttonformat="${attributes.buttonformat}" text_align="${attributes.textAlign}" font_family="${attributes.fontFamily}" text_size="${attributes.textSize}" text_color="${attributes.textColor}" bg_color="${attributes.backgroundColor}" accent_color="${attributes.accentColor}" heading_color="${attributes.headingColor}"]`;

            return el('div', { className: 'tm-landingpage-block-editor' },
                el(InspectorControls, null,
                    el(PanelBody, { title: 'Show Selection', initialOpen: true },
                        el('div', { className: 'tm-show-search-wrapper' },
                            fetchError && el('div', {
                                style: {
                                    padding: '10px',
                                    background: '#fee',
                                    border: '1px solid #fcc',
                                    borderRadius: '4px',
                                    color: '#c33',
                                    fontSize: '12px',
                                    marginBottom: '12px'
                                }
                            }, `Error: ${fetchError}`),
                            el(TextControl, {
                                label: 'Search Show',
                                value: searchTerm,
                                onChange: (value) => setSearchTerm(value),
                                placeholder: 'Type show name...'
                            }),
                            loading && el(Spinner),
                            !fetchError && shows.length === 0 && !loading && el('div', {
                                style: {
                                    padding: '10px',
                                    background: '#f5f5f5',
                                    borderRadius: '4px',
                                    color: '#666',
                                    fontSize: '12px'
                                }
                            }, 'No shows found. Create some shows in the admin panel.'),
                            showResults && filteredShows.length > 0 && el('div', { className: 'tm-show-results' },
                                filteredShows.slice(0, 5).map(show => {
                                    const showTitle = getShowTitleText(show) || 'Untitled';
                                    return el('div', {
                                        key: show.id,
                                        className: 'tm-show-result-item',
                                        onClick: () => selectShow(show.id, showTitle)
                                    }, showTitle);
                                })
                            ),
                            showResults && filteredShows.length === 0 && el('div', {
                                style: {
                                    padding: '10px',
                                    background: '#f5f5f5',
                                    borderRadius: '4px',
                                    color: '#666',
                                    fontSize: '12px'
                                }
                            }, 'No shows match your search.'),
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
                    el(PanelBody, { title: 'Color Settings', initialOpen: false },
                        el('div', null,
                            el('p', { style: { fontSize: '12px', marginBottom: '12px' } }, 'Choose colors for the landing page display:'),
                            el('div', { style: { marginBottom: '16px' } },
                                el('label', { style: { display: 'block', marginBottom: '8px', fontWeight: '500', fontSize: '13px' } }, 'Heading Color'),
                                el(ColorPalette, {
                                    colors: [
                                        { name: 'Black', color: '#1a1a1a' },
                                        { name: 'Dark Gray', color: '#333333' },
                                        { name: 'Blue', color: '#0073aa' },
                                        { name: 'Purple', color: '#6f2da8' },
                                        { name: 'Dark Green', color: '#174e3e' }
                                    ],
                                    value: attributes.headingColor,
                                    onChange: (color) => setAttributes({ headingColor: color })
                                })
                            ),
                            el('div', { style: { marginBottom: '16px' } },
                                el('label', { style: { display: 'block', marginBottom: '8px', fontWeight: '500', fontSize: '13px' } }, 'Text Color'),
                                el(ColorPalette, {
                                    colors: [
                                        { name: 'Black', color: '#000000' },
                                        { name: 'Dark Gray', color: '#333333' },
                                        { name: 'Gray', color: '#666666' },
                                        { name: 'Navy Blue', color: '#0a3a4a' },
                                        { name: 'Dark Brown', color: '#3d2817' }
                                    ],
                                    value: attributes.textColor,
                                    onChange: (color) => setAttributes({ textColor: color })
                                })
                            ),
                            el('div', { style: { marginBottom: '16px' } },
                                el('label', { style: { display: 'block', marginBottom: '8px', fontWeight: '500', fontSize: '13px' } }, 'Accent Color (Links & Highlights)'),
                                el(ColorPalette, {
                                    colors: [
                                        { name: 'Blue', color: '#0073aa' },
                                        { name: 'Green', color: '#00a32a' },
                                        { name: 'Purple', color: '#6f2da8' },
                                        { name: 'Orange', color: '#d97f3a' },
                                        { name: 'Red', color: '#c63d43' }
                                    ],
                                    value: attributes.accentColor,
                                    onChange: (color) => setAttributes({ accentColor: color })
                                })
                            ),
                            el('div', { style: { marginBottom: '0' } },
                                el('label', { style: { display: 'block', marginBottom: '8px', fontWeight: '500', fontSize: '13px' } }, 'Background Color'),
                                el(ColorPalette, {
                                    colors: [
                                        { name: 'White', color: '#ffffff' },
                                        { name: 'Off-White', color: '#f9f9f9' },
                                        { name: 'Light Gray', color: '#f5f5f5' },
                                        { name: 'Cream', color: '#faf7f2' },
                                        { name: 'Light Blue', color: '#f0f7ff' }
                                    ],
                                    value: attributes.backgroundColor,
                                    onChange: (color) => setAttributes({ backgroundColor: color })
                                })
                            )
                        )
                    ),
                    el(PanelBody, { title: 'Typography', initialOpen: false },
                        el(SelectControl, {
                            label: 'Text Alignment',
                            value: attributes.textAlign,
                            options: TEXT_ALIGN_OPTIONS,
                            onChange: (value) => setAttributes({ textAlign: value })
                        }),
                        el(SelectControl, {
                            label: 'Font Family',
                            value: attributes.fontFamily,
                            options: FONT_FAMILY_OPTIONS,
                            onChange: (value) => setAttributes({ fontFamily: value })
                        }),
                        el(SelectControl, {
                            label: 'Text Size',
                            value: attributes.textSize,
                            options: TEXT_SIZE_OPTIONS,
                            onChange: (value) => setAttributes({ textSize: value })
                        })
                    ),
                    el(PanelBody, { title: 'Field Selection', initialOpen: true },
                        el('div', { className: 'tm-field-selector' },
                            el('p', { style: { fontSize: '12px', color: '#666' } }, 'Check fields to include. Drag to reorder.'),
                            el('div', { className: 'tm-fields-list' },
                                AVAILABLE_FIELDS.map((field, index) =>
                                    el('div', {
                                        key: field.id,
                                        className: `tm-field-item ${attributes.fieldList?.includes(field.id) ? 'active' : ''}`,
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
                        }),
                        el(SelectControl, {
                            label: 'Cast Image Size',
                            value: attributes.castImageSize,
                            options: CAST_IMAGE_SIZES,
                            onChange: (value) => setAttributes({ castImageSize: value }),
                            help: 'Large = 100% width, Medium = 75%, Small = 50% within each cast column.'
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
                el('div', { className: 'tm-landingpage-block-preview' },
                    el('div', {
                        style: { 
                            padding: '20px', 
                            background: attributes.backgroundColor, 
                            border: `2px solid ${attributes.accentColor}`, 
                            borderRadius: '4px',
                            color: attributes.textColor,
                            textAlign: attributes.textAlign,
                            fontFamily: FONT_FAMILY_PREVIEW_STYLE[attributes.fontFamily] || 'inherit',
                            fontSize: TEXT_SIZE_PREVIEW_STYLE[attributes.textSize] || 'inherit'
                        }
                    },
                        el('div', {
                            style: {
                                marginBottom: '20px',
                                padding: '15px',
                                background: 'rgba(0,0,0,0.03)',
                                borderLeft: `4px solid ${attributes.accentColor}`,
                                borderRadius: '4px'
                            }
                        },
                            el('label', { style: { fontSize: '11px', color: '#666', fontWeight: '600', display: 'block', marginBottom: '8px' } }, 'SHOW SELECTION'),
                            fetchError && el('div', {
                                style: {
                                    padding: '12px',
                                    background: '#fee',
                                    border: '1px solid #fcc',
                                    borderRadius: '4px',
                                    color: '#c33',
                                    fontSize: '12px',
                                    marginBottom: '12px'
                                }
                            }, `Error: ${fetchError}`),
                            attributes.showId ? 
                                el('div', null,
                                    el('div', { style: { fontSize: '16px', fontWeight: '600', color: attributes.headingColor, marginBottom: '8px' } }, attributes.showTitle),
                                    el('div', null,
                                        el(Button, {
                                            isSecondary: true,
                                            onClick: startShowSearch,
                                            style: { marginTop: '8px' }
                                        }, 'Change Show')
                                    )
                                )
                                :
                                el('div', null,
                                    loading && el('div', {
                                        style: {
                                            display: 'flex',
                                            alignItems: 'center',
                                            gap: '8px',
                                            padding: '12px',
                                            background: '#f5f5f5',
                                            borderRadius: '4px',
                                            color: '#666',
                                            fontSize: '13px',
                                            marginBottom: '12px'
                                        }
                                    }, 'Loading shows...', el(Spinner)),
                                    !loading && el('div', { style: { fontSize: '13px', color: '#666', marginBottom: '12px' } }, 'Click below to select a show:'),
                                    el('div', null,
                                        el(TextControl, {
                                            label: '',
                                            value: searchTerm,
                                            onChange: (value) => setSearchTerm(value),
                                            placeholder: 'Type show name...',
                                            style: { margin: '0' }
                                        })
                                    ),
                                    showResults && filteredShows.length > 0 && el('div', { 
                                        style: { 
                                            marginTop: '8px',
                                            background: '#fff',
                                            border: `1px solid ${attributes.accentColor}`,
                                            borderRadius: '4px',
                                            maxHeight: '150px',
                                            overflow: 'auto'
                                        } 
                                    },
                                        filteredShows.slice(0, 5).map(show => {
                                            const showTitle = getShowTitleText(show) || 'Untitled';
                                            return el('div', {
                                                key: show.id,
                                                style: {
                                                    padding: '10px 12px',
                                                    borderBottom: '1px solid #eee',
                                                    cursor: 'pointer',
                                                    fontSize: '13px',
                                                    transition: 'background-color 0.2s'
                                                },
                                                onMouseEnter: (e) => e.target.style.backgroundColor = `${attributes.accentColor}22`,
                                                onMouseLeave: (e) => e.target.style.backgroundColor = 'transparent',
                                                onClick: () => selectShow(show.id, showTitle)
                                            }, showTitle);
                                        })
                                    ),
                                    showResults && filteredShows.length === 0 && el('div', {
                                        style: {
                                            marginTop: '8px',
                                            padding: '10px',
                                            background: '#f5f5f5',
                                            borderRadius: '4px',
                                            color: '#666',
                                            fontSize: '12px'
                                        }
                                    }, 'No shows match your search.'),
                                    !searchTerm && shows.length === 0 && !loading && el('div', {
                                        style: {
                                            marginTop: '8px',
                                            padding: '10px',
                                            background: '#f5f5f5',
                                            borderRadius: '4px',
                                            color: '#666',
                                            fontSize: '12px'
                                        }
                                    }, 'No shows available. Create some in the admin panel.'),
                                    el(Button, {
                                        isPrimary: true,
                                        onClick: () => selectShow('current', 'Current Show'),
                                        style: { marginTop: '12px' }
                                    }, 'Use Current Show')
                                )
                        ),
                        el('h3', { style: { marginTop: '20px', marginBottom: '0', color: attributes.headingColor } }, 'Preview Settings'),
                        el('p', { style: { fontSize: '13px', color: '#666', marginBottom: '15px' } }, 'Generated shortcode will display all selected fields with your color choices.'),
                        el('div', {
                            style: {
                                background: '#f8f8f8',
                                padding: '12px',
                                borderRadius: '4px',
                                fontFamily: 'monospace',
                                fontSize: '11px',
                                overflow: 'auto',
                                maxHeight: '120px',
                                color: '#333',
                                border: '1px solid #ddd'
                            }
                        }, shortcodeText),
                        el('div', {
                            style: {
                                marginTop: '15px',
                                padding: '12px',
                                background: `${attributes.accentColor}15`,
                                borderLeft: `4px solid ${attributes.accentColor}`,
                                borderRadius: '4px',
                                color: attributes.textColor
                            }
                        },
                            el('div', null, el('strong', { style: { color: attributes.headingColor } }, 'Selected Show: '), attributes.showTitle),
                            el('div', null, el('strong', { style: { color: attributes.headingColor } }, 'Selected Fields: '), (attributes.fieldList || []).length, ' of ', AVAILABLE_FIELDS.length),
                            attributes.fieldList?.includes('castwithbio') && el('div', null, el('strong', { style: { color: attributes.headingColor } }, 'Cast Columns: '), attributes.castcols),
                            attributes.fieldList?.includes('castwithbio') && el('div', null, el('strong', { style: { color: attributes.headingColor } }, 'Cast Image Size: '), attributes.castImageSize),
                            attributes.fieldList?.includes('ticket_url') && el('div', null, el('strong', { style: { color: attributes.headingColor } }, 'Ticket Button: '), attributes.urlbutton ? `Yes (${attributes.buttonformat})` : 'No'),
                            el('div', null, el('strong', { style: { color: attributes.headingColor } }, 'Text Align: '), attributes.textAlign),
                            el('div', null, el('strong', { style: { color: attributes.headingColor } }, 'Font Family: '), attributes.fontFamily),
                            el('div', null, el('strong', { style: { color: attributes.headingColor } }, 'Text Size: '), attributes.textSize),
                            el('div', { style: { marginTop: '10px', paddingTop: '10px', borderTop: `1px solid ${attributes.accentColor}33` } },
                                el('div', { style: { fontSize: '12px', fontWeight: '600', marginBottom: '6px', color: attributes.headingColor } }, 'Color Scheme:'),
                                el('div', { style: { display: 'flex', gap: '8px', fontSize: '11px' } },
                                    el('div', null, el('strong', null, 'Heading:')), 
                                    el('span', { style: { display: 'inline-block', width: '16px', height: '16px', background: attributes.headingColor, borderRadius: '2px', border: '1px solid #ccc' } })
                                ),
                                el('div', { style: { display: 'flex', gap: '8px', fontSize: '11px', marginTop: '4px' } },
                                    el('div', null, el('strong', null, 'Text:')), 
                                    el('span', { style: { display: 'inline-block', width: '16px', height: '16px', background: attributes.textColor, borderRadius: '2px', border: '1px solid #ccc' } })
                                ),
                                el('div', { style: { display: 'flex', gap: '8px', fontSize: '11px', marginTop: '4px' } },
                                    el('div', null, el('strong', null, 'Accent:')), 
                                    el('span', { style: { display: 'inline-block', width: '16px', height: '16px', background: attributes.accentColor, borderRadius: '2px', border: '1px solid #ccc' } })
                                )
                            )
                        )
                    )
                )
            );
        },

        save: function(props) {
            const { attributes } = props;
            const shortcode = `[tm_landingpage show_id="${attributes.showId}" field_list="${(attributes.fieldList || []).join(',')}" castcols="${attributes.castcols}" cast_image_size="${attributes.castImageSize}" urlbutton="${attributes.urlbutton ? 'true' : 'false'}" buttonformat="${attributes.buttonformat}" text_align="${attributes.textAlign}" font_family="${attributes.fontFamily}" text_size="${attributes.textSize}" text_color="${attributes.textColor}" bg_color="${attributes.backgroundColor}" accent_color="${attributes.accentColor}" heading_color="${attributes.headingColor}"]`;
            return el('div', { dangerouslySetInnerHTML: { __html: shortcode } });
        }
    });
})();
