<?php
/**
 * IPV Email Template - Golden Prompt Purchased
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$plans_manager = IPV_Vendor_Plans_Manager::instance();
$golden_prompt = $plans_manager->get_plan( 'golden_prompt' );
$credits_added = $golden_prompt['credits'] ?? 500;
$customer_name = IPV_Vendor_Email_Notifications::get_license_name( $license );

do_action( 'woocommerce_email_header', $email_heading, $email );
?>

<div style="margin-bottom: 40px;">
    <p style="font-size: 16px; color: #333;">
        <?php printf( esc_html__( 'Ciao %s,', 'ipv-pro-vendor' ), esc_html( $customer_name ) ); ?>
    </p>

    <p style="font-size: 16px; color: #333;">
        <?php esc_html_e( 'Grazie per il tuo acquisto! Il tuo Golden Prompt è stato attivato con successo.', 'ipv-pro-vendor' ); ?>
    </p>
</div>

<!-- Golden Card -->
<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom: 30px;">
    <tr>
        <td style="padding: 30px; background: linear-gradient(135deg, #f7971e 0%, #ffd200 100%); border-radius: 12px; text-align: center;">
            <div style="margin-bottom: 15px;">
                <span style="font-size: 48px;">&#11088;</span>
            </div>
            <span style="display: block; font-size: 14px; color: rgba(0,0,0,0.6); text-transform: uppercase; letter-spacing: 2px; margin-bottom: 10px;">
                <?php esc_html_e( 'Golden Prompt Attivato', 'ipv-pro-vendor' ); ?>
            </span>
            <span style="display: block; font-size: 48px; font-weight: bold; color: #333;">
                +<?php echo esc_html( $credits_added ); ?>
            </span>
            <span style="display: block; font-size: 18px; color: #333; margin-top: 5px;">
                <?php esc_html_e( 'crediti aggiunti', 'ipv-pro-vendor' ); ?>
            </span>
        </td>
    </tr>
</table>

<!-- Nuovo Saldo -->
<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom: 30px;">
    <tr>
        <td style="padding: 20px; background: #f8f9fa; border-radius: 8px; text-align: center;">
            <span style="display: block; font-size: 14px; color: #666; margin-bottom: 5px;">
                <?php esc_html_e( 'Il tuo nuovo saldo crediti', 'ipv-pro-vendor' ); ?>
            </span>
            <span style="display: block; font-size: 36px; font-weight: bold; color: #28a745;">
                <?php echo esc_html( $license->credits_remaining ?? 0 ); ?>
            </span>
            <span style="display: block; font-size: 14px; color: #666; margin-top: 5px;">
                <?php esc_html_e( 'crediti disponibili', 'ipv-pro-vendor' ); ?>
            </span>
        </td>
    </tr>
</table>

<!-- Info Box -->
<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom: 30px;">
    <tr>
        <td style="padding: 20px; background: #e8f4fd; border-radius: 8px;">
            <h3 style="margin: 0 0 10px 0; color: #0c5460; font-size: 16px;">
                <?php esc_html_e( 'Come funziona il Golden Prompt?', 'ipv-pro-vendor' ); ?>
            </h3>
            <ul style="margin: 0; padding-left: 20px; color: #0c5460; font-size: 14px; line-height: 1.8;">
                <li><?php esc_html_e( 'I crediti Golden Prompt vengono utilizzati prima dei crediti del piano', 'ipv-pro-vendor' ); ?></li>
                <li><?php esc_html_e( 'Non scadono mai e non vengono resettati con il periodo', 'ipv-pro-vendor' ); ?></li>
                <li><?php esc_html_e( 'Puoi acquistare più Golden Prompt quando vuoi', 'ipv-pro-vendor' ); ?></li>
            </ul>
        </td>
    </tr>
</table>

<!-- CTA -->
<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom: 30px;">
    <tr>
        <td align="center">
            <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'ipv-wallet' ) ); ?>"
               style="display: inline-block; padding: 15px 30px; background: #f7971e; color: #fff; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 16px;">
                <?php esc_html_e( 'Vai al Portafoglio', 'ipv-pro-vendor' ); ?>
            </a>
        </td>
    </tr>
</table>

<?php if ( $order ) : ?>
<p style="font-size: 12px; color: #999; text-align: center;">
    <?php printf( esc_html__( 'Ordine #%s', 'ipv-pro-vendor' ), esc_html( $order->get_id() ) ); ?>
</p>
<?php endif; ?>

<?php do_action( 'woocommerce_email_footer', $email ); ?>
