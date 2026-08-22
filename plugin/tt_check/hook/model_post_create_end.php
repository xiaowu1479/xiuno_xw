<?php exit;
global $group;
$check_result = check_or_not($group,$fid,'post');
if($check_result)check_set_post_check($pid,'0');
?>