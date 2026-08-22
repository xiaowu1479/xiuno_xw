<?php !defined('DEBUG') AND exit('Access Denied.');include _include(APP_PATH.'view/htm/header.inc.htm');?>
<!--{hook search_start.htm}-->

<div class="row">
	<div class="col-lg-12 mx-auto">
		<div class="card">
			<div class="card-body pb-0">
				<form action="<?php echo url('search');?>" id="form">
				
					<div class="input-group mb-3">
						<input type="text" class="form-control" placeholder="<?php echo lang('keyword');?>" name="keyword" value="<?php echo $keyword_decode;?>">
						<div class="input-group-append">
							<button class="btn btn-primary" type="submit" id="submit"><?php echo lang('search');?></button>
						</div>
					</div>
					<?php if($search_type == 'like' || $search_type == 'fulltext') { ?>
                    <div class="form-group input-group">
                        <div class="input-group-prepend"><span class="input-group-text">范围</span></div>
                        <div class="custom-input custom-radio form-control">
                        <?php if($search_range == 0 || $search_range == 1){?>
                            <input type="radio" name="range" value="1" <?php echo $range == '1' ? 'checked' : '';?> class="align-middle" /> 标题<?php }?><?php if($search_range == 0 || $search_range == 2){?>
                            <input type="radio" name="range" value="0" <?php echo $range == '0' ? 'checked' : '';?> class="align-middle ml-2" /> 内容<?php }?>
                            
                            <input type="checkbox" name="exact" value="1" <?php echo $exact == '1' ? 'checked' : '';?> class="align-middle ml-2" /> 精确搜索
                        </div>
                    </div>
					<?php }?>
				</form>
			</div>
		</div>

		<?php if($keyword) { ?>
		<?php if($range == 1) { ?>
		<?php if($threadlist) { ?>
		
		<div class="card">
			<div class="card-header">
				<ul class="nav nav-tabs card-header-tabs">
					<li class="nav-item">
						<a class="nav-link <?php echo $active == 'default' ? 'active' : '';?>" href="./">
							<?php echo lang('thread_list');?>
						</a>
					</li>
					<!--{hook search_thread_list_nav_item_after.htm}-->
				</ul>
			</div>
			<div class="card-body">
				<ul class="list-unstyled threadlist mb-0">
					<!--{hook search_threadlist_before.htm}-->
					<?php include _include(APP_PATH.'view/htm/thread_list.inc.htm');?>
					<!--{hook search_threadlist_after.htm}-->
				</ul>
			</div>
		</div>
		
		<?php } else { ?>
		
		<div class="card">
			<div class="card-body">
				无结果
			</div>
		</div>
		
		<?php } ?>
		
		<?php include _include(APP_PATH.'view/htm/thread_list_mod.inc.htm');?>
		
		<!--{hook search_page_before.htm}-->
		<?php if($pagination) { ?>
		<nav><ul class="pagination justify-content-center"><?php echo $pagination; ?></ul></nav>
		<?php } ?>
		<!--{hook search_page_end.htm}-->

		<?php } elseif($range == 0 || $range == 9) { ?>

		<div class="card">
			<div class="card-body">
				<div class="card-title">
					<div class="media">
						<div>
							<?php echo lang('post_list');?>
						</div>
						<div class="media-body text-right">
							<!--{hook search_post_list_title_right.htm}-->
						</div>
					</div>
				</div>
				<ul class="list-unstyled postlist">
						<!--{hook search_postlist_before.htm}-->
						<?php include _include(APP_PATH.'view/htm/post_list.inc.htm'); ?>
						<!--{hook search_postlist_before.htm}-->
				</ul>
			</div>
		</div>

		<!--{hook search_page_before.htm}-->
		<?php if($pagination) { ?>
		<nav><ul class="pagination justify-content-center"><?php echo $pagination; ?></ul></nav>
		<?php } ?>
		<!--{hook search_page_end.htm}-->

		<?php } ?>
		<?php } ?>
	</div>
</div>

<!--{hook search_end.htm}-->

<?php include _include(APP_PATH.'view/htm/footer.inc.htm');?>

<script>
var jform = $('#form');
var jsubmit = $('#submit');
var jrange = jform.find('input[name="range"]');
var jexact = jform.find('input[name="exact"]');
var jkeyword = jform.find('input[name="keyword"]');
jform.on('submit', function() {
	var range = jrange.checked();
	var exact = jexact.checked();
	if(exact == ''){
		var exact = '0';
	}
	var keyword = jkeyword.val();
	window.location = xn.url('search-' + xn.urlencode(keyword) + '-' + range + '-' + exact);
	return false;
});
$('#nav_pc li[fid="<?php echo $fid;?>"]').addClass('active');
</script>

<!--{hook search_js.htm}-->