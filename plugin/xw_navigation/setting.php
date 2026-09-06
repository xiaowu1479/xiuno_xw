<?php
!defined('DEBUG') AND exit('Access Denied');
$gid != 1 AND message(-1, '无权限');

if(!class_exists('NavigationService', false)) {
    include_once APP_PATH.'plugin/xw_navigation/model/NavigationService.php';
}
class_exists('NavigationService', false) OR message(-1, '导航服务加载失败，请清理 tmp/model.min.php');

$method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';

if($method === 'POST' && !empty($_POST)) {
    csrf_check();
    $a = param('xw_nav_action', '');
    
    if($a === 'save_settings') {
        $s = array(
            'page_title' => trim(strval(param('page_title', ''))),
            'page_description' => trim(strval(param('page_description', ''))),
            'show_search' => param('show_search', 0) ? 1 : 0,
            'show_stats' => param('show_stats', 0) ? 1 : 0,
            'show_sidebar' => param('show_sidebar', 0) ? 1 : 0,
        );
        NavigationService::saveSettings($s);
        message(0, '设置已保存');
    }
    
    if($a === 'add_category') {
        $r = NavigationService::addCategory(array(
            'title' => param('title', ''),
            'icon' => param('icon', ''),
            'sort_order' => param('sort_order', 0),
            'status' => param('status', 1),
        ));
        $r['ok'] OR message(-1, $r['message']);
        message(0, '分类已添加');
    }
    
    if($a === 'update_category') {
        $id = param('id', 0);
        $r = NavigationService::updateCategory($id, array(
            'title' => param('title', ''),
            'icon' => param('icon', ''),
            'sort_order' => param('sort_order', 0),
            'status' => param('status', 1),
        ));
        $r['ok'] OR message(-1, $r['message']);
        message(0, '分类已更新');
    }
    
    if($a === 'delete_category') {
        $id = param('id', 0);
        $r = NavigationService::deleteCategory($id);
        $r['ok'] OR message(-1, $r['message']);
        message(0, '分类已删除');
    }
    
    if($a === 'add_link') {
        $r = NavigationService::addLink(array(
            'category_id' => param('category_id', 0),
            'title' => param('title', ''),
            'url' => param('url', ''),
            'icon' => param('icon', ''),
            'description' => param('description', ''),
            'color' => param('color', ''),
            'target_blank' => param('target_blank', 1),
            'sort_order' => param('sort_order', 0),
            'status' => param('status', 1),
        ));
        $r['ok'] OR message(-1, $r['message']);
        message(0, '链接已添加');
    }
    
    if($a === 'update_link') {
        $id = param('id', 0);
        $r = NavigationService::updateLink($id, array(
            'category_id' => param('category_id', 0),
            'title' => param('title', ''),
            'url' => param('url', ''),
            'icon' => param('icon', ''),
            'description' => param('description', ''),
            'color' => param('color', ''),
            'target_blank' => param('target_blank', 1),
            'sort_order' => param('sort_order', 0),
            'status' => param('status', 1),
        ));
        $r['ok'] OR message(-1, $r['message']);
        message(0, '链接已更新');
    }
    
    if($a === 'delete_link') {
        $id = param('id', 0);
        $r = NavigationService::deleteLink($id);
        $r['ok'] OR message(-1, $r['message']);
        message(0, '链接已删除');
    }
}

$s = NavigationService::settings();
$categories = NavigationService::getCategories(false);
$links = NavigationService::getAllLinks(false);
$header['title'] = '导航页管理';
include _include(APP_PATH.'plugin/xw_navigation/view/htm/admin.htm');
