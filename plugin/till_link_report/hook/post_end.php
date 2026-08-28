<?php
exit;

/*
	链接失效反馈 - post 模块 action 拦截
	路由: post-link_report-create
*/

elseif ($action == 'link_report') {

	try {
		$config = till_link_report_config();

		// 功能开关
		if (empty($config['enable'])) {
			message(0, lang('till_link_report_closed'));
		}

		$header['title'] = lang('till_link_report') . ' - ' . $conf['sitename'];

		// 登录校验
		if (!$uid) {
			message(0, lang('till_link_report_login_tip'));
		}

		if ($method == 'POST') {

			$tid = abs(intval(param('tid')));
			$pid = abs(intval(param('pid')));
			$reason = trim((string) param('reason', ''));

			empty($tid) AND message(0, lang('till_link_report_missing_tid'));

			$thread = thread_read($tid);
			empty($thread) AND message(0, lang('thread_not_exists'));

			// 楼主自己反馈自己帖子
			if (empty($config['self_report']) && intval($thread['uid']) == intval($user['uid'])) {
				message(0, lang('till_link_report_self_tip'));
			}

			// 冷却：同一用户对同一主题
			$cooldown = max(60, intval($config['cooldown']));
			$last = till_link_report_last_by_uid_tid($user['uid'], $tid);
			if ($last && (time() - $last) < $cooldown) {
				message(0, lang('till_link_report_cooldown', array(1 => $cooldown)));
			}

			// IP 间隔
			$ip_interval = max(60, intval($config['ip_interval']));
			$last_ip = till_link_report_last_by_ip_tid($longip, $tid);
			if ($last_ip && (time() - $last_ip) < $ip_interval) {
				message(0, lang('till_link_report_cooldown', array(1 => $ip_interval)));
			}

			// 单用户每日上限
			$max_per_day = max(1, intval($config['max_per_day']));
			$today = till_link_report_today_count_by_uid($user['uid']);
			if ($today >= $max_per_day) {
				message(0, lang('till_link_report_day_limit', array(1 => $max_per_day)));
			}

			$create_ip = $longip;
			$nid = till_link_report__create(array(
				'tid' => $tid,
				'pid' => $pid,
				'uid' => intval($thread['uid']),
				'fromuid' => $user['uid'],
				'reason' => mb_substr($reason, 0, 200, 'UTF-8'),
				'create_date' => $time,
				'create_ip' => $create_ip,
			));

			if ($nid === FALSE) {
				message(0, lang('till_link_report_fail'));
			}

			// 站内通知楼主
			if (function_exists('notice_send') && intval($thread['uid']) != intval($user['uid'])) {
				$thread_url = url('thread-' . $tid);
				$notice_msg = str_replace(
					array('{user_username}', '{thread_subject}', '{thread_url}', '{reason}'),
					array(
						$user['username'],
						htmlspecialchars(strip_tags($thread['subject'])),
						$thread_url,
						htmlspecialchars($reason) !== '' ? htmlspecialchars($reason) : lang('till_link_report_default_reason'),
					),
					lang('till_link_report_notice')
				);
				notice_send($user['uid'], intval($thread['uid']), $notice_msg, intval($config['notice_type']));
			}

			$count = till_link_report_count_by_tid($tid);
			message(1, array(
				'count' => intval($count),
				'msg' => lang('till_link_report_success'),
			));
		}

		message(0, lang('till_link_report_method_error'));
	} catch (Throwable $e) {
		message(0, lang('till_link_report_fail'));
	}
}

?>
