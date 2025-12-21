<?php
/**
 * IPV Email - Low Credits Warning
 *
 * Email inviata quando i crediti scendono sotto il 20%
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Email_Low_Credits extends WC_Email {

    public $license;
    public $percentage;

    public function __construct() {
        $this->id             = 'ipv_low_credits';
        $this->title          = __( 'IPV - Crediti in Esaurimento', 'ipv-pro-vendor' );
        $this->description    = __( 'Email inviata quando i crediti di un cliente scendono sotto il 20%.', 'ipv-pro-vendor' );
        $this->template_base  = IPV_VENDOR_PATH . 'templates/';
        $this->template_html  = 'emails/low-credits.php';
        $this->template_plain = 'emails/plain/low-credits.php';
        $this->placeholders   = [
            '{customer_name}'     => '',
            '{credits_remaining}' => '',
            '{credits_total}'     => '',
            '{percentage}'        => '',
            '{plan_name}'         => '',
        ];

        add_action( 'ipv_send_low_credits_email', [ $this, 'trigger' ], 10, 2 );

        parent::__construct();

        $this->customer_email = true;
    }

    public function get_default_subject() {
        return __( 'Attenzione: I tuoi crediti IPV Pro stanno per esaurirsi', 'ipv-pro-vendor' );
    }

    public function get_default_heading() {
        return __( 'I tuoi crediti sono in esaurimento', 'ipv-pro-vendor' );
    }

    public function trigger( $license, $percentage ) {
        $this->setup_locale();

        $this->license    = $license;
        $this->percentage = $percentage;

        $recipient = IPV_Vendor_Email_Notifications::get_license_email( $license );
        if ( ! $recipient ) {
            return;
        }

        $this->recipient = $recipient;

        $plans_manager = IPV_Vendor_Plans_Manager::instance();
        $plan = $plans_manager->get_plan( $license->variant_slug );

        $this->placeholders['{customer_name}']     = IPV_Vendor_Email_Notifications::get_license_name( $license );
        $this->placeholders['{credits_remaining}'] = $license->credits_remaining ?? 0;
        $this->placeholders['{credits_total}']     = $license->credits_total ?? 0;
        $this->placeholders['{percentage}']        = round( $percentage, 1 );
        $this->placeholders['{plan_name}']         = $plan['name'] ?? ucfirst( $license->variant_slug );

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
                'percentage'    => $this->percentage,
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
                'percentage'    => $this->percentage,
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
            'description' => sprintf( __( 'Disponibili: %s', 'ipv-pro-vendor' ), '{customer_name}, {credits_remaining}, {percentage}, {plan_name}' ),
            'placeholder' => $this->get_default_subject(),
            'default'     => '',
        ];

        $this->form_fields['heading'] = [
            'title'       => __( 'Intestazione', 'ipv-pro-vendor' ),
            'type'        => 'text',
            'desc_tip'    => true,
            'description' => sprintf( __( 'Disponibili: %s', 'ipv-pro-vendor' ), '{customer_name}, {credits_remaining}, {percentage}, {plan_name}' ),
            'placeholder' => $this->get_default_heading(),
            'default'     => '',
        ];
    }
}
