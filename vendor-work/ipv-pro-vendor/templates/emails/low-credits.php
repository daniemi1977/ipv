<?php
/**
 * IPV Email Template - Low Credits Warning
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$plans_manager = IPV_Vendor_Plans_Manager::instance();
$plan = $plans_manager->get_plan( $license->variant_slug );
$customer_name = IPV_Vendor_Email_Notifications::get_license_name( $license );
$credits_remaining = $license->credits_remaining ?? 0;
$credits_total = $license->credits_total ?? 0;

do_action( 'woocommerce_email_header', $email_heading, $email );
?>

<div style="margin-bottom: 40px;">
    <p style="font-size: 16px; color: #333;">
        <?php printf( esc_html__( 'Ciao %s,', 'ipv-pro-vendor' ), esc_html( $customer_name ) ); ?>
    </p>

    <p style="font-size: 16px; color: #333;">
        <?php esc_html_e( 'Ti avvisiamo che i crediti del tuo piano IPV Pro stanno per esaurirsi.', 'ipv-pro-vendor' ); ?>
    </p>
</div>

<!-- Warning Card -->
<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom: 30px;">
    <tr>
        <td style="padding: 25px; background: #fff3cd; border: 2px solid #ffc107; border-radius: 12px; text-align: center;">
            <div style="margin-bottom: 15px;">
                <span style="font-size: 48px;">&#9888;&#65039;</span>
            </div>
            <span style="display: block; font-size: 14px; color: #856404; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px;">
                <?php esc_html_e( 'Crediti in esaurimento', 'ipv-pro-vendor' ); ?>
            </span>
            <span style="display: block; font-size: 48px; font-weight: bold; color: #856404;">
                <?php echo esc_html( $credits_remaining ); ?>
            </span>
            <span style="display: block; font-size: 16px; color: #856404; margin-top: 5px;">
                <?php printf( esc_html__( 'crediti rimasti su %d', 'ipv-pro-vendor' ), $credits_total ); ?>
            </span>

            <!-- Progress Bar -->
            <div style="margin-top: 20px; background: rgba(0,0,0,0.1); height: 10px; border-radius: 5px; overflow: hidden;">
                <div style="background: #dc3545; height: 100%; width: <?php echo esc_attr( round( $percentage ) ); ?>%; border-radius: 5px;"></div>
            </div>
            <span style="display: block; font-size: 14px; color: #856404; margin-top: 10px;">
                <?php printf( esc_html__( 'Solo il %s%% dei crediti rimasti', 'ipv-pro-vendor' ), round( $percentage, 1 ) ); ?>
            </span>
        </td>
    </tr>
</table>

<!-- Opzioni -->
<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom: 30px;">
    <tr>
        <td style="padding: 20px; background: #f8f9fa; border-radius: 8px;">
            <h3 style="margin: 0 0 15px 0; color: #333; font-size: 18px;">
                <?php esc_html_e( 'Come ottenere più crediti?', 'ipv-pro-vendor' ); ?>
            </h3>

            <table cellpadding="0" cellspacing="0" border="0" width="100%">
                <tr>
                    <td style="padding: 15px; background: #fff; border-radius: 8px; margin-bottom: 10px;">
                        <table cellpadding="0" cellspacing="0" border="0" width="100%">
                            <tr>
                                <td style="width: 50px; vertical-align: top;">
                                    <span style="font-size: 24px;">&#11088;</span>
                                </td>
                                <td>
                                    <strong style="color: #333;"><?php esc_html_e( 'Acquista un Golden Prompt', 'ipv-pro-vendor' ); ?></strong>
                                    <p style="margin: 5px 0 0 0; color: #666; font-size: 14px;">
                                        <?php esc_html_e( 'Ottieni 500 crediti extra che non scadono mai.', 'ipv-pro-vendor' ); ?>
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr><td style="height: 10px;"></td></tr>
                <tr>
                    <td style="padding: 15px; background: #fff; border-radius: 8px;">
                        <table cellpadding="0" cellspacing="0" border="0" width="100%">
                            <tr>
                                <td style="width: 50px; vertical-align: top;">
                                    <span style="font-size: 24px;">&#128640;</span>
                                </td>
                                <td>
                                    <strong style="color: #333;"><?php esc_html_e( 'Fai un Upgrade del piano', 'ipv-pro-vendor' ); ?></strong>
                                    <p style="margin: 5px 0 0 0; color: #666; font-size: 14px;">
                                        <?php esc_html_e( 'Passa a un piano superiore per più crediti mensili.', 'ipv-pro-vendor' ); ?>
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<!-- CTA Buttons -->
<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom: 30px;">
    <tr>
        <td align="center">
            <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'ipv-wallet' ) ); ?>"
               style="display: inline-block; padding: 15px 25px; background: #f7971e; color: #fff; text-decoration: none; border-radius: 8px; font-weight: bold; margin-right: 10px;">
                <?php esc_html_e( 'Acquista Crediti', 'ipv-pro-vendor' ); ?>
            </a>
            <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'ipv-upgrade' ) ); ?>"
               style="display: inline-block; padding: 15px 25px; background: #667eea; color: #fff; text-decoration: none; border-radius: 8px; font-weight: bold;">
                <?php esc_html_e( 'Upgrade Piano', 'ipv-pro-vendor' ); ?>
            </a>
        </td>
    </tr>
</table>

<p style="font-size: 14px; color: #666; margin-top: 30px;">
    <?php esc_html_e( 'Questa è una notifica automatica. I tuoi crediti si resettano automaticamente all\'inizio di ogni periodo.', 'ipv-pro-vendor' ); ?>
</p>

<?php do_action( 'woocommerce_email_footer', $email ); ?>
