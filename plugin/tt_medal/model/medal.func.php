<?php
function medal_super_add($uid,$mid){
    $m = medal_read($mid);
    $validity = 0;
    if($m['time']==0){
        $validity = 2145888000;//2038年1月1日0时
    }else {
        $validity = 86400 * $m['time'] + strtotime('today');//当前时间+有效期
    }
    db_insert('user_medal',array('uid'=>$uid,'mid'=>$mid,'time'=>time(),'validity'=>$validity));
    db_update('medal',array('mid'=>$mid),array('have_count+'=>1));
}
function medal_user_delete($uid,$mid){
    db_delete('user_medal',array('uid'=>$uid,'mid'=>$mid));
    db_update('medal',array('mid'=>$mid),array('have_count-'=>1));
}
function medal_user_truncate($uid){
    $md = medal_user_read($uid);
    db_delete('user_medal',array('uid'=>$uid));
    foreach($md as $_m)
        db_update('medal',array('mid'=>$_m['mid']),array('have_count-'=>1));
}
function medal_user_count($uid){
    $r = db_count('user_medal',array('uid'=>$uid));
    return $r? $r:0;
}
function medal_user_read($uid){
    $r = db_find('user_medal',array('uid'=>$uid),array(),1,50);
    return $r;
}
function medal_user_read_fmt($uid){
    $r = medal_user_read($uid);
    foreach($r as &$_r) {
        $_time = $_r['time'];
        $validity = $_r['validity'];
        $_r = medal_read($_r['mid']);
        $_r['fmt_description'] = '['.$_r['name'].'] '.$_r['description'];
        $_r['time'] = $_time;
        $_r['validity'] = $validity;
    }
    return $r;
}
function medal_user_havemedal($mid,$uid){
    $r = medal_user_read($uid);
    $flag=0;
    foreach($r as $_r)
        if($_r['mid']==$mid){
            $flag=1; break;
        }
    return $flag==1;
}
function medal_user__havemedal($mid,$medal_user){
    $r = $medal_user;
    $flag=0;
    foreach($r as $_r)
        if($_r['mid']==$mid){
            $flag=1; break;
        }
    return $flag==1;
}
function medal_read_name($mid){
    $r = db_find_one('medal',array('mid'=>$mid));
    return $r? $r['name']: '未命名';
}
function medal_read($mid){
    $r = db_find_one('medal',array('mid'=>$mid));
    return $r;
}
function medal_buy($uid,$mid){
    $r = db_find_one('medal',array('mid'=>$mid));
    if(!$r) return -1;
    $user = user_read($uid);
    if(!$user) return -2;
    $isbought = db_find_one('user_medal',array('mid'=>$mid,'uid'=>$uid));
    if($isbought) return -4;
    $credits_type = get_credits_name_by_type($r['money_type']);
    if($user[$credits_type] < $r['money']) return -3;
    db_update('user',array('uid'=>$uid),array($credits_type.'-'=>$r['money']));
    medal_super_add($uid,$mid);
    db_insert('user_pay',array('uid'=>$uid,'status'=>1,'num'=>$r['money'],'type'=>18,'credit_type'=>$r['money_type'],'time'=>time(),'code'=>$r['name']));
    return 1;
}
function medal_delete($mid){
    db_delete('medal',array('mid'=>$mid));
    db_delete('user_medal',array('mid'=>$mid));
}
function medal_create($arr){
    db_insert('medal',$arr);
}
//勋章销毁
function medal_kill($uid,$mid){
	$r = db_find_one('medal',array('mid'=>$mid));
	if(!$r) return -1;
	if($r['isbuy']==0) return -3;
	$user = user_read($uid);
	if(!$user) return -2;
	$credits_type = get_credits_name_by_type($r['money_type']);
	$proportion = setting_get('tt_medal')['proportion'] /10;
	$credits_ceil=ceil($r['money'] * $proportion);
	db_update('user',array('uid'=>$uid),array($credits_type.'+'=>$credits_ceil));
	medal_user_delete($uid,$mid);
	notice_send('1', $uid, '您的勋章'.$r['name'].'已被销毁,以返还'.$credits_ceil.'到您的账户', 233); // 3:系统通知
	return 1;
}

function medal_list_get($page=1,$pagesize=50){
    $r= db_find('medal', array(), array('mid'=>1), $page, $pagesize,'mid');
    $rtn = array();
    foreach($r as $k=>$v)
        $rtn[$v['mid']] = $v;
    return $rtn;
}

function medal_db_count(){
    $medallist = db_find('medal',array(),array('mid'=>1),1,50);
    foreach($medallist as $medal){
        $m_count = db_count('user_medal',array('mid'=>$medal['mid']));
        db_update('medal',array('mid'=>$medal['mid']),array('have_count'=>$m_count));
    }	
}
//勋章申请，用户id,勋章id，申请原因
function medal_apply($uid,$mid,$reason){
	$r = db_find_one('medal',array('mid'=>$mid));
	if(!$r) return -1;
	$user = user_read($uid);
	if(!$user) return -2;
	db_insert('medal_check',array('uid'=>$uid,'mid'=>$mid,'time'=>time(),'reason'=>$reason));
	medal_send($uid,$r['name'],0);
	return 1;
}
// 发送系统通知
function medal_send($uid,$name,$type){
    //1同意,2拒绝
    switch ($type) {
        case '0':
            notice_send($uid, '1', '我申请了一个勋章('.$name.')请批准', 233); 
            break;
        case '1':
            notice_send('1',$uid,'您申请的'.lang('medal').'('.$name.')已被管理员批准发放', 233);
            break;
        case '2':
            notice_send('1',$uid,'您申请的'.lang('medal').'('.$name.')已被管理员拒绝发放', 233);
            break;
    }
}
?>