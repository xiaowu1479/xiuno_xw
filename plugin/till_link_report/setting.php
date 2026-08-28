<?php

!defined('DEBUG') and exit('Access Denied.');

$header['title'] = lang('till_link_report').lang('setting');

if ($method == 'GET') {

	$config = till_link_report_config();

	include _include(APP_PATH.'plugin/till_link_report/view/htm/setting.htm');

} else {

	$config = array();

	$config['enable'] = param('enable', 0);
	$config['login_only'] = param('login_only', 1);
	$config['self_report'] = param('self_report', 0);
	$config['cooldown'] = abs(intval(param('cooldown', 300)));
	$config['ip_interval'] = abs(intval(param('ip_interval', 600)));
	$config['max_per_day'] = abs(intval(param('max_per_day', 50)));
	$config['show_count'] = param('show_count', 1);
	$config['notice_type'] = abs(intval(param('notice_type', 156)));

	setting_set('till_link_report', $config);

	message(0, jump(lang('till_link_report_setting_success'), url('plugin-setting-till_link_report')));
}

?>
