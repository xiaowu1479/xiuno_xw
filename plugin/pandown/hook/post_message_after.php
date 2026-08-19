<?php
!defined('DEBUG') and exit('Access Denied.');
?>
<a href="javascript:void(0);" role="button" class="btn btn-sm btn-primary mb-3 mr-2" data-toggle="modal" data-target="#add_pandown_modal">添加网盘链接</a>
<div class="modal fade" id="add_pandown_modal" tabindex="-1" role="dialog" aria-labelledby="add_pandown_label" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered" style="max-width:600px;">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title" id="add_pandown_label">添加网盘链接</h4>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<div class="form-group input-group">
					<div class="input-group-prepend">
						<span class="input-group-text">分享内容：</span>
					</div>
					<textarea class="form-control" name="pandown_url" id="pandown_url" rows="3" placeholder="直接粘贴分享内容（含链接），系统自动识别链接"></textarea>
				</div>
				<div class="form-group" id="pandown_preview" style="display:none;">
					<small class="text-success">识别到链接：</small>
					<code id="pandown_url_preview" class="text-break"></code>
				</div>
				<div class="form-group input-group">
					<div class="input-group-prepend">
						<span class="input-group-text">友情提醒：</span>
					</div>
					<div class="form-control text-info">支持百度网盘、夸克网盘、UC网盘、迅雷网盘、阿里云盘等链接。</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
				<button type="button" class="btn btn-primary" onclick="add_pandown_submit()">确定</button>
			</div>
		</div>
	</div>
</div>
<script>
function pandown_extract_url(text) {
	var m = text.match(/https?:\/\/[^\s<>"']+/);
	if (m) return m[0];
	var patterns = [
		/pan\.baidu\.com\/s\/[a-zA-Z0-9\-]+/i,
		/yun\.baidu\.com\/s\/[a-zA-Z0-9\-]+/i,
		/pan\.quark\.cn\/s\/[a-zA-Z0-9]+/i,
		/kuake\.com\/s\/[a-zA-Z0-9]+/i,
		/drive\.uc\.cn\/s\/[a-zA-Z0-9]+/i,
		/uc\.cn\/s\/[a-zA-Z0-9]+/i,
		/pan\.xunlei\.cn\/s\/[a-zA-Z0-9]+/i,
		/xunlei\.com\/s\/[a-zA-Z0-9]+/i,
		/aliyundrive\.com\/s\/[a-zA-Z0-9]+/i,
		/aliyundrive\.net\/s\/[a-zA-Z0-9]+/i,
	];
	for (var i = 0; i < patterns.length; i++) {
		m = text.match(patterns[i]);
		if (m) {
			var url = m[0];
			if (!/^https?:\/\//i.test(url)) url = 'https://' + url;
			return url;
		}
	}
	return text.trim();
}
function add_pandown_insert(tag) {
	if (window.tinymce && tinymce.activeEditor) {
		tinymce.activeEditor.insertContent(tag, 1);
	} else {
		var jmsg = $('#message');
		if (jmsg.length) {
			jmsg.val(jmsg.val() + tag);
		}
	}
}
function add_pandown_submit() {
	var text = $('#pandown_url').val().trim();
	if (!text) {
		$.alert('请输入分享内容');
		return;
	}
	var url = pandown_extract_url(text);
	if (!url) {
		$.alert('未识别到有效链接，请确认包含网盘链接');
		return;
	}
	add_pandown_insert('[pd url="' + url + '"]');
	$('#pandown_url').val('');
	$('#pandown_preview').hide();
	$('#add_pandown_modal').modal('hide');
}
$('#pandown_url').on('input', function() {
	var text = $(this).val().trim();
	if (!text) { $('#pandown_preview').hide(); return; }
	var url = pandown_extract_url(text);
	if (url && url !== text) {
		$('#pandown_url_preview').text(url);
		$('#pandown_preview').show();
	} else {
		$('#pandown_preview').hide();
	}
});
</script>
