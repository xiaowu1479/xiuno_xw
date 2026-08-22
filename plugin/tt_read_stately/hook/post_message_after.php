<?php
!defined('DEBUG') and exit('Access Denied.');
$king_player_kv = kv_cache_get('king_player');
?>
<div>
</div>
<a href="javascript:void(0);" role="button" class="btn btn-sm btn-primary mb-3 mr-2" data-toggle="modal" data-target="#add_hide_content_modal">添加隐藏内容</a>
<div class="modal fade" id="add_hide_content_modal" tabindex="-1" role="dialog" aria-labelledby="add_hide_content_label" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered" style="max-width:600px; ">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title" id="add_hide_content_label">添加隐藏内容</h4>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<div class="form-group input-group">
					<div class="input-group-prepend">
						<span class="input-group-text">登录可见：</span>
					</div>
					<textarea class="form-control" name="ttlogin_content" id="ttlogin_content" style="height: 8rem;" placeholder="请输入需要登录可见的内容"></textarea>
				</div>
				<div class="form-group input-group">
					<div class="input-group-prepend">
						<span class="input-group-text">回复可见：</span>
					</div>
					<textarea class="form-control" name="ttreply_content" id="ttreply_content" style="height: 8rem;" placeholder="请输入需要回复可见的内容"></textarea>
				</div>
				<div class="form-group input-group">
					<div class="input-group-prepend">
						<span class="input-group-text">友情提醒：</span>
					</div>
					<div class="form-control text-info">输入框为二选一，按您的需求在对应框中输入内容。</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
				<button type="button" class="btn btn-primary" onclick="add_hide_content_submit_modal()">确定</button>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript">
	function tt_read_insert(tag) {
		if (window.tinymce && tinymce.activeEditor) {
			tinymce.activeEditor.insertContent(tag, 1);
		} else {
			var jmsg = $('#message');
			if (jmsg.length) {
				jmsg.val(jmsg.val() + tag);
			}
		}
	}
	function add_hide_content_submit_modal() {
		var jhmodal = $('#add_hide_content_modal');
		if (jhmodal.length) jhmodal.modal('hide');
		var ttlogin_content = $('#ttlogin_content').val();
		var ttreply_content = $('#ttreply_content').val();
		if (ttlogin_content) {
			tt_read_insert('[ttlogin]' + ttlogin_content + '[/ttlogin]');
			$('#ttlogin_content').val('');
		}
		if (ttreply_content) {
			tt_read_insert('[ttreply]' + ttreply_content + '[/ttreply]');
			$('#ttreply_content').val('');
		}
	}
</script>