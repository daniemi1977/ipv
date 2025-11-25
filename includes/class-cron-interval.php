<?php
add_filter('cron_schedules', function($s){
    $s['minute']=['interval'=>60,'display'=>'Every Minute'];
    return $s;
});
