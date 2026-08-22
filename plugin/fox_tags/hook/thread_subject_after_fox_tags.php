
<?php 
!defined('DEBUG') AND exit('Access Denied.');
if(!empty($fox_tag_kv['tag_top'])){
    $fox_tag_thread = fox_tag_thread($tid);
    if(!empty($fox_tag_thread)){
        foreach($fox_tag_thread as $value){
            echo '<a href="' . url("tag-$value[tagid]") . '" target="_blank" class="mr-1 badge badge-primary hidden-sm">' . $value['name'] . '</a>';
        }
    }
}?>
