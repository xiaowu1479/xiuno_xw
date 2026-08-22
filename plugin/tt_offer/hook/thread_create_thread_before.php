<?php exit;
$offer_num = intval(param('offer_num'));
$offer_status=param('offer_status');
if($offer_num<0){message(-1,'输入数字不合法！不能为负。');die();}
if ($offer_status&& $offer_num>0){
    $set_offer = setting_get('tt_offer');
    if($user[get_credits_name_by_type($set_offer['credits_type'])] - $offer_num <0) { message(-1, '您好，您的余额不足，无法发表该悬赏！<a href="my-credits.htm" target="_blank">点此充值</a>'); die();}
}
?>