<!DOCTYPE html>
<html lang="zh">
	<head>
		<script src="./jquery-3.1.0.min.js" type="text/javascript"></script>
<script type="text/javascript">
$(function() {
	$("#captcha").click(function() {
		$(this).attr("src",'captcha_img.php?'+Math.random()); 
	}); 
	$("#btn_submit").click(function() {
		var code = $("#code").val();
		$.post('captchacheck.php',
		{ "code" : code },
		function(msg) {
			if (msg == 1) {
				$("#captcha").trigger("click");
				alert("验证码正确！");
			} else {
				alert("验证码错误！");
			}
		});
	}); 
})
</script>
<meta charset="UTF-8">
<title>验证码测试</title>
	</head>
	<body>
		<img src="captcha_img.php" id="captcha"  /><br />
		<input type="text" name="code" id="code" autocomplete="off"/><br />
		<input type="button" value="提交" id="btn_submit"/>
	</body>
</html>
