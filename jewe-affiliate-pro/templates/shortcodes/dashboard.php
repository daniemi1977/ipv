<?php
/**
 * Shortcode Dashboard Template
 *
 * @package JEWE_Affiliate_Pro
 */

if (!defined('ABSPATH')) {
    exit;
}

$affiliate = JEWE_Affiliate::get_by_user(get_current_user_id());
$user = wp_get_current_user();
$stats = JEWE_Affiliate::get_stats($affiliate->id, '30days');
$tier = JEWE_Affiliate_Gamification::get_tier($affiliate->tier_level);
$tier_progress = JEWE_Affiliate_Gamification::get_tier_progress($affiliate->id);
$insights = JEWE_Affiliate_AI_Insights::get_insights($affiliate->id);
$unread_notifications = JEWE_Affiliate_Notifications::get_unread_count($affiliate->id);
?>

<div class="jewe-dashboard">
    <!-- Header -->
    <div class="jewe-dashboard-header">
        <div class="jewe-user-info">
            <h2><?php printf(__('Ciao, %s!', 'jewe-affiliate-pro'), esc_html($user->display_name)); ?></h2>
            <p class="jewe-user-code"><?php _e('Codice affiliato:', 'jewe-affiliate-pro'); ?> <strong><?php echo esc_html($affiliate->affiliate_code); ?></strong></p>
        </div>
        <div class="jewe-tier-card">
            <span class="jewe-tier-icon">
                <?php
                $tier_icons = ['Bronze' => '🥉', 'Silver' => '🥈', 'Gold' => '🥇', 'Platinum' => '💎', 'Diamond' => '👑'];
                echo $tier_icons[$tier->name] ?? '⭐';
                ?>
            </span>
            <span class="jewe-tier-name"><?php echo esc_html($tier->name); ?></span>
            <span class="jewe-tier-commission"><?php echo $tier->commission_rate; ?>% commissione</span>
        </div>
    </div>

    <!-- Referral URL Box -->
    <div class="jewe-referral-box">
        <label><?php _e('Il tuo link di affiliazione:', 'jewe-affiliate-pro'); ?></label>
        <div class="jewe-referral-url-box">
            <input type="text" value="<?php echo esc_attr(JEWE_Affiliate::get_referral_url($affiliate->id)); ?>" readonly class="jewe-referral-url-input">
            <button type="button" class="jewe-copy-btn"><?php _e('Copia', 'jewe-affiliate-pro'); ?></button>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="jewe-stats-grid">
        <div class="jewe-stat-card highlight">
            <span class="value">€<?php echo number_format($affiliate->current_balance, 2); ?></span>
            <span class="label"><?php _e('Saldo Disponibile', 'jewe-affiliate-pro'); ?></span>
        </div>
        <div class="jewe-stat-card">
            <span class="value">€<?php echo number_format($stats['earnings_total'], 2); ?></span>
            <span class="label"><?php _e('Guadagni (30gg)', 'jewe-affiliate-pro'); ?></span>
        </div>
        <div class="jewe-stat-card">
            <span class="value"><?php echo number_format($stats['total_clicks']); ?></span>
            <span class="label"><?php _e('Click', 'jewe-affiliate-pro'); ?></span>
        </div>
        <div class="jewe-stat-card">
            <span class="value"><?php echo number_format($stats['conversions']); ?></span>
            <span class="label"><?php _e('Conversioni', 'jewe-affiliate-pro'); ?></span>
        </div>
        <div class="jewe-stat-card">
            <span class="value"><?php echo $stats['conversion_rate']; ?>%</span>
            <span class="label"><?php _e('Tasso Conv.', 'jewe-affiliate-pro'); ?></span>
        </div>
    </div>

    <!-- Tabs -->
    <div class="jewe-tabs">
        <button class="jewe-tab active" data-tab="overview"><?php _e('Panoramica', 'jewe-affiliate-pro'); ?></button>
        <button class="jewe-tab" data-tab="commissions"><?php _e('Commissioni', 'jewe-affiliate-pro'); ?></button>
        <button class="jewe-tab" data-tab="team"><?php _e('Il Mio Team', 'jewe-affiliate-pro'); ?></button>
        <button class="jewe-tab" data-tab="badges"><?php _e('Badge & Livelli', 'jewe-affiliate-pro'); ?></button>
        <button class="jewe-tab" data-tab="tools"><?php _e('Strumenti', 'jewe-affiliate-pro'); ?></button>
    </div>

    <!-- Tab Content: Overview -->
    <div id="overview" class="jewe-tab-content active">
        <?php if (!empty($insights)): ?>
        <div class="jewe-card">
            <h3><?php _e('Suggerimenti AI', 'jewe-affiliate-pro'); ?></h3>
            <?php foreach ($insights as $insight): ?>
            <div class="jewe-insight">
                <div class="jewe-insight-score <?php echo $insight['score'] >= 70 ? 'high' : ($insight['score'] >= 40 ? 'medium' : 'low'); ?>">
                    <?php echo round($insight['score']); ?>
                </div>
                <div class="jewe-insight-content">
                    <h4><?php echo esc_html($insight['title']); ?></h4>
                    <p><?php echo esc_html($insight['message']); ?></p>
                    <?php if (!empty($insight['recommendations'])): ?>
                    <ul class="jewe-insight-recommendations">
                        <?php foreach ($insight['recommendations'] as $rec): ?>
                        <li><?php echo esc_html($rec); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Tier Progress -->
        <?php if (!$tier_progress['is_max_tier']): ?>
        <div class="jewe-card">
            <h3><?php printf(__('Progresso verso %s', 'jewe-affiliate-pro'), $tier_progress['next_tier']->name); ?></h3>
            <div class="jewe-tier-progress">
                <div>
                    <span><?php _e('Guadagni:', 'jewe-affiliate-pro'); ?> €<?php echo number_format($affiliate->lifetime_earnings, 2); ?> / €<?php echo number_format($tier_progress['next_tier']->min_earnings, 2); ?></span>
                    <div class="jewe-progress-bar">
                        <div class="jewe-progress-fill" style="width: <?php echo $tier_progress['earnings_progress']; ?>%"></div>
                    </div>
                </div>
                <div style="margin-top: 15px;">
                    <span><?php _e('Referral:', 'jewe-affiliate-pro'); ?> <?php echo $affiliate->total_referrals; ?> / <?php echo $tier_progress['next_tier']->min_referrals; ?></span>
                    <div class="jewe-progress-bar">
                        <div class="jewe-progress-fill" style="width: <?php echo $tier_progress['referrals_progress']; ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Tab Content: Commissions -->
    <div id="commissions" class="jewe-tab-content">
        <div class="jewe-card">
            <h3><?php _e('Ultime Commissioni', 'jewe-affiliate-pro'); ?></h3>
            <?php
            $commissions = JEWE_Affiliate_Database::get_commissions($affiliate->id, ['limit' => 20]);
            if (!empty($commissions)):
            ?>
            <table class="jewe-table">
                <thead>
                    <tr>
                        <th><?php _e('Data', 'jewe-affiliate-pro'); ?></th>
                        <th><?php _e('Ordine', 'jewe-affiliate-pro'); ?></th>
                        <th><?php _e('Tipo', 'jewe-affiliate-pro'); ?></th>
                        <th><?php _e('Importo', 'jewe-affiliate-pro'); ?></th>
                        <th><?php _e('Stato', 'jewe-affiliate-pro'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($commissions as $commission): ?>
                    <tr>
                        <td><?php echo date_i18n(get_option('date_format'), strtotime($commission->created_at)); ?></td>
                        <td>#<?php echo $commission->order_id; ?></td>
                        <td><?php echo esc_html($commission->commission_type); ?></td>
                        <td>€<?php echo number_format($commission->commission_amount, 2); ?></td>
                        <td><span class="jewe-status jewe-status-<?php echo $commission->status; ?>"><?php echo $commission->status; ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p><?php _e('Nessuna commissione ancora.', 'jewe-affiliate-pro'); ?></p>
            <?php endif; ?>
        </div>

        <!-- Payout Request -->
        <div class="jewe-card">
            <h3><?php _e('Richiedi Pagamento', 'jewe-affiliate-pro'); ?></h3>
            <form class="jewe-payout-form">
                <div class="jewe-form-group">
                    <label><?php _e('Importo', 'jewe-affiliate-pro'); ?></label>
                    <input type="number" name="amount" min="<?php echo get_option('jewe_affiliate_min_payout', 50); ?>" max="<?php echo $affiliate->current_balance; ?>" step="0.01" value="<?php echo $affiliate->current_balance; ?>">
                    <small><?php printf(__('Minimo: €%s - Disponibile: €%s', 'jewe-affiliate-pro'), get_option('jewe_affiliate_min_payout', 50), number_format($affiliate->current_balance, 2)); ?></small>
                </div>
                <div class="jewe-form-group">
                    <label><?php _e('Metodo di Pagamento', 'jewe-affiliate-pro'); ?></label>
                    <select name="method">
                        <option value="paypal">PayPal</option>
                        <option value="bank_transfer"><?php _e('Bonifico Bancario', 'jewe-affiliate-pro'); ?></option>
                    </select>
                </div>
                <div class="jewe-form-group">
                    <label><?php _e('Dettagli (Email PayPal o IBAN)', 'jewe-affiliate-pro'); ?></label>
                    <input type="text" name="details" placeholder="<?php _e('Inserisci i dettagli per il pagamento', 'jewe-affiliate-pro'); ?>">
                </div>
                <button type="submit" class="jewe-submit-btn" <?php echo $affiliate->current_balance < get_option('jewe_affiliate_min_payout', 50) ? 'disabled' : ''; ?>>
                    <?php _e('Richiedi Pagamento', 'jewe-affiliate-pro'); ?>
                </button>
            </form>
        </div>
    </div>

    <!-- Tab Content: Team -->
    <div id="team" class="jewe-tab-content">
        <?php
        $mlm_stats = JEWE_Affiliate_MLM::get_stats($affiliate->id);
        $downline = JEWE_Affiliate_MLM::get_downline($affiliate->id);
        ?>
        <div class="jewe-stats-grid">
            <div class="jewe-stat-card">
                <span class="value"><?php echo $mlm_stats->direct_referrals ?? 0; ?></span>
                <span class="label"><?php _e('Referral Diretti', 'jewe-affiliate-pro'); ?></span>
            </div>
            <div class="jewe-stat-card">
                <span class="value"><?php echo $mlm_stats->team_size ?? 0; ?></span>
                <span class="label"><?php _e('Team Totale', 'jewe-affiliate-pro'); ?></span>
            </div>
            <div class="jewe-stat-card">
                <span class="value">€<?php echo number_format($mlm_stats->team_earnings ?? 0, 2); ?></span>
                <span class="label"><?php _e('Guadagni Team', 'jewe-affiliate-pro'); ?></span>
            </div>
        </div>

        <div class="jewe-card">
            <h3><?php _e('Il Tuo Team', 'jewe-affiliate-pro'); ?></h3>
            <?php if (!empty($downline)): ?>
            <table class="jewe-table">
                <thead>
                    <tr>
                        <th><?php _e('Livello', 'jewe-affiliate-pro'); ?></th>
                        <th><?php _e('Nome', 'jewe-affiliate-pro'); ?></th>
                        <th><?php _e('Stato', 'jewe-affiliate-pro'); ?></th>
                        <th><?php _e('Guadagni', 'jewe-affiliate-pro'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($downline as $member):
                        $member_user = get_userdata($member->user_id);
                    ?>
                    <tr>
                        <td><?php echo $member->level_depth; ?></td>
                        <td><?php echo $member_user ? esc_html($member_user->display_name) : 'N/A'; ?></td>
                        <td><span class="jewe-status jewe-status-<?php echo $member->status; ?>"><?php echo $member->status; ?></span></td>
                        <td>€<?php echo number_format($member->lifetime_earnings, 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p><?php _e('Non hai ancora membri nel team. Condividi il tuo link per invitare altri affiliati!', 'jewe-affiliate-pro'); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tab Content: Badges -->
    <div id="badges" class="jewe-tab-content">
        <div class="jewe-card">
            <h3><?php _e('I Tuoi Badge', 'jewe-affiliate-pro'); ?></h3>
            <?php
            $badges = JEWE_Affiliate_Gamification::get_badges_with_progress($affiliate->id);
            ?>
            <div class="jewe-badges-grid">
                <?php foreach ($badges as $badge): ?>
                <div class="jewe-badge <?php echo $badge->earned ? 'earned' : ''; ?>">
                    <span class="jewe-badge-icon <?php echo esc_attr($badge->icon); ?>"></span>
                    <div class="jewe-badge-info">
                        <span class="jewe-badge-name"><?php echo esc_html($badge->name); ?></span>
                        <?php if (!$badge->earned): ?>
                        <div class="jewe-progress-bar">
                            <div class="jewe-progress-fill" style="width: <?php echo $badge->progress; ?>%"></div>
                        </div>
                        <small><?php echo $badge->current_value; ?> / <?php echo $badge->requirement_value; ?></small>
                        <?php else: ?>
                        <small><?php _e('Completato!', 'jewe-affiliate-pro'); ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Tab Content: Tools -->
    <div id="tools" class="jewe-tab-content">
        <div class="jewe-card">
            <h3><?php _e('Generatore Link', 'jewe-affiliate-pro'); ?></h3>
            <div class="jewe-form-group">
                <label><?php _e('URL di destinazione', 'jewe-affiliate-pro'); ?></label>
                <input type="url" id="jewe-link-target" placeholder="https://esempio.com/prodotto">
            </div>
            <button type="button" class="jewe-submit-btn" onclick="generateAffiliateLink()"><?php _e('Genera Link', 'jewe-affiliate-pro'); ?></button>
            <div id="jewe-generated-link" style="margin-top: 15px; display: none;">
                <input type="text" readonly class="jewe-referral-url-input">
                <button type="button" class="jewe-copy-btn"><?php _e('Copia', 'jewe-affiliate-pro'); ?></button>
            </div>
        </div>

        <?php if (get_option('jewe_affiliate_qr_enabled', 'yes') === 'yes'): ?>
        <div class="jewe-card">
            <h3><?php _e('QR Code', 'jewe-affiliate-pro'); ?></h3>
            <p><?php _e('Genera un QR code per promuovere il tuo link offline.', 'jewe-affiliate-pro'); ?></p>
            <img src="https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=<?php echo urlencode(JEWE_Affiliate::get_referral_url($affiliate->id)); ?>" alt="QR Code" style="display: block; margin: 20px auto;">
        </div>
        <?php endif; ?>

        <div class="jewe-card">
            <h3><?php _e('Esporta Dati', 'jewe-affiliate-pro'); ?></h3>
            <p><?php _e('Scarica i tuoi dati in formato CSV o Excel.', 'jewe-affiliate-pro'); ?></p>
            <button class="jewe-export-btn" data-type="commissions" data-format="csv"><?php _e('Esporta Commissioni (CSV)', 'jewe-affiliate-pro'); ?></button>
            <button class="jewe-export-btn" data-type="traffic" data-format="csv"><?php _e('Esporta Traffico (CSV)', 'jewe-affiliate-pro'); ?></button>
        </div>
    </div>
</div>

<script>
function generateAffiliateLink() {
    var target = document.getElementById('jewe-link-target').value;
    if (!target) {
        target = '<?php echo home_url(); ?>';
    }
    var affiliateCode = '<?php echo esc_js($affiliate->affiliate_code); ?>';
    var separator = target.indexOf('?') > -1 ? '&' : '?';
    var fullLink = target + separator + 'ref=' + affiliateCode;

    var container = document.getElementById('jewe-generated-link');
    container.style.display = 'flex';
    container.querySelector('input').value = fullLink;
}
</script>
