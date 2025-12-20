<?php
/**
 * IPV Pro Vendor - API Keys Tester
 * 
 * Test automatico di tutte le API key configurate:
 * - YouTube Data API v3
 * - OpenAI API
 * - SupaData API (Transcription)
 * 
 * @package IPV_Pro_Vendor
 * @version 1.6.4
 * @since 1.6.4
 */

if (!defined('ABSPATH')) {
    exit;
}

class IPV_Vendor_API_Tester {

    /**
     * Initialize
     */
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_menu_page'], 99);
        add_action('wp_ajax_ipv_vendor_test_api', [__CLASS__, 'ajax_test_api']);
    }

    /**
     * Add menu page
     */
    public static function add_menu_page() {
        add_submenu_page(
            'ipv-vendor-dashboard',
            '🧪 Test API Keys',
            '🧪 Test API',
            'manage_options',
            'ipv-vendor-api-tester',
            [__CLASS__, 'render_page']
        );
    }

    /**
     * Render test page
     */
    public static function render_page() {
        // Get current API keys (use same option names as Settings page and API Gateway)
        $youtube_key = get_option('ipv_youtube_api_key', '');
        $openai_key = get_option('ipv_openai_api_key', '');

        // ✅ Usa lo stesso metodo di API Gateway per SupaData
        $supadata_keys = [
            get_option('ipv_supadata_api_key_1', ''),
            get_option('ipv_supadata_api_key_2', ''),
            get_option('ipv_supadata_api_key_3', '')
        ];
        $supadata_keys = array_filter($supadata_keys);
        $supadata_key = !empty($supadata_keys) ? reset($supadata_keys) : 'sd_7183c8f8648e5f63ae3b758d2a950ef1'; // Fallback hardcoded
        
        ?>
        <div class="wrap ipv-vendor-wrap">
            <h1>🧪 Test API Keys</h1>
            <p class="description">
                Verifica che tutte le API key configurate funzionino correttamente.
                Ogni test effettua una chiamata reale all'API corrispondente.
            </p>

            <div class="ipv-api-tester-container" style="margin-top: 30px;">
                
                <!-- API Keys Status Cards -->
                <div class="ipv-cards-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px;">
                    
                    <!-- YouTube API Card -->
                    <div class="ipv-card" id="youtube-card">
                        <div class="ipv-card-header" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px;">
                            <h3 style="margin: 0; display: flex; align-items: center; gap: 10px;">
                                <span style="font-size: 24px;">🎬</span>
                                YouTube API
                            </h3>
                            <span class="status-badge" id="youtube-status">
                                <?php echo $youtube_key ? '<span style="color: #666;">●</span> Configurata' : '<span style="color: #dc3545;">●</span> Non configurata'; ?>
                            </span>
                        </div>
                        
                        <div class="api-info">
                            <p><strong>Endpoint:</strong> YouTube Data API v3</p>
                            <p><strong>Key:</strong> <?php echo $youtube_key ? substr($youtube_key, 0, 10) . '...' . substr($youtube_key, -5) : 'Non configurata'; ?></p>
                        </div>
                        
                        <button 
                            class="button button-primary test-api-btn" 
                            data-api="youtube"
                            style="width: 100%; margin-top: 15px;"
                            <?php echo !$youtube_key ? 'disabled' : ''; ?>
                        >
                            🧪 Test YouTube API
                        </button>
                        
                        <div class="api-result" id="youtube-result" style="margin-top: 15px; display: none;">
                            <!-- Result will be inserted here -->
                        </div>
                    </div>

                    <!-- OpenAI API Card -->
                    <div class="ipv-card" id="openai-card">
                        <div class="ipv-card-header" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px;">
                            <h3 style="margin: 0; display: flex; align-items: center; gap: 10px;">
                                <span style="font-size: 24px;">🤖</span>
                                OpenAI API
                            </h3>
                            <span class="status-badge" id="openai-status">
                                <?php echo $openai_key ? '<span style="color: #666;">●</span> Configurata' : '<span style="color: #dc3545;">●</span> Non configurata'; ?>
                            </span>
                        </div>
                        
                        <div class="api-info">
                            <p><strong>Endpoint:</strong> OpenAI Chat Completion</p>
                            <p><strong>Key:</strong> <?php echo $openai_key ? substr($openai_key, 0, 10) . '...' . substr($openai_key, -5) : 'Non configurata'; ?></p>
                        </div>
                        
                        <button 
                            class="button button-primary test-api-btn" 
                            data-api="openai"
                            style="width: 100%; margin-top: 15px;"
                            <?php echo !$openai_key ? 'disabled' : ''; ?>
                        >
                            🧪 Test OpenAI API
                        </button>
                        
                        <div class="api-result" id="openai-result" style="margin-top: 15px; display: none;">
                            <!-- Result will be inserted here -->
                        </div>
                    </div>

                    <!-- SupaData API Card -->
                    <div class="ipv-card" id="supadata-card">
                        <div class="ipv-card-header" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px;">
                            <h3 style="margin: 0; display: flex; align-items: center; gap: 10px;">
                                <span style="font-size: 24px;">📝</span>
                                SupaData API
                            </h3>
                            <span class="status-badge" id="supadata-status">
                                <?php echo $supadata_key ? '<span style="color: #10b981;">●</span> Configurata' : '<span style="color: #dc3545;">●</span> Non configurata'; ?>
                            </span>
                        </div>
                        
                        <div class="api-info">
                            <p><strong>Endpoint:</strong> SupaData YouTube Transcript</p>
                            <p><strong>Key:</strong> <?php echo $supadata_key ? substr($supadata_key, 0, 12) . '...' : 'Non configurata'; ?></p>
                            <p><em style="color: #666; font-size: 12px;">Usa chiave configurata o fallback hardcoded</em></p>
                        </div>
                        
                        <button 
                            class="button button-primary test-api-btn" 
                            data-api="supadata"
                            style="width: 100%; margin-top: 15px;"
                        >
                            🧪 Test SupaData API
                        </button>
                        
                        <div class="api-result" id="supadata-result" style="margin-top: 15px; display: none;">
                            <!-- Result will be inserted here -->
                        </div>
                    </div>

                </div>

                <!-- Test All Button -->
                <div style="text-align: center; margin: 30px 0;">
                    <button 
                        class="button button-hero" 
                        id="test-all-btn"
                        style="padding: 10px 40px; font-size: 16px;"
                    >
                        🚀 Test Tutte le API
                    </button>
                </div>

                <!-- Overall Results -->
                <div id="overall-results" style="display: none; margin-top: 30px;">
                    <div class="ipv-card">
                        <h3>📊 Riepilogo Test</h3>
                        <div id="overall-results-content"></div>
                    </div>
                </div>

            </div>
        </div>

        <style>
        .ipv-vendor-wrap {
            background: #f5f5f5;
            padding: 20px;
            margin: 20px 20px 20px 0;
            border-radius: 8px;
        }
        
        .ipv-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .status-badge {
            padding: 5px 12px;
            border-radius: 12px;
            background: #f0f0f0;
            font-size: 12px;
            font-weight: 600;
        }
        
        .api-info p {
            margin: 8px 0;
            font-size: 13px;
            color: #666;
        }
        
        .api-info strong {
            color: #333;
        }
        
        .api-result {
            padding: 15px;
            border-radius: 6px;
            border: 2px solid;
            font-size: 14px;
        }
        
        .api-result.success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        
        .api-result.error {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }
        
        .api-result.loading {
            background: #fff3cd;
            border-color: #ffc107;
            color: #856404;
        }
        
        .test-api-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .spinner {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid rgba(0,0,0,0.1);
            border-top-color: #333;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
            margin-right: 8px;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .result-details {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid rgba(0,0,0,0.1);
            font-size: 12px;
        }
        
        .result-details code {
            background: rgba(0,0,0,0.05);
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
        </style>

        <script>
        jQuery(document).ready(function($) {
            
            // Test single API
            $('.test-api-btn').on('click', function() {
                const btn = $(this);
                const api = btn.data('api');
                const resultDiv = $('#' + api + '-result');
                
                btn.prop('disabled', true);
                resultDiv.html('<div class="api-result loading"><span class="spinner"></span> Testing ' + api + ' API...</div>').show();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ipv_vendor_test_api',
                        api: api,
                        nonce: '<?php echo wp_create_nonce('ipv_vendor_test_api'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            resultDiv.html(
                                '<div class="api-result success">' +
                                '<strong>✅ Test Riuscito</strong>' +
                                '<div class="result-details">' + response.data.message + '</div>' +
                                '</div>'
                            );
                            $('#' + api + '-status').html('<span style="color: #28a745;">●</span> Funzionante');
                        } else {
                            resultDiv.html(
                                '<div class="api-result error">' +
                                '<strong>❌ Test Fallito</strong>' +
                                '<div class="result-details">' + response.data.message + '</div>' +
                                '</div>'
                            );
                            $('#' + api + '-status').html('<span style="color: #dc3545;">●</span> Errore');
                        }
                    },
                    error: function() {
                        resultDiv.html(
                            '<div class="api-result error">' +
                            '<strong>❌ Errore di Rete</strong>' +
                            '<div class="result-details">Impossibile completare il test. Verifica la connessione.</div>' +
                            '</div>'
                        );
                    },
                    complete: function() {
                        btn.prop('disabled', false);
                    }
                });
            });
            
            // Test all APIs
            $('#test-all-btn').on('click', function() {
                const btn = $(this);
                const apis = ['youtube', 'openai', 'supadata'];
                let completed = 0;
                let results = {};
                
                btn.prop('disabled', true).text('🔄 Testing...');
                $('#overall-results').hide();
                
                apis.forEach(function(api) {
                    if (!$('.test-api-btn[data-api="' + api + '"]').prop('disabled')) {
                        $('.test-api-btn[data-api="' + api + '"]').trigger('click');
                    }
                });
                
                // Wait for all tests to complete (monitor status badges)
                const checkInterval = setInterval(function() {
                    let allDone = true;
                    apis.forEach(function(api) {
                        const status = $('#' + api + '-status').text().trim();
                        if (status === 'Configurata' || status.includes('...')) {
                            allDone = false;
                        }
                    });
                    
                    if (allDone) {
                        clearInterval(checkInterval);
                        btn.prop('disabled', false).text('🚀 Test Tutte le API');
                        
                        // Show overall results
                        let successCount = 0;
                        let totalConfigured = 0;
                        let html = '<ul style="list-style: none; padding: 0;">';
                        
                        apis.forEach(function(api) {
                            const status = $('#' + api + '-status').text().trim();
                            if (!$('.test-api-btn[data-api="' + api + '"]').prop('disabled')) {
                                totalConfigured++;
                                if (status.includes('Funzionante')) {
                                    successCount++;
                                    html += '<li style="padding: 10px; margin: 5px 0; background: #d4edda; border-left: 4px solid #28a745; border-radius: 4px;">✅ <strong>' + api.toUpperCase() + '</strong>: Funzionante</li>';
                                } else {
                                    html += '<li style="padding: 10px; margin: 5px 0; background: #f8d7da; border-left: 4px solid #dc3545; border-radius: 4px;">❌ <strong>' + api.toUpperCase() + '</strong>: Errore</li>';
                                }
                            } else {
                                html += '<li style="padding: 10px; margin: 5px 0; background: #e2e3e5; border-left: 4px solid #6c757d; border-radius: 4px;">⚠️ <strong>' + api.toUpperCase() + '</strong>: Non configurata</li>';
                            }
                        });
                        
                        html += '</ul>';
                        html += '<div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 6px; text-align: center;">';
                        html += '<strong style="font-size: 18px;">' + successCount + ' su ' + totalConfigured + ' API funzionanti</strong>';
                        html += '</div>';
                        
                        $('#overall-results-content').html(html);
                        $('#overall-results').slideDown();
                    }
                }, 500);
            });
            
        });
        </script>
        <?php
    }

    /**
     * AJAX handler for testing APIs
     */
    public static function ajax_test_api() {
        check_ajax_referer('ipv_vendor_test_api', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permessi insufficienti']);
        }
        
        $api = isset($_POST['api']) ? sanitize_text_field($_POST['api']) : '';
        
        switch ($api) {
            case 'youtube':
                $result = self::test_youtube_api();
                break;
            case 'openai':
                $result = self::test_openai_api();
                break;
            case 'supadata':
                $result = self::test_supadata_api();
                break;
            default:
                wp_send_json_error(['message' => 'API non riconosciuta']);
        }
        
        if ($result['success']) {
            wp_send_json_success(['message' => $result['message']]);
        } else {
            wp_send_json_error(['message' => $result['message']]);
        }
    }

    /**
     * Test YouTube API
     */
    private static function test_youtube_api() {
        $api_key = get_option('ipv_youtube_api_key', '');
        
        if (empty($api_key)) {
            return [
                'success' => false,
                'message' => 'YouTube API key non configurata'
            ];
        }
        
        // Test con un video ID pubblico noto (video di YouTube ufficiale)
        $video_id = 'jNQXAC9IVRw'; // "Me at the zoo" - primo video YouTube
        $url = 'https://www.googleapis.com/youtube/v3/videos?part=snippet&id=' . $video_id . '&key=' . $api_key;
        
        $response = wp_remote_get($url, ['timeout' => 10]);
        
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => 'Errore di connessione: ' . $response->get_error_message()
            ];
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($status_code === 200 && isset($body['items']) && count($body['items']) > 0) {
            $video_title = $body['items'][0]['snippet']['title'] ?? 'Unknown';
            return [
                'success' => true,
                'message' => 'API funzionante ✓<br>Test video: <code>' . esc_html($video_title) . '</code><br>Quota usata: ~1 unità'
            ];
        } elseif ($status_code === 403) {
            $error_msg = $body['error']['message'] ?? 'API key non valida o quota esaurita';
            return [
                'success' => false,
                'message' => 'Errore 403: ' . esc_html($error_msg)
            ];
        } elseif ($status_code === 400) {
            return [
                'success' => false,
                'message' => 'Errore 400: API key non valida o malformata'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Errore HTTP ' . $status_code . ': ' . ($body['error']['message'] ?? 'Errore sconosciuto')
            ];
        }
    }

    /**
     * Test OpenAI API
     */
    private static function test_openai_api() {
        $api_key = get_option('ipv_openai_api_key', '');
        
        if (empty($api_key)) {
            return [
                'success' => false,
                'message' => 'OpenAI API key non configurata'
            ];
        }
        
        $url = 'https://api.openai.com/v1/chat/completions';
        
        $body = [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'user', 'content' => 'Say "API test successful" in Italian']
            ],
            'max_tokens' => 10,
            'temperature' => 0
        ];
        
        $response = wp_remote_post($url, [
            'timeout' => 15,
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ],
            'body' => wp_json_encode($body)
        ]);
        
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => 'Errore di connessione: ' . $response->get_error_message()
            ];
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $response_body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($status_code === 200 && isset($response_body['choices'][0]['message']['content'])) {
            $ai_response = trim($response_body['choices'][0]['message']['content']);
            return [
                'success' => true,
                'message' => 'API funzionante ✓<br>Risposta AI: <code>' . esc_html($ai_response) . '</code><br>Modello: gpt-3.5-turbo'
            ];
        } elseif ($status_code === 401) {
            return [
                'success' => false,
                'message' => 'Errore 401: API key non valida'
            ];
        } elseif ($status_code === 429) {
            return [
                'success' => false,
                'message' => 'Errore 429: Rate limit raggiunto. Riprova tra qualche minuto.'
            ];
        } else {
            $error_msg = $response_body['error']['message'] ?? 'Errore sconosciuto';
            return [
                'success' => false,
                'message' => 'Errore HTTP ' . $status_code . ': ' . esc_html($error_msg)
            ];
        }
    }

    /**
     * Test SupaData API
     */
    private static function test_supadata_api() {
        // ✅ Usa lo stesso metodo di API Gateway per trovare le chiavi
        $keys = [
            get_option('ipv_supadata_api_key_1', ''),
            get_option('ipv_supadata_api_key_2', ''),
            get_option('ipv_supadata_api_key_3', '')
        ];
        
        // Filter empty keys
        $keys = array_filter($keys);
        
        // Se non ci sono chiavi configurate, usa la hardcoded (fallback)
        if (empty($keys)) {
            $api_key = 'sd_7183c8f8648e5f63ae3b758d2a950ef1';
        } else {
            $api_key = reset($keys); // Usa la prima chiave disponibile
        }
        
        if (empty($api_key)) {
            return [
                'success' => false,
                'message' => 'SupaData API key non configurata'
            ];
        }
        
        // ✅ Test con GET e URL completo (come richiesto da SupaData)
        $test_video_url = 'https://www.youtube.com/watch?v=jNQXAC9IVRw'; // "Me at the zoo"
        
        $url = 'https://api.supadata.ai/v1/transcript?' . http_build_query([
            'url' => $test_video_url,
            'text' => 'true',
            'mode' => 'native' // Native mode per test veloce (senza AI)
        ]);
        
        $response = wp_remote_get($url, [
            'timeout' => 20,
            'headers' => [
                'x-api-key' => $api_key,
            ]
        ]);
        
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => 'Errore di connessione: ' . $response->get_error_message()
            ];
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $response_body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($status_code === 200) {
            return [
                'success' => true,
                'message' => 'API funzionante ✓<br>Endpoint: <code>supadata.ai/v1/youtube/transcript</code><br>Credenziali valide'
            ];
        } elseif ($status_code === 401 || $status_code === 403) {
            return [
                'success' => false,
                'message' => 'Errore ' . $status_code . ': API key o secret non validi'
            ];
        } elseif ($status_code === 429) {
            return [
                'success' => false,
                'message' => 'Errore 429: Rate limit raggiunto'
            ];
        } else {
            $error_msg = $response_body['message'] ?? $response_body['error'] ?? 'Errore sconosciuto';
            return [
                'success' => false,
                'message' => 'Errore HTTP ' . $status_code . ': ' . esc_html($error_msg)
            ];
        }
    }
}

// Initialize
IPV_Vendor_API_Tester::init();
