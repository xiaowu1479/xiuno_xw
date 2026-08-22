<?php
function post_request($url, $postdata) {
    $data = http_build_query($postdata);
    $options    = array(
        'http' => array(
            'method'  => 'POST',
            'header'  => "Content-type: application/x-www-form-urlencoded",
            'content' => $data,
            'timeout' => 5
        )
    );
    $context = stream_context_create($options);
    $result    = file_get_contents($url, false, $context);
    if($http_response_header[0] != 'HTTP/1.1 200 OK'){
        $result = array(
            "result" => "success",
            "reason" => "request geetest api fail"
        );
        return json_encode($result);
    }else{
        return $result;
    }
}

function check_token_geetest($arr) {
	$captcha = kv_get('geetest');
	$captcha_id = !empty($captcha['geetestid']) ? $captcha['geetestid'] : '';
	$captcha_key = !empty($captcha['geeteskey']) ? $captcha['geeteskey'] : '';
	$api_server = "http://gcaptcha4.geetest.com";
	$sign_token = hash_hmac('sha256', $arr['lot_number'], $captcha_key);
	$query = array(
		"lot_number" => $arr['lot_number'],
		"captcha_output" => $arr['captcha_output'],
		"pass_token" => $arr['pass_token'],
		"gen_time" => $arr['gen_time'],
		"sign_token" => $sign_token
	);
	$url = sprintf($api_server . "/validate" . "?captcha_id=%s", $captcha_id);
	$res = post_request($url,$query);
	$obj = json_decode($res,true);
	$status = $obj['result'];
	return $status;
}

function geetestcheck($mode) {
    $config = kv_get('geetest');
    if (empty($config[$mode])) return;
    $lot_number = $_POST['lot_number'];
    $captcha_output = $_POST['captcha_output'];
    $pass_token = $_POST['pass_token'];
    $gen_time = $_POST['gen_time'];
    $arr=array('lot_number'=>$lot_number,'captcha_output'=>$captcha_output,'pass_token'=>$pass_token,'gen_time'=>$gen_time);
    empty($lot_number || $captcha_output || $pass_token || $gen_time) AND message('captcha', '请完成人机验证后再提交！');
    $geetest_result = check_token_geetest($arr);
    //file_put_contents(APP_PATH.'plugin/xn_geetest/model/debug.txt', '$geetest_result:'.$geetest_result."\r\n",FILE_APPEND);
   if ($geetest_result!='success'){
       message('captcha', '人机验证失败，请刷新页面后重新进行人机验证！');
   }
}

?>