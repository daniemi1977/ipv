<?php
/**
 * Setup Wizard for IPV Pro Vendor Server
 *
 * Interactive installation wizard with step-by-step configuration
 *
 * @package IPV_Pro_Vendor
 * @version 1.4.0-optimized
 */

if (!defined('ABSPATH')) {
    exit;
}

class IPV_Pro_Vendor_Setup_Wizard {

    /**
     * Initialize wizard
     */
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_wizard_page'));
        add_action('admin_init', array(__CLASS__, 'redirect_to_wizard'));
        add_action('admin_post_ipv_vendor_save_settings', array(__CLASS__, 'save_settings'));
        add_action('admin_post_ipv_vendor_create_products', array(__CLASS__, 'create_products')); // v1.4.1-FIXED3
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
    }

    /**
     * Redirect to wizard after activation
     */
    public static function redirect_to_wizard() {
        if (get_transient('ipv_vendor_show_wizard')) {
            delete_transient('ipv_vendor_show_wizard');

            // Check if setup is complete (v1.4.1 - direct check without auto-installer dependency)
            $is_setup_complete = get_option('ipv_vendor_setup_complete', false);

            if (!$is_setup_complete) {
                wp_safe_redirect(admin_url('admin.php?page=ipv-vendor-setup'));
                exit;
            }
        }
    }

    /**
     * Get setup progress (v1.4.1 - independent from auto-installer)
     */
    private static function get_setup_progress() {
        global $wpdb;

        $progress = [
            'steps' => [
                'tables' => false,
                'api_keys' => false,
                'woocommerce' => false,
                'products' => false
            ],
            'completed' => 0,
            'total' => 4,
            'percentage' => 0
        ];

        // Check database tables
        $table = $wpdb->prefix . 'ipv_licenses';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") == $table) {
            $progress['steps']['tables'] = true;
            $progress['completed']++;
        }

        // Check API keys (use same option names as settings page and API gateway)
        if (get_option('ipv_supadata_api_key_1') && get_option('ipv_openai_api_key')) {
            $progress['steps']['api_keys'] = true;
            $progress['completed']++;
        }

        // Check WooCommerce
        if (class_exists('WooCommerce')) {
            $progress['steps']['woocommerce'] = true;
            $progress['completed']++;
        }

        // Check products
        $products = wc_get_products(['limit' => 1, 'status' => 'publish']);
        if (!empty($products)) {
            $progress['steps']['products'] = true;
            $progress['completed']++;
        }

        $progress['percentage'] = round(($progress['completed'] / $progress['total']) * 100);

        return $progress;
    }

    /**
     * Add wizard page to admin menu
     * v1.4.2-FIXED9: Registrato come submenu di ipv-vendor-dashboard per fix permessi
     */
    public static function add_wizard_page() {
        // Registrato come submenu nascosto MA collegato al parent corretto
        add_submenu_page(
            'ipv-vendor-dashboard', // Parent slug - CRITICAL: deve essere il menu principale!
            'IPV Pro Vendor Setup',
            'Setup Wizard',
            'manage_options',
            'ipv-vendor-setup',
            array(__CLASS__, 'render_wizard')
        );
    }

    /**
     * Enqueue CSS/JS assets
     */
    public static function enqueue_assets($hook) {
        if ($hook !== 'admin_page_ipv-vendor-setup') {
            return;
        }

        wp_enqueue_style('ipv-vendor-wizard', plugins_url('assets/css/wizard.css', dirname(__FILE__)), array(), '1.4.0');
    }

    /**
     * Render setup wizard
     */
    public static function render_wizard() {
        $progress = self::get_setup_progress();
        $current_step = isset($_GET['step']) ? intval($_GET['step']) : 1;

        ?>
        <div class="wrap ipv-vendor-wizard">
            <h1>🚀 IPV Pro Vendor - Setup Wizard</h1>
            <p>Configurazione guidata del server SaaS. Completa tutti i passaggi per iniziare.</p>

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
                    <div class="ipv-step <?php echo $current_step === 2 ? 'active' : ($progress['steps']['api_keys'] ? 'completed' : ''); ?>">
                        <span class="ipv-step-number">2</span>
                        <span class="ipv-step-title">API Keys</span>
                    </div>
                    <div class="ipv-step <?php echo $current_step === 3 ? 'active' : ($progress['steps']['woocommerce'] ? 'completed' : ''); ?>">
                        <span class="ipv-step-number">3</span>
                        <span class="ipv-step-title">WooCommerce</span>
                    </div>
                    <div class="ipv-step <?php echo $current_step === 4 ? 'active' : ($progress['steps']['products'] ? 'completed' : ''); ?>">
                        <span class="ipv-step-number">4</span>
                        <span class="ipv-step-title">Prodotti</span>
                    </div>
                    <div class="ipv-step <?php echo $current_step === 5 ? 'active' : ''; ?>">
                        <span class="ipv-step-number">5</span>
                        <span class="ipv-step-title">Completo</span>
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
                            self::render_step_api_keys();
                            break;
                        case 3:
                            self::render_step_woocommerce($progress);
                            break;
                        case 4:
                            self::render_step_products();
                            break;
                        case 5:
                            self::render_step_complete();
                            break;
                    }
                    ?>
                </div>
            </div>
        </div>

        <style>
        .ipv-vendor-wizard { max-width: 900px; margin: 40px auto; }
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
                <p>Tutte le 5 tabelle sono state create con successo:</p>
                <ul>
                    <li><code>wp_ipv_licenses</code> - Gestione licenze</li>
                    <li><code>wp_ipv_license_activations</code> - Attivazioni siti</li>
                    <li><code>wp_ipv_api_logs</code> - Audit log API</li>
                    <li><code>wp_ipv_security_log</code> - Eventi sicurezza</li>
                    <li><code>wp_ipv_performance_stats</code> - Statistiche performance</li>
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
            <tr>
                <th>MySQL Version:</th>
                <td><code><?php echo $wpdb->db_version(); ?></code></td>
            </tr>
        </table>

        <div class="ipv-wizard-actions">
            <span></span>
            <a href="<?php echo admin_url('admin.php?page=ipv-vendor-setup&step=2'); ?>" class="button button-primary button-large">
                Avanti: Configura API Keys →
            </a>
        </div>
        <?php
    }

    /**
     * Step 2: API Keys Configuration
     */
    private static function render_step_api_keys() {
        // Use same option names as settings page and API gateway
        $youtube_key = get_option('ipv_youtube_api_key');
        $openai_key = get_option('ipv_openai_api_key');
        $supadata_key = get_option('ipv_supadata_api_key_1');

        ?>
        <h2>🔑 Step 2: Configurazione API Keys</h2>
        <p>Inserisci le chiavi API necessarie per il funzionamento del sistema.</p>

        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <?php wp_nonce_field('ipv_vendor_save_settings'); ?>
            <input type="hidden" name="action" value="ipv_vendor_save_settings">
            <input type="hidden" name="redirect_step" value="3">

            <div class="ipv-form-group">
                <label for="youtube_api_key">
                    YouTube Data API Key <span style="color: red;">*</span>
                </label>
                <input type="text"
                       id="youtube_api_key"
                       name="youtube_api_key"
                       value="<?php echo esc_attr($youtube_key); ?>"
                       placeholder="AIzaSy..."
                       required>
                <small>
                    Ottieni la tua chiave su: <a href="https://console.cloud.google.com/apis/credentials" target="_blank">Google Cloud Console</a><br>
                    Abilita "YouTube Data API v3" nel progetto.
                </small>
            </div>

            <div class="ipv-form-group">
                <label for="openai_api_key">
                    OpenAI API Key <span style="color: red;">*</span>
                </label>
                <input type="text"
                       id="openai_api_key"
                       name="openai_api_key"
                       value="<?php echo esc_attr($openai_key); ?>"
                       placeholder="sk-..."
                       required>
                <small>
                    Ottieni la tua chiave su: <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI Platform</a><br>
                    Richiesto per generazione contenuti AI (GPT-4).
                </small>
            </div>

            <div class="ipv-form-group">
                <label for="supadata_key">
                    SupaData API Key <span style="color: red;">*</span>
                </label>
                <input type="text"
                       id="supadata_key"
                       name="supadata_key"
                       value="<?php echo esc_attr($supadata_key); ?>"
                       placeholder="sd_..."
                       required>
                <small>
                    Ottieni le tue credenziali su: <a href="https://supadata.ai" target="_blank">SupaData.ai</a><br>
                    Richiesto per transcript YouTube.
                </small>
            </div>

            <div class="ipv-warning-box">
                <strong>💡 Suggerimento:</strong> Puoi saltare questo passaggio e configurare le API Keys in seguito in <strong>Impostazioni → IPV Pro Vendor</strong>.
            </div>

            <div class="ipv-wizard-actions">
                <a href="<?php echo admin_url('admin.php?page=ipv-vendor-setup&step=1'); ?>" class="button button-large">
                    ← Indietro
                </a>
                <button type="submit" class="button button-primary button-large">
                    Salva e Continua →
                </button>
            </div>
        </form>
        <?php
    }

    /**
     * Step 3: WooCommerce Check
     */
    private static function render_step_woocommerce($progress) {
        $wc_installed = class_exists('WooCommerce');
        $wc_subs_installed = class_exists('WC_Subscriptions');

        ?>
        <h2>🛒 Step 3: WooCommerce Setup</h2>

        <?php if ($wc_installed): ?>
            <div class="ipv-success-box">
                <strong>✅ WooCommerce è installato!</strong>
                <p>Versione: <code><?php echo WC()->version; ?></code></p>
            </div>
        <?php else: ?>
            <div class="ipv-error-box">
                <strong>❌ WooCommerce non trovato</strong>
                <p>WooCommerce è richiesto per gestire vendite e licenze.</p>
                <a href="<?php echo admin_url('plugin-install.php?s=woocommerce&tab=search&type=term'); ?>" class="button button-primary">
                    Installa WooCommerce
                </a>
            </div>
        <?php endif; ?>

        <?php if ($wc_subs_installed): ?>
            <div class="ipv-success-box">
                <strong>✅ WooCommerce Subscriptions attivo!</strong>
                <p>Potrai vendere abbonamenti ricorrenti mensili.</p>
            </div>
        <?php else: ?>
            <div class="ipv-warning-box">
                <strong>⚠️ WooCommerce Subscriptions non trovato</strong>
                <p>Plugin opzionale ma raccomandato per abbonamenti ricorrenti.</p>
                <small>Puoi anche vendere licenze one-time senza Subscriptions.</small>
            </div>
        <?php endif; ?>

        <h3>Configurazione Gateway Pagamento</h3>
        <?php if ($wc_installed): ?>
            <p>Configura il tuo gateway di pagamento preferito:</p>
            <ul>
                <li><a href="<?php echo admin_url('admin.php?page=wc-settings&tab=checkout&section=stripe'); ?>">Stripe</a> (Raccomandato)</li>
                <li><a href="<?php echo admin_url('admin.php?page=wc-settings&tab=checkout&section=paypal'); ?>">PayPal</a></li>
                <li><a href="<?php echo admin_url('admin.php?page=wc-settings&tab=checkout'); ?>">Altri gateway</a></li>
            </ul>
        <?php endif; ?>

        <div class="ipv-wizard-actions">
            <a href="<?php echo admin_url('admin.php?page=ipv-vendor-setup&step=2'); ?>" class="button button-large">
                ← Indietro
            </a>
            <?php if ($wc_installed): ?>
                <a href="<?php echo admin_url('admin.php?page=ipv-vendor-setup&step=4'); ?>" class="button button-primary button-large">
                    Avanti: Crea Prodotti →
                </a>
            <?php else: ?>
                <span class="button button-primary button-large disabled">
                    Installa WooCommerce per continuare
                </span>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Step 4: Create Products (v1.4.2-FIXED5)
     */
    private static function render_step_products() {
        // Get plans from Plans Manager
        $plans_manager = IPV_Vendor_Plans_Manager::instance();
        $plans = $plans_manager->get_plans();
        ?>
        <h2>📦 Step 4: Creazione Prodotti IPV Pro</h2>
        <p>Crea i prodotti WooCommerce dai piani SaaS configurati.</p>

        <div class="ipv-warning-box">
            <strong>💡 Nota:</strong> I prodotti verranno creati basandosi sui piani configurati in <strong>IPV Pro Vendor → Piani SaaS</strong>
        </div>

        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <?php wp_nonce_field('ipv_vendor_create_products'); ?>
            <input type="hidden" name="action" value="ipv_vendor_create_products">

            <h3>📋 Piani Disponibili</h3>
            <table class="widefat" id="pricing-table">
                <thead>
                    <tr>
                        <th>Piano</th>
                        <th>Prezzo</th>
                        <th>Crediti</th>
                        <th>Attivazioni</th>
                        <th>Descrizione</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $row_num = 0;
                    foreach ($plans as $slug => $plan) :
                        if (empty($plan['is_active'])) continue;
                        $row_num++;
                        $bg = ($row_num % 2 === 0) ? 'background: #f9fafb;' : '';

                        // Format price
                        $price_display = '€' . number_format($plan['price'], 2);
                        if ($plan['price_period'] === 'month') {
                            $price_display .= '/mese';
                        } elseif ($plan['price_period'] === 'year') {
                            $price_display .= '/anno';
                        } else {
                            $price_display .= ' (unico)';
                        }

                        // Format credits
                        $credits_display = $plan['credits'] . ' video/';
                        $credits_display .= ($plan['credits_period'] === 'year') ? 'anno' : 'mese';
                    ?>
                    <tr style="<?php echo $bg; ?>">
                        <td><strong><?php echo esc_html($plan['name']); ?></strong></td>
                        <td><?php echo $price_display; ?></td>
                        <td><?php echo $credits_display; ?></td>
                        <td><?php echo esc_html($plan['activations']); ?> sito/i</td>
                        <td><?php echo esc_html($plan['description']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div style="margin-top: 20px; padding: 15px; background: #fef3c7; border: 2px solid #f59e0b; border-radius: 8px;">
                <strong>📝 Nota Importante:</strong> Se modifichi i piani in <em>Piani SaaS</em>, dovrai ricreare i prodotti o aggiornarli manualmente.
            </div>

            <div style="margin-top: 25px;">
                <button type="submit" class="button button-primary button-large">
                    🚀 Crea Prodotti Automaticamente
                </button>
                <a href="<?php echo admin_url('admin.php?page=ipv-vendor-plans'); ?>" class="button button-large">
                    ⚙️ Gestisci Piani SaaS
                </a>
                <a href="<?php echo admin_url('post-new.php?post_type=product'); ?>" class="button button-large">
                    ✏️ Crea Manualmente
                </a>
            </div>
        </form>

        <div class="ipv-wizard-actions">
            <a href="<?php echo admin_url('admin.php?page=ipv-vendor-setup&step=3'); ?>" class="button button-large">
                ← Indietro
            </a>
            <a href="<?php echo admin_url('admin.php?page=ipv-vendor-setup&step=5'); ?>" class="button button-primary button-large">
                Avanti: Completa Setup →
            </a>
        </div>
        <?php
    }

    /**
     * Save settings from Step 2 (API Keys)
     */
    public static function save_settings() {
        check_admin_referer('ipv_vendor_save_settings');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        // Save YouTube API Key (same option name as settings page)
        if (isset($_POST['youtube_api_key'])) {
            update_option('ipv_youtube_api_key', sanitize_text_field($_POST['youtube_api_key']));
        }

        // Save OpenAI API Key (same option name as settings page)
        if (isset($_POST['openai_api_key'])) {
            update_option('ipv_openai_api_key', sanitize_text_field($_POST['openai_api_key']));
        }

        // Save SupaData API Key (same option name as settings page - uses key slots 1-3)
        if (isset($_POST['supadata_key'])) {
            update_option('ipv_supadata_api_key_1', sanitize_text_field($_POST['supadata_key']));
        }

        $redirect_step = isset($_POST['redirect_step']) ? intval($_POST['redirect_step']) : 3;
        wp_safe_redirect(admin_url('admin.php?page=ipv-vendor-setup&step=' . $redirect_step . '&saved=1'));
        exit;
    }

    /**
     * Create IPV Pro products automatically from SaaS Plans
     * v1.4.5:
     * - Trial & Extra Credits: 1 prodotto (una tantum)
     * - Subscription plans: 2 prodotti (Mensile + Annuale)
     * - Annuale: prezzo × 10 (sconto 2 mesi), crediti × 12
     */
    public static function create_products() {
        // Verify nonce
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'ipv_vendor_create_products')) {
            wp_die('Security check failed. Please try again.', 'Security Error', array('back_link' => true));
        }

        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_die(
                'You do not have sufficient permissions to access this page.',
                'Permission Error',
                array(
                    'back_link' => true,
                    'response' => 403
                )
            );
        }

        // Check WooCommerce
        if (!class_exists('WooCommerce')) {
            wp_die('WooCommerce non è installato!', 'WooCommerce Required', array('back_link' => true));
        }

        // Get plans from Plans Manager
        $plans_manager = IPV_Vendor_Plans_Manager::instance();
        $plans = $plans_manager->get_plans();

        $created = 0;
        $skipped = 0;

        // Billing types for subscription plans
        $billing_types = [
            'monthly' => [
                'label' => 'Mensile',
                'price_multiplier' => 1,
                'credits_multiplier' => 1,
                'period_label' => 'mese'
            ],
            'yearly' => [
                'label' => 'Annuale',
                'price_multiplier' => 10, // 10 mesi invece di 12 (sconto 2 mesi)
                'credits_multiplier' => 12, // crediti annuali = mensili × 12
                'period_label' => 'anno'
            ]
        ];

        foreach ($plans as $slug => $plan) {
            // Skip inactive plans
            if (empty($plan['is_active'])) {
                continue;
            }

            // Determine if credits are recurring or one-time
            $is_once = ($plan['price_period'] === 'once');
            $is_extra_credits = (strpos($slug, 'extra_credits') === 0);
            $is_trial = ($slug === 'trial');
            $is_digital_asset = (!empty($plan['product_type']) && $plan['product_type'] === 'digital_asset');

            // For "once" plans (Trial, Extra Credits): create 1 product
            // For "month" plans (Subscriptions): create 2 products (Monthly + Yearly)
            $billing_variations = $is_once ? ['once'] : ['monthly', 'yearly'];

            foreach ($billing_variations as $billing_type) {
                // Build product name
                if ($is_once) {
                    $product_name = 'IPV Pro - ' . $plan['name'];
                    $billing_label = '';
                    $price_multiplier = 1;
                    $credits_multiplier = 1;
                    $period_label = 'una tantum';
                } else {
                    $billing_config = $billing_types[$billing_type];
                    $product_name = 'IPV Pro - ' . $plan['name'] . ' (' . $billing_config['label'] . ')';
                    $billing_label = $billing_config['label'];
                    $price_multiplier = $billing_config['price_multiplier'];
                    $credits_multiplier = $billing_config['credits_multiplier'];
                    $period_label = $billing_config['period_label'];
                }

                // Check if product already exists
                $existing = wc_get_products([
                    'name' => $product_name,
                    'limit' => 1,
                    'status' => ['publish', 'draft']
                ]);

                if (!empty($existing)) {
                    $skipped++;
                    continue; // Skip if exists
                }

                // Calculate price and credits
                $final_price = $plan['price'] * $price_multiplier;
                $final_credits = $plan['credits'] * $credits_multiplier;

                // Build credits label
                if ($is_once) {
                    $credits_label = $final_credits . ' crediti';
                } else {
                    $credits_label = $final_credits . ' video/' . $period_label;
                }

                // === BUILD DESCRIPTIONS BASED ON PLAN TYPE ===

                if ($is_trial) {
                // TRIAL: Crediti di benvenuto, non si rinnovano, non scade
                $short_desc = 'Piano Trial gratuito - 10 crediti di benvenuto. Non scade mai.';
                
                $desc = '<h3>IPV Production System Pro - Piano Trial</h3>';
                $desc .= '<p>Prova gratuita per testare tutte le funzionalita del sistema.</p>';
                $desc .= '<h4>Cosa Include:</h4>';
                $desc .= '<ul>';
                $desc .= '<li><strong>10 crediti di benvenuto</strong> (una tantum, non si rinnovano)</li>';
                $desc .= '<li><strong>Non scade mai</strong> - usa i crediti quando vuoi</li>';
                $desc .= '<li><strong>1 sito</strong> WordPress attivabile</li>';
                $desc .= '<li>Trascrizioni automatiche incluse</li>';
                $desc .= '<li>Descrizioni AI automatiche</li>';
                $desc .= '</ul>';
                $desc .= '<h4>Dopo i 10 crediti:</h4>';
                $desc .= '<p>Puoi acquistare <strong>Crediti Extra</strong> a pacchetti di 10 (0,50 EUR/credito) oppure passare a un piano con crediti mensili.</p>';
                $desc .= '<p><strong>Prezzo:</strong> Gratuito</p>';
                
            } elseif ($is_extra_credits) {
                // EXTRA CREDITS: Pacchetto crediti, non scadono
                $short_desc = 'Pacchetto 10 crediti extra - 0,50 EUR/credito. Non scadono mai.';
                
                $desc = '<h3>IPV Production System Pro - Crediti Extra</h3>';
                $desc .= '<p>Acquista crediti aggiuntivi per il tuo account IPV Pro.</p>';
                $desc .= '<h4>Dettagli:</h4>';
                $desc .= '<ul>';
                $desc .= '<li><strong>10 crediti</strong> per pacchetto</li>';
                $desc .= '<li><strong>0,50 EUR</strong> per credito</li>';
                $desc .= '<li><strong>Non scadono mai</strong> - usali quando vuoi</li>';
                $desc .= '<li>Si sommano ai crediti del tuo piano attivo</li>';
                $desc .= '<li>Ideale per picchi di utilizzo occasionali</li>';
                $desc .= '</ul>';
                $desc .= '<p><strong>Prezzo:</strong> 5,00 EUR (10 crediti)</p>';

            } elseif ($is_digital_asset) {
                // DIGITAL ASSET: Golden prompt - Asset scaricabile una volta
                $short_desc = $plan['name'] . ' - Prompt AI Premium ottimizzati (download sicuro, 1 sito)';

                $desc = '<h3>IPV Production System Pro - ' . esc_html($plan['name']) . '</h3>';
                $desc .= '<p><strong>⚠️ PRODOTTO DIGITALE UNICO - NON DUPLICABILE</strong></p>';
                $desc .= '<p>Collezione esclusiva di prompt AI premium ottimizzati per ottenere il massimo dalle descrizioni video.</p>';

                $desc .= '<h4>Caratteristiche:</h4>';
                $desc .= '<ul>';
                $desc .= '<li><strong>Download sicuro dal server</strong> - Legato alla tua licenza</li>';
                $desc .= '<li><strong>1 sola attivazione</strong> - Utilizzabile su 1 sito WordPress</li>';
                $desc .= '<li><strong>1 solo download</strong> - Dopo il primo download, l\'accesso viene disabilitato</li>';
                $desc .= '<li><strong>Non copiabile</strong> - Sistema anti-pirateria integrato</li>';
                $desc .= '<li><strong>Supporto prioritario</strong> incluso</li>';
                $desc .= '</ul>';

                $desc .= '<h4>Come Funziona:</h4>';
                $desc .= '<ol>';
                $desc .= '<li>Acquista il prodotto e ricevi la licenza</li>';
                $desc .= '<li>Attiva la licenza sul tuo sito WordPress</li>';
                $desc .= '<li>Dal pannello IPV Pro, clicca su "Scarica Golden Prompt"</li>';
                $desc .= '<li>Il file verrà scaricato dal server e il link scadrà automaticamente</li>';
                $desc .= '<li>Installa i prompt e inizia a usarli</li>';
                $desc .= '</ol>';

                $desc .= '<p><strong>IMPORTANTE:</strong> Questo è un acquisto una tantum. Il download è limitato a 1 volta per motivi di sicurezza. Conserva il file scaricato in un luogo sicuro.</p>';
                $desc .= '<p><strong>Prezzo:</strong> ' . number_format($plan['price'], 2, ',', '.') . ' EUR (acquisto una tantum)</p>';

                } else {
                    // SUBSCRIPTION PLANS: Starter, Professional, Business, Executive
                    $short_desc = 'Piano ' . $plan['name'] . ' (' . $billing_label . ') - ' . $credits_label . '. ' . $plan['description'];

                    $desc = '<h3>IPV Production System Pro - Piano ' . esc_html($plan['name']) . ' (' . $billing_label . ')</h3>';
                    $desc .= '<p>' . esc_html($plan['description']) . '</p>';

                    // Add savings info for yearly plans
                    if ($billing_type === 'yearly') {
                        $monthly_total = $plan['price'] * 12;
                        $yearly_price = $final_price;
                        $savings = $monthly_total - $yearly_price;
                        $savings_percent = round(($savings / $monthly_total) * 100);
                        $desc .= '<p style="color: #10b981; font-weight: bold;">RISPARMIA ' . number_format($savings, 2, ',', '.') . ' EUR all\'anno (-' . $savings_percent . '%)!</p>';
                    }

                    $desc .= '<h4>Cosa Include:</h4>';
                    $desc .= '<ul>';
                    $desc .= '<li><strong>' . intval($final_credits) . ' crediti/' . $period_label . '</strong> (si rinnovano automaticamente)</li>';
                    $desc .= '<li><strong>' . intval($plan['activations']) . ' sito/i</strong> WordPress attivabili</li>';

                    if (!empty($plan['features']['transcription'])) {
                        $desc .= '<li>Trascrizioni automatiche incluse</li>';
                    }
                    if (!empty($plan['features']['ai_description'])) {
                        $desc .= '<li>Descrizioni AI automatiche</li>';
                    }
                    if (!empty($plan['features']['priority_support'])) {
                        $desc .= '<li>Supporto prioritario</li>';
                    }
                    if (!empty($plan['features']['api_access'])) {
                        $desc .= '<li>Accesso API completo</li>';
                    }
                    $desc .= '</ul>';

                    $desc .= '<h4>Funzionalita Principali:</h4>';
                    $desc .= '<ul>';
                    $desc .= '<li>Trascrizione automatica video YouTube</li>';
                    $desc .= '<li>Generazione descrizioni con AI (GPT-4)</li>';
                    $desc .= '<li>Download automatico thumbnail HD</li>';
                    $desc .= '<li>Import singolo e massivo</li>';
                    $desc .= '<li>Video Wall personalizzabile</li>';
                    $desc .= '<li>Dashboard analytics completa</li>';
                    $desc .= '</ul>';

                    // Price info
                    $price_info = number_format($final_price, 2, ',', '.') . ' EUR/' . $period_label;
                    $desc .= '<p><strong>Prezzo:</strong> ' . $price_info . '</p>';
                }

                // Create product
                $product = new WC_Product_Simple();
                $product->set_name($product_name);
                $product->set_status('publish');
                $product->set_catalog_visibility('visible');
                $product->set_short_description($short_desc);
                $product->set_description($desc);
                $product->set_regular_price($final_price);
                $product->set_virtual(true);
                $product->set_downloadable(false);

                // Add custom meta
                $product->update_meta_data('_ipv_plan_slug', $slug);
                $product->update_meta_data('_ipv_variant_slug', $slug); // Backward compatibility
                $product->update_meta_data('_ipv_credits_total', $final_credits);
                $product->update_meta_data('_ipv_activation_limit', $plan['activations']);
                $product->update_meta_data('_ipv_is_license_product', 'yes');

                // Billing type metadata
                if ($is_once) {
                    $product->update_meta_data('_ipv_billing_type', 'once');
                    $product->update_meta_data('_ipv_credits_period', 'once');
                } else {
                    $product->update_meta_data('_ipv_billing_type', $billing_type); // monthly or yearly
                    $product->update_meta_data('_ipv_credits_period', $billing_type === 'yearly' ? 'year' : 'month');
                }

                // Product type based on plan
                if ($is_digital_asset) {
                    $product->update_meta_data('_ipv_product_type', 'digital_asset');
                    $product->update_meta_data('_ipv_download_limit', !empty($plan['download_limit']) ? $plan['download_limit'] : 1);
                    $product->update_meta_data('_ipv_remote_download', true);
                } elseif ($is_extra_credits) {
                    $product->update_meta_data('_ipv_product_type', 'extra_credits');
                } elseif ($is_trial) {
                    $product->update_meta_data('_ipv_product_type', 'trial');
                } else {
                    $product->update_meta_data('_ipv_product_type', 'subscription');
                }

                $product_id = $product->save();

                if ($product_id) {
                    $created++;
                }
            }
        }

        wp_safe_redirect(admin_url('admin.php?page=ipv-vendor-setup&step=5&created=' . $created . '&skipped=' . $skipped));
        exit;
    }

    /**
     * Step 5: Setup Complete
     */
    private static function render_step_complete() {
        $created = isset($_GET['created']) ? intval($_GET['created']) : 0;
        $skipped = isset($_GET['skipped']) ? intval($_GET['skipped']) : 0;

        ?>
        <h2>🎉 Setup Completato!</h2>

        <div class="ipv-success-box">
            <strong>✅ Configurazione completata con successo!</strong>
            <p>IPV Pro Vendor è ora pronto per vendere licenze ai tuoi clienti.</p>
        </div>

        <?php if ($created > 0 || $skipped > 0): ?>
            <div class="ipv-warning-box">
                <h3>📦 Prodotti WooCommerce:</h3>
                <ul>
                    <?php if ($created > 0): ?>
                        <li><strong><?php echo $created; ?></strong> prodotto/i creato/i con successo</li>
                    <?php endif; ?>
                    <?php if ($skipped > 0): ?>
                        <li><strong><?php echo $skipped; ?></strong> prodotto/i saltato/i (già esistenti)</li>
                    <?php endif; ?>
                </ul>
                <p>
                    <a href="<?php echo admin_url('edit.php?post_type=product'); ?>" class="button button-primary">
                        🛍️ Visualizza Prodotti
                    </a>
                </p>
            </div>
        <?php endif; ?>

        <h3>📋 Prossimi Passi:</h3>
        <ol>
            <li>
                <strong>Configura i Piani SaaS:</strong>
                <a href="<?php echo admin_url('admin.php?page=ipv-vendor-plans'); ?>">Gestisci Piani SaaS</a>
            </li>
            <li>
                <strong>Verifica i Prodotti:</strong>
                <a href="<?php echo admin_url('edit.php?post_type=product'); ?>">Visualizza Prodotti WooCommerce</a>
            </li>
            <li>
                <strong>Configura Gateway Pagamento:</strong>
                <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=checkout'); ?>">Impostazioni WooCommerce</a>
            </li>
            <li>
                <strong>Testa l'API Gateway:</strong>
                <a href="<?php echo admin_url('admin.php?page=ipv-vendor-status'); ?>">Status & Diagnostica</a>
            </li>
            <li>
                <strong>Visualizza Dashboard:</strong>
                <a href="<?php echo admin_url('admin.php?page=ipv-vendor-dashboard'); ?>">IPV Pro Vendor Dashboard</a>
            </li>
        </ol>

        <div class="ipv-warning-box">
            <strong>⚠️ Nota importante:</strong>
            <p>Se modifichi i piani SaaS in futuro, ricorda di aggiornare o ricreare i prodotti WooCommerce corrispondenti.</p>
        </div>

        <div class="ipv-wizard-actions">
            <a href="<?php echo admin_url('admin.php?page=ipv-vendor-setup&step=4'); ?>" class="button button-large">
                ← Indietro
            </a>
            <a href="<?php echo admin_url('admin.php?page=ipv-vendor-dashboard'); ?>" class="button button-primary button-large">
                🚀 Vai alla Dashboard
            </a>
        </div>
        <?php
    }
}
