
<?php exit;
    if($old['gid'] == 7 && ($_gid != 7)){
        fox_prison_update($_uid, $_gid);
    }
    if($_gid == 7 && ($old['gid'] > 7)){
        $message = param('message');
        db_create('fox_prison', array('uid'=>$_uid, 'aid'=>$uid, 'time'=>$time, 'endtime'=>strtotime("+15 year", $time), 'uip'=>$longip, 'message'=>$message));
    }
?>
