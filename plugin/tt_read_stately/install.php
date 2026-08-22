<?php
!defined('DEBUG') AND exit('Forbidden');
$tablepre = $db->tablepre;
!db_sql_find_one("SHOW COLUMNS FROM {$tablepre}group LIKE 'readp'") AND db_exec("ALTER TABLE {$tablepre}group ADD readp int(5) NOT NULL default '1'");
!db_sql_find_one("SHOW COLUMNS FROM {$tablepre}thread LIKE 'readp'") AND db_exec("ALTER TABLE {$tablepre}thread ADD readp int(5) NOT NULL default '0'");
!db_sql_find_one("SHOW COLUMNS FROM {$tablepre}group LIKE 'allowPostRead'") AND db_exec("ALTER TABLE {$tablepre}group ADD allowPostRead int(5) NOT NULL default '1'");
group_list_cache_delete();
setting_set('tt_read',array('old'=>'0'));
?>