<?php

if(!function_exists('GO')) {
	
 /**
	* Get the app
	* 
	* @return GO\Core\Web\App
	*/
	function GO() {
		return \IFW::app();
	}
}
