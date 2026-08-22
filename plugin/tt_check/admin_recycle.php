<?php !defined('DEBUG') AND exit('Forbidden');
    include _include(ADMIN_PATH.'view/htm/header.inc.htm');
    $num_pid = db_count('thread',array('OK'=>-2));
	$page = param(1,1);
	$data =  db_find('thread',array('OK'=>-2), array('tid'=>-1), $page, $pagesize = 20);
    $pagination = pagination(url($route."-{page}"), $num_pid, $page, $pagesize);
    function username($uid){
        $r = db_find_one('user',array("uid"=>$uid));return $r['username'];
    }
?>
<div class="row"><div class="col-lg-10 mx-auto"><div class="card"><div class="card-body" >
<button class="btn btn-danger btn-block" id="truncate_recycle">清空回收站</button>
</div></div><div class="card"><div class="card-body" >
<table class="table"><tr><th>主题</th><th>作者</th><th>发表时间</th><th>tid</th></tr>
<?php foreach($data as $v){?>
    <tr id="s1_<?php echo $v['tid'];?>"><td><a href="<?php echo '../',url('thread-'.$v['tid']);?>" target="_blank"><?php echo $v['subject'];?></a></td><td><?php echo username($v['uid']);?></td><td><?php echo date('Y-m-d H:i:s',$v['create_date']);?></td><td><?php echo $v['tid'];?></td></tr>
    <tr id="s2_<?php echo $v['tid'];?>"><td colspan="4"><button onclick="set_thread_recycle('<?php echo $v['tid'];?>',-1);">恢复</button><button onclick="set_thread_recycle('<?php echo $v['tid'];?>',1);">彻底删除</button></td></tr><?php }?></table>
<nav class="text-center"><ul class="pagination"><?php echo $pagination; ?></ul></nav>
</div></div></div></div>
<?php include _include(ADMIN_PATH.'view/htm/footer.inc.htm');?>
<script>
    function set_thread_recycle(tid,OK){
        var opt = OK==1?'删除':'恢复';
        $.confirm('是否要'+opt+'该主题呢？',function(){
            $.xpost('../'+xn.url('post-recycle-'+tid),{'opt':OK,'data':tid},function(code,message){
                if(code==0) {
                    $("#s1_" + tid).fadeOut();
                    $("#s2_" + tid).fadeOut();
                } else $.alert(message);
            });
        });
    }
    var jtruncate=$("#truncate_recycle");
    jtruncate.on('click',function(){
        $.confirm('您确定要清空回收站吗？清空后将无法恢复！',function(){
            jtruncate.button('loading');
            var postdata="op=1";
            $.xpost("<?php echo url('plugin-setting-tt_check');?>",postdata,function(code,message){
                jtruncate.button('reset');
                if(code==0) location.reload();
                else $.alert(message);
            });
        });
    });
</script>