<?php
/**
 * Migration Handler - WCFM Affiliate → Affiliate Pro
 *
 * Migra affiliati e attività dal plugin WCFM Affiliate esistente
 * al nuovo sistema Affiliate Pro in modo sicuro e reversibile.
 *
 * Supporta migrazione da:
 * - WCFM Affiliate (wcfm_affiliate_* tables)
 * - AffiliateWP (affiliate_wp_* tables)
 *
 * @package WCFM_Affiliate_Pro
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class WCFM_Affiliate_Migration {

    /**
     * Migration batch size
     */
    private const BATCH_SIZE = 50;

    /**
     * Source plugin type
     */
    private string $source_plugin = '';

    /**
     * Migration log
     */
    private array $log = [];

    /**
     * Dry run mode
     */
    private bool $dry_run = false;

    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks(): void {
        // AJAX handlers
        add_action('wp_ajax_wcfm_aff_pro_check_migration', [$this, 'ajax_check_migration']);
        add_action('wp_ajax_wcfm_aff_pro_start_migration', [$this, 'ajax_start_migration']);
        add_action('wp_ajax_wcfm_aff_pro_migrate_batch', [$this, 'ajax_migrate_batch']);
        add_action('wp_ajax_wcfm_aff_pro_rollback_migration', [$this, 'ajax_rollback_migration']);
        add_action('wp_ajax_wcfm_aff_pro_get_migration_log', [$this, 'ajax_get_migration_log']);
    }

    /**
     * Detect available source plugins for migration
     */
    public function detect_source_plugins(): array {
        global $wpdb;

        $sources = [];

        // Check WCFM Affiliate
        $wcfm_table = $wpdb->prefix . 'wcfm_affiliate_affiliates';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$wcfm_table}'") === $wcfm_table) {
            $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wcfm_table}");
            $sources['wcfm_affiliate'] = [
                'name' => 'WCFM Affiliate',
                'affiliates' => (int) $count,
                'tables' => $this->get_wcfm_affiliate_tables(),
                'available' => true,
            ];
        }

        // Check AffiliateWP
        $affwp_table = $wpdb->prefix . 'affiliate_wp_affiliates';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$affwp_table}'") === $affwp_table) {
            $count = $wpdb->get_var("SELECT COUNT(*) FROM {$affwp_table}");
            $sources['affiliatewp'] = [
                'name' => 'AffiliateWP',
                'affiliates' => (int) $count,
                'tables' => $this->get_affiliatewp_tables(),
                'available' => true,
            ];
        }

        // Check YITH WooCommerce Affiliates
        $yith_table = $wpdb->prefix . 'yith_wcaf_affiliates';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$yith_table}'") === $yith_table) {
            $count = $wpdb->get_var("SELECT COUNT(*) FROM {$yith_table}");
            $sources['yith_affiliates'] = [
                'name' => 'YITH WooCommerce Affiliates',
                'affiliates' => (int) $count,
                'tables' => $this->get_yith_affiliate_tables(),
                'available' => true,
            ];
        }

        return $sources;
    }

    /**
     * Get WCFM Affiliate tables
     */
    private function get_wcfm_affiliate_tables(): array {
        global $wpdb;
        $prefix = $wpdb->prefix . 'wcfm_affiliate_';

        return [
            'affiliates' => $prefix . 'affiliates',
            'referrals' => $prefix . 'referrals',
            'visits' => $prefix . 'visits',
            'payouts' => $prefix . 'payouts',
            'creatives' => $prefix . 'creatives',
        ];
    }

    /**
     * Get AffiliateWP tables
     */
    private function get_affiliatewp_tables(): array {
        global $wpdb;
        $prefix = $wpdb->prefix . 'affiliate_wp_';

        return [
            'affiliates' => $prefix . 'affiliates',
            'referrals' => $prefix . 'referrals',
            'visits' => $prefix . 'visits',
            'payouts' => $prefix . 'payouts',
            'creatives' => $prefix . 'creatives',
            'affiliatemeta' => $prefix . 'affiliatemeta',
        ];
    }

    /**
     * Get YITH Affiliate tables
     */
    private function get_yith_affiliate_tables(): array {
        global $wpdb;
        $prefix = $wpdb->prefix . 'yith_wcaf_';

        return [
            'affiliates' => $prefix . 'affiliates',
            'commissions' => $prefix . 'commissions',
            'clicks' => $prefix . 'clicks',
            'payments' => $prefix . 'payments',
        ];
    }

    /**
     * Get migration statistics
     */
    public function get_migration_stats(string $source): array {
        global $wpdb;

        $stats = [
            'affiliates' => 0,
            'referrals' => 0,
            'visits' => 0,
            'commissions' => 0,
            'payouts' => 0,
            'already_migrated' => 0,
        ];

        switch ($source) {
            case 'wcfm_affiliate':
                $tables = $this->get_wcfm_affiliate_tables();
                break;
            case 'affiliatewp':
                $tables = $this->get_affiliatewp_tables();
                break;
            case 'yith_affiliates':
                $tables = $this->get_yith_affiliate_tables();
                break;
            default:
                return $stats;
        }

        // Count records
        foreach ($tables as $key => $table) {
            if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table) {
                $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
                if (isset($stats[$key])) {
                    $stats[$key] = (int) $count;
                }
            }
        }

        // Check already migrated
        $migrated_table = $wpdb->prefix . 'wcfm_aff_pro_affiliates';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$migrated_table}'") === $migrated_table) {
            $stats['already_migrated'] = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$migrated_table} WHERE migrated_from = %s",
                $source
            );
        }

        return $stats;
    }

    /**
     * Start migration process
     */
    public function start_migration(string $source, array $options = []): array {
        $this->source_plugin = $source;
        $this->dry_run = $options['dry_run'] ?? false;

        // Create migration record
        $migration_id = $this->create_migration_record($source, $options);

        if (!$migration_id) {
            return ['success' => false, 'message' => __('Impossibile creare record di migrazione', 'wcfm-affiliate-pro')];
        }

        // Get total items to migrate
        $stats = $this->get_migration_stats($source);
        $total = $stats['affiliates'] + $stats['referrals'] + $stats['visits'] + $stats['payouts'];

        return [
            'success' => true,
            'migration_id' => $migration_id,
            'total_items' => $total,
            'stats' => $stats,
            'message' => sprintf(__('Migrazione avviata. %d elementi da migrare.', 'wcfm-affiliate-pro'), $total),
        ];
    }

    /**
     * Create migration record
     */
    private function create_migration_record(string $source, array $options): ?int {
        global $wpdb;

        $result = $wpdb->insert(
            $wpdb->prefix . 'wcfm_aff_pro_migrations',
            [
                'source_plugin' => $source,
                'status' => 'in_progress',
                'options' => json_encode($options),
                'started_at' => current_time('mysql'),
                'created_by' => get_current_user_id(),
            ],
            ['%s', '%s', '%s', '%s', '%d']
        );

        return $result ? $wpdb->insert_id : null;
    }

    /**
     * Migrate a batch of records
     */
    public function migrate_batch(int $migration_id, string $type, int $offset): array {
        global $wpdb;

        // Get migration record
        $migration = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wcfm_aff_pro_migrations WHERE id = %d",
            $migration_id
        ));

        if (!$migration) {
            return ['success' => false, 'message' => __('Migrazione non trovata', 'wcfm-affiliate-pro')];
        }

        $this->source_plugin = $migration->source_plugin;
        $options = json_decode($migration->options, true) ?: [];
        $this->dry_run = $options['dry_run'] ?? false;

        $migrated = 0;
        $errors = [];

        switch ($type) {
            case 'affiliates':
                $result = $this->migrate_affiliates($offset, self::BATCH_SIZE);
                break;
            case 'referrals':
                $result = $this->migrate_referrals($offset, self::BATCH_SIZE);
                break;
            case 'visits':
                $result = $this->migrate_visits($offset, self::BATCH_SIZE);
                break;
            case 'commissions':
                $result = $this->migrate_commissions($offset, self::BATCH_SIZE);
                break;
            case 'payouts':
                $result = $this->migrate_payouts($offset, self::BATCH_SIZE);
                break;
            default:
                return ['success' => false, 'message' => __('Tipo migrazione non valido', 'wcfm-affiliate-pro')];
        }

        // Update migration record
        $this->update_migration_progress($migration_id, $type, $result['migrated'], $result['errors']);

        return [
            'success' => true,
            'type' => $type,
            'offset' => $offset,
            'migrated' => $result['migrated'],
            'errors' => $result['errors'],
            'has_more' => $result['has_more'],
            'next_offset' => $offset + self::BATCH_SIZE,
        ];
    }

    /**
     * Migrate affiliates
     */
    private function migrate_affiliates(int $offset, int $limit): array {
        global $wpdb;

        $migrated = 0;
        $errors = [];

        switch ($this->source_plugin) {
            case 'wcfm_affiliate':
                $results = $this->migrate_wcfm_affiliates($offset, $limit);
                break;
            case 'affiliatewp':
                $results = $this->migrate_affiliatewp_affiliates($offset, $limit);
                break;
            case 'yith_affiliates':
                $results = $this->migrate_yith_affiliates($offset, $limit);
                break;
            default:
                return ['migrated' => 0, 'errors' => ['Fonte non supportata'], 'has_more' => false];
        }

        return $results;
    }

    /**
     * Migrate WCFM Affiliate affiliates
     */
    private function migrate_wcfm_affiliates(int $offset, int $limit): array {
        global $wpdb;

        $source_table = $wpdb->prefix . 'wcfm_affiliate_affiliates';
        $target_table = $wpdb->prefix . 'wcfm_aff_pro_affiliates';

        $affiliates = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$source_table} ORDER BY id LIMIT %d OFFSET %d",
            $limit,
            $offset
        ));

        $migrated = 0;
        $errors = [];

        foreach ($affiliates as $affiliate) {
            // Check if already migrated
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$target_table} WHERE user_id = %d",
                $affiliate->user_id
            ));

            if ($exists) {
                $this->log("Affiliato #{$affiliate->id} già migrato (user_id: {$affiliate->user_id})");
                continue;
            }

            // Map status
            $status = $this->map_affiliate_status($affiliate->status ?? 'pending');

            // Generate unique code if not present
            $code = $affiliate->affiliate_code ?? $this->generate_affiliate_code($affiliate->user_id);

            $data = [
                'user_id' => $affiliate->user_id,
                'affiliate_code' => $code,
                'status' => $status,
                'payment_email' => $affiliate->payment_email ?? '',
                'payment_method' => $affiliate->payment_method ?? 'bank_transfer',
                'rate' => $affiliate->rate ?? null,
                'rate_type' => $affiliate->rate_type ?? null,
                'earnings_total' => $affiliate->earnings ?? 0,
                'earnings_paid' => $affiliate->paid ?? 0,
                'earnings_unpaid' => ($affiliate->earnings ?? 0) - ($affiliate->paid ?? 0),
                'referrals_count' => $affiliate->referrals ?? 0,
                'visits_count' => $affiliate->visits ?? 0,
                'parent_affiliate_id' => $affiliate->parent_id ?? null,
                'migrated_from' => 'wcfm_affiliate',
                'migrated_id' => $affiliate->id,
                'created_at' => $affiliate->date_registered ?? current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ];

            if ($this->dry_run) {
                $this->log("DRY RUN: Migrerebbe affiliato #{$affiliate->id}");
                $migrated++;
                continue;
            }

            $result = $wpdb->insert($target_table, $data);

            if ($result) {
                $new_id = $wpdb->insert_id;
                $this->log("Affiliato #{$affiliate->id} migrato con successo (nuovo ID: {$new_id})");

                // Migrate affiliate meta
                $this->migrate_affiliate_meta($affiliate->id, $new_id, 'wcfm_affiliate');

                $migrated++;
            } else {
                $errors[] = "Errore migrazione affiliato #{$affiliate->id}: " . $wpdb->last_error;
                $this->log("ERRORE: Migrazione affiliato #{$affiliate->id} fallita - " . $wpdb->last_error);
            }
        }

        return [
            'migrated' => $migrated,
            'errors' => $errors,
            'has_more' => count($affiliates) === $limit,
        ];
    }

    /**
     * Migrate AffiliateWP affiliates
     */
    private function migrate_affiliatewp_affiliates(int $offset, int $limit): array {
        global $wpdb;

        $source_table = $wpdb->prefix . 'affiliate_wp_affiliates';
        $target_table = $wpdb->prefix . 'wcfm_aff_pro_affiliates';

        $affiliates = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$source_table} ORDER BY affiliate_id LIMIT %d OFFSET %d",
            $limit,
            $offset
        ));

        $migrated = 0;
        $errors = [];

        foreach ($affiliates as $affiliate) {
            // Check if already migrated
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$target_table} WHERE user_id = %d",
                $affiliate->user_id
            ));

            if ($exists) {
                continue;
            }

            // Map status
            $status_map = [
                'active' => 'active',
                'inactive' => 'inactive',
                'pending' => 'pending',
                'rejected' => 'rejected',
            ];
            $status = $status_map[$affiliate->status] ?? 'pending';

            $data = [
                'user_id' => $affiliate->user_id,
                'affiliate_code' => $this->generate_affiliate_code($affiliate->user_id),
                'status' => $status,
                'payment_email' => $affiliate->payment_email ?? '',
                'rate' => $affiliate->rate ?? null,
                'rate_type' => $affiliate->rate_type ?? null,
                'earnings_total' => $affiliate->earnings ?? 0,
                'earnings_unpaid' => $affiliate->unpaid_earnings ?? 0,
                'referrals_count' => $affiliate->referrals ?? 0,
                'visits_count' => $affiliate->visits ?? 0,
                'migrated_from' => 'affiliatewp',
                'migrated_id' => $affiliate->affiliate_id,
                'created_at' => $affiliate->date_registered ?? current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ];

            if ($this->dry_run) {
                $migrated++;
                continue;
            }

            $result = $wpdb->insert($target_table, $data);

            if ($result) {
                $new_id = $wpdb->insert_id;

                // Migrate affiliate meta from affiliatemeta table
                $this->migrate_affiliatewp_meta($affiliate->affiliate_id, $new_id);

                $migrated++;
            } else {
                $errors[] = "Errore migrazione affiliato AffiliateWP #{$affiliate->affiliate_id}";
            }
        }

        return [
            'migrated' => $migrated,
            'errors' => $errors,
            'has_more' => count($affiliates) === $limit,
        ];
    }

    /**
     * Migrate YITH affiliates
     */
    private function migrate_yith_affiliates(int $offset, int $limit): array {
        global $wpdb;

        $source_table = $wpdb->prefix . 'yith_wcaf_affiliates';
        $target_table = $wpdb->prefix . 'wcfm_aff_pro_affiliates';

        $affiliates = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$source_table} ORDER BY ID LIMIT %d OFFSET %d",
            $limit,
            $offset
        ));

        $migrated = 0;
        $errors = [];

        foreach ($affiliates as $affiliate) {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$target_table} WHERE user_id = %d",
                $affiliate->user_id
            ));

            if ($exists) {
                continue;
            }

            $status_map = [
                1 => 'active',
                0 => 'pending',
                -1 => 'rejected',
            ];
            $status = $status_map[$affiliate->enabled] ?? 'pending';

            $data = [
                'user_id' => $affiliate->user_id,
                'affiliate_code' => $affiliate->token ?? $this->generate_affiliate_code($affiliate->user_id),
                'status' => $status,
                'payment_email' => $affiliate->payment_email ?? '',
                'rate' => $affiliate->rate ?? null,
                'earnings_total' => $affiliate->earnings ?? 0,
                'earnings_paid' => $affiliate->paid ?? 0,
                'migrated_from' => 'yith_affiliates',
                'migrated_id' => $affiliate->ID,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ];

            if ($this->dry_run) {
                $migrated++;
                continue;
            }

            $result = $wpdb->insert($target_table, $data);

            if ($result) {
                $migrated++;
            } else {
                $errors[] = "Errore migrazione affiliato YITH #{$affiliate->ID}";
            }
        }

        return [
            'migrated' => $migrated,
            'errors' => $errors,
            'has_more' => count($affiliates) === $limit,
        ];
    }

    /**
     * Migrate referrals
     */
    private function migrate_referrals(int $offset, int $limit): array {
        global $wpdb;

        $migrated = 0;
        $errors = [];

        switch ($this->source_plugin) {
            case 'wcfm_affiliate':
                $source_table = $wpdb->prefix . 'wcfm_affiliate_referrals';
                $id_field = 'id';
                break;
            case 'affiliatewp':
                $source_table = $wpdb->prefix . 'affiliate_wp_referrals';
                $id_field = 'referral_id';
                break;
            default:
                return ['migrated' => 0, 'errors' => ['Fonte non supportata'], 'has_more' => false];
        }

        $target_table = $wpdb->prefix . 'wcfm_aff_pro_referrals';

        $referrals = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$source_table} ORDER BY {$id_field} LIMIT %d OFFSET %d",
            $limit,
            $offset
        ));

        foreach ($referrals as $referral) {
            // Get new affiliate ID
            $new_affiliate_id = $this->get_migrated_affiliate_id($referral->affiliate_id ?? 0);

            if (!$new_affiliate_id) {
                $errors[] = "Affiliato non trovato per referral #{$referral->$id_field}";
                continue;
            }

            // Map status
            $status = $this->map_referral_status($referral->status ?? 'pending');

            $data = [
                'affiliate_id' => $new_affiliate_id,
                'order_id' => $referral->reference ?? $referral->order_id ?? 0,
                'customer_id' => $referral->customer_id ?? 0,
                'amount' => $referral->amount ?? 0,
                'status' => $status,
                'type' => $referral->type ?? 'sale',
                'description' => $referral->description ?? '',
                'migrated_from' => $this->source_plugin,
                'migrated_id' => $referral->$id_field,
                'created_at' => $referral->date ?? current_time('mysql'),
            ];

            if ($this->dry_run) {
                $migrated++;
                continue;
            }

            $result = $wpdb->insert($target_table, $data);

            if ($result) {
                $migrated++;
            } else {
                $errors[] = "Errore migrazione referral #{$referral->$id_field}";
            }
        }

        return [
            'migrated' => $migrated,
            'errors' => $errors,
            'has_more' => count($referrals) === $limit,
        ];
    }

    /**
     * Migrate visits
     */
    private function migrate_visits(int $offset, int $limit): array {
        global $wpdb;

        $migrated = 0;
        $errors = [];

        switch ($this->source_plugin) {
            case 'wcfm_affiliate':
                $source_table = $wpdb->prefix . 'wcfm_affiliate_visits';
                break;
            case 'affiliatewp':
                $source_table = $wpdb->prefix . 'affiliate_wp_visits';
                break;
            default:
                return ['migrated' => 0, 'errors' => [], 'has_more' => false];
        }

        $target_table = $wpdb->prefix . 'wcfm_aff_pro_visits';

        $visits = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$source_table} ORDER BY visit_id LIMIT %d OFFSET %d",
            $limit,
            $offset
        ));

        foreach ($visits as $visit) {
            $new_affiliate_id = $this->get_migrated_affiliate_id($visit->affiliate_id ?? 0);

            if (!$new_affiliate_id) {
                continue;
            }

            $data = [
                'affiliate_id' => $new_affiliate_id,
                'referral_id' => $visit->referral_id ?? null,
                'url' => $visit->url ?? '',
                'referrer' => $visit->referrer ?? '',
                'ip_address' => $visit->ip ?? '',
                'converted' => $visit->referral_id ? 1 : 0,
                'migrated_from' => $this->source_plugin,
                'created_at' => $visit->date ?? current_time('mysql'),
            ];

            if ($this->dry_run) {
                $migrated++;
                continue;
            }

            $result = $wpdb->insert($target_table, $data);

            if ($result) {
                $migrated++;
            }
        }

        return [
            'migrated' => $migrated,
            'errors' => $errors,
            'has_more' => count($visits) === $limit,
        ];
    }

    /**
     * Migrate commissions
     */
    private function migrate_commissions(int $offset, int $limit): array {
        global $wpdb;

        // Commissions are typically part of referrals, but some plugins have separate tables
        $target_table = $wpdb->prefix . 'wcfm_aff_pro_commissions';
        $migrated = 0;
        $errors = [];

        if ($this->source_plugin === 'yith_affiliates') {
            $source_table = $wpdb->prefix . 'yith_wcaf_commissions';

            $commissions = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$source_table} ORDER BY ID LIMIT %d OFFSET %d",
                $limit,
                $offset
            ));

            foreach ($commissions as $commission) {
                $new_affiliate_id = $this->get_migrated_affiliate_id($commission->affiliate_id ?? 0);

                if (!$new_affiliate_id) {
                    continue;
                }

                $data = [
                    'affiliate_id' => $new_affiliate_id,
                    'order_id' => $commission->order_id ?? 0,
                    'product_id' => $commission->product_id ?? 0,
                    'amount' => $commission->amount ?? 0,
                    'status' => $this->map_commission_status($commission->status ?? 'pending'),
                    'type' => 'sale',
                    'migrated_from' => $this->source_plugin,
                    'migrated_id' => $commission->ID,
                    'created_at' => $commission->created_at ?? current_time('mysql'),
                ];

                if ($this->dry_run) {
                    $migrated++;
                    continue;
                }

                $result = $wpdb->insert($target_table, $data);

                if ($result) {
                    $migrated++;
                }
            }

            return [
                'migrated' => $migrated,
                'errors' => $errors,
                'has_more' => count($commissions) === $limit,
            ];
        }

        return ['migrated' => 0, 'errors' => [], 'has_more' => false];
    }

    /**
     * Migrate payouts
     */
    private function migrate_payouts(int $offset, int $limit): array {
        global $wpdb;

        $migrated = 0;
        $errors = [];

        switch ($this->source_plugin) {
            case 'wcfm_affiliate':
                $source_table = $wpdb->prefix . 'wcfm_affiliate_payouts';
                break;
            case 'affiliatewp':
                $source_table = $wpdb->prefix . 'affiliate_wp_payouts';
                break;
            default:
                return ['migrated' => 0, 'errors' => [], 'has_more' => false];
        }

        $target_table = $wpdb->prefix . 'wcfm_aff_pro_payouts';

        $payouts = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$source_table} ORDER BY payout_id LIMIT %d OFFSET %d",
            $limit,
            $offset
        ));

        foreach ($payouts as $payout) {
            $new_affiliate_id = $this->get_migrated_affiliate_id($payout->affiliate_id ?? 0);

            if (!$new_affiliate_id) {
                continue;
            }

            $data = [
                'affiliate_id' => $new_affiliate_id,
                'amount' => $payout->amount ?? 0,
                'status' => $this->map_payout_status($payout->status ?? 'pending'),
                'method' => $payout->payout_method ?? 'bank_transfer',
                'transaction_id' => $payout->transaction_id ?? '',
                'notes' => $payout->notes ?? '',
                'migrated_from' => $this->source_plugin,
                'migrated_id' => $payout->payout_id,
                'created_at' => $payout->date ?? current_time('mysql'),
            ];

            if ($this->dry_run) {
                $migrated++;
                continue;
            }

            $result = $wpdb->insert($target_table, $data);

            if ($result) {
                $migrated++;
            }
        }

        return [
            'migrated' => $migrated,
            'errors' => $errors,
            'has_more' => count($payouts) === $limit,
        ];
    }

    /**
     * Get migrated affiliate ID from original ID
     */
    private function get_migrated_affiliate_id(int $original_id): ?int {
        global $wpdb;

        $target_table = $wpdb->prefix . 'wcfm_aff_pro_affiliates';

        return $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$target_table} WHERE migrated_from = %s AND migrated_id = %d",
            $this->source_plugin,
            $original_id
        ));
    }

    /**
     * Migrate affiliate meta
     */
    private function migrate_affiliate_meta(int $old_id, int $new_id, string $source): void {
        global $wpdb;

        // Get user ID for the affiliate
        $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate($new_id);
        if (!$affiliate) {
            return;
        }

        $user_id = $affiliate->user_id;

        // Copy relevant user meta
        $meta_keys = [
            '_wcfm_affiliate_' => '_wcfm_aff_pro_',
            '_affiliate_' => '_wcfm_aff_pro_',
        ];

        foreach ($meta_keys as $old_prefix => $new_prefix) {
            $metas = $wpdb->get_results($wpdb->prepare(
                "SELECT meta_key, meta_value FROM {$wpdb->usermeta}
                 WHERE user_id = %d AND meta_key LIKE %s",
                $user_id,
                $old_prefix . '%'
            ));

            foreach ($metas as $meta) {
                $new_key = str_replace($old_prefix, $new_prefix, $meta->meta_key);
                update_user_meta($user_id, $new_key, maybe_unserialize($meta->meta_value));
            }
        }
    }

    /**
     * Migrate AffiliateWP meta
     */
    private function migrate_affiliatewp_meta(int $old_affiliate_id, int $new_id): void {
        global $wpdb;

        $meta_table = $wpdb->prefix . 'affiliate_wp_affiliatemeta';

        if ($wpdb->get_var("SHOW TABLES LIKE '{$meta_table}'") !== $meta_table) {
            return;
        }

        $metas = $wpdb->get_results($wpdb->prepare(
            "SELECT meta_key, meta_value FROM {$meta_table} WHERE affiliate_id = %d",
            $old_affiliate_id
        ));

        foreach ($metas as $meta) {
            // Store in our own meta system or user meta
            $affiliate = wcfm_affiliate_pro()->affiliates->get_affiliate($new_id);
            if ($affiliate) {
                update_user_meta($affiliate->user_id, '_wcfm_aff_pro_' . $meta->meta_key, maybe_unserialize($meta->meta_value));
            }
        }
    }

    /**
     * Map affiliate status
     */
    private function map_affiliate_status(string $status): string {
        $map = [
            'active' => 'active',
            'approved' => 'active',
            'enabled' => 'active',
            'inactive' => 'inactive',
            'disabled' => 'inactive',
            'pending' => 'pending',
            'rejected' => 'rejected',
            'declined' => 'rejected',
            'suspended' => 'suspended',
        ];

        return $map[strtolower($status)] ?? 'pending';
    }

    /**
     * Map referral status
     */
    private function map_referral_status(string $status): string {
        $map = [
            'paid' => 'paid',
            'unpaid' => 'approved',
            'pending' => 'pending',
            'rejected' => 'rejected',
            'approved' => 'approved',
        ];

        return $map[strtolower($status)] ?? 'pending';
    }

    /**
     * Map commission status
     */
    private function map_commission_status(string $status): string {
        return $this->map_referral_status($status);
    }

    /**
     * Map payout status
     */
    private function map_payout_status(string $status): string {
        $map = [
            'paid' => 'completed',
            'completed' => 'completed',
            'pending' => 'pending',
            'processing' => 'processing',
            'failed' => 'failed',
        ];

        return $map[strtolower($status)] ?? 'pending';
    }

    /**
     * Generate affiliate code
     */
    private function generate_affiliate_code(int $user_id): string {
        $user = get_userdata($user_id);
        $base = $user ? sanitize_title($user->user_login) : 'aff';
        $code = strtoupper(substr($base, 0, 6)) . $user_id;

        return $code;
    }

    /**
     * Update migration progress
     */
    private function update_migration_progress(int $migration_id, string $type, int $count, array $errors): void {
        global $wpdb;

        $migration = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wcfm_aff_pro_migrations WHERE id = %d",
            $migration_id
        ));

        if (!$migration) {
            return;
        }

        $progress = json_decode($migration->progress ?? '{}', true) ?: [];
        $progress[$type] = ($progress[$type] ?? 0) + $count;

        $all_errors = json_decode($migration->errors ?? '[]', true) ?: [];
        $all_errors = array_merge($all_errors, $errors);

        $wpdb->update(
            $wpdb->prefix . 'wcfm_aff_pro_migrations',
            [
                'progress' => json_encode($progress),
                'errors' => json_encode($all_errors),
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $migration_id],
            ['%s', '%s', '%s'],
            ['%d']
        );
    }

    /**
     * Complete migration
     */
    public function complete_migration(int $migration_id): void {
        global $wpdb;

        $wpdb->update(
            $wpdb->prefix . 'wcfm_aff_pro_migrations',
            [
                'status' => 'completed',
                'completed_at' => current_time('mysql'),
            ],
            ['id' => $migration_id],
            ['%s', '%s'],
            ['%d']
        );
    }

    /**
     * Rollback migration
     */
    public function rollback_migration(int $migration_id): array {
        global $wpdb;

        $migration = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wcfm_aff_pro_migrations WHERE id = %d",
            $migration_id
        ));

        if (!$migration) {
            return ['success' => false, 'message' => __('Migrazione non trovata', 'wcfm-affiliate-pro')];
        }

        $source = $migration->source_plugin;
        $tables = [
            'wcfm_aff_pro_affiliates',
            'wcfm_aff_pro_referrals',
            'wcfm_aff_pro_commissions',
            'wcfm_aff_pro_visits',
            'wcfm_aff_pro_payouts',
        ];

        $deleted = 0;

        foreach ($tables as $table) {
            $full_table = $wpdb->prefix . $table;

            if ($wpdb->get_var("SHOW TABLES LIKE '{$full_table}'") === $full_table) {
                $count = $wpdb->query($wpdb->prepare(
                    "DELETE FROM {$full_table} WHERE migrated_from = %s",
                    $source
                ));
                $deleted += $count;
            }
        }

        // Update migration status
        $wpdb->update(
            $wpdb->prefix . 'wcfm_aff_pro_migrations',
            [
                'status' => 'rolled_back',
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $migration_id],
            ['%s', '%s'],
            ['%d']
        );

        return [
            'success' => true,
            'deleted' => $deleted,
            'message' => sprintf(__('Rollback completato. %d record eliminati.', 'wcfm-affiliate-pro'), $deleted),
        ];
    }

    /**
     * Log message
     */
    private function log(string $message): void {
        $this->log[] = [
            'time' => current_time('mysql'),
            'message' => $message,
        ];

        // Also log to WordPress debug log
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[WCFM Affiliate Pro Migration] ' . $message);
        }
    }

    /**
     * Get migration log
     */
    public function get_log(): array {
        return $this->log;
    }

    // =========================================================================
    // AJAX HANDLERS
    // =========================================================================

    /**
     * AJAX: Check migration availability
     */
    public function ajax_check_migration(): void {
        check_ajax_referer('wcfm_affiliate_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permessi insufficienti', 'wcfm-affiliate-pro')]);
        }

        $sources = $this->detect_source_plugins();

        wp_send_json_success([
            'sources' => $sources,
            'has_sources' => !empty($sources),
        ]);
    }

    /**
     * AJAX: Start migration
     */
    public function ajax_start_migration(): void {
        check_ajax_referer('wcfm_affiliate_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permessi insufficienti', 'wcfm-affiliate-pro')]);
        }

        $source = sanitize_text_field($_POST['source'] ?? '');
        $dry_run = isset($_POST['dry_run']) && $_POST['dry_run'] === 'true';

        if (empty($source)) {
            wp_send_json_error(['message' => __('Fonte migrazione non specificata', 'wcfm-affiliate-pro')]);
        }

        $result = $this->start_migration($source, ['dry_run' => $dry_run]);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * AJAX: Migrate batch
     */
    public function ajax_migrate_batch(): void {
        check_ajax_referer('wcfm_affiliate_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permessi insufficienti', 'wcfm-affiliate-pro')]);
        }

        $migration_id = intval($_POST['migration_id'] ?? 0);
        $type = sanitize_text_field($_POST['type'] ?? '');
        $offset = intval($_POST['offset'] ?? 0);

        if (!$migration_id || empty($type)) {
            wp_send_json_error(['message' => __('Parametri mancanti', 'wcfm-affiliate-pro')]);
        }

        $result = $this->migrate_batch($migration_id, $type, $offset);

        // Check if this type is complete and we need to move to next
        if (!$result['has_more']) {
            $types = ['affiliates', 'referrals', 'visits', 'commissions', 'payouts'];
            $current_index = array_search($type, $types);

            if ($current_index < count($types) - 1) {
                $result['next_type'] = $types[$current_index + 1];
                $result['next_offset'] = 0;
            } else {
                // Migration complete
                $this->complete_migration($migration_id);
                $result['complete'] = true;
            }
        }

        wp_send_json_success($result);
    }

    /**
     * AJAX: Rollback migration
     */
    public function ajax_rollback_migration(): void {
        check_ajax_referer('wcfm_affiliate_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permessi insufficienti', 'wcfm-affiliate-pro')]);
        }

        $migration_id = intval($_POST['migration_id'] ?? 0);

        if (!$migration_id) {
            wp_send_json_error(['message' => __('ID migrazione non specificato', 'wcfm-affiliate-pro')]);
        }

        $result = $this->rollback_migration($migration_id);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * AJAX: Get migration log
     */
    public function ajax_get_migration_log(): void {
        check_ajax_referer('wcfm_affiliate_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permessi insufficienti', 'wcfm-affiliate-pro')]);
        }

        $migration_id = intval($_POST['migration_id'] ?? 0);

        global $wpdb;

        $migration = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wcfm_aff_pro_migrations WHERE id = %d",
            $migration_id
        ));

        if (!$migration) {
            wp_send_json_error(['message' => __('Migrazione non trovata', 'wcfm-affiliate-pro')]);
        }

        wp_send_json_success([
            'migration' => $migration,
            'progress' => json_decode($migration->progress ?? '{}', true),
            'errors' => json_decode($migration->errors ?? '[]', true),
        ]);
    }

    /**
     * Create migrations table
     */
    public static function create_migrations_table(): void {
        global $wpdb;

        $table = $wpdb->prefix . 'wcfm_aff_pro_migrations';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            source_plugin varchar(50) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            options longtext,
            progress longtext,
            errors longtext,
            started_at datetime DEFAULT NULL,
            completed_at datetime DEFAULT NULL,
            created_by bigint(20) unsigned DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY source_plugin (source_plugin),
            KEY status (status)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}
