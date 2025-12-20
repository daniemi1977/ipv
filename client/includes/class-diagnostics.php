<?php
/**
 * IPV Production System Pro - Diagnostics Tool
 *
 * Tool di diagnostica per troubleshooting connessione server e problemi API
 *
 * @package IPV_Production_System_Pro
 * @version 10.0.8
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_Diagnostics {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init() {
        add_action( 'admin_menu', [ $this, 'register_menu' ] );
        add_action( 'wp_ajax_ipv_run_diagnostics', [ $this, 'ajax_run_diagnostics' ] );
    }

    /**
     * Register diagnostics menu
     */
    public function register_menu() {
        add_submenu_page(
            'edit.php?post_type=ipv_video',
            __( 'Diagnostica Sistema', 'ipv-production-system-pro' ),
            __( 'Diagnostica', 'ipv-production-system-pro' ),
            'manage_options',
            'ipv-diagnostics',
            [ $this, 'render_page' ]
        );
    }

    /**
     * Render diagnostics page (v10.2.7 - AJAX based)
     */
    public function render_page() {
        ?>
        <div class="wrap">
            <h1>🔍 Diagnostica Sistema IPV</h1>
            <p>Questo strumento verifica la configurazione e la connessione al server IPV Pro Vendor.</p>

            <button type="button" class="button button-primary" id="ipv-run-diagnostics">
                <span class="dashicons dashicons-search" style="vertical-align: middle;"></span>
                Esegui Diagnostica
            </button>
            <span id="ipv-diagnostics-spinner" class="spinner" style="float: none; margin-left: 10px;"></span>

            <hr>

            <div id="ipv-diagnostics-results"></div>

            <div style="margin-top: 30px; padding: 15px; background: #f0f0f1; border-left: 4px solid #2271b1;">
                <h3>ℹ️ Cosa Controlla Questo Tool</h3>
                <ol>
                    <li><strong>Configurazione Locale</strong> - License key e server URL</li>
                    <li><strong>Server Raggiungibilità</strong> - Se il server risponde</li>
                    <li><strong>Validazione Licenza</strong> - Se la licenza è valida</li>
                    <li><strong>Crediti Disponibili</strong> - Quanti crediti rimangono</li>
                    <li><strong>Test SupaData</strong> - Info sulle API trascrizione</li>
                    <li><strong>Test YouTube Data API</strong> - Se i dati video vengono recuperati</li>
                </ol>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#ipv-run-diagnostics').on('click', function() {
                var $btn = $(this);
                var $spinner = $('#ipv-diagnostics-spinner');
                var $results = $('#ipv-diagnostics-results');
                
                $btn.prop('disabled', true);
                $spinner.addClass('is-active');
                $results.html('<p>⏳ Esecuzione diagnostica in corso...</p>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ipv_run_diagnostics',
                        nonce: '<?php echo wp_create_nonce( "ipv_diagnostics_nonce" ); ?>'
                    },
                    success: function(response) {
                        $btn.prop('disabled', false);
                        $spinner.removeClass('is-active');
                        
                        if (response.success) {
                            $results.html(response.data.html);
                        } else {
                            $results.html('<div class="notice notice-error"><p>❌ Errore: ' + (response.data || 'Sconosciuto') + '</p></div>');
                        }
                    },
                    error: function(xhr, status, error) {
                        $btn.prop('disabled', false);
                        $spinner.removeClass('is-active');
                        $results.html('<div class="notice notice-error"><p>❌ Errore AJAX: ' + error + '</p></div>');
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * AJAX: Run diagnostics (v10.2.7)
     */
    public function ajax_run_diagnostics() {
        check_ajax_referer( 'ipv_diagnostics_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Non autorizzato' );
        }

        $results = [
            'timestamp' => current_time( 'mysql' ),
            'tests' => []
        ];

        // Test 1: Check local configuration
        $results['tests'][] = $this->test_local_config();

        // Test 2: Check server health
        $results['tests'][] = $this->test_server_health();

        // Test 3: Validate license
        $results['tests'][] = $this->test_license_validation();

        // Test 4: Check credits
        $results['tests'][] = $this->test_credits();

        // Test 5: Test SupaData API
        $results['tests'][] = $this->test_supadata_api();

        // Test 6: Test YouTube Data API
        $results['tests'][] = $this->test_youtube_api();

        // Generate HTML
        ob_start();
        $this->render_results( $results );
        $html = ob_get_clean();

        wp_send_json_success( [ 'html' => $html ] );
    }

    /**
     * Test 1: Local configuration
     */
    private function test_local_config() {
        $test = [
            'name' => 'Configurazione Locale',
            'status' => 'success',
            'messages' => []
        ];

        // Check license key
        $license_key = get_option( 'ipv_license_key', '' );
        if ( empty( $license_key ) ) {
            $test['status'] = 'error';
            $test['messages'][] = '❌ License key NON configurata! Vai su IPV Videos → Licenza per configurarla.';
        } else {
            $test['messages'][] = '✅ License key configurata: ' . substr( $license_key, 0, 8 ) . '...' . substr( $license_key, -4 );
        }

        // Check server URL
        $server_url = get_option( 'ipv_api_server_url', '' );
        if ( empty( $server_url ) ) {
            $test['status'] = 'error';
            $test['messages'][] = '❌ Server URL NON configurato! Vai su IPV Videos → Impostazioni → Server per configurarlo.';
        } else {
            $test['messages'][] = '✅ Server URL configurato: ' . $server_url;
        }

        // Check license info cache
        $license_info = get_option( 'ipv_license_info', [] );
        if ( ! empty( $license_info ) && isset( $license_info['status'] ) ) {
            $test['messages'][] = '✅ License info cache: Status = ' . $license_info['status'];
        } else {
            $test['messages'][] = '⚠️ License info cache vuota (normale se licenza mai validata)';
        }

        return $test;
    }

    /**
     * Test 2: Server health check
     */
    private function test_server_health() {
        $test = [
            'name' => 'Server Raggiungibilità',
            'status' => 'unknown',
            'messages' => []
        ];

        $server_url = get_option( 'ipv_api_server_url', '' );
        if ( empty( $server_url ) ) {
            $test['status'] = 'error';
            $test['messages'][] = '❌ Impossibile testare: server URL non configurato';
            return $test;
        }

        $health_url = rtrim( $server_url, '/' ) . '/wp-json/ipv-vendor/v1/health';
        $test['messages'][] = '🔍 Testando: ' . $health_url;

        $response = wp_remote_get( $health_url, [ 'timeout' => 10 ] );

        if ( is_wp_error( $response ) ) {
            $test['status'] = 'error';
            $test['messages'][] = '❌ Errore connessione: ' . $response->get_error_message();
            $test['messages'][] = '💡 Possibili cause:';
            $test['messages'][] = '   - Server URL errato';
            $test['messages'][] = '   - Server offline';
            $test['messages'][] = '   - Firewall blocca la connessione';
            $test['messages'][] = '   - Plugin IPV Pro Vendor non attivo sul server';
            return $test;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $status_code === 200 && isset( $body['status'] ) && $body['status'] === 'ok' ) {
            $test['status'] = 'success';
            $test['messages'][] = '✅ Server raggiungibile!';
            if ( isset( $body['version'] ) ) {
                $test['messages'][] = '✅ Versione server: ' . $body['version'];
            }
            if ( isset( $body['service'] ) ) {
                $test['messages'][] = '✅ Service: ' . $body['service'];
            }
        } else {
            $test['status'] = 'error';
            $test['messages'][] = '❌ Server risponde ma con errore';
            $test['messages'][] = '   HTTP Status: ' . $status_code;
            $test['messages'][] = '   Response: ' . wp_json_encode( $body );
        }

        return $test;
    }

    /**
     * Test 3: License validation
     */
    private function test_license_validation() {
        $test = [
            'name' => 'Validazione Licenza',
            'status' => 'unknown',
            'messages' => []
        ];

        $license_key = get_option( 'ipv_license_key', '' );
        if ( empty( $license_key ) ) {
            $test['status'] = 'error';
            $test['messages'][] = '❌ Impossibile testare: license key non configurata';
            return $test;
        }

        $api_client = IPV_Prod_API_Client_Optimized::instance();

        // Try to validate license via server
        $server_url = get_option( 'ipv_api_server_url', '' );
        if ( empty( $server_url ) ) {
            $test['status'] = 'error';
            $test['messages'][] = '❌ Impossibile testare: server URL non configurato';
            return $test;
        }

        $validate_url = rtrim( $server_url, '/' ) . '/wp-json/ipv-vendor/v1/license/validate';
        $test['messages'][] = '🔍 Validando licenza su: ' . $validate_url;

        $response = wp_remote_post( $validate_url, [
            'timeout' => 15,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $license_key,
                'X-License-Key' => $license_key
            ],
            'body' => wp_json_encode([
                'license_key' => $license_key,
                'site_url' => home_url()
            ])
        ]);

        if ( is_wp_error( $response ) ) {
            $test['status'] = 'error';
            $test['messages'][] = '❌ Errore chiamata: ' . $response->get_error_message();
            return $test;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $status_code === 200 && isset( $body['success'] ) && $body['success'] ) {
            $test['status'] = 'success';
            $test['messages'][] = '✅ Licenza VALIDA!';

            if ( isset( $body['license'] ) ) {
                $license = $body['license'];
                $test['messages'][] = '   - Product: ' . ( $license['product_name'] ?? 'N/A' );
                $test['messages'][] = '   - Status: ' . ( $license['status'] ?? 'N/A' );
                $test['messages'][] = '   - Expiry: ' . ( $license['expiry_date'] ?? 'Never' );
                $test['messages'][] = '   - Site: ' . ( $license['site_url'] ?? 'N/A' );
            }
        } elseif ( $status_code === 401 ) {
            $test['status'] = 'error';
            $test['messages'][] = '❌ Licenza NON VALIDA (401 Unauthorized)';
            $test['messages'][] = '   Messaggio: ' . ( $body['message'] ?? 'Sconosciuto' );
            $test['messages'][] = '💡 Possibili cause:';
            $test['messages'][] = '   - License key errata o scaduta';
            $test['messages'][] = '   - Licenza non attivata per questo dominio';
            $test['messages'][] = '   - Server non riesce a validare la licenza (problema database)';
        } else {
            $test['status'] = 'error';
            $test['messages'][] = '❌ Errore validazione licenza';
            $test['messages'][] = '   HTTP Status: ' . $status_code;
            $test['messages'][] = '   Response: ' . wp_json_encode( $body );
        }

        return $test;
    }

    /**
     * Test 4: Check credits
     */
    private function test_credits() {
        $test = [
            'name' => 'Crediti Disponibili',
            'status' => 'unknown',
            'messages' => []
        ];

        if ( ! IPV_Prod_API_Client_Optimized::is_license_active() ) {
            $test['status'] = 'warning';
            $test['messages'][] = '⚠️ Impossibile testare: licenza non attiva';
            return $test;
        }

        $api_client = IPV_Prod_API_Client_Optimized::instance();

        $server_url = get_option( 'ipv_api_server_url', '' );
        $license_key = get_option( 'ipv_license_key', '' );

        if ( empty( $server_url ) || empty( $license_key ) ) {
            $test['status'] = 'error';
            $test['messages'][] = '❌ Configurazione incompleta';
            return $test;
        }

        $credits_url = rtrim( $server_url, '/' ) . '/wp-json/ipv-vendor/v1/credits';

        $response = wp_remote_get( $credits_url, [
            'timeout' => 10,
            'headers' => [
                'Authorization' => 'Bearer ' . $license_key,
                'X-License-Key' => $license_key
            ]
        ]);

        if ( is_wp_error( $response ) ) {
            $test['status'] = 'error';
            $test['messages'][] = '❌ Errore: ' . $response->get_error_message();
            return $test;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $status_code === 200 && isset( $body['success'] ) && $body['success'] ) {
            $credits = $body['credits'] ?? [];
            $remaining = $credits['credits_remaining'] ?? 0;
            $total = $credits['credits_total'] ?? 0;

            if ( $remaining > 0 ) {
                $test['status'] = 'success';
                $test['messages'][] = '✅ Crediti disponibili: ' . $remaining . '/' . $total;
            } else {
                $test['status'] = 'warning';
                $test['messages'][] = '⚠️ Crediti esauriti: 0/' . $total;
            }

            if ( isset( $credits['credits_reset_date'] ) ) {
                $test['messages'][] = '   Reset: ' . $credits['credits_reset_date'];
            }
        } else {
            $test['status'] = 'error';
            $test['messages'][] = '❌ Errore recupero crediti';
            $test['messages'][] = '   HTTP Status: ' . $status_code;
        }

        return $test;
    }

    /**
     * Test 5: Test SupaData API
     */
    private function test_supadata_api() {
        $test = [
            'name' => 'Test SupaData API',
            'status' => 'unknown',
            'messages' => []
        ];

        if ( ! IPV_Prod_API_Client_Optimized::is_license_active() ) {
            $test['status'] = 'warning';
            $test['messages'][] = '⚠️ Impossibile testare: licenza non attiva';
            return $test;
        }

        $test['messages'][] = '⏭️ Test SupaData saltato (richiede video_id reale e scala crediti)';
        $test['messages'][] = '💡 Per testare SupaData:';
        $test['messages'][] = '   1. Vai su un video esistente';
        $test['messages'][] = '   2. Clicca "Rigenera Trascrizione"';
        $test['messages'][] = '   3. Controlla se funziona';
        $test['status'] = 'info';

        return $test;
    }

    /**
     * Test 6: Test YouTube Data API (v10.2.5)
     */
    private function test_youtube_api() {
        $test = [
            'name' => 'Test YouTube Data API',
            'status' => 'unknown',
            'messages' => []
        ];

        if ( ! IPV_Prod_API_Client_Optimized::is_license_active() ) {
            $test['status'] = 'warning';
            $test['messages'][] = '⚠️ Impossibile testare: licenza non attiva';
            return $test;
        }

        $server_url = get_option( 'ipv_api_server_url', '' );
        $license_key = get_option( 'ipv_license_key', '' );

        if ( empty( $server_url ) || empty( $license_key ) ) {
            $test['status'] = 'error';
            $test['messages'][] = '❌ Configurazione incompleta';
            return $test;
        }

        // Test con video noto (Rick Astley - Never Gonna Give You Up)
        $test_video_id = 'dQw4w9WgXcQ';
        $api_url = rtrim( $server_url, '/' ) . '/wp-json/ipv-vendor/v1/youtube/video-data';

        $test['messages'][] = '🔍 Testando endpoint: ' . $api_url;
        $test['messages'][] = '🎬 Video test: ' . $test_video_id;

        $response = wp_remote_post( $api_url, [
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $license_key,
                'X-License-Key' => $license_key
            ],
            'body' => wp_json_encode([
                'video_id' => $test_video_id,
                'license_key' => $license_key
            ])
        ]);

        if ( is_wp_error( $response ) ) {
            $test['status'] = 'error';
            $test['messages'][] = '❌ Errore connessione: ' . $response->get_error_message();
            return $test;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $status_code === 200 && isset( $body['success'] ) && $body['success'] ) {
            $video_data = $body['video_data'] ?? [];
            $title = $video_data['title'] ?? 'N/A';
            
            $test['status'] = 'success';
            $test['messages'][] = '✅ YouTube API funziona!';
            $test['messages'][] = '   Titolo video: ' . $title;
        } elseif ( $status_code === 500 && isset( $body['code'] ) && $body['code'] === 'youtube_key_missing' ) {
            $test['status'] = 'error';
            $test['messages'][] = '❌ YouTube API Key NON configurata sul server!';
            $test['messages'][] = '💡 Soluzione:';
            $test['messages'][] = '   1. Vai su bissolomarket.com → IPV Pro Vendor → Impostazioni';
            $test['messages'][] = '   2. Inserisci la YouTube Data API v3 Key';
            $test['messages'][] = '   3. Riprova';
        } elseif ( $status_code === 403 ) {
            $test['status'] = 'error';
            $test['messages'][] = '❌ Quota YouTube API esaurita';
            $test['messages'][] = '💡 Aspetta 24 ore o usa una nuova API Key';
        } elseif ( $status_code === 401 ) {
            $test['status'] = 'error';
            $test['messages'][] = '❌ Licenza non valida per questo endpoint';
            $test['messages'][] = '   Response: ' . wp_json_encode( $body );
        } else {
            $test['status'] = 'error';
            $test['messages'][] = '❌ Errore sconosciuto';
            $test['messages'][] = '   HTTP Status: ' . $status_code;
            $test['messages'][] = '   Response: ' . substr( wp_json_encode( $body ), 0, 200 );
        }

        return $test;
    }

    /**
     * Render diagnostic results
     */
    private function render_results( $results ) {
        ?>
        <div style="margin-top: 20px;">
            <h2>📊 Risultati Diagnostica</h2>
            <p><strong>Timestamp:</strong> <?php echo esc_html( $results['timestamp'] ); ?></p>

            <?php foreach ( $results['tests'] as $test ) : ?>
                <div style="margin: 20px 0; padding: 15px; border: 2px solid <?php
                    echo $test['status'] === 'success' ? '#46b450' :
                         ($test['status'] === 'error' ? '#dc3232' :
                         ($test['status'] === 'warning' ? '#ffb900' : '#72aee6'));
                ?>; border-radius: 4px; background: #fff;">
                    <h3 style="margin-top: 0;">
                        <?php
                        if ( $test['status'] === 'success' ) echo '✅';
                        elseif ( $test['status'] === 'error' ) echo '❌';
                        elseif ( $test['status'] === 'warning' ) echo '⚠️';
                        else echo 'ℹ️';
                        ?>
                        <?php echo esc_html( $test['name'] ); ?>
                    </h3>
                    <?php foreach ( $test['messages'] as $message ) : ?>
                        <div style="margin: 5px 0; font-family: monospace;">
                            <?php echo esc_html( $message ); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>

            <div style="margin-top: 30px; padding: 15px; background: #e7f5fe; border-left: 4px solid #2271b1;">
                <h3>🔧 Prossimi Passi</h3>
                <?php
                $has_errors = false;
                foreach ( $results['tests'] as $test ) {
                    if ( $test['status'] === 'error' ) {
                        $has_errors = true;
                        break;
                    }
                }

                if ( $has_errors ) : ?>
                    <p><strong>Risolvi gli errori sopra prima di procedere:</strong></p>
                    <ol>
                        <li>Se manca <strong>license key</strong>: Vai su IPV Videos → Licenza</li>
                        <li>Se manca <strong>server URL</strong>: Vai su IPV Videos → Impostazioni → Server</li>
                        <li>Se il server non risponde: Verifica che IPV Pro Vendor sia attivo sul server</li>
                        <li>Se la licenza non è valida: Controlla che sia attivata per questo dominio</li>
                    </ol>
                <?php else : ?>
                    <p>✅ <strong>Tutto OK!</strong> Il sistema è configurato correttamente.</p>
                    <p>Se continui ad avere problemi con SupaData o altre API:</p>
                    <ol>
                        <li>Verifica che il server abbia la versione <strong>v1.3.1 o successiva</strong></li>
                        <li>Controlla i log del server in: <code>/wp-content/debug.log</code></li>
                        <li>Contatta il supporto con questi risultati diagnostici</li>
                    </ol>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}
