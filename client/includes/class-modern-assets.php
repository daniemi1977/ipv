<?php
/**
 * Modern Assets Manager - CLIENT
 *
 * Gestisce l'integrazione di Tailwind CSS, Chart.js, Alpine.js e Toast Notifications
 *
 * @version 10.4.0
 * @since 10.4.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Prod_Modern_Assets {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_modern_assets' ] );
        add_action( 'admin_head', [ $this, 'add_custom_css_vars' ] );
        add_action( 'admin_footer', [ $this, 'add_toast_container' ] );
    }

    /**
     * Enqueue modern CSS/JS frameworks
     */
    public function enqueue_modern_assets( $hook ) {
        // Only load on IPV pages
        if ( strpos( $hook, 'ipv' ) === false && strpos( $hook, 'edit.php' ) === false ) {
            return;
        }

        // Tailwind CSS via CDN (latest v3.4)
        wp_enqueue_style(
            'ipv-tailwind-css',
            'https://cdn.jsdelivr.net/npm/tailwindcss@3.4.1/dist/tailwind.min.css',
            [],
            '3.4.1'
        );

        // Alpine.js for reactive components
        wp_enqueue_script(
            'ipv-alpine-js',
            'https://cdn.jsdelivr.net/npm/alpinejs@3.13.5/dist/cdn.min.js',
            [],
            '3.13.5',
            true
        );

        // Chart.js for analytics
        wp_enqueue_script(
            'ipv-chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
            [],
            '4.4.1',
            true
        );

        // Custom modern styles
        wp_enqueue_style(
            'ipv-modern-client',
            IPV_PROD_URL . 'admin/assets/css/modern-client.css',
            [ 'ipv-tailwind-css' ],
            IPV_PROD_VERSION
        );

        // Custom modern JS
        wp_enqueue_script(
            'ipv-modern-client-js',
            IPV_PROD_URL . 'admin/assets/js/modern-client.js',
            [ 'jquery', 'ipv-alpine-js', 'ipv-chartjs' ],
            IPV_PROD_VERSION,
            true
        );

        // Localize script for AJAX
        wp_localize_script( 'ipv-modern-client-js', 'ipvModern', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'ipv_modern_nonce' ),
            'i18n' => [
                'success' => __( 'Operazione completata con successo', 'ipv-production-system-pro' ),
                'error' => __( 'Si è verificato un errore', 'ipv-production-system-pro' ),
                'loading' => __( 'Caricamento...', 'ipv-production-system-pro' ),
                'confirmDelete' => __( 'Sei sicuro di voler eliminare?', 'ipv-production-system-pro' ),
                'videoProcessed' => __( 'Video processato con successo', 'ipv-production-system-pro' ),
                'creditsLow' => __( 'Attenzione: crediti in esaurimento', 'ipv-production-system-pro' ),
            ]
        ] );
    }

    /**
     * Add custom CSS variables for brand colors
     */
    public function add_custom_css_vars() {
        ?>
        <style>
            :root {
                --ipv-primary: #3b82f6;      /* Blue-500 */
                --ipv-primary-dark: #1d4ed8; /* Blue-700 */
                --ipv-success: #10b981;      /* Green-500 */
                --ipv-warning: #f59e0b;      /* Amber-500 */
                --ipv-danger: #ef4444;       /* Red-500 */
                --ipv-gray: #6b7280;         /* Gray-500 */
                --ipv-dark: #111827;         /* Gray-900 */
                --ipv-purple: #8b5cf6;       /* Purple-500 */
            }

            /* Remove default WordPress admin styles on IPV pages */
            .ipv-modern-page .wp-heading-inline,
            .ipv-modern-page .page-title-action {
                display: none;
            }

            /* Smooth transitions */
            * {
                transition: all 0.2s ease-in-out;
            }

            /* Custom scrollbar */
            ::-webkit-scrollbar {
                width: 8px;
                height: 8px;
            }

            ::-webkit-scrollbar-track {
                background: #f1f5f9;
            }

            ::-webkit-scrollbar-thumb {
                background: #94a3b8;
                border-radius: 4px;
            }

            ::-webkit-scrollbar-thumb:hover {
                background: #64748b;
            }
        </style>
        <?php
    }

    /**
     * Add toast notification container
     */
    public function add_toast_container() {
        ?>
        <!-- Toast Notifications Container -->
        <div id="ipv-toast-container" class="fixed top-4 right-4 z-50 space-y-2" style="z-index: 999999;">
            <!-- Toasts will be injected here via JavaScript -->
        </div>
        <?php
    }

    /**
     * Display success toast
     */
    public static function show_toast( $message, $type = 'success' ) {
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof window.ipvShowToast === 'function') {
                window.ipvShowToast('<?php echo esc_js( $message ); ?>', '<?php echo esc_js( $type ); ?>');
            }
        });
        </script>
        <?php
    }
}

// Initialize
IPV_Prod_Modern_Assets::instance();
