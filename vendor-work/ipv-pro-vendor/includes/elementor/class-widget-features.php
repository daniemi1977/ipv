<?php
/**
 * IPV Features Widget for Elementor
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Widget_Features extends \Elementor\Widget_Base {

    public function get_name() {
        return 'ipv_features';
    }

    public function get_title() {
        return __( 'IPV Features Grid', 'ipv-pro-vendor' );
    }

    public function get_icon() {
        return 'eicon-gallery-grid';
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
                'default' => 'FUNZIONALITÀ',
            ]
        );

        $this->add_control(
            'title',
            [
                'label'   => __( 'Titolo', 'ipv-pro-vendor' ),
                'type'    => \Elementor\Controls_Manager::TEXT,
                'default' => __( 'Le Nostre Soluzioni', 'ipv-pro-vendor' ),
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'features_section',
            [
                'label' => __( 'Funzionalità', 'ipv-pro-vendor' ),
                'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $repeater = new \Elementor\Repeater();

        $repeater->add_control(
            'icon',
            [
                'label'   => __( 'Icona (Emoji)', 'ipv-pro-vendor' ),
                'type'    => \Elementor\Controls_Manager::TEXT,
                'default' => '🎥',
            ]
        );

        $repeater->add_control(
            'title',
            [
                'label'   => __( 'Titolo', 'ipv-pro-vendor' ),
                'type'    => \Elementor\Controls_Manager::TEXT,
                'default' => __( 'Funzionalità', 'ipv-pro-vendor' ),
            ]
        );

        $repeater->add_control(
            'description',
            [
                'label'   => __( 'Descrizione', 'ipv-pro-vendor' ),
                'type'    => \Elementor\Controls_Manager::TEXTAREA,
                'default' => __( 'Descrizione della funzionalità.', 'ipv-pro-vendor' ),
            ]
        );

        $this->add_control(
            'features',
            [
                'label'   => __( 'Funzionalità', 'ipv-pro-vendor' ),
                'type'    => \Elementor\Controls_Manager::REPEATER,
                'fields'  => $repeater->get_controls(),
                'default' => [
                    [
                        'icon'        => '🎥',
                        'title'       => __( 'Importazione Video', 'ipv-pro-vendor' ),
                        'description' => __( 'Importa video da YouTube e Vimeo con un click. Metadati automatici.', 'ipv-pro-vendor' ),
                    ],
                    [
                        'icon'        => '📝',
                        'title'       => __( 'Trascrizione AI', 'ipv-pro-vendor' ),
                        'description' => __( 'Trascrizioni automatiche con AI avanzata. Multilingua supportato.', 'ipv-pro-vendor' ),
                    ],
                    [
                        'icon'        => '🚀',
                        'title'       => __( 'SEO Automatico', 'ipv-pro-vendor' ),
                        'description' => __( 'Genera descrizioni e tag ottimizzati per i motori di ricerca.', 'ipv-pro-vendor' ),
                    ],
                ],
                'title_field' => '{{{ title }}}',
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
                'default' => '#F8FAFC',
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        ?>
        <section class="ipv-landing ipv-offers ipv-section" id="features" style="background: <?php echo esc_attr( $settings['bg_color'] ); ?>;">
            <div class="ipv-container">
                <div class="ipv-section-header">
                    <span class="ipv-badge"><?php echo esc_html( $settings['badge'] ); ?></span>
                    <h2><?php echo esc_html( $settings['title'] ); ?></h2>
                </div>

                <div class="ipv-row">
                    <?php foreach ( $settings['features'] as $feature ) : ?>
                    <div class="ipv-col ipv-col-33">
                        <div class="ipv-offer-card">
                            <div class="ipv-offer-card__icon">
                                <span style="font-size: 32px;"><?php echo esc_html( $feature['icon'] ); ?></span>
                            </div>
                            <h3><?php echo esc_html( $feature['title'] ); ?></h3>
                            <p><?php echo esc_html( $feature['description'] ); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php
    }
}
