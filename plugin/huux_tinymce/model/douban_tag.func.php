<?php

!defined('DEBUG') AND exit('Access Denied.');

function huux_douban_table_exists($table)
{
    static $exists = array();
    global $db;
    if(isset($exists[$table])) return $exists[$table];
    if(empty($db) || empty($db->tablepre)) return $exists[$table] = FALSE;
    $table = preg_replace('/[^\w]/', '', $table);
    $fulltable = $db->tablepre.$table;
    $row = db_sql_find_one("SHOW TABLES LIKE '".addslashes($fulltable)."'");
    return $exists[$table] = !empty($row);
}

function huux_douban_clean_tag($tag)
{
    $tag = html_entity_decode((string)$tag, ENT_QUOTES, 'UTF-8');
    $tag = trim(preg_replace('/\s+/u', ' ', $tag));
    if($tag === '' || mb_strlen($tag, 'UTF-8') > 32) return '';
    return $tag;
}

function huux_douban_request_tags()
{
    $raw = param('huux_douban_tags', '', FALSE);
    if($raw === '') return array();
    $tags = array();
    foreach(preg_split('/[\s\/,，、]+/u', $raw) as $tag) {
        $tag = huux_douban_clean_tag($tag);
        if($tag !== '' && !in_array($tag, $tags, TRUE)) {
            $tags[] = $tag;
        }
        if(count($tags) >= 12) break;
    }
    return $tags;
}

function huux_douban_request_subject($fallback = '')
{
    $subject = trim(html_entity_decode(param('huux_douban_subject', '', FALSE), ENT_QUOTES, 'UTF-8'));
    $subject = preg_replace('/\s+/u', ' ', $subject);
    return $subject !== '' ? $subject : $fallback;
}

function huux_douban_ensure_tag($name)
{
    global $time;
    $name = huux_douban_clean_tag($name);
    if($name === '' || !huux_douban_table_exists('tag')) return 0;
    $tag = db_find_one('tag', array('name'=>$name));
    if($tag && !empty($tag['tagid'])) return intval($tag['tagid']);
    $arr = array(
        'cateid'=>0,
        'name'=>$name,
        'rank'=>0,
        'enable'=>1,
        'style'=>'secondary',
    );
    foreach(array_keys($arr) as $column) {
        if(!huux_douban_table_has_column('tag', $column)) unset($arr[$column]);
    }
    if(empty($arr['name'])) return 0;
    $tagid = db_create('tag', $arr);
    setting_set('tag_update_time', $time);
    return $tagid ? intval($tagid) : 0;
}

function huux_douban_thread_tagids($tid)
{
    if(!$tid || !huux_douban_table_exists('tag_thread')) return array();
    $rows = db_find('tag_thread', array('tid'=>$tid), array(), 1, 1000);
    return arrlist_values($rows, 'tagid');
}

function huux_douban_sync_thread_tags($tid, array $tags)
{
    global $time;
    if(!$tid || !$tags || !huux_douban_table_exists('tag') || !huux_douban_table_exists('tag_thread')) return;
    $tagids = array();
    foreach($tags as $tag) {
        $tagid = huux_douban_ensure_tag($tag);
        $tagid AND $tagids[] = $tagid;
    }
    $tagids = array_values(array_unique($tagids));
    if(!$tagids) return;

    $oldids = huux_douban_thread_tagids($tid);
    foreach(array_diff($oldids, $tagids) as $tagid) {
        db_delete('tag_thread', array('tagid'=>$tagid, 'tid'=>$tid));
    }
    foreach(array_diff($tagids, $oldids) as $tagid) {
        db_create('tag_thread', array('tagid'=>$tagid, 'tid'=>$tid));
    }
    $update = array();
    if(huux_douban_table_has_column('thread', 'tagids')) {
        $update['tagids'] = implode(',', $tagids);
    }
    if(huux_douban_table_has_column('thread', 'tagids_time')) {
        $update['tagids_time'] = $time;
    }
    $update AND thread_update($tid, $update);
    setting_set('tag_update_time', $time);
}

function huux_douban_table_has_column($table, $column)
{
    static $columns = array();
    global $db;
    $table = preg_replace('/[^\w]/', '', $table);
    if(!isset($columns[$table])) {
        $columns[$table] = array();
        if(!empty($db) && huux_douban_table_exists($table)) {
            $rows = db_sql_find("SHOW COLUMNS FROM `".$db->tablepre.$table."`");
            foreach((array)$rows as $row) {
                if(isset($row['Field'])) $columns[$table][$row['Field']] = TRUE;
            }
        }
    }
    return isset($columns[$table][$column]);
}

?>
