<?php
/**
 * Uninstall WCFM Affiliate Pro
 *
 * Questo file viene eseguito SOLO quando il plugin viene ELIMINATO
 * (non durante la semplice disattivazione).
 *
 * IMPORTANTE:
 * - La DISATTIVAZIONE non elimina dati (plugin reversibile)
 * - Solo l'ELIMINAZIONE del plugin esegue questo file
 * - Rimuove SOLO le tabelle e opzioni con prefisso 'wcfm_aff_pro_'
 * - NON tocca le tabelle di WCFM Affiliate esistente o AffiliateWP
 *
 * @package WCFM_Affiliate_Pro
 * @since 1.0.0
 */

// Exit if accessed directly or not uninstalling
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Prefisso unico delle tabelle
define('WCFM_AFF_PRO_TABLE_PREFIX', 'wcfm_aff_pro_');

/**
 * Remove all plugin data on uninstall
 *
 * ATTENZIONE: Questa operazione è IRREVERSIBILE!
 * Tutti i dati degli affiliati verranno eliminati.
 */
function wcfm_affiliate_pro_uninstall() {
    global $wpdb;

    // Check if user wants to delete all data
    $delete_data = get_option('wcfm_aff_pro_delete_data_on_uninstall', 'no');

    if ($delete_data !== 'yes') {
        // Non eliminare i dati se l'utente non ha esplicitamente scelto di farlo
        return;
    }

    // Drop all plugin tables
    $tables = [
        $wpdb->prefix . WCFM_AFF_PRO_TABLE_PREFIX . 'affiliates',
        $wpdb->prefix . WCFM_AFF_PRO_TABLE_PREFIX . 'referrals',
        $wpdb->prefix . WCFM_AFF_PRO_TABLE_PREFIX . 'commissions',
        $wpdb->prefix . WCFM_AFF_PRO_TABLE_PREFIX . 'payouts',
        $wpdb->prefix . WCFM_AFF_PRO_TABLE_PREFIX . 'payout_items',
        $wpdb->prefix . WCFM_AFF_PRO_TABLE_PREFIX . 'clicks',
        $wpdb->prefix . WCFM_AFF_PRO_TABLE_PREFIX . 'visits',
        $wpdb->prefix . WCFM_AFF_PRO_TABLE_PREFIX . 'coupons',
        $wpdb->prefix . WCFM_AFF_PRO_TABLE_PREFIX . 'notifications',
        $wpdb->prefix . WCFM_AFF_PRO_TABLE_PREFIX . 'tiers',
        $wpdb->prefix . WCFM_AFF_PRO_TABLE_PREFIX . 'creatives',
        $wpdb->prefix . WCFM_AFF_PRO_TABLE_PREFIX . 'mlm',
    ];

    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS {$table}");
    }

    // Delete all plugin options (with unique prefix)
    $options = [
        'wcfm_aff_pro_general',
        'wcfm_aff_pro_commission',
        'wcfm_aff_pro_mlm',
        'wcfm_aff_pro_notifications',
        'wcfm_aff_pro_pages',
        'wcfm_aff_pro_design',
        'wcfm_aff_pro_version',
        'wcfm_aff_pro_db_version',
        'wcfm_aff_pro_delete_data_on_uninstall',
    ];

    foreach ($options as $option) {
        delete_option($option);
    }

    // Delete all transients with plugin prefix
    $wpdb->query(
        "DELETE FROM {$wpdb->options}
         WHERE option_name LIKE '_transient_wcfm_aff_pro_%'
         OR option_name LIKE '_transient_timeout_wcfm_aff_pro_%'"
    );

    // Remove user role
    remove_role('wcfm_aff_pro');

    // Remove capabilities from admin
    $admin = get_role('administrator');
    if ($admin) {
        $capabilities = [
            'manage_aff_pro',
            'approve_aff_pro',
            'manage_aff_pro_commissions',
            'manage_aff_pro_payouts',
            'view_aff_pro_reports',
            'edit_aff_pro_settings',
        ];

        foreach ($capabilities as $cap) {
            $admin->remove_cap($cap);
        }
    }

    // Remove capabilities from shop manager
    $shop_manager = get_role('shop_manager');
    if ($shop_manager) {
        $capabilities = [
            'manage_aff_pro',
            'approve_aff_pro',
            'manage_aff_pro_commissions',
            'view_aff_pro_reports',
        ];

        foreach ($capabilities as $cap) {
            $shop_manager->remove_cap($cap);
        }
    }

    // Delete plugin pages
    $pages = get_option('wcfm_aff_pro_pages', []);
    foreach ($pages as $page_id) {
        if ($page_id) {
            wp_delete_post($page_id, true);
        }
    }

    // Clear any scheduled cron jobs
    wp_clear_scheduled_hook('wcfm_aff_pro_daily_cron');
    wp_clear_scheduled_hook('wcfm_aff_pro_hourly_cron');

    // Flush rewrite rules
    flush_rewrite_rules();
}

// Run uninstall
wcfm_affiliate_pro_uninstall();
