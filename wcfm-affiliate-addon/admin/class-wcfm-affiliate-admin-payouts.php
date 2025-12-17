<?php
/**
 * Admin Payouts Management
 *
 * @package WCFM_Affiliate_Pro
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class WCFM_Affiliate_Admin_Payouts {

    public function render(): void {
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';

        if ($action === 'process' && isset($_GET['id'])) {
            $this->render_process_payout(intval($_GET['id']));
            return;
        }

        $per_page = 20;
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';

        $args = [
            'status' => $status,
            'limit' => $per_page,
            'offset' => ($page - 1) * $per_page,
        ];

        $payouts = wcfm_affiliate_pro()->payouts->get_payouts($args);
        $total = wcfm_affiliate_pro()->payouts->count_payouts(['status' => $status]);
        ?>
        <div class="wrap">
            <h1><?php _e('Pagamenti Affiliati', 'wcfm-affiliate-pro'); ?></h1>

            <!-- Status Filter -->
            <ul class="subsubsub">
                <li>
                    <a href="<?php echo admin_url('admin.php?page=wcfm-affiliate-payouts'); ?>"
                       class="<?php echo empty($status) ? 'current' : ''; ?>">
                        <?php _e('Tutti', 'wcfm-affiliate-pro'); ?>
                        <span class="count">(<?php echo wcfm_affiliate_pro()->payouts->count_payouts(); ?>)</span>
                    </a> |
                </li>
                <li>
                    <a href="<?php echo admin_url('admin.php?page=wcfm-affiliate-payouts&status=pending'); ?>"
                       class="<?php echo $status === 'pending' ? 'current' : ''; ?>">
                        <?php _e('In Attesa', 'wcfm-affiliate-pro'); ?>
                        <span class="count">(<?php echo wcfm_affiliate_pro()->payouts->count_payouts(['status' => 'pending']); ?>)</span>
                    </a> |
                </li>
                <li>
                    <a href="<?php echo admin_url('admin.php?page=wcfm-affiliate-payouts&status=completed'); ?>"
                       class="<?php echo $status === 'completed' ? 'current' : ''; ?>">
                        <?php _e('Completati', 'wcfm-affiliate-pro'); ?>
                        <span class="count">(<?php echo wcfm_affiliate_pro()->payouts->count_payouts(['status' => 'completed']); ?>)</span>
                    </a> |
                </li>
                <li>
                    <a href="<?php echo admin_url('admin.php?page=wcfm-affiliate-payouts&status=failed'); ?>"
                       class="<?php echo $status === 'failed' ? 'current' : ''; ?>">
                        <?php _e('Falliti', 'wcfm-affiliate-pro'); ?>
                        <span class="count">(<?php echo wcfm_affiliate_pro()->payouts->count_payouts(['status' => 'failed']); ?>)</span>
                    </a>
                </li>
            </ul>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('ID', 'wcfm-affiliate-pro'); ?></th>
                        <th><?php _e('Affiliato', 'wcfm-affiliate-pro'); ?></th>
                        <th><?php _e('Importo', 'wcfm-affiliate-pro'); ?></th>
                        <th><?php _e('Metodo', 'wcfm-affiliate-pro'); ?></th>
                        <th><?php _e('Email Pagamento', 'wcfm-affiliate-pro'); ?></th>
                        <th><?php _e('Stato', 'wcfm-affiliate-pro'); ?></th>
                        <th><?php _e('Transazione', 'wcfm-affiliate-pro'); ?></th>
                        <th><?php _e('Data', 'wcfm-affiliate-pro'); ?></th>
                        <th><?php _e('Azioni', 'wcfm-affiliate-pro'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($payouts)): ?>
                        <tr>
                            <td colspan="9"><?php _e('Nessun pagamento trovato.', 'wcfm-affiliate-pro'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($payouts as $payout): ?>
                            <tr>
                                <td><?php echo esc_html($payout->id); ?></td>
                                <td>
                                    <strong><?php echo esc_html($payout->affiliate_name); ?></strong>
                                    <br>
                                    <span class="description"><?php echo esc_html($payout->user_email); ?></span>
                                </td>
                                <td><strong><?php echo wc_price($payout->amount); ?></strong></td>
                                <td><?php echo esc_html(ucfirst(str_replace('_', ' ', $payout->payment_method))); ?></td>
                                <td><?php echo esc_html($payout->payment_email); ?></td>
                                <td>
                                    <span class="wcfm-affiliate-status wcfm-affiliate-status-<?php echo esc_attr($payout->status === 'completed' ? 'approved' : $payout->status); ?>">
                                        <?php echo esc_html(ucfirst($payout->status)); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo $payout->transaction_id ? esc_html($payout->transaction_id) : '-'; ?>
                                </td>
                                <td><?php echo date_i18n(get_option('date_format'), strtotime($payout->date_created)); ?></td>
                                <td>
                                    <?php if ($payout->status === 'pending'): ?>
                                        <a href="<?php echo admin_url('admin.php?page=wcfm-affiliate-payouts&action=process&id=' . $payout->id); ?>"
                                           class="button button-small button-primary">
                                            <?php _e('Elabora', 'wcfm-affiliate-pro'); ?>
                                        </a>
                                        <button class="button button-small wcfm-payout-cancel-btn"
                                                data-id="<?php echo esc_attr($payout->id); ?>">
                                            <?php _e('Annulla', 'wcfm-affiliate-pro'); ?>
                                        </button>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php
            $total_pages = ceil($total / $per_page);
            if ($total_pages > 1):
                echo '<div class="tablenav bottom"><div class="tablenav-pages">';
                echo paginate_links([
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'current' => $page,
                    'total' => $total_pages,
                ]);
                echo '</div></div>';
            endif;
            ?>
        </div>
        <?php
    }

    private function render_process_payout(int $payout_id): void {
        $payout = wcfm_affiliate_pro()->payouts->get_payout($payout_id);

        if (!$payout || $payout->status !== 'pending') {
            wp_die(__('Pagamento non trovato o già elaborato', 'wcfm-affiliate-pro'));
        }

        $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate($payout->affiliate_id);
        $user = get_userdata($affiliate->user_id);
        $payment_details = json_decode($payout->payment_details, true) ?: [];
        ?>
        <div class="wrap">
            <h1>
                <a href="<?php echo admin_url('admin.php?page=wcfm-affiliate-payouts'); ?>" class="page-title-action">
                    &larr; <?php _e('Torna alla lista', 'wcfm-affiliate-pro'); ?>
                </a>
                <?php printf(__('Elabora Pagamento #%d', 'wcfm-affiliate-pro'), $payout_id); ?>
            </h1>

            <div class="wcfm-affiliate-admin-row">
                <div class="wcfm-affiliate-admin-col">
                    <div class="wcfm-affiliate-admin-card">
                        <h3><?php _e('Dettagli Pagamento', 'wcfm-affiliate-pro'); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th><?php _e('Affiliato', 'wcfm-affiliate-pro'); ?></th>
                                <td><?php echo esc_html($user->display_name); ?> (<?php echo esc_html($user->user_email); ?>)</td>
                            </tr>
                            <tr>
                                <th><?php _e('Importo', 'wcfm-affiliate-pro'); ?></th>
                                <td><strong><?php echo wc_price($payout->amount); ?></strong></td>
                            </tr>
                            <tr>
                                <th><?php _e('Metodo', 'wcfm-affiliate-pro'); ?></th>
                                <td><?php echo esc_html(ucfirst(str_replace('_', ' ', $payout->payment_method))); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Email Pagamento', 'wcfm-affiliate-pro'); ?></th>
                                <td><?php echo esc_html($payout->payment_email); ?></td>
                            </tr>
                            <?php if ($payout->payment_method === 'bank_transfer' && !empty($payment_details)): ?>
                                <tr>
                                    <th><?php _e('Intestatario', 'wcfm-affiliate-pro'); ?></th>
                                    <td><?php echo esc_html($payment_details['account_name'] ?? ''); ?></td>
                                </tr>
                                <tr>
                                    <th><?php _e('IBAN', 'wcfm-affiliate-pro'); ?></th>
                                    <td><?php echo esc_html($payment_details['iban'] ?? ''); ?></td>
                                </tr>
                                <tr>
                                    <th><?php _e('BIC/SWIFT', 'wcfm-affiliate-pro'); ?></th>
                                    <td><?php echo esc_html($payment_details['bic_swift'] ?? ''); ?></td>
                                </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>

                <div class="wcfm-affiliate-admin-col">
                    <div class="wcfm-affiliate-admin-card">
                        <h3><?php _e('Conferma Pagamento', 'wcfm-affiliate-pro'); ?></h3>
                        <form id="process-payout-form">
                            <input type="hidden" name="payout_id" value="<?php echo esc_attr($payout_id); ?>">
                            <?php wp_nonce_field('wcfm_affiliate_admin_nonce', 'nonce'); ?>

                            <table class="form-table">
                                <tr>
                                    <th><?php _e('ID Transazione', 'wcfm-affiliate-pro'); ?></th>
                                    <td>
                                        <input type="text" name="transaction_id" class="regular-text"
                                               placeholder="<?php _e('ID transazione PayPal/Bonifico', 'wcfm-affiliate-pro'); ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php _e('Note', 'wcfm-affiliate-pro'); ?></th>
                                    <td>
                                        <textarea name="notes" rows="3" class="large-text"
                                                  placeholder="<?php _e('Note opzionali...', 'wcfm-affiliate-pro'); ?>"></textarea>
                                    </td>
                                </tr>
                            </table>

                            <p class="submit">
                                <button type="submit" class="button button-primary button-large">
                                    <?php _e('Conferma Pagamento', 'wcfm-affiliate-pro'); ?>
                                </button>
                                <button type="button" class="button button-large wcfm-payout-cancel-btn"
                                        data-id="<?php echo esc_attr($payout_id); ?>">
                                    <?php _e('Annulla Richiesta', 'wcfm-affiliate-pro'); ?>
                                </button>
                            </p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
