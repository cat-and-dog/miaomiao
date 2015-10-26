<?php

/**
 * 代理中心，处理统一验证等工作
 * @author 
 *
 */
class ProxyCenter {
	private $_controller;
	private $_logger;
	private $_userModel;

	/**
	 * 请求详情
	 *
	 * @var unknown
	 */
	private $_requests;
	private $_verifyCenter;

	public function __construct($controller, $info) {
		$this->_controller = $controller;

		$this->_logger = Logger::getLogger('proxy');

		$this->_requests = $info;

		$this->_userModel = new UserModelOp();

		$this->_verifyCenter = new VerifyCenter();

		global $_CONFIG;
	}
	/**
	 * 拦截调用到的controller方法 在ProxyCenter中本身没有，故能调用到__call
	 *
	 * @param unknown $method
	 * @param unknown $argument
	 * @throws Exception
	 * @return mixed
	 */
	public function __call($method, $args) {
		/**
		 * 权限验证模块开始
		 */
		$this->execVerifyPri();
		/**
		 * 权限验证模块结束
		 *
		 * 调用controller方法开始
		 */

		$callBack = array(
			$this->_controller,
			$method,
		);
		$return = call_user_func_array($callBack, $args);

		return $return;
	}
	/**
	 * 处理权限验证逻辑
	 */
	private function execVerifyPri() {
		global $_CONTEXT;

		$uin = isset($_COOKIE["uin"]) ? $_COOKIE["uin"] : '';
		$page = '/' . $this->_requests['module'] . '/' . $this->_requests['controller'] . '/' . $this->_requests['action'];

		$userInfo = $this->_verifyCenter->verifyLogin();
		// $userList = $this->_userModel->getAdminList();

		if ($userInfo == false) {
			// 登录失败
			$this->errorHandler('登录失败，请重新登录！', 'http://openapi.baidu.com');

		} else if (empty($userInfo)) {
			// 用户表里无此用户信息，认为非法用户	，页面跳转至出错页
			$this->errorHandler('您不是本系统用户，请添加用户！', 'http://openapi.baidu.com');
			/*} else if (!in_array($userInfo['name'], $_ADMIN_USERS)) {
			$this->errorHandler('您暂无访问本系统的权限，请联系管理员！', 'http://openapi.baidu.com');*/
			// } else if (!in_array($userInfo['name'], $userList)) {
			// $this->errorHandler('您暂无访问本系统的权限，请联系管理员！', 'http://openapi.baidu.com');
		} else {

			$retInfo = array();
			$this->_verifyCenter->setRetInfo($retInfo);
			$opPower = $this->checkOperatePower($retInfo['user_name']);
			if(!$opPower){
				$this->errorHandler('您没有改操作权限，请联系管理员添加操作权限！', 'http://openapi.baidu.com');
			}
			// //判断用户请求页面是否是该用户权限内的，页面跳转至出错页
			// if (isset($retInfo['bindauthoritys']) && !empty($retInfo['bindauthoritys']) && in_array($page, $retInfo['bindauthoritys'])) {
			//获取该用户的所有服务商，一次性查询
			$objAgentModelOp = new AgentModelOp();
			
			$arrRet = $objAgentModelOp->agentQueryByUserName($retInfo['user_name']);
			$retInfo['agent'] = $arrRet;
			// var_dump($retInfo);exit;
			$_CONTEXT['info'] = $retInfo;

			// }else {
			// 	$this->errorHandler('您没有本系统任何权限，请添加权限！', 'http://openapi.baidu.com');
			// }
		}
	}
	private function checkOperatePower($userName){
		$function = $this->_requests['controller'] . '_' . $this->_requests['action'];
		echo 'function:<'.$function.'>';
		$userPower = $_userModel->userPowerQuery($userName);
		
		print_r($userPower);
		if($userPower){
			foreach($userPower as $power){
				
				if($function==$power['powerId'])
					return true;
			}
			}
		
		return false;
	}
	/**
	 * 错误提示
	 *
	 * @param unknown $type
	 * @param unknown $msg
	 */
	private function errorHandler($msg, $url) {
		// if ($_SERVER ['REQUEST_METHOD'] == "POST") {

		$res = array();
		$res['success'] = 0;
		$res['info'] = $msg;
		$res['url'] = $url;
		$json_string = json_encode($res);
		$requestPath = preg_replace('/\?.*$/', '', $_SERVER['REQUEST_URI']);

		// if(isset($_POST['r'])){
		// echo $json_string;
		// }else{
		echo "<script type='text/javascript'>alert('$msg');top.location.href = '$url';</script>";
		// }
		// } else {
		// echo "<script type='text/javascript'>alert('$msg');top.location.href = '$url';</script>";
		// }

		exit();
	}
	/**
	 * 获取代理对象
	 *
	 * @param unknown $controller
	 * @return VerifyCenter
	 */
	public static function getControllerProxy($controller, $info) {
		return new ProxyCenter($controller, $info);
	}
}
