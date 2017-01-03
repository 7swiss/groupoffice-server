<?php
namespace GO\Core;

use Exception;
use IFW\Auth\Exception\LoginRequired;
use IFW\Controller as IFWController;
use IFW\Data\Model;
use IFW\Data\Store;
use IFW\Orm\Record;

class Controller extends IFWController {
	
	/**
	 * Set data to render
	 * 
	 * @var array 
	 */
	protected $responseData = [];
	
	
	private $rendered;
	
	/**
	 * Checks if there's a logged in user
	 * 
	 * @return boolean
	 * @throws LoginRequired
	 */
	protected function checkAccess() {
		
		if(!GO()->getAuth()->isLoggedIn())
		{
			throw new LoginRequired();
		}
		
		return parent::checkAccess();
	}
	
	protected function getDefaultView($interfaceType, $name) {
		
		$view = 'GO\\Core\\View\\'.$interfaceType.'\\'.$name;				
		return $view;
		
//		return parent::getDefaultView($interfaceType, $name);
	}
	
	/**
	 * Helper funtion to render an array into JSON
	 * 
	 * @param array $data
	 * @throws Exception
	 */
	protected function render(array $data = [], $viewName = null) {
		
		$view = $this->getView($viewName);
		$view->render(array_merge($this->responseData, $data));	
		
		$this->rendered[] = $view;
		
		return $view;
	}	

	/**
	 * Used for rendering a model response
	 * 
	 * @param Record $models
	 */
	protected function renderModel(Model $model, $returnProperties = null) {
		
		//For HTTP Caching
		if (GO()->getRequest()->getMethod() == 'GET' && isset($model->modifiedAt)) {
			GO()->getResponse()->setModifiedAt($model->modifiedAt);
			GO()->getResponse()->setEtag($model->modifiedAt->format(\IFW\Util\DateTime::FORMAT_API));
			GO()->getResponse()->abortIfCached();
		}

		$response = ['data' => $model->toArray($returnProperties)];

		//add validation errors even when not requested		
		if(method_exists($model, 'hasValidationErrors')){
			if($model->hasValidationErrors() && !isset($response['data']['validationErrors'])) {
				$response['data']['validationErrors'] = $model->getValidationErrors();
			}		
			$response['success'] = !$model->hasValidationErrors();
		}else
		{		
			$response['success'] = true;
		}
		return $this->render($response);
		
	}
	
	/**
	 * Used for rendering a store response
	 * 
	 * @param Store|array $store
	 */
	protected function renderStore($store) {
		
		if(is_array($store)) {
			$store = new \IFW\Data\Store($store);
		}
		
		$output = $this->render([
				'data' => $store->toArray()
						]);
		
		//generate an ETag for HTTP Caching
		GO()->getResponse()->setETag(md5($output));
		GO()->getResponse()->abortIfCached();
		
		return $output;
	}
	
	protected function callMethodWithParams($methodName, array $routerParams) {
		parent::callMethodWithParams($methodName, $routerParams);
		
		if(GO() instanceof \IFW\Cli\App) {
			foreach($this->rendered as $render) {
				echo $render;
			}
			return $output;
		}
		
		$view = "";
		if(count($this->rendered) > 1) {
			$view .= "[\n";
		}
		
		$view .= implode(",\n\t", $this->rendered);		
		
		if(count($this->rendered) > 1) {
			$view .= "\n]";
		}		
		
		GO()->getResponse()->send($view);
	}
}
