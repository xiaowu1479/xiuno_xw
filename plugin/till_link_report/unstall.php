<?php

/*
	链接失效反馈 - 卸载
	删除反馈记录表 & 配置
*/

!defined('DEBUG') AND exit('Forbidden');

$tablepre = $db->tablepre;

db_exec("DROP TABLE IF EXISTS {$tablepre}link_report");

setting_delete('till_link_report');

?>
