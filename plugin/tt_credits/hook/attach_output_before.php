if(!empty($thread['content_buy'])) {
// hook credits_buy_check_start.php
    $content_pay = db_find_one('paylist', array('tid' => $tid, 'uid' => $uid, 'type' => 1));
    if ((!$content_pay) && ($thread['uid'] != $uid)) { message(-1, lang('cannot_down')); die(); }
// hook credits_buy_check_after.php
}
$set = setting_get('tt_credits');
$credits = $set['down_exp'];
$credits_op = $credits > 0 ? '+' : '';
$golds = $set['down_gold'];
$golds_op = $golds > 0 ? '+' : '';
$rmbs = $set['down_rmb'];
$rmbs_op = $rmbs > 0 ? '+' : '';
$user = user_read($uid);
if ((empty($user)) && ($credits != 0 || $golds != 0 || $rmbs != 0)) {
    message(-1, lang('insufficient_privilege_to_download'));
}
// hook credits_down_check_start.php
if (!empty($user)) {
    if (($credits < 0 && ($user['credits'] + $credits < 0)) || ($golds < 0 && ($user['golds'] + $golds < 0)) || ($rmbs < 0 && ($user['rmbs'] + $rmbs < 0))) {
        message(-1, jump(lang('credit_no_enough'),url('my-credits'),2));
        die(); }
    $uid AND user_update($uid, array('credits+' => $credits, 'golds+' => $golds, 'rmbs+' => $rmbs));
    $uid AND user_update_group($uid);
    
    // 购买付费资源后通知卖家（帖子作者）
    if (isset($set['attach_buy_push']) && $set['attach_buy_push'] == '1' && ($credits < 0 || $golds < 0 || $rmbs < 0) && $uid && $thread['uid'] != $uid) {
        if (function_exists('notice_send')) {
            $attach_name = htmlspecialchars($attach['orgfilename']);
            $thread_title = htmlspecialchars($thread['subject']);
            $user_info = user_read($uid);
            $username = htmlspecialchars($user_info['username']);
            $message = "{$username}购买了您主题【{$thread_title}】中的付费附件【{$attach_name}】！";
            notice_send($uid, $thread['uid'], $message, 1001);
        }
    }
}
// hook credits_down_check_after.php