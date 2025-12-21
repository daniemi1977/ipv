<?php
/**
 * IPV Email - Credits Reset
 *
 * Email inviata quando i crediti vengono resettati (nuovo periodo)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Email_Credits_Reset extends WC_Email {

    public $license;
    public $new_credits;

    public function __construct() {
        $this->id             = 'ipv_credits_reset';
        $this->title          = __( 'IPV - Reset Crediti Mensile', 'ipv-pro-vendor' );
        $this->description    = __( 'Email inviata quando i crediti di un cliente vengono resettati per il nuovo periodo.', 'ipv-pro-vendor' );
        $this->template_base  = IPV_VENDOR_PATH . 'templates/';
        $this->template_html  = 'emails/credits-reset.php';
        $this->template_plain = 'emails/plain/credits-reset.php';
        $this->placeholders   = [
            '{customer_name}' => '',
            '{plan_name}'     => '',
            '{new_credits}'   => '',
        ];

        add_action( 'ipv_send_credits_reset_email', [ $this, 'trigger' ], 10, 2 );

        parent::__construct();

        $this->customer_email = true;
    }

    public function get_default_subject() {
        return __( 'I tuoi crediti IPV Pro sono stati rinnovati!', 'ipv-pro-vendor' );
    }

    public function get_default_heading() {
        return __( 'Crediti rinnovati - Buon lavoro!', 'ipv-pro-vendor' );
    }

    public function trigger( $license, $new_credits ) {
        $this->setup_locale();

        $this->license     = $license;
        $this->new_credits = $new_credits;

        $recipient = IPV_Vendor_Email_Notifications::get_license_email( $license );
        if ( ! $recipient ) {
            return;
        }

        $this->recipient = $recipient;

        $plans_manager = IPV_Vendor_Plans_Manager::instance();
        $plan = $plans_manager->get_plan( $license->variant_slug );

        $this->placeholders['{customer_name}'] = IPV_Vendor_Email_Notifications::get_license_name( $license );
        $this->placeholders['{plan_name}']     = $plan['name'] ?? ucfirst( $license->variant_slug );
        $this->placeholders['{new_credits}']   = $new_credits;

        if ( $this->is_enabled() ) {
            $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
        }

        $this->restore_locale();
    }

    public function get_content_html() {
        return wc_get_template_html(
            $this->template_html,
            [
                'license'       => $this->license,
                'new_credits'   => $this->new_credits,
                'email_heading' => $this->get_heading(),
                'sent_to_admin' => false,
                'plain_text'    => false,
                'email'         => $this,
            ],
            '',
            $this->template_base
        );
    }

    public function get_content_plain() {
        return wc_get_template_html(
            $this->template_plain,
            [
                'license'       => $this->license,
                'new_credits'   => $this->new_credits,
                'email_heading' => $this->get_heading(),
                'sent_to_admin' => false,
                'plain_text'    => true,
                'email'         => $this,
            ],
            '',
            $this->template_base
        );
    }

    public function init_form_fields() {
        parent::init_form_fields();

        $this->form_fields['subject'] = [
            'title'       => __( 'Oggetto', 'ipv-pro-vendor' ),
            'type'        => 'text',
            'desc_tip'    => true,
            'description' => sprintf( __( 'Disponibili: %s', 'ipv-pro-vendor' ), '{customer_name}, {plan_name}, {new_credits}' ),
            'placeholder' => $this->get_default_subject(),
            'default'     => '',
        ];

        $this->form_fields['heading'] = [
            'title'       => __( 'Intestazione', 'ipv-pro-vendor' ),
            'type'        => 'text',
            'desc_tip'    => true,
            'description' => sprintf( __( 'Disponibili: %s', 'ipv-pro-vendor' ), '{customer_name}, {plan_name}, {new_credits}' ),
            'placeholder' => $this->get_default_heading(),
            'default'     => '',
        ];
    }
}
