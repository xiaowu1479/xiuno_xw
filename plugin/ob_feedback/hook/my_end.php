//<?php

	if ($action === 'feedback') {
		$ob_feedback_setting = setting_get('ob_feedback_setting');
		// 我的反馈
		$page = param(2, 0);
		if (!isset($pagesize)) {
			$pagesize = 50;
		}
		$subaction = param('subaction', '');
		
		if ($method == 'GET') {
			$feedback_count = db_find('ob_feedback', ['from_uid' => $uid, 'create_date' => ['>' => strtotime('-3 months')]]);
			$feedback_list = db_find('ob_feedback', ['from_uid' => $uid, 'create_date' => ['>' => strtotime('-3 months')]], ['create_date' => 0], $page, $pagesize);
			$pagination = pagination(url("my-feedback"), min(1, $feedback_count), $page, $pagesize);
			include _include(APP_PATH . 'plugin/ob_feedback/htm/my_feedback.htm');
		} else {
			message(1, 'Bad Request' . __LINE__);
		}
	} elseif ($action === 'feedback_unsolved') {
		$ob_feedback_setting = setting_get('ob_feedback_setting');
		if(!function_exists("tt_credits_rtn_name")) {
			function tt_credits_rtn_name($credits1='0',$credits2='0',$credits3='0'){
   $rtn='';
   if($credits1!='0'&&$credits1!='') $rtn.=','.lang('credits1').':'.$credits1;
   if($credits2!='0'&&$credits2!='') $rtn.=','.lang('credits2').':'.$credits2;
   if($credits3!='0'&&$credits3!='') $rtn.=','.lang('credits3').':'.$credits3/100.0;
   return $rtn;
}
		}
		if ($gid === 1 || $gid === 2) {
			// 管理未解决反馈
			$page = param(2, 0);
			if (!isset($pagesize)) {
				$pagesize = 100;
			}
			if ($method == 'GET') {
				$ssaction = param('ssaction', '');
				switch ($ssaction) {
					case 'solve_and_feedback':
						$fb_id = param('fb_id', 0);
						include _include(APP_PATH . 'plugin/ob_feedback/htm/a_modal__solve_and_feedback.htm');
						break;
					case 'no_solve_and_feedback':
						$fb_id = param('fb_id', 0);
						include _include(APP_PATH . 'plugin/ob_feedback/htm/a_modal__no_solve_and_feedback.htm');
						break;
					case 'no_solve':
						$fb_id = param('fb_id', 0);
						include _include(APP_PATH . 'plugin/ob_feedback/htm/a_modal__no_solve.htm');
						break;
					default:
					$feedback_count = db_find('ob_feedback',  ['status' => 0]);
					$feedback_list = db_find('ob_feedback', ['status' => 0], ['create_date' => 0], $page, $pagesize);
					$pagination = pagination(url("my-feedback_unsolved"), min(1, $feedback_count), $page, $pagesize);
						include _include(APP_PATH . 'plugin/ob_feedback/htm/my_feedback_unsolved.htm');
						break;
				}
			} else {
				$ssaction = param('ssaction', '');
				switch ($ssaction) {
					case 'solve_and_feedback':
						// 获取数据
						$fb_id = param('fb_id', 0);
						$feedback_item = db_read('ob_feedback', ['fb_id' => $fb_id]);
						$notice_text = param('notice_text', '');
						$credits1 = param('credits1', 0);
						$credits2 = param('credits2', 0);
						$credits3 = param('credits3', 0);
						// 实际动作
						if (boolval($feedback_item['isfirst'])) {
							thread_delete($feedback_item['tid']);
						} else {
							post_delete($feedback_item['pid']);
						}
						if (
							($user['credits'] - $credits1) > 0
							|| ($user['golds'] - $credits2) > 0
							|| ($user['rmbs'] - $credits3) > 0
						) {
							$update_to_arr = array();
							$update_from_arr = array();
							if ($credits1 != 0) {
								$update_to_arr['credits+'] = $credits1;
								$update_from_arr['credits-'] = $credits1;
							}
							if ($credits2 != 0) {
								$update_to_arr['golds+'] = $credits2;
								$update_from_arr['golds-'] = $credits2;
							}
							if ($credits3 != 0) {
								$update_to_arr['rmbs+'] = $credits3;
								$update_from_arr['rmbs-'] = $credits3;
							}
							db_update('user', array('uid' => $feedback_item['from_uid']), $update_to_arr);
							db_update('user', array('uid' => $uid), $update_from_arr);
							db_insert('user_pay', array('uid' => $feedback_item['from_uid'], 'status' => 1, 'num' => '0', 'type' => 15, 'credit_type' => '1', 'time' => time(), 'code' => $fb_id . tt_credits_rtn_name($credits1, $credits2, $credits3)));
							db_insert('user_pay', array('uid' => $uid, 'status' => 1, 'num' => '0', 'type' => 14, 'credit_type' => '1', 'time' => time(), 'code' => $fb_id . tt_credits_rtn_name($credits1, $credits2, $credits3)));
						}
						if (function_exists('notice_send')) {
							if (!empty($notice_text)) {
								notice_send($uid, $feedback_item['from_uid'], $notice_text, 3);
							}
						}
						// 更新数据库
						db_update('ob_feedback', ['fb_id' => $fb_id], ['status' => 1, 'solved_by_uid' => $uid, 'solved_date' => time()]);
						// 返回结果
						message(0, '处理完成!');
						break;
					case 'no_solve_and_feedback':
						// 获取数据
						$fb_id = param('fb_id', 0);
						$feedback_item = db_read('ob_feedback', ['fb_id' => $fb_id]);
						$notice_text = param('notice_text', '');
						$credits1 = param('credits1', 0);
						$credits2 = param('credits2', 0);
						$credits3 = param('credits3', 0);
						$callback_message = '';
						if (
							($user['credits'] - $credits1) > 0
							|| ($user['golds'] - $credits2) > 0
							|| ($user['rmbs'] - $credits3) > 0
						) {
							$update_to_arr = array();
							$update_from_arr = array();
							if ($credits1 != 0) {
								$update_to_arr['credits+'] = $credits1;
								$update_from_arr['credits-'] = $credits1;
							}
							if ($credits2 != 0) {
								$update_to_arr['golds+'] = $credits2;
								$update_from_arr['golds-'] = $credits2;
							}
							if ($credits3 != 0) {
								$update_to_arr['rmbs+'] = $credits3;
								$update_from_arr['rmbs-'] = $credits3;
							}
							db_update('user', array('uid' => $feedback_item['from_uid']), $update_to_arr);
							db_update('user', array('uid' => $uid), $update_from_arr);
							db_insert('user_pay', array('uid' => $feedback_item['from_uid'], 'status' => 1, 'num' => '0', 'type' => 15, 'credit_type' => '1', 'time' => time(), 'code' => $fb_id . tt_credits_rtn_name($credits1, $credits2, $credits3)));
							db_insert('user_pay', array('uid' => $uid, 'status' => 1, 'num' => '0', 'type' => 14, 'credit_type' => '1', 'time' => time(), 'code' => $fb_id . tt_credits_rtn_name($credits1, $credits2, $credits3)));
							$callback_message .= '已赠与积分' . tt_credits_rtn_name($credits1, $credits2, $credits3) . '；';
						} else {
							$callback_message .= '未赠与积分' . tt_credits_rtn_name($credits1, $credits2, $credits3) . '；';
						}

						if (function_exists('notice_send')) {
							if (!empty($notice_text)) {
								$r = notice_send($uid, $feedback_item['from_uid'], $notice_text, 3);
								$callback_message .= '发信成功；';
							}
						} else {
							$callback_message .= '发信失败；';
						}
						// 更新数据库
						db_update('ob_feedback', ['fb_id' => $fb_id], ['status' => -1, 'solved_by_uid' => $uid, 'solved_date' => time()]);
						// 返回结果
						message(0, '处理完成!' . $callback_message);
						break;
					case 'solve':
						// 获取数据
						$fb_id = param('fb_id', 0);
						$feedback_item = db_read('ob_feedback', ['fb_id' => $fb_id]);
						// 实际动作
						if (boolval($feedback_item['isfirst'])) {
							thread_delete($feedback_item['tid']);
						} else {
							post_delete($feedback_item['pid']);
						}
						// 更新数据库
						db_update('ob_feedback', ['fb_id' => $fb_id], ['status' => 1, 'solved_by_uid' => $uid, 'solved_date' => time()]);
						// 返回结果
						message(0, '处理完成!');
						break;
					case 'no_solve':
						// 获取数据
						$fb_id = param('fb_id', 0);
						$notice_text = param('notice_text', '');
						// 实际动作
						// 更新数据库
						db_update('ob_feedback', ['fb_id' => $fb_id], ['status' => -1, 'solved_by_uid' => $uid, 'solved_date' => time()]);
						// 返回结果
						message(0, '处理完成!');
						break;
					default:
						// 实际动作
						message(1, 'Bad Request' . __LINE__);
						break;
				}
			}
		} else {
			message(1, '您无权访问本页面');
			die;
		}
	} elseif ($action === 'feedback_delete') {
		$fb_id = param('fb_id', 0);
		if($fb_id > 0) {
			$fb = db_read('ob_feedback', array('fb_id'=>$fb_id));
			if(!empty($fb) && $fb['from_uid'] == $uid) {
				db_delete('ob_feedback', array('fb_id'=>$fb_id));
				message(0, '已删除');
			} elseif(!empty($fb) && ($gid == 1 || $gid == 2)) {
				db_delete('ob_feedback', array('fb_id'=>$fb_id));
				message(0, '已删除');
			} else {
				message(-1, '无权删除此反馈');
			}
		}
		message(-1, '参数错误');
		
	} elseif ($action === 'feedback_solved') {
		if(!function_exists("tt_credits_rtn_name")) {
			function tt_credits_rtn_name($credits1='0',$credits2='0',$credits3='0'){
   $rtn='';
   if($credits1!='0'&&$credits1!='') $rtn.=','.lang('credits1').':'.$credits1;
   if($credits2!='0'&&$credits2!='') $rtn.=','.lang('credits2').':'.$credits2;
   if($credits3!='0'&&$credits3!='') $rtn.=','.lang('credits3').':'.$credits3/100.0;
   return $rtn;
}
		}
		$ob_feedback_setting = setting_get('ob_feedback_setting');
		// 我的反馈
		if ($gid === 1 || $gid === 2) {

			$page = param(2, 0);
			if (!isset($pagesize)) {
				$pagesize = 50;
			}
			$subaction = param('subaction', '');
			if ($method == 'GET') {
				$feedback_count = db_find('ob_feedback',  ['status' => [1, -1]]);
				$feedback_list = db_find('ob_feedback', ['status' => [1, -1]], ['create_date' => 0], $page, $pagesize);
				$pagination = pagination(url("my-feedback_solved"), min(1, $feedback_count), $page, $pagesize);
				include _include(APP_PATH . 'plugin/ob_feedback/htm/my_feedback_solved.htm');
			} else {
				message(1, 'Bad Request' . __LINE__);
			}
		} else {
			message(1, '您无权访问本页面');
			die;
		}
	}
