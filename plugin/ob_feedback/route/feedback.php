<?php

!defined('DEBUG') and exit('Access Denied.');

$header['title'] = '发送反馈';

if ($method == 'GET') {

    include _include(APP_PATH . 'plugin/ob_feedback/htm/feedback.htm');
} else {

    $tablepre = $db->tablepre;

    $to_uid = param('to_uid', '');
    $ob_tid = param('ob_tid', '');
    $ob_pid = param('ob_pid', '');
    $ob_isfirst = param('ob_isfirst', 1);
    $ob_thread_subject = param('ob_thread_subject', '');
    $ob_feedback_type = param('ob_feedback_type', '');
    $ob_message = param('ob_message', '');
    $ob_excerpt = param('ob_excerpt', '');

    $ob_thread_url = url('thread-' . $ob_tid);
    $ob_thread = '<a target="_blank" href="' . $ob_thread_url . '">' . $ob_thread_subject . '</a>';

    if (empty($to_uid)) {
        message(2, '要举报的用户不能为空');
    }

    if (empty($ob_message) && $ob_feedback_type == "其他") {
        message(1, '发送内容不能为空');
    }

    $to_user = user_read_cache($to_uid);
    if (empty($to_user)) {
        message(3, '要举报的用户不存在，请确认后重试');
    }

    $message = '<b class="badge bg-danger">举报</b> '
        . (intval($ob_isfirst) === 1
            ? '<span class="badge bg-label-primary">' . lang('thread') . '</span>'
            : '<span class="badge bg-label-secondary">' . lang('post') . '</span>')
        . '<span class="badge bg-warning">' . $ob_feedback_type . '</span>'
        . '<div class="quote-comment">'
        . $ob_thread
        . '<blockquote>' . $ob_excerpt . '...</blockquote>'
        . '</div>'
        . '<div class="reply-comment">' . $ob_message . '</div>'
        . '<a href="' . url('my-feedback_unsolved') . '" class="btn btn-primary">立即处理</a>';


    //查找管理员和超版，发送一份反馈
    $ob_gid = db_sql_find("SELECT * FROM {$tablepre}user WHERE gid = '1' or gid = '2'");
    if (!empty($ob_gid)) {
        foreach ($ob_gid as $u) {
            notice_send($uid, $u['uid'], $message, 66);
        }
    }

    //判断用户id，不是管理员就发出反馈，防止重复发送
    /*
    $ob_uid = user__read($to_uid);
    if (
        !empty($ob_uid)
        && ($ob_uid['gid'] != 1 && $ob_uid['gid'] != 2)
        && $ob_feedback_type != "侵权投诉"
    ) {
        notice_send($uid, $to_uid, $message, 66);
    }
    */
    db_insert('ob_feedback', [
        'from_uid' => $uid,
        'to_uid' => $to_uid,
        'tid' => $ob_tid,
        'pid' => $ob_pid,
        'create_date' => time(),
        'solved_date' => 0,
        'isfirst' => $ob_isfirst,
        'feedback_type' => $ob_feedback_type,
        'thread_subject' => $ob_thread_subject,
        'message' => $ob_message,
        'excerpt' => $ob_excerpt,
        'status' => 0,
        'solved_by_uid' => 0,
    ]);

    message(0, '发送成功');
}
