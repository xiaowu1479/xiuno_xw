$set_check = setting_get('tt_check');
if($set_check['user_check']=='1' && (!isset($user['OK']) || $user['OK']!='1'))
{message(-1, '您需要等待管理员审核后,才能发表评论!'); die();}