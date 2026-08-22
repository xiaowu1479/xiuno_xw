<?php exit;
elseif($action == 'medal') {
    if($method == 'GET')
        include _include(APP_PATH.'plugin/tt_medal/view/htm/my_medal.htm');
    elseif($method=='POST'){
        $mid = param('mid','-1');
        if($mid=='-1') message(-1,'传参失败！');
        $r = medal_buy($uid,$mid);
        if($r==1) message(0,'购买成功！');
        elseif($r==-1) message(-1,'勋章不存在！');
        elseif($r==-2) message(-1,'用户不存在！');
        elseif($r==-3) message(-1,'用户积分不足！');
        elseif($r==-4) message(-1,'勋章已购买过！');
    }
}elseif($action == 'medal_my'){
    if ($method == 'GET')
        include _include(APP_PATH . 'plugin/tt_medal/view/htm/my_medal_my.htm');
	elseif($method=='POST'){
		$mid = param('mid','-1');
		if($mid=='-1') message(-1,'传参失败！');
		$k = medal_kill($uid,$mid);
		if($k==1) message(0,'勋章以销毁');
        elseif($k==-1) message(-1,'勋章不存在！');
        elseif($k==-2) message(-1,'用户不存在！');
        elseif($k==-3) message(-1,'该勋章不可销毁！');		
	}
}elseif($action == 'medal_apply'){
    if($method == 'GET')
        //这个没用上，可以删掉
        include _include(APP_PATH.'plugin/tt_medal/view/htm/medal_open.htm');
    elseif($method == 'POST'){
        $mid = param('mid','-1');
        $reason = param('reason');
        if($mid=='-1') message(-1,'传参失败！');
        $a=medal_apply($uid,$mid,$reason);
        if($a==1) message(0,'申请成功');
        elseif($action==-1) message(-1,'勋章不存在！');
        elseif($a==-2) message(-1,'用户不存在！');
        elseif($a==-3) message(-1,'申请正在等待审核！');
    }
}
?>