<?php

// hook admin_func_start.php

// 有部分用户
define('XN_ADMIN_BIND_IP', array_value($conf, 'admin_bind_ip'));

function admin_token_check() {
	global $longip, $time, $useragent, $conf;
	$useragent_md5 = md5($useragent);
	
	//$key = md5($longip.$useragent_md5.$conf['auth_key']); // 有些环境是动态 IP
	$key = md5((XN_ADMIN_BIND_IP ? $longip : '').$useragent_md5.xn_key());
	
	// hook admin_token_check_start.php
	
	$admin_token = param('bbs_admin_token');
	if(empty($admin_token)) {
		$_REQUEST[0] = 'index';
		$_REQUEST[1] = 'login';
	} else {
		$s = xn_decrypt($admin_token, $key);
		if(empty($s)) {
			setcookie('bbs_admin_token', '', 0, '', '', '', TRUE);
			//message(-1, lang('admin_token_error'));
			message(-1, lang('admin_token_expiry'));
		}
		list($_ip, $_time) = explode("\t", $s);
		
		// 后台超过 3600 自动退出。
		// Background / more than 3600 automatic withdrawal.
		//if($_ip != $longip || $time - $_time > 3600) {
		if((XN_ADMIN_BIND_IP && $_ip != $longip || !XN_ADMIN_BIND_IP) && $time - $_time > 3600) {
			setcookie('bbs_admin_token', '', 0, '', '', '', TRUE);
			message(-1, lang('admin_token_expiry'));
		}
		
		// 超过半小时，重新发新令牌，防止过期
		// More than half an hour, reset a new token, prevent expired
		if($time - $_time > 1800) {
			admin_token_set();
		}
	}
	// hook admin_token_check_end.php
}

function admin_token_set() {
	global $longip, $time, $useragent, $conf;
	$useragent_md5 = md5($useragent);
	//$key = md5($longip.$useragent_md5.$conf['auth_key']);
	$key = md5((XN_ADMIN_BIND_IP ? $longip : '').$useragent_md5.xn_key());
	
	// hook admin_token_set_start.php
	
	$admin_token = param('bbs_admin_token');
	$s = "$longip	$time";
	
	$admin_token = xn_encrypt($s, $key);
	setcookie('bbs_admin_token', $admin_token, $time + 3600, '',  '', 0, TRUE);
	
	// hook admin_token_set_end.php
}

function admin_token_clean() {
	global $time;
	setcookie('bbs_admin_token', '', $time - 86400, '', '', 0, TRUE);
	
	// hook admin_token_clean_start.php
}

// bootstrap style
function admin_tab_active($arr, $active) {
	// hook admin_tab_active_start.php
	$s = '';
	foreach ($arr as $k=>$v) {
		$s .= '<a role="button" class="btn btn-secondary'.($active == $k ? ' active' : '').'" href="'.$v['url'].'">'.$v['text'].'</a>';
	}
	// hook admin_tab_active_end.php
	return $s;
}

// 检测远程最新版本 (XIUNO XW)
function admin_update_check($force = FALSE) {
	global $conf;
	$key = 'admin_update_check';
	if(!$force) {
		$cache = cache_get($key);
		if($cache !== NULL) return $cache;
	}
	$result = array(
		'error' => 0,
		'latest' => 0,
		'tag' => '',
		'name' => '',
		'body' => '',
		'published_at' => '',
		'html_url' => '',
		'zipball_url' => '',
		'download_url' => '',	// 实际更新下载地址：附件优先，源码包兜底
		'asset_name' => '',		// 命中的附件名（附件优先时非空）
	);
	$url = empty($conf['update_check_url']) ? 'https://api.github.com/repos/xiaowu1479/xiuno_xw/releases/latest' : $conf['update_check_url'];
	$opts = array(
		'http' => array(
			'method' => 'GET',
			'timeout' => 5,
			'header' => "User-Agent: XIUNO XW-Update-Check\r\nAccept: application/vnd.github+json\r\n",
		),
	);
	$ctx = stream_context_create($opts);
	$s = @file_get_contents($url, FALSE, $ctx);
	$arr = array();
	if($s !== FALSE) {
		$arr = json_decode($s, TRUE);
	}
	// 无 Release 时（404），回退到 tags 接口，至少能拿到版本号
	if(empty($arr['tag_name']) && strpos($url, 'releases/latest') !== FALSE) {
		$url2 = substr($url, 0, strpos($url, '/releases/latest')).'/tags';
		$s2 = @file_get_contents($url2, FALSE, $ctx);
		if($s2 !== FALSE) {
			$arr2 = json_decode($s2, TRUE);
			if(!empty($arr2[0]['name'])) {
				$arr = array('tag_name'=>$arr2[0]['name'], 'name'=>'', 'body'=>'', 'published_at'=>'', 'html_url'=>'https://github.com/xiaowu1479/xiuno_xw/releases', 'zipball_url'=>isset($arr2[0]['zipball_url']) ? $arr2[0]['zipball_url'] : '');
			}
		}
	}
	if(empty($arr['tag_name'])) {
		$result['error'] = 1;
	} else {
			$tag = trim($arr['tag_name'], 'vV ');
			$result['tag'] = $tag;
			$result['name'] = empty($arr['name']) ? '' : $arr['name'];
			$result['body'] = empty($arr['body']) ? '' : $arr['body'];
			$result['published_at'] = empty($arr['published_at']) ? '' : substr($arr['published_at'], 0, 10);
			$result['html_url'] = empty($arr['html_url']) ? '' : $arr['html_url'];
			$result['zipball_url'] = empty($arr['zipball_url']) ? '' : $arr['zipball_url'];
			$result['latest'] = version_compare($tag, $conf['version'], '>') ? 1 : 0;
			// 下载源选择：附件（zip/tar.gz）优先，无附件则回退源码包
			$result['download_url'] = $result['zipball_url'];
			if(!empty($arr['assets']) && is_array($arr['assets'])) {
				foreach($arr['assets'] as $_asset) {
					$_name = empty($_asset['name']) ? '' : $_asset['name'];
					if(preg_match('#\.(zip|tar\.gz|tgz)$#i', $_name) && !empty($_asset['browser_download_url'])) {
						$result['download_url'] = $_asset['browser_download_url'];
						$result['asset_name'] = $_name;
						break;
					}
				}
			}
	}
	cache_set($key, $result, 3600);
	return $result;
}

// 一键自动更新 (XIUNO XW)：下载 -> 校验 -> 备份 -> 解压 -> 覆盖 -> 清缓存
function admin_update_do() {
	global $conf, $db, $time;

	// CSRF 校验
	csrf_check();

	// 防并发锁（120 秒超时）
	!xn_lock_start('admin_update', 120) AND message(-1, lang('admin_update_locked'));

	set_time_limit(0);
	@ini_set('memory_limit', '256M');

	// 1. 强制检测更新
	$upinfo = admin_update_check(TRUE);
	if(!empty($upinfo['error'])) {
		xn_lock_end('admin_update');
		message(-1, lang('admin_update_error'));
	}
	if(empty($upinfo['latest'])) {
		xn_lock_end('admin_update');
		message(0, lang('admin_update_latest'));
	}
	$download_url = empty($upinfo['download_url']) ? '' : $upinfo['download_url'];
	if(empty($download_url)) {
		xn_lock_end('admin_update');
		message(-1, lang('admin_update_no_download'));
	}

	// 2. 下载更新包到 tmp
	$tmpfile = $conf['tmp_path'].'update_'.md5($download_url).'.tmp';
	$s = http_get_full($download_url, 120);
	if(empty($s)) {
		xn_lock_end('admin_update');
		message(-1, lang('admin_update_download_failed'));
	}
	file_put_contents($tmpfile, $s);
	unset($s);

	// 3. 判断压缩格式：zip / tar.gz / tgz
	$isfile = file_get_contents($tmpfile, FALSE, NULL, 0, 2);
	$is_zip = $isfile == 'PK';
	$is_gz = !$is_zip && (bin2hex(substr($isfile, 0, 2)) == '1f8b');
	if(!$is_zip && !$is_gz) {
		xn_lock_end('admin_update');
		@xn_unlink($tmpfile);
		message(-1, lang('admin_update_bad_package'));
	}

	// 4. 备份数据目录
	$backupdir = $conf['tmp_path'].'update_backup_'.date('Ymd_His').'/';
	@mkdir($backupdir, 0777, TRUE);
	foreach(array('conf', 'upload', 'log') as $_d) {
		$_src = APP_PATH.$_d;
		if(is_dir($_src)) copy_recusive($_src, $backupdir.$_d);
	}
	// 记录本次更新信息，便于回滚定位
	file_put_contents($backupdir.'update_info.txt', "version={$conf['version']}\ntime={$time}\ntag={$upinfo['tag']}\n");

	// 5. 解压到临时目录
	$extdir = $conf['tmp_path'].'update_ext_'.md5($download_url).'/';
	@xn_unlink($extdir);
	$ok = FALSE;
	if($is_zip) {
		xn_unzip($tmpfile, $extdir);
		$ok = is_dir($extdir);
	} elseif($is_gz) {
		$ok = admin_update_untgz($tmpfile, $extdir);
	}
	if(!$ok) {
		xn_lock_end('admin_update');
		@xn_unlink($tmpfile);
		@rmdir_recusive($extdir, 1);
		message(-1, lang('admin_update_unzip_failed'));
	}
	// 6. 定位源码根目录（解压可能多一层包裹目录）
	list($srcdir, $wrapdir) = admin_update_find_root($extdir);
	if(empty($srcdir) || !is_file($srcdir.'index.php')) {
		xn_lock_end('admin_update');
		@xn_unlink($tmpfile);
		@rmdir_recusive($extdir, 1);
		message(-1, lang('admin_update_bad_package'));
	}

	// 7. 覆盖主程序文件（保留配置/上传/临时/日志；插件目录：发行版含插件才覆盖）
	$skip_dirs = array('conf', 'upload', 'tmp', 'log');
	// 发行版 plugin 为空目录（无插件）时跳过，避免删除服务器已有插件
	$_src_plugin = $srcdir.'plugin/';
	if(!is_dir($_src_plugin) || empty(glob($_src_plugin.'*'))) {
		$skip_dirs[] = 'plugin';
	}
	admin_update_cover($srcdir, APP_PATH, $skip_dirs);

	// 8. 清理
	@xn_unlink($tmpfile);
	@rmdir_recusive($extdir, 1);
	rmdir_recusive($conf['tmp_path'], 1);		// 清编译缓存
	cache_truncate();							// 清缓存

	xn_lock_end('admin_update');

	$msg = lang('admin_update_success', array('version'=>$upinfo['tag']));
	$backupshown = str_replace(APP_PATH, '', $backupdir);
	$msg .= '<br>'.lang('admin_update_backup_done', array('dir'=>$backupshown));
	message(0, $msg);
}

// 下载大文件（不限制 4MB）
function http_get_full($url, $timeout = 120) {
	$opts = array(
		'http' => array(
			'method' => 'GET',
			'timeout' => $timeout,
			'header' => "User-Agent: XIUNO XW-Update\r\nAccept: application/vnd.github+json\r\n",
		),
		'ssl' => array(
			'verify_peer' => FALSE,
			'verify_peer_name' => FALSE,
		),
	);
	$ctx = stream_context_create($opts);
	return @file_get_contents($url, FALSE, $ctx);
}

// 解压 tar.gz / tgz（优先系统 tar，回退 PharData）
function admin_update_untgz($tmpfile, $extdir) {
	@mkdir($extdir, 0777, TRUE);
	// 优先使用系统 tar 命令（Linux 服务器必备，能正确处理 ./ 顶层条目）
	if(function_exists('exec')) {
		$cmd = "tar -xzf ".escapeshellarg($tmpfile)." -C ".escapeshellarg($extdir)." 2>&1";
		@exec($cmd, $output, $ret);
		if($ret === 0 && is_dir($extdir) && !empty(glob($extdir.'*'))) {
			return TRUE;
		}
	}
	// 回退 PharData
	if(class_exists('PharData')) {
		try {
			$phar = new PharData($tmpfile);
			$phar->extractTo($extdir, NULL, TRUE);
			return is_dir($extdir) && !empty(glob($extdir.'*'));
		} catch (Exception $e) {
			return FALSE;
		}
	}
	return FALSE;
}

// 定位解压后的源码根目录
function admin_update_find_root($extdir) {
	substr($extdir, -1) != '/' AND $extdir .= '/';
	$arr = glob($extdir.'*');
	if(empty($arr)) return array('', '');
	// 直接就是源码根
	if(is_file($extdir.'index.php')) return array($extdir, '');
	// 仅一个目录且含 index.php -> 剥一层
	if(count($arr) == 1 && is_dir($arr[0])) {
		$inner = $arr[0].'/';
		if(is_file($inner.'index.php')) return array($inner, basename($arr[0]));
	}
	return array($extdir, '');
}

// 递归覆盖目录：$srcdir 下的文件合并到 $dstdir，跳过 $skip 目录
function admin_update_cover($srcdir, $dstdir, $skip = array()) {
	substr($srcdir, -1) != '/' AND $srcdir .= '/';
	substr($dstdir, -1) != '/' AND $dstdir .= '/';
	$dir = opendir($srcdir);
	while(FALSE !== ($file = readdir($dir))) {
		if($file == '.' || $file == '..') continue;
		if(in_array($file, $skip)) continue;
		$src = $srcdir.$file;
		$dst = $dstdir.$file;
		if(is_dir($src)) {
			!is_dir($dst) AND mkdir($dst, 0777, TRUE);
			admin_update_cover($src, $dst, $skip);
		} else {
			xn_copy($src, $dst);
		}
	}
	closedir($dir);
}

// hook admin_func_end.php

?>