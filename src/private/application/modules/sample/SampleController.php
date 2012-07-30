<?php 

class SampleController extends Ajde_Acl_Controller
{
	protected $_allowedActions = array(
		'view', 'ajax'
	);
	
	public function beforeInvoke()
	{
		Ajde::app()->getDocument()->setTitle("Samples");
		return parent::beforeInvoke();
	}
		
	public function view()
    {
    	Ajde_Model::register($this);		
		// Direct object creation and chaining only from PHP 5.3!
		// $samples = SampleCollection::create()
		/* @var $samples SampleCollection */
		$samples = new SampleCollection();
		$samples
			->orderBy('sort', Ajde_Query::ORDER_ASC)
			->filter('published', 1);		
		if ($this->hasId()) {
			$samples->addFilter(new Ajde_Filter_Where('id', Ajde_Filter::FILTER_EQUALS, $this->getId()));
		}
		$this->getView()->assign('samples', $samples);
		Ajde_Dump::warn('This is a test warning');		
		Ajde::app()->getDocument()->setDescription("This is the samples module");
        return $this->render();
    }
		
	public function edit()
	{
		Ajde_Model::register($this);
		return $this->render();
	}
	
	public function ajax()
	{
		return $this->view();
	}
	
	public function item()
    {
    	Ajde_Model::register($this);		
		$sample = new SampleModel();
		$sample->loadByPK($this->getId());	
		if (!$sample->hasLoaded()) {
			Ajde_Http_Response::redirectNotFound();
		}
		$this->getView()->assign('sample', $sample);
        return $this->render();
    }
	
	public function distributor()
	{
		Ajde_Model::register($this);
		return $this->render();
	}
	
	public function download()
	{
		Ajde_Model::register($this);
		return $this->render();
	}
	
	public function xml()
	{
		Ajde_Model::register($this);		
		$this->getView()->assign('test', "Hello World!");
		return $this->render();
	}
}