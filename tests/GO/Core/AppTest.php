<?php

namespace IFW;
use IFW;

/**
 * The App class is a collection of static functions to access common services
 * like the configuration, reqeuest, debugger etc.
 */
class AppTest extends \PHPUnit_Framework_TestCase{
	function testInit(){

		//\IFW::app()->init(require('config.php'));
		
		$this->assertEquals(IFW::app()->getConfig()->productName,"Group-Office 7.0");


	}
}
