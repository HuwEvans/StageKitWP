/**
 * StageKitWP Blocks - Master Registration
 * 
 * Registers all StageKitWP shortcode blocks under unified group
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
     * Block Configuration for all StageKitWP shortcodes
     * Note: Landing Page block is registered separately as standalone block for better UX
    * This system handles the other 16 shortcode blocks
     */
    const BLOCKS_CONFIG = [
        {
            name: 'stagekitwp/stagekitwp-shows',
            title: 'Shows List',
            description: 'Display all shows grouped by season',
            icon: 'list-view',
            category: 'stagekitwp-core',
            fields: [
                { id: 'exclude', label: 'Exclude Fields', type: 'text', placeholder: 'Comma-separated field names' },
                { id: 'season_id', label: 'Season', type: 'select', options: [] },
                { id: 'which', label: 'Show Filter', type: 'select', options: ['all', 'current', 'next', 'current_and_next'] }
            ],
            defaults: { exclude: '', which: 'all' }
        },
        {
            name: 'stagekitwp/stagekitwp-board-members',
            title: 'Board Members',
            description: 'Display board members in grid, list, table, or spotlight layout with full dark/light mode support',
            icon: 'businessman',
            category: 'stagekitwp-core',
            fields: [
                { id: 'layout', label: 'Layout', type: 'select', options: ['grid', 'list', 'table', 'spotlight'] },
                { id: 'columns', label: 'Columns (grid/spotlight)', type: 'number', min: 1, max: 6, default: 3 },
                { id: 'show_photos', label: 'Show Photos', type: 'boolean' },
                { id: 'show_bio', label: 'Show Bio', type: 'boolean' }
            ],
            defaults: {
                layout: 'grid',
                columns: 3,
                show_photos: true,
                show_bio: false
            }
        },
        {
            name: 'stagekitwp/stagekitwp-sponsors',
            title: 'Sponsors',
            description: 'Display sponsor listings grouped by level (grid) or as an auto-scrolling logo carousel (slider)',
            icon: 'awards',
            category: 'stagekitwp-core',
            fields: [
                { id: 'layout',         label: 'Layout',          type: 'select',  options: ['grid', 'slider'],         default: 'grid' },
                { id: 'show_name',      label: 'Show Name',       type: 'boolean', default: true },
                { id: 'show_company',   label: 'Show Company',    type: 'boolean', default: true },
                { id: 'show_logo',      label: 'Show Logo',       type: 'boolean', default: true },
                { id: 'show_website',   label: 'Show Website',    type: 'boolean', default: true },
                { id: 'slides_visible', label: 'Logos Visible',   type: 'number',  min: 1, default: 4 },
                { id: 'autoplay',       label: 'Autoplay',        type: 'boolean', default: true },
                { id: 'speed',          label: 'Interval (ms)',   type: 'number',  min: 500, default: 3000 }
            ],
            defaults: { layout: 'grid', show_name: true, show_company: true, show_logo: true, show_website: true, slides_visible: 4, autoplay: true, speed: 3000 }
        },
        {
            name: 'stagekitwp/stagekitwp-advertisers',
            title: 'Advertisers',
            description: 'Display advertiser listings with business info',
            icon: 'megaphone',
            category: 'stagekitwp-core',
            fields: [
                { id: 'view', label: 'View', type: 'select', options: ['grid', 'slider'], default: 'grid' },
                { id: 'category', label: 'Category', type: 'text', placeholder: 'e.g. restaurant' },
                { id: 'image_type', label: 'Slider Image', type: 'select', options: ['banner', 'logo'], default: 'banner' },
                { id: 'columns', label: 'Columns', type: 'number', min: 1, max: 6, default: 3 },
                { id: 'lock_columns', label: 'Lock Column Count', type: 'boolean', default: false, help: 'Force the exact column count instead of responsive auto-fit' },
                { id: 'mini_logos', label: 'Mini Logos', type: 'boolean', default: false }
            ],
            defaults: {
                view: 'grid',
                category: '',
                image_type: 'banner',
                columns: 3,
                lock_columns: false,
                mini_logos: false
            }
        },
        {
            name: 'stagekitwp/stagekitwp-seasons',
            title: 'StageKitWP: Seasons',
            description: 'Display theatre seasons in grid or table format with filtering, sorting, and styling options',
            icon: 'calendar',
            category: 'stagekitwp-core',
            fields: [
                { id: 'which', label: 'Season Filter', type: 'select', options: ['all', 'current', 'upcoming', 'past'] },
                { id: 'layout', label: 'Layout', type: 'select', options: ['grid', 'table'] },
                { id: 'columns_lg', label: 'Desktop Columns', type: 'number', min: 1, max: 6, default: 3 },
                { id: 'columns_md', label: 'Tablet Columns', type: 'number', min: 1, max: 6, default: 2 },
                { id: 'columns_sm', label: 'Mobile Columns', type: 'number', min: 1, max: 3, default: 1 },
                { id: 'orderby', label: 'Order By', type: 'select', options: ['start_date', 'end_date', 'title'] },
                { id: 'order', label: 'Order', type: 'select', options: ['ASC', 'DESC'] },
                { id: 'limit', label: 'Limit', type: 'number', min: -1, default: -1 },
                { id: 'hide_image', label: 'Hide Image', type: 'boolean' },
                { id: 'hide_name', label: 'Hide Season Name', type: 'boolean' },
                { id: 'hide_dates', label: 'Hide Dates', type: 'boolean' },
                { id: 'hide_tickets', label: 'Hide Tickets Button', type: 'boolean' },
                { id: 'color_bg', label: 'Background Color', type: 'text', placeholder: '#ffffff' },
                { id: 'color_text', label: 'Text Color', type: 'text', placeholder: '#000000' },
                { id: 'color_heading', label: 'Heading Color', type: 'text', placeholder: '#333333' },
                { id: 'border_color', label: 'Border Color', type: 'text', placeholder: '#dddddd' },
                { id: 'border_width', label: 'Border Width', type: 'number', min: 0, max: 10, default: 1 },
                { id: 'border_radius', label: 'Border Radius', type: 'number', min: 0, max: 60, default: 8 },
                { id: 'padding', label: 'Padding', type: 'text', placeholder: '15px' },
                { id: 'shadow', label: 'Drop Shadow', type: 'text', placeholder: '0 2px 8px rgba(0,0,0,0.1)' }
            ],
            defaults: {
                which: 'all',
                layout: 'grid',
                columns_lg: 3,
                columns_md: 2,
                columns_sm: 1,
                orderby: 'start_date',
                order: 'ASC',
                limit: -1,
                hide_image: false,
                hide_name: false,
                hide_dates: false,
                hide_tickets: false,
                border_width: 1,
                border_radius: 8,
                padding: '15px'
            }
        },
        {
            name: 'stagekitwp/stagekitwp-venues',
            title: 'Venues',
            description: 'Display theatre venue information',
            icon: 'location',
            category: 'stagekitwp-core',
            fields: [
                { id: 'exclude', label: 'Exclude Fields', type: 'text', placeholder: 'Comma-separated field names' }
            ],
            defaults: {}
        },
        {
            name: 'stagekitwp/stagekitwp-testimonials',
            title: 'Testimonials',
            description: 'Display audience testimonials and ratings',
            icon: 'format-quote',
            category: 'stagekitwp-core',
            fields: [
                { id: 'mode', label: 'Mode', type: 'select', options: ['slider', 'grid', 'full', 'per_show', 'per_show_slider'] },
                { id: 'layout', label: 'Layout', type: 'select', options: ['classic', 'quote', 'minimal', 'spotlight', 'overlay'] },
                { id: 'limit', label: 'Limit', type: 'number', min: -1, max: 200, default: -1 },
                { id: 'columns', label: 'Grid Columns', type: 'number', min: 1, max: 4, default: 3 },
                { id: 'image_width', label: 'Image Width (px)', type: 'number', min: 200, max: 1400, default: 520 },
                { id: 'image_height', label: 'Image Height (px)', type: 'number', min: 120, max: 1200, default: 280 },
                { id: 'image_fit', label: 'Image Fit', type: 'select', options: ['cover', 'contain'] },
                { id: 'image_position', label: 'Image Position', type: 'select', options: ['center', 'left', 'right', 'top'] },
                { id: 'image_focus', label: 'Image Focus', type: 'select', options: ['center_center', 'center_top', 'center_bottom', 'left_center', 'right_center'] },
                { id: 'text_overlay', label: 'Text Overlay On Image', type: 'boolean' },
                { id: 'image_opacity', label: 'Image Opacity (0.1-1)', type: 'text', placeholder: '0.45' },
                { id: 'show_id', label: 'Show ID Filter', type: 'number', min: 0, max: 99999 },
                { id: 'show_name', label: 'Show Name', type: 'boolean' },
                { id: 'show_comment', label: 'Show Comment', type: 'boolean' },
                { id: 'show_rating', label: 'Show Rating', type: 'boolean' },
                { id: 'show_show', label: 'Show Linked Show', type: 'boolean' },
                { id: 'show_name_placement', label: 'Show Name Placement', type: 'select', options: ['meta', 'header', 'slug', 'image_indent'] },
                { id: 'tag_icon_source', label: 'Tag Icon Source', type: 'select', options: ['none', 'site_icon', 'miltonman', 'custom'] },
                { id: 'tag_icon_url', label: 'Custom Tag Icon URL', type: 'text', placeholder: 'https://...' },
                { id: 'tag_icon_size', label: 'Tag Icon Size (px)', type: 'number', min: 12, max: 48, default: 18 },
                { id: 'show_date', label: 'Show Date', type: 'boolean' },
                { id: 'show_media', label: 'Show Media', type: 'boolean' },
                { id: 'reviews_per_show', label: 'Per Show: Reviews Per Show', type: 'number', min: 1, max: 20, default: 4 },
                { id: 'review_align', label: 'Per Show: Review Alignment', type: 'select', options: ['left', 'center', 'right', 'alternating', 'alternating_lr'] }
            ],
            defaults: {
                mode: 'slider',
                layout: 'classic',
                limit: -1,
                columns: 3,
                image_width: 520,
                image_height: 280,
                image_fit: 'cover',
                image_position: 'center',
                image_focus: 'center_center',
                text_overlay: false,
                image_opacity: '0.45',
                show_id: 0,
                show_name: true,
                show_comment: true,
                show_rating: true,
                show_show: true,
                show_name_placement: 'meta',
                tag_icon_source: 'none',
                tag_icon_url: '',
                tag_icon_size: 18,
                show_date: true,
                show_media: true,
                reviews_per_show: 4,
                review_align: 'left'
            }
        },
        {
            name: 'stagekitwp/stagekitwp-awards',
            title: 'Awards',
            description: 'Display awards in table, cards, list, or showcase layout with full dark/light mode support',
            icon: 'star-filled',
            category: 'stagekitwp-core',
            fields: [
                { id: 'layout',        label: 'Layout',           type: 'select', options: ['table','cards','list','showcase'] },
                { id: 'season_id',     label: 'Season ID',        type: 'number' },
                { id: 'category',      label: 'Category Filter',  type: 'text',   placeholder: 'Musical / Drama / Comedy' },
                { id: 'show_season',   label: 'Show Season',      type: 'boolean' },
                { id: 'show_category', label: 'Show Category',    type: 'boolean' },
                { id: 'winners_only',  label: 'Winners Only',     type: 'boolean' }
            ],
            defaults: { layout: 'table', show_season: true, show_category: true, winners_only: false }
        },
        {
            name: 'stagekitwp/stagekitwp-contributors',
            title: 'Contributors',
            description: 'Display staff and volunteer contributors',
            icon: 'heart',
            category: 'stagekitwp-core',
            fields: [
                { id: 'exclude', label: 'Exclude Fields', type: 'text', placeholder: 'Comma-separated field names' }
            ],
            defaults: {}
        },
        {
            name: 'stagekitwp/stagekitwp-auditions',
            title: 'Auditions',
            description: 'Display audition information with filtering and responsive layout',
            icon: 'microphone',
            category: 'stagekitwp-core',
            fields: [
                { id: 'show_id', label: 'Show', type: 'select', options: [] },
                { id: 'season_id', label: 'Season', type: 'select', options: [] },
                { id: 'limit', label: 'Limit Auditions', type: 'number', min: 0, default: 0 },
                { id: 'layout', label: 'Layout', type: 'select', options: ['list', 'grid'] },
                { id: 'orderby', label: 'Order By', type: 'select', options: ['date', 'title', 'newest'] },
                { id: 'order', label: 'Order', type: 'select', options: ['ASC', 'DESC'] },
                { id: 'hide_description', label: 'Hide Description', type: 'boolean' },
                { id: 'hide_venue', label: 'Hide Venue Details', type: 'boolean' },
                { id: 'hide_contact', label: 'Hide Contact Info', type: 'boolean' },
                { id: 'hide_image', label: 'Hide Audition Image', type: 'boolean' },
                { id: 'columns_lg', label: 'Desktop Columns', type: 'number', min: 1, max: 6, default: 3 },
                { id: 'columns_md', label: 'Tablet Columns', type: 'number', min: 1, max: 6, default: 2 },
                { id: 'columns_sm', label: 'Mobile Columns', type: 'number', min: 1, max: 3, default: 1 },
                { id: 'color_bg', label: 'Background Color', type: 'text', placeholder: '#ffffff' },
                { id: 'color_text', label: 'Text Color', type: 'text', placeholder: '#000000' },
                { id: 'color_heading', label: 'Heading Color', type: 'text', placeholder: '#333333' },
                { id: 'border_color', label: 'Border Color', type: 'text', placeholder: '#dddddd' },
                { id: 'border_width', label: 'Border Width', type: 'number', min: 0, max: 10, default: 1 },
                { id: 'border_radius', label: 'Border Radius', type: 'number', min: 0, max: 60, default: 8 },
                { id: 'padding', label: 'Padding', type: 'text', placeholder: '15px' },
                { id: 'shadow', label: 'Drop Shadow', type: 'text', placeholder: '0 2px 8px rgba(0,0,0,0.1)' }
            ],
            defaults: {
                layout: 'list',
                orderby: 'date',
                order: 'ASC',
                hide_description: false,
                hide_venue: false,
                hide_contact: false,
                hide_image: false,
                columns_lg: 3,
                columns_md: 2,
                columns_sm: 1,
                border_width: 1,
                border_radius: 8,
                padding: '15px'
            }
        },
     
        {
            name: 'stagekitwp/stagekitwp-season-shows',
            title: 'StageKitWP: Season Shows',
            description: 'Display shows grouped by season status (current/upcoming/past) with director, author, producer, and stage manager details',
            icon: 'list-view',
            category: 'stagekitwp-core',
            fields: [
                { id: 'season_id', label: 'Season', type: 'select', options: [] },
                { id: 'which', label: 'Season Filter', type: 'select', options: ['all', 'current', 'next', 'current_and_next'] },
                { id: 'layout', label: 'Layout', type: 'select', options: ['list', 'grid'] },
                { id: 'columns_lg', label: 'Desktop Columns', type: 'number', min: 1, max: 6, default: 3 },
                { id: 'columns_md', label: 'Tablet Columns', type: 'number', min: 1, max: 6, default: 2 },
                { id: 'columns_sm', label: 'Mobile Columns', type: 'number', min: 1, max: 3, default: 1 },
                { id: 'orderby', label: 'Order By', type: 'select', options: ['title', 'time_slot', 'date'] },
                { id: 'order', label: 'Order', type: 'select', options: ['ASC', 'DESC'] },
                { id: 'hide_description', label: 'Hide Synopsis', type: 'boolean' },
                { id: 'hide_image', label: 'Hide Image', type: 'boolean' },
                { id: 'hide_director', label: 'Hide Director', type: 'boolean' },
                { id: 'hide_author', label: 'Hide Author', type: 'boolean' },
                { id: 'hide_producer', label: 'Hide Producer', type: 'boolean' },
                { id: 'hide_stage_manager', label: 'Hide Stage Manager', type: 'boolean' },
                { id: 'hide_dates', label: 'Hide Show Dates', type: 'boolean' },
                { id: 'color_bg', label: 'Background Color', type: 'text', placeholder: '#ffffff' },
                { id: 'color_text', label: 'Text Color', type: 'text', placeholder: '#000000' },
                { id: 'color_heading', label: 'Heading Color', type: 'text', placeholder: '#333333' },
                { id: 'border_color', label: 'Border Color', type: 'text', placeholder: '#dddddd' },
                { id: 'border_width', label: 'Border Width', type: 'number', min: 0, max: 10, default: 1 },
                { id: 'border_radius', label: 'Border Radius', type: 'number', min: 0, max: 60, default: 8 },
                { id: 'padding', label: 'Padding', type: 'text', placeholder: '15px' },
                { id: 'shadow', label: 'Drop Shadow', type: 'text', placeholder: '0 2px 8px rgba(0,0,0,0.1)' }
            ],
            defaults: {
                which: 'all',
                layout: 'grid',
                columns_lg: 3,
                columns_md: 2,
                columns_sm: 1,
                orderby: 'title',
                order: 'ASC',
                hide_description: false,
                hide_image: false,
                hide_author: false,
                hide_director: false,
                hide_producer: false,
                hide_stage_manager: false,
                hide_dates: false,
                border_width: 1,
                border_radius: 8,
                padding: '15px'
            }
        },
       
        {
            name: 'stagekitwp/stagekitwp-show-cast',
            title: 'Show Cast List',
            description: 'Display the cast for a specific show — equivalent to [stagekitwp_show_cast]',
            icon: 'groups',
            category: 'stagekitwp-core',
            fields: [
                { id: 'show_id', label: 'Show', type: 'select', options: [] }
            ],
            defaults: { show_id: '' }
        },
        {
            name: 'stagekitwp/stagekitwp-programs',
            title: 'Program Downloads',
            description: 'Display downloadable show programs',
            icon: 'media-document',
            category: 'stagekitwp-core',
            fields: [
                { id: 'exclude', label: 'Exclude Fields', type: 'text', placeholder: 'Comma-separated field names' }
            ],
            defaults: {}
        },
        {
            name: 'stagekitwp/stagekitwp-tickets',
            title: 'Ticket Info',
            description: 'Display ticket purchase links for the current season and its shows',
            icon: 'tickets',
            category: 'stagekitwp-core',
            fields: [
                { id: 'layout', label: 'Layout Style', type: 'select', options: [
                    { label: 'Banner — stacked full-width buttons', value: 'banner' },
                    { label: 'Cards — poster art + CTA button',     value: 'cards' },
                    { label: 'Table — horizontal rows with links',   value: 'table' },
                    { label: 'Minimal — text list with arrow',      value: 'minimal' },
                    { label: 'Spotlight — hero image + show tiles', value: 'spotlight' },
                ]},
                { id: 'show_limit',   label: 'Max Shows',         type: 'number', min: 1, default: 4 },
                { id: 'show_image',   label: 'Show Images',       type: 'boolean', default: true },
                { id: 'show_dates',   label: 'Show Dates',        type: 'boolean', default: true },
                { id: 'show_genre',   label: 'Show Genre Badge',  type: 'boolean', default: false },
                { id: 'label_season', label: 'Season Label',      type: 'text',    placeholder: 'Season Tickets' },
                { id: 'label_show',   label: 'Show Label',        type: 'text',    placeholder: 'Show Tickets' },
                { id: 'button_text',  label: 'Button / Link Text',type: 'text',    placeholder: 'Get Tickets' }
            ],
            defaults: { layout: 'banner', show_limit: 4, show_image: true, show_dates: true, show_genre: false }
        },
        {
            name: 'stagekitwp/stagekitwp-past-shows',
            title: 'Past Shows',
            description: 'Display shows from previous seasons',
            icon: 'back',
            category: 'stagekitwp-core',
            fields: [
                { id: 'layout',       label: 'Layout',       type: 'select', options: ['list', 'cards'] },
                { id: 'limit',        label: 'Season Limit', type: 'number', min: -1, default: -1 },
                { id: 'show_author',  label: 'Show Author',  type: 'boolean', default: true },
                { id: 'show_program', label: 'Show Program', type: 'boolean', default: true },
                { id: 'show_awards',  label: 'Show Awards',  type: 'boolean', default: true }
            ],
            defaults: { layout: 'list', limit: -1, show_author: true, show_program: true, show_awards: true }
        },
        {
            name: 'stagekitwp/stagekitwp-sponsor-slider',
            title: 'Sponsor Slider',
            description: 'Display sponsor banners in an auto-scrolling slider',
            icon: 'images-alt2',
            category: 'stagekitwp-core',
            fields: [
                { id: 'slides_visible', label: 'Logos Visible', type: 'number', min: 1, default: 4 },
                { id: 'autoplay',       label: 'Autoplay',       type: 'boolean', default: true },
                { id: 'speed',          label: 'Interval (ms)', type: 'number', min: 500, default: 3000 }
            ],
            defaults: { slides_visible: 4, autoplay: true, speed: 3000 }
        },
    ];

    /**
     * Generic block component that adapts to any configuration
     */
    function createBlockEditor(config) {
        return function(props) {
            const { attributes, setAttributes } = props;
            const [showOptions, setShowOptions] = useState([]);
            const [seasonOptions, setSeasonOptions] = useState([]);
            const [loading, setLoading] = useState(false);

            // Fetch data for select fields (shows, seasons, etc.)
            useEffect(() => {
                const fetchSelectData = async () => {
                    setLoading(true);
                    try {
                        // Use REST root from localized data (supports sub-dir installs).
                        const restRoot = ( window.stagekitwpBlocksData && window.stagekitwpBlocksData.restRoot )
                            ? window.stagekitwpBlocksData.restRoot.replace( /\/$/, '' )
                            : '/wp-json';
                        const restHeaders = ( window.stagekitwpBlocksData && window.stagekitwpBlocksData.restNonce )
                            ? { 'X-WP-Nonce': window.stagekitwpBlocksData.restNonce }
                            : {};

                        // Check if block needs show data
                        const hasShowField = config.fields.some(f => f.id === 'show_id');
                        if (hasShowField) {
                            try {
                                const showsRes = await fetch(restRoot + '/wp/v2/show?per_page=100', { headers: restHeaders });
                                if (showsRes.ok) {
                                    const showsData = await showsRes.json();
                                    if (Array.isArray(showsData)) {
                                        setShowOptions(showsData.map(s => {
                                            const title = s.title?.rendered || s.title || `Show #${s.id}`;
                                            return { label: title, value: s.id.toString() };
                                        }));
                                        console.log('Successfully loaded shows:', showsData.length);
                                    } else {
                                        console.warn('Shows response is not an array:', showsData);
                                    }
                                } else {
                                    console.warn('Shows REST endpoint returned status:', showsRes.status, showsRes.statusText);
                                }
                            } catch (err) {
                                console.error('Error fetching shows:', err.message);
                            }
                        }
                        
                        // Check if block needs season data
                        const hasSeasonField = config.fields.some(f => f.id === 'season_id');
                        if (hasSeasonField) {
                            try {
                                const seasonsRes = await fetch(restRoot + '/wp/v2/season?per_page=100', { headers: restHeaders });
                                if (seasonsRes.ok) {
                                    const seasonsData = await seasonsRes.json();
                                    if (Array.isArray(seasonsData)) {
                                        setSeasonOptions(seasonsData.map(s => {
                                            const title = s.title?.rendered || s.title || `Season #${s.id}`;
                                            return { label: title, value: s.id.toString() };
                                        }));
                                        console.log('Successfully loaded seasons:', seasonsData.length);
                                    } else {
                                        console.warn('Seasons response is not an array:', seasonsData);
                                    }
                                } else {
                                    console.warn('Seasons REST endpoint returned status:', seasonsRes.status, seasonsRes.statusText);
                                }
                            } catch (err) {
                                console.error('Error fetching seasons:', err.message);
                            }
                        }
                    } catch (err) {
                        console.error('Error in fetchSelectData:', err.message);
                    }
                    setLoading(false);
                };
                
                fetchSelectData();
            }, [config.name]);

            const handleChange = (key, value) => {
                setAttributes({ [key]: value });
            };

            const generateShortcode = () => {
                const shortcodeMap = {
                    'stagekitwp/stagekitwp-shows': 'stagekitwp_shows',
                    'stagekitwp/stagekitwp-board-members': 'stagekitwp_board_members',
                    'stagekitwp/stagekitwp-sponsors': 'stagekitwp_sponsors',
                    'stagekitwp/stagekitwp-advertisers': 'stagekitwp_advertisers',
                    'stagekitwp/stagekitwp-seasons': 'stagekitwp_seasons',
                    'stagekitwp/stagekitwp-venues': 'stagekitwp_venues',
                    'stagekitwp/stagekitwp-testimonials': 'stagekitwp_testimonials',
                    'stagekitwp/stagekitwp-awards': 'stagekitwp_awards',
                    'stagekitwp/stagekitwp-contributors': 'stagekitwp_contributors',
                    'stagekitwp/stagekitwp-auditions': 'stagekitwp_auditions',
                    'stagekitwp/stagekitwp-season-shows': 'stagekitwp_season_shows',
                    'stagekitwp/stagekitwp-show-cast': 'stagekitwp_show_cast',
                    'stagekitwp/stagekitwp-programs': 'stagekitwp_programs',
                    'stagekitwp/stagekitwp-tickets': 'stagekitwp_tickets',
                    'stagekitwp/stagekitwp-past-shows': 'stagekitwp_past_shows',
                    'stagekitwp/stagekitwp-sponsor-slider': 'stagekitwp_sponsor_slider'
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

            return el('div', { className: 'stagekitwp-block-editor' },
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
                                    let selectOptions = [];
                                    if (field.id === 'show_id') {
                                        selectOptions = showOptions;
                                    } else if (field.id === 'season_id') {
                                        selectOptions = seasonOptions;
                                    } else {
                                        selectOptions = field.options ? (field.options || []).map(o => ({ label: o, value: o })) : [];
                                    }
                                    
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
                el('div', { className: 'stagekitwp-block-preview', style: { padding: '20px', background: '#f5f5f5', borderRadius: '4px', marginTop: '16px' } },
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
            keywords: [config.title.toLowerCase(), 'stagekitwp'],
            attributes: config.fields.reduce((acc, field) => {
                const typeMap = {
                    multiselect: 'array',
                    number: 'number',
                    boolean: 'boolean',
                    text: 'string',
                    select: 'string'
                };
                const mappedType = typeMap[field.type] || 'string';
                const defaultValue = config.defaults[field.id] !== undefined
                    ? config.defaults[field.id]
                    : (mappedType === 'number' ? 0 : (mappedType === 'boolean' ? false : ''));

                acc[field.id] = { type: mappedType, default: defaultValue };
                return acc;
            }, {}),
            edit: createBlockEditor(config),
            save: function(props) {
                const { attributes } = props;

                const shortcodeMap = {
                    'stagekitwp/stagekitwp-shows': 'stagekitwp_shows',
                    'stagekitwp/stagekitwp-board-members': 'stagekitwp_board_members',
                    'stagekitwp/stagekitwp-sponsors': 'stagekitwp_sponsors',
                    'stagekitwp/stagekitwp-advertisers': 'stagekitwp_advertisers',
                    'stagekitwp/stagekitwp-seasons': 'stagekitwp_seasons',
                    'stagekitwp/stagekitwp-venues': 'stagekitwp_venues',
                    'stagekitwp/stagekitwp-testimonials': 'stagekitwp_testimonials',
                    'stagekitwp/stagekitwp-awards': 'stagekitwp_awards',
                    'stagekitwp/stagekitwp-contributors': 'stagekitwp_contributors',
                    'stagekitwp/stagekitwp-auditions': 'stagekitwp_auditions',
                    'stagekitwp/stagekitwp-season-shows': 'stagekitwp_season_shows',
                    'stagekitwp/stagekitwp-show-cast': 'stagekitwp_show_cast',
                    'stagekitwp/stagekitwp-programs': 'stagekitwp_programs',
                    'stagekitwp/stagekitwp-tickets': 'stagekitwp_tickets',
                    'stagekitwp/stagekitwp-past-shows': 'stagekitwp_past_shows',
                    'stagekitwp/stagekitwp-sponsor-slider': 'stagekitwp_sponsor_slider'
                };

                const shortcodeName = shortcodeMap[config.name];
                let shortcodeStr = `[${shortcodeName}`;

                config.fields.forEach(field => {
                    const value = attributes[field.id];
                    const hasValue = value !== undefined && value !== null && value !== '';

                    if (hasValue) {
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
                return el('div', { dangerouslySetInnerHTML: { __html: shortcodeStr } });
            }
        });
    });
})();
