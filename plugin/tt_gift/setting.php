<?php !defined('DEBUG') AND exit('Access Denied.');
$action = param(3);
function getrandom($length)
{
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890@*#";
    return substr(str_shuffle($chars),0,$length);
}
if(empty($action)){
    if($method == 'GET')
        include _include(APP_PATH.'plugin/tt_gift/setting.htm');
    else if($method=="POST") {
        $op = param('op');
        if($op==0) {
            $zid=param('zid'); $opt = param('opt');if(empty($zid)){message(-1, 'ERROR');die();}
            if($opt==1)
                db_delete('gift',array('zid'=>$zid));
            elseif($opt==-1)
                db_update('gift',array('zid'=>$zid),array('status'=>'0','time_to'=>'0','uid'=>'0'));
            elseif($opt==-2)
                db_update('gift',array('zid'=>$zid),array('status'=>'1','time_to'=>time(),'uid'=>'1'));
            message(0, '设置成功！');
        } elseif($op==1) {
            $num=param('num');$credits=param('credits');$golds=param('golds');$rmbs=param('rmbs');$tids=param('tids');
            $need_update = array();
            if(!empty($credits)) $need_update['credits']=$credits;
            if(!empty($golds)) $need_update['golds']=$golds;
            if(!empty($rmbs)) $need_update['rmbs']=$rmbs;
            if(!empty($tids)) $need_update['tids']=$tids;
            if(empty($num)||empty($need_update)){ message(-1, '没有要生成的数据！');die();}
            $need_update['time_from'] = time();
            for($i=0;$i<$num;$i++) {
                $need_update['card_id']=getrandom(36);
                db_insert('gift',$need_update);
            }
            message(0, '生成成功！');
        }elseif($op==2){
            db_delete('gift',array('status'=>1));
            message(0, '清理成功！');
        }elseif($op==3){
            $input = param('card');
            $r = db_find_one('gift',array('card_id'=>$input));
            if($r){
                $rtn = '查询结果 - '.$input.'<br><br>';
                $rtn.='生成时间:'.date('Y-m-d H:i:s',$r['time_from']).'<br>';
                $rtn.='使用时间:'.($r['time_to']==0?'-':date('Y-m-d H:i:s',$r['time_to'])).'<br>';
                $rtn.='内含经验:'.$r['credits'].'<br>';
                $rtn.='内含金币:'.$r['golds'].'<br>';
                $rtn.='内含人民币:'.$r['rmbs']/100.0.'<br>';
                $rtn.='内含主题:'.$r['tids'].'<br>';
                $rtn.='当前状态:'.$g_credits_status_array[$r['status']].'<br>';
                message(0,$rtn);
            }
            else message(-1,'卡号不存在!');
        }
    }
}

?>