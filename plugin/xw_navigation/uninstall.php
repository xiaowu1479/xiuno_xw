<?php
!defined('DEBUG') AND exit('Access Denied');

// 删除数据表（保持原有行为：历史版本未删除分类表，这里不改变）
// table_drop('nav_category');
table_drop('nav_link');

// 删除设置
setting_set('xw_navigation', NULL);

// 清除缓存与图标抓取标记
@xn_unlink($conf['tmp_path'] . 'model.inc.php');
@xn_unlink($conf['tmp_path'] . 'model.min.php');
@xn_unlink($conf['tmp_path'] . 'xw_navigation_icon255');
@xn_unlink($conf['tmp_path'] . 'plugin_xw_navigation_model_NavIcon.php');
@xn_unlink($conf['tmp_path'] . 'plugin_xw_navigation_model_NavigationService.php');
@xn_unlink($conf['tmp_path'] . 'plugin_xw_navigation_setting.php');
@xn_unlink($conf['tmp_path'] . 'plugin_xw_navigation_view_htm_admin.htm');
@xn_unlink($conf['tmp_path'] . 'plugin_xw_navigation_view_htm_navigation.htm');
@xn_unlink($conf['tmp_path'] . 'plugin_xw_navigation_route_index.php');
