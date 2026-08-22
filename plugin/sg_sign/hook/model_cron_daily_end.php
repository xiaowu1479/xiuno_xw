$sg_sign_set = db_find_one('sg_sign_set', array('id'=>1));
$t = $time - $sg_sign_set['time'];
if($t > 86400){
db_update('sg_sign_set', array('id'=>1), array('sg_sign_top'=>'','sg_sign_one'=>'','sg_sign'=>0));
}