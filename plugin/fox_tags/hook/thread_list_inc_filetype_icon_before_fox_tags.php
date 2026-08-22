
<?php 
!defined('DEBUG') AND exit('Access Denied.');
if(!empty($fox_tag_kv['tag_inc'])){
    $fox_tag_thread = fox_tag_thread($_thread['tid']);
    if(!empty($fox_tag_thread)){
?>
<span class="mb-0 text-info xxhidden-sm">
<?php foreach ($fox_tag_thread as $value){?>
    <a href="<?php echo url("tag-{$value['tagid']}");?>" target="_blank" class="text-info">[<?php echo $value['name'];?>]</a>
<?php }?>
</span>
<?php }}?>
