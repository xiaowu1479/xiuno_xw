<?php
!defined('DEBUG') AND exit('Forbidden');
$tablepre = $db->tablepre;

!db_sql_find_one("SHOW COLUMNS FROM {$tablepre}post LIKE 'OK'") AND db_exec("ALTER TABLE {$tablepre}post ADD COLUMN OK INT(3) DEFAULT '1'");
!db_sql_find_one("SHOW COLUMNS FROM {$tablepre}thread LIKE 'OK'") AND db_exec("ALTER TABLE {$tablepre}thread ADD COLUMN OK INT(3) DEFAULT '1'");
!db_sql_find_one("SHOW COLUMNS FROM {$tablepre}user LIKE 'OK'") AND db_exec("ALTER TABLE {$tablepre}user ADD COLUMN OK INT(3) DEFAULT '1'");
!db_sql_find_one("SHOW COLUMNS FROM {$tablepre}group LIKE 'see_check'") AND db_exec("ALTER TABLE {$tablepre}group ADD COLUMN see_check INT(3) DEFAULT '0'");
!db_sql_find_one("SHOW COLUMNS FROM {$tablepre}group LIKE 'post_check'") AND db_exec("ALTER TABLE {$tablepre}group ADD COLUMN post_check INT(3) DEFAULT '0'");
!db_sql_find_one("SHOW COLUMNS FROM {$tablepre}group LIKE 'thread_check'") AND db_exec("ALTER TABLE {$tablepre}group ADD COLUMN thread_check INT(3) DEFAULT '0'");
!db_sql_find_one("SHOW COLUMNS FROM {$tablepre}group LIKE 'edit_check'") AND db_exec("ALTER TABLE {$tablepre}group ADD COLUMN edit_check INT(3) DEFAULT '0'");
!db_sql_find_one("SHOW COLUMNS FROM {$tablepre}forum LIKE 'post_check'") AND db_exec("ALTER TABLE {$tablepre}forum ADD COLUMN post_check INT(3) DEFAULT '0'");
!db_sql_find_one("SHOW COLUMNS FROM {$tablepre}forum LIKE 'thread_check'") AND db_exec("ALTER TABLE {$tablepre}forum ADD COLUMN thread_check INT(3) DEFAULT '0'");
!db_sql_find_one("SHOW COLUMNS FROM {$tablepre}forum LIKE 'edit_check'") AND db_exec("ALTER TABLE {$tablepre}forum ADD COLUMN edit_check INT(3) DEFAULT '0'");
forum_list_cache_delete();
group_list_cache_delete();
setting_set('tt_check',array('user_check'=>0,'recycle'=>0));
?>