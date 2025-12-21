<?php
/**
 * IPV Hero Widget for Elementor
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Widget_Hero extends \Elementor\Widget_Base {

    public function get_name() {
        return 'ipv_hero';
    }

    public function get_title() {
        return __( 'IPV Hero Section', 'ipv-pro-vendor' );
    }

    public function get_icon() {
        return 'eicon-header';
    }

    public function get_categories() {
        return [ 'ipv-vendor' ];
    }

    public function get_style_depends() {
        return [ 'ipv-landing-page' ];
    }

    protected function register_controls() {

        // Content Section
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
                'type'    => \Elementor\Controls_Manager::TEXTAREA,
                'default' => __( 'Automatizza i tuoi Video YouTube con l\'AI', 'ipv-pro-vendor' ),
            ]
        );

        $this->add_control(
            'subtitle',
            [
                'label'   => __( 'Sottotitolo', 'ipv-pro-vendor' ),
                'type'    => \Elementor\Controls_Manager::TEXTAREA,
                'default' => __( 'Trascrizioni automatiche, descrizioni SEO ottimizzate e molto altro. Risparmia ore di lavoro con IPV Pro.', 'ipv-pro-vendor' ),
            ]
        );

        $this->add_control(
            'button_text',
            [
                'label'   => __( 'Testo Pulsante Primario', 'ipv-pro-vendor' ),
                'type'    => \Elementor\Controls_Manager::TEXT,
                'default' => __( 'Inizia Gratis', 'ipv-pro-vendor' ),
            ]
        );

        $this->add_control(
            'button_url',
            [
                'label'   => __( 'URL Pulsante Primario', 'ipv-pro-vendor' ),
                'type'    => \Elementor\Controls_Manager::URL,
                'default' => [ 'url' => '#pricing' ],
            ]
        );

        $this->add_control(
            'button2_text',
            [
                'label'   => __( 'Testo Pulsante Secondario', 'ipv-pro-vendor' ),
                'type'    => \Elementor\Controls_Manager::TEXT,
                'default' => __( 'Scopri di più', 'ipv-pro-vendor' ),
            ]
        );

        $this->add_control(
            'button2_url',
            [
                'label'   => __( 'URL Pulsante Secondario', 'ipv-pro-vendor' ),
                'type'    => \Elementor\Controls_Manager::URL,
                'default' => [ 'url' => '#features' ],
            ]
        );

        $this->add_control(
            'image',
            [
                'label' => __( 'Immagine Hero', 'ipv-pro-vendor' ),
                'type'  => \Elementor\Controls_Manager::MEDIA,
            ]
        );

        $this->end_controls_section();

        // Style Section
        $this->start_controls_section(
            'style_section',
            [
                'label' => __( 'Stile', 'ipv-pro-vendor' ),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'bg_color_start',
            [
                'label'   => __( 'Colore Sfondo (Inizio)', 'ipv-pro-vendor' ),
                'type'    => \Elementor\Controls_Manager::COLOR,
                'default' => '#8B5CF6',
            ]
        );

        $this->add_control(
            'bg_color_end',
            [
                'label'   => __( 'Colore Sfondo (Fine)', 'ipv-pro-vendor' ),
                'type'    => \Elementor\Controls_Manager::COLOR,
                'default' => '#6D28D9',
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $bg_style = sprintf( 'background: linear-gradient(135deg, %s 0%%, %s 100%%);', $settings['bg_color_start'], $settings['bg_color_end'] );
        ?>
        <section class="ipv-landing ipv-hero" style="<?php echo esc_attr( $bg_style ); ?>">
            <div class="ipv-container">
                <div class="ipv-hero__content">
                    <h1><?php echo wp_kses_post( $settings['title'] ); ?></h1>
                    <p><?php echo esc_html( $settings['subtitle'] ); ?></p>

                    <div class="ipv-hero__buttons">
                        <?php if ( ! empty( $settings['button_text'] ) ) : ?>
                        <a href="<?php echo esc_url( $settings['button_url']['url'] ); ?>" class="ipv-btn ipv-btn--white ipv-btn--lg">
                            <?php echo esc_html( $settings['button_text'] ); ?>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                <polyline points="12 5 19 12 12 19"></polyline>
                            </svg>
                        </a>
                        <?php endif; ?>

                        <?php if ( ! empty( $settings['button2_text'] ) ) : ?>
                        <a href="<?php echo esc_url( $settings['button2_url']['url'] ); ?>" class="ipv-btn ipv-btn--outline" style="color: white; border-color: white;">
                            <?php echo esc_html( $settings['button2_text'] ); ?>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ( ! empty( $settings['image']['url'] ) ) : ?>
                <div class="ipv-hero__image">
                    <img src="<?php echo esc_url( $settings['image']['url'] ); ?>" alt="">
                </div>
                <?php endif; ?>
            </div>
        </section>
        <?php
    }
}
