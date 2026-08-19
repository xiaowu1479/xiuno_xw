if (isset($light_config['new_threadlist']) && $light_config['new_threadlist'] == 1) {
$digest = param(2,0);
if($digest == 5) {
	$thread_list_from_default = 0;
	$active = 'newpost';
	$order = 'tid';
	$fids = arrlist_values($forumlist_show, 'fid');
	$threads = arrlist_sum($forumlist_show, 'threads');
	$pagination = pagination(url("index-{page}-5"), $threads, $page, $pagesize);
	$threadlist = thread_find_by_fids($fids, $page, $pagesize, $order, $threads);
}
 }
 
