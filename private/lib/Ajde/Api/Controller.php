<?php 

abstract class Ajde_Api_Controller extends Ajde_Acl_Controller
{		
	public function beforeInvoke($allowed = array()) {
		Ajde_Model::register('user');
		
		$token = Ajde::app()->getRequest()->getParam('token', '');
		$user = new UserModel();
		
		list($uid, $hash) = explode(':', $token);
		
		if ($user->loadByPK($uid)) {
			if ($user->getCookieHash(false) === $hash) {
				$user->login();
				return parent::beforeInvoke($allowed);
			}
		}
		
		Ajde::app()->getRequest()->set('message', __('You may not have the required permission to view this page'));
		Ajde::app()->getResponse()->dieOnCode(Ajde_Http_Response::RESPONSE_TYPE_UNAUTHORIZED);
	}
}