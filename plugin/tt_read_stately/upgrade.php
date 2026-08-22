<?php
!defined('DEBUG') AND exit('Forbidden');
setting_set('tt_read',array('old'=>'0'));
$sql = "ALTER TABLE ".$tablepre."group ADD allowPostRead int(5) NOT NULL default '1';";
db_exec($sql);
?>