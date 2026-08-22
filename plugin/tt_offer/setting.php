<?php
!defined('DEBUG') AND exit('Access Denied.');
$action = param(3);
if(empty($action)){
    if($method == 'GET')
        include _include(APP_PATH.'plugin/tt_offer/setting.htm');
    elseif($method=='POST'){
        $tablepre=$db->tablepre;
        $update_lists2=param('UallowOffer',array(0));
        $sql = 'UPDATE '.$tablepre.'group SET allowOffer="0";';
        foreach($update_lists2 as $k => $v)
            $sql .= 'UPDATE '.$tablepre.'group SET allowOffer="1" WHERE gid="'.$k.'";';
        $status=db_exec($sql);
        group_list_cache_delete();
        $update_lists=param('FallowOffer',array(0));
        $sql = 'UPDATE '.$tablepre.'forum SET allowOffer="0";';
        foreach($update_lists as $k => $v)
            $sql .= 'UPDATE '.$tablepre.'forum SET allowOffer="1" WHERE fid="'.$k.'";';
        $status=db_exec($sql);
        forum_list_cache_delete();
        setting_set('tt_offer',array('credits_type'=>param('offerCredits',3)));
        message(0,'设置成功！');
    }
}

?>