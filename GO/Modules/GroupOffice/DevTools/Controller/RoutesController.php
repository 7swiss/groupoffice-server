<?php

namespace GO\Modules\GroupOffice\DevTools\Controller;

use IFW;
use GO\Core\Controller;

class RoutesController extends Controller {
	
	protected function checkAccess() {
		return true;
	}

	public function actionMarkdown() {
		//{@link http://intermesh.nl description}		
		
		header('Content-Type: text/plain');
		
		echo " * | Method | Route | Controller     |\n";
		echo " * |--------|-------|----------------|\n";
 	
		foreach(GO()->getRouter()->getRouteCollections() as $routeCollection){
			$controller = $routeCollection->getControllerName();
			
			$routes = $routeCollection->getRoutes();
			
			foreach($routes as $route){
				$method = $route[0];
				$path = $route[1];
				$action = "action".$route[2];				
				
				try {
					echo ' * |'.$method.' | '.$path.' | {@link '.$controller."::".$this->findMethodCaseInsensitive($controller, $action)."}|\n";
				}catch(\Exception $e) {
					echo "\n\nERROR: ".$controller."::".$action." : ".$e->getMessage()."\n\n";
				}
			}
		}
	}
	
	private function findMethodCaseInsensitive($class, $method) {
		$reflection = new \ReflectionClass($class);		
		$method = $reflection->getMethod($method);		
		return $method->getName();
	}
	/**
	 * Get all routes
	 * 
	 * 
	 * @param array $routes
	 * @param string $prefix
	 * @param string[]
	 */
	private function getRoutesAsString($routes=null, $prefix = ''){
		
		$routeStr = [];
		
		if(!isset($routes)){
			$routes = GO()->getRouter()->getRoutes();
		}
		
		foreach($routes as $route => $config){
			
				
			$str = $prefix.'/'.$route;
			if(isset($config['controller'])){
				$routeStr[$str] = $config;
			}

			if(isset($config['routeParams'])){
				foreach($config['routeParams'] as $p){
					$str .= '/['.$p.']';

					if(isset($config['controller'])){
						$routeStr[$str] = $config;
					}
				}
			}

			
			if(isset($config['children'])){
				$routeStr = array_merge($routeStr, $this->getRoutesAsString($config['children'], $str));
			}
		}
		
		return $routeStr;
		
	}
}