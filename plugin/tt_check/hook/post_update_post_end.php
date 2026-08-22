<?php exit;
global $group;
if($isfirst) {
    $new_fid = param('fid');
    $check_result = check_or_not($group, $new_fid, 'thread');
    if($check_result)check_set_thread_check($tid,'0');
} else {
    $check_result = check_or_not($group, $fid, 'thread');
    if($check_result)check_set_post_check($pid,'0');
}
?>