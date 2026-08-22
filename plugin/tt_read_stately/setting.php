<?php
!defined('DEBUG') AND exit('Access Denied.');
$action = param(3);
if(empty($action)){
    if($method == 'GET'){//设置页面
        include _include(APP_PATH.'plugin/tt_read_stately/setting.htm');
    }
    else if($method=="POST")
    {
        $update_lists=param('read_group',array(0)); $status=0;
        $update_lists2=param('read_PostRead',array(0)); $status=0;
        if(empty($update_lists))
            message(-1, '设置失败！');
        $tablepre = $db->tablepre;
        $sql = '';
        foreach($update_lists as $k => $v)
            $sql .= 'UPDATE '.$tablepre.'group SET readp='.$v.' WHERE gid="'.$k.'";';
        $status=db_exec($sql);
        group_list_cache_delete();
        $sql = 'UPDATE '.$tablepre.'group SET allowPostRead="0";';
        foreach($update_lists2 as $k => $v)
            $sql .= 'UPDATE '.$tablepre.'group SET allowPostRead="1" WHERE gid="'.$k.'";';
        $status=db_exec($sql);
        if(param('old_mode')=='old')
            setting_set('tt_read',array('old'=>'1'));
        else
            setting_set('tt_read',array('old'=>'0'));
        message(0, '设置成功！');
    }
}
?>