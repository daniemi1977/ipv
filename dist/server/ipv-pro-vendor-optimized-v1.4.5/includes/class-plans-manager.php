<?php
/**
 * IPV Plans Manager
 *
 * Gestisce i piani SaaS configurabili dall'admin
 *
 * @version 1.3.17
 *
 * CHANGELOG v1.3.17 (2025-12-11):
 * - FIX CRITICO: Descrizioni prodotti WooCommerce corrotte risolto
 * - Aggiunto metodo emoji_to_html_entities() per conversione sicura emoji
 * - Aggiornato generate_product_description() con descrizioni più ricche
 * - Emoji convertite in HTML entities (compatibile UTF8 standard)
 * - Descrizioni strutturate in 4 sezioni (Header, Cosa Include, Funzionalità, Supporto)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Vendor_Plans_Manager {

    private static $instance = null;
    
    /** @var string Option name for storing plans */
    const OPTION_NAME = 'ipv_saas_plans';

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Hooks
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ], 15 );
        add_filter( 'ipv_vendor_plan_options', [ $this, 'get_plan_options' ] );
    }

    /**
     * Add submenu page
     */
    public function add_admin_menu() {
        add_submenu_page(
            'ipv-vendor-dashboard',
            'Piani SaaS',
            '💰 Piani SaaS',
            'manage_options',
            'ipv-vendor-plans',
            [ $this, 'render_plans_page' ]
        );
    }

    /**
     * Get all plans
     */
    public function get_plans() {
        $plans = get_option( self::OPTION_NAME, [] );
        
        // Return defaults if empty
        if ( empty( $plans ) ) {
            return $this->get_default_plans();
        }
        
        return $plans;
    }

    /**
     * Get default plans
     * v1.4.2-FIXED10: Trial con crediti una tantum (non rinnovabili)
     */
    public function get_default_plans() {
        return [
            'trial' => [
                'name' => 'Trial',
                'slug' => 'trial',
                'credits' => 10,
                'credits_period' => 'once', // Una tantum - NON si rinnovano!
                'activations' => 1,
                'price' => 0,
                'price_period' => 'once',
                'features' => [
                    'transcription' => true,
                    'ai_description' => true,
                    'priority_support' => false,
                    'api_access' => false,
                ],
                'description' => '10 crediti di benvenuto gratuiti. Non scade mai.',
                'is_active' => true,
                'sort_order' => 1,
            ],
            'starter' => [
                'name' => 'Starter',
                'slug' => 'starter',
                'credits' => 25,
                'credits_period' => 'month',
                'activations' => 1,
                'price' => 9.99,
                'price_period' => 'month',
                'features' => [
                    'transcription' => true,
                    'ai_description' => true,
                    'priority_support' => false,
                    'api_access' => false,
                ],
                'description' => 'Perfetto per canali in crescita',
                'is_active' => true,
                'sort_order' => 2,
            ],
            'professional' => [
                'name' => 'Professional',
                'slug' => 'professional',
                'credits' => 100,
                'credits_period' => 'month',
                'activations' => 3,
                'price' => 29.99,
                'price_period' => 'month',
                'features' => [
                    'transcription' => true,
                    'ai_description' => true,
                    'priority_support' => true,
                    'api_access' => false,
                ],
                'description' => 'Per creator professionisti',
                'is_active' => true,
                'sort_order' => 3,
            ],
            'business' => [
                'name' => 'Business',
                'slug' => 'business',
                'credits' => 500,
                'credits_period' => 'month',
                'activations' => 10,
                'price' => 79.99,
                'price_period' => 'month',
                'features' => [
                    'transcription' => true,
                    'ai_description' => true,
                    'priority_support' => true,
                    'api_access' => true,
                ],
                'description' => 'Per agenzie e team',
                'is_active' => true,
                'sort_order' => 4,
            ],
            'executive' => [
                'name' => 'Executive',
                'slug' => 'executive',
                'credits' => 2000,
                'credits_period' => 'month',
                'activations' => 50,
                'price' => 499.00,
                'price_period' => 'month',
                'features' => [
                    'transcription' => true,
                    'ai_description' => true,
                    'priority_support' => true,
                    'api_access' => true,
                ],
                'description' => 'Per grandi aziende e network',
                'is_active' => true,
                'sort_order' => 5,
            ],
            'golden_prompt' => [
                'name' => 'Golden prompt',
                'slug' => 'golden_prompt',
                'credits' => 150,
                'credits_period' => 'month',
                'activations' => 5,
                'price' => 59.00,
                'price_period' => 'month',
                'features' => [
                    'transcription' => true,
                    'ai_description' => true,
                    'priority_support' => true,
                    'api_access' => false,
                ],
                'description' => 'Piano speciale con prompt AI ottimizzati',
                'is_active' => true,
                'sort_order' => 6,
            ],
            'extra_credits_10' => [
                'name' => 'IPV Pro - 10',
                'slug' => 'extra_credits_10',
                'credits' => 10,
                'credits_period' => 'once', // Non scadono mai
                'activations' => 0, // Non richiede attivazione
                'price' => 5.00, // 10 crediti x 0.50€ = 5.00€
                'price_period' => 'once',
                'features' => [
                    'transcription' => true,
                    'ai_description' => true,
                    'priority_support' => false,
                    'api_access' => false,
                ],
                'description' => 'Pacchetto 10 crediti extra (0,50€/credito). Non scadono mai.',
                'is_active' => true,
                'sort_order' => 7,
            ],
            'extra_credits_100' => [
                'name' => 'IPV Pro - 100',
                'slug' => 'extra_credits_100',
                'credits' => 100,
                'credits_period' => 'once', // Non scadono mai
                'activations' => 0, // Non richiede attivazione
                'price' => 49.00, // 100 crediti x 0.49€ = 49.00€
                'price_period' => 'once',
                'features' => [
                    'transcription' => true,
                    'ai_description' => true,
                    'priority_support' => false,
                    'api_access' => false,
                ],
                'description' => 'Pacchetto 100 crediti extra (0,49€/credito). Non scadono mai.',
                'is_active' => true,
                'sort_order' => 8,
            ],
        ];
    }

    /**
     * Save plans
     */
    public function save_plans( $plans ) {
        return update_option( self::OPTION_NAME, $plans );
    }

    /**
     * Get single plan by slug
     */
    public function get_plan( $slug ) {
        $plans = $this->get_plans();
        return $plans[ $slug ] ?? null;
    }

    /**
     * Add or update a plan
     */
    public function save_plan( $slug, $plan_data ) {
        $plans = $this->get_plans();
        $plans[ $slug ] = $plan_data;
        return $this->save_plans( $plans );
    }

    /**
     * Delete a plan
     */
    public function delete_plan( $slug ) {
        $plans = $this->get_plans();
        if ( isset( $plans[ $slug ] ) ) {
            unset( $plans[ $slug ] );
            return $this->save_plans( $plans );
        }
        return false;
    }

    /**
     * Get plan options for select dropdowns
     */
    public function get_plan_options( $options = [] ) {
        $plans = $this->get_plans();
        $result = [];
        
        foreach ( $plans as $slug => $plan ) {
            if ( ! empty( $plan['is_active'] ) ) {
                $credits_label = $plan['credits'] . ' video/' . $this->get_period_label( $plan['credits_period'] );
                $result[ $slug ] = $plan['name'] . ' (' . $credits_label . ')';
            }
        }
        
        return $result;
    }

    /**
     * Get period label
     * v1.4.2-FIXED10: Aggiunto 'once' per crediti una tantum
     */
    private function get_period_label( $period ) {
        $labels = [
            'day' => 'giorno',
            'week' => 'settimana',
            'month' => 'mese',
            'year' => 'anno',
            'once' => 'una tantum',
        ];
        return $labels[ $period ] ?? $period;
    }

    /**
     * Converte emoji Unicode in HTML entities
     *
     * v1.3.17 - FIX: Necessario per compatibilità con database che non supportano UTF8MB4
     * o quando i filtri WordPress corrompono le emoji durante il salvataggio
     *
     * @param string $string Stringa con emoji Unicode
     * @return string Stringa con HTML entities
     */
    private function emoji_to_html_entities( $string ) {
        // Mappa emoji comuni → HTML entities
        $emoji_map = [
            // Video & Media
            '🎬' => '&#127916;', // Film clapper
            '🎥' => '&#127909;', // Movie camera
            '📹' => '&#128249;', // Video camera

            // Business & Charts
            '📊' => '&#128202;', // Bar chart
            '📈' => '&#128200;', // Chart increasing
            '💼' => '&#128188;', // Briefcase

            // Tech & Tools
            '🚀' => '&#128640;', // Rocket
            '⚡' => '&#9889;',   // Lightning
            '⚙️' => '&#9881;',   // Gear
            '🔧' => '&#128295;', // Wrench

            // Writing & Communication
            '📝' => '&#128221;', // Memo
            '✍️' => '&#9997;',   // Writing hand
            '📧' => '&#128231;', // Email
            '💬' => '&#128172;', // Speech balloon

            // AI & Robot
            '🤖' => '&#129302;', // Robot
            '🧠' => '&#129504;', // Brain
            '💡' => '&#128161;', // Light bulb

            // Media & Images
            '🖼️' => '&#128444;', // Framed picture
            '🎨' => '&#127912;', // Artist palette
            '📷' => '&#128247;', // Camera

            // Download & Upload
            '📥' => '&#128229;', // Inbox tray
            '📤' => '&#128228;', // Outbox tray
            '⬇️' => '&#11015;',  // Down arrow

            // Success & Quality
            '✅' => '&#9989;',   // Check mark
            '✨' => '&#10024;',  // Sparkles
            '⭐' => '&#11088;',  // Star
            '🌟' => '&#127775;', // Glowing star
            '💎' => '&#128142;', // Gem

            // Security & Access
            '🔑' => '&#128273;', // Key
            '🔒' => '&#128274;', // Lock
            '🛡️' => '&#128737;', // Shield

            // Web & Global
            '🌐' => '&#127760;', // Globe
            '🌍' => '&#127757;', // Earth Europe

            // Misc
            '👤' => '&#128100;', // Bust in silhouette
            '📋' => '&#128203;', // Clipboard
            '📁' => '&#128193;', // Folder
            '🔌' => '&#128268;', // Electric plug
            '🏷️' => '&#127991;', // Label
        ];

        // Sostituisci tutte le emoji con HTML entities
        $converted = str_replace( array_keys( $emoji_map ), array_values( $emoji_map ), $string );

        // Log per debug (solo in sviluppo)
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            $emoji_found = [];
            foreach ( array_keys( $emoji_map ) as $emoji ) {
                if ( strpos( $string, $emoji ) !== false ) {
                    $emoji_found[] = $emoji;
                }
            }
            if ( ! empty( $emoji_found ) ) {
                error_log( '[IPV Plans] Emoji convertite: ' . implode( ', ', $emoji_found ) );
            }
        }

        return $converted;
    }

    /**
     * Get available features
     */
    public function get_available_features() {
        return [
            'transcription' => [
                'name' => 'Trascrizione Video',
                'icon' => '📝',
                'description' => 'Estrazione automatica del testo dai video',
            ],
            'ai_description' => [
                'name' => 'Descrizione AI',
                'icon' => '🤖',
                'description' => 'Generazione automatica descrizioni con AI',
            ],
            'priority_support' => [
                'name' => 'Supporto Prioritario',
                'icon' => '⚡',
                'description' => 'Risposta entro 24 ore',
            ],
            'api_access' => [
                'name' => 'Accesso API',
                'icon' => '🔌',
                'description' => 'Integrazione diretta via REST API',
            ],
            'white_label' => [
                'name' => 'White Label',
                'icon' => '🏷️',
                'description' => 'Rimuovi branding IPV Pro',
            ],
            'custom_prompt' => [
                'name' => 'Prompt Personalizzato',
                'icon' => '✨',
                'description' => 'Golden Prompt personalizzabile',
            ],
        ];
    }

    /**
     * Render the plans management page
     */
    public function render_plans_page() {
        // Handle form submissions
        $this->handle_form_submission();

        $plans = $this->get_plans();
        $features = $this->get_available_features();

        // Sort plans by sort_order
        uasort( $plans, function( $a, $b ) {
            return ( $a['sort_order'] ?? 99 ) - ( $b['sort_order'] ?? 99 );
        });

        ?>
        <div class="wrap">
            <h1>💰 Gestione Piani SaaS</h1>
            
            <p class="description">Configura i piani di abbonamento per IPV Production System Pro. Questi piani appariranno automaticamente nei prodotti WooCommerce.</p>

            <!-- PLANS OVERVIEW -->
            <div class="ipv-plans-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin: 30px 0;">
                <?php foreach ( $plans as $slug => $plan ) : ?>
                    <div class="ipv-plan-card" style="background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); overflow: hidden; <?php echo empty( $plan['is_active'] ) ? 'opacity: 0.6;' : ''; ?>">
                        <!-- Header -->
                        <div style="background: <?php echo $this->get_plan_color( $slug ); ?>; color: white; padding: 20px; text-align: center;">
                            <h3 style="margin: 0; font-size: 24px;"><?php echo esc_html( $plan['name'] ); ?></h3>
                            <div style="font-size: 36px; font-weight: bold; margin: 10px 0;">
                                <?php if ( $plan['price'] == 0 ) : ?>
                                    GRATIS
                                <?php else : ?>
                                    €<?php echo number_format( $plan['price'], 2, ',', '.' ); ?>
                                    <span style="font-size: 14px; font-weight: normal;">/<?php echo esc_html( $this->get_period_label( $plan['price_period'] ) ); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Body -->
                        <div style="padding: 20px;">
                            <!-- Credits -->
                            <div style="text-align: center; margin-bottom: 15px; padding: 10px; background: #f5f5f5; border-radius: 5px;">
                                <strong style="font-size: 28px; color: #667eea;"><?php echo esc_html( $plan['credits'] ); ?></strong>
                                <div style="color: #666;">video / <?php echo esc_html( $this->get_period_label( $plan['credits_period'] ) ); ?></div>
                            </div>
                            
                            <!-- Activations -->
                            <div style="text-align: center; margin-bottom: 15px; color: #666;">
                                🌐 <?php echo esc_html( $plan['activations'] ); ?> sito/i attivabili
                            </div>
                            
                            <!-- Features -->
                            <ul style="list-style: none; padding: 0; margin: 15px 0;">
                                <?php foreach ( $features as $feature_key => $feature ) : ?>
                                    <?php 
                                    $has_feature = ! empty( $plan['features'][ $feature_key ] );
                                    $icon = $has_feature ? '✅' : '❌';
                                    $style = $has_feature ? '' : 'color: #999; text-decoration: line-through;';
                                    ?>
                                    <li style="padding: 5px 0; <?php echo $style; ?>">
                                        <?php echo $icon; ?> <?php echo esc_html( $feature['name'] ); ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            
                            <!-- Description -->
                            <?php if ( ! empty( $plan['description'] ) ) : ?>
                                <p style="color: #666; font-size: 13px; text-align: center; margin: 15px 0 0;">
                                    <?php echo esc_html( $plan['description'] ); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Footer -->
                        <div style="padding: 15px 20px; border-top: 1px solid #eee; display: flex; gap: 10px;">
                            <a href="<?php echo admin_url( 'admin.php?page=ipv-vendor-plans&action=edit&plan=' . $slug ); ?>" class="button" style="flex: 1; text-align: center;">✏️ Modifica</a>
                            <?php if ( $slug !== 'trial' ) : ?>
                                <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=ipv-vendor-plans&action=delete&plan=' . $slug ), 'delete_plan_' . $slug ); ?>" class="button" style="color: #dc3232;" onclick="return confirm('Eliminare il piano <?php echo esc_attr( $plan['name'] ); ?>?');">🗑️</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- ADD NEW PLAN -->
                <div class="ipv-plan-card" style="background: #f9f9f9; border: 2px dashed #ccc; border-radius: 8px; display: flex; align-items: center; justify-content: center; min-height: 400px;">
                    <a href="<?php echo admin_url( 'admin.php?page=ipv-vendor-plans&action=new' ); ?>" style="text-decoration: none; text-align: center; color: #666;">
                        <div style="font-size: 48px;">➕</div>
                        <div style="font-size: 18px; margin-top: 10px;">Aggiungi Piano</div>
                    </a>
                </div>
            </div>

            <!-- EDIT/NEW FORM -->
            <?php $this->render_plan_form(); ?>

            <!-- RESET TO DEFAULTS -->
            <div class="card" style="margin-top: 30px;">
                <h3>🔄 Reset Piani</h3>
                <p>Ripristina i piani ai valori predefiniti. <strong>Attenzione:</strong> questa azione eliminerà tutte le modifiche.</p>
                <form method="post">
                    <?php wp_nonce_field( 'ipv_reset_plans' ); ?>
                    <button type="submit" name="ipv_reset_plans" class="button" onclick="return confirm('Sei sicuro di voler ripristinare i piani predefiniti?');">🔄 Ripristina Piani Default</button>
                </form>
            </div>

            <!-- PLUGIN DOWNLOAD MANAGEMENT -->
            <div class="card" style="margin-top: 30px;">
                <h3>📦 Plugin Client (Download)</h3>
                <p>Carica il file ZIP del plugin client che i clienti scaricheranno dopo l'acquisto.</p>
                
                <?php $plugin_info = $this->get_plugin_download_info(); ?>
                
                <?php if ( $plugin_info ) : ?>
                    <div style="background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                        <strong>✅ Plugin Caricato:</strong><br>
                        📁 File: <code><?php echo esc_html( $plugin_info['filename'] ); ?></code><br>
                        🏷️ Versione: <strong><?php echo esc_html( $plugin_info['version'] ); ?></strong><br>
                        📊 Dimensione: <?php echo esc_html( $plugin_info['filesize'] ); ?><br>
                        📅 Caricato: <?php echo date_i18n( 'd/m/Y H:i', strtotime( $plugin_info['uploaded'] ) ); ?>
                    </div>
                <?php else : ?>
                    <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                        ⚠️ <strong>Nessun plugin caricato.</strong> Carica il file ZIP del plugin client.
                    </div>
                <?php endif; ?>
                
                <form method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field( 'ipv_upload_plugin' ); ?>
                    <input type="file" name="plugin_zip" accept=".zip" required />
                    <button type="submit" name="ipv_upload_plugin" class="button button-primary" style="margin-left: 10px;">
                        📤 Carica Plugin ZIP
                    </button>
                    <p class="description">Formato: ipv-production-system-pro-v10.0.1-saas.zip (la versione viene estratta dal nome file)</p>
                </form>
            </div>

            <!-- WOOCOMMERCE PRODUCT GENERATION -->
            <div class="card" style="margin-top: 30px;">
                <h3>🛒 Genera Prodotti WooCommerce</h3>
                <p>Genera automaticamente i prodotti WooCommerce basati sui piani configurati sopra.</p>
                
                <?php 
                $ipv_products = $this->get_ipv_products();
                if ( ! empty( $ipv_products ) ) : 
                ?>
                    <h4>Prodotti IPV Esistenti:</h4>
                    <table class="wp-list-table widefat fixed striped" style="margin-bottom: 20px;">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome Prodotto</th>
                                <th>Piano</th>
                                <th>Prezzo</th>
                                <th>Crediti</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $ipv_products as $prod ) :
                                $product = wc_get_product( $prod->ID );
                                // PHP 8.x safe type casting
                                $plan_slug_raw = get_post_meta( $prod->ID, '_ipv_plan_slug', true );
                                $plan_slug = ! empty( $plan_slug_raw ) ? (string) $plan_slug_raw : 'N/A';

                                $credits_raw = get_post_meta( $prod->ID, '_ipv_credits_total', true );
                                $credits = ( $credits_raw !== '' && $credits_raw !== null ) ? (int) $credits_raw : 0;
                            ?>
                                <tr>
                                    <td><?php echo esc_html( $prod->ID ); ?></td>
                                    <td><a href="<?php echo get_edit_post_link( $prod->ID ); ?>"><?php echo esc_html( $product->get_name() ); ?></a></td>
                                    <td><code><?php echo esc_html( $plan_slug ); ?></code></td>
                                    <td>€<?php echo esc_html( $product->get_price() ); ?></td>
                                    <td><?php echo esc_html( $credits ); ?>/mese</td>
                                    <td>
                                        <a href="<?php echo get_permalink( $prod->ID ); ?>" target="_blank" class="button button-small">👁️ Vedi</a>
                                        <a href="<?php echo get_edit_post_link( $prod->ID ); ?>" class="button button-small">✏️ Modifica</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <div style="background: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                        ℹ️ Nessun prodotto IPV trovato. Clicca il pulsante sotto per generarli.
                    </div>
                <?php endif; ?>
                
                <?php if ( ! class_exists( 'WooCommerce' ) ) : ?>
                    <div style="background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px;">
                        ❌ <strong>WooCommerce non attivo!</strong> Installa e attiva WooCommerce per generare i prodotti.
                    </div>
                <?php else : ?>
                    <form method="post">
                        <?php wp_nonce_field( 'ipv_generate_products' ); ?>
                        <button type="submit" name="ipv_generate_products" class="button button-primary button-hero" onclick="return confirm('Generare/aggiornare i prodotti WooCommerce per tutti i piani attivi?');">
                            🛒 Genera/Aggiorna Prodotti WooCommerce
                        </button>
                        <p class="description" style="margin-top: 10px;">
                            Questa azione creerà un prodotto WooCommerce per ogni piano attivo, o aggiornerà quelli esistenti.
                            <?php if ( $plugin_info ) : ?>
                                <br>📦 I prodotti includeranno il download di: <strong><?php echo esc_html( $plugin_info['filename'] ); ?></strong>
                            <?php else : ?>
                                <br>⚠️ <strong>Carica prima il plugin ZIP</strong> per includere il download nei prodotti.
                            <?php endif; ?>
                        </p>
                    </form>
                <?php endif; ?>
            </div>

            <!-- DOWNLOAD URLS INFO -->
            <?php if ( $plugin_info ) : ?>
            <div class="card" style="margin-top: 30px;">
                <h3>🔗 URL Download Plugin</h3>
                <p>Questi URL possono essere usati per permettere ai clienti con licenza valida di scaricare il plugin:</p>
                
                <table class="widefat" style="margin-top: 15px;">
                    <tr>
                        <th style="width: 200px;">Download Diretto (WooCommerce)</th>
                        <td>
                            <code style="word-break: break-all;"><?php 
                                $upload_dir = wp_upload_dir();
                                echo esc_html( $upload_dir['baseurl'] . '/ipv-downloads/' . $plugin_info['filename'] ); 
                            ?></code>
                            <p class="description">URL usato da WooCommerce per i download prodotto (protetto da .htaccess)</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Download con License Check</th>
                        <td>
                            <code style="word-break: break-all;"><?php echo esc_html( home_url( '/?ipv_download=1&license=LICENSE_KEY' ) ); ?></code>
                            <p class="description">URL che verifica la licenza prima del download</p>
                        </td>
                    </tr>
                    <tr>
                        <th>API Endpoint</th>
                        <td>
                            <code style="word-break: break-all;"><?php echo esc_html( rest_url( 'ipv-vendor/v1/download' ) ); ?></code>
                            <p class="description">Endpoint REST API per download programmatico</p>
                        </td>
                    </tr>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <style>
            .ipv-plan-card:hover {
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                transform: translateY(-2px);
                transition: all 0.2s ease;
            }
        </style>
        <?php
    }

    /**
     * Get plan color based on slug
     */
    private function get_plan_color( $slug ) {
        $colors = [
            'trial' => '#6c757d',
            'starter' => '#28a745',
            'professional' => '#667eea',
            'business' => '#fd7e14',
            'enterprise' => '#dc3545',
        ];
        return $colors[ $slug ] ?? '#667eea';
    }

    /**
     * Render plan edit/new form
     */
    private function render_plan_form() {
        $action = $_GET['action'] ?? '';
        if ( ! in_array( $action, [ 'edit', 'new' ] ) ) {
            return;
        }

        $plan_slug = sanitize_text_field( $_GET['plan'] ?? '' );
        $plan = [];
        $is_edit = false;

        if ( $action === 'edit' && ! empty( $plan_slug ) ) {
            $plan = $this->get_plan( $plan_slug );
            if ( ! $plan ) {
                echo '<div class="notice notice-error"><p>Piano non trovato!</p></div>';
                return;
            }
            $is_edit = true;
        }

        $features = $this->get_available_features();

        ?>
        <div class="card" style="max-width: 800px; margin-top: 30px;" id="plan-form">
            <h2><?php echo $is_edit ? '✏️ Modifica Piano: ' . esc_html( $plan['name'] ) : '➕ Nuovo Piano'; ?></h2>
            
            <form method="post">
                <?php wp_nonce_field( 'ipv_save_plan' ); ?>
                <input type="hidden" name="plan_action" value="<?php echo $is_edit ? 'edit' : 'new'; ?>" />
                <?php if ( $is_edit ) : ?>
                    <input type="hidden" name="original_slug" value="<?php echo esc_attr( $plan_slug ); ?>" />
                <?php endif; ?>

                <table class="form-table">
                    <tr>
                        <th><label for="plan_name">Nome Piano *</label></th>
                        <td>
                            <input type="text" name="plan_name" id="plan_name" class="regular-text" required 
                                   value="<?php echo esc_attr( $plan['name'] ?? '' ); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th><label for="plan_slug">Slug *</label></th>
                        <td>
                            <input type="text" name="plan_slug" id="plan_slug" class="regular-text" required 
                                   pattern="[a-z0-9_-]+" 
                                   value="<?php echo esc_attr( $plan['slug'] ?? '' ); ?>"
                                   <?php echo $is_edit ? 'readonly style="background: #f0f0f0;"' : ''; ?> />
                            <p class="description">Identificativo unico (solo lettere minuscole, numeri, - e _)</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="plan_description">Descrizione</label></th>
                        <td>
                            <input type="text" name="plan_description" id="plan_description" class="large-text" 
                                   value="<?php echo esc_attr( $plan['description'] ?? '' ); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th><label>Prezzo</label></th>
                        <td>
                            <input type="number" name="plan_price" step="0.01" min="0" style="width: 100px;"
                                   value="<?php echo esc_attr( $plan['price'] ?? 0 ); ?>" /> €
                            
                            <select name="plan_price_period" style="margin-left: 10px;">
                                <option value="month" <?php selected( $plan['price_period'] ?? '', 'month' ); ?>>/ mese</option>
                                <option value="year" <?php selected( $plan['price_period'] ?? '', 'year' ); ?>>/ anno</option>
                                <option value="once" <?php selected( $plan['price_period'] ?? '', 'once' ); ?>>una tantum</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label>Crediti</label></th>
                        <td>
                            <input type="number" name="plan_credits" min="1" style="width: 100px;"
                                   value="<?php echo esc_attr( $plan['credits'] ?? 10 ); ?>" /> video
                            
                            <select name="plan_credits_period" style="margin-left: 10px;">
                                <option value="month" <?php selected( $plan['credits_period'] ?? '', 'month' ); ?>>/ mese</option>
                                <option value="year" <?php selected( $plan['credits_period'] ?? '', 'year' ); ?>>/ anno</option>
                                <option value="day" <?php selected( $plan['credits_period'] ?? '', 'day' ); ?>>/ giorno</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="plan_activations">Limite Siti</label></th>
                        <td>
                            <input type="number" name="plan_activations" id="plan_activations" min="1" max="999" style="width: 100px;"
                                   value="<?php echo esc_attr( $plan['activations'] ?? 1 ); ?>" />
                            <p class="description">Numero massimo di siti WordPress attivabili</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label>Funzionalità</label></th>
                        <td>
                            <?php foreach ( $features as $feature_key => $feature ) : ?>
                                <label style="display: block; margin-bottom: 8px;">
                                    <input type="checkbox" name="plan_features[<?php echo esc_attr( $feature_key ); ?>]" value="1"
                                           <?php checked( ! empty( $plan['features'][ $feature_key ] ) ); ?> />
                                    <?php echo esc_html( $feature['icon'] . ' ' . $feature['name'] ); ?>
                                    <span style="color: #666; font-size: 12px;">— <?php echo esc_html( $feature['description'] ); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="plan_sort_order">Ordine</label></th>
                        <td>
                            <input type="number" name="plan_sort_order" id="plan_sort_order" min="1" max="99" style="width: 60px;"
                                   value="<?php echo esc_attr( $plan['sort_order'] ?? 10 ); ?>" />
                            <p class="description">Ordine di visualizzazione (1 = primo)</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label>Stato</label></th>
                        <td>
                            <label>
                                <input type="checkbox" name="plan_is_active" value="1"
                                       <?php checked( $plan['is_active'] ?? true ); ?> />
                                Piano attivo (visibile e acquistabile)
                            </label>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" name="ipv_save_plan" class="button button-primary">💾 Salva Piano</button>
                    <a href="<?php echo admin_url( 'admin.php?page=ipv-vendor-plans' ); ?>" class="button">Annulla</a>
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Handle form submissions
     */
    private function handle_form_submission() {
        // Reset plans
        if ( isset( $_POST['ipv_reset_plans'] ) ) {
            check_admin_referer( 'ipv_reset_plans' );
            delete_option( self::OPTION_NAME );
            echo '<div class="notice notice-success"><p>✅ Piani ripristinati ai valori predefiniti!</p></div>';
            return;
        }

        // Delete plan
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['plan'] ) ) {
            $slug = sanitize_text_field( $_GET['plan'] );
            check_admin_referer( 'delete_plan_' . $slug );
            
            if ( $this->delete_plan( $slug ) ) {
                echo '<div class="notice notice-success"><p>✅ Piano eliminato!</p></div>';
            }
            return;
        }

        // Upload plugin ZIP
        if ( isset( $_POST['ipv_upload_plugin'] ) && ! empty( $_FILES['plugin_zip']['tmp_name'] ) ) {
            check_admin_referer( 'ipv_upload_plugin' );
            $result = $this->handle_plugin_upload( $_FILES['plugin_zip'] );
            if ( is_wp_error( $result ) ) {
                echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
            } else {
                echo '<div class="notice notice-success"><p>✅ Plugin caricato con successo!</p></div>';
            }
            return;
        }

        // Generate WooCommerce products
        if ( isset( $_POST['ipv_generate_products'] ) ) {
            check_admin_referer( 'ipv_generate_products' );
            $result = $this->generate_woocommerce_products();
            if ( is_wp_error( $result ) ) {
                echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
            } else {
                echo '<div class="notice notice-success"><p>✅ ' . esc_html( $result ) . '</p></div>';
            }
            return;
        }

        // Save plan
        if ( isset( $_POST['ipv_save_plan'] ) ) {
            check_admin_referer( 'ipv_save_plan' );

            $plan_action = sanitize_text_field( $_POST['plan_action'] ?? 'new' );
            $original_slug = sanitize_text_field( $_POST['original_slug'] ?? '' );
            
            $slug = sanitize_title( $_POST['plan_slug'] ?? '' );
            $name = sanitize_text_field( $_POST['plan_name'] ?? '' );

            if ( empty( $slug ) || empty( $name ) ) {
                echo '<div class="notice notice-error"><p>Nome e slug sono obbligatori!</p></div>';
                return;
            }

            // Check for duplicate slug on new plans
            if ( $plan_action === 'new' ) {
                $existing = $this->get_plan( $slug );
                if ( $existing ) {
                    echo '<div class="notice notice-error"><p>Esiste già un piano con questo slug!</p></div>';
                    return;
                }
            }

            // Build features array
            $features = [];
            $available_features = $this->get_available_features();
            foreach ( array_keys( $available_features ) as $feature_key ) {
                $features[ $feature_key ] = ! empty( $_POST['plan_features'][ $feature_key ] );
            }

            $plan_data = [
                'name' => $name,
                'slug' => $slug,
                'description' => sanitize_text_field( $_POST['plan_description'] ?? '' ),
                'price' => floatval( $_POST['plan_price'] ?? 0 ),
                'price_period' => sanitize_text_field( $_POST['plan_price_period'] ?? 'month' ),
                'credits' => absint( $_POST['plan_credits'] ?? 10 ),
                'credits_period' => sanitize_text_field( $_POST['plan_credits_period'] ?? 'month' ),
                'activations' => absint( $_POST['plan_activations'] ?? 1 ),
                'features' => $features,
                'sort_order' => absint( $_POST['plan_sort_order'] ?? 10 ),
                'is_active' => ! empty( $_POST['plan_is_active'] ),
            ];

            $this->save_plan( $slug, $plan_data );
            
            echo '<div class="notice notice-success"><p>✅ Piano salvato con successo!</p></div>';
            
            // Redirect to remove form params
            echo '<script>window.location.href = "' . admin_url( 'admin.php?page=ipv-vendor-plans' ) . '";</script>';
        }
    }

    // ==========================================
    // PLUGIN DOWNLOAD MANAGEMENT
    // ==========================================

    /**
     * Handle plugin ZIP upload
     */
    private function handle_plugin_upload( $file ) {
        // Validate file
        if ( $file['error'] !== UPLOAD_ERR_OK ) {
            return new WP_Error( 'upload_error', 'Errore upload: ' . $file['error'] );
        }

        // Check extension
        $ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
        if ( $ext !== 'zip' ) {
            return new WP_Error( 'invalid_type', 'Il file deve essere un archivio ZIP' );
        }

        // Create upload directory
        $upload_dir = wp_upload_dir();
        $plugin_dir = $upload_dir['basedir'] . '/ipv-downloads';
        
        if ( ! file_exists( $plugin_dir ) ) {
            wp_mkdir_p( $plugin_dir );
            // Protect directory
            file_put_contents( $plugin_dir . '/.htaccess', 'deny from all' );
            file_put_contents( $plugin_dir . '/index.php', '<?php // Silence is golden' );
        }

        // Extract version from filename (e.g., ipv-production-system-pro-v10.0.1-saas.zip)
        preg_match( '/v?(\d+\.\d+\.\d+)/', $file['name'], $matches );
        $version = $matches[1] ?? date( 'Y.m.d' );

        // Save file
        $filename = 'ipv-production-system-pro-v' . $version . '.zip';
        $filepath = $plugin_dir . '/' . $filename;
        
        if ( ! move_uploaded_file( $file['tmp_name'], $filepath ) ) {
            return new WP_Error( 'move_error', 'Impossibile salvare il file' );
        }

        // Save metadata
        update_option( 'ipv_client_plugin_file', $filename );
        update_option( 'ipv_client_plugin_version', $version );
        update_option( 'ipv_client_plugin_uploaded', current_time( 'mysql' ) );

        return true;
    }

    /**
     * Get plugin download info
     */
    public function get_plugin_download_info() {
        $filename = get_option( 'ipv_client_plugin_file', '' );
        $version = get_option( 'ipv_client_plugin_version', '' );
        $uploaded = get_option( 'ipv_client_plugin_uploaded', '' );

        if ( empty( $filename ) ) {
            return null;
        }

        $upload_dir = wp_upload_dir();
        $filepath = $upload_dir['basedir'] . '/ipv-downloads/' . $filename;
        
        if ( ! file_exists( $filepath ) ) {
            return null;
        }

        return [
            'filename' => $filename,
            'version' => $version,
            'uploaded' => $uploaded,
            'filesize' => size_format( filesize( $filepath ) ),
            'filepath' => $filepath,
        ];
    }

    /**
     * Get secure download URL for a license
     */
    public function get_download_url( $license_key = '' ) {
        $token = wp_create_nonce( 'ipv_download_' . $license_key );
        return add_query_arg([
            'ipv_download' => '1',
            'license' => $license_key,
            'token' => $token,
        ], home_url( '/' ) );
    }

    // ==========================================
    // WOOCOMMERCE PRODUCT GENERATION
    // ==========================================

    /**
     * Generate WooCommerce products from plans
     */
    public function generate_woocommerce_products() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return new WP_Error( 'no_woocommerce', 'WooCommerce non è attivo!' );
        }

        $plans = $this->get_plans();
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ( $plans as $slug => $plan ) {
            // Skip inactive plans
            if ( empty( $plan['is_active'] ) ) {
                $skipped++;
                continue;
            }

            // Check if product already exists for this plan
            $existing_product_id = $this->get_product_by_plan_slug( $slug );

            if ( $existing_product_id ) {
                // Update existing product
                $result = $this->update_woocommerce_product( $existing_product_id, $plan );
                if ( $result ) {
                    $updated++;
                }
            } else {
                // Create new product
                $result = $this->create_woocommerce_product( $plan );
                if ( $result ) {
                    $created++;
                }
            }
        }

        return sprintf(
            'Prodotti creati: %d, aggiornati: %d, saltati: %d',
            $created, $updated, $skipped
        );
    }

    /**
     * Find existing WooCommerce product by plan slug
     */
    private function get_product_by_plan_slug( $slug ) {
        global $wpdb;
        
        $product_id = $wpdb->get_var( $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
             WHERE meta_key = '_ipv_plan_slug' AND meta_value = %s
             LIMIT 1",
            $slug
        ));

        return $product_id ? absint( $product_id ) : null;
    }

    /**
     * Create WooCommerce product from plan
     */
    private function create_woocommerce_product( $plan ) {
        // Determine product type
        $is_subscription = in_array( $plan['price_period'], [ 'month', 'year', 'week' ] );
        
        // Use simple product (or subscription if WC Subscriptions is active)
        $product = new WC_Product_Simple();

        // Basic info
        $product->set_name( 'IPV Pro - ' . $plan['name'] );
        $product->set_slug( 'ipv-pro-' . $plan['slug'] );
        $product->set_status( 'publish' );
        $product->set_catalog_visibility( 'visible' );
        $product->set_description( $this->generate_product_description( $plan ) );
        $product->set_short_description( $plan['description'] ?? '' );

        // Price
        $product->set_regular_price( $plan['price'] );
        
        // Virtual & Downloadable
        $product->set_virtual( true );
        $product->set_downloadable( true );

        // Add download file if plugin is uploaded
        $plugin_info = $this->get_plugin_download_info();
        if ( $plugin_info ) {
            $upload_dir = wp_upload_dir();
            $download_url = $upload_dir['baseurl'] . '/ipv-downloads/' . $plugin_info['filename'];
            
            $product->set_downloads([
                [
                    'id' => wp_generate_uuid4(),
                    'name' => 'IPV Production System Pro v' . $plugin_info['version'],
                    'file' => $download_url,
                ]
            ]);
            $product->set_download_limit( -1 ); // Unlimited
            $product->set_download_expiry( 365 ); // 1 year
        }

        // Save product
        $product_id = $product->save();

        if ( ! $product_id ) {
            return false;
        }

        // Set IPV meta
        update_post_meta( $product_id, '_ipv_is_license_product', 'yes' );
        update_post_meta( $product_id, '_ipv_plan_slug', $plan['slug'] );
        update_post_meta( $product_id, '_ipv_credits_total', $plan['credits'] );
        update_post_meta( $product_id, '_ipv_activation_limit', $plan['activations'] );

        // Set category
        $this->assign_product_category( $product_id );

        return $product_id;
    }

    /**
     * Update existing WooCommerce product
     */
    private function update_woocommerce_product( $product_id, $plan ) {
        $product = wc_get_product( $product_id );
        
        if ( ! $product ) {
            return false;
        }

        // Update basic info
        $product->set_name( 'IPV Pro - ' . $plan['name'] );
        $product->set_description( $this->generate_product_description( $plan ) );
        $product->set_short_description( $plan['description'] ?? '' );
        $product->set_regular_price( $plan['price'] );

        // Update download file if plugin is uploaded
        $plugin_info = $this->get_plugin_download_info();
        if ( $plugin_info ) {
            $upload_dir = wp_upload_dir();
            $download_url = $upload_dir['baseurl'] . '/ipv-downloads/' . $plugin_info['filename'];
            
            $product->set_downloads([
                [
                    'id' => wp_generate_uuid4(),
                    'name' => 'IPV Production System Pro v' . $plugin_info['version'],
                    'file' => $download_url,
                ]
            ]);
        }

        $product->save();

        // Update IPV meta
        update_post_meta( $product_id, '_ipv_credits_total', $plan['credits'] );
        update_post_meta( $product_id, '_ipv_activation_limit', $plan['activations'] );

        return true;
    }

    /**
     * Generate product description HTML
     *
     * v1.4.2-FIXED9 - Descrizioni pulite senza emoji/caratteri speciali
     * Risolve problema testo bianco su sfondo bianco in WooCommerce editor
     *
     * @param array $plan Piano con configurazione (name, description, features, etc.)
     * @return string HTML description pulito
     */
    private function generate_product_description( $plan ) {
        $features = $this->get_available_features();
        $period_label = $this->get_period_label( $plan['credits_period'] );

        // Build clean HTML without special characters or emoji
        $html = '<h3>IPV Production System Pro - Piano ' . esc_html( $plan['name'] ) . '</h3>';
        
        $description = $plan['description'] ?? 'Il sistema professionale per automatizzare la produzione video.';
        $html .= '<p>' . esc_html( $description ) . '</p>';

        // Cosa Include
        $html .= '<h4>Cosa Include:</h4>';
        $html .= '<ul>';
        $html .= '<li><strong>' . intval( $plan['credits'] ) . ' video</strong> importabili al ' . esc_html( $period_label ) . '</li>';
        $html .= '<li><strong>' . intval( $plan['activations'] ) . ' sito/i</strong> WordPress attivabili</li>';

        // Features
        foreach ( $features as $key => $feature ) {
            if ( ! empty( $plan['features'][ $key ] ) ) {
                $html .= '<li>' . esc_html( $feature['name'] ) . '</li>';
            }
        }
        $html .= '</ul>';

        // Funzionalita Principali
        $html .= '<h4>Funzionalita Principali:</h4>';
        $html .= '<ul>';
        $html .= '<li>Trascrizione automatica video YouTube</li>';
        $html .= '<li>Generazione descrizioni con AI (GPT-4)</li>';
        $html .= '<li>Download automatico thumbnail HD</li>';
        $html .= '<li>Import singolo e massivo</li>';
        $html .= '<li>Video Wall personalizzabile con filtri AJAX</li>';
        $html .= '<li>Dashboard analytics completa</li>';
        $html .= '<li>Golden Prompt personalizzabile</li>';
        $html .= '<li>Sistema licenze integrato</li>';
        $html .= '</ul>';

        // Supporto & Garanzie
        $html .= '<h4>Supporto e Garanzie:</h4>';
        $html .= '<ul>';
        $html .= '<li>Supporto email prioritario</li>';
        $html .= '<li>Aggiornamenti automatici inclusi</li>';
        $html .= '<li>Garanzia 30 giorni soddisfatti o rimborsati</li>';
        $html .= '<li>Server SaaS dedicato per API</li>';
        $html .= '</ul>';

        // Log per debug
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( '[IPV Plans] Description generated - Length: ' . strlen( $html ) );
        }

        return $html;
    }

    /**
     * Assign product to IPV category
     */
    private function assign_product_category( $product_id ) {
        // Create or get IPV category
        $cat_name = 'IPV Pro Plugin';
        $cat_slug = 'ipv-pro-plugin';
        
        $term = get_term_by( 'slug', $cat_slug, 'product_cat' );
        
        if ( ! $term ) {
            $result = wp_insert_term( $cat_name, 'product_cat', [
                'slug' => $cat_slug,
                'description' => 'Piani e licenze per IPV Production System Pro'
            ]);
            
            if ( ! is_wp_error( $result ) ) {
                $term_id = $result['term_id'];
            }
        } else {
            $term_id = $term->term_id;
        }

        if ( isset( $term_id ) ) {
            wp_set_object_terms( $product_id, $term_id, 'product_cat' );
        }
    }

    /**
     * Get all IPV products
     */
    public function get_ipv_products() {
        $args = [
            'post_type' => 'product',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => '_ipv_is_license_product',
                    'value' => 'yes',
                ]
            ]
        ];
        
        return get_posts( $args );
    }
}
