<?php
/**
 * Settings Handler
 *
 * @package JEWE_Affiliate_Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class JEWE_Affiliate_Settings {

    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
            self::$instance->init_hooks();
        }
        return self::$instance;
    }

    private function init_hooks() {
        add_action('admin_init', [$this, 'register_settings']);
    }

    /**
     * Register settings
     */
    public function register_settings() {
        // General settings
        register_setting('jewe_affiliate_general', 'jewe_affiliate_cookie_days');
        register_setting('jewe_affiliate_general', 'jewe_affiliate_min_payout');
        register_setting('jewe_affiliate_general', 'jewe_affiliate_default_commission');
        register_setting('jewe_affiliate_general', 'jewe_affiliate_auto_approve');
        register_setting('jewe_affiliate_general', 'jewe_affiliate_lifetime_commissions');

        // MLM settings
        register_setting('jewe_affiliate_mlm', 'jewe_affiliate_mlm_enabled');
        register_setting('jewe_affiliate_mlm', 'jewe_affiliate_mlm_levels');

        // Gamification settings
        register_setting('jewe_affiliate_gamification', 'jewe_affiliate_gamification_enabled');

        // Notification settings
        register_setting('jewe_affiliate_notifications', 'jewe_affiliate_email_notifications');
        register_setting('jewe_affiliate_notifications', 'jewe_affiliate_webhook_url');

        // Advanced settings
        register_setting('jewe_affiliate_advanced', 'jewe_affiliate_qr_enabled');
        register_setting('jewe_affiliate_advanced', 'jewe_affiliate_ai_insights_enabled');
    }

    /**
     * Render settings page
     */
    public function render() {
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';

        // Save settings
        if (isset($_POST['jewe_save_settings']) && check_admin_referer('jewe_settings_nonce')) {
            $this->save_settings($active_tab);
            echo '<div class="notice notice-success"><p>' . __('Impostazioni salvate.', 'jewe-affiliate-pro') . '</p></div>';
        }

        ?>
        <div class="wrap">
            <h1><?php _e('Impostazioni JEWE Affiliate Pro', 'jewe-affiliate-pro'); ?></h1>

            <nav class="nav-tab-wrapper">
                <a href="?page=jewe-affiliate-settings&tab=general" class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Generale', 'jewe-affiliate-pro'); ?>
                </a>
                <a href="?page=jewe-affiliate-settings&tab=commissions" class="nav-tab <?php echo $active_tab === 'commissions' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Commissioni', 'jewe-affiliate-pro'); ?>
                </a>
                <a href="?page=jewe-affiliate-settings&tab=mlm" class="nav-tab <?php echo $active_tab === 'mlm' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('MLM / Team', 'jewe-affiliate-pro'); ?>
                </a>
                <a href="?page=jewe-affiliate-settings&tab=gamification" class="nav-tab <?php echo $active_tab === 'gamification' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Gamification', 'jewe-affiliate-pro'); ?>
                </a>
                <a href="?page=jewe-affiliate-settings&tab=notifications" class="nav-tab <?php echo $active_tab === 'notifications' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Notifiche', 'jewe-affiliate-pro'); ?>
                </a>
                <a href="?page=jewe-affiliate-settings&tab=advanced" class="nav-tab <?php echo $active_tab === 'advanced' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Avanzate', 'jewe-affiliate-pro'); ?>
                </a>
            </nav>

            <form method="post">
                <?php wp_nonce_field('jewe_settings_nonce'); ?>

                <div class="jewe-settings-content">
                    <?php
                    switch ($active_tab) {
                        case 'commissions':
                            $this->render_commissions_tab();
                            break;
                        case 'mlm':
                            $this->render_mlm_tab();
                            break;
                        case 'gamification':
                            $this->render_gamification_tab();
                            break;
                        case 'notifications':
                            $this->render_notifications_tab();
                            break;
                        case 'advanced':
                            $this->render_advanced_tab();
                            break;
                        default:
                            $this->render_general_tab();
                    }
                    ?>
                </div>

                <p class="submit">
                    <input type="submit" name="jewe_save_settings" class="button button-primary" value="<?php _e('Salva Impostazioni', 'jewe-affiliate-pro'); ?>">
                </p>
            </form>
        </div>

        <style>
            .jewe-settings-content { background: #fff; padding: 20px; margin-top: 20px; border: 1px solid #ccd0d4; }
            .jewe-settings-content table { width: 100%; }
            .jewe-settings-content th { text-align: left; padding: 15px 10px 15px 0; width: 200px; }
            .jewe-settings-content td { padding: 10px 0; }
            .jewe-settings-content .description { color: #666; font-style: italic; margin-top: 5px; }
            .jewe-settings-section { margin-bottom: 30px; }
            .jewe-settings-section h3 { border-bottom: 1px solid #eee; padding-bottom: 10px; }
        </style>
        <?php
    }

    /**
     * Render general tab
     */
    private function render_general_tab() {
        ?>
        <div class="jewe-settings-section">
            <h3><?php _e('Impostazioni Generali', 'jewe-affiliate-pro'); ?></h3>
            <table>
                <tr>
                    <th><?php _e('Durata Cookie (giorni)', 'jewe-affiliate-pro'); ?></th>
                    <td>
                        <input type="number" name="jewe_affiliate_cookie_days" value="<?php echo esc_attr(get_option('jewe_affiliate_cookie_days', 30)); ?>" min="1" max="365">
                        <p class="description"><?php _e('Per quanto tempo il cookie di referral rimane attivo.', 'jewe-affiliate-pro'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Pagamento Minimo (€)', 'jewe-affiliate-pro'); ?></th>
                    <td>
                        <input type="number" name="jewe_affiliate_min_payout" value="<?php echo esc_attr(get_option('jewe_affiliate_min_payout', 50)); ?>" min="1" step="0.01">
                        <p class="description"><?php _e('Importo minimo per richiedere un pagamento.', 'jewe-affiliate-pro'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Approvazione Automatica', 'jewe-affiliate-pro'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="jewe_affiliate_auto_approve" value="yes" <?php checked(get_option('jewe_affiliate_auto_approve', 'no'), 'yes'); ?>>
                            <?php _e('Approva automaticamente i nuovi affiliati', 'jewe-affiliate-pro'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Commissioni Lifetime', 'jewe-affiliate-pro'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="jewe_affiliate_lifetime_commissions" value="yes" <?php checked(get_option('jewe_affiliate_lifetime_commissions', 'yes'), 'yes'); ?>>
                            <?php _e('L\'affiliato guadagna commissioni da tutti gli ordini futuri del cliente referenziato', 'jewe-affiliate-pro'); ?>
                        </label>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }

    /**
     * Render commissions tab
     */
    private function render_commissions_tab() {
        $tiers = JEWE_Affiliate_Gamification::get_all_tiers();
        ?>
        <div class="jewe-settings-section">
            <h3><?php _e('Commissione Base', 'jewe-affiliate-pro'); ?></h3>
            <table>
                <tr>
                    <th><?php _e('Commissione Default (%)', 'jewe-affiliate-pro'); ?></th>
                    <td>
                        <input type="number" name="jewe_affiliate_default_commission" value="<?php echo esc_attr(get_option('jewe_affiliate_default_commission', 10)); ?>" min="0" max="100" step="0.1">
                        <p class="description"><?php _e('Percentuale commissione per il livello base.', 'jewe-affiliate-pro'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="jewe-settings-section">
            <h3><?php _e('Livelli e Commissioni', 'jewe-affiliate-pro'); ?></h3>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Livello', 'jewe-affiliate-pro'); ?></th>
                        <th><?php _e('Nome', 'jewe-affiliate-pro'); ?></th>
                        <th><?php _e('Min. Guadagni', 'jewe-affiliate-pro'); ?></th>
                        <th><?php _e('Min. Referral', 'jewe-affiliate-pro'); ?></th>
                        <th><?php _e('Commissione %', 'jewe-affiliate-pro'); ?></th>
                        <th><?php _e('MLM L1 %', 'jewe-affiliate-pro'); ?></th>
                        <th><?php _e('MLM L2 %', 'jewe-affiliate-pro'); ?></th>
                        <th><?php _e('MLM L3 %', 'jewe-affiliate-pro'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tiers as $tier): ?>
                    <tr>
                        <td><?php echo esc_html($tier->level); ?></td>
                        <td><input type="text" name="tier_name[<?php echo $tier->id; ?>]" value="<?php echo esc_attr($tier->name); ?>" class="regular-text"></td>
                        <td><input type="number" name="tier_min_earnings[<?php echo $tier->id; ?>]" value="<?php echo esc_attr($tier->min_earnings); ?>" min="0" step="0.01"></td>
                        <td><input type="number" name="tier_min_referrals[<?php echo $tier->id; ?>]" value="<?php echo esc_attr($tier->min_referrals); ?>" min="0"></td>
                        <td><input type="number" name="tier_commission[<?php echo $tier->id; ?>]" value="<?php echo esc_attr($tier->commission_rate); ?>" min="0" max="100" step="0.1"></td>
                        <td><input type="number" name="tier_mlm_l1[<?php echo $tier->id; ?>]" value="<?php echo esc_attr($tier->mlm_commission_l1); ?>" min="0" max="100" step="0.1"></td>
                        <td><input type="number" name="tier_mlm_l2[<?php echo $tier->id; ?>]" value="<?php echo esc_attr($tier->mlm_commission_l2); ?>" min="0" max="100" step="0.1"></td>
                        <td><input type="number" name="tier_mlm_l3[<?php echo $tier->id; ?>]" value="<?php echo esc_attr($tier->mlm_commission_l3); ?>" min="0" max="100" step="0.1"></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Render MLM tab
     */
    private function render_mlm_tab() {
        ?>
        <div class="jewe-settings-section">
            <h3><?php _e('Multi-Level Marketing', 'jewe-affiliate-pro'); ?></h3>
            <table>
                <tr>
                    <th><?php _e('Abilita MLM', 'jewe-affiliate-pro'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="jewe_affiliate_mlm_enabled" value="yes" <?php checked(get_option('jewe_affiliate_mlm_enabled', 'yes'), 'yes'); ?>>
                            <?php _e('Abilita commissioni multi-livello', 'jewe-affiliate-pro'); ?>
                        </label>
                        <p class="description"><?php _e('Gli affiliati guadagnano commissioni anche dalle vendite del loro team.', 'jewe-affiliate-pro'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Livelli MLM', 'jewe-affiliate-pro'); ?></th>
                    <td>
                        <select name="jewe_affiliate_mlm_levels">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php selected(get_option('jewe_affiliate_mlm_levels', 3), $i); ?>>
                                <?php echo $i; ?> <?php echo $i === 1 ? __('livello', 'jewe-affiliate-pro') : __('livelli', 'jewe-affiliate-pro'); ?>
                            </option>
                            <?php endfor; ?>
                        </select>
                        <p class="description"><?php _e('Numero di livelli di profondità per le commissioni MLM.', 'jewe-affiliate-pro'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }

    /**
     * Render gamification tab
     */
    private function render_gamification_tab() {
        ?>
        <div class="jewe-settings-section">
            <h3><?php _e('Gamification', 'jewe-affiliate-pro'); ?></h3>
            <table>
                <tr>
                    <th><?php _e('Abilita Gamification', 'jewe-affiliate-pro'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="jewe_affiliate_gamification_enabled" value="yes" <?php checked(get_option('jewe_affiliate_gamification_enabled', 'yes'), 'yes'); ?>>
                            <?php _e('Abilita badge, livelli e leaderboard', 'jewe-affiliate-pro'); ?>
                        </label>
                    </td>
                </tr>
            </table>
        </div>

        <div class="jewe-settings-section">
            <h3><?php _e('Badge Configurati', 'jewe-affiliate-pro'); ?></h3>
            <?php
            global $wpdb;
            $badges = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}jewe_badges ORDER BY requirement_value ASC");
            ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Nome', 'jewe-affiliate-pro'); ?></th>
                        <th><?php _e('Descrizione', 'jewe-affiliate-pro'); ?></th>
                        <th><?php _e('Requisito', 'jewe-affiliate-pro'); ?></th>
                        <th><?php _e('Bonus', 'jewe-affiliate-pro'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($badges as $badge): ?>
                    <tr>
                        <td><span class="<?php echo esc_attr($badge->icon); ?>"></span> <?php echo esc_html($badge->name); ?></td>
                        <td><?php echo esc_html($badge->description); ?></td>
                        <td><?php echo esc_html($badge->requirement_type); ?>: <?php echo esc_html($badge->requirement_value); ?></td>
                        <td><?php echo esc_html($badge->bonus_type); ?>: <?php echo esc_html($badge->bonus_value); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Render notifications tab
     */
    private function render_notifications_tab() {
        ?>
        <div class="jewe-settings-section">
            <h3><?php _e('Notifiche Email', 'jewe-affiliate-pro'); ?></h3>
            <table>
                <tr>
                    <th><?php _e('Notifiche Email', 'jewe-affiliate-pro'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="jewe_affiliate_email_notifications" value="yes" <?php checked(get_option('jewe_affiliate_email_notifications', 'yes'), 'yes'); ?>>
                            <?php _e('Invia email agli affiliati per eventi importanti', 'jewe-affiliate-pro'); ?>
                        </label>
                    </td>
                </tr>
            </table>
        </div>

        <div class="jewe-settings-section">
            <h3><?php _e('Webhook', 'jewe-affiliate-pro'); ?></h3>
            <table>
                <tr>
                    <th><?php _e('URL Webhook', 'jewe-affiliate-pro'); ?></th>
                    <td>
                        <input type="url" name="jewe_affiliate_webhook_url" value="<?php echo esc_attr(get_option('jewe_affiliate_webhook_url', '')); ?>" class="regular-text">
                        <p class="description"><?php _e('URL per ricevere notifiche webhook (opzionale).', 'jewe-affiliate-pro'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }

    /**
     * Render advanced tab
     */
    private function render_advanced_tab() {
        ?>
        <div class="jewe-settings-section">
            <h3><?php _e('Funzionalità Avanzate', 'jewe-affiliate-pro'); ?></h3>
            <table>
                <tr>
                    <th><?php _e('QR Code', 'jewe-affiliate-pro'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="jewe_affiliate_qr_enabled" value="yes" <?php checked(get_option('jewe_affiliate_qr_enabled', 'yes'), 'yes'); ?>>
                            <?php _e('Abilita generazione QR Code', 'jewe-affiliate-pro'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('AI Insights', 'jewe-affiliate-pro'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="jewe_affiliate_ai_insights_enabled" value="yes" <?php checked(get_option('jewe_affiliate_ai_insights_enabled', 'yes'), 'yes'); ?>>
                            <?php _e('Abilita suggerimenti AI per gli affiliati', 'jewe-affiliate-pro'); ?>
                        </label>
                    </td>
                </tr>
            </table>
        </div>

        <div class="jewe-settings-section">
            <h3><?php _e('REST API', 'jewe-affiliate-pro'); ?></h3>
            <p><?php _e('Endpoint API:', 'jewe-affiliate-pro'); ?> <code><?php echo rest_url('jewe-affiliate/v1/'); ?></code></p>
            <p class="description"><?php _e('L\'API REST è sempre attiva per integrazioni esterne.', 'jewe-affiliate-pro'); ?></p>
        </div>
        <?php
    }

    /**
     * Save settings
     */
    private function save_settings($tab) {
        switch ($tab) {
            case 'general':
                update_option('jewe_affiliate_cookie_days', intval($_POST['jewe_affiliate_cookie_days'] ?? 30));
                update_option('jewe_affiliate_min_payout', floatval($_POST['jewe_affiliate_min_payout'] ?? 50));
                update_option('jewe_affiliate_auto_approve', isset($_POST['jewe_affiliate_auto_approve']) ? 'yes' : 'no');
                update_option('jewe_affiliate_lifetime_commissions', isset($_POST['jewe_affiliate_lifetime_commissions']) ? 'yes' : 'no');
                break;

            case 'commissions':
                update_option('jewe_affiliate_default_commission', floatval($_POST['jewe_affiliate_default_commission'] ?? 10));

                // Update tiers
                if (isset($_POST['tier_name'])) {
                    global $wpdb;
                    $table = $wpdb->prefix . 'jewe_tiers';

                    foreach ($_POST['tier_name'] as $tier_id => $name) {
                        $wpdb->update(
                            $table,
                            [
                                'name' => sanitize_text_field($name),
                                'min_earnings' => floatval($_POST['tier_min_earnings'][$tier_id] ?? 0),
                                'min_referrals' => intval($_POST['tier_min_referrals'][$tier_id] ?? 0),
                                'commission_rate' => floatval($_POST['tier_commission'][$tier_id] ?? 0),
                                'mlm_commission_l1' => floatval($_POST['tier_mlm_l1'][$tier_id] ?? 0),
                                'mlm_commission_l2' => floatval($_POST['tier_mlm_l2'][$tier_id] ?? 0),
                                'mlm_commission_l3' => floatval($_POST['tier_mlm_l3'][$tier_id] ?? 0),
                            ],
                            ['id' => intval($tier_id)]
                        );
                    }
                }
                break;

            case 'mlm':
                update_option('jewe_affiliate_mlm_enabled', isset($_POST['jewe_affiliate_mlm_enabled']) ? 'yes' : 'no');
                update_option('jewe_affiliate_mlm_levels', intval($_POST['jewe_affiliate_mlm_levels'] ?? 3));
                break;

            case 'gamification':
                update_option('jewe_affiliate_gamification_enabled', isset($_POST['jewe_affiliate_gamification_enabled']) ? 'yes' : 'no');
                break;

            case 'notifications':
                update_option('jewe_affiliate_email_notifications', isset($_POST['jewe_affiliate_email_notifications']) ? 'yes' : 'no');
                update_option('jewe_affiliate_webhook_url', esc_url_raw($_POST['jewe_affiliate_webhook_url'] ?? ''));
                break;

            case 'advanced':
                update_option('jewe_affiliate_qr_enabled', isset($_POST['jewe_affiliate_qr_enabled']) ? 'yes' : 'no');
                update_option('jewe_affiliate_ai_insights_enabled', isset($_POST['jewe_affiliate_ai_insights_enabled']) ? 'yes' : 'no');
                break;
        }
    }
}
