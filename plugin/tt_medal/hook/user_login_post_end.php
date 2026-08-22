// 登陆成功后执行，删除过期的蚀刻
$u_medal = db_find('user_medal',array(),array(),1,1000);
foreach($u_medal as $u){
    if($u['validity']<time()){
        db_delete('user_medal',array('uid'=>$u['uid'],'mid'=>$u['mid']));
        db_update('medal',array('mid'=>$u['mid']),array('have_count-'=>1));
    }
}