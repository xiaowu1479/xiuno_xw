<?php !defined('DEBUG') AND exit('Access Denied.');include _include(APP_PATH.'view/htm/header.inc.htm');?>
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header"><i class="icon-tags"></i> 编辑TAG - <?php echo $query['name'];?></div>
            <div class="card-body">
                <form action="<?php echo url("tag-edit-$tagid");?>" method="post" id="form">
                <div class="form-group row">
                    <label class="col-sm-2 form-control-label text-sm-right pr-sm-0 pt-sm-2">TAG标签SEO标题：</label>
                    <div class="col-sm-10">
                        <?php echo $input['subject'];?>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-2 form-control-label text-sm-right pr-sm-0 pt-sm-2">TAG标签SEO关键词：</label>
                    <div class="col-sm-10">
                        <?php echo $input['keywords'];?>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-2 form-control-label text-sm-right pr-sm-0">TAG标签简介：</label>
                    <div class="col-sm-10">
                        <?php echo $input['brief'];?>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-2 form-control-label text-sm-right pr-sm-0 pt-sm-2">TAG标签封面路径：</label>
                    <div class="col-sm-10 input-group">
                        <?php echo $input['cover'];?>
                        <div class="input-group-append">
                            <label class="btn btn-danger mb-0 w-100" id="cover_upload">上传封面<input type="file" class="d-none" accept="image/gif,image/jpeg,image/png,image/jpg" /></label>
                        </div>
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

<?php include _include(APP_PATH.'view/htm/footer.inc.htm');?>

<script>
var jcover_upload = $('#cover_upload');
var jcover = $('#cover');
xn.options.water_image_url = '';
jcover_upload.on('change',function(e){
    var files = xn.get_files_from_event(e);
    xn.upload_file(files[0], '<?php echo url("tag-cover-{$tagid}");?>', {filetype: 'png'}, 
    function(code, message){
        if (code == 0){
            jcover.val(message.url)
        } else {
            $.alert(message)
        }
    })
});
var jform = $("#form");
var jsubmit = $("#submit");
jform.on('submit', function(){
    jform.reset();
    jsubmit.button('loading');
    var postdata = jform.serialize();
    $.xpost(jform.attr('action'), postdata, function(code, message) {
        if(code == 0) {
            $.alert(message, 1);
            setTimeout(function(){window.location = '<?php echo url("tag-$tagid");?>';}, 1000);
            return false;
        }else{
            $.alert(message);
            jsubmit.button('reset');
        }
    });
    return false;
});
</script>