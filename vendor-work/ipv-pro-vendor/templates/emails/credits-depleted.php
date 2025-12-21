<?php
/**
 * IPV Email Template - Credits Depleted
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$plans_manager = IPV_Vendor_Plans_Manager::instance();
$plan = $plans_manager->get_plan( $license->variant_slug );
$customer_name = IPV_Vendor_Email_Notifications::get_license_name( $license );

$credits_manager = IPV_Vendor_Credits_Manager::instance();
$credits_info = $credits_manager->get_credits_info( $license );
$days_to_reset = $credits_info['days_until_reset'] ?? 0;

do_action( 'woocommerce_email_header', $email_heading, $email );
?>

<div style="margin-bottom: 40px;">
    <p style="font-size: 16px; color: #333;">
        <?php printf( esc_html__( 'Ciao %s,', 'ipv-pro-vendor' ), esc_html( $customer_name ) ); ?>
    </p>

    <p style="font-size: 16px; color: #333;">
        <?php esc_html_e( 'I crediti del tuo piano IPV Pro sono esauriti. Per continuare a utilizzare le funzionalità premium, ricarica i tuoi crediti.', 'ipv-pro-vendor' ); ?>
    </p>
</div>

<!-- Alert Card -->
<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom: 30px;">
    <tr>
        <td style="padding: 30px; background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); border-radius: 12px; text-align: center;">
            <div style="margin-bottom: 15px;">
                <span style="font-size: 48px;">&#128683;</span>
            </div>
            <span style="display: block; font-size: 24px; font-weight: bold; color: #fff; margin-bottom: 10px;">
                <?php esc_html_e( 'Crediti Esauriti', 'ipv-pro-vendor' ); ?>
            </span>
            <span style="display: block; font-size: 16px; color: rgba(255,255,255,0.9);">
                <?php esc_html_e( 'Non puoi utilizzare le funzionalità premium fino alla ricarica', 'ipv-pro-vendor' ); ?>
            </span>
        </td>
    </tr>
</table>

<!-- Reset Info -->
<?php if ( $days_to_reset > 0 ) : ?>
<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom: 30px;">
    <tr>
        <td style="padding: 20px; background: #e8f4fd; border-radius: 8px; text-align: center;">
            <span style="display: block; font-size: 14px; color: #0c5460; margin-bottom: 5px;">
                <?php esc_html_e( 'Prossimo reset automatico crediti tra', 'ipv-pro-vendor' ); ?>
            </span>
            <span style="display: block; font-size: 36px; font-weight: bold; color: #17a2b8;">
                <?php echo esc_html( $days_to_reset ); ?>
            </span>
            <span style="display: block; font-size: 14px; color: #0c5460;">
                <?php echo esc_html( _n( 'giorno', 'giorni', $days_to_reset, 'ipv-pro-vendor' ) ); ?>
            </span>
        </td>
    </tr>
</table>
<?php endif; ?>

<!-- Opzioni -->
<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom: 30px;">
    <tr>
        <td style="padding: 20px; background: #f8f9fa; border-radius: 8px;">
            <h3 style="margin: 0 0 20px 0; color: #333; font-size: 18px; text-align: center;">
                <?php esc_html_e( 'Non vuoi aspettare? Ecco le tue opzioni:', 'ipv-pro-vendor' ); ?>
            </h3>

            <!-- Golden Prompt -->
            <table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom: 15px;">
                <tr>
                    <td style="padding: 20px; background: linear-gradient(135deg, #f7971e 0%, #ffd200 100%); border-radius: 8px;">
                        <table cellpadding="0" cellspacing="0" border="0" width="100%">
                            <tr>
                                <td style="width: 60px; vertical-align: middle;">
                                    <span style="font-size: 36px;">&#11088;</span>
                                </td>
                                <td style="vertical-align: middle;">
                                    <strong style="color: #333; font-size: 18px;"><?php esc_html_e( 'Golden Prompt', 'ipv-pro-vendor' ); ?></strong>
                                    <p style="margin: 5px 0 0 0; color: #333; opacity: 0.8;">
                                        <?php esc_html_e( '+500 crediti istantanei che non scadono mai', 'ipv-pro-vendor' ); ?>
                                    </p>
                                </td>
                                <td style="width: 100px; text-align: right; vertical-align: middle;">
                                    <span style="font-size: 20px; font-weight: bold; color: #333;">€69</span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <!-- Extra Credits -->
            <table cellpadding="0" cellspacing="0" border="0" width="100%">
                <tr>
                    <td style="padding: 20px; background: #fff; border: 2px solid #e9ecef; border-radius: 8px;">
                        <table cellpadding="0" cellspacing="0" border="0" width="100%">
                            <tr>
                                <td style="width: 60px; vertical-align: middle;">
                                    <span style="font-size: 36px;">&#128176;</span>
                                </td>
                                <td style="vertical-align: middle;">
                                    <strong style="color: #333; font-size: 18px;"><?php esc_html_e( 'Crediti Extra', 'ipv-pro-vendor' ); ?></strong>
                                    <p style="margin: 5px 0 0 0; color: #666;">
                                        <?php esc_html_e( 'Pacchetti da 10 o 100 crediti aggiuntivi', 'ipv-pro-vendor' ); ?>
                                    </p>
                                </td>
                                <td style="width: 120px; text-align: right; vertical-align: middle;">
                                    <span style="font-size: 14px; color: #666;"><?php esc_html_e( 'da', 'ipv-pro-vendor' ); ?></span>
                                    <span style="font-size: 20px; font-weight: bold; color: #333;">€5</span>
                                </td>
                            </tr>
                        </table>
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
            <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'ipv-wallet' ) ); ?>"
               style="display: inline-block; padding: 18px 40px; background: #28a745; color: #fff; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 18px;">
                <?php esc_html_e( 'Ricarica Ora', 'ipv-pro-vendor' ); ?>
            </a>
        </td>
    </tr>
</table>

<p style="font-size: 14px; color: #666; text-align: center;">
    <?php esc_html_e( 'Oppure aspetta il reset automatico dei crediti con il nuovo periodo del tuo piano.', 'ipv-pro-vendor' ); ?>
</p>

<?php do_action( 'woocommerce_email_footer', $email ); ?>
