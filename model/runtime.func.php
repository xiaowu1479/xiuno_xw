<?php

$g_runtime_dirty = FALSE;
$g_runtime_ops = array();		// 本次请求待合并的操作：array(键, '+|=' , 值)
$g_runtime_deletes = array();	// 本次请求待删除的键

// hook model_runtime_start.php

function runtime_init() {
	// hook model_runtime_init_start.php
	global $conf;
	$runtime = cache_get('runtime'); // 实时运行的数据，初始化！
	if(!is_array($runtime) || !isset($runtime['users'])) {
		$runtime = array();
		$runtime['users'] = user_count();
		$runtime['posts'] = post_count(['pid' => ['>' => 0]]); // 强制精确 COUNT(*)
		$runtime['threads'] = thread_count(['tid' => ['>' => 0]]); // 强制精确 COUNT(*)
		$runtime['posts'] -= $runtime['threads']; // 减去首帖
		$runtime['todayusers'] = 0;
		$runtime['todayposts'] = 0;
		$runtime['todaythreads'] = 0;
		$runtime['onlines'] = max(1, online_count());
		$runtime['cron_1_last_date'] = 0;
		$runtime['cron_2_last_date'] = 0;
		
		// 并发重建时加锁，避免用旧快照覆盖其他请求刚写入的计数
		$lock = runtime_lock();
		$latest = cache_get('runtime');
		if(is_array($latest) && isset($latest['users'])) {
			$runtime = $latest;
		} else {
			cache_set('runtime', $runtime);
		}
		runtime_unlock($lock);
		
	}
	// hook model_runtime_init_end.php
	return $runtime;
}

function runtime_get($k) {
	// hook model_runtime_get_start.php
	global $runtime;
	// hook model_runtime_get_end.php
	return array_value($runtime, $k, NULL);
}

function runtime_set($k, $v) {
	// hook model_runtime_set_start.php
	global $conf, $runtime, $g_runtime_dirty, $g_runtime_ops;
	$op = substr($k, -1);
	if($op == '+' || $op == '-') {
		$k = substr($k, 0, -1);
		$v = intval($v);
		$op == '-' AND $v = -$v;
		!isset($runtime[$k]) AND $runtime[$k] = 0;
		$runtime[$k] += $v;
		$g_runtime_ops[] = array($k, '+', $v);
	} else {
		$runtime[$k] = $v;
		$g_runtime_ops[] = array($k, '=', $v);
	}
	$g_runtime_dirty = TRUE;
	return TRUE;
	// hook model_runtime_set_end.php
}

function runtime_delete($k) {
	// hook model_runtime_delete_start.php
	global $runtime, $g_runtime_dirty, $g_runtime_deletes;
	unset($runtime[$k]);
	$g_runtime_deletes[$k] = $k;
	$g_runtime_dirty = TRUE;
	runtime_save();
	return TRUE;
	// hook model_runtime_delete_end.php
}

function runtime_save() {
	// hook model_runtime_save_start.php
	global $runtime, $g_runtime_dirty, $g_runtime_ops, $g_runtime_deletes;
	
	if(!$g_runtime_dirty) return;
	$g_runtime_dirty = FALSE;
	
	$ops = $g_runtime_ops;
	$deletes = $g_runtime_deletes;
	$g_runtime_ops = array();
	$g_runtime_deletes = array();
	
	function_exists('chdir') AND chdir(APP_PATH);
	
	// 关键：先锁住 runtime，再读取最新缓存并合并本次请求的操作。
	// 直接写回整个数组会让并发请求的旧快照互相覆盖，导致用户数/帖子数时对时错。
	$lock = runtime_lock();
	$latest = cache_get('runtime');
	if(!is_array($latest) || !isset($latest['users'])) {
		// 缓存已丢失，以本次请求的完整快照为准
		$latest = is_array($runtime) ? $runtime : array();
	} else {
		foreach($ops as $op) {
			list($k, $o, $v) = $op;
			if($o == '+') {
				!isset($latest[$k]) AND $latest[$k] = 0;
				$latest[$k] += $v;
			} else {
				$latest[$k] = $v;
			}
		}
		foreach($deletes as $k) {
			unset($latest[$k]);
		}
	}
	$runtime = $latest;
	cache_set('runtime', $runtime);
	runtime_unlock($lock);
	
	// hook model_runtime_save_end.php
}

// runtime 写操作的文件锁（同机多进程有效；跨机部署需改用共享缓存/数据库锁）
function runtime_lock() {
	$file = APP_PATH.'tmp/runtime.lock';
	$fp = @fopen($file, 'c');
	if(!$fp) return NULL;
	@flock($fp, LOCK_EX);
	return $fp;
}

function runtime_unlock($fp) {
	if(!$fp) return;
	@flock($fp, LOCK_UN);
	@fclose($fp);
}

function runtime_truncate() {
	// hook model_runtime_truncate_start.php
	global $conf;
	cache_delete('runtime');
	// hook model_runtime_truncate_end.php
}

register_shutdown_function('runtime_save');

// hook model_runtime_end.php

?>
