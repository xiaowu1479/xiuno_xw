<?php
!defined('DEBUG') AND exit('Access Denied');

$s = NavigationService::settings();

// 后台允许把标题存成空串，这里兜一个默认值，避免 <title> 和页头标题变成空白
if(trim(strval($s['page_title'])) === '') $s['page_title'] = '站点导航';

// 点击统计：命中后直接 302 到目标站，无需再查询列表，省一次全表扫描
$click_id = param('click_id', 0, 'intval');
if($click_id > 0) {
    $link = NavigationService::getLink($click_id);
    if($link) {
        NavigationService::incrementClicks($click_id);
        header('Location: ' . $link['url']);
        exit;
    }
}

// 分类筛选（分类 ID 不存在时回落到「全部」，避免标题/列表对不上）
$category_id = param('category_id', 0, 'intval');
$cat = $category_id > 0 ? NavigationService::getCategory($category_id) : NULL;

if($cat) {
    $links = NavigationService::getLinksByCategory($category_id);
    $current_title = $cat['title'];
    foreach($links as &$link) $link['category_title'] = $cat['title'];
    unset($link);
} else {
    $category_id = 0;
    $links = NavigationService::getAllLinks();
    $current_title = '全部链接';
}

$categories = NavigationService::getCategories();
$stats = NavigationService::getStats();
// 「最常用」热门条：按点击量倒序，无点击数据时为空数组，页面自动不渲染该区块
$hot_links = $s['show_hot'] ? NavigationService::getHotLinks($s['hot_limit']) : array();

// 复用论坛头部：标题/描述/关键词写进 $header，导航页才会像论坛里的一页，
// 而不是一个只有站点名的独立页面
$header['title'] = $s['page_title'].' - '.$conf['sitename'];
$header['mobile_title'] = $s['page_title'];
$header['description'] = $s['page_description'] !== '' ? $s['page_description'] : strip_tags($conf['sitebrief']);
$header['keywords'] = $s['page_title'].','.$current_title;

include _include(APP_PATH.'plugin/xw_navigation/view/htm/navigation.htm');
