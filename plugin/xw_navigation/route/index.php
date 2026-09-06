<?php
!defined('DEBUG') AND exit('Access Denied');

$s = NavigationService::settings();
$category_id = param('category_id', 0, 'intval');

if($category_id > 0) {
    $links = NavigationService::getLinksByCategory($category_id);
    $cat = NavigationService::getCategory($category_id);
    $current_title = $cat ? $cat['title'] : '全部链接';
    foreach($links as &$link) $link['category_title'] = $cat['title'];
} else {
    $links = NavigationService::getAllLinks();
    $current_title = '全部链接';
}

$categories = NavigationService::getCategories();
$stats = NavigationService::getStats();

$click_id = param('click_id', 0, 'intval');
if($click_id > 0) {
    NavigationService::incrementClicks($click_id);
    $link = NavigationService::getLink($click_id);
    if($link) {
        header('Location: ' . $link['url']);
        exit;
    }
}

include _include(APP_PATH.'plugin/xw_navigation/view/htm/navigation.htm');
