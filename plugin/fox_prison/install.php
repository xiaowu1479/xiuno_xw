<?php
/*
 * https://www.xiunobbs.vip/
 * Xiuno BBS生态社区-优化版
 */
!defined('DEBUG') AND exit('Forbidden');
$tablepre = $db->tablepre;

$sql = "UPDATE `{$tablepre}group` SET `name` = '关禁闭组' WHERE `gid` =7;";
db_exec($sql);

group_list_cache_delete();

$sql = "CREATE TABLE IF NOT EXISTS `{$tablepre}fox_prison`(
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(11) unsigned NOT NULL DEFAULT '0',
  `aid` int(11) unsigned NOT NULL DEFAULT '0',
  `time` int(11) unsigned NOT NULL DEFAULT '0',
  `endtime` int(11) unsigned NOT NULL DEFAULT '0',
  `uip` int(11) unsigned NOT NULL DEFAULT '0',
  `message` text NOT NULL,
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;";
db_exec($sql);
?>