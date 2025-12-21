<?php
/**
 * IPV How It Works Widget for Elementor
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Widget_How_It_Works extends \Elementor\Widget_Base {

    public function get_name() {
        return 'ipv_how_it_works';
    }

    public function get_title() {
        return __( 'IPV How It Works', 'ipv-pro-vendor' );
    }

    public function get_icon() {
        return 'eicon-navigation-horizontal';
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
                'default' => 'PROCESSO',
            ]
        );

        $this->add_control(
            'title',
            [
                'label'   => __( 'Titolo', 'ipv-pro-vendor' ),
                'type'    => \Elementor\Controls_Manager::TEXT,
                'default' => __( 'Come Funziona', 'ipv-pro-vendor' ),
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'steps_section',
            [
                'label' => __( 'Steps', 'ipv-pro-vendor' ),
                'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $repeater = new \Elementor\Repeater();

        $repeater->add_control(
            'step_number',
            [
                'label'   => __( 'Numero Step', 'ipv-pro-vendor' ),
                'type'    => \Elementor\Controls_Manager::TEXT,
                'default' => '01',
            ]
        );

        $repeater->add_control(
            'title',
            [
                'label'   => __( 'Titolo', 'ipv-pro-vendor' ),
                'type'    => \Elementor\Controls_Manager::TEXT,
                'default' => 'Step',
            ]
        );

        $repeater->add_control(
            'description',
            [
                'label'   => __( 'Descrizione', 'ipv-pro-vendor' ),
                'type'    => \Elementor\Controls_Manager::TEXTAREA,
            ]
        );

        $repeater->add_control(
            'featured',
            [
                'label'   => __( 'In evidenza', 'ipv-pro-vendor' ),
                'type'    => \Elementor\Controls_Manager::SWITCHER,
                'default' => '',
            ]
        );

        $this->add_control(
            'steps',
            [
                'label'   => __( 'Steps', 'ipv-pro-vendor' ),
                'type'    => \Elementor\Controls_Manager::REPEATER,
                'fields'  => $repeater->get_controls(),
                'default' => [
                    [
                        'step_number' => '01',
                        'title'       => __( 'Importa Video', 'ipv-pro-vendor' ),
                        'description' => __( 'Incolla il link YouTube o carica il tuo video. L\'importazione è automatica.', 'ipv-pro-vendor' ),
                    ],
                    [
                        'step_number' => '02',
                        'title'       => __( 'AI Elabora', 'ipv-pro-vendor' ),
                        'description' => __( 'La nostra AI trascrive, analizza e genera contenuti ottimizzati.', 'ipv-pro-vendor' ),
                        'featured'    => 'yes',
                    ],
                    [
                        'step_number' => '03',
                        'title'       => __( 'Pubblica', 'ipv-pro-vendor' ),
                        'description' => __( 'Rivedi, modifica se necessario e pubblica. Tutto in pochi minuti.', 'ipv-pro-vendor' ),
                    ],
                ],
                'title_field' => 'Step {{{ step_number }}}: {{{ title }}}',
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        ?>
        <section class="ipv-landing ipv-how-it-works ipv-section">
            <div class="ipv-container">
                <div class="ipv-section-header">
                    <span class="ipv-badge ipv-badge--light"><?php echo esc_html( $settings['badge'] ); ?></span>
                    <h2><?php echo esc_html( $settings['title'] ); ?></h2>
                </div>

                <div class="ipv-steps">
                    <?php foreach ( $settings['steps'] as $step ) :
                        $is_featured = $step['featured'] === 'yes';
                    ?>
                    <div class="ipv-step <?php echo $is_featured ? 'ipv-step--featured' : ''; ?>">
                        <span class="ipv-step__badge">STEP-<?php echo esc_html( $step['step_number'] ); ?></span>
                        <h3><?php echo esc_html( $step['title'] ); ?></h3>
                        <p><?php echo esc_html( $step['description'] ); ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php
    }
}
