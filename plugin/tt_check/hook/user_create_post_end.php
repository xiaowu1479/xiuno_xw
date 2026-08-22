<?php exit;
$set_check = setting_get('tt_check');
if($set_check['user_check']!='1')
    user_update($uid,array('OK'=>'1'));
?>