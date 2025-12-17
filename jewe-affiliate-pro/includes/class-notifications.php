<?php
/**
 * Notifications Handler
 *
 * @package JEWE_Affiliate_Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class JEWE_Affiliate_Notifications {

    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Send notification to affiliate
     */
    public static function send($affiliate_id, $type, $data = []) {
        $notification = self::build_notification($type, $data);

        if (!$notification) {
            return false;
        }

        // Save to database
        self::save_notification($affiliate_id, $type, $notification['title'], $notification['message']);

        // Send email if enabled
        if (get_option('jewe_affiliate_email_notifications', 'yes') === 'yes') {
            self::send_email($affiliate_id, $notification);
        }

        // Trigger webhook if configured
        self::trigger_webhook($affiliate_id, $type, $data);

        do_action('jewe_notification_sent', $affiliate_id, $type, $notification);

        return true;
    }

    /**
     * Build notification content
     */
    private static function build_notification($type, $data) {
        $notifications = [
            'welcome' => [
                'title' => __('Benvenuto nel Programma Affiliati!', 'jewe-affiliate-pro'),
                'message' => $data['status'] === 'active'
                    ? sprintf(__('Il tuo account è attivo! Il tuo codice affiliato è: %s', 'jewe-affiliate-pro'), $data['code'])
                    : __('La tua richiesta è in attesa di approvazione.', 'jewe-affiliate-pro'),
            ],
            'status_changed' => [
                'title' => __('Stato Account Aggiornato', 'jewe-affiliate-pro'),
                'message' => sprintf(__('Il tuo account è stato aggiornato a: %s', 'jewe-affiliate-pro'), $data['status']),
            ],
            'new_commission' => [
                'title' => __('Nuova Commissione!', 'jewe-affiliate-pro'),
                'message' => sprintf(
                    __('Hai guadagnato €%s dalla vendita #%d', 'jewe-affiliate-pro'),
                    number_format($data['amount'], 2),
                    $data['order_id']
                ),
            ],
            'mlm_commission' => [
                'title' => __('Commissione Team', 'jewe-affiliate-pro'),
                'message' => sprintf(
                    __('Hai guadagnato €%s dal tuo team (Livello %d)', 'jewe-affiliate-pro'),
                    number_format($data['amount'], 2),
                    $data['level']
                ),
            ],
            'commission_refunded' => [
                'title' => __('Commissione Annullata', 'jewe-affiliate-pro'),
                'message' => sprintf(
                    __('La commissione di €%s per l\'ordine #%d è stata annullata per rimborso.', 'jewe-affiliate-pro'),
                    number_format($data['amount'], 2),
                    $data['order_id']
                ),
            ],
            'tier_upgrade' => [
                'title' => __('Livello Aumentato!', 'jewe-affiliate-pro'),
                'message' => sprintf(
                    __('Complimenti! Sei salito al livello %s!', 'jewe-affiliate-pro'),
                    $data['tier_name']
                ),
            ],
            'badge_earned' => [
                'title' => __('Nuovo Badge!', 'jewe-affiliate-pro'),
                'message' => sprintf(
                    __('Hai guadagnato il badge "%s": %s', 'jewe-affiliate-pro'),
                    $data['badge_name'],
                    $data['badge_description']
                ),
            ],
            'payout_processed' => [
                'title' => __('Pagamento Elaborato', 'jewe-affiliate-pro'),
                'message' => sprintf(
                    __('Il tuo pagamento di €%s è stato elaborato.', 'jewe-affiliate-pro'),
                    number_format($data['amount'], 2)
                ),
            ],
            'payout_rejected' => [
                'title' => __('Richiesta Pagamento Rifiutata', 'jewe-affiliate-pro'),
                'message' => sprintf(
                    __('La tua richiesta di pagamento è stata rifiutata. Motivo: %s', 'jewe-affiliate-pro'),
                    $data['reason'] ?? __('Non specificato', 'jewe-affiliate-pro')
                ),
            ],
            'new_referral' => [
                'title' => __('Nuovo Affiliato nel Team!', 'jewe-affiliate-pro'),
                'message' => __('Un nuovo affiliato si è iscritto usando il tuo link!', 'jewe-affiliate-pro'),
            ],
        ];

        return $notifications[$type] ?? null;
    }

    /**
     * Save notification to database
     */
    private static function save_notification($affiliate_id, $type, $title, $message) {
        global $wpdb;
        $table = $wpdb->prefix . 'jewe_notifications';

        return $wpdb->insert($table, [
            'affiliate_id' => $affiliate_id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
        ]);
    }

    /**
     * Send email notification
     */
    private static function send_email($affiliate_id, $notification) {
        $affiliate = JEWE_Affiliate::get($affiliate_id);
        if (!$affiliate) {
            return false;
        }

        $user = get_userdata($affiliate->user_id);
        if (!$user || !$user->user_email) {
            return false;
        }

        $subject = '[' . get_bloginfo('name') . '] ' . $notification['title'];

        $message = self::get_email_template($notification);

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>',
        ];

        return wp_mail($user->user_email, $subject, $message, $headers);
    }

    /**
     * Get email template
     */
    private static function get_email_template($notification) {
        $template = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #4F46E5; color: white; padding: 20px; text-align: center; }
                .content { padding: 30px; background: #f9f9f9; }
                .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
                .button { display: inline-block; padding: 12px 24px; background: #4F46E5; color: white; text-decoration: none; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>%s</h1>
                </div>
                <div class="content">
                    <p>%s</p>
                    <p style="text-align: center; margin-top: 30px;">
                        <a href="%s" class="button">Vai alla Dashboard</a>
                    </p>
                </div>
                <div class="footer">
                    <p>%s - Programma Affiliati</p>
                </div>
            </div>
        </body>
        </html>';

        return sprintf(
            $template,
            esc_html($notification['title']),
            esc_html($notification['message']),
            esc_url(home_url('/affiliate-dashboard/')),
            esc_html(get_bloginfo('name'))
        );
    }

    /**
     * Trigger webhook
     */
    private static function trigger_webhook($affiliate_id, $type, $data) {
        $webhook_url = get_option('jewe_affiliate_webhook_url', '');

        if (empty($webhook_url)) {
            return;
        }

        $payload = [
            'event' => 'jewe_affiliate_' . $type,
            'affiliate_id' => $affiliate_id,
            'timestamp' => current_time('timestamp'),
            'data' => $data,
        ];

        wp_remote_post($webhook_url, [
            'body' => json_encode($payload),
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'timeout' => 10,
            'blocking' => false,
        ]);
    }

    /**
     * Get notifications for affiliate
     */
    public static function get_notifications($affiliate_id, $limit = 20, $unread_only = false) {
        global $wpdb;
        $table = $wpdb->prefix . 'jewe_notifications';

        $where = $wpdb->prepare("WHERE affiliate_id = %d", $affiliate_id);
        if ($unread_only) {
            $where .= " AND is_read = 0";
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table $where ORDER BY created_at DESC LIMIT %d",
            $limit
        ));
    }

    /**
     * Get unread count
     */
    public static function get_unread_count($affiliate_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'jewe_notifications';

        return intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE affiliate_id = %d AND is_read = 0",
            $affiliate_id
        )));
    }

    /**
     * Mark notification as read
     */
    public static function mark_as_read($notification_id, $affiliate_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'jewe_notifications';

        return $wpdb->update(
            $table,
            ['is_read' => 1],
            ['id' => $notification_id, 'affiliate_id' => $affiliate_id]
        );
    }

    /**
     * Mark all as read
     */
    public static function mark_all_as_read($affiliate_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'jewe_notifications';

        return $wpdb->update(
            $table,
            ['is_read' => 1],
            ['affiliate_id' => $affiliate_id, 'is_read' => 0]
        );
    }
}
