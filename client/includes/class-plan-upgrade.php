<?php
/**
 * IPV Production System Pro - Plan Upgrade Manager
 *
 * Gestisce gli upgrade del piano subscription direttamente dal CLIENT
 * - Mostra piano attuale con dettagli credits
 * - Mostra piani disponibili per upgrade
 * - Link diretti al checkout WooCommerce del server
 * - Comparazione piani side-by-side
 *
 * @package IPV_Production_System_Pro
 * @version 10.3.2
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_Plan_Upgrade {

    /**
     * Initialize
     */
    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'add_menu_page' ], 25 );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
    }

    /**
     * Add menu page
     */
    public static function add_menu_page() {
        add_submenu_page(
            'edit.php?post_type=ipv_video',
            'Upgrade Piano',
            '⬆️ Upgrade Piano',
            'manage_options',
            'ipv-upgrade-plan',
            [ __CLASS__, 'render_page' ]
        );
    }

    /**
     * Enqueue assets
     */
    public static function enqueue_assets( $hook ) {
        if ( $hook !== 'ipv_video_page_ipv-upgrade-plan' ) {
            return;
        }

        wp_enqueue_style( 'ipv-upgrade-plan', IPV_PROD_PLUGIN_URL . 'assets/css/admin.css', [], IPV_PROD_VERSION );
    }

    /**
     * Render page
     */
    public static function render_page() {
        // Get license info
        $license_info = get_option( 'ipv_license_info', [] );
        $server_url = get_option( 'ipv_api_server_url', '' );

        if ( empty( $license_info ) || ! isset( $license_info['status'] ) || $license_info['status'] !== 'active' ) {
            self::render_no_license();
            return;
        }

        $current_plan = self::get_current_plan( $license_info );
        $available_plans = self::get_available_plans( $current_plan );

        ?>
        <div class="wrap ipv-prod-wrap">
            <h1>⬆️ Upgrade Piano Subscription</h1>

            <?php self::render_current_plan( $current_plan, $license_info ); ?>
            <?php self::render_upgrade_plans( $available_plans, $server_url ); ?>
            <?php self::render_plan_comparison(); ?>

        </div>
        <?php
    }

    /**
     * Render no license message
     */
    private static function render_no_license() {
        ?>
        <div class="wrap">
            <h1>⬆️ Upgrade Piano</h1>
            <div class="notice notice-error">
                <p><strong>Licenza non attiva</strong></p>
                <p>Per fare l'upgrade del piano devi prima attivare una licenza valida.</p>
                <p>
                    <a href="<?php echo admin_url( 'admin.php?page=ipv-settings&tab=license' ); ?>" class="button button-primary">
                        Attiva Licenza
                    </a>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Get current plan details
     */
    private static function get_current_plan( $license_info ) {
        $variant = $license_info['variant'] ?? 'unknown';
        $credits_monthly = $license_info['credits_monthly'] ?? 0;
        $credits_extra = $license_info['credits_extra'] ?? 0;
        $credits_total = $credits_monthly + $credits_extra;

        $plans_map = [
            'trial' => [
                'name' => 'Trial',
                'icon' => '🧪',
                'level' => 0,
                'credits' => 10
            ],
            'starter' => [
                'name' => 'Starter',
                'icon' => '🚀',
                'level' => 1,
                'credits' => 25
            ],
            'professional' => [
                'name' => 'Professional',
                'icon' => '💼',
                'level' => 2,
                'credits' => 100
            ],
            'business' => [
                'name' => 'Business',
                'icon' => '🏢',
                'level' => 3,
                'credits' => 500
            ]
        ];

        $plan = $plans_map[ $variant ] ?? [
            'name' => 'Unknown',
            'icon' => '❓',
            'level' => -1,
            'credits' => 0
        ];

        $plan['variant'] = $variant;
        $plan['credits_monthly'] = $credits_monthly;
        $plan['credits_extra'] = $credits_extra;
        $plan['credits_total'] = $credits_total;

        return $plan;
    }

    /**
     * Get available upgrade plans
     */
    private static function get_available_plans( $current_plan ) {
        $all_plans = [
            [
                'variant' => 'starter',
                'name' => 'Starter',
                'icon' => '🚀',
                'level' => 1,
                'credits' => 25,
                'price_monthly' => 19.95,
                'price_yearly' => 199.50,
                'features' => [
                    '25 crediti mensili',
                    'Trascrizioni illimitate',
                    'Descrizioni AI',
                    'Supporto email'
                ]
            ],
            [
                'variant' => 'professional',
                'name' => 'Professional',
                'icon' => '💼',
                'level' => 2,
                'credits' => 100,
                'price_monthly' => 49.95,
                'price_yearly' => 499.50,
                'features' => [
                    '100 crediti mensili',
                    'Trascrizioni illimitate',
                    'Descrizioni AI',
                    'Analytics avanzate',
                    'Supporto prioritario'
                ]
            ],
            [
                'variant' => 'business',
                'name' => 'Business',
                'icon' => '🏢',
                'level' => 3,
                'credits' => 500,
                'price_monthly' => 99.95,
                'price_yearly' => 999.50,
                'features' => [
                    '500 crediti mensili',
                    'Trascrizioni illimitate',
                    'Descrizioni AI',
                    'Analytics avanzate',
                    'White label',
                    'Supporto dedicato 24/7'
                ]
            ]
        ];

        // Filter plans above current level
        return array_filter( $all_plans, function( $plan ) use ( $current_plan ) {
            return $plan['level'] > $current_plan['level'];
        });
    }

    /**
     * Render current plan box
     */
    private static function render_current_plan( $plan, $license_info ) {
        $expiry = $license_info['expiry_date'] ?? 'Mai';
        $status_class = $plan['credits_total'] > 20 ? 'success' : ( $plan['credits_total'] > 5 ? 'warning' : 'error' );
        ?>
        <div class="ipv-card" style="margin: 20px 0; padding: 25px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h2 style="margin: 0 0 10px 0; color: white; font-size: 28px;">
                        <?php echo esc_html( $plan['icon'] ); ?> Piano Attuale: <strong><?php echo esc_html( $plan['name'] ); ?></strong>
                    </h2>
                    <p style="margin: 5px 0; font-size: 16px; opacity: 0.95;">
                        📊 <strong><?php echo number_format_i18n( $plan['credits_total'] ); ?> crediti</strong> disponibili
                        <span style="opacity: 0.8; margin-left: 10px;">
                            (<?php echo number_format_i18n( $plan['credits_monthly'] ); ?> mensili + <?php echo number_format_i18n( $plan['credits_extra'] ); ?> extra)
                        </span>
                    </p>
                    <p style="margin: 5px 0; font-size: 14px; opacity: 0.9;">
                        📅 Scadenza: <strong><?php echo esc_html( $expiry ); ?></strong>
                    </p>
                </div>
                <div style="text-align: center; background: rgba(255,255,255,0.15); padding: 20px; border-radius: 8px; min-width: 120px;">
                    <div style="font-size: 48px; margin-bottom: 5px;"><?php echo esc_html( $plan['icon'] ); ?></div>
                    <div style="font-size: 18px; font-weight: 600;"><?php echo esc_html( $plan['name'] ); ?></div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render upgrade plans
     */
    private static function render_upgrade_plans( $plans, $server_url ) {
        if ( empty( $plans ) ) {
            ?>
            <div class="notice notice-success" style="margin: 20px 0;">
                <p><strong>🎉 Congratulazioni!</strong></p>
                <p>Sei già sul piano massimo disponibile (Business). Non ci sono upgrade disponibili.</p>
                <p>Puoi acquistare <a href="<?php echo esc_url( $server_url ); ?>/shop/" target="_blank">crediti extra</a> se necessario.</p>
            </div>
            <?php
            return;
        }

        ?>
        <h2 style="margin: 30px 0 20px 0;">🚀 Piani Disponibili per Upgrade</h2>
        <p style="margin-bottom: 20px; color: #666;">Scegli il piano che fa per te e fai l'upgrade con un click:</p>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 40px;">
            <?php foreach ( $plans as $plan ): ?>
                <div class="ipv-plan-card" style="background: white; border: 2px solid #e5e7eb; border-radius: 12px; padding: 25px; transition: all 0.3s; position: relative; overflow: hidden;">
                    <div style="position: absolute; top: 0; right: 0; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 8px 15px; border-bottom-left-radius: 12px; font-size: 12px; font-weight: 600;">
                        UPGRADE
                    </div>

                    <div style="text-align: center; margin-bottom: 20px;">
                        <div style="font-size: 64px; margin-bottom: 10px;"><?php echo esc_html( $plan['icon'] ); ?></div>
                        <h3 style="margin: 0 0 5px 0; font-size: 24px;"><?php echo esc_html( $plan['name'] ); ?></h3>
                        <div style="font-size: 14px; color: #6b7280;">
                            <?php echo number_format_i18n( $plan['credits'] ); ?> crediti/mese
                        </div>
                    </div>

                    <div style="text-align: center; margin: 20px 0; padding: 15px; background: #f9fafb; border-radius: 8px;">
                        <div style="font-size: 14px; color: #6b7280; margin-bottom: 5px;">Mensile</div>
                        <div style="font-size: 32px; font-weight: 700; color: #111827;">
                            €<?php echo number_format( $plan['price_monthly'], 2 ); ?>
                        </div>
                        <div style="font-size: 12px; color: #9ca3af; margin-top: 3px;">/mese</div>

                        <div style="margin: 15px 0; padding-top: 15px; border-top: 1px solid #e5e7eb;">
                            <div style="font-size: 14px; color: #6b7280; margin-bottom: 5px;">Annuale</div>
                            <div style="font-size: 24px; font-weight: 700; color: #10b981;">
                                €<?php echo number_format( $plan['price_yearly'], 2 ); ?>
                            </div>
                            <div style="font-size: 11px; color: #10b981; margin-top: 3px;">
                                Risparmi €<?php echo number_format( ($plan['price_monthly'] * 12) - $plan['price_yearly'], 2 ); ?>
                            </div>
                        </div>
                    </div>

                    <ul style="list-style: none; padding: 0; margin: 20px 0;">
                        <?php foreach ( $plan['features'] as $feature ): ?>
                            <li style="padding: 8px 0; color: #374151; font-size: 14px;">
                                ✅ <?php echo esc_html( $feature ); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <div style="margin-top: 25px;">
                        <?php
                        // Build product URL from plan variant
                        $product_slug = 'ipv-pro-' . strtolower( $plan['variant'] );
                        $product_url = trailingslashit( $server_url ) . 'product/' . $product_slug . '/';
                        ?>
                        <a href="<?php echo esc_url( $product_url ); ?>"
                           target="_blank"
                           class="button button-primary button-large"
                           style="width: 100%; text-align: center; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; padding: 12px; font-size: 16px; font-weight: 600;">
                            🚀 Upgrade a <?php echo esc_html( $plan['name'] ); ?>
                        </a>
                        <p style="text-align: center; font-size: 11px; color: #9ca3af; margin: 10px 0 0 0;">
                            Verrai reindirizzato al checkout sicuro
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="notice notice-info" style="margin: 20px 0;">
            <p><strong>ℹ️ Come funziona l'upgrade:</strong></p>
            <ol style="margin: 10px 0 10px 20px;">
                <li>Clicca su "Upgrade" per il piano desiderato</li>
                <li>Completa il checkout su <strong><?php echo esc_html( parse_url( $server_url, PHP_URL_HOST ) ); ?></strong></li>
                <li>La tua licenza verrà aggiornata automaticamente</li>
                <li>I nuovi crediti saranno disponibili immediatamente</li>
            </ol>
        </div>
        <?php
    }

    /**
     * Render plan comparison table
     */
    private static function render_plan_comparison() {
        ?>
        <h2 style="margin: 40px 0 20px 0;">📊 Comparazione Piani</h2>

        <table class="widefat" style="margin-bottom: 30px;">
            <thead>
                <tr>
                    <th style="width: 30%;">Funzionalità</th>
                    <th style="text-align: center;">🧪 Trial</th>
                    <th style="text-align: center;">🚀 Starter</th>
                    <th style="text-align: center;">💼 Professional</th>
                    <th style="text-align: center;">🏢 Business</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Crediti Mensili</strong></td>
                    <td style="text-align: center;">10</td>
                    <td style="text-align: center;">25</td>
                    <td style="text-align: center;">100</td>
                    <td style="text-align: center;">500</td>
                </tr>
                <tr style="background: #f9fafb;">
                    <td><strong>Trascrizioni Video</strong></td>
                    <td style="text-align: center;">✅</td>
                    <td style="text-align: center;">✅</td>
                    <td style="text-align: center;">✅</td>
                    <td style="text-align: center;">✅</td>
                </tr>
                <tr>
                    <td><strong>Descrizioni AI</strong></td>
                    <td style="text-align: center;">✅</td>
                    <td style="text-align: center;">✅</td>
                    <td style="text-align: center;">✅</td>
                    <td style="text-align: center;">✅</td>
                </tr>
                <tr style="background: #f9fafb;">
                    <td><strong>Analytics</strong></td>
                    <td style="text-align: center;">Base</td>
                    <td style="text-align: center;">Base</td>
                    <td style="text-align: center;">✅ Avanzate</td>
                    <td style="text-align: center;">✅ Avanzate</td>
                </tr>
                <tr>
                    <td><strong>White Label</strong></td>
                    <td style="text-align: center;">❌</td>
                    <td style="text-align: center;">❌</td>
                    <td style="text-align: center;">❌</td>
                    <td style="text-align: center;">✅</td>
                </tr>
                <tr style="background: #f9fafb;">
                    <td><strong>Supporto</strong></td>
                    <td style="text-align: center;">Email</td>
                    <td style="text-align: center;">Email</td>
                    <td style="text-align: center;">Prioritario</td>
                    <td style="text-align: center;">24/7 Dedicato</td>
                </tr>
                <tr>
                    <td><strong>Prezzo Mensile</strong></td>
                    <td style="text-align: center;">€1.99</td>
                    <td style="text-align: center;">€19.95</td>
                    <td style="text-align: center;">€49.95</td>
                    <td style="text-align: center;">€99.95</td>
                </tr>
                <tr style="background: #f9fafb;">
                    <td><strong>Prezzo Annuale</strong></td>
                    <td style="text-align: center;">€1.99</td>
                    <td style="text-align: center;"><strong>€199.50</strong><br><small style="color: #10b981;">-17%</small></td>
                    <td style="text-align: center;"><strong>€499.50</strong><br><small style="color: #10b981;">-17%</small></td>
                    <td style="text-align: center;"><strong>€999.50</strong><br><small style="color: #10b981;">-17%</small></td>
                </tr>
            </tbody>
        </table>

        <style>
        .ipv-plan-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
            border-color: #667eea;
        }
        </style>
        <?php
    }
}

// Initialize
IPV_Prod_Plan_Upgrade::init();
