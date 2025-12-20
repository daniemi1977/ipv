<?php
/**
 * IPV Pro - AI Prompt Configuration
 * Gestisce acquisto e configurazione Golden Prompt
 *
 * PROCESSO:
 * 1. Cliente compra Golden Prompt su vendor
 * 2. Cliente inserisce numero licenza in questa pagina
 * 3. Sistema verifica licenza + dominio tramite API vendor
 * 4. Vendor invia Golden Prompt (criptato, NON salvato localmente)
 * 5. Cliente configura fields per auto-compilazione
 * 6. Quando genera descrizione, Golden Prompt viene preso dal vendor (sempre remoto)
 *
 * @package IPV_Production_System_Pro
 * @version 10.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_AI_Prompt_Config {

    const OPTION_KEY = 'ipv_ai_prompt_config';
    const GOLDEN_PROMPT_LICENSE_KEY = 'ipv_golden_prompt_license';

    /**
     * Init hooks
     */
    public static function init() {
        add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
        add_action( 'wp_ajax_ipv_verify_golden_prompt_license', [ __CLASS__, 'ajax_verify_license' ] );
        add_action( 'wp_ajax_ipv_preview_golden_prompt', [ __CLASS__, 'ajax_preview_prompt' ] );
    }

    /**
     * Register settings
     */
    public static function register_settings() {
        register_setting( 'ipv_ai_prompt_config', self::OPTION_KEY, [
            'type' => 'array',
            'sanitize_callback' => [ __CLASS__, 'sanitize_config' ],
            'default' => []
        ] );

        register_setting( 'ipv_ai_prompt_config', self::GOLDEN_PROMPT_LICENSE_KEY, [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ] );
    }

    /**
     * Sanitize config
     */
    public static function sanitize_config( $input ) {
        if ( ! is_array( $input ) ) {
            return [];
        }

        $sanitized = [];
        
        // Text fields
        $text_fields = [ 'nome_canale', 'handle', 'nicchia', 'sponsor_nome', 'sponsor_descrizione', 'email_business', 'bio_canale', 'hashtag_canale' ];
        foreach ( $text_fields as $field ) {
            $sanitized[ $field ] = isset( $input[ $field ] ) ? sanitize_text_field( $input[ $field ] ) : '';
        }

        // URL fields
        $url_fields = [ 'link_youtube', 'link_telegram', 'link_facebook', 'link_instagram', 'link_sito', 'link_donazioni', 'sponsor_link' ];
        foreach ( $url_fields as $field ) {
            $sanitized[ $field ] = isset( $input[ $field ] ) ? esc_url_raw( $input[ $field ] ) : '';
        }

        // ✅ NEW: Flags Sezioni (boolean)
        $section_flags = [
            'show_risorse',          // Mostra sezione "Risorse Menzionate"
            'show_descrizione',      // Mostra sezione "Descrizione"
            'show_argomenti',        // Mostra lista "Argomenti Trattati"
            'show_ospiti',           // Mostra sezione "Ospiti"
            'show_persone',          // Mostra sezione "Persone Menzionate"
            'show_sponsor',          // Mostra sezione "Sponsor"
            'show_community',        // Mostra sezione "Community" (social)
            'show_supporta',         // Mostra sezione "Supporta il Canale"
            'show_chi_siamo',        // Mostra sezione "Chi Siamo"
            'show_contatti',         // Mostra sezione "Contatti"
            'show_indice',           // Mostra sezione "Indice" (timestamp)
            'show_hashtag',          // Mostra hashtag finali
        ];
        
        // ✅ NEW: Flags Link Social (boolean)
        $social_flags = [
            'show_link_youtube',
            'show_link_telegram',
            'show_link_facebook',
            'show_link_instagram',
            'show_link_sito',
            'show_link_donazioni',
        ];

        // Default tutti true se non specificato
        foreach ( array_merge( $section_flags, $social_flags ) as $flag ) {
            $sanitized[ $flag ] = isset( $input[ $flag ] ) ? (bool) $input[ $flag ] : true;
        }

        return $sanitized;
    }

    /**
     * Get config
     */
    public static function get_config() {
        return get_option( self::OPTION_KEY, [] );
    }

    /**
     * Get Golden Prompt License Key
     */
    public static function get_golden_prompt_license() {
        return get_option( self::GOLDEN_PROMPT_LICENSE_KEY, '' );
    }

    /**
     * Check if Golden Prompt is active
     */
    public static function has_golden_prompt() {
        $license = self::get_golden_prompt_license();
        if ( empty( $license ) ) {
            return false;
        }

        // Verifica via vendor API che la licenza Golden Prompt sia valida
        return self::verify_golden_prompt_license( $license );
    }

    /**
     * Verifica licenza Golden Prompt tramite vendor API
     */
    private static function verify_golden_prompt_license( $license_key ) {
        // Usa API client esistente per verificare licenza Golden Prompt
        if ( ! function_exists( 'ipv_verify_golden_prompt_license' ) ) {
            return false;
        }

        return ipv_verify_golden_prompt_license( $license_key );
    }

    /**
     * Get Golden Prompt from vendor (SEMPRE remoto, mai salvato in locale)
     */
    public static function get_golden_prompt_remote() {
        $license_key = self::get_golden_prompt_license();
        
        if ( empty( $license_key ) ) {
            return '';
        }

        // Richiedi Golden Prompt dal vendor
        if ( ! function_exists( 'ipv_fetch_golden_prompt_remote' ) ) {
            return '';
        }

        // Golden Prompt viene ricevuto criptato dal vendor e decriptato qui
        return ipv_fetch_golden_prompt_remote( $license_key );
    }

    /**
     * Sostituisce i placeholder nel Golden Prompt
     */
    public static function apply_placeholders( $prompt ) {
        $config = self::get_config();

        $placeholders = [
            '{NOME_CANALE}' => $config['nome_canale'] ?? '',
            '{HANDLE}' => $config['handle'] ?? '',
            '{NICCHIA}' => $config['nicchia'] ?? '',
            '{LINK_YOUTUBE}' => $config['link_youtube'] ?? '',
            '{LINK_TELEGRAM}' => $config['link_telegram'] ?? '',
            '{LINK_FACEBOOK}' => $config['link_facebook'] ?? '',
            '{LINK_INSTAGRAM}' => $config['link_instagram'] ?? '',
            '{LINK_SITO}' => $config['link_sito'] ?? '',
            '{LINK_DONAZIONI}' => $config['link_donazioni'] ?? '',
            '{SPONSOR_NOME}' => $config['sponsor_nome'] ?? '',
            '{SPONSOR_DESCRIZIONE}' => $config['sponsor_descrizione'] ?? '',
            '{SPONSOR_LINK}' => $config['sponsor_link'] ?? '',
            '{EMAIL_BUSINESS}' => $config['email_business'] ?? '',
            '{BIO_CANALE}' => $config['bio_canale'] ?? '',
            '{HASHTAG_CANALE}' => $config['hashtag_canale'] ?? '',
        ];

        $prompt = str_replace( array_keys( $placeholders ), array_values( $placeholders ), $prompt );

        // ✅ Applica flags sezioni per rimuovere parti non desiderate
        $prompt = self::apply_section_flags( $prompt, $config );

        return $prompt;
    }

    /**
     * Applica flags per rimuovere sezioni non desiderate dal Golden Prompt
     */
    private static function apply_section_flags( $prompt, $config ) {
        // Mapping: flag => pattern da cercare/rimuovere
        $section_patterns = [
            'show_risorse' => [
                '/◆─+\s*RISORSE MENZIONATE\s*─+◆.*?(?=◆─+|$)/is',
                '/RISORSE MENZIONATE.*?(?=\n\n[A-Z]|$)/is',
            ],
            'show_sponsor' => [
                '/▼+\s*SPONSOR DEL VIDEO:.*?▼+/is',
                '/🌟 SPONSOR DEL VIDEO:.*?(?=\n\n[💥🔥📢]|$)/is',
            ],
            'show_community' => [
                '/💥 UNISCITI ALLA COMMUNITY:.*?(?=\n\n[💥🔥📢]|$)/is',
                '/COMMUNITY.*?(?=\n\n[A-Z]|$)/is',
            ],
            'show_supporta' => [
                '/👍 SUPPORTA IL CANALE:.*?(?=\n\n[💥🔥📢]|$)/is',
                '/SUPPORTA.*?(?=\n\n[A-Z]|$)/is',
            ],
            'show_chi_siamo' => [
                '/👤 CHI SIAMO:.*?(?=\n\n[💥🔥📢]|$)/is',
                '/CHI SIAMO.*?(?=\n\n[A-Z]|$)/is',
            ],
            'show_contatti' => [
                '/📩 COLLABORAZIONI & CONTATTI:.*?(?=\n\n[💥🔥📢]|$)/is',
                '/CONTATTI.*?(?=\n\n[A-Z]|$)/is',
            ],
            'show_indice' => [
                '/◆─+\s*INDICE\s*─+◆.*?(?=\n\n#|$)/is',
                '/INDICE.*?(?=\n\n#|$)/is',
            ],
        ];

        // Rimuovi sezioni se flag = false
        foreach ( $section_patterns as $flag => $patterns ) {
            if ( empty( $config[ $flag ] ) || $config[ $flag ] === false ) {
                foreach ( $patterns as $pattern ) {
                    $prompt = preg_replace( $pattern, '', $prompt );
                }
            }
        }

        // ✅ Rimuovi singoli link social se flag = false
        $social_removals = [
            'show_link_telegram' => [ '/📱 Telegram:.*?\n/i', '/Telegram:.*?\n/i' ],
            'show_link_facebook' => [ '/👥 Facebook:.*?\n/i', '/Facebook:.*?\n/i' ],
            'show_link_instagram' => [ '/📸 Instagram:.*?\n/i', '/Instagram:.*?\n/i' ],
            'show_link_sito' => [ '/🌐 Sito:.*?\n/i', '/Sito Web:.*?\n/i' ],
            'show_link_donazioni' => [ '/☕ Donazioni:.*?\n/i', '/Donazioni:.*?\n/i' ],
        ];

        foreach ( $social_removals as $flag => $patterns ) {
            if ( empty( $config[ $flag ] ) || $config[ $flag ] === false ) {
                foreach ( $patterns as $pattern ) {
                    $prompt = preg_replace( $pattern, '', $prompt );
                }
            }
        }

        // ✅ Rimuovi hashtag finali se flag = false
        if ( empty( $config['show_hashtag'] ) || $config['show_hashtag'] === false ) {
            $prompt = preg_replace( '/\n\n#[A-Za-z0-9_]+\s+#.*$/is', '', $prompt );
        }

        // Clean up extra newlines
        $prompt = preg_replace( '/\n{3,}/', "\n\n", $prompt );
        $prompt = trim( $prompt );

        return $prompt;
    }

    /**
     * AJAX: Verifica licenza Golden Prompt
     */
    public static function ajax_verify_license() {
        check_ajax_referer( 'ipv_ai_prompt', 'nonce' );

        $license_key = sanitize_text_field( $_POST['license_key'] ?? '' );

        if ( empty( $license_key ) ) {
            wp_send_json_error( [ 'message' => 'Numero licenza richiesto' ] );
        }

        // Verifica via API vendor
        $verified = self::verify_golden_prompt_license( $license_key );

        if ( ! $verified ) {
            wp_send_json_error( [ 'message' => 'Licenza non valida o non associata a questo dominio' ] );
        }

        // Salva licenza verificata
        update_option( self::GOLDEN_PROMPT_LICENSE_KEY, $license_key );

        wp_send_json_success( [ 
            'message' => 'Licenza Golden Prompt attivata con successo!',
            'license_key' => $license_key
        ] );
    }

    /**
     * AJAX: Preview prompt compilato
     */
    public static function ajax_preview_prompt() {
        check_ajax_referer( 'ipv_ai_prompt', 'nonce' );

        // Get Golden Prompt dal vendor (SEMPRE remoto)
        $golden_prompt = self::get_golden_prompt_remote();

        if ( empty( $golden_prompt ) ) {
            wp_send_json_error( [ 'message' => 'Golden Prompt non attivo. Acquista e attiva una licenza Golden Prompt.' ] );
        }

        // Applica placeholder
        $compiled = self::apply_placeholders( $golden_prompt );

        wp_send_json_success( [ 'prompt' => $compiled ] );
    }

    /**
     * Render pagina
     */
    public static function render_page() {
        $config = self::get_config();
        $golden_license = self::get_golden_prompt_license();
        $has_golden = self::has_golden_prompt();

        // Salva se form inviato
        if ( isset( $_POST['ipv_save_ai_config'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'ipv_ai_prompt_config-options' ) ) {
            update_option( self::OPTION_KEY, self::sanitize_config( $_POST[self::OPTION_KEY] ?? [] ) );
            
            // Salva licenza Golden Prompt se fornita
            if ( ! empty( $_POST[self::GOLDEN_PROMPT_LICENSE_KEY] ) ) {
                update_option( self::GOLDEN_PROMPT_LICENSE_KEY, sanitize_text_field( $_POST[self::GOLDEN_PROMPT_LICENSE_KEY] ) );
                $golden_license = sanitize_text_field( $_POST[self::GOLDEN_PROMPT_LICENSE_KEY] );
                $has_golden = self::has_golden_prompt();
            }

            echo '<div class="notice notice-success"><p>✅ Configurazione salvata con successo!</p></div>';
            $config = self::get_config();
        }

        // URL acquisto Golden Prompt sul vendor
        $vendor_url = IPV_Prod_API_Client_Optimized::get_vendor_url();
        $purchase_url = trailingslashit( $vendor_url ) . 'prodotto-ai-prompt/'; // URL pagina acquisto

        ?>
        <div class="wrap ipv-modern-page">
            <h1 class="wp-heading-inline">🤖 AI e Prompt</h1>
            <p class="description" style="margin-top: 10px; margin-bottom: 20px;">
                Configura il tuo Golden Prompt e i dati del canale per generare descrizioni AI personalizzate.
            </p>

            <div class="ipv-grid" style="grid-template-columns: 2fr 1fr; gap: 24px; margin-top: 24px;">
                <!-- Form configurazione -->
                <div>
                    <!-- Golden Prompt License -->
                    <div class="ipv-card mb-6 <?php echo $has_golden ? 'bg-gradient-to-br from-green-50 to-emerald-50 border-green-200' : 'bg-gradient-to-br from-yellow-50 to-amber-50 border-yellow-200'; ?>">
                        <div class="ipv-card-header <?php echo $has_golden ? 'bg-green-100 border-green-200' : 'bg-yellow-100 border-yellow-200'; ?>">
                            <h2 class="ipv-card-title <?php echo $has_golden ? 'text-green-800' : 'text-yellow-800'; ?>">
                                <svg width="24" height="24" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Golden Prompt <?php echo $has_golden ? 'Attivo' : 'Non Attivo'; ?>
                            </h2>
                        </div>
                        <div class="p-6 space-y-4">
                            <?php if ( ! $has_golden ) : ?>
                                <!-- Non ha Golden Prompt -->
                                <div class="bg-yellow-100 border border-yellow-300 rounded-lg p-4">
                                    <p class="text-yellow-800 font-semibold mb-2">
                                        ⚠️ Golden Prompt non attivo
                                    </p>
                                    <p class="text-sm text-yellow-700 mb-4">
                                        Il Golden Prompt è un prodotto separato che contiene un prompt AI professionale ottimizzato per YouTube.
                                        Acquistalo sul sito vendor per sbloccare descrizioni AI di qualità superiore.
                                    </p>
                                    <a href="<?php echo esc_url( $purchase_url ); ?>" target="_blank" class="ipv-btn ipv-btn-primary">
                                        <svg width="16" height="16" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                        </svg>
                                        Acquista Golden Prompt
                                    </a>
                                </div>

                                <form method="post">
                                    <?php settings_fields( 'ipv_ai_prompt_config' ); ?>
                                    
                                    <div>
                                        <label class="ipv-label">Numero Licenza Golden Prompt <span class="text-red-500">*</span></label>
                                        <input type="text" 
                                               name="<?php echo self::GOLDEN_PROMPT_LICENSE_KEY; ?>" 
                                               value="<?php echo esc_attr( $golden_license ); ?>"
                                               class="ipv-input font-mono uppercase"
                                               placeholder="XXXX-XXXX-XXXX-XXXX"
                                               pattern="[A-Za-z0-9]{4}-[A-Za-z0-9]{4}-[A-Za-z0-9]{4}-[A-Za-z0-9]{4}">
                                        <p class="text-sm text-gray-500 mt-1">
                                            Inserisci il numero licenza ricevuto dopo l'acquisto
                                        </p>
                                    </div>

                                    <button type="button" id="ipv-verify-golden-license" class="ipv-btn ipv-btn-primary mt-4">
                                        <svg width="16" height="16" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Verifica e Attiva
                                    </button>
                                </form>

                            <?php else : ?>
                                <!-- Ha Golden Prompt attivo -->
                                <div class="bg-green-100 border border-green-300 rounded-lg p-4">
                                    <p class="text-green-800 font-semibold mb-2">
                                        ✅ Golden Prompt attivo e verificato
                                    </p>
                                    <p class="text-sm text-green-700">
                                        Licenza: <code class="ipv-code"><?php echo esc_html( substr( $golden_license, 0, 4 ) . '-****-****-' . substr( $golden_license, -4 ) ); ?></code>
                                    </p>
                                </div>

                                <button type="button" id="ipv-deactivate-golden-license" class="ipv-btn ipv-btn-danger">
                                    Disattiva Golden Prompt
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ( $has_golden ) : ?>
                        <!-- Form configurazione fields (solo se ha Golden Prompt) -->
                        <form method="post" action="options.php">
                            <?php settings_fields( 'ipv_ai_prompt_config' ); ?>

                            <!-- Informazioni Base -->
                            <div class="ipv-card mb-6">
                                <div class="ipv-card-header">
                                    <h2 class="ipv-card-title">
                                        <svg width="20" height="20" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Informazioni Base
                                    </h2>
                                </div>
                                <div class="p-6 space-y-4">
                                    <div>
                                        <label class="ipv-label">Nome Canale <span class="text-red-500">*</span></label>
                                        <input type="text" 
                                               name="<?php echo self::OPTION_KEY; ?>[nome_canale]" 
                                               value="<?php echo esc_attr( $config['nome_canale'] ?? '' ); ?>"
                                               class="ipv-input"
                                               placeholder="Il Punto di Vista"
                                               required>
                                        <p class="text-sm text-gray-500 mt-1">Tag: <code>{NOME_CANALE}</code></p>
                                    </div>

                                    <div>
                                        <label class="ipv-label">Handle YouTube <span class="text-red-500">*</span></label>
                                        <input type="text" 
                                               name="<?php echo self::OPTION_KEY; ?>[handle]" 
                                               value="<?php echo esc_attr( $config['handle'] ?? '' ); ?>"
                                               class="ipv-input"
                                               placeholder="ilpuntodivista_official"
                                               required>
                                        <p class="text-sm text-gray-500 mt-1">Tag: <code>{HANDLE}</code> - Handle senza @ (es: ilpuntodivista_official)</p>
                                    </div>

                                    <div>
                                        <label class="ipv-label">Nicchia <span class="text-red-500">*</span></label>
                                        <input type="text" 
                                               name="<?php echo self::OPTION_KEY; ?>[nicchia]" 
                                               value="<?php echo esc_attr( $config['nicchia'] ?? '' ); ?>"
                                               class="ipv-input"
                                               placeholder="esoterismo, spiritualità, misteri, geopolitica"
                                               required>
                                        <p class="text-sm text-gray-500 mt-1">Tag: <code>{NICCHIA}</code> - Argomenti principali del canale</p>
                                    </div>

                                    <div>
                                        <label class="ipv-label">Bio Canale <span class="text-red-500">*</span></label>
                                        <textarea name="<?php echo self::OPTION_KEY; ?>[bio_canale]" 
                                                  class="ipv-input"
                                                  rows="3"
                                                  placeholder="Descrizione del canale in 2-3 righe..."
                                                  required><?php echo esc_textarea( $config['bio_canale'] ?? '' ); ?></textarea>
                                        <p class="text-sm text-gray-500 mt-1">Tag: <code>{BIO_CANALE}</code> - Breve descrizione (2-3 righe)</p>
                                    </div>

                                    <div>
                                        <label class="ipv-label">Hashtag Canale <span class="text-red-500">*</span></label>
                                        <input type="text" 
                                               name="<?php echo self::OPTION_KEY; ?>[hashtag_canale]" 
                                               value="<?php echo esc_attr( $config['hashtag_canale'] ?? '' ); ?>"
                                               class="ipv-input"
                                               placeholder="#IlPuntoDiVista"
                                               required>
                                        <p class="text-sm text-gray-500 mt-1">Tag: <code>{HASHTAG_CANALE}</code> - Hashtag principale</p>
                                    </div>

                                    <div>
                                        <label class="ipv-label">Email Business</label>
                                        <input type="email" 
                                               name="<?php echo self::OPTION_KEY; ?>[email_business]" 
                                               value="<?php echo esc_attr( $config['email_business'] ?? '' ); ?>"
                                               class="ipv-input"
                                               placeholder="business@tuocanale.com">
                                        <p class="text-sm text-gray-500 mt-1">Tag: <code>{EMAIL_BUSINESS}</code> - Email per collaborazioni</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Link Social -->
                            <div class="ipv-card mb-6">
                                <div class="ipv-card-header">
                                    <h2 class="ipv-card-title">
                                        <svg width="20" height="20" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                                        </svg>
                                        Link Social & Web
                                    </h2>
                                </div>
                                <div class="p-6 space-y-4">
                                    <div>
                                        <label class="ipv-label">Link YouTube</label>
                                        <input type="url" 
                                               name="<?php echo self::OPTION_KEY; ?>[link_youtube]" 
                                               value="<?php echo esc_attr( $config['link_youtube'] ?? '' ); ?>"
                                               class="ipv-input"
                                               placeholder="https://www.youtube.com/@ilpuntodivista_official?sub_confirmation=1">
                                        <p class="text-sm text-gray-500 mt-1">Tag: <code>{LINK_YOUTUBE}</code> - URL con ?sub_confirmation=1</p>
                                    </div>

                                    <div>
                                        <label class="ipv-label">Link Telegram</label>
                                        <input type="url" 
                                               name="<?php echo self::OPTION_KEY; ?>[link_telegram]" 
                                               value="<?php echo esc_attr( $config['link_telegram'] ?? '' ); ?>"
                                               class="ipv-input"
                                               placeholder="https://t.me/tuocanale">
                                        <p class="text-sm text-gray-500 mt-1">Tag: <code>{LINK_TELEGRAM}</code></p>
                                    </div>

                                    <div>
                                        <label class="ipv-label">Link Facebook</label>
                                        <input type="url" 
                                               name="<?php echo self::OPTION_KEY; ?>[link_facebook]" 
                                               value="<?php echo esc_attr( $config['link_facebook'] ?? '' ); ?>"
                                               class="ipv-input"
                                               placeholder="https://facebook.com/tuapagina">
                                        <p class="text-sm text-gray-500 mt-1">Tag: <code>{LINK_FACEBOOK}</code></p>
                                    </div>

                                    <div>
                                        <label class="ipv-label">Link Instagram</label>
                                        <input type="url" 
                                               name="<?php echo self::OPTION_KEY; ?>[link_instagram]" 
                                               value="<?php echo esc_attr( $config['link_instagram'] ?? '' ); ?>"
                                               class="ipv-input"
                                               placeholder="https://instagram.com/tuoprofilo">
                                        <p class="text-sm text-gray-500 mt-1">Tag: <code>{LINK_INSTAGRAM}</code></p>
                                    </div>

                                    <div>
                                        <label class="ipv-label">Link Sito Web</label>
                                        <input type="url" 
                                               name="<?php echo self::OPTION_KEY; ?>[link_sito]" 
                                               value="<?php echo esc_attr( $config['link_sito'] ?? '' ); ?>"
                                               class="ipv-input"
                                               placeholder="https://tuosito.com">
                                        <p class="text-sm text-gray-500 mt-1">Tag: <code>{LINK_SITO}</code></p>
                                    </div>

                                    <div>
                                        <label class="ipv-label">Link Donazioni</label>
                                        <input type="url" 
                                               name="<?php echo self::OPTION_KEY; ?>[link_donazioni]" 
                                               value="<?php echo esc_attr( $config['link_donazioni'] ?? '' ); ?>"
                                               class="ipv-input"
                                               placeholder="https://paypal.me/tuoaccount">
                                        <p class="text-sm text-gray-500 mt-1">Tag: <code>{LINK_DONAZIONI}</code> - PayPal, Ko-fi, etc.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Sponsor (Opzionale) -->
                            <div class="ipv-card mb-6">
                                <div class="ipv-card-header">
                                    <h2 class="ipv-card-title">
                                        <svg width="20" height="20" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Sponsor (Opzionale)
                                    </h2>
                                </div>
                                <div class="p-6 space-y-4">
                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                                        <p class="text-sm text-blue-800">
                                            💡 <strong>Nota:</strong> Se non hai sponsor, lascia vuoti. La sezione sponsor verrà rimossa automaticamente.
                                        </p>
                                    </div>

                                    <div>
                                        <label class="ipv-label">Nome Sponsor</label>
                                        <input type="text" 
                                               name="<?php echo self::OPTION_KEY; ?>[sponsor_nome]" 
                                               value="<?php echo esc_attr( $config['sponsor_nome'] ?? '' ); ?>"
                                               class="ipv-input"
                                               placeholder="NordVPN">
                                        <p class="text-sm text-gray-500 mt-1">Tag: <code>{SPONSOR_NOME}</code></p>
                                    </div>

                                    <div>
                                        <label class="ipv-label">Descrizione Sponsor</label>
                                        <textarea name="<?php echo self::OPTION_KEY; ?>[sponsor_descrizione]" 
                                                  class="ipv-input"
                                                  rows="2"
                                                  placeholder="Proteggi la tua privacy online con NordVPN..."><?php echo esc_textarea( $config['sponsor_descrizione'] ?? '' ); ?></textarea>
                                        <p class="text-sm text-gray-500 mt-1">Tag: <code>{SPONSOR_DESCRIZIONE}</code></p>
                                    </div>

                                    <div>
                                        <label class="ipv-label">Link Sponsor (affiliato)</label>
                                        <input type="url" 
                                               name="<?php echo self::OPTION_KEY; ?>[sponsor_link]" 
                                               value="<?php echo esc_attr( $config['sponsor_link'] ?? '' ); ?>"
                                               class="ipv-input"
                                               placeholder="https://nordvpn.com/tuocodice">
                                        <p class="text-sm text-gray-500 mt-1">Tag: <code>{SPONSOR_LINK}</code></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Configurazione Avanzata -->
                            <div class="ipv-card mb-6">
                                <div class="ipv-card-header bg-gradient-to-br from-purple-50 to-indigo-50 border-purple-200">
                                    <h2 class="ipv-card-title text-purple-800">
                                        <svg width="20" height="20" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                                        </svg>
                                        Configurazione Avanzata
                                    </h2>
                                </div>
                                <div class="p-6">
                                    <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 mb-6">
                                        <p class="text-sm text-purple-800">
                                            <strong>🎛️ Controllo Sezioni:</strong> Decidi quali sezioni mostrare nella descrizione finale. Disattiva le sezioni che non vuoi includere.
                                        </p>
                                    </div>

                                    <!-- Sezioni Principali -->
                                    <div class="mb-6">
                                        <h3 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            Sezioni Descrizione
                                        </h3>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <?php
                                            $section_toggles = [
                                                'show_risorse' => [ 'label' => 'Risorse Menzionate', 'desc' => 'Libri, film, link citati nel video', 'icon' => '📚' ],
                                                'show_descrizione' => [ 'label' => 'Descrizione', 'desc' => 'Testo descrittivo principale', 'icon' => '📝' ],
                                                'show_argomenti' => [ 'label' => 'Argomenti Trattati', 'desc' => 'Lista punti principali', 'icon' => '📋' ],
                                                'show_ospiti' => [ 'label' => 'Ospiti', 'desc' => 'Nome e ruolo ospiti', 'icon' => '👤' ],
                                                'show_persone' => [ 'label' => 'Persone Menzionate', 'desc' => 'Nomi citati nel video', 'icon' => '🗣️' ],
                                                'show_sponsor' => [ 'label' => 'Sponsor', 'desc' => 'Box sponsor del video', 'icon' => '🌟' ],
                                                'show_chi_siamo' => [ 'label' => 'Chi Siamo', 'desc' => 'Bio del canale', 'icon' => '👥' ],
                                                'show_contatti' => [ 'label' => 'Contatti', 'desc' => 'Email business', 'icon' => '📩' ],
                                                'show_indice' => [ 'label' => 'Indice (Timestamp)', 'desc' => 'Capitoli con timestamp', 'icon' => '⏱️' ],
                                                'show_hashtag' => [ 'label' => 'Hashtag Finali', 'desc' => 'Hashtag in fondo', 'icon' => '#️⃣' ],
                                            ];

                                            foreach ( $section_toggles as $key => $data ) :
                                                $checked = ! isset( $config[ $key ] ) || $config[ $key ] === true;
                                            ?>
                                            <label class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer transition">
                                                <input type="checkbox" 
                                                       name="<?php echo self::OPTION_KEY; ?>[<?php echo $key; ?>]" 
                                                       value="1"
                                                       <?php checked( $checked ); ?>
                                                       class="mt-1 h-4 w-4 text-purple-600 rounded border-gray-300 focus:ring-purple-500">
                                                <div class="flex-1">
                                                    <div class="font-medium text-gray-900">
                                                        <?php echo $data['icon']; ?> <?php echo $data['label']; ?>
                                                    </div>
                                                    <div class="text-xs text-gray-500 mt-0.5">
                                                        <?php echo $data['desc']; ?>
                                                    </div>
                                                </div>
                                            </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <!-- Sezione Community -->
                                    <div class="mb-6">
                                        <h3 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                            </svg>
                                            Sezioni Community
                                        </h3>
                                        <div class="grid grid-cols-1 gap-4">
                                            <?php
                                            $community_toggles = [
                                                'show_community' => [ 'label' => 'Unisciti alla Community', 'desc' => 'Sezione completa link social', 'icon' => '💥' ],
                                                'show_supporta' => [ 'label' => 'Supporta il Canale', 'desc' => 'CTA like, commenti, iscrizioni', 'icon' => '👍' ],
                                            ];

                                            foreach ( $community_toggles as $key => $data ) :
                                                $checked = ! isset( $config[ $key ] ) || $config[ $key ] === true;
                                            ?>
                                            <label class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer transition">
                                                <input type="checkbox" 
                                                       name="<?php echo self::OPTION_KEY; ?>[<?php echo $key; ?>]" 
                                                       value="1"
                                                       <?php checked( $checked ); ?>
                                                       class="mt-1 h-4 w-4 text-purple-600 rounded border-gray-300 focus:ring-purple-500">
                                                <div class="flex-1">
                                                    <div class="font-medium text-gray-900">
                                                        <?php echo $data['icon']; ?> <?php echo $data['label']; ?>
                                                    </div>
                                                    <div class="text-xs text-gray-500 mt-0.5">
                                                        <?php echo $data['desc']; ?>
                                                    </div>
                                                </div>
                                            </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <!-- Link Social Individuali -->
                                    <div>
                                        <h3 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                                            </svg>
                                            Link Social (Visibilità Individuale)
                                        </h3>
                                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-3">
                                            <p class="text-xs text-blue-800">
                                                💡 Controlla quali link social mostrare nella sezione Community. I link non selezionati verranno nascosti.
                                            </p>
                                        </div>
                                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                                            <?php
                                            $social_toggles = [
                                                'show_link_telegram' => [ 'label' => 'Telegram', 'icon' => '📱' ],
                                                'show_link_facebook' => [ 'label' => 'Facebook', 'icon' => '👥' ],
                                                'show_link_instagram' => [ 'label' => 'Instagram', 'icon' => '📸' ],
                                                'show_link_sito' => [ 'label' => 'Sito Web', 'icon' => '🌐' ],
                                                'show_link_donazioni' => [ 'label' => 'Donazioni', 'icon' => '☕' ],
                                                'show_link_youtube' => [ 'label' => 'YouTube', 'icon' => '📺' ],
                                            ];

                                            foreach ( $social_toggles as $key => $data ) :
                                                $checked = ! isset( $config[ $key ] ) || $config[ $key ] === true;
                                            ?>
                                            <label class="flex items-center gap-2 p-2 bg-white border border-gray-200 rounded hover:border-purple-300 cursor-pointer transition">
                                                <input type="checkbox" 
                                                       name="<?php echo self::OPTION_KEY; ?>[<?php echo $key; ?>]" 
                                                       value="1"
                                                       <?php checked( $checked ); ?>
                                                       class="h-4 w-4 text-purple-600 rounded border-gray-300 focus:ring-purple-500">
                                                <span class="text-sm font-medium text-gray-700">
                                                    <?php echo $data['icon']; ?> <?php echo $data['label']; ?>
                                                </span>
                                            </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <!-- Help Toggle All -->
                                    <div class="mt-6 pt-6 border-t border-gray-200">
                                        <div class="flex gap-3">
                                            <button type="button" id="ipv-toggle-all-on" class="ipv-btn ipv-btn-secondary text-sm">
                                                ✅ Attiva Tutto
                                            </button>
                                            <button type="button" id="ipv-toggle-all-off" class="ipv-btn ipv-btn-secondary text-sm">
                                                ❌ Disattiva Tutto
                                            </button>
                                            <button type="button" id="ipv-toggle-default" class="ipv-btn ipv-btn-secondary text-sm">
                                                🔄 Ripristina Default
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Pulsanti -->
                            <div class="flex gap-3">
                                <button type="submit" name="ipv_save_ai_config" class="ipv-btn ipv-btn-primary">
                                    <svg width="16" height="16" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Salva Configurazione
                                </button>

                                <button type="button" id="ipv-preview-prompt" class="ipv-btn ipv-btn-secondary">
                                    <svg width="16" height="16" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    Preview Golden Prompt
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>

                <!-- Sidebar Info -->
                <div>
                    <?php if ( $has_golden ) : ?>
                        <!-- Stato Configurazione -->
                        <div class="ipv-card mb-6">
                            <div class="ipv-card-header">
                                <h3 class="ipv-card-title text-base">
                                    <svg width="20" height="20" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Stato Configurazione
                                </h3>
                            </div>
                            <div class="p-6 space-y-3">
                                <?php
                                $required = [ 'nome_canale', 'handle', 'nicchia', 'bio_canale', 'hashtag_canale' ];
                                $configured = 0;
                                foreach ( $required as $field ) {
                                    if ( ! empty( $config[ $field ] ) ) {
                                        $configured++;
                                    }
                                }
                                $percentage = ( $configured / count( $required ) ) * 100;
                                $color = $percentage === 100 ? 'green' : ( $percentage > 50 ? 'yellow' : 'red' );
                                ?>

                                <div class="bg-gray-200 rounded-full h-3 overflow-hidden">
                                    <div class="bg-<?php echo $color; ?>-500 h-full transition-all" style="width: <?php echo $percentage; ?>%;"></div>
                                </div>

                                <p class="text-lg font-bold text-gray-900">
                                    <?php echo $configured; ?>/<?php echo count( $required ); ?> campi obbligatori
                                </p>

                                <?php if ( $percentage === 100 ) : ?>
                                    <div class="bg-green-50 border border-green-200 rounded-lg p-3">
                                        <p class="text-sm text-green-800">
                                            ✅ Configurazione completa!
                                        </p>
                                    </div>
                                <?php else : ?>
                                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                        <p class="text-sm text-yellow-800">
                                            ⚠️ Completa tutti i campi obbligatori
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Stato Sezioni Attive (NEW) -->
                    <?php if ( $has_golden ) : ?>
                    <div class="ipv-card mb-6">
                        <div class="ipv-card-header">
                            <h3 class="ipv-card-title text-base">
                                <svg width="20" height="20" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                                </svg>
                                Sezioni Attive
                            </h3>
                        </div>
                        <div class="p-6">
                            <?php
                            // Conta sezioni attive
                            $all_sections = [
                                'show_risorse', 'show_descrizione', 'show_argomenti', 'show_ospiti',
                                'show_persone', 'show_sponsor', 'show_community', 'show_supporta',
                                'show_chi_siamo', 'show_contatti', 'show_indice', 'show_hashtag'
                            ];
                            $active_sections = 0;
                            foreach ( $all_sections as $section ) {
                                if ( ! isset( $config[ $section ] ) || $config[ $section ] === true ) {
                                    $active_sections++;
                                }
                            }
                            $section_percentage = ( $active_sections / count( $all_sections ) ) * 100;

                            // Conta link social attivi
                            $social_links = [
                                'show_link_youtube', 'show_link_telegram', 'show_link_facebook',
                                'show_link_instagram', 'show_link_sito', 'show_link_donazioni'
                            ];
                            $active_links = 0;
                            foreach ( $social_links as $link ) {
                                if ( ! isset( $config[ $link ] ) || $config[ $link ] === true ) {
                                    $active_links++;
                                }
                            }
                            ?>

                            <div class="space-y-4">
                                <!-- Sezioni -->
                                <div>
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-sm font-medium text-gray-700">Sezioni</span>
                                        <span class="text-sm font-bold text-gray-900"><?php echo $active_sections; ?>/<?php echo count( $all_sections ); ?></span>
                                    </div>
                                    <div class="bg-gray-200 rounded-full h-2 overflow-hidden">
                                        <div class="bg-purple-500 h-full transition-all" style="width: <?php echo $section_percentage; ?>%;"></div>
                                    </div>
                                </div>

                                <!-- Link Social -->
                                <div>
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-sm font-medium text-gray-700">Link Social</span>
                                        <span class="text-sm font-bold text-gray-900"><?php echo $active_links; ?>/<?php echo count( $social_links ); ?></span>
                                    </div>
                                    <div class="bg-gray-200 rounded-full h-2 overflow-hidden">
                                        <div class="bg-blue-500 h-full transition-all" style="width: <?php echo ( $active_links / count( $social_links ) ) * 100; ?>%;"></div>
                                    </div>
                                </div>

                                <!-- Status -->
                                <?php if ( $active_sections === count( $all_sections ) && $active_links === count( $social_links ) ) : ?>
                                    <div class="bg-green-50 border border-green-200 rounded-lg p-3">
                                        <p class="text-sm text-green-800 font-semibold">
                                            ✅ Configurazione Completa
                                        </p>
                                        <p class="text-xs text-green-700 mt-1">
                                            Tutte le sezioni attive
                                        </p>
                                    </div>
                                <?php elseif ( $active_sections === 0 ) : ?>
                                    <div class="bg-red-50 border border-red-200 rounded-lg p-3">
                                        <p class="text-sm text-red-800 font-semibold">
                                            ⚠️ Nessuna Sezione Attiva
                                        </p>
                                        <p class="text-xs text-red-700 mt-1">
                                            Attiva almeno alcune sezioni
                                        </p>
                                    </div>
                                <?php else : ?>
                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                                        <p class="text-sm text-blue-800 font-semibold">
                                            🎛️ Configurazione Personalizzata
                                        </p>
                                        <p class="text-xs text-blue-700 mt-1">
                                            <?php echo $active_sections; ?> sezioni, <?php echo $active_links; ?> link social
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Guida Tag -->
                    <div class="ipv-card">
                        <div class="ipv-card-header">
                            <h3 class="ipv-card-title text-base">
                                <svg width="20" height="20" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"></path>
                                </svg>
                                Guida Tag (Placeholder)
                            </h3>
                        </div>
                        <div class="p-6">
                            <p class="text-sm text-gray-700 mb-3">
                                I tag vengono sostituiti automaticamente nel Golden Prompt quando generi le descrizioni AI.
                            </p>

                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                                <p class="text-sm text-blue-800 font-semibold mb-2">
                                    💡 Come funziona:
                                </p>
                                <ol class="text-xs text-blue-700 space-y-1 list-decimal list-inside">
                                    <li>Il Golden Prompt contiene tag come <code>{NOME_CANALE}</code></li>
                                    <li>Tu compili i campi sopra</li>
                                    <li>Quando generi descrizione, i tag vengono sostituiti</li>
                                    <li>L'AI usa il prompt compilato</li>
                                    <li>La descrizione è personalizzata!</li>
                                </ol>
                            </div>

                            <details class="text-sm">
                                <summary class="cursor-pointer font-semibold text-gray-900 mb-2">📋 Lista Completa Tag</summary>
                                <div class="mt-2 space-y-1 text-xs font-mono bg-gray-100 p-3 rounded">
                                    <div><code>{NOME_CANALE}</code></div>
                                    <div><code>{HANDLE}</code></div>
                                    <div><code>{NICCHIA}</code></div>
                                    <div><code>{BIO_CANALE}</code></div>
                                    <div><code>{HASHTAG_CANALE}</code></div>
                                    <div><code>{EMAIL_BUSINESS}</code></div>
                                    <div><code>{LINK_YOUTUBE}</code></div>
                                    <div><code>{LINK_TELEGRAM}</code></div>
                                    <div><code>{LINK_FACEBOOK}</code></div>
                                    <div><code>{LINK_INSTAGRAM}</code></div>
                                    <div><code>{LINK_SITO}</code></div>
                                    <div><code>{LINK_DONAZIONI}</code></div>
                                    <div><code>{SPONSOR_NOME}</code></div>
                                    <div><code>{SPONSOR_DESCRIZIONE}</code></div>
                                    <div><code>{SPONSOR_LINK}</code></div>
                                </div>
                            </details>

                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <p class="text-xs text-gray-600">
                                    <strong>Esempio:</strong>
                                </p>
                                <div class="bg-gray-100 p-2 rounded mt-1 text-xs">
                                    <code>Iscriviti a {NOME_CANALE}</code><br>
                                    <span class="text-green-600">→ Iscriviti a Il Punto di Vista</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Preview (solo se ha Golden Prompt) -->
            <?php if ( $has_golden ) : ?>
            <div id="ipv-preview-modal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 9999; padding: 20px; overflow: auto;">
                <div style="max-width: 1200px; margin: 40px auto; background: white; border-radius: 8px; padding: 24px; position: relative;">
                    <button id="ipv-close-modal" style="position: absolute; top: 16px; right: 16px; background: #ef4444; color: white; border: none; border-radius: 4px; padding: 8px 16px; cursor: pointer; font-weight: bold;">
                        ✕ Chiudi
                    </button>
                    <h2 style="margin-top: 0; font-size: 24px;">Preview Golden Prompt Compilato</h2>
                    <pre id="ipv-preview-content" style="background: #f3f4f6; padding: 20px; border-radius: 4px; overflow: auto; max-height: 600px; white-space: pre-wrap; font-family: monospace; font-size: 12px;"></pre>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Verifica licenza Golden Prompt
            $('#ipv-verify-golden-license').on('click', function() {
                var license = $('input[name="<?php echo self::GOLDEN_PROMPT_LICENSE_KEY; ?>"]').val();
                if (!license) {
                    alert('Inserisci il numero licenza');
                    return;
                }

                $(this).prop('disabled', true).text('Verifica in corso...');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ipv_verify_golden_prompt_license',
                        nonce: '<?php echo wp_create_nonce( 'ipv_ai_prompt' ); ?>',
                        license_key: license
                    },
                    success: function(response) {
                        if (response.success) {
                            alert(response.data.message);
                            location.reload();
                        } else {
                            alert('Errore: ' + response.data.message);
                            $('#ipv-verify-golden-license').prop('disabled', false).text('Verifica e Attiva');
                        }
                    },
                    error: function() {
                        alert('Errore di connessione');
                        $('#ipv-verify-golden-license').prop('disabled', false).text('Verifica e Attiva');
                    }
                });
            });

            // Disattiva Golden Prompt
            $('#ipv-deactivate-golden-license').on('click', function() {
                if (!confirm('Sei sicuro di voler disattivare il Golden Prompt?')) {
                    return;
                }
                // Implementare API call per disattivare
                alert('Funzionalità in sviluppo');
            });

            // ✅ Toggle All Checkboxes ON
            $('#ipv-toggle-all-on').on('click', function() {
                $('.ipv-card input[type="checkbox"]').prop('checked', true);
            });

            // ❌ Toggle All Checkboxes OFF
            $('#ipv-toggle-all-off').on('click', function() {
                $('.ipv-card input[type="checkbox"]').prop('checked', false);
            });

            // 🔄 Ripristina Default (tutti ON)
            $('#ipv-toggle-default').on('click', function() {
                $('.ipv-card input[type="checkbox"]').prop('checked', true);
            });

            // Preview prompt
            $('#ipv-preview-prompt').on('click', function() {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ipv_preview_golden_prompt',
                        nonce: '<?php echo wp_create_nonce( 'ipv_ai_prompt' ); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#ipv-preview-content').text(response.data.prompt);
                            $('#ipv-preview-modal').fadeIn();
                        } else {
                            alert('Errore: ' + response.data.message);
                        }
                    }
                });
            });

            // Chiudi modal
            $('#ipv-close-modal, #ipv-preview-modal').on('click', function(e) {
                if (e.target === this) {
                    $('#ipv-preview-modal').fadeOut();
                }
            });
        });
        </script>
        <?php
    }
}
