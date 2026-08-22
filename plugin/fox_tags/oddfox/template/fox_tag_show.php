<?php !defined('DEBUG') AND exit('Access Denied.');include _include(APP_PATH.'view/htm/header.inc.htm');?>
<div class="row">
    <div class="col-lg-9 main">
        <div class="card">
            <div class="card-body">
                <div class="media">
                    <img src="<?php echo !empty($query['cover']) ? $query['cover'] : 'plugin/fox_tags/oddfox/static/img/cover.png';?>" alt="" class="cover-1 rounded border">
                    <div class="media-body ml-3 position-relative">
                        <?php if(!empty($group['allowtags2'])){?><div class="tag-edit"><a href="<?php echo url("tag-edit-$tagid");?>" role="button" class="badge badge-secondary px-2 py-1"><i class="icon-edit"></i> 编辑</a></div><?php }?>
                        <div class="d-flex justify-content-between">
                            <div>
                                <h5 class="font-weight-bold"><?php echo $tagname;?></h5>
                                <div class="d-block overflow-hidden"><?php echo !empty($query['brief']) ? $query['brief'] : $tagname.'圈子欢迎你~发布内容并打上"'.$tagname.'"标签就能出现在圈子里哦~';?></div>
                            </div>
                        </div>
                    </div>
                </div>                
                <div class="w-100 mt-3">
                    <div class="follow_tag float-right"><a role="button" href="javascript:;" id="follow_tag" onclick="follow_tag('<?php echo $query['tagid'];?>');" class="btn <?php echo !$is_follow ? 'btn-danger' : 'btn-secondary';?> py-0 px-2"><?php echo !$is_follow ? '关注标签' : '已关注';?></a>
                    </div>
                    <div class="text-muted">成员数：<?php echo $query['fans'];?></div>
                </div>
            </div>
        </div>

        <div class="card card-threadlist">
            <div class="card-header d-flex justify-content-between">
                <ul class="nav nav-tabs card-header-tabs">
                    <li class="nav-item">
                        <a class="nav-link active" href="<?php echo url("tag-$tagid");?>">最新</a>
                    </li>
                </ul>
                <div class="text-right text-small pt-1 card-header-dropdown">
                    <div class="btn-toolbar" role="toolbar">
                        <span class="text-muted"><?php echo lang('orderby');?>：</span>
                        <div class="dropdown btn-group">
                            <a href="#" class="dropdown-toggle" id="ordery_dropdown_menu" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <?php echo $orderby == 'tid' ? lang('thread_create_date') : lang('post_create_date');?>
                                <!--{hook forum_thread_list_dropdown_toggle.htm}-->
                            </a>
                            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="ordery_dropdown_menu">
                                <a class="dropdown-item" href="<?php echo url("tag-$tagid-1", array('orderby'=>'tid'));?>"><i class="icon text-primary <?php echo $orderby == 'tid' ? 'icon-check' : '';?>"></i>&nbsp; <?php echo lang('thread_create_date');?></a>
                                <a class="dropdown-item" href="<?php echo url("tag-$tagid-1", array('orderby'=>'lastpid'));?>"><i class="icon text-primary <?php echo $orderby == 'lastpid' ? 'icon-check' : '';?>"></i>&nbsp; <?php echo lang('post_create_date');?></a>
                                <!--{hook forum_thread_list_dropdown_menu.htm}-->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body py-1">
                <ul class="list-unstyled threadlist m-0">
					<?php include _include(APP_PATH.'view/htm/thread_list.inc.htm');?>
                </ul>
            </div>
        </div>
        <?php include _include(APP_PATH.'view/htm/thread_list_mod.inc.htm');?>        
        <nav class="my-3"><ul class="pagination justify-content-center flex-wrap"><?php echo $pagination; ?></ul></nav>
    </div>
    <div class="col-lg-3 d-none d-lg-block aside">        
        <?php $tag_show_new = fox_tag_show_new(20); if(!empty($tag_show_new)){?>
        <div class="card">
          <div class="card-header">
              <ul class="nav nav-tabs card-header-tabs">
                  <li class="nav-item"><a class="nav-link active"><b>最新帖子</b></a></li>
              </ul>
          </div>
          <div class="card-body pt-1 pr-3 pb-2 pl-3">
            <ul class="list-unstyled p-0 m-0 w-100">
                <?php foreach($tag_show_new as $_new){?>
                <li class="text-truncate w-100 border-bottom pt-2 pb-2"><i class="icon-caret-right mr-1"></i> <a href="<?php echo url("thread-$_new[tid]");?>" title="<?php echo $_new['subject'];?>" class="text-dark"><?php echo $_new['subject'];?></a></li>
                <?php }?>
            </ul>
          </div>
        </div><?php }?>
    </div>
</div>
<?php include _include(APP_PATH.'view/htm/footer.inc.htm');?>
<script>$('li[data-active="index"]').addClass('active');$('li[data-active="fid-0"]').addClass('active');$('a[data-active="index"]').addClass('active');
function follow_tag(tagid){
    $.xpost(xn.url('tag-follow'), {'tagid':tagid}, function(code, message){
        if(code == 0){
            $('.follow_tag .btn').removeClass('btn-danger').addClass('btn-secondary').text('已关注');
        }else if(code == 1){
            $('.follow_tag .btn').removeClass('btn-secondary').addClass('btn-danger').text('关注标签');
        }else{
            $.alert(message);        
        }
    });   
    return false;
}
</script>