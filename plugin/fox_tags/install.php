<?php
/*
 * 奇狐插件 安装文件
 * QQ:77798085
 */
!defined('DEBUG') AND exit('Forbidden');
$tablepre = $db->tablepre;

!db_sql_find_one("SHOW COLUMNS FROM `{$tablepre}group` LIKE 'allowtags'") AND db_exec("ALTER TABLE `{$tablepre}group` ADD COLUMN `allowtags` tinyint(1) unsigned NOT NULL DEFAULT '0'");
!db_sql_find_one("SHOW COLUMNS FROM `{$tablepre}group` LIKE 'allowtags2'") AND db_exec("ALTER TABLE `{$tablepre}group` ADD COLUMN `allowtags2` tinyint(1) unsigned NOT NULL DEFAULT '0'");
!db_sql_find_one("SHOW COLUMNS FROM `{$tablepre}group` LIKE 'allowtags3'") AND db_exec("ALTER TABLE `{$tablepre}group` ADD COLUMN `allowtags3` tinyint(1) unsigned NOT NULL DEFAULT '0'");
db_exec("UPDATE `{$tablepre}group` SET `allowtags`='1',`allowtags2`='1' WHERE `gid` in('1,2,4,5');");
group_list_cache_delete();

!db_sql_find_one("SHOW COLUMNS FROM `{$tablepre}thread` LIKE 'keywords'") AND db_exec("ALTER TABLE `{$tablepre}thread` ADD COLUMN `keywords` varchar(250) NOT NULL DEFAULT '' COMMENT '关键词'");

$sql="CREATE TABLE IF NOT EXISTS `{$tablepre}fox_tag`(
  `tagid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` char(32) NOT NULL,
  `subject` char(128) NOT NULL DEFAULT '',
  `keywords` varchar(255) NOT NULL DEFAULT '',
  `brief` text NOT NULL,
  `cover` char(255) NOT NULL DEFAULT '',
  `fans` mediumint(8) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`tagid`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;";
db_exec($sql);

$sql="CREATE TABLE IF NOT EXISTS `{$tablepre}fox_tag_data`(
  `tagid` int(11) unsigned NOT NULL,
  `tid` int(11) unsigned NOT NULL
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";
db_exec($sql);

$sql="CREATE TABLE IF NOT EXISTS `{$tablepre}fox_tag_follow`(
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(11) unsigned NOT NULL DEFAULT '0',          #用户id
  `tagid` int(11) unsigned NOT NULL DEFAULT '0',        #被关注tagid
  `tagname` varchar(30) NOT NULL DEFAULT '',            #被关注TAG
  `create_date` int(11) unsigned NOT NULL DEFAULT '0',  #创建时间
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `tagid` (`tagid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;";
db_exec($sql);

$output = kv_get('fox_tag');
if(empty($output)) {
    $output = array(
        'tag_index' => 30,
        'tag_list' => 80,
        'tag_pos' => 1,
        'tag_inc' => 0,
        'tag_top' => 0,
        'tag_end' => 1,
        'tag_two' => 1,
        'tag_max' => 5,
        'tag_lim' => 4,
        'tag_words' => '',
        'tag_retain' => 0
    );
    kv_cache_set('fox_tag', $output);
}
?>