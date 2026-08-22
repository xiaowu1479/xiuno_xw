<?php

!defined('DEBUG') AND exit('Access Denied.');

user_login_check();

// 管理后台-所有消息 发送通知
$action = param(1);

if($action == 'create') {

	if($method == 'GET') {
		
		$input = array();
		$input['recvuid'] = form_text('recvuid', '');
		$input['message'] = form_textarea('message', '', '100%', 100);
		
		$header['title'] = lang('notice_admin_send_notice');
		$header['mobile_title'] =lang('notice_admin_send_notice');
				
		include _include(APP_PATH."plugin/huux_notice/view/htm/admin_notice_create.htm");
		
	} else {
		
		$message = param('message', '', FALSE);
		$recvuid = param('recvuid', 0);
		$send_all = param('send_all', 0);

		// 检查内容是否为空
		empty($message) AND message('message', lang('notice_admin_send_notice_message_empty'));
		
		if($send_all) {
			// 发送给所有用户
			$userlist = user_find(array(), array(), 1, 10000, 'uid');
			if(empty($userlist)) message(-1, '没有找到用户');
			$count = 0;
			foreach($userlist as $_user) {
				if($_user['uid'] == $uid) continue; // 不给自己发
				notice_send($uid, $_user['uid'], $message, 3);
				$count++;
			}
			message(0, '已发送给 ' . $count . ' 个用户');
		} else {
			empty($recvuid) AND message('recvuid', lang('notice_admin_send_notice_recvuid_empty'));
			
			// 检查接收人是否存在
			$recvuid_check = user__read($recvuid);
			$recvuid_check === FALSE AND message('recvuid', lang('notice_admin_send_notice_user_empty'));
			
			$nid = notice_send($uid, $recvuid, $message, 3); // 3:系统通知
			$nid === FALSE AND message(-1, lang('notice_admin_send_notice_failed'));
			
			message(0, lang('notice_admin_send_notice_sucessfully'));
		}
	}


}elseif($action == 'delete') {

	// 支持单条删除和批量删除
	$nids = param('nids', array());
	$nid = param('nid', 0);
	
	// 如果传了 nids 数组（批量），则转成数组；否则处理单条 nid
	if(!empty($nids)) {
		// nids 可能是逗号分隔的字符串或数组
		if(!is_array($nids)) $nids = explode(',', $nids);
		foreach($nids as $_nid) {
			notice_delete(intval($_nid));
		}
		message(0, lang('notice_delete_notice_sucessfully'));
	} elseif($nid) {
		$r = notice_delete($nid);
		$r === FALSE AND message(-1, lang('notice_delete_notice_failed'));
		message(0, lang('notice_delete_notice_sucessfully'));
	} else {
		message(-1, lang('notice_delete_notice_failed'));
	}

}elseif($action == 'list'){

	$page = param(2, 1);
	$pagesize = 20;
	$active = 'default';
	$notices = notice_count(); //直接获取最新的
	$cond = array();
	$orderby = 'nid';

	$notice_menu = include _include(APP_PATH.'plugin/huux_notice/conf/notice_menu.conf.php');
	$noticelist = notice_find($cond, $page, $pagesize);
	$pagination = pagination(url("notice-list-{page}"), $notices, $page, $pagesize);

	$header['title'] = lang('notice_admin_notice_list');
	$header['mobile_title'] =lang('notice_admin_notice_list');

	include _include(APP_PATH."plugin/huux_notice/view/htm/admin_notice_list.htm");



}elseif($action == 'read'){
	//ajax返回message 暂时不需要
}

?>