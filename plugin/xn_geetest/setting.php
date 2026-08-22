<?php
!defined('DEBUG') AND exit('Access Denied.');

if($method == 'GET') {
	$kv = (array)kv_get('geetest');
	$input = array();
	$input['geetestid'] = form_text('geetestid', $kv['geetestid'] ?? '');
	$input['geeteskey'] = form_text('geeteskey', $kv['geeteskey'] ?? '');
	$input['geetest_user_login_on'] = form_radio_yes_no('geetest_user_login_on', $kv['geetest_user_login_on']);
	$input['geetest_user_create_on'] = form_radio_yes_no('geetest_user_create_on', $kv['geetest_user_create_on']);
	$input['geetest_mail_on'] = form_radio_yes_no('geetest_mail_on', $kv['geetest_mail_on']);
	$input['geetest_user_findpw_on'] = form_radio_yes_no('geetest_user_findpw_on', $kv['geetest_user_findpw_on']);
	$input['geetest_thread_create_on'] = form_radio_yes_no('geetest_thread_create_on', $kv['geetest_thread_create_on']);
	$input['geetest_post_create_on'] = form_radio_yes_no('geetest_post_create_on', $kv['geetest_post_create_on']);
	$input['geetest_edit_on'] = form_radio_yes_no('geetest_edit_on', $kv['geetest_edit_on']);
	include _include(APP_PATH.'plugin/xn_geetest/setting.htm');
} else {
	$kv = array();
	$kv['geetestid'] = param('geetestid');
	$kv['geeteskey'] = param('geeteskey');
	$kv['geetest_user_login_on'] = param('geetest_user_login_on');
	$kv['geetest_user_create_on'] = param('geetest_user_create_on');
	$kv['geetest_mail_on'] = param('geetest_mail_on');
	$kv['geetest_user_findpw_on'] = param('geetest_user_findpw_on');
	$kv['geetest_thread_create_on'] = param('geetest_thread_create_on');
	$kv['geetest_post_create_on'] = param('geetest_post_create_on');
	$kv['geetest_edit_on'] = param('geetest_edit_on');
	kv_set('geetest', $kv);
	message(0, '修改成功');
}	
?>