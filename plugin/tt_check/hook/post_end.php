<?php exit;
elseif($action=='check' && $method=='POST'){
    $opt = param('opt');//1:OK -1:NO -2:DELETE
    $data = param('data');
    $act = param('act');
    if(!$uid){message(-1,'拉取信息失败！');die();}
    if($group['see_check']!=1){message(-1,'无权操作！');die();}
    if($act=='0') check_set_thread_check($data,$opt);
    elseif($act=='1') check_set_post_check($data,$opt);
    elseif($act=='2') check_set_user_check($data,$opt);
    message(0,'审核完毕！');
} elseif($action=='recycle' &&$method=='POST') {
    $set_recycle = setting_get('tt_check');
    if($set_recycle['recycle']!='1') {message(-1,'未开启回收站功能！');die();}
    if($group['see_check']!='1') {message(-1,'无权操作回收站功能！');die();}
    $opt=param('opt'); $data=param('data');
    //-1:recovery 1:delete
    if($opt=='-1') recycle_thread_recovery($data);
    elseif($opt=='1') recycle_thread_delete_true($data);
    message(0,'操作完毕！');
}
?>