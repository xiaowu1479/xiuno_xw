<?php exit;
if($group['allowsell']=="1" && !empty($tid) && is_numeric($tid)) {
    $content_num_status = param('content_num_status');
    $content_num = param('content_num');
    $content_type_name = param('content_type');
    
    // 严格验证输入
    if(!is_numeric($content_num) || $content_num < 0 || $content_num > 999999) {
        $content_num = 0; // 无效价格设为免费
    } else {
        $content_num = intval($content_num);
    }
    
    $content_type = credits_get_content_type_by_name($content_type_name);
    
    // 验证积分类型
    if(!in_array($content_type, array('1', '2', '3'))) {
        $content_type = '1'; // 默认为经验
    }
    
    if ($content_num_status && $content_num > 0) {
        db_update('thread', array('tid' => intval($tid)), array(
            'content_buy' => $content_num, 
            'content_buy_type' => $content_type
        ));
    } else {
        // 确保免费内容的设置
        db_update('thread', array('tid' => intval($tid)), array(
            'content_buy' => 0, 
            'content_buy_type' => $content_type
        ));
    }
}
$update_array = array();
if((($add_credit==1)||($add_credit==0&& $credits<0))&&$credits!=0) $update_array['credits+']=$credits;
if((($add_credit==1)||($add_credit==0&& $golds<0))&&$golds!=0) $update_array['golds+']=$golds;
if((($add_credit==1)||($add_credit==0&& $rmbs<0))&&$rmbs!=0) $update_array['rmbs+']=$rmbs;
$uid AND $update_array AND user_update($uid, $update_array);
$uid AND $update_array AND $user['gid']>=100 AND user_update_group($uid);
$message = '';
isset($update_array['credits+']) AND $message .= lang('credits1').$credits_op.$credits.' ' ;
isset($update_array['golds+']) AND $message .= lang('credits2').$golds_op.$golds.' ' ;
isset($update_array['rmbs+']) AND $message .= lang('credits3').$rmbs_op.$rmbs ;
message(0, lang('create_thread_sucessfully').' '.$message);
?>