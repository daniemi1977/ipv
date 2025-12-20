<?php
/**
 * Setup Wizard for IPV Production System Pro Client
 *
 * Interactive installation wizard with step-by-step configuration
 *
 * @package IPV_Production_System_Pro
 * @version 10.3.0-optimized
 */

if (!defined('ABSPATH')) {
    exit;
}

class IPV_Prod_Setup_Wizard {

    /**
     * Initialize wizard
     */
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_wizard_page'));
        add_action('admin_init', array(__CLASS__, 'redirect_to_wizard'));
        add_action('admin_post_ipv_prod_save_settings', array(__CLASS__, 'save_settings'));
        add_action('admin_post_ipv_prod_activate_license', array(__CLASS__, 'activate_license'));
    }

    /**
     * Redirect to wizard after activation
     */
    public static function redirect_to_wizard() {
        if (get_transient('ipv_prod_show_wizard')) {
            delete_transient('ipv_prod_show_wizard');

            if (!IPV_Prod_Auto_Installer::is_setup_complete()) {
                wp_safe_redirect(admin_url('admin.php?page=ipv-prod-setup'));
                exit;
            }
        }
    }

    /**
     * Add wizard page to admin menu
     */
    public static function add_wizard_page() {
        add_submenu_page(
            null, // Hidden from menu
            'IPV Pro Setup',
            'Setup Wizard',
            'manage_options',
            'ipv-prod-setup',
            array(__CLASS__, 'render_wizard')
        );
    }

    /**
     * Render setup wizard
     */
    public static function render_wizard() {
        $progress = IPV_Prod_Auto_Installer::get_setup_progress();
        $current_step = isset($_GET['step']) ? intval($_GET['step']) : 1;

        ?>
        <div class="wrap ipv-prod-wizard">
            <h1>🎯 IPV Production System Pro - Setup Wizard</h1>
            <p>Configurazione guidata del plugin client. Completa tutti i passaggi per iniziare a importare video.</p>

            <!-- Progress Bar -->
            <div class="ipv-progress-bar">
                <div class="ipv-progress-fill" style="width: <?php echo $progress['percentage']; ?>%"></div>
            </div>
            <p class="ipv-progress-text"><?php echo $progress['completed']; ?> di <?php echo $progress['total']; ?> passaggi completati (<?php echo $progress['percentage']; ?>%)</p>

            <div class="ipv-wizard-container">
                <!-- Step Navigation -->
                <div class="ipv-wizard-steps">
                    <div class="ipv-step <?php echo $current_step === 1 ? 'active' : ($progress['steps']['tables'] ? 'completed' : ''); ?>">
                        <span class="ipv-step-number">1</span>
                        <span class="ipv-step-title">Database</span>
                    </div>
                    <div class="ipv-step <?php echo $current_step === 2 ? 'active' : ($progress['steps']['server_url'] ? 'completed' : ''); ?>">
                        <span class="ipv-step-number">2</span>
                        <span class="ipv-step-title">Server</span>
                    </div>
                    <div class="ipv-step <?php echo $current_step === 3 ? 'active' : ($progress['steps']['activated'] ? 'completed' : ''); ?>">
                        <span class="ipv-step-number">3</span>
                        <span class="ipv-step-title">Licenza</span>
                    </div>
                    <div class="ipv-step <?php echo $current_step === 4 ? 'active' : ($progress['steps']['cron'] ? 'completed' : ''); ?>">
                        <span class="ipv-step-number">4</span>
                        <span class="ipv-step-title">CRON</span>
                    </div>
                    <div class="ipv-step <?php echo $current_step === 5 ? 'active' : ''; ?>">
                        <span class="ipv-step-number">5</span>
                        <span class="ipv-step-title">Test</span>
                    </div>
                </div>

                <!-- Step Content -->
                <div class="ipv-wizard-content">
                    <?php
                    switch ($current_step) {
                        case 1:
                            self::render_step_database($progress);
                            break;
                        case 2:
                            self::render_step_server();
                            break;
                        case 3:
                            self::render_step_license();
                            break;
                        case 4:
                            self::render_step_cron($progress);
                            break;
                        case 5:
                            self::render_step_test();
                            break;
                    }
                    ?>
                </div>
            </div>
        </div>

        <style>
        .ipv-prod-wizard { max-width: 900px; margin: 40px auto; }
        .ipv-progress-bar { height: 10px; background: #e0e0e0; border-radius: 5px; margin: 20px 0; }
        .ipv-progress-fill { height: 100%; background: linear-gradient(90deg, #667eea 0%, #764ba2 100%); border-radius: 5px; transition: width 0.3s; }
        .ipv-progress-text { text-align: center; color: #666; }
        .ipv-wizard-steps { display: flex; justify-content: space-between; margin: 30px 0; }
        .ipv-step { display: flex; flex-direction: column; align-items: center; flex: 1; position: relative; }
        .ipv-step-number { width: 40px; height: 40px; border-radius: 50%; background: #e0e0e0; display: flex; align-items: center; justify-content: center; font-weight: bold; color: #666; margin-bottom: 8px; }
        .ipv-step.active .ipv-step-number { background: #667eea; color: white; }
        .ipv-step.completed .ipv-step-number { background: #10b981; color: white; }
        .ipv-step.completed .ipv-step-number::before { content: "✓"; }
        .ipv-step-title { font-size: 12px; color: #666; }
        .ipv-wizard-content { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-top: 20px; }
        .ipv-wizard-actions { margin-top: 30px; display: flex; justify-content: space-between; }
        .ipv-success-box { background: #d1fae5; border: 1px solid #10b981; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .ipv-warning-box { background: #fef3c7; border: 1px solid #f59e0b; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .ipv-error-box { background: #fee2e2; border: 1px solid #ef4444; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .ipv-info-box { background: #dbeafe; border: 1px solid #3b82f6; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .ipv-form-group { margin: 20px 0; }
        .ipv-form-group label { display: block; font-weight: 600; margin-bottom: 8px; }
        .ipv-form-group input[type="text"] { width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 5px; }
        .ipv-form-group small { color: #6b7280; display: block; margin-top: 5px; }
        </style>
        <?php
    }

    /**
     * Step 1: Database Tables
     */
    private static function render_step_database($progress) {
        ?>
        <h2>📊 Step 1: Creazione Database</h2>

        <?php if ($progress['steps']['tables']): ?>
            <div class="ipv-success-box">
                <strong>✅ Database configurato correttamente!</strong>
                <p>Tutte le 3 tabelle sono state create con successo:</p>
                <ul>
                    <li><code>wp_ipv_prod_queue</code> - Coda import video</li>
                    <li><code>wp_ipv_prod_youtube_cache</code> - Cache dati YouTube</li>
                    <li><code>wp_ipv_prod_metrics</code> - Metriche performance</li>
                </ul>
            </div>
        <?php else: ?>
            <div class="ipv-warning-box">
                <strong>⚠️ Tabelle database non trovate</strong>
                <p>Le tabelle database verranno create automaticamente durante l'attivazione del plugin.</p>
            </div>
        <?php endif; ?>

        <h3>Informazioni Database</h3>
        <table class="widefat">
            <tr>
                <th>Database Host:</th>
                <td><code><?php echo DB_HOST; ?></code></td>
            </tr>
            <tr>
                <th>Database Name:</th>
                <td><code><?php echo DB_NAME; ?></code></td>
            </tr>
            <tr>
                <th>Table Prefix:</th>
                <td><code><?php global $wpdb; echo $wpdb->prefix; ?></code></td>
            </tr>
        </table>

        <div class="ipv-wizard-actions">
            <span></span>
            <a href="<?php echo admin_url('admin.php?page=ipv-prod-setup&step=2'); ?>" class="button button-primary button-large">
                Avanti: Configura Server →
            </a>
        </div>
        <?php
    }

    /**
     * Step 2: Server Configuration
     */
    private static function render_step_server() {
        $server_url = get_option('ipv_prod_server_url');
        $test_result = isset($_GET['test_result']) ? $_GET['test_result'] : null;

        ?>
        <h2>🌐 Step 2: Configurazione Server</h2>
        <p>Inserisci l'URL del server IPV Pro Vendor dove hai attivato il plugin server.</p>

        <?php if ($test_result === 'success'): ?>
            <div class="ipv-success-box">
                <strong>✅ Connessione server riuscita!</strong>
                <p>Il server IPV Pro Vendor è raggiungibile e funzionante.</p>
            </div>
        <?php elseif ($test_result === 'failed'): ?>
            <div class="ipv-error-box">
                <strong>❌ Errore connessione server</strong>
                <p><?php echo esc_html($_GET['test_message'] ?? 'Impossibile connettersi al server'); ?></p>
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <?php wp_nonce_field('ipv_prod_save_settings'); ?>
            <input type="hidden" name="action" value="ipv_prod_save_settings">
            <input type="hidden" name="redirect_step" value="3">

            <div class="ipv-form-group">
                <label for="server_url">
                    Server URL <span style="color: red;">*</span>
                </label>
                <input type="text"
                       id="server_url"
                       name="server_url"
                       value="<?php echo esc_attr($server_url); ?>"
                       placeholder="https://your-vendor-domain.com"
                       required>
                <small>
                    Inserisci l'URL del sito WordPress dove hai installato il plugin IPV Pro Vendor.<br>
                    Esempio: <code>https://ipv-vendor.mysite.com</code>
                </small>
            </div>

            <div class="ipv-info-box">
                <strong>💡 Come trovare il Server URL?</strong>
                <p>Il Server URL è l'indirizzo del sito WordPress dove hai installato il plugin <strong>IPV Pro Vendor</strong> (server SaaS).</p>
            </div>

            <div class="ipv-wizard-actions">
                <a href="<?php echo admin_url('admin.php?page=ipv-prod-setup&step=1'); ?>" class="button button-large">
                    ← Indietro
                </a>
                <div>
                    <button type="submit" name="test_connection" value="1" class="button button-large" style="margin-right: 10px;">
                        🔍 Testa Connessione
                    </button>
                    <button type="submit" class="button button-primary button-large">
                        Salva e Continua →
                    </button>
                </div>
            </div>
        </form>
        <?php
    }

    /**
     * Step 3: License Activation
     */
    private static function render_step_license() {
        $server_url = get_option('ipv_prod_server_url');
        $license_key = get_option('ipv_prod_license_key');
        $license_status = get_option('ipv_prod_license_status');
        $license_plan = get_option('ipv_prod_license_plan');
        $license_credits = get_option('ipv_prod_license_credits');

        ?>
        <h2>🔑 Step 3: Attivazione Licenza</h2>
        <p>Inserisci la license key ricevuta via email dopo l'acquisto.</p>

        <?php if ($license_status === 'active'): ?>
            <div class="ipv-success-box">
                <strong>✅ Licenza attivata con successo!</strong>
                <p>
                    <strong>Piano:</strong> <?php echo esc_html($license_plan); ?><br>
                    <strong>Credits rimanenti:</strong> <?php echo intval($license_credits); ?>
                </p>
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <?php wp_nonce_field('ipv_prod_activate_license'); ?>
            <input type="hidden" name="action" value="ipv_prod_activate_license">

            <div class="ipv-form-group">
                <label for="license_key">
                    License Key <span style="color: red;">*</span>
                </label>
                <input type="text"
                       id="license_key"
                       name="license_key"
                       value="<?php echo esc_attr($license_key); ?>"
                       placeholder="XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX"
                       required
                       style="font-family: monospace; letter-spacing: 2px;">
                <small>
                    Inserisci la license key di 32 caratteri che hai ricevuto via email.<br>
                    Esempio: <code>A1B2C3D4E5F6G7H8I9J0K1L2M3N4O5P6</code>
                </small>
            </div>

            <?php if (empty($server_url)): ?>
                <div class="ipv-error-box">
                    <strong>❌ Server URL non configurato</strong>
                    <p>Torna allo step precedente e configura il Server URL prima di attivare la licenza.</p>
                </div>
            <?php endif; ?>

            <div class="ipv-info-box">
                <strong>💡 Come ottenere una license key?</strong>
                <ol>
                    <li>Acquista un piano IPV Pro sul sito del vendor</li>
                    <li>Riceverai una email con la license key</li>
                    <li>Copia e incolla la license key qui sopra</li>
                </ol>
                <p><strong>Non hai ancora una licenza?</strong> Acquistala su: <a href="<?php echo esc_url($server_url); ?>" target="_blank"><?php echo esc_html($server_url); ?></a></p>
            </div>

            <div class="ipv-wizard-actions">
                <a href="<?php echo admin_url('admin.php?page=ipv-prod-setup&step=2'); ?>" class="button button-large">
                    ← Indietro
                </a>
                <button type="submit" class="button button-primary button-large" <?php echo empty($server_url) ? 'disabled' : ''; ?>>
                    🔐 Attiva Licenza →
                </button>
            </div>
        </form>
        <?php
    }

    /**
     * Step 4: CRON Setup
     */
    private static function render_step_cron($progress) {
        $site_path = ABSPATH;

        ?>
        <h2>⏰ Step 4: Configurazione CRON</h2>
        <p>Il sistema CRON automatizza l'elaborazione dei video in coda.</p>

        <?php if ($progress['steps']['cron']): ?>
            <div class="ipv-success-box">
                <strong>✅ WordPress CRON configurato!</strong>
                <p>Il CRON di WordPress elaborerà automaticamente la coda video.</p>
            </div>
        <?php endif; ?>

        <div class="ipv-info-box">
            <strong>📌 Opzione 1: WordPress CRON (Default)</strong>
            <p>Il plugin usa automaticamente il CRON interno di WordPress. Nessuna configurazione richiesta!</p>
            <ul>
                <li>✅ Processamento coda: ogni 5 minuti</li>
                <li>✅ Aggiornamento YouTube: ogni ora</li>
                <li>✅ Cleanup cache: ogni giorno alle 02:00</li>
            </ul>
        </div>

        <div class="ipv-warning-box">
            <strong>⚙️ Opzione 2: System CRON (Raccomandato per siti ad alto traffico)</strong>
            <p>Per migliori performance, usa il CRON del sistema operativo:</p>
            <ol>
                <li>Disabilita WordPress CRON in <code>wp-config.php</code>:
                    <pre style="background: #f3f4f6; padding: 10px; border-radius: 5px; overflow-x: auto;">define('DISABLE_WP_CRON', true);</pre>
                </li>
                <li>Configura il CRON del server (<code>crontab -e -u www-data</code>):
                    <pre style="background: #f3f4f6; padding: 10px; border-radius: 5px; overflow-x: auto;"># IPV Pro - Queue processing (every 5 minutes)
*/5 * * * * wp ipv-prod queue run --path=<?php echo esc_html($site_path); ?> --quiet

# IPV Pro - YouTube update (every hour)
0 * * * * wp ipv-prod youtube update --path=<?php echo esc_html($site_path); ?> --quiet</pre>
                </li>
            </ol>
        </div>

        <div class="ipv-wizard-actions">
            <a href="<?php echo admin_url('admin.php?page=ipv-prod-setup&step=3'); ?>" class="button button-large">
                ← Indietro
            </a>
            <a href="<?php echo admin_url('admin.php?page=ipv-prod-setup&step=5'); ?>" class="button button-primary button-large">
                Avanti: Test Importazione →
            </a>
        </div>
        <?php
    }

    /**
     * Step 5: Test Import
     */
    private static function render_step_test() {
        $progress = IPV_Prod_Auto_Installer::get_setup_progress();
        $is_complete = $progress['percentage'] >= 80;

        ?>
        <h2>🎉 Step 5: Test e Completamento</h2>

        <?php if ($is_complete): ?>
            <div class="ipv-success-box">
                <strong>✅ Setup completato con successo!</strong>
                <p>IPV Production System Pro è configurato e pronto all'uso.</p>
            </div>

            <h3>🚀 Prossimi Passi</h3>
            <ol>
                <li><strong>Importa il tuo primo video:</strong>
                    <a href="<?php echo admin_url('admin.php?page=ipv-prod-import'); ?>" class="button button-primary">
                        📹 Importa Video
                    </a>
                </li>
                <li><strong>Visualizza la coda:</strong>
                    <a href="<?php echo admin_url('admin.php?page=ipv-prod-queue'); ?>" class="button">
                        📋 Vedi Coda
                    </a>
                </li>
                <li><strong>Aggiungi Video Wall al sito:</strong>
                    <p>Inserisci questo shortcode in una pagina: <code>[ipv_video_wall]</code></p>
                </li>
            </ol>

            <h3>📊 Riepilogo Configurazione</h3>
            <ul>
                <li><?php echo $progress['steps']['tables'] ? '✅' : '❌'; ?> Database: Tabelle create</li>
                <li><?php echo $progress['steps']['server_url'] ? '✅' : '⚠️'; ?> Server: URL configurato</li>
                <li><?php echo $progress['steps']['license'] ? '✅' : '⚠️'; ?> License: Key inserita</li>
                <li><?php echo $progress['steps']['activated'] ? '✅' : '⚠️'; ?> Activation: Licenza attivata</li>
                <li><?php echo $progress['steps']['cron'] ? '✅' : '⚠️'; ?> CRON: Schedulato</li>
            </ul>

        <?php else: ?>
            <div class="ipv-warning-box">
                <strong>⚠️ Setup non completo</strong>
                <p>Alcuni passaggi richiedono attenzione. Completa la configurazione per usare IPV Pro.</p>
            </div>
        <?php endif; ?>

        <h3>📚 Documentazione</h3>
        <ul>
            <li><a href="<?php echo plugins_url('README.md', dirname(__FILE__)); ?>">README.md</a> - Guida completa</li>
            <li><a href="<?php echo admin_url('admin.php?page=ipv-prod-settings'); ?>">Impostazioni</a> - Configurazione avanzata</li>
            <li><a href="https://github.com/daniemi1977/ipv" target="_blank">GitHub Repository</a></li>
        </ul>

        <div class="ipv-wizard-actions">
            <a href="<?php echo admin_url('admin.php?page=ipv-prod-setup&step=4'); ?>" class="button button-large">
                ← Indietro
            </a>
            <a href="<?php echo admin_url('admin.php?page=ipv-prod'); ?>" class="button button-primary button-large">
                Vai al Dashboard →
            </a>
        </div>
        <?php
    }

    /**
     * Save settings from wizard
     */
    public static function save_settings() {
        check_admin_referer('ipv_prod_save_settings');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $server_url = isset($_POST['server_url']) ? esc_url_raw($_POST['server_url']) : '';
        $test_connection = isset($_POST['test_connection']);

        if (!empty($server_url)) {
            update_option('ipv_prod_server_url', $server_url);

            // Test connection if requested
            if ($test_connection) {
                $result = IPV_Prod_Auto_Installer::test_server_connection($server_url);

                $redirect_url = admin_url('admin.php?page=ipv-prod-setup&step=2');
                $redirect_url .= '&test_result=' . ($result['success'] ? 'success' : 'failed');
                $redirect_url .= '&test_message=' . urlencode($result['message']);

                wp_safe_redirect($redirect_url);
                exit;
            }
        }

        $redirect_step = isset($_POST['redirect_step']) ? intval($_POST['redirect_step']) : 3;
        wp_safe_redirect(admin_url('admin.php?page=ipv-prod-setup&step=' . $redirect_step . '&saved=1'));
        exit;
    }

    /**
     * Activate license
     */
    public static function activate_license() {
        check_admin_referer('ipv_prod_activate_license');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $server_url = get_option('ipv_prod_server_url');
        $license_key = isset($_POST['license_key']) ? sanitize_text_field($_POST['license_key']) : '';

        if (empty($server_url)) {
            wp_safe_redirect(admin_url('admin.php?page=ipv-prod-setup&step=3&error=no_server'));
            exit;
        }

        if (empty($license_key)) {
            wp_safe_redirect(admin_url('admin.php?page=ipv-prod-setup&step=3&error=no_license'));
            exit;
        }

        $result = IPV_Prod_Auto_Installer::activate_license($server_url, $license_key);

        if ($result['success']) {
            wp_safe_redirect(admin_url('admin.php?page=ipv-prod-setup&step=4&activated=1'));
        } else {
            wp_safe_redirect(admin_url('admin.php?page=ipv-prod-setup&step=3&error=' . urlencode($result['message'])));
        }
        exit;
    }
}
