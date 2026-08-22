<?php
/*
 * 奇狐插件 配置文件
 * QQ:77798085
 */

!defined('DEBUG') AND exit('Access Denied.');
plugin_info($plugins, $dir);

if($method == 'GET'){
    $input = array();
    //unset($grouplist[0], $grouplist[6], $grouplist[7]);
    $input['tag_index'] = form_text('tag_index', !empty($fox_tag_kv['tag_index']) ? $fox_tag_kv['tag_index'] : 20, 200);
    $input['tag_list'] = form_text('tag_list', !empty($fox_tag_kv['tag_list']) ? $fox_tag_kv['tag_list'] : 100, 200, 'aaaaaaaa');
    $input['tag_pos'] = form_radio('tag_pos', array(0=>'主体', 1=>'侧边'), !empty($fox_tag_kv['tag_pos']) ? $fox_tag_kv['tag_pos'] : 0);
    $input['tag_inc'] = form_radio_yes_no('tag_inc', !empty($fox_tag_kv['tag_inc']) ? $fox_tag_kv['tag_inc'] : 0);    
    $input['tag_top'] = form_radio_yes_no('tag_top', !empty($fox_tag_kv['tag_top']) ? $fox_tag_kv['tag_top'] : 0);
    $input['tag_end'] = form_radio_yes_no('tag_end', !empty($fox_tag_kv['tag_end']) ? $fox_tag_kv['tag_end'] : 0);
    $input['tag_two'] = form_radio('tag_two', array(0=>'关闭', 1=>'一条', 2=>'两条'), !empty($fox_tag_kv['tag_two']) ? $fox_tag_kv['tag_two'] : 0);
    $input['tag_retain'] = form_radio('tag_retain', array(1=>'清空数据', 0=>'保留数据'), !empty($fox_tag_kv['tag_retain']) ? $fox_tag_kv['tag_retain'] : 0);
    $input['tag_max'] = form_text('tag_max', !empty($fox_tag_kv['tag_max']) ? $fox_tag_kv['tag_max'] : 5, 200);
    $input['tag_lim'] = form_text('tag_lim', !empty($fox_tag_kv['tag_lim']) ? $fox_tag_kv['tag_lim'] : 4, 200, '每个中英文都算一个');
    $input['tag_words'] = form_textarea('tag_words', !empty($fox_tag_kv['tag_words']) ? $fox_tag_kv['tag_words'] : '', '100%', 100);
    include _include(APP_PATH.'plugin/fox_tags/oddfox/template/fox_setting.php');
}elseif($method == 'POST'){
    
    $input = array();
    $input['tag_index'] = param('tag_index', 0);
    $input['tag_list'] = param('tag_list', 0);
    $input['tag_pos'] = param('tag_pos', 0);
    $input['tag_inc'] = param('tag_inc', 0);    
    $input['tag_top'] = param('tag_top', 0);
    $input['tag_end'] = param('tag_end', 0);    
    $input['tag_two'] = param('tag_two', 0);
    $input['tag_max'] = param('tag_max', 0);
    $input['tag_lim'] = param('tag_lim', 0);
    $input['tag_words'] = trim(param('tag_words'));
    $input['tag_retain'] = param('tag_retain', 0);
    kv_cache_set('fox_tag', $input);

    $allowtags_arr = param('allowtags', array(0));
    $allowtags_arr2 = param('allowtags2', array(0));
    $allowtags_arr3 = param('allowtags3', array(0));
    db_update('group', array(), array('allowtags'=>0, 'allowtags2'=>0, 'allowtags3'=>0));
    !empty($allowtags_arr) AND group__update($allowtags_arr, array('allowtags'=>1));
    !empty($allowtags_arr2) AND group__update($allowtags_arr2, array('allowtags2'=>1));
    !empty($allowtags_arr3) AND group__update($allowtags_arr3, array('allowtags3'=>1));
    group_list_cache_delete();
    
    message(0, lang('save_successfully'));
}else {
    message(-1, 'Access Denied.');
}
function plugin_info($plugins, $dir){
    $error1 = '请先<a href="'.url("plugin-enable-{$dir}").'" class="text-danger">开启'.$plugins[$dir]['name'].'</a>，您已将该插件禁用。';
    $error2 = '请先<a href="'.url("plugin-install-{$dir}").'" class="text-danger">安装'.$plugins[$dir]['name'].'</a>，您已将该插件卸载。';
    empty($plugins[$dir]['enable']) AND message(-1, $error1);
    empty($plugins[$dir]['installed']) AND message(-1, $error2);
}
?>