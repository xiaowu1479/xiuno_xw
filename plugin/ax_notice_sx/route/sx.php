<?php

!defined('DEBUG') and exit('Access Denied.');

$header['title'] = '发送私信 - 站内信';

user_login_check();

if ($method == 'GET') {

    // 聊天对象：优先 URL uid，其次 to_uid (XIUNO XW)
    $to_uid = intval(param('uid', param('to_uid', 0)));
    $to_user = $to_uid ? user_read($to_uid) : array();

    // 读取与对方的私信历史（notice type=7，双方往来，时间正序）
    $chatlist = array();
    if ($to_uid && $to_user) {
        $tablepre = $db->tablepre;
        $sql = "SELECT * FROM {$tablepre}notice WHERE type=7 AND ((fromuid=$uid AND recvuid=$to_uid) OR (fromuid=$to_uid AND recvuid=$uid)) ORDER BY create_date ASC LIMIT 200";
        $chatlist = db_sql_find($sql);
        if ($chatlist) {
            foreach ($chatlist as &$_chat) {
                $_chat['is_mine'] = ($_chat['fromuid'] == $uid) ? 1 : 0;
                $from_user = user_read_cache($_chat['fromuid']);
                $_chat['from_username'] = empty($from_user['username']) ? '' : $from_user['username'];
                // 提取私信正文（notice message 结构为 comment-info + single-comment）
                $m = preg_match('#<div class=["\']single-comment["\']>(.*?)</div>#is', $_chat['message'], $mm);
                $_chat['content'] = $m ? trim(strip_tags($mm[1])) : trim(strip_tags($_chat['message']));
                $_chat['date_fmt'] = date('m-d H:i', $_chat['create_date']);
            }
            unset($_chat);
        }
    }

    include _include(APP_PATH.'plugin/ax_notice_sx/htm/sx.htm');

} else {

    $to_uid = param('to_uid', '');
    if (empty($to_uid)) {
        message(1, jump('用户不能为空', 'back'));
    }

    $ax_message = param('ax_message', '');
    if (empty($ax_message)) {
        message(1, jump('发送信息不能为空', 'back'));
    }

    $to_user = user_read($to_uid);
    if (empty($to_user)) {
        message(1, jump('用户不存在，请确认后重试', 'back'));
    }

    if($uid == $to_uid) {
        message(1, jump('不能给自己发私信', 'back'));
    }

    $message = "<div class='comment-info'>发来了消息</div><div class='single-comment'>".htmlspecialchars($ax_message, ENT_QUOTES, 'UTF-8')."</div>";

    $nid = notice_send($uid, $to_uid, $message, 7); 
    
    if($nid === FALSE) {
        message(1, jump('发送私信失败，请重试', 'back'));
    }
    
    message(0, '发送私信成功');

}

?>
