<?php
/*
 * https://www.xiunobbs.vip/
 * Xiuno BBS生态社区-优化版
 */

!defined('DEBUG') AND exit('Forbidden');
$tablepre = $db->tablepre;

//恢复用户组名称
$sql = "UPDATE `{$tablepre}group` SET `name` = '终身禁止' WHERE `gid` =7;";
db_exec($sql);

group_list_cache_delete();

//如果卸载数据就没了！
$sql = "DROP TABLE IF EXISTS `{$tablepre}fox_prison`;";
$r = db_exec($sql);
?>