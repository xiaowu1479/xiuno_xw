
<?php exit;
    $header['title'] = $thread['subject'].' - '.$forum['name'].' - '.$conf['sitename']; 
    $header['keywords'] = !empty($thread['keywords']) ? $thread['keywords'] : ''; 
    $header['description'] = $thread['subject'];
?>
