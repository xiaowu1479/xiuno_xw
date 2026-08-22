
<?php 
!defined('DEBUG') AND exit('Access Denied.'); 
    if($_post['user']['gid'] == 7){
        if($user['gid'] == 1){
            $_post['message_fmt'] = $_post['message_fmt_fox'];
        }
    }
?>
