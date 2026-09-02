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

	// 4. 备份主程序（可回滚）：备份到独立 backup/ 目录，不被临时目录清理删除
	$backupdir = admin_update_backup_create($upinfo['tag']);

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
	$skip_dirs = array('conf', 'upload', 'tmp', 'log', 'backup');
	// 发行版 plugin 为空目录（无插件）时跳过，避免删除服务器已有插件
	$_src_plugin = $srcdir.'plugin/';
	if(!is_dir($_src_plugin) || empty(glob($_src_plugin.'*'))) {
		$skip_dirs[] = 'plugin';
	}
	admin_update_cover($srcdir, APP_PATH, $skip_dirs);

	// 7.5 执行数据库升级脚本
	$db_upgrade = admin_update_run_db_upgrade($srcdir);

	// 8. 清理（保留 backup/ 目录，回滚用）
	@xn_unlink($tmpfile);
	@rmdir_recusive($extdir, 1);
	rmdir_recusive($conf['tmp_path'], 1);		// 清编译缓存
	cache_truncate();							// 清缓存
	admin_update_backup_rotate();				// 只保留最近 2 份备份

	xn_lock_end('admin_update');

	$msg = lang('admin_update_success', array('version'=>$upinfo['tag']));
	$backupshown = str_replace(APP_PATH, '', $backupdir);
	$msg .= '<br>'.lang('admin_update_backup_done', array('dir'=>$backupshown));
	if(isset($db_upgrade['msg'])) $msg .= '<br>'.$db_upgrade['msg'];
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

// 检查 SQL 语句是否已应用（预检查，避免重复执行报错）
function admin_update_check_applied($stmt) {
	global $db;
	// ADD COLUMN → 检查列是否已存在
	if(preg_match('/ALTER\s+TABLE\s+(\S+)\s+ADD\s+COLUMN\s+(\S+)/i', $stmt, $m)) {
		$r = db_sql_find_one("SHOW COLUMNS FROM `{$m[1]}` LIKE '{$m[2]}'");
		return $r !== false;
	}
	// ADD KEY/INDEX → 检查索引是否已存在
	if(preg_match('/ALTER\s+TABLE\s+(\S+)\s+ADD\s+(?:KEY|INDEX)\s+(\w+)/i', $stmt, $m)) {
		$r = db_sql_find_one("SHOW KEYS FROM `{$m[1]}` WHERE Key_name='{$m[2]}'");
		return $r !== false;
	}
	// DROP KEY/INDEX → 检查索引是否已不存在
	if(preg_match('/ALTER\s+TABLE\s+(\S+)\s+DROP\s+(?:KEY|INDEX)\s+(\w+)/i', $stmt, $m)) {
		$r = db_sql_find_one("SHOW KEYS FROM `{$m[1]}` WHERE Key_name='{$m[2]}'");
		return $r === false;
	}
	// CREATE TABLE IF NOT EXISTS → 永远不跳过（安全语句）
	if(preg_match('/CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS/i', $stmt)) return false;
	return false;
}

// 判断数据库错误是否无害（重复执行导致，不影响功能）
function admin_update_is_harmless_error($err) {
	$patterns = array(
		'/Duplicate column name/i',
		'/Duplicate key name/i',
		'/already exists/i',
		'/Key .* does not exist/i',
		'/doesn\'t have a key/i',
	);
	foreach($patterns as $p) {
		if(preg_match($p, $err)) return true;
	}
	return false;
}

// 从 SQL 语句中提取简短描述（表名 + 操作）
function admin_update_describe_sql($stmt) {
	if(preg_match('/ALTER\s+TABLE\s+(\S+)\s+ADD\s+COLUMN\s+(\S+)/i', $stmt, $m)) return "ADD COLUMN {$m[1]}.{$m[2]}";
	if(preg_match('/ALTER\s+TABLE\s+(\S+)\s+ADD\s+(?:KEY|INDEX)\s+(\w+)/i', $stmt, $m)) return "ADD KEY {$m[1]}.{$m[2]}";
	if(preg_match('/ALTER\s+TABLE\s+(\S+)\s+DROP\s+(?:KEY|INDEX)\s+(\w+)/i', $stmt, $m)) return "DROP KEY {$m[1]}.{$m[2]}";
	if(preg_match('/ALTER\s+TABLE\s+(\S+)\s+DROP\s+COLUMN\s+(\S+)/i', $stmt, $m)) return "DROP COLUMN {$m[1]}.{$m[2]}";
	if(preg_match('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?(\S+)/i', $stmt, $m)) return "CREATE TABLE {$m[1]}";
	return substr(trim($stmt), 0, 60);
}

// 执行数据库升级脚本（智能检查 + 分类结果）
function admin_update_run_db_upgrade($srcdir) {
	global $db;
	$upgrade_file = $srcdir.'install/upgrade.sql';
	if(!is_file($upgrade_file)) return array('ok' => true, 'msg' => '无升级脚本');

	$sql_content = @file_get_contents($upgrade_file);
	if(empty($sql_content)) return array('ok' => true, 'msg' => '升级脚本为空');

	// 分割 SQL 语句（简单按 ; 分割，去除注释和空行）
	$statements = array();
	$lines = explode("\n", $sql_content);
	$current = '';
	foreach($lines as $line) {
		$line = trim($line);
		if($line === '' || strpos($line, '#') === 0 || strpos($line, '--') === 0) continue;
		$current .= ' '.$line;
		if(substr($line, -1) === ';') {
			$stmt = trim($current);
			if($stmt !== ';') $statements[] = $stmt;
			$current = '';
		}
	}

	$executed = 0;
	$skipped = 0;
	$errors = array();
	$details = array();

	foreach($statements as $stmt) {
		$desc = admin_update_describe_sql($stmt);

		// 预检查：是否已应用
		if(admin_update_check_applied($stmt)) {
			$skipped++;
			$details[] = array('status' => 'skip', 'desc' => $desc, 'msg' => '已应用，无需执行');
			continue;
		}

		// 执行
		try {
			$r = $db->exec($stmt);
			if($r !== FALSE) {
				$executed++;
				$details[] = array('status' => 'ok', 'desc' => $desc, 'msg' => '执行成功');
				continue;
			}
			$err = $db->errstr;
		} catch(Exception $e) {
			$err = $e->getMessage();
		}

		// 判断错误是否无害
		if(admin_update_is_harmless_error($err)) {
			$skipped++;
			$details[] = array('status' => 'skip', 'desc' => $desc, 'msg' => '已存在，无需重复执行');
		} else {
			$errors[] = $err . " | SQL: " . substr($stmt, 0, 100);
			$details[] = array('status' => 'error', 'desc' => $desc, 'msg' => $err);
		}
	}

	// 构建消息
	$total = count($statements);
	$msg = "数据库升级完成：共 $total 条语句";
	if($executed > 0) $msg .= "，执行 $executed 条";
	if($skipped > 0) $msg .= "，跳过 $skipped 条（已应用）";
	if($errors) $msg .= "，错误 " . count($errors) . " 条";
	$msg .= "\n";
	foreach($details as $d) {
		$icon = $d['status'] === 'ok' ? '✓' : ($d['status'] === 'skip' ? '○' : '✗');
		$msg .= "$icon {$d['desc']}（{$d['msg']}）\n";
	}

	return array('ok' => empty($errors), 'msg' => $msg, 'executed' => $executed, 'skipped' => $skipped, 'errors' => $errors, 'details' => $details);
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

// ==================== 更新备份 / 回滚 (XIUNO XW) ====================

// 备份根目录（独立于 tmp/，不会被清临时文件删除）
function admin_update_backup_dir() {
	return APP_PATH.'backup/';
}

// 备份目录防 Web 访问：写入拒绝规则 + 空 index.html，防止备份内容（含程序/配置）被直接下载
function admin_update_backup_protect($dir) {
	!is_file($dir.'index.html') AND @file_put_contents($dir.'index.html', '');
	!is_file($dir.'.htaccess') AND @file_put_contents($dir.'.htaccess', "Order allow,deny\nDeny from all\nRequire all denied\n");
}

// 备份当前主程序：复制 APP_PATH 下除数据/缓存/配置目录外的全部文件到 backup/update_YYYYmmdd_HHMMSS/
function admin_update_backup_create($tag = '') {
	global $conf, $time;
	$dir = admin_update_backup_dir();
	!is_dir($dir) AND mkdir($dir, 0777, TRUE);
	admin_update_backup_protect($dir);
	$backupdir = $dir.'update_'.date('Ymd_His').'/';
	mkdir($backupdir, 0777, TRUE);
	// 备份主程序（插件 / 模板 / 主文件），跳过数据、缓存、配置目录（update 从不覆盖 conf/upload/log）
	$skip = array('conf', 'upload', 'log', 'tmp', 'backup');
	admin_update_cover(APP_PATH, $backupdir, $skip);
	// 记录本次备份信息，便于回滚定位与展示
	file_put_contents($backupdir.'update_info.txt', "version={$conf['version']}\ntime={$time}\ntag={$tag}\n");
	return $backupdir;
}

// 备份目录列表（按时间倒序，新的在前）
function admin_update_backup_list() {
	$dir = admin_update_backup_dir();
	$arr = glob($dir.'update_*');
	$list = array();
	if($arr) foreach($arr as $dir1) {
		if(!is_dir($dir1) || strpos(basename($dir1), 'update_') !== 0) continue;
		$info = array('version'=>'', 'time'=>0, 'tag'=>'');
		$infile = $dir1.'/update_info.txt';
		if(is_file($infile)) {
			foreach(file($infile, FILE_IGNORE_NEW_LINES) as $line) {
				if(strpos($line, '=') === FALSE) continue;
				list($k, $v) = explode('=', $line, 2);
				$info[$k] = $v;
			}
		}
		$list[] = array(
			'dir' => basename($dir1),
			'path' => $dir1,
			'version' => $info['version'],
			'time' => empty($info['time']) ? @filemtime($dir1) : intval($info['time']),
			'tag' => $info['tag'],
		);
	}
	usort($list, function($a, $b) { return $b['time'] - $a['time']; });
	return $list;
}

// 只保留最近 $keep 份备份，删除更早的
function admin_update_backup_rotate($keep = 2) {
	$list = admin_update_backup_list();
	foreach(array_slice($list, $keep) as $backup) {
		rmdir_recusive($backup['path']);
	}
}

// 删除指定备份目录
function admin_update_backup_delete($dirname) {
	$dirname = preg_replace('#[^\w-]#', '', $dirname);
	if(strpos($dirname, 'update_') !== 0) message(-1, lang('admin_update_backup_invalid'));
	$path = admin_update_backup_dir().$dirname;
	if(!is_dir($path)) message(-1, lang('admin_update_backup_not_exists'));
	rmdir_recusive($path);
	message(0, lang('admin_update_backup_deleted'));
}

// 回滚到指定备份目录
function admin_update_rollback($dirname) {
	global $conf;
	csrf_check();
	$dirname = preg_replace('#[^\w-]#', '', $dirname);
	if(strpos($dirname, 'update_') !== 0) message(-1, lang('admin_update_backup_invalid'));
	$backupdir = admin_update_backup_dir().$dirname.'/';
	if(!is_dir($backupdir) || !is_file($backupdir.'update_info.txt')) message(-1, lang('admin_update_backup_not_exists'));

	set_time_limit(0);
	@ini_set('memory_limit', '256M');

	// 回滚前先备份当前状态，防止误操作（并入最近 2 份保留机制）
	$curdir = admin_update_backup_create('rollback_before');

	// 恢复备份：跳过数据/缓存/备份目录，防止覆盖用户数据
	$skip = array('upload', 'log', 'tmp', 'backup');
	admin_update_cover($backupdir, APP_PATH, $skip);

	// 清编译缓存 + 缓存
	rmdir_recusive($conf['tmp_path'], 1);
	cache_truncate();
	admin_update_backup_rotate();

	$msg = lang('admin_update_rollback_success', array('dir'=>$dirname));
	message(0, $msg);
}

// ==================== 数据库健康检查 (XIUNO XW) ====================

// 解析 upgrade.sql 为结构化步骤
function admin_db_parse_upgrade_sql($srcdir = '') {
	if(empty($srcdir)) $srcdir = APP_PATH;
	$upgrade_file = $srcdir.'install/upgrade.sql';
	if(!is_file($upgrade_file)) return array();

	$sql_content = @file_get_contents($upgrade_file);
	if(empty($sql_content)) return array();

	$statements = array();
	$lines = explode("\n", $sql_content);
	$current = '';
	foreach($lines as $line) {
		$line = trim($line);
		if($line === '' || strpos($line, '#') === 0 || strpos($line, '--') === 0) continue;
		$current .= ' '.$line;
		if(substr($line, -1) === ';') {
			$stmt = trim($current);
			if($stmt !== ';') $statements[] = $stmt;
			$current = '';
		}
	}

	$steps = array();
	foreach($statements as $stmt) {
		$desc = admin_update_describe_sql($stmt);
		$check = admin_db_build_check($stmt);
		$fix = $stmt;
		$steps[] = array(
			'sql' => $stmt,
			'desc' => $desc,
			'check' => $check,
			'fix' => $fix,
		);
	}
	return $steps;
}

// 根据 SQL 语句生成检查语句
function admin_db_build_check($stmt) {
	global $db;
	// ADD COLUMN → 检查列是否存在
	if(preg_match('/ALTER\s+TABLE\s+(\S+)\s+ADD\s+COLUMN\s+(\S+)/i', $stmt, $m)) {
		return array(
			'type' => 'column',
			'table' => $m[1],
			'name' => $m[2],
			'sql' => "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$m[1]}' AND COLUMN_NAME = '{$m[2]}'",
		);
	}
	// ADD KEY/INDEX → 检查索引是否存在
	if(preg_match('/ALTER\s+TABLE\s+(\S+)\s+ADD\s+(?:KEY|INDEX)\s+(\w+)/i', $stmt, $m)) {
		return array(
			'type' => 'index',
			'table' => $m[1],
			'name' => $m[2],
			'sql' => "SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$m[1]}' AND INDEX_NAME = '{$m[2]}'",
		);
	}
	// DROP KEY/INDEX → 检查索引是否已不存在
	if(preg_match('/ALTER\s+TABLE\s+(\S+)\s+DROP\s+(?:KEY|INDEX)\s+(\S+)/i', $stmt, $m)) {
		return array(
			'type' => 'index_absent',
			'table' => $m[1],
			'name' => $m[2],
			'sql' => "SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$m[1]}' AND INDEX_NAME = '{$m[2]}'",
		);
	}
	// DROP COLUMN → 检查列是否已不存在
	if(preg_match('/ALTER\s+TABLE\s+(\S+)\s+DROP\s+COLUMN\s+(\S+)/i', $stmt, $m)) {
		return array(
			'type' => 'column_absent',
			'table' => $m[1],
			'name' => $m[2],
			'sql' => "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$m[1]}' AND COLUMN_NAME = '{$m[2]}'",
		);
	}
	// CREATE TABLE → 检查表是否存在
	if(preg_match('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?(\S+)/i', $stmt, $m)) {
		$tbl = preg_replace('/^`?(\w+)`?.*$/', '$1', $m[1]);
		return array(
			'type' => 'table',
			'table' => $tbl,
			'name' => $tbl,
			'sql' => "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$tbl}'",
		);
	}
	return null;
}

// 检查单条 SQL 是否已应用
function admin_db_check_step($step) {
	global $db;
	$check = $step['check'];
	if(!$check) return array('applied' => false, 'reason' => '无法自动检查');

	$r = db_sql_find_one($check['sql']);
	switch($check['type']) {
		case 'column':
			return array('applied' => !empty($r), 'reason' => !empty($r) ? '字段已存在' : '字段缺失');
		case 'index':
			return array('applied' => !empty($r), 'reason' => !empty($r) ? '索引已存在' : '索引缺失');
		case 'index_absent':
			return array('applied' => empty($r), 'reason' => empty($r) ? '索引已删除' : '索引仍存在');
		case 'column_absent':
			return array('applied' => empty($r), 'reason' => empty($r) ? '字段已删除' : '字段仍存在');
		case 'table':
			return array('applied' => !empty($r), 'reason' => !empty($r) ? '表已存在' : '表缺失');
		default:
			return array('applied' => false, 'reason' => '未知检查类型');
	}
}

// 执行数据库健康检查
function admin_db_check() {
	$steps = admin_db_parse_upgrade_sql();
	$results = array();
	$ok_count = 0;
	$fix_count = 0;
	$fail_count = 0;

	foreach($steps as $i => $step) {
		$check_result = admin_db_check_step($step);
		$applied = $check_result['applied'];
		$results[] = array(
			'index' => $i + 1,
			'desc' => $step['desc'],
			'sql' => $step['sql'],
			'applied' => $applied,
			'reason' => $check_result['reason'],
			'needs_fix' => !$applied && $step['check'] !== null,
		);
		if($applied) {
			$ok_count++;
		} elseif($step['check'] !== null) {
			$fix_count++;
		} else {
			$fail_count++;
		}
	}

	return array(
		'ok' => true,
		'results' => $results,
		'summary' => array(
			'total' => count($steps),
			'ok' => $ok_count,
			'need_fix' => $fix_count,
			'unknown' => $fail_count,
		),
	);
}

// 一键修复数据库（只执行缺失的部分）
function admin_db_fix() {
	$steps = admin_db_parse_upgrade_sql();
	$executed = 0;
	$skipped = 0;
	$errors = array();
	$details = array();

	foreach($steps as $i => $step) {
		$desc = $step['desc'];

		// 检查是否已应用
		if($step['check']) {
			$check_result = admin_db_check_step($step);
			if($check_result['applied']) {
				$skipped++;
				$details[] = array('status' => 'skip', 'desc' => $desc, 'msg' => $check_result['reason']);
				continue;
			}
		}

		// 执行
		try {
			$r = db_exec($step['fix']);
			if($r !== FALSE) {
				$executed++;
				$details[] = array('status' => 'ok', 'desc' => $desc, 'msg' => '修复成功');
				continue;
			}
			$err = $db->errstr;
		} catch(Exception $e) {
			$err = $e->getMessage();
		}

		if(admin_update_is_harmless_error($err)) {
			$skipped++;
			$details[] = array('status' => 'skip', 'desc' => $desc, 'msg' => '已存在，无需重复执行');
		} else {
			$errors[] = $err;
			$details[] = array('status' => 'error', 'desc' => $desc, 'msg' => $err);
		}
	}

	$msg = "修复完成：共 " . count($steps) . " 条";
	if($executed > 0) $msg .= "，执行 $executed 条";
	if($skipped > 0) $msg .= "，跳过 $skipped 条";
	if($errors) $msg .= "，错误 " . count($errors) . " 条";

	return array(
		'ok' => empty($errors),
		'msg' => $msg,
		'executed' => $executed,
		'skipped' => $skipped,
		'errors' => $errors,
		'details' => $details,
	);
}

// hook admin_func_end.php

?>