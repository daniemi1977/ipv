<?php
/**
 * IPV Email Template - Credits Reset
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$plans_manager = IPV_Vendor_Plans_Manager::instance();
$plan = $plans_manager->get_plan( $license->variant_slug );
$customer_name = IPV_Vendor_Email_Notifications::get_license_name( $license );

do_action( 'woocommerce_email_header', $email_heading, $email );
?>

<div style="margin-bottom: 40px;">
    <p style="font-size: 16px; color: #333;">
        <?php printf( esc_html__( 'Ciao %s,', 'ipv-pro-vendor' ), esc_html( $customer_name ) ); ?>
    </p>

    <p style="font-size: 16px; color: #333;">
        <?php esc_html_e( 'Ottime notizie! I tuoi crediti IPV Pro sono stati rinnovati per il nuovo periodo.', 'ipv-pro-vendor' ); ?>
    </p>
</div>

<!-- Success Card -->
<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom: 30px;">
    <tr>
        <td style="padding: 30px; background: linear-gradient(135deg, #28a745 0%, #20c997 100%); border-radius: 12px; text-align: center;">
            <div style="margin-bottom: 15px;">
                <span style="font-size: 48px;">&#127881;</span>
            </div>
            <span style="display: block; font-size: 14px; color: rgba(255,255,255,0.9); text-transform: uppercase; letter-spacing: 2px; margin-bottom: 10px;">
                <?php esc_html_e( 'Crediti Rinnovati', 'ipv-pro-vendor' ); ?>
            </span>
            <span style="display: block; font-size: 56px; font-weight: bold; color: #fff;">
                <?php echo esc_html( $new_credits ); ?>
            </span>
            <span style="display: block; font-size: 18px; color: rgba(255,255,255,0.9); margin-top: 5px;">
                <?php esc_html_e( 'crediti disponibili', 'ipv-pro-vendor' ); ?>
            </span>
        </td>
    </tr>
</table>

<!-- Piano Info -->
<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom: 30px;">
    <tr>
        <td style="padding: 20px; background: #f8f9fa; border-radius: 8px;">
            <table cellpadding="0" cellspacing="0" border="0" width="100%">
                <tr>
                    <td style="padding: 10px 0; border-bottom: 1px solid #e9ecef;">
                        <span style="color: #666;">
                            <?php esc_html_e( 'Il tuo piano:', 'ipv-pro-vendor' ); ?>
                        </span>
                    </td>
                    <td style="padding: 10px 0; border-bottom: 1px solid #e9ecef; text-align: right;">
                        <strong style="color: #333;">
                            <?php echo esc_html( $plan['name'] ?? ucfirst( $license->variant_slug ) ); ?>
                        </strong>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 10px 0; border-bottom: 1px solid #e9ecef;">
                        <span style="color: #666;">
                            <?php esc_html_e( 'Crediti per periodo:', 'ipv-pro-vendor' ); ?>
                        </span>
                    </td>
                    <td style="padding: 10px 0; border-bottom: 1px solid #e9ecef; text-align: right;">
                        <strong style="color: #28a745;">
                            <?php echo esc_html( $new_credits ); ?>
                        </strong>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 10px 0;">
                        <span style="color: #666;">
                            <?php esc_html_e( 'Prossimo rinnovo:', 'ipv-pro-vendor' ); ?>
                        </span>
                    </td>
                    <td style="padding: 10px 0; text-align: right;">
                        <strong style="color: #333;">
                            <?php
                            $period = $plan['credits_period'] ?? 'year';
                            $next_reset = strtotime( '+1 ' . $period );
                            echo esc_html( date_i18n( get_option( 'date_format' ), $next_reset ) );
                            ?>
                        </strong>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<!-- Suggerimenti -->
<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom: 30px;">
    <tr>
        <td style="padding: 20px; background: #e8f4fd; border-radius: 8px;">
            <h3 style="margin: 0 0 15px 0; color: #0c5460; font-size: 16px;">
                <?php esc_html_e( 'Suggerimenti per ottimizzare i tuoi crediti', 'ipv-pro-vendor' ); ?>
            </h3>
            <ul style="margin: 0; padding-left: 20px; color: #0c5460; font-size: 14px; line-height: 1.8;">
                <li><?php esc_html_e( 'Utilizza prompt chiari e specifici per risultati migliori', 'ipv-pro-vendor' ); ?></li>
                <li><?php esc_html_e( 'Salva i tuoi prompt preferiti per riutilizzarli', 'ipv-pro-vendor' ); ?></li>
                <li><?php esc_html_e( 'Considera un upgrade se i crediti non bastano', 'ipv-pro-vendor' ); ?></li>
            </ul>
        </td>
    </tr>
</table>

<!-- CTA -->
<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom: 30px;">
    <tr>
        <td align="center">
            <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'ipv-licenses' ) ); ?>"
               style="display: inline-block; padding: 15px 30px; background: #667eea; color: #fff; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 16px;">
                <?php esc_html_e( 'Inizia a Creare', 'ipv-pro-vendor' ); ?>
            </a>
        </td>
    </tr>
</table>

<p style="font-size: 14px; color: #666; text-align: center; margin-top: 30px;">
    <?php esc_html_e( 'Buon lavoro con IPV Pro!', 'ipv-pro-vendor' ); ?>
</p>

<?php do_action( 'woocommerce_email_footer', $email ); ?>
