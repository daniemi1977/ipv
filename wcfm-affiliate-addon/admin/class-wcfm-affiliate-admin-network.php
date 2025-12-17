<?php
/**
 * Admin Network Management
 *
 * Gestione rete MLM con visualizzazione albero e spostamento affiliati
 *
 * @package WCFM_Affiliate_Pro
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class WCFM_Affiliate_Admin_Network {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', [$this, 'add_submenu'], 20);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);

        // AJAX handlers
        add_action('wp_ajax_wcfm_aff_pro_get_network_tree', [$this, 'ajax_get_network_tree']);
        add_action('wp_ajax_wcfm_aff_pro_move_affiliate', [$this, 'ajax_move_affiliate']);
        add_action('wp_ajax_wcfm_aff_pro_search_affiliates', [$this, 'ajax_search_affiliates']);
        add_action('wp_ajax_wcfm_aff_pro_get_affiliate_details', [$this, 'ajax_get_affiliate_details']);
    }

    /**
     * Add submenu page
     */
    public function add_submenu(): void {
        add_submenu_page(
            'wcfm-affiliate',
            __('Gestione Rete', 'wcfm-affiliate-pro'),
            __('Rete MLM', 'wcfm-affiliate-pro'),
            'manage_aff_pro',
            'wcfm-affiliate-network',
            [$this, 'render_network_page']
        );
    }

    /**
     * Enqueue assets
     */
    public function enqueue_assets(string $hook): void {
        if (strpos($hook, 'wcfm-affiliate-network') === false) {
            return;
        }

        // Tailwind CDN
        wp_enqueue_script(
            'tailwindcss',
            'https://cdn.tailwindcss.com',
            [],
            '3.4.1',
            false
        );

        // D3.js for tree visualization
        wp_enqueue_script(
            'd3js',
            'https://d3js.org/d3.v7.min.js',
            [],
            '7.8.5',
            true
        );

        // Custom network JS
        wp_enqueue_script(
            'wcfm-aff-pro-network',
            WCFM_AFFILIATE_PRO_URL . 'assets/js/admin-network.js',
            ['jquery', 'd3js'],
            WCFM_AFFILIATE_PRO_VERSION,
            true
        );

        wp_localize_script('wcfm-aff-pro-network', 'wcfmAffProNetwork', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wcfm_aff_pro_network'),
            'i18n' => [
                'confirm_move' => __('Sei sicuro di voler spostare questo affiliato?', 'wcfm-affiliate-pro'),
                'move_success' => __('Affiliato spostato con successo!', 'wcfm-affiliate-pro'),
                'move_error' => __('Errore durante lo spostamento', 'wcfm-affiliate-pro'),
                'loading' => __('Caricamento...', 'wcfm-affiliate-pro'),
                'no_results' => __('Nessun risultato', 'wcfm-affiliate-pro'),
                'root_node' => __('Rete Principale', 'wcfm-affiliate-pro'),
            ],
        ]);

        // Custom styles
        wp_enqueue_style(
            'wcfm-aff-pro-network',
            WCFM_AFFILIATE_PRO_URL . 'assets/css/admin-network.css',
            [],
            WCFM_AFFILIATE_PRO_VERSION
        );
    }

    /**
     * Render network management page
     */
    public function render_network_page(): void {
        ?>
        <div id="wcfm-aff-pro-network-app" class="wrap">
            <script>
                // Tailwind config
                tailwind.config = {
                    theme: {
                        extend: {
                            colors: {
                                primary: '#00897b',
                                'primary-dark': '#00695c',
                            }
                        }
                    }
                }
            </script>

            <div class="min-h-screen bg-gray-50 p-6">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 flex items-center gap-3">
                        <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <?php _e('Gestione Rete MLM', 'wcfm-affiliate-pro'); ?>
                    </h1>
                    <p class="mt-2 text-gray-600"><?php _e('Visualizza e gestisci la struttura della rete affiliati', 'wcfm-affiliate-pro'); ?></p>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <?php $this->render_stats_cards(); ?>
                </div>

                <!-- Main Content -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Tree Visualization -->
                    <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-900"><?php _e('Albero Rete', 'wcfm-affiliate-pro'); ?></h2>
                            <div class="flex items-center gap-2">
                                <button id="tree-zoom-in" class="p-2 text-gray-500 hover:text-primary hover:bg-gray-100 rounded-lg transition">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/>
                                    </svg>
                                </button>
                                <button id="tree-zoom-out" class="p-2 text-gray-500 hover:text-primary hover:bg-gray-100 rounded-lg transition">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM13 10H7"/>
                                    </svg>
                                </button>
                                <button id="tree-reset" class="p-2 text-gray-500 hover:text-primary hover:bg-gray-100 rounded-lg transition">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                </button>
                                <button id="tree-fullscreen" class="p-2 text-gray-500 hover:text-primary hover:bg-gray-100 rounded-lg transition">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div id="network-tree-container" class="relative" style="height: 600px;">
                            <div id="tree-loading" class="absolute inset-0 flex items-center justify-center bg-white/80 z-10">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="w-10 h-10 border-4 border-primary border-t-transparent rounded-full animate-spin"></div>
                                    <span class="text-gray-500"><?php _e('Caricamento albero...', 'wcfm-affiliate-pro'); ?></span>
                                </div>
                            </div>
                            <svg id="network-tree-svg" class="w-full h-full"></svg>
                        </div>

                        <!-- Legend -->
                        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
                            <div class="flex flex-wrap items-center gap-6 text-sm">
                                <div class="flex items-center gap-2">
                                    <span class="w-4 h-4 rounded-full bg-primary"></span>
                                    <span class="text-gray-600"><?php _e('Attivo', 'wcfm-affiliate-pro'); ?></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="w-4 h-4 rounded-full bg-amber-500"></span>
                                    <span class="text-gray-600"><?php _e('In Attesa', 'wcfm-affiliate-pro'); ?></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="w-4 h-4 rounded-full bg-red-500"></span>
                                    <span class="text-gray-600"><?php _e('Sospeso', 'wcfm-affiliate-pro'); ?></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="w-4 h-4 rounded-full bg-gray-400"></span>
                                    <span class="text-gray-600"><?php _e('Inattivo', 'wcfm-affiliate-pro'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Move Affiliate Panel -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-primary to-primary-dark">
                                <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                    </svg>
                                    <?php _e('Sposta Affiliato', 'wcfm-affiliate-pro'); ?>
                                </h2>
                            </div>
                            <div class="p-6 space-y-4">
                                <!-- Source Affiliate -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <?php _e('Affiliato da Spostare', 'wcfm-affiliate-pro'); ?>
                                    </label>
                                    <div class="relative">
                                        <input type="text" id="move-source-search"
                                               class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary transition"
                                               placeholder="<?php _e('Cerca affiliato...', 'wcfm-affiliate-pro'); ?>">
                                        <div id="move-source-results" class="absolute top-full left-0 right-0 mt-1 bg-white rounded-xl shadow-lg border border-gray-100 max-h-48 overflow-y-auto hidden z-20"></div>
                                    </div>
                                    <input type="hidden" id="move-source-id">
                                    <div id="move-source-selected" class="mt-2 hidden">
                                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                                            <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center">
                                                <span class="text-primary font-semibold" id="move-source-avatar"></span>
                                            </div>
                                            <div class="flex-1">
                                                <p class="font-medium text-gray-900" id="move-source-name"></p>
                                                <p class="text-sm text-gray-500" id="move-source-email"></p>
                                            </div>
                                            <button type="button" id="move-source-clear" class="text-gray-400 hover:text-red-500">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Arrow -->
                                <div class="flex justify-center">
                                    <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                                        </svg>
                                    </div>
                                </div>

                                <!-- Target Parent -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <?php _e('Nuovo Genitore (Upline)', 'wcfm-affiliate-pro'); ?>
                                    </label>
                                    <div class="relative">
                                        <input type="text" id="move-target-search"
                                               class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary transition"
                                               placeholder="<?php _e('Cerca nuovo genitore...', 'wcfm-affiliate-pro'); ?>">
                                        <div id="move-target-results" class="absolute top-full left-0 right-0 mt-1 bg-white rounded-xl shadow-lg border border-gray-100 max-h-48 overflow-y-auto hidden z-20"></div>
                                    </div>
                                    <input type="hidden" id="move-target-id">
                                    <div id="move-target-selected" class="mt-2 hidden">
                                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                                                <span class="text-blue-600 font-semibold" id="move-target-avatar"></span>
                                            </div>
                                            <div class="flex-1">
                                                <p class="font-medium text-gray-900" id="move-target-name"></p>
                                                <p class="text-sm text-gray-500" id="move-target-email"></p>
                                            </div>
                                            <button type="button" id="move-target-clear" class="text-gray-400 hover:text-red-500">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Option to move to root -->
                                    <label class="flex items-center gap-2 mt-3 cursor-pointer">
                                        <input type="checkbox" id="move-to-root" class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                                        <span class="text-sm text-gray-600"><?php _e('Sposta alla radice (senza genitore)', 'wcfm-affiliate-pro'); ?></span>
                                    </label>
                                </div>

                                <!-- Move Button -->
                                <button type="button" id="move-affiliate-btn"
                                        class="w-full py-3 px-4 bg-gradient-to-r from-primary to-primary-dark text-white font-semibold rounded-xl hover:shadow-lg hover:shadow-primary/30 transition disabled:opacity-50 disabled:cursor-not-allowed"
                                        disabled>
                                    <span class="flex items-center justify-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                        </svg>
                                        <?php _e('Sposta Affiliato', 'wcfm-affiliate-pro'); ?>
                                    </span>
                                </button>
                            </div>
                        </div>

                        <!-- Selected Node Details -->
                        <div id="node-details-panel" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hidden">
                            <div class="px-6 py-4 border-b border-gray-100">
                                <h2 class="text-lg font-semibold text-gray-900"><?php _e('Dettagli Affiliato', 'wcfm-affiliate-pro'); ?></h2>
                            </div>
                            <div class="p-6" id="node-details-content">
                                <!-- Populated via JS -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Toast Notification -->
            <div id="toast-notification" class="fixed bottom-6 right-6 transform translate-y-24 opacity-0 transition-all duration-300 z-50">
                <div class="flex items-center gap-3 px-6 py-4 bg-gray-900 text-white rounded-xl shadow-xl">
                    <div id="toast-icon"></div>
                    <span id="toast-message"></span>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render stats cards
     */
    private function render_stats_cards(): void {
        global $wpdb;

        $table = WCFM_Affiliate_DB::$table_affiliates;
        $mlm_table = WCFM_Affiliate_DB::$table_mlm;

        // Total affiliates
        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");

        // Active affiliates
        $active = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'active'");

        // Total in network (with parent)
        $in_network = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$mlm_table}");

        // Network depth
        $max_depth = (int) $wpdb->get_var("SELECT MAX(level) FROM {$mlm_table}");

        $cards = [
            [
                'title' => __('Affiliati Totali', 'wcfm-affiliate-pro'),
                'value' => number_format($total),
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>',
                'color' => 'primary',
            ],
            [
                'title' => __('Affiliati Attivi', 'wcfm-affiliate-pro'),
                'value' => number_format($active),
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                'color' => 'emerald',
            ],
            [
                'title' => __('In Rete MLM', 'wcfm-affiliate-pro'),
                'value' => number_format($in_network),
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>',
                'color' => 'blue',
            ],
            [
                'title' => __('Livelli Rete', 'wcfm-affiliate-pro'),
                'value' => $max_depth ?: 0,
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>',
                'color' => 'purple',
            ],
        ];

        foreach ($cards as $card):
            $colorClasses = [
                'primary' => ['bg-primary/10', 'text-primary'],
                'emerald' => ['bg-emerald-100', 'text-emerald-600'],
                'blue' => ['bg-blue-100', 'text-blue-600'],
                'purple' => ['bg-purple-100', 'text-purple-600'],
            ];
            $colors = $colorClasses[$card['color']];
            ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-xl <?php echo $colors[0]; ?> flex items-center justify-center flex-shrink-0">
                        <svg class="w-7 h-7 <?php echo $colors[1]; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <?php echo $card['icon']; ?>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500"><?php echo $card['title']; ?></p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $card['value']; ?></p>
                    </div>
                </div>
            </div>
            <?php
        endforeach;
    }

    /**
     * AJAX: Get network tree data
     */
    public function ajax_get_network_tree(): void {
        check_ajax_referer('wcfm_aff_pro_network', 'nonce');

        if (!current_user_can('manage_aff_pro')) {
            wp_send_json_error(['message' => __('Permessi insufficienti', 'wcfm-affiliate-pro')]);
        }

        $tree = $this->build_network_tree();

        wp_send_json_success(['tree' => $tree]);
    }

    /**
     * Build network tree structure
     */
    private function build_network_tree(): array {
        global $wpdb;

        $affiliates_table = WCFM_Affiliate_DB::$table_affiliates;
        $mlm_table = WCFM_Affiliate_DB::$table_mlm;

        // Get all affiliates with their MLM relationships
        $affiliates = $wpdb->get_results("
            SELECT
                a.id,
                a.user_id,
                a.affiliate_code,
                a.status,
                a.tier_id,
                a.earnings_total,
                a.referrals_count,
                m.parent_id,
                m.level,
                u.display_name,
                u.user_email
            FROM {$affiliates_table} a
            LEFT JOIN {$mlm_table} m ON a.id = m.affiliate_id
            LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
            ORDER BY m.level ASC, a.id ASC
        ", ARRAY_A);

        // Build tree structure
        $nodes = [];
        $children = [];

        foreach ($affiliates as $aff) {
            $id = $aff['id'];
            $parent_id = $aff['parent_id'] ?: 'root';

            $nodes[$id] = [
                'id' => $id,
                'name' => $aff['display_name'] ?: __('Affiliato #', 'wcfm-affiliate-pro') . $id,
                'email' => $aff['user_email'],
                'code' => $aff['affiliate_code'],
                'status' => $aff['status'],
                'level' => $aff['level'] ?: 0,
                'earnings' => floatval($aff['earnings_total']),
                'referrals' => intval($aff['referrals_count']),
                'parent_id' => $aff['parent_id'],
                'children' => [],
            ];

            if (!isset($children[$parent_id])) {
                $children[$parent_id] = [];
            }
            $children[$parent_id][] = $id;
        }

        // Build hierarchy
        $root = [
            'id' => 'root',
            'name' => __('Rete Principale', 'wcfm-affiliate-pro'),
            'status' => 'root',
            'children' => [],
        ];

        // Add children recursively
        $this->add_children($root, $children, $nodes);

        return $root;
    }

    /**
     * Recursively add children to tree
     */
    private function add_children(array &$parent, array $children, array $nodes): void {
        $parent_id = $parent['id'];

        if (!isset($children[$parent_id])) {
            return;
        }

        foreach ($children[$parent_id] as $child_id) {
            if (!isset($nodes[$child_id])) {
                continue;
            }

            $child = $nodes[$child_id];
            $this->add_children($child, $children, $nodes);
            $parent['children'][] = $child;
        }
    }

    /**
     * AJAX: Move affiliate to new parent
     */
    public function ajax_move_affiliate(): void {
        check_ajax_referer('wcfm_aff_pro_network', 'nonce');

        if (!current_user_can('manage_aff_pro')) {
            wp_send_json_error(['message' => __('Permessi insufficienti', 'wcfm-affiliate-pro')]);
        }

        $affiliate_id = intval($_POST['affiliate_id'] ?? 0);
        $new_parent_id = intval($_POST['new_parent_id'] ?? 0);
        $move_to_root = ($_POST['move_to_root'] ?? 'false') === 'true';

        if (!$affiliate_id) {
            wp_send_json_error(['message' => __('ID affiliato non valido', 'wcfm-affiliate-pro')]);
        }

        // Prevent moving to self or descendants
        if ($new_parent_id === $affiliate_id) {
            wp_send_json_error(['message' => __('Non puoi spostare un affiliato sotto se stesso', 'wcfm-affiliate-pro')]);
        }

        if (!$move_to_root && $this->is_descendant($new_parent_id, $affiliate_id)) {
            wp_send_json_error(['message' => __('Non puoi spostare un affiliato sotto un suo discendente', 'wcfm-affiliate-pro')]);
        }

        global $wpdb;
        $mlm_table = WCFM_Affiliate_DB::$table_mlm;

        // Get current record
        $current = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$mlm_table} WHERE affiliate_id = %d",
            $affiliate_id
        ));

        if ($move_to_root) {
            // Remove from MLM table (becomes root)
            if ($current) {
                $wpdb->delete($mlm_table, ['affiliate_id' => $affiliate_id], ['%d']);

                // Update all descendants levels
                $this->update_descendants_level($affiliate_id, 0);
            }
        } else {
            if (!$new_parent_id) {
                wp_send_json_error(['message' => __('Seleziona un nuovo genitore', 'wcfm-affiliate-pro')]);
            }

            // Get new parent level
            $parent_level = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT level FROM {$mlm_table} WHERE affiliate_id = %d",
                $new_parent_id
            ));

            $new_level = $parent_level + 1;

            if ($current) {
                // Update existing
                $wpdb->update(
                    $mlm_table,
                    [
                        'parent_id' => $new_parent_id,
                        'level' => $new_level,
                    ],
                    ['affiliate_id' => $affiliate_id],
                    ['%d', '%d'],
                    ['%d']
                );
            } else {
                // Insert new
                $wpdb->insert(
                    $mlm_table,
                    [
                        'affiliate_id' => $affiliate_id,
                        'parent_id' => $new_parent_id,
                        'level' => $new_level,
                    ],
                    ['%d', '%d', '%d']
                );
            }

            // Update all descendants levels
            $this->update_descendants_level($affiliate_id, $new_level);
        }

        // Log action
        do_action('wcfm_aff_pro_affiliate_moved', $affiliate_id, $new_parent_id, $move_to_root);

        wp_send_json_success(['message' => __('Affiliato spostato con successo!', 'wcfm-affiliate-pro')]);
    }

    /**
     * Check if affiliate is a descendant
     */
    private function is_descendant(int $potential_descendant, int $ancestor): bool {
        global $wpdb;
        $mlm_table = WCFM_Affiliate_DB::$table_mlm;

        $current = $potential_descendant;
        $max_depth = 20; // Prevent infinite loops
        $depth = 0;

        while ($current && $depth < $max_depth) {
            if ($current === $ancestor) {
                return true;
            }

            $current = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT parent_id FROM {$mlm_table} WHERE affiliate_id = %d",
                $current
            ));

            $depth++;
        }

        return false;
    }

    /**
     * Update descendants level after move
     */
    private function update_descendants_level(int $parent_id, int $parent_level): void {
        global $wpdb;
        $mlm_table = WCFM_Affiliate_DB::$table_mlm;

        // Get direct children
        $children = $wpdb->get_col($wpdb->prepare(
            "SELECT affiliate_id FROM {$mlm_table} WHERE parent_id = %d",
            $parent_id
        ));

        if (empty($children)) {
            return;
        }

        $new_level = $parent_level + 1;

        foreach ($children as $child_id) {
            $wpdb->update(
                $mlm_table,
                ['level' => $new_level],
                ['affiliate_id' => $child_id],
                ['%d'],
                ['%d']
            );

            // Recursively update children
            $this->update_descendants_level($child_id, $new_level);
        }
    }

    /**
     * AJAX: Search affiliates
     */
    public function ajax_search_affiliates(): void {
        check_ajax_referer('wcfm_aff_pro_network', 'nonce');

        $search = sanitize_text_field($_POST['search'] ?? '');
        $exclude = intval($_POST['exclude'] ?? 0);

        if (strlen($search) < 2) {
            wp_send_json_success(['results' => []]);
        }

        global $wpdb;
        $affiliates_table = WCFM_Affiliate_DB::$table_affiliates;

        $search_like = '%' . $wpdb->esc_like($search) . '%';

        $query = "
            SELECT a.id, a.affiliate_code, a.status, u.display_name, u.user_email
            FROM {$affiliates_table} a
            LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
            WHERE (u.display_name LIKE %s OR u.user_email LIKE %s OR a.affiliate_code LIKE %s)
        ";

        $params = [$search_like, $search_like, $search_like];

        if ($exclude) {
            $query .= " AND a.id != %d";
            $params[] = $exclude;
        }

        $query .= " LIMIT 10";

        $results = $wpdb->get_results($wpdb->prepare($query, $params));

        $formatted = array_map(function($row) {
            return [
                'id' => $row->id,
                'name' => $row->display_name ?: __('Affiliato #', 'wcfm-affiliate-pro') . $row->id,
                'email' => $row->user_email,
                'code' => $row->affiliate_code,
                'status' => $row->status,
                'avatar' => strtoupper(substr($row->display_name ?: 'A', 0, 1)),
            ];
        }, $results);

        wp_send_json_success(['results' => $formatted]);
    }

    /**
     * AJAX: Get affiliate details
     */
    public function ajax_get_affiliate_details(): void {
        check_ajax_referer('wcfm_aff_pro_network', 'nonce');

        $affiliate_id = intval($_POST['affiliate_id'] ?? 0);

        if (!$affiliate_id) {
            wp_send_json_error();
        }

        global $wpdb;
        $affiliates_table = WCFM_Affiliate_DB::$table_affiliates;
        $mlm_table = WCFM_Affiliate_DB::$table_mlm;

        $affiliate = $wpdb->get_row($wpdb->prepare("
            SELECT a.*, u.display_name, u.user_email,
                   m.parent_id, m.level,
                   (SELECT COUNT(*) FROM {$mlm_table} WHERE parent_id = a.id) as direct_downline
            FROM {$affiliates_table} a
            LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
            LEFT JOIN {$mlm_table} m ON a.id = m.affiliate_id
            WHERE a.id = %d
        ", $affiliate_id), ARRAY_A);

        if (!$affiliate) {
            wp_send_json_error();
        }

        // Get parent info
        $parent = null;
        if ($affiliate['parent_id']) {
            $parent = $wpdb->get_row($wpdb->prepare("
                SELECT a.id, u.display_name, u.user_email
                FROM {$affiliates_table} a
                LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
                WHERE a.id = %d
            ", $affiliate['parent_id']), ARRAY_A);
        }

        $affiliate['parent'] = $parent;
        $affiliate['earnings_formatted'] = wc_price($affiliate['earnings_total'] ?: 0);

        wp_send_json_success(['affiliate' => $affiliate]);
    }
}

// Initialize
new WCFM_Affiliate_Admin_Network();
