<?php
/**
 * IPV Email Template - Plan Upgrade
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
        <?php esc_html_e( 'Complimenti! Il tuo piano IPV Pro è stato aggiornato con successo.', 'ipv-pro-vendor' ); ?>
    </p>
</div>

<!-- Piano Card -->
<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom: 30px;">
    <tr>
        <td style="padding: 25px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px;">
            <table cellpadding="0" cellspacing="0" border="0" width="100%">
                <tr>
                    <td align="center">
                        <span style="font-size: 14px; color: rgba(255,255,255,0.8); text-transform: uppercase; letter-spacing: 1px;">
                            <?php esc_html_e( 'Il tuo nuovo piano', 'ipv-pro-vendor' ); ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <td align="center" style="padding-top: 10px;">
                        <span style="font-size: 32px; font-weight: bold; color: #fff;">
                            <?php echo esc_html( $new_plan_data['name'] ?? ucfirst( $new_plan ) ); ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <td align="center" style="padding-top: 15px;">
                        <span style="font-size: 24px; color: #fff;">
                            €<?php echo number_format( $new_plan_data['price'] ?? 0, 2, ',', '.' ); ?>
                            <span style="font-size: 14px; opacity: 0.8;">/anno</span>
                        </span>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<!-- Dettagli Upgrade -->
<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom: 30px; background: #f8f9fa; border-radius: 8px;">
    <tr>
        <td style="padding: 20px;">
            <h3 style="margin: 0 0 15px 0; color: #333; font-size: 18px;">
                <?php esc_html_e( 'Cosa cambia con l\'upgrade', 'ipv-pro-vendor' ); ?>
            </h3>

            <table cellpadding="0" cellspacing="0" border="0" width="100%">
                <tr>
                    <td style="padding: 10px 0; border-bottom: 1px solid #e9ecef;">
                        <span style="color: #666;">
                            <?php esc_html_e( 'Piano precedente:', 'ipv-pro-vendor' ); ?>
                        </span>
                    </td>
                    <td style="padding: 10px 0; border-bottom: 1px solid #e9ecef; text-align: right;">
                        <span style="color: #999; text-decoration: line-through;">
                            <?php echo esc_html( $old_plan_data['name'] ?? ucfirst( $old_plan ) ); ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 10px 0; border-bottom: 1px solid #e9ecef;">
                        <span style="color: #666;">
                            <?php esc_html_e( 'Crediti mensili:', 'ipv-pro-vendor' ); ?>
                        </span>
                    </td>
                    <td style="padding: 10px 0; border-bottom: 1px solid #e9ecef; text-align: right;">
                        <span style="color: #28a745; font-weight: bold;">
                            <?php echo esc_html( $new_plan_data['credits'] ?? 0 ); ?>
                            <?php if ( isset( $old_plan_data['credits'] ) && isset( $new_plan_data['credits'] ) ) : ?>
                                <span style="font-size: 12px;">(+<?php echo esc_html( $new_plan_data['credits'] - $old_plan_data['credits'] ); ?>)</span>
                            <?php endif; ?>
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
                        <span style="color: #28a745; font-weight: bold;">
                            <?php echo esc_html( $new_plan_data['activations'] ?? 1 ); ?>
                        </span>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<!-- CTA -->
<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom: 30px;">
    <tr>
        <td align="center">
            <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'ipv-licenses' ) ); ?>"
               style="display: inline-block; padding: 15px 30px; background: #667eea; color: #fff; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 16px;">
                <?php esc_html_e( 'Vai al tuo Account', 'ipv-pro-vendor' ); ?>
            </a>
        </td>
    </tr>
</table>

<p style="font-size: 14px; color: #666; margin-top: 30px;">
    <?php esc_html_e( 'Grazie per aver scelto IPV Pro. I nuovi crediti sono già disponibili nel tuo account!', 'ipv-pro-vendor' ); ?>
</p>

<?php do_action( 'woocommerce_email_footer', $email ); ?>
