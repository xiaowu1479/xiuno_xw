<?php

!defined('DEBUG') AND exit('Access Denied.');

// 附件管理（XIUNO XW）
// 目的：后台能直接查看 / 清理附件，不用去宝塔文件管理器里翻
//  - 列表分页显示附件：缩略图、大小、上传者、所属主题、时间、**是否被帖子正文引用**
//  - 未被引用的（孤儿）可以单独删，也可以一键批量清理
//  - 删除时不直接删文件，而是先移动到 upload/_attach_trash/ 年月日/ 下（可回滚）
$action = param(1);

// hook admin_attach_start.php

if(empty($action) || $action == 'list') {

	$header['title'] = lang('admin_attach');
	$header['mobile_title'] = lang('admin_attach');

	$pagesize = 20;
	$page = param(2, 1);
	$page < 1 AND $page = 1;

	// hook admin_attach_list_start.php

	// 总体统计
	$stat = db_sql_find_one("SELECT COUNT(*) AS c, SUM(filesize) AS s FROM `{$db->tablepre}attach`");
	$stat = $stat ? $stat : array('c'=>0, 's'=>0);

	// 临时目录（upload/tmp）统计：超过 1 天的会被定时任务 attach_gc() 清掉
	$tmpfiles = glob($conf['upload_path'].'tmp/*.*');
	$tmpcount = 0;
	$tmpsize = 0;
	if($tmpfiles) {
		foreach($tmpfiles as $f) {
			if(!is_file($f)) continue;
			$tmpcount++;
			$tmpsize += filesize($f);
		}
	}

	$n = attach_count();
	$attachlist = $n ? attach_find(array(), array('aid'=>-1), $page, $pagesize) : array();
	$pagination = pagination(url("attach-list-{page}"), $n, $page, $pagesize);

	// 逐行补齐：上传者、所属主题、是否被引用
	$postcache = array();
	$usercache = array();
	$threadcache = array();
	$orphan_here = 0;
	if($attachlist) {
		foreach($attachlist as &$a) {
			$a['is_orphan'] = admin_attach_is_orphan($a, $postcache);
			$a['is_orphan'] AND $orphan_here++;
			$a['filesize_h'] = humansize($a['filesize']);
			$a['create_date_fmt'] = date('Y-n-j H:i', $a['create_date']);
			// 上传者
			if(!isset($usercache[$a['uid']])) {
				$_u = user_read($a['uid']);
				$usercache[$a['uid']] = $_u ? $_u['username'] : 'uid '.$a['uid'];
			}
			$a['username'] = $usercache[$a['uid']];
			// 所属主题
			if(!isset($threadcache[$a['tid']])) {
				$_t = $a['tid'] ? thread_read($a['tid']) : array();
				$threadcache[$a['tid']] = $_t ? $_t['subject'] : '';
			}
			$a['subject'] = $threadcache[$a['tid']];
			// 只对能在正文里引用的图片做缩略图，避免大图拖慢后台
			$a['is_img'] = ($a['isimage'] && preg_match('#\.(jpe?g|png|gif|webp|bmp)$#i', $a['filename']));
		}
		unset($a);
	}

	// hook admin_attach_list_end.php

	include _include(ADMIN_PATH."view/htm/attach_list.htm");

} elseif($action == 'delete') {

	// 删除单个附件（文件移到回收目录，记录删除）
	$aid = intval(param('aid'));
	$aid < 1 AND message(-1, lang('admin_attach_invalid'));

	// hook admin_attach_delete_start.php

	$r = admin_attach_remove($aid);
	$r === FALSE AND message(-1, lang('admin_attach_delete_failed'));

	message(0, lang('admin_attach_delete_success'));

} elseif($action == 'clean_orphan') {

	// 一键清理"未被任何帖子引用"的附件（分批，最多一次扫 3000 条）
	// hook admin_attach_clean_orphan_start.php

	$max = 3000;
	$maxid = 0;
	$scanned = 0;
	$removed = 0;
	$freed = 0;
	$postcache = array();
	while($scanned < $max) {
		$rows = db_find('attach', array('aid'=>array('>'=>$maxid)), array('aid'=>1), 1, 200);
		if(!$rows) break;
		foreach($rows as $a) {
			$maxid = $a['aid'];
			$scanned++;
			if(!admin_attach_is_orphan($a, $postcache)) continue;
			$size = intval($a['filesize']);
			if(admin_attach_remove($a['aid']) !== FALSE) {
				$removed++;
				$freed += $size;
			}
		}
	}

	// hook admin_attach_clean_orphan_end.php

	message(0, lang('admin_attach_clean_result', array('n'=>$removed, 'size'=>humansize($freed), 'scanned'=>$scanned)));

} else {

	message(-1, lang('admin_attach_invalid'));
}

// hook admin_attach_end.php

// ----------------------------------------------------------------

// 把附件地址转成"在后台页面里也能用"的地址
// 原因：$conf['upload_url'] 默认是相对路径（upload/），前台在根目录下没问题，
//       但后台页面在 /admin/ 下，浏览器会把它解析成 /admin/upload/... → 图片 404
// 已经是绝对地址（http(s):// 或 / 开头）或部署时自己配过 CDN 域名的，原样返回
function admin_attach_abs_url($url) {
	$url = strval($url);
	if($url === '') return $url;
	if(preg_match('#^(https?:)?//#i', $url)) return $url;		// 完整 URL / 协议相对
	if(substr($url, 0, 1) === '/') return $url;					// 根路径开头
	if(substr($url, 0, 3) === '../') return $url;				// 已经处理过
	return '../'.preg_replace('#^\./#', '', $url);
}

// 判断附件是否"没被任何帖子正文引用"（孤儿）
//  - 没关联到帖子（pid=0）、帖子已被删除、正文里找不到它的地址 → 视为孤儿
//  - $postcache：按 pid 缓存帖子，避免一次请求里重复查询
function admin_attach_is_orphan($a, &$postcache) {
	global $conf;
	$pid = intval($a['pid']);
	if($pid < 1) return TRUE;
	if(!isset($postcache[$pid])) {
		$postcache[$pid] = post_read($pid);
	}
	$post = $postcache[$pid];
	if(empty($post)) return TRUE;
	$url = $conf['upload_url'].'attach/'.$a['filename'];
	if(strpos($post['message'], $url) !== FALSE) return FALSE;
	if(!empty($post['message_fmt']) && strpos($post['message_fmt'], $url) !== FALSE) return FALSE;
	return TRUE;
}

// 删除附件：文件先移到 upload/_attach_trash/年月日/ 下（可回滚），再删数据库记录
// 返回 FALSE 表示失败
function admin_attach_remove($aid) {
	global $conf, $time;
	$aid = intval($aid);
	$attach = attach_read($aid);
	if(!$attach) return FALSE;

	$file = $conf['upload_path'].'attach/'.$attach['filename'];
	if(is_file($file)) {
		$trash = $conf['upload_path'].'_attach_trash/'.date('Ymd', $time).'/'.dirname($attach['filename']).'/';
		!is_dir($trash) AND @mkdir($trash, 0777, TRUE);
		$dest = $trash.basename($attach['filename']);
		// 同名文件已存在时加个后缀，避免互相覆盖
		if(is_file($dest)) {
			$dest = $trash.date('His').'_'.basename($attach['filename']);
		}
		if(!@rename($file, $dest)) {
			// 跨设备等情况下 rename 可能失败，退化为复制 + 删除
			if(!@copy($file, $dest)) return FALSE;
			@unlink($file);
		}
	}

	// 记录删除后，把帖子上的图片/文件计数同步一下（前台附件列表靠这个）
	$pid = intval($attach['pid']);
	$r = attach__delete($aid);	// 只删记录，不碰文件（文件上面已处理）
	if($pid > 0 && function_exists('attach_find_by_pid')) {
		list($attachlist, $imagelist, $filelist) = attach_find_by_pid($pid);
		post__update($pid, array('images'=>count($imagelist), 'files'=>count($filelist)));
	}
	return $r === FALSE ? FALSE : TRUE;
}

?>
