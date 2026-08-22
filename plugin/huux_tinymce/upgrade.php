<?php
/*
	插件升级文件
*/
!defined('DEBUG') AND exit('Forbidden');

// 清理缓存和目录
cache_truncate();
$runtime = NULL; // 清空
rmdir_recusive($conf['tmp_path'], 1);

?>