<?php exit;
if($action == 'cPay'){
	$tid = param(2);
	
	// 严格验证参数
	if(!is_numeric($tid) || $tid <= 0) {
		message(-1, '主题ID无效');
		return;
	}
	
	$tid = intval($tid);
	
	if(empty($uid) || !is_numeric($uid)) {
		message(-2, lang('login_first'));
		return;
	}
	
	$content_pay = db_find_one('paylist', array('tid' => $tid, 'uid' => $uid, 'type' => 1));
	if(!$content_pay){
		if(!$user) message(-2, lang('login_first'));
		$thread = thread_read($tid);
		empty($thread) AND message(-1, lang('thread_not_exists:'));
		
		// 验证付费设置
		$content_buy = isset($thread['content_buy']) ? intval($thread['content_buy']) : 0;
		$content_buy_type = isset($thread['content_buy_type']) ? $thread['content_buy_type'] : '1';
		
		if($content_buy <= 0) {
			message(0, '这是免费内容');
			return;
		}
		
		$operation_credit_area;
		switch($content_buy_type) {
            case '1':$operation_credit_area='credits';break;
            case '2':$operation_credit_area='golds';break;
            case '3':$operation_credit_area='rmbs';break;
            default:$operation_credit_area='rmbs';break;
        }
        
        // 验证用户余额
        $user_balance = isset($user[$operation_credit_area]) ? intval($user[$operation_credit_area]) : 0;
		if($user_balance < $content_buy) {
			message(-3, str_replace(lang('credits'),'<span style="color:dodgerblue;font-weight:bold;">'.lang('credits'.$content_buy_type).'</span>',lang('credit_no_enough')));
			return;
		}
		
		// 防重复购买
		$existing_pay = db_count('paylist', array('tid' => $tid, 'uid' => $uid, 'type' => 1));
		if($existing_pay > 0) {
			message(0, lang('pay_success'));
			return;
		}
		
		// 执行支付
		db_insert('paylist',array('tid' => $tid, 'uid' => $uid, 'credit_type'=>intval($content_buy_type),'num' => $content_buy, 'type' => 1, 'paytime' => time()));
		$now_balance = $user_balance - $content_buy;
		db_update('user', array('uid' => $uid), array($operation_credit_area => $now_balance));
		
		// 给作者加积分
		$author = db_find_one('user', array('uid' => intval($thread['uid'])));
		if(!empty($author)) {
			$author_balance = isset($author[$operation_credit_area]) ? intval($author[$operation_credit_area]) : 0;
			$new_author_balance = $author_balance + $content_buy;
			db_update('user', array('uid' => intval($thread['uid'])), array($operation_credit_area => $new_author_balance));
		}
		
        $uid AND $user['gid']>=100 AND user_update_group($uid);
        $uid AND db_insert('user_pay',array('uid'=>$uid,'status'=>1,'num'=>$content_buy,'type'=>'4','credit_type'=>$content_buy_type,'code'=>$thread['tid'].','.$thread['subject'],'time'=>time()));
        
        // 购买付费主题后通知卖家（帖子作者）
        $setting_credits = setting_get("tt_credits");
        if(isset($setting_credits['buy_push']) && $setting_credits['buy_push']=='1' && $uid && $thread['uid'] != $uid){
            if(function_exists('notice_send')) {
                $thread_title = htmlspecialchars($thread['subject']);
                $user_info = user_read($uid);
                $username = htmlspecialchars($user_info['username']);
                $message = "{$username}购买了您的主题【{$thread_title}】！";
                notice_send($uid, $thread['uid'], $message, 1001);
            }
        }
        
		message(0, lang('pay_success'));
	}else
		message(0, lang('pay_success'));
}elseif($action == 'sPay') {
    $tid = param(2);
    
    // 验证主题ID
    if(!is_numeric($tid) || $tid <= 0) {
        message(-1, '主题ID无效');
        return;
    }
    
    $tid = intval($tid);
    include _include(APP_PATH . 'plugin/tt_credits/view/htm/tt_buy_list.htm');
    return;
}
?>