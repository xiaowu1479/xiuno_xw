<?php
/*
 * 奇狐插件 卸载文件
 * QQ:77798085
 */

!defined('DEBUG') AND exit('Forbidden');
$output = kv_get('fox_tag');

if(!empty($output['tag_retain'])){
    $tablepre = $db->tablepre;
    db_exec("ALTER TABLE `{$tablepre}group` DROP COLUMN `allowtags`;");
    db_exec("ALTER TABLE `{$tablepre}group` DROP COLUMN `allowtags2`;");
    db_exec("ALTER TABLE `{$tablepre}group` DROP COLUMN `allowtags3`;");
    db_exec("ALTER TABLE `{$tablepre}thread` DROP COLUMN `keywords`;");
    db_exec("DROP TABLE IF EXISTS `{$tablepre}fox_tag`;");
    db_exec("DROP TABLE IF EXISTS `{$tablepre}fox_tag_data`;");
    db_exec("DROP TABLE IF EXISTS `{$tablepre}fox_tag_follow`;");
    group_list_cache_delete();
    kv_cache_delete('fox_tag');
}
?>