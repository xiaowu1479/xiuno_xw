
if(isset($light_config['user_bg_switch']) && $light_config['user_bg_switch'] == 1){
 if($action === 'background'){
	if($method == 'GET'){
		include _include(APP_PATH.'plugin/zaesky_theme_light/view/htm/my_background.htm');
	}else if($method == 'POST'){
    $imgsrc = param('imgsrc');
	if($imgsrc > 0 && $imgsrc <= 9) {
		user_update($uid, array('bgimg'=>$imgsrc));
		message(0, lang('change_bg_success'));
	}else{
		message(0, lang('change_bg_fail'));
	}
}
}
}

if(empty($user)) {
    message(-1, '请先登录！');
} else {
    $user_id = $user['uid'];
    $current_date = time();
    $key = "user_likes:$user_id";
    $total_key = "total_likes";

    if ($action === 'posttest' && $method === 'POST') {
        $like_count = kv_cache_get($key);
        if ($like_count === false) {
            $like_count = db_find_one('site_likes', array('user_id'=>$user_id, 'like_date'=>$current_date));
            $like_count = $like_count ? $like_count['like_count'] : 0;
            kv_cache_set($key, $like_count);
        }

        if ($like_count >= 10) {
            message(-1, '您今天的点赞次数已达到上限！');
        }

        $like_count++;
        kv_cache_set($key, $like_count);
        db_insert('site_likes', array('user_id'=>$user_id, 'like_date'=>$current_date, 'like_count'=>$like_count));

        $total_like_count = kv_cache_get($total_key);
        if ($total_like_count === false) {
            $total_like_count = db_find_one('site_total_likes', array(), array('order'=>'id DESC'));
            $total_like_count = $total_like_count ? $total_like_count['total_like_count'] : 0;
            kv_cache_set($total_key, $total_like_count);
        }

        $total_like_count++;
        kv_cache_set($total_key, $total_like_count);
        db_delete('site_total_likes', array());
        db_insert('site_total_likes', array('total_like_count'=>$total_like_count));

        message(0, "点赞成功！您今天已经点赞了{$like_count}次。总共点赞数为{$total_like_count}次。");
    }
}
