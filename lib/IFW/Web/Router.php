<?php
namespace IFW\Web;

use IFW;
use IFW\Exception\HttpException;

/**
 * The router routes requests to their controller actions
 *
 * Some core routes are created in this class in the private function "_collectRoutes".
 * Each module can add routes too in their module file. {@see \IFW\AbstractModule}.
 * 
 * `````````````````````````````````````````````````````````````````````````````
 * <?php
 * namespace GO\Modules\Bands;
 * 
 * use IFW\AbstractModule;
 * use GO\Modules\Bands\Controller\BandController;
 * use GO\Modules\Bands\Controller\HelloController;
 * 
 * class BandsModule extends AbstractModule {
 * 	
 * 	const PERMISSION_CREATE = 1;
 * 	
 * 	public function routes() {
 * 		BandController::routes()
 * 				->get('bands', 'store')
 * 				->get('bands/0','new')
 * 				->get('bands/:bandId','read')
 * 				->put('bands/:bandId', 'update')
 * 				->post('bands', 'create')
 * 				->delete('bands/:bandId','delete');
 * 		
 * 		HelloController::routes()
 * 				->get('bands/hello', 'name');
 * 	}
 * }
 * `````````````````````````````````````````````````````````````````````````````
 * 
 * 
 * == Catch all route:
 * 
 * Using the stop parameter of addRoute you can define a catch all.
 * Using a '*' as prefix to a parameter it will include the whole path after it.
 * 
 * So when calling /api/devtools/test/bar/foo/foobar the following route will
 * call the action with: $path = 'bar/foo/foobar';
 *
 * `````````````````````````````````````````````````````````````````````````````
 * 
 * ->addRoute('*', 'devtools/test/*path/foo/bar', 'test', true);
 * 
 * `````````````````````````````````````````````````````````````````````````````
 * 
 * {@see \IFW\Controller}
 * 
 * 
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class Router extends IFW\Router {

	/**
	 *
	 * @var RouteCollection[] 
	 */
	private $routeCollections = [];

	/**
	 * The current route. 
	 * 
	 * eg. /contacts/1
	 * 
	 * @var string 
	 */
	private $route;

	/**
	 * Get the params passed in a route.
	 * 
	 * eg. /contacts/1 where 1 is a "contactId" would return ["contactId" => 1];
	 * 
	 * @var array
	 */
	private $routeParams = [];
	private $routeParts;

	/**
	 * When building the route table this is set to the module currently processed
	 * so that we can record this.
	 * 
	 * @var string 
	 */
	private $currentModuleName;

	/**
	 * Add a {@see \IFW\Controller} to the router
	 * 
	 * This function returns a RouteCollection object so you can add 
	 * route mappings to controller methods.
	 * 
	 * <code>
	 * 
	 * $router->addController(AuthController::class)				
	 * 			->get('auth', 'isLoggedIn')
	 * 			->post('auth', 'login')
	 * 			->delete('auth', 'logout')						
	 * 			->post('auth/users/:userId/switch-to', 'switchTo');
	 * 
	 * </code>
	 * 
	 * @param $controllerName
	 * @return RouteCollection 
	 */
	public function addRoutesFor($controllerName) {
		$routesCollection = new RouteCollection($controllerName, $this->currentModuleName);
		$this->routeCollections[] = $routesCollection;

		return $routesCollection;
	}

	private $optimizedRoutes = ['children' => [], 'methods' => []];

	/**
	 * Converts route definitions to an optimized array for the router
	 * 
	 * 
	 * @return array
	 */
	private function optimizeRoutes() {
		foreach ($this->routeCollections as $routeCollection) {
			$controller = $routeCollection->getControllerName();
			$moduleName = $routeCollection->getModuleName();

			$routes = $routeCollection->getRoutes();

			foreach ($routes as $route) {
				$method = $route[0];
				$action = $route[2];
				$routeParts = explode('/', $route[1]);

				$this->addRouteParts($routeParts, $method, $action, $controller, $moduleName);
			}
		}

//		var_dump($this->optimizedRoutes);
		return $this->optimizedRoutes;
	}

	/**
	 * Get all the route collections
	 * 
	 * Each controller has a route collection
	 * 
	 * @return RouteCollection[]
	 */
	public function getRouteCollections() {
		return $this->routeCollections;
	}

	/**
	 * Checks if this route part is a parameter. Prefixed with : or *.
	 * 
	 * @param string $part
	 * @return bool
	 */
	private function isParamPart($part) {
		$firstChar = substr($part, 0, 1);

		return $firstChar == ':' || $firstChar == '*';
	}

	private function addRouteParts($routeParts, $method, $action, $controller, $moduleName) {

		$cur = &$this->optimizedRoutes;

		foreach ($routeParts as $part) {
			if (!isset($cur['children'][$part])) {
				$cur['children'][$part] = [
						'methods' => [],
						'children' => []
				];
			}
			$cur = &$cur['children'][$part];
		}

		$cur['methods'][$method] = [
				'controller' => $controller,
				'action' => $action,
				'moduleName' => $moduleName];
	}

	public function getInterfaceType() {
		return 'Web';
	}

	/**
	 * Called on application initialization
	 */
	public function initRoutes() {
		$this->optimizedRoutes = IFW::app()->getCache()->get('routes');
		if (!$this->optimizedRoutes) {

			IFW::app()->debug("Initializing routes");

			foreach (IFW::app()->getModules() as $moduleName) {
				$this->currentModuleName = $moduleName;
				$moduleName::defineWebRoutes($this);
			}

			$this->optimizeRoutes();
			IFW::app()->getCache()->set('routes', $this->optimizedRoutes);
		}
	}

	/**
	 * The current route. 
	 * 
	 * eg. /contacts/1
	 * 
	 * @param string 
	 */
	public function getRoute() {
		return $this->route;
	}

	/**
	 * Finds the controller that matches the route and runs it.
	 */
	public function run() {
		
		\IFW::app()->getDebugger()->setSection(\IFW\Debugger::SECTION_ROUTER);
		
		$this->route = IFW::app()->getRequest()->getRoute();

		if (empty($this->route)) {
			IFW::app()->getResponse()->redirect($this->buildUrl('system/check'));
		}

		if(!$this->walkRoute($this->optimizedRoutes, explode('/', $this->route))) {
			throw new \IFW\Exception\NotFound("Route '" . IFW::app()->getRequest()->getRoute() . "' not defined for method: " . IFW::app()->getRequest()->getMethod() . '.');
		}
	}

	/**
	 * Get the params passed in a route.
	 * 
	 * eg. /contacts/1 where 1 is a "contactId" would return ["contactId" => 1];
	 * 
	 * @return array ["paramName" => "value"]
	 */
	public function getRouteParams() {
		return $this->routeParams;
	}

	private function finishRoute($routes, $routeParams) {

		if (isset($routes['methods'][\IFW::app()->getRequest()->method])) {
			$action = $routes['methods'][\IFW::app()->getRequest()->method];
		} else if (isset($routes['methods']['*'])) {
			$action = $routes['methods']['*'];
		} else {
			return false;
		}

		$this->routeParams = $routeParams;

		$params = array_merge($this->routeParams, $_GET);

		$controller = new $action['controller'];
		$controller->run(strtolower($action['action']), $params);

		return true;
	}

	/**
	 * 
	 * @param string[] $routes The available routes in the router
	 * @throws HttpException
	 */
	private function walkRoute($routes, $routeParts, $routeParams = []) {

		//eg. contacts
		$routePart = array_shift($routeParts);
		if ($routePart === null) {
			//end of the route
			return $this->finishRoute($routes, $routeParams);
		}

		if (isset($routes['children'][$routePart])) {
			//exact match. Follow the route			
			if ($this->walkRoute($routes['children'][$routePart], $routeParts, $routeParams)) {
				return true;
			}
		}

		//try the variables
		foreach ($routes['children'] as $routeParamName => $childRoutes) {
			if ($this->isParamPart($routeParamName)) {

				if (substr($routeParamName, 0, 1) == '*') {
					//this parameter will swallow the whole route and pass it as a aprameter
					if (!empty($this->routeParts)) {
						$routePart .= '/' . implode('/', $routeParts);
						$this->routeParts = [];
					}
				}

				if ($this->walkRoute($childRoutes, $routeParts, array_merge($routeParams, [ltrim($routeParamName, ':*') => $routePart]))) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Get the name of the module the router directed us to.
	 * 
	 * @param string eg. 
	 */
	public function getModuleName() {
		return $this->currentModuleName;
	}
}