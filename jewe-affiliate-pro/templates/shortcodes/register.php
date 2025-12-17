<?php
/**
 * Shortcode Register Template
 *
 * @package JEWE_Affiliate_Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

$is_logged_in = is_user_logged_in();
?>

<div class="jewe-register-form">
    <h2><?php _e('Diventa un Affiliato', 'jewe-affiliate-pro'); ?></h2>

    <p style="text-align: center; margin-bottom: 30px; color: #6B7280;">
        <?php _e('Guadagna commissioni promuovendo i nostri prodotti!', 'jewe-affiliate-pro'); ?>
    </p>

    <form id="jewe-affiliate-register">
        <?php if (!$is_logged_in): ?>
        <div class="jewe-form-group">
            <label for="jewe-name"><?php _e('Nome', 'jewe-affiliate-pro'); ?></label>
            <input type="text" id="jewe-name" name="name" required>
        </div>

        <div class="jewe-form-group">
            <label for="jewe-email"><?php _e('Email', 'jewe-affiliate-pro'); ?></label>
            <input type="email" id="jewe-email" name="email" required>
        </div>

        <div class="jewe-form-group">
            <label for="jewe-password"><?php _e('Password', 'jewe-affiliate-pro'); ?></label>
            <input type="password" id="jewe-password" name="password" required minlength="8">
        </div>
        <?php else:
            $user = wp_get_current_user();
        ?>
        <div class="jewe-form-group">
            <p><strong><?php _e('Registrato come:', 'jewe-affiliate-pro'); ?></strong> <?php echo esc_html($user->display_name); ?> (<?php echo esc_html($user->user_email); ?>)</p>
        </div>
        <?php endif; ?>

        <div class="jewe-form-group">
            <label>
                <input type="checkbox" name="terms" required>
                <?php _e('Accetto i termini e condizioni del programma affiliati', 'jewe-affiliate-pro'); ?>
            </label>
        </div>

        <button type="submit" class="jewe-submit-btn">
            <?php _e('Registrati come Affiliato', 'jewe-affiliate-pro'); ?>
        </button>
    </form>

    <?php if (!$is_logged_in): ?>
    <p style="text-align: center; margin-top: 20px;">
        <?php _e('Hai già un account?', 'jewe-affiliate-pro'); ?>
        <a href="<?php echo wp_login_url(home_url('/affiliate-register/')); ?>"><?php _e('Accedi', 'jewe-affiliate-pro'); ?></a>
    </p>
    <?php endif; ?>

    <div style="margin-top: 40px; padding-top: 30px; border-top: 1px solid #E5E7EB;">
        <h3 style="text-align: center; margin-bottom: 20px;"><?php _e('Perché diventare affiliato?', 'jewe-affiliate-pro'); ?></h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
            <div style="text-align: center;">
                <span style="font-size: 40px;">💰</span>
                <h4><?php _e('Guadagni', 'jewe-affiliate-pro'); ?></h4>
                <p style="color: #6B7280;"><?php _e('Fino al 20% di commissione su ogni vendita', 'jewe-affiliate-pro'); ?></p>
            </div>
            <div style="text-align: center;">
                <span style="font-size: 40px;">👥</span>
                <h4><?php _e('Team', 'jewe-affiliate-pro'); ?></h4>
                <p style="color: #6B7280;"><?php _e('Costruisci un team e guadagna dalle loro vendite', 'jewe-affiliate-pro'); ?></p>
            </div>
            <div style="text-align: center;">
                <span style="font-size: 40px;">📊</span>
                <h4><?php _e('Strumenti', 'jewe-affiliate-pro'); ?></h4>
                <p style="color: #6B7280;"><?php _e('Dashboard avanzata con analytics e AI insights', 'jewe-affiliate-pro'); ?></p>
            </div>
        </div>
    </div>
</div>
