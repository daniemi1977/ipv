<?php
if (!defined('ABSPATH')) exit;

class IPV_AI_Queue {

    const OPTION_KEY = 'ipv_ai_queue';

    public static function init() {
        add_action('ipv_ai_queue_runner', [__CLASS__, 'run']);
        if (!wp_next_scheduled('ipv_ai_queue_runner')) {
            wp_schedule_event(time()+60, 'minute', 'ipv_ai_queue_runner');
        }
    }

    public static function add($post_id){
        $q = get_option(self::OPTION_KEY, []);
        if(!in_array($post_id, $q)){
            $q[] = $post_id;
            update_option(self::OPTION_KEY,$q);
        }
    }

    public static function run(){
        $q = get_option(self::OPTION_KEY, []);
        if(empty($q)) return;

        $post_id = array_shift($q);
        update_option(self::OPTION_KEY,$q);

        $title = get_the_title($post_id);
        $trans = get_post_meta($post_id, '_ipv_transcript', true);
        if($trans){
            $ai = IPV_Prod_AI_Generator::generate_description($title,$trans);
            if(!is_wp_error($ai)){
                update_post_meta($post_id,'_ipv_ai_description',$ai);
            }
        }
    }
}
IPV_AI_Queue::init();
