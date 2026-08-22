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
      <a class="btn btn-dark" href="javascript:void(0);" onclick="javascript:location.reload();"><i class="icon-refresh"></i></a>
      <a class="btn btn-dark" href="<?php echo url("plugin");?>"><i class="icon-times"></i></a>
    </div>
  </div>
</div>

<div class="row">
	<div class="col-lg-12 mx-auto">
		<div class="card">
            <div class="card-header"><i class="icon-search"></i> 奇狐搜索插件LIKE强化版</div>
            <div class="card-body pb-0">
               <div class="alert alert-success mb-0 hidden-sm">在XIUNOBBS官方搜索插件基础上强化了LIKE搜索，支持模糊搜索以及精确搜索，并且分页显示；开启、关闭搜索框以适用于自带搜索框的模版。</div>
            </div>
			<div class="card-body">
				<form action="<?php echo url("plugin-setting-fox_search");?>" method="post" id="form">                
                    <div class="form-group input-group">
                        <div class="input-group-prepend"><span class="input-group-text">搜索类型选项</span></div>
                        <div class="custom-input custom-radio form-control">                            
                            <input type="radio" name="type" value="like" <?php if ($search_conf['type'] == 'like') echo 'checked="checked"';?> /> <?php echo lang('search_type_like');?>
                            <input type="radio" name="type" value="fulltext" <?php if ($search_conf['type'] == 'fulltext') echo 'checked="checked"';?> /> <?php echo lang('search_type_fulltext');?>
                            <input type="radio" name="type" value="site_url" <?php if ($search_conf['type'] == 'site_url') echo 'checked="checked"';?> /> <?php echo lang('search_type_site_url');?>
                        </div>
                    </div>
                    <div class="form-group input-group" id="site_url">
                        <div class="input-group-prepend"><span class="input-group-text">第三方搜索URL</span></div>
                        <input type="text" name="site_url" id="site_url" placeholder="URL中的{keyword}为关键字" value="<?php echo $search_conf['site_url'];?>" class="form-control" />
                    </div>
                    <div class="form-group input-group" id="range">
                        <div class="input-group-prepend"><span class="input-group-text">开启搜索范围</span></div>
                        <div class="custom-input custom-radio form-control">                            
                            <input type="radio" name="range" value="0" <?php if ($search_conf['range'] == 0) echo 'checked="checked"';?> /> <?php echo lang('all');?>
                            <input type="radio" name="range" value="1" <?php if ($search_conf['range'] == 1) echo 'checked="checked"';?> /> <?php echo lang('subject');?>
                            <input type="radio" name="range" value="2" <?php if ($search_conf['range'] == 2) echo 'checked="checked"';?> /> <?php echo lang('search_range_post');?>
                        </div>
                    </div>
                    <div class="form-group input-group" id="style">
                        <div class="input-group-prepend"><span class="input-group-text">默认搜索按钮</span></div>
                        <div class="custom-input custom-radio form-control">                            
                            <input type="radio" name="style" value="0" <?php if ($search_conf['style'] == 0) echo 'checked="checked"';?> /> 开启
                            <input type="radio" name="style" value="1" <?php if ($search_conf['style'] == 1) echo 'checked="checked"';?> /> 关闭
                        </div>
                    </div>
					<div class="form-group row">
						<div class="col-sm-12">
							<button type="submit" class="btn btn-primary btn-block" id="submit" data-loading-text="<?php echo lang('submiting');?>..."><?php echo lang('confirm');?></button>
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
var referer = '<?php echo http_referer();?>';
jform.on('submit', function() {
	jform.reset();
	jsubmit.button('loading');
	var postdata = jform.serialize();
	$.xpost(jform.attr('action'), postdata, function(code, message) {
		if (code == 0) {
			$.alert(message);
			jsubmit.text(message).delay(1000).button('reset').location();
			return;
		} else {
			$.alert(message);
			jsubmit.button('reset');
		}
	});
	return false;
});

function search_type_radio_check(v) {
	var jfulltext = $('#fulltext');
	var jsphinx = $('#sphinx');
	var jsite_url = $('#site_url');
	var jrange = $('#range');
	if (v == 'fulltext') {
		jfulltext.show();
		jsite_url.hide();
		jrange.show();
	} else if (v == 'like') {
		jfulltext.hide();
		jsite_url.hide();
		jrange.show();
	} else if (v == 'sphinx') {
		jfulltext.hide();
		jsite_url.hide();
		jrange.hide();
	} else if (v == 'site_url') {
		jfulltext.hide();
		jsite_url.show();
		jrange.hide();
	}
}

jform.find('input[name="type"]').on('click', function() {
	var v = $(this).val();
	search_type_radio_check(v);
});
search_type_radio_check(jform.find('input[name="type"]').checked());
$('#nav li.nav-item-plugin').addClass('active');
</script>