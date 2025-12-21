<?php
/**
 * IPV Email Template - Low Credits (Plain Text)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$plans_manager = IPV_Vendor_Plans_Manager::instance();
$plan = $plans_manager->get_plan( $license->variant_slug );
$customer_name = IPV_Vendor_Email_Notifications::get_license_name( $license );
$credits_remaining = $license->credits_remaining ?? 0;
$credits_total = $license->credits_total ?? 0;

echo "= " . $email_heading . " =\n\n";

printf( esc_html__( 'Ciao %s,', 'ipv-pro-vendor' ), esc_html( $customer_name ) );
echo "\n\n";

echo esc_html__( 'Ti avvisiamo che i crediti del tuo piano IPV Pro stanno per esaurirsi.', 'ipv-pro-vendor' );
echo "\n\n";

echo "!! " . esc_html__( 'ATTENZIONE: CREDITI IN ESAURIMENTO', 'ipv-pro-vendor' ) . " !!\n";
echo esc_html__( 'Crediti rimasti:', 'ipv-pro-vendor' ) . " " . $credits_remaining . "/" . $credits_total . " (" . round( $percentage, 1 ) . "%)\n\n";

echo "----------------------------------------\n";
echo esc_html__( 'COME OTTENERE PIÙ CREDITI', 'ipv-pro-vendor' ) . "\n";
echo "----------------------------------------\n\n";

echo "1. " . esc_html__( 'Golden Prompt', 'ipv-pro-vendor' ) . "\n";
echo "   " . esc_html__( '+500 crediti istantanei che non scadono mai', 'ipv-pro-vendor' ) . "\n\n";

echo "2. " . esc_html__( 'Upgrade del piano', 'ipv-pro-vendor' ) . "\n";
echo "   " . esc_html__( 'Passa a un piano superiore per più crediti mensili', 'ipv-pro-vendor' ) . "\n\n";

echo esc_html__( 'Acquista Crediti:', 'ipv-pro-vendor' ) . "\n";
echo esc_url( wc_get_account_endpoint_url( 'ipv-wallet' ) ) . "\n\n";

echo esc_html__( 'Upgrade Piano:', 'ipv-pro-vendor' ) . "\n";
echo esc_url( wc_get_account_endpoint_url( 'ipv-upgrade' ) ) . "\n\n";

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );
