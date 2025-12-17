<?php
/**
 * Admin Affiliates Management
 *
 * @package WCFM_Affiliate_Pro
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class WCFM_Affiliate_Admin_Affiliates {

    public function render(): void {
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';

        switch ($action) {
            case 'view':
                $this->render_view_affiliate();
                break;
            case 'edit':
                $this->render_edit_affiliate();
                break;
            default:
                $this->render_list();
        }
    }

    private function render_list(): void {
        $per_page = get_user_meta(get_current_user_id(), 'wcfm_affiliates_per_page', true) ?: 20;
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

        $args = [
            'status' => $status,
            'search' => $search,
            'limit' => $per_page,
            'offset' => ($page - 1) * $per_page,
        ];

        $affiliates = wcfm_affiliate_pro()->affiliates->get_affiliates($args);
        $total = wcfm_affiliate_pro()->affiliates->count_affiliates(['status' => $status]);
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e('Affiliati', 'wcfm-affiliate-pro'); ?></h1>

            <hr class="wp-header-end">

            <!-- Status Filter -->
            <ul class="subsubsub">
                <li>
                    <a href="<?php echo admin_url('admin.php?page=wcfm-affiliate-affiliates'); ?>"
                       class="<?php echo empty($status) ? 'current' : ''; ?>">
                        <?php _e('Tutti', 'wcfm-affiliate-pro'); ?>
                        <span class="count">(<?php echo wcfm_affiliate_pro()->affiliates->count_affiliates(); ?>)</span>
                    </a> |
                </li>
                <li>
                    <a href="<?php echo admin_url('admin.php?page=wcfm-affiliate-affiliates&status=active'); ?>"
                       class="<?php echo $status === 'active' ? 'current' : ''; ?>">
                        <?php _e('Attivi', 'wcfm-affiliate-pro'); ?>
                        <span class="count">(<?php echo wcfm_affiliate_pro()->affiliates->count_affiliates(['status' => 'active']); ?>)</span>
                    </a> |
                </li>
                <li>
                    <a href="<?php echo admin_url('admin.php?page=wcfm-affiliate-affiliates&status=pending'); ?>"
                       class="<?php echo $status === 'pending' ? 'current' : ''; ?>">
                        <?php _e('In Attesa', 'wcfm-affiliate-pro'); ?>
                        <span class="count">(<?php echo wcfm_affiliate_pro()->affiliates->count_affiliates(['status' => 'pending']); ?>)</span>
                    </a> |
                </li>
                <li>
                    <a href="<?php echo admin_url('admin.php?page=wcfm-affiliate-affiliates&status=suspended'); ?>"
                       class="<?php echo $status === 'suspended' ? 'current' : ''; ?>">
                        <?php _e('Sospesi', 'wcfm-affiliate-pro'); ?>
                        <span class="count">(<?php echo wcfm_affiliate_pro()->affiliates->count_affiliates(['status' => 'suspended']); ?>)</span>
                    </a>
                </li>
            </ul>

            <!-- Search Form -->
            <form method="get" class="search-form">
                <input type="hidden" name="page" value="wcfm-affiliate-affiliates">
                <?php if ($status): ?>
                    <input type="hidden" name="status" value="<?php echo esc_attr($status); ?>">
                <?php endif; ?>
                <p class="search-box">
                    <label class="screen-reader-text"><?php _e('Cerca affiliati', 'wcfm-affiliate-pro'); ?></label>
                    <input type="search" name="s" value="<?php echo esc_attr($search); ?>"
                           placeholder="<?php _e('Cerca per nome, email o codice...', 'wcfm-affiliate-pro'); ?>">
                    <input type="submit" class="button" value="<?php _e('Cerca', 'wcfm-affiliate-pro'); ?>">
                </p>
            </form>

            <!-- Affiliates Table -->
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <td class="manage-column column-cb check-column">
                            <input type="checkbox" id="cb-select-all-1">
                        </td>
                        <th><?php _e('Affiliato', 'wcfm-affiliate-pro'); ?></th>
                        <th><?php _e('Codice', 'wcfm-affiliate-pro'); ?></th>
                        <th><?php _e('Stato', 'wcfm-affiliate-pro'); ?></th>
                        <th><?php _e('Referral', 'wcfm-affiliate-pro'); ?></th>
                        <th><?php _e('Guadagni', 'wcfm-affiliate-pro'); ?></th>
                        <th><?php _e('Saldo', 'wcfm-affiliate-pro'); ?></th>
                        <th><?php _e('Registrato', 'wcfm-affiliate-pro'); ?></th>
                        <th><?php _e('Azioni', 'wcfm-affiliate-pro'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($affiliates)): ?>
                        <tr>
                            <td colspan="9"><?php _e('Nessun affiliato trovato.', 'wcfm-affiliate-pro'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($affiliates as $affiliate): ?>
                            <tr>
                                <th scope="row" class="check-column">
                                    <input type="checkbox" name="affiliate_ids[]" value="<?php echo esc_attr($affiliate->id); ?>">
                                </th>
                                <td>
                                    <strong>
                                        <a href="<?php echo admin_url('admin.php?page=wcfm-affiliate-affiliates&action=view&id=' . $affiliate->id); ?>">
                                            <?php echo esc_html($affiliate->display_name); ?>
                                        </a>
                                    </strong>
                                    <br>
                                    <span class="description"><?php echo esc_html($affiliate->user_email); ?></span>
                                </td>
                                <td><code><?php echo esc_html($affiliate->affiliate_code); ?></code></td>
                                <td>
                                    <span class="wcfm-affiliate-status wcfm-affiliate-status-<?php echo esc_attr($affiliate->status); ?>">
                                        <?php echo esc_html(ucfirst($affiliate->status)); ?>
                                    </span>
                                </td>
                                <td><?php echo number_format($affiliate->referrals_count); ?></td>
                                <td><?php echo wc_price($affiliate->earnings_total); ?></td>
                                <td><?php echo wc_price($affiliate->earnings_balance); ?></td>
                                <td><?php echo date_i18n(get_option('date_format'), strtotime($affiliate->date_created)); ?></td>
                                <td>
                                    <?php if ($affiliate->status === 'pending'): ?>
                                        <button class="button button-small wcfm-affiliate-approve-btn"
                                                data-id="<?php echo esc_attr($affiliate->id); ?>">
                                            <?php _e('Approva', 'wcfm-affiliate-pro'); ?>
                                        </button>
                                        <button class="button button-small wcfm-affiliate-reject-btn"
                                                data-id="<?php echo esc_attr($affiliate->id); ?>">
                                            <?php _e('Rifiuta', 'wcfm-affiliate-pro'); ?>
                                        </button>
                                    <?php else: ?>
                                        <a href="<?php echo admin_url('admin.php?page=wcfm-affiliate-affiliates&action=view&id=' . $affiliate->id); ?>"
                                           class="button button-small">
                                            <?php _e('Visualizza', 'wcfm-affiliate-pro'); ?>
                                        </a>
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

    private function render_view_affiliate(): void {
        $affiliate_id = intval($_GET['id'] ?? 0);
        $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate($affiliate_id);

        if (!$affiliate) {
            wp_die(__('Affiliato non trovato', 'wcfm-affiliate-pro'));
        }

        $user = get_userdata($affiliate->user_id);
        $stats = wcfm_affiliate_pro()->affiliates->get_affiliate_stats($affiliate_id);
        ?>
        <div class="wrap">
            <h1>
                <a href="<?php echo admin_url('admin.php?page=wcfm-affiliate-affiliates'); ?>" class="page-title-action">
                    &larr; <?php _e('Torna alla lista', 'wcfm-affiliate-pro'); ?>
                </a>
                <?php echo esc_html($user->display_name); ?>
                <span class="wcfm-affiliate-status wcfm-affiliate-status-<?php echo esc_attr($affiliate->status); ?>">
                    <?php echo esc_html(ucfirst($affiliate->status)); ?>
                </span>
            </h1>

            <div class="wcfm-affiliate-admin-row">
                <div class="wcfm-affiliate-admin-col">
                    <div class="wcfm-affiliate-admin-card">
                        <h3><?php _e('Informazioni Affiliato', 'wcfm-affiliate-pro'); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th><?php _e('Nome', 'wcfm-affiliate-pro'); ?></th>
                                <td><?php echo esc_html($user->display_name); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Email', 'wcfm-affiliate-pro'); ?></th>
                                <td><?php echo esc_html($user->user_email); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Codice Affiliato', 'wcfm-affiliate-pro'); ?></th>
                                <td><code><?php echo esc_html($affiliate->affiliate_code); ?></code></td>
                            </tr>
                            <tr>
                                <th><?php _e('Sito Web', 'wcfm-affiliate-pro'); ?></th>
                                <td>
                                    <?php if ($affiliate->website_url): ?>
                                        <a href="<?php echo esc_url($affiliate->website_url); ?>" target="_blank">
                                            <?php echo esc_html($affiliate->website_url); ?>
                                        </a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Email Pagamento', 'wcfm-affiliate-pro'); ?></th>
                                <td><?php echo esc_html($affiliate->payment_email); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Metodo Pagamento', 'wcfm-affiliate-pro'); ?></th>
                                <td><?php echo esc_html(ucfirst(str_replace('_', ' ', $affiliate->payment_method))); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Registrato il', 'wcfm-affiliate-pro'); ?></th>
                                <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($affiliate->date_created)); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Ultimo accesso', 'wcfm-affiliate-pro'); ?></th>
                                <td>
                                    <?php echo $affiliate->last_login ?
                                        date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($affiliate->last_login)) :
                                        __('Mai', 'wcfm-affiliate-pro'); ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="wcfm-affiliate-admin-col">
                    <div class="wcfm-affiliate-admin-card">
                        <h3><?php _e('Statistiche', 'wcfm-affiliate-pro'); ?></h3>
                        <div class="wcfm-affiliate-admin-stats">
                            <div class="wcfm-affiliate-admin-stat">
                                <span class="wcfm-affiliate-admin-stat-value"><?php echo wc_price($stats['earnings_balance']); ?></span>
                                <span class="wcfm-affiliate-admin-stat-label"><?php _e('Saldo', 'wcfm-affiliate-pro'); ?></span>
                            </div>
                            <div class="wcfm-affiliate-admin-stat">
                                <span class="wcfm-affiliate-admin-stat-value"><?php echo wc_price($stats['earnings_paid']); ?></span>
                                <span class="wcfm-affiliate-admin-stat-label"><?php _e('Pagato', 'wcfm-affiliate-pro'); ?></span>
                            </div>
                            <div class="wcfm-affiliate-admin-stat">
                                <span class="wcfm-affiliate-admin-stat-value"><?php echo wc_price($stats['earnings_total']); ?></span>
                                <span class="wcfm-affiliate-admin-stat-label"><?php _e('Totale', 'wcfm-affiliate-pro'); ?></span>
                            </div>
                            <div class="wcfm-affiliate-admin-stat">
                                <span class="wcfm-affiliate-admin-stat-value"><?php echo number_format($stats['referrals_total']); ?></span>
                                <span class="wcfm-affiliate-admin-stat-label"><?php _e('Referral', 'wcfm-affiliate-pro'); ?></span>
                            </div>
                            <div class="wcfm-affiliate-admin-stat">
                                <span class="wcfm-affiliate-admin-stat-value"><?php echo number_format($stats['visits']); ?></span>
                                <span class="wcfm-affiliate-admin-stat-label"><?php _e('Visite', 'wcfm-affiliate-pro'); ?></span>
                            </div>
                            <div class="wcfm-affiliate-admin-stat">
                                <span class="wcfm-affiliate-admin-stat-value"><?php echo $stats['conversion_rate']; ?>%</span>
                                <span class="wcfm-affiliate-admin-stat-label"><?php _e('Conversione', 'wcfm-affiliate-pro'); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="wcfm-affiliate-admin-card">
                        <h3><?php _e('Azioni', 'wcfm-affiliate-pro'); ?></h3>
                        <div class="wcfm-affiliate-admin-actions">
                            <?php if ($affiliate->status === 'pending'): ?>
                                <button class="button button-primary wcfm-affiliate-approve-btn"
                                        data-id="<?php echo esc_attr($affiliate->id); ?>">
                                    <?php _e('Approva', 'wcfm-affiliate-pro'); ?>
                                </button>
                                <button class="button wcfm-affiliate-reject-btn"
                                        data-id="<?php echo esc_attr($affiliate->id); ?>">
                                    <?php _e('Rifiuta', 'wcfm-affiliate-pro'); ?>
                                </button>
                            <?php elseif ($affiliate->status === 'active'): ?>
                                <button class="button wcfm-affiliate-suspend-btn"
                                        data-id="<?php echo esc_attr($affiliate->id); ?>">
                                    <?php _e('Sospendi', 'wcfm-affiliate-pro'); ?>
                                </button>
                            <?php elseif ($affiliate->status === 'suspended'): ?>
                                <button class="button button-primary wcfm-affiliate-approve-btn"
                                        data-id="<?php echo esc_attr($affiliate->id); ?>">
                                    <?php _e('Riattiva', 'wcfm-affiliate-pro'); ?>
                                </button>
                            <?php endif; ?>
                            <button class="button button-link-delete wcfm-affiliate-delete-btn"
                                    data-id="<?php echo esc_attr($affiliate->id); ?>">
                                <?php _e('Elimina', 'wcfm-affiliate-pro'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_edit_affiliate(): void {
        // Edit form implementation
    }
}
