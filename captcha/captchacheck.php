<?php
require_once('./captcha.php');
$code = $_POST['code'];
$captcha = new Captcha();
if ($captcha -> check_code('CODE', $code)) {
	echo 1;
}else{
	echo 0;
}
