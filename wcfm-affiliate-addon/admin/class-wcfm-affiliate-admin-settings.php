<?php
/**
 * Admin Settings Page
 *
 * @package WCFM_Affiliate_Pro
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class WCFM_Affiliate_Admin_Settings {

    public function render(): void {
        $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
        ?>
        <div class="wrap wcfm-affiliate-admin">
            <h1><?php _e('Impostazioni Affiliate Pro', 'wcfm-affiliate-pro'); ?></h1>

            <nav class="nav-tab-wrapper">
                <a href="?page=wcfm-affiliate-settings&tab=general" class="nav-tab <?php echo $current_tab === 'general' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Generale', 'wcfm-affiliate-pro'); ?>
                </a>
                <a href="?page=wcfm-affiliate-settings&tab=commission" class="nav-tab <?php echo $current_tab === 'commission' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Commissioni', 'wcfm-affiliate-pro'); ?>
                </a>
                <a href="?page=wcfm-affiliate-settings&tab=mlm" class="nav-tab <?php echo $current_tab === 'mlm' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Multi-Livello', 'wcfm-affiliate-pro'); ?>
                </a>
                <a href="?page=wcfm-affiliate-settings&tab=notifications" class="nav-tab <?php echo $current_tab === 'notifications' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Notifiche', 'wcfm-affiliate-pro'); ?>
                </a>
                <a href="?page=wcfm-affiliate-settings&tab=payments" class="nav-tab <?php echo $current_tab === 'payments' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Pagamenti', 'wcfm-affiliate-pro'); ?>
                </a>
                <a href="?page=wcfm-affiliate-settings&tab=design" class="nav-tab <?php echo $current_tab === 'design' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Design', 'wcfm-affiliate-pro'); ?>
                </a>
            </nav>

            <form method="post" action="options.php">
                <?php
                switch ($current_tab) {
                    case 'commission':
                        $this->render_commission_settings();
                        break;
                    case 'mlm':
                        $this->render_mlm_settings();
                        break;
                    case 'notifications':
                        $this->render_notification_settings();
                        break;
                    case 'payments':
                        $this->render_payment_settings();
                        break;
                    case 'design':
                        $this->render_design_settings();
                        break;
                    default:
                        $this->render_general_settings();
                }
                ?>
            </form>
        </div>
        <?php
    }

    private function render_general_settings(): void {
        settings_fields('wcfm_affiliate_general');
        $options = get_option('wcfm_affiliate_general', []);
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Abilita Sistema Affiliate', 'wcfm-affiliate-pro'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="wcfm_affiliate_general[enable]" value="yes"
                            <?php checked($options['enable'] ?? 'yes', 'yes'); ?>>
                        <?php _e('Attiva il programma di affiliazione', 'wcfm-affiliate-pro'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Tipo Registrazione', 'wcfm-affiliate-pro'); ?></th>
                <td>
                    <select name="wcfm_affiliate_general[registration_type]">
                        <option value="auto" <?php selected($options['registration_type'] ?? 'approval', 'auto'); ?>>
                            <?php _e('Approvazione Automatica', 'wcfm-affiliate-pro'); ?>
                        </option>
                        <option value="approval" <?php selected($options['registration_type'] ?? 'approval', 'approval'); ?>>
                            <?php _e('Richiede Approvazione', 'wcfm-affiliate-pro'); ?>
                        </option>
                        <option value="invite" <?php selected($options['registration_type'] ?? 'approval', 'invite'); ?>>
                            <?php _e('Solo su Invito', 'wcfm-affiliate-pro'); ?>
                        </option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Durata Cookie (giorni)', 'wcfm-affiliate-pro'); ?></th>
                <td>
                    <input type="number" name="wcfm_affiliate_general[cookie_duration]" min="1" max="365"
                           value="<?php echo esc_attr($options['cookie_duration'] ?? 30); ?>">
                    <p class="description"><?php _e('Per quanto tempo il referral viene tracciato dopo il click.', 'wcfm-affiliate-pro'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Nome Cookie', 'wcfm-affiliate-pro'); ?></th>
                <td>
                    <input type="text" name="wcfm_affiliate_general[cookie_name]" class="regular-text"
                           value="<?php echo esc_attr($options['cookie_name'] ?? 'wcfm_affiliate_ref'); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Variabile URL Referral', 'wcfm-affiliate-pro'); ?></th>
                <td>
                    <input type="text" name="wcfm_affiliate_general[referral_var]" class="regular-text"
                           value="<?php echo esc_attr($options['referral_var'] ?? 'ref'); ?>">
                    <p class="description"><?php _e('Es: tuosito.com/?ref=codice', 'wcfm-affiliate-pro'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Credito Referrer', 'wcfm-affiliate-pro'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="wcfm_affiliate_general[credit_last_referrer]" value="yes"
                            <?php checked($options['credit_last_referrer'] ?? 'yes', 'yes'); ?>>
                        <?php _e('Accredita l\'ultimo referrer (altrimenti il primo)', 'wcfm-affiliate-pro'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Auto-Approva Vendor', 'wcfm-affiliate-pro'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="wcfm_affiliate_general[auto_approve_vendors]" value="yes"
                            <?php checked($options['auto_approve_vendors'] ?? 'no', 'yes'); ?>>
                        <?php _e('Approva automaticamente i vendor WCFM come affiliati', 'wcfm-affiliate-pro'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Consenti Auto-Referral', 'wcfm-affiliate-pro'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="wcfm_affiliate_general[allow_self_referral]" value="yes"
                            <?php checked($options['allow_self_referral'] ?? 'no', 'yes'); ?>>
                        <?php _e('Permetti agli affiliati di guadagnare commissioni sui propri acquisti', 'wcfm-affiliate-pro'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Pagamento Minimo', 'wcfm-affiliate-pro'); ?></th>
                <td>
                    <input type="number" name="wcfm_affiliate_general[minimum_payout]" min="0" step="0.01"
                           value="<?php echo esc_attr($options['minimum_payout'] ?? 50); ?>">
                    <?php echo get_woocommerce_currency_symbol(); ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Metodi di Pagamento', 'wcfm-affiliate-pro'); ?></th>
                <td>
                    <?php
                    $methods = ['paypal' => 'PayPal', 'bank_transfer' => 'Bonifico Bancario', 'stripe' => 'Stripe', 'store_credit' => 'Credito Negozio'];
                    $enabled = $options['payout_methods'] ?? ['paypal', 'bank_transfer'];
                    foreach ($methods as $key => $label):
                        ?>
                        <label style="display: block; margin-bottom: 5px;">
                            <input type="checkbox" name="wcfm_affiliate_general[payout_methods][]"
                                   value="<?php echo esc_attr($key); ?>"
                                <?php checked(in_array($key, $enabled)); ?>>
                            <?php echo esc_html($label); ?>
                        </label>
                    <?php endforeach; ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Programmazione Pagamenti', 'wcfm-affiliate-pro'); ?></th>
                <td>
                    <select name="wcfm_affiliate_general[payout_schedule]">
                        <option value="manual" <?php selected($options['payout_schedule'] ?? 'monthly', 'manual'); ?>>
                            <?php _e('Manuale', 'wcfm-affiliate-pro'); ?>
                        </option>
                        <option value="weekly" <?php selected($options['payout_schedule'] ?? 'monthly', 'weekly'); ?>>
                            <?php _e('Settimanale', 'wcfm-affiliate-pro'); ?>
                        </option>
                        <option value="biweekly" <?php selected($options['payout_schedule'] ?? 'monthly', 'biweekly'); ?>>
                            <?php _e('Bisettimanale', 'wcfm-affiliate-pro'); ?>
                        </option>
                        <option value="monthly" <?php selected($options['payout_schedule'] ?? 'monthly', 'monthly'); ?>>
                            <?php _e('Mensile', 'wcfm-affiliate-pro'); ?>
                        </option>
                    </select>
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
        <?php
    }

    private function render_commission_settings(): void {
        settings_fields('wcfm_affiliate_commission');
        $options = get_option('wcfm_affiliate_commission', []);
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Tipo Commissione', 'wcfm-affiliate-pro'); ?></th>
                <td>
                    <select name="wcfm_affiliate_commission[type]" id="commission_type">
                        <option value="percentage" <?php selected($options['type'] ?? 'percentage', 'percentage'); ?>>
                            <?php _e('Percentuale', 'wcfm-affiliate-pro'); ?>
                        </option>
                        <option value="flat" <?php selected($options['type'] ?? 'percentage', 'flat'); ?>>
                            <?php _e('Importo Fisso', 'wcfm-affiliate-pro'); ?>
                        </option>
                        <option value="tiered" <?php selected($options['type'] ?? 'percentage', 'tiered'); ?>>
                            <?php _e('Progressivo (basato su livelli)', 'wcfm-affiliate-pro'); ?>
                        </option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Tasso Commissione (%)', 'wcfm-affiliate-pro'); ?></th>
                <td>
                    <input type="number" name="wcfm_affiliate_commission[rate]" min="0" max="100" step="0.01"
                           value="<?php echo esc_attr($options['rate'] ?? 10); ?>">%
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Importo Fisso', 'wcfm-affiliate-pro'); ?></th>
                <td>
                    <input type="number" name="wcfm_affiliate_commission[flat_amount]" min="0" step="0.01"
                           value="<?php echo esc_attr($options['flat_amount'] ?? 5); ?>">
                    <?php echo get_woocommerce_currency_symbol(); ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Commissione per Prodotto', 'wcfm-affiliate-pro'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="wcfm_affiliate_commission[per_product]" value="yes"
                            <?php checked($options['per_product'] ?? 'no', 'yes'); ?>>
                        <?php _e('Permetti commissioni personalizzate per prodotto', 'wcfm-affiliate-pro'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Commissione per Categoria', 'wcfm-affiliate-pro'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="wcfm_affiliate_commission[per_category]" value="yes"
                            <?php checked($options['per_category'] ?? 'no', 'yes'); ?>>
                        <?php _e('Permetti commissioni personalizzate per categoria', 'wcfm-affiliate-pro'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Commissione per Vendor', 'wcfm-affiliate-pro'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="wcfm_affiliate_commission[per_vendor]" value="yes"
                            <?php checked($options['per_vendor'] ?? 'no', 'yes'); ?>>
                        <?php _e('Permetti commissioni personalizzate per vendor', 'wcfm-affiliate-pro'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Commissioni Ricorrenti', 'wcfm-affiliate-pro'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="wcfm_affiliate_commission[recurring]" value="yes"
                            <?php checked($options['recurring'] ?? 'no', 'yes'); ?>>
                        <?php _e('Paga commissioni sui rinnovi abbonamento', 'wcfm-affiliate-pro'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Tasso Ricorrente (%)', 'wcfm-affiliate-pro'); ?></th>
                <td>
                    <input type="number" name="wcfm_affiliate_commission[recurring_rate]" min="0" max="100" step="0.01"
                           value="<?php echo esc_attr($options['recurring_rate'] ?? 5); ?>">%
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Durata Ricorrente (mesi)', 'wcfm-affiliate-pro'); ?></th>
                <td>
                    <input type="number" name="wcfm_affiliate_commission[recurring_duration]" min="1" max="120"
                           value="<?php echo esc_attr($options['recurring_duration'] ?? 12); ?>">
                    <p class="description"><?php _e('Per quanti mesi pagare commissioni ricorrenti (0 = illimitato)', 'wcfm-affiliate-pro'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Metodo Approvazione', 'wcfm-affiliate-pro'); ?></th>
                <td>
                    <select name="wcfm_affiliate_commission[approval_method]">
                        <option value="manual" <?php selected($options['approval_method'] ?? 'manual', 'manual'); ?>>
                            <?php _e('Manuale', 'wcfm-affiliate-pro'); ?>
                        </option>
                        <option value="auto" <?php selected($options['approval_method'] ?? 'manual', 'auto'); ?>>
                            <?php _e('Automatico', 'wcfm-affiliate-pro'); ?>
                        </option>
                        <option value="delay" <?php selected($options['approval_method'] ?? 'manual', 'delay'); ?>>
                            <?php _e('Automatico dopo X giorni', 'wcfm-affiliate-pro'); ?>
                        </option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Ritardo Approvazione (giorni)', 'wcfm-affiliate-pro'); ?></th>
                <td>
                    <input type="number" name="wcfm_affiliate_commission[approval_delay]" min="1" max="365"
                           value="<?php echo esc_attr($options['approval_delay'] ?? 30); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Escludi dal Calcolo', 'wcfm-affiliate-pro'); ?></th>
                <td>
                    <label style="display: block; margin-bottom: 5px;">
                        <input type="checkbox" name="wcfm_affiliate_commission[exclude_shipping]" value="yes"
                            <?php checked($options['exclude_shipping'] ?? 'yes', 'yes'); ?>>
                        <?php _e('Escludi spedizione', 'wcfm-affiliate-pro'); ?>
                    </label>
                    <label style="display: block; margin-bottom: 5px;">
                        <input type="checkbox" name="wcfm_affiliate_commission[exclude_tax]" value="yes"
                            <?php checked($options['exclude_tax'] ?? 'yes', 'yes'); ?>>
                        <?php _e('Escludi tasse', 'wcfm-affiliate-pro'); ?>
                    </label>
                    <label style="display: block;">
                        <input type="checkbox" name="wcfm_affiliate_commission[exclude_discounts]" value="yes"
                            <?php checked($options['exclude_discounts'] ?? 'yes', 'yes'); ?>>
                        <?php _e('Escludi sconti', 'wcfm-affiliate-pro'); ?>
                    </label>
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
        <?php
    }

    private function render_mlm_settings(): void {
        settings_fields('wcfm_affiliate_mlm');
        $options = get_option('wcfm_affiliate_mlm', []);
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Abilita Multi-Livello (MLM)', 'wcfm-affiliate-pro'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="wcfm_affiliate_mlm[enable]" value="yes" id="mlm_enable"
                            <?php checked($options['enable'] ?? 'no', 'yes'); ?>>
                        <?php _e('Attiva commissioni multi-livello', 'wcfm-affiliate-pro'); ?>
                    </label>
                    <p class="description"><?php _e('Gli affiliati guadagneranno commissioni anche sui referral dei loro sotto-affiliati.', 'wcfm-affiliate-pro'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Numero Livelli', 'wcfm-affiliate-pro'); ?></th>
                <td>
                    <input type="number" name="wcfm_affiliate_mlm[levels]" min="2" max="10"
                           value="<?php echo esc_attr($options['levels'] ?? 3); ?>">
                    <p class="description"><?php _e('Massimo numero di livelli nella struttura MLM.', 'wcfm-affiliate-pro'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Tassi per Livello (%)', 'wcfm-affiliate-pro'); ?></th>
                <td>
                    <?php
                    $levels = (int) ($options['levels'] ?? 3);
                    $rates = $options['level_rates'] ?? [10, 5, 2];
                    for ($i = 1; $i <= $levels; $i++):
                        ?>
                        <div style="margin-bottom: 10px;">
                            <label>
                                <?php printf(__('Livello %d:', 'wcfm-affiliate-pro'), $i); ?>
                                <input type="number" name="wcfm_affiliate_mlm[level_rates][]" min="0" max="100" step="0.01"
                                       value="<?php echo esc_attr($rates[$i - 1] ?? 0); ?>" style="width: 80px;">%
                            </label>
                        </div>
                    <?php endfor; ?>
                    <p class="description"><?php _e('Percentuale della commissione base per ogni livello.', 'wcfm-affiliate-pro'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Bonus Override', 'wcfm-affiliate-pro'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="wcfm_affiliate_mlm[override_bonus]" value="yes"
                            <?php checked($options['override_bonus'] ?? 'no', 'yes'); ?>>
                        <?php _e('Abilita bonus override per i top performer', 'wcfm-affiliate-pro'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Tasso Override (%)', 'wcfm-affiliate-pro'); ?></th>
                <td>
                    <input type="number" name="wcfm_affiliate_mlm[override_rate]" min="0" max="100" step="0.01"
                           value="<?php echo esc_attr($options['override_rate'] ?? 2); ?>">%
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
        <?php
    }

    private function render_notification_settings(): void {
        settings_fields('wcfm_affiliate_notifications');
        $options = get_option('wcfm_affiliate_notifications', []);
        ?>
        <h3><?php _e('Notifiche Admin', 'wcfm-affiliate-pro'); ?></h3>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Nuovo Affiliato', 'wcfm-affiliate-pro'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="wcfm_affiliate_notifications[admin_new_affiliate]" value="yes"
                            <?php checked($options['admin_new_affiliate'] ?? 'yes', 'yes'); ?>>
                        <?php _e('Notifica quando un nuovo affiliato si registra', 'wcfm-affiliate-pro'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Nuovo Referral', 'wcfm-affiliate-pro'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="wcfm_affiliate_notifications[admin_new_referral]" value="yes"
                            <?php checked($options['admin_new_referral'] ?? 'yes', 'yes'); ?>>
                        <?php _e('Notifica per ogni nuovo referral', 'wcfm-affiliate-pro'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Richiesta Pagamento', 'wcfm-affiliate-pro'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="wcfm_affiliate_notifications[admin_payout_request]" value="yes"
                            <?php checked($options['admin_payout_request'] ?? 'yes', 'yes'); ?>>
                        <?php _e('Notifica per richieste di pagamento', 'wcfm-affiliate-pro'); ?>
                    </label>
                </td>
            </tr>
        </table>

        <h3><?php _e('Notifiche Affiliato', 'wcfm-affiliate-pro'); ?></h3>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Approvazione', 'wcfm-affiliate-pro'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="wcfm_affiliate_notifications[affiliate_approved]" value="yes"
                            <?php checked($options['affiliate_approved'] ?? 'yes', 'yes'); ?>>
                        <?php _e('Notifica quando l\'affiliato viene approvato', 'wcfm-affiliate-pro'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Rifiuto', 'wcfm-affiliate-pro'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="wcfm_affiliate_notifications[affiliate_rejected]" value="yes"
                            <?php checked($options['affiliate_rejected'] ?? 'yes', 'yes'); ?>>
                        <?php _e('Notifica quando l\'affiliato viene rifiutato', 'wcfm-affiliate-pro'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Nuovo Referral', 'wcfm-affiliate-pro'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="wcfm_affiliate_notifications[affiliate_new_referral]" value="yes"
                            <?php checked($options['affiliate_new_referral'] ?? 'yes', 'yes'); ?>>
                        <?php _e('Notifica l\'affiliato per ogni nuovo referral', 'wcfm-affiliate-pro'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Commissione Approvata', 'wcfm-affiliate-pro'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="wcfm_affiliate_notifications[affiliate_commission_approved]" value="yes"
                            <?php checked($options['affiliate_commission_approved'] ?? 'yes', 'yes'); ?>>
                        <?php _e('Notifica quando una commissione viene approvata', 'wcfm-affiliate-pro'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Pagamento Inviato', 'wcfm-affiliate-pro'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="wcfm_affiliate_notifications[affiliate_payout_sent]" value="yes"
                            <?php checked($options['affiliate_payout_sent'] ?? 'yes', 'yes'); ?>>
                        <?php _e('Notifica quando il pagamento viene inviato', 'wcfm-affiliate-pro'); ?>
                    </label>
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
        <?php
    }

    private function render_payment_settings(): void {
        ?>
        <h3><?php _e('PayPal', 'wcfm-affiliate-pro'); ?></h3>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Client ID', 'wcfm-affiliate-pro'); ?></th>
                <td>
                    <input type="text" name="wcfm_affiliate_paypal_client_id" class="regular-text"
                           value="<?php echo esc_attr(get_option('wcfm_affiliate_paypal_client_id', '')); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Secret Key', 'wcfm-affiliate-pro'); ?></th>
                <td>
                    <input type="password" name="wcfm_affiliate_paypal_secret" class="regular-text"
                           value="<?php echo esc_attr(get_option('wcfm_affiliate_paypal_secret', '')); ?>">
                    <p class="description"><?php _e('Necessario per pagamenti automatici via PayPal Payouts API.', 'wcfm-affiliate-pro'); ?></p>
                </td>
            </tr>
        </table>

        <h3><?php _e('Stripe', 'wcfm-affiliate-pro'); ?></h3>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Secret Key', 'wcfm-affiliate-pro'); ?></th>
                <td>
                    <input type="password" name="wcfm_affiliate_stripe_secret_key" class="regular-text"
                           value="<?php echo esc_attr(get_option('wcfm_affiliate_stripe_secret_key', '')); ?>">
                    <p class="description"><?php _e('Necessario per pagamenti automatici via Stripe Connect.', 'wcfm-affiliate-pro'); ?></p>
                </td>
            </tr>
        </table>
        <?php
        settings_fields('wcfm_affiliate_payments');
        submit_button();
    }

    private function render_design_settings(): void {
        settings_fields('wcfm_affiliate_design');
        $options = get_option('wcfm_affiliate_design', []);
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Colore Primario', 'wcfm-affiliate-pro'); ?></th>
                <td>
                    <input type="color" name="wcfm_affiliate_design[primary_color]"
                           value="<?php echo esc_attr($options['primary_color'] ?? '#00897b'); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Colore Secondario', 'wcfm-affiliate-pro'); ?></th>
                <td>
                    <input type="color" name="wcfm_affiliate_design[secondary_color]"
                           value="<?php echo esc_attr($options['secondary_color'] ?? '#26a69a'); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Stile Dashboard', 'wcfm-affiliate-pro'); ?></th>
                <td>
                    <select name="wcfm_affiliate_design[dashboard_style]">
                        <option value="modern" <?php selected($options['dashboard_style'] ?? 'modern', 'modern'); ?>>
                            <?php _e('Moderno', 'wcfm-affiliate-pro'); ?>
                        </option>
                        <option value="classic" <?php selected($options['dashboard_style'] ?? 'modern', 'classic'); ?>>
                            <?php _e('Classico', 'wcfm-affiliate-pro'); ?>
                        </option>
                        <option value="minimal" <?php selected($options['dashboard_style'] ?? 'modern', 'minimal'); ?>>
                            <?php _e('Minimale', 'wcfm-affiliate-pro'); ?>
                        </option>
                    </select>
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
        <?php
    }
}
