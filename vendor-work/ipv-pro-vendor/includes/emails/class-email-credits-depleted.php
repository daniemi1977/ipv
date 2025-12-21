<?php
/**
 * IPV Email - Credits Depleted
 *
 * Email inviata quando i crediti sono completamente esauriti
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Email_Credits_Depleted extends WC_Email {

    public $license;

    public function __construct() {
        $this->id             = 'ipv_credits_depleted';
        $this->title          = __( 'IPV - Crediti Esauriti', 'ipv-pro-vendor' );
        $this->description    = __( 'Email inviata quando i crediti di un cliente sono completamente esauriti.', 'ipv-pro-vendor' );
        $this->template_base  = IPV_VENDOR_DIR . 'templates/';
        $this->template_html  = 'emails/credits-depleted.php';
        $this->template_plain = 'emails/plain/credits-depleted.php';
        $this->placeholders   = [
            '{customer_name}' => '',
            '{plan_name}'     => '',
            '{days_to_reset}' => '',
        ];

        add_action( 'ipv_send_credits_depleted_email', [ $this, 'trigger' ], 10, 1 );

        parent::__construct();

        $this->customer_email = true;
    }

    public function get_default_subject() {
        return __( 'I tuoi crediti IPV Pro sono esauriti', 'ipv-pro-vendor' );
    }

    public function get_default_heading() {
        return __( 'Crediti esauriti - Ricarica subito!', 'ipv-pro-vendor' );
    }

    public function trigger( $license ) {
        $this->setup_locale();

        $this->license = $license;

        $recipient = IPV_Vendor_Email_Notifications::get_license_email( $license );
        if ( ! $recipient ) {
            return;
        }

        $this->recipient = $recipient;

        $plans_manager = IPV_Vendor_Plans_Manager::instance();
        $plan = $plans_manager->get_plan( $license->variant_slug );

        // Calcola giorni al reset
        $credits_manager = IPV_Vendor_Credits_Manager::instance();
        $credits_info = $credits_manager->get_credits_info( $license );

        $this->placeholders['{customer_name}'] = IPV_Vendor_Email_Notifications::get_license_name( $license );
        $this->placeholders['{plan_name}']     = $plan['name'] ?? ucfirst( $license->variant_slug );
        $this->placeholders['{days_to_reset}'] = $credits_info['days_until_reset'] ?? 0;

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
            'description' => sprintf( __( 'Disponibili: %s', 'ipv-pro-vendor' ), '{customer_name}, {plan_name}, {days_to_reset}' ),
            'placeholder' => $this->get_default_subject(),
            'default'     => '',
        ];

        $this->form_fields['heading'] = [
            'title'       => __( 'Intestazione', 'ipv-pro-vendor' ),
            'type'        => 'text',
            'desc_tip'    => true,
            'description' => sprintf( __( 'Disponibili: %s', 'ipv-pro-vendor' ), '{customer_name}, {plan_name}, {days_to_reset}' ),
            'placeholder' => $this->get_default_heading(),
            'default'     => '',
        ];
    }
}
