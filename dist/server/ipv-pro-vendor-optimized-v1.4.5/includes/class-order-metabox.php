<?php
/**
 * WooCommerce Order Metabox - v1.4.1
 *
 * Displays license key in WooCommerce order edit page
 *
 * @package IPV_Pro_Vendor
 * @since 1.4.1
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Vendor_Order_Metabox {

    public static function init() {
        add_action( 'add_meta_boxes', [ __CLASS__, 'add_metabox' ] );
    }

    public static function add_metabox() {
        add_meta_box(
            'ipv_license_metabox',
            'IPV License Information',
            [ __CLASS__, 'render_metabox' ],
            'shop_order',
            'side',
            'high'
        );
    }

    public static function render_metabox( $post ) {
        $order_id = $post->ID;
        $license_key = get_post_meta( $order_id, '_ipv_license_key', true );

        if ( $license_key ) {
            global $wpdb;
            $table = $wpdb->prefix . 'ipv_licenses';
            $license = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$table} WHERE license_key = %s",
                $license_key
            ), ARRAY_A );

            if ( $license ) {
                ?>
                <p><strong>License Key:</strong><br><?php echo esc_html( $license_key ); ?></p>
                <p><strong>Plan:</strong> <?php echo esc_html( ucfirst( $license['variant_slug'] ) ); ?></p>
                <p><strong>Period:</strong> <?php echo esc_html( $license['period'] ); ?></p>
                <p><strong>Status:</strong> <?php echo esc_html( $license['status'] ); ?></p>
                <p><strong>Credits:</strong> <?php echo intval( $license['credits_monthly'] ) + intval( $license['credits_extra'] ); ?></p>
                <p>
                    <a href="<?php echo admin_url( 'admin.php?page=ipv-licenses&id=' . $license['id'] ); ?>" class="button">
                        View License Details
                    </a>
                </p>
                <?php
            } else {
                echo '<p>License not found</p>';
            }
        } else {
            echo '<p>No license associated with this order</p>';
        }
    }
}
