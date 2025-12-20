<?php
/**
 * Elementor Video Player Widget
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Elementor_Video_Player_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'ipv_video_player';
    }

    public function get_title() {
        return '🎬 IPV Video Player';
    }

    public function get_icon() {
        return 'eicon-youtube';
    }

    public function get_categories() {
        return [ 'ipv-production' ];
    }

    protected function register_controls() {
        // Content Section
        $this->start_controls_section(
            'content_section',
            [
                'label' => 'Video Settings',
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        // Video Selection
        $this->add_control(
            'video_id',
            [
                'label' => 'Select Video',
                'type' => \Elementor\Controls_Manager::SELECT2,
                'options' => $this->get_video_options(),
                'label_block' => true,
            ]
        );

        // Autoplay
        $this->add_control(
            'autoplay',
            [
                'label' => 'Autoplay',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => 'Yes',
                'label_off' => 'No',
                'default' => 'no',
            ]
        );

        // Controls
        $this->add_control(
            'controls',
            [
                'label' => 'Show Controls',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => 'Yes',
                'label_off' => 'No',
                'default' => 'yes',
            ]
        );

        // Mute
        $this->add_control(
            'mute',
            [
                'label' => 'Mute',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => 'Yes',
                'label_off' => 'No',
                'default' => 'no',
            ]
        );

        // Loop
        $this->add_control(
            'loop',
            [
                'label' => 'Loop',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => 'Yes',
                'label_off' => 'No',
                'default' => 'no',
            ]
        );

        $this->end_controls_section();

        // Style Section
        $this->start_controls_section(
            'style_section',
            [
                'label' => 'Player Style',
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        // Aspect Ratio
        $this->add_control(
            'aspect_ratio',
            [
                'label' => 'Aspect Ratio',
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => '16:9',
                'options' => [
                    '16:9' => '16:9',
                    '4:3' => '4:3',
                    '21:9' => '21:9',
                    '1:1' => '1:1',
                ],
            ]
        );

        // Width
        $this->add_responsive_control(
            'width',
            [
                'label' => 'Width',
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => [ '%', 'px' ],
                'range' => [
                    '%' => [
                        'min' => 10,
                        'max' => 100,
                    ],
                    'px' => [
                        'min' => 100,
                        'max' => 2000,
                    ],
                ],
                'default' => [
                    'unit' => '%',
                    'size' => 100,
                ],
                'selectors' => [
                    '{{WRAPPER}} .ipv-elementor-player' => 'width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Get video options for dropdown
     */
    private function get_video_options() {
        $videos = get_posts( [
            'post_type' => 'ipv_video',
            'posts_per_page' => 100,
            'orderby' => 'date',
            'order' => 'DESC',
        ] );

        $options = [ '' => 'Select a video...' ];

        foreach ( $videos as $video ) {
            $options[ $video->ID ] = $video->post_title;
        }

        return $options;
    }

    /**
     * Render widget output
     */
    protected function render() {
        $settings = $this->get_settings_for_display();
        $video_id = $settings['video_id'];

        if ( empty( $video_id ) ) {
            if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
                echo '<div style="padding: 20px; background: #f0f0f0; text-align: center;">Please select a video</div>';
            }
            return;
        }

        $post = get_post( $video_id );
        if ( ! $post ) {
            return;
        }

        // Get video data
        $video_source = get_post_meta( $video_id, '_ipv_video_source', true ) ?: 'youtube';
        $video_yt_id = get_post_meta( $video_id, '_ipv_video_id', true );
        $thumbnail = get_post_meta( $video_id, '_ipv_yt_thumbnail_url', true );

        // Build embed URL based on source
        $embed_url = '';
        $autoplay = $settings['autoplay'] === 'yes' ? 1 : 0;
        $controls = $settings['controls'] === 'yes' ? 1 : 0;
        $mute = $settings['mute'] === 'yes' ? 1 : 0;
        $loop = $settings['loop'] === 'yes' ? 1 : 0;

        switch ( $video_source ) {
            case 'youtube':
                $embed_url = "https://www.youtube.com/embed/{$video_yt_id}?autoplay={$autoplay}&controls={$controls}&mute={$mute}&loop={$loop}";
                break;
            case 'vimeo':
                $embed_url = "https://player.vimeo.com/video/{$video_yt_id}?autoplay={$autoplay}&controls={$controls}&muted={$mute}&loop={$loop}";
                break;
            case 'dailymotion':
                $embed_url = "https://www.dailymotion.com/embed/video/{$video_yt_id}?autoplay={$autoplay}&controls={$controls}&mute={$mute}";
                break;
        }

        // Calculate padding based on aspect ratio
        $aspect_ratios = [
            '16:9' => '56.25%',
            '4:3' => '75%',
            '21:9' => '42.857%',
            '1:1' => '100%',
        ];
        $padding = $aspect_ratios[ $settings['aspect_ratio'] ] ?? '56.25%';

        ?>
        <div class="ipv-elementor-player" style="position: relative; padding-bottom: <?php echo esc_attr( $padding ); ?>; height: 0; overflow: hidden;">
            <iframe
                src="<?php echo esc_url( $embed_url ); ?>"
                style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;"
                frameborder="0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen
            ></iframe>
        </div>
        <?php
    }

    /**
     * Render widget in editor
     */
    protected function content_template() {
        ?>
        <#
        if ( ! settings.video_id ) {
            #>
            <div style="padding: 20px; background: #f0f0f0; text-align: center;">
                Please select a video
            </div>
            <#
            return;
        }
        #>
        <div class="ipv-elementor-player" style="background: #000; aspect-ratio: 16/9;">
            <div style="color: #fff; padding: 40px; text-align: center;">
                Video Player (Preview in frontend)
            </div>
        </div>
        <?php
    }
}
