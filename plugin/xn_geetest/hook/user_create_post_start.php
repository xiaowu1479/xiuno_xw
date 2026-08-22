include_once APP_PATH.'plugin/xn_geetest/model/geetest.fuc.php';
$geetest_mail_status = (array)kv_get('geetest');
if (empty($geetest_mail_status['geetest_mail_on'])){
geetestcheck('geetest_user_create_on');
}