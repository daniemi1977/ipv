<?php
/**
 * IPV Email Template - Credits Reset (Plain Text)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$plans_manager = IPV_Vendor_Plans_Manager::instance();
$plan = $plans_manager->get_plan( $license->variant_slug );
$customer_name = IPV_Vendor_Email_Notifications::get_license_name( $license );

echo "= " . $email_heading . " =\n\n";

printf( esc_html__( 'Ciao %s,', 'ipv-pro-vendor' ), esc_html( $customer_name ) );
echo "\n\n";

echo esc_html__( 'Ottime notizie! I tuoi crediti IPV Pro sono stati rinnovati.', 'ipv-pro-vendor' );
echo "\n\n";

echo "****************************************\n";
echo "          CREDITI RINNOVATI             \n";
echo "            " . $new_credits . " CREDITI              \n";
echo "****************************************\n\n";

echo esc_html__( 'Il tuo piano:', 'ipv-pro-vendor' ) . " " . esc_html( $plan['name'] ?? ucfirst( $license->variant_slug ) ) . "\n";
echo esc_html__( 'Crediti per periodo:', 'ipv-pro-vendor' ) . " " . $new_credits . "\n";

$period = $plan['credits_period'] ?? 'year';
$next_reset = strtotime( '+1 ' . $period );
echo esc_html__( 'Prossimo rinnovo:', 'ipv-pro-vendor' ) . " " . date_i18n( get_option( 'date_format' ), $next_reset ) . "\n\n";

echo esc_html__( 'Suggerimenti per ottimizzare i tuoi crediti:', 'ipv-pro-vendor' ) . "\n";
echo "- " . esc_html__( 'Utilizza prompt chiari e specifici', 'ipv-pro-vendor' ) . "\n";
echo "- " . esc_html__( 'Salva i tuoi prompt preferiti', 'ipv-pro-vendor' ) . "\n";
echo "- " . esc_html__( 'Considera un upgrade se i crediti non bastano', 'ipv-pro-vendor' ) . "\n\n";

echo esc_html__( 'Inizia a Creare:', 'ipv-pro-vendor' ) . "\n";
echo esc_url( wc_get_account_endpoint_url( 'ipv-licenses' ) ) . "\n\n";

echo esc_html__( 'Buon lavoro con IPV Pro!', 'ipv-pro-vendor' ) . "\n\n";

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );
