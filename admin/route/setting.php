<?php

!defined('DEBUG') AND exit('Access Denied.');

$action = param(1);

include _include(APP_PATH.'model/smtp.func.php');
$smtplist = smtp_init(APP_PATH.'conf/smtp.conf.php');
// hook admin_setting_start.php

if($action == 'base') {
	
	// hook admin_setting_base_get_post.php
	
	if($method == 'GET') {
		
		// hook admin_setting_base_get_start.php
		
		$input = array();
		$input['sitename'] = form_text('sitename', $conf['sitename']);
		$input['sitebrief'] = form_textarea('sitebrief', $conf['sitebrief'], '100%', 100);
		$input['runlevel'] = form_radio('runlevel', array(0=>lang('runlevel_0'), 1=>lang('runlevel_1'), 2=>lang('runlevel_2'), 3=>lang('runlevel_3'), 4=>lang('runlevel_4'), 5=>lang('runlevel_5')), $conf['runlevel']);
		$input['user_create_on'] = form_radio_yes_no('user_create_on', $conf['user_create_on']);
		$input['user_create_email_on'] = form_radio_yes_no('user_create_email_on', $conf['user_create_email_on']);
		$input['user_resetpw_on'] = form_radio_yes_no('user_resetpw_on', $conf['user_resetpw_on']);
		$input['captcha_on'] = form_radio_yes_no('captcha_on', $conf['captcha_on'], lang('captcha_tips'));
		$input['captcha_login_on'] = form_radio_yes_no('captcha_login_on', $conf['captcha_login_on']);
		$input['captcha_reg_on'] = form_radio_yes_no('captcha_reg_on', $conf['captcha_reg_on']);
		$input['captcha_post_on'] = form_radio_yes_no('captcha_post_on', $conf['captcha_post_on']);
		$input['cookie_secure'] = form_radio_yes_no('cookie_secure', $conf['cookie_secure'], lang('cookie_secure_tips'));
		$input['online_hold_time'] = form_text('online_hold_time', $conf['online_hold_time'], 100);
		$input['lang'] = form_select('lang', array('zh-cn'=>lang('lang_zh_cn'), 'zh-tw'=>lang('lang_zh_tw'), 'en-us'=>lang('lang_en_us'), 'ru-ru'=>lang('lang_ru_ru'), 'th-th'=>lang('lang_th_th')), $conf['lang']);
		$input['favicon_url'] = form_text('favicon_url', $conf['favicon_url']);
		$input['statistic_code'] = form_textarea('statistic_code', $conf['statistic_code'], '100%', 150);
		
		$header['title'] = lang('admin_site_setting');
		$header['mobile_title'] =lang('admin_site_setting');
		
		// hook admin_setting_base_get_end.php
		
		include _include(ADMIN_PATH.'view/htm/setting_base.htm');
		
	} else {
		
		$sitebrief = param('sitebrief', '', FALSE);
		$sitename = param('sitename', '', FALSE);
		$runlevel = param('runlevel', 0);
		$user_create_on = param('user_create_on', 0);
		$user_create_email_on = param('user_create_email_on', 0);
		$user_resetpw_on = param('user_resetpw_on', 0);
		$captcha_on = param('captcha_on', 0);
		$captcha_login_on = param('captcha_login_on', 0);
		$captcha_reg_on = param('captcha_reg_on', 0);
		$captcha_post_on = param('captcha_post_on', 0);
		$cookie_secure = param('cookie_secure', 0);
		$online_hold_time = param('online_hold_time', 3600);
		
		$_lang = param('lang');
		$favicon_url = param('favicon_url', '', FALSE);
		$statistic_code = param('statistic_code', '', FALSE);
		
		// hook admin_setting_base_post_start.php
		
		$replace = array();
		$replace['sitename'] = $sitename;
		$replace['sitebrief'] = $sitebrief;
		$replace['runlevel'] = $runlevel;
		$replace['user_create_on'] = $user_create_on;
		$replace['user_create_email_on'] = $user_create_email_on;
		$replace['user_resetpw_on'] = $user_resetpw_on;
		$replace['captcha_on'] = $captcha_on;
		$replace['captcha_login_on'] = $captcha_login_on;
		$replace['captcha_reg_on'] = $captcha_reg_on;
		$replace['captcha_post_on'] = $captcha_post_on;
		$replace['cookie_secure'] = $cookie_secure;
		$replace['online_hold_time'] = max(60, min(86400, intval($online_hold_time)));
		$replace['lang'] = $_lang;
		$replace['favicon_url'] = $favicon_url;
		$replace['statistic_code'] = $statistic_code;
		
		file_replace_var(APP_PATH.'conf/conf.php', $replace);
	
		// hook admin_setting_base_post_end.php
		
		message(0, lang('modify_successfully'));
	}

} elseif($action == 'smtp') {

	// hook admin_setting_smtp_get_post.php
	
	if($method == 'GET') {
		
		// hook admin_setting_smtp_get_start.php
		
		$header['title'] = lang('admin_setting_smtp');
		$header['mobile_title'] = lang('admin_setting_smtp');
	
		$smtplist = smtp_find();
		$maxid = smtp_maxid();

		// 测试收件邮箱默认填当前管理员自己的邮箱
		$test_email = isset($user['email']) ? $user['email'] : '';
		
		// hook admin_setting_smtp_get_end.php
		
		include _include(ADMIN_PATH."view/htm/setting_smtp.htm");
	
	} else {
		
		// hook admin_setting_smtp_post_start.php

		$email = param('email', array(''));
		$host = param('host', array(''));
		$port = param('port', array(0));
		$user = param('user', array(''));
		// 密码不转义，否则含 & " < > 等字符的密码会被 htmlspecialchars 破坏
		$pass = param('pass', array(''), FALSE);

		// 测试发信：用页面上当前这一行的配置（含尚未保存的修改）发一封测试邮件
		if(param('smtp_action', '') == 'test') {

			include _include(XIUNOPHP_PATH.'xn_send_mail.func.php');

			$test_to = trim(strval(param('test_email', '', FALSE)));
			$rowid = intval(param('test_row', 0));

			$test_to === '' AND message(-1, lang('smtp_test_email_required'));
			is_email($test_to, $err) OR message(-1, $err);
			isset($email[$rowid]) OR message(-1, lang('smtp_test_row_error'));

			$smtp = array(
				'email' => trim(strval($email[$rowid])),
				'host' => isset($host[$rowid]) ? trim(strval($host[$rowid])) : '',
				'port' => isset($port[$rowid]) ? intval($port[$rowid]) : 0,
				'user' => isset($user[$rowid]) ? trim(strval($user[$rowid])) : '',
				'pass' => isset($pass[$rowid]) ? strval($pass[$rowid]) : '',
			);
			($smtp['email'] === '' || $smtp['host'] === '' || $smtp['user'] === '' || $smtp['pass'] === '')
				AND message(-1, lang('smtp_test_row_error'));

			$subject = lang('smtp_test_subject', array('sitename'=>$conf['sitename']));
			$message = lang('smtp_test_body', array(
				'sitename' => $conf['sitename'],
				'host' => $smtp['host'],
				'port' => $smtp['port'],
				'from' => $smtp['email'],
				'to' => $test_to,
				'time' => date('Y-m-d H:i:s', $time),
			));

			// hook admin_setting_smtp_test_before.php
			$r = xn_send_mail($smtp, $conf['sitename'], $test_to, $subject, $message);
			// hook admin_setting_smtp_test_after.php

			if($r === TRUE) {
				message(0, lang('smtp_test_success', array('email'=>$test_to)));
			}
			xn_log('SMTP 测试发信失败：'.$smtp['host'].' - '.$errstr, 'send_mail_error');
			message(-1, lang('smtp_test_failed', array('error'=>$errstr)));
		}

		$smtplist = array();
		foreach ($email as $k=>$v) {
			$smtplist[$k] = array(
				'email'=>$email[$k],
				'host'=>$host[$k],
				'port'=>$port[$k],
				'user'=>$user[$k],
				'pass'=>$pass[$k],
			);
		}
		$r = file_put_contents_try(APP_PATH.'conf/smtp.conf.php', "<?php\r\nreturn ".var_export($smtplist,true).";\r\n?>");
		!$r AND message(-1, lang('conf/smtp.conf.php', array('file'=>'conf/smtp.conf.php')));
		
		// hook admin_setting_smtp_post_end.php
		
		message(0, lang('save_successfully'));
	}
}

// hook admin_setting_end.php

?>