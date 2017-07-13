<?php

namespace IFW\Web;

/**
 * Holds route definitions for a Controller.
 * 
 * @see Router
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class RouteCollection {
	/**
	 *
	 * @var string 
	 */
	private $controllerName;
	
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
	 * Add a custom route
	 * 
	 * @todo Check if route already exists
	 * 
	 * @param string $method GET, PUT, POST, DELETE etc. or * for any method.
	 * @param string $route eg. auth/users
	 * @param string $action The controller action without the "action" prefix. eg. "store" leads to method actionStore. The actions are case-insensitive.
	 * @return \IFW\Web\RouteCollection
	 */
	public function addRoute($method, $route, $action) {
		$this->routes[] = [$method, $route, $action];
		
		return $this;
	}
	
	/**
	 * Add a route that handles a GET request
	 * 
	 * Example:
	 * 
	 * ```````````````````````````````````````````````````````````````````````````
	 * BrowserController::routes()
	 *			->get('auth/users', 'store');
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * @param string $route eg. auth/users
	 * @param string $action The controller action without the "action" prefix. eg. "store" leads to method actionStore. The actions are case-insensitive.
	 * @return self
	 */
	public function get($route, $action){
		return $this->addRoute('GET', $route, $action);
	}
	
	/**
	 * Add a route that handles a POST request
	 * 
	 * Example:
	 * 
	 * ```````````````````````````````````````````````````````````````````````````
	 * BrowserController::routes()
	 *			->get('auth/users', 'create');
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * @param string $route eg. auth/users
	 * @param string $action The controller action without the "action" prefix. eg. "store" leads to method actionStore. The actions are case-insensitive.
	 * @return self
	 */
	public function post($route, $action){
		return $this->addRoute('POST', $route, $action);
	}
	
	/**
	 * Add a route that handles a POST request
	 * 
	 * Example:
	 * 
	 * ```````````````````````````````````````````````````````````````````````````
	 * AuthController::routes()
	 *			->get('auth/users/:userId', 'update');
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * @param string $route eg. auth/users
	 * @param string $action The controller action without the "action" prefix. eg. "store" leads to method actionStore. The actions are case-insensitive.
	 * @return self
	 */
	public function put($route, $action){
		return $this->addRoute('PUT', $route, $action);
	}
	
	/**
	 * Add a route that handles a POST request
	 * 
	 * Example:
	 * 
	 * ```````````````````````````````````````````````````````````````````````````
	 * AuthController::routes()
	 *			->delete('auth/users/:userId', 'delete');
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * @param string $route eg. auth/users
	 * @param string $action The controller action without the "action" prefix. eg. "store" leads to method actionStore. The actions are case-insensitive.
	 * @return self
	 */
	public function delete($route, $action){
		return $this->addRoute('DELETE', $route, $action);
	}
	
	
	/**
	 * Shorthand function for defining CRUD actions.
	 * 
	 * Example:
	 * 
	 * ```````````````````````````````````````````````````````````````````````````
	 * ContactController::routes()
	 *	->crud('contacts', 'contactId');
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * Is short for:
	 * 
	 * ```````````````````````````````````````````````````````````````````````````
	 * ContactController::routes()
				->get('contacts', 'store')
				->get('contacts/0','new')
				->get('contacts/:contactId','read')
				->put('contacts/:contactId', 'update')
				->post('contacts', 'create')
				->delete('contacts/:contactId','delete')
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * @param string $route eg. 'contacts'
	 * @return self
	 */
	public function crud($route, $paramName){
		$this->get($route, 'store')
				->get($route.'/0','newInstance')
				->get($route.'/:'.$paramName,'read')
				->put($route.'/:'.$paramName, 'update')
				->post($route, 'create')				
				->put($route, 'multiple')
				->delete($route.'/:'.$paramName,'delete');	
		
		return $this;
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
}