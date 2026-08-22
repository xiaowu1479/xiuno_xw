
<?php exit;
$follow = param('follow', 0);
$extra['follow'] = $follow;
if($follow == 1){
    $thread_list_from_default = 0;
    $active = 'follow';
    $pagination = '';
    $threadlist = array();
    if(!empty($user)){
        $follows = fox_tag_follow_count($uid);
        $follow_query = db_find('fox_tag_follow', array('uid'=>$uid));
        if(!empty($follow_query)){
            $tagids = arrlist_values($follow_query, 'tagid');
            $tids_data = db_find('fox_tag_data', array('tagid'=>$tagids), array('tid'=>-1), 1, 99999, '', array('tid'));
            $tids = arrlist_values($tids_data, 'tid');
            $tids = array_unique($tids);
            $tids = array_merge($tids);
            $threadlist = thread_find_by_tids($tids, array($orderby=>-1));
            $threadlist = arrlist_cond_orderby($threadlist, array('fid'=>$fid), array($orderby=>-1), 1, 99999);
            $total_tids = count($threadlist);
            $threadlist = arrlist_cond_orderby($threadlist, array('fid'=>$fid), array($orderby=>-1), $page, $pagesize);
            $pagination = pagination(url("forum-$fid-{page}", array('follow'=>1)), $total_tids, $page, $pagesize);
        }
    }
}?>
