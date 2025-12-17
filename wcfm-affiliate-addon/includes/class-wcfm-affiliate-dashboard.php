<?php
/**
 * Affiliate Dashboard
 *
 * Gestisce la dashboard frontend per gli affiliati.
 *
 * @package WCFM_Affiliate_Pro
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class WCFM_Affiliate_Dashboard
 */
class WCFM_Affiliate_Dashboard {

    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks(): void {
        // Dashboard AJAX
        add_action('wp_ajax_wcfm_affiliate_get_dashboard_data', [$this, 'get_dashboard_data']);
        add_action('wp_ajax_wcfm_affiliate_get_chart_data', [$this, 'get_chart_data']);
    }

    /**
     * Render dashboard
     */
    public function render(): string {
        if (!is_user_logged_in()) {
            return $this->render_login_form();
        }

        $user_id = get_current_user_id();
        $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate_by_user($user_id);

        if (!$affiliate) {
            return $this->render_not_affiliate();
        }

        if ($affiliate->status === 'pending') {
            return $this->render_pending_approval();
        }

        if ($affiliate->status === 'rejected') {
            return $this->render_rejected($affiliate);
        }

        if ($affiliate->status === 'suspended') {
            return $this->render_suspended($affiliate);
        }

        // Active affiliate dashboard
        return $this->render_active_dashboard($affiliate);
    }

    /**
     * Render login form
     */
    private function render_login_form(): string {
        ob_start();
        ?>
        <div class="wcfm-affiliate-login-container">
            <h2><?php _e('Accedi al tuo Account Affiliato', 'wcfm-affiliate-pro'); ?></h2>

            <?php wp_login_form([
                'redirect' => wcfm_affiliate_pro()->affiliates->get_dashboard_url(),
                'form_id' => 'wcfm-affiliate-login',
                'label_username' => __('Email', 'wcfm-affiliate-pro'),
            ]); ?>

            <p class="wcfm-affiliate-register-link">
                <?php _e('Non sei ancora affiliato?', 'wcfm-affiliate-pro'); ?>
                <a href="<?php echo esc_url(get_permalink(get_option('wcfm_affiliate_pages', [])['registration'] ?? 0)); ?>">
                    <?php _e('Registrati ora', 'wcfm-affiliate-pro'); ?>
                </a>
            </p>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render not affiliate message
     */
    private function render_not_affiliate(): string {
        ob_start();
        ?>
        <div class="wcfm-affiliate-message wcfm-affiliate-info">
            <h3><?php _e('Non sei un affiliato', 'wcfm-affiliate-pro'); ?></h3>
            <p><?php _e('Non risulti registrato nel nostro programma di affiliazione.', 'wcfm-affiliate-pro'); ?></p>
            <a href="<?php echo esc_url(get_permalink(get_option('wcfm_affiliate_pages', [])['registration'] ?? 0)); ?>" class="wcfm-affiliate-btn">
                <?php _e('Diventa Affiliato', 'wcfm-affiliate-pro'); ?>
            </a>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render pending approval
     */
    private function render_pending_approval(): string {
        ob_start();
        ?>
        <div class="wcfm-affiliate-message wcfm-affiliate-warning">
            <div class="wcfm-affiliate-icon">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12,6 12,12 16,14"></polyline>
                </svg>
            </div>
            <h3><?php _e('Richiesta in Attesa', 'wcfm-affiliate-pro'); ?></h3>
            <p><?php _e('La tua richiesta di affiliazione è in attesa di approvazione. Ti contatteremo presto!', 'wcfm-affiliate-pro'); ?></p>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render rejected
     */
    private function render_rejected(object $affiliate): string {
        ob_start();
        ?>
        <div class="wcfm-affiliate-message wcfm-affiliate-error">
            <div class="wcfm-affiliate-icon">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="15" y1="9" x2="9" y2="15"></line>
                    <line x1="9" y1="9" x2="15" y2="15"></line>
                </svg>
            </div>
            <h3><?php _e('Richiesta Rifiutata', 'wcfm-affiliate-pro'); ?></h3>
            <?php if ($affiliate->rejection_reason): ?>
                <p><?php echo esc_html($affiliate->rejection_reason); ?></p>
            <?php else: ?>
                <p><?php _e('La tua richiesta di affiliazione è stata rifiutata.', 'wcfm-affiliate-pro'); ?></p>
            <?php endif; ?>
            <p><?php _e('Se ritieni sia un errore, contatta il supporto.', 'wcfm-affiliate-pro'); ?></p>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render suspended
     */
    private function render_suspended(object $affiliate): string {
        ob_start();
        ?>
        <div class="wcfm-affiliate-message wcfm-affiliate-error">
            <div class="wcfm-affiliate-icon">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                    <line x1="12" y1="9" x2="12" y2="13"></line>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
            </div>
            <h3><?php _e('Account Sospeso', 'wcfm-affiliate-pro'); ?></h3>
            <?php if ($affiliate->notes): ?>
                <p><?php echo esc_html($affiliate->notes); ?></p>
            <?php else: ?>
                <p><?php _e('Il tuo account affiliato è stato sospeso.', 'wcfm-affiliate-pro'); ?></p>
            <?php endif; ?>
            <p><?php _e('Contatta il supporto per maggiori informazioni.', 'wcfm-affiliate-pro'); ?></p>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render active dashboard
     */
    private function render_active_dashboard(object $affiliate): string {
        $stats = wcfm_affiliate_pro()->affiliates->get_affiliate_stats($affiliate->id);
        $settings = get_option('wcfm_affiliate_general', []);
        $minimum_payout = floatval($settings['minimum_payout'] ?? 50);

        ob_start();
        ?>
        <div class="wcfm-affiliate-dashboard" data-affiliate-id="<?php echo esc_attr($affiliate->id); ?>">

            <!-- Dashboard Header -->
            <div class="wcfm-affiliate-dashboard-header">
                <div class="wcfm-affiliate-welcome">
                    <h2><?php printf(__('Ciao, %s!', 'wcfm-affiliate-pro'), esc_html(wp_get_current_user()->display_name)); ?></h2>
                    <p class="wcfm-affiliate-code">
                        <?php _e('Il tuo codice affiliato:', 'wcfm-affiliate-pro'); ?>
                        <strong><?php echo esc_html($affiliate->affiliate_code); ?></strong>
                    </p>
                </div>
                <div class="wcfm-affiliate-period-selector">
                    <select id="wcfm-affiliate-period">
                        <option value="7days"><?php _e('Ultimi 7 giorni', 'wcfm-affiliate-pro'); ?></option>
                        <option value="30days" selected><?php _e('Ultimi 30 giorni', 'wcfm-affiliate-pro'); ?></option>
                        <option value="90days"><?php _e('Ultimi 90 giorni', 'wcfm-affiliate-pro'); ?></option>
                        <option value="year"><?php _e('Quest\'anno', 'wcfm-affiliate-pro'); ?></option>
                        <option value="all"><?php _e('Tutto', 'wcfm-affiliate-pro'); ?></option>
                    </select>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="wcfm-affiliate-stats-grid">
                <div class="wcfm-affiliate-stat-card wcfm-affiliate-stat-balance">
                    <div class="wcfm-affiliate-stat-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="1" x2="12" y2="23"></line>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                    </div>
                    <div class="wcfm-affiliate-stat-content">
                        <span class="wcfm-affiliate-stat-value"><?php echo wc_price($stats['earnings_balance']); ?></span>
                        <span class="wcfm-affiliate-stat-label"><?php _e('Saldo Disponibile', 'wcfm-affiliate-pro'); ?></span>
                    </div>
                    <?php if ($stats['earnings_balance'] >= $minimum_payout): ?>
                        <button class="wcfm-affiliate-btn wcfm-affiliate-btn-sm wcfm-affiliate-request-payout">
                            <?php _e('Richiedi Pagamento', 'wcfm-affiliate-pro'); ?>
                        </button>
                    <?php endif; ?>
                </div>

                <div class="wcfm-affiliate-stat-card">
                    <div class="wcfm-affiliate-stat-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="8.5" cy="7" r="4"></circle>
                            <line x1="20" y1="8" x2="20" y2="14"></line>
                            <line x1="23" y1="11" x2="17" y2="11"></line>
                        </svg>
                    </div>
                    <div class="wcfm-affiliate-stat-content">
                        <span class="wcfm-affiliate-stat-value"><?php echo number_format($stats['referrals_total']); ?></span>
                        <span class="wcfm-affiliate-stat-label"><?php _e('Referral Totali', 'wcfm-affiliate-pro'); ?></span>
                    </div>
                </div>

                <div class="wcfm-affiliate-stat-card">
                    <div class="wcfm-affiliate-stat-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </div>
                    <div class="wcfm-affiliate-stat-content">
                        <span class="wcfm-affiliate-stat-value"><?php echo number_format($stats['visits']); ?></span>
                        <span class="wcfm-affiliate-stat-label"><?php _e('Visite', 'wcfm-affiliate-pro'); ?></span>
                    </div>
                </div>

                <div class="wcfm-affiliate-stat-card">
                    <div class="wcfm-affiliate-stat-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                            <polyline points="17 6 23 6 23 12"></polyline>
                        </svg>
                    </div>
                    <div class="wcfm-affiliate-stat-content">
                        <span class="wcfm-affiliate-stat-value"><?php echo $stats['conversion_rate']; ?>%</span>
                        <span class="wcfm-affiliate-stat-label"><?php _e('Tasso Conversione', 'wcfm-affiliate-pro'); ?></span>
                    </div>
                </div>

                <div class="wcfm-affiliate-stat-card">
                    <div class="wcfm-affiliate-stat-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                            <line x1="1" y1="10" x2="23" y2="10"></line>
                        </svg>
                    </div>
                    <div class="wcfm-affiliate-stat-content">
                        <span class="wcfm-affiliate-stat-value"><?php echo wc_price($stats['earnings_paid']); ?></span>
                        <span class="wcfm-affiliate-stat-label"><?php _e('Guadagni Pagati', 'wcfm-affiliate-pro'); ?></span>
                    </div>
                </div>

                <div class="wcfm-affiliate-stat-card wcfm-affiliate-stat-total">
                    <div class="wcfm-affiliate-stat-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M8 14s1.5 2 4 2 4-2 4-2"></path>
                            <line x1="9" y1="9" x2="9.01" y2="9"></line>
                            <line x1="15" y1="9" x2="15.01" y2="9"></line>
                        </svg>
                    </div>
                    <div class="wcfm-affiliate-stat-content">
                        <span class="wcfm-affiliate-stat-value"><?php echo wc_price($stats['earnings_total']); ?></span>
                        <span class="wcfm-affiliate-stat-label"><?php _e('Guadagni Totali', 'wcfm-affiliate-pro'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Dashboard Navigation -->
            <div class="wcfm-affiliate-dashboard-nav">
                <button class="wcfm-affiliate-nav-btn active" data-tab="overview">
                    <?php _e('Panoramica', 'wcfm-affiliate-pro'); ?>
                </button>
                <button class="wcfm-affiliate-nav-btn" data-tab="links">
                    <?php _e('Link Referral', 'wcfm-affiliate-pro'); ?>
                </button>
                <button class="wcfm-affiliate-nav-btn" data-tab="referrals">
                    <?php _e('Referral', 'wcfm-affiliate-pro'); ?>
                </button>
                <button class="wcfm-affiliate-nav-btn" data-tab="payouts">
                    <?php _e('Pagamenti', 'wcfm-affiliate-pro'); ?>
                </button>
                <button class="wcfm-affiliate-nav-btn" data-tab="creatives">
                    <?php _e('Materiali', 'wcfm-affiliate-pro'); ?>
                </button>
                <button class="wcfm-affiliate-nav-btn" data-tab="settings">
                    <?php _e('Impostazioni', 'wcfm-affiliate-pro'); ?>
                </button>
            </div>

            <!-- Dashboard Content -->
            <div class="wcfm-affiliate-dashboard-content">

                <!-- Overview Tab -->
                <div class="wcfm-affiliate-tab-content active" data-tab="overview">
                    <div class="wcfm-affiliate-chart-container">
                        <h3><?php _e('Andamento Commissioni', 'wcfm-affiliate-pro'); ?></h3>
                        <canvas id="wcfm-affiliate-chart"></canvas>
                    </div>

                    <div class="wcfm-affiliate-recent-referrals">
                        <h3><?php _e('Referral Recenti', 'wcfm-affiliate-pro'); ?></h3>
                        <?php $this->render_referrals_table($affiliate->id, 5); ?>
                    </div>
                </div>

                <!-- Links Tab -->
                <div class="wcfm-affiliate-tab-content" data-tab="links">
                    <div class="wcfm-affiliate-link-generator">
                        <h3><?php _e('Genera Link Referral', 'wcfm-affiliate-pro'); ?></h3>

                        <div class="wcfm-affiliate-form-group">
                            <label><?php _e('URL di Destinazione', 'wcfm-affiliate-pro'); ?></label>
                            <input type="url" id="wcfm-affiliate-link-url" placeholder="<?php echo esc_url(home_url()); ?>">
                        </div>

                        <div class="wcfm-affiliate-form-group">
                            <label><?php _e('Campagna (opzionale)', 'wcfm-affiliate-pro'); ?></label>
                            <input type="text" id="wcfm-affiliate-link-campaign" placeholder="<?php _e('es. facebook, newsletter', 'wcfm-affiliate-pro'); ?>">
                        </div>

                        <button class="wcfm-affiliate-btn" id="wcfm-affiliate-generate-link">
                            <?php _e('Genera Link', 'wcfm-affiliate-pro'); ?>
                        </button>

                        <div class="wcfm-affiliate-generated-link" style="display: none;">
                            <label><?php _e('Il tuo Link Referral', 'wcfm-affiliate-pro'); ?></label>
                            <div class="wcfm-affiliate-link-output">
                                <input type="text" id="wcfm-affiliate-link-result" readonly>
                                <button class="wcfm-affiliate-btn wcfm-affiliate-copy-link">
                                    <?php _e('Copia', 'wcfm-affiliate-pro'); ?>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="wcfm-affiliate-quick-links">
                        <h3><?php _e('Link Rapidi', 'wcfm-affiliate-pro'); ?></h3>
                        <div class="wcfm-affiliate-quick-links-grid">
                            <?php $this->render_quick_links($affiliate); ?>
                        </div>
                    </div>
                </div>

                <!-- Referrals Tab -->
                <div class="wcfm-affiliate-tab-content" data-tab="referrals">
                    <h3><?php _e('I Tuoi Referral', 'wcfm-affiliate-pro'); ?></h3>
                    <?php $this->render_referrals_table($affiliate->id); ?>
                </div>

                <!-- Payouts Tab -->
                <div class="wcfm-affiliate-tab-content" data-tab="payouts">
                    <div class="wcfm-affiliate-payout-info">
                        <div class="wcfm-affiliate-payout-balance">
                            <span class="wcfm-affiliate-label"><?php _e('Saldo Disponibile', 'wcfm-affiliate-pro'); ?></span>
                            <span class="wcfm-affiliate-value"><?php echo wc_price($stats['earnings_balance']); ?></span>
                        </div>
                        <div class="wcfm-affiliate-payout-minimum">
                            <span class="wcfm-affiliate-label"><?php _e('Pagamento Minimo', 'wcfm-affiliate-pro'); ?></span>
                            <span class="wcfm-affiliate-value"><?php echo wc_price($minimum_payout); ?></span>
                        </div>
                    </div>

                    <?php if ($stats['earnings_balance'] >= $minimum_payout): ?>
                        <div class="wcfm-affiliate-payout-form">
                            <h4><?php _e('Richiedi Pagamento', 'wcfm-affiliate-pro'); ?></h4>
                            <form id="wcfm-affiliate-payout-form">
                                <div class="wcfm-affiliate-form-group">
                                    <label><?php _e('Importo', 'wcfm-affiliate-pro'); ?></label>
                                    <input type="number" name="amount" min="<?php echo esc_attr($minimum_payout); ?>"
                                           max="<?php echo esc_attr($stats['earnings_balance']); ?>"
                                           value="<?php echo esc_attr($stats['earnings_balance']); ?>" step="0.01">
                                </div>
                                <div class="wcfm-affiliate-form-group">
                                    <label><?php _e('Metodo di Pagamento', 'wcfm-affiliate-pro'); ?></label>
                                    <select name="payment_method">
                                        <?php foreach (wcfm_affiliate_pro()->payouts->get_payment_methods() as $key => $method): ?>
                                            <option value="<?php echo esc_attr($key); ?>" <?php selected($affiliate->payment_method, $key); ?>>
                                                <?php echo esc_html($method['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" class="wcfm-affiliate-btn">
                                    <?php _e('Invia Richiesta', 'wcfm-affiliate-pro'); ?>
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>

                    <div class="wcfm-affiliate-payout-history">
                        <h4><?php _e('Storico Pagamenti', 'wcfm-affiliate-pro'); ?></h4>
                        <?php $this->render_payouts_table($affiliate->id); ?>
                    </div>
                </div>

                <!-- Creatives Tab -->
                <div class="wcfm-affiliate-tab-content" data-tab="creatives">
                    <h3><?php _e('Materiali Promozionali', 'wcfm-affiliate-pro'); ?></h3>
                    <?php $this->render_creatives($affiliate); ?>
                </div>

                <!-- Settings Tab -->
                <div class="wcfm-affiliate-tab-content" data-tab="settings">
                    <h3><?php _e('Impostazioni Account', 'wcfm-affiliate-pro'); ?></h3>
                    <?php $this->render_settings_form($affiliate); ?>
                </div>

            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render referrals table
     */
    private function render_referrals_table(int $affiliate_id, int $limit = 20): void {
        global $wpdb;

        $referrals = $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, o.ID as order_id
             FROM " . WCFM_Affiliate_DB::$table_referrals . " r
             LEFT JOIN {$wpdb->posts} o ON r.order_id = o.ID
             WHERE r.affiliate_id = %d
             ORDER BY r.date_created DESC
             LIMIT %d",
            $affiliate_id,
            $limit
        ));
        ?>
        <table class="wcfm-affiliate-table">
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
                        <td colspan="4" class="wcfm-affiliate-no-data">
                            <?php _e('Nessun referral ancora', 'wcfm-affiliate-pro'); ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($referrals as $referral): ?>
                        <tr>
                            <td><?php echo date_i18n(get_option('date_format'), strtotime($referral->date_created)); ?></td>
                            <td>#<?php echo esc_html($referral->order_id); ?></td>
                            <td><?php echo wc_price($referral->amount); ?></td>
                            <td>
                                <span class="wcfm-affiliate-status wcfm-affiliate-status-<?php echo esc_attr($referral->status); ?>">
                                    <?php echo esc_html($this->get_status_label($referral->status)); ?>
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
        <table class="wcfm-affiliate-table">
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
                        <td colspan="4" class="wcfm-affiliate-no-data">
                            <?php _e('Nessun pagamento ancora', 'wcfm-affiliate-pro'); ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($payouts as $payout): ?>
                        <tr>
                            <td><?php echo date_i18n(get_option('date_format'), strtotime($payout->date_created)); ?></td>
                            <td><?php echo wc_price($payout->amount); ?></td>
                            <td><?php echo esc_html(ucfirst(str_replace('_', ' ', $payout->payment_method))); ?></td>
                            <td>
                                <span class="wcfm-affiliate-status wcfm-affiliate-status-<?php echo esc_attr($payout->status); ?>">
                                    <?php echo esc_html($this->get_payout_status_label($payout->status)); ?>
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
     * Render quick links
     */
    private function render_quick_links(object $affiliate): void {
        $pages = [
            home_url() => __('Homepage', 'wcfm-affiliate-pro'),
            wc_get_page_permalink('shop') => __('Negozio', 'wcfm-affiliate-pro'),
        ];

        // Add popular products
        $products = wc_get_products([
            'status' => 'publish',
            'limit' => 4,
            'orderby' => 'popularity',
        ]);

        foreach ($products as $product) {
            $pages[$product->get_permalink()] = $product->get_name();
        }

        foreach ($pages as $url => $label):
            $referral_link = wcfm_affiliate_pro()->referrals->generate_link($affiliate->id, $url);
            ?>
            <div class="wcfm-affiliate-quick-link">
                <span class="wcfm-affiliate-quick-link-label"><?php echo esc_html($label); ?></span>
                <div class="wcfm-affiliate-quick-link-url">
                    <input type="text" value="<?php echo esc_url($referral_link); ?>" readonly>
                    <button class="wcfm-affiliate-copy-btn" data-copy="<?php echo esc_url($referral_link); ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                        </svg>
                    </button>
                </div>
            </div>
        <?php endforeach;
    }

    /**
     * Render creatives
     */
    private function render_creatives(object $affiliate): void {
        global $wpdb;

        $creatives = $wpdb->get_results(
            "SELECT * FROM " . WCFM_Affiliate_DB::$table_creatives . " WHERE status = 'active' ORDER BY date_created DESC"
        );

        if (empty($creatives)):
            ?>
            <p class="wcfm-affiliate-no-data"><?php _e('Nessun materiale promozionale disponibile', 'wcfm-affiliate-pro'); ?></p>
            <?php
            return;
        endif;
        ?>
        <div class="wcfm-affiliate-creatives-grid">
            <?php foreach ($creatives as $creative): ?>
                <div class="wcfm-affiliate-creative">
                    <?php if ($creative->type === 'banner' && $creative->image_url): ?>
                        <img src="<?php echo esc_url($creative->image_url); ?>" alt="<?php echo esc_attr($creative->name); ?>">
                    <?php endif; ?>
                    <div class="wcfm-affiliate-creative-info">
                        <h4><?php echo esc_html($creative->name); ?></h4>
                        <?php if ($creative->description): ?>
                            <p><?php echo esc_html($creative->description); ?></p>
                        <?php endif; ?>
                        <?php
                        $creative_link = wcfm_affiliate_pro()->referrals->generate_link($affiliate->id, $creative->url);
                        ?>
                        <div class="wcfm-affiliate-creative-code">
                            <textarea readonly><?php echo esc_textarea($this->get_creative_code($creative, $creative_link)); ?></textarea>
                            <button class="wcfm-affiliate-copy-btn">
                                <?php _e('Copia Codice', 'wcfm-affiliate-pro'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Get creative embed code
     */
    private function get_creative_code(object $creative, string $link): string {
        if ($creative->type === 'banner') {
            return sprintf(
                '<a href="%s" target="_blank" rel="noopener"><img src="%s" alt="%s" width="%d" height="%d"></a>',
                esc_url($link),
                esc_url($creative->image_url),
                esc_attr($creative->name),
                $creative->width ?: 300,
                $creative->height ?: 250
            );
        }

        return sprintf(
            '<a href="%s" target="_blank" rel="noopener">%s</a>',
            esc_url($link),
            esc_html($creative->text ?: $creative->name)
        );
    }

    /**
     * Render settings form
     */
    private function render_settings_form(object $affiliate): void {
        $payment_methods = wcfm_affiliate_pro()->payouts->get_payment_methods();
        $payment_details = $affiliate->payment_details ? json_decode($affiliate->payment_details, true) : [];
        ?>
        <form id="wcfm-affiliate-settings-form" class="wcfm-affiliate-form">
            <div class="wcfm-affiliate-form-section">
                <h4><?php _e('Informazioni Pagamento', 'wcfm-affiliate-pro'); ?></h4>

                <div class="wcfm-affiliate-form-group">
                    <label for="payment_method"><?php _e('Metodo di Pagamento Preferito', 'wcfm-affiliate-pro'); ?></label>
                    <select name="payment_method" id="payment_method">
                        <?php foreach ($payment_methods as $key => $method): ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($affiliate->payment_method, $key); ?>>
                                <?php echo esc_html($method['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="wcfm-affiliate-form-group">
                    <label for="payment_email"><?php _e('Email Pagamento', 'wcfm-affiliate-pro'); ?></label>
                    <input type="email" name="payment_email" id="payment_email"
                           value="<?php echo esc_attr($affiliate->payment_email); ?>">
                </div>

                <!-- Payment method specific fields -->
                <?php foreach ($payment_methods as $key => $method):
                    if (empty($method['fields'])) continue;
                    ?>
                    <div class="wcfm-affiliate-payment-fields" data-method="<?php echo esc_attr($key); ?>"
                         style="<?php echo $affiliate->payment_method !== $key ? 'display:none;' : ''; ?>">
                        <?php foreach ($method['fields'] as $field_key => $field): ?>
                            <div class="wcfm-affiliate-form-group">
                                <label for="payment_<?php echo esc_attr($field_key); ?>">
                                    <?php echo esc_html($field['label']); ?>
                                    <?php if ($field['required']): ?><span class="required">*</span><?php endif; ?>
                                </label>
                                <input type="<?php echo esc_attr($field['type']); ?>"
                                       name="payment_details[<?php echo esc_attr($field_key); ?>]"
                                       id="payment_<?php echo esc_attr($field_key); ?>"
                                       value="<?php echo esc_attr($payment_details[$field_key] ?? ''); ?>"
                                       <?php echo $field['required'] ? 'required' : ''; ?>>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="wcfm-affiliate-form-section">
                <h4><?php _e('Informazioni Promozionali', 'wcfm-affiliate-pro'); ?></h4>

                <div class="wcfm-affiliate-form-group">
                    <label for="website_url"><?php _e('Sito Web', 'wcfm-affiliate-pro'); ?></label>
                    <input type="url" name="website_url" id="website_url"
                           value="<?php echo esc_url($affiliate->website_url); ?>"
                           placeholder="https://tuosito.com">
                </div>

                <div class="wcfm-affiliate-form-group">
                    <label for="promotional_methods"><?php _e('Metodi Promozionali', 'wcfm-affiliate-pro'); ?></label>
                    <textarea name="promotional_methods" id="promotional_methods" rows="4"
                              placeholder="<?php _e('Descrivi come promuoverai i nostri prodotti (blog, social, email, ecc.)', 'wcfm-affiliate-pro'); ?>"><?php echo esc_textarea($affiliate->promotional_methods); ?></textarea>
                </div>
            </div>

            <button type="submit" class="wcfm-affiliate-btn">
                <?php _e('Salva Impostazioni', 'wcfm-affiliate-pro'); ?>
            </button>
        </form>
        <?php
    }

    /**
     * Get status label
     */
    private function get_status_label(string $status): string {
        $labels = [
            'pending' => __('In Attesa', 'wcfm-affiliate-pro'),
            'approved' => __('Approvato', 'wcfm-affiliate-pro'),
            'rejected' => __('Rifiutato', 'wcfm-affiliate-pro'),
            'paid' => __('Pagato', 'wcfm-affiliate-pro'),
        ];

        return $labels[$status] ?? $status;
    }

    /**
     * Get payout status label
     */
    private function get_payout_status_label(string $status): string {
        $labels = [
            'pending' => __('In Attesa', 'wcfm-affiliate-pro'),
            'processing' => __('In Elaborazione', 'wcfm-affiliate-pro'),
            'completed' => __('Completato', 'wcfm-affiliate-pro'),
            'failed' => __('Fallito', 'wcfm-affiliate-pro'),
            'cancelled' => __('Annullato', 'wcfm-affiliate-pro'),
        ];

        return $labels[$status] ?? $status;
    }

    /**
     * Get dashboard data via AJAX
     */
    public function get_dashboard_data(): void {
        check_ajax_referer('wcfm_affiliate_pro_nonce', 'nonce');

        $user_id = get_current_user_id();
        $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate_by_user($user_id);

        if (!$affiliate) {
            wp_send_json_error();
        }

        $period = sanitize_text_field($_POST['period'] ?? '30days');
        $stats = wcfm_affiliate_pro()->referrals->get_stats($affiliate->id, $period);

        wp_send_json_success($stats);
    }

    /**
     * Get chart data via AJAX
     */
    public function get_chart_data(): void {
        check_ajax_referer('wcfm_affiliate_pro_nonce', 'nonce');

        $user_id = get_current_user_id();
        $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate_by_user($user_id);

        if (!$affiliate) {
            wp_send_json_error();
        }

        $period = sanitize_text_field($_POST['period'] ?? '30days');

        global $wpdb;

        $date_condition = '';
        switch ($period) {
            case '7days':
                $date_condition = 'AND date_created >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
                $days = 7;
                break;
            case '30days':
                $date_condition = 'AND date_created >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
                $days = 30;
                break;
            case '90days':
                $date_condition = 'AND date_created >= DATE_SUB(NOW(), INTERVAL 90 DAY)';
                $days = 90;
                break;
            default:
                $date_condition = 'AND date_created >= DATE_SUB(NOW(), INTERVAL 365 DAY)';
                $days = 365;
        }

        // Get commissions by day
        $commissions = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(date_created) as date, SUM(commission_amount) as total
             FROM " . WCFM_Affiliate_DB::$table_commissions . "
             WHERE affiliate_id = %d $date_condition
             GROUP BY DATE(date_created)
             ORDER BY date ASC",
            $affiliate->id
        ), OBJECT_K);

        // Get clicks by day
        $clicks = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(date_created) as date, COUNT(*) as total
             FROM " . WCFM_Affiliate_DB::$table_clicks . "
             WHERE affiliate_id = %d $date_condition
             GROUP BY DATE(date_created)
             ORDER BY date ASC",
            $affiliate->id
        ), OBJECT_K);

        // Build data for chart
        $labels = [];
        $commission_data = [];
        $click_data = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $labels[] = date_i18n('j M', strtotime($date));
            $commission_data[] = isset($commissions[$date]) ? (float) $commissions[$date]->total : 0;
            $click_data[] = isset($clicks[$date]) ? (int) $clicks[$date]->total : 0;
        }

        wp_send_json_success([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => __('Commissioni', 'wcfm-affiliate-pro'),
                    'data' => $commission_data,
                    'borderColor' => '#00897b',
                    'backgroundColor' => 'rgba(0, 137, 123, 0.1)',
                    'fill' => true,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => __('Click', 'wcfm-affiliate-pro'),
                    'data' => $click_data,
                    'borderColor' => '#7c4dff',
                    'backgroundColor' => 'rgba(124, 77, 255, 0.1)',
                    'fill' => true,
                    'yAxisID' => 'y1',
                ],
            ],
        ]);
    }
}
