<?php
class AgentModelOp{
	
	private $_db_hummer;
	private $_db_dev;
	private $tableBaseAgent;
	private $tableUserAgent;
	public function __construct(){
			$this->_db_hummer = bdDBProxyWrapper::getInstance(DB_FOR_LIGHT_STATISTICS);
	// 		$this->_db_hummer = bdDBProxyWrapper::getInstance(DB_FOR_LIGHTAPP_HUMMER);	
			$this->_db_dev = bdDBProxyWrapper::getInstance(DB_FOR_DEVELOPER_PLATFORM,true);
			$this->tableBaseAgent = "lightapp_at_agent";
			$this->tableUserAgent = "op_user_agent";
		}
	public function agentRelationDelete($userName){
		$result = false;
		if(!empty($userName)){
			$sql_user_agent = "delete from `op_user_agent` where `user_name` ='{$userName}'";
			$result = $this->_db_hummer->doTransaction(array($sql_user_agent));
		}
		return $result;
	}
	public function agentRelationAdd($userName,$agentId){
		$userName = $this->checkInput($userName);
		$agentId = $this->checkInput($agentId);
	//	$ret = array();
		$value = array(
			'user_name'=> $userName,
			'agent_id'=> $agentId
		);
		//print_r($value);
		if(empty($agentId)){
			//传过来的服务商id是空，说明不对用户分配服务商了。那么就不插入了
			return true;
		}
		$result = $this->_db_hummer->insert($value,$this->tableUserAgent);
		//$sql = "insert into op_user_agent('user_name','agent_id') values('{$userName}','{$agentId}')";
		//$result = $this->_db_hummer->queryAllRows($sql);
		//echo $result;
		return $result;
		
	}
	public function agentQueryByUserName($userName){
		
		$data = array();
		//查询符合条件的用户-服务商关联表的记录
		$sqlUserAgentHead = "select user_name,agent_id from {$this->tableUserAgent} ";
		$sqlBaseAgentHead = "select agent_name,agent_id,dev_uid from {$this->tableBaseAgent} ";
		$sqlUserAgentId = $sqlUserAgentHead;
		$sqlBaseAgentId = $sqlBaseAgentHead;
		$sqlUserAgent = $sqlUserAgentHead;
		$sqlBaseAgent = $sqlBaseAgentHead;
		$retUserAgent = array();
		$retBaseAgentId = array();
		if(isset($userName)){
			
			if(!is_array($userName)){
				$userName = $this->checkInput($userName);
				$userName = array($userName);
			}
			//var_dump($userName);
			$userNameStr = '';
			//var_dump(empty($userName));
			if(!empty($userName)){
				$userNameStr = "('".implode("','",$userName)."')";
			}
			echo $userNameStr;
			
			$userAgentCondition='';
			if(!empty($userNameStr)){
				$userAgentCondition .= " user_name in {$userNameStr} ";	
				$sqlUserAgent .= " where {$userAgentCondition}";
				
			}
		}
		$retUserAgent = $this->_db_hummer->queryAllRows($sqlUserAgent);
		$userAgentId=array();
		if($retUserAgent){
			foreach($retUserAgent as $userAgent){
				$userAgentId[]=$userAgent['agent_id'];
			}
		}
		
		$agentId = array_unique($userAgentId);
		if($agentId){
			$userAgentArr = array();
			$baseAgentArr = array();
			$agentCondition = "('".implode("','",$agentId)."')";
			$sqlBaseAgent .= " where agent_id in {$agentCondition} ";
			//echo $sqlBaseAgent;
			$retBaseAgent = $this->_db_dev->queryAllRows($sqlBaseAgent);
			if($retUserAgent){
				foreach($retUserAgent as $userAgent){
					$userAgentArr[$userAgent['user_name']][]=$userAgent['agent_id'];
				}
			}
			if(!empty($retBaseAgent)){
				foreach($retBaseAgent as $baseAgent){
					$baseAgentArr[$baseAgent['agent_id']]= array('agent_name'=>$baseAgent['agent_name'],'dev_uid'=>$baseAgent['dev_uid']);
				}
			}
			if($userAgentArr){
				foreach($userAgentArr as $userName=>$agentIds){
					$userAgentData = array();
					foreach($agentIds as $agentId){
						if(array_key_exists($agentId,$baseAgentArr)){
							$agent=array();
							$agent['agentId']=$agentId;
							$agent['agentName']=$baseAgentArr[$agentId]['agent_name'];
							$agent['devUid']=$baseAgentArr[$agentId]['dev_uid'];
							$userAgentData[$userName][]=$agent;
						}
					}
					
					$data[] = $userAgentData;
				}
			}
			
			return $data;
		}else {
			return null;
		}
	}
	
	public function getAllAgent(){
		$sql = "select agent_name,agent_id,dev_uid from {$this->tableBaseAgent} ";
		$ret = $this->_db_dev->queryAllRows($sql);
		
		return $ret;
	}
	public function getAllRelationAgentId(){
		$sqlUserAgentId = "select distinct agent_id from op_user_agent";
		$ret = $this->_db_hummer->queryAllRows($sqlUserAgentId);
		return $ret;
	}
	public function userAgentQryByKey($key){
		$key = $this->checkInput($key);
		$sql = "select user_name,agent_id from {$this->tableUserAgent} where user_name like '%{$key}%' or agent_id like '%{$key}%'";
		echo 'userAgentQryByKey:'.$sql;
		$ret = $this->_db_hummer->queryAllRows($sql);
		return $ret;
	}
	public function userAgentQryByAgentId($agentIds){
		
		$sql = "select user_name,agent_id from {$this->tableUserAgent} where agent_id in {$agentIds}";
		echo 'userAgentQryByAgentId:'.$sql;
		$ret = $this->_db_hummer->queryAllRows($sql);
		return $ret;
		
	}
	public function baseAgentNameQryByKey($key,$agentIds){
		$key = $this->checkInput($key);
		$sql = "select agent_name,agent_id from {$this->tableBaseAgent} where agent_name like '%{$key}%' and agent_id in {$agentIds} ";
		echo 'baseAgentNameQryByKey:'.$sql;
		$ret = $this->_db_dev->queryAllRows($sql);
		return $ret;
	}
	public function userAgentQuery($conditionArr){
		$data = array();
		$userName = $this->checkInput($conditionArr['userName']);
		$key = $this->checkInput($conditionArr['key']);
		//echo $userName;
		//echo $key;
	    $userAgentCondition='';
	    $baseAgentCondition='';
		//查询符合条件的用户-服务商关联表的记录
		$sqlUserAgentHead = "select user_name,agent_id from {$this->tableUserAgent} ";
		$sqlBaseAgentHead = "select agent_name,agent_id,dev_uid from {$this->tableBaseAgent} ";
		$sqlUserAgentId = $sqlUserAgentHead;
		$sqlBaseAgentId = $sqlBaseAgentHead;
		$sqlUserAgent = $sqlUserAgentHead;
		$sqlBaseAgent = $sqlBaseAgentHead;
		$retUserAgentId = array();
		$retBaseAgentId = array();
		if(!empty($userName)){
			$userAgentCondition .= " user_name = '{$userName}' ";	
			$sqlUserAgentId .= " where {$userAgentCondition}";
			$retUserAgentId = $this->_db_hummer->queryAllRows($sqlUserAgentId);
		}
		if(!empty($key)){
			if(!empty($userAgentCondition)){
				$userAgentCondition.=" and ";
			}
			$userAgentCondition.=" (user_name like '%{$key}%' or agent_id like '%{$key}%')";
			$baseAgentCondition.=" (agent_name like '%{$key}%' or agent_id like '%{$key}%')";
			$sqlBaseAgentId .= "where {$baseAgentCondition} ";
			$retUserAgentId = array_merge($retUserAgentId,$this->_db_hummer->queryAllRows($sqlUserAgentId));
			$retBaseAgentId = $this->_db_dev->queryAllRows($sqlBaseAgentId);
			
		} 
		if(empty($userName)&& empty($key)){
			$retUserAgentId = $this->_db_hummer->queryAllRows($sqlUserAgentId);
			$retBaseAgentId = $this->_db_dev->queryAllRows($sqlBaseAgentId);
		}
		
		//echo 'sqlUserAgentId:['.$sqlUserAgentId.'] sqlBaseAgent:['.$sqlBaseAgent.']';
		
		
		$userAgentId=array();
		$baseAgentId=array();
		if($retUserAgentId){
			foreach($retUserAgentId as $userAgent){
				$userAgentId[]=$userAgent['agent_id'];
			}
		}
		if($retBaseAgentId){
			foreach($retBaseAgentId as $baseAgent){
				$baseAgentId[]=$baseAgent['agent_id'];
			}
		}
		$agentId = array_unique(array_merge($userAgentId,$baseAgentId));
		//print_r($agentId);
		if($agentId){
			$userAgentArr = array();
			$baseAgentArr = array();
			$agentCondition = "('".implode("','",$agentId)."')";
			$sqlUserAgent .= " where agent_id in {$agentCondition} ";
			$sqlBaseAgent .= " where agent_id in {$agentCondition} ";
			//echo 'sqlUserAgent1:['.$sqlUserAgent.'] sqlBaseAgent:['.$sqlBaseAgent.']';
			//die();
			$retUserAgent = $this->_db_hummer->queryAllRows($sqlUserAgent);
			$retBaseAgent = $this->_db_dev->queryAllRows($sqlBaseAgent);
/*			echo 'userAgent:';
			print_r($retUserAgent);
			echo 'baseAgent';
			print_r($retBaseAgent);*/
			if($retUserAgent){
				foreach($retUserAgent as $userAgent){
					$userAgentArr[$userAgent['agent_id']]=$userAgent['user_name'];
				}
			}
			if(!empty($retBaseAgent)){
				foreach($retBaseAgent as $baseAgent){
					$baseAgentArr[$baseAgent['agent_id']]= array('agent_name'=>$baseAgent['agent_name'],'dev_uid'=>$baseAgent['dev_uid']);
				}
			}
			
			if($baseAgentArr){
				foreach($baseAgentArr as $id=>$agent){
					
					$data[] = array('user_name'=>array_key_exists($id,$userAgentArr)? $userAgentArr[$id]:'','agent_id'=>$id,'agent_name'=>$agent['agent_name'],'dev_uid'=>$agent['dev_uid']);
				}
			}
			
			return $data;
		}else {
			return null;
		}
	}
	
	public function checkInput($input){
		if(!empty($input)){
			// 去除斜杠
			if (get_magic_quotes_gpc())
			  {
			  $input = stripslashes($input);
			  }
			// 如果不是数字则加引号
			if (!is_numeric($input))
			  {
			  $input =  $this->_db_hummer->realEscapeString($input);
			  }
		}
		return $input;

	}
}


