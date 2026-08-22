
<?php exit;
elseif($action == 'tags'){
    if($method != 'GET'){
        exit('Access Denied.');
    }
    $pagination = '';
    $list = array();
    if(!empty($user)){
        $page = param(2, 1);
        $pagesize = 10;
        $total = fox_tag_follow_count($uid);
        $query = db_find('fox_tag_follow', array('uid'=>$uid), array('create_date'=>-1), 1, 99999);        
        if(!empty($query)){
            $pagination = pagination(url("my-tags-{page}"), $total, $page, $pagesize);
            $tagids = arrlist_values($query, 'tagid');
            $list = db_find('fox_tag', array('tagid'=>$tagids), array('tagid'=>-1), $page, $pagesize);
            if(!empty($list)){
                foreach($list as &$val){
                    empty($val['cover']) AND $val['cover'] = 'plugin/fox_tags/oddfox/static/img/cover.png';
                }
            }
        }
    }
    include _include(APP_PATH.'plugin/fox_tags/oddfox/template/odd_my_tags.php');
}?>
