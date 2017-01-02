<?php
namespace IFW\Util;

use IFW;
use IFW\Data\ArrayableInterface;

/**
 * URL Object
 * 
 * Used to contruct URL's
 */
class Url implements ArrayableInterface{
	
	private $location;
	
	private $params;
	
	/**
	 * Create an URL object
	 * 
	 * @param string $location eg. http://localhost/ or /relative
	 * @param array $params
	 */
	public function __construct($location, array $params) {
		$this->location = $location;
		$this->params = $params;
	}
	
	/**
	 * Adds a query paramter
	 * 
	 * @param string $name
	 * @param string $value
	 */
	public function addParam($name, $value) {
		$this->params[$name] = $value;
	}
	
	/**
	 * Remove query parameter
	 * 
	 * @param string $name
	 */
	public function removeParam($name) {
		unset($this->params[$name]);
	}
	
	public function __toString() {
	
		$url = $this->location;
		
		if (!empty($this->params)) {
			
			$sep = '?';

			foreach ($this->params as $key => $value) {				
				$url .= $sep.$key . '=' . urlencode($value);				
				$sep ='&';
			}
		}

		return $url;
	}
	
	/**
	 * Detect the base URL of this server
	 * 
	 * @param boolean $absolute Create an absolute URL
	 * @param string
	 */
	public static function getBaseUrl($absolute) {

		if($absolute) {
			$https = IFW::app()->getRequest()->isHttps();
			$url = $https ? 'https://'.$_SERVER['HTTP_HOST'] : 'http://'.$_SERVER['HTTP_HOST'];
			if ((!$https && $_SERVER["SERVER_PORT"] != 80) || ($https && $_SERVER["SERVER_PORT"] != 443)){
				$url .= ":".$_SERVER["SERVER_PORT"];
			}
		}else
		{
			$url = '';
		}
		$url .= $_SERVER['SCRIPT_NAME'].'/';

		return $url;
	}
	
	public function toArray($attributes = null) {
		return (string) $this;
	}
}