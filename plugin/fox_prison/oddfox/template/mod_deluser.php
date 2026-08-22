<?php !defined('DEBUG') AND exit('Access Denied.');include _include(APP_PATH.'view/htm/header.inc.htm');?>
<div class="card">
    <div class="card-header font-weight-bold">确定枪毙</div>
    <div class="card-body ajax-body">
        <form action="<?php echo url("mod-delusers");?>" method="post" id="mod_delusers_form">
        <div class="form-group row">
            <label class="col-4 form-control-label text-right"></label>
            <div class="col-12 text-center">共枪毙 <span class="total font-weight-bold text-danger"></span> 个用户</div>
        </div>
        <div class="form-group row">
            <label class="col-4 form-control-label text-right"></label>
            <div class="col-8">
                <button type="submit" class="btn btn-primary mr-2" data-loading-text="<?php echo lang('submiting');?>...">确定枪毙</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">关闭窗口</button>
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
var jlists = $(arg);  // .lists
var idsarr = jlists.find('input[name="moduid"]').checked();
var jform = $('#mod_delusers_form');
var jsubmit = jform.find('button[type="submit"]');
var jcancel = jform.find('button[type="button"]');
var jtotal = jform.find('span.total');
jtotal.text(idsarr.length);
jform.on('submit', function(){
    jform.reset();
    jsubmit.button('loading');
    var postdata = jform.serializeObject();
    postdata.idsarr = idsarr;
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
</script>