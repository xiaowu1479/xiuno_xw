<?php !defined('DEBUG') AND exit('Access Denied.');include _include(APP_PATH.'view/htm/header.inc.htm');?>
<div class="row mb-3">
    <div class="col-lg-12 main">
        <div class="card card-threadlist mb-0">
            <div class="card-header d-flex justify-content-between">
                <ul class="nav nav-tabs card-header-tabs">
                    <li class="nav-item">
                        <a class="nav-link active" href="<?php echo url("tag-list");?>"><i class="icon-tags"></i> 标签列表</a>
                    </li>
                </ul>
            </div>
            <div class="card-body px-3 pt-3 pb-0 tagslist">
                <ul><?php foreach($fox_tag_list as $val){?>
                    <li><a class="name" href="<?php echo url("tag-{$val['tagid']}");?>"><?php echo $val['name'];?></a><small>×<?php echo $val['num'];?></small><p><a class="tit" href="<?php echo url("thread-{$val['tid']}");?>"><?php echo $val['subject'];?></a></p></li>
            <?php }?>
                </ul>
            </div><!-- card-body -->
        <?php if($pagination){?>
            <nav class="my-0"><ul class="pagination justify-content-center flex-wrap"><?php echo $pagination; ?></ul></nav>            
        <?php }?>
        </div>        
    </div>
</div>
<?php include _include(APP_PATH.'view/htm/footer.inc.htm');?>
<script>$('li[data-active="index"]').addClass('active');$('li[data-active="fid-0"]').addClass('active');$('a[data-active="index"]').addClass('active');</script>