
<?php
!defined('DEBUG') AND exit('Access Denied.');
$search_conf = kv_cache_get('search_conf');
$style = isset($search_conf['style']) ? $search_conf['style'] : 0;
if($style == 0){?>

<div class="card">
  <div class="card-body p-2">
  <form action="<?php echo url('search');?>" id="search_form">
      <div class="input-group">
        <input type="text" class="form-control" placeholder="<?php echo lang('keyword');?>" name="keyword">
        <div class="input-group-append">
          <button class="btn btn-primary" type="submit"><?php echo lang('search');?></button>
        </div>
      </div>
  </form>
  </div>
</div>
<?php }?>
