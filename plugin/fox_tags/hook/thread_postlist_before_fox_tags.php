
<?php 
!defined('DEBUG') AND exit('Access Denied.');
if(!empty($fox_tag_kv['tag_two'])){
    $thread_related = fox_tag_thread_related($thread['fid'], $thread['tid'], $thread['keywords']);
    if(!empty($thread_related)){
        if($fox_tag_kv['tag_two'] == 2){
?>
<div class="card">
    <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs">
            <li class="nav-item"><a class="nav-link active"><b>相关推荐</b></a></li>
        </ul>
    </div>
    <div class="card-body pt-1 pr-3 pb-2 pl-3">
      <ul class="list-unstyled p-0 m-0 w-100">
          <?php foreach($thread_related as $k => $v){$k = $k+1;?>
          <li class="text-truncate w-100 border-bottom pt-2 pb-2 col-sm-12 col-md-12 col-lg-6 float-left pl-0"><span class="float-right hidden-sm"><?php echo date('m-d', $v['create_date']);?></span><i class="icon-caret-right mr-1"></i> <a href="<?php echo url("thread-$v[tid]");?>" title="<?php echo $v['subject'];?>"><?php echo $v['subject'];?></a></li>
          <?php }?>
      </ul>
    </div>
</div>
<?php }else{?>
<div class="card">
    <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs">
            <li class="nav-item"><a class="nav-link active"><b>相关推荐</b></a></li>
        </ul>
    </div>
    <div class="card-body pt-1 pr-3 pb-2 pl-3">
      <ul class="list-unstyled p-0 m-0 w-100">
          <?php foreach($thread_related as $k => $v){$k = $k+1;?>
          <li class="text-truncate w-100 pt-2 pb-2 pl-0 <?php if($k < 10){?>border-bottom<?php }?>"><span class="float-right hidden-sm"><?php echo date('Y-m-d', $v['create_date']);?></span><i class="icon-caret-right mr-1"></i> <a href="<?php echo url("thread-$v[tid]");?>" title="<?php echo $v['subject'];?>"><?php echo $v['subject'];?></a></li>
          <?php }?>
      </ul>
    </div>
</div>
<?php }}}?>
