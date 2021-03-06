<?php

class Ajde_Cms extends Ajde_Object_Singleton
{
	public static function getInstance()
	{
		static $instance;
		return $instance === null ? $instance = new self : $instance;
	}
	
	protected function __construct()
	{
		Ajde_Model::registerAll();
	}
	
	public function __bootstrap()
	{
		Ajde_Event::register('Ajde_Application', 'onAfterRequestCreated', array($this, 'setHomepage'));
		return true;
	}
	
	public function setHomepage()
	{
		$homepageNodeId = (int) SettingModel::byName('homepage');
		
		if ($homepageNodeId) {
			$node = NodeModel::fromPk($homepageNodeId);
			if ($node) {
				Config::getInstance()->homepageRoute = $node->getUrl();
			}
		}
	}
}