<?php exit;
$set_check = setting_get('tt_check');
if($set_check['recycle']=='1'){
    if($isfirst) {
        if($thread['OK']=='-2' && $group['see_check']) thread_delete($tid);
        else recycle_thread_delete($tid);
    } else {
        post_delete($pid);
        //post_list_cache_delete($tid);
    }
} else {
?>