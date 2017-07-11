<?php
namespace GO\Modules\GroupOffice\ApiBrowser\Controller;

use GO\Core\Controller;
use GO\Core\Modules\ModuleUtils;
use ReflectionClass;
use ReflectionMethod;

class BrowseController extends Controller{
	
	
	
	private function _getControllerClasses(){
		
		$appFolders = ModuleUtils::getModuleFolders();
		
		$controllers = array();
		
		foreach($appFolders as $prefix => $moduleFolder){		
			
			$controllerFolder = $moduleFolder->getFolder('Controller');
			
			if($controllerFolder->exists()){
				$files = $controllerFolder->getChildren();
				
				foreach($files as $file){
					
					$className = $prefix."Controller\\". $file->getNameWithoutExtension();
					
					if(class_exists($className)){
						$controllers[]=$className;
					}
				}
			}
		}
		return $controllers;
	}
	
	public function controllers(){
		
		$classes = $this->_getControllerClasses();
		
		$response = array('success'=>true, 'results'=>array());
		
		foreach($classes as $className){
			
			$reflection = new ReflectionClass($className);
			
			if(!$reflection->isAbstract()){
			
				$reflectionMethods = $reflection->getMethods();

				$methods = array();
				foreach($reflectionMethods as $reflectionMethod){

					/* @var $reflectionMethod ReflectionMethod */
					if(substr($reflectionMethod->getName(),0,6)=='action'){
						
						$method = array(
								'name'=>$reflectionMethod->getName(),
								'route'=>$className::getRoute($reflectionMethod->getName()),
								'getParams'=>array()
								);
						
						$params = $reflectionMethod->getParameters();
						
						foreach($params as $param){
							$method['getParams'][]=array('name'=>$param->getName(),'defaultValue'=>$param->isOptional() ? $param->getDefaultValue() : null);
						}
						
						
						$methods[] = $method;
					}

				}

				$response['results'][]=array('name'=>$className, 'actions'=>$methods);		
			}
			
		}		
		
		echo $this->view->render('json', $response);
	}	
}