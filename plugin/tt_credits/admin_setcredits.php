<?php !defined('DEBUG') AND exit('Forbidden');include _include(ADMIN_PATH.'view/htm/header.inc.htm');
$set = setting_get('tt_credits'); 
// 为新配置项提供默认值，避免 Undefined index 错误
if (!isset($set['buy_push'])) $set['buy_push'] = '0';
if (!isset($set['attach_buy_push'])) $set['attach_buy_push'] = '0';
$s_index=0; ?>
<div class="row"><div class="col-lg-10 mx-auto"><div class="card"><div class="card-body" ><h3>设置积分规则及兑换比例</h3>增加积分时，请不要输入+号，如果要扣积分输入-，不要输入符号、字母、无意义字符等，防止出错。<br>不科学地设置可能会出现用户刷分的情况，比如说下载附件时 <?php echo lang('credits1');?>+1 <?php echo lang('credits2');?>-1 ，会导致用户变相刷经验升级！请务必合理设置。<br><form action="<?php echo url("plugin-setting-tt_credits");?>" method="post" id="form"><table cellspacing="0" class="table"><tr><th>-</th><th>发表主题</th><th>发表回帖</th><th>下载附件</th><th>精华1</th><th>精华2</th><th>精华3</th></tr>
<tr><td><?php echo lang('credits1');?></td><?php for($a=0;$a<6;$a++) {echo '<td><input class="form-control" name="',$g_credits_item_array[$s_index],'" maxlength="8" value="',$set[$g_credits_item_array[$s_index]],'"/></td>'; $s_index++;}?></tr>
<tr><td><?php echo lang('credits2');?></td><?php for($a=0;$a<6;$a++) {echo '<td><input class="form-control" name="',$g_credits_item_array[$s_index],'" maxlength="8" value="',$set[$g_credits_item_array[$s_index]],'"/></td>'; $s_index++;}?></tr>
<tr><td><?php echo lang('credits3');?></td><?php for($a=0;$a<6;$a++) {echo '<td><input class="form-control" name="',$g_credits_item_array[$s_index],'" maxlength="8" value="',$set[$g_credits_item_array[$s_index]],'"/></td>'; $s_index++;}?></tr></tr></table>每日通过发表主题/回帖加分的最高次数(减分不限,输入0为无限次数):<input class="form-control" name="limit" value="<?php echo $set['limit'];?>"/><br>最低兑换金额，不要输入小数点和字符，防止出错:<input class="form-control" name="min" value="<?php echo $set['min'];?>"/>
<div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;">
<h5>RMB单位换算设置</h5>
<p><strong>RMB单位换算比例：</strong>1元 = 多少个系统<?php echo lang('credits3');?>单位</p>
<p style="color: #dc3545;">例如：设置为100表示1元=100个系统单位(分制)，设置为1表示1元=1个系统单位(元制)</p>
</div>
RMB单位换算比例 (1元 = ? 个系统<?php echo lang('credits3');?>单位):<input class="form-control" name="rmb_unit_rate" value="<?php echo isset($set['rmb_unit_rate']) ? $set['rmb_unit_rate'] : '100';?>" placeholder="例如：100表示分制，1表示元制"/><br>

<div style="background-color: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;">
<h5>兑换比例设置说明</h5>
<p><strong>RMB兑换金币比例：</strong>1 <?php echo lang('credits3');?>单位 可以兑换多少 <?php echo lang('credits2');?></p>
<p><strong>金币兑换RMB比例：</strong>1 <?php echo lang('credits2');?> 可以兑换多少 <?php echo lang('credits3');?>单位</p>
<p style="color: #0d6efd;">当前RMB显示单位：
<?php 
$unit_info = credits_get_rmb_unit_info();
if($unit_info['is_yuan']) {
    echo '元制(1元=1单位)';
} elseif($unit_info['is_jiao']) {
    echo '角制(1元=10单位)';
} else {
    echo $unit_info['unit_name'].'制(1元='.$unit_info['rate'].'单位)';
}
?>
</p>
</div>
RMB兑换金币比例 (1<?php echo lang('credits3');?>单位 → ? <?php echo lang('credits2');?>):<input class="form-control" name="exchange_n" value="<?php echo isset($set['exchange_n']) ? $set['exchange_n'] : '1';?>" placeholder="例如：10表示1单位可兑换10金币"/><br>
金币兑换RMB比例 (1<?php echo lang('credits2');?> → ? <?php echo lang('credits3');?>单位):<input class="form-control" name="exchange_c" value="<?php echo isset($set['exchange_c']) ? $set['exchange_c'] : '0.1';?>" placeholder="例如：0.1表示1金币可兑换0.1单位"/><br>
<input type="checkbox" name="convert_exchange" id="convert_exchange" value="convert_exchange" <?php if($set&&$set['convert_exchange']) echo 'checked'; ?>/><label for="convert_exchange">开启反向兑换(<?php echo lang('credits2');?>→<?php echo lang('credits3');?>)</label><br>
<input type="checkbox" name="buy_push" id="buy_push" value="buy_push" <?php if($set['buy_push'] == '1') echo 'checked'; ?>/><label for="buy_push">购买付费主题后消息通知卖家</label><br>
<input type="checkbox" name="attach_buy_push" id="attach_buy_push" value="attach_buy_push" <?php if($set['attach_buy_push'] == '1') echo 'checked'; ?>/><label for="attach_buy_push">购买付费附件后消息通知卖家</label>
<button type="submit" class="btn btn-success btn-block" id="submit" data-loading-text="<?php echo lang('submiting');?>..."><?php echo lang('confirm');?></button></form></div></div><div class="card"><div class="card-body" >
<h3>设置注册默认积分</h3>请不要输入特殊字符等，防止出错。该工具可能会出错，请谨慎使用！！！后期不建议更改，本工具暂时不支持查看当前状态，请从PMA或前台注册一个用户，查看是否生效。<br>
 <form action="<?php echo url("plugin-setting-tt_credits");?>" method="post" id="form2">
<div class="input-group mb-3"><div class="input-group-prepend"><span class="input-group-text"><?php echo lang('credits1');?></span></div><input class="form-control" name="d_credit" value="0"/></div>
<div class="input-group mb-3"><div class="input-group-prepend"><span class="input-group-text"><?php echo lang('credits2');?></span></div><input class="form-control" name="d_gold" value="0"/></div>
<div class="input-group mb-3"><div class="input-group-prepend"><span class="input-group-text"><?php echo lang('credits3');?></span></div><input class="form-control" name="d_rmb" value="0" id="d_rmb"/></div>
     <?php $rmb_unit_info_2 = credits_get_rmb_unit_info(); ?>
     合<?php echo lang('credits3');?> ¥ <span style="color:red;" id="show_rmb">0</span> (当前为<?php 
echo $rmb_unit_info_2['is_yuan'] ? '元制' : ($rmb_unit_info_2['is_jiao'] ? '角制' : $rmb_unit_info_2['unit_name'].'制'); ?>)<br>
     <button type="submit" class="btn btn-success btn-block" id="submit2" data-loading-text="<?php echo lang('submiting');?>..."><?php echo lang('confirm');?></button></form></div></div>
<?php include _include(ADMIN_PATH.'view/htm/footer.inc.htm');?>
<script>
    var jform = $("#form");var jsubmit = $("#submit"); var jform2=$("#form2"); var jsubmit2=$("#submit2");
    jform.on('submit', function(){
        jform.reset();jsubmit.button('loading');
        var postdata = jform.serialize();postdata+= "&op=3";
        $.xpost(jform.attr('action'), postdata, function(code, message) {
            if(code == 0) {
                $.alert(message);setTimeout(function() {window.location.reload();jsubmit.button('reset');}, 1000);
                return; } else {$.alert(message);jsubmit.button('reset');}
        });return false;});
    jform2.on('submit', function(){
        jform2.reset();jsubmit2.button('loading');
        var postdata = jform2.serialize();postdata+= "&op=5";
        $.xpost(jform2.attr('action'), postdata, function(code, message) {
            if(code == 0) {$.alert(message); setTimeout(function() {window.location.reload();jsubmit2.button('reset');}, 1000);
                return;} else {$.alert(message);jsubmit2.button('reset');}
        });return false;});
    var jinput2=$("#d_rmb");var jrmbs=$("#show_rmb");
    jinput2.bind('input propertychange',function(){
        ONInput2(jinput2);
    });
    function ONInput2(input){
        input.val(input.val().replace(/[^0-9]/g, ''));
        var rmb_unit_rate = <?php echo $rmb_unit_info_2['rate']; ?>;
        jrmbs.text(input.val()==''?'0':(input.val()/rmb_unit_rate).toFixed(3));
    }
</script>