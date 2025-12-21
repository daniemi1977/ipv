<?php
/**
 * IPV Email Template - Golden Prompt (Plain Text)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$plans_manager = IPV_Vendor_Plans_Manager::instance();
$golden_prompt = $plans_manager->get_plan( 'golden_prompt' );
$credits_added = $golden_prompt['credits'] ?? 500;
$customer_name = IPV_Vendor_Email_Notifications::get_license_name( $license );

echo "= " . $email_heading . " =\n\n";

printf( esc_html__( 'Ciao %s,', 'ipv-pro-vendor' ), esc_html( $customer_name ) );
echo "\n\n";

echo esc_html__( 'Grazie per il tuo acquisto! Il tuo Golden Prompt è stato attivato con successo.', 'ipv-pro-vendor' );
echo "\n\n";

echo "****************************************\n";
echo "         GOLDEN PROMPT ATTIVATO         \n";
echo "              +" . $credits_added . " CREDITI              \n";
echo "****************************************\n\n";

echo esc_html__( 'Il tuo nuovo saldo:', 'ipv-pro-vendor' ) . " " . esc_html( $license->credits_remaining ?? 0 ) . " " . esc_html__( 'crediti', 'ipv-pro-vendor' ) . "\n\n";

echo esc_html__( 'Come funziona il Golden Prompt?', 'ipv-pro-vendor' ) . "\n";
echo "- " . esc_html__( 'I crediti vengono utilizzati prima di quelli del piano', 'ipv-pro-vendor' ) . "\n";
echo "- " . esc_html__( 'Non scadono mai e non vengono resettati', 'ipv-pro-vendor' ) . "\n";
echo "- " . esc_html__( 'Puoi acquistare più Golden Prompt quando vuoi', 'ipv-pro-vendor' ) . "\n\n";

echo esc_html__( 'Vai al Portafoglio:', 'ipv-pro-vendor' ) . "\n";
echo esc_url( wc_get_account_endpoint_url( 'ipv-wallet' ) ) . "\n\n";

if ( $order ) {
    printf( esc_html__( 'Ordine #%s', 'ipv-pro-vendor' ), esc_html( $order->get_id() ) );
    echo "\n\n";
}

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );
