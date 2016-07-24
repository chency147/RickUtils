<?php
/**
 * 验证码类
 *
 * @author Rick Chen
 */
class Captcha {
	const DEFAULT_FONT = 'texb.ttf';
	// 字符池
	public $char_pool =
		'2345678abcdefghjkmnprstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';
	// 图像高度
	public $height = 0;
	// 图像宽度
	public $width = 0;
	// 验证码
	public $code = '';
	// 验证码图片
	public $image;
	// 背景颜色
	public $color_bg;
	// 字体
	public $font;
	// 字体大小
	public $font_size;
	// 验证码长度
	public $char_num = 0;
	// 验证码在session中保存的key
	public $session_key;

	/**
	 * 构造函数
	 *
	 * @param	int		p_num		验证码字符数量
	 */
	public function __construct($p_num = 4) {
		// 默认的背景颜色范围
		$this ->  default_bg_color_range = array(
			'r1' => 255,
			'g1' => 255,
			'b1' => 255,
			'r2' => 200,
			'g2' => 200,
			'b2' => 200
		);
		// 默认的字体颜色范围
		$this ->  default_font_color_range = array(
			'r1' => 0,
			'g1' => 0,
			'b1' => 0,
			'r2' => 100,
			'g2' => 100,
			'b2' => 100
		);
		// 默认的干扰色颜色范围
		$this ->  default_noise_color_range = array(
			'r1' => 50,
			'g1' => 50,
			'b1' => 50,
			'r2' => 127,
			'g2' => 127,
			'b2' => 127
		);
		$this -> initialize($p_num);
	}

	/**
	 * 析构函数
	 */
	public function __destruct() {
		// 释放图片内存
		if (is_resource($this -> image)) {
			imagedestroy($this -> image);
		}
	}
	/**
	 * 初始化函数
	 *
	 * @param	int		p_char_num			验证码字符数量
	 * @param	int		p_width				验证码图片宽度
	 * @param	int		p_height			验证码图片高度
	 * @param	array	p_bg_color_range	背景颜色范围
	 * @param	array	p_font_color_range	字体颜色范围
	 * @param	array	p_noise_color_range	噪点颜色范围
	 * @param	string	p_font				字体
	 * @param	int		p_font_size			字体大小
	 */
	public function initialize($p_char_num = 4, $p_width = 100, $p_height = 50,  
		$p_bg_color_range = NULL, $p_font_color_range = NULL, 
		$p_noise_color_range = NULL, $p_font = NULL, $p_font_size = 18) {
		$this -> width = $p_width;
		$this -> height = $p_height;
		$this -> char_num = $p_char_num;
		$this -> set_color($p_bg_color_range, 'bg');
		$this -> set_color($p_font_color_range, 'font');
		$this -> set_color($p_noise_color_range, 'noise');
		if (file_exists($p_font)){
			$this -> font = $p_font;
		}else{
			$this -> font = self::DEFAULT_FONT;
		}
		$this -> font_size = $p_font_size;
	}

	/**
	 * 初始化验证码字符
	 *
	 * @return	string		验证码字符串
	 */
	public function init_code($p_code = NULL) {
		if (is_string($p_code)) {
			$this -> code = $p_code;
			return $this -> code;
		}
		$this -> code = '';
		for ($i = 0; $i < $this -> char_num; $i ++) {
			$this -> code .= 
				$this -> char_pool[rand(0, mb_strlen($this -> char_pool) - 1)];
		}
		return $this -> code;
	}

	/**
	 * 设置颜色范围
	 * 参数中所给的颜色范围有效并且颜色类型正确则返回TRUE
	 *
	 * @param	array	p_color_range		颜色范围
	 * @param	string	p_color_type		颜色类型
	 * @return	bool	true | false
	 */
	public function set_color($p_color_range, $p_color_type) {
		$type_array = array('bg', 'font', 'noise');
		// 确认颜色类型是否在给定颜色类型列表中
		if (!in_array($p_color_type, $type_array)) {
			return FALSE;
		}
		// 检查颜色范围有效性
		if (!$this -> is_color_range_valid($p_color_range)) {
			// 颜色范围无效，并且颜色范围未设置，则设置为默认颜色范围
			if (empty($this -> {$p_color_type.'_color_range'})) {
				$this -> {$p_color_type.'_color_range'} = 
					$this -> {'default_'.$p_color_type.'_color_range'};
			}
			return FALSE;
		} else {
			$this -> {$p_color_type.'_color_range'} = $p_color_range;
			return TRUE;
		}
	}

	/**
	 * 验证颜色范围是否有效
	 *
	 * @param	array	color_range		颜色范围
	 * @return	bool	TRUE | FALSE
	 */
	private function is_color_range_valid($p_color_range) {
		// 如果输入参数不是数组，那么直接返回FALSE
		if (!is_array($p_color_range)) {
			return FALSE;
		}
		$key_array = array('r1', 'g1', 'b1', 'r2', 'g2', 'b2');
		foreach($key_array as $key) {
			$value = &$p_color_range[$key];
			// 如果不存在某个参数的设定，
			// 或者这个参数不是整数，那么直接返回FALSE
			if (empty($value) || !is_int($value)) {
				return FALSE;
			}
			// 将超出颜色范围的值规范化
			if ($value < 0) {
				$value = 0;
			} else if ($value > 255){
				$value = 255;
			}
		}
		return TRUE;
	}

	/**
	 * 绘制验证码图片
	 *
	 * @param	int		p_f_size_vol		字体大小波动
	 * @param	int		p_x_vol				字符x坐标波动
	 * @param	int		p_y_vol				字符y坐标波动
	 * @param	int		p_angle				字符偏移角度
	 * @param	int		p_noise_den			噪点密度
	 * @return	resource	图片对象
	 */
	public function create_image($p_f_size_vol = 2, $p_x_vol = 5, $p_y_vol = 5, 
		$p_angle = 20, $p_noise_den = 0.15) {
		// 初始化验证码
		$this -> init_code();
		// 创建图片
		$this -> image = imagecreate($this -> width, $this -> height);
		// 填充背景颜色
		imagefill($this -> image, 0, 0,
			$this -> get_rand_color($this -> image, $this -> bg_color_range));
		// 平均每个字符可以分配到的宽度
		$avg_width = $this -> width / $this -> char_num;
		for($i = 0; $i < $this -> char_num; $i ++) {
			// 字体大小
			$size = rand($this -> font_size - $p_f_size_vol,
				$this -> font_size + $p_f_size_vol);
			// 字符偏移角度
			$angle = rand((-1) * $p_angle, $p_angle);
			// 字符x轴坐标
			$x = $avg_width * $i + rand(0, $size / 2);
			// 字符y轴坐标
			$y = abs(($this -> height - $size)) 
				+ rand(-1 * $p_y_vol, $p_y_vol);
			// 在图像上绘制字符
			imagettftext($this -> image, $size, $angle, $x, $y, 
				$this -> get_rand_color(
					$this -> image, $this -> font_color_range),
				$this -> font, $this -> code[$i]);
		}
		// 绘制噪点
		if ($p_noise_den > 1) {
			$p_noise_den = 1;
		}
		$p_noise_num = $this -> width * $this -> height * $p_noise_den;
		for ($i = 0; $i < $p_noise_num; $i ++) {
			$x = rand(0, $this -> width);
			$y = rand(0, $this -> height);
			imagesetpixel($this -> image, $x, $y, 
				$this -> get_rand_color(
					$this -> image, $this -> noise_color_range));
		}
		return $this -> image;
	}

	/**
	 * 从颜色范围中随机选取一个颜色
	 *
	 * @param	resource	p_image				图像对象
	 * @param	array		p_color_range		颜色范围
	 * @return	int		颜色
	 */
	private function get_rand_color($p_image, $p_color_range) {
		return imagecolorallocate($p_image, 
			rand($p_color_range['r1'], $p_color_range['r2']),
			rand($p_color_range['g1'], $p_color_range['g2']),
			rand($p_color_range['b1'], $p_color_range['b2'])
		);
	}

	/**
	 * 将验证码保存到session
	 *
	 * @param	string		p_session_key		保存在session中的key
	 */
	public function save_to_session($p_session_key) {
		// 开启session
		session_start();
		$_SESSION[$p_session_key] = $this -> code;
	}

	/**
	 * 验证码正确性验证
	 *
	 * @param	string		p_session_key		保存在session中的key
	 * @param	string		p_code				用户输入的验证码
	 * @return	bool	true | false
	 */
	public function check_code($p_session_key, $p_code) {
		// 开启session
		session_start();
		if (strcasecmp($_SESSION[$p_session_key], $p_code) == 0) {
			unset($_SESSION[$p_session_key]);
			return TRUE;
		}else{
			return FALSE;
		}
	}
}
