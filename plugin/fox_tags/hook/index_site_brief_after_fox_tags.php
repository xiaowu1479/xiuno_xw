
<?php 
!defined('DEBUG') AND exit('Access Denied.');
if(!empty($fox_tag_kv['tag_pos'])){
    if(!empty($fox_tag_kv['tag_index'])){
        $fox_tag_index_list = fox_tag_index_list($fox_tag_kv['tag_index']);
        if(!empty($fox_tag_index_list)){
?>
<div class="card index-tags">    
    <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs"><li class="nav-item"><a href="<?php echo url("tag-list");?>" target="_blank" class="nav-link active"><b>更多标签</b></a></li></ul>
    </div>
    <div class="card-body items"><?php foreach($fox_tag_index_list as $val){?>
        <a href="<?php echo url("tag-".$val['tagid']);?>" target="_blank"><?php echo $val['name'] ." ({$val['num']})";?></a><?php }?>        
    </div>
</div>
<?php }}}?>
