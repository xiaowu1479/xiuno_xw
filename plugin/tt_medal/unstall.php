<?php
!defined('DEBUG') AND exit('Forbidden');
$tablepre = $db->tablepre;
db_exec("DROP TABLE IF EXISTS {$tablepre}medal");
db_exec("DROP TABLE IF EXISTS {$tablepre}user_medal");
db_exec("DROP TABLE IF EXISTS {$tablepre}medal_check");
setting_delete('tt_medal');