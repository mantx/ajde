<?php

/**
 * Default to extend Ajde_Acl_Controller for enhanced security
 */
class _coreCrudController extends Ajde_Acl_Controller
{
	/************************
	 * Ajde_Component_Crud
	 ************************/
	
	public function beforeInvoke() {
		$crud = $this->getCrudInstance();
		if (!$crud && Ajde::app()->getRequest()->has('crudId')) {
			Ajde_Model::registerAll();
			$session = new Ajde_Session('AC.Crud');			
			$crud = $session->getModel(Ajde::app()->getRequest()->getParam('crudId'));
		}
		if ($crud) {
			/* @var $crud Ajde_Crud */
			$this->setAclParam($crud->getSessionName());
		}
		return parent::beforeInvoke();
	}
	
	public function listHtml()
	{
		$cache = Ajde_Cache::getInstance();
		$cache->disable();
		
		if (Ajde::app()->getRequest()->has('edit') || Ajde::app()->getRequest()->has('new')) {
			return $this->editDefault();			
		}
		
		if (Ajde::app()->getRequest()->has('output') && Ajde::app()->getRequest()->get('output') == 'table') {
			Ajde::app()->getDocument()->setLayout(new Ajde_Layout('empty'));
		}
				
		$crud = $this->getCrudInstance();
		/* @var $crud Ajde_Crud */
		if (!$crud) {
			Ajde::app()->getResponse()->redirectNotFound();
		}		
				
		$session = new Ajde_Session('AC.Crud');
		$session->setModel($crud->getHash(), $crud);
		
		$crud->loadCollectionView();
		
		$view = $crud->getTemplate();		
		$view->assign('crud', $crud);
		
		return $view->render();
	}
	
	public function editDefault()
	{		
		$this->setAction('edit');
		
		$crud = $this->getCrudInstance();
		/* @var $crud Ajde_Crud */
		if (!$crud) {
			Ajde::app()->getResponse()->redirectNotFound();
		}
		
		$editOptions = $crud->getOptions('edit');
		if ($crud->getOperation() === 'list') {
			if (!empty($editOptions) &&
					isset($editOptions['action'])) {
				$crud->setAction($editOptions['action']);
			} else {
				if ($crud->getOption('edit.layout')) {
					$crud->setAction('edit/layout');
				} else {
					$crud->setAction('edit');
				}
			}
		}
		
		if (!$crud->hasId()) {
			$crud->setId(Ajde::app()->getRequest()->getParam('edit', false));
		}
		
		$disable = Ajde::app()->getRequest()->getParam('disable', array());
		$hide = Ajde::app()->getRequest()->getParam('hide', array());
		$prefill = Ajde::app()->getRequest()->getParam('prefill', array());
		foreach($prefill as $field => $value) {
			$crud->setOption('fields.' . $field . '.value', $value);			
		}
		foreach($disable as $field => $value) {
			if ($value) {
				$crud->setOption('fields.' . $field . '.readonly', true);	
			}
		}
		foreach($hide as $field => $value) {
			if ($value) {
				$crud->setOption('fields.' . $field . '.hidden', true);	
			}
		}
		
		// Hide mainfilter fields	
		$crudView = $crud->loadCollectionView();
		$mainFilter = $crudView->getMainFilter();
		if ($mainFilter) {
			$currentFilter = $crudView->getFilterForField($mainFilter);
			$crud->setOption('fields.' . $mainFilter . '.value', $currentFilter);	
			$crud->setOption('fields.' . $mainFilter . '.hidden', true);	
		}
		
		// Reload Crud fields in case they were already loaded
		$crud->loadFields();
			
		$session = new Ajde_Session('AC.Crud');
		$session->setModel($crud->getHash(), $crud);
		
		$view = $crud->getTemplate();		
		
		$view->requireJsFirst('component/shortcut', 'html', MODULE_DIR . '_core/');		
		$view->assign('crud', $crud);
		
		// Editor
		if (Config::get('textEditor')) {
			$editorClassName = "Ajde_Crud_Editor_" . ucfirst(Config::get('textEditor'));
			$textEditor = new $editorClassName();
			/* @var $textEditor Ajde_Crud_Editor */
			$textEditor->getResources($view);
		}
		
		return $view->render();
	}
	
	public function mainfilterHtml()
	{
		$crud = $this->getCrud();
		$this->getView()->assign('crud', $crud);
		return $this->render();
	}
	
	public function commitJson()
	{
		$operation = Ajde::app()->getRequest()->getParam('operation');
		$crudId = Ajde::app()->getRequest()->getParam('crudId');
		$id = Ajde::app()->getRequest()->getPostParam('id', false);
		
		Ajde_Model::registerAll();
		
		switch ($operation) {
			case 'delete':
				return $this->delete($crudId, $id);
				break;
			case 'save':
				return $this->save($crudId, $id);
				break;
			case 'sort':
				return $this->sort($crudId, $id);
				break;
			case 'deleteMultiple':
				return $this->deleteMultiple($crudId, $id);
				break;
			case 'addMultiple':
				return $this->addMultiple($crudId, $id);
				break;
			case 'getMultipleRow':
				return $this->getMultipleRow($crudId);
				break;
			default:
				return array('operation' => $operation, 'success' => false);
				break;
		}
	}
	
	private function delete($crudId, $id)
	{
		$session = new Ajde_Session('AC.Crud');		
		$crud = $session->getModel($crudId);
		$model = $crud->getModel();
		
		if (!is_array($id)) {
			$id = array($id);
		}
		
		$success = true;
		foreach($id as $elm) {
			$model->loadByPK($elm);
			$success = $success * $model->delete();
		}
		
		return array(
			'operation' => 'delete',
			'success' => (bool) $success,
			'message' => Ajde_Component_String::makePlural(count($id), 'record') . ' deleted'
			);
	}
	
	private function sort($crudId, $id)
	{
		$session = new Ajde_Session('AC.Crud');
		/* @var $crud Ajde_Crud */
		$crud = $session->getModel($crudId);
		$model = $crud->getModel();
		
		// Extra careful handling of parameters, as we are baking crude SQL here
		
		// Get and validate sort field
		$sortField = Ajde::app()->getRequest()->getPostParam('field');
		$field = $crud->getField($sortField); // throws exception when not found
		
		if (!is_array($id)) {
			$id = array($id);
		}
		
		// Make sure ids is a array of integers
		$ids = array();
		foreach($id as $elm) {
			$ids[] = (int) $elm;
		}
		
		// Get lowest current sort values
		$idString = implode(', ', $ids);
		$sql = "SELECT MIN(" . $sortField . ") AS min FROM " . (string) $model->getTable() . " WHERE " . $model->getTable()->getPK() . " IN (" . $idString . ")";
		
		$statement = $model->getConnection()->prepare($sql);
		$statement->execute();
		$result = $statement->fetch(PDO::FETCH_ASSOC);	
		if ($result === false || empty($result)) {
			$sortValue = 0;
		} else {
			$sortValue = $result['min'];
		}
		
		$success = true;
		foreach($ids as $id) {
			$model->loadByPK($id);
			$model->set($sortField, $sortValue);		
			$success = $success * $model->save();
			$sortValue++;
			if ($field->has('sort_children')) {
				// TODO: implement parent recursive sorting
			}
		}

		// Call afterSort once on model
		if (method_exists($model, 'afterSort')) {
			$model->afterSort();
		}
		
		return array(
			'operation' => 'sort',
			'success' => (bool) $success,
			'message' => 'Records sorted'
		);
	}
	
	private function save($crudId, $id)
	{
		$session = new Ajde_Session('AC.Crud');		
		/* @var $crud Ajde_Crud */
		$crud = $session->getModel($crudId);
		/* @var $model Ajde_Model */
		$model = $crud->getModel();		
		$model->setOptions($crud->getOptions('model'));
		
		// Get POST params
		$post = $_POST;
		foreach($post as $key => $value) {
			// Include empty values, so we can set them to null if the table structure allows us
//			if (empty($value)) {
//				unset($post[$key]);
//			}
		}
		$id = issetor($post["id"]);
		$operation = empty($id) ? 'insert' : 'save';
		
		if ($operation === 'save') {
			$model->loadByPK($id);
		}
		$model->populate($post);
		
		Ajde_Event::trigger($model, 'beforeCrudSave', array($crud));
		
		if (!$model->validate($crud->getOptions('fields'))) {
			return array('operation' => $operation, 'success' => false, 'errors' => $model->getValidationErrors());
		}
//		if (!$model->autocorrect($crud->getOptions('fields'))) {
//			return array('operation' => $operation, 'success' => false, 'errors' => $model->getAutocorrectErrors());
//		}
		
		$success = $model->{$operation}();
		
		if ($success === true) {
			// Destroy reference to crud instance
			$session->destroy($crudId);
			
			if (Ajde::app()->getRequest()->getParam('fromIframe', '0') != 1) {
				// Set flash alert
				Ajde_Session_Flash::alert('Record ' . ($operation == 'insert' ? 'added' : 'saved'));
			}
		}
		return array(
			'operation' => $operation,
			'id' => $model->getPK(),
			'displayField' => $model->get($model->getDisplayField()),
			'success' => $success);
	}
	
	private function deleteMultiple($crudId, $id)
	{
		$session = new Ajde_Session('AC.Crud');		
		/* @var $crud Ajde_Crud */
		$crud = $session->getModel($crudId);
		/* @var $model Ajde_Model */
		$model = $crud->getModel();
		
		$parentId = Ajde::app()->getRequest()->getPostParam('parent_id');
		$fieldName = Ajde::app()->getRequest()->getPostParam('field');
		
		// Get field properties
		$fieldProperties = $crud->getOption('fields.' . $fieldName);
		
		$success = false;
		$modelName = $crud->getOption('fields.' . $fieldName . '.modelName', $fieldName);
		if (isset($fieldProperties['crossReferenceTable'])) {
			$parentField = (string) $model->getTable();
			$sql = 'DELETE FROM '.$fieldProperties['crossReferenceTable'].' WHERE '.$parentField.' = ? AND '.$modelName.' = ? LIMIT 1';
			$statement = $model->getConnection()->prepare($sql);
			$success = $statement->execute(array($parentId, $id));			
		} else {
			$childClass = ucfirst($modelName) . 'Model';
			$child = new $childClass();
			/* @var $child Ajde_Model */
			$child->loadByPK($id);
			$success = $child->delete();
		}
						
		return array(
			'operation' => 'deleteMultiple',			
			'success' => $success,
			'message' => ucfirst($fieldName) . (isset($fieldProperties['crossReferenceTable']) ? ' disconnected' : ' deleted')
		);
	}
	
	private function addMultiple($crudId, $id)
	{
		$session = new Ajde_Session('AC.Crud');		
		/* @var $crud Ajde_Crud */
		$crud = $session->getModel($crudId);
		/* @var $model Ajde_Model */
		$model = $crud->getModel();
		
		$parentId = Ajde::app()->getRequest()->getPostParam('parent_id');
		$fieldName = Ajde::app()->getRequest()->getPostParam('field');
		
		// Get field properties
		$fieldProperties = $crud->getOption('fields.' . $fieldName);
		
		$success = false;
		$modelName = $crud->getOption('fields.' . $fieldName . '.modelName', $fieldName);
		if (isset($fieldProperties['crossReferenceTable'])) {
			
			// Already in there?
			$parentField = (string) $model->getTable();
			$sql = 'SELECT * FROM '.$fieldProperties['crossReferenceTable'].' WHERE '.$parentField.' = ? AND '.$modelName.' = ? LIMIT 1';
			$statement = $model->getConnection()->prepare($sql);
			$success = $statement->execute(array($parentId, $id));			
			$result = $statement->fetch(PDO::FETCH_ASSOC);	
			if ($success === true && $result !== false && !empty($result)) {
				return array(
					'operation' => 'addMultiple',			
					'success' => false,
					'message' => ucfirst($fieldName) . ' already connected'
				);
			}			
			
			$sql = 'INSERT INTO '.$fieldProperties['crossReferenceTable'].' ('.$parentField.', '.$modelName.') VALUES (?, ?)';
			$statement = $model->getConnection()->prepare($sql);
			$success = $statement->execute(array($parentId, $id));			
		} else {
			// Not possible
		}
						
		return array(
			'operation' => 'addMultiple',			
			'success' => $success,
			'message' => ucfirst($fieldName) . ' added'
		);
	}
	
	private function getMultipleRow($crudId)
	{
		$session = new Ajde_Session('AC.Crud');		
		/* @var $crud Ajde_Crud */
		$crud = $session->getModel($crudId);
		/* @var $model Ajde_Model */
		$model = $crud->getModel();
		
		// Get field properties
		$id = Ajde::app()->getRequest()->getParam('id');
		$fieldName = Ajde::app()->getRequest()->getParam('field');
		$fieldProperties = $crud->getOption('fields.' . $fieldName);
		$modelName = $crud->getOption('fields.' . $fieldName . '.modelName', $fieldName);
		
		// Get child model
		$className = ucfirst($modelName) . 'Model';		
		$child = new $className;	
		/* @var $child Ajde_Model */
		$child->loadByPK($id);
		
		$ret = array();
		if (isset($fieldProperties['tableFields'])) {
			foreach ($fieldProperties['tableFields'] as $extraField) { 
				$value	= $child->get($extraField['name']);
				$type	= $extraField['type'];
				if ($type == 'file' && $value) {
					$extension = pathinfo($value, PATHINFO_EXTENSION);
						if ($isImage = in_array(strtolower($extension), array('jpg', 'jpeg', 'png', 'gif'))) {
							$thumbDim = isset($fieldProperties['thumbDim']) ? $fieldProperties['thumbDim'] : array('width' => 75, 'height' => 75);
							$html = "<a class='imagePreview img' title='" . _e($value) . "' href='" . $extraField['saveDir'] . $value . "' target='_blank'>";
							$image = new Ajde_Resource_Image($extraField['saveDir'] . $value);
							$image->setWidth($thumbDim['width']);
							$image->setHeight($thumbDim['height']);
							$image->setCrop(true);							
							$html = $html . "<img src='" . $image->getLinkUrl() . "' width='" . $thumbDim['width'] . "' height='" . $thumbDim['height'] . "' />";
							$html = $html . "</a>";
						} else {
							$html = "<img class='icon' src='" . Ajde_Resource_FileIcon::_($extension) . "' />";
							$html = $html . " <a class='filePreview preview' href='" . $extraField['saveDir'] . $value . "' target='_blank'>" . $value . "</a>";
						}
				 } else {
					$html = $value;
				 }
				 $ret[] = $html;
			}
		}
		
		$success = true;
		
		return array(
			'operation' => 'getMultipleRow',			
			'success' => $success,
			'data' => $ret
		);
	}
}