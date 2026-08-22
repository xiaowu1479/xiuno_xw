if (isset($light_config['new_threadlist']) && $light_config['new_threadlist'] == 1) { 
$digest = param('digest', 0);

if($digest == 5) {
	$thread_list_from_default = 0;
	$active = 'newpost';
  	if(!$tagids || $tagids=='0___' ||$tagids=='0_0_0_0' ){
		$order = 'tid';
      $fids = array();
		$fids[] = $fid;
      $threads = db_count('thread', array('fid'=>$fid));
      $pagination = pagination(url("forum-$fid-{page}").'?digest=5', $threads, $page, $pagesize);
		 //$threadlist = thread_find_by_fid($fid, $page, $pagesize, $orderby);
      $threadlist = thread_find_by_fids($fids, $page, $pagesize, $order, $threads);
	}else{
      if($runtime['threads'] > 1000000) {
          $count_sql_md5 = md5($count_sql);
          $find_sql_md5 = md5($find_sql);
          $n = cache_get($count_sql_md5);
          if($n === NULL || DEBUG) {
              $arr = db_sql_find_one($count_sql);
              $n = $arr['num'];
              cache_set($count_sql_md5, $n, 30);
          }
          $tids = cache_get($find_sql_md5);
          if($tids === NULL || DEBUG) {
              $tidlist = db_sql_find($find_sql);
              $tids = arrlist_values($tidlist, 'tid');
              cache_set($find_sql_md5, $tids, 30);
          }
      } else {
          $arr = db_sql_find_one($count_sql);
          $n = $arr['num'];
          $tidlist = db_sql_find($find_sql);
          $tids = arrlist_values($tidlist, 'tid');
          unset($arr, $tidlist);
      }

      $pagination = pagination(url("forum-$fid-{page}", array('tagids'=>"{$tagid1}_{$tagid2}_{$tagid3}_{$tagid4}")), $n, $page, $pagesize);
      $threadlist = thread_find_by_tids($tids);
      $toplist = array();
  }
}
 } 
