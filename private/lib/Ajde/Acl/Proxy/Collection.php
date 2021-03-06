<?php

class Ajde_Acl_Proxy_Collection extends Ajde_Collection
{
	public $ignoreAccessControl = false;
	public $autoRedirect = true;
	
	public function getAclParam()
	{
		return '';
	}
		
	private function validateModels($clean = true) {	
		if ($this->ignoreAccessControl === true) {
			return true;
		}
		$newItems = array();
		foreach($this as $key => $item) {
			/* @var $item Ajde_Acl_Proxy_Model */
			if (!$item->validateAccess('read', false)) {
				if ($clean) {
					// No. Instead, add validated item to newItems array. 
					// Unsetting an internal Iterator array fucks up the indexes
					// unset($this->_items[$key]);
				} else {
					if ($this->autoRedirect == true) {
						$this->validationErrorRedirect();
					}
				}
			} else {
				$newItems[] = $item;
			}
		}
		$this->_items = $newItems;
		$this->rewind();
	}
	
	private function validationErrorRedirect()
	{
		Ajde::app()->getRequest()->set('message', __('You may not have the required permission to view this resource'));
		Ajde::app()->getResponse()->dieOnCode(Ajde_Http_Response::RESPONSE_TYPE_UNAUTHORIZED);
	}
	
	public function load() {
		parent::load();
		if ($this->count()) {
			$this->validateModels();
		}
		return $this->_items;		
	}
}