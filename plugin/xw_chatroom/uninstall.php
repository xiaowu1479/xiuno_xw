<?php
!defined('DEBUG') AND exit('Access Denied');
global $db, $conf;

$tablepre = $db->tablepre;

// 删除表
db_exec("DROP TABLE IF EXISTS {$tablepre}xw_chat_channel");
db_exec("DROP TABLE IF EXISTS {$tablepre}xw_chat_message");
db_exec("DROP TABLE IF EXISTS {$tablepre}xw_chat_online");
db_exec("DROP TABLE IF EXISTS {$tablepre}xw_chat_read");

// 删除设置
setting_delete('xw_chatroom');

// 清理缓存
if(isset($conf['tmp_path']) && function_exists('xn_unlink')) {
    @xn_unlink($conf['tmp_path'].'model.min.php');
}
