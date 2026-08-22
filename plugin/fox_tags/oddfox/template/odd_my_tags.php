<template include="./plugin/fox_tags/oddfox/template/my_tags.template.htm">
<slot name="my_body">
<?php if($list){?>
<?php foreach($list as $k=>$_tag){?>
    <div class="media w-100 mb-3" id="follow_<?php echo $_tag['tagid'];?>">
        <a href="<?php echo url("tag-$_tag[tagid]");?>" target="_blank"><img src="<?php echo $_tag['cover'];?>" alt="" class="avatar-4 rounded border"></a>
        <div class="media-body ml-3 position-relative">
            <div class="float-right mt-4"><a role="button" href="javascript:;" onclick="follow_tag('<?php echo $_tag['tagid'];?>');" class="btn btn-secondary py-0 px-2">取消关注</a></div>
            <div class="d-flex justify-content-between">
                <div>
                    <h5 class="font-weight-bold mb-0 mt-4"><a href="<?php echo url("tag-$_tag[tagid]");?>" target="_blank"><?php echo $_tag['name'];?></a></h5>
                </div>
            </div>
        </div>
    </div>
<?php }?>
    <?php if($pagination){?>
    <nav class="my-3"><ul class="pagination justify-content-center flex-wrap"><?php echo $pagination; ?></ul></nav>
    <?php }?>
<?php }?>
</slot>
</template>
<script>
$('a[data-active="my"]').addClass('active');
$('a[data-active="my-tags"]').addClass('active');
function follow_tag(tagid){
    $.xpost(xn.url('tag-follow'), {'tagid':tagid}, function(code, message){
        if(code == 1){
            $('#follow_'+tagid).remove();
        }else{
            $.alert(message);        
        }
    });
    return false;
}
</script>