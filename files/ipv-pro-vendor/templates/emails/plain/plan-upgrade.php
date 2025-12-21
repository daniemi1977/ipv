<?php
/**
 * IPV Email Template - Plan Upgrade (Plain Text)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$plans_manager = IPV_Vendor_Plans_Manager::instance();
$new_plan_data = $plans_manager->get_plan( $new_plan );
$old_plan_data = $plans_manager->get_plan( $old_plan );
$customer_name = IPV_Vendor_Email_Notifications::get_license_name( $license );

echo "= " . $email_heading . " =\n\n";

printf( esc_html__( 'Ciao %s,', 'ipv-pro-vendor' ), esc_html( $customer_name ) );
echo "\n\n";

echo esc_html__( 'Complimenti! Il tuo piano IPV Pro è stato aggiornato con successo.', 'ipv-pro-vendor' );
echo "\n\n";

echo "----------------------------------------\n";
echo esc_html__( 'IL TUO NUOVO PIANO', 'ipv-pro-vendor' ) . "\n";
echo "----------------------------------------\n";
echo esc_html( $new_plan_data['name'] ?? ucfirst( $new_plan ) ) . "\n";
echo "€" . number_format( $new_plan_data['price'] ?? 0, 2, ',', '.' ) . "/anno\n\n";

echo esc_html__( 'Cosa cambia:', 'ipv-pro-vendor' ) . "\n";
echo "- " . esc_html__( 'Piano precedente:', 'ipv-pro-vendor' ) . " " . esc_html( $old_plan_data['name'] ?? ucfirst( $old_plan ) ) . "\n";
echo "- " . esc_html__( 'Crediti mensili:', 'ipv-pro-vendor' ) . " " . esc_html( $new_plan_data['credits'] ?? 0 );
if ( isset( $old_plan_data['credits'] ) && isset( $new_plan_data['credits'] ) ) {
    echo " (+" . ( $new_plan_data['credits'] - $old_plan_data['credits'] ) . ")";
}
echo "\n";
echo "- " . esc_html__( 'Siti attivabili:', 'ipv-pro-vendor' ) . " " . esc_html( $new_plan_data['activations'] ?? 1 ) . "\n\n";

echo esc_html__( 'Vai al tuo Account:', 'ipv-pro-vendor' ) . "\n";
echo esc_url( wc_get_account_endpoint_url( 'ipv-licenses' ) ) . "\n\n";

echo esc_html__( 'Grazie per aver scelto IPV Pro!', 'ipv-pro-vendor' ) . "\n";

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );
