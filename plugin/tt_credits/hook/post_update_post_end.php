<?php exit;
if($group['allowsell']=="1" && $isfirst && !empty($tid) && is_numeric($tid)) {
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
?>