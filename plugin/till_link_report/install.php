<?php

/*
	链接失效反馈 - 安装
	创建反馈记录表 & 初始化默认配置
*/

!defined('DEBUG') AND exit('Forbidden');

$tablepre = $db->tablepre;

$sql = "CREATE TABLE IF NOT EXISTS {$tablepre}link_report (
	id int(11) unsigned NOT NULL AUTO_INCREMENT,
	tid int(11) unsigned NOT NULL DEFAULT '0',		-- 被反馈的主题ID
	pid int(11) unsigned NOT NULL DEFAULT '0',		-- 首个帖子ID
	uid int(11) unsigned NOT NULL DEFAULT '0',		-- 被反馈的楼主uid
	fromuid int(11) unsigned NOT NULL DEFAULT '0',	-- 反馈人uid
	reason varchar(255) NOT NULL DEFAULT '',		-- 反馈说明(可选)
	create_date int(11) unsigned NOT NULL DEFAULT '0',
	create_ip int(11) unsigned NOT NULL DEFAULT '0',
	PRIMARY KEY (id),
	KEY (tid),
	KEY (fromuid),
	KEY (create_date)
) ENGINE=MyISAM DEFAULT CHARSET=utf8";
$r = db_exec($sql);
$r === FALSE AND message(-1, '创建链接反馈表 link_report 失败');

// 默认配置
$config = array(
	'enable' => 1,					// 启用反馈
	'login_only' => 1,				// 仅登录用户可反馈
	'self_report' => 0,				// 是否允许楼主自己反馈自己帖子
	'cooldown' => 300,				// 同一用户对同一主题的反馈间隔(秒)
	'ip_interval' => 600,			// 同一IP反馈间隔(秒)
	'notice_type' => 156,			// 站内通知类型标识
	'max_per_day' => 50,			// 单用户每日最大反馈次数
	'show_count' => 1,				// 按钮旁是否显示反馈次数
);
setting_set('till_link_report', $config);

?>
