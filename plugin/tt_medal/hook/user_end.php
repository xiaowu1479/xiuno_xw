<?php exit;
if($action=='medal')
    if($method=='GET') {
        $_uid = param(2, 0);
        empty($_uid) AND $_uid = $uid;
        $_user = user_read($_uid);

        empty($_user) AND message(-1, lang('user_not_exists'));
        $header['title'] = $_user['username'];
        $header['mobile_title'] = $_user['username'];
        include _include(APP_PATH . 'plugin/tt_medal/view/htm/user_medal.htm');
    }
?>