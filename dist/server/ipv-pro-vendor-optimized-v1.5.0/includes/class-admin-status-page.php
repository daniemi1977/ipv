<?php
/**
 * IPV Pro Vendor - Admin Status Page
 *
 * Pagina di troubleshooting e status del sistema
 *
 * @package IPV_Pro_Vendor
 * @version 1.3.5
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Vendor_Admin_Status_Page {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init() {
        add_action( 'admin_menu', [ $this, 'register_menu' ] );
        add_action( 'admin_post_ipv_vendor_rerun_config', [ $this, 'rerun_configuration' ] );
    }

    /**
     * Register menu page
     */
    public function register_menu() {
        // Main menu under WooCommerce
        add_submenu_page(
            'woocommerce',
            'IPV Vendor Status',
            'IPV Vendor Status',
            'manage_options', // v1.3.5 - Changed from manage_woocommerce for broader access
            'ipv-vendor-status',
            [ $this, 'render_page' ]
        );

        // v1.3.5 - Backward compatibility alias for old URL
        add_submenu_page(
            null, // Hidden menu (no parent)
            'IPV Vendor Troubleshooting',
            'IPV Vendor Troubleshooting',
            'manage_options',
            'ipv-vendor-troubleshooting',
            [ $this, 'render_page' ]
        );
    }

    /**
     * Render status page
     */
    public function render_page() {
        $status = IPV_Vendor_Auto_Configurator::instance()->get_status();
        $header_status = $status['authorization_header'] ?? 'unknown';
        $fixes_applied = $status['fixes_applied'] ?? [];
        $last_check = $status['last_check'] ?? 'Mai';

        ?>
        <div class="wrap">
            <h1>🔧 IPV Pro Vendor - System Status</h1>

            <div class="card" style="max-width: 800px;">
                <h2>📊 Status Attuale</h2>

                <!-- Authorization Header Status -->
                <table class="widefat">
                    <tr>
                        <th style="width: 250px;">Authorization Header:</th>
                        <td>
                            <?php if ( $header_status === 'working' ) : ?>
                                <span style="color: #46b450; font-weight: bold;">✅ FUNZIONANTE</span>
                                <p style="margin: 10px 0 0 0; color: #666;">L'header Authorization viene ricevuto correttamente dal server. Il sistema funziona!</p>
                            <?php elseif ( $header_status === 'blocked' ) : ?>
                                <span style="color: #dc3232; font-weight: bold;">❌ BLOCCATO</span>
                                <p style="margin: 10px 0 0 0; color: #666;">L'hosting sta bloccando l'header Authorization. Azione manuale richiesta.</p>
                            <?php else : ?>
                                <span style="color: #f0b849; font-weight: bold;">❓ SCONOSCIUTO</span>
                                <p style="margin: 10px 0 0 0; color: #666;">Status non ancora verificato. Clicca "Verifica Ora" sotto.</p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Ultimo Controllo:</th>
                        <td><?php echo esc_html( $last_check ); ?></td>
                    </tr>
                    <tr>
                        <th>Versione Plugin:</th>
                        <td><?php echo esc_html( IPV_VENDOR_VERSION ); ?></td>
                    </tr>
                </table>

                <?php if ( ! empty( $fixes_applied ) ) : ?>
                <h3 style="margin-top: 30px;">✅ Fix Applicati Automaticamente</h3>
                <ul style="list-style: disc; margin-left: 20px;">
                    <?php foreach ( $fixes_applied as $fix ) : ?>
                        <li><?php echo esc_html( $this->get_fix_description( $fix ) ); ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>

                <h3 style="margin-top: 30px;">🔄 Azioni</h3>
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                    <?php wp_nonce_field( 'ipv_vendor_rerun_config', 'ipv_vendor_nonce' ); ?>
                    <input type="hidden" name="action" value="ipv_vendor_rerun_config">
                    <p>
                        <button type="submit" class="button button-primary">
                            🔧 Verifica Ora &amp; Ri-applica Fix
                        </button>
                    </p>
                    <p class="description">
                        Clicca per verificare lo status corrente e tentare di riapplicare automaticamente i fix necessari.
                    </p>
                </form>
            </div>

            <?php if ( $header_status === 'blocked' ) : ?>
            <div class="notice notice-warning inline" style="max-width: 800px; margin-top: 20px;">
                <h3>⚠️ Azione Manuale Richiesta</h3>
                <p>L'auto-configurazione non è riuscita a risolvere il problema dell'Authorization header bloccato.</p>

                <h4>📋 Opzioni:</h4>

                <h5>Opzione 1: Contatta l'Hosting (Consigliato)</h5>
                <ol>
                    <li>Apri un ticket con il supporto del tuo hosting</li>
                    <li>Chiedi di <strong>abilitare il passaggio dell'header Authorization</strong> per le REST API WordPress</li>
                    <li>Menziona che stai usando WooCommerce REST API</li>
                </ol>

                <h5>Opzione 2: Aggiungi Manualmente al .htaccess Root</h5>
                <ol>
                    <li>Apri il file <code>.htaccess</code> nella root del sito (stesso livello di wp-config.php)</li>
                    <li>Aggiungi queste righe IN CIMA al file:</li>
                </ol>
                <pre style="background: #f5f5f5; padding: 15px; border: 1px solid #ddd; overflow-x: auto;">
&lt;IfModule mod_rewrite.c&gt;
RewriteEngine On
RewriteCond %{HTTP:Authorization} .+
RewriteRule ^ - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
&lt;/IfModule&gt;</pre>

                <h5>Opzione 3: Server Nginx (Non Apache)</h5>
                <p>Se il tuo server usa Nginx invece di Apache, il file .htaccess non funzionerà. Contatta l'hosting per aggiungere questa configurazione:</p>
                <pre style="background: #f5f5f5; padding: 15px; border: 1px solid #ddd; overflow-x: auto;">
location / {
    fastcgi_param HTTP_AUTHORIZATION $http_authorization;
    fastcgi_pass_header Authorization;
}</pre>
            </div>
            <?php endif; ?>

            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2>🧪 Test Manuale</h2>
                <p>Per testare manualmente se l'Authorization header funziona, esegui questo comando dal terminale:</p>
                <pre style="background: #2c3338; color: #fff; padding: 15px; overflow-x: auto;">curl -X POST <?php echo esc_url( rest_url( 'ipv-vendor/v1/health' ) ); ?> \
  -H "Authorization: Bearer test123" \
  -H "Content-Type: application/json"</pre>
                <p class="description">Se ricevi una risposta JSON, l'endpoint funziona. Il plugin controllerà se l'header Authorization viene ricevuto.</p>
            </div>

            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2>📚 Documentazione</h2>
                <ul>
                    <li><a href="https://github.com/daniemi1977/ipv" target="_blank">📦 Repository GitHub</a></li>
                    <li><a href="https://github.com/daniemi1977/ipv/blob/main/TROUBLESHOOTING-AUTHORIZATION-ERROR.md" target="_blank">🔍 Guida Troubleshooting Completa</a></li>
                    <li><a href="https://github.com/daniemi1977/ipv/blob/main/CHANGELOG-v1.3.3-SERVER.md" target="_blank">📝 Changelog v1.3.3</a></li>
                </ul>
            </div>

            <div class="card" style="max-width: 800px; margin-top: 20px; background: #f0f0f1;">
                <h2>💡 Come Funziona l'Auto-Configurazione</h2>
                <p>Quando attivi il plugin, il sistema esegue automaticamente questi step:</p>
                <ol>
                    <li><strong>Test iniziale:</strong> Verifica se l'Authorization header già funziona</li>
                    <li><strong>Fix #1:</strong> Crea file .htaccess nella cartella del plugin</li>
                    <li><strong>Fix #2:</strong> Tenta di modificare il .htaccess root del sito (se scrivibile)</li>
                    <li><strong>Fix #3:</strong> Tenta di aggiungere fix al wp-config.php (se scrivibile)</li>
                    <li><strong>Test finale:</strong> Verifica se i fix hanno funzionato</li>
                    <li><strong>Notifica:</strong> Mostra risultato e azioni richieste (se necessario)</li>
                </ol>
                <p><strong>Health Check:</strong> Il sistema ri-controlla automaticamente ogni 12 ore e ri-applica i fix se necessario.</p>
            </div>

            <div class="card" style="max-width: 800px; margin-top: 20px; background: #fff3cd;">
                <h2>⚠️ Importante</h2>
                <p><strong>Il plugin NON rimuove mai i fix applicati durante la disattivazione.</strong></p>
                <p>Se disattivi il plugin, i file .htaccess e le modifiche a wp-config.php rimarranno. Questo è per sicurezza, per evitare di rompere la configurazione del sito.</p>
                <p>Se vuoi rimuovere manualmente i fix:</p>
                <ul>
                    <li>Cerca le sezioni con commento <code>IPV Pro Vendor</code> in .htaccess e wp-config.php</li>
                    <li>Rimuovi quelle sezioni</li>
                    <li>I backup originali sono salvati con estensione <code>.ipv-backup-TIMESTAMP</code></li>
                </ul>
            </div>
        </div>

        <style>
            .card h2 { margin-top: 0; border-bottom: 2px solid #0073aa; padding-bottom: 10px; }
            .card h3 { color: #0073aa; }
            .widefat th { background: #f0f0f1; font-weight: bold; }
            .widefat td, .widefat th { padding: 15px; }
        </style>
        <?php
    }

    /**
     * Rerun configuration
     */
    public function rerun_configuration() {
        check_admin_referer( 'ipv_vendor_rerun_config', 'ipv_vendor_nonce' );

        if ( ! current_user_can( 'manage_options' ) ) { // v1.3.5 - Changed from manage_woocommerce
            wp_die( 'Unauthorized' );
        }

        // Re-run auto-configuration
        $auto_config = IPV_Vendor_Auto_Configurator::instance();
        $result = $auto_config->activate();

        // Redirect back with message
        $redirect = add_query_arg( [
            'page' => 'ipv-vendor-status',
            'config_rerun' => $result ? 'success' : 'failed'
        ], admin_url( 'admin.php' ) );

        wp_safe_redirect( $redirect );
        exit;
    }

    /**
     * Get fix description
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
}
