<?php
namespace IFW\Cli;

class RouteCollection {
		/**
	 *
	 * @var string 
	 */
	private $controllerName;
	
	/**
	 *
	 * @var string 
	 */
	private $moduleName;
	
	/**
	 *
	 * @var string[] 
	 */
	private $routes = [];
	
	public function __construct($controllerName, $moduleName) {
		$this->controllerName = $controllerName;
		$this->moduleName = $moduleName;
	}
	
		/**
	 * Get the route definitions
	 * 
	 * @return array
	 */
	public function getRoutes(){
		return $this->routes;
	}
	
	/**
	 * Get the controller name the routes are for.
	 * 
	 * @param string
	 */
	public function getControllerName(){
		return $this->controllerName;
	}
	
	/**
	 * Get the module name the routes and controller belong to.
	 * 
	 * @param string
	 */
	public function getModuleName(){
		return $this->moduleName;
	}
	
	
	/**
	 * 
	 * Set a route
	 * 
	 * Example:
	 * 
	 * ``````````````````````````````````````````````````````````````````````````
	 * 	public static function defineCliRoutes(\IFW\Cli\Router $router) {
	 *		$router->addRoutesFor(SystemController::class)
	 *				->set('system/check', 'check')
	 *				->set('system/install', 'install')
	 *				->set('system/upgrade', 'upgrade');
	 *	}
	 * ```````````````````````````````````````````````````````````````````````````
	 * @param string $route
	 * @param string $method
	 * @return \IFW\Cli\RouteCollection
	 */
	public function set($route, $method) {
		$this->routes[$route] = $method;
		
		return $this;
	}
}
