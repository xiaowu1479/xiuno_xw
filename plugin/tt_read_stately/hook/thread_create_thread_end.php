$pnumber = param('readp');$pstatus=param('readp_status');
if ($pstatus&& $pnumber>0)
    {db_update('thread', array('tid' => $tid), array('readp' => $pnumber));}