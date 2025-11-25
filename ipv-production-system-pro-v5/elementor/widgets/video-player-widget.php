<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class IPV_Video_Player_Widget extends \Elementor\Widget_Base {
    public function get_name() { return 'ipv_video_player'; }
    public function get_title() { return __( 'IPV Video Player', 'ipv-production-system-pro' ); }
    public function get_icon() { return 'eicon-youtube'; }
    public function get_categories() { return [ 'ipv-elements' ]; }

    protected function register_controls() {
        $this->start_controls_section('content_section', [
            'label' => __( 'Content', 'ipv-production-system-pro' ),
            'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);
        $this->add_control('video_id', [
            'label' => __( 'Video ID YouTube', 'ipv-production-system-pro' ),
            'type' => \Elementor\Controls_Manager::TEXT,
            'placeholder' => 'dQw4w9WgXcQ',
        ]);
        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $video_id = $settings['video_id'];
        if ( $video_id ) {
            echo '<div class="ipv-video-player-wrapper"><iframe width="100%" height="500" src="https://www.youtube.com/embed/' . esc_attr($video_id) . '" frameborder="0" allowfullscreen></iframe></div>';
        }
    }
}
