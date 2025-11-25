<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Video_Slider_Widget extends \Elementor\Widget_Base {
    public function get_name() { return 'ipv_video_slider'; }
    public function get_title() { return __( 'IPV Video Slider', 'ipv-production-system-pro' ); }
    public function get_icon() { return 'eicon-slider-push'; }
    public function get_categories() { return [ 'ipv-elements' ]; }

    protected function register_controls() {
        $this->start_controls_section('content_section', [
            'label' => __( 'Content', 'ipv-production-system-pro' ),
        ]);
        $this->add_control('posts_per_page', [
            'label' => __( 'Numero Video', 'ipv-production-system-pro' ),
            'type' => \Elementor\Controls_Manager::NUMBER,
            'default' => 6,
        ]);
        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        echo '[ipv_video_wall per_page="' . esc_attr($settings['posts_per_page']) . '" layout="grid" columns="3"]';
    }
}
