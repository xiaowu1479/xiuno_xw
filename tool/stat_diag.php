<?php
// ============================================================
// XIUNO XW 统计数字排查脚本（临时用，排查完请删除本文件）
//
// 用法：
//   1. 上传到站点根目录（或 tool/ 目录）
//   2. 浏览器访问：
//      https://你的域名/stat_diag.php?token=xwdiag20261026
//   3. 若首页数字不对，再加 &fix=1 强制清除统计缓存并重算：
//      https://你的域名/stat_diag.php?token=xwdiag20261026&fix=1
//   4. 若抓取首页失败，可指定地址：
//      ...&fix=1&url=https://你的域名/
//
// 只读诊断；fix=1 仅删除 runtime 统计缓存，不动任何数据
// ============================================================

$TOKEN = 'xwdiag20261026';
if(!isset($_GET['token']) || $_GET['token'] !== $TOKEN) {
	http_response_code(403);
	exit('Forbidden');
}

header('Content-Type: text/plain; charset=utf-8');

$root = is_dir(__DIR__.'/conf') ? __DIR__.'/' : dirname(__DIR__).'/';
chdir($root);

function line($k, $v) {
	echo str_pad($k, 30, ' ').': '.$v."\n";
}

function fetch_home($url, $host) {
	$r = array('html' => FALSE, 'code' => 0, 'headers' => array(), 'error' => '');
	if(!function_exists('curl_init')) {
		$ctx = stream_context_create(array('http' => array('timeout' => 15, 'header' => 'Host: '.$host."\r\n", 'ignore_errors' => TRUE)));
		$r['html'] = @file_get_contents($url, FALSE, $ctx);
		$r['code'] = $r['html'] === FALSE ? 0 : 200;
		return $r;
	}
	$ch = curl_init($url);
	$hdrs = array();
	curl_setopt_array($ch, array(
		CURLOPT_RETURNTRANSFER => TRUE,
		CURLOPT_TIMEOUT => 15,
		CURLOPT_CONNECTTIMEOUT => 8,
		CURLOPT_FOLLOWLOCATION => TRUE,
		CURLOPT_MAXREDIRS => 3,
		CURLOPT_HTTPHEADER => array('Host: '.$host),
		CURLOPT_SSL_VERIFYPEER => FALSE,
		CURLOPT_SSL_VERIFYHOST => FALSE,
		CURLOPT_HEADERFUNCTION => function($ch, $h) use (&$hdrs) {
			$p = strpos($h, ':');
			if($p !== FALSE) {
				$k = strtolower(trim(substr($h, 0, $p)));
				$hdrs[$k] = trim(substr($h, $p + 1));
			}
			return strlen($h);
		},
	));
	$r['html'] = curl_exec($ch);
	$r['code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$r['error'] = curl_error($ch);
	$r['headers'] = $hdrs;
	curl_close($ch);
	return $r;
}

function sidebar_numbers($html) {
	if($html === FALSE || $html === '') return NULL;
	if(!preg_match('#<table class="w-100 small">(.*?)</table>#s', $html, $m)) return NULL;
	$t = strip_tags(str_replace(array('</td>', '</tr>'), array(' | ', ' '), $m[1]));
	return trim(preg_replace('/\s+/u', ' ', $t));
}

echo "===== XIUNO XW 统计诊断 =====\n";
line('时间', date('Y-m-d H:i:s'));
line('站点根目录', $root);
line('PHP 版本', PHP_VERSION);
if(function_exists('posix_geteuid') && function_exists('posix_getpwuid')) {
	$pw = posix_getpwuid(posix_geteuid());
	line('PHP 运行用户', $pw ? $pw['name'].' (uid '.$pw['uid'].')' : posix_geteuid());
}

// ---------- 版本 ----------
$index = @file_get_contents($root.'index.php');
if(preg_match("/\\\$conf\['version'\]\s*=\s*'([^']+)'/", $index, $m)) {
	line('程序版本', $m[1]);
} else {
	line('程序版本', '读取失败');
}

// ---------- 配置 ----------
$conf = @include $root.'conf/conf.php';
if(!is_array($conf)) {
	echo "conf/conf.php 读取失败，无法继续\n";
	exit;
}
$dbtype = $conf['db']['type'];
$d = $conf['db'][$dbtype]['master'];
line('数据库类型', $dbtype);
line('数据库地址/库名', $d['host'].' / '.$d['name']);
line('表前缀', $d['tablepre']);
line('从库数量', isset($conf['db'][$dbtype]['slaves']) ? count($conf['db'][$dbtype]['slaves']) : 0);

// ---------- 数据库真实行数 ----------
$db_users = NULL;
try {
	$dsn = "mysql:host={$d['host']};dbname={$d['name']};charset=utf8mb4";
	$pdo = new PDO($dsn, $d['user'], $d['password'], array(PDO::ATTR_TIMEOUT => 5, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
	foreach(array('user', 'thread', 'post') as $t) {
		$n = $pdo->query("SELECT COUNT(*) FROM `{$d['tablepre']}$t`")->fetchColumn();
		if($t === 'user') $db_users = $n;
		line("数据库 $t 行数", $n);
	}
} catch(Exception $e) {
	line('数据库连接', '失败: '.$e->getMessage());
}

// ---------- 缓存配置 ----------
line('缓存开关', !empty($conf['cache']['enable']) ? '开启' : '关闭');
line('缓存类型', $conf['cache']['type']);

$cachetype = $conf['cache']['type'];
if($cachetype === 'file') {
	$cp = $conf['cache']['file']['path'];
	$abs = (substr($cp, 0, 1) === '/' || preg_match('#^[A-Za-z]:#', $cp)) ? $cp : $root.$cp;
	line('缓存目录', $cp);
	line('缓存目录(绝对)', $abs);
	line('缓存目录存在', is_dir($abs) ? '是' : '否');
	line('缓存目录可写', is_writable($abs) ? '是' : '否');
	$pre = $conf['cache']['file']['cachepre'];
	$hash = md5($pre.'runtime');
	$rf = rtrim($abs, '/\\').'/'.substr($hash, 0, 2).'/'.$hash.'.php';
	line('runtime 缓存文件', $rf);
	if(is_file($rf)) {
		line('  文件可写', is_writable($rf) ? '是' : '否');
		$owner = @fileowner($rf);
		if(function_exists('posix_getpwuid') && $owner !== FALSE) {
			$pw = @posix_getpwuid($owner);
			$owner = $pw ? $pw['name'].' (uid '.$pw['uid'].')' : $owner;
		}
		line('  文件属主', $owner);
		line('  修改时间', date('Y-m-d H:i:s', filemtime($rf)));
		$s = @file_get_contents($rf);
		if($s !== FALSE && ($p = strpos($s, "\t")) !== FALSE) {
			$data = json_decode(substr($s, $p + 1), TRUE);
			if(isset($data['v']['users'])) {
				line('  缓存内 users', $data['v']['users']);
				line('  缓存内 threads/posts', $data['v']['threads'].' / '.$data['v']['posts']);
			} else {
				line('  缓存内容', '无法解析');
			}
		}
	} else {
		line('  缓存文件', '不存在（下次请求会按数据库重建）');
	}
	$test = rtrim($abs, '/\\').'/.diag_'.mt_rand(100000, 999999).'.tmp';
	$ok = @file_put_contents($test, 'x');
	line('缓存目录写入测试', $ok === 1 ? '成功' : '失败（这就是问题所在）');
	$ok === 1 AND @unlink($test);
}

// ---------- 通过框架读取 runtime（覆盖 redis/memcached 等） ----------
if(!defined('APP_PATH')) define('APP_PATH', $root);
if(!defined('DEBUG')) define('DEBUG', 0);
if(!function_exists('cache_new')) {
	@include $root.'xiunophp/xiunophp.php';
}
$cache_ok = FALSE;
if(function_exists('cache_new')) {
	$_SERVER['time'] = time();
	$_SERVER['cache'] = cache_new($conf['cache']);
	if($_SERVER['cache']) {
		$cache_ok = TRUE;
		$rt = cache_get('runtime');
		if(is_array($rt)) {
			line('框架读到 runtime.users', isset($rt['users']) ? $rt['users'] : '(无)');
			line('框架读到 runtime.threads', isset($rt['threads']) ? $rt['threads'] : '(无)');
			line('框架读到 runtime.posts', isset($rt['posts']) ? $rt['posts'] : '(无)');
		} else {
			line('框架读到 runtime', '空（下次请求会重建）');
		}
		$w = cache_set('__diag_probe__', 1, 10);
		line('框架缓存写入测试', $w ? '成功' : '失败（缓存不可写，计数无法更新）');
		$w AND cache_delete('__diag_probe__');
	} else {
		line('缓存对象', '创建失败');
	}
}

// ---------- 编译缓存是否最新 ----------
$min = $root.'tmp/model.min.php';
line('tmp/model.min.php', is_file($min) ? '存在' : '不存在');
if(is_file($min)) {
	line('  修改时间', date('Y-m-d H:i:s', filemtime($min)));
	line('  源码修改时间', is_file($root.'model/runtime.func.php') ? date('Y-m-d H:i:s', filemtime($root.'model/runtime.func.php')) : '无');
	$s = @file_get_contents($min);
	line('  含新版并发合并代码', (strpos($s, 'runtime_lock') !== FALSE) ? '是' : '否（编译缓存是旧的，需清空 tmp）');
}
line('tmp 目录可写', is_writable($root.'tmp/') ? '是' : '否');

// ---------- 抓取首页（本机 + 公网） ----------
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
$pub = isset($_GET['url']) ? $_GET['url'] : 'http://'.$host.'/';
$origins = array('本机首页(http://127.0.0.1/)' => 'http://127.0.0.1/', '公网首页('.$pub.')' => $pub);
foreach($origins as $label => $u) {
	$r = fetch_home($u, $host);
	line($label, 'HTTP '.$r['code'].($r['error'] ? ' / '.$r['error'] : ''));
	$nums = sidebar_numbers($r['html']);
	line('  '.$label.' 侧栏数字', $nums === NULL ? '未找到统计表格' : $nums);
	foreach(array('age', 'x-cache', 'x-proxy-cache', 'cf-cache-status', 'via', 'x-cache-lookup', 'server') as $h) {
		if(!empty($r['headers'][$h])) line('  响应头 '.$h, $r['headers'][$h]);
	}
}

// ---------- fix=1：强制清除 runtime 缓存并重算 ----------
if(isset($_GET['fix']) && $_GET['fix'] == '1') {
	echo "\n---- 执行修复：清除 runtime 统计缓存 ----\n";
	if(!$cache_ok) {
		line('结果', '缓存对象不可用，无法清除');
	} else {
		$ok = cache_delete('runtime');
		line('删除 runtime 缓存', $ok ? '成功' : '失败（缓存目录/文件不可写，请修复权限）');
		$rt = cache_get('runtime');
		line('删除后 runtime', $rt === NULL ? '已清空' : json_encode($rt));
		if($rt === NULL) {
			// 用公网地址触发一次首页请求（127.0.0.1 可能落到别的站点）
			$r = fetch_home($pub, $host);
			line('触发重建后 HTTP', $r['code']);
			$nums = sidebar_numbers($r['html']);
			line('重建后首页数字', $nums === NULL ? '未找到统计表格' : $nums);
			$rt = cache_get('runtime');
			if(is_array($rt)) {
				line('重建后 runtime.users', isset($rt['users']) ? $rt['users'] : '(无)');
				line('数据库 user 行数', $db_users === NULL ? '未知' : $db_users);
				if($db_users !== NULL && isset($rt['users'])) {
					line('是否一致', $rt['users'] == $db_users ? '一致' : '不一致（缓存重建仍有问题）');
				}
			} else {
				line('重建后 runtime', '仍为空（公网地址抓取失败，请加 &url=https://你的域名/ 再试）');
			}
		}
	}
}

echo "\n===== 诊断结束（请把以上内容完整复制给开发者） =====\n";
