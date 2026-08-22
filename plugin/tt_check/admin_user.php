<?php !defined('DEBUG') AND exit('Forbidden');include _include(ADMIN_PATH.'view/htm/header.inc.htm');
    $search_OK = $route=='check_user'?0:-1;
    $num_pid = db_count('user',array('OK'=>$search_OK));
	$page = param(1,1);
	$data =  db_find('user',array('OK'=>$search_OK), array('uid'=>-1), $page, $pagesize = 20);
    $pagination = pagination(url($route."-{page}"), $num_pid, $page, $pagesize);
?>
<div class="row"><div class="col-lg-10 mx-auto"><div class="card"><div class="card-body" >
<table class="table"><tr><th>uid</th><th>用户名</th><th>注册时间</th><th>email地址</th><th>IP地址</th></tr>
<?php foreach($data as $v){ user_format($v);?>
    <tr id="s1_<?php echo $v['uid'];?>"><td><?php echo $v['uid'];?></td><td><?php echo $v['username'];?></td><td><?php echo date('Y-m-d H:i:s',$v['create_date']);?></td><td><?php echo $v['email'];?></td><td><?php echo $v['create_ip_fmt'];?></td>
    </tr><tr id="s2_<?php echo $v['uid'];?>"><td colspan="4"><button onclick="set_user_check('<?php echo $v['uid'];?>',1);">通过</button><button onclick="set_user_check('<?php echo $v['uid'];?>',-1);">拒绝</button><button onclick="set_user_check('<?php echo $v['uid'];?>',-2);">删除</button></td>
    </tr><?php }?></table>
<nav class="text-center"><ul class="pagination"><?php echo $pagination; ?></ul></nav>
</div></div></div></div>
<?php include _include(ADMIN_PATH.'view/htm/footer.inc.htm');?>
<script>
    function set_user_check(uid,OK){
        $.confirm('是否要审核该用户呢？',function(){
            $.xpost('../'+xn.url('post-check-'+uid),{'opt':OK,'data':uid,'act':2},function(code,message){
                if(code==0) {
                    $("#s1_" + uid).fadeOut();
                    $("#s2_" + uid).fadeOut();
                } else $.alert(message);
            });
        });
    }
</script>