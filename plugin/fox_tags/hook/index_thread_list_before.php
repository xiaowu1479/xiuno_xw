
<?php exit;
$order = param('orderby');
empty($order) AND $order = $conf['order_default'];
$extra = array(); // 给插件预留
$order != 'tid' AND $order = 'lastpid';
$follow = param(2, 0);
if($follow == 6){
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
            $threadlist = thread_find_by_tids($tids, array($order=>1));
            $total_tids = count($threadlist);
            $threadlist = arrlist_cond_orderby($threadlist, array(), array($order=>-1), $page, $pagesize);
            $pagination = pagination(url("$route-{page}-6"), $total_tids, $page, $pagesize);
        }
    }
}?>
