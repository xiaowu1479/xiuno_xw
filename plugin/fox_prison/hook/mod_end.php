
<?php exit;
elseif($action == 'prison'){
    user_login_check();
    $puid = param(2, 0);
    empty($puid) AND message(-1, '用户ID不能为空！');
    empty($group['allowbanuser']) AND message(-1, lang('insufficient_privilege'));
    $query = user_safe_info(user_read($puid));
    empty($query) AND message(-1, '用户不存在');
    $query['gid'] < 6 AND message(-1, '不允许禁闭管理组，请先调整用户用户组');
    if($method == 'GET'){
        include _include(APP_PATH."plugin/fox_prison/oddfox/template/mod_prison.php");
    }
    elseif($method == 'POST'){
        $message = param('message', '');
        empty($message) AND message(-1, '禁闭理由不能为空！');
        $endtime = param('endtime', 0);
        $timetype = param('timetype', 0);
        empty($endtime) AND message(-1, '请填写时间！');
        empty($timetype) AND message(-1, '请选择时间类型！');
        fox_prison_create($puid, $uid, $endtime, $timetype, $message);
    }else{
        message(-1, 'Access Denied.');
    }
}
elseif($action == 'openuser'){
    user_login_check();
    empty($group['allowbanuser']) AND message(-1, lang('insufficient_privilege'));
    $act = param(2, '');
    if($act == 'all'){
        if($method == 'GET') {
            include _include(APP_PATH."plugin/fox_prison/oddfox/template/mod_openuser.php");
        }
        elseif($method == 'POST'){
            $idsarr = param('idsarr', array(0));
            empty($idsarr) AND message(-1, '请至少选择一个用户');
            if (is_array($idsarr)){
                foreach ($idsarr as $id){
                    $u = user_read_cache($id);
                    if(empty($u)) continue;
                    if($u['gid'] < 6) continue;
                    fox_prison_update($id);
                }
            }
            message(0, '释放成功');
        }
    }else{
        if($method == 'POST'){
            $puid = param(2, 0);
            $query = user_safe_info(user_read($puid));
            empty($query) AND message(-1, '用户不存在');
            $query['gid'] < 6 AND message(-1, '您无权处理该用户组！');
            $r = fox_prison_update($puid);
            $r === FALSE AND message(-1, '释放失败');
            message(0, '释放成功');
        }else{
            message(-1, 'Access Denied.');
        }
    }
}
elseif($action == 'delusers'){
    user_login_check();
    empty($group['allowdeleteuser']) AND message(-1, lang('insufficient_delete_user_privilege'));
    
    if($method == 'GET') {
        include _include(APP_PATH."plugin/fox_prison/oddfox/template/mod_deluser.php");
    }
    elseif($method == 'POST'){
        $idsarr = param('idsarr', array(0));
        empty($idsarr) AND message(-1, '请至少选择一个用户');
        if(is_array($idsarr)) {
            foreach ($idsarr as $id){
                $u = user_read($id);
                if(empty($u)) continue;
                if($u['gid'] < 6) continue;
                user_delete($id);
            }
        }
        message(0, '枪毙完成');
    }
}
?>
