<?php exit;
$set_check = setting_get('tt_check');
if($set_check['recycle']=='1'){
    foreach($threadlist as &$thread) {
        $fid = $thread['fid'];
        $tid = $thread['tid'];
        if(forum_access_mod($fid, $gid, 'allowdelete')) {
            if($thread['OK']=='-2'&&$group['see_check']) thread_delete($tid);
            else recycle_thread_delete($tid);
            $arr = array(
                'uid' => $uid,
                'tid' => $thread['tid'],
                'pid' => $thread['firstpid'],
                'subject' => $thread['subject'],
                'comment' => '',
                'create_date' => $time,
                'action' => 'delete',
            );
            modlog_create($arr);
        }
    }
} else {
?>