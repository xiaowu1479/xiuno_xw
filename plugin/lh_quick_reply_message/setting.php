<?php

!defined('DEBUG') and exit('Access Denied.');


if ($method == 'GET') {
	
	$thread_quick_reply_message_config = setting_get('thread_quick_reply_message');
        $input['quick_reply_message_default'] = form_text('quick_reply_message_default',$thread_quick_reply_message_config['quick_reply_message_default']);
        $input['post_message_count_by_uid'] = form_text('post_message_count_by_uid',$thread_quick_reply_message_config['post_message_count_by_uid']);
        $input['post_message_error'] = form_text('post_message_error',$thread_quick_reply_message_config['post_message_error']);
        $input['is_quick_reply_message'] = form_radio_yes_no('is_quick_reply_message',$thread_quick_reply_message_config['is_quick_reply_message']);
        $input['quick_reply_message'] = form_textarea('quick_reply_message',$thread_quick_reply_message_config['quick_reply_message'], '100%', 200);
	
	include _include(APP_PATH.'plugin/lh_quick_reply_message/setting.htm');
	
} else {
	$thread_quick_reply_message_config['is_quick_reply_message'] = param('is_quick_reply_message',0);
        $thread_quick_reply_message_config['quick_reply_message_default'] = param('quick_reply_message_default','');
        $thread_quick_reply_message_config['quick_reply_message'] = param('quick_reply_message','');
        $thread_quick_reply_message_config['post_message_error'] = param('post_message_error','');
        $thread_quick_reply_message_config['post_message_count_by_uid'] = param('post_message_count_by_uid',0);
        
        if( empty($thread_quick_reply_message_config['quick_reply_message_default']) ){
            message(1, '请填写默认内容！');
        }elseif( empty($thread_quick_reply_message_config['post_message_error']) ){
            message(1, '请填写超出限制提示语！');
        }elseif( intval($thread_quick_reply_message_config['post_message_count_by_uid']) == 0 ){
            message(1, '请填写每个帖子某用户最多回复数！');
        }
        
        setting_set('thread_quick_reply_message',$thread_quick_reply_message_config);
        
        message(0, '保存成功！');
}

?>