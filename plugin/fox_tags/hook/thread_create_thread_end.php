
        <?php exit;
        if(!empty($tid)){
            $from_tag = param('fox_tag', '');
            !empty($from_tag) AND fox_tag_thread_create($tid, $from_tag);
        }?>
