<?php
/**
 * Get the app
 * 
 * @return GO\Core\Web\App
 */
if(!function_exists('GO')) {
	function GO() {
		return \IFW::app();
	}
}
