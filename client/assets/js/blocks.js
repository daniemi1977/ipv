/**
 * IPV Production Gutenberg Blocks
 * v7.12.0
 */

(function(wp) {
    const { registerBlockType } = wp.blocks;
    const { InspectorControls } = wp.blockEditor;
    const { PanelBody, SelectControl, ToggleControl, RangeControl } = wp.components;
    const { useState, useEffect } = wp.element;

    // Video Player Block
    registerBlockType('ipv-production/video-player', {
        title: 'IPV Video Player',
        icon: 'video-alt3',
        category: 'embed',
        attributes: {
            videoId: { type: 'number', default: 0 },
            autoplay: { type: 'boolean', default: false },
            controls: { type: 'boolean', default: true }
        },
        edit: function(props) {
            const { attributes, setAttributes } = props;
            const [videos, setVideos] = useState([]);

            useEffect(() => {
                fetch(ipvBlocks.apiUrl + '/videos?per_page=50')
                    .then(res => res.json())
                    .then(data => {
                        if (data.videos) {
                            setVideos(data.videos.map(v => ({
                                label: v.title,
                                value: v.id
                            })));
                        }
                    });
            }, []);

            return wp.element.createElement(
                'div',
                { className: 'ipv-block-editor' },
                wp.element.createElement(InspectorControls, {},
                    wp.element.createElement(PanelBody, { title: 'Impostazioni Video' },
                        wp.element.createElement(SelectControl, {
                            label: 'Seleziona Video',
                            value: attributes.videoId,
                            options: [{ label: '-- Seleziona --', value: 0 }, ...videos],
                            onChange: (value) => setAttributes({ videoId: parseInt(value) })
                        }),
                        wp.element.createElement(ToggleControl, {
                            label: 'Autoplay',
                            checked: attributes.autoplay,
                            onChange: (value) => setAttributes({ autoplay: value })
                        }),
                        wp.element.createElement(ToggleControl, {
                            label: 'Controlli',
                            checked: attributes.controls,
                            onChange: (value) => setAttributes({ controls: value })
                        })
                    )
                ),
                wp.element.createElement('div', {
                    style: {
                        border: '2px dashed #ccc',
                        padding: '40px',
                        textAlign: 'center',
                        background: '#f8f9fa'
                    }
                },
                    wp.element.createElement('span', {
                        className: 'dashicons dashicons-video-alt3',
                        style: { fontSize: '48px', color: '#666' }
                    }),
                    wp.element.createElement('p', {},
                        attributes.videoId > 0
                            ? 'Video ID: ' + attributes.videoId
                            : 'Seleziona un video dalla barra laterale'
                    )
                )
            );
        },
        save: function() {
            return null; // Rendered server-side
        }
    });

    // Video Grid Block
    registerBlockType('ipv-production/video-grid', {
        title: 'IPV Video Grid',
        icon: 'grid-view',
        category: 'widgets',
        attributes: {
            columns: { type: 'number', default: 3 },
            count: { type: 'number', default: 6 },
            category: { type: 'string', default: '' }
        },
        edit: function(props) {
            const { attributes, setAttributes } = props;
            const [categories, setCategories] = useState([]);

            useEffect(() => {
                fetch('/wp-json/wp/v2/ipv_categoria')
                    .then(res => res.json())
                    .then(data => {
                        setCategories(data.map(cat => ({
                            label: cat.name,
                            value: cat.slug
                        })));
                    });
            }, []);

            return wp.element.createElement(
                'div',
                { className: 'ipv-block-editor' },
                wp.element.createElement(InspectorControls, {},
                    wp.element.createElement(PanelBody, { title: 'Impostazioni Grid' },
                        wp.element.createElement(RangeControl, {
                            label: 'Colonne',
                            value: attributes.columns,
                            onChange: (value) => setAttributes({ columns: value }),
                            min: 1,
                            max: 5
                        }),
                        wp.element.createElement(RangeControl, {
                            label: 'Numero Video',
                            value: attributes.count,
                            onChange: (value) => setAttributes({ count: value }),
                            min: 1,
                            max: 20
                        }),
                        wp.element.createElement(SelectControl, {
                            label: 'Categoria',
                            value: attributes.category,
                            options: [{ label: 'Tutte', value: '' }, ...categories],
                            onChange: (value) => setAttributes({ category: value })
                        })
                    )
                ),
                wp.element.createElement('div', {
                    style: {
                        border: '2px dashed #ccc',
                        padding: '40px',
                        textAlign: 'center',
                        background: '#f8f9fa'
                    }
                },
                    wp.element.createElement('span', {
                        className: 'dashicons dashicons-grid-view',
                        style: { fontSize: '48px', color: '#666' }
                    }),
                    wp.element.createElement('p', {},
                        'Video Grid: ' + attributes.columns + ' colonne, ' + attributes.count + ' video'
                    )
                )
            );
        },
        save: function() {
            return null; // Rendered server-side
        }
    });

})(window.wp);
