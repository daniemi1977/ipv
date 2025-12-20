<?php
/**
 * IPV Webhook Handler
 *
 * Gestisce webhook da WooCommerce e altri servizi
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Vendor_Webhook_Handler {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Webhooks WooCommerce già gestiti in class-woocommerce-integration.php
        // Questo file è pronto per webhook futuri (es. Stripe, PayPal, ecc.)
    }

    /**
     * Log webhook call
     */
    public function log_webhook( $source, $event, $data = [] ) {
        error_log( sprintf(
            'IPV Vendor Webhook: %s - %s - %s',
            $source,
            $event,
            json_encode( $data )
        ));
    }
}
