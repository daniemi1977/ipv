<?php
/**
 * Email Notifications
 *
 * Gestisce le notifiche email del sistema affiliate.
 *
 * @package WCFM_Affiliate_Pro
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class WCFM_Affiliate_Emails
 */
class WCFM_Affiliate_Emails {

    /**
     * Email settings
     */
    private array $settings;

    /**
     * Constructor
     */
    public function __construct() {
        $this->settings = get_option('wcfm_affiliate_notifications', []);

        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks(): void {
        // Affiliate events
        add_action('wcfm_affiliate_new_registration', [$this, 'send_new_registration_admin'], 10, 2);
        add_action('wcfm_affiliate_approved', [$this, 'send_approved_notification'], 10, 2);
        add_action('wcfm_affiliate_rejected', [$this, 'send_rejected_notification'], 10, 3);

        // Referral events
        add_action('wcfm_affiliate_new_referral', [$this, 'send_new_referral_notification'], 10, 3);

        // Commission events
        add_action('wcfm_affiliate_commission_approved', [$this, 'send_commission_approved'], 10, 1);

        // Payout events
        add_action('wcfm_affiliate_payout_request', [$this, 'send_payout_request_admin'], 10, 2);
        add_action('wcfm_affiliate_payout_completed', [$this, 'send_payout_completed'], 10, 2);
    }

    /**
     * Send new registration email to admin
     */
    public function send_new_registration_admin(int $affiliate_id, int $user_id): void {
        if (($this->settings['admin_new_affiliate'] ?? 'yes') !== 'yes') {
            return;
        }

        $user = get_userdata($user_id);
        $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate($affiliate_id);

        if (!$user || !$affiliate) {
            return;
        }

        $admin_email = get_option('admin_email');

        $subject = sprintf(
            __('[%s] Nuova richiesta affiliazione: %s', 'wcfm-affiliate-pro'),
            get_bloginfo('name'),
            $user->display_name
        );

        $message = $this->get_email_template('admin-new-affiliate', [
            'user_name' => $user->display_name,
            'user_email' => $user->user_email,
            'affiliate_code' => $affiliate->affiliate_code,
            'website_url' => $affiliate->website_url,
            'promotional_methods' => $affiliate->promotional_methods,
            'admin_url' => admin_url('admin.php?page=wcfm-affiliate-affiliates&action=view&id=' . $affiliate_id),
        ]);

        $this->send_email($admin_email, $subject, $message);
    }

    /**
     * Send approved notification
     */
    public function send_approved_notification(int $affiliate_id, int $user_id): void {
        if (($this->settings['affiliate_approved'] ?? 'yes') !== 'yes') {
            return;
        }

        $user = get_userdata($user_id);
        $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate($affiliate_id);

        if (!$user || !$affiliate) {
            return;
        }

        $subject = sprintf(
            __('[%s] La tua richiesta di affiliazione è stata approvata!', 'wcfm-affiliate-pro'),
            get_bloginfo('name')
        );

        $referral_link = wcfm_affiliate_pro()->referrals->generate_link($affiliate_id);

        $message = $this->get_email_template('affiliate-approved', [
            'user_name' => $user->display_name,
            'affiliate_code' => $affiliate->affiliate_code,
            'referral_link' => $referral_link,
            'dashboard_url' => wcfm_affiliate_pro()->affiliates->get_dashboard_url(),
        ]);

        $this->send_email($user->user_email, $subject, $message);
    }

    /**
     * Send rejected notification
     */
    public function send_rejected_notification(int $affiliate_id, int $user_id, string $reason = ''): void {
        if (($this->settings['affiliate_rejected'] ?? 'yes') !== 'yes') {
            return;
        }

        $user = get_userdata($user_id);

        if (!$user) {
            return;
        }

        $subject = sprintf(
            __('[%s] Aggiornamento sulla tua richiesta di affiliazione', 'wcfm-affiliate-pro'),
            get_bloginfo('name')
        );

        $message = $this->get_email_template('affiliate-rejected', [
            'user_name' => $user->display_name,
            'reason' => $reason,
            'support_email' => get_option('admin_email'),
        ]);

        $this->send_email($user->user_email, $subject, $message);
    }

    /**
     * Send new referral notification
     */
    public function send_new_referral_notification(int $referral_id, int $affiliate_id, int $order_id): void {
        global $wpdb;

        // Check both admin and affiliate notifications
        $send_admin = ($this->settings['admin_new_referral'] ?? 'yes') === 'yes';
        $send_affiliate = ($this->settings['affiliate_new_referral'] ?? 'yes') === 'yes';

        if (!$send_admin && !$send_affiliate) {
            return;
        }

        $referral = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . WCFM_Affiliate_DB::$table_referrals . " WHERE id = %d",
            $referral_id
        ));

        $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate($affiliate_id);
        $user = get_userdata($affiliate->user_id);
        $order = wc_get_order($order_id);

        if (!$referral || !$affiliate || !$user || !$order) {
            return;
        }

        // Send to affiliate
        if ($send_affiliate) {
            $subject = sprintf(
                __('[%s] Nuovo referral! Hai guadagnato %s', 'wcfm-affiliate-pro'),
                get_bloginfo('name'),
                wc_price($referral->amount)
            );

            $message = $this->get_email_template('affiliate-new-referral', [
                'user_name' => $user->display_name,
                'order_id' => $order_id,
                'order_total' => wc_price($order->get_total()),
                'commission' => wc_price($referral->amount),
                'status' => $referral->status,
                'dashboard_url' => wcfm_affiliate_pro()->affiliates->get_dashboard_url(),
            ]);

            $this->send_email($user->user_email, $subject, $message);
        }

        // Send to admin
        if ($send_admin) {
            $admin_email = get_option('admin_email');

            $subject = sprintf(
                __('[%s] Nuovo referral da %s', 'wcfm-affiliate-pro'),
                get_bloginfo('name'),
                $user->display_name
            );

            $message = $this->get_email_template('admin-new-referral', [
                'affiliate_name' => $user->display_name,
                'affiliate_code' => $affiliate->affiliate_code,
                'order_id' => $order_id,
                'order_total' => wc_price($order->get_total()),
                'commission' => wc_price($referral->amount),
                'admin_url' => admin_url('admin.php?page=wcfm-affiliate-referrals'),
            ]);

            $this->send_email($admin_email, $subject, $message);
        }
    }

    /**
     * Send commission approved notification
     */
    public function send_commission_approved(int $commission_id): void {
        if (($this->settings['affiliate_commission_approved'] ?? 'yes') !== 'yes') {
            return;
        }

        $commission = wcfm_affiliate_pro()->commissions->get_commission($commission_id);

        if (!$commission) {
            return;
        }

        $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate($commission->affiliate_id);
        $user = get_userdata($affiliate->user_id);

        if (!$affiliate || !$user) {
            return;
        }

        $subject = sprintf(
            __('[%s] Commissione approvata: %s', 'wcfm-affiliate-pro'),
            get_bloginfo('name'),
            wc_price($commission->commission_amount)
        );

        $message = $this->get_email_template('commission-approved', [
            'user_name' => $user->display_name,
            'commission_amount' => wc_price($commission->commission_amount),
            'order_id' => $commission->order_id,
            'current_balance' => wc_price($affiliate->earnings_balance),
            'dashboard_url' => wcfm_affiliate_pro()->affiliates->get_dashboard_url(),
        ]);

        $this->send_email($user->user_email, $subject, $message);
    }

    /**
     * Send payout request to admin
     */
    public function send_payout_request_admin(int $payout_id, int $affiliate_id): void {
        if (($this->settings['admin_payout_request'] ?? 'yes') !== 'yes') {
            return;
        }

        $payout = wcfm_affiliate_pro()->payouts->get_payout($payout_id);
        $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate($affiliate_id);
        $user = get_userdata($affiliate->user_id);

        if (!$payout || !$affiliate || !$user) {
            return;
        }

        $admin_email = get_option('admin_email');

        $subject = sprintf(
            __('[%s] Nuova richiesta di pagamento: %s', 'wcfm-affiliate-pro'),
            get_bloginfo('name'),
            wc_price($payout->amount)
        );

        $message = $this->get_email_template('admin-payout-request', [
            'affiliate_name' => $user->display_name,
            'affiliate_email' => $user->user_email,
            'amount' => wc_price($payout->amount),
            'payment_method' => ucfirst(str_replace('_', ' ', $payout->payment_method)),
            'payment_email' => $payout->payment_email,
            'admin_url' => admin_url('admin.php?page=wcfm-affiliate-payouts&action=process&id=' . $payout_id),
        ]);

        $this->send_email($admin_email, $subject, $message);
    }

    /**
     * Send payout completed notification
     */
    public function send_payout_completed(int $payout_id, int $affiliate_id): void {
        if (($this->settings['affiliate_payout_sent'] ?? 'yes') !== 'yes') {
            return;
        }

        $payout = wcfm_affiliate_pro()->payouts->get_payout($payout_id);
        $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate($affiliate_id);
        $user = get_userdata($affiliate->user_id);

        if (!$payout || !$affiliate || !$user) {
            return;
        }

        $subject = sprintf(
            __('[%s] Pagamento inviato: %s', 'wcfm-affiliate-pro'),
            get_bloginfo('name'),
            wc_price($payout->amount)
        );

        $message = $this->get_email_template('payout-completed', [
            'user_name' => $user->display_name,
            'amount' => wc_price($payout->amount),
            'payment_method' => ucfirst(str_replace('_', ' ', $payout->payment_method)),
            'transaction_id' => $payout->transaction_id,
            'dashboard_url' => wcfm_affiliate_pro()->affiliates->get_dashboard_url(),
        ]);

        $this->send_email($user->user_email, $subject, $message);
    }

    /**
     * Send daily summary
     */
    public function send_daily_summary(): void {
        $admin_email = get_option('admin_email');

        // Get yesterday's stats
        global $wpdb;

        $yesterday = date('Y-m-d', strtotime('-1 day'));

        $stats = [
            'new_affiliates' => (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM " . WCFM_Affiliate_DB::$table_affiliates . " WHERE DATE(date_created) = %s",
                $yesterday
            )),
            'new_referrals' => (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM " . WCFM_Affiliate_DB::$table_referrals . " WHERE DATE(date_created) = %s",
                $yesterday
            )),
            'total_commissions' => (float) $wpdb->get_var($wpdb->prepare(
                "SELECT SUM(commission_amount) FROM " . WCFM_Affiliate_DB::$table_commissions . " WHERE DATE(date_created) = %s",
                $yesterday
            )),
            'visits' => (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM " . WCFM_Affiliate_DB::$table_visits . " WHERE DATE(date_created) = %s",
                $yesterday
            )),
        ];

        // Only send if there's activity
        if ($stats['new_referrals'] > 0 || $stats['new_affiliates'] > 0) {
            $subject = sprintf(
                __('[%s] Riepilogo Affiliati - %s', 'wcfm-affiliate-pro'),
                get_bloginfo('name'),
                date_i18n(get_option('date_format'), strtotime($yesterday))
            );

            $message = $this->get_email_template('daily-summary', [
                'date' => date_i18n(get_option('date_format'), strtotime($yesterday)),
                'new_affiliates' => $stats['new_affiliates'],
                'new_referrals' => $stats['new_referrals'],
                'total_commissions' => wc_price($stats['total_commissions']),
                'visits' => $stats['visits'],
                'admin_url' => admin_url('admin.php?page=wcfm-affiliate'),
            ]);

            $this->send_email($admin_email, $subject, $message);
        }
    }

    /**
     * Send email
     */
    private function send_email(string $to, string $subject, string $message): bool {
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>',
        ];

        $message = $this->wrap_email_template($message);

        return wp_mail($to, $subject, $message, $headers);
    }

    /**
     * Get email template
     */
    private function get_email_template(string $template, array $args = []): string {
        $templates = [
            'admin-new-affiliate' => '
                <h2>' . __('Nuova Richiesta di Affiliazione', 'wcfm-affiliate-pro') . '</h2>
                <p>' . __('Un nuovo utente ha richiesto di diventare affiliato:', 'wcfm-affiliate-pro') . '</p>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd;"><strong>' . __('Nome', 'wcfm-affiliate-pro') . '</strong></td>
                        <td style="padding: 10px; border: 1px solid #ddd;">{user_name}</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd;"><strong>' . __('Email', 'wcfm-affiliate-pro') . '</strong></td>
                        <td style="padding: 10px; border: 1px solid #ddd;">{user_email}</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd;"><strong>' . __('Codice', 'wcfm-affiliate-pro') . '</strong></td>
                        <td style="padding: 10px; border: 1px solid #ddd;">{affiliate_code}</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd;"><strong>' . __('Sito Web', 'wcfm-affiliate-pro') . '</strong></td>
                        <td style="padding: 10px; border: 1px solid #ddd;">{website_url}</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd;"><strong>' . __('Metodi Promozionali', 'wcfm-affiliate-pro') . '</strong></td>
                        <td style="padding: 10px; border: 1px solid #ddd;">{promotional_methods}</td>
                    </tr>
                </table>
                <p style="margin-top: 20px;">
                    <a href="{admin_url}" style="background: #00897b; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 4px;">
                        ' . __('Visualizza Richiesta', 'wcfm-affiliate-pro') . '
                    </a>
                </p>
            ',

            'affiliate-approved' => '
                <h2>' . __('Congratulazioni! Sei stato approvato!', 'wcfm-affiliate-pro') . '</h2>
                <p>' . __('Ciao {user_name},', 'wcfm-affiliate-pro') . '</p>
                <p>' . __('La tua richiesta di affiliazione è stata approvata! Puoi iniziare subito a guadagnare commissioni.', 'wcfm-affiliate-pro') . '</p>
                <div style="background: #f5f5f5; padding: 20px; border-radius: 4px; margin: 20px 0;">
                    <p><strong>' . __('Il tuo codice affiliato:', 'wcfm-affiliate-pro') . '</strong> {affiliate_code}</p>
                    <p><strong>' . __('Il tuo link referral:', 'wcfm-affiliate-pro') . '</strong><br>
                    <a href="{referral_link}">{referral_link}</a></p>
                </div>
                <p>
                    <a href="{dashboard_url}" style="background: #00897b; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 4px;">
                        ' . __('Vai alla Dashboard', 'wcfm-affiliate-pro') . '
                    </a>
                </p>
            ',

            'affiliate-rejected' => '
                <h2>' . __('Aggiornamento sulla tua richiesta', 'wcfm-affiliate-pro') . '</h2>
                <p>' . __('Ciao {user_name},', 'wcfm-affiliate-pro') . '</p>
                <p>' . __('Purtroppo al momento non possiamo approvare la tua richiesta di affiliazione.', 'wcfm-affiliate-pro') . '</p>
                <p><strong>' . __('Motivo:', 'wcfm-affiliate-pro') . '</strong> {reason}</p>
                <p>' . __('Se hai domande, contattaci a:', 'wcfm-affiliate-pro') . ' {support_email}</p>
            ',

            'affiliate-new-referral' => '
                <h2>' . __('Nuovo Referral!', 'wcfm-affiliate-pro') . '</h2>
                <p>' . __('Ciao {user_name},', 'wcfm-affiliate-pro') . '</p>
                <p>' . __('Ottimo lavoro! Hai generato un nuovo referral:', 'wcfm-affiliate-pro') . '</p>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd;"><strong>' . __('Ordine', 'wcfm-affiliate-pro') . '</strong></td>
                        <td style="padding: 10px; border: 1px solid #ddd;">#{order_id}</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd;"><strong>' . __('Totale Ordine', 'wcfm-affiliate-pro') . '</strong></td>
                        <td style="padding: 10px; border: 1px solid #ddd;">{order_total}</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd;"><strong>' . __('Commissione', 'wcfm-affiliate-pro') . '</strong></td>
                        <td style="padding: 10px; border: 1px solid #ddd;">{commission}</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd;"><strong>' . __('Stato', 'wcfm-affiliate-pro') . '</strong></td>
                        <td style="padding: 10px; border: 1px solid #ddd;">{status}</td>
                    </tr>
                </table>
                <p style="margin-top: 20px;">
                    <a href="{dashboard_url}" style="background: #00897b; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 4px;">
                        ' . __('Vai alla Dashboard', 'wcfm-affiliate-pro') . '
                    </a>
                </p>
            ',

            'admin-new-referral' => '
                <h2>' . __('Nuovo Referral Affiliato', 'wcfm-affiliate-pro') . '</h2>
                <p><strong>{affiliate_name}</strong> ({affiliate_code}) ' . __('ha generato un nuovo referral:', 'wcfm-affiliate-pro') . '</p>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd;"><strong>' . __('Ordine', 'wcfm-affiliate-pro') . '</strong></td>
                        <td style="padding: 10px; border: 1px solid #ddd;">#{order_id}</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd;"><strong>' . __('Totale Ordine', 'wcfm-affiliate-pro') . '</strong></td>
                        <td style="padding: 10px; border: 1px solid #ddd;">{order_total}</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd;"><strong>' . __('Commissione', 'wcfm-affiliate-pro') . '</strong></td>
                        <td style="padding: 10px; border: 1px solid #ddd;">{commission}</td>
                    </tr>
                </table>
                <p style="margin-top: 20px;">
                    <a href="{admin_url}" style="background: #00897b; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 4px;">
                        ' . __('Visualizza Referral', 'wcfm-affiliate-pro') . '
                    </a>
                </p>
            ',

            'commission-approved' => '
                <h2>' . __('Commissione Approvata!', 'wcfm-affiliate-pro') . '</h2>
                <p>' . __('Ciao {user_name},', 'wcfm-affiliate-pro') . '</p>
                <p>' . __('Una tua commissione è stata approvata:', 'wcfm-affiliate-pro') . '</p>
                <div style="background: #f5f5f5; padding: 20px; border-radius: 4px; margin: 20px 0;">
                    <p><strong>' . __('Importo:', 'wcfm-affiliate-pro') . '</strong> {commission_amount}</p>
                    <p><strong>' . __('Ordine:', 'wcfm-affiliate-pro') . '</strong> #{order_id}</p>
                    <p><strong>' . __('Saldo attuale:', 'wcfm-affiliate-pro') . '</strong> {current_balance}</p>
                </div>
                <p>
                    <a href="{dashboard_url}" style="background: #00897b; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 4px;">
                        ' . __('Vai alla Dashboard', 'wcfm-affiliate-pro') . '
                    </a>
                </p>
            ',

            'admin-payout-request' => '
                <h2>' . __('Nuova Richiesta di Pagamento', 'wcfm-affiliate-pro') . '</h2>
                <p><strong>{affiliate_name}</strong> ' . __('ha richiesto un pagamento:', 'wcfm-affiliate-pro') . '</p>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd;"><strong>' . __('Importo', 'wcfm-affiliate-pro') . '</strong></td>
                        <td style="padding: 10px; border: 1px solid #ddd;">{amount}</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd;"><strong>' . __('Metodo', 'wcfm-affiliate-pro') . '</strong></td>
                        <td style="padding: 10px; border: 1px solid #ddd;">{payment_method}</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd;"><strong>' . __('Email Pagamento', 'wcfm-affiliate-pro') . '</strong></td>
                        <td style="padding: 10px; border: 1px solid #ddd;">{payment_email}</td>
                    </tr>
                </table>
                <p style="margin-top: 20px;">
                    <a href="{admin_url}" style="background: #00897b; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 4px;">
                        ' . __('Elabora Pagamento', 'wcfm-affiliate-pro') . '
                    </a>
                </p>
            ',

            'payout-completed' => '
                <h2>' . __('Pagamento Inviato!', 'wcfm-affiliate-pro') . '</h2>
                <p>' . __('Ciao {user_name},', 'wcfm-affiliate-pro') . '</p>
                <p>' . __('Il tuo pagamento è stato elaborato:', 'wcfm-affiliate-pro') . '</p>
                <div style="background: #f5f5f5; padding: 20px; border-radius: 4px; margin: 20px 0;">
                    <p><strong>' . __('Importo:', 'wcfm-affiliate-pro') . '</strong> {amount}</p>
                    <p><strong>' . __('Metodo:', 'wcfm-affiliate-pro') . '</strong> {payment_method}</p>
                    <p><strong>' . __('ID Transazione:', 'wcfm-affiliate-pro') . '</strong> {transaction_id}</p>
                </div>
                <p>
                    <a href="{dashboard_url}" style="background: #00897b; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 4px;">
                        ' . __('Vai alla Dashboard', 'wcfm-affiliate-pro') . '
                    </a>
                </p>
            ',

            'daily-summary' => '
                <h2>' . __('Riepilogo Giornaliero Affiliati', 'wcfm-affiliate-pro') . '</h2>
                <p>' . __('Data:', 'wcfm-affiliate-pro') . ' {date}</p>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd;"><strong>' . __('Nuovi Affiliati', 'wcfm-affiliate-pro') . '</strong></td>
                        <td style="padding: 10px; border: 1px solid #ddd;">{new_affiliates}</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd;"><strong>' . __('Nuovi Referral', 'wcfm-affiliate-pro') . '</strong></td>
                        <td style="padding: 10px; border: 1px solid #ddd;">{new_referrals}</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd;"><strong>' . __('Commissioni Totali', 'wcfm-affiliate-pro') . '</strong></td>
                        <td style="padding: 10px; border: 1px solid #ddd;">{total_commissions}</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd;"><strong>' . __('Visite', 'wcfm-affiliate-pro') . '</strong></td>
                        <td style="padding: 10px; border: 1px solid #ddd;">{visits}</td>
                    </tr>
                </table>
                <p style="margin-top: 20px;">
                    <a href="{admin_url}" style="background: #00897b; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 4px;">
                        ' . __('Visualizza Dashboard', 'wcfm-affiliate-pro') . '
                    </a>
                </p>
            ',
        ];

        $template_content = $templates[$template] ?? '';

        // Replace placeholders
        foreach ($args as $key => $value) {
            $template_content = str_replace('{' . $key . '}', $value, $template_content);
        }

        return $template_content;
    }

    /**
     * Wrap email template
     */
    private function wrap_email_template(string $content): string {
        $design = get_option('wcfm_affiliate_design', []);
        $primary_color = $design['primary_color'] ?? '#00897b';

        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
        </head>
        <body style="margin: 0; padding: 0; background-color: #f4f4f4; font-family: Arial, sans-serif;">
            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4; padding: 20px;">
                <tr>
                    <td align="center">
                        <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden;">
                            <!-- Header -->
                            <tr>
                                <td style="background-color: ' . esc_attr($primary_color) . '; padding: 30px; text-align: center;">
                                    <h1 style="color: #ffffff; margin: 0; font-size: 24px;">' . get_bloginfo('name') . '</h1>
                                </td>
                            </tr>
                            <!-- Content -->
                            <tr>
                                <td style="padding: 30px;">
                                    ' . $content . '
                                </td>
                            </tr>
                            <!-- Footer -->
                            <tr>
                                <td style="background-color: #f9f9f9; padding: 20px; text-align: center; font-size: 12px; color: #666;">
                                    <p>' . sprintf(__('&copy; %s %s - Programma Affiliati', 'wcfm-affiliate-pro'), date('Y'), get_bloginfo('name')) . '</p>
                                    <p>' . get_bloginfo('url') . '</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>';
    }
}
