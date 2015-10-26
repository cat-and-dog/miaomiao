<?php
class Openapi_AgentOp extends BaseController{
	private $agentModel;
	public function _init(){

			$this->agentModel = new AgentModelOp();
		}
		
		public function userAgentAdd(){
		$userName=$this->getParams('userName');
		$agentIds=$this->getParams('agentIds');
		
		if(!is_array($agentIds)){
			$agentIds = array($agentIds);
		}
		//先删再插
		//$delRet = $this->agentModel->agentRelationDelete($userName);
		$insertRet = array();
		//if($delRet){
			
			if($agentIds){
				foreach($agentIds as $agentId){
					$result = $this->agentModel->agentRelationAdd($userName,$agentId);
					if($result){
						$insertRet[]=$result;
					}
				}
			}
		//}
		if($insertRet){
			$ret['success']=0;
			$ret['info']='';
			$ret['affectedRows']=count($insertRet);
		}else{
			if(empty($agentIds)){
				$ret['success']=0;
				$ret['info']='';
				$ret['affectedRows']=count($insertRet);
			}
			else{
				$ret['success']=1;
				$ret['info']='保存失败！';
				$ret['affectedRows']=count($insertRet);
			}
		}
		
		if($ret) {
				$json = json_encode($ret);//, true
				echo $json;
				exit;
			} else {
				print_r($ret);
				exit();
			}
	}
	
	public function userAgentSave(){
		$userName=$this->getParams('userName');
		$agentIds=$this->getParams('agentIds');
		
		if(!is_array($agentIds)){
			$agentIds = array($agentIds);
		}
		//先删再插
		$delRet = $this->agentModel->agentRelationDelete($userName);
		$insertRet = array();
		if($delRet){
			
			if($agentIds){
				foreach($agentIds as $agentId){
					$result = $this->agentModel->agentRelationAdd($userName,$agentId);
					if($result){
						$insertRet[]=$result;
					}
				}
			}
		}
		if($insertRet){
			$ret['success']=0;
			$ret['info']='';
			$ret['affectedRows']=count($insertRet);
		}else{
			if(empty($agentIds)){
				$ret['success']=0;
				$ret['info']='';
				$ret['affectedRows']=count($insertRet);
			}
			else{
				$ret['success']=1;
				$ret['info']='保存失败！';
				$ret['affectedRows']=count($insertRet);
			}
		}
		
		if($ret) {
				$json = json_encode($ret);//, true
				echo $json;
				exit;
			} else {
				print_r($ret);
				exit();
			}
	}
	
	public function agentQueryByUserName(){
		$ret =array();
		$userName=$this->getParams('userName');
		
		$curPage = $this->getParams('pn');//当前页
		$offset = $this->getParams('ps');//每页记录数
		$order       = $this->getParams('order', null, 0);//排序，0，倒序，1，升序，默认0
		
		
		$data = $this->agentModel->agentQueryByUserName($userName);
		if($data){
			$ret['success']=1;
			$ret['info']='';
			$ret['total']=count($data);
			$ret['pn']=$curPage;
			$ret['ps']=$offset;
			$ret['data']=$data;
		}else{
			$ret['success']=0;
			$ret['info']='没有找到相关数据';
			$ret['total']=0;
			$ret['pn']=$curPage;
			$ret['ps']=$offset;
			$ret['data']=$data;
		}
		
		if($ret) {
				$json = json_encode($ret);//, true
				echo $json;
				exit;
			} else {
				print_r($ret);
				exit();
			}
	}
	public function getAllAgent(){
		$curPage = $this->getParams('pn');//当前页
		$offset = $this->getParams('ps');//每页记录数
		$order       = $this->getParams('order', null, 0);//排序，0，倒序，1，升序，默认0
		$result = $this->agentModel->getAllAgent();
		$data=array();
		if($result){
			foreach($result as $v){
				$list=array();
				$list['agentId']=$v['agent_id'];
				$list['agentName']=$v['agent_name'];
				//$list['devUid']=$v['dev_uid'];
				$data[]=$list;
			}
		}
		
		
		if($data){
			$ret['success']=0;
			$ret['info']='';
			$ret['total']=count($data);
			$ret['pn']=$curPage;
			$ret['ps']=$offset;
			$ret['data']=$data;
		}else{
			$ret['success']=1;
			$ret['info']='没有找到相关数据';
			$ret['total']=0;
			$ret['pn']=$curPage;
			$ret['ps']=$offset;
			$ret['data']=$data;
		}
		
		if($ret) {
				$json = json_encode($ret);//, true
				echo $json;
				exit;
			} else {
				print_r($ret);
				exit();
			}
	}
	
	// A:  userName  agentId

	// B:	agentId agentName 

	// select * from a where userName like '%queryStr%'

	// select agentId from B where agentId like  '%%' or agentName like '%%';

	// select userName,agentId from A where userName like '%%' or agentId in ();

	// select agentId,agentName from B where agentId in ();

	public function userAgentQryByKey(){
		$key=$this->getParams('key');
		$userNames = array();
		$agentIds = array();
		//1.查出已有归属关系的服务商的id集，用于查其对应的名称
		$agentIdsRet = $this->agentModel->getAllRelationAgentId();
		if($agentIdsRet){
			foreach($agentIdsRet as $v){
				$agentIds[]= $v['agent_id'];
			}
			
		}
		$agentIdsStr = "('".implode("','",$agentIds)."')";
		
		//2.查agentName服务关键字的服务商信息表的记录
		$agentRet  = $this->agentModel->baseAgentNameQryByKey($key,$agentIdsStr);
		$selectAgentIds = array();
		if($agentRet){
			foreach($agentRet as $v){
				$selectAgentIds[] = $v['agent_id'];
			}
		}
		$selectAgentIdsStr = "('".implode("','",$selectAgentIds)."')";
		echo $selectAgentIdsStr;
		//die();
		//3.根据服务商名称匹配的服务商id查询归属信息
		$userAgentRet = $this->agentModel->userAgentQryByAgentId($selectAgentIdsStr);
		if($userAgentRet){
			foreach($userAgentRet as $v){
				$userNames[]=$v['user_name'];
			}
		}
		//4.查userName，agentId符合关键字的服务商归属表的记录
		$userAgentRet1 = $this->agentModel->userAgentQryByKey($key);
		
		if($userAgentRet1){
			foreach($userAgentRet1 as $v ){
				$userNames[] = $v['user_name'];
			}
		}

		$userNames = isset($userNames)?array_unique($userNames):array();
		var_dump($userNames);
		$data = array();
	
			
			$data = $this->agentQueryByUserName($userNames);
		
		if($data){
			$ret['success']=1;
			$ret['info']='';
			$ret['total']=count($data);
			$ret['pn']=$curPage;
			$ret['ps']=$offset;
			$ret['data']=$data;
		}else{
			$ret['success']=0;
			$ret['info']='没有找到相关数据';
			$ret['total']=0;
			$ret['pn']=$curPage;
			$ret['ps']=$offset;
			$ret['data']=$data;
		}
		
		if($ret) {
				$json = json_encode($ret);//, true
				echo $json;
				exit;
			} else {
				print_r($ret);
				exit();
			}
		
		
	}
	
	public function userAgentQuery(){
		$ret =array();
		$userName=$this->getParams('userName');
		$key=$this->getParams('key');
		$curPage = $this->getParams('pn');//当前页
		$offset = $this->getParams('ps');//每页记录数
		$order       = $this->getParams('order', null, 0);//排序，0，倒序，1，升序，默认0
		$conditionArr = array(
		'userName'=>$userName,
		'key'=>$key
		);
		
		$data = $this->agentModel->userAgentQuery($conditionArr);
		if($data){
			$ret['success']=1;
			$ret['info']='';
			$ret['total']=count($data);
			$ret['pn']=$curPage;
			$ret['ps']=$offset;
			$ret['data']=$data;
		}else{
			$ret['success']=0;
			$ret['info']='没有找到相关数据';
			$ret['total']=0;
			$ret['pn']=$curPage;
			$ret['ps']=$offset;
			$ret['data']=$data;
		}
		
		if($ret) {
				$json = json_encode($ret);//, true
				echo $json;
				exit;
			} else {
				print_r($ret);
				exit();
			}
	}
	
	
}



