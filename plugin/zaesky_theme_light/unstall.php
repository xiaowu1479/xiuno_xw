<?php

/*
	Xiuno BBS 4.0 xiuno L
*/

!defined('DEBUG') AND exit('Forbidden');
$tablepre = $db->tablepre;
$sql = "ALTER TABLE {$tablepre}user DROP COLUMN bgimg";
db_exec($sql);
$sql = "DROP TABLE IF EXISTS {$tablepre}authCode";
db_exec($sql);
message(-1, '<h3><i class="icon-cogs"></i> 卸载完成！</h3><p>期待您的下次使用，再见！</p> <a role="button" class="btn btn-secondary btn-block m-t-1" href="javascript:history.back();">返回</a>');


?>