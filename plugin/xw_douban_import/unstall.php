<?php

/*
	豆瓣影视批量导入 - 卸载
*/

!defined('DEBUG') AND exit('Forbidden');

$tablepre = $db->tablepre;

$r = db_exec("DROP TABLE IF EXISTS {$tablepre}xw_douban_import;");
$r === FALSE AND message(-1, '删除导入任务表 xw_douban_import 失败');

?>
