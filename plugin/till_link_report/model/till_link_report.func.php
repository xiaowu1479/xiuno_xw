<?php

/*
	链接失效反馈 - 模型函数
*/

!defined('DEBUG') AND exit('Access Denied.');

// 读取配置
function till_link_report_config() {
	static $conf = null;
	if ($conf === null) {
		$conf = setting_get('till_link_report');
		if (!is_array($conf)) $conf = array();
		$defaults = array(
			'enable' => 1,
			'login_only' => 1,
			'self_report' => 0,
			'cooldown' => 300,
			'ip_interval' => 600,
			'notice_type' => 156,
			'max_per_day' => 50,
			'show_count' => 1,
		);
		$conf = array_merge($defaults, $conf);
	}
	return $conf;
}

// 删除一条反馈
function till_link_report__delete($id) {
	if (empty($id)) return FALSE;
	return db_delete('link_report', array('id' => $id));
}

// 读取单条反馈
function till_link_report__read($id) {
	return db_find_one('link_report', array('id' => $id));
}

// 新增反馈记录
function till_link_report__create($arr) {
	return db_create('link_report', $arr);
}

// 按条件统计
function till_link_report_count($cond = array()) {
	return db_count('link_report', $cond);
}

// 按条件查找(分页)
function till_link_report_find($cond = array(), $orderby = array(), $page = 1, $pagesize = 20) {
	return db_find('link_report', $cond, $orderby, $page, $pagesize);
}

// 按主题统计反馈次数
function till_link_report_count_by_tid($tid) {
	return db_count('link_report', array('tid' => $tid));
}

// 最近一次反馈时间(用于防刷)
function till_link_report_last_by_uid_tid($uid, $tid) {
	$row = db_find_one('link_report', array('fromuid' => $uid, 'tid' => $tid), array('create_date' => -1));
	return $row ? intval($row['create_date']) : 0;
}

// 同一IP最近反馈时间
function till_link_report_last_by_ip_tid($ip, $tid) {
	$row = db_find_one('link_report', array('create_ip' => $ip, 'tid' => $tid), array('create_date' => -1));
	return $row ? intval($row['create_date']) : 0;
}

// 某用户今日反馈总数
function till_link_report_today_count_by_uid($uid) {
	$today_start = strtotime(date('Y-m-d'));
	return db_count('link_report', array('fromuid' => $uid, 'create_date' => array('>' => $today_start)));
}

?>
