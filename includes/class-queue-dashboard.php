<?php
if (!defined('ABSPATH')) exit;

class IPV_Queue_Dashboard {
    public static function init(){
        add_submenu_page(
            'ipv-production-system',
            'AI Queue',
            'AI Queue',
            'manage_options',
            'ipv-queue-dashboard',
            [__CLASS__,'render']
        );
    }

    public static function render(){
        $q = get_option('ipv_ai_queue',[]);
        echo '<div class="wrap"><h1>AI Queue Status</h1>';
        echo '<p>Video in coda:</p>';
        echo '<ul>';
        foreach($q as $id){
            echo '<li>'.get_the_title($id).'</li>';
        }
        echo '</ul>';
        echo '</div>';
    }
}
IPV_Queue_Dashboard::init();
