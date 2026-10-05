<?php
!defined('DEBUG') AND exit('Access Denied');

// 创建分类表
$table_category = $db->tablepre . 'nav_category';
$sql_category = "CREATE TABLE IF NOT EXISTS $table_category (
    id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(100) NOT NULL DEFAULT '',
    icon VARCHAR(255) DEFAULT '',
    sort_order INT(10) DEFAULT 0,
    status TINYINT(1) DEFAULT 1,
    created INT(10) UNSIGNED DEFAULT 0,
    PRIMARY KEY (id),
    KEY sort_order (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
db_exec($sql_category);

// 创建链接表
$table_link = $db->tablepre . 'nav_link';
$sql_link = "CREATE TABLE IF NOT EXISTS $table_link (
    id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    category_id INT(10) UNSIGNED NOT NULL DEFAULT 0,
    title VARCHAR(100) NOT NULL DEFAULT '',
    url VARCHAR(500) NOT NULL DEFAULT '',
    icon VARCHAR(255) DEFAULT '',
    description VARCHAR(500) DEFAULT '',
    color VARCHAR(20) DEFAULT '',
    target_blank TINYINT(1) DEFAULT 1,
    sort_order INT(10) DEFAULT 0,
    status TINYINT(1) DEFAULT 1,
    clicks INT(10) UNSIGNED DEFAULT 0,
    created INT(10) UNSIGNED DEFAULT 0,
    PRIMARY KEY (id),
    KEY category_id (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
db_exec($sql_link);

// 插入默认分类与链接：仅在全新建库时执行。
// 卸载时并不会删除 nav_category 表（见 uninstall.php），若无条件插入，
// 重新安装就会产生一批重复的默认分类；而且默认链接原来硬编码 category_id=1..4，
// 表里已有数据时会挂到错误的分类下，这里改用 db_insert 返回的真实 ID。
if(!db_count('nav_category') && !db_count('nav_link')) {
    $now = time();

    $cid_common = db_insert('nav_category', array('title' => '常用工具', 'icon' => 'fas fa-fire', 'sort_order' => 1, 'status' => 1, 'created' => $now));
    $cid_dev    = db_insert('nav_category', array('title' => '开发资源', 'icon' => 'fas fa-code', 'sort_order' => 2, 'status' => 1, 'created' => $now));
    $cid_design = db_insert('nav_category', array('title' => '设计工具', 'icon' => 'fas fa-palette', 'sort_order' => 3, 'status' => 1, 'created' => $now));
    $cid_ai     = db_insert('nav_category', array('title' => 'AI工具', 'icon' => 'fas fa-robot', 'sort_order' => 4, 'status' => 1, 'created' => $now));

    db_insert('nav_link', array('category_id' => $cid_common, 'title' => 'GitHub', 'url' => 'https://github.com', 'icon' => 'fab fa-github', 'description' => '代码托管平台', 'sort_order' => 1, 'status' => 1, 'clicks' => 0, 'created' => $now));
    db_insert('nav_link', array('category_id' => $cid_common, 'title' => 'Google', 'url' => 'https://www.google.com', 'icon' => 'fas fa-search', 'description' => '搜索引擎', 'sort_order' => 2, 'status' => 1, 'clicks' => 0, 'created' => $now));
    db_insert('nav_link', array('category_id' => $cid_dev, 'title' => 'VS Code', 'url' => 'https://code.visualstudio.com', 'icon' => 'fas fa-code', 'description' => '代码编辑器', 'sort_order' => 1, 'status' => 1, 'clicks' => 0, 'created' => $now));
    db_insert('nav_link', array('category_id' => $cid_design, 'title' => 'Figma', 'url' => 'https://www.figma.com', 'icon' => 'fab fa-figma', 'description' => '设计工具', 'sort_order' => 1, 'status' => 1, 'clicks' => 0, 'created' => $now));
    db_insert('nav_link', array('category_id' => $cid_ai, 'title' => 'ChatGPT', 'url' => 'https://chat.openai.com', 'icon' => 'fas fa-brain', 'description' => 'AI助手', 'sort_order' => 1, 'status' => 1, 'clicks' => 0, 'created' => $now));
}

// 清除缓存（model.inc.php 里含插件模型列表，新增模型文件必须一起清掉才生效）
@xn_unlink($conf['tmp_path'] . 'model.inc.php');
@xn_unlink($conf['tmp_path'] . 'model.min.php');
@xn_unlink($conf['tmp_path'] . 'xw_navigation_icon255');
@xn_unlink($conf['tmp_path'] . 'plugin_xw_navigation_model_NavIcon.php');
@xn_unlink($conf['tmp_path'] . 'plugin_xw_navigation_model_NavigationService.php');
@xn_unlink($conf['tmp_path'] . 'plugin_xw_navigation_setting.php');
@xn_unlink($conf['tmp_path'] . 'plugin_xw_navigation_view_htm_admin.htm');
@xn_unlink($conf['tmp_path'] . 'plugin_xw_navigation_view_htm_navigation.htm');
@xn_unlink($conf['tmp_path'] . 'plugin_xw_navigation_route_index.php');
