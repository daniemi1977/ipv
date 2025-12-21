<?php
/**
 * IPV Email - Plan Upgrade
 *
 * Email inviata quando un utente fa upgrade del piano
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Email_Plan_Upgrade extends WC_Email {

    public $license;
    public $old_plan;
    public $new_plan;

    public function __construct() {
        $this->id             = 'ipv_plan_upgrade';
        $this->title          = __( 'IPV - Upgrade Piano', 'ipv-pro-vendor' );
        $this->description    = __( 'Email inviata quando un cliente fa upgrade del piano IPV Pro.', 'ipv-pro-vendor' );
        $this->template_base  = IPV_VENDOR_PATH . 'templates/';
        $this->template_html  = 'emails/plan-upgrade.php';
        $this->template_plain = 'emails/plain/plan-upgrade.php';
        $this->placeholders   = [
            '{customer_name}' => '',
            '{old_plan}'      => '',
            '{new_plan}'      => '',
            '{new_credits}'   => '',
        ];

        // Trigger
        add_action( 'ipv_send_plan_upgrade_email', [ $this, 'trigger' ], 10, 3 );

        parent::__construct();

        $this->customer_email = true;
    }

    public function get_default_subject() {
        return __( 'Upgrade completato: Benvenuto nel piano {new_plan}!', 'ipv-pro-vendor' );
    }

    public function get_default_heading() {
        return __( 'Il tuo piano IPV Pro è stato aggiornato', 'ipv-pro-vendor' );
    }

    public function trigger( $license, $old_plan, $new_plan ) {
        $this->setup_locale();

        $this->license  = $license;
        $this->old_plan = $old_plan;
        $this->new_plan = $new_plan;

        $recipient = IPV_Vendor_Email_Notifications::get_license_email( $license );
        if ( ! $recipient ) {
            return;
        }

        $this->recipient = $recipient;

        $plans_manager = IPV_Vendor_Plans_Manager::instance();
        $new_plan_data = $plans_manager->get_plan( $new_plan );
        $old_plan_data = $plans_manager->get_plan( $old_plan );

        $this->placeholders['{customer_name}'] = IPV_Vendor_Email_Notifications::get_license_name( $license );
        $this->placeholders['{old_plan}']      = $old_plan_data['name'] ?? ucfirst( $old_plan );
        $this->placeholders['{new_plan}']      = $new_plan_data['name'] ?? ucfirst( $new_plan );
        $this->placeholders['{new_credits}']   = $new_plan_data['credits'] ?? 0;

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
                'old_plan'      => $this->old_plan,
                'new_plan'      => $this->new_plan,
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
                'old_plan'      => $this->old_plan,
                'new_plan'      => $this->new_plan,
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
            'description' => sprintf( __( 'Disponibili: %s', 'ipv-pro-vendor' ), '{customer_name}, {old_plan}, {new_plan}, {new_credits}' ),
            'placeholder' => $this->get_default_subject(),
            'default'     => '',
        ];

        $this->form_fields['heading'] = [
            'title'       => __( 'Intestazione', 'ipv-pro-vendor' ),
            'type'        => 'text',
            'desc_tip'    => true,
            'description' => sprintf( __( 'Disponibili: %s', 'ipv-pro-vendor' ), '{customer_name}, {old_plan}, {new_plan}, {new_credits}' ),
            'placeholder' => $this->get_default_heading(),
            'default'     => '',
        ];
    }
}
