<?php

/**
 * zaesky_theme_light 主题插件函数文件
 * 修复：将顶层执行代码移至函数内部，避免 include 时立即执行导致 500 错误
 */

/**
 * 获取主题配置（带缓存）
 * @return array
 */
function light_get_config() {
    static $config = null;
    if ($config === null) {
        $config = setting_get('admin_light_setting');
        if (!is_array($config)) {
            $config = array();
        }
    }
    return $config;
}

/**
 * 获取 action 参数
 * @return mixed
 */
function light_get_action() {
    static $action = null;
    if ($action === null) {
        $action = param(1);
    }
    return $action;
}

/**
 * 获取布局样式配置
 * @return array 包含各种样式类名
 */
function light_get_layout_config() {
    static $layout = null;
    if ($layout !== null) {
        return $layout;
    }
    
    $light_config = light_get_config();
    
    $layout = array(
        'main_switch' => 'col-lg-7',
        'index_header_switch' => 'hidden-lg',
        'icon_switch' => 'hidden-sm hidden-md',
        'thread_nav_switch' => 'd-none',
        'thread_left_l' => 'd-lg-block',
        'thread_left_m' => 'col-lg-8',
        'footer_icon_switch' => 'col-lg-9',
    );
    
    if (isset($light_config['side_nav_switch']) && $light_config['side_nav_switch'] == 0) {
        $layout['main_switch'] = 'col-lg-9';
        $layout['index_header_switch'] = '';
        $layout['icon_switch'] = 'd-none';
    }
    
    if (isset($light_config['thread_top_nav']) && $light_config['thread_top_nav'] == 1) {
        $layout['thread_nav_switch'] = '';
    }
    
    if (isset($light_config['thread_left_switch']) && $light_config['thread_left_switch'] == 0) {
        $layout['thread_left_l'] = '';
        $layout['thread_left_m'] = 'col-lg-9';
    }
    
    if (isset($light_config['footer_icon_show']) && $light_config['footer_icon_show'] == 1) {
        $layout['footer_icon_switch'] = 'col-lg-7';
    }
    
    return $layout;
}

/**
 * 数字转换（万、千单位）
 * @param int|float $num
 * @return string
 */
function convert($num) {
    if ($num >= 100000) {
        $num = round($num / 10000) . 'w';
    } else if ($num >= 10000) {
        $num = round($num / 10000, 1) . 'w';
    } else if ($num >= 1000) {
        $num = round($num / 1000, 1) . 'k';
    }
    return $num;
}

/**
 * 获取最新注册用户列表
 * @param int $num 数量
 * @return array
 */
function discovery_get_site_new_user($num) {
    $userlist = cache_get('get_site_new_user');
    if (empty($userlist)) {
        $userlist = db_find('user', array(), array('login_date' => -1), 1, $num, 'uid');
        if (is_array($userlist)) {
            foreach ($userlist as &$user) {
                $username = $user['username'];
                $user['dname'] = $username;
            }
        }
        cache_set('get_site_new_user', $userlist, 3600);
    }
    return $userlist;
}

/**
 * 获取热门板块
 * @return array
 */
function discovery_get_hot_forum() {
    $hotforumList = cache_get('discovery_get_hot_forum');
    if (empty($hotforumList)) {
        $hotforumList = db_find('forum', array(), array('threads' => -1), 1, 3);
        cache_set('discovery_get_hot_forum', $hotforumList, 86400);
    }
    return $hotforumList;
}

/**
 * 获取财富榜
 * @return array
 */
function discovery_get_gold_List() {
    $goldRankList = cache_get('discovery_get_gold_List');
    if (empty($goldRankList)) {
        $goldRankList = db_find('user', array(), array('golds' => -1), 1, 5);
        cache_set('discovery_get_gold_List', $goldRankList, 86400);
    }
    return $goldRankList;
}

/**
 * 获取贡献榜
 * @return array
 */
function discovery_get_thread_List() {
    $threadRankList = cache_get('discovery_get_thread_List');
    if (empty($threadRankList)) {
        $threadRankList = db_find('user', array(), array('threads' => -1), 1, 5);
        cache_set('discovery_get_thread_List', $threadRankList, 86400);
    }
    return $threadRankList;
}

/**
 * 获取活跃榜
 * @return array
 */
function discovery_get_login_List() {
    $loginRankList = cache_get('discovery_get_login_List');
    if (empty($loginRankList)) {
        $loginRankList = db_find('user', array(), array('logins' => -1), 1, 5);
        cache_set('discovery_get_login_List', $loginRankList, 86400);
    }
    return $loginRankList;
}

/**
 * 获取回复最多的文章
 * @return array
 */
function discovery_get_site_top_list() {
    $result = cache_get('discovery_get_site_top_list');
    if (empty($result)) {
        $result = db_find('thread', array('views' => array('>' => 100), 'posts' => array('>' => 20)), array('views' => 2), 1, 20);
        cache_set('discovery_get_site_top_list', $result, 86400);
    }
    return $result;
}

/**
 * 获取最新的帖子
 * @return array
 */
function get_new_threadlist() {
    $newList = db_find('thread', array(), array('create_date' => -1), 1, 8);
    return $newList;
}

/**
 * 获取最新回复的帖子
 * @return array
 */
function get_new_reply_threadlist() {
    $newReplyList = db_find('thread', array(), array('last_date' => -1), 1, 8);
    return $newReplyList;
}

/**
 * 获取最新评论
 * @return array
 */
function discovery_get_comment_list() {
    $cachename = "discovery_get_comment_list";
    $threadlist = cache_get($cachename);
    if ($threadlist === NULL) {
        $threadlist = post_find(array("isfirst" => 0, "quotepid" => 0), array("create_date" => "-1"), 1, 8);
        cache_set($cachename, $threadlist, 600);
    }
    return $threadlist;
}

/**
 * 获取在线用户列表（带缓存，安全调用）
 * @return array
 */
function light_get_online_users() {
    static $arronline = null;
    if ($arronline !== null) {
        return $arronline;
    }
    
    $arronline = array();
    
    if (!function_exists('online_list_cache')) {
        return $arronline;
    }
    
    $onlineList = online_list_cache();
    if (!is_array($onlineList) || empty($onlineList)) {
        return $arronline;
    }
    
    $arronline = assoc_unique($onlineList, 'uid');
    return $arronline;
}

/**
 * 获取伪静态前缀
 * @return string
 */
function light_get_rew() {
    static $rew = null;
    if ($rew !== null) {
        return $rew;
    }
    
    global $conf;
    $rew = (isset($conf['url_rewrite_on']) && $conf['url_rewrite_on'] == 0) ? '?' : '';
    return $rew;
}

/**
 * 根据指定的key值为数组去重
 * @param array $arr
 * @param string $key
 * @return array
 */
function assoc_unique($arr, $key) {
    if (!is_array($arr) || empty($arr)) {
        return array();
    }
    
    $tmp_arr = array();
    foreach ($arr as $k => $v) {
        if (!is_array($v) || !isset($v[$key])) {
            continue;
        }
        if (in_array($v[$key], $tmp_arr)) {
            unset($arr[$k]);
        } else {
            $tmp_arr[] = $v[$key];
        }
    }
    sort($arr);
    return $arr;
}

/**
 * 返回修罗主程序所处相对目录
 * @return string
 */
function getlistn() {
    $phpSelf = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '';
    return substr($phpSelf, 0, strlen($phpSelf) - 10);
}

/**
 * 统计目录下文件数量
 * @param string $url
 * @return int
 */
function ShuLiang($url) {
    $sl = 0;
    $arr = glob($url);
    if (!is_array($arr)) {
        return 0;
    }
    foreach ($arr as $v) {
        if (is_file($v)) {
            $sl++;
        } else {
            $sl += ShuLiang($v . "/*");
        }
    }
    return $sl;
}

/**
 * 获取用户未读通知数
 * @param int $uid
 * @return int
 */
function getUnread_notices($uid) {
    $uid = intval($uid);
    if ($uid <= 0) {
        return 0;
    }
    $result = db_sql_find("SELECT unread_notices FROM bbs_user WHERE uid = $uid;");
    if (is_array($result) && isset($result[0]['unread_notices'])) {
        return $result[0]['unread_notices'];
    }
    return 0;
}

/**
 * 获取用户发布统计
 * @param int $uid
 * @param int $days
 * @return array
 */
function getPublishCounts($uid, $days = 7) {
    $uid = intval($uid);
    if ($uid <= 0) {
        return array_fill(0, $days, array('publish_date' => '', 'publish_count' => 0));
    }
    
    $currentDate = date('Y-m-d');
    $dates = array();
    for ($i = 0; $i < $days; $i++) {
        $dates[] = date('Y-m-d', strtotime("-$i days", strtotime($currentDate)));
    }
    
    $dates = array_reverse($dates);
    
    $sql = "
    SELECT 
        DATE(FROM_UNIXTIME(create_date)) AS publish_date, 
        COUNT(*) AS publish_count
    FROM 
        bbs_post
    WHERE 
        uid = $uid
        AND create_date >= UNIX_TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL $days DAY)) 
        AND isfirst = 0
    GROUP BY 
        DATE(FROM_UNIXTIME(create_date))
    ORDER BY 
        publish_date ASC;
    ";
    
    $results = db_sql_find($sql);
    
    $publishCounts = array();
    if (is_array($results)) {
        foreach ($results as $row) {
            $publishCounts[$row['publish_date']] = (int)$row['publish_count'];
        }
    }
    
    $finalResult = array_fill(0, $days, array('publish_date' => '', 'publish_count' => 0));
    foreach ($dates as $index => $date) {
        $finalResult[$index] = array(
            'publish_date' => $date,
            'publish_count' => isset($publishCounts[$date]) ? $publishCounts[$date] : 0
        );
    }
    
    return $finalResult;
}

/**
 * 获取用户注册天数
 * @param int $registrationTimestamp
 * @return int
 */
function getUserRegistrationDays($registrationTimestamp) {
    if ($registrationTimestamp) {
        $currentDate = time();
        $days = ($currentDate - $registrationTimestamp) / (60 * 60 * 24);
        return ceil($days);
    }
    return 0;
}

/**
 * 兼容旧代码：初始化全局变量（仅在需要时调用）
 * 此函数用于兼容可能直接使用 $arronline 和 $rew 的旧模板代码
 */
function light_init_legacy_globals() {
    global $arronline, $rew, $action, $light_config;
    global $main_switch, $index_header_switch, $icon_switch;
    global $thread_nav_switch, $thread_left_l, $thread_left_m, $footer_icon_switch;
    
    $action = light_get_action();
    $light_config = light_get_config();
    $arronline = light_get_online_users();
    $rew = light_get_rew();
    
    $layout = light_get_layout_config();
    $main_switch = $layout['main_switch'];
    $index_header_switch = $layout['index_header_switch'];
    $icon_switch = $layout['icon_switch'];
    $thread_nav_switch = $layout['thread_nav_switch'];
    $thread_left_l = $layout['thread_left_l'];
    $thread_left_m = $layout['thread_left_m'];
    $footer_icon_switch = $layout['footer_icon_switch'];
}

?>
