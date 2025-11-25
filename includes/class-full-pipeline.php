<?php
if (!defined('ABSPATH')) exit;

class IPV_Full_Pipeline {

    public static function run($post_id){
        $video_id = get_post_meta($post_id,'_ipv_video_id',true);
        if(!$video_id) return;

        // Thumbnail
        $url="https://img.youtube.com/vi/{$video_id}/maxresdefault.jpg";
        $tmp=download_url($url);
        if(!is_wp_error($tmp)){
            $file=['name'=>"{$video_id}.jpg",'tmp_name'=>$tmp];
            $mid=media_handle_sideload($file,$post_id);
            if(!is_wp_error($mid)) set_post_thumbnail($post_id,$mid);
        }

        // Transcript
        $trans = IPV_SupaData::get_transcript($video_id);
        if(!is_wp_error($trans)) update_post_meta($post_id,'_ipv_transcript',$trans);

        // AI Description
        if($trans){
            $ai = IPV_Prod_AI_Generator::generate_description(get_the_title($post_id),$trans);
            if(!is_wp_error($ai)) update_post_meta($post_id,'_ipv_ai_description',$ai);
        }

        // Notify
        IPV_Telegram::send("Pipeline completata per video: ".get_the_title($post_id));
    }
}
