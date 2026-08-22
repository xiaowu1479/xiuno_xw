<?php 
    !defined('DEBUG') AND exit('Access Denied.');
    if($method == 'GET') {
        $header['title'] = '设置禁止邮箱注册';
        $header['mobile_title'] = '设置禁止邮箱注册';
        $conf_email = include APP_PATH."plugin/lee_not_email/conf.php";
        $input['b_or_w'] = form_radio('b_or_w',array('b'=>'黑名单','w'=>'白名单','n'=>'关闭'),$conf_email['b_or_w']);
        $input['black_list'] = form_textarea('black_list', $conf_email['black_list'],'100%',100);
        $input['black_tips'] = form_text('black_tips',$conf_email['black_tips']);
        $input['white_list'] = form_textarea('white_list', $conf_email['white_list'],'100%',100);
        $input['white_tips'] = form_text('white_tips',$conf_email['white_tips']);
        include _include(APP_PATH."/plugin/lee_not_email/setting.htm");
    } else {
        $b_or_w = param('b_or_w');
        $black_list = param('black_list');
        $black_tips = param('black_tips');
        $white_list = param('white_list');
        $white_tips = param('white_tips');
        $email_arr = [
            'b_or_w' =>$b_or_w,
            'black_list' => $black_list,
            'black_tips' => $black_tips,
            'white_list' => $white_list,
            'white_tips' => $white_tips
        ];
    $r = file_put_contents_try(APP_PATH.'plugin/lee_not_email/conf.php', "<?php\r\nreturn ".var_export($email_arr,true).";\r\n?>");
    !$r AND message(-1, '保存失败');
    message(0,'保存成功');
}
?>