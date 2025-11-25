<?php
/**
 * Elementor Video Grid Widget
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Video_Grid_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'ipv_video_grid';
    }

    public function get_title() {
        return __( 'IPV Video Grid', 'ipv-production-system-pro' );
    }

    public function get_icon() {
        return 'eicon-posts-grid';
    }

    public function get_categories() {
        return [ 'ipv-elements' ];
    }

    protected function register_controls() {
        $this->start_controls_section(
            'content_section',
            [
                'label' => __( 'Content', 'ipv-production-system-pro' ),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'per_page',
            [
                'label' => __( 'Video per pagina', 'ipv-production-system-pro' ),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 12,
            ]
        );

        $this->add_control(
            'columns',
            [
                'label' => __( 'Colonne', 'ipv-production-system-pro' ),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => '3',
                'options' => [
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                ],
            ]
        );

        $this->add_control(
            'show_filters',
            [
                'label' => __( 'Mostra Filtri', 'ipv-production-system-pro' ),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();

        echo do_shortcode( sprintf(
            '[ipv_video_wall per_page="%d" columns="%s" show_filters="%s"]',
            $settings['per_page'],
            $settings['columns'],
            $settings['show_filters']
        ) );
    }
}
