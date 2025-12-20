<?php
/**
 * Credits Admin Notices
 *
 * Displays admin notices when credits are low or depleted
 *
 * @package IPV_Production_System_Pro
 * @since 10.3.1
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_Credits_Notices {

    /**
     * Threshold for low credits warning (percentage)
     */
    const LOW_CREDITS_THRESHOLD = 20;

    /**
     * Threshold for very low credits alert (percentage)
     */
    const VERY_LOW_CREDITS_THRESHOLD = 10;

    /**
     * Initialize
     */
    public static function init() {
        add_action( 'admin_notices', [ __CLASS__, 'show_credits_notices' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_styles' ] );
    }

    /**
     * Enqueue inline styles for notices
     */
    public static function enqueue_styles() {
        ?>
        <style>
            .ipv-credits-notice {
                position: relative;
                padding: 15px 20px;
                border-left-width: 4px;
            }
            .ipv-credits-notice .notice-icon {
                font-size: 20px;
                margin-right: 10px;
                vertical-align: middle;
            }
            .ipv-credits-notice .notice-content {
                display: inline-block;
                vertical-align: middle;
            }
            .ipv-credits-notice .notice-title {
                font-weight: 600;
                font-size: 14px;
                margin-bottom: 5px;
            }
            .ipv-credits-notice .notice-message {
                font-size: 13px;
                margin-bottom: 10px;
            }
            .ipv-credits-notice .notice-actions {
                margin-top: 10px;
            }
            .ipv-credits-notice .notice-actions .button {
                margin-right: 8px;
            }
            .ipv-credits-notice .credits-bar {
                height: 8px;
                background: #e0e0e0;
                border-radius: 4px;
                overflow: hidden;
                margin-top: 8px;
                max-width: 300px;
            }
            .ipv-credits-notice .credits-bar-fill {
                height: 100%;
                transition: width 0.3s ease;
            }
            .ipv-credits-notice .credits-bar-fill.low { background: #ff9800; }
            .ipv-credits-notice .credits-bar-fill.critical { background: #f44336; }
            .ipv-credits-notice .credits-bar-fill.depleted { background: #d32f2f; }
        </style>
        <?php
    }

    /**
     * Show credits notices
     */
    public static function show_credits_notices() {
        // Only show to admins
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Get license info (v10.3.1-FIXED - no API_Client dependency)
        $license_info = get_option( 'ipv_license_info', [] );

        // Check if license is active
        if ( empty( $license_info ) || ( isset( $license_info['status'] ) && $license_info['status'] !== 'active' ) ) {
            return;
        }
        $credits = $license_info['credits'] ?? [];
        $credits_remaining = $credits['credits_remaining'] ?? 0;
        $credits_total = $credits['credits_total'] ?? 0;
        $percentage = $credits['percentage'] ?? 100;

        // Don't show notice if credits are sufficient
        if ( $percentage > self::LOW_CREDITS_THRESHOLD ) {
            return;
        }

        // Get server URL for purchase link
        $server_url = get_option( 'ipv_vendor_url', '' );
        $purchase_url = trailingslashit( $server_url ) . 'my-account/ipv-credits/';

        // Determine notice type
        if ( $credits_remaining == 0 ) {
            self::show_depleted_notice( $purchase_url );
        } elseif ( $percentage <= self::VERY_LOW_CREDITS_THRESHOLD ) {
            self::show_critical_notice( $credits_remaining, $credits_total, $percentage, $purchase_url );
        } else {
            self::show_low_notice( $credits_remaining, $credits_total, $percentage, $purchase_url );
        }
    }

    /**
     * Show depleted credits notice (0 credits)
     */
    private static function show_depleted_notice( $purchase_url ) {
        ?>
        <div class="notice notice-error ipv-credits-notice is-dismissible">
            <span class="notice-icon">🔴</span>
            <div class="notice-content">
                <div class="notice-title">Crediti IPV Esauriti</div>
                <div class="notice-message">
                    Non hai più crediti disponibili. Le operazioni AI (trascrizioni e descrizioni) sono sospese fino al prossimo reset o all'acquisto di crediti extra.
                </div>
                <div class="credits-bar">
                    <div class="credits-bar-fill depleted" style="width: 0%"></div>
                </div>
                <div class="notice-actions">
                    <a href="<?php echo esc_url( $purchase_url ); ?>"
                       class="button button-primary"
                       target="_blank">
                        💳 Acquista Crediti Extra
                    </a>
                    <a href="<?php echo admin_url( 'edit.php?post_type=ipv_video&page=ipv-license' ); ?>"
                       class="button button-secondary">
                        🔄 Aggiorna Licenza
                    </a>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Show critical low credits notice (< 10%)
     */
    private static function show_critical_notice( $credits_remaining, $credits_total, $percentage, $purchase_url ) {
        ?>
        <div class="notice notice-error ipv-credits-notice is-dismissible">
            <span class="notice-icon">⚠️</span>
            <div class="notice-content">
                <div class="notice-title">Crediti IPV in Esaurimento</div>
                <div class="notice-message">
                    <strong>Attenzione:</strong> Ti rimangono solo <strong><?php echo number_format_i18n( $credits_remaining ); ?></strong> crediti su <?php echo number_format_i18n( $credits_total ); ?> (<?php echo number_format( $percentage, 1 ); ?>%).
                    Acquista crediti extra per continuare a processare video senza interruzioni.
                </div>
                <div class="credits-bar">
                    <div class="credits-bar-fill critical" style="width: <?php echo $percentage; ?>%"></div>
                </div>
                <div class="notice-actions">
                    <a href="<?php echo esc_url( $purchase_url ); ?>"
                       class="button button-primary"
                       target="_blank">
                        💳 Acquista Crediti Extra
                    </a>
                    <a href="<?php echo admin_url( 'edit.php?post_type=ipv_video&page=ipv-license' ); ?>"
                       class="button button-secondary">
                        🔄 Verifica Licenza
                    </a>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Show low credits notice (< 20%)
     */
    private static function show_low_notice( $credits_remaining, $credits_total, $percentage, $purchase_url ) {
        ?>
        <div class="notice notice-warning ipv-credits-notice is-dismissible">
            <span class="notice-icon">⚠️</span>
            <div class="notice-content">
                <div class="notice-title">Crediti IPV Limitati</div>
                <div class="notice-message">
                    Hai ancora <strong><?php echo number_format_i18n( $credits_remaining ); ?></strong> crediti su <?php echo number_format_i18n( $credits_total ); ?> (<?php echo number_format( $percentage, 1 ); ?>%).
                    Considera l'acquisto di crediti extra per evitare interruzioni.
                </div>
                <div class="credits-bar">
                    <div class="credits-bar-fill low" style="width: <?php echo $percentage; ?>%"></div>
                </div>
                <div class="notice-actions">
                    <a href="<?php echo esc_url( $purchase_url ); ?>"
                       class="button button-primary"
                       target="_blank">
                        💳 Acquista Crediti Extra
                    </a>
                </div>
            </div>
        </div>
        <?php
    }
}
