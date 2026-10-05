<?php
!defined('DEBUG') AND exit('Access Denied');
global $db, $conf;

$tablepre = $db->tablepre;

// 频道表
$sql = "CREATE TABLE IF NOT EXISTS {$tablepre}xw_chat_channel (
    id int unsigned NOT NULL AUTO_INCREMENT,
    name varchar(64) NOT NULL DEFAULT '',
    description varchar(255) NOT NULL DEFAULT '',
    slug varchar(64) NOT NULL DEFAULT '',
    sort_order int NOT NULL DEFAULT 0,
    is_default tinyint unsigned NOT NULL DEFAULT 0,
    status tinyint unsigned NOT NULL DEFAULT 1,
    online_count int unsigned NOT NULL DEFAULT 0,
    created int unsigned NOT NULL DEFAULT 0,
    PRIMARY KEY(id),
    UNIQUE KEY slug(slug),
    KEY status_sort(status, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$r = db_exec($sql);
$r === FALSE AND message(-1, '创建 xw_chat_channel 表失败');

// 消息表
$sql = "CREATE TABLE IF NOT EXISTS {$tablepre}xw_chat_message (
    id int unsigned NOT NULL AUTO_INCREMENT,
    channel_id int unsigned NOT NULL DEFAULT 0,
    uid int unsigned NOT NULL DEFAULT 0,
    content text NOT NULL,
    type tinyint unsigned NOT NULL DEFAULT 0,
    ref_channel_id int unsigned NOT NULL DEFAULT 0,
    reply_to int unsigned NOT NULL DEFAULT 0,
    created int unsigned NOT NULL DEFAULT 0,
    PRIMARY KEY(id),
    KEY channel_id_created(channel_id, created),
    KEY uid_created(uid, created)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$r = db_exec($sql);
$r === FALSE AND message(-1, '创建 xw_chat_message 表失败');

// 在线心跳表
$sql = "CREATE TABLE IF NOT EXISTS {$tablepre}xw_chat_online (
    uid int unsigned NOT NULL DEFAULT 0,
    channel_id int unsigned NOT NULL DEFAULT 0,
    last_heartbeat int unsigned NOT NULL DEFAULT 0,
    PRIMARY KEY(uid, channel_id),
    KEY channel_heartbeat(channel_id, last_heartbeat)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$r = db_exec($sql);
$r === FALSE AND message(-1, '创建 xw_chat_online 表失败');

// 已读记录表
$sql = "CREATE TABLE IF NOT EXISTS {$tablepre}xw_chat_read (
    uid int unsigned NOT NULL DEFAULT 0,
    channel_id int unsigned NOT NULL DEFAULT 0,
    last_read_id int unsigned NOT NULL DEFAULT 0,
    PRIMARY KEY(uid, channel_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$r = db_exec($sql);
$r === FALSE AND message(-1, '创建 xw_chat_read 表失败');

// 默认公共频道
$_exist = db_sql_find_one("SELECT id FROM {$tablepre}xw_chat_channel WHERE slug='public'");
if(!$_exist) {
    db_insert('xw_chat_channel', array(
        'name' => '公共频道',
        'description' => '所有人可见的公共聊天频道',
        'slug' => 'public',
        'sort_order' => 0,
        'is_default' => 1,
        'status' => 1,
        'online_count' => 0,
        'created' => time(),
    ));
}

// 默认设置：以 ChatroomService::defaults() 为唯一来源。
// 原先这里手写一份、模型 defaults() 里又写一份，两份会漂移
// （heartbeat_interval / online_timeout 就是因为 defaults() 里没有而被后台保存时抹掉的）。
if(!class_exists('ChatroomService', false)) {
    @include_once APP_PATH.'plugin/xw_chatroom/model/ChatroomService.php';
}
class_exists('ChatroomService', false) OR message(-1, '聊天室服务加载失败，请先清理 tmp/model.min.php');
setting_set('xw_chatroom', ChatroomService::defaults());

// 清理缓存：model.inc.php 记录模型清单，只清 model.min.php 不足以让新增模型生效
if(isset($conf['tmp_path']) && function_exists('xn_unlink')) {
    @xn_unlink($conf['tmp_path'].'model.inc.php');
    @xn_unlink($conf['tmp_path'].'model.min.php');
}
