$conf_email = include "plugin/lee_not_email/conf.php";
$arr = explode('@', $email);
if($conf_email['b_or_w'] == 'b'){ 
    strpos($conf_email['black_list'], $arr[1]) !== false AND message('email',$conf_email['black_tips']);
}elseif($conf_email['b_or_w'] == 'w'){
    strpos($conf_email['white_list'], $arr[1]) === false AND message('email',$conf_email['white_tips']);
}