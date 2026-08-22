<?php !defined('DEBUG') AND exit('Access Denied.');include _include(ADMIN_PATH.'view/htm/header.inc.htm');?>
<div class="row">
    <div class="col-lg-12">
        <div class="btn-group mb-3" role="group">
            <a class="btn btn-secondary" href="<?php echo url("plugin");?>">本地插件</a>
            <a class="btn btn-secondary active" href="javascript:void(0);">插件设置</a>
        </div>
        <div class="btn-group mb-3 hidden-sm float-right" role="group">
            <a class="btn btn-danger" target="_blank" href="https://bbs.oddfox.cn"><i class="icon-firefox"></i></a>
            <a class="btn btn-dark" href="javascript:void(0);" onclick="javascript:location.reload();"><i class="icon-refresh"></i></a>
            <a class="btn btn-dark" href="<?php echo url("plugin");?>"><i class="icon-times"></i></a>
        </div>
        <div class="w-100"></div>
        <div class="card">
            <div class="card-header"><i class="icon-cogs"></i> 插件设置</div>
            <div class="card-body pb-0">
                <form action="<?php echo url("plugin-setting-fox_tags");?>" method="post" id="form">
                <div class="form-group row">
                    <label class="col-sm-2 form-control-label text-sm-right pr-sm-0 pt-sm-2">首页标签显示条数：</label>
                    <div class="col-sm-10">
                        <?php echo $input['tag_index'];?>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-2 form-control-label text-sm-right pr-sm-0 pt-sm-2">标签列表显示条数：</label>
                    <div class="col-sm-10">
                        <?php echo $input['tag_list'];?>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-2 form-control-label text-sm-right pr-sm-0">首页标签栏显示位置：</label>
                    <div class="col-sm-10">
                        <?php echo $input['tag_pos'];?>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-2 form-control-label text-sm-right pr-sm-0">列表标题后显示标签：</label>
                    <div class="col-sm-10">
                        <?php echo $input['tag_inc'];?>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-2 form-control-label text-sm-right pr-sm-0">帖子标题后显示标签：</label>
                    <div class="col-sm-10">
                        <?php echo $input['tag_top'];?>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-2 form-control-label text-sm-right pr-sm-0">帖子内容后显示标签：</label>
                    <div class="col-sm-10">
                        <?php echo $input['tag_end'];?>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-2 form-control-label text-sm-right pr-sm-0">帖子后相关推荐设置：</label>
                    <div class="col-sm-10">
                        <?php echo $input['tag_two'];?>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-2 form-control-label text-sm-right pr-sm-0">卸载时数据处理：</label>
                    <div class="col-sm-10">
                        <?php echo $input['tag_retain'];?>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-2 form-control-label text-sm-right pr-sm-0 pt-sm-2">每帖最多几个标签：</label>
                    <div class="col-sm-10">
                        <?php echo $input['tag_max'];?>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-2 form-control-label text-sm-right pr-sm-0 pt-sm-2">每个标签限制长度：</label>
                    <div class="col-sm-10">
                        <?php echo $input['tag_lim'];?>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-2 form-control-label text-sm-right pr-sm-0">标签敏感词检测：</label>
                    <div class="col-sm-10">
                        <?php echo $input['tag_words'];?>
                        <div class="text-grey small mt-2">注：管理员组不受限；多个敏感词请用半角逗号,分隔 如：Jack,Lisa,Mike</div>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-2 form-control-label text-sm-right pr-sm-0">过滤敏感词的用户组：</label>
                    <div class="col-sm-10"><?php foreach($grouplist as $_group){?>
                        <label class="custom-input custom-checkbox mr-2"><input type="checkbox" name="allowtags3[]" value="<?php echo $_group['gid']?>" class="allowtags3" <?php if(!empty($_group['allowtags3'])){?>checked<?php }?> /> <?php echo $_group['name'];?></label><?php }?>
                        <label class="custom-input custom-checkbox text-danger"><input type="checkbox" class="checkall" data-target=".allowtags3" /> 全选反选</label>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-2 form-control-label text-sm-right pr-sm-0">允许填写标签的用户组：</label>
                    <div class="col-sm-10"><?php foreach($grouplist as $_group){?>
                        <label class="custom-input custom-checkbox mr-2"><input type="checkbox" name="allowtags[]" value="<?php echo $_group['gid']?>" class="allowtags" <?php if(!empty($_group['allowtags'])){?>checked<?php }?> /> <?php echo $_group['name'];?></label><?php }?>
                        <label class="custom-input custom-checkbox text-danger"><input type="checkbox" class="checkall" data-target=".allowtags" /> 全选反选</label>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-2 form-control-label text-sm-right pr-sm-0">编辑标签详情用户组：</label>
                    <div class="col-sm-10"><?php foreach($grouplist as $_group){?>
                        <label class="custom-input custom-checkbox mr-2"><input type="checkbox" name="allowtags2[]" value="<?php echo $_group['gid']?>" class="allowtags2" <?php if(!empty($_group['allowtags2'])){?>checked<?php }?> /> <?php echo $_group['name'];?></label><?php }?>
                        <label class="custom-input custom-checkbox text-danger"><input type="checkbox" class="checkall" data-target=".allowtags2" /> 全选反选</label>
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
jform.on('submit', function(){
    jform.reset();
    jsubmit.button('loading');
    var postdata = jform.serialize();
    $.xpost(jform.attr('action'), postdata, function(code, message) {
        if(code == 0) {
            $.alert(message, 1);
            setTimeout(function(){window.location.reload();jsubmit.button('reset');}, 1000);
            return;
        } else {
            $.alert(message);
            jsubmit.button('reset');
        }
    });
    return false;
});
$('#nav li.nav-item-plugin').addClass('active');
</script>