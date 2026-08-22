<?php 
!defined('DEBUG') AND exit('Access Denied.');
$status = param(1, 1);
$page = param(2, 1);
$pagesize = 24;
$total = db_count('fox_prison', array('status'=>$status));
static $cache = array();
if(!isset($cache[$page])){
    $list = db_find('fox_prison', array('status'=>$status), array('id'=>-1), $page, $pagesize);
    if($list){
        foreach($list as &$val){
            $val['user'] = user_safe_info(user_read($val['uid']));
            $val['admin'] = user_safe_info(user_read($val['aid']));
        }
        unset($val);
    }
    $cache[$page] = $list;
}
$list = $cache[$page];
$pagination = pagination(url("prison-{$status}-{page}"), $total, $page, $pagesize);
// SEO
$header['title'] = '小黑屋_' . $conf['sitename'];;
$header['keywords'] = '';
$header['description'] = $conf['sitebrief'];
include _include(APP_PATH.'plugin/fox_prison/oddfox/template/fox_prison_list.php');
?>