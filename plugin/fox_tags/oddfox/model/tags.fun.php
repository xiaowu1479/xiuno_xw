<?php
function fox_tag_thread_create($tid, $from_tag){
    if(empty($tid)) return FALSE;
    if(empty($from_tag)) return FALSE;
    $array = fox_tag_filter($from_tag);
    $tag_arr = $array['arr'];
    $tagtotal = $array['total'];

    if(!empty($tagtotal)){
        for($i=0; $i < $tagtotal; $i++){
            $query = db_find_one('fox_tag', array('name' => $tag_arr[$i]));
            if(empty($query)){
                $tagid = db_create('fox_tag', array('name' => $tag_arr[$i]));
                db_create('fox_tag_data', array('tagid' => $tagid,'tid' => $tid));
            }else{
                db_create('fox_tag_data', array('tagid' => $query['tagid'], 'tid' => $tid));
            }
        }
        $keywords = implode(',', $tag_arr);
        thread__update($tid, array('keywords' => $keywords));
        return TRUE;
    }
    return FALSE;
}

function fox_tag_post_update($tid, $from_tag){
    if(empty($tid)) return FALSE;
    if(!empty($from_tag)){
        $array = fox_tag_filter($from_tag, 1);
        $tag_arr = $array['arr'];
        $tagtotal = $array['total'];
        
        fox_tag_delete($tid);
        //更新标签
        if(!empty($tagtotal)){
            for($i=0; $i < $tagtotal; $i++){
                $find_tag = db_find_one('fox_tag', array('name' => $tag_arr[$i]));
                if(empty($find_tag)){
                    $tagid = db_create('fox_tag', array('name' => $tag_arr[$i]));
                    db_create('fox_tag_data', array('tagid' => $tagid, 'tid' => $tid));
                }else{
                    db_create('fox_tag_data', array('tagid' => $find_tag['tagid'], 'tid' => $tid));
                }
            }
            $tag_name_str = implode(',', $tag_arr);
            thread__update($tid, array('keywords' => $tag_name_str));
        }
    }else{
        fox_tag_delete($tid, 1);
    }
}

function fox_tag_find_tagname(&$arr = array()){
    if(!empty($arr)){
        foreach($arr as &$val) {
            $q = db_find_one('fox_tag', array('tagid'=>$val['tagid']));
            if(empty($q['name'])) continue;            
            $val['name'] = $q['name'];
        }
        unset($val);
    }
    return $arr;
}

function fox_tag_index_list($limit){
    $output = cache_get('fox_tag_index_list');
    if(empty($output)){
        global $db;
        $tablepre = $db->tablepre;
        $output = db_sql_find("SELECT *, COUNT(DISTINCT tid) as num FROM `{$tablepre}fox_tag_data` GROUP BY `tagid` ORDER BY COUNT(DISTINCT tid) DESC LIMIT {$limit};");
        fox_tag_find_tagname($output);
        cache_set('fox_tag_index_list', $output, 3600); //有效期1小时
    }
    return $output;
}

function fox_tag_thread($tid, $limit = 10){
    $output = cache_get('thread_tag_'.$tid.'_'.$limit);
    if(empty($output)){
        $output = db_find('fox_tag_data', array('tid'=>$tid), array('tagid'=>+1), 1, $limit);
        fox_tag_find_tagname($output);
        cache_set('thread_tag_'.$tid.'_'.$limit, $output, 3600); //有效期1小时
    }
    return $output;
}

function fox_tag_thread_related($fid, $tid, $keywords, $limit = 10){
    global $db;
    $tablepre = $db->tablepre;
    if(!empty($keywords)){
        $output = cache_get('thread_related_'.$tid.'_'.$limit);
        if(empty($output)){
            $output = array();
            $keywords_arr = array_unique(explode(',',$keywords));
            $where = "WHERE (`tid` != $tid) AND (`keywords` LIKE '%";
            for($i=0;$i<count($keywords_arr);$i++){
                if($i == 0){
                    $where .= $keywords_arr[$i] . "%'";
                }else{
                    $where .= " OR `keywords` LIKE '%" .$keywords_arr[$i] ."%'";
                }
            }
            $where .= ')';
            $output = db_sql_find("SELECT `subject`, `tid`, `create_date` FROM `{$tablepre}thread` $where ORDER BY `tid` DESC LIMIT {$limit};");
            if(empty($output)){
                $where = "WHERE (`tid` != $tid) AND (`subject` LIKE '%";
                for($i=0;$i<count($keywords_arr);$i++){
                    if($i == 0){
                        $where .= $keywords_arr[$i] . "%'";
                    }else{
                        $where .= " OR `subject` LIKE '%" .$keywords_arr[$i] ."%'";
                    }
                }
                $where .= ')';
                $output = db_sql_find("SELECT `subject`, `tid`, `create_date` FROM `{$tablepre}thread` $where ORDER BY `tid` DESC LIMIT {$limit};");
            }
            cache_set('thread_related_'.$tid.'_'.$limit, $output, 3600); //有效期1小时
        }
    }else{
        $output = cache_get('thread_related_'.$tid.'_'.$limit);
        if(empty($output)){
            $output = db_sql_find("SELECT `subject`, `tid`, `create_date` FROM `{$tablepre}thread` WHERE `fid` = {$fid} ORDER BY RAND() limit {$limit};");
            cache_set('thread_related_'.$tid.'_'.$limit, $output, 3600); //有效期1小时
        }
    }
    return $output;
}

function fox_tag_post($tid=0){
    $keywords = '';
    if(!empty($tid)){
        $query = db_find_one('thread', array('tid'=>$tid), array(), array('keywords'));
        !empty($query) AND $keywords = $query['keywords'];
    }
    return $keywords;
}

function fox_tag_show_new($limit = 30){
    global $gid;
    $output = cache_get('tag_show_new');
    if(empty($output)){
        $output = thread_find_by_fid(0, 1, $limit, 'tid');
        thread_list_access_filter($output, $gid); //过滤没有权限访问的主题 //删除缓存cache_delete('tag_show_new');
        cache_set('tag_show_new', $output, 3600); //有效期1小时
    }
    return $output;
}

function fox_tag_delete($tid, $upkw = 0){
    $query = db_find('fox_tag_data', array('tid'=>$tid), array(), 1, 999);
    if(!empty($query)){
        $tagid_arr = arrlist_values($query, 'tagid');            
        db_delete('fox_tag_data', array('tid'=>$tid));
        !empty($upkw) AND thread__update($tid, array('keywords' => ''));
        if(count($tagid_arr)){
            for($j=0; $j < count($tagid_arr); $j++){
                $tagid = $tagid_arr[$j];
                $q = db_find_one('fox_tag_data', array('tagid'=>$tagid));
                if(empty($q)){
                    db_delete('fox_tag', array('tagid'=>$tagid));
                    db_delete('fox_tag_follow', array('tagid'=>$tagid));
                }
            }
        }
    }
}

function fox_tag_follow_count($uid){
    if(!empty($uid)){
        return db_count('fox_tag_follow', array('uid'=>$uid));
    }
}

function fox_tag_handle($str, $fuhao = ' '){
    $arr = array_merge(array_filter(array_unique(explode($fuhao, $str))));
    $total = count($arr);
    if(!empty($total)){
        for($i=0; $i<$total; $i++){
            $arr[$i] = trim($arr[$i]);
            $arr[$i] = str_replace('    ', ',', $arr[$i]);
            $arr[$i] = str_replace('，', ',', $arr[$i]);
            $arr[$i] = str_replace(' ', ',', $arr[$i]);
        }
        return $str = implode(',', $arr);
    }
    return '';    
}

function fox_tag_filter($str, $limit = 0){
    global $fox_tag_kv;
    $output = array();
    $str = fox_tag_handle($str);
    $str = fox_tag_handle($str, ',');
    //$str = str_replace("，", ",", $str);
    //$str = str_replace(" ", ",", $str);
    //$str = str_replace("    ", ",", $str);
    //$str = str_replace("    ", ",", $str);
    $arr = array_unique(explode(',', $str));
    $arr = array_filter($arr);
    $arr = array_merge($arr);
    if(!empty($arr) && $limit){
        foreach($arr as $val){
            if(!empty($fox_tag_kv['tag_lim'])){
                (xn_strlen($val) > $fox_tag_kv['tag_lim']) AND message(-1, '每个标签限制长度'.$fox_tag_kv['tag_lim'].'个字！');
            }
        }
    }
    $total = count($arr);
    if(!empty($fox_tag_kv['tag_max']) && $limit){
        ($total > $fox_tag_kv['tag_max']) AND message(-1, '每个帖字最多限制使用'.$fox_tag_kv['tag_max'].'个标签！');
    }
    $output = array('arr'=>$arr, 'total'=>$total);
    return $output;
}

function fox_tag_words_check($str, &$error){
    global $fox_tag_kv;
    $tag_filter = fox_tag_filter($str);
    if(!empty($tag_filter['arr']) && !empty($tag_filter['total'])){
        $s = implode(',', $tag_filter['arr']);
        $words_check = !empty($fox_tag_kv['tag_words']) ? explode(',', $fox_tag_kv['tag_words']) : array();
        if(!isset($words_check) || !is_array($words_check)){
            return false;
        }
        foreach($words_check as $v){
            if(empty($v)) continue;
            if(strpos(strtolower($s),strtolower($v)) !== false) {
                $error = $v;
                return true;
            }
        }
        return false;
    }
    return false;
}

function fox_tag_str_safe($s) {
    $s = str_replace(array('\'', '\\', '"', '%', '<', '>', '`', '*', '&', '#'), '', $s);
    $s = preg_replace('#\s+#', ' ', $s);
    $s = trim($s);
    return $s;
}

/*
 * 二维数组去掉重复值 array_unique
 */
function fox_tag_array_unique($array){
    $out = array();
    if(is_array($array)){
        foreach($array as $key=>$value){
            if(!in_array($value, $out)){
                $out[$key] = $value;
            }
        }
        $out = array_values($out);
    }
    return $out;
}
?>