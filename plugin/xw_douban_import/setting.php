<?php

/*
	豆瓣影视批量导入 - 后台管理页
	URL: /admin/?plugin-setting-xw_douban_import.htm
	（框架已校验管理员身份、后台令牌、CSRF）
*/

!defined('DEBUG') AND exit('Access Denied.');

include _include(APP_PATH.'plugin/xw_douban_import/model/card.func.php');
include _include(APP_PATH.'plugin/xw_douban_import/model/importer.func.php');

$tablepre = $db->tablepre;

// 未安装时提示
if (!db_sql_find_one("SHOW TABLES LIKE '{$tablepre}xw_douban_import'")) {
	message(-1, '插件尚未安装，请先在插件列表中安装并启用');
}

$kv = kv_get('xw_douban_import');
if (empty($kv)) $kv = array('uid'=>1, 'fid'=>0, 'link_prefix'=>'下载链接：', 'skip_dup'=>1, 'merge_same'=>1, 'api_enable'=>0, 'api_token'=>'', 'direct_push'=>0);
$kv += array('uid'=>1, 'fid'=>0, 'link_prefix'=>'下载链接：', 'skip_dup'=>1, 'merge_same'=>1, 'api_enable'=>0, 'api_token'=>'', 'direct_push'=>0);

$action = param('action', '');

// ---------------- 页面渲染 ----------------

if ($action == '' && $method == 'GET') {

	// 版块列表
	$forumlist = array();
	$forumarr = db_sql_find("SELECT fid, name FROM {$tablepre}forum ORDER BY rank ASC");
	foreach ((array)$forumarr as $f) {
		$forumlist[] = array('fid'=>intval($f['fid']), 'name'=>$f['name']);
	}

	// 标签分类+标签（含绑定的版块 fid，前端按版块过滤）
	$tagdata = array();
	$cates = db_find('tag_cate', array(), array(), 1, 500);
	$tags = db_find('tag', array(), array(), 1, 2000);
	$tags_by_cate = array();
	foreach ((array)$tags as $t) {
		if (empty($t['enable'])) continue;
		$t['tagid'] = intval($t['tagid']);
		$t['cateid'] = intval($t['cateid']);
		$tags_by_cate[$t['cateid']][] = $t;
	}
	foreach ((array)$cates as $c) {
		if (empty($c['enable'])) continue;
		$cateid = intval($c['cateid']);
		$tagdata[] = array(
			'cateid' => $cateid,
			'fid' => intval($c['fid']),
			'name' => $c['name'],
			'tags' => isset($tags_by_cate[$cateid]) ? $tags_by_cate[$cateid] : array(),
		);
	}

	// 任务记录（最近 100 条）
	$tasklist = db_find('xw_douban_import', array(), array('id'=>-1), 1, 100);
	foreach ((array)$tasklist as &$v) {
		$v['id'] = intval($v['id']);
		$v['fid'] = intval($v['fid']);
		$v['uid'] = intval($v['uid']);
		$v['status'] = intval($v['status']);
		$v['tid'] = intval($v['tid']);
		$v['dateline'] = intval($v['dateline']);
	}
	unset($v);

	// 统计
	$count_pending = db_count('xw_douban_import', array('status'=>0));
	$count_claimed = db_count('xw_douban_import', array('status'=>3));
	$count_failed = db_count('xw_douban_import', array('status'=>2));
	$count_done = db_count('xw_douban_import', array('status'=>1));

	include _include(APP_PATH.'plugin/xw_douban_import/htm/setting.htm');
	exit;
}

// ---------------- AJAX 接口 ----------------

if ($action == 'parse') {
	$fid = param('fid', 0);
	$uid = param('uid', 1);
	$link_prefix = trim(param('link_prefix', '', FALSE));
	$skip_dup = param('skip_dup', 1);
	$merge_same = param('merge_same', 1);
	$tagids = preg_replace('/[^0-9,]/', '', param('tagids', '', FALSE));
	$tagids = trim($tagids, ',');

	// 解析来源：上传文件优先，其次粘贴文本
	if (!empty($_FILES['file']['name'])) {
		$items = xwdi_parse_upload_file($_FILES['file']);
	} else {
		$text = param('text', '', FALSE);
		$items = xwdi_parse_lines(xwdi_to_utf8($text));
	}

	if ($items instanceof RuntimeException) {
		message(-1, $items->getMessage());
	}
	if (empty($items)) {
		message(-1, '没有解析到有效数据。请确认格式：每行一条「影视名|网盘链接」');
	}

	$arr = xwdi_tasks_add($items, $fid, $uid, $tagids, $skip_dup);

	// 记住本次选择作为默认值
	kv_set('xw_douban_import', array(
		'uid' => $uid,
		'fid' => $fid,
		'link_prefix' => ($link_prefix !== '' ? $link_prefix : '下载链接：'),
		'skip_dup' => $skip_dup,
		'merge_same' => $merge_same,
	));

	echo json_encode(array('code'=>0, 'message'=>'ok', 'data'=>$arr));
	exit;
}

if ($action == 'reset') {
	$id = param('id', 0);
	$new_title = trim(param('new_title', '', FALSE));
	$task = db_find_one('xw_douban_import', array('id'=>$id));
	if (empty($task)) message(-1, '任务不存在');

	// 支持改名后重置
	$update = array('status'=>0, 'err'=>'', 'lock_time'=>0);
	if ($new_title !== '') {
		$new_title = mb_substr(preg_replace('/\s+/u', ' ', $new_title), 0, 120, 'UTF-8');
		$update['title'] = $new_title;
		$update['hash'] = md5($new_title."\n".$task['link']);
	}
	db_update('xw_douban_import', array('id'=>$id), $update);
	message(0, '任务已重置为待处理，等待本地客户端拉取发布');
}

if ($action == 'export') {

	// 防止 Excel 公式注入：以 = + - @ 开头的单元格前置单引号
	function xwdi_csv_safe($v) {
		$v = (string)$v;
		return (isset($v[0]) && in_array($v[0], array('=', '+', '-', '@'))) ? "'".$v : $v;
	}

	header('Content-Type: application/csv; charset=UTF-8');
	header('Content-Disposition: attachment; filename="douban_import_tasks_'.date('Ymd_His').'.csv"');
	header('Pragma: no-cache');

	$fp = fopen('php://output', 'w');
	fwrite($fp, "\xEF\xBB\xBF");	// UTF-8 BOM，Excel 中文不乱码
	fputcsv($fp, array('ID', '影视名', '网盘链接', '版块fid', '发帖UID', '标签tagids', '状态', '主题TID', '备注', '导入时间'));

	$rows = db_find('xw_douban_import', array(), array('id'=>-1), 1, 5000);
	foreach ((array)$rows as $r) {
		if ($r['status'] == 1) {
			$status = empty($r['err']) ? '成功(新帖)' : '已追加';
			$note = $r['err'];
		} elseif ($r['status'] == 2) {
			$status = '失败';
			$note = $r['err'];
		} else {
			$status = '待处理';
			$note = '';
		}
		fputcsv($fp, array(
			intval($r['id']),
			xwdi_csv_safe($r['title']),
			xwdi_csv_safe($r['link']),
			intval($r['fid']),
			intval($r['uid']),
			$r['tagids'],
			$status,
			intval($r['tid']),
			xwdi_csv_safe($note),
			date('Y-m-d H:i:s', intval($r['dateline'])),
		));
	}
	fclose($fp);
	exit;
}

if ($action == 'pending') {
	$rows = db_find('xw_douban_import', array('status'=>0), array('id'=>1), 1, 5000);
	$ids = array();
	foreach ((array)$rows as $r0) $ids[] = intval($r0['id']);
	echo json_encode(array('code'=>0, 'message'=>'ok', 'data'=>array('ids'=>$ids)));
	exit;
}

if ($action == 'batch_publish') {
	$ids = isset($_POST['ids']) ? $_POST['ids'] : array();
	if (!is_array($ids) || empty($ids)) message(-1, '未选择任务');
	$ok = $fail = $skip = 0;
	$details = array();
	foreach ($ids as $id) {
		$id = intval($id);
		$task = db_find_one('xw_douban_import', array('id'=>$id));
		if (empty($task)) { $skip++; continue; }
		if (intval($task['status']) == 1) { $skip++; continue; }
		if (empty($task['title'])) { $skip++; continue; }
		// 没有采集数据的待处理任务，需要客户端先推送数据才能发布
		if (intval($task['status']) == 0 && empty($task['tid'])) {
			// 检查是否有 payload_data（从 API 直推模式来的任务会有 data）
			$fail++;
			$details[] = "#{$task['id']} {$task['title']}: 需先由本地客户端推送采集数据";
			continue;
		}
		try {
			$r = xwdi_task_process($task);
			$note = '';
			if (!empty($r['merged'])) {
				$note = empty($r['dup']) ? '已追加到旧帖' : '链接已存在于旧帖';
			}
			db_update('xw_douban_import', array('id'=>$id), array('status'=>1, 'tid'=>intval($r['tid']), 'err'=>$note, 'lock_time'=>0));
			$ok++;
			$details[] = "#{$task['id']} {$task['title']}: → #{$r['tid']}";
		} catch (Throwable $e) {
			$err = mb_substr($e->getMessage(), 0, 200, 'UTF-8');
			db_update('xw_douban_import', array('id'=>$id), array('status'=>2, 'err'=>$err, 'lock_time'=>0));
			$fail++;
			$details[] = "#{$task['id']} {$task['title']}: {$err}";
		}
	}
	echo json_encode(array('code'=>0, 'message'=>"发布完成：成功 $ok，失败 $fail，跳过 $skip", 'data'=>array('ok'=>$ok, 'fail'=>$fail, 'skip'=>$skip, 'details'=>$details)));
	exit;
}

if ($action == 'clear') {
	$mode = param_word('mode');
	if ($mode == 'done') {
		db_delete('xw_douban_import', array('status'=>1));
	} elseif ($mode == 'failed') {
		db_delete('xw_douban_import', array('status'=>2));
	} else {
		db_delete('xw_douban_import', array());
	}
	message(0, '清理完成');
}

// ---------------- API 设置 ----------------

if ($action == 'api_save') {
	$api_enable = param('api_enable', 0) ? 1 : 0;
	$direct_push = param('direct_push', 0) ? 1 : 0;
	$kv = kv_get('xw_douban_import');
	$kv['api_enable'] = $api_enable;
	$kv['direct_push'] = $direct_push;
	kv_set('xw_douban_import', $kv);
	message(0, 'API 设置已保存');
}

if ($action == 'api_regen_token') {
	$kv = kv_get('xw_douban_import');
	$kv['api_token'] = bin2hex(random_bytes(24));
	kv_set('xw_douban_import', $kv);
	echo json_encode(array('code'=>0, 'message'=>'ok', 'data'=>array('token'=>$kv['api_token'])));
	exit;
}

message(-1, '未知操作');
