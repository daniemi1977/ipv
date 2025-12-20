<?php
/**
 * IPV Pro Vendor - Auto Configurator
 *
 * Sistema di auto-configurazione che risolve automaticamente
 * il problema dell'Authorization header bloccato.
 *
 * @package IPV_Pro_Vendor
 * @version 1.3.3
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Vendor_Auto_Configurator {

    private static $instance = null;
    private $fixes_applied = [];
    private $config_status = [];

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Run on plugin activation
     */
    public function activate() {
        $this->log( 'Starting auto-configuration...' );

        // Test 1: Verifica se Authorization header funziona già
        $header_works = $this->test_authorization_header();

        if ( $header_works ) {
            $this->log( 'Authorization header già funzionante - nessun fix necessario' );
            update_option( 'ipv_vendor_config_status', [
                'authorization_header' => 'working',
                'fixes_applied' => [],
                'last_check' => current_time( 'mysql' )
            ]);
            return true;
        }

        $this->log( 'Authorization header bloccato - applicando fix automatici...' );

        // Fix 1: Crea .htaccess nella cartella plugin
        $this->create_plugin_htaccess();

        // Fix 2: Modifica .htaccess root (se possibile)
        $this->modify_root_htaccess();

        // Fix 3: Aggiungi fix a wp-config.php (se possibile)
        $this->add_wp_config_fix();

        // Test finale
        sleep( 1 ); // Attendi che i fix vengano applicati
        $header_works_now = $this->test_authorization_header();

        if ( $header_works_now ) {
            $this->log( 'SUCCESS: Authorization header ora funziona!' );
            update_option( 'ipv_vendor_config_status', [
                'authorization_header' => 'working',
                'fixes_applied' => $this->fixes_applied,
                'last_check' => current_time( 'mysql' )
            ]);
            set_transient( 'ipv_vendor_activation_notice', 'success', 60 );
        } else {
            $this->log( 'WARNING: Authorization header ancora bloccato dopo fix automatici' );
            update_option( 'ipv_vendor_config_status', [
                'authorization_header' => 'blocked',
                'fixes_applied' => $this->fixes_applied,
                'last_check' => current_time( 'mysql' ),
                'manual_action_required' => true
            ]);
            set_transient( 'ipv_vendor_activation_notice', 'manual_required', 60 );
        }

        return $header_works_now;
    }

    /**
     * Test se Authorization header funziona
     */
    private function test_authorization_header() {
        // Crea un test endpoint temporaneo
        add_action( 'rest_api_init', function() {
            register_rest_route( 'ipv-vendor/v1', '/auth-test', [
                'methods' => 'GET',
                'callback' => function() {
                    return [
                        'has_auth' => ! empty( $_SERVER['HTTP_AUTHORIZATION'] ) || ! empty( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ),
                        'http_auth' => $_SERVER['HTTP_AUTHORIZATION'] ?? null,
                        'redirect_auth' => $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null
                    ];
                },
                'permission_callback' => '__return_true'
            ]);
        });

        // Flush rewrite rules
        flush_rewrite_rules();

        // Test con wp_remote_request
        $test_url = rest_url( 'ipv-vendor/v1/auth-test' );
        $response = wp_remote_get( $test_url, [
            'headers' => [
                'Authorization' => 'Bearer test123456'
            ],
            'timeout' => 10
        ]);

        if ( is_wp_error( $response ) ) {
            $this->log( 'Test fallito: ' . $response->get_error_message() );
            return false;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( isset( $body['has_auth'] ) && $body['has_auth'] ) {
            $this->log( 'Test passato: Authorization header ricevuto' );
            return true;
        }

        $this->log( 'Test fallito: Authorization header non ricevuto' );
        return false;
    }

    /**
     * Fix 1: Crea .htaccess nella cartella plugin
     */
    private function create_plugin_htaccess() {
        $plugin_dir = IPV_VENDOR_DIR;
        $htaccess_file = $plugin_dir . '.htaccess';

        $content = <<<'HTACCESS'
# IPV Pro Vendor - Auto-generated .htaccess
# v1.3.3 - Authorization Header Fix

<IfModule mod_rewrite.c>
RewriteEngine On

# CRITICAL: Preserve Authorization header
RewriteCond %{HTTP:Authorization} .+
RewriteRule ^ - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

# Fallback for X-License-Key
RewriteCond %{HTTP:X-License-Key} .+
RewriteRule ^ - [E=HTTP_X_LICENSE_KEY:%{HTTP:X-License-Key}]
</IfModule>

# Security
<IfModule mod_autoindex.c>
Options -Indexes
</IfModule>

<FilesMatch "^(README\.md|CHANGELOG\.md|composer\.(json|lock)|\.git.*)$">
<IfModule mod_authz_core.c>
    Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
    Order allow,deny
    Deny from all
</IfModule>
</FilesMatch>
HTACCESS;

        $result = @file_put_contents( $htaccess_file, $content );

        if ( $result !== false ) {
            $this->fixes_applied[] = 'plugin_htaccess';
            $this->log( 'Created .htaccess in plugin directory' );
            return true;
        } else {
            $this->log( 'Failed to create .htaccess in plugin directory (permissions?)' );
            return false;
        }
    }

    /**
     * Fix 2: Modifica .htaccess root
     */
    private function modify_root_htaccess() {
        $root_htaccess = ABSPATH . '.htaccess';

        // Verifica se esiste e se è scrivibile
        if ( ! file_exists( $root_htaccess ) ) {
            $this->log( 'Root .htaccess non esiste - creazione...' );
            return $this->create_root_htaccess();
        }

        if ( ! is_writable( $root_htaccess ) ) {
            $this->log( 'Root .htaccess non scrivibile - skip' );
            return false;
        }

        // Leggi contenuto esistente
        $content = file_get_contents( $root_htaccess );

        // Verifica se il fix è già presente
        if ( strpos( $content, 'IPV Pro Vendor' ) !== false ||
             strpos( $content, 'HTTP_AUTHORIZATION' ) !== false ) {
            $this->log( 'Root .htaccess già contiene fix Authorization - skip' );
            return true;
        }

        // Prepara il fix
        $fix = <<<'FIX'

# BEGIN IPV Pro Vendor - Authorization Fix (Auto-added v1.3.3)
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteCond %{HTTP:Authorization} .+
RewriteRule ^ - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
</IfModule>
# END IPV Pro Vendor

FIX;

        // Inserisci il fix all'inizio del file (dopo RewriteEngine On se presente)
        if ( preg_match( '/(RewriteEngine\s+On)/i', $content ) ) {
            // Inserisci dopo RewriteEngine On
            $new_content = preg_replace(
                '/(RewriteEngine\s+On)/i',
                "$1\n" . $fix,
                $content,
                1
            );
        } else {
            // Inserisci all'inizio
            $new_content = $fix . "\n" . $content;
        }

        // Backup del file originale
        @copy( $root_htaccess, $root_htaccess . '.ipv-backup-' . time() );

        // Scrivi il nuovo contenuto
        $result = @file_put_contents( $root_htaccess, $new_content );

        if ( $result !== false ) {
            $this->fixes_applied[] = 'root_htaccess_modified';
            $this->log( 'Modified root .htaccess with Authorization fix' );
            return true;
        } else {
            $this->log( 'Failed to modify root .htaccess' );
            return false;
        }
    }

    /**
     * Crea root .htaccess se non esiste
     */
    private function create_root_htaccess() {
        $root_htaccess = ABSPATH . '.htaccess';

        $content = <<<'HTACCESS'
# BEGIN IPV Pro Vendor - Authorization Fix (Auto-created v1.3.3)
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteCond %{HTTP:Authorization} .+
RewriteRule ^ - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
</IfModule>
# END IPV Pro Vendor

# BEGIN WordPress
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>
# END WordPress
HTACCESS;

        $result = @file_put_contents( $root_htaccess, $content );

        if ( $result !== false ) {
            $this->fixes_applied[] = 'root_htaccess_created';
            $this->log( 'Created root .htaccess with Authorization fix' );
            return true;
        } else {
            $this->log( 'Failed to create root .htaccess (permissions?)' );
            return false;
        }
    }

    /**
     * Fix 3: Aggiungi fix a wp-config.php
     */
    private function add_wp_config_fix() {
        $wp_config = ABSPATH . 'wp-config.php';

        if ( ! file_exists( $wp_config ) || ! is_writable( $wp_config ) ) {
            $this->log( 'wp-config.php non accessibile - skip' );
            return false;
        }

        $content = file_get_contents( $wp_config );

        // Verifica se il fix è già presente
        if ( strpos( $content, 'IPV_VENDOR_AUTH_FIX' ) !== false ) {
            $this->log( 'wp-config.php già contiene fix - skip' );
            return true;
        }

        // Prepara il fix
        $fix = <<<'PHP'

// BEGIN IPV Pro Vendor - Authorization Fix (Auto-added v1.3.3)
if ( ! defined( 'IPV_VENDOR_AUTH_FIX' ) ) {
    define( 'IPV_VENDOR_AUTH_FIX', true );
    // Fix Authorization header per alcuni hosting
    if ( isset( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) && empty( $_SERVER['HTTP_AUTHORIZATION'] ) ) {
        $_SERVER['HTTP_AUTHORIZATION'] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    }
}
// END IPV Pro Vendor

PHP;

        // Inserisci prima di "/* That's all, stop editing! */"
        if ( strpos( $content, "/* That's all, stop editing!" ) !== false ) {
            $new_content = str_replace(
                "/* That's all, stop editing!",
                $fix . "\n/* That's all, stop editing!",
                $content
            );
        } else {
            // Inserisci alla fine
            $new_content = $content . "\n" . $fix;
        }

        // Backup
        @copy( $wp_config, $wp_config . '.ipv-backup-' . time() );

        // Scrivi
        $result = @file_put_contents( $wp_config, $new_content );

        if ( $result !== false ) {
            $this->fixes_applied[] = 'wp_config_modified';
            $this->log( 'Modified wp-config.php with Authorization fix' );
            return true;
        } else {
            $this->log( 'Failed to modify wp-config.php' );
            return false;
        }
    }

    /**
     * Show admin notice after activation
     */
    public function show_activation_notice() {
        $notice_type = get_transient( 'ipv_vendor_activation_notice' );

        if ( ! $notice_type ) {
            return;
        }

        delete_transient( 'ipv_vendor_activation_notice' );

        $status = get_option( 'ipv_vendor_config_status', [] );
        $fixes = $status['fixes_applied'] ?? [];

        if ( $notice_type === 'success' ) {
            ?>
            <div class="notice notice-success is-dismissible">
                <h3>✅ IPV Pro Vendor - Configurazione Automatica Completata!</h3>
                <p><strong>Il plugin è pronto all'uso.</strong> L'Authorization header funziona correttamente.</p>
                <?php if ( ! empty( $fixes ) ) : ?>
                <p><strong>Fix applicati automaticamente:</strong></p>
                <ul style="list-style: disc; margin-left: 20px;">
                    <?php foreach ( $fixes as $fix ) : ?>
                        <li><?php echo esc_html( $this->get_fix_description( $fix ) ); ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
                <p>✨ <strong>Nessuna azione richiesta</strong> - Puoi iniziare a usare il plugin!</p>
            </div>
            <?php
        } else {
            ?>
            <div class="notice notice-warning">
                <h3>⚠️ IPV Pro Vendor - Azione Manuale Richiesta</h3>
                <p>Il plugin ha tentato di configurarsi automaticamente, ma l'Authorization header è ancora bloccato dall'hosting.</p>
                <?php if ( ! empty( $fixes ) ) : ?>
                <p><strong>Fix tentati automaticamente:</strong></p>
                <ul style="list-style: disc; margin-left: 20px;">
                    <?php foreach ( $fixes as $fix ) : ?>
                        <li><?php echo esc_html( $this->get_fix_description( $fix ) ); ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
                <p><strong>📋 Azione Richiesta:</strong></p>
                <ol style="margin-left: 20px;">
                    <li>Contatta il supporto del tuo hosting (SiteGround, Bluehost, etc.)</li>
                    <li>Chiedi di abilitare il passaggio dell'header <code>Authorization</code> per le REST API</li>
                    <li>Oppure segui la <a href="<?php echo admin_url( 'admin.php?page=ipv-vendor-troubleshooting' ); ?>">guida troubleshooting</a></li>
                </ol>
            </div>
            <?php
        }
    }

    /**
     * Get human-readable description for fix
     */
    private function get_fix_description( $fix ) {
        $descriptions = [
            'plugin_htaccess' => 'Creato .htaccess nella cartella plugin',
            'root_htaccess_modified' => 'Modificato .htaccess root del sito',
            'root_htaccess_created' => 'Creato .htaccess root del sito',
            'wp_config_modified' => 'Aggiunto fix al file wp-config.php'
        ];

        return $descriptions[ $fix ] ?? $fix;
    }

    /**
     * Periodic health check
     */
    public function health_check() {
        $status = get_option( 'ipv_vendor_config_status', [] );
        $last_check = $status['last_check'] ?? '';

        // Check ogni 6 ore
        if ( ! empty( $last_check ) ) {
            $last_check_time = strtotime( $last_check );
            if ( time() - $last_check_time < 6 * HOUR_IN_SECONDS ) {
                return; // Troppo presto per ricontrollare
            }
        }

        // Test Authorization header
        $header_works = $this->test_authorization_header();

        // Aggiorna status
        $status['authorization_header'] = $header_works ? 'working' : 'blocked';
        $status['last_check'] = current_time( 'mysql' );
        update_option( 'ipv_vendor_config_status', $status );

        // Se non funziona, prova a riapplicare fix
        if ( ! $header_works && empty( $status['manual_action_required'] ) ) {
            $this->log( 'Health check: Authorization header bloccato - tentativo fix...' );
            $this->activate(); // Riapplica tutti i fix
        }
    }

    /**
     * Log per debug
     */
    private function log( $message ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( '[IPV Vendor Auto-Config] ' . $message );
        }
    }

    /**
     * Get current status
     */
    public function get_status() {
        return get_option( 'ipv_vendor_config_status', [
            'authorization_header' => 'unknown',
            'fixes_applied' => [],
            'last_check' => null
        ]);
    }
}
