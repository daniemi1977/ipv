<?php
/**
 * IPV Email Notifications Manager
 *
 * Gestisce le notifiche email tramite il sistema WooCommerce
 *
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Vendor_Email_Notifications {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        // Registra le email personalizzate in WooCommerce
        add_filter( 'woocommerce_email_classes', [ $this, 'register_email_classes' ] );

        // Hook per trigger notifiche
        add_action( 'ipv_plan_upgraded', [ $this, 'trigger_plan_upgrade_email' ], 10, 3 );
        add_action( 'ipv_plan_downgraded', [ $this, 'trigger_plan_downgrade_email' ], 10, 3 );
        add_action( 'ipv_golden_prompt_purchased', [ $this, 'trigger_golden_prompt_email' ], 10, 2 );
        add_action( 'ipv_credits_low', [ $this, 'trigger_low_credits_email' ], 10, 2 );
        add_action( 'ipv_credits_depleted', [ $this, 'trigger_credits_depleted_email' ], 10, 1 );
        add_action( 'ipv_credits_reset', [ $this, 'trigger_credits_reset_email' ], 10, 2 );

        // Hook per controllare crediti bassi dopo ogni utilizzo
        add_action( 'ipv_credits_used', [ $this, 'check_low_credits' ], 10, 2 );

        // Hook WooCommerce per ordini completati
        add_action( 'woocommerce_order_status_completed', [ $this, 'handle_order_completed' ], 20 );

        // Aggiungi sezione email nelle impostazioni WooCommerce
        add_filter( 'woocommerce_email_actions', [ $this, 'add_email_actions' ] );
    }

    /**
     * Registra le classi email personalizzate
     */
    public function register_email_classes( $email_classes ) {
        // Include le classi email
        require_once dirname( __FILE__ ) . '/emails/class-email-plan-upgrade.php';
        require_once dirname( __FILE__ ) . '/emails/class-email-plan-downgrade.php';
        require_once dirname( __FILE__ ) . '/emails/class-email-golden-prompt.php';
        require_once dirname( __FILE__ ) . '/emails/class-email-low-credits.php';
        require_once dirname( __FILE__ ) . '/emails/class-email-credits-depleted.php';
        require_once dirname( __FILE__ ) . '/emails/class-email-credits-reset.php';

        // Registra le email
        $email_classes['IPV_Email_Plan_Upgrade'] = new IPV_Email_Plan_Upgrade();
        $email_classes['IPV_Email_Plan_Downgrade'] = new IPV_Email_Plan_Downgrade();
        $email_classes['IPV_Email_Golden_Prompt'] = new IPV_Email_Golden_Prompt();
        $email_classes['IPV_Email_Low_Credits'] = new IPV_Email_Low_Credits();
        $email_classes['IPV_Email_Credits_Depleted'] = new IPV_Email_Credits_Depleted();
        $email_classes['IPV_Email_Credits_Reset'] = new IPV_Email_Credits_Reset();

        return $email_classes;
    }

    /**
     * Aggiungi azioni email per i trigger
     */
    public function add_email_actions( $actions ) {
        $actions[] = 'ipv_plan_upgraded';
        $actions[] = 'ipv_plan_downgraded';
        $actions[] = 'ipv_golden_prompt_purchased';
        $actions[] = 'ipv_credits_low';
        $actions[] = 'ipv_credits_depleted';
        $actions[] = 'ipv_credits_reset';
        return $actions;
    }

    /**
     * Trigger email upgrade piano
     */
    public function trigger_plan_upgrade_email( $license, $old_plan, $new_plan ) {
        WC()->mailer();
        do_action( 'ipv_send_plan_upgrade_email', $license, $old_plan, $new_plan );
    }

    /**
     * Trigger email downgrade piano
     */
    public function trigger_plan_downgrade_email( $license, $old_plan, $new_plan ) {
        WC()->mailer();
        do_action( 'ipv_send_plan_downgrade_email', $license, $old_plan, $new_plan );
    }

    /**
     * Trigger email acquisto Golden Prompt
     */
    public function trigger_golden_prompt_email( $license, $order ) {
        WC()->mailer();
        do_action( 'ipv_send_golden_prompt_email', $license, $order );
    }

    /**
     * Trigger email crediti bassi
     */
    public function trigger_low_credits_email( $license, $percentage ) {
        // Evita spam: controlla se già inviata nelle ultime 24 ore
        $last_sent = get_transient( 'ipv_low_credits_email_' . $license->id );
        if ( $last_sent ) {
            return;
        }

        WC()->mailer();
        do_action( 'ipv_send_low_credits_email', $license, $percentage );

        // Segna come inviata per 24 ore
        set_transient( 'ipv_low_credits_email_' . $license->id, time(), DAY_IN_SECONDS );
    }

    /**
     * Trigger email crediti esauriti
     */
    public function trigger_credits_depleted_email( $license ) {
        // Evita spam: controlla se già inviata nelle ultime 24 ore
        $last_sent = get_transient( 'ipv_depleted_email_' . $license->id );
        if ( $last_sent ) {
            return;
        }

        WC()->mailer();
        do_action( 'ipv_send_credits_depleted_email', $license );

        set_transient( 'ipv_depleted_email_' . $license->id, time(), DAY_IN_SECONDS );
    }

    /**
     * Trigger email reset crediti
     */
    public function trigger_credits_reset_email( $license, $new_credits ) {
        WC()->mailer();
        do_action( 'ipv_send_credits_reset_email', $license, $new_credits );
    }

    /**
     * Controlla se i crediti sono bassi dopo ogni utilizzo
     */
    public function check_low_credits( $license, $credits_used ) {
        if ( ! $license || empty( $license->credits_total ) ) {
            return;
        }

        $remaining = (int) $license->credits_remaining;
        $total = (int) $license->credits_total;

        if ( $total <= 0 ) {
            return;
        }

        $percentage = ( $remaining / $total ) * 100;

        // Crediti esauriti
        if ( $remaining <= 0 ) {
            do_action( 'ipv_credits_depleted', $license );
            return;
        }

        // Crediti sotto il 20%
        if ( $percentage <= 20 ) {
            do_action( 'ipv_credits_low', $license, $percentage );
        }
    }

    /**
     * Gestisci ordine completato per trigger notifiche
     */
    public function handle_order_completed( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        foreach ( $order->get_items() as $item ) {
            $product = $item->get_product();
            if ( ! $product ) {
                continue;
            }

            $plan_slug = $product->get_meta( '_ipv_plan_slug' );

            // Golden Prompt acquistato
            if ( $plan_slug === 'golden_prompt' ) {
                $license_key = $order->get_meta( '_ipv_license_key' );
                if ( $license_key ) {
                    global $wpdb;
                    $license = $wpdb->get_row( $wpdb->prepare(
                        "SELECT * FROM {$wpdb->prefix}ipv_licenses WHERE license_key = %s",
                        $license_key
                    ) );
                    if ( $license ) {
                        do_action( 'ipv_golden_prompt_purchased', $license, $order );
                    }
                }
            }

            // Upgrade piano
            $upgrade_license = $order->get_meta( '_ipv_upgrade_license' );
            if ( $upgrade_license && in_array( $plan_slug, [ 'starter', 'professional', 'business' ] ) ) {
                global $wpdb;
                $license = $wpdb->get_row( $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}ipv_licenses WHERE license_key = %s",
                    $upgrade_license
                ) );
                if ( $license ) {
                    $old_plan = $order->get_meta( '_ipv_old_plan' ) ?: 'trial';
                    do_action( 'ipv_plan_upgraded', $license, $old_plan, $plan_slug );
                }
            }
        }
    }

    /**
     * Helper: Ottieni email utente da licenza
     */
    public static function get_license_email( $license ) {
        if ( ! empty( $license->email ) ) {
            return $license->email;
        }

        if ( ! empty( $license->user_id ) ) {
            $user = get_userdata( $license->user_id );
            if ( $user ) {
                return $user->user_email;
            }
        }

        return '';
    }

    /**
     * Helper: Ottieni nome utente da licenza
     */
    public static function get_license_name( $license ) {
        if ( ! empty( $license->user_id ) ) {
            $user = get_userdata( $license->user_id );
            if ( $user ) {
                return $user->display_name ?: $user->user_login;
            }
        }

        return __( 'Cliente', 'ipv-pro-vendor' );
    }
}

// Initialize
IPV_Vendor_Email_Notifications::instance();
