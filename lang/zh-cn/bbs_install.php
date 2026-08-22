<?php

return array(
	'installed_tips' => '程序已经安装过了，如需重新安装，请删除 conf/conf.php ！',
	'please_set_conf_file_writable' => '请设置 conf/conf.php 文件为可写！',
	'evn_not_support_php_mysql' => '当前 PHP 环境不支持 mysql 和 pdo_mysql，无法继续安装。',
	'dbhost_is_empty' => '数据库主机不能为空',
	'dbname_is_empty' => '数据库名不能为空',
	'dbuser_is_empty' => '用户名不能为空',
	'adminuser_is_empty' => '管理员用户名不能为空',
	'adminpass_is_empty' => '管理员密码不能为空',
	'adminpass_confirm_is_empty' => '请再次输入管理员密码',
	'adminpass_not_match' => '两次输入的密码不一致',
	'conguralation_installed' => '恭喜，安装成功！为了安全请删除 install 目录。',
	
	'step_1_title' => '一、安装环境检测',
	'runtime_env_check' => '网站运行环境检测',
	'required' => '需要',
	'current' => '当前',
	'check_result' => '检测结果',
	'passed' => '通过',
	'not_passed' => '通过',
	'not_the_best' => '不是最理想的环境',
	'dir_writable_check' => '目录 / 文件 权限检测',
	'writable' => '可写',
	'unwritable' => '不可写',
	'check_again' => '重新检测',
	'os' => '操作系统',
	'unix_like' => '类 UNIX',
	'php_version' => 'PHP 版本',
	
	'step_2_title' => '二、数据库设置',
	'db_type' => '数据库类型',
	'db_engine' => '数据库引擎',
	'db_host' => '数据库服务器',
	'db_name' => '数据库名',
	'db_user' => '数据库用户名',
	'db_pass' => '数据库密码',
	'step_3_title' => '三、管理员信息',
	'admin_email' => '管理员邮箱',
	'admin_username' => '管理员用户名',
	'admin_pw' => '管理员密码',
	'installing_about_moment' => '正在安装，大概需要一分钟左右',
	'license_title' => 'XIUNO XW 1.1.1 中文版授权协议',
	'license_content' => '感谢您选择 XIUNO XW 1.1.1，它是一款基于 Xiuno BBS 4.0.4 二次开发的轻论坛，国产、小巧、稳定、支持在大数据量下仍然保持高负载能力。它只有 20 多个表，源代码压缩后 1M 左右，运行速度非常快，处理单次请求在 0.01 秒级别，全面支持 PHP 8.x，对第三方类库依赖极少，仅仅前端依赖 jquery.js，方便部署和维护，是一个非常好的二次开发的基石。

XIUNO XW 1.1.1 全面兼容 PHP 8.0 - 8.4，修复了 SMTP 邮件系统，升级了缓存机制，新增文件缓存驱动，支持 Redis 密码认证等现代特性。

XIUNO XW 基于 Xiuno BBS 4.0.4 (MIT 协议) 二次开发，您可以自由修改、派生版本、商用而不用担心任何法律风险（修改后应保留原来的版权信息）。',
	'license_date' => '最后更新：2026年8月21日',
	'agree_license_to_continue' => '同意协议继续安装',
	'install_title' => 'XIUNO XW 1.1.1 安装向导',
	'install_guide' => '安装向导',
	
	'function_check' => '函数依赖检查',
	'supported' => '支持',
	'not_supported' => '不支持',
	'function_glob_not_exists' => '后台插件功能依赖该函数，请配置 php.ini，设置 disabled_functions = ; 去除对该函数的限制',
	'function_gzcompress_not_exists' => '后台插件功能依赖该函数，Linux 主机请添加编译参数 --with-zlib，Windows 主机请配置 php.ini 注释掉 extension=php_zlib.dll',
	'function_mb_substr_not_exists' => '系统依赖该函数，请检查 php.ini，取消注释 extension=mbstring 或 extension=php_mbstring.dll 前面的分号，然后重启 Web 服务器',
	
	// hook lang_zh_cn_bbs_install.php

);

?>