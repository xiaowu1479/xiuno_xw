<?php

if(isset($light_config['disc_page']) && $light_config['disc_page'] && $light_config['disc_page_index']) {

	// hook bbs_include_index_htm_before.php
	include _include(APP_PATH.'plugin/zaesky_theme_light/view/htm/discovery.htm');
} else {
	
	// hook bbs_include_index_php_before.php
	include _include(APP_PATH.'route/index.php');
}

?>