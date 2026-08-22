<?php exit;
global $group;
$check_result = check_or_not($group,$fid,'thread');
if($check_result)check_set_thread_check($tid,'0');
?>