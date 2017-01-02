<?php
use IFW\App;
/**
 * Global IFW class with static functions to access the application instance.
 * 
 * eg. IFW::app()->config() access the application configuration
 */
class IFW {
	private static $app;
	
	/**
	 * Register the application instance
	 * 
	 * @param App $app
	 * @throws Exception
	 */
	public static function setApp(App $app) {
		if(isset(self::$app)){
			throw new Exception("You can only register one application");
		}
		
		self::$app = $app;
	}
	
	/**
	 * Get the application instance
	 * 
	 * @return \IFW\Web\App|IFW\Cli\App
	 */
	public static function app() {
		return self::$app;
	}
}