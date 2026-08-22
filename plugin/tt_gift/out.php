<?php !defined('DEBUG') AND exit('Access Denied.');
$type = param('type');
$type_s = param('s');
if (isset($type)) {
    if($type_s=='all')
        $data = db_find('gift', array(), array('zid' => -1),1,10000);
    else
        $data = db_find('gift', array('status'=>'0'), array('zid' => -1),1,10000);
    header('Content-Type: application/download');
    header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
    header('Expires:0');
    header('Pragma:public');
    if ($type == 'csv') {
        header("Content-type:text/csv;");
        header("Content-Disposition:attachment;filename=gift_expert.csv");
        foreach ($data as $k => $v__) {
            foreach ($v__ as $k => $v___)
                echo $k, ',';
            echo "\r\n"; break;
        }
        foreach ($data as $v) {
            foreach($v as $v_)
                echo $v_,',';
            echo "\r\n";
        }
    } elseif ($type == 'txt') {
        header("Content-type:text/txt;");
        header("Content-Disposition:attachment;filename=gift_expert.txt" );
        foreach ($data as $v)
        echo($v['card_id']), "\r\n";
    }
}
?>