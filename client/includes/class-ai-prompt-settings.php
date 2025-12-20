<?php
/**
 * AI & Prompt Settings
 * Gestisce configurazione Golden Prompt con template remoto
 *
 * @package IPV_Production_System_Pro
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Prod_AI_Prompt_Settings {

    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Option keys
     */
    const OPTION_PREFIX = 'ipv_golden_prompt_';
    
    /**
     * Get singleton instance
     */
    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action( 'admin_menu', [ $this, 'add_menu_page' ], 25 );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'wp_ajax_ipv_apply_golden_template', [ $this, 'ajax_apply_template' ] );
    }

    /**
     * Add menu page
     */
    public function add_menu_page() {
        add_submenu_page(
            'edit.php?post_type=ipv_video',
            __( 'AI & Prompt', 'ipv-production-system-pro' ),
            __( '🤖 AI & Prompt', 'ipv-production-system-pro' ),
            'manage_options',
            'ipv-ai-prompt',
            [ $this, 'render_page' ]
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        $fields = $this->get_fields();
        
        foreach ( $fields as $field ) {
            register_setting( 
                'ipv_ai_prompt_settings', 
                self::OPTION_PREFIX . $field['id'],
                [
                    'type' => $field['type'] ?? 'string',
                    'sanitize_callback' => $field['sanitize'] ?? 'sanitize_text_field'
                ]
            );
        }
    }

    /**
     * Get form fields
     */
    private function get_fields() {
        return [
            // Identità Canale
            [
                'id' => 'nome_canale',
                'label' => 'Nome Canale',
                'type' => 'text',
                'placeholder' => 'Il Punto di Vista',
                'description' => 'Nome del tuo canale YouTube',
                'section' => 'identity'
            ],
            [
                'id' => 'handle',
                'label' => 'Handle YouTube',
                'type' => 'text',
                'placeholder' => 'ilpuntodivista_official',
                'description' => 'Handle senza @ (es: canale_nome)',
                'section' => 'identity'
            ],
            [
                'id' => 'nicchia',
                'label' => 'Nicchia',
                'type' => 'text',
                'placeholder' => 'Esoterismo, Spiritualità, Misteri',
                'description' => 'Argomenti principali del canale',
                'section' => 'identity'
            ],
            [
                'id' => 'bio_canale',
                'label' => 'Bio Canale',
                'type' => 'textarea',
                'placeholder' => 'Descrivi il tuo canale in 2-3 righe...',
                'description' => 'Breve descrizione del canale (2-3 righe)',
                'section' => 'identity'
            ],
            
            // Link Social
            [
                'id' => 'link_telegram',
                'label' => 'Link Telegram',
                'type' => 'url',
                'placeholder' => 'https://t.me/...',
                'section' => 'social'
            ],
            [
                'id' => 'link_facebook',
                'label' => 'Link Facebook',
                'type' => 'url',
                'placeholder' => 'https://facebook.com/...',
                'section' => 'social'
            ],
            [
                'id' => 'link_instagram',
                'label' => 'Link Instagram',
                'type' => 'url',
                'placeholder' => 'https://instagram.com/...',
                'section' => 'social'
            ],
            [
                'id' => 'link_sito',
                'label' => 'Link Sito Web',
                'type' => 'url',
                'placeholder' => 'https://...',
                'section' => 'social'
            ],
            [
                'id' => 'link_donazioni',
                'label' => 'Link Donazioni',
                'type' => 'url',
                'placeholder' => 'https://paypal.me/... o https://ko-fi.com/...',
                'section' => 'social'
            ],
            
            // Sponsor (multipli - gestiti separatamente come JSON)
            [
                'id' => 'sponsors',
                'label' => 'Sponsor',
                'type' => 'sponsors_array',
                'description' => 'Puoi aggiungere più sponsor. Lascia vuoto se non hai sponsor.',
                'section' => 'sponsor',
                'sanitize' => 'sanitize_sponsors_array'
            ],
            
            // Contatti
            [
                'id' => 'email_business',
                'label' => 'Email Business',
                'type' => 'email',
                'placeholder' => 'business@email.com',
                'description' => 'Email per collaborazioni',
                'section' => 'contacts'
            ],
            [
                'id' => 'hashtag_canale',
                'label' => 'Hashtag Canale',
                'type' => 'text',
                'placeholder' => 'IlPuntoDiVista',
                'description' => 'Hashtag principale senza # (es: IlPuntoDiVista)',
                'section' => 'contacts'
            ],
            
            // ✅ FLAGS SEZIONI (18 totali)
            // Sezioni Descrizione (12)
            [
                'id' => 'show_risorse',
                'label' => '📚 Risorse Menzionate',
                'type' => 'boolean',
                'default' => true,
                'description' => 'Libri, film, link citati nel video',
                'section' => 'flags'
            ],
            [
                'id' => 'show_descrizione',
                'label' => '📝 Descrizione',
                'type' => 'boolean',
                'default' => true,
                'description' => 'Testo descrittivo principale',
                'section' => 'flags'
            ],
            [
                'id' => 'show_argomenti',
                'label' => '📋 Argomenti Trattati',
                'type' => 'boolean',
                'default' => true,
                'description' => 'Lista punti principali',
                'section' => 'flags'
            ],
            [
                'id' => 'show_ospiti',
                'label' => '👤 Ospiti',
                'type' => 'boolean',
                'default' => true,
                'description' => 'Nome e ruolo ospiti',
                'section' => 'flags'
            ],
            [
                'id' => 'show_persone',
                'label' => '🗣️ Persone Menzionate',
                'type' => 'boolean',
                'default' => true,
                'description' => 'Nomi citati nel video',
                'section' => 'flags'
            ],
            [
                'id' => 'show_sponsor',
                'label' => '🌟 Sponsor',
                'type' => 'boolean',
                'default' => true,
                'description' => 'Box sponsor del video',
                'section' => 'flags'
            ],
            [
                'id' => 'show_chi_siamo',
                'label' => '👥 Chi Siamo',
                'type' => 'boolean',
                'default' => true,
                'description' => 'Bio del canale',
                'section' => 'flags'
            ],
            [
                'id' => 'show_contatti',
                'label' => '📩 Contatti',
                'type' => 'boolean',
                'default' => true,
                'description' => 'Email business',
                'section' => 'flags'
            ],
            [
                'id' => 'show_indice',
                'label' => '⏱️ Indice Timestamp',
                'type' => 'boolean',
                'default' => true,
                'description' => 'Capitoli con timestamp',
                'section' => 'flags'
            ],
            [
                'id' => 'show_hashtag',
                'label' => '#️⃣ Hashtag',
                'type' => 'boolean',
                'default' => true,
                'description' => 'Hashtag finali',
                'section' => 'flags'
            ],
            [
                'id' => 'show_community',
                'label' => '💥 Unisciti Community',
                'type' => 'boolean',
                'default' => true,
                'description' => 'Sezione link social',
                'section' => 'flags'
            ],
            [
                'id' => 'show_supporta',
                'label' => '👍 Supporta Canale',
                'type' => 'boolean',
                'default' => true,
                'description' => 'Like, commenti, iscrizioni',
                'section' => 'flags'
            ],
            
            // Link Social Individuali (6)
            [
                'id' => 'show_link_telegram',
                'label' => '📱 Telegram',
                'type' => 'boolean',
                'default' => true,
                'section' => 'social_flags'
            ],
            [
                'id' => 'show_link_facebook',
                'label' => '👥 Facebook',
                'type' => 'boolean',
                'default' => true,
                'section' => 'social_flags'
            ],
            [
                'id' => 'show_link_instagram',
                'label' => '📸 Instagram',
                'type' => 'boolean',
                'default' => true,
                'section' => 'social_flags'
            ],
            [
                'id' => 'show_link_sito',
                'label' => '🌐 Sito Web',
                'type' => 'boolean',
                'default' => true,
                'section' => 'social_flags'
            ],
            [
                'id' => 'show_link_donazioni',
                'label' => '☕ Donazioni',
                'type' => 'boolean',
                'default' => true,
                'section' => 'social_flags'
            ],
            [
                'id' => 'show_link_youtube',
                'label' => '📺 YouTube',
                'type' => 'boolean',
                'default' => true,
                'section' => 'social_flags'
            ],
        ];
    }

    /**
     * Get saved value
     */
    private function get_value( $field_id, $default = '' ) {
        return get_option( self::OPTION_PREFIX . $field_id, $default );
    }

    /**
     * Render page
     */
    public function render_page() {
        $has_vendor_template = function_exists( 'ipv_get_golden_prompt' ) && ipv_has_golden_prompt();
        ?>
        <div class="ipv-modern-page bg-gray-50 min-h-screen -ml-5 -mt-2 p-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">🤖 <?php _e( 'AI & Prompt Configuration', 'ipv-production-system-pro' ); ?></h1>
                <p class="text-gray-600"><?php _e( 'Configura i dati per il Golden Prompt. Verranno automaticamente inseriti nel template remoto.', 'ipv-production-system-pro' ); ?></p>
            </div>

            <?php if ( $has_vendor_template ) : ?>
                <div class="ipv-card mb-6 bg-green-50 border-green-200">
                    <div class="p-4 flex items-center gap-3">
                        <svg width="24" height="24" class="text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div class="flex-1">
                            <p class="font-semibold text-green-900"><?php _e( 'Golden Prompt Template caricato dal server!', 'ipv-production-system-pro' ); ?></p>
                            <p class="text-sm text-green-700"><?php _e( 'I tuoi dati verranno automaticamente inseriti nel template remoto.', 'ipv-production-system-pro' ); ?></p>
                        </div>
                    </div>
                </div>
            <?php else : ?>
                <div class="ipv-card mb-6 bg-yellow-50 border-yellow-200">
                    <div class="p-4 flex items-center gap-3">
                        <svg width="24" height="24" class="text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <div class="flex-1">
                            <p class="font-semibold text-yellow-900"><?php _e( 'Nessun Golden Prompt Template dal server', 'ipv-production-system-pro' ); ?></p>
                            <p class="text-sm text-yellow-700"><?php _e( 'Configura comunque i dati. Quando il template sarà disponibile, verranno utilizzati automaticamente.', 'ipv-production-system-pro' ); ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <form method="post" action="options.php" class="space-y-6">
                <?php settings_fields( 'ipv_ai_prompt_settings' ); ?>

                <!-- Identità Canale -->
                <div class="ipv-card">
                    <div class="ipv-card-header">
                        <h2 class="ipv-card-title">
                            <svg width="20" height="20" class="text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            <?php _e( 'Identità Canale', 'ipv-production-system-pro' ); ?>
                        </h2>
                    </div>
                    <div class="p-6 space-y-4">
                        <?php $this->render_fields( 'identity' ); ?>
                    </div>
                </div>

                <!-- Link Social -->
                <div class="ipv-card">
                    <div class="ipv-card-header">
                        <h2 class="ipv-card-title">
                            <svg width="20" height="20" class="text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"></path>
                            </svg>
                            <?php _e( 'Link Social', 'ipv-production-system-pro' ); ?>
                        </h2>
                    </div>
                    <div class="p-6 space-y-4">
                        <?php $this->render_fields( 'social' ); ?>
                        <p class="text-sm text-gray-500">
                            💡 <?php _e( 'Lascia vuoti i campi dei social che non usi. Verranno automaticamente omessi.', 'ipv-production-system-pro' ); ?>
                        </p>
                    </div>
                </div>

                <!-- Sponsor Multipli (Opzionale) -->
                <div class="ipv-card">
                    <div class="ipv-card-header">
                        <h2 class="ipv-card-title">
                            <svg width="20" height="20" class="text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <?php _e( '🌟 Sponsor (Multipli)', 'ipv-production-system-pro' ); ?>
                        </h2>
                    </div>
                    <div class="p-6">
                        <p class="text-sm text-gray-500 mb-4">
                            💡 <?php _e( 'Puoi aggiungere più sponsor. Ogni sponsor apparirà nella sezione dedicata del Golden Prompt. Lascia vuoto se non hai sponsor.', 'ipv-production-system-pro' ); ?>
                        </p>

                        <?php
                        $sponsors = json_decode( get_option( self::OPTION_PREFIX . 'sponsors', '[]' ), true );
                        if ( ! is_array( $sponsors ) ) $sponsors = [];
                        ?>

                        <div id="ipv-sponsors-container" class="space-y-4">
                            <?php if ( empty( $sponsors ) ) : ?>
                                <!-- Template vuoto per primo sponsor -->
                                <div class="ipv-sponsor-item bg-gray-50 border border-gray-200 rounded-lg p-4" data-index="0">
                                    <div class="flex justify-between items-center mb-3">
                                        <span class="font-semibold text-gray-700">🌟 Sponsor #1</span>
                                        <button type="button" class="ipv-remove-sponsor text-red-500 hover:text-red-700 text-sm" style="display:none;">
                                            ✕ Rimuovi
                                        </button>
                                    </div>
                                    <div class="grid grid-cols-1 gap-3">
                                        <div>
                                            <label class="ipv-label">Nome Sponsor</label>
                                            <input type="text" name="sponsors[0][nome]" class="ipv-input" placeholder="Es: NordVPN">
                                        </div>
                                        <div>
                                            <label class="ipv-label">Descrizione</label>
                                            <textarea name="sponsors[0][descrizione]" class="ipv-input" rows="2" placeholder="Es: Proteggi la tua privacy online..."></textarea>
                                        </div>
                                        <div>
                                            <label class="ipv-label">Link</label>
                                            <input type="url" name="sponsors[0][link]" class="ipv-input" placeholder="https://...">
                                        </div>
                                    </div>
                                </div>
                            <?php else : ?>
                                <?php foreach ( $sponsors as $index => $sponsor ) : ?>
                                    <div class="ipv-sponsor-item bg-gray-50 border border-gray-200 rounded-lg p-4" data-index="<?php echo $index; ?>">
                                        <div class="flex justify-between items-center mb-3">
                                            <span class="font-semibold text-gray-700">🌟 Sponsor #<?php echo $index + 1; ?></span>
                                            <button type="button" class="ipv-remove-sponsor text-red-500 hover:text-red-700 text-sm">
                                                ✕ Rimuovi
                                            </button>
                                        </div>
                                        <div class="grid grid-cols-1 gap-3">
                                            <div>
                                                <label class="ipv-label">Nome Sponsor</label>
                                                <input type="text" name="sponsors[<?php echo $index; ?>][nome]" class="ipv-input" placeholder="Es: NordVPN" value="<?php echo esc_attr( $sponsor['nome'] ?? '' ); ?>">
                                            </div>
                                            <div>
                                                <label class="ipv-label">Descrizione</label>
                                                <textarea name="sponsors[<?php echo $index; ?>][descrizione]" class="ipv-input" rows="2" placeholder="Es: Proteggi la tua privacy online..."><?php echo esc_textarea( $sponsor['descrizione'] ?? '' ); ?></textarea>
                                            </div>
                                            <div>
                                                <label class="ipv-label">Link</label>
                                                <input type="url" name="sponsors[<?php echo $index; ?>][link]" class="ipv-input" placeholder="https://..." value="<?php echo esc_url( $sponsor['link'] ?? '' ); ?>">
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <button type="button" id="ipv-add-sponsor" class="mt-4 bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg inline-flex items-center gap-2 transition">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            <?php _e( 'Aggiungi Sponsor', 'ipv-production-system-pro' ); ?>
                        </button>
                    </div>
                </div>

                <!-- Contatti -->
                <div class="ipv-card">
                    <div class="ipv-card-header">
                        <h2 class="ipv-card-title">
                            <svg width="20" height="20" class="text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            <?php _e( 'Contatti & Hashtag', 'ipv-production-system-pro' ); ?>
                        </h2>
                    </div>
                    <div class="p-6 space-y-4">
                        <?php $this->render_fields( 'contacts' ); ?>
                    </div>
                </div>

                <!-- ✅ NUOVO! Personalizza Sezioni Golden Prompt -->
                <div class="ipv-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
                    <div class="ipv-card-header" style="border-color: rgba(255,255,255,0.2);">
                        <h2 class="ipv-card-title" style="color: white;">
                            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                            </svg>
                            <?php _e( '🎛️ Personalizza Sezioni Golden Prompt', 'ipv-production-system-pro' ); ?>
                        </h2>
                    </div>
                    <div class="p-6" style="background: white; border-radius: 0 0 8px 8px;">
                        <p class="text-sm text-gray-600 mb-4">
                            💡 <?php _e( 'Scegli quali sezioni mostrare nelle descrizioni AI. Tutte le sezioni sono attive di default.', 'ipv-production-system-pro' ); ?>
                        </p>

                        <!-- Sezioni Descrizione -->
                        <div class="mb-6">
                            <h3 class="font-semibold text-gray-900 mb-3" style="font-size: 15px; border-bottom: 2px solid #667eea; padding-bottom: 8px;">
                                📋 Sezioni Descrizione
                            </h3>
                            <div class="grid grid-cols-2 gap-3">
                                <?php $this->render_fields( 'flags' ); ?>
                            </div>
                        </div>

                        <!-- Sezioni Community -->
                        <div class="mb-6">
                            <h3 class="font-semibold text-gray-900 mb-3" style="font-size: 15px; border-bottom: 2px solid #667eea; padding-bottom: 8px;">
                                💥 Sezioni Community
                            </h3>
                            <div class="grid grid-cols-2 gap-3">
                                <?php 
                                $community_flags = array_filter($this->get_fields(), function($field) {
                                    return in_array($field['id'], ['show_community', 'show_supporta']);
                                });
                                foreach ($community_flags as $field) {
                                    $this->render_checkbox_field($field);
                                }
                                ?>
                            </div>
                        </div>

                        <!-- Link Social Individuali -->
                        <div class="mb-4">
                            <h3 class="font-semibold text-gray-900 mb-3" style="font-size: 15px; border-bottom: 2px solid #667eea; padding-bottom: 8px;">
                                🔗 Link Social Individuali
                            </h3>
                            <div class="grid grid-cols-3 gap-2">
                                <?php $this->render_fields( 'social_flags' ); ?>
                            </div>
                        </div>

                        <div class="bg-blue-50 border border-blue-200 rounded p-3 text-sm text-blue-900">
                            <strong>💡 Suggerimento:</strong> Lascia tutto selezionato per descrizioni complete, oppure deseleziona le sezioni che non vuoi per un approccio minimalista.
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="space-y-4">
                    <!-- Button Salva Locale -->
                    <div class="flex gap-3">
                        <button type="submit" class="ipv-btn ipv-btn-secondary">
                            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <?php _e( 'Salva Configurazione Locale', 'ipv-production-system-pro' ); ?>
                        </button>
                    </div>

                    <!-- ✅ NUOVO! Button Genera Golden Prompt -->
                    <div class="ipv-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
                        <div class="p-6 text-center">
                            <h3 class="text-white font-bold text-lg mb-2">
                                ✨ Genera il tuo Golden Prompt Personalizzato
                            </h3>
                            <p class="text-white text-sm mb-4 opacity-90">
                                Invia i tuoi dati al server per generare il Golden Prompt compilato e personalizzato
                            </p>
                            <button type="button" id="ipv-generate-golden-prompt" class="bg-white text-purple-700 font-bold px-8 py-3 rounded-lg hover:bg-gray-100 transition inline-flex items-center gap-2">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                                <?php _e( 'Genera Golden Prompt', 'ipv-production-system-pro' ); ?>
                            </button>
                            <div id="ipv-generate-status" class="mt-3 text-white text-sm"></div>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Preview -->
            <?php if ( $has_vendor_template ) : ?>
            <div class="ipv-card mt-6">
                <div class="ipv-card-header">
                    <h2 class="ipv-card-title">
                        <svg width="20" height="20" class="text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        <?php _e( 'Preview Placeholder', 'ipv-production-system-pro' ); ?>
                    </h2>
                </div>
                <div class="p-6">
                    <p class="text-sm text-gray-600 mb-3"><?php _e( 'Questi sono i placeholder che verranno sostituiti nel Golden Prompt:', 'ipv-production-system-pro' ); ?></p>
                    <div class="bg-gray-100 p-4 rounded-lg font-mono text-xs space-y-1">
                        <?php foreach ( $this->get_fields() as $field ) : ?>
                            <div class="text-gray-700">
                                <span class="text-blue-600">{<?php echo strtoupper( $field['id'] ); ?>}</span> 
                                → 
                                <span class="text-green-600"><?php echo esc_html( $this->get_value( $field['id'], '[non configurato]' ) ); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <?php if ( $has_vendor_template ) : ?>
        <script>
        jQuery(document).ready(function($) {
            $('#ipv-apply-template').on('click', function() {
                if (!confirm('<?php _e( 'Applicare il template Golden Prompt con i dati configurati? Il Golden Prompt locale verrà sovrascritto.', 'ipv-production-system-pro' ); ?>')) {
                    return;
                }

                const btn = $(this);
                btn.prop('disabled', true).html('<svg class="animate-spin w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> <?php _e( 'Applicando...', 'ipv-production-system-pro' ); ?>');

                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'ipv_apply_golden_template',
                        nonce: '<?php echo wp_create_nonce( 'ipv_apply_template' ); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('✅ ' + response.data.message);
                            location.reload();
                        } else {
                            alert('❌ ' + response.data.message);
                        }
                    },
                    error: function() {
                        alert('❌ <?php _e( 'Errore durante l\'applicazione del template', 'ipv-production-system-pro' ); ?>');
                    },
                    complete: function() {
                        btn.prop('disabled', false).html('<svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> <?php _e( 'Applica Template Ora', 'ipv-production-system-pro' ); ?>');
                    }
                });
            });

            // ═══════════════════════════════════════════════════════════════
            // SPONSOR MULTIPLI - Aggiungi/Rimuovi dinamicamente
            // ═══════════════════════════════════════════════════════════════

            // Aggiungi nuovo sponsor
            $('#ipv-add-sponsor').on('click', function() {
                const container = $('#ipv-sponsors-container');
                const items = container.find('.ipv-sponsor-item');
                const newIndex = items.length;

                const newSponsor = `
                    <div class="ipv-sponsor-item bg-gray-50 border border-gray-200 rounded-lg p-4" data-index="${newIndex}">
                        <div class="flex justify-between items-center mb-3">
                            <span class="font-semibold text-gray-700">🌟 Sponsor #${newIndex + 1}</span>
                            <button type="button" class="ipv-remove-sponsor text-red-500 hover:text-red-700 text-sm">
                                ✕ Rimuovi
                            </button>
                        </div>
                        <div class="grid grid-cols-1 gap-3">
                            <div>
                                <label class="ipv-label">Nome Sponsor</label>
                                <input type="text" name="sponsors[${newIndex}][nome]" class="ipv-input" placeholder="Es: NordVPN">
                            </div>
                            <div>
                                <label class="ipv-label">Descrizione</label>
                                <textarea name="sponsors[${newIndex}][descrizione]" class="ipv-input" rows="2" placeholder="Es: Proteggi la tua privacy online..."></textarea>
                            </div>
                            <div>
                                <label class="ipv-label">Link</label>
                                <input type="url" name="sponsors[${newIndex}][link]" class="ipv-input" placeholder="https://...">
                            </div>
                        </div>
                    </div>
                `;

                container.append(newSponsor);

                // Mostra bottone rimuovi su tutti gli item
                container.find('.ipv-remove-sponsor').show();
            });

            // Rimuovi sponsor
            $(document).on('click', '.ipv-remove-sponsor', function() {
                const item = $(this).closest('.ipv-sponsor-item');
                const container = $('#ipv-sponsors-container');

                item.remove();

                // Rinumera gli sponsor rimanenti
                container.find('.ipv-sponsor-item').each(function(index) {
                    $(this).attr('data-index', index);
                    $(this).find('.font-semibold').text('🌟 Sponsor #' + (index + 1));
                    $(this).find('input, textarea').each(function() {
                        const name = $(this).attr('name');
                        if (name) {
                            $(this).attr('name', name.replace(/sponsors\[\d+\]/, 'sponsors[' + index + ']'));
                        }
                    });
                });

                // Nascondi rimuovi se c'è solo un item
                if (container.find('.ipv-sponsor-item').length <= 1) {
                    container.find('.ipv-remove-sponsor').hide();
                }
            });

            // Funzione per raccogliere sponsor come array
            function collectSponsors() {
                const sponsors = [];
                $('#ipv-sponsors-container .ipv-sponsor-item').each(function() {
                    const nome = $(this).find('input[name*="[nome]"]').val();
                    const descrizione = $(this).find('textarea[name*="[descrizione]"]').val();
                    const link = $(this).find('input[name*="[link]"]').val();

                    // Aggiungi solo se almeno il nome è compilato
                    if (nome && nome.trim() !== '') {
                        sponsors.push({
                            nome: nome.trim(),
                            descrizione: descrizione ? descrizione.trim() : '',
                            link: link ? link.trim() : ''
                        });
                    }
                });
                return sponsors;
            }

            // ✅ Handler per "Genera Golden Prompt"
            $('#ipv-generate-golden-prompt').on('click', function() {
                const btn = $(this);
                const statusDiv = $('#ipv-generate-status');

                // Conferma
                if (!confirm('<?php _e( 'Generare il Golden Prompt personalizzato sul server? Verranno inviati tutti i dati configurati.', 'ipv-production-system-pro' ); ?>')) {
                    return;
                }

                // Raccogli TUTTI i dati (13 campi + sponsor multipli + 18 flags)
                const config = {
                    // Dati identità
                    nome_canale: $('#<?php echo self::OPTION_PREFIX; ?>nome_canale').val(),
                    handle: $('#<?php echo self::OPTION_PREFIX; ?>handle').val(),
                    nicchia: $('#<?php echo self::OPTION_PREFIX; ?>nicchia').val(),
                    bio_canale: $('#<?php echo self::OPTION_PREFIX; ?>bio_canale').val(),

                    // Link social
                    link_telegram: $('#<?php echo self::OPTION_PREFIX; ?>link_telegram').val(),
                    link_facebook: $('#<?php echo self::OPTION_PREFIX; ?>link_facebook').val(),
                    link_instagram: $('#<?php echo self::OPTION_PREFIX; ?>link_instagram').val(),
                    link_sito: $('#<?php echo self::OPTION_PREFIX; ?>link_sito').val(),
                    link_donazioni: $('#<?php echo self::OPTION_PREFIX; ?>link_donazioni').val(),

                    // ✅ SPONSOR MULTIPLI (array)
                    sponsors: collectSponsors(),

                    // Contatti
                    email_business: $('#<?php echo self::OPTION_PREFIX; ?>email_business').val(),
                    hashtag_canale: $('#<?php echo self::OPTION_PREFIX; ?>hashtag_canale').val(),

                    // ✅ 18 FLAGS
                    show_risorse: $('#<?php echo self::OPTION_PREFIX; ?>show_risorse').is(':checked'),
                    show_descrizione: $('#<?php echo self::OPTION_PREFIX; ?>show_descrizione').is(':checked'),
                    show_argomenti: $('#<?php echo self::OPTION_PREFIX; ?>show_argomenti').is(':checked'),
                    show_ospiti: $('#<?php echo self::OPTION_PREFIX; ?>show_ospiti').is(':checked'),
                    show_persone: $('#<?php echo self::OPTION_PREFIX; ?>show_persone').is(':checked'),
                    show_sponsor: $('#<?php echo self::OPTION_PREFIX; ?>show_sponsor').is(':checked'),
                    show_chi_siamo: $('#<?php echo self::OPTION_PREFIX; ?>show_chi_siamo').is(':checked'),
                    show_contatti: $('#<?php echo self::OPTION_PREFIX; ?>show_contatti').is(':checked'),
                    show_indice: $('#<?php echo self::OPTION_PREFIX; ?>show_indice').is(':checked'),
                    show_hashtag: $('#<?php echo self::OPTION_PREFIX; ?>show_hashtag').is(':checked'),
                    show_community: $('#<?php echo self::OPTION_PREFIX; ?>show_community').is(':checked'),
                    show_supporta: $('#<?php echo self::OPTION_PREFIX; ?>show_supporta').is(':checked'),
                    show_link_telegram: $('#<?php echo self::OPTION_PREFIX; ?>show_link_telegram').is(':checked'),
                    show_link_facebook: $('#<?php echo self::OPTION_PREFIX; ?>show_link_facebook').is(':checked'),
                    show_link_instagram: $('#<?php echo self::OPTION_PREFIX; ?>show_link_instagram').is(':checked'),
                    show_link_sito: $('#<?php echo self::OPTION_PREFIX; ?>show_link_sito').is(':checked'),
                    show_link_donazioni: $('#<?php echo self::OPTION_PREFIX; ?>show_link_donazioni').is(':checked'),
                    show_link_youtube: $('#<?php echo self::OPTION_PREFIX; ?>show_link_youtube').is(':checked'),
                };

                // Disabilita button e mostra loading
                btn.prop('disabled', true).html('<svg class="animate-spin w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> <?php _e( 'Generando...', 'ipv-production-system-pro' ); ?>');
                statusDiv.html('⏳ <?php _e( 'Invio dati al server...', 'ipv-production-system-pro' ); ?>');

                // ✅ POST AL VENDOR
                $.ajax({
                    url: '<?php echo defined('IPV_VENDOR_URL') ? IPV_VENDOR_URL : 'https://aiedintorni.it'; ?>/wp-json/ipv-vendor/v1/golden-prompt/compile',
                    method: 'POST',
                    headers: {
                        'X-License-Key': '<?php echo esc_js( get_option('ipv_pro_license_key', '') ); ?>'
                    },
                    data: JSON.stringify({ config: config }),
                    contentType: 'application/json',
                    timeout: 30000,
                    success: function(response) {
                        if (response.success) {
                            statusDiv.html('✅ ' + (response.message || '<?php _e( 'Golden Prompt generato con successo!', 'ipv-production-system-pro' ); ?>'));
                            setTimeout(function() {
                                alert('✅ <?php _e( 'Golden Prompt generato! Ora puoi usarlo per generare descrizioni AI personalizzate.', 'ipv-production-system-pro' ); ?>');
                                location.reload();
                            }, 1500);
                        } else {
                            statusDiv.html('❌ ' + (response.message || '<?php _e( 'Errore durante la generazione', 'ipv-production-system-pro' ); ?>'));
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', xhr.responseText);
                        let errorMsg = '<?php _e( 'Errore di connessione al server', 'ipv-production-system-pro' ); ?>';
                        if (xhr.status === 401) {
                            errorMsg = '<?php _e( 'Licenza non valida o Golden Prompt non attivo', 'ipv-production-system-pro' ); ?>';
                        } else if (xhr.status === 400) {
                            errorMsg = '<?php _e( 'Dati configurazione non validi', 'ipv-production-system-pro' ); ?>';
                        }
                        statusDiv.html('❌ ' + errorMsg);
                    },
                    complete: function() {
                        btn.prop('disabled', false).html('<svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg> <?php _e( 'Genera Golden Prompt', 'ipv-production-system-pro' ); ?>');
                    }
                });
            });
        });
        </script>
        <?php endif; ?>
        <?php
    }

    /**
     * Render fields for section
     */
    private function render_fields( $section ) {
        $fields = array_filter( $this->get_fields(), function( $field ) use ( $section ) {
            return $field['section'] === $section;
        });

        foreach ( $fields as $field ) {
            // ✅ Gestione checkbox per boolean
            if ( isset($field['type']) && $field['type'] === 'boolean' ) {
                $this->render_checkbox_field( $field );
                continue;
            }
            
            $value = $this->get_value( $field['id'] );
            ?>
            <div>
                <label for="<?php echo esc_attr( self::OPTION_PREFIX . $field['id'] ); ?>" class="ipv-label">
                    <?php echo esc_html( $field['label'] ); ?>
                </label>
                
                <?php if ( $field['type'] === 'textarea' ) : ?>
                    <textarea
                        id="<?php echo esc_attr( self::OPTION_PREFIX . $field['id'] ); ?>"
                        name="<?php echo esc_attr( self::OPTION_PREFIX . $field['id'] ); ?>"
                        class="ipv-input"
                        rows="3"
                        placeholder="<?php echo esc_attr( $field['placeholder'] ?? '' ); ?>"
                    ><?php echo esc_textarea( $value ); ?></textarea>
                <?php else : ?>
                    <input
                        type="<?php echo esc_attr( $field['type'] ); ?>"
                        id="<?php echo esc_attr( self::OPTION_PREFIX . $field['id'] ); ?>"
                        name="<?php echo esc_attr( self::OPTION_PREFIX . $field['id'] ); ?>"
                        value="<?php echo esc_attr( $value ); ?>"
                        class="ipv-input"
                        placeholder="<?php echo esc_attr( $field['placeholder'] ?? '' ); ?>"
                    >
                <?php endif; ?>
                
                <?php if ( ! empty( $field['description'] ) ) : ?>
                    <p class="text-sm text-gray-500 mt-1"><?php echo esc_html( $field['description'] ); ?></p>
                <?php endif; ?>
            </div>
            <?php
        }
    }

    /**
     * Render checkbox field
     */
    private function render_checkbox_field( $field ) {
        $value = $this->get_value( $field['id'], $field['default'] ?? false );
        $checked = $value === '1' || $value === true;
        ?>
        <label class="flex items-center gap-2 p-3 bg-gray-50 rounded hover:bg-gray-100 cursor-pointer transition">
            <input
                type="checkbox"
                id="<?php echo esc_attr( self::OPTION_PREFIX . $field['id'] ); ?>"
                name="<?php echo esc_attr( self::OPTION_PREFIX . $field['id'] ); ?>"
                value="1"
                <?php checked( $checked ); ?>
                class="w-4 h-4"
            >
            <span class="flex-1">
                <strong class="text-sm"><?php echo esc_html( $field['label'] ); ?></strong>
                <?php if ( ! empty( $field['description'] ) ) : ?>
                    <br><small class="text-gray-600"><?php echo esc_html( $field['description'] ); ?></small>
                <?php endif; ?>
            </span>
        </label>
        <?php
    }

    /**
     * AJAX: Apply template
     */
    public function ajax_apply_template() {
        check_ajax_referer( 'ipv_apply_template', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Permessi insufficienti', 'ipv-production-system-pro' ) ] );
        }

        // Get template from vendor
        if ( ! function_exists( 'ipv_get_golden_prompt' ) || ! ipv_has_golden_prompt() ) {
            wp_send_json_error( [ 'message' => __( 'Nessun template disponibile dal server', 'ipv-production-system-pro' ) ] );
        }

        $template = ipv_get_golden_prompt();
        
        // Get user data
        $replacements = $this->get_replacements();
        
        // Replace placeholders
        $compiled = $this->compile_template( $template, $replacements );
        
        // Save to local golden prompt
        update_option( 'ipv_golden_prompt', $compiled );
        
        wp_send_json_success( [ 
            'message' => __( 'Golden Prompt applicato con successo!', 'ipv-production-system-pro' ),
            'length' => strlen( $compiled )
        ] );
    }

    /**
     * Get replacements array
     */
    public function get_replacements() {
        $replacements = [];
        
        foreach ( $this->get_fields() as $field ) {
            $value = $this->get_value( $field['id'] );
            $key = '{' . strtoupper( $field['id'] ) . '}';
            $replacements[ $key ] = $value;
        }
        
        // Add special replacements
        $handle = $this->get_value( 'handle' );
        if ( ! empty( $handle ) ) {
            $replacements['{LINK_YOUTUBE}'] = 'https://www.youtube.com/@' . $handle . '?sub_confirmation=1';
        }
        
        return $replacements;
    }

    /**
     * Compile template with replacements
     */
    public function compile_template( $template, $replacements ) {
        $compiled = $template;
        
        foreach ( $replacements as $placeholder => $value ) {
            // Skip empty values for optional fields
            if ( empty( $value ) && $this->is_optional_field( $placeholder ) ) {
                // Remove entire sections for sponsor if empty
                if ( strpos( $placeholder, 'SPONSOR' ) !== false ) {
                    $compiled = $this->remove_sponsor_section( $compiled );
                }
                continue;
            }
            
            $compiled = str_replace( $placeholder, $value, $compiled );
        }
        
        // Clean up any remaining placeholders
        $compiled = preg_replace( '/\{[A-Z_]+\}/', '', $compiled );
        
        return $compiled;
    }

    /**
     * Check if field is optional
     */
    private function is_optional_field( $placeholder ) {
        $optional = [ 
            '{SPONSOR_NOME}', 
            '{SPONSOR_DESCRIZIONE}', 
            '{SPONSOR_LINK}',
            '{LINK_FACEBOOK}',
            '{LINK_INSTAGRAM}',
            '{LINK_SITO}'
        ];
        
        return in_array( $placeholder, $optional );
    }

    /**
     * Remove sponsor section if empty
     */
    private function remove_sponsor_section( $content ) {
        // Remove sponsor section between separators
        $pattern = '/▼+\s*🌟 SPONSOR DEL VIDEO:.*?▼+/s';
        return preg_replace( $pattern, '', $content );
    }
}

// Initialize
IPV_Prod_AI_Prompt_Settings::instance();
