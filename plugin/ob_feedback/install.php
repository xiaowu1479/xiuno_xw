<?php
$tablepre = $db->tablepre;


$sql = "CREATE TABLE IF NOT EXISTS {$tablepre}ob_feedback (
    fb_id INT UNSIGNED AUTO_INCREMENT,
    from_uid INT UNSIGNED NOT NULL,
    to_uid INT UNSIGNED NOT NULL,
    tid INT UNSIGNED NOT NULL,
    pid INT UNSIGNED NOT NULL,
    create_date INT UNSIGNED NOT NULL,
    solved_date INT UNSIGNED NOT NULL Default 0,
    isfirst TINYINT NOT NULL Default 1,
    feedback_type VARCHAR(255),
    thread_subject VARCHAR(255),
    message TEXT,
    excerpt TEXT,
    status TINYINT NOT NULL Default 0 Comment '-1未解决 0待解决 1解决',
    solved_by_uid INT UNSIGNED NOT NULL Default 0,
    PRIMARY KEY (fb_id)
  );";

$r = db_exec($sql);
$r === FALSE and message(-1, '创建反馈表结构失败');


$ob_feedback_setting = setting_get('ob_feedback_setting');
if (empty($ob_feedback_setting)) {
  $ob_feedback_setting = array(
    'reasons' => implode(PHP_EOL, ['涉嫌违法举报','敏感话题举报','违规内容举报','涉黄举报','侵权投诉','链接失效',]),
    'default_notice_text_solve' => '您好，我们已对您反馈的问题进行了处理。感谢您的支持！',
    'default_notice_text_no_solve' => '您好，我们已经收到了您的反馈。非常抱歉，由于某些原因，我们暂时无法处理您提出的问题。我们会继续关注此问题并尽快解决。感谢您的支持与理解。如有其他问题，请随时联系我们。',
    'default_credits1_solve' => 0,
    'default_credits2_solve' => 0,
    'default_credits3_solve' => 0,
    'default_credits1_no_solve' => 0,
    'default_credits2_no_solve' => 0,
    'default_credits3_no_solve' => 0,
  );

  setting_set('ob_feedback_setting', $ob_feedback_setting);
}
