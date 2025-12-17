<?php
/**
 * Admin Commissions Management
 *
 * @package WCFM_Affiliate_Pro
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class WCFM_Affiliate_Admin_Commissions {

    public function render(): void {
        $per_page = 20;
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $affiliate_id = isset($_GET['affiliate_id']) ? intval($_GET['affiliate_id']) : 0;

        $args = [
            'status' => $status,
            'affiliate_id' => $affiliate_id,
            'limit' => $per_page,
            'offset' => ($page - 1) * $per_page,
        ];

        $commissions = wcfm_affiliate_pro()->commissions->get_commissions($args);
        $total = wcfm_affiliate_pro()->commissions->count_commissions(['status' => $status, 'affiliate_id' => $affiliate_id]);
        ?>
        <div class="wrap">
            <h1><?php _e('Commissioni', 'wcfm-affiliate-pro'); ?></h1>

            <!-- Status Filter -->
            <ul class="subsubsub">
                <li>
                    <a href="<?php echo admin_url('admin.php?page=wcfm-affiliate-commissions'); ?>"
                       class="<?php echo empty($status) ? 'current' : ''; ?>">
                        <?php _e('Tutte', 'wcfm-affiliate-pro'); ?>
                        <span class="count">(<?php echo wcfm_affiliate_pro()->commissions->count_commissions(); ?>)</span>
                    </a> |
                </li>
                <li>
                    <a href="<?php echo admin_url('admin.php?page=wcfm-affiliate-commissions&status=pending'); ?>"
                       class="<?php echo $status === 'pending' ? 'current' : ''; ?>">
                        <?php _e('In Attesa', 'wcfm-affiliate-pro'); ?>
                        <span class="count">(<?php echo wcfm_affiliate_pro()->commissions->count_commissions(['status' => 'pending']); ?>)</span>
                    </a> |
                </li>
                <li>
                    <a href="<?php echo admin_url('admin.php?page=wcfm-affiliate-commissions&status=approved'); ?>"
                       class="<?php echo $status === 'approved' ? 'current' : ''; ?>">
                        <?php _e('Approvate', 'wcfm-affiliate-pro'); ?>
                        <span class="count">(<?php echo wcfm_affiliate_pro()->commissions->count_commissions(['status' => 'approved']); ?>)</span>
                    </a> |
                </li>
                <li>
                    <a href="<?php echo admin_url('admin.php?page=wcfm-affiliate-commissions&status=paid'); ?>"
                       class="<?php echo $status === 'paid' ? 'current' : ''; ?>">
                        <?php _e('Pagate', 'wcfm-affiliate-pro'); ?>
                        <span class="count">(<?php echo wcfm_affiliate_pro()->commissions->count_commissions(['status' => 'paid']); ?>)</span>
                    </a>
                </li>
            </ul>

            <!-- Bulk Actions -->
            <form method="post" id="commissions-form">
                <div class="tablenav top">
                    <div class="alignleft actions bulkactions">
                        <select name="bulk_action" id="bulk-action-selector-top">
                            <option value=""><?php _e('Azioni di gruppo', 'wcfm-affiliate-pro'); ?></option>
                            <option value="approve"><?php _e('Approva', 'wcfm-affiliate-pro'); ?></option>
                            <option value="reject"><?php _e('Rifiuta', 'wcfm-affiliate-pro'); ?></option>
                        </select>
                        <input type="submit" class="button action" value="<?php _e('Applica', 'wcfm-affiliate-pro'); ?>">
                    </div>
                </div>

                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <td class="manage-column column-cb check-column">
                                <input type="checkbox" id="cb-select-all-1">
                            </td>
                            <th><?php _e('ID', 'wcfm-affiliate-pro'); ?></th>
                            <th><?php _e('Affiliato', 'wcfm-affiliate-pro'); ?></th>
                            <th><?php _e('Ordine', 'wcfm-affiliate-pro'); ?></th>
                            <th><?php _e('Base', 'wcfm-affiliate-pro'); ?></th>
                            <th><?php _e('Tasso', 'wcfm-affiliate-pro'); ?></th>
                            <th><?php _e('Commissione', 'wcfm-affiliate-pro'); ?></th>
                            <th><?php _e('Stato', 'wcfm-affiliate-pro'); ?></th>
                            <th><?php _e('Data', 'wcfm-affiliate-pro'); ?></th>
                            <th><?php _e('Azioni', 'wcfm-affiliate-pro'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($commissions)): ?>
                            <tr>
                                <td colspan="10"><?php _e('Nessuna commissione trovata.', 'wcfm-affiliate-pro'); ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($commissions as $commission): ?>
                                <tr>
                                    <th scope="row" class="check-column">
                                        <input type="checkbox" name="commission_ids[]" value="<?php echo esc_attr($commission->id); ?>">
                                    </th>
                                    <td><?php echo esc_html($commission->id); ?></td>
                                    <td>
                                        <a href="<?php echo admin_url('admin.php?page=wcfm-affiliate-affiliates&action=view&id=' . $commission->affiliate_id); ?>">
                                            <?php echo esc_html($commission->affiliate_name); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="<?php echo admin_url('post.php?post=' . $commission->order_id . '&action=edit'); ?>">
                                            #<?php echo esc_html($commission->order_id); ?>
                                        </a>
                                    </td>
                                    <td><?php echo wc_price($commission->base_amount); ?></td>
                                    <td>
                                        <?php
                                        if ($commission->type === 'percentage') {
                                            echo esc_html($commission->rate) . '%';
                                        } else {
                                            echo wc_price($commission->rate);
                                        }
                                        ?>
                                    </td>
                                    <td><strong><?php echo wc_price($commission->commission_amount); ?></strong></td>
                                    <td>
                                        <span class="wcfm-affiliate-status wcfm-affiliate-status-<?php echo esc_attr($commission->status); ?>">
                                            <?php echo esc_html(ucfirst($commission->status)); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date_i18n(get_option('date_format'), strtotime($commission->date_created)); ?></td>
                                    <td>
                                        <?php if ($commission->status === 'pending'): ?>
                                            <button class="button button-small wcfm-commission-approve-btn"
                                                    data-id="<?php echo esc_attr($commission->id); ?>">
                                                <?php _e('Approva', 'wcfm-affiliate-pro'); ?>
                                            </button>
                                            <button class="button button-small wcfm-commission-reject-btn"
                                                    data-id="<?php echo esc_attr($commission->id); ?>">
                                                <?php _e('Rifiuta', 'wcfm-affiliate-pro'); ?>
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
            </form>

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
}
