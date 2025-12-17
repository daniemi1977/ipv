<?php
/**
 * Shortcodes Handler
 *
 * @package JEWE_Affiliate_Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class JEWE_Affiliate_Shortcodes {

    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
            self::$instance->register_shortcodes();
        }
        return self::$instance;
    }

    /**
     * Register all shortcodes
     */
    private function register_shortcodes() {
        add_shortcode('jewe_affiliate_dashboard', [$this, 'dashboard_shortcode']);
        add_shortcode('jewe_affiliate_register', [$this, 'register_shortcode']);
        add_shortcode('jewe_affiliate_link', [$this, 'link_shortcode']);
        add_shortcode('jewe_affiliate_leaderboard', [$this, 'leaderboard_shortcode']);
        add_shortcode('jewe_affiliate_stats', [$this, 'stats_shortcode']);
        add_shortcode('jewe_affiliate_referral_url', [$this, 'referral_url_shortcode']);
    }

    /**
     * Dashboard shortcode
     */
    public function dashboard_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Devi essere loggato per accedere alla dashboard.', 'jewe-affiliate-pro') . ' <a href="' . wp_login_url(get_permalink()) . '">' . __('Accedi', 'jewe-affiliate-pro') . '</a></p>';
        }

        $affiliate = JEWE_Affiliate::get_by_user(get_current_user_id());

        if (!$affiliate) {
            return '<p>' . __('Non sei ancora un affiliato.', 'jewe-affiliate-pro') . ' <a href="' . home_url('/affiliate-register/') . '">' . __('Registrati', 'jewe-affiliate-pro') . '</a></p>';
        }

        if ($affiliate->status === 'pending') {
            return '<div class="jewe-notice jewe-notice-warning">' . __('Il tuo account è in attesa di approvazione.', 'jewe-affiliate-pro') . '</div>';
        }

        if ($affiliate->status === 'suspended') {
            return '<div class="jewe-notice jewe-notice-error">' . __('Il tuo account è stato sospeso.', 'jewe-affiliate-pro') . '</div>';
        }

        ob_start();
        include JEWE_AFFILIATE_PLUGIN_DIR . 'templates/shortcodes/dashboard.php';
        return ob_get_clean();
    }

    /**
     * Register shortcode
     */
    public function register_shortcode($atts) {
        if (is_user_logged_in()) {
            $affiliate = JEWE_Affiliate::get_by_user(get_current_user_id());
            if ($affiliate) {
                return '<p>' . __('Sei già registrato come affiliato.', 'jewe-affiliate-pro') . ' <a href="' . home_url('/affiliate-dashboard/') . '">' . __('Vai alla Dashboard', 'jewe-affiliate-pro') . '</a></p>';
            }
        }

        ob_start();
        include JEWE_AFFILIATE_PLUGIN_DIR . 'templates/shortcodes/register.php';
        return ob_get_clean();
    }

    /**
     * Affiliate link shortcode
     */
    public function link_shortcode($atts) {
        $atts = shortcode_atts([
            'url' => '',
            'text' => __('Clicca qui', 'jewe-affiliate-pro'),
            'class' => 'jewe-affiliate-link',
        ], $atts);

        if (!is_user_logged_in()) {
            return '<a href="' . esc_url($atts['url'] ?: home_url()) . '" class="' . esc_attr($atts['class']) . '">' . esc_html($atts['text']) . '</a>';
        }

        $affiliate = JEWE_Affiliate::get_by_user(get_current_user_id());
        if (!$affiliate) {
            return '<a href="' . esc_url($atts['url'] ?: home_url()) . '" class="' . esc_attr($atts['class']) . '">' . esc_html($atts['text']) . '</a>';
        }

        $url = JEWE_Affiliate::get_referral_url($affiliate->id, $atts['url']);

        return '<a href="' . esc_url($url) . '" class="' . esc_attr($atts['class']) . '">' . esc_html($atts['text']) . '</a>';
    }

    /**
     * Leaderboard shortcode
     */
    public function leaderboard_shortcode($atts) {
        $atts = shortcode_atts([
            'limit' => 10,
            'period' => '30days',
            'show_earnings' => 'no',
        ], $atts);

        $leaders = JEWE_Affiliate::get_leaderboard(intval($atts['limit']), $atts['period']);

        ob_start();
        ?>
        <div class="jewe-leaderboard">
            <table class="jewe-leaderboard-table">
                <thead>
                    <tr>
                        <th><?php _e('Posizione', 'jewe-affiliate-pro'); ?></th>
                        <th><?php _e('Affiliato', 'jewe-affiliate-pro'); ?></th>
                        <th><?php _e('Livello', 'jewe-affiliate-pro'); ?></th>
                        <th><?php _e('Vendite', 'jewe-affiliate-pro'); ?></th>
                        <?php if ($atts['show_earnings'] === 'yes'): ?>
                        <th><?php _e('Guadagni', 'jewe-affiliate-pro'); ?></th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leaders as $i => $leader):
                        $user = get_userdata($leader->user_id);
                        $name = $user ? substr($user->display_name, 0, 3) . '***' : __('Anonimo', 'jewe-affiliate-pro');
                        $tier = JEWE_Affiliate_Gamification::get_tier($leader->tier_level);
                    ?>
                    <tr class="<?php echo $i < 3 ? 'jewe-top-' . ($i + 1) : ''; ?>">
                        <td class="jewe-rank">
                            <?php if ($i === 0): ?>
                                <span class="jewe-medal jewe-gold">🥇</span>
                            <?php elseif ($i === 1): ?>
                                <span class="jewe-medal jewe-silver">🥈</span>
                            <?php elseif ($i === 2): ?>
                                <span class="jewe-medal jewe-bronze">🥉</span>
                            <?php else: ?>
                                <?php echo $i + 1; ?>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html($name); ?></td>
                        <td><?php echo $tier ? esc_html($tier->name) : 'Bronze'; ?></td>
                        <td><?php echo intval($leader->period_sales); ?></td>
                        <?php if ($atts['show_earnings'] === 'yes'): ?>
                        <td>€<?php echo number_format($leader->period_earnings, 2); ?></td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Stats shortcode (for current affiliate)
     */
    public function stats_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '';
        }

        $affiliate = JEWE_Affiliate::get_by_user(get_current_user_id());
        if (!$affiliate || $affiliate->status !== 'active') {
            return '';
        }

        $atts = shortcode_atts([
            'period' => '30days',
            'show' => 'earnings,clicks,conversions',
        ], $atts);

        $stats = JEWE_Affiliate::get_stats($affiliate->id, $atts['period']);
        $show = array_map('trim', explode(',', $atts['show']));

        ob_start();
        ?>
        <div class="jewe-stats-widget">
            <?php if (in_array('earnings', $show)): ?>
            <div class="jewe-stat">
                <span class="jewe-stat-value">€<?php echo number_format($stats['earnings_total'], 2); ?></span>
                <span class="jewe-stat-label"><?php _e('Guadagni', 'jewe-affiliate-pro'); ?></span>
            </div>
            <?php endif; ?>

            <?php if (in_array('clicks', $show)): ?>
            <div class="jewe-stat">
                <span class="jewe-stat-value"><?php echo number_format($stats['total_clicks']); ?></span>
                <span class="jewe-stat-label"><?php _e('Click', 'jewe-affiliate-pro'); ?></span>
            </div>
            <?php endif; ?>

            <?php if (in_array('conversions', $show)): ?>
            <div class="jewe-stat">
                <span class="jewe-stat-value"><?php echo number_format($stats['conversions']); ?></span>
                <span class="jewe-stat-label"><?php _e('Conversioni', 'jewe-affiliate-pro'); ?></span>
            </div>
            <?php endif; ?>

            <?php if (in_array('rate', $show)): ?>
            <div class="jewe-stat">
                <span class="jewe-stat-value"><?php echo $stats['conversion_rate']; ?>%</span>
                <span class="jewe-stat-label"><?php _e('Tasso Conversione', 'jewe-affiliate-pro'); ?></span>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Referral URL shortcode
     */
    public function referral_url_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '';
        }

        $affiliate = JEWE_Affiliate::get_by_user(get_current_user_id());
        if (!$affiliate || $affiliate->status !== 'active') {
            return '';
        }

        $atts = shortcode_atts([
            'url' => home_url(),
            'display' => 'full', // full, short, input
        ], $atts);

        $referral_url = JEWE_Affiliate::get_referral_url($affiliate->id, $atts['url']);

        if ($atts['display'] === 'input') {
            return '<div class="jewe-referral-url-box">
                <input type="text" value="' . esc_attr($referral_url) . '" readonly class="jewe-referral-url-input">
                <button type="button" class="jewe-copy-btn" data-url="' . esc_attr($referral_url) . '">' . __('Copia', 'jewe-affiliate-pro') . '</button>
            </div>';
        }

        if ($atts['display'] === 'short') {
            return '<code>' . esc_html($affiliate->affiliate_code) . '</code>';
        }

        return '<code>' . esc_html($referral_url) . '</code>';
    }
}
