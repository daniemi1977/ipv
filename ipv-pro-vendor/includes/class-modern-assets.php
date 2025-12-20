<?php
/**
 * Modern Assets Manager
 *
 * Gestisce l'integrazione di Tailwind CSS, Chart.js, Alpine.js e Toast Notifications
 *
 * @version 1.5.0
 * @since 1.5.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Vendor_Modern_Assets {

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
        // Only load on IPV Vendor pages
        if ( strpos( $hook, 'ipv-vendor' ) === false && strpos( $hook, 'ipv_vendor' ) === false ) {
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

        // Heroicons (icon system)
        wp_enqueue_style(
            'ipv-heroicons',
            'https://cdn.jsdelivr.net/npm/heroicons@2.1.1/24/outline/style.min.css',
            [],
            '2.1.1'
        );

        // Custom modern styles
        wp_enqueue_style(
            'ipv-modern-admin',
            IPV_VENDOR_URL . 'admin/assets/css/modern-admin.css',
            [ 'ipv-tailwind-css' ],
            IPV_VENDOR_VERSION
        );

        // Custom modern JS
        wp_enqueue_script(
            'ipv-modern-admin-js',
            IPV_VENDOR_URL . 'admin/assets/js/modern-admin.js',
            [ 'jquery', 'ipv-alpine-js', 'ipv-chartjs' ],
            IPV_VENDOR_VERSION,
            true
        );

        // Localize script for AJAX
        wp_localize_script( 'ipv-modern-admin-js', 'ipvModern', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'ipv_modern_nonce' ),
            'i18n' => [
                'success' => __( 'Operazione completata con successo', 'ipv-vendor' ),
                'error' => __( 'Si è verificato un errore', 'ipv-vendor' ),
                'loading' => __( 'Caricamento...', 'ipv-vendor' ),
                'confirmDelete' => __( 'Sei sicuro di voler eliminare?', 'ipv-vendor' ),
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
        $icons = [
            'success' => '<svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>',
            'error' => '<svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>',
            'warning' => '<svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>',
            'info' => '<svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
        ];

        $colors = [
            'success' => 'bg-green-50 border-green-200',
            'error' => 'bg-red-50 border-red-200',
            'warning' => 'bg-yellow-50 border-yellow-200',
            'info' => 'bg-blue-50 border-blue-200',
        ];

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
IPV_Vendor_Modern_Assets::instance();
