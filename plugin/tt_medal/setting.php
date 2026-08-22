<?php
!defined('DEBUG') AND exit('Access Denied.');
$action = param(3);
if(empty($action)){
    $medallist = medal_list_get();
    $maxid = db_maxid('medal', 'mid');
    if($method == 'GET')
        include _include(APP_PATH.'plugin/tt_medal/setting.htm');
    elseif($method=='POST'){
        $op = param('op','0');
        if($op=='0') {
            $rowidarr = param('rowid', array(0));
            $namearr = param('name', array(''));
            $filenamearr = param('filename', array(''));
            $descriptionarr = param('description', array(''));
            $isbuyarr = param('isbuy', array(''));
            $moneyarr = param('money', array(''));
            $money_typearr = param('money_type', array(''));
            $m_time = param('time', array(''));
            foreach ($rowidarr as $k => $v) {
                if (empty($namearr[$k]) && empty($filenamearr[$k]) && empty($descriptionarr[$k]) && empty($isbuyarr[$k]) && empty($moneyarr[$k]) && empty($money_typearr[$k]) && empty($m_time[$k])) continue;
                $arr = array(
                    'mid' => $k,
                    'name' => $namearr[$k],
                    'filename' => $filenamearr[$k],
                    'description' => $descriptionarr[$k],
                    'isbuy' => $isbuyarr[$k],
                    'money' => $moneyarr[$k],
                    'money_type' => $money_typearr[$k],
                    'time' => $m_time[$k]
                );

                if (!isset($medallist[$k])) medal_create($arr);
                else db_update('medal', array('mid' => $k), $arr);
            }

            $deletearr = array_diff_key($medallist, $rowidarr);
            foreach ($deletearr as $k => $v)
                medal_delete($k);
            medal_db_count();
            message(0, '保存成功');
        } elseif($op=='1') {
            $username = param('username');
            $user = db_find_one('user',array('username'=>$username));
            if(!$user){message(-1,'用户不存在！');die();}
            $uid = $user['uid'];
            $medal_list_arr = param('medal',array(''));
            $user_medal_list_arr = medal_user_read($uid);
            foreach($medallist as $_medal){
                $mid = $_medal['mid'];
                $set_status = (isset($medal_list_arr[$mid]) AND $medal_list_arr[$mid]=='1')? 1: 0;
                $user_status = medal_user_havemedal($mid,$uid) ? 1:0;
                if($set_status!=$user_status){
                    if($set_status==1 && $user_status==0) medal_super_add($uid,$mid);
                    elseif($set_status==0 && $user_status==1) medal_user_delete($uid,$mid);
                }
            }
            message(0,'设置成功');
        }elseif($op=='2'){
            $mcid = param('mcid');
            $type = param('type');
            $medal = db_find_one('medal_check',array('mcid'=>$mcid));
            $mCheck = db_find_one('medal',array('mid'=>$medal['mid']));
        	if(!$mCheck){message(-1,lang('medal').'不存在');die();} 
        	$uCheck = user_read($medal['uid']);
        	if(!$uCheck){message(-1,'用户不存在');die();}
        	if($type=='1'){
        	    medal_super_add($medal['uid'],$medal['mid']);
        	    medal_send($medal['uid'],$mCheck['name'],1);
        	}else{
        	    medal_send($medal['uid'],$mCheck['name'],2);
        	}
        	db_delete('medal_check',array('mcid'=>$mcid));
        	message(0,'审批流程结束');
        }elseif($op=='3'){
            $proportion = param('proportion');
            if($proportion>10) $proportion = 10 ;
            setting_set('tt_medal',array('proportion'=>$proportion));
            message(0,'保存成功');
        }
    }
}
?>