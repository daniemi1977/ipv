<?php
/**
 * Dual Role Handler - Affiliate ↔ Vendor Transitions
 *
 * Gestisce la transizione semplificata tra:
 * - Affiliato → Venditore (aprire uno shop mantenendo status affiliato)
 * - Venditore → Affiliato (ottenere codice affiliato per guadagnare commissioni)
 *
 * @package WCFM_Affiliate_Pro
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class WCFM_Affiliate_Dual_Role {

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
        // AJAX handlers
        add_action('wp_ajax_wcfm_aff_pro_become_vendor', [$this, 'ajax_become_vendor']);
        add_action('wp_ajax_wcfm_aff_pro_become_affiliate', [$this, 'ajax_become_affiliate']);
        add_action('wp_ajax_wcfm_aff_pro_link_accounts', [$this, 'ajax_link_accounts']);
        add_action('wp_ajax_wcfm_aff_pro_get_dual_role_status', [$this, 'ajax_get_status']);

        // Auto-link when vendor is created
        add_action('wcfm_membership_registration', [$this, 'on_vendor_registration'], 10, 2);
        add_action('wcfmmp_store_created', [$this, 'on_store_created'], 10, 1);

        // Auto-link when affiliate is approved
        add_action('wcfm_aff_pro_affiliate_approved', [$this, 'on_affiliate_approved'], 10, 1);

        // Add widgets to dashboards
        add_action('wcfm_affiliate_pro_dashboard_widgets', [$this, 'add_become_vendor_widget'], 20);
        add_action('wcfm_vendor_dashboard_after_widgets', [$this, 'add_become_affiliate_widget'], 20);

        // Shortcodes
        add_shortcode('wcfm_aff_pro_dual_role', [$this, 'dual_role_shortcode']);
        add_shortcode('wcfm_aff_pro_become_vendor', [$this, 'become_vendor_shortcode']);
        add_shortcode('wcfm_aff_pro_become_affiliate', [$this, 'become_affiliate_shortcode']);
    }

    /**
     * Check if user is a vendor
     */
    public function is_vendor(int $user_id = 0): bool {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        if (!$user_id) {
            return false;
        }

        // Check WCFM vendor role
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }

        $vendor_roles = ['wcfm_vendor', 'seller', 'vendor', 'dc_vendor', 'wc_product_vendors_admin_vendor'];

        foreach ($vendor_roles as $role) {
            if (in_array($role, (array) $user->roles)) {
                return true;
            }
        }

        // Check WCFM vendor status
        if (function_exists('wcfm_is_vendor')) {
            return wcfm_is_vendor($user_id);
        }

        return false;
    }

    /**
     * Check if user is an affiliate
     */
    public function is_affiliate(int $user_id = 0): bool {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        if (!$user_id) {
            return false;
        }

        $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate_by_user($user_id);
        return $affiliate && $affiliate->status === 'active';
    }

    /**
     * Check if user has pending affiliate application
     */
    public function has_pending_affiliate(int $user_id = 0): bool {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate_by_user($user_id);
        return $affiliate && $affiliate->status === 'pending';
    }

    /**
     * Get user's dual role status
     */
    public function get_dual_role_status(int $user_id = 0): array {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate_by_user($user_id);

        return [
            'is_vendor' => $this->is_vendor($user_id),
            'is_affiliate' => $this->is_affiliate($user_id),
            'has_pending_affiliate' => $this->has_pending_affiliate($user_id),
            'affiliate_code' => $affiliate ? $affiliate->affiliate_code : null,
            'affiliate_id' => $affiliate ? $affiliate->id : null,
            'vendor_id' => $this->is_vendor($user_id) ? $user_id : null,
            'can_become_vendor' => $this->can_become_vendor($user_id),
            'can_become_affiliate' => $this->can_become_affiliate($user_id),
        ];
    }

    /**
     * Check if user can become a vendor
     */
    public function can_become_vendor(int $user_id = 0): bool {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        // Already a vendor
        if ($this->is_vendor($user_id)) {
            return false;
        }

        // Check if WCFM allows vendor registration
        if (class_exists('WCFMmp')) {
            $wcfm_registration = get_option('wcfm_registration_options', []);
            if (isset($wcfm_registration['enable_registration']) && $wcfm_registration['enable_registration'] === 'no') {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if user can become an affiliate
     */
    public function can_become_affiliate(int $user_id = 0): bool {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        // Already an affiliate or has pending
        $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate_by_user($user_id);
        if ($affiliate) {
            return false;
        }

        // Check settings
        $settings = get_option('wcfm_aff_pro_general', []);
        if (isset($settings['enable']) && $settings['enable'] === 'no') {
            return false;
        }

        return true;
    }

    /**
     * Register vendor as affiliate (quick registration)
     */
    public function register_vendor_as_affiliate(int $user_id, array $data = []): array {
        if (!$this->is_vendor($user_id)) {
            return ['success' => false, 'message' => __('L\'utente non è un venditore.', 'wcfm-affiliate-pro')];
        }

        // Check if already affiliate
        $existing = wcfm_affiliate_pro()->affiliates->get_affiliate_by_user($user_id);
        if ($existing) {
            return [
                'success' => true,
                'message' => __('Sei già un affiliato!', 'wcfm-affiliate-pro'),
                'affiliate_code' => $existing->affiliate_code,
                'status' => $existing->status,
            ];
        }

        $user = get_userdata($user_id);
        $settings = get_option('wcfm_aff_pro_general', []);

        // Prepare affiliate data
        $affiliate_data = [
            'payment_email' => $data['payment_email'] ?? $user->user_email,
            'payment_method' => $data['payment_method'] ?? 'bank_transfer',
            'website' => $data['website'] ?? '',
            'notes' => $data['notes'] ?? __('Registrato automaticamente come venditore', 'wcfm-affiliate-pro'),
        ];

        // Auto-approve vendors as affiliates if setting enabled
        $auto_approve = ($settings['auto_approve_vendors'] ?? 'no') === 'yes';

        $affiliate_id = wcfm_affiliate_pro()->affiliates->register_affiliate($user_id, $affiliate_data);

        if (!$affiliate_id) {
            return ['success' => false, 'message' => __('Errore durante la registrazione.', 'wcfm-affiliate-pro')];
        }

        // Auto approve if enabled
        if ($auto_approve) {
            wcfm_affiliate_pro()->affiliates->update_affiliate_status($affiliate_id, 'active');
        }

        $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate($affiliate_id);

        return [
            'success' => true,
            'message' => $auto_approve
                ? __('Sei ora un affiliato! Il tuo codice è pronto.', 'wcfm-affiliate-pro')
                : __('Richiesta inviata! Riceverai una notifica quando sarà approvata.', 'wcfm-affiliate-pro'),
            'affiliate_code' => $affiliate->affiliate_code,
            'status' => $affiliate->status,
            'affiliate_id' => $affiliate_id,
        ];
    }

    /**
     * Redirect affiliate to vendor registration
     */
    public function get_vendor_registration_url(int $user_id = 0): string {
        // WCFM vendor registration page
        if (class_exists('WCFMmp')) {
            $vendor_reg_page = get_option('wcfm_vendor_registration_page_id', 0);
            if ($vendor_reg_page) {
                return get_permalink($vendor_reg_page);
            }
        }

        // Fallback to WCFM endpoint
        if (function_exists('wcfm_get_endpoint_url')) {
            return wcfm_get_endpoint_url('wcfm-vendor-registration');
        }

        // WooCommerce my account
        return wc_get_page_permalink('myaccount');
    }

    /**
     * AJAX: Become vendor (redirect with data)
     */
    public function ajax_become_vendor(): void {
        check_ajax_referer('wcfm_affiliate_pro_nonce', 'nonce');

        $user_id = get_current_user_id();

        if (!$user_id) {
            wp_send_json_error(['message' => __('Devi essere loggato.', 'wcfm-affiliate-pro')]);
        }

        if ($this->is_vendor($user_id)) {
            wp_send_json_error(['message' => __('Sei già un venditore.', 'wcfm-affiliate-pro')]);
        }

        // Store affiliate data in transient for pre-fill
        $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate_by_user($user_id);
        if ($affiliate) {
            set_transient('wcfm_aff_pro_vendor_prefill_' . $user_id, [
                'affiliate_id' => $affiliate->id,
                'affiliate_code' => $affiliate->affiliate_code,
                'user_id' => $user_id,
            ], HOUR_IN_SECONDS);
        }

        wp_send_json_success([
            'message' => __('Reindirizzamento alla registrazione venditore...', 'wcfm-affiliate-pro'),
            'redirect_url' => $this->get_vendor_registration_url($user_id),
        ]);
    }

    /**
     * AJAX: Become affiliate
     */
    public function ajax_become_affiliate(): void {
        check_ajax_referer('wcfm_affiliate_pro_nonce', 'nonce');

        $user_id = get_current_user_id();

        if (!$user_id) {
            wp_send_json_error(['message' => __('Devi essere loggato.', 'wcfm-affiliate-pro')]);
        }

        $data = [
            'payment_email' => sanitize_email($_POST['payment_email'] ?? ''),
            'payment_method' => sanitize_text_field($_POST['payment_method'] ?? 'bank_transfer'),
        ];

        $result = $this->register_vendor_as_affiliate($user_id, $data);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * AJAX: Link existing accounts
     */
    public function ajax_link_accounts(): void {
        check_ajax_referer('wcfm_affiliate_pro_nonce', 'nonce');

        $user_id = get_current_user_id();

        if (!$user_id) {
            wp_send_json_error(['message' => __('Devi essere loggato.', 'wcfm-affiliate-pro')]);
        }

        // Already handled by user_id in both systems
        wp_send_json_success([
            'message' => __('I tuoi account sono già collegati.', 'wcfm-affiliate-pro'),
            'status' => $this->get_dual_role_status($user_id),
        ]);
    }

    /**
     * AJAX: Get dual role status
     */
    public function ajax_get_status(): void {
        check_ajax_referer('wcfm_affiliate_pro_nonce', 'nonce');

        $user_id = get_current_user_id();

        if (!$user_id) {
            wp_send_json_error(['message' => __('Devi essere loggato.', 'wcfm-affiliate-pro')]);
        }

        wp_send_json_success($this->get_dual_role_status($user_id));
    }

    /**
     * On vendor registration - auto-register as affiliate if they were one
     */
    public function on_vendor_registration(int $vendor_id, array $data): void {
        // Check if they had a prefill (came from affiliate dashboard)
        $prefill = get_transient('wcfm_aff_pro_vendor_prefill_' . $vendor_id);

        if ($prefill) {
            // They were already an affiliate, just delete transient
            delete_transient('wcfm_aff_pro_vendor_prefill_' . $vendor_id);
            return;
        }

        // Check settings for auto-registration
        $settings = get_option('wcfm_aff_pro_general', []);
        if (($settings['auto_register_vendors'] ?? 'no') === 'yes') {
            $this->register_vendor_as_affiliate($vendor_id);
        }
    }

    /**
     * On store created
     */
    public function on_store_created(int $vendor_id): void {
        // Same logic as vendor registration
        $this->on_vendor_registration($vendor_id, []);
    }

    /**
     * On affiliate approved - check if they're also a vendor
     */
    public function on_affiliate_approved(int $affiliate_id): void {
        $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate($affiliate_id);
        if (!$affiliate) {
            return;
        }

        // If they're also a vendor, we can add special capabilities or bonuses
        if ($this->is_vendor($affiliate->user_id)) {
            // Maybe add vendor-affiliate bonus tier
            do_action('wcfm_aff_pro_vendor_affiliate_activated', $affiliate->user_id, $affiliate_id);
        }
    }

    /**
     * Add "Become a Vendor" widget to affiliate dashboard
     */
    public function add_become_vendor_widget(): void {
        $user_id = get_current_user_id();

        if ($this->is_vendor($user_id)) {
            // Already a vendor - show linked status
            $this->render_linked_vendor_widget($user_id);
            return;
        }

        if (!$this->can_become_vendor($user_id)) {
            return;
        }

        $this->render_become_vendor_widget($user_id);
    }

    /**
     * Add "Become an Affiliate" widget to vendor dashboard
     */
    public function add_become_affiliate_widget(): void {
        $user_id = get_current_user_id();

        if (!$this->is_vendor($user_id)) {
            return;
        }

        if ($this->is_affiliate($user_id)) {
            // Already an affiliate - show affiliate code
            $this->render_linked_affiliate_widget($user_id);
            return;
        }

        if ($this->has_pending_affiliate($user_id)) {
            $this->render_pending_affiliate_widget($user_id);
            return;
        }

        if (!$this->can_become_affiliate($user_id)) {
            return;
        }

        $this->render_become_affiliate_widget($user_id);
    }

    /**
     * Render "Become a Vendor" widget
     */
    private function render_become_vendor_widget(int $user_id): void {
        ?>
        <div class="wcfm-aff-pro-dual-role-widget wcfm-aff-pro-become-vendor">
            <div class="widget-header">
                <span class="dashicons dashicons-store"></span>
                <h3><?php _e('Apri il tuo Shop', 'wcfm-affiliate-pro'); ?></h3>
            </div>
            <div class="widget-content">
                <p><?php _e('Vuoi vendere i tuoi prodotti? Diventa un venditore e apri il tuo negozio online, mantenendo i tuoi guadagni da affiliato!', 'wcfm-affiliate-pro'); ?></p>
                <ul class="benefits-list">
                    <li><span class="dashicons dashicons-yes"></span> <?php _e('Mantieni il tuo codice affiliato', 'wcfm-affiliate-pro'); ?></li>
                    <li><span class="dashicons dashicons-yes"></span> <?php _e('Guadagna vendendo + referral', 'wcfm-affiliate-pro'); ?></li>
                    <li><span class="dashicons dashicons-yes"></span> <?php _e('Gestione unificata', 'wcfm-affiliate-pro'); ?></li>
                </ul>
                <button type="button" class="wcfm-aff-pro-btn wcfm-aff-pro-btn-primary" id="wcfm-aff-pro-become-vendor">
                    <span class="dashicons dashicons-store"></span>
                    <?php _e('Diventa Venditore', 'wcfm-affiliate-pro'); ?>
                </button>
            </div>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#wcfm-aff-pro-become-vendor').on('click', function() {
                var $btn = $(this);
                $btn.prop('disabled', true).addClass('loading');

                $.ajax({
                    url: wcfm_affiliate_pro.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wcfm_aff_pro_become_vendor',
                        nonce: wcfm_affiliate_pro.nonce
                    },
                    success: function(response) {
                        if (response.success && response.data.redirect_url) {
                            window.location.href = response.data.redirect_url;
                        } else {
                            alert(response.data.message || 'Errore');
                            $btn.prop('disabled', false).removeClass('loading');
                        }
                    },
                    error: function() {
                        alert('Errore di connessione');
                        $btn.prop('disabled', false).removeClass('loading');
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Render "Become an Affiliate" widget for vendors
     */
    private function render_become_affiliate_widget(int $user_id): void {
        $user = get_userdata($user_id);
        ?>
        <div class="wcfm-aff-pro-dual-role-widget wcfm-aff-pro-become-affiliate">
            <div class="widget-header">
                <span class="dashicons dashicons-groups"></span>
                <h3><?php _e('Ottieni il tuo Codice Affiliato', 'wcfm-affiliate-pro'); ?></h3>
            </div>
            <div class="widget-content">
                <p><?php _e('Guadagna commissioni extra invitando nuovi clienti! Ottieni il tuo codice affiliato personale.', 'wcfm-affiliate-pro'); ?></p>
                <ul class="benefits-list">
                    <li><span class="dashicons dashicons-yes"></span> <?php _e('Commissioni su ogni referral', 'wcfm-affiliate-pro'); ?></li>
                    <li><span class="dashicons dashicons-yes"></span> <?php _e('Link di invito personalizzato', 'wcfm-affiliate-pro'); ?></li>
                    <li><span class="dashicons dashicons-yes"></span> <?php _e('Statistiche e report', 'wcfm-affiliate-pro'); ?></li>
                </ul>

                <form id="wcfm-aff-pro-affiliate-form" class="affiliate-quick-form">
                    <div class="form-row">
                        <label for="aff_payment_email"><?php _e('Email per pagamenti', 'wcfm-affiliate-pro'); ?></label>
                        <input type="email" id="aff_payment_email" name="payment_email"
                               value="<?php echo esc_attr($user->user_email); ?>" required>
                    </div>
                    <div class="form-row">
                        <label for="aff_payment_method"><?php _e('Metodo di pagamento', 'wcfm-affiliate-pro'); ?></label>
                        <select id="aff_payment_method" name="payment_method">
                            <option value="bank_transfer"><?php _e('Bonifico Bancario', 'wcfm-affiliate-pro'); ?></option>
                            <option value="paypal"><?php _e('PayPal', 'wcfm-affiliate-pro'); ?></option>
                            <option value="store_credit"><?php _e('Credito Negozio', 'wcfm-affiliate-pro'); ?></option>
                        </select>
                    </div>
                    <button type="submit" class="wcfm-aff-pro-btn wcfm-aff-pro-btn-success">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php _e('Ottieni Codice Affiliato', 'wcfm-affiliate-pro'); ?>
                    </button>
                </form>
            </div>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#wcfm-aff-pro-affiliate-form').on('submit', function(e) {
                e.preventDefault();
                var $form = $(this);
                var $btn = $form.find('button[type="submit"]');

                $btn.prop('disabled', true).addClass('loading');

                $.ajax({
                    url: wcfm_affiliate_pro.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wcfm_aff_pro_become_affiliate',
                        nonce: wcfm_affiliate_pro.nonce,
                        payment_email: $('#aff_payment_email').val(),
                        payment_method: $('#aff_payment_method').val()
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.data.message || 'Errore');
                            $btn.prop('disabled', false).removeClass('loading');
                        }
                    },
                    error: function() {
                        alert('Errore di connessione');
                        $btn.prop('disabled', false).removeClass('loading');
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Render linked vendor widget (when affiliate is also vendor)
     */
    private function render_linked_vendor_widget(int $user_id): void {
        $store_name = '';
        $store_url = '';

        if (function_exists('wcfm_get_vendor_store_name')) {
            $store_name = wcfm_get_vendor_store_name($user_id);
        }

        if (function_exists('wcfmmp_get_store_url')) {
            $store_url = wcfmmp_get_store_url($user_id);
        }
        ?>
        <div class="wcfm-aff-pro-dual-role-widget wcfm-aff-pro-linked-vendor">
            <div class="widget-header">
                <span class="dashicons dashicons-yes-alt" style="color: #10b981;"></span>
                <h3><?php _e('Sei anche Venditore!', 'wcfm-affiliate-pro'); ?></h3>
            </div>
            <div class="widget-content">
                <p><?php _e('Il tuo account affiliato è collegato al tuo negozio.', 'wcfm-affiliate-pro'); ?></p>
                <?php if ($store_name): ?>
                    <p><strong><?php _e('Negozio:', 'wcfm-affiliate-pro'); ?></strong> <?php echo esc_html($store_name); ?></p>
                <?php endif; ?>
                <div class="widget-actions">
                    <?php if ($store_url): ?>
                        <a href="<?php echo esc_url($store_url); ?>" class="wcfm-aff-pro-btn wcfm-aff-pro-btn-outline" target="_blank">
                            <span class="dashicons dashicons-store"></span>
                            <?php _e('Vai al Negozio', 'wcfm-affiliate-pro'); ?>
                        </a>
                    <?php endif; ?>
                    <?php if (function_exists('wcfm_get_endpoint_url')): ?>
                        <a href="<?php echo wcfm_get_endpoint_url('wcfm-dashboard'); ?>" class="wcfm-aff-pro-btn wcfm-aff-pro-btn-outline">
                            <span class="dashicons dashicons-dashboard"></span>
                            <?php _e('Dashboard Venditore', 'wcfm-affiliate-pro'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render linked affiliate widget (when vendor is also affiliate)
     */
    private function render_linked_affiliate_widget(int $user_id): void {
        $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate_by_user($user_id);
        if (!$affiliate) {
            return;
        }

        $stats = wcfm_affiliate_pro()->affiliates->get_affiliate_stats($affiliate->id);
        $referral_url = wcfm_affiliate_pro()->referrals->generate_referral_link(home_url(), $affiliate->affiliate_code);
        ?>
        <div class="wcfm-aff-pro-dual-role-widget wcfm-aff-pro-linked-affiliate">
            <div class="widget-header">
                <span class="dashicons dashicons-groups" style="color: #10b981;"></span>
                <h3><?php _e('Il tuo Codice Affiliato', 'wcfm-affiliate-pro'); ?></h3>
            </div>
            <div class="widget-content">
                <div class="affiliate-code-box">
                    <span class="code-label"><?php _e('Codice:', 'wcfm-affiliate-pro'); ?></span>
                    <code class="affiliate-code"><?php echo esc_html($affiliate->affiliate_code); ?></code>
                    <button type="button" class="copy-code-btn" data-code="<?php echo esc_attr($affiliate->affiliate_code); ?>" title="<?php _e('Copia codice', 'wcfm-affiliate-pro'); ?>">
                        <span class="dashicons dashicons-admin-page"></span>
                    </button>
                </div>

                <div class="affiliate-link-box">
                    <span class="link-label"><?php _e('Link:', 'wcfm-affiliate-pro'); ?></span>
                    <input type="text" class="affiliate-link" value="<?php echo esc_url($referral_url); ?>" readonly>
                    <button type="button" class="copy-link-btn" data-link="<?php echo esc_url($referral_url); ?>" title="<?php _e('Copia link', 'wcfm-affiliate-pro'); ?>">
                        <span class="dashicons dashicons-admin-links"></span>
                    </button>
                </div>

                <div class="affiliate-quick-stats">
                    <div class="stat">
                        <span class="stat-value"><?php echo number_format_i18n($stats['referrals_count'] ?? 0); ?></span>
                        <span class="stat-label"><?php _e('Referral', 'wcfm-affiliate-pro'); ?></span>
                    </div>
                    <div class="stat">
                        <span class="stat-value"><?php echo wc_price($stats['earnings_total'] ?? 0); ?></span>
                        <span class="stat-label"><?php _e('Guadagni', 'wcfm-affiliate-pro'); ?></span>
                    </div>
                    <div class="stat">
                        <span class="stat-value"><?php echo wc_price($stats['earnings_balance'] ?? 0); ?></span>
                        <span class="stat-label"><?php _e('Saldo', 'wcfm-affiliate-pro'); ?></span>
                    </div>
                </div>

                <?php if (function_exists('wcfm_get_endpoint_url')): ?>
                    <a href="<?php echo wcfm_get_endpoint_url('wcfm-affiliate-pro'); ?>" class="wcfm-aff-pro-btn wcfm-aff-pro-btn-primary">
                        <span class="dashicons dashicons-chart-area"></span>
                        <?php _e('Dashboard Affiliato', 'wcfm-affiliate-pro'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('.copy-code-btn, .copy-link-btn').on('click', function() {
                var text = $(this).data('code') || $(this).data('link');
                navigator.clipboard.writeText(text).then(function() {
                    alert('<?php _e('Copiato!', 'wcfm-affiliate-pro'); ?>');
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Render pending affiliate widget
     */
    private function render_pending_affiliate_widget(int $user_id): void {
        ?>
        <div class="wcfm-aff-pro-dual-role-widget wcfm-aff-pro-pending-affiliate">
            <div class="widget-header">
                <span class="dashicons dashicons-clock" style="color: #f59e0b;"></span>
                <h3><?php _e('Richiesta Affiliato in Attesa', 'wcfm-affiliate-pro'); ?></h3>
            </div>
            <div class="widget-content">
                <p><?php _e('La tua richiesta di diventare affiliato è in fase di revisione. Ti notificheremo quando sarà approvata.', 'wcfm-affiliate-pro'); ?></p>
            </div>
        </div>
        <?php
    }

    /**
     * Dual role shortcode - shows status and actions
     */
    public function dual_role_shortcode($atts): string {
        if (!is_user_logged_in()) {
            return '<p>' . __('Devi essere loggato per visualizzare questa sezione.', 'wcfm-affiliate-pro') . '</p>';
        }

        $user_id = get_current_user_id();
        $status = $this->get_dual_role_status($user_id);

        ob_start();
        ?>
        <div class="wcfm-aff-pro-dual-role-status">
            <h3><?php _e('Il tuo Status', 'wcfm-affiliate-pro'); ?></h3>

            <div class="status-cards">
                <div class="status-card <?php echo $status['is_affiliate'] ? 'active' : ($status['has_pending_affiliate'] ? 'pending' : 'inactive'); ?>">
                    <span class="dashicons dashicons-groups"></span>
                    <span class="status-label"><?php _e('Affiliato', 'wcfm-affiliate-pro'); ?></span>
                    <span class="status-value">
                        <?php
                        if ($status['is_affiliate']) {
                            echo '<span class="badge badge-success">' . __('Attivo', 'wcfm-affiliate-pro') . '</span>';
                            echo '<code>' . esc_html($status['affiliate_code']) . '</code>';
                        } elseif ($status['has_pending_affiliate']) {
                            echo '<span class="badge badge-warning">' . __('In Attesa', 'wcfm-affiliate-pro') . '</span>';
                        } else {
                            echo '<span class="badge badge-inactive">' . __('Non attivo', 'wcfm-affiliate-pro') . '</span>';
                        }
                        ?>
                    </span>
                </div>

                <div class="status-card <?php echo $status['is_vendor'] ? 'active' : 'inactive'; ?>">
                    <span class="dashicons dashicons-store"></span>
                    <span class="status-label"><?php _e('Venditore', 'wcfm-affiliate-pro'); ?></span>
                    <span class="status-value">
                        <?php
                        if ($status['is_vendor']) {
                            echo '<span class="badge badge-success">' . __('Attivo', 'wcfm-affiliate-pro') . '</span>';
                        } else {
                            echo '<span class="badge badge-inactive">' . __('Non attivo', 'wcfm-affiliate-pro') . '</span>';
                        }
                        ?>
                    </span>
                </div>
            </div>

            <?php if ($status['can_become_vendor'] || $status['can_become_affiliate']): ?>
                <div class="upgrade-options">
                    <h4><?php _e('Espandi le tue possibilità', 'wcfm-affiliate-pro'); ?></h4>

                    <?php if ($status['can_become_affiliate']): ?>
                        <div class="upgrade-option">
                            <?php $this->render_become_affiliate_widget($user_id); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($status['can_become_vendor']): ?>
                        <div class="upgrade-option">
                            <?php $this->render_become_vendor_widget($user_id); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Become vendor shortcode
     */
    public function become_vendor_shortcode($atts): string {
        if (!is_user_logged_in()) {
            return '<p>' . __('Devi essere loggato.', 'wcfm-affiliate-pro') . '</p>';
        }

        $user_id = get_current_user_id();

        if ($this->is_vendor($user_id)) {
            return '<p>' . __('Sei già un venditore!', 'wcfm-affiliate-pro') . '</p>';
        }

        ob_start();
        $this->render_become_vendor_widget($user_id);
        return ob_get_clean();
    }

    /**
     * Become affiliate shortcode
     */
    public function become_affiliate_shortcode($atts): string {
        if (!is_user_logged_in()) {
            return '<p>' . __('Devi essere loggato.', 'wcfm-affiliate-pro') . '</p>';
        }

        $user_id = get_current_user_id();

        if ($this->is_affiliate($user_id)) {
            $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate_by_user($user_id);
            return '<p>' . __('Sei già un affiliato!', 'wcfm-affiliate-pro') . ' ' .
                   __('Il tuo codice:', 'wcfm-affiliate-pro') . ' <code>' . esc_html($affiliate->affiliate_code) . '</code></p>';
        }

        if ($this->has_pending_affiliate($user_id)) {
            return '<p>' . __('La tua richiesta è in fase di revisione.', 'wcfm-affiliate-pro') . '</p>';
        }

        ob_start();
        $this->render_become_affiliate_widget($user_id);
        return ob_get_clean();
    }
}
