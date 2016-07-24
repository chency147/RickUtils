<?php
require_once('./captcha.php');
$captcha = new Captcha();
$captcha -> create_image();
$captcha -> save_to_session('CODE');
header("Content-type: image/png");
imagepng($captcha -> image);
