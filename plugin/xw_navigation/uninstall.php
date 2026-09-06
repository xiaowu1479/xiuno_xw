<?php
!defined('DEBUG') AND exit('Access Denied');

// 删除数据表
table_drop('nav_category');
table_drop('nav_link');

// 删除设置
setting_set('xw_navigation', NULL);

// 清除缓存
@xn_unlink($conf['tmp_path'] . 'model.min.php');
