<?php
/**
 * IPV Production System Pro - Menu Manager
 * 
 * Sistema di menu centralizzato e semplificato
 * v1.0.3 - Menu semplificato + Golden Prompt Pro
 *
 * STRUTTURA MENU BASE:
 * ├── 📊 Dashboard (panoramica rapida)
 * ├── 📋 Tutti i Video
 * ├── 📥 Importa Video (singolo/multiplo/canale/RSS in tab)
 * ├── ⏳ Coda Elaborazione
 * ├── ⚙️ Impostazioni
 * └── 🔧 Strumenti
 *
 * CON GOLDEN PROMPT PRO (59€):
 * ├── 📁 Categorie
 * ├── 👤 Relatori
 * └── 🏷️ Tag
 *
 * @package IPV_Production_System_Pro
 * @version 10.0.24
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_Menu_Manager {

    const MENU_SLUG = 'ipv-production';
    const CAPABILITY = 'manage_options';

    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'register_menus' ], 5 );
        add_action( 'parent_file', [ __CLASS__, 'fix_parent_file' ] );
        add_action( 'submenu_file', [ __CLASS__, 'fix_submenu_file' ], 10, 2 );
    }

    /**
     * v1.0.3 - Check if user has Golden Prompt Pro license (59€ plan)
     * Golden Prompt enables: Categorie, Relatori, Tag menus
     *
     * @return bool
     */
    private static function has_golden_prompt_license() {
        // Check via Golden Prompt Client if available
        if ( class_exists( 'IPV_Pro_Golden_Prompt_Client' ) ) {
            $client = IPV_Pro_Golden_Prompt_Client::instance();
            if ( method_exists( $client, 'has_golden_prompt' ) && $client->has_golden_prompt() ) {
                return true;
            }
        }

        // Fallback: check license info for variant
        $license_info = get_option( 'ipv_license_info', [] );
        if ( ! empty( $license_info['variant'] ) ) {
            // Golden Prompt is included in Professional (59€) and Business plans
            $pro_variants = [ 'professional', 'business', 'enterprise', 'pro', 'golden' ];
            return in_array( strtolower( $license_info['variant'] ), $pro_variants, true );
        }

        return false;
    }

    /**
     * Registra menu principale e sottomenu
     */
    public static function register_menus() {
        // ══════════════════════════════════════════
        // MENU PRINCIPALE
        // ══════════════════════════════════════════
        add_menu_page(
            __( 'IPV Videos', 'ipv-production-system-pro' ),
            __( 'IPV Videos', 'ipv-production-system-pro' ),
            self::CAPABILITY,
            self::MENU_SLUG,
            [ __CLASS__, 'render_dashboard' ],
            'dashicons-video-alt3',
            26
        );

        // ══════════════════════════════════════════
        // SEZIONE 1: OPERAZIONI PRINCIPALI
        // ══════════════════════════════════════════

        // Dashboard (primo = default)
        add_submenu_page(
            self::MENU_SLUG,
            __( 'Dashboard', 'ipv-production-system-pro' ),
            '📊 ' . __( 'Dashboard', 'ipv-production-system-pro' ),
            self::CAPABILITY,
            self::MENU_SLUG,
            [ __CLASS__, 'render_dashboard' ]
        );

        // Tutti i Video
        add_submenu_page(
            self::MENU_SLUG,
            __( 'Tutti i Video', 'ipv-production-system-pro' ),
            '📋 ' . __( 'Tutti i Video', 'ipv-production-system-pro' ),
            'edit_posts',
            'edit.php?post_type=ipv_video'
        );

        // Importa Video (PRINCIPALE - con tab)
        add_submenu_page(
            self::MENU_SLUG,
            __( 'Importa Video', 'ipv-production-system-pro' ),
            '📥 ' . __( 'Importa Video', 'ipv-production-system-pro' ),
            self::CAPABILITY,
            'ipv-import',
            [ __CLASS__, 'render_import_page' ]
        );

        // Coda Elaborazione
        add_submenu_page(
            self::MENU_SLUG,
            __( 'Coda', 'ipv-production-system-pro' ),
            '⏳ ' . __( 'Coda', 'ipv-production-system-pro' ),
            self::CAPABILITY,
            'ipv-queue',
            [ __CLASS__, 'render_queue_page' ]
        );

        // ══════════════════════════════════════════
        // SEZIONE 2: ORGANIZZAZIONE CONTENUTI (solo con Golden Prompt Pro)
        // ══════════════════════════════════════════

        // v1.0.3 - Mostra Categorie, Relatori, Tag solo con licenza Golden Prompt attiva
        $has_golden_prompt = self::has_golden_prompt_license();

        if ( $has_golden_prompt ) {
            // Categorie
            add_submenu_page(
                self::MENU_SLUG,
                __( 'Categorie', 'ipv-production-system-pro' ),
                '📁 ' . __( 'Categorie', 'ipv-production-system-pro' ),
                'manage_categories',
                'edit-tags.php?taxonomy=ipv_categoria&post_type=ipv_video'
            );

            // Relatori
            add_submenu_page(
                self::MENU_SLUG,
                __( 'Relatori', 'ipv-production-system-pro' ),
                '👤 ' . __( 'Relatori', 'ipv-production-system-pro' ),
                'manage_categories',
                'edit-tags.php?taxonomy=ipv_relatore&post_type=ipv_video'
            );

            // Tag
            add_submenu_page(
                self::MENU_SLUG,
                __( 'Tag', 'ipv-production-system-pro' ),
                '🏷️ ' . __( 'Tag', 'ipv-production-system-pro' ),
                'manage_categories',
                'edit-tags.php?taxonomy=post_tag&post_type=ipv_video'
            );
        }

        // ══════════════════════════════════════════
        // SEZIONE 3: CONFIGURAZIONE
        // ══════════════════════════════════════════

        // Impostazioni
        add_submenu_page(
            self::MENU_SLUG,
            __( 'Impostazioni', 'ipv-production-system-pro' ),
            '⚙️ ' . __( 'Impostazioni', 'ipv-production-system-pro' ),
            self::CAPABILITY,
            'ipv-settings',
            [ __CLASS__, 'render_settings_page' ]
        );

        // Strumenti
        add_submenu_page(
            self::MENU_SLUG,
            __( 'Strumenti', 'ipv-production-system-pro' ),
            '🔧 ' . __( 'Strumenti', 'ipv-production-system-pro' ),
            self::CAPABILITY,
            'ipv-tools',
            [ __CLASS__, 'render_tools_page' ]
        );
    }

    /**
     * Fix parent file per evidenziare il menu corretto
     */
    public static function fix_parent_file( $parent_file ) {
        global $current_screen;
        
        if ( ! $current_screen ) {
            return $parent_file;
        }

        // Se siamo in edit/new di ipv_video o nelle tassonomie
        if ( $current_screen->post_type === 'ipv_video' || 
             in_array( $current_screen->taxonomy, [ 'ipv_categoria', 'ipv_relatore' ] ) ||
             ( $current_screen->taxonomy === 'post_tag' && $current_screen->post_type === 'ipv_video' ) ) {
            return self::MENU_SLUG;
        }

        return $parent_file;
    }

    /**
     * Fix submenu file per evidenziare il sottomenu corretto
     */
    public static function fix_submenu_file( $submenu_file, $parent_file ) {
        global $current_screen;
        
        if ( ! $current_screen ) {
            return $submenu_file;
        }

        if ( $current_screen->base === 'edit' && $current_screen->post_type === 'ipv_video' ) {
            return 'edit.php?post_type=ipv_video';
        }

        if ( $current_screen->base === 'post' && $current_screen->post_type === 'ipv_video' ) {
            return 'edit.php?post_type=ipv_video';
        }

        return $submenu_file;
    }

    // ══════════════════════════════════════════════════════════════
    // RENDER: DASHBOARD
    // ══════════════════════════════════════════════════════════════

    public static function render_dashboard() {
        if ( class_exists( 'IPV_Prod_Dashboard' ) && method_exists( 'IPV_Prod_Dashboard', 'render' ) ) {
            IPV_Prod_Dashboard::render();
        } else {
            self::render_fallback_dashboard();
        }
    }

    private static function render_fallback_dashboard() {
        global $wpdb;
        
        $total = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='ipv_video' AND post_status='publish'" );
        $queue_pending = 0;
        
        if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}ipv_prod_queue'" ) ) {
            $queue_pending = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ipv_prod_queue WHERE status='pending'" );
        }
        ?>
        <div class="wrap">
            <h1>📊 <?php esc_html_e( 'Dashboard', 'ipv-production-system-pro' ); ?></h1>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 30px 0;">
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 12px; text-align: center;">
                    <div style="font-size: 48px; font-weight: bold;"><?php echo esc_html( $total ); ?></div>
                    <div style="opacity: 0.9;">Video Totali</div>
                </div>
                <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 30px; border-radius: 12px; text-align: center;">
                    <div style="font-size: 48px; font-weight: bold;"><?php echo esc_html( $queue_pending ); ?></div>
                    <div style="opacity: 0.9;">In Coda</div>
                </div>
            </div>

            <div style="background: #f0f6fc; padding: 20px; border-radius: 8px; margin-top: 30px;">
                <h3 style="margin-top: 0;">🚀 Inizia Subito</h3>
                <p>
                    <a href="<?php echo admin_url( 'admin.php?page=ipv-import' ); ?>" class="button button-primary button-hero">
                        📥 Importa il tuo primo video
                    </a>
                </p>
            </div>
        </div>
        <?php
    }

    // ══════════════════════════════════════════════════════════════
    // RENDER: IMPORT PAGE (con tab)
    // ══════════════════════════════════════════════════════════════

    public static function render_import_page() {
        $active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'single';
        ?>
        <div class="wrap">
            <h1>📥 <?php esc_html_e( 'Importa Video', 'ipv-production-system-pro' ); ?></h1>

            <p class="description" style="font-size: 14px; margin-bottom: 20px;">
                Scegli come vuoi importare i tuoi video. Dopo l'importazione, il sistema genererà automaticamente trascrizione e descrizione AI.
            </p>

            <nav class="nav-tab-wrapper" style="margin-bottom: 20px;">
                <a href="<?php echo add_query_arg( 'tab', 'single', remove_query_arg( 'tab' ) ); ?>" 
                   class="nav-tab <?php echo $active_tab === 'single' ? 'nav-tab-active' : ''; ?>">
                    📹 Singolo Video
                </a>
                <a href="<?php echo add_query_arg( 'tab', 'bulk', remove_query_arg( 'tab' ) ); ?>" 
                   class="nav-tab <?php echo $active_tab === 'bulk' ? 'nav-tab-active' : ''; ?>">
                    📦 Lista URL
                </a>
                <a href="<?php echo add_query_arg( 'tab', 'channel', remove_query_arg( 'tab' ) ); ?>" 
                   class="nav-tab <?php echo $active_tab === 'channel' ? 'nav-tab-active' : ''; ?>">
                    📺 Da Canale
                </a>
                <a href="<?php echo add_query_arg( 'tab', 'rss', remove_query_arg( 'tab' ) ); ?>" 
                   class="nav-tab <?php echo $active_tab === 'rss' ? 'nav-tab-active' : ''; ?>">
                    📡 Feed RSS (Auto)
                </a>
            </nav>

            <div class="tab-content" style="background: #fff; padding: 25px; border: 1px solid #ccd0d4; border-top: none;">
                <?php
                switch ( $active_tab ) {
                    case 'bulk':
                        self::render_tab_bulk();
                        break;
                    case 'channel':
                        self::render_tab_channel();
                        break;
                    case 'rss':
                        self::render_tab_rss();
                        break;
                    default:
                        self::render_tab_single();
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Tab: Singolo Video
     */
    private static function render_tab_single() {
        // Handle form submission
        if ( isset( $_POST['ipv_import_single'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'ipv_import_single' ) ) {
            $url = esc_url_raw( $_POST['youtube_url'] ?? '' );
            if ( ! empty( $url ) && class_exists( 'IPV_Prod_Helpers' ) ) {
                $video_id = IPV_Prod_Helpers::extract_youtube_id( $url );
                if ( $video_id ) {
                    // Verifica duplicati
                    if ( class_exists( 'IPV_Prod_Helpers' ) && IPV_Prod_Helpers::video_exists( $video_id ) ) {
                        echo '<div class="notice notice-warning is-dismissible"><p>⚠️ <strong>Video già importato:</strong> Questo video è già presente nel sistema.</p></div>';
                    } else {
                        // Aggiungi alla coda
                        if ( class_exists( 'IPV_Prod_Queue' ) ) {
                            IPV_Prod_Queue::enqueue( $video_id, $url, 'manual' );
                            echo '<div class="notice notice-success is-dismissible" style="border-left-color:#22c55e;">
                                    <p style="font-size:14px;margin:8px 0;">
                                        <strong>✅ Video aggiunto con successo!</strong><br>
                                        <span style="color:#666;">Video ID: <code>' . esc_html( $video_id ) . '</code></span><br>
                                        <a href="' . admin_url( 'admin.php?page=ipv-queue' ) . '" class="button button-primary" style="margin-top:8px;">📋 Visualizza nella Coda →</a>
                                    </p>
                                  </div>';
                        }
                    }
                } else {
                    echo '<div class="notice notice-error is-dismissible"><p>❌ <strong>Errore:</strong> URL non valido. Inserisci un URL YouTube corretto.</p></div>';
                }
            }
        }
        ?>
        <h2 style="margin-top: 0;">📹 Importa un Singolo Video</h2>
        <p>Inserisci l'URL di un video YouTube per importarlo.</p>

        <form method="post" style="max-width: 600px;">
            <?php wp_nonce_field( 'ipv_import_single' ); ?>
            
            <table class="form-table">
                <tr>
                    <th><label for="youtube_url">URL YouTube</label></th>
                    <td>
                        <input type="url" name="youtube_url" id="youtube_url" 
                               class="large-text" 
                               placeholder="https://www.youtube.com/watch?v=..." 
                               required>
                        <p class="description">
                            Formati accettati: youtube.com/watch?v=, youtu.be/, youtube.com/shorts/
                        </p>
                    </td>
                </tr>
            </table>

            <p>
                <button type="submit" name="ipv_import_single" class="button button-primary button-hero">
                    📥 Importa Video
                </button>
            </p>
        </form>

        <div style="background: #f0f6fc; padding: 15px; border-radius: 6px; margin-top: 30px;">
            <strong>💡 Cosa succede dopo?</strong>
            <ol style="margin: 10px 0 0 20px;">
                <li>Il video viene aggiunto alla <strong>Coda</strong></li>
                <li>Vengono scaricati titolo, descrizione e thumbnail</li>
                <li>Viene generata la <strong>trascrizione</strong> automatica</li>
                <li>L'AI crea una <strong>descrizione ottimizzata</strong></li>
            </ol>
        </div>
        <?php
    }

    /**
     * Tab: Import Multiplo
     */
    private static function render_tab_bulk() {
        // Handle form submission
        if ( isset( $_POST['ipv_import_bulk'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'ipv_import_bulk' ) ) {
            $urls = sanitize_textarea_field( $_POST['youtube_urls'] ?? '' );
            $lines = array_filter( array_map( 'trim', explode( "\n", $urls ) ) );
            
            $added = 0;
            $skipped = 0;
            
            foreach ( $lines as $url ) {
                if ( class_exists( 'IPV_Prod_Helpers' ) ) {
                    $video_id = IPV_Prod_Helpers::extract_youtube_id( $url );
                    if ( $video_id ) {
                        if ( class_exists( 'IPV_Prod_Helpers' ) && IPV_Prod_Helpers::video_exists( $video_id ) ) {
                            $skipped++;
                        } else {
                            IPV_Prod_Queue::enqueue( $video_id, $url, 'bulk' );
                            $added++;
                        }
                    }
                }
            }
            
            if ( $added > 0 ) {
                echo '<div class="notice notice-success is-dismissible" style="border-left-color:#22c55e;">
                        <p style="font-size:14px;margin:8px 0;">
                            <strong>✅ Import completato con successo!</strong><br>
                            <span style="color:#666;">Video aggiunti: <strong>' . $added . '</strong>';
                if ( $skipped > 0 ) echo ' | Saltati (già esistenti): <strong>' . $skipped . '</strong>';
                echo '</span><br>
                            <a href="' . admin_url( 'admin.php?page=ipv-queue' ) . '" class="button button-primary" style="margin-top:8px;">📋 Visualizza nella Coda →</a>
                        </p>
                      </div>';
            } elseif ( $skipped > 0 ) {
                echo '<div class="notice notice-warning is-dismissible"><p>⚠️ <strong>Nessun video aggiunto:</strong> Tutti i ' . $skipped . ' video erano già stati importati.</p></div>';
            }
        }
        ?>
        <h2 style="margin-top: 0;">📦 Import Multiplo (Lista URL)</h2>
        <p>Inserisci più URL di YouTube, uno per riga.</p>

        <form method="post" style="max-width: 700px;">
            <?php wp_nonce_field( 'ipv_import_bulk' ); ?>
            
            <p>
                <textarea name="youtube_urls" rows="12" class="large-text code"
                          placeholder="https://www.youtube.com/watch?v=abc123
https://www.youtube.com/watch?v=def456
https://youtu.be/ghi789"></textarea>
            </p>
            <p class="description">Un URL per riga. I video duplicati verranno automaticamente saltati.</p>

            <p>
                <button type="submit" name="ipv_import_bulk" class="button button-primary button-hero">
                    📦 Importa Tutti
                </button>
            </p>
        </form>
        <?php
    }

    /**
     * Tab: Import da Canale
     */
    private static function render_tab_channel() {
        // Handle form submission
        if ( isset( $_POST['ipv_import_channel'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'ipv_import_channel' ) ) {
            $channel_id = sanitize_text_field( $_POST['channel_id'] ?? '' );
            $max_videos = absint( $_POST['max_videos'] ?? 10 );
            
            if ( ! empty( $channel_id ) && class_exists( 'IPV_Prod_YouTube_API' ) ) {
                $videos = IPV_Prod_YouTube_API::get_channel_videos( $channel_id, $max_videos );
                
                if ( is_wp_error( $videos ) ) {
                    echo '<div class="notice notice-error"><p>❌ Errore: ' . esc_html( $videos->get_error_message() ) . '</p></div>';
                } elseif ( ! empty( $videos ) ) {
                    $added = 0;
                    foreach ( $videos as $video ) {
                        $vid = $video['video_id'] ?? '';
                        if ( $vid && ! IPV_Prod_Helpers::video_exists( $vid ) ) {
                            IPV_Prod_Queue::enqueue( $vid, 'https://youtube.com/watch?v=' . $vid, 'channel' );
                            $added++;
                        }
                    }
                    echo '<div class="notice notice-success"><p>✅ ' . $added . ' video dal canale aggiunti alla coda!</p></div>';
                } else {
                    echo '<div class="notice notice-warning"><p>⚠️ Nessun video trovato nel canale.</p></div>';
                }
            }
        }
        ?>
        <h2 style="margin-top: 0;">📺 Import da Canale YouTube</h2>
        <p>Importa gli ultimi video pubblicati da un canale.</p>

        <form method="post" style="max-width: 600px;">
            <?php wp_nonce_field( 'ipv_import_channel' ); ?>
            
            <table class="form-table">
                <tr>
                    <th><label for="channel_id">ID Canale</label></th>
                    <td>
                        <input type="text" name="channel_id" id="channel_id" 
                               class="regular-text" 
                               placeholder="UC..." required>
                        <p class="description">
                            Lo trovi nell'URL del canale: youtube.com/channel/<strong>UC...</strong><br>
                            Oppure cerca "@nomecanale" su YouTube e copia l'ID dalla URL.
                        </p>
                    </td>
                </tr>
                <tr>
                    <th><label for="max_videos">Numero video</label></th>
                    <td>
                        <input type="number" name="max_videos" id="max_videos" 
                               value="10" min="1" max="50" style="width: 80px;">
                        <p class="description">Quanti degli ultimi video vuoi importare (max 50)</p>
                    </td>
                </tr>
            </table>

            <p>
                <button type="submit" name="ipv_import_channel" class="button button-primary button-hero">
                    📺 Importa dal Canale
                </button>
            </p>
        </form>
        <?php
    }

    /**
     * Tab: RSS Feed
     */
    private static function render_tab_rss() {
        // Handler salvataggio
        if ( isset( $_POST['ipv_save_rss'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'ipv_rss_settings' ) ) {
            update_option( 'ipv_rss_enabled', isset( $_POST['ipv_rss_enabled'] ) ? '1' : '0' );
            update_option( 'ipv_rss_channel_id', sanitize_text_field( $_POST['ipv_rss_channel_id'] ?? '' ) );
            update_option( 'ipv_rss_frequency', sanitize_text_field( $_POST['ipv_rss_frequency'] ?? 'hourly' ) );
            echo '<div class="notice notice-success"><p>✅ Impostazioni RSS salvate!</p></div>';
        }

        $rss_enabled = get_option( 'ipv_rss_enabled', '0' );
        $rss_channel = get_option( 'ipv_rss_channel_id', '' );
        $rss_frequency = get_option( 'ipv_rss_frequency', 'hourly' );
        ?>
        <h2 style="margin-top: 0;">📡 Import Automatico via RSS</h2>
        <p>Configura l'importazione automatica dei nuovi video dal tuo canale YouTube.</p>

        <div style="background: #fff8e1; border-left: 4px solid #ffc107; padding: 15px; margin-bottom: 20px;">
            <strong>🤖 Come funziona:</strong><br>
            Una volta configurato, il sistema controllerà periodicamente il feed RSS del canale 
            e importerà automaticamente i nuovi video pubblicati.
        </div>

        <form method="post" style="max-width: 600px;">
            <?php wp_nonce_field( 'ipv_rss_settings' ); ?>
            
            <table class="form-table">
                <tr>
                    <th>Stato</th>
                    <td>
                        <label>
                            <input type="checkbox" name="ipv_rss_enabled" value="1" 
                                   <?php checked( $rss_enabled, '1' ); ?>>
                            <strong>Abilita import automatico RSS</strong>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th><label for="ipv_rss_channel_id">ID Canale</label></th>
                    <td>
                        <input type="text" name="ipv_rss_channel_id" id="ipv_rss_channel_id" 
                               class="regular-text" 
                               value="<?php echo esc_attr( $rss_channel ); ?>"
                               placeholder="UC...">
                    </td>
                </tr>
                <tr>
                    <th><label for="ipv_rss_frequency">Frequenza controllo</label></th>
                    <td>
                        <select name="ipv_rss_frequency" id="ipv_rss_frequency">
                            <option value="hourly" <?php selected( $rss_frequency, 'hourly' ); ?>>Ogni ora</option>
                            <option value="twicedaily" <?php selected( $rss_frequency, 'twicedaily' ); ?>>Due volte al giorno</option>
                            <option value="daily" <?php selected( $rss_frequency, 'daily' ); ?>>Una volta al giorno</option>
                        </select>
                    </td>
                </tr>
            </table>

            <p>
                <button type="submit" name="ipv_save_rss" class="button button-primary">
                    💾 Salva Configurazione RSS
                </button>
            </p>
        </form>

        <?php if ( $rss_enabled === '1' && ! empty( $rss_channel ) ) : ?>
            <div style="background: #e8f5e9; padding: 15px; border-radius: 6px; margin-top: 20px;">
                <strong>✅ Import automatico ATTIVO</strong><br>
                Canale: <code><?php echo esc_html( $rss_channel ); ?></code><br>
                Feed URL: <code>https://www.youtube.com/feeds/videos.xml?channel_id=<?php echo esc_html( $rss_channel ); ?></code>
            </div>
        <?php endif; ?>
        <?php
    }

    // ══════════════════════════════════════════════════════════════
    // RENDER: QUEUE PAGE
    // ══════════════════════════════════════════════════════════════

    public static function render_queue_page() {
        if ( class_exists( 'IPV_Queue_Dashboard' ) && method_exists( 'IPV_Queue_Dashboard', 'render' ) ) {
            IPV_Queue_Dashboard::render();
        } else {
            echo '<div class="wrap"><h1>⏳ Coda</h1><p>Componente coda non disponibile.</p></div>';
        }
    }

    // ══════════════════════════════════════════════════════════════
    // RENDER: SETTINGS PAGE
    // ══════════════════════════════════════════════════════════════

    public static function render_settings_page() {
        $active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'general';
        ?>
        <div class="wrap">
            <h1>⚙️ <?php esc_html_e( 'Impostazioni', 'ipv-production-system-pro' ); ?></h1>

            <nav class="nav-tab-wrapper" style="margin-bottom: 20px;">
                <a href="<?php echo add_query_arg( 'tab', 'general', remove_query_arg( 'tab' ) ); ?>" 
                   class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
                    ⚙️ Generali
                </a>
                <a href="<?php echo add_query_arg( 'tab', 'automation', remove_query_arg( 'tab' ) ); ?>" 
                   class="nav-tab <?php echo $active_tab === 'automation' ? 'nav-tab-active' : ''; ?>">
                    🤖 Automazione
                </a>
                <a href="<?php echo add_query_arg( 'tab', 'ai', remove_query_arg( 'tab' ) ); ?>" 
                   class="nav-tab <?php echo $active_tab === 'ai' ? 'nav-tab-active' : ''; ?>">
                    ✨ AI & Prompt
                </a>
                <a href="<?php echo add_query_arg( 'tab', 'license', remove_query_arg( 'tab' ) ); ?>" 
                   class="nav-tab <?php echo $active_tab === 'license' ? 'nav-tab-active' : ''; ?>">
                    🔑 Licenza
                </a>
            </nav>

            <div class="tab-content" style="background: #fff; padding: 25px; border: 1px solid #ccd0d4; border-top: none;">
                <?php
                switch ( $active_tab ) {
                    case 'automation':
                        self::render_settings_automation();
                        break;
                    case 'ai':
                        self::render_settings_ai();
                        break;
                    case 'license':
                        self::render_settings_license();
                        break;
                    default:
                        self::render_settings_general();
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }

    private static function render_settings_general() {
        if ( isset( $_POST['ipv_save_general'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'ipv_settings_general' ) ) {
            update_option( 'ipv_min_duration_minutes', absint( $_POST['ipv_min_duration_minutes'] ?? 0 ) );
            update_option( 'ipv_exclude_shorts', isset( $_POST['ipv_exclude_shorts'] ) ? '1' : '0' );
            update_option( 'ipv_youtube_api_key', sanitize_text_field( $_POST['ipv_youtube_api_key'] ?? '' ) );
            echo '<div class="notice notice-success"><p>✅ Salvato!</p></div>';
        }

        $min_duration = get_option( 'ipv_min_duration_minutes', 0 );
        $exclude_shorts = get_option( 'ipv_exclude_shorts', '1' );
        $api_key = get_option( 'ipv_youtube_api_key', '' );
        ?>
        <h2 style="margin-top: 0;">⚙️ Impostazioni Generali</h2>

        <form method="post">
            <?php wp_nonce_field( 'ipv_settings_general' ); ?>
            
            <table class="form-table">
                <tr>
                    <th><label for="ipv_min_duration_minutes">Durata minima</label></th>
                    <td>
                        <input type="number" name="ipv_min_duration_minutes" id="ipv_min_duration_minutes" 
                               value="<?php echo esc_attr( $min_duration ); ?>" min="0" max="60" style="width: 80px;"> minuti
                        <p class="description">
                            Video più corti verranno saltati (utile per escludere shorts).<br>
                            Imposta <strong>0</strong> per importare tutti i video.
                        </p>
                    </td>
                </tr>
                <tr>
                    <th>Shorts</th>
                    <td>
                        <label>
                            <input type="checkbox" name="ipv_exclude_shorts" value="1" <?php checked( $exclude_shorts, '1' ); ?>>
                            Non importare YouTube Shorts
                        </label>
                    </td>
                </tr>
                <tr>
                    <th><label for="ipv_youtube_api_key">YouTube API Key</label></th>
                    <td>
                        <input type="text" name="ipv_youtube_api_key" id="ipv_youtube_api_key" 
                               value="<?php echo esc_attr( $api_key ); ?>" class="regular-text" placeholder="AIza...">
                        <p class="description">
                            Opzionale. Usata come fallback se il server cloud non risponde.
                        </p>
                    </td>
                </tr>
            </table>

            <p><button type="submit" name="ipv_save_general" class="button button-primary">💾 Salva</button></p>
        </form>
        <?php
    }

    private static function render_settings_automation() {
        if ( isset( $_POST['ipv_save_automation'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'ipv_settings_automation' ) ) {
            update_option( 'ipv_auto_transcript', isset( $_POST['ipv_auto_transcript'] ) ? '1' : '0' );
            update_option( 'ipv_auto_ai_description', isset( $_POST['ipv_auto_ai_description'] ) ? '1' : '0' );
            update_option( 'ipv_extract_tags', isset( $_POST['ipv_extract_tags'] ) ? '1' : '0' );
            update_option( 'ipv_extract_categories', isset( $_POST['ipv_extract_categories'] ) ? '1' : '0' );
            update_option( 'ipv_extract_speakers', isset( $_POST['ipv_extract_speakers'] ) ? '1' : '0' );
            echo '<div class="notice notice-success"><p>✅ Salvato!</p></div>';
        }

        $auto_transcript = get_option( 'ipv_auto_transcript', '1' );
        $auto_ai = get_option( 'ipv_auto_ai_description', '1' );
        $extract_tags = get_option( 'ipv_extract_tags', '1' );
        $extract_categories = get_option( 'ipv_extract_categories', '1' );
        $extract_speakers = get_option( 'ipv_extract_speakers', '0' );
        ?>
        <h2 style="margin-top: 0;">🤖 Automazione</h2>
        <p>Configura cosa deve succedere automaticamente dopo l'importazione di un video.</p>

        <form method="post">
            <?php wp_nonce_field( 'ipv_settings_automation' ); ?>
            
            <h3>📋 Pipeline Automatica</h3>
            <table class="form-table">
                <tr>
                    <th>Dopo l'import</th>
                    <td>
                        <p><label>
                            <input type="checkbox" name="ipv_auto_transcript" value="1" <?php checked( $auto_transcript, '1' ); ?>>
                            📝 Genera <strong>trascrizione</strong> automatica
                        </label></p>
                        <p><label>
                            <input type="checkbox" name="ipv_auto_ai_description" value="1" <?php checked( $auto_ai, '1' ); ?>>
                            ✨ Genera <strong>descrizione AI</strong> automatica
                        </label></p>
                    </td>
                </tr>
            </table>

            <h3>🏷️ Estrazione Metadata</h3>
            <p class="description">Cosa estrarre dalla descrizione AI per organizzare i contenuti:</p>
            <table class="form-table">
                <tr>
                    <th>Estrai automaticamente</th>
                    <td>
                        <p><label>
                            <input type="checkbox" name="ipv_extract_tags" value="1" <?php checked( $extract_tags, '1' ); ?>>
                            🏷️ <strong>Tag</strong> (dagli hashtag)
                        </label></p>
                        <p><label>
                            <input type="checkbox" name="ipv_extract_categories" value="1" <?php checked( $extract_categories, '1' ); ?>>
                            📁 <strong>Categorie</strong> (dagli argomenti trattati)
                        </label></p>
                        <p><label>
                            <input type="checkbox" name="ipv_extract_speakers" value="1" <?php checked( $extract_speakers, '1' ); ?>>
                            👤 <strong>Relatori</strong> (solo se menzionati nel titolo)
                        </label>
                        <span class="description" style="display: block; margin-left: 25px;">
                            ⚠️ Disattiva se importi video di altri canali senza ospiti
                        </span></p>
                    </td>
                </tr>
            </table>

            <p><button type="submit" name="ipv_save_automation" class="button button-primary">💾 Salva</button></p>
        </form>
        <?php
    }

    private static function render_settings_ai() {
        if ( isset( $_POST['ipv_save_ai'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'ipv_settings_ai' ) ) {
            update_option( 'ipv_golden_prompt', wp_kses_post( $_POST['ipv_golden_prompt'] ?? '' ) );
            echo '<div class="notice notice-success"><p>✅ Golden Prompt salvato!</p></div>';
        }

        $golden_prompt = get_option( 'ipv_golden_prompt', '' );
        ?>
        <h2 style="margin-top: 0;">✨ AI & Golden Prompt</h2>
        <p>Il "Golden Prompt" sono le istruzioni che l'AI segue per generare le descrizioni dei tuoi video.</p>

        <form method="post">
            <?php wp_nonce_field( 'ipv_settings_ai' ); ?>
            
            <?php if ( empty( $golden_prompt ) ) : ?>
                <div class="notice notice-warning" style="margin: 0 0 20px;">
                    <p>⚠️ <strong>Nessun Golden Prompt configurato.</strong> L'AI userà istruzioni generiche.</p>
                </div>
            <?php else : ?>
                <div class="notice notice-success" style="margin: 0 0 20px;">
                    <p>✅ Golden Prompt configurato (<?php echo strlen( $golden_prompt ); ?> caratteri)</p>
                </div>
            <?php endif; ?>

            <table class="form-table">
                <tr>
                    <td style="padding-left: 0;">
                        <label for="ipv_golden_prompt"><strong>Il tuo Golden Prompt:</strong></label>
                        <textarea name="ipv_golden_prompt" id="ipv_golden_prompt" 
                                  rows="20" class="large-text code"
                                  placeholder="Scrivi qui le istruzioni per l'AI...

Esempio:
Sei un assistente che crea descrizioni per video YouTube.
Per ogni video, genera:
1. Un riassunto coinvolgente (2-3 frasi)
2. I punti chiave discussi
3. 5-10 hashtag pertinenti

Usa un tono professionale ma accessibile.
Scrivi in italiano."><?php echo esc_textarea( $golden_prompt ); ?></textarea>
                    </td>
                </tr>
            </table>

            <p><button type="submit" name="ipv_save_ai" class="button button-primary">💾 Salva Golden Prompt</button></p>
        </form>

        <div style="background: #f0f6fc; padding: 15px; border-radius: 6px; margin-top: 20px;">
            <strong>💡 Suggerimenti per un buon Golden Prompt:</strong>
            <ul style="margin: 10px 0 0 20px;">
                <li>Specifica il <strong>tono</strong> desiderato (formale, amichevole, tecnico)</li>
                <li>Indica la <strong>lingua</strong> di output</li>
                <li>Elenca le <strong>sezioni</strong> da includere (riassunto, punti chiave, hashtag)</li>
                <li>Definisci eventuali <strong>emoji</strong> da usare</li>
                <li>Specifica la <strong>lunghezza</strong> desiderata</li>
            </ul>
        </div>
        <?php
    }

    private static function render_settings_license() {
        if ( class_exists( 'IPV_Prod_License_Manager_Client' ) && method_exists( 'IPV_Prod_License_Manager_Client', 'render_license_page' ) ) {
            IPV_Prod_License_Manager_Client::render_license_page();
        } else {
            echo '<p>Gestore licenza non disponibile.</p>';
        }
    }

    // ══════════════════════════════════════════════════════════════
    // RENDER: TOOLS PAGE
    // ══════════════════════════════════════════════════════════════

    public static function render_tools_page() {
        $active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'diagnostics';
        ?>
        <div class="wrap">
            <h1>🔧 <?php esc_html_e( 'Strumenti', 'ipv-production-system-pro' ); ?></h1>

            <nav class="nav-tab-wrapper" style="margin-bottom: 20px;">
                <a href="<?php echo add_query_arg( 'tab', 'diagnostics', remove_query_arg( 'tab' ) ); ?>" 
                   class="nav-tab <?php echo $active_tab === 'diagnostics' ? 'nav-tab-active' : ''; ?>">
                    🔍 Diagnostica
                </a>
                <a href="<?php echo add_query_arg( 'tab', 'bulk', remove_query_arg( 'tab' ) ); ?>" 
                   class="nav-tab <?php echo $active_tab === 'bulk' ? 'nav-tab-active' : ''; ?>">
                    ⚡ Operazioni Bulk
                </a>
                <a href="<?php echo add_query_arg( 'tab', 'cleanup', remove_query_arg( 'tab' ) ); ?>" 
                   class="nav-tab <?php echo $active_tab === 'cleanup' ? 'nav-tab-active' : ''; ?>">
                    🧹 Pulizia
                </a>
            </nav>

            <div class="tab-content" style="background: #fff; padding: 25px; border: 1px solid #ccd0d4; border-top: none;">
                <?php
                switch ( $active_tab ) {
                    case 'bulk':
                        self::render_tools_bulk();
                        break;
                    case 'cleanup':
                        self::render_tools_cleanup();
                        break;
                    default:
                        self::render_tools_diagnostics();
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }

    private static function render_tools_diagnostics() {
        if ( class_exists( 'IPV_Prod_Diagnostics' ) && method_exists( IPV_Prod_Diagnostics::instance(), 'render_page' ) ) {
            IPV_Prod_Diagnostics::instance()->render_page();
        } else {
            echo '<p>Diagnostica non disponibile.</p>';
        }
    }

    private static function render_tools_bulk() {
        global $wpdb;
        
        $total = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='ipv_video' AND post_status='publish'" );
        $no_transcript = $wpdb->get_var( "
            SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_ipv_transcript'
            WHERE p.post_type = 'ipv_video' AND p.post_status = 'publish'
            AND (pm.meta_value IS NULL OR pm.meta_value = '')
        " );
        $no_ai = $wpdb->get_var( "
            SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_ipv_ai_description'
            WHERE p.post_type = 'ipv_video' AND p.post_status = 'publish'
            AND (pm.meta_value IS NULL OR pm.meta_value = '')
        " );
        ?>
        <h2 style="margin-top: 0;">⚡ Operazioni Bulk</h2>

        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 30px;">
            <div style="background: #f0f6fc; padding: 20px; border-radius: 8px; text-align: center;">
                <div style="font-size: 32px; font-weight: bold; color: #1e40af;"><?php echo $total; ?></div>
                <div>Video totali</div>
            </div>
            <div style="background: #fef3c7; padding: 20px; border-radius: 8px; text-align: center;">
                <div style="font-size: 32px; font-weight: bold; color: #92400e;"><?php echo $no_transcript; ?></div>
                <div>Senza trascrizione</div>
            </div>
            <div style="background: #fee2e2; padding: 20px; border-radius: 8px; text-align: center;">
                <div style="font-size: 32px; font-weight: bold; color: #991b1b;"><?php echo $no_ai; ?></div>
                <div>Senza descrizione AI</div>
            </div>
        </div>

        <p>
            Per eseguire operazioni bulk, vai in <strong>Tutti i Video</strong>, 
            seleziona i video e usa il menu <strong>Azioni di gruppo</strong>:
        </p>

        <ul style="list-style: disc; margin-left: 20px; line-height: 2;">
            <li><strong>🔄 Refresh Dati YouTube</strong> – Aggiorna titolo, thumbnail, visualizzazioni</li>
            <li><strong>📝 Rigenera Trascrizioni</strong> – Scarica nuove trascrizioni</li>
            <li><strong>✨ Rigenera Descrizioni AI</strong> – Crea nuove descrizioni con l'AI</li>
            <li><strong>🚀 Pipeline Completa</strong> – Trascrizione + AI in sequenza</li>
        </ul>

        <p style="margin-top: 20px;">
            <a href="<?php echo admin_url( 'edit.php?post_type=ipv_video' ); ?>" class="button button-primary button-hero">
                📋 Vai a Tutti i Video
            </a>
        </p>
        <?php
    }

    private static function render_tools_cleanup() {
        if ( isset( $_POST['ipv_cleanup'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'ipv_cleanup' ) ) {
            global $wpdb;
            $action = sanitize_text_field( $_POST['cleanup_action'] ?? '' );
            $message = '';

            switch ( $action ) {
                case 'clear_done':
                    $table = $wpdb->prefix . 'ipv_prod_queue';
                    $deleted = $wpdb->delete( $table, [ 'status' => 'done' ], [ '%s' ] );
                    $message = "✅ Eliminati {$deleted} job completati.";
                    break;
                case 'clear_cache':
                    $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_ipv_yt_%'" );
                    $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_ipv_yt_%'" );
                    $message = "✅ Cache pulita.";
                    break;
            }

            if ( $message ) {
                echo '<div class="notice notice-success"><p>' . esc_html( $message ) . '</p></div>';
            }
        }
        ?>
        <h2 style="margin-top: 0;">🧹 Pulizia</h2>

        <form method="post">
            <?php wp_nonce_field( 'ipv_cleanup' ); ?>
            
            <table class="form-table">
                <tr>
                    <th>Coda</th>
                    <td>
                        <button type="submit" name="ipv_cleanup" value="1" class="button">
                            🗑️ Pulisci job completati
                        </button>
                        <input type="hidden" name="cleanup_action" value="clear_done">
                        <p class="description">Rimuove i job già elaborati dalla coda.</p>
                    </td>
                </tr>
            </table>
        </form>

        <form method="post">
            <?php wp_nonce_field( 'ipv_cleanup' ); ?>
            <table class="form-table">
                <tr>
                    <th>Cache</th>
                    <td>
                        <button type="submit" name="ipv_cleanup" value="1" class="button">
                            🔄 Pulisci cache YouTube
                        </button>
                        <input type="hidden" name="cleanup_action" value="clear_cache">
                        <p class="description">Forza il refresh dei dati YouTube al prossimo caricamento.</p>
                    </td>
                </tr>
            </table>
        </form>
        <?php
    }

}
