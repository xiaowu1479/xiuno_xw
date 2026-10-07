/* ================================================================
 *  上传前瘦身：把要上传的图片转成 WebP（XIUNO XW）
 *
 *  为什么需要它：
 *    站点的上传链路是
 *      TinyMCE 粘贴 → xn.upload_file() → xn.image_resize()（等比缩放 + 水印）
 *                    → canvas.toDataURL('image/png') → base64 POST 到 attach-create
 *    而 xn.image_resize() 里是写死的 filetype = 'png'，也就是**统一输出 PNG**；
 *    再加上 base64 本身的 1.33 倍膨胀，实测：
 *      1672×940 的图 → 4.7MB 的 POST 正文
 *      1232×706 的图 → 2.2MB 的 POST 正文
 *    于是"粘贴图片 → 点提交"要干等好几秒（发帖时因为上传在你打标题期间就跑完了，
 *    所以感觉是秒发；编辑时粘贴完立刻点提交，就全落在等待里）。
 *
 *  做法：
 *    保持原有处理（xn.image_resize 的限宽 + 水印）完全不变，只把它的结果再编码成
 *    WebP 后自己 POST 上传，体积通常只有 PNG 的 1/10（实测 4.7MB → 434KB、
 *    2.2MB → 159KB）。任何一步失败（浏览器不支持 WebP 编码 / 结果反而更大 /
 *    图片解码失败）都自动回退到原本的 xn.upload_file 流程，行为只会更好不会更差。
 * ================================================================ */
(function () {
	'use strict';
	if (window.xwUploadImage) { return; }

	var Q = 0.85;			// WebP 质量
	var MAX_W = 2560;		// 与 xn.upload_file 里的默认上限保持一致
	var MAX_H = 4960;
	var webp_ok = null;

	// 浏览器能不能用 canvas 编码 WebP（取一个 1px 画布试一次即可）
	function webpSupported() {
		if (webp_ok !== null) { return webp_ok; }
		try {
			var c = document.createElement('canvas');
			c.width = 1; c.height = 1;
			webp_ok = c.toDataURL('image/webp').indexOf('data:image/webp') === 0;
		} catch (e) {
			webp_ok = false;
		}
		return webp_ok;
	}

	// 把一段 base64 图再编码为 WebP，失败/不支持时回调 null
	function toWebp(base64, cb) {
		var im = new Image();
		im.onload = function () {
			var s = null;
			try {
				var c = document.createElement('canvas');
				c.width = im.naturalWidth || im.width;
				c.height = im.naturalHeight || im.height;
				c.getContext('2d').drawImage(im, 0, 0);
				s = c.toDataURL('image/webp', Q);
			} catch (e) {
				s = null;
			}
			cb(s && s.indexOf('data:image/webp') === 0 ? s : null);
		};
		im.onerror = function () { cb(null); };
		im.src = base64;
	}

	function post(name, data, width, height, success) {
		$.xpost(xn.url('attach-create'), {
			is_image: 1,
			name: name,
			data: data,
			width: width,
			height: height
		}, function (code, json) {
			code == 0 ? success(json.url) : $.alert(json);
		});
	}

	// 入口：TinyMCE 的粘贴 / 拖拽 / 工具栏上传都会走到这里
	window.xwUploadImage = function (blob, blobInfo, success, failure) {
		var stock = function () {
			// 原有流程（xn.image_resize 会把图编成 PNG）
			xn.upload_file(blob, xn.url('attach-create'), {is_image: 1}, function (code, json) {
				code == 0 ? success(json.url) : $.alert(json);
			});
		};

		if (!blob || !window.FileReader || !$.xpost || !window.xn || !xn.image_resize
			|| blob.type === 'image/gif' || !webpSupported()) {
			stock();
			return;
		}

		var name = (blobInfo && blobInfo.filename && blobInfo.filename())
			|| (blob.type === 'image/png' ? 'paste.png' : 'paste.jpg');
		var fr = new FileReader();
		fr.onerror = stock;
		fr.onload = function () {
			// 1) 站点原有处理：等比缩放（2560×4960 上限）+ 水印
			xn.image_resize(fr.result, function (code, msg) {
				if (code != 0 || !msg || !msg.data) { stock(); return; }
				// 2) 再编码成 WebP，只有确实更小才用
				toWebp(msg.data, function (webp) {
					var data = (webp && webp.length < msg.data.length) ? webp : msg.data;
					// 文件名保持原样（服务端会把 WebP 内容的扩展名统一修正为 .webp）
					post(name, data, msg.width, msg.height, success);
				});
			}, {width: MAX_W, height: MAX_H});
		};
		fr.readAsDataURL(blob);
	};
})();
