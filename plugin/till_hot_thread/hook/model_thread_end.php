function threadlist_read_hot($pagesize = 20) {
$hotlist = cache_get("till_hot_threads");
if (!$hotlist) {
$times_tamp_a = strtotime("-1 week");
$hotlist = db_find('thread', array('create_date' => array('>' => $times_tamp_a)), array('views' => -1), 1, $pagesize, 'tid');
cache_set("till_hot_threads", $hotlist, 3600);
return $hotlist;
}
// 过滤已删除的帖子
foreach($hotlist as $tid=>$thread) {
$t = thread__read($tid);
if(empty($t)) unset($hotlist[$tid]);
}
return $hotlist;
}

function till_hot_thread_clear_cache() {
cache_set("till_hot_threads", null, 0);
}
