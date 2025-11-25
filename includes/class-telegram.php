<?php
if (!defined('ABSPATH')) exit;

class IPV_Telegram {

    public static function send($msg){
        $token = get_option('ipv_telegram_bot_token','');
        $chat  = get_option('ipv_telegram_chat_id','');
        if(!$token || !$chat) return;

        $url = "https://api.telegram.org/bot{$token}/sendMessage";
        $args = [
            'body'=>[
                'chat_id'=>$chat,
                'text'=>$msg
            ]
        ];
        wp_remote_post($url,$args);
    }
}
