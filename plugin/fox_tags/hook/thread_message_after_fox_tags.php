
<?php 
!defined('DEBUG') AND exit('Access Denied.');
if(!empty($fox_tag_kv['tag_end'])){
    $fox_tag_thread = fox_tag_thread($tid);
    if(!empty($fox_tag_thread)){
?>
<p class="thread-tags my-3"><span class="btn btn-sm btn-danger">TAGS <i class="icon-tags"></i></span> 
<?php foreach ($fox_tag_thread as $value){?>
    <a href="<?php echo url("tag-{$value['tagid']}");?>" rel="tag" target="_blank" class="btn btn-sm btn-outline-secondary"><?php echo $value['name'];?></a>
<?php }?>
</p>
<?php }}?>
