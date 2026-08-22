<?php
function credits_get_content_type_by_name($name){
    if($name==lang('credits1')) return '1';
    elseif ($name==lang('credits2')) return '2';
    elseif ($name==lang('credits3')) return '3';
    else return '1';
}
function credits_thread_purchased_find_by_uid($uid, $page = 1, $pagesize = 20) {
    $tids = db_find('paylist', array('uid'=>$uid), array('paytime'=>-1), $page, $pagesize, 'tid');
    $threadlist = array();
    foreach($tids as $_tid)
        $threadlist[$_tid['tid']] = thread_read_cache($_tid['tid']);
    if($threadlist) foreach($threadlist as &$thread) thread_format($thread);
    return $threadlist;
}
function credits_purchased_count($cond = array()) {
    $n = db_count('paylist', $cond);
    return $n;
}
function get_credits_name_by_type($type){
    switch($type) {
        default:return 'credits';break;
        case '1':return 'credits';break;
        case '2':return 'golds';break;
        case '3':return 'rmbs';break;
    }
}
function credits_get_text($type,$credits,$process=1){
    $rtn=lang('credits'.$type).' ';
    if($process&&$type=='3') {
        $set = setting_get('tt_credits');
        $rmb_unit_rate = isset($set['rmb_unit_rate']) ? floatval($set['rmb_unit_rate']) : 100;
        $rtn.= ($credits/$rmb_unit_rate);
    } else {
        $rtn.=$credits;
    }
    return $rtn;
}

// 获取RMB显示格式的辅助函数
function credits_get_rmb_display($credits_value, $show_unit = true) {
    $set = setting_get('tt_credits');
    $rmb_unit_rate = isset($set['rmb_unit_rate']) ? floatval($set['rmb_unit_rate']) : 100;
    
    if($rmb_unit_rate == 1) {
        // 元制：1元=1单位
        $display_value = $credits_value;
        $unit = $show_unit ? '元' : '';
    } else {
        // 分制或其他比例
        $display_value = $credits_value / $rmb_unit_rate;
        $unit = $show_unit ? '元' : '';
    }
    
    return number_format($display_value, 2) . $unit;
}

// 获取RMB单位信息
function credits_get_rmb_unit_info() {
    $set = setting_get('tt_credits');
    $rmb_unit_rate = isset($set['rmb_unit_rate']) ? floatval($set['rmb_unit_rate']) : 100;
    
    // 根据比例自动识别单位类型
    $unit_info = array();
    if ($rmb_unit_rate == 1) {
        // 元制：1元=1单位
        $unit_info = array(
            'type' => 'yuan',
            'unit_name' => '元',
            'system_unit_name' => '元'
        );
    } elseif ($rmb_unit_rate == 10) {
        // 角制：1元=10单位
        $unit_info = array(
            'type' => 'jiao', 
            'unit_name' => '角',
            'system_unit_name' => '角'
        );
    } else {
        // 分制或其他：1元=100单位等
        $unit_info = array(
            'type' => 'fen',
            'unit_name' => '分',
            'system_unit_name' => '分'
        );
    }
    
    return array_merge($unit_info, array(
        'rate' => $rmb_unit_rate,
        'is_yuan' => ($rmb_unit_rate == 1),
        'is_jiao' => ($rmb_unit_rate == 10),
        'is_fen' => ($rmb_unit_rate != 1 && $rmb_unit_rate != 10)
    ));
}

// 格式化RMB金额显示，根据单位类型自动转换
function credits_format_rmb_amount($amount, $show_unit = true, $show_yuan_equivalent = false) {
    $unit_info = credits_get_rmb_unit_info();
    $rate = $unit_info['rate'];
    
    if ($unit_info['is_yuan']) {
        // 元制：直接显示
        $display_amount = $amount;
        $unit = $show_unit ? '元' : '';
    } elseif ($unit_info['is_jiao']) {
        // 角制：显示角为主
        $display_amount = $amount;
        $unit = $show_unit ? '角' : '';
    } else {
        // 分制：显示分为主
        $display_amount = $amount;
        $unit = $show_unit ? '分' : '';
    }
    
    $result = number_format($display_amount, ($unit_info['is_yuan'] ? 2 : 0)) . $unit;
    
    // 如果需要显示元等价值
    if ($show_yuan_equivalent && !$unit_info['is_yuan']) {
        $yuan_value = $amount / $rate;
        $result .= ' (约' . number_format($yuan_value, 2) . '元)';
    }
    
    return $result;
}
function credits_get_icon_html($type){
    switch($type){
        case 1:$rtn='<i class="icon-flask" aria-hidden="true"></i>';break;
        case 2:$rtn='<i class="icon-diamond" aria-hidden="true"></i>';break;
        case 3:$rtn='<i class="icon-jpy" aria-hidden="true"></i>';break;
        default: $rtn='<i class="icon-diamond" aria-hidden="true"></i>';break;
    }
    return $rtn;
}
$g_credits_item_array = array('thread_exp','post_exp','down_exp','digest1_exp','digest2_exp','digest3_exp','thread_gold','post_gold','down_gold','digest1_gold','digest2_gold','digest3_gold','thread_rmb','post_rmb','down_rmb','digest1_rmb','digest2_rmb','digest3_rmb');
$g_credits_type_array=array('支付宝充值','微信充值','提现','充值审核','购买主题','使用卡密','兑换','VIP充值','购买邀请码','领取红包','发送红包','撤回红包','发送转账','收到转账','打赏','收到打赏','悬赏','收到悬赏','购买勋章','QQ充值');
$g_credits_status_array=array('失败','审核中','成功','等待充值');
?>