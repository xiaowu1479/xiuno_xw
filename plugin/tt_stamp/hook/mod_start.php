<?php exit;

if($action == 'stamp') {
	
	if($method == 'GET') {
		include _include(APP_PATH.'plugin/tt_stamp/view/htm/mod_stamp.htm');
	} else {
		
		$stamp = param('stamp', 0);
		
		$tidarr = param('tidarr', array(0));
		empty($tidarr) AND message(-1, lang('please_choose_thread'));
		$threadlist = thread_find_by_tids($tidarr);
		
		foreach($threadlist as &$thread) {
			$fid = $thread['fid'];
			$tid = $thread['tid'];
			if(forum_access_mod($fid, $gid, 'allowtop')) {
				thread_stamp_change($tid, $stamp, $thread['uid'], $thread['fid']);
				$arr = array(
					'uid' => $uid,
					'tid' => $thread['tid'],
					'pid' => $thread['firstpid'],
					'subject' => $thread['subject'],
					'comment' => '',
					'create_date' => $time,
					'action' => 'set_stamp',
				);
				modlog_create($arr);
			}
		}
		unset($thread);

		message(0, lang('set_completely'));
	
	}
}

?>