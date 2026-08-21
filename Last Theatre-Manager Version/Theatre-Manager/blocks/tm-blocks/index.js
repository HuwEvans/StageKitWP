/**
 * Theatre Manager Blocks - Master Registration
 * 
 * Registers all Theatre Manager shortcode blocks under unified group
 */

(function() {
    const el = wp.element.createElement;
    const { registerBlockType } = wp.blocks;
    const { InspectorControls, BlockControls } = wp.blockEditor;
    const { 
        PanelBody, TextControl, SelectControl, CheckboxControl, Button, 
        Spinner, Notice, ToggleControl, RangeControl, TextareaControl
    } = wp.components;
    const { useState, useEffect } = wp.element;

    /**
     * Block Configuration for all Theatre Manager shortcodes
     * Note: Landing Page block is registered separately as standalone block for better UX
     * This system handles the other 19 blocks
     */
    const BLOCKS_CONFIG = [
        {
            name: 'theatre-manager/tm-shows',
            title: 'Shows List',
            description: 'Display all shows grouped by season',
            icon: 'list-view',
            category: 'theatre-manager',
            fields: [
                { id: 'exclude', label: 'Exclude Fields', type: 'text', placeholder: 'Comma-separated field names' },
                { id: 'season_id', label: 'Season', type: 'select', options: [] },
                { id: 'which', label: 'Show Filter', type: 'select', options: ['all', 'current', 'next', 'current_and_next'] }
            ],
            defaults: { exclude: '', which: 'all' }
        },
        {
            name: 'theatre-manager/tm-cast',
            title: 'Cast List',
            description: 'Display cast members with details',
            icon: 'groups',
            category: 'theatre-manager',
            fields: [
                { id: 'exclude', label: 'Exclude Fields', type: 'text', placeholder: 'Comma-separated field names' },
                { id: 'show_id', label: 'Show', type: 'select', options: [] },
                { id: 'group_by', label: 'Group By', type: 'select', options: ['none', 'show'] },
                { id: 'orderby', label: 'Order By', type: 'select', options: ['title', 'date', 'meta_value'] },
                { id: 'order', label: 'Order', type: 'select', options: ['ASC', 'DESC'] }
            ],
            defaults: { group_by: 'show', orderby: 'title', order: 'ASC' }
        },
        {
            name: 'theatre-manager/tm-board-members',
            title: 'Board Members',
            description: 'Display board member listings with photos and roles',
            icon: 'businessman',
            category: 'theatre-manager',
            fields: [
                { id: 'exclude', label: 'Exclude Fields', type: 'text', placeholder: 'Comma-separated field names' }
            ],
            defaults: {}
        },
        {
            name: 'theatre-manager/tm-sponsors',
            title: 'Sponsors',
            description: 'Display sponsor listings with logos',
            icon: 'awards',
            category: 'theatre-manager',
            fields: [
                { id: 'exclude', label: 'Exclude Fields', type: 'text', placeholder: 'Comma-separated field names' },
                { id: 'orderby', label: 'Order By', type: 'select', options: ['title', 'date'] }
            ],
            defaults: { orderby: 'title' }
        },
        {
            name: 'theatre-manager/tm-advertisers',
            title: 'Advertisers',
            description: 'Display advertiser listings with business info',
            icon: 'megaphone',
            category: 'theatre-manager',
            fields: [
                { id: 'exclude', label: 'Exclude Fields', type: 'text', placeholder: 'Comma-separated field names' }
            ],
            defaults: {}
        },
        {
            name: 'theatre-manager/tm-seasons',
            title: 'Seasons',
            description: 'Display theatre seasons with schedules',
            icon: 'calendar',
            category: 'theatre-manager',
            fields: [
                { id: 'exclude', label: 'Exclude Fields', type: 'text', placeholder: 'Comma-separated field names' }
            ],
            defaults: {}
        },
        {
            name: 'theatre-manager/tm-venues',
            title: 'Venues',
            description: 'Display theatre venue information',
            icon: 'location',
            category: 'theatre-manager',
            fields: [
                { id: 'exclude', label: 'Exclude Fields', type: 'text', placeholder: 'Comma-separated field names' }
            ],
            defaults: {}
        },
        {
            name: 'theatre-manager/tm-testimonials',
            title: 'Testimonials',
            description: 'Display audience testimonials and ratings',
            icon: 'format-quote',
            category: 'theatre-manager',
            fields: [
                { id: 'exclude', label: 'Exclude Fields', type: 'text', placeholder: 'Comma-separated field names' },
                { id: 'limit', label: 'Limit', type: 'number', min: 1, default: -1 }
            ],
            defaults: { limit: -1 }
        },
        {
            name: 'theatre-manager/tm-awards',
            title: 'Awards',
            description: 'Display show awards and recognitions',
            icon: 'star-filled',
            category: 'theatre-manager',
            fields: [
                { id: 'exclude', label: 'Exclude Fields', type: 'text', placeholder: 'Comma-separated field names' }
            ],
            defaults: {}
        },
        {
            name: 'theatre-manager/tm-contributors',
            title: 'Contributors',
            description: 'Display staff and volunteer contributors',
            icon: 'heart',
            category: 'theatre-manager',
            fields: [
                { id: 'exclude', label: 'Exclude Fields', type: 'text', placeholder: 'Comma-separated field names' }
            ],
            defaults: {}
        },
        {
            name: 'theatre-manager/tm-auditions',
            title: 'Auditions',
            description: 'Display audition information and schedules',
            icon: 'microphone',
            category: 'theatre-manager',
            fields: [
                { id: 'exclude', label: 'Exclude Fields', type: 'text', placeholder: 'Comma-separated field names' }
            ],
            defaults: {}
        },
        {
            name: 'theatre-manager/tm-sponsor-slider',
            title: 'Sponsor Slider',
            description: 'Display sponsors in a responsive slider/carousel',
            icon: 'slides',
            category: 'theatre-manager',
            fields: [
                { id: 'limit', label: 'Items to Show', type: 'number', min: 1, default: 6 }
            ],
            defaults: { limit: 6 }
        },
        {
            name: 'theatre-manager/tm-season-banner',
            title: 'Season Banner',
            description: 'Display current season banner with show information',
            icon: 'image',
            category: 'theatre-manager',
            fields: [
                { id: 'season_id', label: 'Season', type: 'select', options: [] }
            ],
            defaults: {}
        },
        {
            name: 'theatre-manager/tm-season-shows',
            title: 'Season Shows',
            description: 'Display shows for a specific season',
            icon: 'list-view',
            category: 'theatre-manager',
            fields: [
                { id: 'season_id', label: 'Season', type: 'select', options: [] },
                { id: 'exclude', label: 'Exclude Fields', type: 'text', placeholder: 'Comma-separated field names' }
            ],
            defaults: {}
        },
        {
            name: 'theatre-manager/tm-season-cast',
            title: 'Season Cast',
            description: 'Display all cast members for a season',
            icon: 'groups',
            category: 'theatre-manager',
            fields: [
                { id: 'season_id', label: 'Season', type: 'select', options: [] }
            ],
            defaults: {}
        },
        {
            name: 'theatre-manager/tm-season-images',
            title: 'Season Gallery',
            description: 'Display photo gallery for a season',
            icon: 'format-gallery',
            category: 'theatre-manager',
            fields: [
                { id: 'season_id', label: 'Season', type: 'select', options: [] },
                { id: 'cols', label: 'Columns', type: 'number', min: 1, max: 6, default: 3 }
            ],
            defaults: { cols: 3 }
        },
        {
            name: 'theatre-manager/tm-cast-show',
            title: 'Cast Member Show Roles',
            description: 'Display shows a specific cast member has appeared in',
            icon: 'theater',
            category: 'theatre-manager',
            fields: [
                { id: 'cast_id', label: 'Cast Member', type: 'select', options: [] }
            ],
            defaults: {}
        },
        {
            name: 'theatre-manager/tm-programs',
            title: 'Program Downloads',
            description: 'Display downloadable show programs',
            icon: 'media-document',
            category: 'theatre-manager',
            fields: [
                { id: 'exclude', label: 'Exclude Fields', type: 'text', placeholder: 'Comma-separated field names' }
            ],
            defaults: {}
        },
        {
            name: 'theatre-manager/tm-tickets',
            title: 'Ticket Info',
            description: 'Display ticket purchase information',
            icon: 'tickets',
            category: 'theatre-manager',
            fields: [
                { id: 'exclude', label: 'Exclude Fields', type: 'text', placeholder: 'Comma-separated field names' }
            ],
            defaults: {}
        }
    ];

    /**
     * Generic block component that adapts to any configuration
     */
    function createBlockEditor(config) {
        return function(props) {
            const { attributes, setAttributes } = props;
            const [showOptions, setShowOptions] = useState([]);
            const [loading, setLoading] = useState(false);

            // Fetch data for select fields (shows, seasons, etc.)
            useEffect(() => {
                const fetchSelectData = async () => {
                    setLoading(true);
                    try {
                        // Fetch shows
                        const showsRes = await fetch('/wp-json/wp/v2/show?per_page=100');
                        const showsData = await showsRes.json();
                        if (Array.isArray(showsData)) {
                            setShowOptions(showsData.map(s => ({ label: s.title.rendered, value: s.id.toString() })));
                        }
                    } catch (err) {
                        console.error('Error fetching data:', err);
                    }
                    setLoading(false);
                };
                
                fetchSelectData();
            }, []);

            const handleChange = (key, value) => {
                setAttributes({ [key]: value });
            };

            const generateShortcode = () => {
                const shortcodeMap = {
                    'theatre-manager/tm-shows': 'tm_shows',
                    'theatre-manager/tm-cast': 'tm_cast',
                    'theatre-manager/tm-board-members': 'tm_board_members',
                    'theatre-manager/tm-sponsors': 'tm_sponsors',
                    'theatre-manager/tm-advertisers': 'tm_advertisers',
                    'theatre-manager/tm-seasons': 'tm_seasons',
                    'theatre-manager/tm-venues': 'tm_venues',
                    'theatre-manager/tm-testimonials': 'tm_testimonials',
                    'theatre-manager/tm-awards': 'tm_awards',
                    'theatre-manager/tm-contributors': 'tm_contributors',
                    'theatre-manager/tm-auditions': 'tm_auditions',
                    'theatre-manager/tm-sponsor-slider': 'tm_sponsor_slider',
                    'theatre-manager/tm-season-banner': 'tm_season_banner',
                    'theatre-manager/tm-season-shows': 'tm_season_shows',
                    'theatre-manager/tm-season-cast': 'tm_season_cast',
                    'theatre-manager/tm-season-images': 'tm_season_images',
                    'theatre-manager/tm-cast-show': 'tm_cast_show',
                    'theatre-manager/tm-programs': 'tm_programs',
                    'theatre-manager/tm-tickets': 'tm_tickets'
                };

                const shortcodeName = shortcodeMap[config.name] || config.name.split('/')[1];
                let shortcodeStr = `[${shortcodeName}`;

                config.fields.forEach(field => {
                    const value = attributes[field.id];
                    if (value !== undefined && value !== null && value !== '') {
                        if (field.type === 'boolean') {
                            shortcodeStr += ` ${field.id}="${value ? 'true' : 'false'}"`;
                        } else if (Array.isArray(value)) {
                            shortcodeStr += ` ${field.id}="${value.join(',')}"`;
                        } else {
                            shortcodeStr += ` ${field.id}="${value}"`;
                        }
                    }
                });

                shortcodeStr += ']';
                return shortcodeStr;
            };

            return el('div', { className: 'tm-block-editor' },
                el(InspectorControls, null,
                    el(PanelBody, { title: 'Settings', initialOpen: true },
                        config.fields.map(field => {
                            const value = attributes[field.id] !== undefined ? attributes[field.id] : (config.defaults[field.id] || '');

                            switch (field.type) {
                                case 'text':
                                    return el(TextControl, {
                                        key: field.id,
                                        label: field.label,
                                        value: value,
                                        placeholder: field.placeholder,
                                        onChange: (val) => handleChange(field.id, val),
                                        help: field.help
                                    });
                                case 'number':
                                    return el(RangeControl, {
                                        key: field.id,
                                        label: field.label,
                                        value: parseInt(value) || 0,
                                        min: field.min,
                                        max: field.max,
                                        onChange: (val) => handleChange(field.id, val),
                                        help: field.help
                                    });
                                case 'boolean':
                                    return el(ToggleControl, {
                                        key: field.id,
                                        label: field.label,
                                        checked: value === true || value === 'true',
                                        onChange: (val) => handleChange(field.id, val),
                                        help: field.help
                                    });
                                case 'select':
                                    const selectOptions = field.id.includes('show') && field.id !== 'show_id'
                                        ? showOptions
                                        : (field.options || []).map(o => ({ label: o, value: o }));
                                    
                                    return el(SelectControl, {
                                        key: field.id,
                                        label: field.label,
                                        value: value,
                                        options: [{ label: '--- Select ---', value: '' }, ...selectOptions],
                                        onChange: (val) => handleChange(field.id, val),
                                        help: field.help
                                    });
                                default:
                                    return null;
                            }
                        })
                    )
                ),
                el('div', { className: 'tm-block-preview', style: { padding: '20px', background: '#f5f5f5', borderRadius: '4px', marginTop: '16px' } },
                    el('h4', null, config.title),
                    el('p', { style: { fontSize: '12px', color: '#666' } }, config.description),
                    el('code', {
                        style: {
                            display: 'block',
                            background: '#fff',
                            padding: '12px',
                            borderRadius: '4px',
                            fontSize: '11px',
                            overflow: 'auto',
                            marginTop: '8px'
                        }
                    }, generateShortcode())
                )
            );
        };
    }

    /**
     * Register all blocks
     */
    BLOCKS_CONFIG.forEach(config => {
        registerBlockType(config.name, {
            title: config.title,
            description: config.description,
            category: config.category,
            icon: config.icon,
            keywords: [config.title.toLowerCase(), 'theatre manager'],
            attributes: config.fields.reduce((acc, field) => {
                acc[field.id] = { type: field.type === 'multiselect' ? 'array' : 'string', default: '' };
                return acc;
            }, {}),
            edit: createBlockEditor(config),
            save: function(props) {
                const { attributes } = props;
                const config = BLOCKS_CONFIG.find(c => c.name === props.name);
                if (!config) return null;

                const shortcodeMap = {
                    'theatre-manager/tm-shows': 'tm_shows',
                    'theatre-manager/tm-cast': 'tm_cast',
                    'theatre-manager/tm-board-members': 'tm_board_members',
                    'theatre-manager/tm-sponsors': 'tm_sponsors',
                    'theatre-manager/tm-advertisers': 'tm_advertisers',
                    'theatre-manager/tm-seasons': 'tm_seasons',
                    'theatre-manager/tm-venues': 'tm_venues',
                    'theatre-manager/tm-testimonials': 'tm_testimonials',
                    'theatre-manager/tm-awards': 'tm_awards',
                    'theatre-manager/tm-contributors': 'tm_contributors',
                    'theatre-manager/tm-auditions': 'tm_auditions',
                    'theatre-manager/tm-sponsor-slider': 'tm_sponsor_slider',
                    'theatre-manager/tm-season-banner': 'tm_season_banner',
                    'theatre-manager/tm-season-shows': 'tm_season_shows',
                    'theatre-manager/tm-season-cast': 'tm_season_cast',
                    'theatre-manager/tm-season-images': 'tm_season_images',
                    'theatre-manager/tm-cast-show': 'tm_cast_show',
                    'theatre-manager/tm-programs': 'tm_programs',
                    'theatre-manager/tm-tickets': 'tm_tickets'
                };

                const shortcodeName = shortcodeMap[config.name];
                let shortcodeStr = `[${shortcodeName}`;

                config.fields.forEach(field => {
                    const value = attributes[field.id];
                    if (value) {
                        if (field.type === 'boolean') {
                            shortcodeStr += ` ${field.id}="${value === 'true' ? 'true' : 'false'}"`;
                        } else if (Array.isArray(value)) {
                            shortcodeStr += ` ${field.id}="${value.join(',')}"`;
                        } else {
                            shortcodeStr += ` ${field.id}="${value}"`;
                        }
                    }
                });

                shortcodeStr += ']';
                return el('div', { dangerouslySetInnerHTML: { __html: shortcodeStr } });
            }
        });
    });
})();
