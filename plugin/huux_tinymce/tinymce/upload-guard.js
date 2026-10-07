/* ================================================================
 *  提交守卫：等图片上传完再提交（XIUNO XW）
 *
 *  要解决的问题：
 *    编辑器粘贴的图片先以 base64 内联在正文里，再由 images_upload_handler
 *    异步上传，上传完成后才替换成 upload/tmp/xxx 的短地址。而提交是同步的：
 *    只要在上传完成前点「编辑帖子 / 发帖」，正文里还是那条 base64，
 *    体积约为图片字节的 4/3，一旦超过服务端上限（2048000 字符）就会报
 *    「内容太长」，等上传完成后再点一次才成功 —— 表现为"每次都要点两次"。
 *
 *  做法：
 *    用「文档捕获阶段」监听 submit（保证早于模板里后绑定的提交处理器执行），
 *    提交前先 editor.save()，若正文里还有未上传完成的图片（src 为 data:/blob:，
 *    或正文长度异常），就拦下这次提交、显示提示、等上传完成，然后自动重新
 *    触发一次提交；等待超时（约 90 秒）则提示并放行一次，避免用户被卡死。
 *
 *  安全性：
 *    只处理「编辑器所在的表单」；没有 TinyMCE、没有待上传图片时一律直接放行，
 *    不影响原生 textarea 与其它表单。
 * ================================================================ */
(function () {
	'use strict';
	if (window.xwUploadGuard) { return; }
	window.xwUploadGuard = true;

	var MAX_TRIES = 300;		// 300 × 300ms ≈ 90 秒
	var LEN_ALARM = 1000000;	// 正文超过 100 万字符：基本只可能是内联图片
	var waiting = false;		// 正在等待上传
	var allowOnce = false;		// 等待超时后放行一次

	function $(sel) { return window.jQuery ? window.jQuery(sel) : null; }

	function editor() {
		return (typeof tinyMCE !== 'undefined' && tinyMCE.editors) ? tinyMCE.editors['message'] : null;
	}

	function content() {
		var ed = editor();
		if (ed) { try { return ed.getContent(); } catch (e) {} }
		var ta = document.getElementById('message');
		return ta ? ta.value : '';
	}

	// 正文里是否还有"没落到服务器上"的图片
	function pendingImages() {
		var html = content();
		if (/<img[^>]+src\s*=\s*["']\s*(?:data:|blob:)/i.test(html)) { return true; }
		return html.length > LEN_ALARM;
	}

	function save() {
		var ed = editor();
		if (ed) { try { ed.save(); } catch (e) {} }
	}

	function hint(form, txt) {
		var $ = window.jQuery;
		if (!$) { return; }
		var $btn = $(form).find('#submit');
		if (!$btn.length) { return; }
		var $h = $(form).find('.xw-upload-hint');
		if (!$h.length) {
			$h = $('<span class="xw-upload-hint text-grey small ml-2"></span>').appendTo($btn.parent());
		}
		$h.text(txt || '');
		if (txt) { $h.show(); } else { $h.hide(); }
	}

	// 等图片上传完成：先催一次 uploadImages()，再轮询正文
	function waitImages(form, done) {
		var ed = editor(), tries = 0;
		if (ed && typeof ed.uploadImages === 'function') {
			try { ed.uploadImages(function () {}); } catch (e) {}
		}
		var timer = setInterval(function () {
			if (!pendingImages()) {
				clearInterval(timer);
				done(true);
				return;
			}
			tries++;
			if (tries % 10 === 0) { hint(form, '图片上传中（' + Math.round(tries / 3.33) + ' 秒），完成后将自动提交…'); }
			if (tries >= MAX_TRIES) {
				clearInterval(timer);
				done(false);
			}
		}, 300);
	}

	document.addEventListener('submit', function (e) {
		var form = e.target;
		if (!form || form.nodeName !== 'FORM') { return; }
		if (!form.querySelector || !form.querySelector('#message')) { return; }	// 不是编辑器表单
		if (!editor()) { return; }												// 没有 TinyMCE，走原生逻辑

		if (waiting) { e.preventDefault(); e.stopPropagation(); return; }		// 等待期间再点：忽略
		if (allowOnce) { allowOnce = false; return; }							// 超时后放行一次

		save();
		if (!pendingImages()) { return; }										// 正常提交

		// 还有图片在上传：拦下这次提交（捕获阶段，模板里后绑定的处理器不会执行）
		e.preventDefault();
		e.stopPropagation();

		var $ = window.jQuery;
		waiting = true;
		hint(form, '图片上传中，完成后将自动提交…');
		if ($) {
			var $btn = $(form).find('#submit');
			if ($btn.length && $btn.button) { $btn.button('loading'); }
		}

		waitImages(form, function (ok) {
			waiting = false;
			if (ok) {
				hint(form, '');
				// 关键：重新把编辑区内容同步回 textarea。
				// 拦截时那次 save() 同步进去的还是含 base64 的旧内容，若不再存一次，
				// 提交出去的仍然是那坨大字符串（长度检查照样会挂）。
				save();
				// triggerHandler：只走已绑定的提交流程（ajax 提交），不会再触发浏览器原生提交
				if ($) { $(form).triggerHandler('submit'); }
				return;
			}
			allowOnce = true;
			hint(form, '注意：仍有图片未上传完成，本次已放行');
			if ($) {
				var $btn = $(form).find('#submit');
				if ($btn.length && $btn.button) { $btn.button('reset'); }
				if ($.alert) {
					$.alert('有图片还没有上传完成，请稍候再试；如果反复失败，请把图片删除后重新粘贴一次。');
				}
			}
		});
	}, true);
})();
