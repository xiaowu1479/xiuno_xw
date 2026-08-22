<?php !defined('DEBUG') AND exit('Forbidden');
include _include(ADMIN_PATH.'view/htm/header.inc.htm');
$medalchecklist=db_find('medal_check',array('result'=>'0'))
?>
<div class="row">
    <div class="col-lg-10 mx-auto">
        <div class="card"><div class="card-body">
            <h4><?php echo lang('medal_check') ?></h4>
            <table class="table" style="text-align:center;vertical-align:middle;">
                <tr><th><?php echo lang('medal') ?></th>
                <th>申请人</th>
                <th>申请时间</th>
                <th>申请原因</th>
                <th>操作</th></tr>
                <?php foreach($medalchecklist as $m){?><tr>
                    <td><img width="20px" height="35px" src="../plugin/tt_medal/img/<?php echo medal_read($m['mid'])['filename'];?>" title="<?php echo medal_read_name($m['mid']);?>"/></td>
                    <td><?php echo db_find_one('user',array('uid'=>$m['uid']))['username'] ;?></td>
                    <td><?php echo date('Y/m/d H:i:s',$m['time']);?></td>
                    <td><?php echo $m['reason'];?></td>
                    <td>
                        <button type="button" class="btn btn-outline-primary" onclick="adminVerify('<?php echo $m["mcid"];?>',1)">同意</button>
                        <button type="button" class="btn btn-outline-primary" onclick="adminVerify('<?php echo $m["mcid"];?>',0)">拒绝</button>
                    </td>
                </tr><?php }?>    
            </table>
        </div>
    </div>
</div>
<?php include _include(ADMIN_PATH.'view/htm/footer.inc.htm');?>
<script>
    function adminVerify(mcid,type){
        var postdata={"mcid":mcid,"type":type,"op":2}
        $.xpost(xn.url('plugin-setting-tt_medal'),postdata,function(code,message){
            $.alert(message);
            if(code==0)
			    setInterval(function(){
			        location.reload();
			},1000);    
        })
    }
</script>