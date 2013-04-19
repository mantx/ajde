<?php 

class AdminSetupController extends AdminController
{	
	public function nodes()
	{
		Ajde_Model::register($this);
		
		Ajde::app()->getDocument()->setTitle("Setup nodes");
		return $this->render();
	}
	
	public function meta()
	{
		Ajde_Model::register($this);
		
		Ajde::app()->getDocument()->setTitle("Setup fields");
		return $this->render();
	}
	
	public function menus()
	{
		Ajde_Model::register($this);
		
		Ajde::app()->getDocument()->setTitle("Setup menus");
		return $this->render();
	}
}