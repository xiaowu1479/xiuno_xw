<?php

/*
	豆瓣影视批量导入 - 安装
	创建导入任务表，初始化缓存目录
*/

!defined('DEBUG') AND exit('Forbidden');

$tablepre = $db->tablepre;

$sql = "CREATE TABLE IF NOT EXISTS {$tablepre}xw_douban_import (
	id int(11) unsigned NOT NULL AUTO_INCREMENT,
	hash char(32) NOT NULL DEFAULT '',				-- 标题+链接 md5，用于去重
	title varchar(128) NOT NULL DEFAULT '',			-- 影视名
	link varchar(512) NOT NULL DEFAULT '',			-- 网盘链接
	fid int(11) unsigned NOT NULL DEFAULT '0',		-- 目标版块
	uid int(11) unsigned NOT NULL DEFAULT '0',		-- 发帖用户
	tagids varchar(255) NOT NULL DEFAULT '',		-- 固定标签 tagid，逗号分隔
	status tinyint(1) NOT NULL DEFAULT '0',			-- 0待处理 1成功 2失败
	tid int(11) unsigned NOT NULL DEFAULT '0',		-- 发布后的主题ID
	err varchar(255) NOT NULL DEFAULT '',			-- 失败原因
	dateline int(11) unsigned NOT NULL DEFAULT '0',
	PRIMARY KEY (id),
	KEY (hash),
	KEY (status)
) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$r = db_exec($sql);
$r === FALSE AND message(-1, '创建导入任务表 xw_douban_import 失败');

// 缓存目录（豆瓣抓取结果缓存，与 huux_tinymce 的缓存相互独立）
$cache_dir = APP_PATH . 'plugin/xw_douban_import/cache/douban';
!is_dir($cache_dir) AND mkdir($cache_dir, 0755, true);

// 默认配置
$kv = kv_get('xw_douban_import');
empty($kv) AND kv_set('xw_douban_import', array(
	'uid' => 1,					// 默认发帖用户
	'fid' => 0,					// 默认版块（0=未指定，取第一个版块）
	'link_prefix' => '下载链接：',
	'skip_dup' => 1,			// 跳过已成功发布的重复条目
	'merge_same' => 1,			// 同名影片追加到旧帖
));

?>
