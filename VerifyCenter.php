<?php

/**
 * 权限验证中心
 *
 * 
 *
 */

class VerifyCenter {

	private $logger;
	private $userModel;
	// private $roleModel;
	private $userInfo;

	public function __construct() {
		$this->userModel = new UserModelOp();
		// $this->roleModel = new RoleModel();
		$this->logger = Logger::getLogger("proxy");
	}

	/**
	 * 验证登录或者是非法用户
	 * 登录不成功直接返回false
	 *
	 * @return multitype:number string unknown |boolean
	 */
	public function verifyLogin() {

		$ret = false;

		$uuap_config = null;
		if (ISDEV === true) {
			$uuap_config = getConfig('uuap_conf_test');
		} else {
			$uuap_config = getConfig('uuap_conf');
		}

		if (!is_array($uuap_config)) {
			return $ret;
		}

		// Uncomment to enable debugging
		phpCAS::setDebug();

		// Initialize phpCAS
		phpCAS::client(CAS_VERSION_2_0, $uuap_config['cas_host'], intval($uuap_config['cas_port']), $uuap_config['cas_context']);

		// For quick testing you can disable SSL validation of the CAS server.
		// THIS SETTING IS NOT RECOMMENDED FOR PRODUCTION.
		// VALIDATING THE CAS SERVER IS CRUCIAL TO THE SECURITY OF THE CAS PROTOCOL!
		phpCAS::setNoCasServerValidation();

		// force CAS authentication
		$islogin = phpCAS::isAuthenticated();
		// var_dump($islogin);exit;
		// 		$islogin = phpCAS::checkAuthentication();
		// $islogin = phpCAS::isSessionAuthenticated();
		if ($islogin === true) {

			$login_name = phpCAS::getUser();
			echo 'login_name:'.$login_name;
			$login_ret = $this->userModel->userQueryByName($login_name);
			var_dump($login_ret);
			$login_info=array(); 
			if($login_ret){
				foreach($login_ret as $v){
					$login_ret['id']=$v['id'];
					$login_ret['name']=$v['name'];
					$login_ret['role_id']=$v['role_id'];
				}
			}
			$login_info = (is_array($login_info) && count($login_info) > 0) ? $login_info : array();
			// if (empty($login_info)) {
			// 	$succ = $this->userModel->addUser(array('name' => $login_name));
			// 	//TODO succ == false 失败时处理方案
			// 	// $login_info = array('name' => $login_name);
			// 	$login_info = $this->userModel->getUsersList($login_name, 0, 1);
			// 	$login_info = (is_array($login_info) && count($login_info) > 0) ? $login_info[0] : array();
			// }
			
			$this->userInfo = $login_info;

			$ret = $login_info;
		} else {
			// phpCAS::forceAuthentication();
			// $login_url = phpCAS::getServerLoginURL();
			global $_HOSTNAME;
			$url = 'http://' . $_HOSTNAME . '/api/user/loginredirect';
			$login_url = phpCAS::getServerLoginURLWithCallbackURL($url);
			$res = array(
				'success' => 2,
				'info' => "请登录",
				'data' => array('redirect_url' => $login_url),
			);
			$json_string = json_encode($res);
			echo $json_string;
			exit();
		}

		return $ret;
	}

	public function setRetInfo(&$retInfo) {

		$user_id = $this->userInfo['id'];

		//user基本信息
		$retInfo['user_id'] = $user_id;
		$retInfo['user_name'] = $this->userInfo['name'];
		$retInfo['user_role'] = $this->userInfo['role_id'];
		// $retInfo['login_name'] = $this->userInfo['login_name'];
		// $retInfo['full_name'] = $this->userInfo['full_name'];
		// $retInfo['email'] = $this->userInfo['email'];

		//分配的角色信息
		// $user_roles = $this->userModel->getBindedRolesList($user_id, 0);
		// if (!empty($user_roles)) {

		// 	$user_roleIds = array();
		// 	foreach ($user_roles as $eachRole) {
		// 		$user_roleIds[] = $eachRole['role_id'];
		// 	}

		// 	$retInfo['bindroles'] = $user_roleIds;
		// }else {
		// 	$retInfo['bindroles'] = array();
		// }

		// //分配的权限信息
		// if (!empty($user_roles)) {
		// 	$tempInfo = array();
		// 	foreach ($user_roles as $eachRole) {
		// 		$role_authoritys = $this->roleModel->getBindedAuthoritysList($eachRole['role_id'], 0);

		// 		if (!empty($role_authoritys)) {
		// 			$tempInfo = array_merge($tempInfo, $role_authoritys);
		// 		}
		// 	}

		// 	$temp = array();

		// 	foreach ($tempInfo as $eachTempInfo) {
		// 		$temp[] = $eachTempInfo['path'];
		// 	}

		// 	$retInfo['bindauthoritys'] = array_unique($temp);
		// } else {
		// 	$retInfo['bindauthoritys'] = array();
		// }
	}
}
