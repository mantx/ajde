<?php 

abstract class Ajde_Acl_Controller extends Ajde_User_Controller
{		
	protected $_registerAclModels = array('acl');
	
	protected $_allowedActions = array();
	
	/* ACL sets this to true or false to grant/prevent access in beforeInvoke() */
	private $_hasAccess;
	
	public function beforeInvoke($allowed = array())
	{
		foreach($this->_registerAclModels as $model) {
			Ajde_Model::register($model);
		}
		if (!in_array($this->getAction(), array_merge($this->_allowedActions, $allowed)) && $this->hasAccess() === false) {
			Ajde::app()->getRequest()->set('message', __('You may not have the required permission to view this page'));
			Ajde::app()->getResponse()->dieOnCode(Ajde_Http_Response::RESPONSE_TYPE_UNAUTHORIZED);
		} else {
			return true;
		}
	}
	
	protected function getOwnerId()
	{
		return false;
	}
	
	protected function getAclParam()
	{
		return parent::getAclParam();
	}
	
	protected function setAclParam($param)
	{
		parent::setAclParam($param);
	}
	
	private function getAclConditions()
	{
		$module = $this->getModule();
		$action = $this->getAction();
		$param = $this->hasAclParam() ? $this->getAclParam() : '';
		$controller = $this->getRoute()->hasController() ? $this->getRoute()->getController() : '';
		$extra = $controller . ($controller && $param ? ':' : '') . $param;
		return array('module' => $module, 'action' => $action, 'extra' => $extra);
	}
	
	public function validateAccess($conditions = null)
	{
		if (!isset($conditions)) {
			$conditions = $this->getAclConditions();
		}		
		return Ajde_Acl::validateController($conditions['module'], $conditions['action'], $conditions['extra']);
	}
	
	protected function hasAccess()
	{
		if (!isset($this->_hasAccess)) {
			$conditions = $this->getAclConditions();
			$aclTimer = Ajde::app()->addTimer("<i>ACL validation for " . implode('/', $conditions) . "</i>");
			$this->_hasAccess = $this->validateAccess($conditions);
			Ajde::app()->endTimer($aclTimer);
		}
		return $this->_hasAccess;
	}
}