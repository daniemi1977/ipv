<?php
/**
 * IPV CTA Widget for Elementor
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Widget_CTA extends \Elementor\Widget_Base {

    public function get_name() {
        return 'ipv_cta';
    }

    public function get_title() {
        return __( 'IPV Call to Action', 'ipv-pro-vendor' );
    }

    public function get_icon() {
        return 'eicon-call-to-action';
    }

    public function get_categories() {
        return [ 'ipv-vendor' ];
    }

    public function get_style_depends() {
        return [ 'ipv-landing-page' ];
    }

    protected function register_controls() {

        $this->start_controls_section(
            'content_section',
            [
                'label' => __( 'Contenuto', 'ipv-pro-vendor' ),
                'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'title',
            [
                'label'   => __( 'Titolo', 'ipv-pro-vendor' ),
                'type'    => \Elementor\Controls_Manager::TEXT,
                'default' => __( 'Pronto a Risparmiare Tempo?', 'ipv-pro-vendor' ),
            ]
        );

        $this->add_control(
            'subtitle',
            [
                'label'   => __( 'Sottotitolo', 'ipv-pro-vendor' ),
                'type'    => \Elementor\Controls_Manager::TEXTAREA,
                'default' => __( 'Inizia oggi con la prova gratuita. Nessuna carta di credito richiesta.', 'ipv-pro-vendor' ),
            ]
        );

        $this->add_control(
            'button_text',
            [
                'label'   => __( 'Testo Pulsante', 'ipv-pro-vendor' ),
                'type'    => \Elementor\Controls_Manager::TEXT,
                'default' => __( 'Inizia Gratis', 'ipv-pro-vendor' ),
            ]
        );

        $this->add_control(
            'button_url',
            [
                'label'   => __( 'URL Pulsante', 'ipv-pro-vendor' ),
                'type'    => \Elementor\Controls_Manager::URL,
                'default' => [ 'url' => '#pricing' ],
            ]
        );

        $this->end_controls_section();

        // Style
        $this->start_controls_section(
            'style_section',
            [
                'label' => __( 'Stile', 'ipv-pro-vendor' ),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'bg_color',
            [
                'label'   => __( 'Colore Sfondo', 'ipv-pro-vendor' ),
                'type'    => \Elementor\Controls_Manager::COLOR,
                'default' => '#8B5CF6',
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        ?>
        <section class="ipv-landing ipv-cta ipv-section" style="background: <?php echo esc_attr( $settings['bg_color'] ); ?>;">
            <div class="ipv-container">
                <div class="ipv-cta__text" style="text-align: center; max-width: 700px; margin: 0 auto;">
                    <h2><?php echo esc_html( $settings['title'] ); ?></h2>
                    <p><?php echo esc_html( $settings['subtitle'] ); ?></p>
                    <a href="<?php echo esc_url( $settings['button_url']['url'] ); ?>" class="ipv-btn ipv-btn--white ipv-btn--lg">
                        <?php echo esc_html( $settings['button_text'] ); ?>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </a>
                </div>
            </div>
        </section>
        <?php
    }
}
