<?php
!defined('DEBUG') AND exit('Access Denied');

class NavigationService {
    
    // 「最常用」热门条的数量上限与默认值。
    // 抽成常量是为了让三处（后台输入框 / 保存时钳制 / 查询时钳制）只认这一个来源，以后改一处即可。
    // 上限刻意压得低：它是一行横向排列的胶囊，条数一多就会折行，反而不好看。
    const HOT_MAX = 6;
    const HOT_DEFAULT = 6;
    
    public static function defaults() {
        return array(
            'page_title' => '站点导航',
            'page_description' => '精心整理的优质资源，助你提升效率',
            'show_search' => 1,
            'show_stats' => 1,
            'show_sidebar' => 1,
            'show_hot' => 1,
            'hot_limit' => self::HOT_DEFAULT,
            'auto_favicon' => 1,
        );
    }
    
    public static function settings() {
        $s = setting_get('xw_navigation');
        return is_array($s) ? array_merge(self::defaults(), $s) : self::defaults();
    }
    
    // 图标抓取模块（按需加载，避免影响未使用该功能的页面）
    public static function iconLoader() {
        if(!class_exists('NavIcon', FALSE)) {
            include_once APP_PATH.'plugin/xw_navigation/model/NavIcon.php';
        }
        return class_exists('NavIcon', FALSE);
    }
    
    // 图标留空时按 URL 自动抓取站点图标（关闭「自动抓取」或抓取失败则仍为空）
    private static function resolveIcon($url, $icon) {
        $icon = trim(strval($icon));
        if($icon !== '') return $icon;
        $s = self::settings();
        if(empty($s['auto_favicon'])) return '';
        if(!self::iconLoader()) return '';
        return NavIcon::fetch($url);
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
    
    // 热门链接：按点击量倒序，只取有点击量的，供前台「最常用」条使用
    public static function getHotLinks($limit = self::HOT_DEFAULT) {
        global $db;
        // 查询时再钳一次：即使库里存着旧的大数值（比如老版本设的 20），也不会真的取那么多
        $limit = max(1, min(self::HOT_MAX, intval($limit)));
        $sql = "SELECT l.*, c.title AS category_title FROM {$db->tablepre}nav_link l LEFT JOIN {$db->tablepre}nav_category c ON l.category_id=c.id WHERE l.status=1 AND l.clicks>0 ORDER BY l.clicks DESC, l.id ASC LIMIT $limit";
        $list = db_sql_find($sql);
        return $list ? $list : array();
    }
    
    // 生成链接图标的 HTML：FA 类名直接用；图片按「本地/自定义 → 多个可达图标源」链式回退。
    // 前台主列表与热门条共用，避免两处重复同一套回退逻辑。
    public static function iconHtml($link, $img_class = 'xw-favicon') {
        $icon = trim(strval($link['icon']));
        if(strpos($icon, 'fa') === 0) {
            return '<i class="'.htmlspecialchars($icon).'"></i>';
        }
        $has_navicon = class_exists('NavIcon', FALSE);
        $host = $has_navicon
            ? NavIcon::host($link['url'])
            : strtolower(strval(parse_url($link['url'], PHP_URL_HOST)));
        $chain = array();
        // 本地缓存路径要转成站点根路径，避免相对路径在子目录下解析错误
        if($icon !== '') $chain[] = $has_navicon ? NavIcon::url($icon) : $icon;
        if($host !== '') {
            // 不使用 google.com/s2/favicons（国内不可达，被 hosts 劫持还会返回纯文本错误页）
            $chain[] = 'https://favicon.im/'.$host;
            $chain[] = 'https://'.$host.'/favicon.ico';
            $chain[] = 'https://favicon.zhusl.com/ico?url='.urlencode($host);
        }
        if(!$chain) return '<i class="fas fa-globe"></i>';
        return '<img class="'.htmlspecialchars($img_class).'"'
            .' src="'.htmlspecialchars($chain[0]).'"'
            .' data-srcs="'.htmlspecialchars(implode('|', $chain)).'"'
            .' data-idx="0"'
            .' alt="'.htmlspecialchars($link['title']).'"'
            .' onerror="xwIconNext(this)">';
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
            'icon' => self::resolveIcon($url, isset($arr['icon']) ? $arr['icon'] : ''),
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
            'icon' => self::resolveIcon($url, isset($arr['icon']) ? $arr['icon'] : ''),
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
    
    // 图标为空的链接数量（NULL 也计入）
    public static function countMissingIcons() {
        global $db;
        $r = db_sql_find_one("SELECT COUNT(*) AS c FROM {$db->tablepre}nav_link WHERE icon='' OR icon IS NULL");
        return $r ? intval($r['c']) : 0;
    }
    
    // 重新抓取单条链接的图标
    public static function grabIcon($id) {
        $id = intval($id);
        $link = self::getLink($id);
        if(!$link) return array('ok' => FALSE, 'message' => '链接不存在');
        if(!self::iconLoader()) return array('ok' => FALSE, 'message' => '图标模块未加载，请到后台「其他 → 清理缓存」清空临时文件');
        $path = NavIcon::fetch($link['url']);
        if($path === '') return array('ok' => FALSE, 'message' => '抓取失败：站点无 favicon 或网络不可达');
        db_update('nav_link', array('id' => $id), array('icon' => $path));
        return array('ok' => TRUE, 'icon' => $path);
    }
    
    // 批量抓取：每次最多处理 $limit 条图标为空的链接，避免请求超时
    public static function grabIcons($limit = 20) {
        global $db;
        $limit = max(1, min(100, intval($limit)));
        $links = db_sql_find("SELECT id, url FROM {$db->tablepre}nav_link WHERE icon='' OR icon IS NULL ORDER BY id ASC LIMIT $limit");
        if(!is_array($links)) $links = array();
        $ok = $fail = 0;
        foreach($links as $link) {
            $r = self::grabIcon($link['id']);
            $r['ok'] ? $ok++ : $fail++;
        }
        return array('ok' => TRUE, 'done' => $ok, 'fail' => $fail, 'rest' => self::countMissingIcons());
    }
    
    public static function incrementClicks($id) {
        global $db;
        $id = intval($id);
        if($id <= 0) return;
        $db->query("UPDATE {$db->tablepre}nav_link SET clicks=clicks+1 WHERE id=$id");
    }
    
    public static function getStats() {
        global $db;
        $total_links = db_count('nav_link', array('status' => 1));
        $total_categories = db_count('nav_category', array('status' => 1));
        // 总点击量：直接走 SQL 聚合。原来是 db_find 最多取 1000 行再在 PHP 里累加，
        // 一是白读一整表数据，二是超过 1000 条链接时统计会偏小。
        $r = db_sql_find_one("SELECT COALESCE(SUM(clicks), 0) AS c FROM {$db->tablepre}nav_link");
        $total_clicks = $r ? intval($r['c']) : 0;
        return array(
            'total_links' => intval($total_links),
            'total_categories' => intval($total_categories),
            'total_clicks' => number_format($total_clicks),
        );
    }
}
