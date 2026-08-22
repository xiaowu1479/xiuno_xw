
<?php exit;
    if($post['user']['gid'] == 7){
        $post['message_fmt_fox'] = '<div class="aframe alert-master" role="alert"><i class="icon-unlock-alt"></i> <b>该用户被关禁闭，内容被隐藏。</b><hr class="my-2"><span>'.$post['message_fmt'].'</span></div>';
        $post['message_fmt'] = '<div class="aframe alert-guest " role="alert"><i class="icon-lock"></i> <span>该用户被关禁闭，内容被隐藏。</span></div>';
    }
?>
