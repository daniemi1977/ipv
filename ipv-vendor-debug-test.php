<?php
/**
 * IPV VENDOR - Script di Debug per Authorization Header
 *
 * ISTRUZIONI:
 * 1. Carica questo file nella root del server (public_html/)
 * 2. Visita: https://tuo-server.com/ipv-vendor-debug-test.php
 * 3. Copia tutto l'output e mandamelo
 *
 * Questo script mostra ESATTAMENTE quali header arrivano al server
 */

// Impedisci accesso diretto da browser (rimuovi temporaneamente per test)
// if ( ! isset( $_GET['test'] ) ) die( 'Access denied' );

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>IPV Vendor - Debug Authorization Header</title>
    <style>
        body {
            font-family: monospace;
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
        }
        .section {
            background: #252526;
            border: 1px solid #3c3c3c;
            border-radius: 4px;
            padding: 15px;
            margin: 15px 0;
        }
        .success { color: #4ec9b0; }
        .error { color: #f48771; }
        .warning { color: #ce9178; }
        .info { color: #569cd6; }
        h2 { color: #4ec9b0; border-bottom: 2px solid #3c3c3c; padding-bottom: 10px; }
        h3 { color: #dcdcaa; margin-top: 20px; }
        pre {
            background: #1e1e1e;
            border: 1px solid #3c3c3c;
            padding: 10px;
            overflow-x: auto;
            border-radius: 4px;
        }
        .test-result {
            font-size: 18px;
            font-weight: bold;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .test-success {
            background: #1e3a20;
            border: 2px solid #4ec9b0;
            color: #4ec9b0;
        }
        .test-fail {
            background: #3a1e1e;
            border: 2px solid #f48771;
            color: #f48771;
        }
    </style>
</head>
<body>

<h1>🔍 IPV Vendor - Debug Authorization Header</h1>

<?php

echo '<div class="section">';
echo '<h2>1. Tutti gli Header HTTP Ricevuti</h2>';
echo '<pre>';
foreach ( $_SERVER as $key => $value ) {
    if ( strpos( $key, 'HTTP_' ) === 0 ) {
        echo sprintf( "%-40s = %s\n", $key, $value );
    }
}
echo '</pre>';
echo '</div>';

echo '<div class="section">';
echo '<h2>2. Header Authorization (Metodi Diversi)</h2>';

$methods = [
    'HTTP_AUTHORIZATION' => $_SERVER['HTTP_AUTHORIZATION'] ?? null,
    'REDIRECT_HTTP_AUTHORIZATION' => $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null,
    'HTTP_X_LICENSE_KEY' => $_SERVER['HTTP_X_LICENSE_KEY'] ?? null,
];

$authorization_found = false;
$authorization_value = null;

foreach ( $methods as $method => $value ) {
    if ( $value !== null ) {
        echo '<div class="success">✅ ' . $method . ' = ' . $value . '</div>';
        $authorization_found = true;
        $authorization_value = $value;
    } else {
        echo '<div class="error">❌ ' . $method . ' = NULL</div>';
    }
}

echo '</div>';

echo '<div class="section">';
echo '<h2>3. Variabili Ambiente PHP</h2>';
echo '<pre>';
echo sprintf( "%-40s = %s\n", 'PHP_AUTH_USER', $_SERVER['PHP_AUTH_USER'] ?? 'NULL' );
echo sprintf( "%-40s = %s\n", 'PHP_AUTH_PW', $_SERVER['PHP_AUTH_PW'] ?? 'NULL' );
echo sprintf( "%-40s = %s\n", 'AUTH_TYPE', $_SERVER['AUTH_TYPE'] ?? 'NULL' );
echo '</pre>';
echo '</div>';

echo '<div class="section">';
echo '<h2>4. getallheaders() Function</h2>';
if ( function_exists( 'getallheaders' ) ) {
    $headers = getallheaders();
    echo '<pre>';
    foreach ( $headers as $key => $value ) {
        if ( stripos( $key, 'auth' ) !== false || stripos( $key, 'license' ) !== false ) {
            echo sprintf( "%-40s = %s\n", $key, $value );
        }
    }
    echo '</pre>';
} else {
    echo '<div class="warning">⚠️ getallheaders() non disponibile</div>';
}
echo '</div>';

echo '<div class="section">';
echo '<h2>5. Server Configuration</h2>';
echo '<pre>';
echo sprintf( "%-40s = %s\n", 'SERVER_SOFTWARE', $_SERVER['SERVER_SOFTWARE'] ?? 'UNKNOWN' );
echo sprintf( "%-40s = %s\n", 'SERVER_NAME', $_SERVER['SERVER_NAME'] ?? 'UNKNOWN' );
echo sprintf( "%-40s = %s\n", 'DOCUMENT_ROOT', $_SERVER['DOCUMENT_ROOT'] ?? 'UNKNOWN' );
echo sprintf( "%-40s = %s\n", 'PHP Version', phpversion() );
echo sprintf( "%-40s = %s\n", 'mod_rewrite', in_array( 'mod_rewrite', apache_get_modules() ?? [] ) ? 'ENABLED' : 'UNKNOWN' );
echo '</pre>';
echo '</div>';

echo '<div class="section">';
echo '<h2>6. File .htaccess Check</h2>';
$htaccess_locations = [
    $_SERVER['DOCUMENT_ROOT'] . '/.htaccess',
    $_SERVER['DOCUMENT_ROOT'] . '/wp-content/plugins/ipv-pro-vendor/.htaccess',
];

foreach ( $htaccess_locations as $location ) {
    if ( file_exists( $location ) ) {
        echo '<div class="success">✅ Trovato: ' . $location . '</div>';
        echo '<h4>Contenuto:</h4>';
        echo '<pre>' . htmlspecialchars( file_get_contents( $location ) ) . '</pre>';
    } else {
        echo '<div class="error">❌ Non trovato: ' . $location . '</div>';
    }
}
echo '</div>';

// TEST RESULT
echo '<div class="test-result ' . ( $authorization_found ? 'test-success' : 'test-fail' ) . '">';
if ( $authorization_found ) {
    echo '✅ SUCCESS: Authorization header RICEVUTO dal server!<br>';
    echo 'Valore: ' . htmlspecialchars( $authorization_value ) . '<br><br>';
    echo 'Il problema NON è l\'header bloccato.<br>';
    echo 'Possibili cause alternative:<br>';
    echo '- License key non valida nel database server<br>';
    echo '- License key non attivata per questo dominio<br>';
    echo '- Problema di validazione nel codice<br>';
} else {
    echo '❌ FAIL: Authorization header NON ARRIVA al server!<br><br>';
    echo 'Causa: L\'hosting sta bloccando l\'header.<br><br>';
    echo '<strong>SOLUZIONE:</strong><br>';
    echo '1. Verifica che .htaccess esista in ' . $_SERVER['DOCUMENT_ROOT'] . '/.htaccess<br>';
    echo '2. Aggiungi queste righe IN CIMA:<br><br>';
    echo '<pre style="background: #000; color: #0f0; padding: 10px;">';
    echo 'RewriteEngine On' . "\n";
    echo 'RewriteCond %{HTTP:Authorization} ^(.*)' . "\n";
    echo 'RewriteRule ^(.*) - [E=HTTP_AUTHORIZATION:%1]' . "\n";
    echo '</pre>';
}
echo '</div>';

echo '<div class="section">';
echo '<h2>7. Test con cURL (da eseguire da terminale)</h2>';
echo '<p class="info">Copia e esegui questo comando dal tuo computer per testare:</p>';
$test_url = 'https://' . $_SERVER['SERVER_NAME'] . '/ipv-vendor-debug-test.php';
echo '<pre>';
echo 'curl -H "Authorization: Bearer test123456" ' . $test_url . "\n\n";
echo '# Se vedi "Authorization header RICEVUTO" → .htaccess funziona!' . "\n";
echo '# Se vedi "Authorization header NON ARRIVA" → .htaccess non funziona' . "\n";
echo '</pre>';
echo '</div>';

echo '<div class="section">';
echo '<h2>8. WordPress API Test</h2>';
if ( file_exists( $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php' ) ) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php';

    echo '<div class="success">✅ WordPress caricato</div>';

    // Test health endpoint
    $health_url = home_url( '/wp-json/ipv-vendor/v1/health' );
    echo '<h4>Test Health Endpoint:</h4>';
    echo '<pre>URL: ' . $health_url . '</pre>';

    $response = wp_remote_get( $health_url );
    if ( ! is_wp_error( $response ) ) {
        $body = wp_remote_retrieve_body( $response );
        echo '<div class="success">✅ Health endpoint risponde</div>';
        echo '<pre>' . htmlspecialchars( $body ) . '</pre>';
    } else {
        echo '<div class="error">❌ Health endpoint non risponde: ' . $response->get_error_message() . '</div>';
    }

    // Check plugin active
    if ( is_plugin_active( 'ipv-pro-vendor/ipv-pro-vendor.php' ) ) {
        echo '<div class="success">✅ IPV Pro Vendor attivo</div>';
        if ( defined( 'IPV_VENDOR_VERSION' ) ) {
            echo '<div class="info">Versione: ' . IPV_VENDOR_VERSION . '</div>';
        }
    } else {
        echo '<div class="error">❌ IPV Pro Vendor NON attivo</div>';
    }
} else {
    echo '<div class="warning">⚠️ WordPress non trovato in ' . $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php</div>';
}
echo '</div>';

?>

<div class="section">
    <h2>9. Prossimi Passi</h2>
    <ol>
        <li><strong>Copia TUTTO questo output</strong> e mandamelo</li>
        <li>Se vedi "SUCCESS" sopra ma continui ad avere unauthorized:
            <ul>
                <li>Il problema è la license key, non l'header</li>
                <li>Verifica license key nel database server</li>
                <li>Controlla che sia attivata per il dominio client</li>
            </ul>
        </li>
        <li>Se vedi "FAIL" sopra:
            <ul>
                <li>Modifica .htaccess come indicato</li>
                <li>Ricarica questa pagina</li>
                <li>Dovrebbe mostrare "SUCCESS"</li>
            </ul>
        </li>
    </ol>
</div>

<div class="section">
    <h2>10. Elimina Questo File</h2>
    <p class="warning">⚠️ IMPORTANTE: Dopo il debug, <strong>elimina questo file</strong> dal server per sicurezza!</p>
    <p>File da eliminare: <code><?php echo __FILE__; ?></code></p>
</div>

</body>
</html>
