<?php exit;
$spay_url = url('thread-sPay-'.$tid);
if (!empty($thread['content_buy_type']) && $thread['content_buy_type'] == '3') {
    $thread['content_buy'] /= 1;
}

if ($route == 'mip') {
    $html_pay = '<strong>您好，本帖含有付费内容，请您点击下方“查看完整版网页”获取！</strong>';
} else {
    $html_pay = '
    <div style="border-radius: 12px; border: 1px solid #ffe58f; background: #fffbe6; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin: 20px 0;">
        <div style="font-weight: bold; font-size: 16px; color: #d48806; margin-bottom: 10px;">
            <i class="icon-shopping-cart" style="color:orange; margin-right: 6px;"></i> '.$conf['sitename'].' - '.lang("purchase").'
        </div>
        <div style="font-size: 14px; color: #874d00; margin-bottom: 15px;">
            '.lang("have_pay").' <strong>'.$thread['content_buy'].lang('credits'.$thread['content_buy_type']).'</strong> '.lang("after_see").'
        </div>
        <div style="text-align: center;">
            <button id="b_p" type="submit" 
                class="btn btn-warning" 
                style="border-radius: 999px; padding: 8px 24px; font-weight: bold; font-size: 15px; color: white;" 
                data-loading-text="'.lang('submiting').'..." 
                data-active="'.url('thread-cPay-'.$tid).'">
                '.lang("purchase").'
            </button>
        </div>
    </div>';
}

$preg_pay = preg_match_all('/\[ttPay\](.*?)\[\/ttPay\]/is', $first['message_fmt'], $array);
$first['purchased'] = '1';
$content_pay = db_find_one('paylist', array('tid' => $tid, 'uid' => $uid, 'type' => 1));
$is_set = 0;

if ($thread['content_buy']) {
    if ($preg_pay) {
        $array_count = count($array[0]);
        for ($i = 0; $i < $array_count; $i++) {
            $a = $array[0][$i];
            $b = '
            <div style="border-radius: 12px; border: 1px solid #b7eb8f; background: #f6ffed; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin: 20px 0;">
                <div style="font-weight: bold; font-size: 16px; color: #389e0d; margin-bottom: 10px;">
                    <i class="icon-shopping-cart" style="color:green; margin-right: 6px;"></i> '.$conf['sitename'].' - '.lang("see_paid").'
                    <div style="float:right;">
                        <a href="'.$spay_url.'" 
                           style="border-radius: 999px; padding: 6px 18px; background: #e6f7ff; color: #1890ff; font-size: 13px; text-decoration: none; border: 1px solid #91d5ff;">
                           查看购买记录
                        </a>
                    </div>
                </div>
                <hr style="margin: 10px 0;">
                <div style="font-size: 14px; color: #262626;">'.$array[1][$i].'</div>
            </div>';

            if ($content_pay || $thread['uid'] == $uid) {
                $first['message_fmt'] = str_replace($a, $b, $first['message_fmt']);
            } else {
                $first['message_fmt'] = str_replace($a, $is_set == 0 ? $html_pay : '', $first['message_fmt']);
                $is_set = 1;
                $first['purchased'] = '0';
            }
        }
    }
} else {
    $first['message_fmt'] = str_replace('[ttPay]', '', $first['message_fmt']);
    $first['message_fmt'] = str_replace('[/ttPay]', '', $first['message_fmt']);
}
?>