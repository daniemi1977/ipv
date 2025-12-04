<?php
if (!defined('ABSPATH')) exit;

class IPV_Queue_Dashboard {
    public static function init(){
        add_submenu_page(
            'ipv-production',
            'AI Queue',
            'AI Queue',
            'manage_options',
            'ipv-queue-dashboard',
            [__CLASS__,'render']
        );
    }

    public static function render(){
        $q = get_option('ipv_ai_queue',[]);
        echo '<div class="wrap"><h1>' . esc_html__( 'AI Queue Status', 'ipv-production-system-pro' ) . '</h1>';
        echo '<p>' . esc_html__( 'Queued videos:', 'ipv-production-system-pro' ) . '</p>';
        echo '<ul>';
        foreach($q as $id){
            echo '<li>' . esc_html( get_the_title($id) ) . '</li>';
        }
        echo '</ul>';
        echo '</div>';
    }
}
IPV_Queue_Dashboard::init();
