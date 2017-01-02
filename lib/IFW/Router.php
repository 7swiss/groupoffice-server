<?php
namespace IFW;

use IFW\Data\Object;
use IFW\Util\Url;

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
}