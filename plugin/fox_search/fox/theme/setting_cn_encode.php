<?php !defined('DEBUG') AND exit('Access Denied.');include _include(ADMIN_PATH.'view/htm/header.inc.htm');?>
<style>
.input-group-text{width:120px!important;}@media(max-width:576px){#body > .container > .row.mb-3{margin-bottom:0px!important;}}
.table th,.table td{padding:0.5rem 10px 0.5rem 0;}.table thead th{border-bottom:1px solid #dee2e6;}
</style>
<div class="row mb-3">
  <div class="col-md-8">
    <div class="btn-group">
      <a class="btn btn-secondary" href="<?php echo url("plugin");?>">本地插件</a>
			<?php echo admin_tab_active(array(
					'set'=>array('url'=>url('plugin-setting-fox_search-set'), 'text'=>lang('admin_setting_search_set')),
					'cn_encode'=>array('url'=>url('plugin-setting-fox_search-cn_encode'), 'text'=>lang('admin_setting_search_cn_encode'))
				), $action);
			?>
      <a class="btn btn-secondary" href="https://bbs.oddfox.cn/xn.htm" target="_blank">插件中心</a>
    </div>
  </div>
  <div class="col-lg-4 text-right hidden-sm">
    <div class="btn-group">
      <a class="btn btn-danger" target="_blank" href="//bbs.oddfox.cn">问题反馈</a>
      <a class="btn btn-dark" href="<?php echo url("plugin-setting-fox_search-cn_encode");?>"><i class="icon-refresh"></i></a>
      <a class="btn btn-dark" href="<?php echo url("plugin");?>"><i class="icon-times"></i></a>
    </div>
  </div>
</div>

<div class="row">
	<div class="col-lg-12 mx-auto">
		<div class="card">
          <div class="card-header"><i class="icon-search"></i> 奇狐搜索插件LIKE强化版</div>
          <div class="card-body p-0"><div class="alert alert-warning mb-0 hidden-sm">在XIUNOBBS官方搜索插件基础上强化了LIKE搜索，并且显示分页；开启、关闭搜索框以适用于自带搜索框的模版</div></div>
			<div class="card-body">
				<form action="" method="get" id="form">
                    <div class="form-group input-group" id="style">
                        <div class="input-group-prepend"><span class="input-group-text">当前主题贴数</span></div>
                        <div class="custom-input custom-radio form-control">                            
                            <?php echo $runtime['threads']; ?>
                        </div>
                    </div>
                    <div class="form-group input-group" id="style">
                        <div class="input-group-prepend"><span class="input-group-text">全部帖子数量</span></div>
                        <div class="custom-input custom-radio form-control">                            
                            <?php echo $posts; ?>
                        </div>
                    </div>
                    <div class="form-group input-group" id="style">
                        <div class="input-group-prepend"><span class="input-group-text">搜索范围重建</span></div>
                        <div class="custom-input custom-radio form-control">                            
                            <input type="radio" name="range" value="1" checked="checked" /> <?php echo lang('search_range_subject');?>
                            <input type="radio" name="range" value="2" /> <?php echo lang('search_range_post');?>
                        </div>
                    </div>
                    <div class="form-group input-group" id="subject_start_div">
                        <div class="input-group-prepend"><span class="input-group-text">重建范围进度</span></div>
                        <input type="text" name="subject_start" id="subject_start" placeholder="" value="<?php echo $subject_start; ?>" class="form-control" />
                    </div>
                    <div class="form-group input-group" id="post_start_div">
                        <div class="input-group-prepend"><span class="input-group-text">重建范围进度</span></div>
                        <input type="text" name="post_start" id="post_start" placeholder="" value="<?php echo $post_start; ?>" class="form-control" />
                    </div>
					<div class="form-group row">
						<div class="col-sm-12">
							<button type="submit" class="btn btn-primary btn-block" id="submit" data-loading-text="<?php echo lang('submiting');?>..."><?php echo lang('admin_start_rebuild');?></button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

<?php include _include(ADMIN_PATH.'view/htm/footer.inc.htm');?>

<script>
var jform = $("#form");
var jsubmit = $("#submit");
var jrange = jform.find('input[name="range"]');
var jsubject_start_div = $('#subject_start_div');
var jpost_start_div = $('#post_start_div');
var jsubject_start = jform.find('input[name="subject_start"]');
var jpost_start = jform.find('input[name="post_start"]');
jform.on('submit', function() {
	jform.reset();
	jsubmit.button('loading');
	var range = jrange.checked();
	var start = (range == 1 ? jsubject_start.val() : jpost_start.val());
	window.location = xn.url('plugin-setting-fox_search-rebuild-' + range + '-' + start);
	return false;
});

function on_range_change(v) {
	if (v == 1) {
		jsubject_start_div.show();
		jpost_start_div.hide();
	} else {
		jsubject_start_div.hide();
		jpost_start_div.show();
	}
}
jrange.on('change', function() {
	on_range_change($(this).val());
});
on_range_change(jrange.checked());
$('#nav li.nav-item-plugin').addClass('active');
</script>