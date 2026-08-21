(function () {
    const el = wp.element.createElement;
    const { registerBlockType } = wp.blocks;
    const { InspectorControls } = wp.blockEditor;
    const { PanelBody, SelectControl, ToggleControl, RangeControl, TextControl } = wp.components;
    const ServerSideRender = wp.serverSideRender;

    registerBlockType('stagekitwp/board-members', {
        title: 'Board Members',
        description: 'Display board member profiles with responsive columns and per-block style overrides.',
        category: 'stagekitwp-core',
        icon: 'groups',

        edit: function (props) {
            const { attributes, setAttributes } = props;

            return el('div', { className: 'stagekitwp-board-members-editor' },
                el(InspectorControls, null,
                    el(PanelBody, { title: 'Layout', initialOpen: true },
                        el(SelectControl, {
                            label: 'Display Layout',
                            value: attributes.layout || 'grid',
                            options: [
                                { label: 'Grid', value: 'grid' },
                                { label: 'List', value: 'list' },
                                { label: 'Accordion', value: 'accordion' }
                            ],
                            onChange: function (value) {
                                setAttributes({ layout: value });
                            }
                        }),
                        el(ToggleControl, {
                            label: 'Show Photos',
                            checked: attributes.showPhotos !== false,
                            onChange: function (value) {
                                setAttributes({ showPhotos: value });
                            }
                        }),
                        el(ToggleControl, {
                            label: 'Show Company',
                            checked: attributes.showCompany !== false,
                            onChange: function (value) {
                                setAttributes({ showCompany: value });
                            }
                        }),
                        el(SelectControl, {
                            label: 'Photo Size',
                            value: attributes.photoSize || 'medium',
                            options: [
                                { label: 'Small', value: 'small' },
                                { label: 'Medium', value: 'medium' },
                                { label: 'Large', value: 'large' }
                            ],
                            onChange: function (value) {
                                setAttributes({ photoSize: value });
                            }
                        })
                    ),
                    el(PanelBody, { title: 'Responsive Columns', initialOpen: false },
                        el(RangeControl, {
                            label: 'Desktop Columns',
                            value: parseInt(attributes.columnsDesktop || attributes.columns || 0, 10),
                            min: 0,
                            max: 6,
                            help: '0 uses Display Options default.',
                            onChange: function (value) {
                                setAttributes({ columnsDesktop: value, columns: value });
                            }
                        }),
                        el(RangeControl, {
                            label: 'Tablet Columns',
                            value: parseInt(attributes.columnsTablet || 0, 10),
                            min: 0,
                            max: 6,
                            help: '0 auto-calculates based on desktop columns.',
                            onChange: function (value) {
                                setAttributes({ columnsTablet: value });
                            }
                        }),
                        el(RangeControl, {
                            label: 'Mobile Columns',
                            value: parseInt(attributes.columnsMobile || 1, 10),
                            min: 1,
                            max: 3,
                            onChange: function (value) {
                                setAttributes({ columnsMobile: value });
                            }
                        })
                    ),
                    el(PanelBody, { title: 'Display Option Overrides', initialOpen: false },
                        el(TextControl, {
                            label: 'Background Color',
                            value: attributes.bgColor || '',
                            placeholder: '#ffffff',
                            help: 'Hex color. Leave empty to use Display Options default.',
                            onChange: function (value) {
                                setAttributes({ bgColor: value });
                            }
                        }),
                        el(TextControl, {
                            label: 'Text Color',
                            value: attributes.textColor || '',
                            placeholder: '#000000',
                            onChange: function (value) {
                                setAttributes({ textColor: value });
                            }
                        }),
                        el(TextControl, {
                            label: 'Border Color',
                            value: attributes.borderColor || '',
                            placeholder: '#000000',
                            onChange: function (value) {
                                setAttributes({ borderColor: value });
                            }
                        }),
                        el(RangeControl, {
                            label: 'Border Width (px)',
                            value: parseInt(attributes.borderWidth || 0, 10),
                            min: 0,
                            max: 20,
                            onChange: function (value) {
                                setAttributes({ borderWidth: value });
                            }
                        }),
                        el(ToggleControl, {
                            label: 'Rounded Corners',
                            checked: attributes.rounded === true,
                            onChange: function (value) {
                                setAttributes({ rounded: value });
                            }
                        }),
                        el(RangeControl, {
                            label: 'Border Radius (px)',
                            value: parseInt(attributes.borderRadius || 0, 10),
                            min: 0,
                            max: 60,
                            onChange: function (value) {
                                setAttributes({ borderRadius: value });
                            }
                        }),
                        el(ToggleControl, {
                            label: 'Drop Shadow',
                            checked: attributes.shadow === true,
                            onChange: function (value) {
                                setAttributes({ shadow: value });
                            }
                        }),
                        el(TextControl, {
                            label: 'Base Font Family',
                            value: attributes.baseFont || '',
                            placeholder: 'Arial, sans-serif',
                            onChange: function (value) {
                                setAttributes({ baseFont: value });
                            }
                        })
                    )
                ),
                el(ServerSideRender, {
                    block: 'stagekitwp/board-members',
                    attributes: attributes
                })
            );
        },

        save: function () {
            return null;
        }
    });
})();
