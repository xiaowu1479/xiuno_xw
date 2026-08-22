<?php !defined('DEBUG') AND exit('Forbidden');
$tablepre = $db->tablepre;
//20180915
$set = setting_get('tt_check');
$set['recycle']=0;
setting_set('tt_check',$set);

?>