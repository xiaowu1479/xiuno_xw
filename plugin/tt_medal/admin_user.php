<?php !defined('DEBUG') AND exit('Forbidden');
include _include(ADMIN_PATH.'view/htm/header.inc.htm');
$target_username = param('username');
$target_user = db_find_one('user',array('username'=>$target_username));
$num_pid = db_count('medal',array());
$medallist =  medal_list_get(1,50);
?>
<div class="row"><div class="col-lg-10 mx-auto"><div class="card"><div class="card-body">
                <?php if($target_user){?>
                <h4>管理用户的勋章 - <?php echo $target_username;?></h4>
                <form action="<?php echo url('plugin-setting-tt_medal');?>" method="POST" id="form">
                <input hidden="hidden" name="username" value="<?php echo $target_username;?>"/>
                <table class="table"><tr><th>勋章</th><th>介绍</th><th>拥有</th></tr>
                    <?php foreach($medallist as $m){?><tr>
                        <td><img src="../plugin/tt_medal/img/<?php echo $m['filename'];?>"/><br><?php echo $m['name'];?></td>
                        <td><?php echo $m['description'];?></td>
                        <td><input type="checkbox" name="medal[<?php echo $m['mid'];?>]" value="1" <?php if(medal_user_havemedal($m['mid'],$target_user['uid'])) echo 'checked';?>/></td>
                    </tr><?php }?>
                </table>
                <button type="button" class="btn btn-outline-primary btn-block" id="submit">设定</button>
                </form>
                <?php } else {?>该用户不存在！<?php }?>
</div></div></div></div>
<?php include _include(ADMIN_PATH.'view/htm/footer.inc.htm');?>
<script>
    var jform = $("#form");var jsubmit = $("#submit");
    jsubmit.on('click', function(){
        jform.reset();
        jsubmit.button('loading');
        var postdata = jform.serialize()+'&op=1';
        $.xpost(jform.attr('action'), postdata, function(code, message) {
            if(code == 0) {
                $.alert(message);
                setTimeout(function() {
                    window.location.reload();
                    jsubmit.button('reset');
                }, 1000);
                return;}
            else {$.alert(message);jsubmit.button('reset');}});
        return false;});
</script>
