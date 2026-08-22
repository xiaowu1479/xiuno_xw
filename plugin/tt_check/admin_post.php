<?php !defined('DEBUG') AND exit('Forbidden');include _include(ADMIN_PATH.'view/htm/header.inc.htm');
    $search_OK = $route=='check_post'?0:-1;
    $num_pid = db_count('post',array('OK'=>$search_OK,'isfirst'=>0));
	$page = param(1,1);
	$data =  db_find('post',array('OK'=>$search_OK,'isfirst'=>0), array('pid'=>-1), $page, $pagesize = 20);
    $pagination = pagination(url($route."-{page}"), $num_pid, $page, $pagesize);
    function username($uid){
        $r = db_find_one('user',array("uid"=>$uid));return $r['username'];
    }
    function thread_subject_get($tid){
        $r = db_find_one('thread',array('tid'=>$tid));
        return $r['subject'];
    }
?>
<div class="row"><div class="col-lg-10 mx-auto"><div class="card"><div class="card-body" >
<table class="table"><tr><th>主题</th><th>作者</th><th>发表时间</th><th>pid</th></tr>
<?php foreach($data as $v){?>
    <tr id="s1_<?php echo $v['pid'];?>"><td><a href="<?php echo '../',url('thread-'.$v['tid']);?>" target="_blank"><?php echo thread_subject_get($v['tid']);?></a></td><td><?php echo username($v['uid']);?></td><td><?php echo date('Y-m-d H:i:s',$v['create_date']);?></td><td><?php echo $v['pid'];?></td></tr>
    <tr id="s2_<?php echo $v['pid'];?>"><td colspan="4">内容：&nbsp;<?php echo $v['message'];?></td></tr>
    <tr id="s3_<?php echo $v['pid'];?>"><td colspan="4"><button onclick="set_thread_check('<?php echo $v['pid'];?>',1);">通过</button><button onclick="set_thread_check('<?php echo $v['pid'];?>',-1);">拒绝</button><button onclick="set_thread_check('<?php echo $v['pid'];?>',-2);">删除</button></td></tr><?php }?></table>
<nav class="text-center"><ul class="pagination"><?php echo $pagination; ?></ul></nav>
</div></div></div></div>
<?php include _include(ADMIN_PATH.'view/htm/footer.inc.htm');?>
<script>
    function set_thread_check(pid,OK){
        $.confirm('是否要审核该回帖呢？',function(){
            $.xpost('../'+xn.url('post-check-'+pid),{'opt':OK,'data':pid,'act':1},function(code,message){
                if(code==0) {
                    $("#s1_" + pid).fadeOut();
                    $("#s2_" + pid).fadeOut();
                    $("#s3_" + pid).fadeOut();
                } else $.alert(message);
            });
        });
    }
</script>