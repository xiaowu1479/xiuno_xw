<?php
!defined('DEBUG') AND exit('Access Denied');

class NavigationService {
    
    public static function defaults() {
        return array(
            'page_title' => '站点导航',
            'page_description' => '精心整理的优质资源，助你提升效率',
            'show_search' => 1,
            'show_stats' => 1,
            'show_sidebar' => 1,
        );
    }
    
    public static function settings() {
        $s = setting_get('xw_navigation');
        return is_array($s) ? array_merge(self::defaults(), $s) : self::defaults();
    }
    
    public static function saveSettings($s) {
        return setting_set('xw_navigation', $s);
    }
    
    // 分类
    public static function getCategories($onlyActive = true) {
        $where = $onlyActive ? array('status' => 1) : array();
        $list = db_find('nav_category', $where, array('sort_order' => 1, 'id' => 1), 1, 100, 'id');
        return $list ? $list : array();
    }
    
    public static function getCategory($id) {
        $id = intval($id);
        if($id <= 0) return NULL;
        return db_find_one('nav_category', array('id' => $id));
    }
    
    public static function addCategory($arr) {
        $title = trim(strval($arr['title']));
        if($title === '') return array('ok' => false, 'message' => '分类名称不能为空');
        $id = db_insert('nav_category', array(
            'title' => $title,
            'icon' => trim(strval($arr['icon'])),
            'sort_order' => intval($arr['sort_order']),
            'status' => intval($arr['status']),
            'created' => time(),
        ));
        if(!$id) return array('ok' => false, 'message' => '创建失败');
        return array('ok' => true, 'id' => intval($id));
    }
    
    public static function updateCategory($id, $arr) {
        $id = intval($id);
        $cat = self::getCategory($id);
        if(!$cat) return array('ok' => false, 'message' => '分类不存在');
        $title = trim(strval($arr['title']));
        if($title === '') return array('ok' => false, 'message' => '分类名称不能为空');
        db_update('nav_category', array('id' => $id), array(
            'title' => $title,
            'icon' => trim(strval($arr['icon'])),
            'sort_order' => intval($arr['sort_order']),
            'status' => intval($arr['status']),
        ));
        return array('ok' => true);
    }
    
    public static function deleteCategory($id) {
        $id = intval($id);
        $cat = self::getCategory($id);
        if(!$cat) return array('ok' => false, 'message' => '分类不存在');
        db_delete('nav_link', array('category_id' => $id));
        db_delete('nav_category', array('id' => $id));
        return array('ok' => true);
    }
    
    // 链接
    public static function getLinksByCategory($categoryId = 0, $onlyActive = true) {
        $where = $onlyActive ? array('status' => 1) : array();
        if($categoryId > 0) $where['category_id'] = intval($categoryId);
        $list = db_find('nav_link', $where, array('sort_order' => 1, 'id' => 1), 1, 500, 'id');
        return $list ? $list : array();
    }
    
    public static function getLink($id) {
        $id = intval($id);
        if($id <= 0) return NULL;
        return db_find_one('nav_link', array('id' => $id));
    }
    
    public static function getAllLinks($onlyActive = true) {
        global $db;
        $where = $onlyActive ? "WHERE l.status=1" : "";
        $sql = "SELECT l.*, c.title AS category_title FROM {$db->tablepre}nav_link l LEFT JOIN {$db->tablepre}nav_category c ON l.category_id=c.id $where ORDER BY l.sort_order ASC, l.id ASC";
        return db_sql_find($sql);
    }
    
    public static function addLink($arr) {
        $title = trim(strval($arr['title']));
        $url = trim(strval($arr['url']));
        if($title === '') return array('ok' => false, 'message' => '标题不能为空');
        if($url === '') return array('ok' => false, 'message' => 'URL不能为空');
        $id = db_insert('nav_link', array(
            'category_id' => intval($arr['category_id']),
            'title' => $title,
            'url' => $url,
            'icon' => trim(strval($arr['icon'])),
            'description' => trim(strval($arr['description'])),
            'color' => trim(strval($arr['color'])),
            'target_blank' => intval($arr['target_blank']),
            'sort_order' => intval($arr['sort_order']),
            'status' => intval($arr['status']),
            'clicks' => 0,
            'created' => time(),
        ));
        if(!$id) return array('ok' => false, 'message' => '创建失败');
        return array('ok' => true, 'id' => intval($id));
    }
    
    public static function updateLink($id, $arr) {
        $id = intval($id);
        $link = self::getLink($id);
        if(!$link) return array('ok' => false, 'message' => '链接不存在');
        $title = trim(strval($arr['title']));
        $url = trim(strval($arr['url']));
        if($title === '') return array('ok' => false, 'message' => '标题不能为空');
        if($url === '') return array('ok' => false, 'message' => 'URL不能为空');
        db_update('nav_link', array('id' => $id), array(
            'category_id' => intval($arr['category_id']),
            'title' => $title,
            'url' => $url,
            'icon' => trim(strval($arr['icon'])),
            'description' => trim(strval($arr['description'])),
            'color' => trim(strval($arr['color'])),
            'target_blank' => intval($arr['target_blank']),
            'sort_order' => intval($arr['sort_order']),
            'status' => intval($arr['status']),
        ));
        return array('ok' => true);
    }
    
    public static function deleteLink($id) {
        $id = intval($id);
        $link = self::getLink($id);
        if(!$link) return array('ok' => false, 'message' => '链接不存在');
        db_delete('nav_link', array('id' => $id));
        return array('ok' => true);
    }
    
    public static function incrementClicks($id) {
        global $db;
        $id = intval($id);
        if($id <= 0) return;
        $db->query("UPDATE {$db->tablepre}nav_link SET clicks=clicks+1 WHERE id=$id");
    }
    
    public static function getStats() {
        $total_links = db_count('nav_link', array('status' => 1));
        $total_categories = db_count('nav_category', array('status' => 1));
        // 获取总点击量
        $links = db_find('nav_link', array(), array(), 1, 1000, '', array('clicks'));
        $total_clicks = 0;
        if($links) {
            foreach($links as $link) {
                $total_clicks += intval($link['clicks']);
            }
        }
        return array(
            'total_links' => intval($total_links),
            'total_categories' => intval($total_categories),
            'total_clicks' => number_format($total_clicks),
        );
    }
}
