<?php
/**
 * Migration Wizard Admin Page
 *
 * Interfaccia utente per migrare dati da altri plugin affiliate.
 *
 * @package WCFM_Affiliate_Pro
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class WCFM_Affiliate_Admin_Migration {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', [$this, 'add_migration_menu'], 99);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    /**
     * Add migration submenu
     */
    public function add_migration_menu(): void {
        add_submenu_page(
            'wcfm-affiliate-pro',
            __('Migrazione Dati', 'wcfm-affiliate-pro'),
            __('Migrazione', 'wcfm-affiliate-pro'),
            'manage_options',
            'wcfm-affiliate-migration',
            [$this, 'render_migration_page']
        );
    }

    /**
     * Enqueue scripts
     */
    public function enqueue_scripts(string $hook): void {
        if ($hook !== 'wcfm-affiliate-pro_page_wcfm-affiliate-migration') {
            return;
        }

        wp_enqueue_style(
            'wcfm-affiliate-migration',
            WCFM_AFFILIATE_PRO_URL . 'assets/css/admin-migration.css',
            [],
            WCFM_AFFILIATE_PRO_VERSION
        );

        wp_enqueue_script(
            'wcfm-affiliate-migration',
            WCFM_AFFILIATE_PRO_URL . 'assets/js/admin-migration.js',
            ['jquery'],
            WCFM_AFFILIATE_PRO_VERSION,
            true
        );

        wp_localize_script('wcfm-affiliate-migration', 'wcfmAffMigration', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wcfm_affiliate_admin_nonce'),
            'i18n' => [
                'confirm_start' => __('Sei sicuro di voler avviare la migrazione?', 'wcfm-affiliate-pro'),
                'confirm_rollback' => __('Sei sicuro di voler annullare la migrazione? Tutti i dati migrati verranno eliminati.', 'wcfm-affiliate-pro'),
                'migrating' => __('Migrazione in corso...', 'wcfm-affiliate-pro'),
                'completed' => __('Migrazione completata!', 'wcfm-affiliate-pro'),
                'error' => __('Errore durante la migrazione', 'wcfm-affiliate-pro'),
                'rolling_back' => __('Rollback in corso...', 'wcfm-affiliate-pro'),
                'rollback_complete' => __('Rollback completato', 'wcfm-affiliate-pro'),
            ],
        ]);
    }

    /**
     * Render migration page
     */
    public function render_migration_page(): void {
        $migration = new WCFM_Affiliate_Migration();
        $sources = $migration->detect_source_plugins();
        $past_migrations = $this->get_past_migrations();
        ?>
        <div class="wrap wcfm-migration-wrap">
            <h1 class="wp-heading-inline">
                <span class="dashicons dashicons-migrate"></span>
                <?php esc_html_e('Migrazione Affiliati', 'wcfm-affiliate-pro'); ?>
            </h1>

            <div class="migration-container">
                <!-- Header Info -->
                <div class="migration-header-card">
                    <div class="header-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 16v-4M12 8h.01"/>
                        </svg>
                    </div>
                    <div class="header-content">
                        <h2><?php esc_html_e('Importa i tuoi affiliati esistenti', 'wcfm-affiliate-pro'); ?></h2>
                        <p><?php esc_html_e('Questo wizard ti permette di migrare affiliati, referral, visite e pagamenti da altri plugin affiliate. La migrazione è sicura e reversibile.', 'wcfm-affiliate-pro'); ?></p>
                    </div>
                </div>

                <!-- Source Selection -->
                <?php if (!empty($sources)): ?>
                <div class="migration-step" id="step-source">
                    <h3 class="step-title">
                        <span class="step-number">1</span>
                        <?php esc_html_e('Seleziona la fonte', 'wcfm-affiliate-pro'); ?>
                    </h3>

                    <div class="source-cards">
                        <?php foreach ($sources as $key => $source): ?>
                        <div class="source-card" data-source="<?php echo esc_attr($key); ?>">
                            <div class="source-icon">
                                <?php echo $this->get_plugin_icon($key); ?>
                            </div>
                            <div class="source-info">
                                <h4><?php echo esc_html($source['name']); ?></h4>
                                <p class="affiliates-count">
                                    <strong><?php echo number_format($source['affiliates']); ?></strong>
                                    <?php esc_html_e('affiliati trovati', 'wcfm-affiliate-pro'); ?>
                                </p>
                            </div>
                            <div class="source-check">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                    <polyline points="20 6 9 17 4 12"/>
                                </svg>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Preview Section -->
                <div class="migration-step" id="step-preview" style="display: none;">
                    <h3 class="step-title">
                        <span class="step-number">2</span>
                        <?php esc_html_e('Anteprima migrazione', 'wcfm-affiliate-pro'); ?>
                    </h3>

                    <div class="preview-container">
                        <div class="preview-stats" id="preview-stats">
                            <!-- Filled by JavaScript -->
                        </div>

                        <div class="migration-options">
                            <label class="option-checkbox">
                                <input type="checkbox" id="dry-run-mode" checked>
                                <span class="checkmark"></span>
                                <span class="option-label">
                                    <?php esc_html_e('Modalità test (dry run)', 'wcfm-affiliate-pro'); ?>
                                    <small><?php esc_html_e('Simula la migrazione senza modificare il database', 'wcfm-affiliate-pro'); ?></small>
                                </span>
                            </label>
                        </div>

                        <div class="preview-actions">
                            <button type="button" class="button" id="btn-back-source">
                                <span class="dashicons dashicons-arrow-left-alt"></span>
                                <?php esc_html_e('Indietro', 'wcfm-affiliate-pro'); ?>
                            </button>
                            <button type="button" class="button button-primary" id="btn-start-migration">
                                <span class="dashicons dashicons-migrate"></span>
                                <?php esc_html_e('Avvia Migrazione', 'wcfm-affiliate-pro'); ?>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Progress Section -->
                <div class="migration-step" id="step-progress" style="display: none;">
                    <h3 class="step-title">
                        <span class="step-number">3</span>
                        <?php esc_html_e('Migrazione in corso', 'wcfm-affiliate-pro'); ?>
                    </h3>

                    <div class="progress-container">
                        <div class="progress-header">
                            <span class="progress-label"><?php esc_html_e('Progresso', 'wcfm-affiliate-pro'); ?></span>
                            <span class="progress-percent" id="progress-percent">0%</span>
                        </div>
                        <div class="progress-bar-container">
                            <div class="progress-bar" id="progress-bar"></div>
                        </div>
                        <div class="progress-status" id="progress-status">
                            <?php esc_html_e('Preparazione...', 'wcfm-affiliate-pro'); ?>
                        </div>

                        <div class="progress-details">
                            <div class="detail-item">
                                <span class="detail-label"><?php esc_html_e('Affiliati', 'wcfm-affiliate-pro'); ?></span>
                                <span class="detail-value" id="progress-affiliates">0</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label"><?php esc_html_e('Referral', 'wcfm-affiliate-pro'); ?></span>
                                <span class="detail-value" id="progress-referrals">0</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label"><?php esc_html_e('Visite', 'wcfm-affiliate-pro'); ?></span>
                                <span class="detail-value" id="progress-visits">0</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label"><?php esc_html_e('Pagamenti', 'wcfm-affiliate-pro'); ?></span>
                                <span class="detail-value" id="progress-payouts">0</span>
                            </div>
                        </div>

                        <div class="progress-log-container">
                            <h4><?php esc_html_e('Log Migrazione', 'wcfm-affiliate-pro'); ?></h4>
                            <div class="progress-log" id="progress-log"></div>
                        </div>

                        <div class="progress-actions" style="display: none;" id="progress-actions">
                            <button type="button" class="button" id="btn-close-progress">
                                <?php esc_html_e('Chiudi', 'wcfm-affiliate-pro'); ?>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Results Section -->
                <div class="migration-step" id="step-results" style="display: none;">
                    <div class="results-container">
                        <div class="results-icon success">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="16 10 11 15 8 12"/>
                            </svg>
                        </div>
                        <h3><?php esc_html_e('Migrazione Completata!', 'wcfm-affiliate-pro'); ?></h3>
                        <p class="results-summary" id="results-summary"></p>

                        <div class="results-stats" id="results-stats"></div>

                        <div class="results-actions">
                            <a href="<?php echo esc_url(admin_url('admin.php?page=wcfm-affiliate-pro')); ?>" class="button button-primary">
                                <?php esc_html_e('Vai alla Dashboard', 'wcfm-affiliate-pro'); ?>
                            </a>
                            <button type="button" class="button button-link-delete" id="btn-rollback">
                                <?php esc_html_e('Annulla Migrazione', 'wcfm-affiliate-pro'); ?>
                            </button>
                        </div>
                    </div>
                </div>

                <?php else: ?>
                <!-- No Sources Found -->
                <div class="no-sources-found">
                    <div class="no-sources-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="8" x2="12" y2="12"/>
                            <line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                    </div>
                    <h3><?php esc_html_e('Nessun plugin affiliate trovato', 'wcfm-affiliate-pro'); ?></h3>
                    <p><?php esc_html_e('Non sono stati rilevati plugin affiliate da cui migrare i dati. Sono supportati:', 'wcfm-affiliate-pro'); ?></p>
                    <ul>
                        <li>WCFM Affiliate</li>
                        <li>AffiliateWP</li>
                        <li>YITH WooCommerce Affiliates</li>
                    </ul>
                </div>
                <?php endif; ?>

                <!-- Past Migrations -->
                <?php if (!empty($past_migrations)): ?>
                <div class="migration-history">
                    <h3>
                        <span class="dashicons dashicons-backup"></span>
                        <?php esc_html_e('Cronologia Migrazioni', 'wcfm-affiliate-pro'); ?>
                    </h3>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('ID', 'wcfm-affiliate-pro'); ?></th>
                                <th><?php esc_html_e('Fonte', 'wcfm-affiliate-pro'); ?></th>
                                <th><?php esc_html_e('Stato', 'wcfm-affiliate-pro'); ?></th>
                                <th><?php esc_html_e('Data', 'wcfm-affiliate-pro'); ?></th>
                                <th><?php esc_html_e('Azioni', 'wcfm-affiliate-pro'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($past_migrations as $m): ?>
                            <tr>
                                <td>#<?php echo esc_html($m->id); ?></td>
                                <td><?php echo esc_html($this->get_source_name($m->source_plugin)); ?></td>
                                <td>
                                    <span class="migration-status status-<?php echo esc_attr($m->status); ?>">
                                        <?php echo esc_html($this->get_status_label($m->status)); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($m->created_at))); ?>
                                </td>
                                <td>
                                    <?php if ($m->status === 'completed'): ?>
                                    <button type="button" class="button button-small btn-rollback-history" data-id="<?php echo esc_attr($m->id); ?>">
                                        <?php esc_html_e('Rollback', 'wcfm-affiliate-pro'); ?>
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

            <!-- Hidden data -->
            <input type="hidden" id="selected-source" value="">
            <input type="hidden" id="migration-id" value="">
        </div>
        <?php
    }

    /**
     * Get plugin icon SVG
     */
    private function get_plugin_icon(string $key): string {
        switch ($key) {
            case 'wcfm_affiliate':
                return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3h18v18H3zM12 8v8M8 12h8"/></svg>';
            case 'affiliatewp':
                return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 22 8.5 22 15.5 12 22 2 15.5 2 8.5 12 2"/><line x1="12" y1="22" x2="12" y2="15.5"/><polyline points="22 8.5 12 15.5 2 8.5"/></svg>';
            case 'yith_affiliates':
                return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>';
            default:
                return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>';
        }
    }

    /**
     * Get source name
     */
    private function get_source_name(string $source): string {
        $names = [
            'wcfm_affiliate' => 'WCFM Affiliate',
            'affiliatewp' => 'AffiliateWP',
            'yith_affiliates' => 'YITH Affiliates',
        ];
        return $names[$source] ?? $source;
    }

    /**
     * Get status label
     */
    private function get_status_label(string $status): string {
        $labels = [
            'pending' => __('In attesa', 'wcfm-affiliate-pro'),
            'in_progress' => __('In corso', 'wcfm-affiliate-pro'),
            'completed' => __('Completato', 'wcfm-affiliate-pro'),
            'failed' => __('Fallito', 'wcfm-affiliate-pro'),
            'rolled_back' => __('Annullato', 'wcfm-affiliate-pro'),
        ];
        return $labels[$status] ?? $status;
    }

    /**
     * Get past migrations
     */
    private function get_past_migrations(): array {
        global $wpdb;

        $table = $wpdb->prefix . 'wcfm_aff_pro_migrations';

        if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) {
            return [];
        }

        return $wpdb->get_results(
            "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT 10"
        );
    }
}
