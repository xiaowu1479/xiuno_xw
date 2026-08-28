<?php

/*
	豆瓣影视批量导入 - 升级
	清理模板编译缓存；为已有数据库补齐 lock_time 列与 API 配置
*/

!defined('DEBUG') AND exit('Forbidden');

$tablepre = $db->tablepre;

// v1.1: 补齐 lock_time 列（仅对已安装但未更新的老数据库）
if (db_sql_find_one("SHOW TABLES LIKE '{$tablepre}xw_douban_import'")) {
	if (!db_sql_find_one("SHOW COLUMNS FROM `{$tablepre}xw_douban_import` LIKE 'lock_time'")) {
		db_exec("ALTER TABLE `{$tablepre}xw_douban_import` ADD COLUMN `lock_time` int(11) unsigned NOT NULL DEFAULT '0' AFTER `lock_time`");
	}
}

// 补齐 API 配置项（新增 kv 字段保持向后兼容）
$kv = kv_get('xw_douban_import');
if (!is_array($kv)) $kv = array();
$defaults = array(
	'api_enable' => 0,
	'api_token' => '',
	'direct_push' => 0,
	'api_fail_count' => 0,
	'api_fail_time' => 0,
);
$need_save = false;
foreach ($defaults as $k => $v) {
	if (!array_key_exists($k, $kv)) {
		$kv[$k] = $v;
		$need_save = true;
	}
}
// 首次升级时自动生成 token（无需手动点生成）
if ($kv['api_token'] === '') {
	$kv['api_token'] = bin2hex(random_bytes(24));
	$need_save = true;
}
$need_save AND kv_set('xw_douban_import', $kv);

plugin_clear_tmp_dir();

?>
