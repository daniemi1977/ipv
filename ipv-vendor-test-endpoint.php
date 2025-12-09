<?php
/**
 * IPV Pro Vendor - Test Endpoint (TEMPORANEO)
 *
 * Questo file aggiunge un endpoint di test che BYPASSA la validazione della licenza
 * per capire se il problema è l'Authorization header o qualcos'altro.
 *
 * ISTRUZIONI:
 * 1. Carica questo file in: wp-content/plugins/ipv-pro-vendor/
 * 2. Modifica ipv-pro-vendor.php e aggiungi ALLA FINE (prima dell'ultima riga):
 *    require_once IPV_VENDOR_DIR . 'ipv-vendor-test-endpoint.php';
 * 3. Testa l'endpoint: https://tuo-server.com/wp-json/ipv-vendor/v1/test-transcript
 * 4. ELIMINA questo file dopo il test!
 *
 * Plugin Name: IPV Vendor Test Endpoint
 * Description: TEMPORANEO - Test endpoint senza validazione licenza
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'rest_api_init', function() {
    register_rest_route( 'ipv-vendor/v1', '/test-transcript', [
        'methods' => 'POST',
        'callback' => 'ipv_vendor_test_transcript',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route( 'ipv-vendor/v1', '/test-debug', [
        'methods' => 'GET',
        'callback' => 'ipv_vendor_test_debug',
        'permission_callback' => '__return_true'
    ]);
});

/**
 * Test endpoint - Simula chiamata trascrizione SENZA validare licenza
 */
function ipv_vendor_test_transcript( $request ) {
    $debug_info = [
        'timestamp' => current_time( 'mysql' ),
        'endpoint' => '/test-transcript',
        'message' => 'Test endpoint - NO license validation'
    ];

    // Test 1: Verifica header Authorization
    $debug_info['headers'] = [
        'HTTP_AUTHORIZATION' => $_SERVER['HTTP_AUTHORIZATION'] ?? 'NULL',
        'REDIRECT_HTTP_AUTHORIZATION' => $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? 'NULL',
        'HTTP_X_LICENSE_KEY' => $_SERVER['HTTP_X_LICENSE_KEY'] ?? 'NULL',
    ];

    // Test 2: Verifica parametri richiesta
    $debug_info['params'] = [
        'video_id' => $request->get_param( 'video_id' ) ?? 'NULL',
        'mode' => $request->get_param( 'mode' ) ?? 'NULL',
        'lang' => $request->get_param( 'lang' ) ?? 'NULL',
    ];

    // Test 3: Verifica se SupaData API key configurata
    if ( class_exists( 'IPV_Vendor_API_Gateway' ) ) {
        $api_gateway = IPV_Vendor_API_Gateway::instance();
        $supadata_key = get_option( 'ipv_vendor_supadata_key', '' );
        $debug_info['api_gateway'] = [
            'class_exists' => true,
            'supadata_key_configured' => ! empty( $supadata_key ) ? 'YES (length: ' . strlen( $supadata_key ) . ')' : 'NO'
        ];
    } else {
        $debug_info['api_gateway'] = [
            'class_exists' => false,
            'error' => 'IPV_Vendor_API_Gateway class not found'
        ];
    }

    // Test 4: Verifica License Manager
    if ( class_exists( 'IPV_Vendor_License_Manager' ) ) {
        $license_manager = IPV_Vendor_License_Manager::instance();
        $debug_info['license_manager'] = [
            'class_exists' => true,
        ];

        // Prova a validare license key se arrivata
        $license_key = null;
        if ( ! empty( $_SERVER['HTTP_AUTHORIZATION'] ) ) {
            if ( preg_match( '/Bearer\s+(.+)$/i', $_SERVER['HTTP_AUTHORIZATION'], $matches ) ) {
                $license_key = $matches[1];
            }
        }

        if ( $license_key ) {
            $debug_info['license_test'] = [
                'license_key_received' => substr( $license_key, 0, 8 ) . '...' . substr( $license_key, -4 ),
                'attempting_validation' => 'YES'
            ];

            $license = $license_manager->validate_license( $license_key );
            if ( is_wp_error( $license ) ) {
                $debug_info['license_test']['result'] = 'ERROR';
                $debug_info['license_test']['error_code'] = $license->get_error_code();
                $debug_info['license_test']['error_message'] = $license->get_error_message();
            } else {
                $debug_info['license_test']['result'] = 'SUCCESS';
                $debug_info['license_test']['license_id'] = $license->id ?? 'NULL';
                $debug_info['license_test']['license_status'] = $license->status ?? 'NULL';
                $debug_info['license_test']['site_url'] = $license->site_url ?? 'NULL';
            }
        } else {
            $debug_info['license_test'] = [
                'license_key_received' => 'NO',
                'reason' => 'Authorization header vuoto o malformato'
            ];
        }
    } else {
        $debug_info['license_manager'] = [
            'class_exists' => false,
            'error' => 'IPV_Vendor_License_Manager class not found'
        ];
    }

    // Test 5: Verifica database connection
    global $wpdb;
    $debug_info['database'] = [
        'wpdb_exists' => isset( $wpdb ),
        'table_prefix' => $wpdb->prefix ?? 'NULL',
    ];

    // Test query licenses table
    $licenses_table = $wpdb->prefix . 'ipv_licenses';
    $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$licenses_table}'" ) === $licenses_table;
    $debug_info['database']['licenses_table_exists'] = $table_exists ? 'YES' : 'NO';

    if ( $table_exists ) {
        $license_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$licenses_table}" );
        $debug_info['database']['total_licenses'] = $license_count;
    }

    // Determina status generale
    $has_authorization = ! empty( $_SERVER['HTTP_AUTHORIZATION'] ) || ! empty( $_SERVER['HTTP_X_LICENSE_KEY'] );
    $debug_info['overall_status'] = $has_authorization ? 'AUTHORIZATION_RECEIVED' : 'AUTHORIZATION_MISSING';

    // Log per debug
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        error_log( '=== IPV VENDOR TEST ENDPOINT ===' );
        error_log( json_encode( $debug_info, JSON_PRETTY_PRINT ) );
    }

    return rest_ensure_response([
        'success' => true,
        'message' => 'Test endpoint - Nessuna validazione licenza eseguita',
        'debug' => $debug_info,
        'instructions' => [
            'Se vedi AUTHORIZATION_RECEIVED' => 'L\'header arriva! Il problema è nella validazione della licenza.',
            'Se vedi AUTHORIZATION_MISSING' => 'L\'header NON arriva. Problema .htaccess.',
            'Prossimi passi' => [
                'AUTHORIZATION_RECEIVED' => 'Controlla i dettagli in license_test per vedere perché la validazione fallisce',
                'AUTHORIZATION_MISSING' => 'Modifica .htaccess del server per preservare Authorization header'
            ]
        ]
    ]);
}

/**
 * Debug endpoint - Mostra configurazione completa
 */
function ipv_vendor_test_debug() {
    $debug = [
        'timestamp' => current_time( 'mysql' ),
        'wordpress' => [
            'version' => get_bloginfo( 'version' ),
            'home_url' => home_url(),
            'site_url' => site_url(),
        ],
        'plugin' => [
            'version' => defined( 'IPV_VENDOR_VERSION' ) ? IPV_VENDOR_VERSION : 'NOT_DEFINED',
            'active' => is_plugin_active( 'ipv-pro-vendor/ipv-pro-vendor.php' ),
        ],
        'server' => [
            'software' => $_SERVER['SERVER_SOFTWARE'] ?? 'UNKNOWN',
            'php_version' => phpversion(),
            'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'UNKNOWN',
        ],
        'options' => [
            'supadata_key_configured' => ! empty( get_option( 'ipv_vendor_supadata_key' ) ),
            'openai_key_configured' => ! empty( get_option( 'ipv_vendor_openai_key' ) ),
            'youtube_key_configured' => ! empty( get_option( 'ipv_vendor_youtube_key' ) ),
        ],
        'classes' => [
            'IPV_Vendor_API_Gateway' => class_exists( 'IPV_Vendor_API_Gateway' ),
            'IPV_Vendor_License_Manager' => class_exists( 'IPV_Vendor_License_Manager' ),
            'IPV_Vendor_Credits_Manager' => class_exists( 'IPV_Vendor_Credits_Manager' ),
        ]
    ];

    return rest_ensure_response([
        'success' => true,
        'debug' => $debug
    ]);
}
