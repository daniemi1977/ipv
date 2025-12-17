<?php
/**
 * WCFM Integration
 *
 * Integrazione con WCFM Marketplace per funzionalità multivendor.
 *
 * @package WCFM_Affiliate_Pro
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class WCFM_Affiliate_WCFM_Integration
 */
class WCFM_Affiliate_WCFM_Integration {

    /**
     * Constructor
     */
    public function __construct() {
        // Check if WCFM is active
        if (!$this->is_wcfm_active()) {
            return;
        }

        $this->init_hooks();
    }

    /**
     * Check if WCFM is active
     */
    public function is_wcfm_active(): bool {
        return class_exists('WCFM') || class_exists('WCFMmp');
    }

    /**
     * Initialize hooks
     */
    private function init_hooks(): void {
        // WCFM Menu
        add_filter('wcfm_menus', [$this, 'add_wcfm_menu'], 100);
        add_action('wcfm_load_views', [$this, 'load_wcfm_views'], 100);
        add_action('wcfm_load_scripts', [$this, 'load_wcfm_scripts'], 100);
        add_action('wcfm_load_styles', [$this, 'load_wcfm_styles'], 100);

        // WCFM Endpoints
        add_filter('wcfm_query_vars', [$this, 'add_query_vars'], 100);
        add_filter('wcfm_endpoint_title', [$this, 'endpoint_title'], 100, 2);

        // Vendor dashboard integration
        add_action('wcfm_vendor_settings_update', [$this, 'save_vendor_affiliate_settings'], 10, 2);
        add_filter('wcfm_vendor_settings_fields_commission', [$this, 'add_vendor_commission_fields'], 100, 2);

        // Product fields
        add_action('wcfm_product_manage_fields_pricing_end', [$this, 'add_product_affiliate_fields'], 10, 2);
        add_action('wcfm_product_manage_save', [$this, 'save_product_affiliate_fields'], 10, 2);

        // Order processing
        add_action('wcfm_after_order_mark_complete', [$this, 'process_vendor_commission'], 10, 2);
        add_action('wcfm_after_order_refund', [$this, 'handle_vendor_refund'], 10, 2);

        // Vendor registration as affiliate
        add_action('wcfm_new_vendor_registration', [$this, 'auto_register_vendor_affiliate'], 10, 1);

        // WCFM Store manager hooks
        add_action('wcfm_store_settings_update', [$this, 'update_store_affiliate_settings'], 100, 2);

        // Dashboard widgets
        add_action('wcfm_dashboard_after_commission_details', [$this, 'add_affiliate_widget'], 10);

        // Capability integration
        add_filter('wcfm_capability_settings_fields', [$this, 'add_capability_settings'], 100, 2);
    }

    /**
     * Add WCFM menu items
     *
     * NOTA: Aggiunge "Affiliate Pro" come sottomenu sotto il menu Affiliate esistente
     * NON sovrascrive il menu Affiliate esistente
     */
    public function add_wcfm_menu(array $menus): array {
        global $WCFM;

        if (!$this->vendor_can_be_affiliate()) {
            return $menus;
        }

        // Aggiungi "Affiliate Pro" come sottomenu dopo il menu Affiliate esistente
        // Usa priority maggiore per apparire dopo il menu Affiliate standard
        $menus['wcfm-affiliate-pro'] = [
            'label' => __('Affiliate Pro', 'wcfm-affiliate-pro'),
            'url' => wcfm_get_endpoint_url('wcfm-affiliate-pro'),
            'icon' => 'user-shield',  // Icona diversa per distinguerlo
            'priority' => 58,  // Dopo il menu Affiliate esistente (55-57)
        ];

        $menus['wcfm-affiliate-pro-referrals'] = [
            'label' => __('Referral Pro', 'wcfm-affiliate-pro'),
            'url' => wcfm_get_endpoint_url('wcfm-affiliate-pro-referrals'),
            'icon' => 'link',
            'priority' => 59,
            'capability' => 'view_aff_pro_dashboard',
        ];

        $menus['wcfm-affiliate-pro-payouts'] = [
            'label' => __('Pagamenti Pro', 'wcfm-affiliate-pro'),
            'url' => wcfm_get_endpoint_url('wcfm-affiliate-pro-payouts'),
            'icon' => 'credit-card',
            'priority' => 60,
            'capability' => 'request_aff_pro_payout',
        ];

        return $menus;
    }

    /**
     * Add query vars
     *
     * Usa endpoint UNICI con prefisso 'wcfm-affiliate-pro'
     */
    public function add_query_vars(array $query_vars): array {
        $query_vars['wcfm-affiliate-pro'] = 'wcfm-affiliate-pro';
        $query_vars['wcfm-affiliate-pro-referrals'] = 'wcfm-affiliate-pro-referrals';
        $query_vars['wcfm-affiliate-pro-payouts'] = 'wcfm-affiliate-pro-payouts';
        $query_vars['wcfm-affiliate-pro-links'] = 'wcfm-affiliate-pro-links';
        $query_vars['wcfm-affiliate-pro-creatives'] = 'wcfm-affiliate-pro-creatives';

        return $query_vars;
    }

    /**
     * Endpoint title
     */
    public function endpoint_title(string $title, string $endpoint): string {
        switch ($endpoint) {
            case 'wcfm-affiliate-pro':
                return __('Dashboard Affiliate Pro', 'wcfm-affiliate-pro');
            case 'wcfm-affiliate-pro-referrals':
                return __('I Miei Referral Pro', 'wcfm-affiliate-pro');
            case 'wcfm-affiliate-pro-payouts':
                return __('Pagamenti Affiliate Pro', 'wcfm-affiliate-pro');
            case 'wcfm-affiliate-pro-links':
                return __('Link Referral Pro', 'wcfm-affiliate-pro');
            case 'wcfm-affiliate-pro-creatives':
                return __('Materiali Promozionali Pro', 'wcfm-affiliate-pro');
        }

        return $title;
    }

    /**
     * Load WCFM views
     */
    public function load_wcfm_views(string $endpoint): void {
        global $WCFM, $wp;

        switch ($endpoint) {
            case 'wcfm-affiliate-pro':
                $this->render_wcfm_affiliate_dashboard();
                break;
            case 'wcfm-affiliate-pro-referrals':
                $this->render_wcfm_referrals();
                break;
            case 'wcfm-affiliate-pro-payouts':
                $this->render_wcfm_payouts();
                break;
            case 'wcfm-affiliate-pro-links':
                $this->render_wcfm_links();
                break;
            case 'wcfm-affiliate-pro-creatives':
                $this->render_wcfm_creatives();
                break;
        }
    }

    /**
     * Load WCFM scripts
     */
    public function load_wcfm_scripts(string $endpoint): void {
        if (strpos($endpoint, 'wcfm-affiliate-pro') !== 0) {
            return;
        }

        wp_enqueue_script(
            'wcfm-affiliate-pro-wcfm',
            WCFM_AFFILIATE_PRO_URL . 'assets/js/wcfm-integration.js',
            ['jquery'],
            WCFM_AFFILIATE_PRO_VERSION,
            true
        );

        wp_localize_script('wcfm-affiliate-pro-wcfm', 'wcfm_affiliate_params', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wcfm_affiliate_pro_nonce'),
        ]);
    }

    /**
     * Load WCFM styles
     */
    public function load_wcfm_styles(string $endpoint): void {
        if (strpos($endpoint, 'wcfm-affiliate-pro') !== 0) {
            return;
        }

        wp_enqueue_style(
            'wcfm-affiliate-pro-wcfm',
            WCFM_AFFILIATE_PRO_URL . 'assets/css/wcfm-integration.css',
            [],
            WCFM_AFFILIATE_PRO_VERSION
        );
    }

    /**
     * Check if vendor can be affiliate
     */
    private function vendor_can_be_affiliate(): bool {
        $user_id = get_current_user_id();

        if (!$user_id) {
            return false;
        }

        // Check if user is a vendor
        if (!wcfm_is_vendor($user_id)) {
            return false;
        }

        return true;
    }

    /**
     * Render WCFM affiliate dashboard
     */
    private function render_wcfm_affiliate_dashboard(): void {
        $user_id = get_current_user_id();
        $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate_by_user($user_id);

        if (!$affiliate) {
            // Show registration form
            $this->render_wcfm_affiliate_registration();
            return;
        }

        if ($affiliate->status !== 'active') {
            $this->render_wcfm_affiliate_status($affiliate);
            return;
        }

        $stats = wcfm_affiliate_pro()->affiliates->get_affiliate_stats($affiliate->id);
        ?>
        <div class="collapse wcfm-collapse">
            <div class="wcfm-page-headig">
                <span class="wcfmfa fa-users"></span>
                <span class="wcfm-page-heading-text"><?php _e('Dashboard Affiliate', 'wcfm-affiliate-pro'); ?></span>
            </div>
            <div class="wcfm-clearfix"></div>

            <!-- Stats Grid -->
            <div class="wcfm_affiliate_stats_grid">
                <div class="wcfm_affiliate_stat_box">
                    <div class="wcfm_affiliate_stat_icon"><span class="wcfmfa fa-wallet"></span></div>
                    <div class="wcfm_affiliate_stat_content">
                        <span class="wcfm_affiliate_stat_value"><?php echo wc_price($stats['earnings_balance']); ?></span>
                        <span class="wcfm_affiliate_stat_label"><?php _e('Saldo Disponibile', 'wcfm-affiliate-pro'); ?></span>
                    </div>
                </div>

                <div class="wcfm_affiliate_stat_box">
                    <div class="wcfm_affiliate_stat_icon"><span class="wcfmfa fa-users"></span></div>
                    <div class="wcfm_affiliate_stat_content">
                        <span class="wcfm_affiliate_stat_value"><?php echo number_format($stats['referrals_total']); ?></span>
                        <span class="wcfm_affiliate_stat_label"><?php _e('Referral Totali', 'wcfm-affiliate-pro'); ?></span>
                    </div>
                </div>

                <div class="wcfm_affiliate_stat_box">
                    <div class="wcfm_affiliate_stat_icon"><span class="wcfmfa fa-eye"></span></div>
                    <div class="wcfm_affiliate_stat_content">
                        <span class="wcfm_affiliate_stat_value"><?php echo number_format($stats['visits']); ?></span>
                        <span class="wcfm_affiliate_stat_label"><?php _e('Visite', 'wcfm-affiliate-pro'); ?></span>
                    </div>
                </div>

                <div class="wcfm_affiliate_stat_box">
                    <div class="wcfm_affiliate_stat_icon"><span class="wcfmfa fa-chart-line"></span></div>
                    <div class="wcfm_affiliate_stat_content">
                        <span class="wcfm_affiliate_stat_value"><?php echo $stats['conversion_rate']; ?>%</span>
                        <span class="wcfm_affiliate_stat_label"><?php _e('Tasso Conversione', 'wcfm-affiliate-pro'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Referral Link Section -->
            <div class="wcfm-container">
                <div class="wcfm-content">
                    <h2><?php _e('Il Tuo Link Referral', 'wcfm-affiliate-pro'); ?></h2>
                    <div class="wcfm_affiliate_link_box">
                        <?php
                        $referral_link = wcfm_affiliate_pro()->referrals->generate_link($affiliate->id);
                        ?>
                        <input type="text" id="wcfm_affiliate_link" value="<?php echo esc_url($referral_link); ?>" readonly>
                        <button type="button" class="wcfm_submit_button" id="wcfm_copy_affiliate_link">
                            <?php _e('Copia Link', 'wcfm-affiliate-pro'); ?>
                        </button>
                    </div>
                    <p class="description">
                        <?php printf(__('Codice Affiliato: <strong>%s</strong>', 'wcfm-affiliate-pro'), esc_html($affiliate->affiliate_code)); ?>
                    </p>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="wcfm-container">
                <div class="wcfm-content">
                    <h2><?php _e('Azioni Rapide', 'wcfm-affiliate-pro'); ?></h2>
                    <div class="wcfm_affiliate_quick_actions">
                        <a href="<?php echo wcfm_get_endpoint_url('wcfm-affiliate-pro-referrals'); ?>" class="wcfm_affiliate_action_btn">
                            <span class="wcfmfa fa-list"></span>
                            <?php _e('Visualizza Referral', 'wcfm-affiliate-pro'); ?>
                        </a>
                        <a href="<?php echo wcfm_get_endpoint_url('wcfm-affiliate-pro-payouts'); ?>" class="wcfm_affiliate_action_btn">
                            <span class="wcfmfa fa-credit-card"></span>
                            <?php _e('Richiedi Pagamento', 'wcfm-affiliate-pro'); ?>
                        </a>
                        <a href="<?php echo wcfm_get_endpoint_url('wcfm-affiliate-pro-links'); ?>" class="wcfm_affiliate_action_btn">
                            <span class="wcfmfa fa-link"></span>
                            <?php _e('Genera Link', 'wcfm-affiliate-pro'); ?>
                        </a>
                        <a href="<?php echo wcfm_get_endpoint_url('wcfm-affiliate-pro-creatives'); ?>" class="wcfm_affiliate_action_btn">
                            <span class="wcfmfa fa-image"></span>
                            <?php _e('Materiali', 'wcfm-affiliate-pro'); ?>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Recent Referrals -->
            <div class="wcfm-container">
                <div class="wcfm-content">
                    <h2><?php _e('Referral Recenti', 'wcfm-affiliate-pro'); ?></h2>
                    <?php $this->render_referrals_table($affiliate->id, 5); ?>
                </div>
            </div>

            <!-- Dual Role Widget: Become a Vendor -->
            <?php
            if (wcfm_affiliate_pro()->dual_role) {
                do_action('wcfm_affiliate_pro_dashboard_widgets');
            }
            ?>
        </div>
        <?php
    }

    /**
     * Render WCFM affiliate registration
     */
    private function render_wcfm_affiliate_registration(): void {
        $user = wp_get_current_user();
        ?>
        <div class="collapse wcfm-collapse">
            <div class="wcfm-page-headig">
                <span class="wcfmfa fa-users"></span>
                <span class="wcfm-page-heading-text"><?php _e('Diventa Affiliato', 'wcfm-affiliate-pro'); ?></span>
            </div>
            <div class="wcfm-clearfix"></div>

            <div class="wcfm-container">
                <div class="wcfm-content">
                    <div class="wcfm_affiliate_registration_info">
                        <h3><?php _e('Unisciti al Nostro Programma Affiliati', 'wcfm-affiliate-pro'); ?></h3>
                        <p><?php _e('Come venditore, puoi guadagnare commissioni extra promuovendo i nostri prodotti!', 'wcfm-affiliate-pro'); ?></p>

                        <ul class="wcfm_affiliate_benefits">
                            <li><span class="wcfmfa fa-check"></span> <?php _e('Commissioni competitive su ogni vendita', 'wcfm-affiliate-pro'); ?></li>
                            <li><span class="wcfmfa fa-check"></span> <?php _e('Tracking avanzato dei referral', 'wcfm-affiliate-pro'); ?></li>
                            <li><span class="wcfmfa fa-check"></span> <?php _e('Materiali promozionali pronti all\'uso', 'wcfm-affiliate-pro'); ?></li>
                            <li><span class="wcfmfa fa-check"></span> <?php _e('Pagamenti regolari e puntuali', 'wcfm-affiliate-pro'); ?></li>
                        </ul>
                    </div>

                    <form id="wcfm_affiliate_registration_form" class="wcfm_affiliate_form">
                        <?php wp_nonce_field('wcfm_affiliate_register', 'wcfm_affiliate_nonce'); ?>

                        <div class="wcfm-form-group">
                            <label><?php _e('Email Pagamento', 'wcfm-affiliate-pro'); ?></label>
                            <input type="email" name="payment_email" value="<?php echo esc_attr($user->user_email); ?>" required>
                        </div>

                        <div class="wcfm-form-group">
                            <label><?php _e('Metodo di Pagamento Preferito', 'wcfm-affiliate-pro'); ?></label>
                            <select name="payment_method">
                                <?php foreach (wcfm_affiliate_pro()->payouts->get_payment_methods() as $key => $method): ?>
                                    <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($method['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="wcfm-form-group">
                            <label><?php _e('Sito Web (opzionale)', 'wcfm-affiliate-pro'); ?></label>
                            <input type="url" name="website_url" placeholder="https://tuosito.com">
                        </div>

                        <div class="wcfm-form-group">
                            <label><?php _e('Come promuoverai i prodotti?', 'wcfm-affiliate-pro'); ?></label>
                            <textarea name="promotional_methods" rows="4" placeholder="<?php _e('Descrivi i tuoi metodi promozionali...', 'wcfm-affiliate-pro'); ?>"></textarea>
                        </div>

                        <button type="submit" class="wcfm_submit_button">
                            <?php _e('Richiedi Iscrizione', 'wcfm-affiliate-pro'); ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render WCFM affiliate status
     */
    private function render_wcfm_affiliate_status(object $affiliate): void {
        ?>
        <div class="collapse wcfm-collapse">
            <div class="wcfm-page-headig">
                <span class="wcfmfa fa-users"></span>
                <span class="wcfm-page-heading-text"><?php _e('Stato Affiliazione', 'wcfm-affiliate-pro'); ?></span>
            </div>
            <div class="wcfm-clearfix"></div>

            <div class="wcfm-container">
                <div class="wcfm-content">
                    <?php if ($affiliate->status === 'pending'): ?>
                        <div class="wcfm_affiliate_status_message wcfm_affiliate_pending">
                            <span class="wcfmfa fa-clock"></span>
                            <h3><?php _e('Richiesta in Attesa', 'wcfm-affiliate-pro'); ?></h3>
                            <p><?php _e('La tua richiesta di affiliazione è in corso di revisione. Ti contatteremo presto!', 'wcfm-affiliate-pro'); ?></p>
                        </div>
                    <?php elseif ($affiliate->status === 'rejected'): ?>
                        <div class="wcfm_affiliate_status_message wcfm_affiliate_rejected">
                            <span class="wcfmfa fa-times-circle"></span>
                            <h3><?php _e('Richiesta Rifiutata', 'wcfm-affiliate-pro'); ?></h3>
                            <?php if ($affiliate->rejection_reason): ?>
                                <p><?php echo esc_html($affiliate->rejection_reason); ?></p>
                            <?php else: ?>
                                <p><?php _e('La tua richiesta non è stata approvata. Contatta il supporto per maggiori informazioni.', 'wcfm-affiliate-pro'); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php elseif ($affiliate->status === 'suspended'): ?>
                        <div class="wcfm_affiliate_status_message wcfm_affiliate_suspended">
                            <span class="wcfmfa fa-ban"></span>
                            <h3><?php _e('Account Sospeso', 'wcfm-affiliate-pro'); ?></h3>
                            <p><?php _e('Il tuo account affiliato è stato sospeso. Contatta il supporto per maggiori informazioni.', 'wcfm-affiliate-pro'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render WCFM referrals page
     */
    private function render_wcfm_referrals(): void {
        $user_id = get_current_user_id();
        $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate_by_user($user_id);

        if (!$affiliate || $affiliate->status !== 'active') {
            wp_redirect(wcfm_get_endpoint_url('wcfm-affiliate-pro'));
            exit;
        }
        ?>
        <div class="collapse wcfm-collapse">
            <div class="wcfm-page-headig">
                <span class="wcfmfa fa-list"></span>
                <span class="wcfm-page-heading-text"><?php _e('I Miei Referral', 'wcfm-affiliate-pro'); ?></span>
            </div>
            <div class="wcfm-clearfix"></div>

            <div class="wcfm-container">
                <div class="wcfm-content">
                    <?php $this->render_referrals_table($affiliate->id); ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render WCFM payouts page
     */
    private function render_wcfm_payouts(): void {
        $user_id = get_current_user_id();
        $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate_by_user($user_id);

        if (!$affiliate || $affiliate->status !== 'active') {
            wp_redirect(wcfm_get_endpoint_url('wcfm-affiliate-pro'));
            exit;
        }

        $stats = wcfm_affiliate_pro()->affiliates->get_affiliate_stats($affiliate->id);
        $settings = get_option('wcfm_aff_pro_general', []);
        $minimum_payout = floatval($settings['minimum_payout'] ?? 50);
        ?>
        <div class="collapse wcfm-collapse">
            <div class="wcfm-page-headig">
                <span class="wcfmfa fa-credit-card"></span>
                <span class="wcfm-page-heading-text"><?php _e('Pagamenti Affiliate', 'wcfm-affiliate-pro'); ?></span>
            </div>
            <div class="wcfm-clearfix"></div>

            <div class="wcfm-container">
                <div class="wcfm-content">
                    <div class="wcfm_affiliate_payout_summary">
                        <div class="wcfm_affiliate_payout_box">
                            <span class="wcfm_affiliate_payout_label"><?php _e('Saldo Disponibile', 'wcfm-affiliate-pro'); ?></span>
                            <span class="wcfm_affiliate_payout_value"><?php echo wc_price($stats['earnings_balance']); ?></span>
                        </div>
                        <div class="wcfm_affiliate_payout_box">
                            <span class="wcfm_affiliate_payout_label"><?php _e('Pagamento Minimo', 'wcfm-affiliate-pro'); ?></span>
                            <span class="wcfm_affiliate_payout_value"><?php echo wc_price($minimum_payout); ?></span>
                        </div>
                        <div class="wcfm_affiliate_payout_box">
                            <span class="wcfm_affiliate_payout_label"><?php _e('Totale Pagato', 'wcfm-affiliate-pro'); ?></span>
                            <span class="wcfm_affiliate_payout_value"><?php echo wc_price($stats['earnings_paid']); ?></span>
                        </div>
                    </div>

                    <?php if ($stats['earnings_balance'] >= $minimum_payout): ?>
                        <div class="wcfm_affiliate_request_payout">
                            <h3><?php _e('Richiedi Pagamento', 'wcfm-affiliate-pro'); ?></h3>
                            <form id="wcfm_affiliate_payout_form">
                                <?php wp_nonce_field('wcfm_affiliate_pro_nonce', 'nonce'); ?>

                                <div class="wcfm-form-group">
                                    <label><?php _e('Importo', 'wcfm-affiliate-pro'); ?></label>
                                    <input type="number" name="amount" min="<?php echo esc_attr($minimum_payout); ?>"
                                           max="<?php echo esc_attr($stats['earnings_balance']); ?>"
                                           value="<?php echo esc_attr($stats['earnings_balance']); ?>" step="0.01">
                                </div>

                                <div class="wcfm-form-group">
                                    <label><?php _e('Metodo di Pagamento', 'wcfm-affiliate-pro'); ?></label>
                                    <select name="payment_method">
                                        <?php foreach (wcfm_affiliate_pro()->payouts->get_payment_methods() as $key => $method): ?>
                                            <option value="<?php echo esc_attr($key); ?>" <?php selected($affiliate->payment_method, $key); ?>>
                                                <?php echo esc_html($method['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <button type="submit" class="wcfm_submit_button">
                                    <?php _e('Invia Richiesta', 'wcfm-affiliate-pro'); ?>
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>

                    <h3><?php _e('Storico Pagamenti', 'wcfm-affiliate-pro'); ?></h3>
                    <?php $this->render_payouts_table($affiliate->id); ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render WCFM links page
     */
    private function render_wcfm_links(): void {
        $user_id = get_current_user_id();
        $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate_by_user($user_id);

        if (!$affiliate || $affiliate->status !== 'active') {
            wp_redirect(wcfm_get_endpoint_url('wcfm-affiliate-pro'));
            exit;
        }
        ?>
        <div class="collapse wcfm-collapse">
            <div class="wcfm-page-headig">
                <span class="wcfmfa fa-link"></span>
                <span class="wcfm-page-heading-text"><?php _e('Genera Link Referral', 'wcfm-affiliate-pro'); ?></span>
            </div>
            <div class="wcfm-clearfix"></div>

            <div class="wcfm-container">
                <div class="wcfm-content">
                    <div class="wcfm_affiliate_link_generator">
                        <div class="wcfm-form-group">
                            <label><?php _e('URL di Destinazione', 'wcfm-affiliate-pro'); ?></label>
                            <input type="url" id="wcfm_link_destination" placeholder="<?php echo esc_url(home_url()); ?>">
                        </div>

                        <div class="wcfm-form-group">
                            <label><?php _e('Campagna (opzionale)', 'wcfm-affiliate-pro'); ?></label>
                            <input type="text" id="wcfm_link_campaign" placeholder="<?php _e('es. facebook, newsletter', 'wcfm-affiliate-pro'); ?>">
                        </div>

                        <button type="button" class="wcfm_submit_button" id="wcfm_generate_link">
                            <?php _e('Genera Link', 'wcfm-affiliate-pro'); ?>
                        </button>

                        <div class="wcfm_affiliate_generated_link" style="display: none;">
                            <label><?php _e('Il Tuo Link Referral', 'wcfm-affiliate-pro'); ?></label>
                            <div class="wcfm_affiliate_link_output">
                                <input type="text" id="wcfm_generated_link" readonly>
                                <button type="button" class="wcfm_submit_button wcfm_copy_link">
                                    <?php _e('Copia', 'wcfm-affiliate-pro'); ?>
                                </button>
                            </div>
                        </div>
                    </div>

                    <h3><?php _e('Link Rapidi ai Tuoi Prodotti', 'wcfm-affiliate-pro'); ?></h3>
                    <?php $this->render_vendor_product_links($user_id, $affiliate); ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render WCFM creatives page
     */
    private function render_wcfm_creatives(): void {
        $user_id = get_current_user_id();
        $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate_by_user($user_id);

        if (!$affiliate || $affiliate->status !== 'active') {
            wp_redirect(wcfm_get_endpoint_url('wcfm-affiliate-pro'));
            exit;
        }
        ?>
        <div class="collapse wcfm-collapse">
            <div class="wcfm-page-headig">
                <span class="wcfmfa fa-image"></span>
                <span class="wcfm-page-heading-text"><?php _e('Materiali Promozionali', 'wcfm-affiliate-pro'); ?></span>
            </div>
            <div class="wcfm-clearfix"></div>

            <div class="wcfm-container">
                <div class="wcfm-content">
                    <?php
                    global $wpdb;
                    $creatives = $wpdb->get_results(
                        "SELECT * FROM " . WCFM_Affiliate_DB::$table_creatives . " WHERE status = 'active'"
                    );

                    if (empty($creatives)):
                        ?>
                        <p><?php _e('Nessun materiale promozionale disponibile al momento.', 'wcfm-affiliate-pro'); ?></p>
                        <?php
                    else:
                        ?>
                        <div class="wcfm_affiliate_creatives_grid">
                            <?php foreach ($creatives as $creative):
                                $creative_link = wcfm_affiliate_pro()->referrals->generate_link($affiliate->id, $creative->url);
                                ?>
                                <div class="wcfm_affiliate_creative_item">
                                    <?php if ($creative->type === 'banner' && $creative->image_url): ?>
                                        <img src="<?php echo esc_url($creative->image_url); ?>" alt="<?php echo esc_attr($creative->name); ?>">
                                    <?php endif; ?>
                                    <h4><?php echo esc_html($creative->name); ?></h4>
                                    <textarea readonly><?php echo esc_textarea(sprintf(
                                        '<a href="%s" target="_blank"><img src="%s" alt="%s"></a>',
                                        esc_url($creative_link),
                                        esc_url($creative->image_url),
                                        esc_attr($creative->name)
                                    )); ?></textarea>
                                    <button type="button" class="wcfm_submit_button wcfm_copy_creative">
                                        <?php _e('Copia Codice', 'wcfm-affiliate-pro'); ?>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php
                    endif;
                    ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render referrals table
     */
    private function render_referrals_table(int $affiliate_id, int $limit = 20): void {
        global $wpdb;

        $referrals = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM " . WCFM_Affiliate_DB::$table_referrals . "
             WHERE affiliate_id = %d
             ORDER BY date_created DESC
             LIMIT %d",
            $affiliate_id,
            $limit
        ));
        ?>
        <table class="wcfm-table wcfm_affiliate_table">
            <thead>
                <tr>
                    <th><?php _e('Data', 'wcfm-affiliate-pro'); ?></th>
                    <th><?php _e('Ordine', 'wcfm-affiliate-pro'); ?></th>
                    <th><?php _e('Importo', 'wcfm-affiliate-pro'); ?></th>
                    <th><?php _e('Stato', 'wcfm-affiliate-pro'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($referrals)): ?>
                    <tr>
                        <td colspan="4"><?php _e('Nessun referral ancora', 'wcfm-affiliate-pro'); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($referrals as $referral): ?>
                        <tr>
                            <td><?php echo date_i18n(get_option('date_format'), strtotime($referral->date_created)); ?></td>
                            <td>#<?php echo esc_html($referral->order_id); ?></td>
                            <td><?php echo wc_price($referral->amount); ?></td>
                            <td>
                                <span class="wcfm_affiliate_status wcfm_affiliate_status_<?php echo esc_attr($referral->status); ?>">
                                    <?php echo esc_html(ucfirst($referral->status)); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * Render payouts table
     */
    private function render_payouts_table(int $affiliate_id): void {
        $payouts = wcfm_affiliate_pro()->payouts->get_payouts([
            'affiliate_id' => $affiliate_id,
            'limit' => 20,
        ]);
        ?>
        <table class="wcfm-table wcfm_affiliate_table">
            <thead>
                <tr>
                    <th><?php _e('Data', 'wcfm-affiliate-pro'); ?></th>
                    <th><?php _e('Importo', 'wcfm-affiliate-pro'); ?></th>
                    <th><?php _e('Metodo', 'wcfm-affiliate-pro'); ?></th>
                    <th><?php _e('Stato', 'wcfm-affiliate-pro'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($payouts)): ?>
                    <tr>
                        <td colspan="4"><?php _e('Nessun pagamento ancora', 'wcfm-affiliate-pro'); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($payouts as $payout): ?>
                        <tr>
                            <td><?php echo date_i18n(get_option('date_format'), strtotime($payout->date_created)); ?></td>
                            <td><?php echo wc_price($payout->amount); ?></td>
                            <td><?php echo esc_html(ucfirst(str_replace('_', ' ', $payout->payment_method))); ?></td>
                            <td>
                                <span class="wcfm_affiliate_status wcfm_affiliate_status_<?php echo esc_attr($payout->status); ?>">
                                    <?php echo esc_html(ucfirst($payout->status)); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * Render vendor product links
     */
    private function render_vendor_product_links(int $vendor_id, object $affiliate): void {
        $products = wc_get_products([
            'author' => $vendor_id,
            'status' => 'publish',
            'limit' => 10,
        ]);

        if (empty($products)):
            ?>
            <p><?php _e('Non hai ancora prodotti pubblicati.', 'wcfm-affiliate-pro'); ?></p>
            <?php
            return;
        endif;
        ?>
        <table class="wcfm-table">
            <thead>
                <tr>
                    <th><?php _e('Prodotto', 'wcfm-affiliate-pro'); ?></th>
                    <th><?php _e('Link Referral', 'wcfm-affiliate-pro'); ?></th>
                    <th><?php _e('Azioni', 'wcfm-affiliate-pro'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product):
                    $link = wcfm_affiliate_pro()->referrals->generate_link($affiliate->id, $product->get_permalink());
                    ?>
                    <tr>
                        <td><?php echo esc_html($product->get_name()); ?></td>
                        <td><input type="text" value="<?php echo esc_url($link); ?>" readonly class="wcfm_affiliate_product_link"></td>
                        <td>
                            <button type="button" class="wcfm_submit_button wcfm_copy_product_link" data-link="<?php echo esc_url($link); ?>">
                                <?php _e('Copia', 'wcfm-affiliate-pro'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * Add product affiliate fields
     */
    public function add_product_affiliate_fields(int $product_id, array $product_data): void {
        $commission_rate = get_post_meta($product_id, '_wcfm_affiliate_commission_rate', true);
        $commission_type = get_post_meta($product_id, '_wcfm_affiliate_commission_type', true);
        ?>
        <div class="wcfm-affiliate-product-settings">
            <h3><?php _e('Impostazioni Affiliate', 'wcfm-affiliate-pro'); ?></h3>

            <div class="wcfm-form-group">
                <label><?php _e('Tipo Commissione Affiliato', 'wcfm-affiliate-pro'); ?></label>
                <select name="_wcfm_affiliate_commission_type">
                    <option value=""><?php _e('Usa impostazione globale', 'wcfm-affiliate-pro'); ?></option>
                    <option value="percentage" <?php selected($commission_type, 'percentage'); ?>><?php _e('Percentuale', 'wcfm-affiliate-pro'); ?></option>
                    <option value="flat" <?php selected($commission_type, 'flat'); ?>><?php _e('Importo Fisso', 'wcfm-affiliate-pro'); ?></option>
                </select>
            </div>

            <div class="wcfm-form-group">
                <label><?php _e('Tasso Commissione Affiliato', 'wcfm-affiliate-pro'); ?></label>
                <input type="number" name="_wcfm_affiliate_commission_rate" step="0.01" min="0"
                       value="<?php echo esc_attr($commission_rate); ?>"
                       placeholder="<?php _e('Lascia vuoto per usare il valore globale', 'wcfm-affiliate-pro'); ?>">
            </div>
        </div>
        <?php
    }

    /**
     * Save product affiliate fields
     */
    public function save_product_affiliate_fields(int $product_id, array $data): void {
        if (isset($data['_wcfm_affiliate_commission_type'])) {
            update_post_meta($product_id, '_wcfm_affiliate_commission_type', sanitize_text_field($data['_wcfm_affiliate_commission_type']));
        }

        if (isset($data['_wcfm_affiliate_commission_rate'])) {
            update_post_meta($product_id, '_wcfm_affiliate_commission_rate', floatval($data['_wcfm_affiliate_commission_rate']));
        }
    }

    /**
     * Auto register vendor as affiliate
     */
    public function auto_register_vendor_affiliate(int $vendor_id): void {
        $settings = get_option('wcfm_aff_pro_general', []);

        if (($settings['auto_approve_vendors'] ?? 'no') !== 'yes') {
            return;
        }

        wcfm_affiliate_pro()->affiliates->register_affiliate($vendor_id, [
            'payment_email' => get_userdata($vendor_id)->user_email,
        ]);
    }

    /**
     * Process vendor commission
     */
    public function process_vendor_commission(int $order_id, int $order_item_id): void {
        // Commission is already handled by the main commission class
        // This hook can be used for vendor-specific logic
        do_action('wcfm_affiliate_vendor_commission_processed', $order_id, $order_item_id);
    }

    /**
     * Handle vendor refund
     */
    public function handle_vendor_refund(int $order_id, int $refund_id): void {
        // Refund is already handled by the main commission class
        do_action('wcfm_affiliate_vendor_refund_processed', $order_id, $refund_id);
    }

    /**
     * Add affiliate widget to vendor dashboard
     *
     * Shows affiliate stats if vendor is an affiliate,
     * or shows "Become an Affiliate" widget if they're not.
     */
    public function add_affiliate_widget(): void {
        $user_id = get_current_user_id();
        $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate_by_user($user_id);

        if ($affiliate && $affiliate->status === 'active') {
            // Vendor is already an affiliate - show stats widget
            $stats = wcfm_affiliate_pro()->affiliates->get_affiliate_stats($affiliate->id);
            ?>
            <div class="wcfm_dashboard_affiliate_widget">
                <div class="wcfm_dashboard_widget_title">
                    <span class="wcfmfa fa-users"></span>
                    <?php _e('Guadagni Affiliate', 'wcfm-affiliate-pro'); ?>
                </div>
                <div class="wcfm_dashboard_widget_content">
                    <span class="wcfm_dashboard_widget_value"><?php echo wc_price($stats['earnings_balance']); ?></span>
                    <span class="wcfm_dashboard_widget_label"><?php _e('Saldo Disponibile', 'wcfm-affiliate-pro'); ?></span>
                    <a href="<?php echo wcfm_get_endpoint_url('wcfm-affiliate-pro'); ?>" class="wcfm_dashboard_widget_link">
                        <?php _e('Visualizza Dashboard Affiliate', 'wcfm-affiliate-pro'); ?>
                    </a>
                </div>
            </div>
            <?php
        }

        // Always trigger the dual-role widget hook for vendors
        // This will show "Become an Affiliate" if they're not one yet
        if (wcfm_affiliate_pro()->dual_role) {
            do_action('wcfm_vendor_dashboard_after_widgets');
        }
    }

    /**
     * Add vendor commission fields
     */
    public function add_vendor_commission_fields(array $fields, int $vendor_id): array {
        $commission_rate = get_user_meta($vendor_id, '_wcfm_affiliate_commission_rate', true);
        $commission_type = get_user_meta($vendor_id, '_wcfm_affiliate_commission_type', true);

        $fields['_wcfm_affiliate_commission'] = [
            'label' => __('Commissione Affiliati Personalizzata', 'wcfm-affiliate-pro'),
            'type' => 'title',
        ];

        $fields['_wcfm_affiliate_commission_type'] = [
            'label' => __('Tipo Commissione', 'wcfm-affiliate-pro'),
            'type' => 'select',
            'options' => [
                '' => __('Usa impostazione globale', 'wcfm-affiliate-pro'),
                'percentage' => __('Percentuale', 'wcfm-affiliate-pro'),
                'flat' => __('Importo Fisso', 'wcfm-affiliate-pro'),
            ],
            'value' => $commission_type,
        ];

        $fields['_wcfm_affiliate_commission_rate'] = [
            'label' => __('Tasso Commissione', 'wcfm-affiliate-pro'),
            'type' => 'number',
            'value' => $commission_rate,
            'attributes' => [
                'step' => '0.01',
                'min' => '0',
            ],
        ];

        return $fields;
    }

    /**
     * Save vendor affiliate settings
     */
    public function save_vendor_affiliate_settings(int $vendor_id, array $data): void {
        if (isset($data['_wcfm_affiliate_commission_type'])) {
            update_user_meta($vendor_id, '_wcfm_affiliate_commission_type', sanitize_text_field($data['_wcfm_affiliate_commission_type']));
        }

        if (isset($data['_wcfm_affiliate_commission_rate'])) {
            update_user_meta($vendor_id, '_wcfm_affiliate_commission_rate', floatval($data['_wcfm_affiliate_commission_rate']));
        }
    }

    /**
     * Update store affiliate settings
     */
    public function update_store_affiliate_settings(int $store_id, array $store_data): void {
        // Store-specific affiliate settings can be implemented here
    }

    /**
     * Add capability settings
     */
    public function add_capability_settings(array $fields, array $capabilities): array {
        $fields['wcfm_affiliate_capabilities'] = [
            'label' => __('Funzionalità Affiliate', 'wcfm-affiliate-pro'),
            'type' => 'title',
            'priority' => 100,
        ];

        $fields['view_affiliate_dashboard'] = [
            'label' => __('Visualizza Dashboard Affiliate', 'wcfm-affiliate-pro'),
            'type' => 'checkboxoffon',
            'value' => 'yes',
        ];

        $fields['manage_affiliate_links'] = [
            'label' => __('Gestisci Link Referral', 'wcfm-affiliate-pro'),
            'type' => 'checkboxoffon',
            'value' => 'yes',
        ];

        $fields['request_payout'] = [
            'label' => __('Richiedi Pagamento', 'wcfm-affiliate-pro'),
            'type' => 'checkboxoffon',
            'value' => 'yes',
        ];

        return $fields;
    }
}
