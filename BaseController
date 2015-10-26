<?php
/**
 * Base Controller
 *
 */
class BaseController {
	/**
	 * 请求类型
	 *
	 * @var unknown
	 */
	const REQUEST_POST = 'post';
	const REQUEST_GET = 'get';
	const REQUEST_COOKIE = 'cookie';
	const REQUEST_SERVER = 'server';
	const REQUEST_SESSION = 'session';
	const REPLY_TEXT = 'text';
	const REPLY_NEWS = 'news';
	const REPLY_MUSIC = 'music';

	private $_redismodel;
	private $_userModel; //用户model
	/**
	 * 默认每页展示数据条数
	 */
	const NUM_PER_PAGE = 10;

	public function __construct() {
		$this->initredis();
		$this->_init();
		$this->_userModel = new UserModel();

		$this->logger = Logger::getLogger('controller');
	}

	/**
	 * 子类无需定义__construct 只需实现此方法
	 * 控制器初始化接口，由子类实现
	 */
	protected function _init() {

	}
	public function getBaseRedisModelInst() {
    	return $this->_redismodel;
    }
	private function initredis() {
		global $_CONFIG;
		if (ISDEV) {
			$redis_conf = $_CONFIG['redis']['dev'];
		} else {
			$redis_conf = $_CONFIG['redis']['release'];
		}

		if (empty($redis_conf) && !array_key_exists('redis', (array) $redis_conf)) {
			return null;
		}

		foreach ($redis_conf as $v) {
			$this->_redismodel = RedisCenter::getRedisModel($v, 1, 0);
			if ($this->_redismodel) {
				return $this;
			}
		}
	}

	/**
	 * gaoziwen
	 * 将json串写入redis
	 */
	public function jsonToRedis($jsonDate) {
		$retcode = $this->_redismodel->rPush('excelKey', $jsonDate);
		return $retcode;
	}

	/**
	 *
	 *
	 *
	 * 获取相关参数的值
	 * 如果不指定类型,依次从get->post->cookie中获取,否则返回默认值
	 *
	 * @param str $key
	 *        	参数名
	 * @param str $type
	 *        	参数类型,null|get|post|cookie|server|session 默认为null
	 * @param str|int $value
	 *        	当没有获取到相关参数值时的默认值,默认为空
	 * @return str int 参数值
	 */
	protected function getParams($key, $type = null, $value = '') {
		$result = $value;
		if (empty($key)) {
			return $value;
		}

		$type = strtolower($type);
		switch ($type) {
			case self::REQUEST_GET:
				$result = isset($_GET[$key]) ? $_GET[$key] : $value;
				break;
			case self::REQUEST_POST:
				$result = isset($_POST[$key]) ? $_POST[$key] : $value;
				break;
			case self::REQUEST_COOKIE:
				$result = isset($_COOKIE[$key]) ? $_COOKIE[$key] : $value;
				break;
			case self::REQUEST_SERVER:
				$result = isset($_SERVER[$key]) ? $_SERVER[$key] : $value;
				break;
			case self::REQUEST_SESSION:
				$result = isset($_SESSION[$key]) ? $_SESSION[$key] : $value;
				break;
			default:
				$result = isset($_GET[$key]) ? $_GET[$key] : (isset($_POST[$key]) ? $_POST[$key] : (isset($_COOKIE[$key]) ? $_COOKIE[$key] : $value));
				break;
		}
		return !is_array($result) ? self::htmlEscapeChars($result) : $result;
	}

	public static function htmlEscapeChars($string, $len = 0, $etc = '') {
		if ($len == 0) {
			return htmlspecialchars($string, ENT_QUOTES);
		} else {
			$res = chars_substring($string, $len, $etc);
			return htmlspecialchars($res, ENT_QUOTES);
		}
	}

	/**
	 *
	 *
	 *
	 * 根据指定的类型,返回输出结果
	 * 目前只支持JSON和HTML两种格式,需求其他格式自己再封装,默认JSON格式
	 *
	 * JSON格式:当前端传有varname参数时,返回var xx = xxx的数据形式
	 * 当前端传有jsoncallback参数时,返回的是xx(****)的数据形式
	 * 其他情况返回json后的数据
	 * HTML格式:直接输出数据的结构
	 *
	 * @param str|int|array $out
	 *        	返回的结果集
	 * @param str $type
	 *        	$type 返回结果类型,JSON|HTML,默认json
	 * @param str $exit
	 *        	是否直接返回退出,默认不退出
	 */
	protected function outMessage($out, $type = 'JSON', $exit = false) {
		switch (strtoupper($type)) {
			case 'JSON':
				if (!empty($this->varname) && empty($this->jsoncallback)) {
					if ($this->hasScript) {
						@header("Content-type: text/html");
						echo "<script>try{document.domain='baidu.com';var " . $this->varname, ' = ', json_encode($out) . ';}catch(e){}</script>';
					} else {
						@header("Content-type: application/javascript");
						echo 'var ', $this->varname, ' = ', json_encode($out);
					}
				} elseif (!empty($this->jsoncallback)) {
					if ($this->hasScript) {
						@header("Content-type: text/html");
						echo "<script>try{document.domain='baidu.com';" . $this->jsoncallback, '(', json_encode($out), ');}catch(e){}' . "</script>";
					} else {
						@header("Content-type: application/javascript");
						echo $this->jsoncallback, '(', json_encode($out), ')';
					}
				} else {
					@header('Content-type:text/html;charset=utf-8');
					echo json_encode($out);
				}
				break;
			case 'SCRIPT':
				$ret = json_encode($out);
				echo "<script type='text/javascript'>try{document.domain='baidu.com';var data={$ret}}catch(e){}</script>";
				break;
			case 'GBK':
				@header("Content-type:text/html; charset=GB2312");
				echo ($out);
				break;
			case 'XML':
				@header("Content-type:text/xml");
				echo $out;
				break;
			case 'HTML':

			default:
				@header('Content-type:text/html;charset=utf-8');
				echo var_export($out, true);
				break;
		}
		if ($exit) {
			exit();
		}
	}

	/**
	 * View 实例初始化
	 */
	protected function _getView() {
		global $_CONTEXT;
		$path = APPLICATION_PATH . '/view/templates/' . $_CONTEXT['module'];
		$view = new View();
		$view->setFilePath($path);
		$view->web_url = 'http://' . $_SERVER['HTTP_HOST'];
		$view->server_host = $_SERVER['HTTP_HOST'];
		$view->module = $_CONTEXT['module'];
		$view->controller = $_CONTEXT['controller'];
		$view->action = $_CONTEXT['action'];
		$admin_id = trim(strrchr($_SERVER['REQUEST_URI'], '='), "=");

		if (intval($admin_id) == 0 && isset($_CONTEXT['info']['dealer_id'])) {
			$view->id = intval($_CONTEXT['info']['dealer_id']);
		} else {
			$view->id = intval($admin_id);
		}

		//设置静态资源的版本号
		$view->static_version = STATIC_VERSION;
		return $view;
	}

	/**
	 * 查询用户对本控制器内的某个方法在是否有权限操作
	 * 注：$action必须是本控制器内的
	 * @param unknown $action
	 */
	protected function hasPrivilege($action) {
		global $_CONTEXT;
		return in_array(strtolower($action), $_CONTEXT['info']['actions']);
	}

	/**
	 * json和js返回
	 *
	 * @param unknown $ret
	 */
	protected function _echoCallBack($ret) {
		echo "<script>document.domain='baidu.com';window.parent.goalert($ret);</script>";
		exit();
	}
	protected function _echoCallBack1($ret) {
		echo "<script>window.parent.goalert($ret);</script>";
		exit();
	}
	/**
	 * 接口返回数据json统一包装函数
	 *
	 * @param unknown $ret
	 * @param string $info
	 * @param unknown $data
	 */
	protected function _echoJson($ret, $info = '', $data = array()) {
		$res = array();
		$res['ret'] = $ret;
		$res['info'] = $info;
		$res['data'] = $data;
		echo json_encode($res);
		exit();
	}
	protected function _echoCallBackData($ret, $info = '', $data = array()) {
		$res = array();
		$res['ret'] = $ret;
		$res['info'] = $info;
		$res['data'] = $data;
		echo "<script type='text/javascript'>document.domain='baidu.com';var data=" . json_encode($res) . "</script>";
		exit();
	}

	/**
	 * 获取配置数据
	 *
	 * @param string $section
	 * @return boolean multitype: Ambigous
	 */
	protected function _getConfig($section = '') {
		$configFile = APP_PATH . '/config/config.ini';

		$configinfo = parse_ini_file($configFile, 1);
		if (empty($configinfo)) {
			return false;
		}

		if ($section == '') {
			return $configinfo;
		}

		if (isset($configinfo[$section])) {
			return $configinfo[$section];
		} else {
			return false;
		}
	}

	/**
	 * time format
	 */
	protected function getFormatTimeAccSecs($seconds) {
		return ($seconds != 0) ? date('Y-m-d H:i:s', $seconds) : '';
	}

	/**
	 * 验证Email
	 */

	public function chk_email($str) {
		return (preg_match('/^[a-zA-Z0-9_.\-]+@+[a-zA-Z0-9_\-]+(\\.[a-zA-Z0-9_\-]+)+$/', $str)) ? true : false;
	}
	/**
	 * 验证电话号码座机,手机
	 */

	public function chk_desk($str) {
		if (strlen($str) <= 20) {
			return true;
		}

		return false;

		//return (preg_match("/^(\d{3}-\d{8}|\d{4}-\d{7}|[1-9]\d{10})$/",$str)) ? true : false;
	}

	/**
	 * 验证手机号码
	 */
	public function chk_phone($str) {
		return (preg_match("/^[1-9]\d{10}$/", $str)) ? true : false;
	}

	/**
	 * 验证是否为指定长度的字母/数字组合/汉字
	 */
	public function chk_text1($num1, $num2, $str) {
		Return (preg_match("/^([a-zA-Z0-9\x{4e00}-\x{9fa5}]{" . $num1 . "," . $num2 . "})$/u", $str)) ? true : false;
		Return (preg_match("/^([a-zA-Z0-9]{" . $num1 . "," . $num2 . "}|[\x{4e00}-\x{9fa5}]{" . $num1 . "," . $num2 . "})$/u", $str)) ? true : false;
	}

	/**
	 * 验证是否为指定长度数字
	 */
	public function chk_text2($num1, $num2, $str) {
		return (preg_match("/^[0-9]{" . $num1 . "," . $num2 . "}$/i", $str)) ? true : false;
	}

	/**
	 * 验证是否为指定长度汉字
	 */
	public function chk_font($num1, $num2, $str) {
		return (preg_match("/^([\x{4e00}-\x{9fa5}]{" . $num1 . "," . $num2 . "})$/u", $str)) ? true : false;
	}

	/**
	 * 验证url地址
	 */
	public function chk_url($str) {
		return (preg_match("/^http:\/\/[A-Za-z0-9]+\.[A-Za-z0-9]+[\/=\?%\-&_~`@[\]\':+!]*([^<>\"\"])*$/", $str)) ? true : false;
	}

	/**
	 * 验证邮编
	 */
	public function chk_zip($str) {
		return (preg_match("/^\d{6}$/", $str)) ? true : false;
	}

	/**
	 * 验证传真
	 */
	public function chk_fax($str) {
		return (preg_match("/[-+]{1}\d+$/", $str)) ? true : false;
	}

	public function checkShortName($shortname = '') {
		$dealerInfoRedisModel = new DealerInfoRedisModel();
		$ret = $dealerInfoRedisModel->isshortNameInSet($shortname);
		return $ret ? true : false;
	}

	public function checkqq($qq) {
		$adminRedisModel = new AdminRedisModel();
		$ret = $adminRedisModel->isQQIdInSet($qq);
		return $ret ? true : false;
	}

	/**
	 * thomasdu
	 * jsoup 形式输出信息
	 * @param $code
	 * @param $data
	 * @param string $callback
	 * @param string $callbackname
	 */
	public function jsonp_echo($code, $data, $callback = '', $callbackname = 'QZOutputJson') {
		$ret_data = array(
			'ret' => $code,
			'data' => $data,
		);

		$str = json_encode($ret_data);
		if (!empty($callback)) {
			$callback = preg_replace("#[^A-z0-9]#", '', $callback);
			echo "$callback({$str})";
		} else if (!empty($callbackname)) {
			$callbackname = preg_replace("#[^A-z0-9]#", '', $callbackname);
			echo "{$callbackname}={$str}";
		} else {
			echo json_encode($ret_data);
		}

		exit();
	}

	/**
	 * 数据请求类接口 json 返回值
	 * @param  int 		$success  	返回状态，0：失败 1：成功
	 * @param  str  	$info     	错误信息，ret为1时为空字符串
	 * @param  int 		$cur_page 	当前页，和请求参数中的cur_page相同，默认1
	 * @param  int 		$offset   	每页数据条目数，和请求参数中的offset相同，默认20
	 * @param  int 		$total    	数据条目总数
	 * @param  array    $data     	当前页具体数据，数量 <= offset
	 * @param  str      $cacheKey   缓存键名
	 * @param  int      $expire     缓存时间
	 * @param  int      $export     是否导出csv 0|1
	 * @param  str      $csvName    导出csv文件名称
	 * @param  arr      $ext        其他拓展字段
	 */
	public function echoDataJson($success, $info, $cur_page = 1, $offset = 20, $total = 1, $data = array(), $type = null, $cacheKey = '', $expire = HOUR_DATA_EXPIRED, $export = 0, $csvName = '', $ext = array()) {
		if (ISDEV == true) {
			header("Access-Control-Allow-Origin: *");
		}
		$ret = array();
		$ret['success'] = $success;
		$ret['info'] = $info;
		$ret['cur_page'] = $cur_page;
		$ret['offset'] = $offset;
		$ret['total'] = $total;
		$ret['data'] = $data;

		if (!empty($ext)) {
			$ret['ext'] = $ext;
		}

		if ($cacheKey && ISCACHE && in_array($type, array('day', 'week', 'month'))) {
			$this->_redismodel && $this->_redismodel->set($cacheKey, $ret, $expire);
			if (BYPASS_EXIT) {
				return;
			}
			//batch write cache
		}
		if (1 == $export) {
			$this->setHttpHeaderForCsv($csvName);
			foreach ($data AS $v) {
				$this->output_csv($v);
			}
		} else {
			echo json_encode($ret);
		}
		exit();
	}

	/**
	 * 操作类接口 json 返回值
	 * @param  int 		$success  	返回状态，0：失败 1：成功
	 * @param  str  	$info     	错误信息，ret为1时为空字符串
	 */
	public function echoActJson($success, $info) {
		if (ISDEV == true) {
			header("Access-Control-Allow-Origin: *");
		}
		$ret = array();
		$ret['success'] = $success;
		$ret['info'] = $info;
		echo json_encode($ret);
		exit();
	}

	/**
	 * 返回不带分页信息的简单json数据
	 * @param  int 		$success  	返回状态，0：失败 1：成功
	 * @param  str  	$info     	错误信息，ret为1时为空字符串
	 * @param  array    $data     	当前页具体数据
	 */
	public function echoShortDataJson($success, $info, $data = array()) {
		if (ISDEV == true) {
			header("Access-Control-Allow-Origin: *");
		}
		$ret = array();
		$ret['success'] = $success;
		$ret['info'] = $info;
		$ret['data'] = $data;
		echo json_encode($ret);
		exit();
	}

	/**
	 * set http header for csv
	 * @param string $filename
	 */
	public function setHttpHeaderForCsv($filename) {
		if (ISDEV == true) {
			header("Access-Control-Allow-Origin: *");
		}
		header('Content-Type: text/csv');
		header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
	}

	/**
	 * 通用发送csv
	 */
	public function output_csv($arr) {
		static $file = NULL;
		if ($file == NULL) {
			$file = fopen('php://output', 'w');
		}
		$arr = array_map(array($this, 'mapToGBK'), $arr);
		fputcsv($file, $arr);
	}

	/**
	 * 工具函数，utf8转码成GBK
	 * @param string $str
	 */
	private function mapToGBK($str) {
		return iconv("UTF-8", "GBK", $str);
	}

	/**
	 * 获取缓存数据
	 * @param string $key 键名
	 */
	public function cacheData(&$cacheKey, $type, $startDate, $endDate) {
		if (ISCACHE == 0) {
			return 0;
		}

		switch ($type) {
			case 'day':
				$cacheKey .= $startDate;
				break;
			case 'week':
			case 'month':
				$cacheKey .= $startDate . $endDate;
				break;
			default:
				return 0;
				break;
		}
		$cacheKey = CACHEPRE . $cacheKey;

		if (BYPASS_EXIT) {
			return;
		}
		//batch write cache

		$ret = $this->_redismodel ? $this->_redismodel->get($cacheKey) : 0;
		return $ret ? $ret : 0;
	}

	//根据cardId和actionId权限生效
	public function checkAuthority($cardId, $actionId) {
		global $_CONTEXT;
		$userId = $_CONTEXT['info']['user_id'];
		/*$userName = $_CONTEXT['info']['user_name'];
		$userRole = $_CONTEXT['info']['user_role'];*/

		// add super_admin role
		$userRole = $_CONTEXT['info']['user_role'];
		if ($userRole == 3 || $userRole == 4) {
			// role 3 super_admin_readonly
			// role 4 super_admin
			if ($userRole == 3 && $actionId != BD_ACTION_VIEW) {
				return false;
			}
			return true;
		}

		$ret = $this->_userModel->getOwnerAuthority($userId);
		$authId = $cardId . $actionId;
		$result = false;
		foreach ($ret as $key => $value) {
			if ($value['auth_id'] == $authId) {
				$result = true;
			}

		}

		return $result;

	}

}
