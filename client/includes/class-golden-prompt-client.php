<?php
/**
 * IPV Golden Prompt Client - SEMPRE REMOTO
 *
 * Recupera il Golden Prompt dal server vendor ogni volta che serve.
 * IL GOLDEN PROMPT NON VIENE MAI SALVATO LOCALMENTE!
 *
 * WORKFLOW (secondo schema):
 * 1. Client configura dati + flags nel suo pannello
 * 2. Client POST config al vendor → vendor compila e salva
 * 3. Quando serve AI → Client GET remoto dal vendor
 * 4. Client usa GP in memoria → scarta dopo uso
 *
 * @package IPV_Production_System_Pro
 * @since 10.5.0
 * @version 1.0.1 - Rimosso salvataggio locale, sempre GET remoto
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Pro_Golden_Prompt_Client {

    private static $instance = null;
    private $vendor_url;
    private $license_key;

    /**
     * Cache in memoria (non persistente) per evitare chiamate multiple nella stessa request
     */
    private static $memory_cache = null;
    private static $memory_cache_hash = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->vendor_url = defined( 'IPV_VENDOR_URL' ) ? IPV_VENDOR_URL : 'https://aiedintorni.it';
        $this->license_key = get_option( 'ipv_pro_license_key', '' );

        // Admin AJAX per check status
        add_action( 'wp_ajax_ipv_check_vendor_golden_prompt', [ $this, 'ajax_check_status' ] );
    }

    /**
     * GET Golden Prompt dal vendor (SEMPRE remoto, mai da cache locale)
     *
     * @return string|null Golden Prompt text o null se non disponibile
     */
    public function get_golden_prompt() {
        // Check cache in memoria (stessa request PHP)
        if ( self::$memory_cache !== null ) {
            return self::$memory_cache;
        }

        // Refresh license key
        $this->license_key = get_option( 'ipv_pro_license_key', '' );

        if ( empty( $this->license_key ) ) {
            if ( class_exists( 'IPV_Prod_Logger' ) ) {
                IPV_Prod_Logger::log( 'Golden Prompt: Nessuna licenza configurata' );
            }
            return null;
        }

        // GET remoto dal vendor
        $response = wp_remote_get( trailingslashit( $this->vendor_url ) . 'wp-json/ipv-vendor/v1/golden-prompt', [
            'timeout' => 30,
            'headers' => [
                'X-License-Key' => $this->license_key
            ]
        ]);

        if ( is_wp_error( $response ) ) {
            if ( class_exists( 'IPV_Prod_Logger' ) ) {
                IPV_Prod_Logger::log( 'Golden Prompt GET error: ' . $response->get_error_message() );
            }
            return null;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code !== 200 ) {
            if ( class_exists( 'IPV_Prod_Logger' ) ) {
                IPV_Prod_Logger::log( 'Golden Prompt GET HTTP error: ' . $code );
            }
            return null;
        }

        if ( ! $body['success'] || ! $body['has_golden_prompt'] || empty( $body['golden_prompt'] ) ) {
            if ( class_exists( 'IPV_Prod_Logger' ) ) {
                IPV_Prod_Logger::log( 'Golden Prompt: Non configurato per questa licenza' );
            }
            return null;
        }

        // Salva in cache memoria (NON persistente - solo per questa request)
        self::$memory_cache = $body['golden_prompt'];
        self::$memory_cache_hash = $body['hash'] ?? md5( $body['golden_prompt'] );

        if ( class_exists( 'IPV_Prod_Logger' ) ) {
            IPV_Prod_Logger::log( 'Golden Prompt: Recuperato con successo dal vendor (remoto)' );
        }

        return self::$memory_cache;
    }

    /**
     * Verifica se esiste un Golden Prompt per questa licenza (check remoto)
     *
     * @return bool
     */
    public function has_golden_prompt() {
        // Se già in cache memoria, esiste
        if ( self::$memory_cache !== null ) {
            return true;
        }

        // Refresh license key
        $this->license_key = get_option( 'ipv_pro_license_key', '' );

        if ( empty( $this->license_key ) ) {
            return false;
        }

        // Check remoto leggero (solo hash, non tutto il GP)
        $response = wp_remote_get( trailingslashit( $this->vendor_url ) . 'wp-json/ipv-vendor/v1/golden-prompt/check', [
            'timeout' => 15,
            'headers' => [
                'X-License-Key' => $this->license_key
            ]
        ]);

        if ( is_wp_error( $response ) ) {
            return false;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code !== 200 ) {
            return false;
        }

        return ! empty( $body['has_golden_prompt'] ) && $body['has_golden_prompt'] === true;
    }

    /**
     * Ottieni l'hash del Golden Prompt (per comparazioni)
     *
     * @return string|null
     */
    public function get_hash() {
        if ( self::$memory_cache_hash !== null ) {
            return self::$memory_cache_hash;
        }

        // Refresh license key
        $this->license_key = get_option( 'ipv_pro_license_key', '' );

        if ( empty( $this->license_key ) ) {
            return null;
        }

        $response = wp_remote_get( trailingslashit( $this->vendor_url ) . 'wp-json/ipv-vendor/v1/golden-prompt/hash', [
            'timeout' => 15,
            'headers' => [
                'X-License-Key' => $this->license_key
            ]
        ]);

        if ( is_wp_error( $response ) ) {
            return null;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        return $body['hash'] ?? null;
    }

    /**
     * Invalida la cache in memoria (forza nuovo fetch alla prossima chiamata)
     */
    public function invalidate_cache() {
        self::$memory_cache = null;
        self::$memory_cache_hash = null;
    }

    /**
     * AJAX: Check status (per UI admin)
     */
    public function ajax_check_status() {
        check_ajax_referer( 'ipv_golden_prompt_sync', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permessi insufficienti' );
        }

        wp_send_json_success( [
            'has_golden_prompt' => $this->has_golden_prompt(),
            'hash' => $this->get_hash(),
            'mode' => 'always_remote' // Indica che usa sempre GET remoto
        ] );
    }

    /**
     * Render info box per la pagina Golden Prompt
     */
    public static function render_vendor_status_box() {
        $client = self::instance();
        $has_vendor = $client->has_golden_prompt();
        $hash = $client->get_hash();

        ?>
        <div class="ipv-vendor-gp-status" style="background: <?php echo $has_vendor ? '#d4edda' : '#fff3cd'; ?>; border: 1px solid <?php echo $has_vendor ? '#c3e6cb' : '#ffeeba'; ?>; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
            <h3 style="margin-top: 0; color: <?php echo $has_vendor ? '#155724' : '#856404'; ?>;">
                <?php echo $has_vendor ? '✅ Golden Prompt Attivo (Remoto)' : '⚠️ Nessun Golden Prompt Configurato'; ?>
            </h3>

            <?php if ( $has_vendor ) : ?>
                <p style="color: #155724; margin: 10px 0;">
                    <strong>Il tuo canale ha un Golden Prompt personalizzato.</strong><br>
                    Il prompt viene sempre recuperato dal server vendor quando serve (mai salvato localmente).
                </p>
                <p style="color: #666; font-size: 13px;">
                    <strong>Hash:</strong> <?php echo $hash ? substr( $hash, 0, 16 ) . '...' : 'N/A'; ?><br>
                    <strong>Modalità:</strong> Sempre Remoto (sicuro)
                </p>
            <?php else : ?>
                <p style="color: #856404; margin: 10px 0;">
                    Il vendor non ha ancora configurato un Golden Prompt per la tua licenza.<br>
                    Configura i tuoi dati e clicca "Genera Golden Prompt" per crearne uno.
                </p>
            <?php endif; ?>

            <div style="margin-top: 15px; background: #e7f3ff; border: 1px solid #b8daff; border-radius: 4px; padding: 10px;">
                <p style="margin: 0; font-size: 12px; color: #004085;">
                    <strong>🔐 Sicurezza:</strong> Il Golden Prompt esiste SOLO sul server vendor.
                    Non viene mai salvato sul tuo sito. Ogni richiesta AI lo recupera in tempo reale.
                </p>
            </div>
        </div>
        <?php
    }
}

/**
 * Helper function per ottenere il Golden Prompt (sempre remoto)
 * Da usare SOLO internamente nel codice del plugin
 */
function ipv_get_golden_prompt() {
    return IPV_Pro_Golden_Prompt_Client::instance()->get_golden_prompt();
}

/**
 * Helper function per verificare se esiste un Golden Prompt (check remoto)
 */
function ipv_has_golden_prompt() {
    return IPV_Pro_Golden_Prompt_Client::instance()->has_golden_prompt();
}

/**
 * Helper per ottenere l'hash del Golden Prompt
 */
function ipv_get_golden_prompt_hash() {
    return IPV_Pro_Golden_Prompt_Client::instance()->get_hash();
}

/**
 * Helper per fetch remoto Golden Prompt (usato da class-ai-prompt-config.php)
 */
function ipv_fetch_golden_prompt_remote( $license_key = null ) {
    // Se viene passata una license key specifica, usala
    // Altrimenti usa quella del client
    return IPV_Pro_Golden_Prompt_Client::instance()->get_golden_prompt();
}

/**
 * Helper per verificare licenza Golden Prompt (placeholder - da implementare se serve)
 */
function ipv_verify_golden_prompt_license( $license_key ) {
    // Per ora ritorna true se ha un GP attivo
    // In futuro potrebbe verificare una licenza separata per il GP
    return IPV_Pro_Golden_Prompt_Client::instance()->has_golden_prompt();
}
