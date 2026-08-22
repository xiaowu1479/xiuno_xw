<?php
/**
 * XIUNO XW (基于修罗BBS/Xiuno BBS 4.x) 火车头免登录发布接口
 * ============================================
 * 上传到 XIUNO XW 根目录
 * 
 * 测试访问：
 *   http://你的域名/loco.php?pass=123456&action=list_forum         -- 列出版块(JSON)
 *   http://你的域名/loco.php?pass=123456&action=list_forum_text   -- 列出版块(文本)
 *   http://你的域名/loco.php?pass=123456&action=list_tag_text     -- 列出标签(文本，需xn_tag)
 *   http://你的域名/loco.php?pass=123456&action=list_category_text -- 合并版块+标签(推荐，单栏目列表)
 *   http://你的域名/loco.php?pass=123456&action=list_user         -- 列出用户(JSON)
 *   http://你的域名/loco.php?pass=123456&action=debug             -- 调试参数
 * 
 * 火车头配置（推荐）：
 *   提交地址：http://你的域名/loco.php?pass=123456&action=save
 *   获取栏目列表：http://你的域名/loco.php?pass=123456&action=list_category_text
 *   编码：UTF-8
 *   发布方式：POST
 *   POST数据：fid=[分类ID]&uid=[标签:用户ID]&subject=[标签:标题]&message=[标签:内容]
 *   成功标记：yes
 *   失败标记：err
 * 
 * 合并列表说明（list_category_text）：
 *   - 版块输出：ID 保持不变，如 <<<1--版块名称>>>
 *   - 标签输出：ID = 10000 + tagid，如 <<<10005--版块/分类:标签名 [标签]>>>
 *   - 服务端收到 ID>=10000 自动识别为标签，找到对应版块发帖并关联标签
 *   - 如果同时需要自定义 tagid，保留 &tagid= 参数，两者叠加
 */

error_reporting(0);

// ====== 配置项（密码读取自 conf/conf.php 的 loco_pass 配置）======
define('APP_PATH', dirname(__FILE__) . '/');
define('XIUNOPHP_PATH', APP_PATH . 'xiunophp/');
define('ADMIN_PATH', APP_PATH . 'admin/');
!defined('DEBUG') AND define('DEBUG', 0);

// 加载配置
$conf = include APP_PATH . 'conf/conf.php';
if (empty($conf)) exit('err:conf');

// 未配置密码时禁止使用
$pass = isset($conf['loco_pass']) ? $conf['loco_pass'] : '';
if (empty($pass)) exit('err:pass_not_configured');

// 获取表前缀
$tablepre = isset($conf['db']['mysql']['master']['tablepre']) ? $conf['db']['mysql']['master']['tablepre'] : 'bbs_';

// 加载框架
include XIUNOPHP_PATH . 'xiunophp.min.php';

// 连接数据库
$db = db_new($conf['db']);
if (!db_connect($db)) exit('err:db');
$_SERVER['db'] = $db;

// 设置 Xiuno 需要的全局变量
$_SERVER['time'] = time();
$time = $_SERVER['time'];
$longip = ip2long(ip());
$longip < 0 AND $longip = sprintf("%u", $longip);
$_SERVER['longip'] = $longip;

// 设置管理员用户组（thread_create 需要 $gid 全局变量）
$gid = 1;

// 初始化缓存系统（thread_create 内会调用 cache_delete/cache_set）
$_SERVER['cache'] = cache_new($conf['cache']);

// 手动加载版块列表（绕过缓存系统，thread_create 和 forum_read 需要 $forumlist）
$forumlist = array();
$forums_raw = $db->query("SELECT * FROM `{$tablepre}forum` ORDER BY `rank` ASC");
if (!empty($forums_raw)) {
    foreach ($forums_raw as $f) {
        $forumlist[intval($f['fid'])] = $f;
    }
}

// 加载全部模型文件（保持与官方顺序一致）
$include_model_files = array(
    APP_PATH . 'model/kv.func.php',
    APP_PATH . 'model/queue.func.php',
    APP_PATH . 'model/group.func.php',
    APP_PATH . 'model/user.func.php',
    APP_PATH . 'model/forum.func.php',
    APP_PATH . 'model/forum_access.func.php',
    APP_PATH . 'model/thread.func.php',
    APP_PATH . 'model/thread_top.func.php',
    APP_PATH . 'model/post.func.php',
    APP_PATH . 'model/attach.func.php',
    APP_PATH . 'model/check.func.php',
    APP_PATH . 'model/mythread.func.php',
    APP_PATH . 'model/runtime.func.php',
    APP_PATH . 'model/table_day.func.php',
    APP_PATH . 'model/cron.func.php',
    APP_PATH . 'model/form.func.php',
    APP_PATH . 'model/misc.func.php',
    // xn_tag 增强版插件模型文件
    APP_PATH . 'plugin/xn_tag/model/tag_cate.func.php',
    APP_PATH . 'plugin/xn_tag/model/tag.func.php',
    APP_PATH . 'plugin/xn_tag/model/tag_thread.func.php',
);
foreach ($include_model_files as $file) {
    include $file;
}

// ============================================================
//  接口处理
// ============================================================

// 密码验证
if (!isset($_REQUEST['pass']) || $_REQUEST['pass'] !== $pass) {
    exit('err:pass');
}

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'save';

// ---------- 列出版块（JSON 格式，用于测试） ----------
if ($action == 'list_forum') {
    header('Content-Type: application/json; charset=utf-8');
    $forums = array();
    $arr = $db->query("SELECT `fid`, `name` FROM `{$tablepre}forum` ORDER BY `rank` ASC");
    if (!empty($arr)) {
        foreach ($arr as $f) {
            $forums[] = array('fid' => intval($f['fid']), 'name' => $f['name']);
        }
    }
    echo json_encode(array('code' => 0, 'forums' => $forums));
    exit;
}

// ---------- 列出版块（文本格式，供火车头获取栏目） ----------
if ($action == 'list_forum_text') {
    header('Content-Type: text/plain; charset=utf-8');
    $arr = $db->query("SELECT `fid`, `name` FROM `{$tablepre}forum` ORDER BY `rank` ASC");
    if (!empty($arr)) {
        foreach ($arr as $f) {
            echo '<<<'.intval($f['fid']).'--'.$f['name'].'>>>';
        }
    }
    exit;
}

// ---------- 调试模式：查看火车头发送了哪些参数 ----------
if ($action == 'debug') {
    header('Content-Type: text/plain; charset=utf-8');
    echo "===== GET 参数 =====\n";
    print_r($_GET);
    echo "\n===== POST 参数 =====\n";
    print_r($_POST);
    echo "\n===== REQUEST 参数 =====\n";
    print_r($_REQUEST);
    echo "\n===== 原始 POST 数据 =====\n";
    echo file_get_contents('php://input');
    exit;
}

// ---------- 列出标签（文本格式，供火车头获取标签） ----------
if ($action == 'list_tag_text') {
    header('Content-Type: text/plain; charset=utf-8');
    $catelist = $db->query("SELECT c.*, f.name AS forumname FROM `{$tablepre}tag_cate` c LEFT JOIN `{$tablepre}forum` f ON f.fid=c.fid ORDER BY c.rank ASC");
    if (!empty($catelist)) {
        foreach ($catelist as $cate) {
            $tags = $db->query("SELECT * FROM `{$tablepre}tag` WHERE cateid={$cate['cateid']} AND enable=1 ORDER BY rank ASC");
            if (!empty($tags)) {
                foreach ($tags as $tag) {
                    echo '<<<'.intval($tag['tagid']).'--['.$cate['forumname'].'/'.$cate['name'].'] '.$tag['name'].'>>>';
                }
            }
        }
    }
    exit;
}

// ---------- 合并列出版块+标签（供火车头单一栏目列表使用） ----------
// 标签ID = 10000 + tagid，服务端自动识别
if ($action == 'list_category_text') {
    header('Content-Type: text/plain; charset=utf-8');
    // 版块（ID < 10000）
    foreach ($forumlist as $fid => $f) {
        echo '<<<'.$fid.'--'.$f['name'].'>>>';
    }
    // 标签（ID = 10000 + tagid，用 [标签] 后缀区分）
    $tagrows = $db->query("SELECT t.tagid, t.name AS tagname, c.name AS catename, c.fid FROM `{$tablepre}tag` t LEFT JOIN `{$tablepre}tag_cate` c ON c.cateid=t.cateid WHERE t.enable=1 ORDER BY c.rank, t.rank");
    if (!empty($tagrows)) {
        foreach ($tagrows as $r) {
            $fname = isset($forumlist[$r['fid']]) ? $forumlist[$r['fid']]['name'] : '?';
            echo '<<<'.(10000 + intval($r['tagid'])).'--'.$fname.'/'.$r['catename'].':'.$r['tagname'].' [标签]>>>';
        }
    }
    exit;
}

// ---------- 调试：查看标签原始数据 ----------
if ($action == 'debug_tags') {
    header('Content-Type: text/plain; charset=utf-8');
    echo "=== tablepre =".$tablepre."===\n";
    echo "=== forum (对照) ===\n";
    $f = $db->query("SELECT fid,name FROM `{$tablepre}forum` LIMIT 3");
    var_dump($f);
    echo "\n=== tag_cate ===\n";
    $c = $db->query("SELECT * FROM `{$tablepre}tag_cate`");
    var_dump($c);
    echo "\n=== tag ===\n";
    $t = $db->query("SELECT * FROM `{$tablepre}tag`");
    var_dump($t);
    echo "\n=== tag_thread LIMIT 20 ===\n";
    $tt = $db->query("SELECT * FROM `{$tablepre}tag_thread` LIMIT 20");
    var_dump($tt);
    exit;
}

// ---------- 列出用户 ----------
if ($action == 'list_user') {
    header('Content-Type: application/json; charset=utf-8');
    $users = array();
    $arr = $db->query("SELECT `uid`, `username` FROM `{$tablepre}user` WHERE `uid` > 0 ORDER BY `uid` ASC LIMIT 200");
    if (!empty($arr)) {
        foreach ($arr as $u) {
            $users[] = array('uid' => intval($u['uid']), 'username' => $u['username']);
        }
    }
    echo json_encode(array('code' => 0, 'users' => $users));
    exit;
}

// ---------- 发布主题 ----------
$fid     = isset($_REQUEST['fid'])     ? intval($_REQUEST['fid'])   : 1;
$uid     = isset($_REQUEST['uid'])     ? intval($_REQUEST['uid'])   : 1;
$subject = isset($_REQUEST['subject']) ? trim($_REQUEST['subject']) : '';
$message = isset($_REQUEST['message']) ? $_REQUEST['message']       : '';

// 火车头可能会转义引号，去掉反斜杠
if(function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
    $subject = stripslashes($subject);
    $message = stripslashes($message);
}

if (empty($subject) || empty($message)) {
    exit('err:empty');
}

    // 处理标签ID偏移（list_category_text 合并列表用，标签ID = 10000 + tagid）
$real_tagid = 0;
if ($fid >= 10000) {
    $real_tagid = $fid - 10000;
    $tag = null;
    $r = $db->query("SELECT * FROM `{$tablepre}tag` WHERE tagid=$real_tagid AND enable=1 LIMIT 1");
    if (!empty($r)) { foreach ($r as $v) { $tag = $v; break; } }
    if (empty($tag)) exit('err:tagid. tagid='.$real_tagid);
    $tagcate = null;
    $r = $db->query("SELECT * FROM `{$tablepre}tag_cate` WHERE cateid={$tag['cateid']} LIMIT 1");
    if (!empty($r)) { foreach ($r as $v) { $tagcate = $v; break; } }
    if (empty($tagcate)) exit('err:tagcate. cateid='.$tag['cateid']);
    $fid = intval($tagcate['fid']);
    if (empty($fid)) exit('err:cate_fid0');
}

// 验证版块存在
$forum = forum_read($fid);
if (empty($forum)) exit('err:fid');

// 验证用户存在
$_user = user_read($uid);
if (empty($_user)) exit('err:uid');

// 构建帖子数组
$thread = array(
    'fid'     => $fid,
    'uid'     => $uid,
    'subject' => $subject,
    'doctype' => 0,
    'message' => $message,
    'time'    => $time,
    'longip'  => $longip,
);

// 创建主题（$firstpid 引用传出）
$tid = thread_create($thread, $firstpid);

if (!$tid) {
    exit('err:create');
}

// 关联标签（支持 xn_tag 增强版）
if ($real_tagid > 0) {
    tag_thread_create($real_tagid, $tid);
}
$tagid_raw = isset($_REQUEST['tagid']) ? $_REQUEST['tagid'] : '';
if ($tagid_raw !== '') {
    $tagid_arr = explode(',', strval($tagid_raw));
    foreach ($tagid_arr as $tagid) {
        $tagid = intval($tagid);
        if ($tagid > 0) {
            tag_thread_create($tagid, $tid);
        }
    }
}

echo 'yes';
