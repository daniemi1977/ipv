<?php
/**
 * IPV Pricing Widget for Elementor
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Widget_Pricing extends \Elementor\Widget_Base {

    public function get_name() {
        return 'ipv_pricing';
    }

    public function get_title() {
        return __( 'IPV Pricing Table', 'ipv-pro-vendor' );
    }

    public function get_icon() {
        return 'eicon-price-table';
    }

    public function get_categories() {
        return [ 'ipv-vendor' ];
    }

    public function get_style_depends() {
        return [ 'ipv-landing-page' ];
    }

    protected function register_controls() {

        $this->start_controls_section(
            'header_section',
            [
                'label' => __( 'Intestazione', 'ipv-pro-vendor' ),
                'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'badge',
            [
                'label'   => __( 'Badge', 'ipv-pro-vendor' ),
                'type'    => \Elementor\Controls_Manager::TEXT,
                'default' => 'PRICING',
            ]
        );

        $this->add_control(
            'title',
            [
                'label'   => __( 'Titolo', 'ipv-pro-vendor' ),
                'type'    => \Elementor\Controls_Manager::TEXT,
                'default' => __( 'Prezzi Trasparenti', 'ipv-pro-vendor' ),
            ]
        );

        $this->add_control(
            'featured_plan',
            [
                'label'   => __( 'Piano in evidenza', 'ipv-pro-vendor' ),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'default' => 'professional',
                'options' => [
                    'trial'        => 'Trial',
                    'starter'      => 'Starter',
                    'professional' => 'Professional',
                    'business'     => 'Business',
                ],
            ]
        );

        $this->add_control(
            'show_extras',
            [
                'label'   => __( 'Mostra Extra Credits', 'ipv-pro-vendor' ),
                'type'    => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();

        // Get plans from manager
        if ( class_exists( 'IPV_Vendor_Plans_Manager' ) ) {
            $plans_manager = IPV_Vendor_Plans_Manager::instance();
            $plans = $plans_manager->get_plans();
        } else {
            $plans = $this->get_default_plans();
        }

        $pricing_plans = [];
        foreach ( [ 'trial', 'starter', 'professional', 'business' ] as $slug ) {
            if ( isset( $plans[ $slug ] ) ) {
                $pricing_plans[ $slug ] = $plans[ $slug ];
            }
        }
        ?>
        <section class="ipv-landing ipv-pricing ipv-section" id="pricing">
            <div class="ipv-container">
                <div class="ipv-section-header">
                    <span class="ipv-badge ipv-badge--light"><?php echo esc_html( $settings['badge'] ); ?></span>
                    <h2><?php echo esc_html( $settings['title'] ); ?></h2>
                </div>

                <div class="ipv-pricing-cards">
                    <?php foreach ( $pricing_plans as $slug => $plan ) :
                        $is_featured = $slug === $settings['featured_plan'];
                        $features = $this->get_plan_features( $slug, $plan );
                    ?>
                    <div class="ipv-pricing-card <?php echo $is_featured ? 'ipv-pricing-card--featured' : ''; ?>">
                        <div class="ipv-pricing-card__name">
                            <?php echo esc_html( $plan['name'] ); ?>
                        </div>
                        <div class="ipv-pricing-card__price">
                            <?php if ( $plan['price'] > 0 ) : ?>
                                €<?php echo number_format( $plan['price'], 2, ',', '.' ); ?>
                                <span>/anno</span>
                            <?php else : ?>
                                Gratis<span>&nbsp;</span>
                            <?php endif; ?>
                        </div>

                        <ul class="ipv-pricing-card__features">
                            <?php foreach ( $features as $feature ) : ?>
                            <li class="<?php echo $feature['included'] ? '' : 'disabled'; ?>">
                                <?php if ( $feature['included'] ) : ?>
                                    <svg class="check" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                <?php else : ?>
                                    <svg class="x" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="18" y1="6" x2="6" y2="18"></line>
                                        <line x1="6" y1="6" x2="18" y2="18"></line>
                                    </svg>
                                <?php endif; ?>
                                <?php echo esc_html( $feature['text'] ); ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>

                        <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="ipv-btn <?php echo $is_featured ? 'ipv-btn--primary' : 'ipv-btn--outline'; ?>">
                            <?php echo $slug === 'trial' ? 'Inizia Gratis' : 'Scegli Piano'; ?>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                <polyline points="12 5 19 12 12 19"></polyline>
                            </svg>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php if ( $settings['show_extras'] === 'yes' ) : ?>
                <div class="ipv-pricing-extras" style="margin-top: 60px; text-align: center;">
                    <h3 style="color: #fff; margin-bottom: 30px;">Hai bisogno di crediti extra?</h3>
                    <div style="display: flex; justify-content: center; gap: 20px; flex-wrap: wrap;">
                        <?php
                        $extras = [
                            'golden_prompt'     => [ 'name' => 'Golden Prompt', 'price' => 69, 'credits' => 50, 'icon' => '⭐' ],
                            'extra_credits_10'  => [ 'name' => 'Extra 10', 'price' => 5, 'credits' => 10, 'icon' => '💰' ],
                            'extra_credits_100' => [ 'name' => 'Extra 100', 'price' => 45, 'credits' => 100, 'icon' => '💎' ],
                        ];
                        foreach ( $extras as $slug => $extra ) :
                        ?>
                        <div style="background: rgba(255,255,255,0.1); padding: 25px; border-radius: 12px; min-width: 180px;">
                            <div style="font-size: 32px; margin-bottom: 10px;"><?php echo $extra['icon']; ?></div>
                            <h4 style="color: #fff; margin-bottom: 5px;"><?php echo esc_html( $extra['name'] ); ?></h4>
                            <div style="font-size: 24px; font-weight: 700; color: #A855F7; margin-bottom: 5px;">
                                €<?php echo number_format( $extra['price'], 2, ',', '.' ); ?>
                            </div>
                            <div style="color: rgba(255,255,255,0.7); font-size: 14px;">
                                +<?php echo $extra['credits']; ?> crediti
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </section>
        <?php
    }

    private function get_plan_features( $slug, $plan ) {
        $credits = isset( $plan['credits'] ) ? $plan['credits'] : 0;
        $activations = isset( $plan['activations'] ) ? $plan['activations'] : 1;

        return [
            [ 'text' => sprintf( '%d crediti/anno', $credits ), 'included' => true ],
            [ 'text' => sprintf( '%d sito attivabile', $activations ), 'included' => true ],
            [ 'text' => 'Trascrizione AI', 'included' => true ],
            [ 'text' => 'Descrizioni SEO', 'included' => $slug !== 'trial' ],
            [ 'text' => 'Supporto prioritario', 'included' => in_array( $slug, [ 'professional', 'business' ] ) ],
            [ 'text' => 'Accesso API', 'included' => $slug === 'business' ],
        ];
    }

    private function get_default_plans() {
        return [
            'trial' => [ 'name' => 'Trial', 'price' => 0, 'credits' => 10, 'activations' => 1 ],
            'starter' => [ 'name' => 'Starter', 'price' => 109.99, 'credits' => 100, 'activations' => 1 ],
            'professional' => [ 'name' => 'Professional', 'price' => 199.99, 'credits' => 250, 'activations' => 3 ],
            'business' => [ 'name' => 'Business', 'price' => 309.90, 'credits' => 500, 'activations' => 10 ],
        ];
    }
}
