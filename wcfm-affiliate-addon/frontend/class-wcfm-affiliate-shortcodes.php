<?php
/**
 * Shortcodes
 *
 * @package WCFM_Affiliate_Pro
 * @since 1.0.0
 *
 * NOTA: Usa nomi shortcode UNICI con prefisso wcfm_aff_pro_
 * per evitare conflitti con altri plugin affiliate
 */

if (!defined('ABSPATH')) {
    exit;
}

class WCFM_Affiliate_Shortcodes {

    public function __construct() {
        // Shortcode UNICI per evitare conflitti
        add_shortcode('wcfm_aff_pro_dashboard', [$this, 'dashboard_shortcode']);
        add_shortcode('wcfm_aff_pro_registration', [$this, 'register_shortcode']);
        add_shortcode('wcfm_aff_pro_register', [$this, 'register_shortcode']); // alias
        add_shortcode('wcfm_aff_pro_login', [$this, 'login_shortcode']);
        add_shortcode('wcfm_aff_pro_link', [$this, 'link_shortcode']);
        add_shortcode('wcfm_aff_pro_stats', [$this, 'stats_shortcode']);
        add_shortcode('wcfm_aff_pro_creatives', [$this, 'creatives_shortcode']);
        add_shortcode('wcfm_aff_pro_leaderboard', [$this, 'leaderboard_shortcode']);
    }

    public function dashboard_shortcode($atts): string {
        if (!is_user_logged_in()) {
            $pages = get_option('wcfm_aff_pro_pages', []);
            $login_page = $pages['login'] ?? 0;
            if ($login_page) {
                return sprintf(
                    '<p>%s <a href="%s">%s</a></p>',
                    __('Devi essere loggato per accedere alla dashboard.', 'wcfm-affiliate-pro'),
                    get_permalink($login_page),
                    __('Accedi', 'wcfm-affiliate-pro')
                );
            }
            return '<p>' . __('Devi essere loggato per accedere alla dashboard.', 'wcfm-affiliate-pro') . '</p>';
        }

        $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate_by_user(get_current_user_id());

        if (!$affiliate) {
            $pages = get_option('wcfm_aff_pro_pages', []);
            $registration_page = $pages['registration'] ?? 0;
            if ($registration_page) {
                return sprintf(
                    '<p>%s <a href="%s">%s</a></p>',
                    __('Non sei ancora un affiliato.', 'wcfm-affiliate-pro'),
                    get_permalink($registration_page),
                    __('Registrati ora', 'wcfm-affiliate-pro')
                );
            }
            return '<p>' . __('Non sei un affiliato.', 'wcfm-affiliate-pro') . '</p>';
        }

        if ($affiliate->status === 'pending') {
            return '<div class="wcfm-affiliate-notice wcfm-affiliate-notice-warning">' .
                __('La tua richiesta di affiliazione è in attesa di approvazione.', 'wcfm-affiliate-pro') .
                '</div>';
        }

        if ($affiliate->status === 'suspended') {
            return '<div class="wcfm-affiliate-notice wcfm-affiliate-notice-error">' .
                __('Il tuo account affiliato è stato sospeso.', 'wcfm-affiliate-pro') .
                '</div>';
        }

        ob_start();
        wcfm_affiliate_pro()->dashboard->render();
        return ob_get_clean();
    }

    public function register_shortcode($atts): string {
        if (is_user_logged_in()) {
            $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate_by_user(get_current_user_id());

            if ($affiliate) {
                $pages = get_option('wcfm_aff_pro_pages', []);
                $dashboard_page = $pages['dashboard'] ?? 0;
                return sprintf(
                    '<p>%s <a href="%s">%s</a></p>',
                    __('Sei già un affiliato.', 'wcfm-affiliate-pro'),
                    get_permalink($dashboard_page),
                    __('Vai alla Dashboard', 'wcfm-affiliate-pro')
                );
            }

            // Allow logged-in users to become affiliates
            return $this->render_logged_in_registration();
        }

        return $this->render_registration_form();
    }

    private function render_registration_form(): string {
        $errors = [];
        if (function_exists('WC') && WC()->session) {
            $errors = WC()->session->get('wcfm_aff_pro_registration_errors', []);
            WC()->session->set('wcfm_aff_pro_registration_errors', []);
        }

        ob_start();
        ?>
        <div class="wcfm-affiliate-register-form">
            <?php if (!empty($errors)): ?>
                <div class="wcfm-affiliate-notice wcfm-affiliate-notice-error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo esc_html($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" class="wcfm-affiliate-form">
                <?php wp_nonce_field('wcfm_aff_pro_register', 'wcfm_aff_pro_nonce'); ?>

                <div class="wcfm-affiliate-form-row wcfm-affiliate-form-row-half">
                    <div class="wcfm-affiliate-form-field">
                        <label for="first_name"><?php _e('Nome', 'wcfm-affiliate-pro'); ?> <span class="required">*</span></label>
                        <input type="text" name="first_name" id="first_name" required
                               value="<?php echo esc_attr($_POST['first_name'] ?? ''); ?>">
                    </div>
                    <div class="wcfm-affiliate-form-field">
                        <label for="last_name"><?php _e('Cognome', 'wcfm-affiliate-pro'); ?></label>
                        <input type="text" name="last_name" id="last_name"
                               value="<?php echo esc_attr($_POST['last_name'] ?? ''); ?>">
                    </div>
                </div>

                <div class="wcfm-affiliate-form-field">
                    <label for="email"><?php _e('Email', 'wcfm-affiliate-pro'); ?> <span class="required">*</span></label>
                    <input type="email" name="email" id="email" required
                           value="<?php echo esc_attr($_POST['email'] ?? ''); ?>">
                </div>

                <div class="wcfm-affiliate-form-row wcfm-affiliate-form-row-half">
                    <div class="wcfm-affiliate-form-field">
                        <label for="username"><?php _e('Nome utente', 'wcfm-affiliate-pro'); ?> <span class="required">*</span></label>
                        <input type="text" name="username" id="username" required
                               value="<?php echo esc_attr($_POST['username'] ?? ''); ?>">
                    </div>
                    <div class="wcfm-affiliate-form-field">
                        <label for="password"><?php _e('Password', 'wcfm-affiliate-pro'); ?> <span class="required">*</span></label>
                        <input type="password" name="password" id="password" required minlength="6">
                    </div>
                </div>

                <div class="wcfm-affiliate-form-field">
                    <label for="website"><?php _e('Sito Web', 'wcfm-affiliate-pro'); ?></label>
                    <input type="url" name="website" id="website"
                           value="<?php echo esc_attr($_POST['website'] ?? ''); ?>"
                           placeholder="https://">
                </div>

                <div class="wcfm-affiliate-form-row wcfm-affiliate-form-row-half">
                    <div class="wcfm-affiliate-form-field">
                        <label for="payment_email"><?php _e('Email pagamento', 'wcfm-affiliate-pro'); ?></label>
                        <input type="email" name="payment_email" id="payment_email"
                               value="<?php echo esc_attr($_POST['payment_email'] ?? ''); ?>"
                               placeholder="<?php _e('Se diversa dalla email principale', 'wcfm-affiliate-pro'); ?>">
                    </div>
                    <div class="wcfm-affiliate-form-field">
                        <label for="payment_method"><?php _e('Metodo di pagamento', 'wcfm-affiliate-pro'); ?></label>
                        <select name="payment_method" id="payment_method">
                            <option value="paypal"><?php _e('PayPal', 'wcfm-affiliate-pro'); ?></option>
                            <option value="bank_transfer"><?php _e('Bonifico Bancario', 'wcfm-affiliate-pro'); ?></option>
                            <option value="store_credit"><?php _e('Credito Negozio', 'wcfm-affiliate-pro'); ?></option>
                        </select>
                    </div>
                </div>

                <?php
                $general_settings = get_option('wcfm_aff_pro_general', []);
                $terms_page = $general_settings['terms_page'] ?? 0;
                if ($terms_page):
                ?>
                <div class="wcfm-affiliate-form-field">
                    <label class="wcfm-affiliate-checkbox">
                        <input type="checkbox" name="terms" required>
                        <?php printf(
                            __('Accetto i <a href="%s" target="_blank">Termini e Condizioni</a>', 'wcfm-affiliate-pro'),
                            get_permalink($terms_page)
                        ); ?>
                    </label>
                </div>
                <?php endif; ?>

                <div class="wcfm-affiliate-form-field">
                    <button type="submit" name="wcfm_aff_pro_register" class="wcfm-affiliate-btn wcfm-affiliate-btn-primary">
                        <?php _e('Registrati come Affiliato', 'wcfm-affiliate-pro'); ?>
                    </button>
                </div>

                <p class="wcfm-affiliate-login-link">
                    <?php _e('Hai già un account?', 'wcfm-affiliate-pro'); ?>
                    <?php
                    $pages = get_option('wcfm_aff_pro_pages', []);
                    $login_page = $pages['login'] ?? 0;
                    if ($login_page):
                    ?>
                        <a href="<?php echo get_permalink($login_page); ?>"><?php _e('Accedi', 'wcfm-affiliate-pro'); ?></a>
                    <?php else: ?>
                        <a href="<?php echo wp_login_url(get_permalink()); ?>"><?php _e('Accedi', 'wcfm-affiliate-pro'); ?></a>
                    <?php endif; ?>
                </p>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_logged_in_registration(): string {
        $user = wp_get_current_user();

        ob_start();
        ?>
        <div class="wcfm-affiliate-register-form">
            <p><?php printf(__('Ciao %s! Vuoi diventare un affiliato?', 'wcfm-affiliate-pro'), esc_html($user->display_name)); ?></p>

            <form method="post" class="wcfm-affiliate-form" id="wcfm-affiliate-become-form">
                <?php wp_nonce_field('wcfm_affiliate_frontend_nonce', 'nonce'); ?>

                <div class="wcfm-affiliate-form-field">
                    <label for="website"><?php _e('Sito Web', 'wcfm-affiliate-pro'); ?></label>
                    <input type="url" name="website" id="website" placeholder="https://">
                </div>

                <div class="wcfm-affiliate-form-row wcfm-affiliate-form-row-half">
                    <div class="wcfm-affiliate-form-field">
                        <label for="payment_email"><?php _e('Email pagamento', 'wcfm-affiliate-pro'); ?></label>
                        <input type="email" name="payment_email" id="payment_email"
                               value="<?php echo esc_attr($user->user_email); ?>">
                    </div>
                    <div class="wcfm-affiliate-form-field">
                        <label for="payment_method"><?php _e('Metodo di pagamento', 'wcfm-affiliate-pro'); ?></label>
                        <select name="payment_method" id="payment_method">
                            <option value="paypal"><?php _e('PayPal', 'wcfm-affiliate-pro'); ?></option>
                            <option value="bank_transfer"><?php _e('Bonifico Bancario', 'wcfm-affiliate-pro'); ?></option>
                            <option value="store_credit"><?php _e('Credito Negozio', 'wcfm-affiliate-pro'); ?></option>
                        </select>
                    </div>
                </div>

                <div class="wcfm-affiliate-form-field">
                    <button type="submit" class="wcfm-affiliate-btn wcfm-affiliate-btn-primary" id="wcfm-affiliate-become-btn">
                        <?php _e('Diventa Affiliato', 'wcfm-affiliate-pro'); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function login_shortcode($atts): string {
        $pages = get_option('wcfm_aff_pro_pages', []);

        if (is_user_logged_in()) {
            $dashboard_page = $pages['dashboard'] ?? 0;
            return sprintf(
                '<p>%s <a href="%s">%s</a></p>',
                __('Sei già loggato.', 'wcfm-affiliate-pro'),
                get_permalink($dashboard_page),
                __('Vai alla Dashboard', 'wcfm-affiliate-pro')
            );
        }

        $dashboard_page = $pages['dashboard'] ?? 0;
        $redirect = $dashboard_page ? get_permalink($dashboard_page) : home_url();

        ob_start();
        ?>
        <div class="wcfm-affiliate-login-form">
            <?php
            wp_login_form([
                'redirect' => $redirect,
                'label_username' => __('Nome utente o Email', 'wcfm-affiliate-pro'),
                'label_password' => __('Password', 'wcfm-affiliate-pro'),
                'label_remember' => __('Ricordami', 'wcfm-affiliate-pro'),
                'label_log_in' => __('Accedi', 'wcfm-affiliate-pro'),
            ]);
            ?>
            <p class="wcfm-affiliate-register-link">
                <?php _e('Non hai un account?', 'wcfm-affiliate-pro'); ?>
                <?php
                $registration_page = $pages['registration'] ?? 0;
                if ($registration_page):
                ?>
                    <a href="<?php echo get_permalink($registration_page); ?>"><?php _e('Registrati', 'wcfm-affiliate-pro'); ?></a>
                <?php endif; ?>
            </p>
        </div>
        <?php
        return ob_get_clean();
    }

    public function link_shortcode($atts): string {
        $atts = shortcode_atts([
            'url' => '',
            'text' => '',
            'class' => '',
        ], $atts);

        if (!is_user_logged_in()) {
            return $atts['text'] ?: $atts['url'];
        }

        $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate_by_user(get_current_user_id());

        if (!$affiliate || $affiliate->status !== 'active') {
            return $atts['text'] ?: $atts['url'];
        }

        $url = $atts['url'] ?: home_url();
        $link = wcfm_affiliate_pro()->referrals->generate_referral_link($affiliate->id, $url);

        if ($atts['text']) {
            return sprintf(
                '<a href="%s" class="%s">%s</a>',
                esc_url($link),
                esc_attr($atts['class']),
                esc_html($atts['text'])
            );
        }

        return esc_url($link);
    }

    public function stats_shortcode($atts): string {
        if (!is_user_logged_in()) {
            return '';
        }

        $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate_by_user(get_current_user_id());

        if (!$affiliate || $affiliate->status !== 'active') {
            return '';
        }

        $stats = wcfm_affiliate_pro()->affiliates->get_affiliate_stats($affiliate->id);

        ob_start();
        ?>
        <div class="wcfm-affiliate-stats-widget">
            <div class="wcfm-affiliate-stat-item">
                <span class="stat-value"><?php echo wc_price($stats['earnings_balance']); ?></span>
                <span class="stat-label"><?php _e('Saldo', 'wcfm-affiliate-pro'); ?></span>
            </div>
            <div class="wcfm-affiliate-stat-item">
                <span class="stat-value"><?php echo number_format($stats['referrals_total']); ?></span>
                <span class="stat-label"><?php _e('Referral', 'wcfm-affiliate-pro'); ?></span>
            </div>
            <div class="wcfm-affiliate-stat-item">
                <span class="stat-value"><?php echo $stats['conversion_rate']; ?>%</span>
                <span class="stat-label"><?php _e('Conversione', 'wcfm-affiliate-pro'); ?></span>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function creatives_shortcode($atts): string {
        if (!is_user_logged_in()) {
            return '';
        }

        $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate_by_user(get_current_user_id());

        if (!$affiliate || $affiliate->status !== 'active') {
            return '';
        }

        global $wpdb;
        // Usa prefisso tabelle unico
        $table = $wpdb->prefix . 'wcfm_aff_pro_creatives';
        $creatives = $wpdb->get_results("SELECT * FROM {$table} WHERE status = 'active' ORDER BY id DESC");

        if (empty($creatives)) {
            return '<p>' . __('Nessun materiale promozionale disponibile.', 'wcfm-affiliate-pro') . '</p>';
        }

        ob_start();
        ?>
        <div class="wcfm-affiliate-creatives-grid">
            <?php foreach ($creatives as $creative): ?>
                <div class="wcfm-affiliate-creative">
                    <?php if ($creative->type === 'banner'): ?>
                        <img src="<?php echo esc_url($creative->image_url); ?>" alt="<?php echo esc_attr($creative->name); ?>">
                    <?php else: ?>
                        <div class="wcfm-affiliate-creative-text">
                            <?php echo esc_html($creative->text_content); ?>
                        </div>
                    <?php endif; ?>
                    <div class="wcfm-affiliate-creative-actions">
                        <input type="text" readonly class="wcfm-affiliate-creative-code"
                               value="<?php echo esc_attr(wcfm_affiliate_pro()->referrals->generate_referral_link($affiliate->id, $creative->link_url)); ?>">
                        <button type="button" class="wcfm-affiliate-btn wcfm-affiliate-copy-btn">
                            <?php _e('Copia', 'wcfm-affiliate-pro'); ?>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function leaderboard_shortcode($atts): string {
        $atts = shortcode_atts([
            'limit' => 10,
            'period' => '30days',
        ], $atts);

        $top_affiliates = wcfm_affiliate_pro()->reports->get_top_affiliates($atts['period'], intval($atts['limit']));

        if (empty($top_affiliates)) {
            return '<p>' . __('Nessun dato disponibile.', 'wcfm-affiliate-pro') . '</p>';
        }

        ob_start();
        ?>
        <div class="wcfm-affiliate-leaderboard">
            <table class="wcfm-affiliate-table">
                <thead>
                    <tr>
                        <th><?php _e('#', 'wcfm-affiliate-pro'); ?></th>
                        <th><?php _e('Affiliato', 'wcfm-affiliate-pro'); ?></th>
                        <th><?php _e('Referral', 'wcfm-affiliate-pro'); ?></th>
                        <th><?php _e('Guadagni', 'wcfm-affiliate-pro'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_affiliates as $index => $aff): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><?php echo esc_html($aff->display_name); ?></td>
                            <td><?php echo number_format($aff->referrals_count); ?></td>
                            <td><?php echo wc_price($aff->total_earnings); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }
}
