<?php !defined('DEBUG') AND exit('Access Denied.');include _include(APP_PATH.'view/htm/header.inc.htm');?>
<div class="card">
    <div class="card-header font-weight-bold">关小黑屋</div>
    <div class="card-body ajax-body">
        <form action="<?php echo url("mod-prison-{$puid}");?>" method="post" id="mod_prison_form">
        <div class="form-group row">
            <div class="col-sm-12">
                <textarea name="message" id="message" class="form-control" placeholder="禁闭理由" style="width:100%;height:100px;"></textarea>
                <div class="text-grey small mt-2">注：支持简单HTML格式，换行用&lt;br &gt;</div>
            </div>
        </div>
        <div class="input-group mb-3">
            <div class="input-group-prepend"><span class="input-group-text w-100">禁闭时长</span></div>
            <input type="number" class="form-control col-2" placeholder="有效时间" name="endtime" value="1" id="endtime" minlength="1" maxlength="2" />
            <select name="timetype" id="timetype" class="custom-select">
                <option value="1" selected="selected">天</option>
                <option value="2">月</option>
                <option value="3">永久</option>
            </select>
        </div>
        <div class="d-flex justify-content-between">
            <div></div>
            <div>
                <button type="submit" id="submit" class="btn btn-primary" data-loading-text="<?php echo lang('submiting');?>">确定禁闭</button>
                <button type="button" class="btn btn-secondary" id="Close" data-dismiss="modal">关闭窗口</button>
            </div>
        </div>
        </form>
    </div>
</div>
<?php include _include(APP_PATH.'view/htm/footer.inc.htm');?>
<script ajax-eval="true">
// 接受传参
var args = args || {jmodal: null, callback: null, arg: null};
var jmodal = args.jmodal;
var callback = args.callback;
var arg = args.arg;
var jform = $('#mod_prison_form');
var jsubmit = jform.find('button[type="submit"]');
var jcancel = jform.find('button[type="button"]');
jform.on('submit', function(){
    jform.reset();
    jsubmit.button('loading');
    var postdata = jform.serialize();
    $.xpost(jform.attr('action'), postdata, function(code, message){
        if(code == 0){
            jsubmit.button(message);
            setTimeout(function(){
                if(jmodal) jmodal.modal('dispose');
                if(callback) callback(message);
                window.location.reload();
            }, 1000);
        }else{
            alert(message);
            jsubmit.button('reset');
        }
    });
    return false;
});
jcancel.on('click', function(){
    if(jmodal) jmodal.modal('dispose');$('body').removeClass('modal-open').removeAttr('style');
});
$("#endtime").bind("input propertychange",function(event){
    if(this.value.length == 1) {
        this.value = this.value.replace(/[^1-9]/g, '1');
    } else {
        this.value = this.value.replace(/\D/g, '0');
    }
})
</script>