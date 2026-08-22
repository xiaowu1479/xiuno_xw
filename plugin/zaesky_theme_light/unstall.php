<?php

/*
	Xiuno BBS 4.0 xiuno L
*/

!defined('DEBUG') AND exit('Forbidden');
$tablepre = $db->tablepre;
// 容错清理：列/表不存在时忽略 (fixed by XIUNO XW)
$sql = "ALTER TABLE {$tablepre}user DROP COLUMN bgimg";
@db_exec($sql);
$sql = "DROP TABLE IF EXISTS {$tablepre}authCode";
db_exec($sql);
message(0, lang('plugin_unstall_sucessfully', array('name'=>lang('plugin'), 'dir'=>'plugin/zaesky_theme_light')));


?>