<?php
/**
 * IPV Production System Pro - Elementor Templates
 *
 * Pre-built Elementor templates using IPV widgets
 *
 * @package IPV_Production_System_Pro
 * @version 8.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_Elementor_Templates {

    public static function init() {
        // Check if Elementor is installed
        if ( ! did_action( 'elementor/loaded' ) ) {
            return;
        }

        add_action( 'elementor/init', [ __CLASS__, 'register_templates' ] );
        add_action( 'admin_menu', [ __CLASS__, 'add_templates_page' ] );
        add_action( 'wp_ajax_ipv_import_elementor_template', [ __CLASS__, 'ajax_import_template' ] );
    }

    /**
     * Add templates management page
     */
    public static function add_templates_page() {
        add_submenu_page(
            'edit.php?post_type=ipv_video',
            'Elementor Templates',
            '📐 Elementor Templates',
            'manage_options',
            'ipv-elementor-templates',
            [ __CLASS__, 'render_templates_page' ]
        );
    }

    /**
     * Render templates page
     */
    public static function render_templates_page() {
        $templates = self::get_available_templates();
        ?>
        <div class="wrap">
            <h1>📐 IPV Elementor Templates</h1>
            <p class="description">
                Template Elementor pronti all'uso con i widget IPV già configurati. Clicca "Importa" per aggiungere il template alla tua libreria Elementor.
            </p>

            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top: 30px;">
                <?php foreach ( $templates as $key => $template ) : ?>
                    <div class="card" style="padding: 0; overflow: hidden;">
                        <!-- Preview Image -->
                        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 60px 20px; text-align: center; color: #fff;">
                            <div style="font-size: 48px; margin-bottom: 15px;">
                                <?php echo esc_html( $template['icon'] ); ?>
                            </div>
                            <h3 style="margin: 0; color: #fff; font-size: 18px;">
                                <?php echo esc_html( $template['title'] ); ?>
                            </h3>
                        </div>

                        <!-- Template Info -->
                        <div style="padding: 20px;">
                            <p style="margin: 0 0 15px 0; color: #666; line-height: 1.6;">
                                <?php echo esc_html( $template['description'] ); ?>
                            </p>

                            <div style="margin-bottom: 15px; padding: 10px; background: #f0f0f0; border-radius: 4px; font-size: 12px;">
                                <strong>Include:</strong><br>
                                <?php echo esc_html( $template['includes'] ); ?>
                            </div>

                            <button
                                class="button button-primary ipv-import-template"
                                data-template="<?php echo esc_attr( $key ); ?>"
                                style="width: 100%;"
                            >
                                ⬇️ Importa Template
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div style="margin-top: 40px; padding: 20px; background: #f0f6fc; border-left: 4px solid #2271b1; border-radius: 4px;">
                <h3>💡 Come Usare i Template</h3>
                <ol style="line-height: 2;">
                    <li>Clicca "Importa Template" sul template che ti interessa</li>
                    <li>Il template verrà aggiunto alla tua libreria Elementor</li>
                    <li>Crea una nuova pagina con Elementor</li>
                    <li>Clicca sull'icona 📁 "Aggiungi Template" in Elementor</li>
                    <li>Nella tab "Le mie librerie", troverai il template importato</li>
                    <li>Clicca "Inserisci" per usare il template</li>
                    <li>Personalizza con i tuoi contenuti!</li>
                </ol>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('.ipv-import-template').on('click', function() {
                var $btn = $(this);
                var template = $btn.data('template');

                if ($btn.prop('disabled')) return;

                $btn.prop('disabled', true).text('⏳ Importando...');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ipv_import_elementor_template',
                        template: template,
                        nonce: '<?php echo wp_create_nonce( 'ipv_elementor_templates' ); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $btn.text('✅ Importato!').css('background', '#00a32a');
                            alert('✅ Template importato con successo!\n\nTrovalo in: Elementor → Le mie librerie');
                        } else {
                            alert('❌ Errore: ' + response.data);
                            $btn.prop('disabled', false).text('⬇️ Importa Template');
                        }
                    },
                    error: function() {
                        alert('❌ Errore di connessione');
                        $btn.prop('disabled', false).text('⬇️ Importa Template');
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Get available templates
     */
    public static function get_available_templates() {
        return [
            'single_video' => [
                'title' => 'Single Video Page',
                'icon' => '🎬',
                'description' => 'Pagina singola video professionale con player, info e video correlati',
                'includes' => 'Video Player, Info Meta, Related Videos Grid',
                'type' => 'page',
            ],
            'video_gallery' => [
                'title' => 'Video Gallery',
                'icon' => '📱',
                'description' => 'Galleria video responsive con filtri e ricerca',
                'includes' => 'Search Form, Category Filters, Video Grid',
                'type' => 'page',
            ],
            'video_landing' => [
                'title' => 'Video Landing Page',
                'icon' => '🚀',
                'description' => 'Landing page moderna con hero video e CTA',
                'includes' => 'Hero Player, Features, Video Grid, Stats',
                'type' => 'page',
            ],
            'video_channel' => [
                'title' => 'Video Channel Home',
                'icon' => '📺',
                'description' => 'Home page stile YouTube con sezioni multiple',
                'includes' => 'Hero, Latest Videos, Popular Videos, Categories',
                'type' => 'page',
            ],
        ];
    }

    /**
     * Register templates in Elementor
     */
    public static function register_templates() {
        // Templates are registered on-demand when imported
    }

    /**
     * AJAX: Import template
     */
    public static function ajax_import_template() {
        check_ajax_referer( 'ipv_elementor_templates', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permessi insufficienti' );
        }

        $template_key = isset( $_POST['template'] ) ? sanitize_text_field( $_POST['template'] ) : '';

        if ( empty( $template_key ) ) {
            wp_send_json_error( 'Template non specificato' );
        }

        $template_data = self::get_template_data( $template_key );

        if ( ! $template_data ) {
            wp_send_json_error( 'Template non trovato' );
        }

        // Create Elementor template
        $template_id = wp_insert_post( [
            'post_title' => $template_data['title'],
            'post_type' => 'elementor_library',
            'post_status' => 'publish',
            'meta_input' => [
                '_elementor_template_type' => $template_data['type'],
                '_elementor_edit_mode' => 'builder',
                '_elementor_data' => wp_json_encode( $template_data['content'] ),
            ],
        ] );

        if ( is_wp_error( $template_id ) ) {
            wp_send_json_error( 'Errore durante la creazione del template' );
        }

        IPV_Prod_Logger::log( 'Elementor template imported', [
            'template' => $template_key,
            'id' => $template_id
        ] );

        wp_send_json_success( [ 'template_id' => $template_id ] );
    }

    /**
     * Get template data
     */
    public static function get_template_data( $template_key ) {
        $templates_data = [
            'single_video' => [
                'title' => 'IPV - Single Video Page',
                'type' => 'page',
                'content' => self::get_single_video_template(),
            ],
            'video_gallery' => [
                'title' => 'IPV - Video Gallery',
                'type' => 'page',
                'content' => self::get_video_gallery_template(),
            ],
            'video_landing' => [
                'title' => 'IPV - Video Landing Page',
                'type' => 'page',
                'content' => self::get_video_landing_template(),
            ],
            'video_channel' => [
                'title' => 'IPV - Video Channel Home',
                'type' => 'page',
                'content' => self::get_video_channel_template(),
            ],
        ];

        return $templates_data[ $template_key ] ?? null;
    }

    /**
     * Single Video Page Template
     */
    private static function get_single_video_template() {
        return [
            [
                'id' => self::generate_id(),
                'elType' => 'section',
                'settings' => [
                    'layout' => 'boxed',
                    'content_width' => [ 'size' => 1200 ],
                ],
                'elements' => [
                    [
                        'id' => self::generate_id(),
                        'elType' => 'column',
                        'settings' => [ '_column_size' => 100 ],
                        'elements' => [
                            // Video Player
                            [
                                'id' => self::generate_id(),
                                'elType' => 'widget',
                                'widgetType' => 'ipv_video_player',
                                'settings' => [
                                    'controls' => 'yes',
                                    'aspect_ratio' => '16:9',
                                    'width' => [ 'size' => 100, 'unit' => '%' ],
                                ],
                            ],
                            // Spacer
                            [
                                'id' => self::generate_id(),
                                'elType' => 'widget',
                                'widgetType' => 'spacer',
                                'settings' => [ 'space' => [ 'size' => 40 ] ],
                            ],
                            // Video Stats
                            [
                                'id' => self::generate_id(),
                                'elType' => 'widget',
                                'widgetType' => 'ipv_stats',
                                'settings' => [
                                    'show' => 'total,views',
                                    'style' => 'inline',
                                ],
                            ],
                            // Spacer
                            [
                                'id' => self::generate_id(),
                                'elType' => 'widget',
                                'widgetType' => 'spacer',
                                'settings' => [ 'space' => [ 'size' => 60 ] ],
                            ],
                            // Heading - Related Videos
                            [
                                'id' => self::generate_id(),
                                'elType' => 'widget',
                                'widgetType' => 'heading',
                                'settings' => [
                                    'title' => 'Video Correlati',
                                    'header_size' => 'h2',
                                ],
                            ],
                            // Related Videos Grid
                            [
                                'id' => self::generate_id(),
                                'elType' => 'widget',
                                'widgetType' => 'ipv_video_grid',
                                'settings' => [
                                    'posts_per_page' => 6,
                                    'columns' => 3,
                                    'show_title' => 'yes',
                                    'show_excerpt' => 'yes',
                                    'show_meta' => 'yes',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Video Gallery Template
     */
    private static function get_video_gallery_template() {
        return [
            // Hero Section
            [
                'id' => self::generate_id(),
                'elType' => 'section',
                'settings' => [
                    'background_background' => 'gradient',
                    'background_color' => '#667eea',
                    'background_color_b' => '#764ba2',
                    'padding' => [ 'top' => 80, 'bottom' => 80 ],
                ],
                'elements' => [
                    [
                        'id' => self::generate_id(),
                        'elType' => 'column',
                        'settings' => [ '_column_size' => 100 ],
                        'elements' => [
                            [
                                'id' => self::generate_id(),
                                'elType' => 'widget',
                                'widgetType' => 'heading',
                                'settings' => [
                                    'title' => '🎬 Video Gallery',
                                    'header_size' => 'h1',
                                    'align' => 'center',
                                    'title_color' => '#ffffff',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            // Search & Filters
            [
                'id' => self::generate_id(),
                'elType' => 'section',
                'settings' => [
                    'content_width' => [ 'size' => 1200 ],
                    'padding' => [ 'top' => 40, 'bottom' => 20 ],
                ],
                'elements' => [
                    [
                        'id' => self::generate_id(),
                        'elType' => 'column',
                        'settings' => [ '_column_size' => 100 ],
                        'elements' => [
                            [
                                'id' => self::generate_id(),
                                'elType' => 'widget',
                                'widgetType' => 'ipv_search',
                                'settings' => [
                                    'show_filters' => 'yes',
                                    'show_sorting' => 'yes',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            // Video Grid
            [
                'id' => self::generate_id(),
                'elType' => 'section',
                'settings' => [
                    'content_width' => [ 'size' => 1400 ],
                    'padding' => [ 'top' => 20, 'bottom' => 80 ],
                ],
                'elements' => [
                    [
                        'id' => self::generate_id(),
                        'elType' => 'column',
                        'settings' => [ '_column_size' => 100 ],
                        'elements' => [
                            [
                                'id' => self::generate_id(),
                                'elType' => 'widget',
                                'widgetType' => 'ipv_video_grid',
                                'settings' => [
                                    'posts_per_page' => 12,
                                    'columns' => 4,
                                    'show_title' => 'yes',
                                    'show_excerpt' => 'yes',
                                    'show_meta' => 'yes',
                                    'gap' => [ 'size' => 30 ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Video Landing Page Template
     */
    private static function get_video_landing_template() {
        return [
            // Hero with Video Player
            [
                'id' => self::generate_id(),
                'elType' => 'section',
                'settings' => [
                    'layout' => 'full_width',
                    'background_background' => 'classic',
                    'background_color' => '#000000',
                    'padding' => [ 'top' => 100, 'bottom' => 100 ],
                ],
                'elements' => [
                    [
                        'id' => self::generate_id(),
                        'elType' => 'column',
                        'settings' => [ '_column_size' => 100 ],
                        'elements' => [
                            [
                                'id' => self::generate_id(),
                                'elType' => 'widget',
                                'widgetType' => 'heading',
                                'settings' => [
                                    'title' => 'Scopri i Nostri Video',
                                    'header_size' => 'h1',
                                    'align' => 'center',
                                    'title_color' => '#ffffff',
                                ],
                            ],
                            [
                                'id' => self::generate_id(),
                                'elType' => 'widget',
                                'widgetType' => 'spacer',
                                'settings' => [ 'space' => [ 'size' => 30 ] ],
                            ],
                            [
                                'id' => self::generate_id(),
                                'elType' => 'widget',
                                'widgetType' => 'ipv_video_player',
                                'settings' => [
                                    'controls' => 'yes',
                                    'aspect_ratio' => '16:9',
                                    'width' => [ 'size' => 80, 'unit' => '%' ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            // Stats Section
            [
                'id' => self::generate_id(),
                'elType' => 'section',
                'settings' => [
                    'background_background' => 'classic',
                    'background_color' => '#f8f9fa',
                    'padding' => [ 'top' => 60, 'bottom' => 60 ],
                ],
                'elements' => [
                    [
                        'id' => self::generate_id(),
                        'elType' => 'column',
                        'settings' => [ '_column_size' => 100 ],
                        'elements' => [
                            [
                                'id' => self::generate_id(),
                                'elType' => 'widget',
                                'widgetType' => 'ipv_stats',
                                'settings' => [
                                    'show' => 'total,views,categories,recent',
                                    'style' => 'cards',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            // Latest Videos
            [
                'id' => self::generate_id(),
                'elType' => 'section',
                'settings' => [
                    'content_width' => [ 'size' => 1200 ],
                    'padding' => [ 'top' => 80, 'bottom' => 80 ],
                ],
                'elements' => [
                    [
                        'id' => self::generate_id(),
                        'elType' => 'column',
                        'settings' => [ '_column_size' => 100 ],
                        'elements' => [
                            [
                                'id' => self::generate_id(),
                                'elType' => 'widget',
                                'widgetType' => 'heading',
                                'settings' => [
                                    'title' => 'Ultimi Video',
                                    'header_size' => 'h2',
                                    'align' => 'center',
                                ],
                            ],
                            [
                                'id' => self::generate_id(),
                                'elType' => 'widget',
                                'widgetType' => 'spacer',
                                'settings' => [ 'space' => [ 'size' => 40 ] ],
                            ],
                            [
                                'id' => self::generate_id(),
                                'elType' => 'widget',
                                'widgetType' => 'ipv_video_grid',
                                'settings' => [
                                    'posts_per_page' => 6,
                                    'columns' => 3,
                                    'orderby' => 'date',
                                    'order' => 'DESC',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Video Channel Home Template
     */
    private static function get_video_channel_template() {
        return [
            // Hero Section
            [
                'id' => self::generate_id(),
                'elType' => 'section',
                'settings' => [
                    'layout' => 'full_width',
                    'background_background' => 'gradient',
                    'background_color' => '#1a1a2e',
                    'background_color_b' => '#16213e',
                    'padding' => [ 'top' => 80, 'bottom' => 80 ],
                ],
                'elements' => [
                    [
                        'id' => self::generate_id(),
                        'elType' => 'column',
                        'settings' => [ '_column_size' => 100 ],
                        'elements' => [
                            [
                                'id' => self::generate_id(),
                                'elType' => 'widget',
                                'widgetType' => 'heading',
                                'settings' => [
                                    'title' => '📺 Video Channel',
                                    'header_size' => 'h1',
                                    'align' => 'center',
                                    'title_color' => '#ffffff',
                                ],
                            ],
                            [
                                'id' => self::generate_id(),
                                'elType' => 'widget',
                                'widgetType' => 'ipv_stats',
                                'settings' => [
                                    'show' => 'total,views',
                                    'style' => 'inline',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            // Latest Videos Section
            [
                'id' => self::generate_id(),
                'elType' => 'section',
                'settings' => [
                    'content_width' => [ 'size' => 1400 ],
                    'padding' => [ 'top' => 60, 'bottom' => 40 ],
                ],
                'elements' => [
                    [
                        'id' => self::generate_id(),
                        'elType' => 'column',
                        'settings' => [ '_column_size' => 100 ],
                        'elements' => [
                            [
                                'id' => self::generate_id(),
                                'elType' => 'widget',
                                'widgetType' => 'heading',
                                'settings' => [
                                    'title' => '🆕 Ultimi Video',
                                    'header_size' => 'h2',
                                ],
                            ],
                            [
                                'id' => self::generate_id(),
                                'elType' => 'widget',
                                'widgetType' => 'ipv_video_grid',
                                'settings' => [
                                    'posts_per_page' => 8,
                                    'columns' => 4,
                                    'orderby' => 'date',
                                    'order' => 'DESC',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            // Popular Videos Section
            [
                'id' => self::generate_id(),
                'elType' => 'section',
                'settings' => [
                    'content_width' => [ 'size' => 1400 ],
                    'background_background' => 'classic',
                    'background_color' => '#f8f9fa',
                    'padding' => [ 'top' => 60, 'bottom' => 60 ],
                ],
                'elements' => [
                    [
                        'id' => self::generate_id(),
                        'elType' => 'column',
                        'settings' => [ '_column_size' => 100 ],
                        'elements' => [
                            [
                                'id' => self::generate_id(),
                                'elType' => 'widget',
                                'widgetType' => 'heading',
                                'settings' => [
                                    'title' => '🔥 Video Più Visti',
                                    'header_size' => 'h2',
                                ],
                            ],
                            [
                                'id' => self::generate_id(),
                                'elType' => 'widget',
                                'widgetType' => 'ipv_video_grid',
                                'settings' => [
                                    'posts_per_page' => 8,
                                    'columns' => 4,
                                    'orderby' => 'meta_value_num',
                                    'order' => 'DESC',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            // Video Wall Section
            [
                'id' => self::generate_id(),
                'elType' => 'section',
                'settings' => [
                    'layout' => 'full_width',
                    'padding' => [ 'top' => 60, 'bottom' => 60 ],
                ],
                'elements' => [
                    [
                        'id' => self::generate_id(),
                        'elType' => 'column',
                        'settings' => [ '_column_size' => 100 ],
                        'elements' => [
                            [
                                'id' => self::generate_id(),
                                'elType' => 'widget',
                                'widgetType' => 'heading',
                                'settings' => [
                                    'title' => '🎥 Tutti i Video',
                                    'header_size' => 'h2',
                                    'align' => 'center',
                                ],
                            ],
                            [
                                'id' => self::generate_id(),
                                'elType' => 'widget',
                                'widgetType' => 'spacer',
                                'settings' => [ 'space' => [ 'size' => 30 ] ],
                            ],
                            [
                                'id' => self::generate_id(),
                                'elType' => 'widget',
                                'widgetType' => 'ipv_video_wall',
                                'settings' => [
                                    'layout' => 'grid',
                                    'infinite_scroll' => 'yes',
                                    'show_filters' => 'yes',
                                    'show_search' => 'yes',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Generate unique element ID
     */
    private static function generate_id() {
        return substr( md5( uniqid( rand(), true ) ), 0, 7 );
    }
}

IPV_Prod_Elementor_Templates::init();
