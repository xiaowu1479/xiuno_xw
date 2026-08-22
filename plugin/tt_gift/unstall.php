<?php
!defined('DEBUG') AND exit('Forbidden');
$tablepre = $db->tablepre;
db_exec("DROP TABLE IF EXISTS {$tablepre}gift");