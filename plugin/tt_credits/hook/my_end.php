elseif($action == 'credits') {
    if($method == 'GET')
        include _include(APP_PATH.'plugin/tt_credits/view/htm/my_credits.htm');
}elseif($action == 'purchased') {
    if($method == 'GET'){
        $pagesize = 20;
        $page = param(2, 1);
        $cond = array('uid'=>$uid);
        $threadlist = credits_thread_purchased_find_by_uid($uid, $page, $pagesize);
        $pagination = pagination(url("my-purchased-{page}"), credits_purchased_count($cond), $page, $pagesize);
        include _include(APP_PATH.'plugin/tt_credits/view/htm/my_purchased.htm');
    }
}elseif($action == 'trade') {
    if($method == 'GET')
        include _include(APP_PATH.'plugin/tt_credits/view/htm/my_trade.htm');
    elseif($method=='POST'){
        $op = param('op');
        if($op=='n'){
            $set=setting_get('tt_credits');
            $e_rmb_input = param('e_rmb'); // 用户输入的RMB金额
            
            // 严格验证输入
            if(empty($uid) || !is_numeric($uid)) {message(-1, "用户验证失败!");die();}
            if(!is_numeric($e_rmb_input) || $e_rmb_input <= 0 || $e_rmb_input > 999999) {message(-1, "兑换金额必须为1-999999之间的正数!");die();}
            
            $e_rmb_input = abs(floatval($e_rmb_input)); // 用户输入的RMB金额
            
            $my_rmbs = intval($user['rmbs']);
            $my_golds = intval($user['golds']);
            $min = isset($set['min']) ? intval($set['min']) : 1;
            
            // RMB兑换金币的比例：1 RMB(分) 可以兑换多少金币
            $rmb_to_gold_rate = isset($set['exchange_n']) ? floatval($set['exchange_n']) : 1;
            
            // 计算需要消耗的RMB(分)和能获得的金币
            $rmb_cost = $e_rmb_input; // 消耗的RMB(分)
            $gold_get = $e_rmb_input * $rmb_to_gold_rate; // 获得的金币
            
            if($rmb_cost < $min) {message(-1, '最低兑换金额：'.($min/1).'分，您兑换的金额不足。');die();}
            if($my_rmbs < $rmb_cost) {message(-1, lang('credit_no_enough'));die();}
            
            // 防重复提交检查
            $recent_query = db_find_one('user_pay',array('uid'=>$uid,'type'=>'6'),array('time'=>-1));
            $now_time = time();
            if(!empty($recent_query) && ($now_time-$recent_query['time'])<=5) {message(-1, "每5秒只能兑换一次，您兑换过于频繁！");die();}
            
            $new_golds = $my_golds + $gold_get;
            $new_rmbs = $my_rmbs - $rmb_cost;
            
            // 确保不出现负数
            if($new_rmbs < 0 || $new_golds < 0) {message(-1, "兑换失败，数据异常!");die();}
            
            db_insert('user_pay',array('uid'=>$uid,'status'=>1,'num'=>abs($rmb_cost),'type'=>'6','credit_type'=>'3','code'=>'RMB兑换金币 比例1:'.floatval($rmb_to_gold_rate),'time'=>time()));
            user_update($user['uid'],array('rmbs'=>abs($new_rmbs),'golds'=>abs($new_golds)));
            user_update_group($user['uid']);
            message(0, lang('update_successfully'));
        }elseif($op=='c'){
            $set=setting_get('tt_credits');
            $e_golds_input=param('e_golds_c'); // 用户输入的金币数量
            
            // 严格验证输入
            if(empty($uid) || !is_numeric($uid)) {message(-1, "用户验证失败!");die();}
            if(!is_numeric($e_golds_input) || $e_golds_input <= 0 || $e_golds_input > 999999) {message(-1, "兑换金额必须为1-999999之间的正数!");die();}
            
            $e_golds_input = abs(floatval($e_golds_input)); // 用户输入的金币数量
            
            $my_golds = intval($user['golds']);
            $my_rmbs = intval($user['rmbs']);
            $min = isset($set['min']) ? intval($set['min']) : 1;
            
            // 金币兑换RMB的比例：1 金币 可以兑换多少 RMB系统单位
            $gold_to_rmb_rate = isset($set['exchange_c']) ? floatval($set['exchange_c']) : 1;
            
            // 计算需要消耗的金币和能获得的RMB单位
            $gold_cost = $e_golds_input; // 消耗的金币
            $rmb_get = $e_golds_input * $gold_to_rmb_rate; // 获得的RMB系统单位
            
            // 检查最低兑换限制（按获得的RMB计算）
            if($rmb_get < $min) {
                $unit_info = credits_get_rmb_unit_info();
                $min_display = credits_format_rmb_amount($min, true, false);
                message(-1, '最低兑换额：'.$min_display.'，您兑换的金额不足。');
                die();
            }
            if($my_golds < $gold_cost) {message(-1, lang('credit_no_enough'));die();}
            
            // 防重复提交检查
            $recent_query = db_find_one('user_pay',array('uid'=>$uid,'type'=>'6'),array('time'=>-1));
            $now_time = time();
            if(!empty($recent_query) && ($now_time-$recent_query['time'])<=5) {message(-1, "每5秒只能兑换一次，您兑换过于频繁！");die();}
            
            $new_golds = $my_golds - $gold_cost;
            $new_rmbs = $my_rmbs + $rmb_get;
            
            // 确保不出现负数
            if($new_golds < 0 || $new_rmbs < 0) {message(-1, "兑换失败，数据异常!");die();}
            
            db_insert('user_pay',array('uid'=>$uid,'status'=>1,'num'=>abs($gold_cost),'type'=>'6','credit_type'=>'2','code'=>'金币兑换RMB 比例1:'.floatval($gold_to_rmb_rate),'time'=>time()));
            user_update($user['uid'],array('rmbs'=>abs($new_rmbs),'golds'=>abs($new_golds)));
            user_update_group($user['uid']);
            message(0, lang('update_successfully'));
        }elseif($op=='t'){
            $to_username=param('trans_username'); 
            $to_num=param('trans_num'); 
            $credits_type=param('trans_credits');
            
            // 严格验证输入参数
            if(empty($uid) || !is_numeric($uid)) {message(-1, "用户验证失败!");die();}
            if(empty($to_username) || strlen($to_username) > 32) {message(-1, "用户名不能为空且长度不能超过32字符!");die();}
            if(!is_numeric($to_num) || $to_num <= 0 || $to_num > 999999999) {message(-1, "转账金额必须为1-999999999之间的正数!");die();}
            if(!in_array($credits_type, array('1', '2', '3'))) {message(-1, "积分类型无效!");die();}
            
            $to_num = abs(intval($to_num)); // 确保为正整数
            $to_username = trim($to_username);
            
            // 验证接收用户
            $to_user = db_find_one('user',array('username'=>$to_username));
            if(empty($to_user)) {message(-1, "用户不存在!");die();}
            if($user['username']==$to_username) {message(-1, "不能自己给自己转账！");die();}
            
            $credits_name = get_credits_name_by_type($credits_type);
            if(!isset($user[$credits_name])) {message(-1, "积分类型错误!");die();}
            
            // 验证余额
            $current_balance = intval($user[$credits_name]);
            if($current_balance < $to_num) {message(-1, "您的余额不足,请充值!");die();}
            
            // 防重复提交检查
            $recent_transfer = db_find_one('user_pay',array('uid'=>$uid,'type'=>12),array('time'=>-1));
            if(!empty($recent_transfer) && (time() - $recent_transfer['time']) < 3) {
                message(-1, "转账过于频繁，请3秒后再试!");die();
            }
            
            // 执行转账（使用绝对值确保安全）
            db_update('user',array('username'=>$to_username),array($credits_name.'+'=>abs($to_num)));
            db_update('user',array('uid'=>$uid),array($credits_name.'-'=>abs($to_num)));
            db_insert('user_pay',array('uid'=>$to_user['uid'],'status'=>1,'num'=>abs($to_num),'type'=>13,'credit_type'=>$credits_type,'time'=>time(),'code'=>'收到来自'.$user['username'].'的转账'));
            db_insert('user_pay',array('uid'=>$uid,'status'=>1,'num'=>abs($to_num),'type'=>12,'credit_type'=>$credits_type,'time'=>time(),'code'=>'转账给'.$to_username));
            message(0,'转账成功!');
        }
    }
}
elseif($action == 'record') {
    if($method == 'GET')
        include _include(APP_PATH.'plugin/tt_credits/view/htm/my_record.htm');
}elseif($action == 'vip') {
    // VIP插件路由
    if($method == 'GET' && function_exists('vip_isvip'))
        include _include(APP_PATH.'plugin/tt_vip/view/htm/my_vip.htm');
}elseif($action == 'tt_vip_open') {
    // VIP开通页面
    if($method == 'GET' && function_exists('vip_isvip')) {
        include _include(APP_PATH.'plugin/tt_vip/view/htm/open.htm');
    } elseif($method == 'POST' && function_exists('vip_add')) {
        // 处理VIP购买请求
        $buy_type = param('buy_type');
        $buy_num = param('buy_num');
        
        // 严格验证输入参数
        if(empty($uid) || !is_numeric($uid)) {message(-1, "用户验证失败!");die();}
        if(!in_array($buy_type, array('month', 'season', 'half_year', 'year'))) {message(-1, "VIP类型无效!");die();}
        if(!is_numeric($buy_num) || $buy_num <= 0 || $buy_num > 999) {message(-1, "购买数量必须为1-999之间的正数!");die();}
        
        $buy_num = abs(intval($buy_num));
        
        // 检查用户当前是否已经是VIP
        $is_current_vip = vip_isvip($uid);
        
        // 调用VIP购买函数
        $result = vip_add($uid, $buy_num, $buy_type);
        
        if($result === 1) {
            // 根据之前的VIP状态决定提示信息
            if($is_current_vip) {
                message(0, "VIP续费成功！感谢您的支持，会员时长已延长！");
            } else {
                message(0, "VIP开通成功！欢迎成为VIP会员，尊享专属特权！");
            }
        } elseif($result === -1) {
            // 使用VIP插件设置的详细错误信息
            $error_msg = isset($GLOBALS['vip_error_message']) ? $GLOBALS['vip_error_message'] : "余额不足，无法开通VIP！";
            message(-1, $error_msg);
        } elseif($result === -2) {
            message(-1, "购买数量无效！");
        } elseif($result === -3) {
            message(-1, "VIP类型无效！");
        } else {
            message(-1, "开通失败，请稍后重试！");
        }
    }
}