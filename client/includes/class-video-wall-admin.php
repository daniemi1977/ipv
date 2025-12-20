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

        // Filter Configuration (v9.0.0 - unified from class-video-wall-settings.php)
        register_setting( 'ipv_wall_settings', 'ipv_wall_enabled_filters', [
            'type'              => 'array',
            'default'           => [ 'categories', 'speakers', 'tags', 'search', 'sort' ],
            'sanitize_callback' => [ __CLASS__, 'sanitize_enabled_filters' ],
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
                            <h2>📐 Layout e Struttura
                                <span class="ipv-help-icon" title="Configura come vengono visualizzati i video nel wall">ℹ️</span>
                            </h2>

                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        Layout Griglia
                                        <span class="ipv-help-icon" title="Scegli lo stile di visualizzazione: 2+3 è perfetto per evidenziare i video più recenti, Standard per una griglia uniforme, Masonry per un effetto Pinterest, Lista per dettagli completi">ℹ️</span>
                                    </th>
                                    <td>
                                        <select name="ipv_wall_layout" id="ipv_wall_layout">
                                            <option value="2+3" <?php selected( $layout, '2+3' ); ?>>2+3 (2 video sopra, 3 sotto)</option>
                                            <option value="standard" <?php selected( $layout, 'standard' ); ?>>Standard (griglia uniforme)</option>
                                            <option value="masonry" <?php selected( $layout, 'masonry' ); ?>>Masonry (altezze variabili)</option>
                                            <option value="list" <?php selected( $layout, 'list' ); ?>>Lista (1 colonna con preview)</option>
                                        </select>
                                        <p class="description">💡 <strong>2+3:</strong> Ideale per homepage | <strong>Standard:</strong> Griglia pulita | <strong>Masonry:</strong> Design creativo | <strong>Lista:</strong> Massimo dettaglio</p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">
                                        Video per Pagina
                                        <span class="ipv-help-icon" title="Quanti video mostrare prima della paginazione. Consigliato: 5-12 per prestazioni ottimali">ℹ️</span>
                                    </th>
                                    <td>
                                        <input type="number" name="ipv_wall_per_page" value="<?php echo esc_attr( $per_page ); ?>" min="1" max="50" class="small-text">
                                        <p class="description">💡 Consigliato: <strong>5</strong> per layout 2+3, <strong>9-12</strong> per griglie standard</p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">
                                        Numero Colonne
                                        <span class="ipv-help-icon" title="Numero di colonne per layout Standard/Masonry. Mobile: si adatta automaticamente a 2 colonne">ℹ️</span>
                                    </th>
                                    <td>
                                        <select name="ipv_wall_columns">
                                            <option value="2" <?php selected( $columns, 2 ); ?>>2 Colonne</option>
                                            <option value="3" <?php selected( $columns, 3 ); ?>>3 Colonne (consigliato)</option>
                                            <option value="4" <?php selected( $columns, 4 ); ?>>4 Colonne</option>
                                            <option value="5" <?php selected( $columns, 5 ); ?>>5 Colonne</option>
                                        </select>
                                        <p class="description">⚠️ Usato solo per layout <strong>Standard</strong> e <strong>Masonry</strong> (ignorato per 2+3 e Lista)</p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">
                                        Mostra Filtri
                                        <span class="ipv-help-icon" title="Abilita barra filtri AJAX per categorie, relatori, tag e ordinamento. Aggiorna i video senza ricaricare la pagina">ℹ️</span>
                                    </th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="ipv_wall_show_filters" value="yes" <?php checked( $show_filters, 'yes' ); ?>>
                                            Abilita filtri categorie e relatori
                                        </label>
                                        <p class="description">🎯 Consente agli utenti di filtrare i video per categoria, relatore e tag in tempo reale</p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">
                                        Mostra Ricerca
                                        <span class="ipv-help-icon" title="Campo di ricerca live per trovare video per titolo o contenuto">ℹ️</span>
                                    </th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="ipv_wall_show_search" value="yes" <?php checked( $show_search, 'yes' ); ?>>
                                            Abilita campo di ricerca
                                        </label>
                                        <p class="description">🔍 Ricerca istantanea per titolo video</p>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <!-- Display Settings -->
                        <div class="ipv-admin-section">
                            <h2>👁️ Elementi Visibili
                                <span class="ipv-help-icon" title="Scegli quali informazioni mostrare nelle card video">ℹ️</span>
                            </h2>

                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        Informazioni da Mostrare
                                        <span class="ipv-help-icon" title="Ogni elemento aggiunge metadati alla card video. Meno elementi = design più pulito">ℹ️</span>
                                    </th>
                                    <td>
                                        <label><input type="checkbox" name="ipv_wall_show_date" value="yes" <?php checked( $show_date, 'yes' ); ?>> 📅 Data pubblicazione</label><br>
                                        <label><input type="checkbox" name="ipv_wall_show_category" value="yes" <?php checked( $show_category, 'yes' ); ?>> 📁 Categoria</label><br>
                                        <label><input type="checkbox" name="ipv_wall_show_speaker" value="yes" <?php checked( $show_speaker, 'yes' ); ?>> 👤 Relatore/Speaker</label><br>
                                        <label><input type="checkbox" name="ipv_wall_show_views" value="yes" <?php checked( $show_views, 'yes' ); ?>> 👁️ Numero visualizzazioni</label><br>
                                        <label><input type="checkbox" name="ipv_wall_show_duration" value="yes" <?php checked( $show_duration, 'yes' ); ?>> ⏱️ Durata video</label>
                                        <p class="description">💡 Consiglio: Abilita 3-4 elementi per un design bilanciato</p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">
                                        Lunghezza Estratto
                                        <span class="ipv-help-icon" title="Mostra un estratto della descrizione video sotto il titolo. 0 = nascosto, 150-200 caratteri consigliati">ℹ️</span>
                                    </th>
                                    <td>
                                        <input type="number" name="ipv_wall_excerpt_length" value="<?php echo esc_attr( $excerpt_length ); ?>" min="0" max="500" class="small-text">
                                        <p class="description">📝 <strong>0</strong> = nascosto | <strong>150-200</strong> = consigliato | <strong>500</strong> = massimo</p>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <!-- Color Settings -->
                        <div class="ipv-admin-section">
                            <h2>🎨 Colori e Stile
                                <span class="ipv-help-icon" title="Personalizza i colori del video wall per adattarli al tuo brand">ℹ️</span>
                            </h2>

                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        Colore Accent
                                        <span class="ipv-help-icon" title="Colore principale per badge, link hover e elementi di accento. Usa il colore primario del tuo brand">ℹ️</span>
                                    </th>
                                    <td>
                                        <input type="text" name="ipv_wall_accent_color" value="<?php echo esc_attr( $accent_color ); ?>" class="ipv-color-picker">
                                        <p class="description">🎯 Badge data, link hover, pulsanti | Default: <strong>#FB0F5A</strong></p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">
                                        Background Card
                                        <span class="ipv-help-icon" title="Colore di sfondo delle card video. Usa un colore chiaro per migliore leggibilità">ℹ️</span>
                                    </th>
                                    <td>
                                        <input type="text" name="ipv_wall_card_bg" value="<?php echo esc_attr( $card_bg ); ?>" class="ipv-color-picker">
                                        <p class="description">📦 Sfondo delle card video | Default: <strong>#F5F5F5</strong></p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">
                                        Background Meta
                                        <span class="ipv-help-icon" title="Colore di sfondo della barra informazioni in basso alle card (categoria, speaker, durata)">ℹ️</span>
                                    </th>
                                    <td>
                                        <input type="text" name="ipv_wall_meta_bg" value="<?php echo esc_attr( $meta_bg ); ?>" class="ipv-color-picker">
                                        <p class="description">📊 Sfondo barra informazioni | Default: <strong>#EAEAEA</strong></p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">
                                        Colore Testo
                                        <span class="ipv-help-icon" title="Colore del testo principale. Assicurati di un buon contrasto con lo sfondo">ℹ️</span>
                                    </th>
                                    <td>
                                        <input type="text" name="ipv_wall_text_color" value="<?php echo esc_attr( $text_color ); ?>" class="ipv-color-picker">
                                        <p class="description">✏️ Testo principale | Default: <strong>#555</strong></p>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <!-- Sorting Settings -->
                        <div class="ipv-admin-section">
                            <h2>🔃 Ordinamento
                                <span class="ipv-help-icon" title="Imposta l'ordine predefinito di visualizzazione dei video">ℹ️</span>
                            </h2>

                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        Ordina Per
                                        <span class="ipv-help-icon" title="Criterio di ordinamento predefinito. Gli utenti possono cambiarlo tramite i filtri se abilitati">ℹ️</span>
                                    </th>
                                    <td>
                                        <select name="ipv_wall_orderby">
                                            <option value="date" <?php selected( $orderby, 'date' ); ?>>📅 Data pubblicazione (consigliato)</option>
                                            <option value="title" <?php selected( $orderby, 'title' ); ?>>🔤 Titolo alfabetico</option>
                                            <option value="modified" <?php selected( $orderby, 'modified' ); ?>>🔄 Ultima modifica</option>
                                            <option value="rand" <?php selected( $orderby, 'rand' ); ?>>🎲 Casuale</option>
                                            <option value="meta_value_num" <?php selected( $orderby, 'meta_value_num' ); ?>>👁️ Visualizzazioni</option>
                                        </select>
                                        <p class="description">💡 <strong>Data:</strong> più recenti prima | <strong>Visualizzazioni:</strong> più popolari prima</p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">
                                        Ordine
                                        <span class="ipv-help-icon" title="Direzione ordinamento: crescente o decrescente">ℹ️</span>
                                    </th>
                                    <td>
                                        <select name="ipv_wall_order">
                                            <option value="DESC" <?php selected( $order, 'DESC' ); ?>>⬇️ Decrescente (più recente/alto prima)</option>
                                            <option value="ASC" <?php selected( $order, 'ASC' ); ?>>⬆️ Crescente (più vecchio/basso prima)</option>
                                        </select>
                                        <p class="description">⚙️ DESC consigliato per date e visualizzazioni | ASC per titoli alfabetici</p>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <!-- Animation Settings -->
                        <div class="ipv-admin-section">
                            <h2>✨ Animazioni
                                <span class="ipv-help-icon" title="Effetti visivi per migliorare l'esperienza utente">ℹ️</span>
                            </h2>

                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        Effetto Hover
                                        <span class="ipv-help-icon" title="Animazione quando l'utente passa il mouse sulla card video">ℹ️</span>
                                    </th>
                                    <td>
                                        <select name="ipv_wall_hover_effect">
                                            <option value="lift" <?php selected( $hover_effect, 'lift' ); ?>>⬆️ Lift (solleva card)</option>
                                            <option value="zoom" <?php selected( $hover_effect, 'zoom' ); ?>>🔍 Zoom (ingrandisce immagine)</option>
                                            <option value="none" <?php selected( $hover_effect, 'none' ); ?>>⛔ Nessuno</option>
                                        </select>
                                        <p class="description">💡 <strong>Lift:</strong> moderno e pulito | <strong>Zoom:</strong> focus sull'immagine</p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">
                                        Animazione Caricamento
                                        <span class="ipv-help-icon" title="Effetto di entrata quando i video vengono caricati o filtrati">ℹ️</span>
                                    </th>
                                    <td>
                                        <select name="ipv_wall_load_animation">
                                            <option value="fade" <?php selected( $load_animation, 'fade' ); ?>>💫 Fade In (dissolvenza)</option>
                                            <option value="slide" <?php selected( $load_animation, 'slide' ); ?>>⬆️ Slide Up (scivola dal basso)</option>
                                            <option value="none" <?php selected( $load_animation, 'none' ); ?>>⛔ Nessuna</option>
                                        </select>
                                        <p class="description">🎬 Animazione mostrata quando i video appaiono dopo il caricamento o filtro</p>
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
                    <!-- Shortcodes Library -->
                    <div class="ipv-admin-box ipv-shortcodes-library">
                        <h3>📋 Shortcodes Disponibili</h3>
                        <p class="description">Tutti gli shortcode IPV Production System Pro</p>

                        <!-- Video Wall -->
                        <div class="ipv-shortcode-item">
                            <h4>🎬 Video Wall</h4>
                            <div class="ipv-shortcode-code">
                                <code>[ipv_video_wall]</code>
                                <button type="button" class="button button-small ipv-copy-btn" data-shortcode="[ipv_video_wall]">Copia</button>
                            </div>
                            <p class="ipv-shortcode-desc">Griglia video completa con filtri AJAX, ricerca e paginazione dinamica</p>
                            <details class="ipv-shortcode-params">
                                <summary>Parametri disponibili</summary>
                                <ul>
                                    <li><code>per_page="5"</code> - Video per pagina</li>
                                    <li><code>layout="2+3"</code> - Layout: 2+3, standard, masonry, list</li>
                                    <li><code>columns="3"</code> - Numero colonne (2-5)</li>
                                    <li><code>show_filters="yes"</code> - Mostra filtri (yes/no)</li>
                                    <li><code>categoria="slug"</code> - Filtra per categoria</li>
                                    <li><code>relatore="slug"</code> - Filtra per relatore</li>
                                </ul>
                                <p class="ipv-example"><strong>Esempio:</strong><br>
                                <code>[ipv_video_wall per_page="<?php echo $per_page; ?>" columns="<?php echo $columns; ?>" layout="<?php echo $layout; ?>"]</code></p>
                            </details>
                        </div>

                        <!-- Coming Soon -->
                        <div class="ipv-shortcode-item">
                            <h4>🔴 Video in Anteprima</h4>
                            <div class="ipv-shortcode-code">
                                <code>[ipv_coming_soon]</code>
                                <button type="button" class="button button-small ipv-copy-btn" data-shortcode="[ipv_coming_soon]">Copia</button>
                            </div>
                            <p class="ipv-shortcode-desc">Mostra video programmati/premiere con countdown e badge LIVE</p>
                            <details class="ipv-shortcode-params">
                                <summary>Parametri disponibili</summary>
                                <ul>
                                    <li><code>layout="list"</code> - Layout: list, cards, grid</li>
                                    <li><code>limit="5"</code> - Numero massimo video</li>
                                    <li><code>title="In Programma"</code> - Titolo sezione</li>
                                    <li><code>show_countdown="true"</code> - Mostra countdown</li>
                                    <li><code>show_title="true"</code> - Mostra titolo sezione</li>
                                </ul>
                                <p class="ipv-example"><strong>Esempio:</strong><br>
                                <code>[ipv_coming_soon layout="cards" limit="10"]</code></p>
                                <p class="ipv-note">💡 <strong>Alias italiano:</strong> <code>[ipv_in_programma]</code></p>
                            </details>
                        </div>

                        <!-- Video Player -->
                        <div class="ipv-shortcode-item">
                            <h4>▶️ Video Player Singolo</h4>
                            <div class="ipv-shortcode-code">
                                <code>[ipv_video id="123"]</code>
                                <button type="button" class="button button-small ipv-copy-btn" data-shortcode='[ipv_video id="123"]'>Copia</button>
                            </div>
                            <p class="ipv-shortcode-desc">Embed player responsive per singolo video</p>
                            <details class="ipv-shortcode-params">
                                <summary>Parametri disponibili</summary>
                                <ul>
                                    <li><code>id="123"</code> - ID post video (richiesto)</li>
                                    <li><code>autoplay="no"</code> - Autoplay (yes/no)</li>
                                    <li><code>controls="yes"</code> - Controlli player (yes/no)</li>
                                    <li><code>mute="no"</code> - Audio muto (yes/no)</li>
                                    <li><code>loop="no"</code> - Loop video (yes/no)</li>
                                    <li><code>width="100%"</code> - Larghezza player</li>
                                    <li><code>aspect="16:9"</code> - Aspect ratio: 16:9, 4:3, 21:9, 1:1</li>
                                </ul>
                                <p class="ipv-example"><strong>Esempio:</strong><br>
                                <code>[ipv_video id="123" autoplay="yes" mute="yes"]</code></p>
                            </details>
                        </div>

                        <!-- Video Grid -->
                        <div class="ipv-shortcode-item">
                            <h4>📊 Griglia Video</h4>
                            <div class="ipv-shortcode-code">
                                <code>[ipv_grid]</code>
                                <button type="button" class="button button-small ipv-copy-btn" data-shortcode="[ipv_grid]">Copia</button>
                            </div>
                            <p class="ipv-shortcode-desc">Griglia semplice di video (senza filtri dinamici)</p>
                            <details class="ipv-shortcode-params">
                                <summary>Parametri disponibili</summary>
                                <ul>
                                    <li><code>count="6"</code> - Numero video da mostrare</li>
                                    <li><code>columns="3"</code> - Numero colonne (2-5)</li>
                                    <li><code>category="1,2"</code> - ID categorie (separati da virgola)</li>
                                    <li><code>orderby="date"</code> - Ordina per: date, title, modified, rand, views</li>
                                    <li><code>order="DESC"</code> - Ordine: DESC, ASC</li>
                                    <li><code>show_title="yes"</code> - Mostra titolo</li>
                                    <li><code>show_excerpt="yes"</code> - Mostra estratto</li>
                                    <li><code>show_meta="yes"</code> - Mostra metadati</li>
                                    <li><code>gap="20px"</code> - Spaziatura tra card</li>
                                </ul>
                                <p class="ipv-example"><strong>Esempio:</strong><br>
                                <code>[ipv_grid count="9" columns="3" orderby="views"]</code></p>
                            </details>
                        </div>

                        <!-- Search Form -->
                        <div class="ipv-shortcode-item">
                            <h4>🔍 Form di Ricerca</h4>
                            <div class="ipv-shortcode-code">
                                <code>[ipv_search]</code>
                                <button type="button" class="button button-small ipv-copy-btn" data-shortcode="[ipv_search]">Copia</button>
                            </div>
                            <p class="ipv-shortcode-desc">Form di ricerca avanzata con filtri categoria/relatore</p>
                            <details class="ipv-shortcode-params">
                                <summary>Parametri disponibili</summary>
                                <ul>
                                    <li><code>placeholder="Cerca video..."</code> - Placeholder campo ricerca</li>
                                    <li><code>button_text="Cerca"</code> - Testo pulsante</li>
                                    <li><code>show_filters="yes"</code> - Mostra filtri (yes/no)</li>
                                    <li><code>show_sorting="yes"</code> - Mostra ordinamento (yes/no)</li>
                                </ul>
                                <p class="ipv-example"><strong>Esempio:</strong><br>
                                <code>[ipv_search placeholder="Trova il tuo video..." button_text="🔍 Cerca"]</code></p>
                            </details>
                        </div>

                        <!-- Stats -->
                        <div class="ipv-shortcode-item">
                            <h4>📈 Statistiche</h4>
                            <div class="ipv-shortcode-code">
                                <code>[ipv_stats]</code>
                                <button type="button" class="button button-small ipv-copy-btn" data-shortcode="[ipv_stats]">Copia</button>
                            </div>
                            <p class="ipv-shortcode-desc">Box statistiche: totale video, views, categorie, recenti</p>
                            <details class="ipv-shortcode-params">
                                <summary>Parametri disponibili</summary>
                                <ul>
                                    <li><code>show="total,views,categories,recent"</code> - Statistiche da mostrare</li>
                                    <li><code>style="cards"</code> - Stile: cards, list, inline</li>
                                </ul>
                                <p class="ipv-example"><strong>Esempio:</strong><br>
                                <code>[ipv_stats show="total,views" style="cards"]</code></p>
                            </details>
                        </div>

                        <style>
                        .ipv-shortcodes-library { max-height: 600px; overflow-y: auto; }
                        .ipv-shortcode-item { margin-bottom: 20px; padding: 15px; background: #f9f9f9; border-left: 3px solid #2271b1; border-radius: 4px; }
                        .ipv-shortcode-item h4 { margin: 0 0 10px 0; font-size: 14px; color: #2271b1; }
                        .ipv-shortcode-code { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; }
                        .ipv-shortcode-code code { flex: 1; background: #fff; padding: 8px 12px; border: 1px solid #ddd; border-radius: 3px; font-size: 12px; }
                        .ipv-shortcode-desc { margin: 0 0 10px 0; font-size: 13px; color: #666; font-style: italic; }
                        .ipv-shortcode-params { margin-top: 10px; }
                        .ipv-shortcode-params summary { cursor: pointer; font-size: 12px; font-weight: 600; color: #2271b1; padding: 5px 0; }
                        .ipv-shortcode-params ul { margin: 10px 0; padding-left: 20px; font-size: 12px; }
                        .ipv-shortcode-params li { margin-bottom: 5px; }
                        .ipv-shortcode-params li code { background: #fff; padding: 2px 6px; border: 1px solid #ddd; border-radius: 2px; font-size: 11px; }
                        .ipv-example { margin-top: 10px; padding: 10px; background: #fff; border: 1px solid #ddd; border-radius: 3px; font-size: 12px; }
                        .ipv-example code { background: #f0f0f0; padding: 2px 6px; border-radius: 2px; }
                        .ipv-note { margin-top: 8px; padding: 8px; background: #fff3cd; border-left: 3px solid #ffc107; font-size: 12px; }
                        .ipv-copy-btn { white-space: nowrap; }
                        </style>

                        <script>
                        jQuery(document).ready(function($) {
                            $('.ipv-copy-btn').on('click', function() {
                                var shortcode = $(this).data('shortcode');
                                navigator.clipboard.writeText(shortcode).then(function() {
                                    var btn = $(event.target);
                                    var originalText = btn.text();
                                    btn.text('✓ Copiato!').css('background', '#46b450');
                                    setTimeout(function() {
                                        btn.text(originalText).css('background', '');
                                    }, 2000);
                                });
                            });
                        });
                        </script>
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
                        <hr style="margin: 15px 0; border: none; border-top: 1px solid #ddd;">
                        <p style="font-size: 12px; color: #666; margin: 10px 0 0 0;">
                            💡 <strong>Tip:</strong> Passa il mouse sull'icona <span class="ipv-help-icon" style="font-size: 14px;">ℹ️</span> per maggiori informazioni su ogni opzione
                        </p>
                    </div>
                </div>
            </div>

            <style>
            /* Help Icons Tooltip System */
            .ipv-help-icon {
                display: inline-block;
                width: 18px;
                height: 18px;
                line-height: 18px;
                text-align: center;
                font-size: 14px;
                cursor: help;
                margin-left: 5px;
                opacity: 0.7;
                transition: opacity 0.2s;
                position: relative;
            }
            .ipv-help-icon:hover {
                opacity: 1;
            }
            .ipv-help-icon:hover::after {
                content: attr(title);
                position: absolute;
                left: 100%;
                top: 50%;
                transform: translateY(-50%);
                margin-left: 10px;
                background: #2c3338;
                color: #fff;
                padding: 8px 12px;
                border-radius: 4px;
                font-size: 13px;
                white-space: normal;
                width: 280px;
                z-index: 10000;
                box-shadow: 0 2px 10px rgba(0,0,0,0.2);
                line-height: 1.4;
                font-weight: normal;
                pointer-events: none;
            }
            .ipv-help-icon:hover::before {
                content: '';
                position: absolute;
                left: 100%;
                top: 50%;
                transform: translateY(-50%);
                margin-left: 4px;
                border: 6px solid transparent;
                border-right-color: #2c3338;
                z-index: 10001;
            }
            /* Mobile adjustments */
            @media (max-width: 782px) {
                .ipv-help-icon:hover::after {
                    left: auto;
                    right: 0;
                    margin-left: 0;
                    width: 220px;
                }
                .ipv-help-icon:hover::before {
                    display: none;
                }
            }
            /* Admin Sections Styling */
            .ipv-admin-section {
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                padding: 20px;
                margin-bottom: 20px;
            }
            .ipv-admin-section h2 {
                margin: 0 0 20px 0;
                padding-bottom: 15px;
                border-bottom: 2px solid #2271b1;
                font-size: 18px;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .ipv-admin-section .form-table th {
                padding: 15px 0;
                width: 250px;
            }
            .ipv-admin-section .form-table td {
                padding: 15px 0;
            }
            .ipv-admin-section .description {
                margin-top: 8px;
                font-size: 13px;
                line-height: 1.5;
            }
            /* Color Picker */
            .ipv-color-picker {
                max-width: 100px;
            }
            </style>
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

    /**
     * Sanitize enabled filters array
     * Migrated from class-video-wall-settings.php in v9.0.0
     */
    public static function sanitize_enabled_filters( $value ) {
        if ( ! is_array( $value ) ) {
            return [ 'categories', 'speakers', 'tags', 'search', 'sort' ];
        }
        $valid_filters = [ 'categories', 'speakers', 'tags', 'search', 'sort' ];
        return array_values( array_intersect( $value, $valid_filters ) );
    }
}
