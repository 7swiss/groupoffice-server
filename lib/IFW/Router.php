<?php
namespace IFW;

use Exception;
use IFW;
use IFW\Data\Object;
use IFW\Exception\HttpException;
use IFW\Util\Url;
use ReflectionMethod;

abstract class Router extends Object implements RouterInterface{
	

	
	/**
	 * Generate a controller URL.
	 * 
	 * eg. buildUrl('auth/users', ['sortDirection' => 'DESC']); will build:
	 * 
	 * "/(path/to/index.php|aliastoindexphp)/auth/users&sortDirection=DESC"
	 *
	 * @param string $route To controller. eg. addressbook/contact/submit
	 * @param array $params eg. ['id' => 1, 'someVar' => 'someValue']
	 * @return Utl
	 * 
	 */
	public function buildUrl($route = '', $params = [], $absolute = false) {

		$url = Url::getBaseUrl($absolute);

		if (!empty($route)) {
			$url .= $route;
		}
		
		return new Url($url, $params);
	}
	
	/**
	 * Get the interface type
	 * 
	 * Typically Cli or Web but might be extended in the future.
	 * 
	 * @param string
	 */
	public function getInterfaceType() {
		$parts = explode('\\', $this->getClassName());
		
		return $parts[count($parts)-2];
	}
	
	/**
	 * Finds the controller that matches the given route and runs it.
	 */
	abstract public function run();
	
	/**
	 * The current route. 
	 * 
	 * eg. /contacts/1
	 * 
	 * @param string 
	 */
	abstract public function getRoute();
	
	/**
	 * Get the params passed in a route.
	 * 
	 * eg. /contacts/1 where 1 is a "contactId" would return ["contactId" => 1];
	 * 
	 * @return array ["paramName" => "value"]
	 */
	abstract public function getRouteParams();
	
	/**
	 * Get the name of the module the router directed us to.
	 * 
	 * @param string
	 */
	abstract public function getModuleName();
	
	
	/**
	 * Called on application initialization
	 */
	abstract public function initRoutes();	
	
	
	protected function getActionParams($controllerCls, $methodName) {
		
		if(!class_exists($controllerCls)){
			throw new Exception('Class  '.$controllerCls." defined in router but it doesn't exist");
		}
		
		$controller = new $controllerCls;
		
		if(!method_exists($controller, $methodName)){
			throw new Exception("Method '".$methodName."' defined in router but doesn't exist in controller '".$controllerCls."'");
		}
		
		$method = new ReflectionMethod($controller, $methodName);
		$rParams = $method->getParameters();		

		$methodArgs = [];	
		
		foreach ($rParams as $param) {			
			$arg = ['isOptional' => $param->isOptional(), 'default' => $param->isOptional() ? $param->getDefaultValue() : null];			
			$methodArgs[$param->getName()] = $arg;			
		}
		
		return $methodArgs;
	}
	
	/**
	 * Runs controller method with URL query and route params.
	 * 
	 * For an explanation about route params {@see Router::routeParams}
	 * 
	 * @param string $methodName
	 * @params array $routerParams A merge of route and query params
	 * @throws HttpException
	 */
	protected function callAction(Controller $controller, $methodName, array $methodParams,  array $requestParams){
	
		//call method with all parameters from the $_REQUEST object.
		$methodArgs = [];
		foreach ($methodParams as $paramName => $paramMeta) {
			if (!isset($requestParams[$paramName]) && !$paramMeta['isOptional']) {
				throw new HttpException(400, "Bad request. Missing argument '" . $paramName . "' for action method '" . get_class($controller) . "->" . $methodName . "'");
			}

			$methodArgs[] = isset($requestParams[$paramName]) ? $requestParams[$paramName] : $paramMeta['default'];
		}
		
		IFW::app()->getDebugger()->setSection(Debugger::SECTION_CONTROLLER);
		
		$controller->checkAccess();
		
		call_user_func_array([$controller, $methodName], $methodArgs);
	}
}