<?php

/*
	Xiuno BBS 4.0 插件实例：广告插件设置
	admin/plugin-setting-ob_feedback.htm
*/

!defined('DEBUG') and exit('Access Denied.');

if (!function_exists('form_number')) {
	/**
	 * 数字输入框（HTML5）
	 * @param string $name 设置项的name
	 * @param int|float $value 预先填充的内容
	 * @param int $width 数字框的宽度（最好别用），false是不定义宽度，单位像素
	 * @param int|float $min 数字框的最小值（左侧数值）
	 * @param int|float $max 数字框的最大值（右侧数值）
	 * @param int|float $step 数字框的数字间隔（一格增减多少）（如果 step="3"，则合法数字是 -3,0,3,6，以此类推）
	 * @param string $placeholder 若未填写文字时的占位文字
	 * @return string 对应的HTML代码
	 */
	function form_number($name, $value, $width = FALSE, $min = 0, $max = 100, $step = 1, $placeholder = '') {
		$style = '';
		if ($width !== FALSE) {
			is_numeric($width) and $width .= 'px';
			$style = " style=\"width: $width\"";
		}

		$s = "<input type='number' name=\"$name\" id=\"$name\" min=\"$min\" max=\"$max\" step=\"$step\" value=\"$value\" class=\"form-control\" placeholder=\"$placeholder\" $style />";

		return $s;
	}
}

$setting = setting_get('ob_feedback_setting');

if ($method == 'GET') {

	$input = array();
	$input['reasons'] = form_textarea('reasons', $setting['reasons'], '100%', '100px');
	$input['default_notice_text_solve'] = form_textarea('default_notice_text_solve', $setting['default_notice_text_solve'], '100%', '100px');
	$input['default_notice_text_no_solve'] = form_textarea('default_notice_text_no_solve', $setting['default_notice_text_no_solve'], '100%', '100px');
	$input['default_credits1_solve'] = form_number('default_credits1_solve', $setting['default_credits1_solve'], false, 0, 10000, 1);
	$input['default_credits2_solve'] = form_number('default_credits2_solve', $setting['default_credits2_solve'], false, 0, 10000, 1);
	$input['default_credits3_solve'] = form_number('default_credits3_solve', $setting['default_credits3_solve'], false, 0, 10000, 1);
	$input['default_credits1_no_solve'] = form_number('default_credits1_no_solve', $setting['default_credits1_no_solve'], false, 0, 10000, 1);
	$input['default_credits2_no_solve'] = form_number('default_credits2_no_solve', $setting['default_credits2_no_solve'], false, 0, 10000, 1);
	$input['default_credits3_no_solve'] = form_number('default_credits3_no_solve', $setting['default_credits3_no_solve'], false, 0, 10000, 1);

	include _include(APP_PATH . 'plugin/ob_feedback/setting.htm');
} else {
	$setting['reasons'] = param('reasons', '');
	$setting['default_notice_text_solve'] = param('default_notice_text_solve', '');
	$setting['default_notice_text_no_solve'] = param('default_notice_text_no_solve', '');
	$setting['default_credits1_solve'] = param('default_credits1_solve', 0);
	$setting['default_credits2_solve'] = param('default_credits2_solve', 0);
	$setting['default_credits3_solve'] = param('default_credits3_solve', 0);
	$setting['default_credits1_no_solve'] = param('default_credits1_no_solve', 0);
	$setting['default_credits2_no_solve'] = param('default_credits2_no_solve', 0);
	$setting['default_credits3_no_solve'] = param('default_credits3_no_solve', 0);

	setting_set('ob_feedback_setting', $setting);

	message(0, '修改成功');
}
