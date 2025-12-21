<?php
/**
 * IPV Landing Page
 *
 * Shortcodes per la landing page SaaS style
 *
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Vendor_Landing_Page {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        // Register shortcodes
        add_shortcode( 'ipv_pricing', [ $this, 'pricing_shortcode' ] );
        add_shortcode( 'ipv_hero', [ $this, 'hero_shortcode' ] );
        add_shortcode( 'ipv_features', [ $this, 'features_shortcode' ] );
        add_shortcode( 'ipv_how_it_works', [ $this, 'how_it_works_shortcode' ] );
        add_shortcode( 'ipv_cta', [ $this, 'cta_shortcode' ] );

        // Enqueue assets
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    /**
     * Enqueue landing page assets
     */
    public function enqueue_assets() {
        wp_register_style(
            'ipv-landing-page',
            IPV_VENDOR_URL . 'assets/css/landing-page.css',
            [],
            IPV_VENDOR_VERSION
        );
    }

    /**
     * Pricing Section Shortcode
     */
    public function pricing_shortcode( $atts ) {
        wp_enqueue_style( 'ipv-landing-page' );

        $atts = shortcode_atts([
            'title' => __( 'Prezzi Trasparenti', 'ipv-pro-vendor' ),
            'subtitle' => __( 'PRICING', 'ipv-pro-vendor' ),
            'show_toggle' => 'yes',
        ], $atts );

        $plans_manager = IPV_Vendor_Plans_Manager::instance();
        $plans = $plans_manager->get_plans();

        // Filter only subscription plans
        $pricing_plans = [];
        foreach ( [ 'trial', 'starter', 'professional', 'business' ] as $slug ) {
            if ( isset( $plans[ $slug ] ) && ! empty( $plans[ $slug ]['is_active'] ) ) {
                $pricing_plans[ $slug ] = $plans[ $slug ];
            }
        }

        ob_start();
        ?>
        <section class="ipv-landing ipv-pricing ipv-section">
            <div class="ipv-container">
                <div class="ipv-section-header">
                    <span class="ipv-badge"><?php echo esc_html( $atts['subtitle'] ); ?></span>
                    <h2><?php echo esc_html( $atts['title'] ); ?></h2>
                </div>

                <?php if ( $atts['show_toggle'] === 'yes' ) : ?>
                <div class="ipv-pricing-toggle">
                    <button class="ipv-pricing-toggle__btn active" data-period="year">
                        <?php esc_html_e( 'Annuale', 'ipv-pro-vendor' ); ?>
                    </button>
                    <button class="ipv-pricing-toggle__btn" data-period="month">
                        <?php esc_html_e( 'Mensile', 'ipv-pro-vendor' ); ?>
                    </button>
                </div>
                <?php endif; ?>

                <div class="ipv-pricing-cards">
                    <?php foreach ( $pricing_plans as $slug => $plan ) :
                        $is_featured = $slug === 'professional';
                        $features = $this->get_plan_features( $slug, $plan );
                        $product_url = $this->get_plan_product_url( $slug );
                    ?>
                    <div class="ipv-pricing-card <?php echo $is_featured ? 'ipv-pricing-card--featured' : ''; ?>">
                        <div class="ipv-pricing-card__name">
                            <?php echo esc_html( $plan['name'] ); ?>
                        </div>
                        <div class="ipv-pricing-card__price">
                            <?php if ( $plan['price'] > 0 ) : ?>
                                €<?php echo number_format( $plan['price'], 2, ',', '.' ); ?>
                                <span>/<?php echo esc_html( $this->get_period_label( $plan['price_period'] ?? 'year' ) ); ?></span>
                            <?php else : ?>
                                <?php esc_html_e( 'Gratis', 'ipv-pro-vendor' ); ?>
                                <span>&nbsp;</span>
                            <?php endif; ?>
                        </div>

                        <ul class="ipv-pricing-card__features">
                            <?php foreach ( $features as $feature ) : ?>
                            <li class="<?php echo $feature['included'] ? '' : 'disabled'; ?>">
                                <?php if ( $feature['included'] ) : ?>
                                    <svg class="check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                <?php else : ?>
                                    <svg class="x" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="18" y1="6" x2="6" y2="18"></line>
                                        <line x1="6" y1="6" x2="18" y2="18"></line>
                                    </svg>
                                <?php endif; ?>
                                <?php echo esc_html( $feature['text'] ); ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>

                        <a href="<?php echo esc_url( $product_url ); ?>" class="ipv-btn <?php echo $is_featured ? 'ipv-btn--primary' : 'ipv-btn--outline'; ?>">
                            <?php if ( $slug === 'trial' ) : ?>
                                <?php esc_html_e( 'Inizia Gratis', 'ipv-pro-vendor' ); ?>
                            <?php else : ?>
                                <?php esc_html_e( 'Scegli Piano', 'ipv-pro-vendor' ); ?>
                            <?php endif; ?>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                <polyline points="12 5 19 12 12 19"></polyline>
                            </svg>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Extra Credits -->
                <div class="ipv-pricing-extras" style="margin-top: 60px; text-align: center;">
                    <h3 style="margin-bottom: 20px;"><?php esc_html_e( 'Hai bisogno di crediti extra?', 'ipv-pro-vendor' ); ?></h3>
                    <div class="ipv-row" style="justify-content: center; gap: 20px;">
                        <?php
                        $extras = [
                            'golden_prompt' => $plans['golden_prompt'] ?? null,
                            'extra_credits_10' => $plans['extra_credits_10'] ?? null,
                            'extra_credits_100' => $plans['extra_credits_100'] ?? null,
                        ];
                        foreach ( $extras as $slug => $extra ) :
                            if ( ! $extra || empty( $extra['is_active'] ) ) continue;
                        ?>
                        <div class="ipv-extra-card" style="background: var(--ipv-white); padding: 25px; border-radius: 12px; border: 1px solid var(--ipv-border); min-width: 200px;">
                            <div style="font-size: 24px; margin-bottom: 10px;">
                                <?php echo $slug === 'golden_prompt' ? '⭐' : '💰'; ?>
                            </div>
                            <h4 style="margin-bottom: 5px;"><?php echo esc_html( $extra['name'] ); ?></h4>
                            <div style="font-size: 24px; font-weight: 700; color: var(--ipv-primary); margin-bottom: 5px;">
                                €<?php echo number_format( $extra['price'], 2, ',', '.' ); ?>
                            </div>
                            <div style="color: var(--ipv-text-muted); font-size: 14px; margin-bottom: 15px;">
                                +<?php echo esc_html( $extra['credits'] ); ?> <?php esc_html_e( 'crediti', 'ipv-pro-vendor' ); ?>
                            </div>
                            <a href="<?php echo esc_url( $this->get_plan_product_url( $slug ) ); ?>" class="ipv-btn ipv-btn--sm ipv-btn--outline">
                                <?php esc_html_e( 'Acquista', 'ipv-pro-vendor' ); ?>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }

    /**
     * Hero Section Shortcode
     */
    public function hero_shortcode( $atts ) {
        wp_enqueue_style( 'ipv-landing-page' );

        $atts = shortcode_atts([
            'title' => __( 'Automatizza i tuoi Video YouTube con l\'AI', 'ipv-pro-vendor' ),
            'subtitle' => __( 'Trascrizioni automatiche, descrizioni SEO e molto altro. Risparmia ore di lavoro con IPV Pro.', 'ipv-pro-vendor' ),
            'button_text' => __( 'Inizia Gratis', 'ipv-pro-vendor' ),
            'button_url' => '#pricing',
            'button2_text' => __( 'Scopri di più', 'ipv-pro-vendor' ),
            'button2_url' => '#features',
            'image' => '',
        ], $atts );

        ob_start();
        ?>
        <section class="ipv-landing ipv-hero">
            <div class="ipv-container">
                <div class="ipv-hero__content">
                    <h1><?php echo wp_kses_post( $atts['title'] ); ?></h1>
                    <p><?php echo esc_html( $atts['subtitle'] ); ?></p>

                    <div class="ipv-hero__buttons">
                        <a href="<?php echo esc_url( $atts['button_url'] ); ?>" class="ipv-btn ipv-btn--white ipv-btn--lg">
                            <?php echo esc_html( $atts['button_text'] ); ?>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                <polyline points="12 5 19 12 12 19"></polyline>
                            </svg>
                        </a>
                        <a href="<?php echo esc_url( $atts['button2_url'] ); ?>" class="ipv-btn ipv-btn--outline" style="color: white; border-color: white;">
                            <?php echo esc_html( $atts['button2_text'] ); ?>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                <polyline points="12 5 19 12 12 19"></polyline>
                            </svg>
                        </a>
                    </div>
                </div>

                <?php if ( $atts['image'] ) : ?>
                <div class="ipv-hero__image">
                    <img src="<?php echo esc_url( $atts['image'] ); ?>" alt="IPV Pro Dashboard">
                </div>
                <?php endif; ?>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }

    /**
     * Features Section Shortcode
     */
    public function features_shortcode( $atts ) {
        wp_enqueue_style( 'ipv-landing-page' );

        $atts = shortcode_atts([
            'title' => __( 'Le Nostre Soluzioni', 'ipv-pro-vendor' ),
            'subtitle' => __( 'FUNZIONALITÀ', 'ipv-pro-vendor' ),
        ], $atts );

        $features = [
            [
                'icon' => '🎥',
                'title' => __( 'Importazione Video', 'ipv-pro-vendor' ),
                'desc' => __( 'Importa video da YouTube e Vimeo con un click. Metadati automatici.', 'ipv-pro-vendor' ),
            ],
            [
                'icon' => '📝',
                'title' => __( 'Trascrizione AI', 'ipv-pro-vendor' ),
                'desc' => __( 'Trascrizioni automatiche con AI avanzata. Multilingua supportato.', 'ipv-pro-vendor' ),
            ],
            [
                'icon' => '🚀',
                'title' => __( 'SEO Automatico', 'ipv-pro-vendor' ),
                'desc' => __( 'Genera descrizioni e tag ottimizzati per i motori di ricerca.', 'ipv-pro-vendor' ),
            ],
        ];

        ob_start();
        ?>
        <section class="ipv-landing ipv-offers ipv-section" id="features">
            <div class="ipv-container">
                <div class="ipv-section-header">
                    <span class="ipv-badge"><?php echo esc_html( $atts['subtitle'] ); ?></span>
                    <h2><?php echo esc_html( $atts['title'] ); ?></h2>
                </div>

                <div class="ipv-row">
                    <?php foreach ( $features as $feature ) : ?>
                    <div class="ipv-col ipv-col-33">
                        <div class="ipv-offer-card">
                            <div class="ipv-offer-card__icon">
                                <span style="font-size: 32px;"><?php echo $feature['icon']; ?></span>
                            </div>
                            <h3><?php echo esc_html( $feature['title'] ); ?></h3>
                            <p><?php echo esc_html( $feature['desc'] ); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }

    /**
     * How It Works Shortcode
     */
    public function how_it_works_shortcode( $atts ) {
        wp_enqueue_style( 'ipv-landing-page' );

        $atts = shortcode_atts([
            'title' => __( 'Come Funziona', 'ipv-pro-vendor' ),
            'subtitle' => __( 'PROCESSO', 'ipv-pro-vendor' ),
        ], $atts );

        $steps = [
            [
                'step' => '01',
                'title' => __( 'Importa Video', 'ipv-pro-vendor' ),
                'desc' => __( 'Incolla il link YouTube o carica il tuo video. L\'importazione è automatica.', 'ipv-pro-vendor' ),
            ],
            [
                'step' => '02',
                'title' => __( 'AI Elabora', 'ipv-pro-vendor' ),
                'desc' => __( 'La nostra AI trascrive, analizza e genera contenuti ottimizzati.', 'ipv-pro-vendor' ),
                'featured' => true,
            ],
            [
                'step' => '03',
                'title' => __( 'Pubblica', 'ipv-pro-vendor' ),
                'desc' => __( 'Rivedi, modifica se necessario e pubblica. Tutto in pochi minuti.', 'ipv-pro-vendor' ),
            ],
        ];

        ob_start();
        ?>
        <section class="ipv-landing ipv-how-it-works ipv-section">
            <div class="ipv-container">
                <div class="ipv-section-header">
                    <span class="ipv-badge ipv-badge--light"><?php echo esc_html( $atts['subtitle'] ); ?></span>
                    <h2><?php echo esc_html( $atts['title'] ); ?></h2>
                </div>

                <div class="ipv-steps">
                    <?php foreach ( $steps as $step ) : ?>
                    <div class="ipv-step <?php echo ! empty( $step['featured'] ) ? 'ipv-step--featured' : ''; ?>">
                        <span class="ipv-step__badge">STEP-<?php echo esc_html( $step['step'] ); ?></span>
                        <h3><?php echo esc_html( $step['title'] ); ?></h3>
                        <p><?php echo esc_html( $step['desc'] ); ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }

    /**
     * CTA Section Shortcode
     */
    public function cta_shortcode( $atts ) {
        wp_enqueue_style( 'ipv-landing-page' );

        $atts = shortcode_atts([
            'title' => __( 'Pronto a Risparmiare Tempo?', 'ipv-pro-vendor' ),
            'subtitle' => __( 'Inizia oggi con la prova gratuita. Nessuna carta di credito richiesta.', 'ipv-pro-vendor' ),
            'button_text' => __( 'Inizia Gratis', 'ipv-pro-vendor' ),
            'button_url' => '#pricing',
        ], $atts );

        ob_start();
        ?>
        <section class="ipv-landing ipv-cta ipv-section">
            <div class="ipv-container">
                <div class="ipv-cta__text" style="text-align: center; max-width: 700px; margin: 0 auto;">
                    <h2><?php echo esc_html( $atts['title'] ); ?></h2>
                    <p><?php echo esc_html( $atts['subtitle'] ); ?></p>
                    <a href="<?php echo esc_url( $atts['button_url'] ); ?>" class="ipv-btn ipv-btn--white ipv-btn--lg">
                        <?php echo esc_html( $atts['button_text'] ); ?>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </a>
                </div>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }

    /**
     * Get features list for a plan
     */
    private function get_plan_features( $slug, $plan ) {
        $all_features = [
            'credits' => sprintf( __( '%d crediti/anno', 'ipv-pro-vendor' ), $plan['credits'] ),
            'sites' => sprintf( __( '%d sito attivabile', 'ipv-pro-vendor' ), $plan['activations'] ),
            'transcription' => __( 'Trascrizione AI', 'ipv-pro-vendor' ),
            'seo' => __( 'Descrizioni SEO', 'ipv-pro-vendor' ),
            'priority_support' => __( 'Supporto prioritario', 'ipv-pro-vendor' ),
            'api_access' => __( 'Accesso API', 'ipv-pro-vendor' ),
            'white_label' => __( 'White label', 'ipv-pro-vendor' ),
        ];

        $plan_features = $plan['features'] ?? [];

        return [
            [ 'text' => $all_features['credits'], 'included' => true ],
            [ 'text' => $all_features['sites'], 'included' => true ],
            [ 'text' => $all_features['transcription'], 'included' => true ],
            [ 'text' => $all_features['seo'], 'included' => $slug !== 'trial' ],
            [ 'text' => $all_features['priority_support'], 'included' => ! empty( $plan_features['priority_support'] ) ],
            [ 'text' => $all_features['api_access'], 'included' => ! empty( $plan_features['api_access'] ) ],
        ];
    }

    /**
     * Get product URL for a plan
     */
    private function get_plan_product_url( $slug ) {
        global $wpdb;

        $product_id = $wpdb->get_var( $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta}
            WHERE meta_key = '_ipv_plan_slug' AND meta_value = %s
            LIMIT 1",
            $slug
        ) );

        if ( $product_id ) {
            return get_permalink( $product_id );
        }

        // Fallback to shop
        return wc_get_page_permalink( 'shop' );
    }

    /**
     * Get period label
     */
    private function get_period_label( $period ) {
        $labels = [
            'day' => __( 'giorno', 'ipv-pro-vendor' ),
            'week' => __( 'settimana', 'ipv-pro-vendor' ),
            'month' => __( 'mese', 'ipv-pro-vendor' ),
            'year' => __( 'anno', 'ipv-pro-vendor' ),
        ];
        return $labels[ $period ] ?? $period;
    }
}

// Initialize
IPV_Vendor_Landing_Page::instance();
