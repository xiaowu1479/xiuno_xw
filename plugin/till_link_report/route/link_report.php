<?php

!defined('DEBUG') AND exit('Access Denied.');

user_login_check();

$action = param(1);

if ($action == 'list') {

	$page = max(1, intval(param(2, 1)));
	$pagesize = 20;

	$count = till_link_report_count();
	$list = till_link_report_find(array(), array('create_date' => -1), $page, $pagesize);

	$pagination = pagination(url("link_report-list-{page}"), $count, $page, $pagesize);

	$header['title'] = lang('till_link_report') . lang('setting');
	$header['mobile_title'] = lang('till_link_report') . lang('setting');

	include _include(APP_PATH.'plugin/till_link_report/view/htm/admin_list.htm');

} elseif ($action == 'delete') {

	$id = intval(param('id', 0));
	if (!$id) {
		message(-1, lang('till_link_report_missing_tid'));
	}

	// 支持批量删除
	$ids = param('ids', array());
	if (!empty($ids)) {
		if (!is_array($ids)) $ids = explode(',', $ids);
		foreach ($ids as $_id) {
			till_link_report__delete(intval($_id));
		}
		message(0, lang('till_link_report_delete_success'));
	} else {
		$r = till_link_report__delete($id);
		$r === FALSE AND message(-1, lang('till_link_report_delete_fail'));
		message(0, lang('till_link_report_delete_success'));
	}

}

message(0, lang('till_link_report_method_error'));

?>
