<?php !defined('DEBUG') AND exit('Forbidden');include _include(ADMIN_PATH.'view/htm/header.inc.htm');
    $search_OK = $route=='check_thread'?0:-1;
    $num_pid = db_count('thread',array('OK'=>$search_OK));
	$page = param(1,1);
	$data =  db_find('thread',array('OK'=>$search_OK), array('tid'=>-1), $page, $pagesize = 20);
    $pagination = pagination(url($route."-{page}"), $num_pid, $page, $pagesize);
    function username($uid){
        $r = db_find_one('user',array("uid"=>$uid));return $r['username'];
    }
?>
<div class="row"><div class="col-lg-10 mx-auto"><div class="card"><div class="card-body" >
<table class="table"><tr><th>主题</th><th>作者</th><th>发表时间</th><th>tid</th></tr>
<?php foreach($data as $v){?>
    <tr id="s1_<?php echo $v['tid'];?>"><td><a href="<?php echo '../',url('thread-'.$v['tid']);?>" target="_blank"><?php echo $v['subject'];?></a></td><td><?php echo username($v['uid']);?></td><td><?php echo date('Y-m-d H:i:s',$v['create_date']);?></td><td><?php echo $v['tid'];?></td></tr>
    <tr id="s2_<?php echo $v['tid'];?>"><td colspan="4"><button onclick="set_thread_check('<?php echo $v['tid'];?>',1);">通过</button><button onclick="set_thread_check('<?php echo $v['tid'];?>',-1);">拒绝</button><button onclick="set_thread_check('<?php echo $v['tid'];?>',-2);">删除</button></td></tr><?php }?></table>
<nav class="text-center"><ul class="pagination"><?php echo $pagination; ?></ul></nav>
</div></div></div></div>
<?php include _include(ADMIN_PATH.'view/htm/footer.inc.htm');?>
<script>
    function set_thread_check(tid,OK){
        $.confirm('是否要审核该主题呢？',function(){
            $.xpost('../'+xn.url('post-check-'+tid),{'opt':OK,'data':tid,'act':0},function(code,message){
                if(code==0) {
                    $("#s1_" + tid).fadeOut();
                    $("#s2_" + tid).fadeOut();
                } else $.alert(message);
            });
        });
    }
</script>