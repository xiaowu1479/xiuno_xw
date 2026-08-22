<?php
/**
 * 转载标注插件安装文件
 *
 * @create 2020-03-17
 * @author 成都威尔德 https://www.werde.cn
 */ 
!defined('DEBUG') and exit('Forbidden');
$tablepre = $db->tablepre;
!db_sql_find_one("SHOW COLUMNS FROM {$tablepre}post LIKE 'source'") AND db_exec("ALTER TABLE {$tablepre}post ADD COLUMN source VARCHAR(255) NOT NULL DEFAULT '' COMMENT '转载出处'");
!db_sql_find_one("SHOW COLUMNS FROM {$tablepre}thread LIKE 'thumbnail'") AND db_exec("ALTER TABLE {$tablepre}thread ADD COLUMN thumbnail VARCHAR(980) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT '0' COMMENT '文章缩略图'");

$get_signature = kv_get('post_source');
if (!$get_signature) { $get_signature = array('position'=>'1', 'html'=>'1', 'characters'=>'120', 'report'=>'https://www.werde.cn/'); kv_set('post_source', $get_signature); }