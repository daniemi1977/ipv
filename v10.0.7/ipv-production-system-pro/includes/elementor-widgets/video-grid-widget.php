<?php
/**
 * Elementor Video Grid Widget
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Elementor_Video_Grid_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'ipv_video_grid';
    }

    public function get_title() {
        return '📱 IPV Video Grid';
    }

    public function get_icon() {
        return 'eicon-gallery-grid';
    }

    public function get_categories() {
        return [ 'ipv-production' ];
    }

    protected function register_controls() {
        // Content Section
        $this->start_controls_section(
            'content_section',
            [
                'label' => 'Grid Settings',
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        // Number of videos
        $this->add_control(
            'posts_per_page',
            [
                'label' => 'Number of Videos',
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 6,
                'min' => 1,
                'max' => 50,
            ]
        );

        // Columns
        $this->add_responsive_control(
            'columns',
            [
                'label' => 'Columns',
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => '3',
                'tablet_default' => '2',
                'mobile_default' => '1',
                'options' => [
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                    '6' => '6',
                ],
            ]
        );

        // Category filter
        $this->add_control(
            'category',
            [
                'label' => 'Filter by Category',
                'type' => \Elementor\Controls_Manager::SELECT2,
                'multiple' => true,
                'options' => $this->get_category_options(),
                'label_block' => true,
            ]
        );

        // Order by
        $this->add_control(
            'orderby',
            [
                'label' => 'Order By',
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'date',
                'options' => [
                    'date' => 'Date',
                    'title' => 'Title',
                    'rand' => 'Random',
                    'meta_value_num' => 'Views',
                ],
            ]
        );

        // Order
        $this->add_control(
            'order',
            [
                'label' => 'Order',
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'DESC',
                'options' => [
                    'ASC' => 'Ascending',
                    'DESC' => 'Descending',
                ],
            ]
        );

        // Show info
        $this->add_control(
            'show_title',
            [
                'label' => 'Show Title',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => 'Yes',
                'label_off' => 'No',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_excerpt',
            [
                'label' => 'Show Excerpt',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => 'Yes',
                'label_off' => 'No',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_meta',
            [
                'label' => 'Show Meta (Duration, Views)',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => 'Yes',
                'label_off' => 'No',
                'default' => 'yes',
            ]
        );

        $this->end_controls_section();

        // Style Section
        $this->start_controls_section(
            'style_section',
            [
                'label' => 'Grid Style',
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        // Gap
        $this->add_responsive_control(
            'gap',
            [
                'label' => 'Gap',
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'size' => 20,
                ],
                'selectors' => [
                    '{{WRAPPER}} .ipv-elementor-grid' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        // Border radius
        $this->add_control(
            'border_radius',
            [
                'label' => 'Border Radius',
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => [ 'px', '%' ],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                    ],
                ],
                'default' => [
                    'size' => 8,
                ],
                'selectors' => [
                    '{{WRAPPER}} .ipv-grid-item' => 'border-radius: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .ipv-grid-item img' => 'border-radius: {{SIZE}}{{UNIT}} {{SIZE}}{{UNIT}} 0 0;',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Get category options
     */
    private function get_category_options() {
        $terms = get_terms( [
            'taxonomy' => 'ipv_categoria',
            'hide_empty' => false,
        ] );

        $options = [];
        foreach ( $terms as $term ) {
            $options[ $term->term_id ] = $term->name;
        }

        return $options;
    }

    /**
     * Render widget output
     */
    protected function render() {
        $settings = $this->get_settings_for_display();

        // Build query args
        $args = [
            'post_type' => 'ipv_video',
            'posts_per_page' => $settings['posts_per_page'],
            'orderby' => $settings['orderby'],
            'order' => $settings['order'],
        ];

        // Add category filter
        if ( ! empty( $settings['category'] ) ) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'ipv_categoria',
                    'field' => 'term_id',
                    'terms' => $settings['category'],
                ],
            ];
        }

        // Order by views
        if ( $settings['orderby'] === 'meta_value_num' ) {
            $args['meta_key'] = '_ipv_yt_view_count';
        }

        $query = new WP_Query( $args );

        if ( ! $query->have_posts() ) {
            echo '<p>No videos found.</p>';
            return;
        }

        $columns = $settings['columns'];
        $columns_tablet = $settings['columns_tablet'] ?? 2;
        $columns_mobile = $settings['columns_mobile'] ?? 1;

        ?>
        <div class="ipv-elementor-grid" style="display: grid; grid-template-columns: repeat(<?php echo esc_attr( $columns ); ?>, 1fr);">
            <?php while ( $query->have_posts() ) : $query->the_post(); ?>
                <?php
                $video_id = get_the_ID();
                $thumbnail = get_post_meta( $video_id, '_ipv_yt_thumbnail_url', true );
                $duration = get_post_meta( $video_id, '_ipv_yt_duration_formatted', true );
                $views = get_post_meta( $video_id, '_ipv_yt_view_count', true );
                $permalink = get_permalink();
                ?>
                <div class="ipv-grid-item" style="background: #fff; border: 1px solid #e0e0e0; overflow: hidden;">
                    <a href="<?php echo esc_url( $permalink ); ?>" style="text-decoration: none; color: inherit;">
                        <?php if ( $thumbnail ) : ?>
                            <div style="position: relative; padding-bottom: 56.25%; background: #000;">
                                <img
                                    src="<?php echo esc_url( $thumbnail ); ?>"
                                    alt="<?php the_title_attribute(); ?>"
                                    style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;"
                                >
                                <?php if ( $settings['show_meta'] === 'yes' && $duration ) : ?>
                                    <span style="position: absolute; bottom: 8px; right: 8px; background: rgba(0,0,0,0.8); color: #fff; padding: 2px 6px; border-radius: 3px; font-size: 12px;">
                                        <?php echo esc_html( $duration ); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div style="padding: 15px;">
                            <?php if ( $settings['show_title'] === 'yes' ) : ?>
                                <h3 style="margin: 0 0 10px 0; font-size: 16px; line-height: 1.4;">
                                    <?php the_title(); ?>
                                </h3>
                            <?php endif; ?>

                            <?php if ( $settings['show_excerpt'] === 'yes' ) : ?>
                                <p style="margin: 0 0 10px 0; font-size: 14px; color: #666; line-height: 1.5;">
                                    <?php echo wp_trim_words( get_the_excerpt(), 20 ); ?>
                                </p>
                            <?php endif; ?>

                            <?php if ( $settings['show_meta'] === 'yes' && $views ) : ?>
                                <div style="font-size: 13px; color: #999;">
                                    👁️ <?php echo number_format( $views ); ?> views
                                </div>
                            <?php endif; ?>
                        </div>
                    </a>
                </div>
            <?php endwhile; ?>
        </div>

        <style>
            @media (max-width: 768px) {
                .ipv-elementor-grid {
                    grid-template-columns: repeat(<?php echo esc_attr( $columns_tablet ); ?>, 1fr) !important;
                }
            }
            @media (max-width: 480px) {
                .ipv-elementor-grid {
                    grid-template-columns: repeat(<?php echo esc_attr( $columns_mobile ); ?>, 1fr) !important;
                }
            }
        </style>
        <?php

        wp_reset_postdata();
    }

    protected function content_template() {
        ?>
        <div class="ipv-elementor-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
            <div style="background: #f0f0f0; padding: 40px; text-align: center; border-radius: 8px;">
                Video Grid (Preview in frontend)
            </div>
            <div style="background: #f0f0f0; padding: 40px; text-align: center; border-radius: 8px;">
                Video Grid (Preview in frontend)
            </div>
            <div style="background: #f0f0f0; padding: 40px; text-align: center; border-radius: 8px;">
                Video Grid (Preview in frontend)
            </div>
        </div>
        <?php
    }
}
