elseif($action == 'gift') {
    if($method == 'GET') {
        include _include(APP_PATH.'plugin/tt_credits/view/htm/my_trade.htm');
    }
    elseif($method == 'POST') {
    $key= param('key');$msg=array();
    if(strlen($key)!=36){message(-1,"礼品卡卡号为36位，您输入的不是36位，请检查是否输入错误。");die();}
    if(empty($uid)){message(-1,"获取用户信息失败。");die();}
    $_sql =db_find_one('gift',array('card_id'=>$key),array('time_from'=>-1));
    if($_sql['status']!=0){message(-1,"礼品卡已经被使用！");die();}
    if(empty($_sql)) {message(-1,"卡密不存在！");die();}
    db_update('gift',array('card_id'=>$key),array('status'=>'1','uid'=>$uid,'time_to'=>time()));
    $need_update=array();
    if(!empty($_sql['credits'])) {$need_update['credits+']=$_sql['credits'];$msg[lang('credits1')]=$_sql['credits'];}
    if(!empty($_sql['golds'])) {$need_update['golds+']=$_sql['golds'];$msg[lang('credits2')]=$_sql['golds'];}
    if(!empty($_sql['rmbs'])) {$need_update['rmbs+']=$_sql['rmbs'];$msg[lang('credits3')]=$_sql['rmbs']/100.0;}
    if(!empty($need_update)) user_update($uid,$need_update);
    if(!empty($_sql['tids']))
    {
        $msg["帖子ID"]=$_sql['tids'];
        $tid_arr = explode('|',$_sql['tids']); $_time=time();
        foreach($tid_arr as $_tid) {
            $_bought = db_count('paylist',array('tid' => $_tid, 'uid' => $uid));
            if(! $_bought)
                db_insert('paylist', array('tid' => $_tid, 'uid' => $uid, 'credit_type' => '1', 'num' => '0', 'type' => 1, 'paytime' => $_time));
        }
    }
    $msg1=''; $flag=0;
    foreach($msg as $k=>$v)
    {if($flag==1)$msg1.='、';
    $msg1.=$k.':'.$v;$flag=1;}
    db_insert('user_pay',array('uid'=>$uid,'status'=>1,'num'=>'0','type'=>'5','credit_type'=>'1','code'=>$msg1,'time'=>time()));
    user_update_group($uid);
    message(0,"成功，礼品卡包含以下内容：".$msg1);
    }
}