
<?php exit;
    $from_tag = param('fox_tag', '');
    !empty($from_tag) AND fox_tag_filter($from_tag, 1);
    !empty($from_tag) AND fox_tag_words_check($from_tag, $qt_error) AND message('fox_tag', '标签含有敏感词：' . $qt_error);
?>
