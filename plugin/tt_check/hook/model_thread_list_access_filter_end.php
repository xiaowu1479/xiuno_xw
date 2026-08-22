<?php exit;
global $uid,$group;
$see_check = check_need_check($group,'see');
foreach($threadlist as $tid=>$thread)
    if((!isset($thread['OK']) || $thread['OK']!='1') && (!$see_check) && $uid!=$thread['uid'])
        unset($threadlist[$tid]);
?>