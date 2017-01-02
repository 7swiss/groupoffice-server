<?php
namespace IFW;

interface RouterInterface {

	/**
	 * Get the params passed in a route.
	 * 
	 * eg. /contacts/1 where 1 is a "contactId" would return ["contactId" => 1];
	 * 
	 * @return array ["paramName" => "value"]
	 */
	public function getRouteParams();	
	
	/**
	 * Finds the controller that matches the route and runs it.
	 */
	public function run();
}
