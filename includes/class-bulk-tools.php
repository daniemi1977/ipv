<?php
if (!defined('ABSPATH')) exit;

class IPV_Bulk_Tools {
    public static function init() {
        add_action('admin_menu',[__CLASS__,'menu']);
        add_action('admin_enqueue_scripts',[__CLASS__,'assets']);
        add_action('wp_ajax_ipv_bulk_action',[__CLASS__,'ajax']);
    }

    public static function assets(){
        wp_enqueue_script('ipv-bulk-tools', plugins_url('../assets/js/bulk-tools.js', __FILE__), ['jquery'], null, true);
    }

    public static function menu() {
        add_submenu_page(
            'ipv-production-system',
            'Bulk Tools',
            'Bulk Tools',
            'manage_options',
            'ipv-bulk-tools',
            [__CLASS__,'render']
        );
    }

    public static function render() {
        $videos = get_posts(['post_type'=>'ipv_video','numberposts'=>-1]);
        echo '<div class="wrap"><h1>IPV Bulk Tools</h1>';
        echo '<p>Seleziona i video da processare:</p>';
        echo '<table class="widefat"><tbody>';
        foreach($videos as $v){
            echo '<tr><td><input type="checkbox" class="ipv-bulk-select" value="'.$v->ID.'"></td><td>'.$v->post_title.'</td></tr>';
        }
        echo '</tbody></table>';

        echo '<div style="margin-top:20px;">';
        echo '<button class="button button-primary" onclick="ipvBulkRun('thumbs')">Thumbnails</button> ';
        echo '<button class="button" onclick="ipvBulkRun('transcript')">Trascrizioni</button> ';
        echo '<button class="button" onclick="ipvBulkRun('ai')">AI Description</button> ';
        echo '<button class="button" onclick="ipvBulkRun('repair')">Repair Meta</button> ';
        echo '<button class="button" onclick="ipvBulkRun('import')">Import Feed</button>';
        echo '</div>';

        echo '<div style="margin-top:20px;">
                <div id="ipv-progress" style="width:100%;background:#ccc;height:20px;">
                    <div id="ipv-progress-bar" style="height:20px;width:0;background:#4caf50;"></div>
                </div>
              </div>';

        echo '<pre id="ipv-bulk-log" style="margin-top:20px;background:#f5f5f5;padding:10px;height:300px;overflow:auto;"></pre>';
        echo '</div>';
    }

    public static function ajax() {
        $type = sanitize_text_field($_POST['bt']);
        $ids = isset($_POST['ids']) ? array_map('intval', $_POST['ids']) : [];
        if(empty($ids)){
            $videos = get_posts(['post_type'=>'ipv_video','numberposts'=>-1]);
            $ids = wp_list_pluck($videos,'ID');
        }

        $total = count($ids);
        $done = 0;
        $log="Start $type\n";

        foreach($ids as $id){
            $done++;
            $title=get_the_title($id);
            $log.="[$done/$total] $title\n";

            $video_id=get_post_meta($id,'_ipv_video_id',true);

            if($type==='thumbs'){
                $url="https://img.youtube.com/vi/{$video_id}/maxresdefault.jpg";
                $tmp=download_url($url);
                if(!is_wp_error($tmp)){
                    $file=['name'=>"{$video_id}.jpg",'tmp_name'=>$tmp];
                    $mid=media_handle_sideload($file,$id);
                    if(!is_wp_error($mid)){
                        set_post_thumbnail($id,$mid);
                        $log.="Thumbnail OK\n";
                    }
                }
            }

            if($type==='transcript'){
                if($video_id){
                    $t=IPV_SupaData::get_transcript($video_id);
                    if(!is_wp_error($t)){
                        update_post_meta($id,'_ipv_transcript',$t);
                        $log.="Transcript OK\n";
                    }
                }
            }

            if($type==='ai') // enqueue
                IPV_AI_Queue::add($id);
            if($type==='ai'){
                $trans=get_post_meta($id,'_ipv_transcript',true);
                if($trans){
                    $ai=IPV_Prod_AI_Generator::generate_description(get_the_title($id),$trans);
                    if(!is_wp_error($ai)){
                        update_post_meta($id,'_ipv_ai_description',$ai);
                        $log.="AI OK\n";
                    }
                }
            }

            if($type==='repair'){
                if(!$video_id){
                    delete_post_meta($id,'_ipv_video_id');
                    $log.="Repaired video meta\n";
                }
            }

            if($type==='import'){
                IPV_Prod_RSS_Importer::import_latest();
                $log.="Import run\n";
            }
        }

        echo nl2br($log."Done.");
        wp_die();
    }
}
IPV_Bulk_Tools::init();
