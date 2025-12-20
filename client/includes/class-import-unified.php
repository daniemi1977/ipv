<?php
/**
 * IPV Production System Pro - Import Unificato
 *
 * Unifica tutti i metodi di import in un'unica interfaccia con tab:
 * - Video Singolo (YouTube URL)
 * - Import Multiplo (batch URL)
 * - Import da RSS Feed
 * - Import da Canale YouTube
 *
 * @package IPV_Production_System_Pro
 * @version 10.0.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_Import_Unified {

    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'add_menu' ], 10 );
        add_action( 'admin_post_ipv_import_single', [ __CLASS__, 'handle_single_import' ] );
        add_action( 'admin_post_ipv_import_batch', [ __CLASS__, 'handle_batch_import' ] );
        add_action( 'admin_post_ipv_import_rss_save', [ __CLASS__, 'handle_rss_save' ] );
        add_action( 'admin_post_ipv_import_channel', [ __CLASS__, 'handle_channel_import' ] );
    }

    public static function add_menu() {
        add_submenu_page(
            'edit.php?post_type=ipv_video',
            __( 'Import Video', 'ipv-production-system-pro' ),
            '📥 ' . __( 'Import Video', 'ipv-production-system-pro' ),
            'manage_options',
            'ipv-import',
            [ __CLASS__, 'render' ]
        );
    }

    public static function render() {
        $current_tab = $_GET['tab'] ?? 'single';
        $allowed_tabs = [ 'single', 'batch', 'rss', 'channel' ];

        if ( ! in_array( $current_tab, $allowed_tabs ) ) {
            $current_tab = 'single';
        }

        ?>
        <div class="wrap ipv-import-page">
            <h1>📥 <?php _e( 'Import Video', 'ipv-production-system-pro' ); ?></h1>

            <?php settings_errors( 'ipv_import' ); ?>

            <nav class="nav-tab-wrapper">
                <a href="?post_type=ipv_video&page=ipv-import&tab=single" class="nav-tab <?php echo $current_tab === 'single' ? 'nav-tab-active' : ''; ?>">
                    📺 <?php _e( 'Video Singolo', 'ipv-production-system-pro' ); ?>
                </a>
                <a href="?post_type=ipv_video&page=ipv-import&tab=batch" class="nav-tab <?php echo $current_tab === 'batch' ? 'nav-tab-active' : ''; ?>">
                    📦 <?php _e( 'Import Multiplo', 'ipv-production-system-pro' ); ?>
                </a>
                <a href="?post_type=ipv_video&page=ipv-import&tab=rss" class="nav-tab <?php echo $current_tab === 'rss' ? 'nav-tab-active' : ''; ?>">
                    📡 <?php _e( 'RSS Feed', 'ipv-production-system-pro' ); ?>
                </a>
                <a href="?post_type=ipv_video&page=ipv-import&tab=channel" class="nav-tab <?php echo $current_tab === 'channel' ? 'nav-tab-active' : ''; ?>">
                    📺 <?php _e( 'Canale YouTube', 'ipv-production-system-pro' ); ?>
                </a>
            </nav>

            <div class="ipv-tab-content">
                <?php
                switch ( $current_tab ) {
                    case 'single':
                        self::render_single_tab();
                        break;
                    case 'batch':
                        self::render_batch_tab();
                        break;
                    case 'rss':
                        self::render_rss_tab();
                        break;
                    case 'channel':
                        self::render_channel_tab();
                        break;
                }
                ?>
            </div>
        </div>

        <style>
        .ipv-tab-content {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-top: none;
            padding: 20px;
            margin-bottom: 20px;
        }
        .ipv-import-box {
            max-width: 800px;
            margin: 20px 0;
        }
        .ipv-import-box h3 {
            margin-top: 0;
        }
        .ipv-input-group {
            margin-bottom: 20px;
        }
        .ipv-input-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .ipv-input-group input[type="text"],
        .ipv-input-group input[type="url"],
        .ipv-input-group input[type="number"],
        .ipv-input-group select,
        .ipv-input-group textarea {
            width: 100%;
            max-width: 600px;
        }
        .ipv-input-group textarea {
            min-height: 150px;
        }
        .ipv-help-text {
            color: #666;
            font-size: 13px;
            margin-top: 5px;
        }
        .ipv-info-box {
            background: #e7f3ff;
            border-left: 4px solid #2271b1;
            padding: 15px;
            margin: 20px 0;
        }
        .ipv-warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
        }
        .ipv-rss-status {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .ipv-rss-status h4 {
            margin-top: 0;
        }
        .ipv-status-active {
            color: #28a745;
            font-weight: bold;
        }
        .ipv-status-inactive {
            color: #dc3545;
            font-weight: bold;
        }
        </style>
        <?php
    }

    /**
     * Tab: Video Singolo
     */
    private static function render_single_tab() {
        ?>
        <div class="ipv-import-box">
            <h2><?php _e( 'Importa Video Singolo', 'ipv-production-system-pro' ); ?></h2>
            <p><?php _e( 'Inserisci l\'URL di un video YouTube per importarlo nel tuo sito.', 'ipv-production-system-pro' ); ?></p>

            <form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
                <?php wp_nonce_field( 'ipv_import_single', 'ipv_import_nonce' ); ?>
                <input type="hidden" name="action" value="ipv_import_single">

                <div class="ipv-input-group">
                    <label for="video_url"><?php _e( 'URL Video YouTube', 'ipv-production-system-pro' ); ?></label>
                    <input type="url"
                           name="video_url"
                           id="video_url"
                           class="regular-text"
                           placeholder="https://www.youtube.com/watch?v=..."
                           required>
                    <p class="ipv-help-text">
                        <?php _e( 'Formati supportati: youtube.com/watch?v=ID, youtu.be/ID', 'ipv-production-system-pro' ); ?>
                    </p>
                </div>

                <div class="ipv-input-group">
                    <label>
                        <input type="checkbox" name="auto_publish" value="1">
                        <?php _e( 'Pubblica automaticamente dopo l\'import', 'ipv-production-system-pro' ); ?>
                    </label>
                </div>

                <div class="ipv-input-group">
                    <label>
                        <input type="checkbox" name="generate_transcript" value="1" checked>
                        <?php _e( 'Genera trascrizione (usa 1 credito)', 'ipv-production-system-pro' ); ?>
                    </label>
                </div>

                <div class="ipv-input-group">
                    <label>
                        <input type="checkbox" name="generate_description" value="1" checked>
                        <?php _e( 'Genera descrizione AI (Golden Prompt)', 'ipv-production-system-pro' ); ?>
                    </label>
                </div>

                <p>
                    <button type="submit" class="button button-primary button-hero">
                        <span class="dashicons dashicons-upload" style="vertical-align: middle;"></span>
                        <?php _e( 'Importa Video', 'ipv-production-system-pro' ); ?>
                    </button>
                </p>
            </form>

            <div class="ipv-info-box">
                <strong>💡 Suggerimento:</strong>
                <?php _e( 'Il video verrà aggiunto alla coda e processato automaticamente. Puoi monitorare lo stato nella pagina "Coda".', 'ipv-production-system-pro' ); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Tab: Import Multiplo (Batch)
     */
    private static function render_batch_tab() {
        ?>
        <div class="ipv-import-box">
            <h2><?php _e( 'Import Multiplo', 'ipv-production-system-pro' ); ?></h2>
            <p><?php _e( 'Importa più video contemporaneamente inserendo un URL per riga.', 'ipv-production-system-pro' ); ?></p>

            <form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
                <?php wp_nonce_field( 'ipv_import_batch', 'ipv_import_nonce' ); ?>
                <input type="hidden" name="action" value="ipv_import_batch">

                <div class="ipv-input-group">
                    <label for="video_urls"><?php _e( 'URL Video (uno per riga)', 'ipv-production-system-pro' ); ?></label>
                    <textarea name="video_urls"
                              id="video_urls"
                              class="large-text"
                              rows="10"
                              placeholder="https://www.youtube.com/watch?v=...&#10;https://www.youtube.com/watch?v=...&#10;https://youtu.be/..."
                              required></textarea>
                    <p class="ipv-help-text">
                        <?php _e( 'Inserisci un URL YouTube per riga. Righe vuote o commenti (#) verranno ignorati.', 'ipv-production-system-pro' ); ?>
                    </p>
                </div>

                <div class="ipv-input-group">
                    <label>
                        <input type="checkbox" name="auto_publish" value="1">
                        <?php _e( 'Pubblica automaticamente tutti i video dopo l\'import', 'ipv-production-system-pro' ); ?>
                    </label>
                </div>

                <div class="ipv-input-group">
                    <label>
                        <input type="checkbox" name="generate_transcript" value="1" checked>
                        <?php _e( 'Genera trascrizioni (1 credito per video)', 'ipv-production-system-pro' ); ?>
                    </label>
                </div>

                <div class="ipv-input-group">
                    <label>
                        <input type="checkbox" name="generate_description" value="1" checked>
                        <?php _e( 'Genera descrizioni AI', 'ipv-production-system-pro' ); ?>
                    </label>
                </div>

                <div class="ipv-input-group">
                    <label>
                        <input type="checkbox" name="skip_duplicates" value="1" checked>
                        <?php _e( 'Salta video già importati (controllo duplicati)', 'ipv-production-system-pro' ); ?>
                    </label>
                </div>

                <p>
                    <button type="submit" class="button button-primary button-hero">
                        <span class="dashicons dashicons-upload" style="vertical-align: middle;"></span>
                        <?php _e( 'Importa Tutti i Video', 'ipv-production-system-pro' ); ?>
                    </button>
                </p>
            </form>

            <div class="ipv-warning-box">
                <strong>⚠️ Attenzione:</strong>
                <?php _e( 'L\'import di molti video consuma crediti. Verifica di avere crediti sufficienti prima di procedere.', 'ipv-production-system-pro' ); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Tab: RSS Feed
     */
    private static function render_rss_tab() {
        $rss_enabled = get_option( 'ipv_rss_enabled', false );
        $feed_url = get_option( 'ipv_rss_feed_url', '' );
        $schedule = get_option( 'ipv_rss_schedule', 'hourly' );
        $auto_publish = get_option( 'ipv_rss_auto_publish', false );

        ?>
        <div class="ipv-import-box">
            <h2><?php _e( 'Import Automatico da RSS Feed', 'ipv-production-system-pro' ); ?></h2>
            <p><?php _e( 'Configura un feed RSS di YouTube per importare automaticamente i nuovi video pubblicati.', 'ipv-production-system-pro' ); ?></p>

            <?php if ( $rss_enabled ) : ?>
            <div class="ipv-rss-status">
                <h4>📡 <?php _e( 'Stato RSS Feed', 'ipv-production-system-pro' ); ?></h4>
                <p class="ipv-status-active">✅ <?php _e( 'Attivo', 'ipv-production-system-pro' ); ?></p>
                <p><strong><?php _e( 'Feed URL:', 'ipv-production-system-pro' ); ?></strong> <?php echo esc_html( $feed_url ); ?></p>
                <p><strong><?php _e( 'Frequenza:', 'ipv-production-system-pro' ); ?></strong> <?php echo esc_html( $schedule ); ?></p>
                <?php
                $next_run = wp_next_scheduled( 'ipv_rss_check_feed' );
                if ( $next_run ) :
                ?>
                <p><strong><?php _e( 'Prossima scansione:', 'ipv-production-system-pro' ); ?></strong>
                    <?php echo human_time_diff( $next_run, current_time( 'timestamp' ) ); ?>
                    <?php _e( 'da adesso', 'ipv-production-system-pro' ); ?>
                </p>
                <?php endif; ?>
            </div>
            <?php else : ?>
            <div class="ipv-rss-status">
                <p class="ipv-status-inactive">❌ <?php _e( 'Non Attivo', 'ipv-production-system-pro' ); ?></p>
            </div>
            <?php endif; ?>

            <form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
                <?php wp_nonce_field( 'ipv_import_rss_save', 'ipv_import_nonce' ); ?>
                <input type="hidden" name="action" value="ipv_import_rss_save">

                <div class="ipv-input-group">
                    <label for="feed_url"><?php _e( 'URL Feed YouTube', 'ipv-production-system-pro' ); ?></label>
                    <input type="url"
                           name="feed_url"
                           id="feed_url"
                           class="regular-text"
                           value="<?php echo esc_attr( $feed_url ); ?>"
                           placeholder="https://www.youtube.com/feeds/videos.xml?channel_id=...">
                    <p class="ipv-help-text">
                        <?php _e( 'Formato: https://www.youtube.com/feeds/videos.xml?channel_id=YOUR_CHANNEL_ID', 'ipv-production-system-pro' ); ?>
                    </p>
                </div>

                <div class="ipv-input-group">
                    <label for="schedule"><?php _e( 'Frequenza Controllo', 'ipv-production-system-pro' ); ?></label>
                    <select name="schedule" id="schedule">
                        <option value="30min" <?php selected( $schedule, '30min' ); ?>><?php _e( 'Ogni 30 minuti', 'ipv-production-system-pro' ); ?></option>
                        <option value="hourly" <?php selected( $schedule, 'hourly' ); ?>><?php _e( 'Ogni ora', 'ipv-production-system-pro' ); ?></option>
                        <option value="sixhours" <?php selected( $schedule, 'sixhours' ); ?>><?php _e( 'Ogni 6 ore', 'ipv-production-system-pro' ); ?></option>
                        <option value="twicedaily" <?php selected( $schedule, 'twicedaily' ); ?>><?php _e( 'Due volte al giorno', 'ipv-production-system-pro' ); ?></option>
                        <option value="daily" <?php selected( $schedule, 'daily' ); ?>><?php _e( 'Una volta al giorno', 'ipv-production-system-pro' ); ?></option>
                    </select>
                </div>

                <div class="ipv-input-group">
                    <label>
                        <input type="checkbox" name="rss_enabled" value="1" <?php checked( $rss_enabled ); ?>>
                        <?php _e( 'Abilita import automatico da RSS', 'ipv-production-system-pro' ); ?>
                    </label>
                </div>

                <div class="ipv-input-group">
                    <label>
                        <input type="checkbox" name="auto_publish" value="1" <?php checked( $auto_publish ); ?>>
                        <?php _e( 'Pubblica automaticamente i video importati', 'ipv-production-system-pro' ); ?>
                    </label>
                </div>

                <p>
                    <button type="submit" class="button button-primary">
                        <span class="dashicons dashicons-saved" style="vertical-align: middle;"></span>
                        <?php _e( 'Salva Configurazione RSS', 'ipv-production-system-pro' ); ?>
                    </button>
                </p>
            </form>

            <div class="ipv-info-box">
                <h4>💡 <?php _e( 'Come ottenere il feed RSS di un canale YouTube', 'ipv-production-system-pro' ); ?></h4>
                <ol>
                    <li><?php _e( 'Vai sul canale YouTube', 'ipv-production-system-pro' ); ?></li>
                    <li><?php _e( 'Copia il Channel ID dall\'URL o dalle impostazioni', 'ipv-production-system-pro' ); ?></li>
                    <li><?php _e( 'Il feed URL sarà:', 'ipv-production-system-pro' ); ?>
                        <code>https://www.youtube.com/feeds/videos.xml?channel_id=YOUR_CHANNEL_ID</code>
                    </li>
                </ol>
            </div>
        </div>
        <?php
    }

    /**
     * Tab: Canale YouTube
     */
    private static function render_channel_tab() {
        $channel_id = get_option( 'ipv_youtube_channel_id', '' );

        ?>
        <div class="ipv-import-box">
            <h2><?php _e( 'Import da Canale YouTube', 'ipv-production-system-pro' ); ?></h2>
            <p><?php _e( 'Importa video in blocco dalla cronologia di un canale YouTube.', 'ipv-production-system-pro' ); ?></p>

            <form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
                <?php wp_nonce_field( 'ipv_import_channel', 'ipv_import_nonce' ); ?>
                <input type="hidden" name="action" value="ipv_import_channel">

                <div class="ipv-input-group">
                    <label for="channel_id"><?php _e( 'Channel ID YouTube', 'ipv-production-system-pro' ); ?></label>
                    <input type="text"
                           name="channel_id"
                           id="channel_id"
                           class="regular-text"
                           value="<?php echo esc_attr( $channel_id ); ?>"
                           placeholder="UC..."
                           required>
                    <p class="ipv-help-text">
                        <?php _e( 'Il Channel ID inizia sempre con "UC" e si trova nell\'URL del canale.', 'ipv-production-system-pro' ); ?>
                    </p>
                </div>

                <div class="ipv-input-group">
                    <label for="max_results"><?php _e( 'Numero video da importare', 'ipv-production-system-pro' ); ?></label>
                    <select name="max_results" id="max_results">
                        <option value="10">10 video</option>
                        <option value="25">25 video</option>
                        <option value="50">50 video</option>
                        <option value="100" selected>100 video</option>
                        <option value="500">500 video (massimo)</option>
                    </select>
                    <p class="ipv-help-text">
                        <?php _e( 'Verranno importati gli ultimi N video pubblicati sul canale.', 'ipv-production-system-pro' ); ?>
                    </p>
                </div>

                <div class="ipv-input-group">
                    <label>
                        <input type="checkbox" name="skip_duplicates" value="1" checked>
                        <?php _e( 'Salta video già importati', 'ipv-production-system-pro' ); ?>
                    </label>
                </div>

                <div class="ipv-input-group">
                    <label>
                        <input type="checkbox" name="auto_publish" value="1">
                        <?php _e( 'Pubblica automaticamente i video importati', 'ipv-production-system-pro' ); ?>
                    </label>
                </div>

                <div class="ipv-input-group">
                    <label>
                        <input type="checkbox" name="generate_transcript" value="1" checked>
                        <?php _e( 'Genera trascrizioni (1 credito per video)', 'ipv-production-system-pro' ); ?>
                    </label>
                </div>

                <p>
                    <button type="submit" class="button button-primary button-hero">
                        <span class="dashicons dashicons-download" style="vertical-align: middle;"></span>
                        <?php _e( 'Importa dal Canale', 'ipv-production-system-pro' ); ?>
                    </button>
                </p>
            </form>

            <div class="ipv-warning-box">
                <strong>⚠️ Attenzione:</strong>
                <?php _e( 'L\'import di 500 video può richiedere molto tempo e consumare molti crediti. Si consiglia di iniziare con batch più piccoli.', 'ipv-production-system-pro' ); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Handle single video import
     */
    public static function handle_single_import() {
        check_admin_referer( 'ipv_import_single', 'ipv_import_nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized' );
        }

        $video_url = sanitize_text_field( $_POST['video_url'] ?? '' );
        $auto_publish = ! empty( $_POST['auto_publish'] );
        $generate_transcript = ! empty( $_POST['generate_transcript'] );
        $generate_description = ! empty( $_POST['generate_description'] );

        if ( empty( $video_url ) ) {
            add_settings_error( 'ipv_import', 'missing_url', __( 'URL video mancante.', 'ipv-production-system-pro' ), 'error' );
            set_transient( 'settings_errors', get_settings_errors(), 30 );
            wp_safe_redirect( admin_url( 'edit.php?post_type=ipv_video&page=ipv-import&tab=single' ) );
            exit;
        }

        // Extract video ID
        $video_id = self::extract_video_id( $video_url );
        if ( ! $video_id ) {
            add_settings_error( 'ipv_import', 'invalid_url', __( 'URL YouTube non valido.', 'ipv-production-system-pro' ), 'error' );
            set_transient( 'settings_errors', get_settings_errors(), 30 );
            wp_safe_redirect( admin_url( 'edit.php?post_type=ipv_video&page=ipv-import&tab=single' ) );
            exit;
        }

        // Add to queue (if queue class exists)
        if ( class_exists( 'IPV_Prod_Queue' ) ) {
            IPV_Prod_Queue::enqueue( $video_id, $video_url, 'manual' );
            add_settings_error( 'ipv_import', 'success', __( '✅ Video aggiunto alla coda di import!', 'ipv-production-system-pro' ), 'success' );
        } else {
            add_settings_error( 'ipv_import', 'no_queue', __( 'Sistema coda non disponibile.', 'ipv-production-system-pro' ), 'error' );
        }

        set_transient( 'settings_errors', get_settings_errors(), 30 );
        wp_safe_redirect( admin_url( 'edit.php?post_type=ipv_video&page=ipv-import&tab=single' ) );
        exit;
    }

    /**
     * Handle batch import
     */
    public static function handle_batch_import() {
        check_admin_referer( 'ipv_import_batch', 'ipv_import_nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized' );
        }

        $video_urls = sanitize_textarea_field( $_POST['video_urls'] ?? '' );
        $skip_duplicates = ! empty( $_POST['skip_duplicates'] );

        if ( empty( $video_urls ) ) {
            add_settings_error( 'ipv_import', 'missing_urls', __( 'Nessun URL inserito.', 'ipv-production-system-pro' ), 'error' );
            set_transient( 'settings_errors', get_settings_errors(), 30 );
            wp_safe_redirect( admin_url( 'edit.php?post_type=ipv_video&page=ipv-import&tab=batch' ) );
            exit;
        }

        $urls = explode( "\n", $video_urls );
        $added = 0;
        $skipped = 0;

        foreach ( $urls as $url ) {
            $url = trim( $url );

            // Skip empty lines and comments
            if ( empty( $url ) || strpos( $url, '#' ) === 0 ) {
                continue;
            }

            $video_id = self::extract_video_id( $url );
            if ( ! $video_id ) {
                $skipped++;
                continue;
            }

            // Check duplicates
            if ( $skip_duplicates && self::video_exists( $video_id ) ) {
                $skipped++;
                continue;
            }

            // Add to queue
            if ( class_exists( 'IPV_Prod_Queue' ) ) {
                IPV_Prod_Queue::enqueue( $video_id, $url, 'batch' );
                $added++;
            }
        }

        if ( $added > 0 ) {
            add_settings_error( 'ipv_import', 'success',
                sprintf( __( '✅ %d video aggiunti alla coda. %d saltati.', 'ipv-production-system-pro' ), $added, $skipped ),
                'success'
            );
        } else {
            add_settings_error( 'ipv_import', 'no_imports', __( 'Nessun video valido da importare.', 'ipv-production-system-pro' ), 'warning' );
        }

        set_transient( 'settings_errors', get_settings_errors(), 30 );
        wp_safe_redirect( admin_url( 'edit.php?post_type=ipv_video&page=ipv-import&tab=batch' ) );
        exit;
    }

    /**
     * Handle RSS save
     */
    public static function handle_rss_save() {
        check_admin_referer( 'ipv_import_rss_save', 'ipv_import_nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized' );
        }

        $feed_url = esc_url_raw( $_POST['feed_url'] ?? '' );
        $schedule = sanitize_text_field( $_POST['schedule'] ?? 'hourly' );
        $rss_enabled = ! empty( $_POST['rss_enabled'] );
        $auto_publish = ! empty( $_POST['auto_publish'] );

        update_option( 'ipv_rss_feed_url', $feed_url );
        update_option( 'ipv_rss_schedule', $schedule );
        update_option( 'ipv_rss_enabled', $rss_enabled );
        update_option( 'ipv_rss_auto_publish', $auto_publish );

        // Reschedule cron
        wp_clear_scheduled_hook( 'ipv_rss_check_feed' );
        if ( $rss_enabled && ! empty( $feed_url ) ) {
            wp_schedule_event( time(), $schedule, 'ipv_rss_check_feed' );
        }

        add_settings_error( 'ipv_import', 'success', __( '✅ Configurazione RSS salvata!', 'ipv-production-system-pro' ), 'success' );
        set_transient( 'settings_errors', get_settings_errors(), 30 );
        wp_safe_redirect( admin_url( 'edit.php?post_type=ipv_video&page=ipv-import&tab=rss' ) );
        exit;
    }

    /**
     * Handle channel import (v10.0.15)
     */
    public static function handle_channel_import() {
        check_admin_referer( 'ipv_import_channel', 'ipv_import_nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized' );
        }

        $channel_id = sanitize_text_field( $_POST['channel_id'] ?? '' );
        $max_results = absint( $_POST['max_results'] ?? 50 );

        if ( empty( $channel_id ) ) {
            add_settings_error( 'ipv_import', 'error', __( '❌ Channel ID richiesto', 'ipv-production-system-pro' ), 'error' );
            set_transient( 'settings_errors', get_settings_errors(), 30 );
            wp_safe_redirect( admin_url( 'edit.php?post_type=ipv_video&page=ipv-import&tab=channel' ) );
            exit;
        }

        update_option( 'ipv_youtube_channel_id', $channel_id );

        // v10.0.15 - Implementa import canale usando RSS feed
        // YouTube fornisce un RSS feed per ogni canale
        $feed_url = 'https://www.youtube.com/feeds/videos.xml?channel_id=' . $channel_id;

        // Fetch RSS feed
        $response = wp_remote_get( $feed_url, [ 'timeout' => 30 ] );

        if ( is_wp_error( $response ) ) {
            add_settings_error( 'ipv_import', 'error',
                sprintf( __( '❌ Errore fetch feed: %s', 'ipv-production-system-pro' ), $response->get_error_message() ),
                'error'
            );
            set_transient( 'settings_errors', get_settings_errors(), 30 );
            wp_safe_redirect( admin_url( 'edit.php?post_type=ipv_video&page=ipv-import&tab=channel' ) );
            exit;
        }

        $body = wp_remote_retrieve_body( $response );
        libxml_use_internal_errors( true );
        $xml = simplexml_load_string( $body );

        if ( false === $xml ) {
            add_settings_error( 'ipv_import', 'error', __( '❌ Errore parsing XML feed', 'ipv-production-system-pro' ), 'error' );
            set_transient( 'settings_errors', get_settings_errors(), 30 );
            wp_safe_redirect( admin_url( 'edit.php?post_type=ipv_video&page=ipv-import&tab=channel' ) );
            exit;
        }

        // Registra namespace XML
        $xml->registerXPathNamespace( 'atom', 'http://www.w3.org/2005/Atom' );
        $xml->registerXPathNamespace( 'yt', 'http://www.youtube.com/xml/schemas/2015' );

        // Estrai video entries
        $entries = $xml->xpath( '//atom:entry' );

        if ( empty( $entries ) ) {
            add_settings_error( 'ipv_import', 'warning', __( '⚠️ Nessun video trovato nel canale', 'ipv-production-system-pro' ), 'warning' );
            set_transient( 'settings_errors', get_settings_errors(), 30 );
            wp_safe_redirect( admin_url( 'edit.php?post_type=ipv_video&page=ipv-import&tab=channel' ) );
            exit;
        }

        $imported = 0;
        $skipped = 0;
        $limit = min( $max_results, count( $entries ) );

        foreach ( array_slice( $entries, 0, $limit ) as $entry ) {
            // Estrai video ID
            $entry->registerXPathNamespace( 'yt', 'http://www.youtube.com/xml/schemas/2015' );
            $video_id_nodes = $entry->xpath( 'yt:videoId' );

            if ( empty( $video_id_nodes ) ) {
                continue;
            }

            $video_id = (string) $video_id_nodes[0];

            // Verifica se già importato
            if ( self::video_exists( $video_id ) ) {
                $skipped++;
                continue;
            }

            // Aggiungi alla coda
            $video_url = 'https://www.youtube.com/watch?v=' . $video_id;
            IPV_Prod_Queue::enqueue( $video_id, $video_url, 'channel' );
            $imported++;
        }

        add_settings_error( 'ipv_import', 'success',
            sprintf( __( '✅ Import canale: %d video aggiunti alla coda, %d già esistenti', 'ipv-production-system-pro' ), $imported, $skipped ),
            'success'
        );

        set_transient( 'settings_errors', get_settings_errors(), 30 );
        wp_safe_redirect( admin_url( 'edit.php?post_type=ipv_video&page=ipv-import&tab=channel' ) );
        exit;
    }

    /**
     * Extract YouTube video ID from URL
     */
    private static function extract_video_id( $url ) {
        preg_match( '/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $matches );
        return $matches[1] ?? null;
    }

    /**
     * Check if video already exists
     */
    private static function video_exists( $video_id ) {
        $existing = get_posts([
            'post_type' => 'ipv_video',
            'meta_key' => '_ipv_video_id',
            'meta_value' => $video_id,
            'posts_per_page' => 1,
            'fields' => 'ids'
        ]);
        return ! empty( $existing );
    }
}
