
        <?php exit;
        if($isfirst && $tid){
            $from_tag = param('fox_tag', '');
            fox_tag_post_update($tid, $from_tag);
            cache_delete("thread_tag_{$tid}_10");
            cache_delete("thread_related_{$tid}_10");
            !empty($from_tag) AND fox_tag_words_check($from_tag, $qt_error) AND message('fox_tag', '标签含有敏感词：' . $qt_error);
        }?>
