<?php
/**
 * IPV Email Template - Credits Depleted (Plain Text)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$plans_manager = IPV_Vendor_Plans_Manager::instance();
$plan = $plans_manager->get_plan( $license->variant_slug );
$customer_name = IPV_Vendor_Email_Notifications::get_license_name( $license );

$credits_manager = IPV_Vendor_Credits_Manager::instance();
$credits_info = $credits_manager->get_credits_info( $license );
$days_to_reset = $credits_info['days_until_reset'] ?? 0;

echo "= " . $email_heading . " =\n\n";

printf( esc_html__( 'Ciao %s,', 'ipv-pro-vendor' ), esc_html( $customer_name ) );
echo "\n\n";

echo esc_html__( 'I crediti del tuo piano IPV Pro sono esauriti.', 'ipv-pro-vendor' );
echo "\n\n";

echo "****************************************\n";
echo "           CREDITI ESAURITI             \n";
echo "****************************************\n\n";

if ( $days_to_reset > 0 ) {
    printf( esc_html__( 'Prossimo reset automatico tra %d giorni', 'ipv-pro-vendor' ), $days_to_reset );
    echo "\n\n";
}

echo esc_html__( 'Non vuoi aspettare? Ecco le tue opzioni:', 'ipv-pro-vendor' ) . "\n\n";

echo "1. " . esc_html__( 'Golden Prompt - €69', 'ipv-pro-vendor' ) . "\n";
echo "   " . esc_html__( '+500 crediti istantanei che non scadono mai', 'ipv-pro-vendor' ) . "\n\n";

echo "2. " . esc_html__( 'Crediti Extra - da €5', 'ipv-pro-vendor' ) . "\n";
echo "   " . esc_html__( 'Pacchetti da 10 o 100 crediti aggiuntivi', 'ipv-pro-vendor' ) . "\n\n";

echo esc_html__( 'Ricarica Ora:', 'ipv-pro-vendor' ) . "\n";
echo esc_url( wc_get_account_endpoint_url( 'ipv-wallet' ) ) . "\n\n";

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );
