//判断快速回复限制
$thread_quick_reply_message_config = setting_get('thread_quick_reply_message');
if( $thread_quick_reply_message_config['is_quick_reply_message'] && $uid ){
    $post_message_count_by_uid  =   (int)$thread_quick_reply_message_config['post_message_count_by_uid'];
    
    //帖子回复数大于限制数才需要判断
    if( $thread['posts'] >= $post_message_count_by_uid ){
    
        $postlist_by_uid = post_find(array('tid'=>$thread['tid'],'uid'=>$uid),array(),1,$post_message_count_by_uid);
        
        if(  count($postlist_by_uid) == $post_message_count_by_uid ){
            message('message', $thread_quick_reply_message_config['post_message_error']);
        }
    }
}
