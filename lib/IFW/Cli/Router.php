<?php
namespace IFW\Cli;

use IFW;
use IFW\Exception\NotFound;

class Router extends IFW\Router{
	
	/**
	 * When building the route table this is set to the module currently processed
	 * so that we can record this.
	 * 
	 * @var string 
	 */
	private $currentModuleName;	
	
	private $routeCollections = [];
	private $optimizedRoutes;
	
	private $route;
	private $routeParams = [];
	private $routeParts = [];
	
	public function addRoutesFor($controllerName) {
		$routesCollection = new RouteCollection($controllerName, $this->currentModuleName);
		$this->routeCollections[] = $routesCollection;
		
		return $routesCollection;
	}
	
	public function run() {
		
		$command = IFW::app()->getCommand();
		
		$route = $command->getRoute();
		
		$this->route = trim($route,	'/');

		$this->routeParts = explode('/', $this->route);

		$this->walkRoute($this->optimizedRoutes);
	}
	
	
	public function initRoutes() {
		$this->optimizedRoutes = IFW::app()->getCache()->get('cliroutes');
		if(!$this->optimizedRoutes) {
			
			foreach(IFW::app()->getModules() as $moduleName) {				
				$this->currentModuleName = $moduleName;				
				$moduleName::defineCliRoutes($this);
			}
			
			$this->optimizeRoutes();
			IFW::app()->getCache()->set('cliroutes', $this->optimizedRoutes);
		}
	}
	
	private function optimizeRoutes() {
		foreach($this->routeCollections as $routeCollection){
			$controller = $routeCollection->getControllerName();
			$moduleName = $routeCollection->getModuleName();
			
			$routes = $routeCollection->getRoutes();
			
			foreach($routes as $route => $action) {				
				$routeParts = explode('/', $route);				
				$this->addRouteParts($routeParts, $action, $controller, $moduleName);
			}
		}
	}
	
	private function addRouteParts($routeParts, $action, $controller, $moduleName){
//		if(!isset($this->_optimizedRoutes[$method])){
//			$this->_optimizedRoutes[$method] = [];
//		}
		
		$cur = [
				'children' => &$this->optimizedRoutes
		];
		
		//eg /bands/:bandId/albums/:albumId
		//At the bands we store bandId as routeParams and at albums we store albumId.
		//But the total of route params is 2 for this route.		
		$totalRouteParams = 0; 
		
		foreach($routeParts as $part) {
			
//			if(empty($part)) {
//				var_dump($routeParts);
//				throw new \Exception();
//			}
			
			if(substr($part, 0, 1) != ':'){			
				if(!isset($cur['children'][$part])) {
					$cur['children'][$part] = [						
						'children' => [],
						'routeParams' => []
					];
				}
				
				if(!isset($cur['children'][$part])) {
					$cur['children'][$part] = [						
						'routeParams' => [],
						'actions' => [], //indexed with no. of params
					];
				}

				$cur = &$cur['children'][$part];
			}else
			{
				//route parameter
				$routeParam = substr($part, 1);
				if(!in_array($routeParam, $cur['routeParams'])){
					$cur['routeParams'][] = $routeParam;
				}
				
				$totalRouteParams++;
			}
		}	
		$cur['actions'][$totalRouteParams] = [$controller, $action, $moduleName];		
	}
	
	
	private function walkRoute($routes) {

		$routePart = array_shift($this->routeParts);
		
		if(!isset($routes[$routePart])){
			throw new NotFound("Route $routePart not found! " . var_export($routes, true));
		}
		$config = $routes[$routePart];
		
		/* eg:
		 * array(2) {
    ["methods"]=>
    array(4) {
      ["GET"]=>
      array(2) {
        ["routeParams"]=>
        array(0) {
        }
        ["actions"]=>
        array(1) {
          [0]=>
          array(2) {
            [0]=>
            string(46) "GO\Core\Auth\Browser\Controller\AuthController"
            [1]=>
            string(10) "isLoggedIn"
          }
        }
      }
		 */
		
		
		$this->extractRouteParams($config);		

		if (!empty($this->routeParts)) {
			return $this->walkRoute($config['children']);
		} else {			
			$action = $this->getAction($config);
			
			//send router parameters and GET parameters together
			$params = array_merge($this->routeParams, IFW::app()->getCommand()->getArguments());

			$this->currentModuleName = $action[2];
			
			$controller = new $action[0]();
			$controller->run(strtolower($action[1]), $params);
		}
		
	}
	
	/**
	 * Get the name of the module the router directed us to.
	 * 
	 * @param string
	 */
	public function getModuleName() {
		return $this->currentModuleName;
	}
	
	
	/**
	 * The fill find the controller and controller action method based on the number
	 * of route parameters and HTTP request method (PUT, POST, GET etc.)
	 * 
	 * @param type $config
	 * @param type $method
	 * @return type
	 * @throws HttpException
	 */
	private function getAction($config) {
		$paramCount = count($this->routeParams);
			
		if (empty($config['actions'][$paramCount])) {
//				var_dump($config['methods']['GET']);
//				throw new HttpException(405, "HTTP ".$method." not allowed. Only ".implode(',', array_keys($config['methods']))." are supported");
//				throw new Exception("No action defined for this route!");
			throw new NotFound("No action defined for route ".$this->route." params: ".var_export($this->routeParams, true));
		}
		
		return $config['actions'][$paramCount];
	}

	/**
	 * Extracts route parameters
	 * For example in /contact/:contactId ":contactId" is a route parameter.
	 * 
	 * So when /contact/1 is requested $this->routeParams['contactId']=1; will be set by this function.
	 * 
	 * The part is shifted off the route parts array.
	 * 
	 * @param array $config
	 * @param string $method
	 * @return boolean false if there is no configuration for the given HTTP method.
	 */
	private function extractRouteParams($config) {
		
		//HTTP method has no routes
		
		foreach ($config['routeParams'] as $paramName) {
			
			if(empty($this->routeParts)){
				break;
			}
			
			$part = array_shift($this->routeParts);			
			if(isset($config['children'][$part])){
			
				//put back the part
				array_unshift($this->routeParts, $part);
				
				// If there's an exact child match.
				// Like 0 here matches both rules. In this case the rule with the exact match wins.
				//
				// Rules example:
				// ->get('auth/users/0','new')
				// ->get('auth/users/:userId','read')
				
				break;
			}
			
			//add the param
			$this->routeParams[$paramName] = $part;
		}
	}

	public function getRoute() {
		return $this->route;
	}

	public function getRouteParams() {
		return $this->routeParams;
	}

}