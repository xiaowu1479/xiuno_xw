<?php
/*
 * 奇狐插件 配置文件
 * QQ:77798085
 */

!defined('DEBUG') AND exit('Access Denied.');
$error1 = '请先<a href="'.url("plugin-install-fox_shang").'" class="text-danger">安装'.$plugins['fox_shang']['name'].'</a>，您已将该插件卸载。';
$error2 = '请先<a href="'.url("plugin-enable-fox_shang").'" class="text-danger">开启'.$plugins['fox_shang']['name'].'</a>，您已将该插件禁用。';
empty($plugins['fox_shang']['installed']) AND message(-1, $error1);
empty($plugins['fox_shang']['enable']) AND message(-1, $error2);

if($method == 'GET'){
    $input = array();
    $fox_shang_data = kv_cache_get('fox_shang');
    $input['fox_shang_al'] = form_text('fox_shang_al', !empty($fox_shang_data['fox_shang_al']) ? $fox_shang_data['fox_shang_al'] : '');
    $input['fox_shang_wx'] = form_text('fox_shang_wx', !empty($fox_shang_data['fox_shang_wx']) ? $fox_shang_data['fox_shang_wx'] : '');
    include _include(APP_PATH.'plugin/fox_shang/oddfox/theme/fox_setting.php');    
}
elseif($method=='POST'){
    $post_data = array();
    $post_data['fox_shang_al'] = param('fox_shang_al', '', FALSE);
    $post_data['fox_shang_wx'] = param('fox_shang_wx', '', FALSE);
    kv_cache_set('fox_shang', $post_data);
    message(0, lang('save_successfully'));
}else{
    exit('Access Denied.');
}    
?>