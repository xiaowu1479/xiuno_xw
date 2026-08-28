<?php

/*
	豆瓣影视批量导入 - 导入解析 / 任务处理 / 发布
*/

define('XWDI_BATCH_LIMIT', 500);		// 单次最多解析条数
define('XWDI_UPLOAD_MAX_SIZE', 2097152);	// 上传文件上限 2MB

// ---------------- 文件/文本解析 ----------------

// 解析「影视名|网盘链接」文本，返回 array(array('title'=>..,'link'=>..), ..)
function xwdi_parse_lines($text) {
	$items = array();
	$text = str_replace(array("\r\n", "\r"), "\n", (string)$text);
	$lines = explode("\n", $text);
	foreach ($lines as $line) {
		$line = trim($line);
		if ($line === '' || $line[0] === '#') continue;
		// 支持半角|、全角｜、Tab 分隔
		$parts = preg_split('/\t|\||｜/u', $line);
		if (!$parts) continue;
		$title = trim((string)$parts[0]);
		if ($title === '') continue;
		$link = isset($parts[1]) ? trim(implode('|', array_slice($parts, 1))) : '';
		$items[] = array('title' => $title, 'link' => $link);
		if (count($items) >= XWDI_BATCH_LIMIT) break;
	}
	return $items;
}

// 文本编码修正：GBK 等自动转 UTF-8
function xwdi_to_utf8($text) {
	if (mb_check_encoding($text, 'UTF-8')) return $text;
	$converted = @mb_convert_encoding($text, 'UTF-8', 'GBK');
	return $converted === false ? $text : $converted;
}

// 解析 xlsx：取每个 Sheet 行的第 1 列为影视名、第 2 列为网盘链接
function xwdi_parse_xlsx($path) {
	if (!class_exists('ZipArchive')) return new RuntimeException('服务器 PHP 缺少 zip 扩展，无法解析 xlsx，请改用 txt/csv');
	$zip = new ZipArchive();
	if ($zip->open($path) !== TRUE) return new RuntimeException('xlsx 文件打开失败');

	// 读取共享字符串
	$shared = array();
	$ss = $zip->getFromName('xl/sharedStrings.xml');
	if ($ss !== false) {
		$xml = @simplexml_load_string($ss);
		if ($xml !== false) {
			foreach ($xml->si as $si) {
				$t = '';
				// rich text: 多个 <r><t>
				foreach ($si->r as $r) { $t .= (string)$r->t; }
				if ($t === '') $t = (string)$si->t;
				$shared[] = $t;
			}
		}
	}

	// 找第一个 sheet
	$sheetPath = false;
	for ($i = 1; $i <= 5; $i++) {
		$name = $i == 1 ? 'xl/worksheets/sheet1.xml' : "xl/worksheets/sheet$i.xml";
		if ($zip->locateName($name) !== false) { $sheetPath = $name; break; }
	}
	if ($sheetPath === false) { $zip->close(); return new RuntimeException('xlsx 中未找到工作表'); }
	$data = $zip->getFromName($sheetPath);
	$zip->close();

	$xml = @simplexml_load_string($data);
	if ($xml === false) return new RuntimeException('xlsx 内容解析失败');

	$items = array();
	foreach ($xml->sheetData->row as $row) {
		$cells = array();
		foreach ($row->c as $c) {
			$ref = isset($c['r']) ? (string)$c['r'] : '';
			preg_match('/^([A-Z]+)/', $ref, $m);
			$col = isset($m[1]) ? xwdi_col_index($m[1]) : count($cells);
			$type = isset($c['t']) ? (string)$c['t'] : '';
			$val = '';
			if ($type === 's') {
				$idx = intval($c->v);
				$val = isset($shared[$idx]) ? (string)$shared[$idx] : '';
			} elseif ($type === 'inlineStr') {
				$val = isset($c->is->t) ? (string)$c->is->t : '';
			} else {
				$val = isset($c->v) ? (string)$c->v : '';
			}
			$cells[$col] = trim($val);
		}
		if (!isset($cells[0]) || $cells[0] === '') continue;
		$title = trim(preg_replace('/\s+/u', ' ', $cells[0]));
		if ($title === '' || in_array(strtolower($title), array('影视名', '标题', '片名', '名称'), true)) continue; // 跳过表头
		$link = isset($cells[1]) ? trim($cells[1]) : '';
		$items[] = array('title' => $title, 'link' => $link);
		if (count($items) >= XWDI_BATCH_LIMIT) break;
	}
	return $items;
}

function xwdi_col_index($letters) {
	$n = 0;
	$len = strlen($letters);
	for ($i = 0; $i < $len; $i++) {
		$n = $n * 26 + (ord($letters[$i]) - 64);
	}
	return max(0, $n - 1);
}

// 解析 csv：第 1 列影视名、第 2 列网盘链接
function xwdi_parse_csv($path) {
	$items = array();
	$fh = @fopen($path, 'r');
	if (!$fh) return new RuntimeException('csv 文件打开失败');
	while (($row = fgetcsv($fh)) !== false) {
		if (empty($row) || !isset($row[0])) continue;
		$row[0] = xwdi_to_utf8(trim((string)$row[0]));
		if ($row[0] === '') continue;
		$title = trim(preg_replace('/\s+/u', ' ', $row[0]));
		if (in_array(strtolower($title), array('影视名', '标题', '片名', '名称'), true)) continue;
		$link = isset($row[1]) ? xwdi_to_utf8(trim((string)$row[1])) : '';
		$items[] = array('title' => $title, 'link' => $link);
		if (count($items) >= XWDI_BATCH_LIMIT) break;
	}
	fclose($fh);
	return $items;
}

// 统一入口：根据扩展名解析上传文件
function xwdi_parse_upload_file($file) {
	if (empty($file) || !is_array($file) || $file['error'] != UPLOAD_ERR_OK) {
		return new RuntimeException('文件上传失败，请重试');
	}
	if ($file['size'] > XWDI_UPLOAD_MAX_SIZE) {
		return new RuntimeException('文件超过 2MB 限制');
	}
	$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
	if ($ext === 'txt') {
		$text = @file_get_contents($file['tmp_name']);
		if ($text === false) return new RuntimeException('txt 文件读取失败');
		return xwdi_parse_lines(xwdi_to_utf8($text));
	}
	if ($ext === 'csv') {
		return xwdi_parse_csv($file['tmp_name']);
	}
	if ($ext === 'xlsx') {
		return xwdi_parse_xlsx($file['tmp_name']);
	}
	return new RuntimeException('不支持的文件类型 .'.$ext.'，仅支持 txt/csv/xlsx');
}

// ---------------- 任务表操作 ----------------

function xwdi_task_hash($title, $link) {
	return md5($title."\n".$link);
}

// 批量入库，返回 array(total, inserted, skipped)
function xwdi_tasks_add($items, $fid, $uid, $tagids, $skip_dup = 1) {
	global $time;
	$total = count($items);
	$inserted = 0;
	$skipped = 0;
	$seen = array();
	foreach ($items as $item) {
		$title = mb_substr($item['title'], 0, 120, 'UTF-8');
		$link = mb_substr($item['link'], 0, 500, 'UTF-8');
		$hash = xwdi_task_hash($title, $link);
		// 批内去重
		if (isset($seen[$hash])) { $skipped++; continue; }
		$seen[$hash] = 1;
		// 与历史成功记录去重
		if ($skip_dup && db_find_one('xw_douban_import', array('hash'=>$hash, 'status'=>1))) {
			$skipped++;
			continue;
		}
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
	return array('total' => $total, 'inserted' => $inserted, 'skipped' => $skipped);
}

// 查找同名影片已成功发布的旧帖（优先最近记录，且帖子仍存在）
function xwdi_find_existing_thread($title) {
	$rows = db_find('xw_douban_import', array('title'=>$title, 'status'=>1), array('id'=>-1), 1, 20);
	foreach ((array)$rows as $r) {
		if (empty($r['tid'])) continue;
		// 仅校验帖子存在性，不做展示格式化
		$thread = db_find_one('thread', array('tid'=>intval($r['tid'])));
		if (!empty($thread)) return $r;
	}
	return FALSE;
}

// 向旧帖首楼的下载区追加网盘链接
// 返回 TRUE=追加成功 'EXISTS'=链接已存在 FALSE=旧帖数据异常
function xwdi_append_link_to_thread($tid, $link, $prefix) {
	$post = db_find_one('post', array('tid'=>$tid, 'isfirst'=>1));
	if (empty($post)) return FALSE;

	$safe_link = str_replace(array('"', "'", '\\', '[', ']'), '', $link);
	$tag = '[pd url="'.$safe_link.'"]';
	$update = array();
	$exists = TRUE;

	foreach (array('message', 'message_fmt') as $field) {
		$content = (string)$post[$field];
		if ($content === '') return FALSE;
		if (strpos($content, $tag) !== false) continue;	// 该列已有此链接

		$pos = strripos($content, '[pd url="');
		if ($pos !== false) {
			// 插到最后一个 [pd] 标签后面，并排显示多个网盘按钮
			$end = strpos($content, '"]', $pos);
			$insert_pos = ($end === false) ? strlen($content) : $end + 2;
			$update[$field] = substr($content, 0, $insert_pos).' '.$tag.substr($content, $insert_pos);
		} else {
			// 旧帖没有下载区，末尾新建一段
			$update[$field] = $content.'<p>'.xwdi_douban_e($prefix).$tag.'</p>';
		}
	}

	if ($update) {
		$r = post__update($post['pid'], $update);
		if ($r === FALSE) return FALSE;
		$exists = FALSE;
	}
	return $exists ? 'EXISTS' : TRUE;
}

// 处理单个任务：将采集数据渲染为卡片 + 下载链接，创建主题。
// merge_same 查重已在 api.php submit 中完成，此处只负责发新帖。
// 返回 array(tid, subject, merged=0, dup=0)，失败抛异常
function xwdi_task_process($task, $payload_data = NULL) {
	global $time;

	if (empty($task['title'])) throw new RuntimeException('影视名为空');

	$kv = kv_get('xw_douban_import');
	$prefix = empty($kv['link_prefix']) ? '下载链接：' : $kv['link_prefix'];

	// v1.1 起抓取在本地 Python 客户端完成，服务端只接收数据
	if (!is_array($payload_data) || empty($payload_data)) {
		throw new RuntimeException('未收到采集数据，请用本地客户端(xwdi_client)处理任务');
	}
	$payload = xwdi_payload_from_api($payload_data);
	$payload['subject'] = xwdi_payload_subject($payload);
	$subject = $payload['subject'];
	$message = $payload['html'] = xwdi_render_html($payload);

	// 拼接下载链接行（[pd] 短代码由「网盘链接转二维码 pandown」插件渲染为网盘按钮+二维码）
	if ($task['link'] !== '') {
		// 短代码属性内不能出现引号/方括号，剔除以免破坏格式
		$safe_link = str_replace(array('"', "'", '\\', '[', ']'), '', $task['link']);
		$message .= '<p>'.xwdi_douban_e($prefix).'[pd url="'.$safe_link.'"]</p>';
	}

	$longip = ip2long(ip());
	$longip < 0 AND $longip = sprintf("%u", $longip);

	$fid = isset($task['fid']) ? intval($task['fid']) : 0;
	$uid = isset($task['uid']) ? intval($task['uid']) : 0;
	$uid <= 0 AND $uid = 1;

	$thread = array(
		'fid' => $fid,
		'uid' => $uid,
		'subject' => $subject,
		'doctype' => 0,
		'message' => $message,
		'time' => $time,
		'longip' => $longip,
	);
	$firstpid = 0;
	$tid = thread_create($thread, $firstpid);
	if (!$tid) throw new RuntimeException('创建主题失败');

	// 关联固定标签
	xwdi_thread_bind_tags($tid, $task['tagids']);

	return array('tid' => $tid, 'subject' => $subject, 'merged' => 0, 'dup' => 0);
}

// 给主题绑定标签：写入 tag_thread 并回填 thread.tagids（xn_tag 增强版的缓存字段）
function xwdi_thread_bind_tags($tid, $tagids_raw) {
	global $time;

	$tagids = array();
	foreach (explode(',', strval($tagids_raw)) as $tagid) {
		$tagid = intval($tagid);
		if ($tagid > 0 && !in_array($tagid, $tagids)) {
			// 标签必须存在且启用
			$tag = db_find_one('tag', array('tagid'=>$tagid));
			if (!empty($tag) && !empty($tag['enable'])) {
				$tagids[] = $tagid;
			}
		}
	}
	if (!$tagids) return;

	$oldrows = db_find('tag_thread', array('tid'=>$tid), array(), 1, 500);
	$oldids = arrlist_values($oldrows, 'tagid');

	// 合并旧标签（保留其他来源的标签），写入新增
	$merge = array_values(array_unique(array_merge($oldids, $tagids)));
	foreach (array_diff($tagids, $oldids) as $tagid) {
		db_create('tag_thread', array('tagid'=>$tagid, 'tid'=>$tid));
	}

	// 回填 thread.tagids 缓存字段（xn_tag 安装时添加）
	$update = array();
	if (db_sql_find_one("SHOW COLUMNS FROM `".get_db_tablepre()."thread` LIKE 'tagids'")) {
		$update['tagids'] = implode(',', array_slice($merge, 0, 10));
	}
	if (db_sql_find_one("SHOW COLUMNS FROM `".get_db_tablepre()."thread` LIKE 'tagids_time'")) {
		$update['tagids_time'] = $time;
	}
	if ($update) thread_update($tid, $update);

	// 通知 xn_tag 刷新标签缓存
	setting_set('tag_update_time', $time);
}

function get_db_tablepre() {
	global $db;
	return $db->tablepre;
}

?>
