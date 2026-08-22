<?php
!defined('DEBUG') AND exit('Access Denied.');
$action = param(3);
if(empty($action)){
    if($method == 'GET'){//设置页面
        include _include(APP_PATH.'plugin/tt_credits/setting.htm');
    } elseif($method=="POST") {
        $gettype = param('op');
        if($gettype=='0') {
            $username = param('username');
            $golds = param('golds_change');
            $rmbs = param('rmbs_change');
            $exps = param('exp_change');
            
            // 严格验证输入
            if(empty($username) || strlen($username) > 32) {
                message("-1", "用户名无效");
                return;
            }
            
            $username = trim($username);
            
            // 验证数值
            if(!is_numeric($golds) || !is_numeric($rmbs) || !is_numeric($exps)) {
                message("-1", "积分数值格式错误");
                return;
            }
            
            // 防止设置过大的数值
            $golds = intval($golds);
            $rmbs = intval($rmbs); 
            $exps = intval($exps);
            
            if(abs($golds) > 999999999 || abs($rmbs) > 999999999 || abs($exps) > 999999999) {
                message("-1", "积分数值超出范围");
                return;
            }
            
            $user = db_find_one('user', array('username' => $username));
            if (empty($user)) {
                message("-1", "用户不存在");
                return;
            }
            
            $db_array = array(
                'golds' => $golds,
                'rmbs' => $rmbs, 
                'credits' => $exps
            );
            
            db_update('user', array('username' => $username), $db_array);
            $user = db_find_one('user', array('username' => $username));
            $now_user_array = array("exp" => intval($user['credits']), "golds" => intval($user['golds']), "rmbs" => intval($user['rmbs']));
            message("0", $now_user_array);
        } elseif($gettype=='4') {
            $username = param('username');
            
            // 验证用户名
            if(empty($username) || strlen($username) > 32) {
                message("-1", "用户名无效");
                return;
            }
            
            $username = trim($username);
            $user = db_find_one('user', array('username' => $username));
            
            if(empty($user)) {
                message("-1", "用户不存在");
                return;
            }
            
            $now_user_array = array(
                "exp" => intval($user['credits']), 
                "golds" => intval($user['golds']), 
                "rmbs" => intval($user['rmbs'])
            );
            message("0", $now_user_array);
        } elseif($gettype=='1') {
            $_uid = param('_useruid'); 
            $_tid = param('_tid');
            
            // 验证参数
            if(!is_numeric($_uid) || $_uid <= 0) {
                message("-1", "用户ID无效");
                return;
            }
            
            if(!is_numeric($_tid) || $_tid <= 0) {
                message("-1", "主题ID无效");
                return;
            }
            
            $_uid = intval($_uid);
            $_tid = intval($_tid);
            
            // 验证用户和主题是否存在
            $user_exists = db_count('user', array('uid' => $_uid));
            $thread_exists = db_count('thread', array('tid' => $_tid));
            
            if($user_exists == 0) {
                message("-1", "用户不存在");
                return;
            }
            
            if($thread_exists == 0) {
                message("-1", "主题不存在");
                return;
            }
            
            db_insert('paylist',array('tid' => $_tid, 'uid' => $_uid, 'num'=>0,'credit_type'=>1, 'type' => 1, 'paytime' => time()));
            message("0","操作成功");
        } elseif ($gettype=='2') {
            $update_lists=param('sell_group',array(0)); $status=0;
            if(empty($update_lists))
                message(-1, '设置失败！');
            $tablepre = $db->tablepre;
            $sql = 'UPDATE '.$tablepre.'group SET allowsell="0";';
            foreach($update_lists as $k => $v)
                $sql .= 'UPDATE '.$tablepre.'group SET allowsell="1" WHERE gid="'.$k.'";';
            db_exec($sql);
            group_list_cache_delete();
            message(0, '设置成功！');
        } elseif ($gettype=='3') {
            $settings = array();
            $valid_keys = array('thread_exp','post_exp','down_exp','digest1_exp','digest2_exp','digest3_exp',
                               'thread_gold','post_gold','down_gold','digest1_gold','digest2_gold','digest3_gold',
                               'thread_rmb','post_rmb','down_rmb','digest1_rmb','digest2_rmb','digest3_rmb');
            
            foreach($valid_keys as $key) {
                $value = param($key);
                if(!is_numeric($value) || abs($value) > 999999) {
                    message(-1, '积分设置 '.$key.' 数值无效或超出范围');
                    return;
                }
                $settings[$key] = intval($value);
            }
            
            $limit = param('limit');
            $min = param('min');
            $exchange_n = param('exchange_n','1');
            $exchange_c = param('exchange_c','1');
            
            // 验证其他设置
            if(!is_numeric($limit) || $limit < 0 || $limit > 999999) {
                message(-1, '积分限制设置无效');
                return;
            }
            
            if(!is_numeric($min) || $min < 0 || $min > 999999) {
                message(-1, '最低兑换额设置无效');
                return;
            }
            
            if(!is_numeric($exchange_n) || $exchange_n <= 0 || $exchange_n > 1000) {
                message(-1, '兑换比例N设置无效');
                return;
            }
            
            if(!is_numeric($exchange_c) || $exchange_c <= 0 || $exchange_c > 1000) {
                message(-1, '兑换比例C设置无效');
                return;
            }
            
            $rmb_unit_rate = param('rmb_unit_rate', '100');
            
            // 验证RMB单位换算比例
            if(!is_numeric($rmb_unit_rate) || $rmb_unit_rate <= 0 || $rmb_unit_rate > 10000) {
                message(-1, 'RMB单位换算比例必须为1-10000之间的正数');
                return;
            }
            
            $settings['limit'] = intval($limit);
            $settings['min'] = intval($min);
            $settings['convert_exchange'] = param('convert_exchange')=='convert_exchange'?'1':'0';
            $settings['exchange_n'] = floatval($exchange_n);
            $settings['exchange_c'] = floatval($exchange_c);
            $settings['rmb_unit_rate'] = floatval($rmb_unit_rate);
            $settings['buy_push'] = param('buy_push')=='buy_push' ? '1' : '0';
            $settings['attach_buy_push'] = param('attach_buy_push')=='attach_buy_push' ? '1' : '0';
            
            setting_set('tt_credits',$settings);
            message(0, '设置成功！');
        } elseif($gettype=='5') {
            $credit = param('d_credit');
            $gold = param('d_gold');
            $rmb = param('d_rmb');
            
            // 严格验证输入
            if(!is_numeric($credit) || !is_numeric($gold) || !is_numeric($rmb)) {
                message(-1, '默认积分值必须为数字');
                return;
            }
            
            $credit = intval($credit);
            $gold = intval($gold);
            $rmb = intval($rmb);
            
            // 防止设置过大的默认值
            if(abs($credit) > 999999 || abs($gold) > 999999 || abs($rmb) > 999999) {
                message(-1, '默认积分值超出范围(最大999999)');
                return;
            }
            
            $tablepre = $db->tablepre;
            // 使用参数化查询防止SQL注入
            $sql = "alter table {$tablepre}user alter column credits set default " . intval($credit) . ";";
            db_exec($sql);
            $sql = "alter table {$tablepre}user alter column golds set default " . intval($gold) . ";";
            db_exec($sql);
            $sql = "alter table {$tablepre}user alter column rmbs set default " . intval($rmb) . ";";
            db_exec($sql);
            message(0, '设置成功！');
        } elseif($gettype=='6') {
            $username = param('username','');
            if(empty($username)) {message(-1, 'ERROR');die();}
            $user = db_find_one('user',array('username'=>$username));
            user_update_group($user['uid']);
            message(0,'设置成功！');
        }
    }
}

?>