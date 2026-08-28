<?php

/*
	豆瓣影视批量导入 - 本地客户端对接 API（独立入口）
	=================================================
	URL: /plugin/xw_douban_import/api.php?action=ping|fetch_tasks|submit|reset

	鉴权（每次请求带三个请求头）：
	  X-XWDI-Timestamp : 秒级时间戳，与服务器偏差 <=300 秒
	  X-XWDI-Nonce     : 每次请求的随机串（<=64 字符）
	  X-XWDI-Sign      : hash_hmac('sha256', timestamp."\n".nonce."\n".md5(rawBody), api_token)
	后台可开关 API、重新生成 token；连续鉴权失败 20 次/小时将临时封禁。

	任务状态：0 待处理  1 成功  2 失败  3 采集中(fetch 锁定)
*/

header('Content-Type: application/json; charset=utf-8');

// 临时调试：将错误写入日志文件（调通后可删除）
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(dirname(__DIR__)) . '/cache/douban_api_error.log');
register_shutdown_function(function() {
	$e = error_get_last();
	if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
		file_put_contents(dirname(dirname(__DIR__)) . '/cache/douban_api_error.log',
			date('[Y-m-d H:i:s] ') . $e['message'] . ' in ' . $e['file'] . ':' . $e['line'] . "\n", FILE_APPEND);
	}
});

function xwdi_api_out($code, $message, $data = array()) {
	echo json_encode(
		$data === array()
			? array('code' => $code, 'message' => $message)
			: array('code' => $code, 'message' => $message, 'data' => $data),
		JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
	);
	exit;
}

// ---------------- 引导 xiuno 框架（复刻 index.php，不做路由） ----------------

!defined('DEBUG') AND define('DEBUG', 0);
define('APP_PATH', dirname(dirname(__DIR__)) . '/');
!defined('ADMIN_PATH') AND define('ADMIN_PATH', APP_PATH . 'admin/');
!defined('XIUNOPHP_PATH') AND define('XIUNOPHP_PATH', APP_PATH . 'xiunophp/');

$conf = (@include APP_PATH . 'conf/conf.php');
if (!is_array($conf)) {
	xwdi_api_out(-1, '读取站点配置失败：conf/conf.php 不存在或加载出错（APP_PATH=' . APP_PATH . '）');
}
substr($conf['log_path'], 0, 2) == './' AND $conf['log_path'] = APP_PATH . $conf['log_path'];
substr($conf['tmp_path'], 0, 2) == './' AND $conf['tmp_path'] = APP_PATH . $conf['tmp_path'];
substr($conf['upload_path'], 0, 2) == './' AND $conf['upload_path'] = APP_PATH . $conf['upload_path'];
isset($conf['cache']['file']['path']) AND substr($conf['cache']['file']['path'], 0, 2) == './' AND $conf['cache']['file']['path'] = APP_PATH . $conf['cache']['file']['path'];
$_SERVER['conf'] = $conf;

include XIUNOPHP_PATH . (DEBUG > 1 ? 'xiunophp.php' : 'xiunophp.min.php');
include APP_PATH . 'model/plugin.func.php';
include _include(APP_PATH . 'model.inc.php');
include _include(APP_PATH . 'plugin/xw_douban_import/model/card.func.php');
include _include(APP_PATH . 'plugin/xw_douban_import/model/importer.func.php');

// ---------------- 配置与鉴权 ----------------

define('XWDI_API_LOCK_LIMIT', 20);		// 鉴权失败封禁阈值（次/小时）
define('XWDI_API_TS_WINDOW', 300);		// 时间戳允许偏差（秒）
define('XWDI_API_STALE_LOCK', 1800);	// 采集锁超时回收（秒）

global $time;
$kv = kv_get('xw_douban_import');
$kv += array(
	'uid' => 1, 'fid' => 0, 'link_prefix' => '下载链接：', 'skip_dup' => 1, 'merge_same' => 1,
	'api_enable' => 0, 'api_token' => '', 'direct_push' => 0,
	'api_fail_count' => 0, 'api_fail_time' => 0,
);

$raw_body = (string) file_get_contents('php://input');
$action = isset($_GET['action']) ? trim((string) $_GET['action']) : '';

// 鉴权失败计数（合并写入，避免覆盖其他配置项）
function xwdi_api_auth_fail($message) {
	global $time;
	$kv = kv_get('xw_douban_import');
	kv_set('xw_douban_import', $kv + array(
		'api_fail_count' => intval($kv['api_fail_count']) + 1,
		'api_fail_time' => $time,
	));
	xwdi_api_out(-1, $message);
}

function xwdi_api_auth() {
	global $kv, $raw_body, $time;

	if (empty($kv['api_enable'])) {
		xwdi_api_out(-1, 'API 未启用，请先在插件后台开启「本地采集对接」');
	}
	if (strlen($kv['api_token']) < 32) {
		xwdi_api_out(-1, 'API token 未初始化，请在插件后台重新生成');
	}

	// 封禁窗口检查
	if (intval($kv['api_fail_count']) >= XWDI_API_LOCK_LIMIT && $time - intval($kv['api_fail_time']) < 3600) {
		xwdi_api_out(-1, '鉴权失败次数过多，请稍后再试');
	}

	// 优先读请求头，兼容 Windows phpStudy 等 $_SERVER 读不到自定义头的环境
	$ts = isset($_SERVER['HTTP_X_XWDI_TIMESTAMP']) ? trim((string) $_SERVER['HTTP_X_XWDI_TIMESTAMP']) : '';
	$nonce = isset($_SERVER['HTTP_X_XWDI_NONCE']) ? trim((string) $_SERVER['HTTP_X_XWDI_NONCE']) : '';
	$sign = isset($_SERVER['HTTP_X_XWDI_SIGN']) ? trim((string) $_SERVER['HTTP_X_XWDI_SIGN']) : '';
	// 兜底：从 GET 参数读（Python 客户端同时发 Header + Query）
	if ($ts === '') $ts = isset($_GET['ts']) ? trim((string) $_GET['ts']) : '';
	if ($nonce === '') $nonce = isset($_GET['nonce']) ? trim((string) $_GET['nonce']) : '';
	if ($sign === '') $sign = isset($_GET['sign']) ? trim((string) $_GET['sign']) : '';
	if ($ts === '' || $nonce === '' || $sign === '') {
		xwdi_api_auth_fail('缺少鉴权参数');
	}
	if (!preg_match('/^\d{10}$/', $ts) || abs($time - intval($ts)) > XWDI_API_TS_WINDOW) {
		xwdi_api_auth_fail('时间戳无效或已过期');
	}
	if (strlen($nonce) > 64) {
		xwdi_api_auth_fail('nonce 无效');
	}
	$string_to_sign = $ts . "\n" . $nonce . "\n" . md5($raw_body);
	$expected = hash_hmac('sha256', $string_to_sign, $kv['api_token']);
	if (!hash_equals($expected, $sign)) {
		xwdi_api_auth_fail('签名校验失败');
	}

	// 认证成功则清零失败计数
	if (intval($kv['api_fail_count']) > 0) {
		kv_set('xw_douban_import', $kv + array('api_fail_count' => 0));
	}
}

// ---------------- action: ping（连通性自检） ----------------

if ($action == 'ping') {
	// 浏览器无签名直接访问时，返回在线提示
	$has_auth = !empty($_SERVER['HTTP_X_XWDI_TIMESTAMP']) || !empty($_GET['ts']);
	if (!$has_auth) {
		xwdi_api_out(0, '豆瓣影视批量导入 API 已就绪，请使用本地客户端（tools/xwdi_client）连接');
	}
	xwdi_api_auth();
	xwdi_api_out(0, 'ok', array(
		'version' => '1.1',
		'pending' => db_count('xw_douban_import', array('status' => 0)),
		'claimed' => db_count('xw_douban_import', array('status' => 3)),
		'failed' => db_count('xw_douban_import', array('status' => 2)),
		'direct_push' => intval($kv['direct_push']),
	));
}

if ($action == 'get_options') {
	xwdi_api_auth();
	$tablepre = get_db_tablepre();
	$forumarr = db_sql_find("SELECT fid, name FROM {$tablepre}forum ORDER BY rank ASC");
	$forums = array();
	foreach ((array)$forumarr as $f) {
		$forums[] = array('fid' => intval($f['fid']), 'name' => $f['name']);
	}
	$tags = array();
	if (db_sql_find_one("SHOW TABLES LIKE '{$tablepre}tag'")) {
		$cates = db_find('tag_cate', array('enable' => 1), array(), 1, 500);
		$cate_map = array();
		foreach ((array)$cates as $c) {
			$cate_map[intval($c['cateid'])] = array('name' => $c['name'], 'fid' => intval($c['fid']));
		}
		$tagarr = db_find('tag', array('enable' => 1), array(), 1, 2000);
		foreach ((array)$tagarr as $t) {
			$tagid = intval($t['tagid']);
			$cateid = intval($t['cateid']);
			$fname = isset($cate_map[$cateid]) ? $cate_map[$cateid]['name'] : '';
			$ffid = isset($cate_map[$cateid]) ? $cate_map[$cateid]['fid'] : 0;
			$tags[] = array('tagid' => $tagid, 'name' => $t['name'], 'cateid' => $cateid, 'cate_name' => $fname, 'fid' => $ffid);
		}
	}
	xwdi_api_out(0, 'ok', array('forums' => $forums, 'tags' => $tags));
}

// 后续 action 均要求 POST JSON（仅非 ping 的 action 需要校验 body）
$body = array();
if ($action !== '' && $action !== 'ping' && $action !== 'debug_sign') {
	$body = json_decode($raw_body, true);
	if (!is_array($body)) {
		xwdi_api_out(-1, '请求体必须是合法 JSON');
	}
}

// ---------------- action: fetch_tasks（拉取并锁定待处理任务） ----------------

if ($action == 'fetch_tasks') {
	xwdi_api_auth();

	$limit = isset($body['limit']) ? intval($body['limit']) : 20;
	$limit = max(1, min(100, $limit));

	// 回收超时锁
	db_exec("UPDATE " . get_db_tablepre() . "xw_douban_import SET status=0 WHERE status=3 AND lock_time>0 AND lock_time<" . (intval($time) - XWDI_API_STALE_LOCK));

	$rows = db_find('xw_douban_import', array('status' => 0), array('id' => 1), 1, $limit);
	$out = array();
	foreach ((array) $rows as $r) {
		$id = intval($r['id']);
		db_update('xw_douban_import', array('id' => $id), array('status' => 3, 'lock_time' => $time));
		$out[] = array(
			'id' => $id,
			'hash' => $r['hash'],
			'title' => $r['title'],
			'link' => $r['link'],
			'fid' => intval($r['fid']),
			'uid' => intval($r['uid']),
			'tagids' => $r['tagids'],
		);
	}
	xwdi_api_out(0, 'ok', array('tasks' => $out));
}

// ---------------- action: submit（回传单条结果并发布） ----------------
// v1.2 重构：查重前置，先查后建，避免重复发帖

if ($action == 'submit') {
	xwdi_api_auth();

	$ok = !empty($body['ok']);
	$in_id = isset($body['id']) ? intval($body['id']) : 0;
	$in_hash = isset($body['hash']) ? trim((string) $body['hash']) : '';
	$data = isset($body['data']) && is_array($body['data']) ? $body['data'] : array();
	$err = isset($body['err']) ? trim((string) $body['err']) : '未知错误';

	// 失败回传：标记失败
	if (!$ok) {
		$task = array();
		if ($in_id > 0) {
			$task = db_find_one('xw_douban_import', array('id' => $in_id));
		} elseif ($in_hash !== '') {
			$task = db_find_one('xw_douban_import', array('hash' => $in_hash));
		}
		if (empty($task)) {
			xwdi_api_out(-1, '任务不存在，无法记录失败原因');
		}
		if (intval($task['status']) == 1) {
			xwdi_api_out(0, 'ok', array('id' => intval($task['id']), 'tid' => intval($task['tid']), 'subject' => '', 'merged' => 0, 'dup' => 1));
		}
		$err = mb_substr($err, 0, 200, 'UTF-8');
		db_update('xw_douban_import', array('id' => intval($task['id'])), array('status' => 2, 'err' => $err, 'lock_time' => 0));
		xwdi_api_out(0, 'ok', array('id' => intval($task['id']), 'tid' => 0, 'subject' => '', 'merged' => 0, 'dup' => 0, 'failed' => 1));
	}

	// ========== 第一步：提取推送数据 ==========
	$d_title = isset($data['title']) ? trim((string) $data['title']) : '';
	if ($d_title === '') {
		xwdi_api_out(-1, '数据缺少标题');
	}
	$d_title = mb_substr(preg_replace('/\s+/u', ' ', $d_title), 0, 120, 'UTF-8');
	$d_link = isset($body['link']) ? mb_substr(trim((string) $body['link']), 0, 500, 'UTF-8') : '';
	$hash = md5($d_title . "\n" . $d_link);

	// ========== 第二步：查重 —— 同名帖子合并（优先级最高） ==========
	$merge_same = empty($kv['merge_same']) ? 0 : 1;
	if ($merge_same) {
		$existing = xwdi_find_existing_thread($d_title);
		if (!empty($existing)) {
			$prefix = empty($kv['link_prefix']) ? '下载链接：' : $kv['link_prefix'];
			$r = xwdi_append_link_to_thread(intval($existing['tid']), $d_link, $prefix);
			if ($r === FALSE) {
				xwdi_api_out(-1, '旧帖 #' . $existing['tid'] . ' 数据异常，无法追加');
			}
			// 记录本次操作到任务表（便于后台查看合并记录）
			db_create('xw_douban_import', array(
				'hash' => $hash, 'title' => $d_title, 'link' => $d_link,
				'fid' => intval($existing['fid']), 'uid' => intval($existing['uid']),
				'tagids' => '', 'status' => 1, 'tid' => intval($existing['tid']),
				'err' => ($r === 'EXISTS') ? '链接已存在' : '已追加到旧帖',
				'dateline' => $time, 'lock_time' => 0,
			));
			xwdi_api_out(0, 'ok', array(
				'id' => intval($existing['id']), 'tid' => intval($existing['tid']),
				'subject' => $d_title, 'merged' => 1, 'dup' => ($r === 'EXISTS') ? 1 : 0,
			));
		}
	}

	// ========== 第三步：查重 —— 同 hash 幂等检查 ==========
	$old = db_find_one('xw_douban_import', array('hash' => $hash, 'status' => 1));
	if (!empty($old)) {
		xwdi_api_out(0, 'ok', array(
			'id' => intval($old['id']), 'tid' => intval($old['tid']),
			'subject' => $old['title'], 'merged' => 0, 'dup' => 1,
		));
	}

	// ========== 第四步：查重通过，创建任务并发布 ==========
	$fid = isset($body['fid']) ? intval($body['fid']) : intval($kv['fid']);
	$uid = isset($body['uid']) ? intval($body['uid']) : intval($kv['uid']);
	$uid <= 0 AND $uid = 1;
	$tagids = isset($body['tagids']) ? preg_replace('/[^0-9,]/', '', (string) $body['tagids']) : '';

	$task_id = db_create('xw_douban_import', array(
		'hash' => $hash, 'title' => $d_title, 'link' => $d_link,
		'fid' => $fid, 'uid' => $uid, 'tagids' => trim($tagids, ','),
		'status' => 0, 'dateline' => $time, 'lock_time' => 0,
	));
	if (empty($task_id)) {
		xwdi_api_out(-1, '创建任务失败');
	}

	$task = db_find_one('xw_douban_import', array('id' => $task_id));
	if (empty($task)) {
		xwdi_api_out(-1, '任务查询失败');
	}

	try {
		$r = xwdi_task_process($task, $data);
		$note = '';
		if (!empty($r['merged'])) {
			$note = empty($r['dup']) ? '已追加到旧帖' : '链接已存在于旧帖';
		}
		db_update('xw_douban_import', array('id' => $task_id), array('status' => 1, 'tid' => intval($r['tid']), 'err' => $note, 'lock_time' => 0));
		xwdi_api_out(0, 'ok', array(
			'id' => $task_id, 'tid' => intval($r['tid']),
			'subject' => $r['subject'], 'merged' => intval($r['merged']), 'dup' => intval($r['dup']),
		));
	} catch (Throwable $e) {
		$err = mb_substr($e->getMessage(), 0, 200, 'UTF-8');
		db_update('xw_douban_import', array('id' => $task_id), array('status' => 2, 'err' => $err, 'lock_time' => 0));
		xwdi_api_out(-1, $err, array('id' => $task_id));
	}
}

// ---------------- action: reset（重置失败/僵死任务为待处理） ----------------

if ($action == 'debug_sign') {
	$ts = isset($_GET['ts']) ? trim((string) $_GET['ts']) : '';
	$nonce = isset($_GET['nonce']) ? trim((string) $_GET['nonce']) : '';
	$raw = (string) file_get_contents('php://input');
	$token = $kv['api_token'];
	$sts = $ts . "\n" . $nonce . "\n" . md5($raw);
	$sign = hash_hmac('sha256', $sts, $token);
	xwdi_api_out(0, 'debug', array(
		'token_len' => strlen($token),
		'token_head' => substr($token, 0, 8) . '...',
		'body_len' => strlen($raw),
		'md5_body' => md5($raw),
		'string_to_sign' => $sts,
		'computed_sign' => $sign,
	));
}

// ---------------- action: import_tasks（本地客户端导入清单并建任务） ----------------

if ($action == 'import_tasks') {
	xwdi_api_auth();

	$items = isset($body['items']) && is_array($body['items']) ? $body['items'] : array();
	$fid = isset($body['fid']) ? intval($body['fid']) : 0;
	$uid = isset($body['uid']) ? intval($body['uid']) : 1;
	$tagids = isset($body['tagids']) ? preg_replace('/[^0-9,]/', '', (string) $body['tagids']) : '';
	$fid <= 0 AND $fid = intval($kv['fid']);
	$uid <= 0 AND $uid = intval($kv['uid']);
	$uid <= 0 AND $uid = 1;

	if (empty($items)) {
		xwdi_api_out(-1, '没有解析到有效数据');
	}

	$total = count($items);
	$inserted = 0;
	$skipped = 0;
	$seen = array();

	foreach ($items as $item) {
		$title = mb_substr(trim((string) ($item['title'] ?? '')), 0, 120, 'UTF-8');
		$link = mb_substr(trim((string) ($item['link'] ?? '')), 0, 500, 'UTF-8');
		if ($title === '') continue;

		$hash = md5($title . "\n" . $link);
		if (isset($seen[$hash])) { $skipped++; continue; }
		$seen[$hash] = 1;

		$old = db_find_one('xw_douban_import', array('hash' => $hash, 'status' => 1));
		if (!empty($old)) { $skipped++; continue; }

		db_create('xw_douban_import', array(
			'hash' => $hash,
			'title' => $title,
			'link' => $link,
			'fid' => $fid,
			'uid' => $uid,
			'tagids' => $tagids,
			'status' => 0,
			'dateline' => $time,
		));
		$inserted++;
	}

	xwdi_api_out(0, 'ok', array(
		'total' => $total,
		'inserted' => $inserted,
		'skipped' => $skipped,
		'pending' => db_count('xw_douban_import', array('status' => 0)),
	));
}

if ($action == 'reset') {
	xwdi_api_auth();

	$reseted = 0;
	$mode = isset($body['mode']) ? trim((string) $body['mode']) : '';
	if ($mode == 'failed') {
		$reseted = db_count('xw_douban_import', array('status' => 2));
		db_update('xw_douban_import', array('status' => 2), array('status' => 0, 'err' => '', 'lock_time' => 0));
	} elseif ($mode == 'stale') {
		db_exec("UPDATE " . get_db_tablepre() . "xw_douban_import SET status=0, lock_time=0 WHERE status=3");
		$reseted = 1;
	} elseif (!empty($body['ids']) && is_array($body['ids'])) {
		foreach ($body['ids'] as $rid) {
			$rid = intval($rid);
			if ($rid <= 0) continue;
			db_update('xw_douban_import', array('id' => $rid, 'status' => 2), array('status' => 0, 'err' => '', 'lock_time' => 0));
			db_update('xw_douban_import', array('id' => $rid, 'status' => 3), array('status' => 0, 'err' => '', 'lock_time' => 0));
			$reseted++;
		}
	} else {
		xwdi_api_out(-1, '缺少 reset 参数：mode=failed|stale 或 ids[]');
	}
	xwdi_api_out(0, 'ok', array('reseted' => $reseted, 'pending' => db_count('xw_douban_import', array('status' => 0))));
}

xwdi_api_out(-1, '未知操作：' . $action);

?>
