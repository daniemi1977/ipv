<?php
/**
 * Credits Dashboard Widget
 *
 * Displays remaining credits in WordPress dashboard with purchase link
 *
 * @package IPV_Production_System_Pro
 * @since 10.3.1
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_Credits_Widget {

    /**
     * Initialize
     */
    public static function init() {
        add_action( 'wp_dashboard_setup', [ __CLASS__, 'register_widget' ] );
    }

    /**
     * Register dashboard widget
     */
    public static function register_widget() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        wp_add_dashboard_widget(
            'ipv_credits_widget',
            '📊 IPV - Crediti Rimanenti',
            [ __CLASS__, 'render_widget' ]
        );
    }

    /**
     * Render widget content
     */
    public static function render_widget() {
        $license_info = get_option( 'ipv_license_info', [] );

        // Check if license is active (v10.3.1-FIXED - no API_Client dependency)
        $is_active = ! empty( $license_info ) && ( ! isset( $license_info['status'] ) || $license_info['status'] === 'active' );

        if ( ! $is_active ) {
            echo '<div class="ipv-credits-widget">';
            echo '<p class="text-warning"><strong>⚠️ Licenza non attiva</strong></p>';
            echo '<p>Attiva la tua licenza per iniziare a utilizzare IPV Production System Pro.</p>';
            echo '<a href="' . admin_url( 'edit.php?post_type=ipv_video&page=ipv-license' ) . '" class="button button-primary">Attiva Licenza</a>';
            echo '</div>';
            return;
        }

        // Get credits data
        $credits = $license_info['credits'] ?? [];
        $credits_remaining = $credits['credits_remaining'] ?? 0;
        $credits_total = $credits['credits_total'] ?? 0;
        $percentage = $credits['percentage'] ?? 0;
        $reset_date = $credits['reset_date_formatted'] ?? 'N/A';

        // Determine status and color
        $status_class = 'success';
        $status_icon = '✅';
        $status_text = 'Crediti sufficienti';

        if ( $percentage < 20 ) {
            $status_class = 'danger';
            $status_icon = '🔴';
            $status_text = 'Crediti in esaurimento!';
        } elseif ( $percentage < 50 ) {
            $status_class = 'warning';
            $status_icon = '⚠️';
            $status_text = 'Crediti limitati';
        }

        ?>
        <div class="ipv-credits-widget">
            <style>
                .ipv-credits-widget { padding: 10px 0; }
                .ipv-credits-widget .credits-display {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    margin-bottom: 15px;
                }
                .ipv-credits-widget .credits-number {
                    font-size: 32px;
                    font-weight: bold;
                    line-height: 1;
                }
                .ipv-credits-widget .credits-total {
                    font-size: 16px;
                    color: #666;
                    margin-left: 5px;
                }
                .ipv-credits-widget .credits-status {
                    font-size: 14px;
                    padding: 5px 10px;
                    border-radius: 3px;
                    font-weight: 500;
                }
                .ipv-credits-widget .credits-status.success {
                    background: #d4edda;
                    color: #155724;
                }
                .ipv-credits-widget .credits-status.warning {
                    background: #fff3cd;
                    color: #856404;
                }
                .ipv-credits-widget .credits-status.danger {
                    background: #f8d7da;
                    color: #721c24;
                }
                .ipv-credits-widget .progress-bar {
                    height: 20px;
                    background: #e9ecef;
                    border-radius: 4px;
                    overflow: hidden;
                    margin-bottom: 15px;
                }
                .ipv-credits-widget .progress-fill {
                    height: 100%;
                    transition: width 0.3s ease;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: white;
                    font-size: 11px;
                    font-weight: bold;
                }
                .ipv-credits-widget .progress-fill.success { background: #28a745; }
                .ipv-credits-widget .progress-fill.warning { background: #ffc107; color: #333; }
                .ipv-credits-widget .progress-fill.danger { background: #dc3545; }
                .ipv-credits-widget .credits-info {
                    font-size: 12px;
                    color: #666;
                    margin-bottom: 15px;
                }
                .ipv-credits-widget .credits-actions {
                    display: flex;
                    gap: 8px;
                }
                .ipv-credits-widget .button-small {
                    font-size: 12px;
                    padding: 5px 12px;
                    height: auto;
                    line-height: 1.4;
                }
            </style>

            <div class="credits-display">
                <div>
                    <span class="credits-number"><?php echo number_format_i18n( $credits_remaining ); ?></span>
                    <span class="credits-total">/ <?php echo number_format_i18n( $credits_total ); ?></span>
                </div>
                <span class="credits-status <?php echo esc_attr( $status_class ); ?>">
                    <?php echo $status_icon; ?> <?php echo esc_html( $status_text ); ?>
                </span>
            </div>

            <div class="progress-bar">
                <div class="progress-fill <?php echo esc_attr( $status_class ); ?>" style="width: <?php echo $percentage; ?>%">
                    <?php echo number_format( $percentage, 0 ); ?>%
                </div>
            </div>

            <div class="credits-info">
                📅 <strong>Reset crediti:</strong> <?php echo esc_html( $reset_date ); ?>
                <?php if ( ! empty( $license_info['variant'] ) ) : ?>
                    <br>
                    🎯 <strong>Piano:</strong> <?php echo esc_html( ucfirst( $license_info['variant'] ) ); ?>
                <?php endif; ?>
            </div>

            <?php if ( $percentage < 50 ) : ?>
                <div class="credits-actions">
                    <?php
                    // Get purchase URL from server settings
                    $server_url = get_option( 'ipv_vendor_url', '' );
                    $purchase_url = trailingslashit( $server_url ) . 'my-account/ipv-credits/';
                    ?>
                    <a href="<?php echo esc_url( $purchase_url ); ?>"
                       class="button button-primary button-small"
                       target="_blank">
                        💳 Acquista Crediti Extra
                    </a>
                    <a href="<?php echo admin_url( 'edit.php?post_type=ipv_video&page=ipv-license' ); ?>"
                       class="button button-secondary button-small">
                        🔄 Aggiorna Licenza
                    </a>
                </div>
            <?php else : ?>
                <div class="credits-actions">
                    <a href="<?php echo admin_url( 'edit.php?post_type=ipv_video&page=ipv-license' ); ?>"
                       class="button button-secondary button-small">
                        🔄 Aggiorna Licenza
                    </a>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}
