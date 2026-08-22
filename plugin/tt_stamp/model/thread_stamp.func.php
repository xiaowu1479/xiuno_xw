<?php

function thread_stamp_delete($tid) {
	$r = db_delete('thread_stamp', array('tid'=>$tid));
	return $r;
}

function thread_stamp_create($tid, $stamp, $uid, $fid) {
	$r = db_create('thread_stamp', array('fid'=>$fid, 'tid'=>$tid, 'uid'=>$uid, 'stamp_type'=>$stamp));
	return $r;
}

function thread_stamp_read($tid) {
	$arr = db_read('thread_stamp', array('tid'=>$tid));
	return $arr;
}

function thread_stamp_update($tid, $arr) {
	$r = db_update('thread_stamp', array('tid'=>$tid), $arr);
	return $r;
}

function thread_stamp_change($tid, $stamp, $uid, $fid) {
	$arr = thread_stamp_read($tid);
	if($stamp == 0) {
		if($arr) {
			thread_stamp_delete($tid, $uid, $fid);
		}
	} else {
		if($arr) {
			thread_stamp_update($tid, array('stamp'=>$stamp));
		} else {
			thread_stamp_create($tid, $stamp, $uid, $fid);
		}
	}
	thread_update($tid, array('stamp'=>$stamp));
}

function thread_stamp_find_by_fid($fid = 0, $page = 1, $pagesize = 20) {
	if($fid == 0) {
		$threadlist = db_find('thread_stamp', array(), array('tid'=>-1), $page, $pagesize, 'tid');
	} else {
		$threadlist = db_find('thread_stamp', array('fid'=>$fid), array('tid'=>-1), $page, $pagesize, 'tid');
	}
	$tids = arrlist_values($threadlist, 'tid');
	$threadlist = thread_find_by_tids($tids);
	return $threadlist;
}

?>