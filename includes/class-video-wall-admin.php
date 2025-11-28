<?php
/**
 * Video Wall Admin Panel
 * Gestione completa configurazione Video Wall
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_Video_Wall_Admin {

    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'add_admin_menu' ] );
        add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_admin_assets' ] );
        add_action( 'wp_ajax_ipv_wall_preview', [ __CLASS__, 'ajax_preview' ] );
    }

    public static function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=ipv_video',
            'Video Wall Settings',
            'Video Wall',
            'manage_options',
            'ipv-video-wall',
            [ __CLASS__, 'render_admin_page' ]
        );
    }

    public static function enqueue_admin_assets( $hook ) {
        if ( 'ipv_video_page_ipv-video-wall' !== $hook ) {
            return;
        }

        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker' );

        wp_enqueue_style(
            'ipv-wall-admin',
            IPV_PROD_PLUGIN_URL . 'assets/css/video-wall-admin.css',
            [],
            IPV_PROD_VERSION
        );

        wp_enqueue_script(
            'ipv-wall-admin',
            IPV_PROD_PLUGIN_URL . 'assets/js/video-wall-admin.js',
            [ 'jquery', 'wp-color-picker' ],
            IPV_PROD_VERSION,
            true
        );

        wp_localize_script( 'ipv-wall-admin', 'ipvWallAdmin', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'ipv_wall_admin' ),
        ]);
    }

    public static function register_settings() {
        // Layout Settings
        register_setting( 'ipv_wall_settings', 'ipv_wall_layout', [
            'type'    => 'string',
            'default' => '2+3',
        ]);

        register_setting( 'ipv_wall_settings', 'ipv_wall_per_page', [
            'type'    => 'integer',
            'default' => 5,
        ]);

        register_setting( 'ipv_wall_settings', 'ipv_wall_columns', [
            'type'    => 'integer',
            'default' => 3,
        ]);

        register_setting( 'ipv_wall_settings', 'ipv_wall_show_filters', [
            'type'    => 'string',
            'default' => 'yes',
        ]);

        register_setting( 'ipv_wall_settings', 'ipv_wall_show_search', [
            'type'    => 'string',
            'default' => 'yes',
        ]);

        // Display Settings
        register_setting( 'ipv_wall_settings', 'ipv_wall_show_date', [
            'type'    => 'string',
            'default' => 'yes',
        ]);

        register_setting( 'ipv_wall_settings', 'ipv_wall_show_category', [
            'type'    => 'string',
            'default' => 'yes',
        ]);

        register_setting( 'ipv_wall_settings', 'ipv_wall_show_speaker', [
            'type'    => 'string',
            'default' => 'yes',
        ]);

        register_setting( 'ipv_wall_settings', 'ipv_wall_show_views', [
            'type'    => 'string',
            'default' => 'yes',
        ]);

        register_setting( 'ipv_wall_settings', 'ipv_wall_show_duration', [
            'type'    => 'string',
            'default' => 'yes',
        ]);

        register_setting( 'ipv_wall_settings', 'ipv_wall_excerpt_length', [
            'type'    => 'integer',
            'default' => 0,
        ]);

        // Color Settings
        register_setting( 'ipv_wall_settings', 'ipv_wall_accent_color', [
            'type'    => 'string',
            'default' => '#FB0F5A',
        ]);

        register_setting( 'ipv_wall_settings', 'ipv_wall_card_bg', [
            'type'    => 'string',
            'default' => '#F5F5F5',
        ]);

        register_setting( 'ipv_wall_settings', 'ipv_wall_meta_bg', [
            'type'    => 'string',
            'default' => '#EAEAEA',
        ]);

        register_setting( 'ipv_wall_settings', 'ipv_wall_text_color', [
            'type'    => 'string',
            'default' => '#555',
        ]);

        // Sorting Settings
        register_setting( 'ipv_wall_settings', 'ipv_wall_orderby', [
            'type'    => 'string',
            'default' => 'date',
        ]);

        register_setting( 'ipv_wall_settings', 'ipv_wall_order', [
            'type'    => 'string',
            'default' => 'DESC',
        ]);

        // Animation Settings
        register_setting( 'ipv_wall_settings', 'ipv_wall_hover_effect', [
            'type'    => 'string',
            'default' => 'lift',
        ]);

        register_setting( 'ipv_wall_settings', 'ipv_wall_load_animation', [
            'type'    => 'string',
            'default' => 'fade',
        ]);
    }

    public static function render_admin_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Save settings
        if ( isset( $_POST['ipv_wall_save_settings'] ) && check_admin_referer( 'ipv_wall_settings' ) ) {
            self::save_settings();
            echo '<div class="notice notice-success is-dismissible"><p>Impostazioni salvate con successo!</p></div>';
        }

        $layout        = get_option( 'ipv_wall_layout', '2+3' );
        $per_page      = get_option( 'ipv_wall_per_page', 5 );
        $columns       = get_option( 'ipv_wall_columns', 3 );
        $show_filters  = get_option( 'ipv_wall_show_filters', 'yes' );
        $show_search   = get_option( 'ipv_wall_show_search', 'yes' );

        $show_date     = get_option( 'ipv_wall_show_date', 'yes' );
        $show_category = get_option( 'ipv_wall_show_category', 'yes' );
        $show_speaker  = get_option( 'ipv_wall_show_speaker', 'yes' );
        $show_views    = get_option( 'ipv_wall_show_views', 'yes' );
        $show_duration = get_option( 'ipv_wall_show_duration', 'yes' );
        $excerpt_length = get_option( 'ipv_wall_excerpt_length', 0 );

        $accent_color  = get_option( 'ipv_wall_accent_color', '#FB0F5A' );
        $card_bg       = get_option( 'ipv_wall_card_bg', '#F5F5F5' );
        $meta_bg       = get_option( 'ipv_wall_meta_bg', '#EAEAEA' );
        $text_color    = get_option( 'ipv_wall_text_color', '#555' );

        $orderby       = get_option( 'ipv_wall_orderby', 'date' );
        $order         = get_option( 'ipv_wall_order', 'DESC' );

        $hover_effect  = get_option( 'ipv_wall_hover_effect', 'lift' );
        $load_animation = get_option( 'ipv_wall_load_animation', 'fade' );

        ?>
        <div class="wrap ipv-wall-admin-wrap">
            <h1>🎬 Video Wall - Pannello di Controllo</h1>

            <div class="ipv-wall-admin-container">
                <div class="ipv-wall-admin-main">
                    <form method="post" action="">
                        <?php wp_nonce_field( 'ipv_wall_settings' ); ?>

                        <!-- Layout Settings -->
                        <div class="ipv-admin-section">
                            <h2>📐 Layout e Struttura</h2>

                            <table class="form-table">
                                <tr>
                                    <th scope="row">Layout Griglia</th>
                                    <td>
                                        <select name="ipv_wall_layout" id="ipv_wall_layout">
                                            <option value="2+3" <?php selected( $layout, '2+3' ); ?>>2+3 (2 video sopra, 3 sotto)</option>
                                            <option value="standard" <?php selected( $layout, 'standard' ); ?>>Standard (griglia uniforme)</option>
                                            <option value="masonry" <?php selected( $layout, 'masonry' ); ?>>Masonry (altezze variabili)</option>
                                            <option value="list" <?php selected( $layout, 'list' ); ?>>Lista (1 colonna con preview)</option>
                                        </select>
                                        <p class="description">Layout predefinito per la visualizzazione dei video</p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">Video per Pagina</th>
                                    <td>
                                        <input type="number" name="ipv_wall_per_page" value="<?php echo esc_attr( $per_page ); ?>" min="1" max="50" class="small-text">
                                        <p class="description">Numero di video da mostrare per pagina</p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">Numero Colonne</th>
                                    <td>
                                        <select name="ipv_wall_columns">
                                            <option value="2" <?php selected( $columns, 2 ); ?>>2 Colonne</option>
                                            <option value="3" <?php selected( $columns, 3 ); ?>>3 Colonne</option>
                                            <option value="4" <?php selected( $columns, 4 ); ?>>4 Colonne</option>
                                            <option value="5" <?php selected( $columns, 5 ); ?>>5 Colonne</option>
                                        </select>
                                        <p class="description">Colonne per layout standard (ignorato per layout 2+3)</p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">Mostra Filtri</th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="ipv_wall_show_filters" value="yes" <?php checked( $show_filters, 'yes' ); ?>>
                                            Abilita filtri categorie e relatori
                                        </label>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">Mostra Ricerca</th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="ipv_wall_show_search" value="yes" <?php checked( $show_search, 'yes' ); ?>>
                                            Abilita campo di ricerca
                                        </label>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <!-- Display Settings -->
                        <div class="ipv-admin-section">
                            <h2>👁️ Elementi Visibili</h2>

                            <table class="form-table">
                                <tr>
                                    <th scope="row">Informazioni da Mostrare</th>
                                    <td>
                                        <label><input type="checkbox" name="ipv_wall_show_date" value="yes" <?php checked( $show_date, 'yes' ); ?>> Data pubblicazione</label><br>
                                        <label><input type="checkbox" name="ipv_wall_show_category" value="yes" <?php checked( $show_category, 'yes' ); ?>> Categoria</label><br>
                                        <label><input type="checkbox" name="ipv_wall_show_speaker" value="yes" <?php checked( $show_speaker, 'yes' ); ?>> Relatore/Speaker</label><br>
                                        <label><input type="checkbox" name="ipv_wall_show_views" value="yes" <?php checked( $show_views, 'yes' ); ?>> Numero visualizzazioni</label><br>
                                        <label><input type="checkbox" name="ipv_wall_show_duration" value="yes" <?php checked( $show_duration, 'yes' ); ?>> Durata video</label>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">Lunghezza Estratto</th>
                                    <td>
                                        <input type="number" name="ipv_wall_excerpt_length" value="<?php echo esc_attr( $excerpt_length ); ?>" min="0" max="500" class="small-text">
                                        <p class="description">Numero caratteri estratto (0 = disabilitato)</p>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <!-- Color Settings -->
                        <div class="ipv-admin-section">
                            <h2>🎨 Colori e Stile</h2>

                            <table class="form-table">
                                <tr>
                                    <th scope="row">Colore Accent</th>
                                    <td>
                                        <input type="text" name="ipv_wall_accent_color" value="<?php echo esc_attr( $accent_color ); ?>" class="ipv-color-picker">
                                        <p class="description">Badge data, link hover, pulsanti</p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">Background Card</th>
                                    <td>
                                        <input type="text" name="ipv_wall_card_bg" value="<?php echo esc_attr( $card_bg ); ?>" class="ipv-color-picker">
                                        <p class="description">Sfondo delle card video</p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">Background Meta</th>
                                    <td>
                                        <input type="text" name="ipv_wall_meta_bg" value="<?php echo esc_attr( $meta_bg ); ?>" class="ipv-color-picker">
                                        <p class="description">Sfondo barra informazioni in basso</p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">Colore Testo</th>
                                    <td>
                                        <input type="text" name="ipv_wall_text_color" value="<?php echo esc_attr( $text_color ); ?>" class="ipv-color-picker">
                                        <p class="description">Colore testo principale</p>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <!-- Sorting Settings -->
                        <div class="ipv-admin-section">
                            <h2>🔃 Ordinamento</h2>

                            <table class="form-table">
                                <tr>
                                    <th scope="row">Ordina Per</th>
                                    <td>
                                        <select name="ipv_wall_orderby">
                                            <option value="date" <?php selected( $orderby, 'date' ); ?>>Data pubblicazione</option>
                                            <option value="title" <?php selected( $orderby, 'title' ); ?>>Titolo</option>
                                            <option value="modified" <?php selected( $orderby, 'modified' ); ?>>Ultima modifica</option>
                                            <option value="rand" <?php selected( $orderby, 'rand' ); ?>>Casuale</option>
                                            <option value="meta_value_num" <?php selected( $orderby, 'meta_value_num' ); ?>>Visualizzazioni</option>
                                        </select>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">Ordine</th>
                                    <td>
                                        <select name="ipv_wall_order">
                                            <option value="DESC" <?php selected( $order, 'DESC' ); ?>>Decrescente (più recente prima)</option>
                                            <option value="ASC" <?php selected( $order, 'ASC' ); ?>>Crescente (più vecchio prima)</option>
                                        </select>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <!-- Animation Settings -->
                        <div class="ipv-admin-section">
                            <h2>✨ Animazioni</h2>

                            <table class="form-table">
                                <tr>
                                    <th scope="row">Effetto Hover</th>
                                    <td>
                                        <select name="ipv_wall_hover_effect">
                                            <option value="lift" <?php selected( $hover_effect, 'lift' ); ?>>Lift (solleva card)</option>
                                            <option value="zoom" <?php selected( $hover_effect, 'zoom' ); ?>>Zoom (ingrandisce immagine)</option>
                                            <option value="none" <?php selected( $hover_effect, 'none' ); ?>>Nessuno</option>
                                        </select>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">Animazione Caricamento</th>
                                    <td>
                                        <select name="ipv_wall_load_animation">
                                            <option value="fade" <?php selected( $load_animation, 'fade' ); ?>>Fade In</option>
                                            <option value="slide" <?php selected( $load_animation, 'slide' ); ?>>Slide Up</option>
                                            <option value="none" <?php selected( $load_animation, 'none' ); ?>>Nessuna</option>
                                        </select>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <p class="submit">
                            <button type="submit" name="ipv_wall_save_settings" class="button button-primary button-large">
                                💾 Salva Impostazioni
                            </button>
                            <button type="button" class="button button-secondary button-large" id="ipv-reset-defaults">
                                🔄 Ripristina Predefiniti
                            </button>
                        </p>
                    </form>
                </div>

                <div class="ipv-wall-admin-sidebar">
                    <!-- Shortcode Generator -->
                    <div class="ipv-admin-box">
                        <h3>📋 Shortcode Generator</h3>
                        <p>Copia questo shortcode per usare il Video Wall:</p>
                        <div class="ipv-shortcode-preview">
                            <code id="ipv-shortcode-output">[ipv_video_wall]</code>
                            <button type="button" class="button button-small" id="ipv-copy-shortcode">Copia</button>
                        </div>

                        <h4>Con Parametri:</h4>
                        <div class="ipv-shortcode-preview">
                            <code id="ipv-shortcode-custom">[ipv_video_wall per_page="<?php echo $per_page; ?>" columns="<?php echo $columns; ?>"]</code>
                            <button type="button" class="button button-small" id="ipv-copy-custom">Copia</button>
                        </div>
                    </div>

                    <!-- Quick Stats -->
                    <div class="ipv-admin-box">
                        <h3>📊 Statistiche</h3>
                        <?php
                        $total_videos = wp_count_posts( 'ipv_video' )->publish;
                        $total_cats = wp_count_terms( 'ipv_categoria' );
                        $total_speakers = wp_count_terms( 'ipv_relatore' );
                        ?>
                        <ul class="ipv-stats-list">
                            <li><strong><?php echo number_format_i18n( $total_videos ); ?></strong> Video pubblicati</li>
                            <li><strong><?php echo number_format_i18n( $total_cats ); ?></strong> Categorie</li>
                            <li><strong><?php echo number_format_i18n( $total_speakers ); ?></strong> Relatori</li>
                        </ul>
                    </div>

                    <!-- Preview -->
                    <div class="ipv-admin-box">
                        <h3>👁️ Anteprima Colori</h3>
                        <div class="ipv-color-preview" id="ipv-color-preview">
                            <div class="ipv-preview-badge" style="background: <?php echo esc_attr( $accent_color ); ?>">
                                Badge Data
                            </div>
                            <div class="ipv-preview-card" style="background: <?php echo esc_attr( $card_bg ); ?>">
                                <div class="ipv-preview-title">Titolo Video</div>
                                <div class="ipv-preview-meta" style="background: <?php echo esc_attr( $meta_bg ); ?>; color: <?php echo esc_attr( $text_color ); ?>">
                                    Categoria • Speaker • Views
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Help -->
                    <div class="ipv-admin-box">
                        <h3>❓ Guida Rapida</h3>
                        <ul>
                            <li>• Layout <strong>2+3</strong> = 2 video grandi + 3 piccoli</li>
                            <li>• <strong>Standard</strong> = griglia uniforme</li>
                            <li>• <strong>Masonry</strong> = altezze variabili</li>
                            <li>• <strong>Lista</strong> = visualizzazione lista</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private static function save_settings() {
        $fields = [
            'ipv_wall_layout',
            'ipv_wall_per_page',
            'ipv_wall_columns',
            'ipv_wall_show_filters',
            'ipv_wall_show_search',
            'ipv_wall_show_date',
            'ipv_wall_show_category',
            'ipv_wall_show_speaker',
            'ipv_wall_show_views',
            'ipv_wall_show_duration',
            'ipv_wall_excerpt_length',
            'ipv_wall_accent_color',
            'ipv_wall_card_bg',
            'ipv_wall_meta_bg',
            'ipv_wall_text_color',
            'ipv_wall_orderby',
            'ipv_wall_order',
            'ipv_wall_hover_effect',
            'ipv_wall_load_animation',
        ];

        foreach ( $fields as $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                update_option( $field, sanitize_text_field( $_POST[ $field ] ) );
            } else {
                // Checkbox non selezionate
                if ( strpos( $field, 'show_' ) !== false ) {
                    update_option( $field, 'no' );
                }
            }
        }
    }

    public static function ajax_preview() {
        check_ajax_referer( 'ipv_wall_admin', 'nonce' );

        // Generate preview HTML
        wp_send_json_success( [
            'html' => 'Preview HTML here',
        ]);
    }
}
