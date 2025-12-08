<?php
/**
 * Elementor Video Wall Widget
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Elementor_Video_Wall_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'ipv_video_wall';
    }

    public function get_title() {
        return '🎥 IPV Video Wall';
    }

    public function get_icon() {
        return 'eicon-posts-grid';
    }

    public function get_categories() {
        return [ 'ipv-production' ];
    }

    protected function register_controls() {
        // Content Section
        $this->start_controls_section(
            'content_section',
            [
                'label' => 'Video Wall Settings',
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        // Layout
        $this->add_control(
            'layout',
            [
                'label' => 'Layout',
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'grid',
                'options' => [
                    'grid' => 'Grid',
                    'masonry' => 'Masonry',
                    'list' => 'List',
                ],
            ]
        );

        // Infinite scroll
        $this->add_control(
            'infinite_scroll',
            [
                'label' => 'Infinite Scroll',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => 'Yes',
                'label_off' => 'No',
                'default' => 'yes',
            ]
        );

        // Show filters
        $this->add_control(
            'show_filters',
            [
                'label' => 'Show Category Filters',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => 'Yes',
                'label_off' => 'No',
                'default' => 'yes',
            ]
        );

        // Show search
        $this->add_control(
            'show_search',
            [
                'label' => 'Show Search Box',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => 'Yes',
                'label_off' => 'No',
                'default' => 'yes',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Render widget output
     */
    protected function render() {
        $settings = $this->get_settings_for_display();

        // Enqueue Video Wall assets
        wp_enqueue_style( 'ipv-video-wall' );
        wp_enqueue_script( 'ipv-video-wall' );

        $layout = $settings['layout'];
        $infinite_scroll = $settings['infinite_scroll'] === 'yes';
        $show_filters = $settings['show_filters'] === 'yes';
        $show_search = $settings['show_search'] === 'yes';

        ?>
        <div class="ipv-elementor-video-wall" data-layout="<?php echo esc_attr( $layout ); ?>" data-infinite-scroll="<?php echo $infinite_scroll ? '1' : '0'; ?>">
            <?php if ( $show_search || $show_filters ) : ?>
                <div class="ipv-wall-toolbar" style="margin-bottom: 20px; display: flex; gap: 15px; flex-wrap: wrap; align-items: center;">
                    <?php if ( $show_search ) : ?>
                        <div style="flex: 1; min-width: 250px;">
                            <input
                                type="text"
                                class="ipv-wall-search"
                                placeholder="🔍 Search videos..."
                                style="width: 100%; padding: 10px 15px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;"
                            >
                        </div>
                    <?php endif; ?>

                    <?php if ( $show_filters ) : ?>
                        <?php
                        $categories = get_terms( [
                            'taxonomy' => 'ipv_categoria',
                            'hide_empty' => true,
                        ] );
                        if ( ! empty( $categories ) ) :
                        ?>
                            <div class="ipv-wall-filters" style="display: flex; gap: 8px; flex-wrap: wrap;">
                                <button class="ipv-filter-btn active" data-category="all" style="padding: 8px 16px; border: 1px solid #ddd; background: #2271b1; color: #fff; border-radius: 4px; cursor: pointer; font-size: 13px;">
                                    All
                                </button>
                                <?php foreach ( $categories as $category ) : ?>
                                    <button class="ipv-filter-btn" data-category="<?php echo esc_attr( $category->term_id ); ?>" style="padding: 8px 16px; border: 1px solid #ddd; background: #fff; color: #333; border-radius: 4px; cursor: pointer; font-size: 13px;">
                                        <?php echo esc_html( $category->name ); ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="ipv-wall-container" id="ipv-wall-<?php echo uniqid(); ?>">
                <!-- Content loaded via AJAX or PHP -->
                <div class="ipv-wall-loading" style="text-align: center; padding: 40px;">
                    <div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #2271b1; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                </div>
            </div>

            <?php if ( $infinite_scroll ) : ?>
                <div class="ipv-wall-loader" style="text-align: center; padding: 20px; display: none;">
                    <div style="display: inline-block; width: 30px; height: 30px; border: 3px solid #f3f3f3; border-top: 3px solid #2271b1; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                </div>
            <?php else : ?>
                <div class="ipv-wall-pagination" style="text-align: center; margin-top: 30px;"></div>
            <?php endif; ?>
        </div>

        <style>
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }

            .ipv-filter-btn:hover {
                background: #f0f0f0 !important;
            }

            .ipv-filter-btn.active {
                background: #2271b1 !important;
                color: #fff !important;
            }
        </style>

        <script>
        jQuery(document).ready(function($) {
            const $container = $('.ipv-elementor-video-wall');
            const $wallContainer = $container.find('.ipv-wall-container');
            const $search = $container.find('.ipv-wall-search');
            const $filters = $container.find('.ipv-filter-btn');

            let currentPage = 1;
            let currentCategory = 'all';
            let currentSearch = '';
            let loading = false;

            // Load videos
            function loadVideos(reset = false) {
                if (loading) return;
                loading = true;

                if (reset) {
                    currentPage = 1;
                    $wallContainer.html('<div class="ipv-wall-loading" style="text-align: center; padding: 40px;"><div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #2271b1; border-radius: 50%; animation: spin 1s linear infinite;"></div></div>');
                }

                $.ajax({
                    url: ipvVideoWall.ajaxUrl,
                    type: 'GET',
                    data: {
                        action: 'ipv_load_video_wall',
                        page: currentPage,
                        category: currentCategory,
                        search: currentSearch,
                        layout: '<?php echo esc_js( $layout ); ?>',
                    },
                    success: function(response) {
                        if (response.success) {
                            if (reset) {
                                $wallContainer.html(response.data.html);
                            } else {
                                $wallContainer.append(response.data.html);
                            }

                            if (response.data.has_more) {
                                currentPage++;
                            }
                        }
                    },
                    complete: function() {
                        loading = false;
                    }
                });
            }

            // Search
            let searchTimeout;
            $search.on('input', function() {
                clearTimeout(searchTimeout);
                currentSearch = $(this).val();
                searchTimeout = setTimeout(function() {
                    loadVideos(true);
                }, 500);
            });

            // Filters
            $filters.on('click', function() {
                $filters.removeClass('active').css({'background': '#fff', 'color': '#333'});
                $(this).addClass('active').css({'background': '#2271b1', 'color': '#fff'});
                currentCategory = $(this).data('category');
                loadVideos(true);
            });

            // Infinite scroll
            <?php if ( $infinite_scroll ) : ?>
            $(window).on('scroll', function() {
                const scrollPos = $(window).scrollTop() + $(window).height();
                const containerBottom = $container.offset().top + $container.outerHeight();

                if (scrollPos >= containerBottom - 200 && !loading) {
                    loadVideos(false);
                }
            });
            <?php endif; ?>

            // Initial load
            loadVideos(true);
        });
        </script>
        <?php
    }

    protected function content_template() {
        ?>
        <div class="ipv-elementor-video-wall">
            <div style="background: #f0f0f0; padding: 60px; text-align: center; border-radius: 8px;">
                <h3 style="margin: 0 0 10px 0;">IPV Video Wall</h3>
                <p style="margin: 0; color: #666;">Preview available in frontend</p>
            </div>
        </div>
        <?php
    }
}
