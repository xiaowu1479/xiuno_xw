<?php 
function fox_prison_create($uid, $aid, $date, $type, $message){
    global $time, $longip;
    if((!empty($message)) && (!empty($date)) && (!empty($type))){
        $query = db_count('fox_prison', array('uid'=>$uid, 'status'=>1));
        !empty($query) AND message(0, '禁闭成功');
        if($type == 1){
            $endtime = strtotime("+{$date} day", $time);
        }
        elseif($type == 2){
            $endtime = strtotime("+{$date} month", $time);
        }
        elseif($type == 3){
            $endtime = strtotime("+15 year", $time);
        }
        if(!empty($endtime)){
            db_create('fox_prison', array('uid'=>$uid, 'aid'=>$aid, 'time'=>$time, 'endtime'=>$endtime, 'uip'=>$longip, 'message'=>$message));
            $r = user_update($uid, array('gid'=>7));
            $r === FALSE AND message(-1, '禁闭失败');
            message(0, '禁闭成功');
        }
        message(-1, 'Error.');
    }
    message(-1, 'Error.');
}
function fox_prison_update($uid, $gid = 101){
    if(!empty($uid)){
        global $time;
        $r = user_update($uid, array('gid'=>$gid));
        user_update_group($uid);
        db_update('fox_prison', array('uid'=>$uid), array('endtime'=>$time, 'status'=>0));
        return $r;
    }
    return FALSE;
}
function fox_prison_auto_update($uid){
    user_update($uid, array('gid'=>101));
    $r = db_update('fox_prison', array('uid'=>$uid), array('status'=>0));
    return $r;
}
function fox_prison_read($uid, $col){
    if(!empty($uid)){
        $r = db_find_one('fox_prison', array('uid'=>$uid, 'status'=>1), array('id'=>-1));
        if(empty($r)) return 0;
        return $r[$col];
    }
}
function fox_prison_delete($uid){
    if(!empty($uid)){
        db_delete('fox_prison', array('uid'=>$uid));        
        return $r;
    }
}
function fox_prison_user_format(&$user){
    if($user){
        global $time;
        $user['prison_status'] = fox_prison_read($user['uid'], 'status');
        if($user['prison_status']){
            $prison_end_time = fox_prison_read($user['uid'], 'endtime');
            if($prison_end_time){
                ($prison_end_time < $time) AND fox_prison_auto_update($user['uid']) AND user_update_group($user['uid']);
            }
        }
        $user['prison_message'] = fox_prison_read($user['uid'], 'message');
        $user['prison_start_time'] = fox_prison_read($user['uid'], 'time');
        !empty($user['prison_start_time']) AND $user['prison_start_time'] = date('Y年m月d日', $user['prison_start_time']);
        $user['prison_end_time'] = fox_prison_read($user['uid'], 'endtime');
        !empty($user['prison_end_time']) AND $user['prison_end_time'] = date('Y年m月d日', $user['prison_end_time']);
    }
}
?>