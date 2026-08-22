<?php
!defined('DEBUG') AND exit('Forbidden');

//初始化
$kv = array(
    'is_quick_reply_message' => 1,
    'quick_reply_message_default'=>'请选择回复内容',
    'quick_reply_message'=>'哈哈，不错哦！#不错的帖子！#非常棒！！！',
    'post_message_count_by_uid'=>2,
    'post_message_error'=>'回帖数量已超上限，请不要灌水！',
);
setting_set('thread_quick_reply_message', $kv);