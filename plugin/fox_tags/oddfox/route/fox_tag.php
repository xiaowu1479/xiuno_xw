<?php 
!defined('DEBUG') AND exit('Access Denied.');
$action = param(1, '');
if($action != 'list' && $action != 'edit' && $action != 'cover' && $action != 'follow' && $action != 'ajax'){
    $action = param(1, 0);
    if(empty($action)){
        exit('Access Denied.');
    }
    elseif(is_numeric($action)){
        $action = 'default';
    }
}
if($action == 'default'){
    if($method != 'GET'){
        exit('Access Denied.');
    }
    $orderby = param('orderby');
    !in_array($orderby, array('tid', 'lastpid')) AND $orderby = 'lastpid';
    $tagid = param(1, 0);
    empty($tagid) AND message(-1, 'TAG不存在');
    global $conf, $db;
    $tablepre = $db->tablepre;
    $n = db_count('fox_tag_data', array('tagid'=>$tagid));
    empty($n) AND message(-1, 'TAG不存在');
    $page = param(2, 1);
    empty($page) AND $page = 1;
    $pagesize = $conf['pagesize'];
    $tag_tid_list = db_find('fox_tag_data', array('tagid'=>$tagid), array('tid'=>-1), 1, 99999, '', array('tid'));
    empty($tag_tid_list) AND message(-1, 'TAG不存在');
    $tag_tids = arrlist_values($tag_tid_list, 'tid');
    
    $threadlist = thread_find_by_tids($tag_tids, array($orderby=>1));
    $total_tids = count($threadlist);
    $threadlist = arrlist_cond_orderby($threadlist, array(), array($orderby=>-1), $page, $pagesize);
    empty($threadlist) AND message(-1, 'TAG不存在');
    $pagination = pagination(url("tag-$tagid-{page}"), $total_tids, $page, $pagesize);

    $query = db_find_one('fox_tag', array('tagid'=>$tagid));
    empty($query) AND message(-1, 'TAG不存在');
    
    $is_follow = 0;
    if(!empty($user)){
        $res = db_find('fox_tag_follow', array('uid'=>$uid, 'tagid'=>$tagid));
        !empty($res) AND $is_follow = 1;
    }

    $tagname = !empty($query['name']) ? $query['name'] : '';
    $header['title'] = $tagname . '的相关贴子_' .$conf['sitename'];
    $header['keywords'] = $tagname;
    $header['description'] = $tagname . '的相关贴子';
    
    !empty($query['subject']) AND $header['title'] = $query['subject'];
    !empty($query['keywords']) AND $header['keywords'] = $query['keywords'];
    !empty($query['brief']) AND $header['description'] = $query['brief'];
    include _include(APP_PATH.'plugin/fox_tags/oddfox/template/fox_tag_show.php');
    exit;
}
elseif($action == 'list'){
    if($method != 'GET'){
        exit('Access Denied.');
    }
    $page = param(2, 0);
    empty($page) AND $page = 1;

    global $conf, $db;
    $tablepre = $db->tablepre;
    $n = db_count('fox_tag');
    empty($n) AND message(-1, 'TAG不存在');

    $tag_list = isset($fox_tag_kv['tag_list']) ? $fox_tag_kv['tag_list'] : 200;
    $pagesize = $tag_list;
    $start = ($page - 1) * $pagesize;    
    $fox_tag_list = db_sql_find("SELECT *, COUNT(DISTINCT tid) as num FROM `{$tablepre}fox_tag_data` GROUP BY `tagid` ORDER BY COUNT(DISTINCT tid) DESC LIMIT $start, $pagesize");
    foreach($fox_tag_list as &$val) {
        $_fox_tag = db_find_one('fox_tag', array('tagid'=>$val['tagid']));
        $q_thread = db_find_one('thread', array('tid' => $val['tid']), array(), array('subject'));
        $val['subject'] = $q_thread['subject'];
        $val['name'] = $_fox_tag['name'];
    }
    unset($val);
    empty($fox_tag_list) AND message(-1, 'TAG分页不存在');
    
    $pagination = pagination(url("tag-list-{page}"), $n, $page, $pagesize);
    $header['title'] = '标签列表_' . $conf['sitename'];
    $header['description'] = $conf['sitename'] . '标签列表';
    
    include _include(APP_PATH.'plugin/fox_tags/oddfox/template/fox_tag_list.php');
    exit;
}
elseif($action == 'edit'){
    empty($user) AND message(-1, '请先登录！');
    empty($group['allowtags2']) AND message(-1, '您所在的用户组权限不足');

    $tagid = param(2, 0);
    empty($tagid) AND message(-1, 'TAG不存在');
    $query = db_find_one('fox_tag', array('tagid'=>$tagid));
    empty($query) AND message(-1, 'TAG不存在');

    if($method == 'GET'){
        $input = array();
        $input['subject'] = form_text('subject', !empty($query['subject']) ? $query['subject'] : '');
        $input['keywords'] = form_text('keywords', !empty($query['keywords']) ? $query['keywords'] : '');    
        $input['brief'] = form_textarea('brief', !empty($query['brief']) ? $query['brief'] : '', '100%', 60);
        $input['cover'] = form_text('cover', !empty($query['cover']) ? $query['cover'] : '');
        include _include(APP_PATH.'plugin/fox_tags/oddfox/template/fox_tag_edit.php');
        exit;
    }
    elseif($method == 'POST'){
        $subject = param('subject', '');
        $keywords = param('keywords', '');
        $brief = param('brief', '');
        $cover = param('cover', '');
        $r = db_update('fox_tag', array('tagid'=>$query['tagid']), array('subject'=>$subject, 'keywords'=>$keywords, 'brief'=>$brief, 'cover'=>$cover));
        if($r !== FALSE){
            message(0, jump('提交成功！', url("tag-$query[tagid]"), 1));
        }
        message(-1, '提交失败！');
    }
}
elseif($action == 'cover'){
    empty($user) AND message(-1, '请先登录！');
    empty($group['allowtags2']) AND message(-1, '您所在的用户组权限不足');
    if($method != 'POST'){
        exit('Access Denied.');
    }
    $tagid = param(2, 0);
    empty($tagid) AND message(-1, 'TAG不存在');
    $query = db_find_one('fox_tag', array('tagid'=>$tagid));
    empty($query) AND message(-1, 'TAG不存在');
    $data = param('data', '', FALSE);
    empty($data) AND message(-1, '数据为空');
    $data = base64_decode_file_data($data);
    $size = strlen($data);
    $size > 200000 AND message(-1, lang('filesize_too_large', array('maxsize'=>'200K', 'size'=>$size)));
    $filename = $tagid . '.png';
    $path = $conf['upload_path'].'tagcover/';
    $url = $conf['upload_url'].'tagcover/'.$filename;
    !is_dir($path) AND (mkdir($path, 0755, TRUE) OR message(-2, '目录创建失败'));
    file_put_contents($path.$filename, $data) OR message(-1, '写入文件失败');
    !is_file($path.$filename) AND message(-1, '上传失败');
    message(0, array('url'=>$url));
}
elseif($action == 'follow'){
    empty($user) AND message(-1, '请先登录！');
    $tagid = param('tagid', 0);
    empty($tagid) AND message(-1, 'TAG不存在01');
    if($method != 'POST'){
        exit('Access Denied.');
    }

    $query = db_find_one('fox_tag', array('tagid'=>$tagid));
    empty($query) AND message(-1, 'TAG不存在02');
    $r = db_find('fox_tag_follow', array('uid'=>$uid, 'tagid'=>$tagid));
    if($r){            
        db_delete('fox_tag_follow', array('uid'=>$uid, 'tagid'=>$tagid));
        db_update('fox_tag', array('tagid'=>$tagid), array('fans-'=>1));
        message(1, '已取消关注标签！');
    }else{
        db_create('fox_tag_follow', array('uid'=>$uid, 'tagid'=>$tagid, 'tagname'=>$query['name'], 'create_date'=>$time));
        db_update('fox_tag', array('tagid'=>$tagid), array('fans+'=>1));
        message(0, '关注标签关注成功！');
    }
    message(1, '已取消关注标签！');
}
elseif($action == 'ajax'){
    empty($user) AND message(-1, '请先登录！');
    header('access-contol-allow-credentials: true');
    header('access-control-allow-origin: ' . http_url_path());
    header('cache-control: no-cache, must-revalidate, max-age=0');
    header('Connection: close');
    $cond_sql = '';
    $list = array();
    $tablepre = $db->tablepre;
    $keyword = param('text', '');
    $keyword_decode = fox_tag_str_safe(trim($keyword));
    if(empty($keyword_decode)){
        header("Content-type:text/json; charset=utf-8");
        $json = xn_json_encode(array());
        exit($json);
    }
    $fox_tag_filter = fox_tag_filter($keyword_decode);
    $keyword_array = $fox_tag_filter['arr'];
    foreach($keyword_array as $key => $val){
        $val = addslashes($val);
        $cond_sql.= "`keywords` LIKE '%$val%'";
        if($key != count($keyword_array) - 1) $cond_sql.= ' OR ';
    }
    $query = db_sql_find("SELECT `keywords` FROM `{$tablepre}thread` WHERE $cond_sql ORDER BY `tid` DESC LIMIT 10;");
    $query_unique = fox_tag_array_unique($query);    
    header("Content-type:text/json; charset=utf-8");
    $json = xn_json_encode($query_unique);
    exit($json);
}
else{
    exit('Access Denied.');
}
?>