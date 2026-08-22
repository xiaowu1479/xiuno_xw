<?php
/*
 * 奇狐搜索插件LIKE强化版 插件卸载
 * admin/plugin-unstall-fox_search.htm
*/

!defined('DEBUG') AND exit('Forbidden');

kv_delete('search_conf');

$tablepre = $db->tablepre;

$sql = "DROP TABLE IF EXISTS {$tablepre}post_search;";
$r = db_exec($sql);

$sql = "DROP TABLE IF EXISTS {$tablepre}thread_search;";
$r = db_exec($sql);

$r === FALSE AND message(-1, '卸载表失败');
?>