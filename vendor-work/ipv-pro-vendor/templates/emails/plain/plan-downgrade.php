<?php
/**
 * IPV Email Template - Plan Downgrade (Plain Text)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$plans_manager = IPV_Vendor_Plans_Manager::instance();
$new_plan_data = $plans_manager->get_plan( $new_plan );
$old_plan_data = $plans_manager->get_plan( $old_plan );
$customer_name = IPV_Vendor_Email_Notifications::get_license_name( $license );

echo "= " . $email_heading . " =\n\n";

printf( esc_html__( 'Ciao %s,', 'ipv-pro-vendor' ), esc_html( $customer_name ) );
echo "\n\n";

echo esc_html__( 'Il tuo piano IPV Pro è stato modificato come richiesto.', 'ipv-pro-vendor' );
echo "\n\n";

echo "! " . esc_html__( 'I tuoi crediti sono stati adeguati al nuovo piano.', 'ipv-pro-vendor' ) . "\n\n";

echo "----------------------------------------\n";
echo esc_html__( 'IL TUO PIANO ATTUALE', 'ipv-pro-vendor' ) . "\n";
echo "----------------------------------------\n";
echo esc_html( $new_plan_data['name'] ?? ucfirst( $new_plan ) ) . "\n";
if ( ( $new_plan_data['price'] ?? 0 ) > 0 ) {
    echo "€" . number_format( $new_plan_data['price'], 2, ',', '.' ) . "/anno\n\n";
} else {
    echo esc_html__( 'Gratuito', 'ipv-pro-vendor' ) . "\n\n";
}

echo esc_html__( 'Riepilogo:', 'ipv-pro-vendor' ) . "\n";
echo "- " . esc_html__( 'Piano precedente:', 'ipv-pro-vendor' ) . " " . esc_html( $old_plan_data['name'] ?? ucfirst( $old_plan ) ) . "\n";
echo "- " . esc_html__( 'Nuovi crediti mensili:', 'ipv-pro-vendor' ) . " " . esc_html( $new_plan_data['credits'] ?? 0 ) . "\n";
echo "- " . esc_html__( 'Siti attivabili:', 'ipv-pro-vendor' ) . " " . esc_html( $new_plan_data['activations'] ?? 1 ) . "\n\n";

echo esc_html__( 'Hai bisogno di più crediti? Puoi sempre fare un upgrade:', 'ipv-pro-vendor' ) . "\n";
echo esc_url( wc_get_account_endpoint_url( 'ipv-upgrade' ) ) . "\n\n";

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );
