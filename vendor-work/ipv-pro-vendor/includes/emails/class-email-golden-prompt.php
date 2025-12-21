<?php
/**
 * IPV Email - Golden Prompt Purchased
 *
 * Email inviata quando un utente acquista un Golden Prompt
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Email_Golden_Prompt extends WC_Email {

    public $license;
    public $order;

    public function __construct() {
        $this->id             = 'ipv_golden_prompt';
        $this->title          = __( 'IPV - Acquisto Golden Prompt', 'ipv-pro-vendor' );
        $this->description    = __( 'Email inviata quando un cliente acquista un Golden Prompt.', 'ipv-pro-vendor' );
        $this->template_base  = IPV_VENDOR_DIR . 'templates/';
        $this->template_html  = 'emails/golden-prompt.php';
        $this->template_plain = 'emails/plain/golden-prompt.php';
        $this->placeholders   = [
            '{customer_name}'    => '',
            '{credits_added}'    => '',
            '{credits_remaining}'=> '',
            '{order_id}'         => '',
        ];

        add_action( 'ipv_send_golden_prompt_email', [ $this, 'trigger' ], 10, 2 );

        parent::__construct();

        $this->customer_email = true;
    }

    public function get_default_subject() {
        return __( 'Golden Prompt attivato! +500 crediti aggiunti', 'ipv-pro-vendor' );
    }

    public function get_default_heading() {
        return __( 'Il tuo Golden Prompt è stato attivato', 'ipv-pro-vendor' );
    }

    public function trigger( $license, $order ) {
        $this->setup_locale();

        $this->license = $license;
        $this->order   = $order;

        $recipient = IPV_Vendor_Email_Notifications::get_license_email( $license );
        if ( ! $recipient ) {
            return;
        }

        $this->recipient = $recipient;

        $plans_manager = IPV_Vendor_Plans_Manager::instance();
        $golden_prompt = $plans_manager->get_plan( 'golden_prompt' );
        $credits_added = $golden_prompt['credits'] ?? 500;

        $this->placeholders['{customer_name}']     = IPV_Vendor_Email_Notifications::get_license_name( $license );
        $this->placeholders['{credits_added}']     = $credits_added;
        $this->placeholders['{credits_remaining}'] = $license->credits_remaining ?? 0;
        $this->placeholders['{order_id}']          = $order ? $order->get_id() : '';

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
                'order'         => $this->order,
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
                'order'         => $this->order,
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
            'description' => sprintf( __( 'Disponibili: %s', 'ipv-pro-vendor' ), '{customer_name}, {credits_added}, {credits_remaining}' ),
            'placeholder' => $this->get_default_subject(),
            'default'     => '',
        ];

        $this->form_fields['heading'] = [
            'title'       => __( 'Intestazione', 'ipv-pro-vendor' ),
            'type'        => 'text',
            'desc_tip'    => true,
            'description' => sprintf( __( 'Disponibili: %s', 'ipv-pro-vendor' ), '{customer_name}, {credits_added}, {credits_remaining}' ),
            'placeholder' => $this->get_default_heading(),
            'default'     => '',
        ];
    }
}
