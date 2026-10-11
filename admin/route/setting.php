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
		$input['lang'] = form_select('lang', array('zh-cn'=>lang('lang_zh_cn'), 'zh-tw'=>lang('lang_zh_tw'), 'en-us'=>lang('lang_en_us'), 'ru-ru'=>lang('lang_ru_ru'), 'th-th'=>lang('lang_th_th')), $conf['lang']);
		$input['favicon_url'] = form_text('favicon_url', $conf['favicon_url']);
		$input['statistic_code'] = form_textarea('statistic_code', $conf['statistic_code'], '100%', 150);
		$input['theme_auto_on'] = form_radio_yes_no('theme_auto_on', $conf['theme_auto_on']);
		$input['theme_night_start'] = form_text('theme_night_start', $conf['theme_night_start'], 90);
		$input['theme_night_end'] = form_text('theme_night_end', $conf['theme_night_end'], 90);

		// 当前时段提示：开着的时段设置到底有没有生效，一眼可见
		$theme_now_hint = '';
		if(!empty($conf['theme_auto_on'])) {
			if(function_exists('theme_is_night')) {
				$xw_now = time();
				$theme_now_hint = date('H:i', $xw_now).' → '.(
					theme_is_night($conf['theme_night_start'], $conf['theme_night_end'], $xw_now)
						? lang('theme_mode_dark') : lang('theme_mode_light')
				);
			} else {
				$theme_now_hint = lang('theme_cache_stale');
			}
		}
		
		$header['title'] = lang('admin_site_setting');
		$header['mobile_title'] =lang('admin_site_setting');
		
		// hook admin_setting_base_get_end.php
		
		include _include(ADMIN_PATH.'view/htm/setting_base.htm');
		
	} else {
		
		$sitebrief = param('sitebrief', '', FALSE);
		$sitename = param('sitename', '', FALSE);
		$runlevel = param('runlevel', 0);
		$_lang = param('lang');
		$favicon_url = param('favicon_url', '', FALSE);
		$statistic_code = param('statistic_code', '', FALSE);

		$theme_auto_on = param('theme_auto_on', 0) ? 1 : 0;
		// 时段规范成 HH:MM，非法输入回退默认值，避免把配置写坏
		$_theme_times = array('theme_night_start' => '19:00', 'theme_night_end' => '07:00');
		foreach($_theme_times as $_tk => $_tdef) {
			$_tv = trim(strval(param($_tk, '', FALSE)));
			if(preg_match('/^(\d{1,2}):(\d{1,2})$/', $_tv, $_tm)) {
				$_tv = sprintf('%02d:%02d', min(23, intval($_tm[1])), min(59, intval($_tm[2])));
			} else {
				$_tv = $_tdef;
			}
			$_theme_times[$_tk] = $_tv;
		}
		
		// hook admin_setting_base_post_start.php
		
		$replace = array();
		$replace['sitename'] = $sitename;
		$replace['sitebrief'] = $sitebrief;
		$replace['runlevel'] = $runlevel;
		$replace['lang'] = $_lang;
		$replace['favicon_url'] = $favicon_url;
		$replace['statistic_code'] = $statistic_code;
		$replace['theme_auto_on'] = $theme_auto_on;
		$replace['theme_night_start'] = $_theme_times['theme_night_start'];
		$replace['theme_night_end'] = $_theme_times['theme_night_end'];
		
		file_replace_var(APP_PATH.'conf/conf.php', $replace);
	
		// hook admin_setting_base_post_end.php
		
		message(0, lang('modify_successfully'));
	}

} elseif($action == 'user') {
	
	// hook admin_setting_user_get_post.php
	
	if($method == 'GET') {
		
		// hook admin_setting_user_get_start.php
		
		$input = array();
		$input['user_create_on'] = form_radio_yes_no('user_create_on', $conf['user_create_on']);
		$input['user_create_email_on'] = form_radio_yes_no('user_create_email_on', $conf['user_create_email_on']);
		$input['user_resetpw_on'] = form_radio_yes_no('user_resetpw_on', $conf['user_resetpw_on']);
		$input['captcha_on'] = form_radio_yes_no('captcha_on', $conf['captcha_on']);
		$input['captcha_login_on'] = form_radio_yes_no('captcha_login_on', $conf['captcha_login_on']);
		$input['captcha_reg_on'] = form_radio_yes_no('captcha_reg_on', $conf['captcha_reg_on']);
		$input['captcha_post_on'] = form_radio_yes_no('captcha_post_on', $conf['captcha_post_on']);
		
		$header['title'] = lang('admin_setting_user');
		$header['mobile_title'] = lang('admin_setting_user');
		
		// hook admin_setting_user_get_end.php
		
		include _include(ADMIN_PATH.'view/htm/setting_user.htm');
		
	} else {
		
		$user_create_on = param('user_create_on', 0) ? 1 : 0;
		$user_create_email_on = param('user_create_email_on', 0) ? 1 : 0;
		$user_resetpw_on = param('user_resetpw_on', 0) ? 1 : 0;
		$captcha_on = param('captcha_on', 0) ? 1 : 0;
		$captcha_login_on = param('captcha_login_on', 0) ? 1 : 0;
		$captcha_reg_on = param('captcha_reg_on', 0) ? 1 : 0;
		$captcha_post_on = param('captcha_post_on', 0) ? 1 : 0;
		
		// hook admin_setting_user_post_start.php
		
		$replace = array();
		$replace['user_create_on'] = $user_create_on;
		$replace['user_create_email_on'] = $user_create_email_on;
		$replace['user_resetpw_on'] = $user_resetpw_on;
		$replace['captcha_on'] = $captcha_on;
		$replace['captcha_login_on'] = $captcha_login_on;
		$replace['captcha_reg_on'] = $captcha_reg_on;
		$replace['captcha_post_on'] = $captcha_post_on;
		
		file_replace_var(APP_PATH.'conf/conf.php', $replace);
		
		// hook admin_setting_user_post_end.php
		
		message(0, lang('modify_successfully'));
	}
	
} elseif($action == 'security') {
	
	// hook admin_setting_security_get_post.php
	
	if($method == 'GET') {
		
		// hook admin_setting_security_get_start.php
		
		$input = array();
		// Token 绑定 IP / 后台绑定 IP / Cookie Secure / CDN（改这些配置不用再去动服务器上的 conf/conf.php）
		$input['token_bind_ip'] = form_radio('token_bind_ip', array(1=>lang('token_bind_ip_1'), 2=>lang('token_bind_ip_2'), 0=>lang('token_bind_ip_0')), isset($conf['token_bind_ip']) ? intval($conf['token_bind_ip']) : 1);
		$input['cookie_secure'] = form_radio_yes_no('cookie_secure', isset($conf['cookie_secure']) ? $conf['cookie_secure'] : 0);
		$input['online_hold_time'] = form_text('online_hold_time', $conf['online_hold_time'], 100);
		$input['admin_bind_ip'] = form_radio_yes_no('admin_bind_ip', isset($conf['admin_bind_ip']) ? $conf['admin_bind_ip'] : 0);
		$input['cdn_on'] = form_radio_yes_no('cdn_on', isset($conf['cdn_on']) ? $conf['cdn_on'] : 0);
		$cdn_ip_str = isset($conf['cdn_ip']) ? (is_array($conf['cdn_ip']) ? implode(', ', $conf['cdn_ip']) : strval($conf['cdn_ip'])) : '';
		$input['cdn_ip'] = form_text('cdn_ip', $cdn_ip_str, 420, '1.2.3.4, 1.2.3.0/24');
		
		$header['title'] = lang('admin_setting_security');
		$header['mobile_title'] = lang('admin_setting_security');
		
		// hook admin_setting_security_get_end.php
		
		include _include(ADMIN_PATH.'view/htm/setting_security.htm');
		
	} else {
		
		$token_bind_ip = intval(param('token_bind_ip', 1));
		in_array($token_bind_ip, array(0, 1, 2)) OR $token_bind_ip = 1;	// 非法值回退到「同 C 段」
		$cookie_secure = param('cookie_secure', 0) ? 1 : 0;
		$online_hold_time = max(60, min(86400, intval(param('online_hold_time', 3600))));
		$admin_bind_ip = param('admin_bind_ip', 0) ? 1 : 0;
		$cdn_on = param('cdn_on', 0) ? 1 : 0;
		// CDN 白名单：允许逗号 / 空格 / 换行混用，统一存成 "a, b" 字符串；明显非法的条目直接丢弃
		$cdn_ip_arr = array();
		foreach(preg_split('#[\s,]+#', trim(strval(param('cdn_ip', '', FALSE)))) as $_ipitem) {
			$_ipitem = trim($_ipitem);
			if($_ipitem === '') continue;
			if(strpos($_ipitem, '/') !== FALSE) {
				list($_ipnet, $_ipmask) = explode('/', $_ipitem, 2);
				if(ip2long($_ipnet) === FALSE || !ctype_digit($_ipmask) || intval($_ipmask) > 32) continue;
			} elseif(substr($_ipitem, -1) == '*') {
				if(ip2long(substr($_ipitem, 0, -1).'0') === FALSE) continue;
			} else {
				if(ip2long($_ipitem) === FALSE) continue;
			}
			$cdn_ip_arr[] = $_ipitem;
		}
		$cdn_ip = implode(', ', $cdn_ip_arr);
		
		// hook admin_setting_security_post_start.php
		
		$replace = array();
		$replace['token_bind_ip'] = $token_bind_ip;
		$replace['cookie_secure'] = $cookie_secure;
		$replace['online_hold_time'] = $online_hold_time;
		$replace['admin_bind_ip'] = $admin_bind_ip;
		$replace['cdn_on'] = $cdn_on;
		$replace['cdn_ip'] = $cdn_ip;
		
		file_replace_var(APP_PATH.'conf/conf.php', $replace);
		
		// hook admin_setting_security_post_end.php
		
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