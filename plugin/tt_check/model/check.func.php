<?php
function check_set_thread_check($tid,$status){
    if($status==-2)
        thread_delete($tid);
    else {
        db_update('thread', array('tid' => $tid), array('OK' => $status));
        db_update('post', array('tid' => $tid, 'isfirst' => '1'), array('OK' => $status));
    }
}
function check_set_post_check($pid,$status){
    if($status==-2)
        post_delete($pid);
    else
        db_update('post',array('pid'=>$pid,'isfirst'=>'0'),array('OK'=>$status));
}
function check_set_user_check($uid,$status){
    if($status==-2) user_delete($uid);
    else db_update('user',array('uid'=>$uid),array('OK'=>$status));
}
function check_reg_or_not(){
    $set = setting_get('tt_check');
    return $set['user_check']=='1';
}
function check_or_not($group,$fid,$action){
    $target_group = $group;
    $user_check = !empty($target_group[$action.'_check']) ? $target_group[$action.'_check'] : '0';
    $forum = db_find_one('forum',array('fid'=>$fid));
    $forum_check = !empty($forum[$action.'_check']) ? $forum[$action.'_check'] : '0';
    return $user_check=='1' && $forum_check=='1';
}
function check_need_check($group,$action){
    return !empty($group[$action.'_check']) && $group[$action.'_check']=='1';
}
function recycle_thread_delete($tid){
    db_update('thread',array('tid'=>$tid),array('OK'=>-2));
    db_update('post',array('tid'=>$tid,'isfirst'=>1),array('OK'=>-2));
}
function recycle_thread_delete_true($tid){
    $r = thread_delete($tid);
    return $r;
}
function recycle_thread_recovery($tid){
    db_update('thread',array('tid'=>$tid),array('OK'=>1));
    db_update('post',array('tid'=>$tid,'isfirst'=>1),array('OK'=>1));
}
function recycle_truncate(){
    $r = db_find('thread',array('OK'=>-2),array('tid'=>-1),1,10000);
    foreach($r as $_r)
        thread_delete($_r['tid']);
}
?>