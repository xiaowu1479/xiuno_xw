<?php exit;
$pnumber = param('readp'); $pstatus=param('readp_status');
if ($pstatus && $pnumber>=0)
    db_update('thread', array('tid' => $tid), array('readp' => $pnumber));
else
    db_update('thread', array('tid' => $tid), array('readp' => 0));
?>