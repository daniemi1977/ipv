<?php
/**
 * IPV Plans Manager
 *
 * Gestisce i piani SaaS configurabili dall'admin
 *
 * @version 2.0.0
 *
 * CHANGELOG v2.0.0:
 * - Prezzi aggiornati da WooCommerce live
 * - Professional ridotto a €199.99/anno
 * - Business ridotto a €309.90/anno
 * - Golden Prompt aumentato a €69.00
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Vendor_Plans_Manager {

    private static $instance = null;

    /** @var string Option name for storing plans */
    const OPTION_NAME = 'ipv_saas_plans';

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ], 15 );
        add_filter( 'ipv_vendor_plan_options', [ $this, 'get_plan_options' ] );
    }

    public function add_admin_menu() {
        add_submenu_page(
            'ipv-vendor-dashboard',
            'Piani SaaS',
            '💰 Piani SaaS',
            'manage_options',
            'ipv-vendor-plans',
            [ $this, 'render_plans_page' ]
        );
    }

    public function get_plans() {
        $plans = get_option( self::OPTION_NAME, [] );

        if ( empty( $plans ) ) {
            return $this->get_default_plans();
        }

        return $plans;
    }

    /**
     * Get default plans - PREZZI AGGIORNATI 21/12/2025
     */
    public function get_default_plans() {
        return [
            // ========================================
            // PIANO TRIAL (gratuito, benvenuto)
            // ========================================
            'trial' => [
                'name' => 'Trial',
                'slug' => 'trial',
                'credits' => 10,
                'credits_period' => 'once',
                'activations' => 1,
                'price' => 0.00,
                'price_period' => 'once',
                'features' => [
                    'transcription' => true,
                    'ai_description' => true,
                    'priority_support' => false,
                    'api_access' => false,
                ],
                'description' => '10 crediti di benvenuto gratuiti. Non scade mai.',
                'is_active' => true,
                'sort_order' => 1,
            ],

            // ========================================
            // PIANO STARTER ANNUALE - €109.99/anno
            // ========================================
            'starter' => [
                'name' => 'Starter',
                'slug' => 'starter',
                'credits' => 600,
                'credits_period' => 'year',
                'activations' => 1,
                'price' => 109.99,
                'price_period' => 'year',
                'features' => [
                    'transcription' => true,
                    'ai_description' => true,
                    'priority_support' => false,
                    'api_access' => false,
                ],
                'description' => '600 crediti/anno (50/mese). Perfetto per canali in crescita.',
                'is_active' => true,
                'sort_order' => 2,
            ],

            // ========================================
            // PIANO PROFESSIONAL ANNUALE - €199.99/anno
            // ========================================
            'professional' => [
                'name' => 'Professional',
                'slug' => 'professional',
                'credits' => 1200,
                'credits_period' => 'year',
                'activations' => 3,
                'price' => 199.99,
                'price_period' => 'year',
                'features' => [
                    'transcription' => true,
                    'ai_description' => true,
                    'priority_support' => true,
                    'api_access' => false,
                ],
                'description' => '1200 crediti/anno (100/mese). Per creator professionisti.',
                'is_active' => true,
                'sort_order' => 3,
            ],

            // ========================================
            // PIANO BUSINESS ANNUALE - €309.90/anno
            // ========================================
            'business' => [
                'name' => 'Business',
                'slug' => 'business',
                'credits' => 6000,
                'credits_period' => 'year',
                'activations' => 10,
                'price' => 309.90,
                'price_period' => 'year',
                'features' => [
                    'transcription' => true,
                    'ai_description' => true,
                    'priority_support' => true,
                    'api_access' => true,
                ],
                'description' => '6000 crediti/anno (500/mese). Per agenzie e team.',
                'is_active' => true,
                'sort_order' => 4,
            ],

            // ========================================
            // GOLDEN PROMPT - €69.00 una tantum
            // ========================================
            'golden_prompt' => [
                'name' => 'Golden Prompt',
                'slug' => 'golden_prompt',
                'credits' => 0,
                'credits_period' => 'once',
                'activations' => 1,
                'price' => 69.00,
                'price_period' => 'once',
                'product_type' => 'addon',
                'features' => [
                    'transcription' => false,
                    'ai_description' => false,
                    'priority_support' => true,
                    'api_access' => false,
                    'golden_prompt_access' => true,
                ],
                'description' => 'Template Premium per descrizioni YouTube professionali.',
                'is_active' => true,
                'sort_order' => 5,
            ],

            // ========================================
            // CREDITI EXTRA 10 - €5.00
            // ========================================
            'extra_credits_10' => [
                'name' => 'Crediti Extra - 10',
                'slug' => 'extra_credits_10',
                'credits' => 10,
                'credits_period' => 'once',
                'activations' => 0,
                'price' => 5.00,
                'price_period' => 'once',
                'product_type' => 'addon',
                'features' => [
                    'transcription' => true,
                    'ai_description' => true,
                    'priority_support' => false,
                    'api_access' => false,
                ],
                'description' => '10 crediti extra (€0,50/credito). Non scadono mai.',
                'is_active' => true,
                'sort_order' => 6,
            ],

            // ========================================
            // CREDITI EXTRA 100 - €45.00
            // ========================================
            'extra_credits_100' => [
                'name' => 'Crediti Extra - 100',
                'slug' => 'extra_credits_100',
                'credits' => 100,
                'credits_period' => 'once',
                'activations' => 0,
                'price' => 45.00,
                'price_period' => 'once',
                'product_type' => 'addon',
                'features' => [
                    'transcription' => true,
                    'ai_description' => true,
                    'priority_support' => false,
                    'api_access' => false,
                ],
                'description' => '100 crediti extra (€0,45/credito). Non scadono mai.',
                'is_active' => true,
                'sort_order' => 7,
            ],
        ];
    }

    public function save_plans( $plans ) {
        return update_option( self::OPTION_NAME, $plans );
    }

    public function get_plan( $slug ) {
        $plans = $this->get_plans();
        return $plans[ $slug ] ?? null;
    }

    public function save_plan( $slug, $plan_data ) {
        $plans = $this->get_plans();
        $plans[ $slug ] = $plan_data;
        return $this->save_plans( $plans );
    }

    public function delete_plan( $slug ) {
        $plans = $this->get_plans();
        if ( isset( $plans[ $slug ] ) ) {
            unset( $plans[ $slug ] );
            return $this->save_plans( $plans );
        }
        return false;
    }

    public function get_plan_options( $options = [] ) {
        $plans = $this->get_plans();
        $result = [];

        foreach ( $plans as $slug => $plan ) {
            if ( ! empty( $plan['is_active'] ) ) {
                $credits_label = $plan['credits'] . ' crediti/' . $this->get_period_label( $plan['credits_period'] );
                $result[ $slug ] = $plan['name'] . ' (' . $credits_label . ')';
            }
        }

        return $result;
    }

    private function get_period_label( $period ) {
        $labels = [
            'day' => 'giorno',
            'week' => 'settimana',
            'month' => 'mese',
            'year' => 'anno',
            'once' => 'una tantum',
        ];
        return $labels[ $period ] ?? $period;
    }

    public function get_available_features() {
        return [
            'transcription' => [
                'name' => 'Trascrizione Video',
                'icon' => '📝',
                'description' => 'Estrazione automatica del testo dai video',
            ],
            'ai_description' => [
                'name' => 'Descrizione AI',
                'icon' => '🤖',
                'description' => 'Generazione automatica descrizioni con AI',
            ],
            'priority_support' => [
                'name' => 'Supporto Prioritario',
                'icon' => '⚡',
                'description' => 'Risposta entro 24 ore',
            ],
            'api_access' => [
                'name' => 'Accesso API',
                'icon' => '🔌',
                'description' => 'Integrazione diretta via REST API',
            ],
        ];
    }

    /**
     * Render plans page
     */
    public function render_plans_page() {
        $this->handle_form_submission();
        $plans = $this->get_plans();
        $features = $this->get_available_features();

        uasort( $plans, fn( $a, $b ) => ( $a['sort_order'] ?? 99 ) - ( $b['sort_order'] ?? 99 ) );

        ?>
        <div class="wrap">
            <h1 class="text-2xl font-bold mb-6">💰 Gestione Piani SaaS</h1>

            <p class="text-gray-600 mb-6">Configura i piani di abbonamento per IPV Production System Pro.</p>

            <!-- Plans Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
                <?php foreach ( $plans as $slug => $plan ) : ?>
                    <div class="bg-white rounded-xl shadow-md overflow-hidden <?php echo empty( $plan['is_active'] ) ? 'opacity-60' : ''; ?>">
                        <div class="p-4 text-white text-center" style="background: <?php echo $this->get_plan_color( $slug ); ?>">
                            <h3 class="text-xl font-bold"><?php echo esc_html( $plan['name'] ); ?></h3>
                            <div class="text-3xl font-bold mt-2">
                                <?php if ( $plan['price'] == 0 ) : ?>
                                    GRATIS
                                <?php else : ?>
                                    €<?php echo number_format( $plan['price'], 2, ',', '.' ); ?>
                                    <span class="text-sm font-normal">/<?php echo esc_html( $this->get_period_label( $plan['price_period'] ) ); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="p-4">
                            <div class="text-center mb-4 p-3 bg-gray-100 rounded-lg">
                                <span class="text-2xl font-bold text-indigo-600"><?php echo esc_html( $plan['credits'] ); ?></span>
                                <div class="text-sm text-gray-600">crediti / <?php echo esc_html( $this->get_period_label( $plan['credits_period'] ) ); ?></div>
                            </div>

                            <div class="text-center text-gray-600 mb-4">
                                🌐 <?php echo esc_html( $plan['activations'] ); ?> sito/i attivabili
                            </div>

                            <ul class="space-y-2 mb-4">
                                <?php foreach ( $features as $feature_key => $feature ) : ?>
                                    <?php $has = ! empty( $plan['features'][ $feature_key ] ); ?>
                                    <li class="flex items-center <?php echo $has ? '' : 'text-gray-400 line-through'; ?>">
                                        <span class="mr-2"><?php echo $has ? '✅' : '❌'; ?></span>
                                        <?php echo esc_html( $feature['name'] ); ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>

                            <?php if ( ! empty( $plan['description'] ) ) : ?>
                                <p class="text-sm text-gray-500 text-center"><?php echo esc_html( $plan['description'] ); ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="px-4 py-3 bg-gray-50 border-t flex gap-2">
                            <a href="<?php echo admin_url( 'admin.php?page=ipv-vendor-plans&action=edit&plan=' . $slug ); ?>"
                               class="flex-1 text-center px-3 py-2 bg-white border rounded hover:bg-gray-100">
                                ✏️ Modifica
                            </a>
                            <?php if ( $slug !== 'trial' ) : ?>
                                <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=ipv-vendor-plans&action=delete&plan=' . $slug ), 'delete_plan_' . $slug ); ?>"
                                   class="px-3 py-2 text-red-600 hover:bg-red-50 rounded"
                                   onclick="return confirm('Eliminare?');">🗑️</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Add New -->
                <div class="border-2 border-dashed border-gray-300 rounded-xl flex items-center justify-center min-h-[300px] hover:border-indigo-400 transition-colors">
                    <a href="<?php echo admin_url( 'admin.php?page=ipv-vendor-plans&action=new' ); ?>"
                       class="text-center text-gray-500 hover:text-indigo-600">
                        <div class="text-5xl mb-2">➕</div>
                        <div>Aggiungi Piano</div>
                    </a>
                </div>
            </div>

            <?php $this->render_plan_form(); ?>

            <!-- Reset Button -->
            <div class="bg-white rounded-lg shadow p-6 mt-8">
                <h3 class="text-lg font-semibold mb-2">🔄 Reset Piani</h3>
                <p class="text-gray-600 mb-4">Ripristina i piani ai valori predefiniti.</p>
                <form method="post">
                    <?php wp_nonce_field( 'ipv_reset_plans' ); ?>
                    <button type="submit" name="ipv_reset_plans" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300"
                            onclick="return confirm('Ripristinare i piani predefiniti?');">
                        🔄 Ripristina Default
                    </button>
                </form>
            </div>
        </div>

        <!-- Tailwind CDN -->
        <script src="https://cdn.tailwindcss.com"></script>
        <?php
    }

    private function get_plan_color( $slug ) {
        $colors = [
            'trial' => '#6b7280',
            'starter' => '#10b981',
            'professional' => '#6366f1',
            'business' => '#f59e0b',
            'golden_prompt' => '#ec4899',
            'extra_credits_10' => '#8b5cf6',
            'extra_credits_100' => '#8b5cf6',
        ];
        return $colors[ $slug ] ?? '#6366f1';
    }

    private function render_plan_form() {
        $action = $_GET['action'] ?? '';
        if ( ! in_array( $action, [ 'edit', 'new' ] ) ) {
            return;
        }

        $plan_slug = sanitize_text_field( $_GET['plan'] ?? '' );
        $plan = [];
        $is_edit = false;

        if ( $action === 'edit' && ! empty( $plan_slug ) ) {
            $plan = $this->get_plan( $plan_slug );
            if ( ! $plan ) {
                echo '<div class="notice notice-error"><p>Piano non trovato!</p></div>';
                return;
            }
            $is_edit = true;
        }

        $features = $this->get_available_features();

        ?>
        <div class="bg-white rounded-lg shadow p-6 max-w-2xl" id="plan-form">
            <h2 class="text-xl font-bold mb-4"><?php echo $is_edit ? '✏️ Modifica: ' . esc_html( $plan['name'] ) : '➕ Nuovo Piano'; ?></h2>

            <form method="post" class="space-y-4">
                <?php wp_nonce_field( 'ipv_save_plan' ); ?>
                <input type="hidden" name="plan_action" value="<?php echo $is_edit ? 'edit' : 'new'; ?>" />
                <?php if ( $is_edit ) : ?>
                    <input type="hidden" name="original_slug" value="<?php echo esc_attr( $plan_slug ); ?>" />
                <?php endif; ?>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Nome Piano *</label>
                        <input type="text" name="plan_name" required
                               value="<?php echo esc_attr( $plan['name'] ?? '' ); ?>"
                               class="w-full px-3 py-2 border rounded" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Slug *</label>
                        <input type="text" name="plan_slug" required pattern="[a-z0-9_-]+"
                               value="<?php echo esc_attr( $plan['slug'] ?? '' ); ?>"
                               class="w-full px-3 py-2 border rounded <?php echo $is_edit ? 'bg-gray-100' : ''; ?>"
                               <?php echo $is_edit ? 'readonly' : ''; ?> />
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Descrizione</label>
                    <input type="text" name="plan_description"
                           value="<?php echo esc_attr( $plan['description'] ?? '' ); ?>"
                           class="w-full px-3 py-2 border rounded" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Prezzo (€)</label>
                        <div class="flex">
                            <input type="number" name="plan_price" step="0.01" min="0"
                                   value="<?php echo esc_attr( $plan['price'] ?? 0 ); ?>"
                                   class="w-24 px-3 py-2 border rounded-l" />
                            <select name="plan_price_period" class="px-3 py-2 border-t border-b border-r rounded-r">
                                <option value="month" <?php selected( $plan['price_period'] ?? '', 'month' ); ?>>/ mese</option>
                                <option value="year" <?php selected( $plan['price_period'] ?? '', 'year' ); ?>>/ anno</option>
                                <option value="once" <?php selected( $plan['price_period'] ?? '', 'once' ); ?>>una tantum</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Crediti</label>
                        <div class="flex">
                            <input type="number" name="plan_credits" min="0"
                                   value="<?php echo esc_attr( $plan['credits'] ?? 10 ); ?>"
                                   class="w-24 px-3 py-2 border rounded-l" />
                            <select name="plan_credits_period" class="px-3 py-2 border-t border-b border-r rounded-r">
                                <option value="month" <?php selected( $plan['credits_period'] ?? '', 'month' ); ?>>/ mese</option>
                                <option value="year" <?php selected( $plan['credits_period'] ?? '', 'year' ); ?>>/ anno</option>
                                <option value="once" <?php selected( $plan['credits_period'] ?? '', 'once' ); ?>>una tantum</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Limite Siti</label>
                        <input type="number" name="plan_activations" min="0" max="999"
                               value="<?php echo esc_attr( $plan['activations'] ?? 1 ); ?>"
                               class="w-24 px-3 py-2 border rounded" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Ordine</label>
                        <input type="number" name="plan_sort_order" min="1" max="99"
                               value="<?php echo esc_attr( $plan['sort_order'] ?? 10 ); ?>"
                               class="w-20 px-3 py-2 border rounded" />
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">Funzionalità</label>
                    <div class="space-y-2">
                        <?php foreach ( $features as $feature_key => $feature ) : ?>
                            <label class="flex items-center">
                                <input type="checkbox" name="plan_features[<?php echo esc_attr( $feature_key ); ?>]" value="1"
                                       <?php checked( ! empty( $plan['features'][ $feature_key ] ) ); ?>
                                       class="mr-2" />
                                <?php echo esc_html( $feature['icon'] . ' ' . $feature['name'] ); ?>
                                <span class="text-gray-500 text-sm ml-2">— <?php echo esc_html( $feature['description'] ); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div>
                    <label class="flex items-center">
                        <input type="checkbox" name="plan_is_active" value="1"
                               <?php checked( $plan['is_active'] ?? true ); ?>
                               class="mr-2" />
                        Piano attivo (visibile e acquistabile)
                    </label>
                </div>

                <div class="flex gap-3 pt-4">
                    <button type="submit" name="ipv_save_plan"
                            class="px-6 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                        💾 Salva Piano
                    </button>
                    <a href="<?php echo admin_url( 'admin.php?page=ipv-vendor-plans' ); ?>"
                       class="px-6 py-2 bg-gray-200 rounded hover:bg-gray-300">
                        Annulla
                    </a>
                </div>
            </form>
        </div>
        <?php
    }

    private function handle_form_submission() {
        if ( isset( $_POST['ipv_reset_plans'] ) ) {
            check_admin_referer( 'ipv_reset_plans' );
            delete_option( self::OPTION_NAME );
            echo '<div class="notice notice-success"><p>✅ Piani ripristinati!</p></div>';
            return;
        }

        if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['plan'] ) ) {
            $slug = sanitize_text_field( $_GET['plan'] );
            check_admin_referer( 'delete_plan_' . $slug );

            if ( $this->delete_plan( $slug ) ) {
                echo '<div class="notice notice-success"><p>✅ Piano eliminato!</p></div>';
            }
            return;
        }

        if ( isset( $_POST['ipv_save_plan'] ) ) {
            check_admin_referer( 'ipv_save_plan' );

            $plan_action = sanitize_text_field( $_POST['plan_action'] ?? 'new' );
            $slug = sanitize_title( $_POST['plan_slug'] ?? '' );
            $name = sanitize_text_field( $_POST['plan_name'] ?? '' );

            if ( empty( $slug ) || empty( $name ) ) {
                echo '<div class="notice notice-error"><p>Nome e slug obbligatori!</p></div>';
                return;
            }

            if ( $plan_action === 'new' && $this->get_plan( $slug ) ) {
                echo '<div class="notice notice-error"><p>Slug già esistente!</p></div>';
                return;
            }

            $features = [];
            $available_features = $this->get_available_features();
            foreach ( array_keys( $available_features ) as $feature_key ) {
                $features[ $feature_key ] = ! empty( $_POST['plan_features'][ $feature_key ] );
            }

            $plan_data = [
                'name' => $name,
                'slug' => $slug,
                'description' => sanitize_text_field( $_POST['plan_description'] ?? '' ),
                'price' => floatval( $_POST['plan_price'] ?? 0 ),
                'price_period' => sanitize_text_field( $_POST['plan_price_period'] ?? 'year' ),
                'credits' => absint( $_POST['plan_credits'] ?? 10 ),
                'credits_period' => sanitize_text_field( $_POST['plan_credits_period'] ?? 'year' ),
                'activations' => absint( $_POST['plan_activations'] ?? 1 ),
                'features' => $features,
                'sort_order' => absint( $_POST['plan_sort_order'] ?? 10 ),
                'is_active' => ! empty( $_POST['plan_is_active'] ),
            ];

            $this->save_plan( $slug, $plan_data );

            echo '<div class="notice notice-success"><p>✅ Piano salvato!</p></div>';
            echo '<script>window.location.href = "' . admin_url( 'admin.php?page=ipv-vendor-plans' ) . '";</script>';
        }
    }
}

// Initialize
IPV_Vendor_Plans_Manager::instance();
