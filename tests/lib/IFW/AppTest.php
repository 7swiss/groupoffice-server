<?php 
namespace IFW;

/**
 * The App class is a collection of static functions to access common services
 * like the configuration, reqeuest, debugger etc.
 */
class AppTest extends \PHPUnit\Framework\TestCase{
	function testInit(){

		
		$this->assertEquals(\IFW::app()->getConfig()->productName,"Group-Office 7.0");


	}
}
