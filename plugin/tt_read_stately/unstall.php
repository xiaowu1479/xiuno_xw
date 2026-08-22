<?php
!defined('DEBUG') AND exit('Forbidden');
$tablepre = $db->tablepre;
db_exec("ALTER TABLE {$tablepre}group DROP COLUMN readp");
db_exec("ALTER TABLE {$tablepre}thread DROP COLUMN readp");
db_exec("ALTER TABLE {$tablepre}group DROP COLUMN allowPostRead");
setting_delete('tt_read');