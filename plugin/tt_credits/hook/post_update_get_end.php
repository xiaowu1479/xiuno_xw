<?php exit;
$content_num = !empty($thread['content_buy']) ? $thread['content_buy'] : 0;
$content_type = !empty($thread['content_buy_type']) ? ($thread['content_buy_type']=='0'?'1': $thread['content_buy_type']) : '1';
if($group['allowsell']=="1") {
    $input['content_num_status'] = form_radio_yes_no('content_num_status', $content_num > 0 ? 1 : 0);
}
?>