<?php exit;
	// ================================================================
	//  图片上传自动瘦身（XIUNO XW）
	//  钩子点：route/attach.php 的 attach_create_save_before（在 file_put_contents 之前）
	//  作用：编辑器粘贴 / 拖拽上传 / 附件按钮传上来的图，落盘前先「限宽 + 条件转 WebP」
	//  安全性：转换不划算或命中任何回退条件时保持原样；只改 $data 与文件名/尺寸变量，
	//          不触碰上传校验、权限、session 记录等既有逻辑
	//  注意：本钩子是新文件，需到「后台 → 其他 → 清理缓存」清空临时文件后才生效
	// ================================================================
	include_once APP_PATH.'plugin/till_thread_locallmage/imagex.inc.php';

	if(!empty($data) && strlen($data) > 10) {

		// 先用核心已生成的临时名落一份盘，供 GD 读取
		$scratch = $conf['upload_path'].'tmp/'.$tmpanme;
		@file_put_contents($scratch, $data);

		$img = xw_localimg_optimize($scratch, $tmpurl, xw_localimg_cfg());

		if(!empty($img['changed'])) {
			// 占到便宜：用优化后的字节替换，并让文件名/尺寸跟着改（核心随后按 $data 写盘）
			$data = @file_get_contents($img['path']);
			$ext = 'webp';
			$tmpanme = basename($img['path']);
			$tmpfile = $img['path'];
			$tmpurl = $img['url'];
			$filetype = 'image';
			$is_image = 1;
			$sz = @getimagesize($tmpfile);
			$sz AND list($width, $height) = $sz;
		} elseif(xw_localimg_is_webp($data)) {
			// 客户端已经转好 WebP 后上传（见 huux_tinymce/tinymce/upload-prep.js），
			// 服务器这边通常"没占到便宜"（本来就是 WebP），于是会保留 .png/.jpg 的文件名，
			// 内容却是 WebP —— 名字与内容不一致，静态服务器会按扩展名发错的 Content-Type。
			// 这里统一改成 .webp；只需要改文件名/尺寸变量，核心随后会把 $data 写到 $tmpfile。
			@unlink($scratch);
			$newname = $uid.'_'.xn_rand(15).'.webp';
			$ext = 'webp';
			$tmpanme = $newname;
			$tmpfile = $conf['upload_path'].'tmp/'.$newname;
			$tmpurl = $conf['upload_url'].'tmp/'.$newname;
			$filetype = 'image';
			$is_image = 1;
			$sz = function_exists('getimagesizefromstring') ? @getimagesizefromstring($data) : FALSE;
			$sz AND list($width, $height) = $sz;
		} else {
			// 没占到便宜：删掉试写的文件，交回核心按原样写
			@unlink($scratch);
		}
	}
