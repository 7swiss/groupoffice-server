<?php

namespace GO\Core\CustomFields\Controller;

use GO\Core\Controller;
use IFW\Orm\Record;
use IFW\Util\ClassFinder;

class ModelController extends Controller{	
	protected function actionGet() {
		
		$customFieldModels = [];
		foreach(\GO()->getModules() as $module) {
			
		
			$classFinder = new ClassFinder();
			$classFinder->setNamespace($module::getNamespace());
			$modelClasses = $classFinder->findByParent(Record::class);
			
			foreach($modelClasses as $modelClass){
				
				if($relation = $modelClass::getRelation('customfields')){
					
					$customFieldModels[] = [
							'id' => $modelClass, 
							'modelName' => $modelClass, 
							'moduleName' => $module, 
							'customFieldsModelName' => $relation->getToRecordName()
					];
				}
			}
		}
		
		$this->render(['data' => $customFieldModels, 'success' => true]);
	}
	
	protected function actionRead($modelName = null, $returnProperties = "") {
		$currentModule = false;
		
		
		foreach(\GO()->getModules() as $module) {
			
			
		
			$classFinder = new ClassFinder();
			$classFinder->setNamespace($module::getNamespace());
			$modelClasses = $classFinder->findByParent(Record::class);
			
			foreach($modelClasses as $modelClass){
				if($relation = $modelClass::getRelation('customfields')){
					
					// find the model
					if($relation->getToRecordName() == $modelName) {
						$currentModule = array(
								'id' => $modelClass, 
								'modelName' => $modelClass, 
								'moduleName' => $module,
								'customFieldsModelName' => $relation->getToRecordName());
					}
					
				}
			}
		}
		
		

		if (!$currentModule) {
			throw new NotFound();
		}

		$this->render(['data' => $currentModule, 'success' => true]);
	}
}
