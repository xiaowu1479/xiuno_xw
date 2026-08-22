<?php
/*
 * 奇狐插件 安装文件
 * QQ:77798085
 */

!defined('DEBUG') AND exit('Forbidden');

$get_data = kv_get('fox_shang');
if(empty($get_data)){
    $post_data = array(
        'fox_shang_al' => 'plugin/fox_shang/oddfox/img/al_reward.png',
        'fox_shang_wx' => 'plugin/fox_shang/oddfox/img/wx_reward.png',
    );
    kv_cache_set('fox_shang', $post_data);
}?>