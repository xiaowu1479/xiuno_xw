<?php
!defined('DEBUG') AND exit('Access Denied.');
$action = param(3);
if(empty($action)){
    if($method == 'GET')
        include _include(APP_PATH.'plugin/tt_check/setting.htm');
    elseif($method=='POST'){
        $op=param('op');
        if($op=='0') {
            setting_set('tt_check', array('user_check' => param('cREG', '0'), 'recycle' => param('cRECYCLE', '0')));
            $tablepre = $db->tablepre;
            $update_lists = param('gTHREAD', array(0));
            $sql = 'UPDATE ' . $tablepre . 'group SET thread_check="0";';
            foreach ($update_lists as $k => $v)
                $sql .= 'UPDATE ' . $tablepre . 'group SET thread_check="1" WHERE gid="' . $k . '";';
            db_exec($sql);
            $update_lists = param('gPOST', array(0));
            $sql = 'UPDATE ' . $tablepre . 'group SET post_check="0";';
            foreach ($update_lists as $k => $v)
                $sql .= 'UPDATE ' . $tablepre . 'group SET post_check="1" WHERE gid="' . $k . '";';
            db_exec($sql);
            $update_lists = param('gSEE', array(0));
            $sql = 'UPDATE ' . $tablepre . 'group SET see_check="0";';
            foreach ($update_lists as $k => $v)
                $sql .= 'UPDATE ' . $tablepre . 'group SET see_check="1" WHERE gid="' . $k . '";';
            db_exec($sql);
            $update_lists = param('gEDIT', array(0));
            $sql = 'UPDATE ' . $tablepre . 'group SET edit_check="0";';
            foreach ($update_lists as $k => $v)
                $sql .= 'UPDATE ' . $tablepre . 'group SET edit_check="1" WHERE gid="' . $k . '";';
            db_exec($sql);
            $update_lists = param('fTHREAD', array(0));
            $sql = 'UPDATE ' . $tablepre . 'forum SET thread_check="0";';
            foreach ($update_lists as $k => $v)
                $sql .= 'UPDATE ' . $tablepre . 'forum SET thread_check="1" WHERE fid="' . $k . '";';
            db_exec($sql);
            $update_lists = param('fPOST', array(0));
            $sql = 'UPDATE ' . $tablepre . 'forum SET post_check="0";';
            foreach ($update_lists as $k => $v)
                $sql .= 'UPDATE ' . $tablepre . 'forum SET post_check="1" WHERE fid="' . $k . '";';
            db_exec($sql);
            $update_lists = param('fEDIT', array(0));
            $sql = 'UPDATE ' . $tablepre . 'forum SET edit_check="0";';
            foreach ($update_lists as $k => $v)
                $sql .= 'UPDATE ' . $tablepre . 'forum SET edit_check="1" WHERE fid="' . $k . '";';
            db_exec($sql);
            forum_list_cache_delete();
            group_list_cache_delete();
            message(0, '设置成功！');
        }elseif($op=='1'){
            recycle_truncate();
            message(0, '清空成功！');
        }
    }
}

?>