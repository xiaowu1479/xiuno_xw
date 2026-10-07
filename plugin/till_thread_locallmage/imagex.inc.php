<?php
// ================================================================
//  图片优化公共库（XIUNO XW）
//  被两处复用：
//    1) route/localimg.php         —— 编辑器「图片本地化」下载远程图后
//    2) hook/attach_create_save_before.php —— 核心上传通道（编辑器粘贴/上传/附件）
//
//  策略：
//   1. 宽度超过 max_width 时等比缩小 —— 体积大幅下降主要靠这一步
//   2. 转 WebP 之后**只有比原文件小 10% 以上才替换**，否则保留原图：
//      颜色少、线条规整的图（UI 截图）PNG 本身压得极好，转 WebP 反而会变大
//   3. 全部回退路径：GD 无 WebP / 动图 GIF（GD 只能取第一帧）/ 解码失败 /
//      边长超 4000px / 非图片 → 一律原样保留，绝不产生坏图
// ================================================================

// 可调参数（改这里即可，两处调用同时生效）
function xw_localimg_cfg() {
	return array(
		'max_width'      => 1200,       // 超过该宽度等比缩小
		'q_photo'        => 82,         // 照片类质量（颜色多、细节多）
		'q_graphic'      => 90,         // 图形/截图类质量（文字多，压太狠会发虚）
		'graphic_colors' => 1500,       // 抽样颜色数低于此值视为图形/截图类
		'min_bytes'      => 0,          // 体积小于此值且宽度不超标时不处理；0 = 都尝试
		'min_gain'       => 0.9,        // 新文件必须小于原体积的 90% 才替换
		'max_side'       => 4000,       // 任一边超过此值直接放弃处理
	);
}

// 优化图片，返回 array(path,url,filesize,width,height,changed,quality)
// 优化失败、"不划算"或命中任一回退条件时，返回的仍是原文件信息（changed=FALSE）
function xw_localimg_optimize($path, $url, $cfg) {
	$ret = array('path'=>$path, 'url'=>$url, 'filesize'=>0, 'width'=>0, 'height'=>0, 'changed'=>FALSE, 'quality'=>0);
	if(!is_file($path)) return $ret;
	$ret['filesize'] = filesize($path);

	$sz = @getimagesize($path);
	if(!$sz) return $ret;                                              // 不是图片
	list($ow, $oh, $type) = $sz;
	$ret['width'] = $ow;
	$ret['height'] = $oh;
	if($ow <= 0 || $oh <= 0) return $ret;
	if($ow > $cfg['max_side'] || $oh > $cfg['max_side']) return $ret;   // 尺寸过大，跳过
	if($ow <= $cfg['max_width'] && $ret['filesize'] < $cfg['min_bytes']) return $ret;  // 又小又不超标，不动
	if(!function_exists('imagewebp')) return $ret;                      // GD 没编译 WebP
	if($type == IMAGETYPE_GIF) return $ret;                             // 动图不碰

	switch($type) {
		case IMAGETYPE_JPEG: $im = @imagecreatefromjpeg($path); break;
		case IMAGETYPE_PNG:  $im = @imagecreatefrompng($path);  break;
		case IMAGETYPE_WEBP: $im = @imagecreatefromwebp($path); break;
		default: return $ret;
	}
	if(!$im) return $ret;

	// 限宽（等比）
	if($ow > $cfg['max_width']) {
		$nw = $cfg['max_width'];
		$nh = max(1, (int)round($oh * $nw / $ow));
		$dst = @imagescale($im, $nw, $nh);
		if($dst) {
			imagedestroy($im);
			$im = $dst;
		}
	}

	// 图形/截图类用高质量，照片类用常规质量
	$q = xw_localimg_is_graphic($im, $cfg['graphic_colors']) ? $cfg['q_graphic'] : $cfg['q_photo'];
	$fw = imagesx($im);
	$fh = imagesy($im);

	$tmp_out = $path.'.webp';
	$ok = @imagewebp($im, $tmp_out, $q);
	imagedestroy($im);
	if(!$ok || !is_file($tmp_out)) {
		is_file($tmp_out) AND @unlink($tmp_out);
		return $ret;
	}

	clearstatcache();
	$newsize = filesize($tmp_out);
	if($newsize < $ret['filesize'] * $cfg['min_gain']) {
		// 确实更小 → 换成 .webp（同名前缀换扩展名），删掉原文件
		$newpath = preg_replace('/\.[a-z0-9]+$/i', '', $path).'.webp';
		@unlink($path);
		@rename($tmp_out, $newpath);
		$ret['path'] = $newpath;
		$ret['url'] = preg_replace('/\.[a-z0-9]+$/i', '', $url).'.webp';
		$ret['filesize'] = $newsize;
		$ret['width'] = $fw;
		$ret['height'] = $fh;
		$ret['changed'] = TRUE;
		$ret['quality'] = $q;
	} else {
		// 没占到便宜 → 保留原图（尺寸也随之保持原样）
		@unlink($tmp_out);
	}
	return $ret;
}

// 抽样统计颜色数：颜色少判为"图形/截图类"（用高质量），否则按照片类处理
function xw_localimg_is_graphic($im, $threshold) {
	$w = imagesx($im);
	$h = imagesy($im);
	if($w < 1 || $h < 1) return TRUE;
	$step_x = max(1, (int)($w / 40));
	$step_y = max(1, (int)($h / 40));
	$seen = array();
	for($y = 0; $y < $h; $y += $step_y) {
		for($x = 0; $x < $w; $x += $step_x) {
			$seen[imagecolorat($im, $x, $y)] = 1;
			if(count($seen) > $threshold) return FALSE;
		}
	}
	return TRUE;
}
