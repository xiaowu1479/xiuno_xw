<?php

/*

	Xiuno_xw 1.0.0 配置文件 (基于 Xiuno BBS 4.0 二开)
	支持多台 DB，主从配置好以后，xn 会自动根据 SQL 读写分离。
	支持各种 cache，本机 apc/xcache, 网络: redis/memcached/mysql
	支持 CDN，如果前端开启了 CDN 请设置 cdn_on=>1, 否则获取 IP 会不准确 
	支持临时目录设置，独立 Linux 主机，可以设置为 /dev/shm 通过内存加速
*/
return array (
	'db' => array (
		'type' => 'mysql',	
		'mysql' => array (
			'master' => array (
				'host' => 'localhost',
				'user' => 'root',
				'password' => 'root',
				'name' => 'test',
				'tablepre' => 'bbs_',
				'charset' => 'utf8mb4',
				'engine' => 'innodb',
			),
			'slaves' => array (),
		),
		'pdo_mysql' => array (
			'master' => array (
				'host' => 'localhost',
				'user' => 'root',
				'password' => 'root',
				'name' => 'test',
				'tablepre' => 'bbs_',
				'charset' => 'utf8mb4',
				'engine' => 'innodb',
			),
			'slaves' => array (),
		),
	),
	'cache' => array (
		'enable' => true,
		'type' => 'file',	// 可选: redis, memcached, mysql, file, xcache, apc, yac
		'memcached' => array (
			'host' => 'localhost',
			'port' => '11211',
			'cachepre' => 'bbs_',
		),
		'redis' => array (
			'host' => 'localhost',
			'port' => '6379',
			'password' => '',	// Redis 密码 (Redis >= 6.0 ACL)
			'database' => 0,	// Redis 数据库序号
			'timeout' => 1.0,	// 连接超时(秒)
			'cachepre' => 'bbs_',
		),
		'file' => array (
			'path' => './tmp/cache/',	// 可配置为 /dev/shm/ 使用内存加速
			'cachepre' => 'bbs_',
		),
		'xcache' => array (
			'cachepre' => 'bbs_',
		),
		'yac' => array (
			'cachepre' => 'bbs_',
		),
		'apc' => array (
			'cachepre' => 'bbs_',
		),
		'mysql' => array (
			'cachepre' => 'bbs_',
		),
	),
	'tmp_path' => './tmp/',		// 可以配置为 linux 下的 /dev/shm ，通过内存缓存临时文件
	'log_path' => './log/',		// 日志目录
	
	// -------------------> Xiuno_xw 配置

	'view_url' => 'view/',		// 可以配置单独的 CDN 域名：比如：http://static.domain.com/view/
	'upload_url' => 'upload/',	// 可以配置单独的 CDN 域名：比如：http://upload.domain.com/upload/
	'upload_path' => './upload/',	// 物理路径，可以用 NFS 存入到单独的文件服务器
	
	'logo_mobile_url' => 'view/img/logo.png',		// 手机的 LOGO URL
	'logo_pc_url' => 'view/img/logo.png',			// PC 的 LOGO URL
	'logo_water_url' => 'view/img/water-small.png',		// 水印的 LOGO URL
	
	'sitename' => 'Xiuno_xw',
	'sitebrief' => 'Site Brief',
	'timezone' => 'Asia/Shanghai',	// 时区，默认中国
	'lang' => 'zh-cn',
	'runlevel' => 5,		// 0: 站点关闭; 1: 管理员可读写; 2: 会员可读;  3: 会员可读写; 4：所有人只读; 5: 所有人可读写
	'runlevel_reason' => 'The site is under maintenance, please visit later.',
	
	'cookie_domain' => '',
	'cookie_path' => '',
	'auth_key' => 'efdkjfjiiiwurjdmclsldow753jsdj438',
	
	'pagesize' => 20,
	'postlist_pagesize' => 100,
	'cache_thread_list_pages' => 10,
	'online_update_span' => 120,	// 在线更新频度，大站设置的长一些
	'online_hold_time' => 3600,	// 在线的时间
	'session_delay_update' => 1,
	'upload_image_width' => 927,	// 上传图片自动缩略的最大宽度
	'order_default' => 'lastpid',
	'attach_dir_save_rule' => 'Ym',	// 附件存放规则，附件多用：Ymd，附件少：Ym
	
	'update_views_on' => 1,
	'user_create_email_on' => 0,
	'user_create_on' => 1,
	'user_resetpw_on' => 0,
	
	'admin_bind_ip' => 0,		// 后台是否绑定 IP
	
	'cdn_on' => 0,
	
	'loco_pass' => '',		// 火车头采集接口密码，留空则禁用 loco.php
	'token_bind_ip' => 1,		// Token 是否绑定 IP（移动网络下可关闭）
	'token_expire' => 604800,	// Token 有效期（秒），默认 7 天
	
	/* 支持多种 URL 格式：
		0: ?thread-create-1.htm
		1: thread-create-1.htm
		2: ?/thread/create/1  不支持
		3: /thread/create/1   不支持
	*/
	'url_rewrite_on' => 0,
	
	// 禁止插件
	'disabled_plugin' => 0, 
	  
	'version' => '1.0.0',
	'static_version' => '?1.0',
	'installed' => 0,
);
?>