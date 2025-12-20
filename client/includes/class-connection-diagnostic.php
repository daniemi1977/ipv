<?php
/**
 * IPV Production System Pro - Connection Diagnostic Tool
 * 
 * Diagnose 401 Unauthorized errors and license connection issues
 * 
 * @package IPV_Production_System_Pro
 * @version 10.5.2+
 * @since 10.5.2
 */

if (!defined('ABSPATH')) {
    exit;
}

class IPV_Prod_Connection_Diagnostic {

    /**
     * Initialize
     */
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_menu_page'], 99);
        add_action('wp_ajax_ipv_test_connection', [__CLASS__, 'ajax_test_connection']);
    }

    /**
     * Add menu page
     */
    public static function add_menu_page() {
        add_submenu_page(
            'edit.php?post_type=ipv_video',
            '🔍 Diagnostic 401',
            '🔍 Diagnostic',
            'manage_options',
            'ipv-diagnostic',
            [__CLASS__, 'render_page']
        );
    }

    /**
     * Render diagnostic page
     */
    public static function render_page() {
        // Get current config
        $license_key = get_option('ipv_license_key', '');
        $server_url = get_option('ipv_api_server_url', '');
        $domain = home_url();
        
        ?>
        <div class="wrap">
            <h1>🔍 Diagnostic 401 Unauthorized</h1>
            <p class="description">
                Questo tool identifica perché ricevi errori 401 sulle trascrizioni.
            </p>

            <div style="background: #fff; border: 1px solid #ccc; border-radius: 8px; padding: 20px; margin: 20px 0; max-width: 1200px;">
                
                <h2>📋 Configurazione Attuale</h2>
                
                <table class="widefat" style="margin: 20px 0;">
                    <tbody>
                        <tr>
                            <th style="width: 200px; text-align: left; padding: 12px;">Server URL</th>
                            <td style="padding: 12px;">
                                <code><?php echo esc_html($server_url ?: 'NON CONFIGURATO'); ?></code>
                                <?php if (empty($server_url)): ?>
                                    <span style="color: #dc3545; margin-left: 10px;">❌ MANCANTE</span>
                                <?php else: ?>
                                    <span style="color: #28a745; margin-left: 10px;">✅ OK</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th style="text-align: left; padding: 12px;">License Key</th>
                            <td style="padding: 12px;">
                                <?php if (!empty($license_key)): ?>
                                    <code><?php echo substr($license_key, 0, 10) . '...' . substr($license_key, -5); ?></code>
                                    <span style="color: #28a745; margin-left: 10px;">✅ PRESENTE</span>
                                <?php else: ?>
                                    <code>NON CONFIGURATA</code>
                                    <span style="color: #dc3545; margin-left: 10px;">❌ MANCANTE</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th style="text-align: left; padding: 12px;">Dominio Client</th>
                            <td style="padding: 12px;">
                                <code><?php echo esc_html(parse_url($domain, PHP_URL_HOST)); ?></code>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <?php if (empty($license_key) || empty($server_url)): ?>
                    <div style="background: #fff3cd; border: 2px solid #ffc107; border-radius: 6px; padding: 15px; margin: 20px 0;">
                        <strong>⚠️ CONFIGURAZIONE INCOMPLETA</strong>
                        <p>Per procedere, devi prima:</p>
                        <ol>
                            <li>Vai su <strong>IPV Videos → Licenza</strong></li>
                            <li>Inserisci Server URL (es: https://tuo-vendor.com)</li>
                            <li>Inserisci License Key</li>
                            <li>Clicca "Salva e Valida"</li>
                            <li>Torna qui per testare</li>
                        </ol>
                    </div>
                <?php else: ?>
                    
                    <h2>🧪 Test Connessione</h2>
                    
                    <button 
                        class="button button-primary button-hero" 
                        id="start-diagnostic"
                        style="margin: 20px 0; padding: 10px 30px;"
                    >
                        🚀 Avvia Diagnostic Completo
                    </button>
                    
                    <div id="diagnostic-results" style="margin-top: 20px; display: none;">
                        <!-- Results will be inserted here -->
                    </div>

                <?php endif; ?>

            </div>

            <div style="background: #f0f0f0; border-radius: 8px; padding: 20px; margin: 20px 0; max-width: 1200px;">
                <h3>💡 Errori Comuni 401</h3>
                
                <details style="margin: 15px 0;">
                    <summary style="cursor: pointer; font-weight: bold; padding: 10px; background: white; border-radius: 4px;">
                        ❌ License Key Invalida
                    </summary>
                    <div style="padding: 15px; background: white; margin-top: 5px; border-radius: 4px;">
                        <p><strong>Sintomo:</strong> Errore "unauthorized" subito dopo configurazione</p>
                        <p><strong>Causa:</strong> License key copiata male, caratteri mancanti, spazi extra</p>
                        <p><strong>Fix:</strong></p>
                        <ol>
                            <li>Copia nuovamente la license key dal vendor</li>
                            <li>Assicurati di copiare TUTTA la stringa (es: IPV-XXXXXXXXXXXX)</li>
                            <li>NON aggiungere spazi prima/dopo</li>
                            <li>Incolla su IPV Videos → Licenza</li>
                            <li>Salva e testa</li>
                        </ol>
                    </div>
                </details>

                <details style="margin: 15px 0;">
                    <summary style="cursor: pointer; font-weight: bold; padding: 10px; background: white; border-radius: 4px;">
                        ❌ Dominio Non Match
                    </summary>
                    <div style="padding: 15px; background: white; margin-top: 5px; border-radius: 4px;">
                        <p><strong>Sintomo:</strong> 401 anche con license key corretta</p>
                        <p><strong>Causa:</strong> License registrata per altro dominio</p>
                        <p><strong>Fix:</strong></p>
                        <ol>
                            <li>Verifica dominio attuale: <code><?php echo parse_url($domain, PHP_URL_HOST); ?></code></li>
                            <li>Sul vendor, controlla dominio registrato per questa licenza</li>
                            <li>Se diverso:
                                <ul>
                                    <li>Opzione A: Richiedi cambio dominio (1x/anno automatico)</li>
                                    <li>Opzione B: Usa licenza corretta per questo dominio</li>
                                </ul>
                            </li>
                        </ol>
                    </div>
                </details>

                <details style="margin: 15px 0;">
                    <summary style="cursor: pointer; font-weight: bold; padding: 10px; background: white; border-radius: 4px;">
                        ❌ License Scaduta/Sospesa
                    </summary>
                    <div style="padding: 15px; background: white; margin-top: 5px; border-radius: 4px;">
                        <p><strong>Sintomo:</strong> Funzionava prima, ora 401</p>
                        <p><strong>Causa:</strong> License scaduta o sospesa per mancato pagamento</p>
                        <p><strong>Fix:</strong></p>
                        <ol>
                            <li>Controlla stato licenza sul vendor</li>
                            <li>Verifica data scadenza</li>
                            <li>Verifica pagamenti in sospeso</li>
                            <li>Rinnova se necessario</li>
                        </ol>
                    </div>
                </details>

                <details style="margin: 15px 0;">
                    <summary style="cursor: pointer; font-weight: bold; padding: 10px; background: white; border-radius: 4px;">
                        ❌ Server URL Errato
                    </summary>
                    <div style="padding: 15px; background: white; margin-top: 5px; border-radius: 4px;">
                        <p><strong>Sintomo:</strong> Errore di connessione o 401</p>
                        <p><strong>Causa:</strong> URL vendor sbagliato</p>
                        <p><strong>Fix:</strong></p>
                        <ol>
                            <li>Verifica URL corretto (es: https://tuo-vendor.com)</li>
                            <li>NO trailing slash: https://vendor.com/ ❌</li>
                            <li>SI formato corretto: https://vendor.com ✅</li>
                            <li>Deve includere https://</li>
                        </ol>
                    </div>
                </details>

            </div>
        </div>

        <style>
        details summary {
            transition: background 0.2s;
        }
        details summary:hover {
            background: #f5f5f5 !important;
        }
        details[open] summary {
            margin-bottom: 10px;
            border-bottom: 2px solid #0073aa;
        }
        </style>

        <script>
        jQuery(document).ready(function($) {
            
            $('#start-diagnostic').on('click', function() {
                const btn = $(this);
                const results = $('#diagnostic-results');
                
                btn.prop('disabled', true).text('🔄 Testing...');
                results.html('<div style="padding: 20px; text-align: center;"><span class="spinner is-active" style="float: none; margin: 0;"></span><br><br>Esecuzione test...</div>').show();
                
                // Test sequence
                const tests = [
                    { name: 'license_validation', label: 'Validazione Licenza' },
                    { name: 'server_reachable', label: 'Connessione Server' },
                    { name: 'domain_check', label: 'Verifica Dominio' },
                    { name: 'credits_check', label: 'Controllo Crediti' },
                    { name: 'transcript_test', label: 'Test Transcript API' }
                ];
                
                let html = '<div style="background: white; border: 1px solid #ccc; border-radius: 8px; padding: 20px;">';
                html += '<h3>📊 Risultati Test</h3>';
                html += '<div id="test-progress"></div>';
                html += '</div>';
                results.html(html);
                
                // Run tests sequentially
                runTests(tests, 0);
                
                function runTests(tests, index) {
                    if (index >= tests.length) {
                        btn.prop('disabled', false).text('🚀 Avvia Diagnostic Completo');
                        return;
                    }
                    
                    const test = tests[index];
                    const progress = $('#test-progress');
                    
                    progress.append(`
                        <div id="test-${test.name}" style="padding: 15px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px;">
                            <strong>${index + 1}. ${test.label}</strong>
                            <span class="spinner is-active" style="float: right;"></span>
                        </div>
                    `);
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'ipv_test_connection',
                            test: test.name,
                            nonce: '<?php echo wp_create_nonce('ipv_diagnostic'); ?>'
                        },
                        success: function(response) {
                            const testDiv = $(`#test-${test.name}`);
                            testDiv.find('.spinner').remove();
                            
                            if (response.success) {
                                testDiv.css('background', '#d4edda').css('border-color', '#28a745');
                                testDiv.append(`
                                    <div style="margin-top: 10px; color: #155724;">
                                        ✅ ${response.data.message}
                                    </div>
                                `);
                            } else {
                                testDiv.css('background', '#f8d7da').css('border-color', '#dc3545');
                                testDiv.append(`
                                    <div style="margin-top: 10px; color: #721c24;">
                                        ❌ ${response.data.message}
                                    </div>
                                `);
                                
                                if (response.data.fix) {
                                    testDiv.append(`
                                        <div style="margin-top: 10px; padding: 10px; background: rgba(255,255,255,0.7); border-radius: 4px;">
                                            <strong>💡 Fix:</strong><br>
                                            ${response.data.fix}
                                        </div>
                                    `);
                                }
                            }
                            
                            // Continue to next test
                            setTimeout(() => runTests(tests, index + 1), 500);
                        },
                        error: function() {
                            const testDiv = $(`#test-${test.name}`);
                            testDiv.find('.spinner').remove();
                            testDiv.css('background', '#fff3cd').css('border-color', '#ffc107');
                            testDiv.append('<div style="margin-top: 10px; color: #856404;">⚠️ Test fallito (errore di rete)</div>');
                            
                            setTimeout(() => runTests(tests, index + 1), 500);
                        }
                    });
                }
            });
            
        });
        </script>
        <?php
    }

    /**
     * AJAX handler for connection tests
     */
    public static function ajax_test_connection() {
        check_ajax_referer('ipv_diagnostic', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permessi insufficienti']);
        }
        
        $test = isset($_POST['test']) ? sanitize_text_field($_POST['test']) : '';
        
        switch ($test) {
            case 'license_validation':
                self::test_license_validation();
                break;
            case 'server_reachable':
                self::test_server_reachable();
                break;
            case 'domain_check':
                self::test_domain_check();
                break;
            case 'credits_check':
                self::test_credits_check();
                break;
            case 'transcript_test':
                self::test_transcript_api();
                break;
            default:
                wp_send_json_error(['message' => 'Test non riconosciuto']);
        }
    }

    /**
     * Test 1: License Validation
     */
    private static function test_license_validation() {
        $license_key = get_option('ipv_license_key', '');
        
        if (empty($license_key)) {
            wp_send_json_error([
                'message' => 'License key non configurata',
                'fix' => 'Vai su <strong>IPV Videos → Licenza</strong> e inserisci la tua license key.'
            ]);
        }
        
        // Check format
        if (!preg_match('/^IPV-[A-Z0-9]{10,}/', $license_key)) {
            wp_send_json_error([
                'message' => 'License key formato invalido',
                'fix' => 'La license key deve iniziare con "IPV-" seguito da lettere/numeri. Copia nuovamente dal vendor.'
            ]);
        }
        
        wp_send_json_success([
            'message' => 'License key presente e formato corretto: ' . substr($license_key, 0, 10) . '...'
        ]);
    }

    /**
     * Test 2: Server Reachable
     */
    private static function test_server_reachable() {
        $server_url = get_option('ipv_api_server_url', '');
        
        if (empty($server_url)) {
            wp_send_json_error([
                'message' => 'Server URL non configurato',
                'fix' => 'Vai su <strong>IPV Videos → Licenza</strong> e inserisci l\'URL del tuo vendor.'
            ]);
        }
        
        // Try to reach server
        $health_url = rtrim($server_url, '/') . '/wp-json/ipv-vendor/v1/health';
        $response = wp_remote_get($health_url, ['timeout' => 10]);
        
        if (is_wp_error($response)) {
            wp_send_json_error([
                'message' => 'Impossibile raggiungere il server: ' . $response->get_error_message(),
                'fix' => 'Verifica che l\'URL sia corretto. Deve essere tipo: https://tuo-vendor.com (senza trailing slash)'
            ]);
        }
        
        $status = wp_remote_retrieve_response_code($response);
        
        if ($status !== 200) {
            wp_send_json_error([
                'message' => 'Server risponde ma con errore HTTP ' . $status,
                'fix' => 'Il server vendor potrebbe non essere configurato correttamente. Contatta il supporto.'
            ]);
        }
        
        wp_send_json_success([
            'message' => 'Server raggiungibile e funzionante: ' . $server_url
        ]);
    }

    /**
     * Test 3: Domain Check
     */
    private static function test_domain_check() {
        $license_key = get_option('ipv_license_key', '');
        $server_url = get_option('ipv_api_server_url', '');
        $current_domain = parse_url(home_url(), PHP_URL_HOST);
        
        // Call license validation endpoint
        $url = rtrim($server_url, '/') . '/wp-json/ipv-vendor/v1/license/validate';
        
        $response = wp_remote_post($url, [
            'timeout' => 10,
            'headers' => [
                'Authorization' => 'Bearer ' . $license_key,
                'X-Site-URL' => home_url(),
                'Content-Type' => 'application/json'
            ],
            'body' => wp_json_encode([
                'license_key' => $license_key,
                'domain' => $current_domain
            ])
        ]);
        
        if (is_wp_error($response)) {
            wp_send_json_error([
                'message' => 'Errore di connessione: ' . $response->get_error_message(),
                'fix' => 'Verifica connessione internet e firewall.'
            ]);
        }
        
        $status = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($status === 401) {
            $error_msg = $body['message'] ?? 'unauthorized';
            
            if (strpos($error_msg, 'domain') !== false) {
                wp_send_json_error([
                    'message' => 'DOMAIN MISMATCH: La licenza è registrata per un altro dominio',
                    'fix' => 'Dominio attuale: <code>' . $current_domain . '</code><br>' .
                            'Contatta il supporto per cambiare dominio oppure usa la licenza corretta per questo sito.'
                ]);
            }
            
            wp_send_json_error([
                'message' => 'License non autorizzata: ' . $error_msg,
                'fix' => 'Verifica che la license key sia corretta. Potrebbe essere scaduta o sospesa.'
            ]);
        }
        
        if ($status === 403) {
            wp_send_json_error([
                'message' => 'License non attiva o scaduta',
                'fix' => 'Rinnova la tua licenza sul vendor oppure contatta il supporto.'
            ]);
        }
        
        if ($status === 200 && isset($body['valid']) && $body['valid']) {
            wp_send_json_success([
                'message' => 'Dominio verificato correttamente: ' . $current_domain
            ]);
        }
        
        wp_send_json_error([
            'message' => 'Risposta inattesa dal server (HTTP ' . $status . ')',
            'fix' => 'Contatta il supporto con questo codice: HTTP' . $status
        ]);
    }

    /**
     * Test 4: Credits Check
     */
    private static function test_credits_check() {
        $license_info = get_option('ipv_license_info', []);
        
        if (empty($license_info)) {
            wp_send_json_error([
                'message' => 'Dati licenza non in cache',
                'fix' => 'Vai su <strong>IPV Videos → Licenza</strong> e clicca "Aggiorna Licenza" per sincronizzare.'
            ]);
        }
        
        $credits_remaining = $license_info['credits_remaining'] ?? 0;
        
        if ($credits_remaining <= 0) {
            wp_send_json_error([
                'message' => 'NESSUN CREDITO DISPONIBILE',
                'fix' => 'Acquista crediti extra o attendi il reset mensile (piani subscription).'
            ]);
        }
        
        wp_send_json_success([
            'message' => 'Crediti disponibili: ' . $credits_remaining
        ]);
    }

    /**
     * Test 5: Transcript API Test
     */
    private static function test_transcript_api() {
        // Use a known short video for testing
        $test_video_id = 'jNQXAC9IVRw'; // "Me at the zoo" - 19 seconds
        
        if (!class_exists('IPV_Prod_API_Client_Optimized')) {
            wp_send_json_error([
                'message' => 'API Client non disponibile',
                'fix' => 'Verifica che il plugin sia installato correttamente.'
            ]);
        }
        
        $client = IPV_Prod_API_Client_Optimized::instance();
        $result = $client->get_transcript($test_video_id, 'auto', 'en');
        
        if (is_wp_error($result)) {
            $error_code = $result->get_error_code();
            $error_msg = $result->get_error_message();
            
            if ($error_code === 'unauthorized') {
                wp_send_json_error([
                    'message' => '401 UNAUTHORIZED: ' . $error_msg,
                    'fix' => '<strong>PROBLEMA IDENTIFICATO!</strong><br>' .
                            '1. La license key O il dominio NON sono corretti sul vendor<br>' .
                            '2. Verifica su vendor admin che la licenza sia attiva per il dominio: ' . parse_url(home_url(), PHP_URL_HOST) . '<br>' .
                            '3. Se necessario, riassegna la licenza al dominio corretto<br>' .
                            '4. Aggiorna la license key sul client se cambiata'
                ]);
            }
            
            if ($error_code === 'no_license') {
                wp_send_json_error([
                    'message' => 'License non configurata',
                    'fix' => 'Torna al <strong>Test 1</strong> - devi prima configurare la licenza.'
                ]);
            }
            
            wp_send_json_error([
                'message' => 'Errore API: ' . $error_msg . ' (Codice: ' . $error_code . ')',
                'fix' => 'Errore tecnico. Contatta il supporto con questo codice: ' . $error_code
            ]);
        }
        
        // Success!
        wp_send_json_success([
            'message' => '🎉 TRANSCRIPT API FUNZIONANTE! Il problema 401 è risolto.'
        ]);
    }
}

// Initialize
IPV_Prod_Connection_Diagnostic::init();
