
<?php !defined('DEBUG') AND exit('Access Denied.');if(!empty($group['allowtags'])){$from_tag=!empty($tid) ? fox_tag_post($tid) : '';?>
<div class="form-group input-group position-relative" id="fox_tag_box">
    <div class="input-group-prepend"><span class="input-group-text">帖子标签：</span></div>
    <input type="text" name="fox_tag" id="fox_tag" placeholder="多个标签用空格或半角逗号隔开，如：奇狐,插件,模版" value="<?php echo $from_tag;?>" class="form-control" autocomplete="off" />
    <div id="tag_loading" class="bg-white border position-absolute p-2" style="display:none">
        <h6 class="heading">相关标签</h6>
        <div class="row">
            <div class="col-12">
                <ul id="tag-ajax-res" class="list-unstyled mb-0"></ul>
            </div>
        </div>
    </div>
</div>
<?php }?>
