<?php
!defined('DEBUG') AND exit('Access Denied');
$gid != 1 AND message(-1, '无权限');
if(!class_exists('ChatroomService', false)) include_once APP_PATH.'plugin/xw_chatroom/model/ChatroomService.php';
class_exists('ChatroomService', false) OR message(-1, '聊天室服务加载失败，请清理 tmp/model.min.php');

$method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';

if($method === 'POST' && !empty($_POST)) {
    csrf_check();
    $a = param('xw_chat_action', '');
    if($a === 'save_settings') {
        // 以 defaults() 为基底再逐项覆盖：setting_set 是「整体覆盖写入」，
        // 这里若手写一份键名，漏掉的项（原先的 heartbeat_interval / online_timeout）会被静默抹掉。
        $s = ChatroomService::defaults();
        $s['enabled'] = param('enabled', 0) ? 1 : 0;
        $s['show_nav'] = param('show_nav', 0) ? 1 : 0;
        $s['msg_max_length'] = max(1, min(5000, intval(param('msg_max_length', 500))));
        $s['msg_interval'] = max(0, min(3600, intval(param('msg_interval', 3))));
        $s['history_limit'] = max(5, min(200, intval(param('history_limit', 50))));
        $s['poll_interval'] = max(1000, min(60000, intval(param('poll_interval', 3000))));
        $s['heartbeat_interval'] = max(5000, min(300000, intval(param('heartbeat_interval', 30000))));
        $s['online_timeout'] = max(30, min(3600, intval(param('online_timeout', 120))));
        $s['url_filter_mode'] = in_array(param('url_filter_mode', 'none'), array('none', 'whitelist', 'blacklist'), true) ? param('url_filter_mode', 'none') : 'none';
        $s['url_whitelist'] = strval(param('url_whitelist', '', FALSE));
        $s['url_blacklist'] = strval(param('url_blacklist', '', FALSE));
        $s['url_replace'] = mb_substr(trim(strval(param('url_replace', '', FALSE))), 0, 64);
        $s['allow_guest_read'] = param('allow_guest_read', 0) ? 1 : 0;
        $s['allow_guest_send'] = param('allow_guest_send', 0) ? 1 : 0;
        ChatroomService::saveSettings($s);
        message(0, '设置已保存');
    }
    if($a === 'create_channel') {
        $r = ChatroomService::createChannel(array(
            'name' => param('name', ''),
            'description' => param('description', ''),
            'slug' => param('slug', ''),
            'sort_order' => param('sort_order', 0),
            'is_default' => param('is_default', 0),
            'status' => param('status', 1),
        ));
        $r['ok'] OR message(-1, $r['message']);
        message(0, '频道已创建');
    }
    if($a === 'update_channel') {
        $id = param('id', 0);
        $r = ChatroomService::updateChannel($id, array(
            'name' => param('name', ''),
            'description' => param('description', ''),
            'slug' => param('slug', ''),
            'sort_order' => param('sort_order', 0),
            'is_default' => param('is_default', 0),
            'status' => param('status', 1),
        ));
        $r['ok'] OR message(-1, $r['message']);
        message(0, '频道已更新');
    }
    if($a === 'delete_channel') {
        $id = param('id', 0);
        $r = ChatroomService::deleteChannel($id);
        $r['ok'] OR message(-1, $r['message']);
        message(0, '频道已删除');
    }
}

$s = ChatroomService::settings();
$channels = ChatroomService::allChannels();
// 后台列表的「在线」也用实时口径，避免和前台显示成两个数字
$chat_online_counts = ChatroomService::getOnlineCounts();
$header['title'] = '聊天室设置';
include _include(APP_PATH.'plugin/xw_chatroom/view/setting.htm');
