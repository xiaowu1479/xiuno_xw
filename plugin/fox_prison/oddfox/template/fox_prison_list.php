<?php !defined('DEBUG') AND exit('Access Denied.');include _include(APP_PATH.'view/htm/header.inc.htm');?>
<style>.lists .media-body{position:relative;}.lists .name{font-size:14px;line-height:16px;}.lists .badge-box{position:absolute;top:28px;left:0;}.lists .brief-box{height:24px;overflow:hidden;text-overflow:ellipsis;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;}</style>
<div class="row">
    <div class="col-lg-12" id="main">
        <div class="card mb-0">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs">
                    <li class="nav-item"><a class="nav-link <?php if($status){?>active<?php }?>" data-active="prison" href="<?php echo url("prison");?>"><b>禁闭名单</b></a></li>
                    <li class="nav-item"><a class="nav-link <?php if(!$status){?>active<?php }?>" data-active="prison" href="<?php echo url("prison-0");?>"><b>释放记录</b></a></li>
                </ul>                
            </div>
            <div class="card-body">
                <div class="row lists pt-0 px-2">
                <?php if(!empty($list)){foreach($list as $value){?>
                    <div class="col-sm-12 col-md-6 col-lg-4 px-2">
                        <div class="card border">
                            <div class="card-body">
                                <div class="media">
                                    <a href="<?php echo url("user-{$value['uid']}");?>" target="_blank"><img class="avatar-4 rounded" src="<?php echo $value['user']['avatar_url'];?>" /></a>
                                    <div class="media-body ml-3">
                                        <a href="<?php echo url("user-{$value['uid']}");?>" target="_blank" class="name"><?php echo $value['user']['username'];?></a>
                                        <div class="badge-box">
                                            <div class="text-gray">禁闭时间：<span><?php echo date('Y-m-d', $value['time']);?></span></div>
                                            <div class="text-gray">释放时间：<span><?php echo date('Y-m-d', $value['endtime']);?></span></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="brief-box text-muted mt-3">
                                <?php if($gid == 1){if($status){?><input type="checkbox" name="moduid" class="mr-1" value="<?php echo $value['uid'];?>" /><?php }}?>
                                <i class="icon-bell icon-fw"></i> 禁闭原因：<?php echo strip_tags($value['message']);?></div>
                            </div>
                        </div>
                    </div>
                <?php }}else{?>
                    无
                <?php }?>
                </div>
                <?php if(!empty($list)){if($gid == 1){if($status){?>
                <div class="text-center mb-3">
                    <input type="checkbox" data-target='input[name="moduid"]' class="checkall d-none" aria-label="全选" />
                    <div class="btn-group mod-button" role="group">
                    <button class="btn btn-secondary" id="checkall">全选</button>
                    <button class="btn btn-secondary" data-modal-url="<?php echo url("mod-openuser-all");?>" data-modal-title="确定释放？" data-modal-arg=".lists" data-modal-size="md">释放</button>
                    <button class="btn btn-secondary" data-modal-url="<?php echo url("mod-delusers");?>" data-modal-title="确定枪毙？" data-modal-arg=".lists" data-modal-size="md">枪毙</button>
                    </div>
                </div>
                <?php }}}?>
                <?php if($pagination){?><nav class="text-center"><ul class="pagination justify-content-center mb-0 mx-2"><?php echo $pagination;?></ul></nav><?php }?>
            </div>
        </div>
    </div>
</div>
<?php include _include(APP_PATH.'view/htm/footer.inc.htm');?>
<script>$('#checkall').click(function(){$("input[type='checkbox']").trigger("click");});</script>