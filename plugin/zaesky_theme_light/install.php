<?php

/*
	Xiuno BBS 4.0 xiuno L
*/

!defined('DEBUG') AND exit('Forbidden');
define('ROOT', APP_PATH.'plugin/zaesky_theme_light/model/');
include(ROOT . 'install.php');

$installer = new ThemeLightInstaller($db);
ThemeLightInstaller::install();
?>