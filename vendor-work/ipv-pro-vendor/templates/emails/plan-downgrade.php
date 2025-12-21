<?php
/**
 * IPV Email Template - Plan Downgrade
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$plans_manager = IPV_Vendor_Plans_Manager::instance();
$new_plan_data = $plans_manager->get_plan( $new_plan );
$old_plan_data = $plans_manager->get_plan( $old_plan );
$customer_name = IPV_Vendor_Email_Notifications::get_license_name( $license );

do_action( 'woocommerce_email_header', $email_heading, $email );
?>

<div style="margin-bottom: 40px;">
    <p style="font-size: 16px; color: #333;">
        <?php printf( esc_html__( 'Ciao %s,', 'ipv-pro-vendor' ), esc_html( $customer_name ) ); ?>
    </p>

    <p style="font-size: 16px; color: #333;">
        <?php esc_html_e( 'Il tuo piano IPV Pro è stato modificato come richiesto.', 'ipv-pro-vendor' ); ?>
    </p>
</div>

<!-- Notice -->
<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom: 30px;">
    <tr>
        <td style="padding: 15px 20px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">
            <p style="margin: 0; color: #856404; font-size: 14px;">
                <?php esc_html_e( 'I tuoi crediti sono stati adeguati al nuovo piano. Eventuali crediti in eccesso non sono stati trasferiti.', 'ipv-pro-vendor' ); ?>
            </p>
        </td>
    </tr>
</table>

<!-- Piano Card -->
<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom: 30px;">
    <tr>
        <td style="padding: 25px; background: #f8f9fa; border: 2px solid #e9ecef; border-radius: 12px;">
            <table cellpadding="0" cellspacing="0" border="0" width="100%">
                <tr>
                    <td align="center">
                        <span style="font-size: 14px; color: #666; text-transform: uppercase; letter-spacing: 1px;">
                            <?php esc_html_e( 'Il tuo piano attuale', 'ipv-pro-vendor' ); ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <td align="center" style="padding-top: 10px;">
                        <span style="font-size: 28px; font-weight: bold; color: #333;">
                            <?php echo esc_html( $new_plan_data['name'] ?? ucfirst( $new_plan ) ); ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <td align="center" style="padding-top: 15px;">
                        <span style="font-size: 20px; color: #666;">
                            <?php if ( ( $new_plan_data['price'] ?? 0 ) > 0 ) : ?>
                                €<?php echo number_format( $new_plan_data['price'], 2, ',', '.' ); ?>
                                <span style="font-size: 14px;">/anno</span>
                            <?php else : ?>
                                <?php esc_html_e( 'Gratuito', 'ipv-pro-vendor' ); ?>
                            <?php endif; ?>
                        </span>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<!-- Dettagli -->
<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom: 30px;">
    <tr>
        <td style="padding: 20px; background: #fff; border: 1px solid #e9ecef; border-radius: 8px;">
            <h3 style="margin: 0 0 15px 0; color: #333; font-size: 18px;">
                <?php esc_html_e( 'Riepilogo modifiche', 'ipv-pro-vendor' ); ?>
            </h3>

            <table cellpadding="0" cellspacing="0" border="0" width="100%">
                <tr>
                    <td style="padding: 10px 0; border-bottom: 1px solid #e9ecef;">
                        <span style="color: #666;">
                            <?php esc_html_e( 'Piano precedente:', 'ipv-pro-vendor' ); ?>
                        </span>
                    </td>
                    <td style="padding: 10px 0; border-bottom: 1px solid #e9ecef; text-align: right;">
                        <span style="color: #333;">
                            <?php echo esc_html( $old_plan_data['name'] ?? ucfirst( $old_plan ) ); ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 10px 0; border-bottom: 1px solid #e9ecef;">
                        <span style="color: #666;">
                            <?php esc_html_e( 'Nuovi crediti mensili:', 'ipv-pro-vendor' ); ?>
                        </span>
                    </td>
                    <td style="padding: 10px 0; border-bottom: 1px solid #e9ecef; text-align: right;">
                        <span style="color: #333; font-weight: bold;">
                            <?php echo esc_html( $new_plan_data['credits'] ?? 0 ); ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 10px 0;">
                        <span style="color: #666;">
                            <?php esc_html_e( 'Siti attivabili:', 'ipv-pro-vendor' ); ?>
                        </span>
                    </td>
                    <td style="padding: 10px 0; text-align: right;">
                        <span style="color: #333; font-weight: bold;">
                            <?php echo esc_html( $new_plan_data['activations'] ?? 1 ); ?>
                        </span>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<!-- Upgrade CTA -->
<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom: 30px; background: #e8f4fd; border-radius: 8px;">
    <tr>
        <td style="padding: 20px; text-align: center;">
            <p style="margin: 0 0 15px 0; color: #0c5460; font-size: 14px;">
                <?php esc_html_e( 'Hai bisogno di più crediti? Puoi sempre tornare al tuo piano precedente o fare un upgrade.', 'ipv-pro-vendor' ); ?>
            </p>
            <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'ipv-upgrade' ) ); ?>"
               style="display: inline-block; padding: 12px 25px; background: #17a2b8; color: #fff; text-decoration: none; border-radius: 6px; font-weight: bold;">
                <?php esc_html_e( 'Gestisci Piano', 'ipv-pro-vendor' ); ?>
            </a>
        </td>
    </tr>
</table>

<p style="font-size: 14px; color: #666; margin-top: 30px;">
    <?php esc_html_e( 'Se hai domande o hai bisogno di assistenza, non esitare a contattarci.', 'ipv-pro-vendor' ); ?>
</p>

<?php do_action( 'woocommerce_email_footer', $email ); ?>
