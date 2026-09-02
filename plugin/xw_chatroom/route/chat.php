<?php
!defined('DEBUG') AND exit('Access Denied');
if(!class_exists('ChatroomService', false)) include_once APP_PATH.'plugin/xw_chatroom/model/ChatroomService.php';
class_exists('ChatroomService', false) OR message(-1, '聊天室服务加载失败，请清理 tmp/model.min.php');

$s = ChatroomService::settings();
$action = param(1, '');
$method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';

// 接口：轮询新消息
if($action === 'messages') {
    $channelId = param(2, 0);
    $lastId = param(3, 0);
    $ch = ChatroomService::channelById($channelId);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    if(!$ch || intval($ch['status']) !== 1) {
        echo xn_json_encode(array('code' => 1, 'message' => '频道不存在'));
        exit;
    }
    if(empty($s['allow_guest_read']) && $uid <= 0) {
        echo xn_json_encode(array('code' => 0, 'messages' => array()));
        exit;
    }
    $limit = intval($s['history_limit']);
    $list = ChatroomService::messages($channelId, $lastId, $limit);
    echo xn_json_encode(array('code' => 0, 'messages' => $list));
    exit;
}

// POST：发送消息
if($method === 'POST' && $action === 'send') {
    csrf_check();
    $channelId = param(2, 0);
    $content = trim(strip_tags(strval(param('content', '', FALSE))));
    $r = ChatroomService::sendMessage($channelId, $uid, $content);
    header('Content-Type: application/json; charset=utf-8');
    if(!$r['ok']) {
        echo xn_json_encode(array('code' => 1, 'message' => $r['message']));
        exit;
    }
    echo xn_json_encode(array('code' => 0, 'message' => '已发送', 'id' => intval($r['id'])));
    exit;
}

// POST：分享频道
if($method === 'POST' && $action === 'share') {
    csrf_check();
    $from = param('from_channel_id', 0);
    $to = param('to_channel_id', 0);
    $r = ChatroomService::shareChannel($from, $to, $uid);
    header('Content-Type: application/json; charset=utf-8');
    if(!$r['ok']) {
        echo xn_json_encode(array('code' => 1, 'message' => $r['message']));
        exit;
    }
    echo xn_json_encode(array('code' => 0, 'message' => '已分享'));
    exit;
}

// POST：心跳
if($method === 'POST' && $action === 'heartbeat') {
    $channelId = param(2, 0);
    $r = ChatroomService::heartbeat($channelId, $uid);
    header('Content-Type: application/json; charset=utf-8');
    echo xn_json_encode($r);
    exit;
}

// POST：更新已读
if($method === 'POST' && $action === 'read') {
    $channelId = param(2, 0);
    $lastReadId = param(3, 0);
    $r = ChatroomService::updateRead($channelId, $uid, $lastReadId);
    header('Content-Type: application/json; charset=utf-8');
    echo xn_json_encode($r);
    exit;
}

// GET：获取在线用户
if($action === 'online') {
    $channelId = param(2, 0);
    $list = ChatroomService::getOnlineUsers($channelId);
    header('Content-Type: application/json; charset=utf-8');
    echo xn_json_encode(array('code' => 0, 'users' => $list));
    exit;
}

// 显示聊天室页面
if($action === '') {
    $ch = ChatroomService::defaultChannel();
} else {
    $ch = ChatroomService::channelBySlug($action);
    if(!$ch) $ch = ChatroomService::defaultChannel();
}
if(!$ch) message(-1, '聊天室尚未创建任何频道');
if(empty($s['allow_guest_read']) && $uid <= 0) {
    user_login_check();
}
// 设置 BASE_HREF 让 header 模板中的相对链接(登录/注册等)正确指向站点根目录
!defined('BASE_HREF') AND define('BASE_HREF', './');
$channels = ChatroomService::channels();
$chat_last_id = 0;
$chat_init_messages = ChatroomService::messages(intval($ch['id']), 0, intval($s['history_limit']));
foreach($chat_init_messages as $m) {
    if(intval($m['id']) > $chat_last_id) $chat_last_id = intval($m['id']);
}
// 初始在线数（传给模板立即显示，不等 JS fetch）
$chat_current_online = ChatroomService::getOnlineCount(intval($ch['id']));
$header['title'] = '聊天室';
$header['mobile_title'] = '聊天室';
include _include(APP_PATH.'plugin/xw_chatroom/view/chat.htm');
