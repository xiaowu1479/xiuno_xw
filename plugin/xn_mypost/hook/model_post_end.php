
// 此处有缓存，是否有必要？
function post_find_by_uid($uid, $page = 1, $pagesize = 50) {
	global $conf, $db;
	
	// hook model_post_find_by_uid_start.php
	
	// 只查回帖（排除 isfirst=1 的主题帖），按 pid 倒序
	$tablepre = $db->tablepre;
	$start = ($page - 1) * $pagesize;
	$sql = "SELECT pid FROM {$tablepre}post WHERE uid='$uid' AND isfirst=0 ORDER BY pid DESC LIMIT $start, $pagesize";
	$arrlist = db_sql_find($sql);
	$pids = arrlist_values($arrlist, 'pid');
	if(empty($pids)) return array();
	$postlist = post_find_by_pids($pids);
	$postlist = arrlist_multisort($postlist, 'pid', FALSE);
	
	foreach($postlist as $k=>&$post) {
		user_post_message_format($post['message_fmt']);
		$post['filelist'] = array();
		$post['floor'] = 0; // 默认
		$thread = thread_read_cache($post['tid']);
		$post['subject'] = $thread['subject'];
	}
	
	// hook model_post_find_by_uid_end.php
	return $postlist;
}