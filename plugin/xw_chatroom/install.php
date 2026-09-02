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

// 默认设置
setting_set('xw_chatroom', array(
    'enabled' => 1,
    'show_nav' => 1,
    'msg_max_length' => 500,
    'msg_interval' => 3,
    'history_limit' => 50,
    'poll_interval' => 3000,
    'heartbeat_interval' => 30000,
    'online_timeout' => 120,
    'url_filter_mode' => 'none',
    'url_whitelist' => '',
    'url_blacklist' => '',
    'url_replace' => '[链接已屏蔽]',
    'allow_guest_read' => 1,
    'allow_guest_send' => 0,
));

// 清理缓存
if(isset($conf['tmp_path']) && function_exists('xn_unlink')) {
    @xn_unlink($conf['tmp_path'].'model.min.php');
}
